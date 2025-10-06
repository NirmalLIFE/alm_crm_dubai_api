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
        /* * {
            box-sizing: border-box;
        } */

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
        }

        table.customer {
            margin-left: 40px;
            margin-right: 40px;
            margin-bottom: 0;
            border: none;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            width: 100%;
        }

        table.customers {
            border-collapse: collapse;
            border: 1px solid #000;
            width: 100%;
            margin-top: 5px;
            font-size: 14px;
            font-family: DejaVu Sans, sans-serif;
        }

        table.customers .spacer td {
            height: 4px !important;
        }

        table.customers td {
            padding: 0px 5px 0px 5px;
            text-align: center;
            height: 19px;
            font-size: 10px;
        }

        table.customers th {
            padding: 5px;
            border-bottom: 1px solid #000;
            color: #000;
            font-size: 8px;
        }

        table.customers .even_row {
            background-color: #d3d3d3;
        }

        table.customers .odd_row {
            background-color: #f9f9f9;
        }

        .column {
            width: 50%;
            float: left;
        }

        .column1 {
            width: 50%;
            float: left;
            margin-left: 3px;
        }

        .row {
            width: 90%;
            float: left;
        }

        .row2 {
            width: 100%;
        }

        table,
        tr,
        td,
        th,
        tbody,
        thead,
        tfoot {
            page-break-inside: avoid !important;
        }
    </style>
</head>

