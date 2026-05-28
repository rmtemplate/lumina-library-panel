<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\UserWishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(): View
    {
        $wishlists = auth()->user()->wishlists()
            ->with(['book.category', 'book.stocks'])
            ->orderByDesc('added_at')
            ->paginate(20);

        return view('user.wishlist.index', compact('wishlists'));
    }

    public function toggle(Book $book): RedirectResponse
    {
        $user = auth()->user();
        $existing = $user->wishlists()->where('book_id', $book->id)->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', "\"{$book->title}\" dihapus dari wishlist.");
        }

        $user->wishlists()->create([
            'book_id'  => $book->id,
            'added_at' => now(),
        ]);

        return back()->with('success', "\"{$book->title}\" ditambahkan ke wishlist.");
    }
}
