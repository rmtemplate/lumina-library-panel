<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Role;
use App\Services\Admin\LookupService;
use App\Services\Admin\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array($request->integer('per_page', 20), [10, 20, 30, 50, 100], true) ? $request->integer('per_page', 20) : 20;
        $roles = Role::withCount(['users', 'permissions'])->orderByDesc('sort_order')->paginate($perPage)->withQueryString();
        return view('admin.roles.index', compact('roles'));
    }

    public function create(LookupService $lookup): View
    {
        return view('admin.roles.create', ['permissions' => $lookup->activePermissions()]);
    }

    public function store(RoleRequest $request, RoleService $service): RedirectResponse
    {
        $role = $service->create($request->validated() + [
            'is_active' => $request->boolean('is_active', true),
            'is_admin_role' => $request->boolean('is_admin_role', false),
            'is_staff_role' => $request->boolean('is_staff_role', false),
            'scope' => $request->input('scope', 'branch'),
            'sort_order' => $request->integer('sort_order', 0),
        ], $request->user()->id);

        return redirect()->route('admin.roles.index')->with('success', "Role {$role->name} berhasil dibuat.");
    }

    public function edit(Role $role, LookupService $lookup): View
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => $lookup->activePermissions(),
        ]);
    }

    public function update(RoleRequest $request, Role $role, RoleService $service): RedirectResponse
    {
        $service->update($role, $request->validated() + [
            'is_active' => $request->boolean('is_active', true),
            'is_admin_role' => $request->boolean('is_admin_role', false),
            'is_staff_role' => $request->boolean('is_staff_role', false),
            'scope' => $request->input('scope', 'branch'),
            'sort_order' => $request->integer('sort_order', 0),
        ], $request->user()->id);

        return redirect()->route('admin.roles.index')->with('success', "Role {$role->name} berhasil diperbarui.");
    }

    public function destroy(Role $role, RoleService $service): RedirectResponse
    {
        $service->delete($role);
        return redirect()->route('admin.roles.index')->with('success', "Role {$role->name} berhasil dihapus.");
    }
}
