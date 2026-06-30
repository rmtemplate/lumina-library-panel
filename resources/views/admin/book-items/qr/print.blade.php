<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Labels — Lumina Library</title>
    <style>
        :root { --ink:#031632; --muted:#6b7280; --line:#d8dbe6; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #eef1f7; color: var(--ink);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }

        .toolbar { position: sticky; top: 0; display: flex; gap: 8px; justify-content: center;
            padding: 14px; background: #fff; border-bottom: 1px solid var(--line); }
        .toolbar button { font: inherit; font-weight: 700; padding: 9px 18px; border-radius: 10px;
            border: 1px solid var(--line); background: #031632; color: #fff; cursor: pointer; }
        .toolbar button.secondary { background: #fff; color: var(--ink); }

        /* A4 sheet, 3 columns of labels with print-friendly gaps. */
        .sheet { width: 210mm; min-height: 297mm; margin: 16px auto; padding: 12mm 10mm;
            background: #fff; box-shadow: 0 6px 24px rgba(3,22,50,.12);
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 6mm; align-content: start; }

        .label { border: 1px dashed var(--line); border-radius: 8px; padding: 6mm 4mm;
            display: flex; flex-direction: column; align-items: center; text-align: center;
            break-inside: avoid; page-break-inside: avoid; }
        .label .brand { display: flex; align-items: center; gap: 4px; margin-bottom: 3mm; }
        .label .brand img { height: 12px; width: 12px; object-fit: contain; }
        .label .brand span { font-size: 8pt; font-weight: 800; letter-spacing: .04em; color: var(--muted); }
        .label img.qr { width: 33mm; height: 33mm; }
        .label .title { margin-top: 3mm; font-size: 9pt; font-weight: 800; line-height: 1.2;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .label .code { margin-top: 1.5mm; font-size: 9pt; font-weight: 800; font-family: ui-monospace, "Courier New", monospace; }
        .label .meta { font-size: 7pt; color: var(--muted); font-family: ui-monospace, "Courier New", monospace; }
        .label .branch { margin-top: 1mm; font-size: 7pt; color: var(--muted); }

        @media print {
            html, body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: auto; margin: 0; padding: 8mm; box-shadow: none; }
            .label { border-color: #c9ccd6; }
        }
        @page { size: A4; margin: 8mm; }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Print / Save PDF</button>
        <button class="secondary" onclick="window.close()">Close</button>
    </div>

    <div class="sheet">
        @foreach($labels as $label)
            <div class="label">
                <div class="brand">
                    <img src="{{ asset('lumina-logo.png') }}" alt="">
                    <span>LUMINA LIBRARY</span>
                </div>
                <img class="qr" src="{{ $label['qr'] }}" alt="QR {{ $label['item_code'] }}">
                <div class="title">{{ $label['title'] }}</div>
                <div class="code">{{ $label['item_code'] }}</div>
                @if(!empty($label['barcode']))<div class="meta">{{ $label['barcode'] }}</div>@endif
                @if(!empty($label['branch']))<div class="branch">{{ $label['branch'] }}</div>@endif
            </div>
        @endforeach
    </div>
</body>
</html>
