<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MyBookDetailResource extends MyBookResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['detail'] = [
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toISOString(),
            'reservation_expires_at' => $this->reservation_expires_at?->toISOString(),
            'return_info' => [
                'is_overdue' => (int) ($this->overdue_days ?? 0) > 0,
                'overdue_days' => (int) ($this->overdue_days ?? 0),
                'fine_per_day' => (float) ($this->fine_per_day ?? 0),
                'total_fine' => (float) ($this->total_fine ?? 0),
                'message' => $this->resolveReturnMessage(),
            ],
        ];

        return $data;
    }

    private function resolveReturnMessage(): string
    {
        $status = (string) ($this->computed_status ?? $this->status);

        return match ($status) {
            'active' => 'Buku masih dalam masa peminjaman.',
            'due_soon' => 'Buku harus segera dikembalikan.',
            'overdue' => 'Buku sudah melewati batas pengembalian dan berpotensi terkena denda.',
            'ready_for_return' => 'Denda telah dilunasi. Silakan kembalikan buku ke perpustakaan.',
            'returned' => 'Peminjaman selesai. Buku sudah dikembalikan.',
            default => 'Status peminjaman sedang diproses.',
        };
    }
}