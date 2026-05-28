<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookCategoryRequest;
use App\Models\BookCategory;
use App\Services\Admin\BookCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookCategoryController extends Controller
{
    public function index(Request $request, BookCategoryService $service): View
    {
        return view('admin.categories.index', [
            'categories' => $service->paginate($request->only(['search', 'status', 'per_page', 'sort', 'direction'])),
            'canManageCategory' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', ['category' => null]);
    }

    public function store(BookCategoryRequest $request, BookCategoryService $service): RedirectResponse
    {
        $category = $service->create($request->validated(), $request->user()->id);

        return redirect()->route('admin.categories.index')
            ->with('success', "Category {$category->name} berhasil dibuat di Lumina Library.");
    }

    public function edit(BookCategory $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(BookCategoryRequest $request, BookCategory $category, BookCategoryService $service): RedirectResponse
    {
        $category = $service->update($category, $request->validated(), $request->user()->id);

        return redirect()->route('admin.categories.index')
            ->with('success', "Category {$category->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, BookCategory $category, BookCategoryService $service): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $service->delete($category, $request->user()->id);

        return redirect()->route('admin.categories.index')
            ->with('success', "Category {$category->name} berhasil dihapus.");
    }
}
