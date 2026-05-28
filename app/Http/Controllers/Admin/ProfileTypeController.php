<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfileTypeRequest;
use App\Models\ProfileType;
use App\Services\Admin\ProfileTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileTypeController extends Controller
{
    public function index(Request $request, ProfileTypeService $service): View
    {
        return view('admin.profile-types.index', [
            'profileTypes' => $service->paginate($request->only(['search', 'status', 'per_page'])),
        ]);
    }

    public function create(): View
    {
        return view('admin.profile-types.create', ['profileType' => null]);
    }

    public function store(ProfileTypeRequest $request, ProfileTypeService $service): RedirectResponse
    {
        $profileType = $service->create($request->validated(), $request->user()->id);

        return redirect()->route('admin.profile-types.index')
            ->with('success', "Profile type {$profileType->label} berhasil dibuat.");
    }

    public function edit(ProfileType $profileType): View
    {
        return view('admin.profile-types.edit', compact('profileType'));
    }

    public function update(ProfileTypeRequest $request, ProfileType $profileType, ProfileTypeService $service): RedirectResponse
    {
        $profileType = $service->update($profileType, $request->validated(), $request->user()->id);

        return redirect()->route('admin.profile-types.index')
            ->with('success', "Profile type {$profileType->label} berhasil diperbarui.");
    }

    public function destroy(Request $request, ProfileType $profileType, ProfileTypeService $service): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $service->delete($profileType, $request->user()->id);

        return redirect()->route('admin.profile-types.index')
            ->with('success', "Profile type {$profileType->label} berhasil dihapus.");
    }
}
