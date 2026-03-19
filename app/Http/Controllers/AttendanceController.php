<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::query()->with('user');
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->get('date_to'));
        }

        $rows = $query->latest('attendance_date')->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('attendance.index', compact('rows', 'users'));
    }

    public function create()
    {
        return view('attendance.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attendance_date' => 'required|date',
            'clock_in_at' => 'nullable|date',
            'clock_out_at' => 'nullable|date|after_or_equal:clock_in_at',
            'status' => 'required|string|in:present,absent,leave,half_day',
            'notes' => 'nullable|string|max:2000',
        ]);

        Attendance::updateOrCreate(
            ['user_id' => $request->user()->id, 'attendance_date' => $validated['attendance_date']],
            [
                'clock_in_at' => $validated['clock_in_at'] ?? null,
                'clock_out_at' => $validated['clock_out_at'] ?? null,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return redirect()->route('attendance.index')->with('success', 'Attendance saved.');
    }
}
