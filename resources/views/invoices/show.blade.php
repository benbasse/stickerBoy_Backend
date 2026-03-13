<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->invoice_number }}</title>

    <style>
        @page {
            margin: 20mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            color: #1a202c;
        }

        /* ================= HEADER ================= */
        .header {
            background-color: #5a67d8;
            color: #ffffff;
            padding: 20px;
            margin-bottom: 25px;
        }

        .header table {
            width: 100%;
        }

        .company-name {
            font-size: 22pt;
            font-weight: bold;
        }

        .company-tagline {
            font-size: 10pt;
        }

        .invoice-title {
            font-size: 26pt;
            font-weight: bold;
            text-align: right;
        }

        .invoice-number {
            font-size: 12pt;
            text-align: right;
        }

        /* ================= INFO ================= */
        .info-table {
            width: 100%;
            margin-bottom: 25px;
        }

        .info-box {
            border: 1px solid #e2e8f0;
            padding: 12px;
        }

        .info-title {
            font-weight: bold;
            color: #5a67d8;
            margin-bottom: 8px;
        }

        .info-row {
            margin-bottom: 5px;
        }

        /* ================= TABLE ================= */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .items-table thead {
            background-color: #5a67d8;
            color: #ffffff;
        }

        .items-table th {
            padding: 10px;
            font-size: 9pt;
            text-align: left;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* ================= TOTALS ================= */
        .totals-table {
            width: 100%;
            margin-top: 10px;
        }

        .totals-table td {
            padding: 6px;
        }

        .total-label {
            font-weight: bold;
        }

        .grand-total {
            font-size: 14pt;
            font-weight: bold;
            color: #5a67d8;
        }

        /* ================= FOOTER ================= */
        .footer {
            background-color: #1a202c;
            color: #ffffff;
            padding: 15px;
            margin-top: 30px;
            font-size: 9pt;
        }

        .footer table {
            width: 100%;
        }

        .footer-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .footer-note {
            text-align: center;
            margin-top: 10px;
            font-size: 8pt;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="header">
        <table>
            <tr>
                <td style="width:60%;">
                    <div class="company-name">STICKER BOY</div>
                    <div class="company-tagline">Pas une Marque</div>
                </td>
                <td style="width:40%;">
                    <div class="invoice-title">FACTURE</div>
                    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- INFOS -->
    <table class="info-table">
        <tr>
            <td style="width:50%;padding-right:10px;">
                <div class="info-box">
                    <div class="info-title">Client</div>
                    <div class="info-row">
                        {{ $invoice->order->customer->firstname }}
                        {{ $invoice->order->customer->lastname }}
                    </div>
                    <div class="info-row">
                        Téléphone : {{ $invoice->order->customer->phone }}
                    </div>
                    @if($invoice->order->customer->address)
                    <div class="info-row">
                        Adresse de livraison : {{ $invoice->order->customer->address }}
                    </div>
                    @endif
                </div>
            </td>
            <td style="width:50%;padding-left:10px;">
                <div class="info-box">
                    <div class="info-title">Détails</div>
                    <div class="info-row">
                        Date : {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}
                    </div>
                    <div class="info-row">
                        Commande : #{{ $invoice->order->reference }}
                    </div>
                    <div class="info-row">
                        Statut : {{ strtoupper($invoice->status) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- ITEMS -->
    <table class="items-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Produit</th>
                <th class="text-center">Taille</th>
                <th class="text-center">Qté</th>
                <th class="text-right">Prix</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->order->orderItems as $item)
                <tr>
                    <td>
                        @php
                            $img = null;
                            $productName = 'Produit';
                            $imgBase64 = null;

                            if ($item->product_type === 'sticker') {
                                $sticker = $item->sticker ?? \App\Models\Sticker::find($item->product_id);
                                if ($sticker) {
                                    $img = $sticker->image ?? null;
                                    $productName = $sticker->name ?? 'Sticker';
                                }
                            } elseif ($item->product_type === 'tote_bag') {
                                $toteBag = $item->toteBag ?? \App\Models\ToteBag::find($item->product_id);
                                if ($toteBag) {
                                    $img = $toteBag->image ?? null;
                                    $productName = $toteBag->name ?? 'Tote Bag';
                                }
                            }

                            // Construire le chemin complet et encoder en base64
                            if ($img) {
                                $imgPath = storage_path('app/public/' . $img);
                                if (file_exists($imgPath)) {
                                    $type = pathinfo($imgPath, PATHINFO_EXTENSION);
                                    $data = file_get_contents($imgPath);
                                    $imgBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                                }
                            }
                        @endphp
                        @if($imgBase64)
                            <img src="{{ $imgBase64 }}" alt="{{ $productName }}" style="width:40px;height:40px;border-radius:6px;object-fit:cover;border:1px solid #e2e8f0;" />
                        @else
                            <div style="width:40px;height:40px;border-radius:6px;background:#e2e8f0;text-align:center;line-height:40px;color:#aaa;font-size:10px;">N/A</div>
                        @endif
                    </td>
                    <td>{{ $productName }}</td>
                    <td class="text-center">
                        @php
                            $size = $item->size ?? 'medium'; // Par défaut: medium
                        @endphp
                        @switch($size)
                            @case('small')
                                Petit
                                @break
                            @case('medium')
                                Moyen
                                @break
                            @case('large')
                                Grand
                                @break
                            @default
                                Moyen
                        @endswitch
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">
                        {{ number_format($item->unit_price, 0, ',', ' ') }} FCFA
                    </td>
                    <td class="text-right">
                        {{ number_format($item->subtotal, 0, ',', ' ') }} FCFA
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- TOTALS -->
    <table class="totals-table">
        <tr>
            <td style="width:70%;"></td>
            <td class="total-label text-right">Sous-total :</td>
            <td class="text-right">
                {{ number_format($invoice->order->orderItems->sum('subtotal'), 0, ',', ' ') }} FCFA
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="grand-total text-right">TOTAL :</td>
            <td class="grand-total text-right">
                {{ number_format($invoice->total_amount, 0, ',', ' ') }} FCFA
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        <table>
            <tr>
                <td>
                    <div class="footer-title">Coordonnées</div>
                    Dakar, Sénégal<br>
                    contact@stickerboy.sn<br>
                    +221 XX XXX XX XX
                </td>
                <td>
                    <div class="footer-title">Paiement</div>
                    Orange Money / Wave<br>
                    Paiement à la livraison
                </td>
                <td>
                    <div class="footer-title">Support</div>
                    Assistance client<br>
                    Lun - Sam
                </td>
            </tr>
        </table>

        <div class="footer-note">
            Facture générée électroniquement • STICKER BOY © {{ date('Y') }}
        </div>
    </div>

</body>
</html>
