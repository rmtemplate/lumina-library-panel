<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Admin\BranchAccessService;
use App\Services\Admin\LookupService;
use App\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request, UserService $service, LookupService $lookup): View
    {
        return view('admin.users.index', [
            'users' => $service->paginate($request->only(['search', 'role', 'status', 'branch_id', 'per_page']), $request->user()),
            'roles' => $lookup->manageableRoles($request->user()),
            'branches' => $lookup->activeBranches($request->user()),
        ]);
    }

    public function create(Request $request, LookupService $lookup): View
    {
        return view('admin.users.create', [
            'roles' => $lookup->manageableRoles($request->user()),
            'profileTypes' => $lookup->activeProfileTypes(),
            'branches' => $lookup->activeBranches($request->user()),
        ]);
    }

    public function store(StoreUserRequest $request, UserService $service): RedirectResponse
    {
        $user = $service->create($request->validated(), $request->user()->id, $request->user());
        return redirect()->route('admin.users.index')->with('success', "Pengguna {$user->fullname} berhasil dibuat.");
    }

    public function show(Request $request, User $user, BranchAccessService $access): View
    {
        $access->assertCanAccessBranch($request->user(), $user->branch_id);
        return view('admin.users.show', ['user' => $user->load(['roles', 'profileType', 'branch', 'borrowings', 'fines'])]);
    }

    public function edit(Request $request, User $user, LookupService $lookup, BranchAccessService $access): View
    {
        $access->assertCanAccessBranch($request->user(), $user->branch_id);
        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => $lookup->manageableRoles($request->user()),
            'profileTypes' => $lookup->activeProfileTypes(),
            'branches' => $lookup->activeBranches($request->user()),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UserService $service): RedirectResponse
    {
        $service->update($user, $request->validated(), $request->user()->id, $request->user());
        return redirect()->route('admin.users.index')->with('success', "Data pengguna {$user->fullname} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $user, UserService $service): RedirectResponse
    {
        $service->delete($user, $request->user()->id, $request->user());
        return redirect()->route('admin.users.index')->with('success', "Pengguna {$user->fullname} berhasil dihapus.");
    }

    public function toggleStatus(Request $request, User $user, UserService $service): RedirectResponse
    {
        $updated = $service->toggleStatus($user, $request->user()->id, $request->user());
        return back()->with('success', "Status {$updated->fullname} berhasil diperbarui.");
    }

    public function syncRoles(Request $request, User $user, UserService $service): RedirectResponse
    {
        $validated = $request->validate(['role_ids' => ['required', 'array', 'min:1'], 'role_ids.*' => ['uuid', 'exists:roles,id']]);
        $service->syncRoles($user, $validated['role_ids'], $request->user()->id, $request->user());
        return back()->with('success', 'Role pengguna berhasil diperbarui.');
    }
}
