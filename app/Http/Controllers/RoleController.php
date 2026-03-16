<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::with('permissions')->withCount(['users', 'permissions']);

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $roles = $query->latest()->paginate(15)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => Role::count(),
            'active' => Role::where('is_active', true)->count(),
            'inactive' => Role::where('is_active', false)->count(),
            'with_permissions' => Role::has('permissions')->count(),
        ];

        return view('roles.index', compact('roles', 'stats'));
    }

    public function create()
    {
        $permissions = Permission::where('is_active', true)
            ->where('module', '!=', 'profile')
            ->orderBy('module')->orderBy('name')->get();
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'slug' => 'nullable|string|max:255|unique:roles,slug',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['is_active'] = $request->has('is_active');

        $role = Role::create($validated);

        // Attach permissions
        if ($request->has('permissions')) {
            $role->permissions()->attach($request->permissions);
        }

        // Log activity
        ActivityLog::log(
            Auth::id(),
            'role_created',
            "Created role: {$role->name}",
            Role::class,
            $role->id,
            ['role_name' => $role->name]
        );

        return redirect()->route('roles.show', $role)->with('success', 'Role created successfully.');
    }

    public function show(Role $role)
    {
        $role->load(['permissions', 'users.branch']);
        $permissions = Permission::where('is_active', true)
            ->where('module', '!=', 'profile')
            ->orderBy('module')->orderBy('name')->get();
        return view('roles.show', compact('role', 'permissions'));
    }

    public function edit(Role $role)
    {
        // Prevent editing protected roles
        if ($role->is_protected) {
            return redirect()->route('roles.show', $role)
                ->with('error', 'Protected roles cannot be edited.');
        }

        $permissions = Permission::where('is_active', true)
            ->where('module', '!=', 'profile')
            ->orderBy('module')->orderBy('name')->get();
        $role->load('permissions');
        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        // Prevent updating protected roles
        if ($role->is_protected) {
            return redirect()->route('roles.show', $role)
                ->with('error', 'Protected roles cannot be modified.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'slug' => 'nullable|string|max:255|unique:roles,slug,' . $role->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['is_active'] = $request->has('is_active');

        $role->update($validated);

        // Sync permissions
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        } else {
            $role->permissions()->detach();
        }

        // Log activity
        ActivityLog::log(
            Auth::id(),
            'role_updated',
            "Updated role: {$role->name}",
            Role::class,
            $role->id,
            ['role_name' => $role->name]
        );

        return redirect()->route('roles.show', $role)->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        // Prevent deletion of protected roles
        if ($role->is_protected) {
            return redirect()->route('roles.index')
                ->with('error', 'Protected roles cannot be deleted.');
        }

        // Prevent deletion if role has users
        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete role that has assigned users.');
        }

        $roleName = $role->name;
        $role->permissions()->detach();
        $role->delete();

        // Log activity
        ActivityLog::log(
            Auth::id(),
            'role_deleted',
            "Deleted role: {$roleName}",
            Role::class,
            null,
            ['role_name' => $roleName]
        );

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
