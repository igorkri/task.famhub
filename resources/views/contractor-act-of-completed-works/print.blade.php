<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Акт № {{ $act->number }}</title>

    <style>
        @font-face {
            font-family: 'Times New Roman';
            src: url('{{ storage_path("fonts/times.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        * {
            font-family: 'Times New Roman', serif;
        }


        body {
            margin: 20px 25px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        /* У PDF DejaVu Sans має жирний варіант з кирилицею; Times-Bold (fallback) — лише Latin-1, тому для PDF використовуємо DejaVu Sans */
        .bold { font-size: 16px; }
        /* .bold { font-weight: bold; } */

        .section { margin-bottom: 14px; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .no-border td {
            border: none;
            padding: 2px 0;
        }

        .bordered th,
        .bordered td {
            border: 1px solid #000;
            padding: 4px 6px;
        }

        .bordered th {
            background: #efefef;
        }

        .bg-gray-200 { background: #efefef; }

        .small { font-size: 11px; }

        .signature-block {
            margin-top: 40px;
        }

        .footer {
            margin-top: 30px;
            font-size: 13px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }

    .footer {
        width: 100%;
        border-collapse: collapse;
        margin-top: 30px;
        font-size: 13px;
        border: 1px solid #000;
    }

    .footer td {
        width: 50%;
        vertical-align: top;
        padding: 12px 14px;
    }

    .footer td:first-child {
        border-right: 1px solid #000;
    }

    .footer .company-name {
    
        font-size: 13px;
        margin-bottom: 6px;
    }

    .footer p {
        margin: 3px 0;
        line-height: 1.3;
    }

    .footer .label {
        
    }

    .min-padding td,
    .min-padding th {
        padding: 0 10px;
    }
</style>
</head>
<body>

{{-- БЛОК ЗАТВЕРДЖЕННЯ --}}
<table class="no-border section">
    <tr>
        <td width="50%">
            <div class="bold">ЗАТВЕРДЖУЮ</div>
            {{ $act->contractor->name }}<br><br>
            ___________________________<br>
            {{ $act->contractor->full_name }}
        </td>

        <td width="50%" class="text-right">
            <div class="bold">ЗАТВЕРДЖУЮ</div>
            Директор {{ $act->customer_data['name'] ?? $act->customer?->name }}<br><br>
            ___________________________<br>
            {{ $act->customer_data['director'] ?? $act->customer?->in_the_person_of }}
        </td>
    </tr>
</table>


{{-- НАЗВА --}}
<div class="text-center section">
    <div class="" style="font-size: 16px;">
        Акт здачі-приймання робіт (надання послуг)
    </div>
    № {{ $act->number }} від {{ $act->date?->format('d.m.Y') }} р.
</div>


{{-- ВСТУП --}}
<div class="section">
    Ми, що нижче підписалися, представник Замовника
    <span class="bold">{{ $act->customer_data['name'] ?? $act->customer?->name }}</span>
    та представник Виконавця
    <span class="bold">{{ $act->contractor->name }}</span>,
    склали цей акт про те, що відповідно до:

    <div class="bold" style="margin-top:5px;">
        Договору № {{ $act->agreement_number }} від {{ $act->agreement_date?->format('d.m.Y') }} р.
    </div>
</div>


{{-- ТАБЛИЦЯ ПОСЛУГ --}}
<table class="bordered section">
    <thead>
        <tr>
            <td class="text-center bg-gray-200" width="5%">№</td>
            <td class="text-center bg-gray-200" width="45%">Найменування послуг</td>
            <td class="text-center bg-gray-200" width="10%">Од.</td>
            <td class="text-center bg-gray-200" width="10%">К-сть</td>
            <td class="text-center bg-gray-200" width="15%">Ціна</td>
            <td class="text-center bg-gray-200" width="15%">Сума</td>
        </tr>
    </thead>
    <tbody>
        @foreach($act->items as $item)
        <tr>
            <td class="text-center">{{ $item->sequence_number }}</td>
            <td>{{ $item->service_description }}</td>
            <td class="text-center">{{ $item->unit }}</td>
            <td class="text-center">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($item->amount, 2, ',', ' ') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>


{{-- ПІДСУМКИ --}}
<table class="no-border">
    <tr>
        <td width="60%"></td>
        <td width="40%">
            <table class="min-padding">
                <tr>
                    <td>Всього</td>
                    <td class="text-right">{{ number_format($act->total_amount, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>ПДВ</td>
                    <td class="text-right">{{ number_format($act->vat_amount, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td class="bold">Разом з ПДВ</td>
                    <td class="text-right bold">{{ number_format($act->total_with_vat, 2, ',', ' ') }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>


{{-- СУМА ПРОПИСОМ --}}
@if($act->total_amount_in_words)
<div class="section">
    Загальна вартість робіт (послуг): 
    <span class="bold">{{ $act->total_amount_in_words }}</span>
    <br>
    Сторони претензій одна до одної не мають.
</div>
@endif

{{-- ПІДПИСИ --}}
<table class="no-border signature-block">
    <tr>
        <td width="50%">
            Від Виконавця<br><br>
            ___________________________
        </td>

        <td width="50%" class="text-right">
            Від Замовника<br><br>
            ___________________________
        </td>
    </tr>
</table>


{{-- РЕКВІЗИТИ --}}
<table class="footer min-padding">
    <tr>
        <td>
            <div class="company-name">
                {{ $act->contractor->name }}
            </div>

            @php $req = $act->contractor->requisites ?? []; @endphp

            @if(!empty($req['identification_code']))
                <p><span class="label">Ідентифікаційний номер:</span> {{ $req['identification_code'] }}</p>
            @endif

            @if(!empty($req['legal_address']))
                <p><span class="label">Юридична адреса:</span> {{ $req['legal_address'] }}</p>
            @endif

            @if(!empty($req['physical_address']))
                <p><span class="label">Фізична адреса:</span> {{ $req['physical_address'] }}</p>
            @endif

            @if(!empty($act->contractor->phone))
                <p><span class="label">Тел.:</span> {{ $act->contractor->phone }}</p>
            @endif

            @if(!empty($req['iban']))
                <p><span class="label">IBAN:</span> {{ $req['iban'] }}</p>
            @endif

            @if(!empty($req['bank_name']))
                <p><span class="label">Банк:</span> {{ $req['bank_name'] }}</p>
            @endif

            @if(!empty($req['mfo']))
                <p><span class="label">МФО:</span> {{ $req['mfo'] }}</p>
            @endif
        </td>

        <td>
            <div class="company-name">
                {{ $act->customer_data['name'] ?? $act->customer?->name }}
            </div>

            @php $c = $act->customer_data ?? []; @endphp

            @if(!empty($c['identification_code']))
                <p><span class="label">Ідентифікаційний код ЄДРПОУ:</span> {{ $c['identification_code'] }}</p>
            @endif

            @if(!empty($c['vat_certificate']))
                <p><span class="label">Свідоцтво ПДВ:</span> №{{ $c['vat_certificate'] }}</p>
            @endif

            @if(!empty($c['individual_tax_number']))
                <p><span class="label">Індивідуальний податковий №:</span> {{ $c['individual_tax_number'] }}</p>
            @endif

            @if(!empty($c['bank_name']))
                <p><span class="label">Банк:</span> {{ $c['bank_name'] }}</p>
            @endif

            @if(!empty($c['mfo']))
                <p><span class="label">МФО:</span> {{ $c['mfo'] }}</p>
            @endif

            @if(!empty($c['iban']))
                <p><span class="label">IBAN:</span> {{ $c['iban'] }}</p>
            @endif

            @if(!empty($c['address']))
                <p>
                    <span class="label">Адреса:</span>
                    {{ is_string($c['address']) ? str_replace("\n", ', ', $c['address']) : '' }}
                </p>
            @endif
        </td>
    </tr>
</table>

</body>
</html>