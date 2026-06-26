<?php

namespace App\Services\Api\Pickup;

use App\Events\Pickup\PickupFailed;
use App\Events\Pickup\PickupSuccess;
use App\Events\Staff\StaffForcePickup;
use App\Models\Borrowing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single place that knows how to announce a pickup outcome over WebSocket,
 * used by every pickup path: a real QR scan (StaffQrScanService::scanPickup),
 * the staff bypass (StaffQrScanService::forcePickup), and the unauthenticated
 * local dev bypass (App\Services\Api\Dev\DevBypassService::forcePickup).
 * Centralizing this avoids re-deriving the same payload/actor-label logic in
 * three different services.
 */
class PickupBroadcastService
{
    public function success(Borrowing $borrowing, string $pickupMethod): void
    {
        $pickedAt = now()->toISOString();

        DB::afterCommit(function () use ($borrowing, $pickupMethod, $pickedAt) {
            PickupSuccess::dispatch(
                $borrowing->user_id,
                $borrowing->id,
                $borrowing->borrowing_code,
                $pickupMethod,
                $pickedAt
            );

            Log::info('[WS] Broadcast sent: pickup.success', [
                'borrowing_id' => $borrowing->id,
                'pickup_method' => $pickupMethod,
                'channel' => 'user.' . $borrowing->user_id,
            ]);

            if ($pickupMethod !== Borrowing::PICKUP_METHOD_QR_SCAN) {
                StaffForcePickup::dispatch(
                    $borrowing->id,
                    $borrowing->borrowing_code,
                    $pickupMethod,
                    null,
                    $pickupMethod === Borrowing::PICKUP_METHOD_DEV_BYPASS ? 'LOCAL_DEV' : 'Staff'
                );

                Log::info('[WS] Broadcast sent: staff.force-pickup', [
                    'borrowing_id' => $borrowing->id,
                    'pickup_method' => $pickupMethod,
                    'channel' => 'borrowing.' . $borrowing->id,
                ]);
            }
        });
    }

    public function failed(string $borrowingId, ?string $borrowingCode, string $reason, string $attemptedVia): void
    {
        try {
            PickupFailed::dispatch($borrowingId, $borrowingCode, $reason, $attemptedVia);

            Log::warning('[WS] Broadcast sent: pickup.failed', [
                'borrowing_id' => $borrowingId,
                'reason' => $reason,
                'attempted_via' => $attemptedVia,
                'channel' => 'borrowing.' . $borrowingId,
            ]);
        } catch (\Throwable $e) {
            // A broadcast failure (e.g. Reverb not running locally) must
            // never mask the original validation error being thrown by the
            // caller right after this — log and swallow.
            Log::error('[WS] Broadcast failed: pickup.failed', [
                'borrowing_id' => $borrowingId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
