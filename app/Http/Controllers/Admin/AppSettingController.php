<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppSettingRequest;
use App\Services\Admin\AppSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AppSettingController extends Controller
{
    public function index(AppSettingService $service): View
    {
        return view('admin.settings.index', ['settings' => $service->allKeyed()]);
    }

    public function update(AppSettingRequest $request, AppSettingService $service): RedirectResponse
    {
        $service->updateMany($request->validated('settings'), $request->user()->id);
        return back()->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
