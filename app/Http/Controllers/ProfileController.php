<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\Branch;
use App\Models\User;
use App\Models\SaleItem;
use App\Models\CommissionDisbursement;
use App\Models\ActivityLog;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $user->load('branch.region', 'fieldAgentProfile');

        $isFieldAgent = (bool) $user->fieldAgentProfile;
        // Commission stats from User model (all users who earn commission)
        $totalEarned = (float) ($user->total_commission_earned ?? 0);
        $availableBalance = (float) ($user->commission_available_balance ?? 0);
        $commissionStats = [
            'total_earned' => $totalEarned,
            'total_withdrawn' => $totalEarned - $availableBalance,
            'available_balance' => $availableBalance,
        ];

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        // Get user's recent activity logs
        $activityLogs = $user->activityLogs()->with('model')->latest()->take(10)->get();

        $dashboardBackgrounds = config('dashboard_backgrounds', []);
        $themes = config('themes', []);

        return view('profile.show', compact('user', 'branches', 'isFieldAgent', 'commissionStats', 'activityLogs', 'dashboardBackgrounds', 'themes'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->phone = $validated['phone'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }

    public function updateDashboardBackground(Request $request)
    {
        $rules = [
            'dashboard_background_type' => 'nullable|in:color,pattern,image',
            'dashboard_background_value' => 'nullable|string|max:255',
        ];

        $isCustomImage = $request->input('dashboard_background_type') === 'image'
            && $request->input('dashboard_background_value') === 'custom';

        if ($isCustomImage && $request->hasFile('dashboard_background_image')) {
            $rules['dashboard_background_image'] = 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120';
        }

        $validated = $request->validate($rules);

        $user = Auth::user();
        $type = $validated['dashboard_background_type'] ?? null;
        $value = $validated['dashboard_background_value'] ?? null;

        if ($type === 'image' && $value === 'custom' && $request->hasFile('dashboard_background_image')) {
            $file = $request->file('dashboard_background_image');
            $dir = 'dashboard-backgrounds/' . $user->id;
            if ($user->dashboard_background_custom_path) {
                Storage::disk('public')->delete($user->dashboard_background_custom_path);
            }
            $path = $file->store($dir, 'public');
            $user->dashboard_background_type = 'image';
            $user->dashboard_background_value = 'custom';
            $user->dashboard_background_custom_path = $path;
            $user->save();
            return redirect()->route('profile.show')->with('success', 'Custom background image uploaded. Check your dashboard!');
        }

        $user->dashboard_background_type = $type !== '' && $type !== null ? $type : null;
        $user->dashboard_background_value = $user->dashboard_background_type ? ($value !== '' ? $value : null) : null;
        if ($user->dashboard_background_type !== 'image' || $user->dashboard_background_value !== 'custom') {
            $user->dashboard_background_custom_path = null;
        }
        $user->save();

        return redirect()->route('profile.show')->with('success', 'Dashboard background updated. Check your dashboard!');
    }

    public function updateTheme(Request $request)
    {
        $themes = config('themes', []);
        $validated = $request->validate([
            'theme' => 'required|string|max:50|in:' . implode(',', array_keys($themes)),
        ]);

        $user = Auth::user();
        $user->theme = $validated['theme'];
        $user->save();

        return redirect()->route('profile.show')->with('success', 'Theme updated.');
    }

    /**
     * Update profile picture with cropping
     */
    public function updateProfilePicture(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'profile_picture' => 'required|string', // Base64 encoded image
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'width' => 'required|numeric|min:100',
            'height' => 'required|numeric|min:100',
        ]);

        try {
            // Decode base64 image (already cropped to 300x300 by frontend)
            $imageData = $validated['profile_picture'];
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif

                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    return back()->withErrors(['profile_picture' => 'Invalid image type.']);
                }

                $imageData = base64_decode($imageData);

                if ($imageData === false) {
                    return back()->withErrors(['profile_picture' => 'Invalid image data.']);
                }

                // Create image from data using GD (already cropped to 300x300)
                $sourceImage = imagecreatefromstring($imageData);
                if ($sourceImage === false) {
                    return back()->withErrors(['profile_picture' => 'Failed to process image.']);
                }

                // The image is already cropped to 300x300 by the frontend
                // Just ensure it's exactly 300x300 and save it
                $sourceWidth = imagesx($sourceImage);
                $sourceHeight = imagesy($sourceImage);

                // Create final image (300x300)
                $finalImage = imagecreatetruecolor(300, 300);

                // Copy and resize if needed (should already be 300x300, but ensure it)
                imagecopyresampled(
                    $finalImage,
                    $sourceImage,
                    0,
                    0,
                    0,
                    0,
                    300,
                    300,
                    $sourceWidth,
                    $sourceHeight
                );

                // Delete old profile picture if exists
                if ($user->profile_picture) {
                    Storage::disk('public')->delete($user->profile_picture);
                }

                // Save new profile picture
                $filename = 'profile-pictures/' . $user->id . '/' . uniqid() . '.jpg';
                $path = storage_path('app/public/' . $filename);

                // Ensure directory exists
                $directory = dirname($path);
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Save as JPEG
                imagejpeg($finalImage, $path, 90);

                // Free memory
                imagedestroy($sourceImage);
                imagedestroy($finalImage);

                // Update user profile picture
                $user->profile_picture = $filename;
                $user->save();

                // Log activity
                ActivityLog::log(
                    Auth::id(),
                    'profile_picture_updated',
                    "Updated profile picture",
                    User::class,
                    $user->id,
                    []
                );

                if ($request->expectsJson()) {
                    return response()->json(['success' => true, 'message' => 'Profile picture updated successfully.']);
                }
                return redirect()->route('profile.show')->with('success', 'Profile picture updated successfully.');
            } else {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Invalid image format.'], 422);
                }
                return back()->withErrors(['profile_picture' => 'Invalid image format.']);
            }
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update profile picture: ' . $e->getMessage()], 500);
            }
            return back()->withErrors(['profile_picture' => 'Failed to update profile picture: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete profile picture
     */
    public function deleteProfilePicture()
    {
        $user = Auth::user();

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
            $user->profile_picture = null;
            $user->save();

            // Log activity
            ActivityLog::log(
                Auth::id(),
                'profile_picture_deleted',
                "Deleted profile picture",
                User::class,
                $user->id,
                []
            );

            return redirect()->route('profile.show')->with('success', 'Profile picture deleted successfully.');
        }

        return redirect()->route('profile.show')->with('error', 'No profile picture to delete.');
    }
}
