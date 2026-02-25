<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Акт № {{ $act->number }} — {{ $act->date?->format('d.m.Y') }}</title>
    <style>
        body { font-family: system-ui, 'Segoe UI', sans-serif; font-size: 12px; line-height: 1.4; color: #111; max-width: 800px; margin: 0 auto; padding: 20px; }
        .no-print { margin-bottom: 16px; }
        @media print { .no-print { display: none !important; } body { padding: 0; } }
        h1 { font-size: 16px; text-align: center; margin: 0 0 20px; font-weight: 600; }
        .meta { margin-bottom: 16px; }
        .meta p { margin: 4px 0; }
        .parties { display: grid; gap: 16px; margin: 16px 0; }
        .party { border: 1px solid #ccc; padding: 10px; }
        .party strong { display: block; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; font-weight: 600; }
        .num { text-align: center; width: 36px; }
        .qty, .price, .amount { text-align: right; white-space: nowrap; }
        .totals { margin-top: 12px; text-align: right; }
        .totals p { margin: 4px 0; }
        .amount-words { margin-top: 12px; font-style: italic; }
        .btn { padding: 8px 16px; background: #f59e0b; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #d97706; }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" class="btn" onclick="window.print();">🖨️ Друк</button>
    </div>

    <h1>АКТ здачі-прийняття робіт (надання послуг)</h1>

    <div class="meta">
        <p><strong>№</strong> {{ $act->number }} &nbsp; <strong>від</strong> {{ $act->date?->format('d.m.Y') }}</p>
        @if($act->place_of_compilation)
            <p><strong>Місце складання:</strong> {{ $act->place_of_compilation }}</p>
        @endif
        @if($act->agreement_number || $act->agreement_date)
            <p><strong>За договором:</strong> {{ $act->agreement_number }} від {{ $act->agreement_date?->format('d.m.Y') }}</p>
        @endif
    </div>

    <div class="parties">
        <div class="party">
            <strong>Замовник:</strong>
            @php $c = $act->customer_data ?? []; @endphp
            {{ $c['name'] ?? '—' }}<br>
            @if(!empty($c['director'])) {{ $c['director'] }}<br> @endif
            @if(!empty($c['identification_code'])) ЄДРПОУ {{ $c['identification_code'] }}<br> @endif
            @if(!empty($c['address'])) {{ $c['address'] }}<br> @endif
            @if(!empty($c['bank_name']) || !empty($c['iban'])) {{ $c['bank_name'] ?? '' }} {{ $c['iban'] ?? '' }} @endif
        </div>
        <div class="party">
            <strong>Виконавець:</strong>
            {{ $act->contractor->full_name ?? $act->contractor->name }}<br>
            @if($act->contractor->requisites)
                @if(!empty($act->contractor->requisites['identification_code'])) ЄДРПОУ/ІПН {{ $act->contractor->requisites['identification_code'] }}<br> @endif
                @if(!empty($act->contractor->requisites['legal_address'])) {{ $act->contractor->requisites['legal_address'] }}<br> @endif
                @if(!empty($act->contractor->requisites['physical_address'])) {{ $act->contractor->requisites['physical_address'] }}<br> @endif
                @if(!empty($act->contractor->requisites['bank_name']) || !empty($act->contractor->requisites['iban'])) {{ $act->contractor->requisites['bank_name'] ?? '' }} {{ $act->contractor->requisites['iban'] ?? '' }} @endif
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="num">№ п/п</th>
                <th>Послуга / робота</th>
                <th>Од.</th>
                <th class="qty">К-сть</th>
                <th class="price">Ціна</th>
                <th class="amount">Сума</th>
            </tr>
        </thead>
        <tbody>
            @foreach($act->items as $item)
            <tr>
                <td class="num">{{ $item->sequence_number }}</td>
                <td>{{ $item->service_description }}</td>
                <td>{{ $item->unit }}</td>
                <td class="qty">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                <td class="price">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                <td class="amount">{{ number_format($item->amount, 2, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p><strong>Всього:</strong> {{ number_format($act->total_amount, 2, ',', ' ') }} грн</p>
        <p><strong>ПДВ:</strong> {{ number_format($act->vat_amount, 2, ',', ' ') }} грн</p>
        <p><strong>Загальна вартість з ПДВ:</strong> {{ number_format($act->total_with_vat, 2, ',', ' ') }} грн</p>
    </div>
    @if($act->total_amount_in_words)
        <p class="amount-words"><strong>Сума прописом:</strong> {{ $act->total_amount_in_words }}</p>
    @endif
</body>
</html>
