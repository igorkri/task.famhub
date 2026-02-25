<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <title>Акти виконаних робіт</title>

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

        html, body {
            background: #ffffff;
        }

        body {
            margin: 20px 25px;
        }

        /* Для фото- та офісної печаті: явний білий фон, збереження кольорів */
        @media print {
            html, body {
                background: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-size: 16px; }

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

        .min-padding td,
        .min-padding th {
            padding: 0 10px;
        }

        /* Кожен акт — окрема сторінка в PDF */
        .act-page {
            page-break-after: always;
        }

        .act-page:last-child {
            page-break-after: auto;
        }
    </style>
</head>
<body>

@foreach($acts as $act)
<div class="act-page">
    @include('contractor-act-of-completed-works.partials.act-content', ['act' => $act])
</div>
@endforeach

</body>
</html>
