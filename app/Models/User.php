<?php

namespace App\Models;

use App\Traits\HasRoles;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, HasRoles, Notifiable, SoftDeletes;

    public const ACTIVE = true;
    public const INACTIVE = false;
    public const VERIFIED = true;
    public const UNVERIFIED = false;

    protected $table = 'users';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'branch_id', 'fullname', 'email', 'phone', 'password_hash', 'member_code',
        'avatar_path', 'avatar_is_external', 'profile_type_id', 'institution_name',
        'major_name', 'current_level', 'staff_position', 'staff_library_id',
        'is_verified', 'is_active', 'last_login_at', 'created_by', 'updated_by',
    ];

    protected $hidden = ['password_hash', 'remember_token'];

    protected $casts = [
        'avatar_is_external' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function profileType(): BelongsTo { return $this->belongsTo(ProfileType::class, 'profile_type_id'); }
    public function branch(): BelongsTo { return $this->belongsTo(LibraryBranch::class, 'branch_id'); }
    public function staffLibrary(): BelongsTo { return $this->belongsTo(LibraryBranch::class, 'staff_library_id'); }
    public function userRoles(): HasMany { return $this->hasMany(UserRole::class, 'user_id'); }
    public function sessions(): HasMany { return $this->hasMany(UserSession::class, 'user_id'); }
    public function borrowings(): HasMany { return $this->hasMany(Borrowing::class, 'user_id'); }
    public function fines(): HasMany { return $this->hasMany(Fine::class, 'user_id'); }
    public function cart(): HasOne { return $this->hasOne(Cart::class, 'user_id'); }
    public function wishlists(): HasMany { return $this->hasMany(UserWishlist::class, 'user_id'); }
    public function reviews(): HasMany { return $this->hasMany(BookReview::class, 'user_id'); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class, 'user_id'); }
    public function notificationPreference(): HasOne { return $this->hasOne(NotificationPreference::class, 'user_id'); }
    public function searchHistories(): HasMany { return $this->hasMany(SearchHistory::class, 'user_id'); }
    public function bookViews(): HasMany { return $this->hasMany(BookView::class, 'user_id'); }
    public function activityLogs(): HasMany { return $this->hasMany(ActivityLog::class, 'user_id'); }
    public function reservations(): HasMany { return $this->hasMany(BookReservation::class, 'user_id'); }

    // ── Accessors ──────────────────────────────────────────────────────────

    /**
     * Resolves avatar_url secara aman:
     * - null  jika tidak ada avatar_path
     * - external URL langsung dikembalikan
     * - local path dikonversi ke storage URL
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        if (
            $this->avatar_is_external
            || str_starts_with($this->avatar_path, 'http://')
            || str_starts_with($this->avatar_path, 'https://')
        ) {
            return $this->avatar_path;
        }

        return asset('storage/' . $this->avatar_path);
    }

    public function getBranchScopeId(): ?string
    {
        return $this->branch_id ?: $this->staff_library_id;
    }

    public function bookPreferences(): HasMany
    {
        return $this->hasMany(UserBookPreference::class, 'user_id');
    }

    public function recommendationEvents(): HasMany
    {
        return $this->hasMany(RecommendationEvent::class, 'user_id');
    }

}
