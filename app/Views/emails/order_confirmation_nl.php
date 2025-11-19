<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bevestiging van bestelling</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background-color: #006eb8;
            padding: 30px 20px;
            text-align: center;
        }
        .header img {
            max-width: 250px;
            height: auto;
        }
        .content {
            padding: 40px 30px;
        }
        .title {
            color: #e73029;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .greeting {
            color: #333333;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #006eb8;
            padding: 20px;
            margin: 25px 0;
        }
        .info-box h3 {
            color: #006eb8;
            font-size: 18px;
            margin: 0 0 10px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #666666;
            font-weight: bold;
        }
        .info-value {
            color: #333333;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
        }
        .products-table th {
            background-color: #006eb8;
            color: #ffffff;
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }
        .products-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            color: #333333;
            font-size: 14px;
        }
        .products-table tr:last-child td {
            border-bottom: none;
        }
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .delivery-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 25px 0;
        }
        .delivery-box strong {
            color: #856404;
        }
        .contact-box {
            background-color: #e8f4f8;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .contact-box h4 {
            color: #006eb8;
            margin: 0 0 10px 0;
        }
        .contact-box p {
            color: #333333;
            margin: 5px 0;
            font-size: 14px;
        }
        .footer {
            background-color: #333333;
            color: #ffffff;
            padding: 20px;
            text-align: center;
            font-size: 12px;
        }
        .footer a {
            color: #ffffff;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 20px 15px;
            }
            .info-row {
                flex-direction: column;
            }
            .products-table {
                font-size: 12px;
            }
            .products-table th,
            .products-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header met logo -->
        <div class="header">
            <img src="https://actions.trendyfoods.com/stm/uploads/emails/logo.png" alt="Trendy Foods">
        </div>

        <!-- Hoofdinhoud -->
        <div class="content">
            <div class="title">✓ Uw bestelling is bevestigd!</div>
            
            <div class="greeting">
                Goedendag <strong><?= htmlspecialchars($order['company_name']) ?></strong>,
                <br><br>
                We hebben uw bestelling voor de campagne <strong><?= htmlspecialchars($order['campaign_title_nl']) ?></strong> goed ontvangen.
                <br><br>
                Uw bestelling wordt zo snel mogelijk verwerkt.
            </div>

            <!-- Bestelinformatie -->
            <div class="info-box">
                <h3>Details van uw bestelling</h3>

                <div class="info-row">
                    <span class="info-label">Klantnummer:</span>
                    <span class="info-value"><?= htmlspecialchars($order['customer_number'] ?? '') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Besteldatum:</span>
                    <span class="info-value"><?= date('d/m/Y \o\m H:i', strtotime($order['created_at'])) ?></span>
                </div>
            </div>

            <!-- Leveringsdatum indien van toepassing -->
            <?php if ($order['deferred_delivery'] == 1 && !empty($order['delivery_date'])): ?>
            <div class="delivery-box">
                <strong>📦 Verwachte leverdatum vanaf:</strong>
                <?php
                $deliveryDate = new DateTime($order['delivery_date']);
                $formatter = new IntlDateFormatter('nl_BE', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
                echo $formatter->format($deliveryDate);
                ?>
            </div>
            <?php endif; ?>

            <!-- Overzicht producten -->
            <h3 style="color: #006eb8; margin-top: 30px;">Overzicht van uw bestelling</h3>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="text-align: center;">Aantal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalQuantity = 0;
                    foreach ($order['lines'] as $line): 
                        $totalQuantity += $line['quantity'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($line['name_nl']) ?></td>
                        <td style="text-align: center;"><?= htmlspecialchars($line['quantity']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td style="text-align: right;">Totaal artikelen:</td>
                        <td style="text-align: center;"><?= $totalQuantity ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Contactinformatie -->
            <div class="contact-box">
                <h4>Een vraag? Neem contact met ons op</h4>
                <?php if ($order['country'] === 'BE'): ?>
                <p><strong>N.V. TRENDY FOODS BELGIUM</strong></p>
                <p>Rue du Fond des Fourches, 23D</p>
                <p>B-4041 Vottem (Z.I. de Milmort)</p>
                <p>België</p>
                <?php else: ?>
                <p><strong>TRENDY FOODS LUXEMBOURG S.A.</strong></p>
                <p>Z.A.E. Wolser G 331</p>
                <p>L-3434 DUDELANGE</p>
                <p>Luxemburg</p>
                <?php endif; ?>
                <p style="margin-top: 10px;">
                    Email: <a href="mailto:info@trendyfoods.be" style="color: #006eb8;">info@trendyfoods.be</a>
                </p>
            </div>

            <p style="color: #666666; font-size: 14px; margin-top: 30px;">
                Bedankt voor uw vertrouwen.<br>
                <strong>Het Trendy Foods team</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?= date('Y') ?> Trendy Foods. Alle rechten voorbehouden.</p>
            <p style="margin-top: 10px; font-size: 11px; color: #999999;">
                Deze e-mail werd automatisch verzonden, gelieve hier niet op te antwoorden.
            </p>
        </div>
    </div>
</body>
</html>