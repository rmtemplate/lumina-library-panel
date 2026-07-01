<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Borrowing extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const PENDING = 'pending';
    public const AWAITING_PICKUP = 'awaiting_pickup';
    public const ACTIVE = 'active';
    public const READY_FOR_RETURN = 'ready_for_return';
    // Staff scanned the Return QR and is verifying the physical books. The
    // member's Return QR is now disabled and they see "waiting for staff
    // verification" — they cannot pay until the staff finishes (which either
    // creates charges -> WAITING_PAYMENT, or closes the loan -> RETURNED).
    public const RETURN_PENDING_VERIFICATION = 'return_pending_verification';
    public const RETURNED = 'returned';
    public const RETURNED_WITH_FINE = 'returned_with_fine';
    public const WAITING_PAYMENT = 'waiting_payment';
    public const OVERDUE = 'overdue';
    public const EXPIRED = 'expired';
    public const CANCELLED = 'cancelled';

    // ── Two-stage return flow (see docs/return-flow.md) ──────────────────────
    // Staff scanned the Return QR — books physically received. From here the
    // flow gates on TWO SEPARATE Midtrans payments before completion.
    public const RETURN_RECEIVED = 'return_received';
    // Overdue fine outstanding — a dedicated OVERDUE_FINE payment must settle
    // before staff may inspect the books.
    public const WAITING_OVERDUE_PAYMENT = 'waiting_overdue_payment';
    // No/paid overdue — staff inspects each book's condition.
    public const BOOK_INSPECTION = 'book_inspection';
    // Damage/lost/manual charges outstanding — a SEPARATE DAMAGE_CHARGE payment
    // (never combined with the overdue one) must settle before completion.
    public const WAITING_DAMAGE_PAYMENT = 'waiting_damage_payment';
    // Terminal success state for the two-stage flow.
    public const COMPLETED = 'completed';

    public const PICKUP_METHOD_QR_SCAN = 'qr_scan';
    public const PICKUP_METHOD_STAFF_BYPASS = 'staff_bypass';
    public const PICKUP_METHOD_DEV_BYPASS = 'DEV_BYPASS';

    protected $table = 'borrowings';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'borrowing_code', 'user_id', 'branch_id', 'library_branch_id', 'borrow_date',
        'due_date', 'return_date', 'status', 'notes', 'approved_by', 'approved_at',
        'reservation_expires_at', 'pickup_method', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'borrow_date' => 'date', 'due_date' => 'date', 'return_date' => 'date',
        'approved_at' => 'datetime', 'reservation_expires_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function branch(): BelongsTo { return $this->belongsTo(LibraryBranch::class, 'branch_id'); }
    public function libraryBranch(): BelongsTo { return $this->belongsTo(LibraryBranch::class, 'library_branch_id'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(BorrowingItem::class, 'borrowing_id'); }
    public function qrCodes(): HasMany { return $this->hasMany(BorrowingQrCode::class, 'borrowing_id'); }
    public function payments(): HasMany { return $this->hasMany(BorrowingPayment::class, 'borrowing_id'); }
    public function latestPayment(): HasOne { return $this->hasOne(BorrowingPayment::class, 'borrowing_id')->latestOfMany(); }
}
