<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Акт № {{ $act->number }} — {{ $act->date?->format('d.m.Y') }}</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif !important; font-weight: normal !important; }
        body { font-size: 11px; line-height: 1.35; color: #111; max-width: 210mm; margin: 0 auto; padding: 18px 24px; }
        .no-print { margin-bottom: 12px; }
        @media print { .no-print { display: none !important; } body { padding: 8px; } }
        .header-approve { display: table; width: 100%; margin-bottom: 18px; }
        .header-approve .left, .header-approve .right { display: table-cell; width: 50%; vertical-align: top; }
        .header-approve .right { text-align: right; }
        .header-approve .label { margin-bottom: 4px; }
        .stamp-placeholder { min-height: 60px; }
        .act-title { text-align: center; font-size: 13px; font-weight: bold; margin-bottom: 10px; }
        .b { font-weight: bold; }
        .intro { margin: 12px 0; text-align: justify; }
        .agreement-line { margin: 8px 0; }
        .services-intro { margin: 8px 0; }
        table.act-table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        table.act-table th, table.act-table td { border: 1px solid #333; padding: 5px 6px; text-align: left; font-size: 10px; }
        table.act-table th { background: #f5f5f5; }
        table.act-table .col-num { width: 32px; text-align: center; }
        table.act-table .col-unit { width: 60px; text-align: center; }
        table.act-table .col-qty { width: 50px; text-align: center; }
        table.act-table .col-price, table.act-table .col-sum { width: 80px; text-align: right; white-space: nowrap; }
        .totals-block { margin: 10px 0; width: 260px; margin-left: auto; }
        .totals-block p { margin: 3px 0; display: flex; justify-content: space-between; }
        .totals-block .label { padding-right: 6px; }
        .totals-block .value { min-width: 90px; text-align: right; }
        .place-compilation { margin: 6px 0; width: 260px; margin-left: auto; }
        .amount-words { margin: 10px 0; }
        .declarations { margin: 10px 0; }
        .signatures { display: table; width: 100%; margin-top: 24px; }
        .signatures .left, .signatures .right { display: table-cell; width: 50%; vertical-align: top; padding-top: 40px; }
        .signatures .right { text-align: right; }
        .signatures .label { margin-bottom: 4px; }
        .footer-block { display: table; width: 100%; margin-top: 24px; padding-top: 12px; border-top: 1px solid #ccc; font-size: 10px; }
        .footer-block .left, .footer-block .right { display: table-cell; width: 50%; vertical-align: top; padding-right: 12px; }
        .footer-block .right { padding-right: 0; padding-left: 12px; }
        .footer-block p { margin: 2px 0; }
        .btn { padding: 8px 16px; background: #f59e0b; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #d97706; }
    </style>
</head>
<body>
    @php $isPdf = $isPdf ?? false; @endphp
    @if (! $isPdf)
        <div class="no-print">
            <button type="button" class="btn" onclick="window.print();">🖨️ Друк</button>
        </div>
    @endif

    {{-- ЗАТВЕРДЖУЮ (дві колонки) --}}
    <div class="header-approve">
        <div class="left">
            <div class="label">ЗАТВЕРДЖУЮ</div>
            <div>{{ $act->contractor->name }}</div>
            <br>
            <br>
            <div>{{ $act->contractor->full_name }}</div>
            <div class="stamp-placeholder"></div>
        </div>
        <div class="right">
            <div class="label">ЗАТВЕРДЖУЮ</div>
            <div>Директор {{ $customerName = ($act->customer_data['name'] ?? $act->customer?->name ?? '—') }}</div>
            <br>
            <br>
            <div>{{ $act->customer_data['director'] ?? $act->customer?->in_the_person_of ?? '—' }}</div>
        </div>
    </div>

    <div class="act-title">
        <b class="b">
        Акт здачі-приймання робіт (надання послуг) № {{ $act->number }} від {{ $act->date?->format('d.m.Y') ?? '—' }} р.
    </b>
    </div>

    <div class="intro">
        Ми, що нижче підписалися, представник Замовника {{ $customerName }} 
        в особі директора {{ $act->customer?->in_the_person_of ?? '—' }}, з одного боку, 
        і представник Виконавця {{ $act->contractor->name }} 
        в особі {{ $act->contractor->in_the_person_of ?? $act->contractor->full_name ?? $act->contractor->name }}, 
        з іншого боку, склали цей акт про те, що на підставі наступних документів:
    </div>

    <div class="agreement-line">
        <b class="b">Договір: № {{ $act->agreement_number ?? '—' }} від {{ $act->agreement_date?->format('d.m.Y') ?? '—' }}р.</b>
    </div>

    <div class="services-intro">
        виконавцем були проведені наступні роботи (зроблені такі послуги):
    </div>

    <table class="act-table">
        <thead>
            <tr>
                <th class="col-num">№ п/п</th>
                <th>Послуга</th>
                <th class="col-unit">Од.</th>
                <th class="col-qty">К-сть</th>
                <th class="col-price">Ціна</th>
                <th class="col-sum">Сума</th>
            </tr>
        </thead>
        <tbody>
            @foreach($act->items as $item)
            <tr>
                <td class="col-num">{{ $item->sequence_number }}</td>
                <td>{{ $item->service_description }}</td>
                <td class="col-unit">{{ $item->unit }}</td>
                <td class="col-qty">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                <td class="col-price">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                <td class="col-sum">{{ number_format($item->amount, 2, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-block">
        <p>
            <span class="label">Всього:</span>
            <span class="value">{{ number_format($act->total_amount, 2, ',', ' ') }}</span>
        </p>
        <p>
            <span class="label">Податок на додану вартість (ПДВ):</span>
            <span class="value">{{ number_format($act->vat_amount, 2, ',', ' ') }}</span>
        </p>
        <p>
            <span class="label">Загальна вартість з ПДВ:</span>
            <span class="value">{{ number_format($act->total_with_vat, 2, ',', ' ') }}</span>
        </p>
    </div>

    @if($act->place_of_compilation)
    <div class="place-compilation">
        Місце складання: {{ $act->place_of_compilation }}
    </div>
    @endif

    @if($act->total_amount_in_words)
    <div class="amount-words">
        Загальна вартість робіт (послуг) склала: {{ $act->total_amount_in_words }}
    </div>
    @endif

    <div class="declarations">
        <p>Сторони претензій не мають.</p>
        <p>Виконавець працює за спрощеною системою оподаткування. ПДВ не сплачується.</p>
    </div>

    <div class="signatures">
        <div class="left">
            <div class="label">Від Виконавця</div>
        </div>
        <div class="right">
            <div class="label">Від Замовника</div>
            <div>Директор: {{ $act->customer_data['director'] ?? $act->customer?->in_the_person_of ?? '—' }}</div>
        </div>
    </div>

    <div class="footer-block">
        <div class="left">
            <p><strong>{{ $act->contractor->full_name ?? $act->contractor->name }}</strong></p>
            @php $req = $act->contractor->requisites ?? []; @endphp
            @if(!empty($req['identification_code']))<p>Ідентифікаційний номер: {{ $req['identification_code'] }}</p>@endif
            @if(!empty($req['legal_address']))<p>Юридична адреса: {{ $req['legal_address'] }}</p>@endif
            @if(!empty($req['physical_address']))<p>Фізична адреса: {{ $req['physical_address'] }}</p>@endif
            @if(!empty($act->contractor->phone))<p>тел.: {{ $act->contractor->phone }}</p>@endif
            @if(!empty($req['iban']))<p>р/р No: {{ $req['iban'] }}</p>@endif
            @if(!empty($req['bank_name']))<p>Банк: {{ $req['bank_name'] }}</p>@endif
            @if(!empty($req['mfo']))<p>МФО: {{ $req['mfo'] }}</p>@endif
        </div>
        <div class="right">
            <p><strong>{{ $act->customer_data['name'] ?? $act->customer?->name ?? '—' }}</strong></p>
            @php $c = $act->customer_data ?? []; @endphp
            @if(!empty($c['identification_code']))<p>Ідентифікаційний код ЄДРПОУ: {{ $c['identification_code'] }}</p>@endif
            @if(!empty($c['vat_certificate']))<p>Свідоцтво ПДВ: №{{ $c['vat_certificate'] }}</p>@endif
            @if(!empty($c['individual_tax_number']))<p>Індивідуальний податковий: №{{ $c['individual_tax_number'] }}</p>@endif
            @if(!empty($c['bank_name']))<p>Банк ПАТ: {{ $c['bank_name'] }}</p>@endif
            @if(!empty($c['mfo']))<p>МФО: {{ $c['mfo'] }}</p>@endif
            @if(!empty($c['iban']))<p>IBAN: {{ $c['iban'] }}</p>@endif
            @if(!empty($c['address']))<p>Адреса: {{ is_string($c['address']) ? str_replace("\n", ', ', $c['address']) : '' }}</p>@endif
        </div>
    </div>
</body>
</html>