<body style="box-sizing: border-box;">

    <!-- <div class="abc" id="content"> -->
    <table class="header">
        <tr>
            <td>P.O Box: 9573, 12th Street, Mussafah 33,<br> Abu Dhabi, UAE</td>
            <td align="right" style="direction: rtl;">صندوق بريد: 9573 ، شارع 12 ، مصفح 33 ،<br> أبوظبي ، الإمارات العربية المتحدة</td>
        </tr>
        <tr>
            <td>Tel: (02)5503552 </td>
            <td align="right">هاتف: (02) 5503552 </td>
        </tr>
    </table>
    <table class="customer">
        <tr>
            <td colspan="2" align="center">
                <h5 style="font-family: DejaVu Sans, sans-serif;text-align: center;">QUOTATION - <?= $qt_master['qt_code']; ?></h5>
            </td>
        </tr>
        <tr>
            <td>Customer: <b><?= $qt_master['qt_cus_name']; ?></b></td>
            <td align="right">Service Advisor: <?= $qt_master['sa_name']; ?></td>
        </tr>
        <tr>
            <td>Contact: <?= $qt_master['qt_cus_contact']; ?></td>
            <td align="right">SA Email:<?= $qt_master['sa_email']; ?></td>
        </tr>
        <tr>
            <td>Make/Model/Year: <?= $qt_master['qt_make']; ?></td>
            <td align="right">Reference: <?= $qt_master['qt_jc_no']; ?></td>
        </tr>
        <tr>
            <td>Chassis Number: <?= $qt_master['qt_chasis']; ?></td>
            <td align="right">Date: <?= date_format(date_create($qt_version2['qvm_created_on']), "Y M d H:i:s"); ?></td>
        </tr>
        <tr>
            <td>Reg No: <?= $qt_master['qt_reg_no']; ?></td>
            <td align="right">TRN No: 100262598400003 </td>
        </tr>
    </table>
    <div class="row" style="margin-left: 40px;margin-right: 40px;">
        <div class="column">
            <?php if ($qt_version1_flag == 1) : ?>
                <h5 style="margin:5px 0 0 0;">Option A (Recommended)</h5>
            <?php else : ?>
                <h5 style="margin:5px 0 0 0;">Option A</h5>
            <?php endif; ?>
            <?php if (!empty($qt_version1['qvm_quote_label'])) { ?>
                <span style="font-size: 10px;"><?= $qt_version1['qvm_quote_label']; ?></span><?php } ?>
            <?php if (empty($qt_version1['qvm_quote_label'])) { ?>
                <span style="color: #fff;font-size: 10px;">NIL</span><?php } ?>
            <table class="customers">
                <thead>
                    <tr>
                        <th class="text-center" style="text-align: left;width:10%;">S. No</th>
                        <th class="text-center" style="text-align: left;width:40%;">Description </th>
                        <th class="text-center" style="text-align: left;width:10%;">Qty.</th>
                        <th class="text-center" style="text-align: right;width:20%;">Unit Price</th>
                        <th class="text-center" style="text-align: right;width:20%;">Net Price</th>

                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sub_total = 0;
                    $tot_row = 0;
                    $i = 0;
                    $even = true;
                    foreach ($qt_group1 as $group_items) { ?>
                        <?php
                        $j = 0;
                        $even = !$even;
                        foreach ($group_items as $line_item) {
                            $sub_total = $sub_total + ($line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty']);
                        ?>
                            <tr class="<?php if ($even) {
                                            echo "even_row";
                                        } else {
                                            echo "odd_row";
                                        }
                                        if (sizeof($group_items) - 1 == $j) {
                                            echo " end_row";
                                        } ?>">
                                <?php if ($j == 0) { ?><td rowspan="<?php echo sizeof($group_items) ?>" style="text-align:left;vertical-align: top;padding-top: 4px;"><?php echo $i + 1; ?> </td><?php } ?>
                                <?php if ($line_item['item_type'] == 1) { ?>
                                    <td style="text-align:left;"><?php echo $line_item['item_name']; ?> <span style="font-weight: 700;">(<?php echo $line_item['brand_name'] . "-" . $line_item['qit_type'] ?>)</span></td>{{ line_item.item_name }}
                                <?php } ?>
                                <?php if ($line_item['item_type'] == 2) { ?><td style="text-align:left;"><?php echo $line_item['item_name']; ?></td><?php } ?>
                                <?php if ($line_item['item_type'] == 3) { ?><td style="text-align:left;"><?php echo $line_item['item_code']; ?></td><?php } ?>
                                <?php if ($line_item['item_type'] == 1 || $line_item['item_type'] == 3) { ?>
                                    <td><?php echo sprintf("%.2f", $line_item['qtvi_qtv_item_qty']); ?></td><?php } ?>
                                <?php if ($line_item['item_type'] == 2) { ?><td><?php echo ""; ?></td><?php } ?>
                                <td style="text-align:right;"><?php echo sprintf("%.2f", $line_item['qtvi_qtv_item_price']); ?></td>
                                <td style="text-align:right;"><?php echo sprintf("%.2f", $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty']); ?></td>
                            </tr>
                            <?php if ($tot_row == 10) { ?>
                                <div style="page-break-after: always;">&nbsp;</div>
                            <?php } ?>
                        <?php $j++;
                            $tot_row++;
                        } ?>
                        <tr class="spacer">
                            <td colspan="5"></td>
                        </tr>
                    <?php $i++;
                    } ?>

                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5">
                            <hr style="border-top: 1px solid #eee">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2" class="text-center" style="text-align: right;">Sub Total</td>
                        <td class="text-center" style="text-align: right;"><b><?php echo number_format((float)$sub_total, 2, '.', '') ?></b></td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2" class="text-center" style="text-align: right;">VAT (5%)</td>
                        <td class="text-center" style="text-align: right;"><?php echo number_format((float)$sub_total * 0.05, 2, '.', '') ?></td>
                    </tr>
                    <?php if (floatval($qt_version1['qvm_discount'] > 0)) { ?><tr>
                            <td colspan="2"></td>
                            <td colspan="2" class="text-center" style="text-align: right;">Discount</td>
                            <td class="text-center" style="text-align: right;"><b>
                                    <?= $qt_version1['qvm_discount']; ?></b></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2" class="text-center" style="text-align: right;"><b>Grand Total</b></td>
                        <td class="text-center" style="text-align: right;">
                            <h4><?php echo number_format((float)$sub_total + (float)$sub_total * 0.05 - (float)$qt_version1['qvm_discount'], 2, '.', '') ?></h4>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" style="text-align: right;text-transform: capitalize;font-size: 9px;"><strong>
                                <?php $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                                echo $f->format(number_format((float)$sub_total + (float)$sub_total * 0.05 - (float)$qt_version1['qvm_discount'], 2, '.', '')) . " Dirhams Only";
                                ?>
                            </strong></td>

                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="column1" style="text-align: right;">
            <?php if ($qt_version2_flag == 1) : ?>
                <h5 style="margin:5px 0 0 0;">Option B (Recommended)</h5>
            <?php else : ?>
                <h5 style="margin:5px 0 0 0;">Option B</h5>
            <?php endif; ?>
            <?php if (!empty($qt_version2['qvm_quote_label'])) { ?>
                <span style="font-size: 10px;"><?= $qt_version2['qvm_quote_label']; ?></span><?php } ?>
            <?php if (empty($qt_version2['qvm_quote_label'])) { ?>
                <span style="color: #fff;font-size: 10px;">NIL</span><?php } ?>
            <table class="customers">
                <thead>
                    <tr>
                        <th class="text-center" style="text-align: left;width:10%;">S. No</th>
                        <th class="text-center" style="text-align: left;width:40%;">Description </th>
                        <th class="text-center" style="text-align: left;width:10%;">Qty.</th>
                        <th class="text-center" style="text-align: right;width:20%;">Unit Price</th>
                        <th class="text-center" style="text-align: right;width:20%;">Net Price</th>

                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sub_total = 0;
                    $i = 0;
                    $even = true;
                    foreach ($qt_group2 as $group_items) { ?>
                        <?php
                        $j = 0;
                        $even = !$even;
                        foreach ($group_items as $line_item) {
                            $sub_total = $sub_total + ($line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty']);
                        ?>
                            <tr class="<?php if ($even) {
                                            echo "even_row";
                                        } else {
                                            echo "odd_row";
                                        } ?>">
                                <?php if ($j == 0) { ?><td rowspan="<?php echo sizeof($group_items) ?>" style="text-align:left;vertical-align: top;padding-top: 4px;"><?php echo $i + 1; ?> </td><?php } ?>
                                <?php if ($line_item['item_type'] == 1) { ?>
                                    <td style="text-align:left;"><?php echo $line_item['item_name']; ?> <span style="font-weight: 700;">(<?php echo $line_item['brand_name'] . "-" . $line_item['qit_type'] ?>)</span></td>{{ line_item.item_name }}
                                <?php } ?>
                                <?php if ($line_item['item_type'] == 3) { ?><td style="text-align:left;"><?php echo $line_item['item_code']; ?></td><?php } ?>
                                <?php if ($line_item['item_type'] == 2) { ?><td style="text-align:left;"><?php echo $line_item['item_name']; ?></td><?php } ?>
                                <?php if ($line_item['item_type'] == 1  || $line_item['item_type'] == 3) { ?>
                                    <td style=""><?php echo sprintf("%.2f", $line_item['qtvi_qtv_item_qty']); ?></td><?php } ?>
                                <?php if ($line_item['item_type'] == 2) { ?><td><?php echo ""; ?></td><?php } ?>
                                <td style="text-align:right;"><?php echo sprintf("%.2f", $line_item['qtvi_qtv_item_price']); ?></td>
                                <td style="text-align:right;"><?php echo sprintf("%.2f", $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty']); ?></td>
                            </tr>
                        <?php $j++;
                        } ?>
                        <tr class="spacer">
                            <td colspan="5"></td>
                        </tr>
                    <?php $i++;
                    } ?>

                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5">
                            <hr style="border-top: 1px solid #eee">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2" class="text-center" style="text-align: right;">Sub Total</td>
                        <td class="text-center" style="text-align: right;"><b><?= $qt_version2['qvm_spare_total'] + $qt_version2['qvm_labour_total']; ?></b></td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2" class="text-center" style="text-align: right;">VAT (5%)</td>
                        <td class="text-center" style="text-align: right;"><?php echo number_format((float)$sub_total * 0.05, 2, '.', '') ?></td>
                    </tr>
                    <?php if (floatval($qt_version2['qvm_discount'] > 0)) { ?>
                        <tr>
                            <td colspan="2"></td>
                            <td colspan="2" class="text-center" style="text-align: right;">Discount</td>
                            <td class="text-center" style="text-align: right;"><b>
                                    <?= $qt_version2['qvm_discount']; ?></b></td>
                        </tr>
                    <?php } ?>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2" class="text-center" style="text-align: right;"><b>Grand Total</b></td>
                        <td class="text-center" style="text-align: right;">
                            <h4><?php echo number_format((float)$sub_total + (float)$sub_total * 0.05 - (float)$qt_version2['qvm_discount'], 2, '.', '') ?></h4>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" style="text-align: right;text-transform: capitalize;font-size: 9px;"><strong>
                                <?php $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                                echo $f->format(number_format((float)$sub_total + (float)$sub_total * 0.05 - (float)$qt_version2['qvm_discount'], 2, '.', '')) . " Dirhams Only";
                                ?>
                            </strong></td>

                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="row2">
        <table class="customer">
            <tr>
                <td>
                    <hr />
                </td>
            </tr>
            <tr>
                <td><span class="body_master" style=" font-family: DejaVu Sans, sans-serif;margin-left: 40px;margin-right: 40px;">
                        <b>Notes</b></span></td>
            </tr>
            <?php if (!empty($qt_version1['qvm_note'])) { ?><tr>
                    <td style="vertical-align: top;">
                        <pre style="margin: 0; font-family: DejaVu Sans, sans-serif; font-size:12px;">
Option A : <?= htmlspecialchars($qt_version1['qvm_note']) ?>
    </pre>
                    </td>
                </tr><?php } ?>
            <?php if (!empty($qt_version2['qvm_note'])) { ?><tr>
                    <td style="vertical-align: top;">
                        <pre style="margin: 0; font-family: DejaVu Sans, sans-serif; font-size:12px;">
Option B : <?= htmlspecialchars($qt_version2['qvm_note']) ?>
    </pre>
                    </td>
                </tr><?php } ?>

            <tr>
                <td style="padding-top: 5%;"><span style=" font-family: DejaVu Sans, sans-serif;font-size:12px;text-align: justify; text-justify: inter-word;">
                        All information contained within this quote is valid only during this offer period. Thereafter, all prices are subject to change.
                    </span></td>
            </tr>
        </table>
    </div>
    <!-- <hr> -->
    <!-- <div class="column" style="text-align: left;">
            <span style=" font-family: DejaVu Sans, sans-serif;">
                Notes</span>
            <br>
            <br>

            <br>

        </div> -->
    <!-- <div class="column1" style="text-align: left;">
            <span style=" font-family: DejaVu Sans, sans-serif;">
                Notes</span>
            <br>
            <br>
            <span style="font-size: 10px;">version notes </span>
        </div> -->
    <!-- <span style=" font-family: DejaVu Sans, sans-serif;">
            Notes</span>
        <br>
        <br>
        <span style=" font-family: DejaVu Sans, sans-serif;font-size:12px;text-align: justify; text-justify: inter-word;">
        All information contained within this quote is valid only during this offer period. Thereafter, all prices are subject to change.
        </span> -->
    <br>
    <!-- <div class="row" style="text-align: center;">
            <h6 style="text-align: center;letter-spacing: 2px;font-family: DejaVu Sans, sans-serif;">www.benzuae.com </h4>
        </div> -->
    <!-- </div> -->

</body>

</html>