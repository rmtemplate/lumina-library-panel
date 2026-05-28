<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WaiveFineRequest;
use App\Models\Fine;
use App\Services\Admin\BranchAccessService;
use App\Services\Admin\FineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FineController extends Controller
{
    public function index(Request $request, FineService $service): View
    {
        return view('admin.fines.index', ['fines' => $service->paginate($request->only(['search', 'status', 'per_page']), $request->user()), 'summary' => $service->summary($request->user())]);
    }

    public function show(Request $request, Fine $fine, BranchAccessService $access): View
    {
        $access->assertCanAccessBranch($request->user(), $fine->branch_id);
        return view('admin.fines.show', ['fine' => $fine->load(['user', 'branch', 'borrowingItem.bookItem.book', 'payments'])]);
    }

    public function waive(WaiveFineRequest $request, Fine $fine, FineService $service): RedirectResponse
    {
        $service->waive($fine, $request->validated('waived_reason'), $request->user());
        return back()->with('success', 'Denda berhasil dibebaskan.');
    }
}
