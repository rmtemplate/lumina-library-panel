<!DOCTYPE html>
<html lang="id" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat Jatuh Tempo — Lumina Library</title>
    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { margin: 0; padding: 0; background-color: #f3f4f5; font-family: 'Inter', Arial, sans-serif; -webkit-text-size-adjust: 100%; }
        table { border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        a { text-decoration: none; }
        @media screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .main-card { border-radius: 0 !important; }
            .content-padding { padding: 28px 16px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f5;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f3f4f5;padding:40px 0;">
    <tr>
        <td align="center" style="padding:40px 16px;">
            <table class="email-container main-card" border="0" cellpadding="0" cellspacing="0" width="600"
                   style="background-color:#ffffff;border-radius:16px;border:1px solid #e1e3e4;box-shadow:0 4px 24px rgba(0,0,0,.04);width:600px;max-width:600px;">

                {{-- Header --}}
                <tr>
                    <td align="center" class="content-padding" style="padding:36px 40px 20px;">
                        <img src="{{ $logoUrl }}" alt="Lumina Library" width="160" style="display:block;border:0;max-width:100%;height:auto;" />
                    </td>
                </tr>

                {{-- Status Banner --}}
                <tr>
                    <td style="padding:0 40px;">
                        <div style="border-radius:12px;padding:16px 20px;background-color:{{ $isOverdue ? '#fff1f0' : '#fffbeb' }};border:1px solid {{ $isOverdue ? '#ffa39e' : '#ffe58f' }};">
                            <p style="margin:0;font-size:15px;font-weight:600;color:{{ $isOverdue ? '#cf1322' : '#874d00' }};">
                                @if ($isOverdue)
                                    ⚠️ Buku Anda sudah terlambat {{ $overdueDays }} hari dikembalikan.
                                @elseif ($daysLeft === 0)
                                    🔔 Jatuh tempo peminjaman Anda hari ini!
                                @else
                                    📅 Buku Anda akan jatuh tempo dalam {{ $daysLeft }} hari.
                                @endif
                            </p>
                        </div>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td class="content-padding" style="padding:24px 40px 36px;">
                        <p style="margin:0 0 8px;font-size:16px;font-weight:600;color:#031632;">Halo, {{ $user->fullname }},</p>
                        <p style="margin:0 0 24px;font-size:15px;color:#44474d;line-height:24px;">
                            @if ($isOverdue)
                                Peminjaman Anda telah melewati tanggal jatuh tempo. Denda keterlambatan terus berjalan setiap hari.
                                Segera kembalikan buku ke perpustakaan untuk menghindari denda yang lebih besar.
                            @elseif ($daysLeft === 0)
                                Hari ini adalah hari terakhir peminjaman Anda. Kembalikan buku hari ini untuk menghindari denda keterlambatan.
                            @else
                                Ini adalah pengingat bahwa peminjaman Anda akan segera jatuh tempo.
                                Harap kembalikan buku tepat waktu untuk menghindari denda keterlambatan.
                            @endif
                        </p>

                        {{-- Borrowing Info --}}
                        <div style="background-color:#f8f9fa;border-radius:12px;border:1px solid #e1e3e4;padding:20px;margin-bottom:24px;">
                            <p style="margin:0 0 12px;font-size:13px;font-weight:600;color:#75777e;text-transform:uppercase;letter-spacing:.05em;">Detail Peminjaman</p>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="padding:4px 0;font-size:14px;color:#44474d;width:40%;">Kode Peminjaman</td>
                                    <td style="padding:4px 0;font-size:14px;font-weight:600;color:#031632;">{{ $borrowing->borrowing_code }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;font-size:14px;color:#44474d;">Tanggal Pinjam</td>
                                    <td style="padding:4px 0;font-size:14px;font-weight:600;color:#031632;">{{ $borrowing->borrow_date?->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;font-size:14px;color:#44474d;">Jatuh Tempo</td>
                                    <td style="padding:4px 0;font-size:14px;font-weight:600;color:{{ $isOverdue ? '#cf1322' : '#031632' }};">{{ $borrowing->due_date?->format('d M Y') }}</td>
                                </tr>
                                @if ($isOverdue)
                                <tr>
                                    <td style="padding:4px 0;font-size:14px;color:#44474d;">Keterlambatan</td>
                                    <td style="padding:4px 0;font-size:14px;font-weight:600;color:#cf1322;">{{ $overdueDays }} hari</td>
                                </tr>
                                @elseif ($daysLeft > 0)
                                <tr>
                                    <td style="padding:4px 0;font-size:14px;color:#44474d;">Sisa Waktu</td>
                                    <td style="padding:4px 0;font-size:14px;font-weight:600;color:#031632;">{{ $daysLeft }} hari</td>
                                </tr>
                                @endif
                            </table>
                        </div>

                        {{-- Books --}}
                        @if ($items->isNotEmpty())
                        <p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#75777e;text-transform:uppercase;letter-spacing:.05em;">Buku yang Dipinjam</p>
                        @foreach ($items as $item)
                        <div style="padding:10px 0;border-bottom:1px solid #f0f0f0;">
                            <p style="margin:0;font-size:14px;font-weight:600;color:#031632;">{{ $item->book?->title ?? '—' }}</p>
                            @if ($item->book?->author)
                            <p style="margin:2px 0 0;font-size:13px;color:#75777e;">{{ $item->book->author }}</p>
                            @endif
                        </div>
                        @endforeach
                        @endif

                        <p style="margin:28px 0 0;font-size:14px;color:#44474d;line-height:22px;">
                            Kembalikan buku ke perpustakaan terdekat selama jam operasional.
                            Gunakan aplikasi Lumina Library untuk melihat detail peminjaman Anda.
                        </p>
                    </td>
                </tr>
            </table>

            {{-- Footer --}}
            <table class="email-container" border="0" cellpadding="0" cellspacing="0" width="600" style="width:600px;max-width:600px;">
                <tr>
                    <td align="center" style="padding:20px 16px;">
                        <p style="margin:0 0 4px;font-size:12px;color:#75777e;">
                            Pesan ini dikirim secara otomatis oleh sistem Lumina Library.
                        </p>
                        <p style="margin:0;font-size:12px;font-weight:500;color:#44474d;">
                            &copy; {{ $year }} Lumina Library. Hak cipta dilindungi undang-undang.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
