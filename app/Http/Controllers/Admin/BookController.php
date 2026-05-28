<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookRequest;
use App\Http\Requests\Admin\UpdateBookRequest;
use App\Models\Book;
use App\Services\Admin\BookService;
use App\Services\Admin\LookupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request, BookService $service, LookupService $lookup): View
    {
        return view('admin.books.index', [
            'books' => $service->paginate($request->only(['search', 'category_id', 'status', 'per_page', 'sort', 'direction'])),
            'categories' => $lookup->activeCategories(),
        ]);
    }

    public function create(LookupService $lookup): View
    {
        return view('admin.books.create', ['book' => null, 'categories' => $lookup->activeCategories()]);
    }

    public function store(StoreBookRequest $request, BookService $service): RedirectResponse
    {
        $book = $service->create($request->validated(), $request->user()->id);
        return redirect()->route('admin.books.index')->with('success', "Judul {$book->title} berhasil ditambahkan. Tambahkan copy fisiknya di menu Book Items.");
    }

    public function show(Book $book): View
    {
        return view('admin.books.show', ['book' => $book->load(['category', 'items.branch', 'items.rack'])]);
    }

    public function edit(Book $book, LookupService $lookup): View
    {
        return view('admin.books.edit', ['book' => $book, 'categories' => $lookup->activeCategories()]);
    }

    public function update(UpdateBookRequest $request, Book $book, BookService $service): RedirectResponse
    {
        $service->update($book, $request->validated(), $request->user()->id);
        return redirect()->route('admin.books.index')->with('success', "Judul {$book->title} berhasil diperbarui.");
    }

    public function destroy(Request $request, Book $book, BookService $service): RedirectResponse
    {
        $service->delete($book, $request->user()->id);
        return redirect()->route('admin.books.index')->with('success', "Judul {$book->title} berhasil dihapus.");
    }

    public function toggleStatus(Request $request, Book $book, BookService $service): RedirectResponse
    {
        $updated = $service->toggleStatus($book, $request->user()->id);
        return back()->with('success', "Status judul {$updated->title} berhasil diperbarui.");
    }
}
