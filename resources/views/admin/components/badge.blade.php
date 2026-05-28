@props(['status' => null, 'variant' => null])
@php
    $value = strtolower((string) ($status ?? $slot));
    $map = [
        'active' => 'success', 'available' => 'success', 'verified' => 'success', 'paid' => 'success', 'returned' => 'success', 'approved' => 'success',
        'pending' => 'warning', 'waiting' => 'warning', 'unpaid' => 'warning', 'borrowed' => 'warning', 'maintenance' => 'info', 'overdue' => 'danger',
        'inactive' => 'danger', 'cancelled' => 'danger', 'rejected' => 'danger', 'lost' => 'danger', 'damaged' => 'danger', 'waived' => 'info',
    ];
    $variant = $variant ?: ($map[$value] ?? 'neutral');
    $classes = ['success'=>'badge-success','danger'=>'badge-danger','warning'=>'badge-warning','info'=>'badge-info','primary'=>'badge-primary','secondary'=>'badge-secondary','neutral'=>'badge-neutral'][$variant] ?? 'badge-neutral';
@endphp
<span {{ $attributes->merge(['class' => $classes]) }}>{{ $slot->isEmpty() ? ucfirst($value) : $slot }}</span>
