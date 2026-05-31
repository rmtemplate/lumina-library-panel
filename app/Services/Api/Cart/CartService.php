<?php

namespace App\Services\Api\Cart;

use App\Models\Book;
use App\Models\BookItem;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Services\AppSettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        private readonly AppSettingService $appSettingService
    ) {
    }

    public function getCart(User $user): Cart
    {
        $cart = $this->firstOrCreateCart($user);

        return $this->loadCart($cart);
    }

    public function addItem(User $user, string $bookId, string $branchId): Cart
    {
        return DB::transaction(function () use ($user, $bookId, $branchId) {
            $cart = $this->firstOrCreateCart($user);

            $book = Book::query()
                ->where('id', $bookId)
                ->where('is_active', true)
                ->first();

            if (! $book) {
                throw ValidationException::withMessages([
                    'book_id' => ['Buku tidak ditemukan atau tidak aktif.'],
                ]);
            }

            $sameBookExists = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('book_id', $bookId)
                ->where('branch_id', $branchId)
                ->exists();

            if ($sameBookExists) {
                throw ValidationException::withMessages([
                    'book_id' => ['Buku sudah ada pada cart/keranjang.'],
                ]);
            }

            $maxCartItems = $this->appSettingService->getInt('max_cart_items', 10);

            $currentCartItems = CartItem::query()
                ->where('cart_id', $cart->id)
                ->count();

            if ($currentCartItems >= $maxCartItems) {
                throw ValidationException::withMessages([
                    'cart' => ["Maksimal item dalam cart adalah {$maxCartItems} buku."],
                ]);
            }

            $availableItemsCount = BookItem::query()
                ->where('book_id', $bookId)
                ->where('branch_id', $branchId)
                ->where('status', BookItem::AVAILABLE)
                ->where('is_active', true)
                ->count();

            if ($availableItemsCount < 1) {
                throw ValidationException::withMessages([
                    'book_id' => ['Buku tidak tersedia pada perpustakaan yang dipilih.'],
                ]);
            }

            CartItem::query()->create([
                'cart_id' => $cart->id,
                'book_id' => $bookId,
                'branch_id' => $branchId,
                'added_at' => now(),
            ]);

            return $this->loadCart($cart->refresh());
        });
    }

    public function removeItem(User $user, string $cartItemId): Cart
    {
        return DB::transaction(function () use ($user, $cartItemId) {
            $cart = $this->firstOrCreateCart($user);

            $item = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('id', $cartItemId)
                ->first();

            if (! $item) {
                throw ValidationException::withMessages([
                    'cart_item_id' => ['Item cart tidak ditemukan.'],
                ]);
            }

            $item->delete();

            return $this->loadCart($cart->refresh());
        });
    }

    public function clear(User $user): Cart
    {
        return DB::transaction(function () use ($user) {
            $cart = $this->firstOrCreateCart($user);

            CartItem::query()
                ->where('cart_id', $cart->id)
                ->delete();

            return $this->loadCart($cart->refresh());
        });
    }

    private function firstOrCreateCart(User $user): Cart
    {
        return Cart::query()->firstOrCreate([
            'user_id' => $user->id,
        ]);
    }

    private function loadCart(Cart $cart): Cart
    {
        $cart->load([
            'items' => function ($query) {
                $query->latest('added_at');
            },
            'items.book.category',
            'items.branch',
        ]);

        if ($cart->items->isEmpty()) {
            return $cart;
        }

        $bookIds = $cart->items->pluck('book_id')->unique()->values();
        $branchIds = $cart->items->pluck('branch_id')->unique()->values();

        $availabilityRows = BookItem::query()
            ->select([
                'book_id',
                'branch_id',
            ])
            ->selectRaw('COUNT(*) as available_items_count')
            ->where('status', BookItem::AVAILABLE)
            ->where('is_active', true)
            ->whereIn('book_id', $bookIds)
            ->whereIn('branch_id', $branchIds)
            ->groupBy([
                'book_id',
                'branch_id',
            ])
            ->get()
            ->keyBy(fn ($row) => $this->pairKey($row->book_id, $row->branch_id));

        $cart->items->each(function (CartItem $item) use ($availabilityRows) {
            $row = $availabilityRows->get($this->pairKey($item->book_id, $item->branch_id));

            $item->setAttribute(
                'available_items_count',
                (int) ($row?->available_items_count ?? 0)
            );
        });

        return $cart;
    }

    private function pairKey(string $bookId, string $branchId): string
    {
        return $bookId . '|' . $branchId;
    }
}