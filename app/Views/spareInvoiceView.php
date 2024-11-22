<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        /* body { font-family: DejaVu Sans, sans-serif; }  */
        /* #content
{
    width:680px;
    height:700px;
    margin-top: 10px;
    
    margin-left: 10px;
    margin-right: 10px;
    
} */
        .body_master {
            margin-left: 30px !important;
            margin-right: 30px !important;
        }

        .img-container {

            margin-bottom: 10px;


        }

        table.header {
            margin-left: 40px;
            margin-right: 40px;
            margin-top: 5px;
            border: none;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            width: 100%;
            page-break-before: avoid;
        }

        table.customer {
            margin-left: 40px;
            margin-right: 40px;
            margin-bottom: 5px;
            border: none;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            width: 100%;
            page-break-before: avoid;
        }

        table.customers {
            border-collapse: collapse;
            border: none;
            width: 100%;
            font-size: 14px;
            margin-left: 40px;
            margin-right: 40px;
            font-family: DejaVu Sans, sans-serif;
        }

        table.customers td {
            padding: 5px;
            text-align: center;
            height: 25px;
            font-size: 11px;
        }

        table.customers th {
            padding-top: 5px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
            color: #000;
            font-size: 11px;
        }

        table.customers .even_row {
            background-color: #eee;
        }

        table.customers .odd_row {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>
    <table class="header body_master">
        <tr>
            <td>P.O Box: 9573, 12th Street, Mussafah 33,<br> Abu Dhabi, UAE</td>
            <td align="right" style="direction: rtl;">صندوق بريد: 9573 ، شارع 12 ، مصفح 33 ،<br> أبوظبي ، الإمارات العربية المتحدة</td>
        </tr>
        <tr>
            <td>Tel: (02)5503552 </td>
            <td align="right">هاتف: (02) 5503552 </td>
        </tr>
    </table>

    <table class="customer body_master">
        <tr>
            <td colspan="2" align="center">
                <h5 style="font-family: DejaVu Sans, sans-serif;text-align: center;">SPARE INVOICE - <?= $invoice['inv_nm_id'] ?></h5>
            </td>
        </tr>
        <tr>
            <td>Customer: <b><?= $invoice['customer_name']; ?></b></td>
            <td align="right">Service Advisor: <?= $invoice['us_firstname']; ?></td>
        </tr>
        <tr>
            <td>Contact: <?= $invoice['phone']; ?></td>
            <td align="right">SA Email:<?= $invoice['us_email']; ?></td>
        </tr>
        <tr>
            <td>Make/Model/Year: <?= $invoice['model_name']; ?></td>
            <td align="right">Reference: <?= $invoice['inv_jobcard_no']; ?></td>
        </tr>
        <tr>
            <td>Chassis Number: <?= $invoice['chassis_no']; ?></td>
            <td align="right">Date: <?= date_format(date_create($invoice['inv_created_on']), "Y M d H:i:s"); ?></td>
        </tr>
        <tr>
            <td>Reg No: <?= $invoice['reg_no']; ?></td>
            <td align="right">TRN No: 100262598400003 </td>
        </tr>
    </table>

    <table class="customers body_master">
        <thead>
            <tr>
                <th class="text-center" style="text-align: center;">Sl No</th>
                <th class="text-center" style="text-align: center;">Description </th>
                <th class="text-center" style="text-align: center;">Quantity</th>
                <th class="text-center" style="text-align: right;">Amount</th>
                <th class="text-center" style="text-align: right;">VAT</th>
                <th class="text-center" style="text-align: right;">Net Price</th>

            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            $even = true;
            ?>

            <?php foreach ($invoice['items'] as $item) {
            ?>
                <?php
                $even = !$even; ?>
                <tr class="<?php if ($even) {
                                echo "even_row";
                            } else {
                                echo "odd_row";
                            } ?>">
                    <td class="text-center" style="text-align: left;"><?= $i; ?></td>
                    <td class="text-center" style="text-align: left;text-transform: uppercase;"><?= $item['inv_item_description']; ?></td>
                    <td class="text-center"><?= $item['inv_item_qty']; ?></td>
                    <td class="text-center" style="text-align: right;"><?= number_format(((float)$item['inv_item_margin_amount'] / 1.05), 2, '.', ''); ?></td>
                    <td class="text-center" style="text-align: right;"><?= number_format(((float)$item['inv_item_margin_amount'] / 1.05) * 0.05, 2, '.', '') ?></td>
                    <td class="text-center" style="text-align: right;"><?= number_format((float)$item['inv_item_margin_amount'], 2, '.', '') ?></td>

                </tr>
            <?php $i++;
            }; ?>
            <tr>


        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">
                    <hr style="border-top: 1px solid #eee">
                </td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td colspan="3" class="text-center" style="text-align: right;">Sub Total</td>
                <td class="text-center" style="text-align: right;"><b><?= number_format(((float)$invoice['inv_alm_margin_total'] /1.05), 2, '.', ''); ?></b></td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td colspan="3" class="text-center" style="text-align: right;">VAT (5%)</td>
                <td class="text-center" style="text-align: right;"><b><?php echo number_format(((float)$invoice['inv_alm_margin_total'] /1.05) * 0.05, 2, '.', '') ?></b></td>
            </tr>
            <?php if (floatval($invoice['inv_alm_discount']) > 0) { ?><tr>
                    <td colspan="2"></td>
                    <td colspan="3" class="text-center" style="text-align: right;">Discount</td>
                    <td class="text-center" style="text-align: right;"><b>
                            <?= number_format((float)$invoice['inv_alm_discount'], 2, '.', '') ?></b></td>
                </tr><?php } ?>
            <tr>
                <td colspan="2"></td>
                <td colspan="3" class="text-center" style="text-align: right;"><b>Grand Total</b></td>
                <td class="text-center" style="text-align: right;">
                    <h3><?php echo number_format((float)$invoice['inv_alm_margin_total'] - (float)$invoice['inv_alm_discount'], 2, '.', '')  ?></h3>
                </td>
            </tr>
            <tr>
                <td colspan="6" style="text-align: right;text-transform: capitalize;"><strong>
                        <?php $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                        echo $f->format(number_format((float)$invoice['inv_alm_margin_total'] - (float)$invoice['inv_alm_discount'], 2, '.', '')) . " Dirhams Only";
                        ?>
                    </strong></td>

            </tr>
        </tfoot>
    </table>

    <table class="customer" style="margin-left: 40px;margin-right: 40px;">
        <tr>
            <td>
                <hr>
            </td>
        </tr>
    </table>
    <!-- <div>
        <h6 style="text-align: center;letter-spacing: 2px;font-family: DejaVu Sans, sans-serif;">www.benzuae.com </h4>
    </div> -->
</body>

</html>