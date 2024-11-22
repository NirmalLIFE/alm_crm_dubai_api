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
                <h5 style="font-family: DejaVu Sans, sans-serif;text-align: center;">QUOTATION - <?= $qt_master['qt_code'] . "_V" . $qt_versions['qvm_version_no']; ?></h5>
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
            <td align="right">Date: <?= date_format(date_create($qt_versions['qvm_created_on']), "Y M d H:i:s"); ?></td>
        </tr>
        <tr>
            <td>Reg No: <?= $qt_master['qt_reg_no']; ?></td>
            <td align="right">TRN No: 100262598400003 </td>
        </tr>
    </table>

    <table class="customers body_master">
        <thead>
            <tr>
                <th style="width: 10%; text-align: center;">Sl No</th>
                <?php if ($type == 1) { ?>
                    <th style="width: 30%; text-align: center;">Description</th>
                    <th style="width: 20%; text-align: center;">Availability</th>
                    <th style="width: 10%; text-align: center;">Quantity</th>
                    <th style="width: 15%; text-align: right;">Unit Price</th>
                    <th style="width: 15%; text-align: right;">Net Price</th>
                <?php } ?>
                <?php if ($type == 2) { ?>
                    <th style="width: 30%; text-align: center;">Description Of Work</th>
                    <th style="width: 20%; text-align: center;">Condition</th>
                    <th style="width: 20%; text-align: center;">Priority</th>
                    <th style="width: 12.5%; text-align: right;">Labour</th>
                    <th style="width: 12.5%; text-align: right;">Parts</th>
                <?php } ?>
                <?php if ($type == 3) { ?>
                    <th style="width: 50%; text-align: center;">Description Of Work</th>
                    <th style="width: 20%; text-align: center;">Condition</th>
                    <th style="width: 10%; text-align: center;">Priority</th>
                    <th style="width: 15%; text-align: right;">Price</th>
                <?php } ?>
            </tr>
        </thead>

        <?php if ($type == 1) { ?>
            <tbody>
                <?php
                $sub_total = 0;
                $i = 0;
                $even = true;
                foreach ($qt_group as $group_items) { ?>
                    <?php
                    $j = 0;
                    $even = !$even;
                    foreach ($group_items as $line_item) {
                        $sub_total = $sub_total + ($line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty']); ?>
                        <tr class="<?php if ($even) {
                                        echo "even_row";
                                    } else {
                                        echo "odd_row";
                                    } ?>">
                            <?php if ($j == 0) { ?><td style="width:7%;text-align:left;vertical-align: top;"><?php echo $i + 1; ?> </td><?php } ?>
                            <?php if ($j != 0) { ?><td style="width:7%;text-align:left;vertical-align: top;font-size: 0;"></td><?php } ?>
                            <?php if ($line_item['item_type'] == 1) { ?>
                                <td style="width:34%;text-align:left;"><?php echo $line_item['item_name']; ?> <span style="font-weight: 700;">(<?php echo $line_item['brand_name'] . "-" . $line_item['qit_type'] ?>)</span></td>{{ line_item.item_name }}
                            <?php } ?>
                            <?php if ($line_item['item_type'] == 3) { ?><td style="width:34%;text-align:left;"><?php echo $line_item['item_code']; ?></td><?php } ?>
                            <?php if ($line_item['item_type'] == 2) { ?><td style="width:34%;text-align:left;"><?php echo $line_item['item_name']; ?></td><?php } ?>
                            <?php if ($line_item['item_type'] == 1) { ?><td style="width:20%;text-align:center;"><?php echo $line_item['qit_availability']; ?></td><?php } ?>
                            <?php if ($line_item['item_type'] == 2 || $line_item['item_type'] == 3) { ?><td style="width:20%;"><?php echo ""; ?></td><?php } ?>
                            <?php if ($line_item['item_type'] == 1 || $line_item['item_type'] == 3) { ?>
                                <td style="width:9%;"><?php echo sprintf("%.2f", $line_item['qtvi_qtv_item_qty']); ?></td><?php } ?>
                            <?php if ($line_item['item_type'] == 2) { ?><td style="width:9%;"><?php echo ""; ?></td><?php } ?>
                            <td style="width:15%;text-align:right;"><?php echo sprintf("%.2f", $line_item['qtvi_qtv_item_price']); ?></td>
                            <td style="width:15%;text-align:right;"><?php echo sprintf("%.2f", $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty']); ?></td>
                        </tr>
                    <?php $j++;
                    } ?>
                <?php $i++;
                } ?>

            </tbody>
        <?php } ?>
        <?php if ($type == 2) { ?>
            <tbody>
                <?php
                $i = 0;
                $sub_total = 0;
                $total_labour_price = 0;
                $total_parts_price = 0;

                foreach ($qt_group as $group_items) {
                    $i++;
                    $group_labour_price = 0;
                    $group_parts_price = 0;
                    $first_item = true;
                    $labour_count = 0;


                    foreach ($group_items as $line_item) {
                        $sub_total = $sub_total + ($line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty']);
                        if ($line_item['item_type'] == 2 || $line_item['item_type'] == 3) {
                            $group_labour_price += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                            $total_labour_price += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                            $labour_count++;
                        } elseif ($line_item['item_type'] == 1) {
                            $group_parts_price += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                            $total_parts_price += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                        }
                    }
                    foreach ($group_items as $line_item) {
                        if ($first_item) {
                ?>
                            <tr>
                                <td><?php echo $i; ?></td>
                                <?php if ($line_item['item_type'] == 2 || $line_item['item_type'] == 3) { ?>
                                    <td style="text-align:left;">
                                        <?php if ($line_item['item_type'] == 3) {
                                            echo $line_item['item_code'];
                                        } else {
                                            echo $line_item['item_name'];
                                        } ?>
                                    </td>
                                    <!-- <td><?php echo $line_item['item_name']; ?></td> -->
                                    <td>
                                        <?php if ($line_item['item_type'] == 3) {
                                            echo '';
                                        } else {
                                            echo $line_item['item_condition'];
                                        } ?>
                                    </td>
                                    <td style="text-align:center; <?php
                                                                    if ($line_item['item_type'] == 3) {
                                                                        echo 'color: red; font-weight: bold;';
                                                                    } elseif ($line_item['item_priority'] == 'High') {
                                                                        echo 'color: red; font-weight: bold;';
                                                                    } elseif ($line_item['item_priority'] == 'Medium') {
                                                                        echo 'color: #f0f00e; font-weight: bold;';
                                                                    }
                                                                    ?>">
                                        <?php
                                        if ($line_item['item_type'] == 3) {
                                            echo 'High';
                                        } else {
                                            echo $line_item['item_priority'];
                                        }
                                        ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <?php
                                        $value = $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                                        echo number_format($value, 2);
                                        ?>
                                    </td>
                                <?php } ?>
                                <td style="text-align:right;" rowspan="<?php echo $labour_count; ?>"><?php echo number_format($group_parts_price, 2); ?></td>
                            </tr>
                            <?php
                            $first_item = false;
                        } else {
                            if ($line_item['item_type'] == 2 || $line_item['item_type'] == 3) {
                            ?>
                                <tr>
                                    <td></td>
                                    <td style="text-align:left;">
                                        <?php if ($line_item['item_type'] == 3) {
                                            echo $line_item['item_code'];
                                        } else {
                                            echo $line_item['item_name'];
                                        } ?>
                                    </td>
                                    <!-- <td><?php echo $line_item['item_name']; ?></td> -->
                                    <td>
                                        <?php if ($line_item['item_type'] == 3) {
                                            echo '';
                                        } else {
                                            echo $line_item['item_condition'];
                                        } ?>
                                    </td>
                                    <td style="text-align:center; <?php
                                                                    if ($line_item['item_type'] == 3) {
                                                                        echo 'color: red; font-weight: bold;';
                                                                    } elseif ($line_item['item_priority'] == 'High') {
                                                                        echo 'color: red; font-weight: bold;';
                                                                    } elseif ($line_item['item_priority'] == 'Medium') {
                                                                        echo 'color: #f0f00e; font-weight: bold;';
                                                                    }
                                                                    ?>">
                                        <?php
                                        if ($line_item['item_type'] == 3) {
                                            echo 'High';
                                        } else {
                                            echo $line_item['item_priority'];
                                        }
                                        ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <?php
                                        $value = $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                                        echo number_format($value, 2);
                                        ?>
                                    </td>
                                </tr>
                <?php
                            }
                        }
                    }
                }
                $vat_labour = $total_labour_price * 0.05;
                $vat_parts = $total_parts_price * 0.05;
                ?>
            </tbody>
        <?php } ?>
        <!-- <?php if ($type == 3) { ?>
            <tbody>
                <?php
                    $sub_total = 0;
                    $total_labour_price = 0;
                    $total_parts_price = 0;
                    $group_count = 0;


                    foreach ($qt_group as $group_items) {
                        $group_count++;
                        $group_price = 0;
                        $labour_items = [];
                        $labour_count = 0;

                        foreach ($group_items as $line_item) {
                            $sub_total += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                            if ($line_item['item_type'] == 2 || $line_item['item_type'] == 3) {
                                $group_price += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                                $total_labour_price += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                                $labour_items[] = [
                                    'itemName' => $line_item['item_name'],
                                    'itemCondition' => $line_item['item_condition'],
                                    'itemPriority' => $line_item['item_priority'],
                                    'item_type' => $line_item['item_type'],
                                    'item_code' => $line_item['item_code'],
                                ];
                                $labour_count++;
                            } elseif ($line_item['item_type'] == 1) {
                                $group_price += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                                $total_parts_price += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                            }
                        }


                ?>
                    <tr>
                        <td><?php echo $group_count; ?></td>
                        <td style="text-align:left;">
                            <?php if ($labour_items[0]['item_type'] == 3) {
                                echo $labour_items[0]['item_code'];
                            } else {
                                echo $labour_items[0]['itemName'];
                            } ?>
                        </td>
                        <td>
                            <?php if ($labour_items[0]['item_type'] == 3) {
                                echo '';
                            } else {
                                echo $labour_items[0]['itemCondition'];
                            } ?>
                        </td>
                        <?php
                        $priority = $labour_items[0]['item_type'] == 3 ? 'High' : ($labour_items[0]['itemPriority'] ?? '');
                        ?>
                        <td style="text-align:center;<?php echo isset($priority) ? ($priority == 'High' ? 'color: red; font-weight: bold;' : ($priority == 'Medium' ? 'color: #f0f00e; font-weight: bold;' : '')) : ''; ?>">
                            <?php echo $priority; ?>
                        </td>
                        <td style="text-align:right;" rowspan="<?php echo $labour_count; ?>"><?php echo number_format($group_price, 2); ?></td>
                    </tr>
                    <?php
                        for ($i = 1; $i < count($labour_items); $i++) {
                    ?>
                        <tr>
                            <td></td>
                            <td style="text-align:left;">
                                <?php if ($labour_items[$i]['item_type'] == 3) {
                                    echo $labour_items[$i]['item_code'];
                                } else {
                                    echo $labour_items[$i]['itemName'];
                                } ?>
                            </td>
                            <td>
                                <?php if ($labour_items[$i]['item_type'] == 3) {
                                    echo '';
                                } else {
                                    echo $labour_items[$i]['itemCondition'];
                                } ?>
                            </td>
                            <?php
                            $priority = $labour_items[$i]['item_type'] == 3 ? 'High' : ($labour_items[$i]['itemPriority'] ?? '');
                            ?>
                            <td style="text-align:center;<?php echo isset($priority) ? ($priority == 'High' ? 'color: red; font-weight: bold;' : ($priority == 'Medium' ? 'color: #f0f00e; font-weight: bold;' : '')) : ''; ?>">
                                <?php echo $priority; ?>
                            </td>
                            <td></td>
                        </tr>
                <?php
                        }
                    }
                    $vat_labour = $total_labour_price * 0.05;
                    $vat_parts = $total_parts_price * 0.05;
                ?>
            </tbody>
        <?php } ?> -->
        <?php if ($type == 3) { ?>
            <tbody>
                <?php
                $sub_total = 0;
                $total_labour_price = 0;
                $total_parts_price = 0;
                $group_count = 0;

                foreach ($qt_group as $group_items) {
                    $group_count++;
                    $group_price = 0;
                    $labour_items = [];
                    $labour_count = 0;
                    $item_names = [];

                    $group_priority = 'Low';
                    $group_condition = '';

                    foreach ($group_items as $line_item) {
                        $sub_total += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                        if ($line_item['item_type'] == 2 || $line_item['item_type'] == 3) {
                            $group_price += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                            $total_labour_price += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                            $labour_count++;
                            $item_names[] = ($line_item['item_type'] == 2) ? $line_item['item_name'] : $line_item['item_code'];

                            if ($line_item['item_priority'] == 'High' && $group_priority != 'High') {
                                $group_priority = 'High';
                                $group_condition = $line_item['item_condition'];
                            } elseif ($line_item['item_priority'] == 'Medium' && $group_priority != 'High') {
                                $group_priority = 'Medium';
                                $group_condition = $line_item['item_condition'];
                            } elseif ($group_priority == 'Low' && $group_priority != 'Medium' && $group_priority != 'High') {
                                $group_condition = $line_item['item_condition'];
                            }
                        } elseif ($line_item['item_type'] == 1) {
                            $group_price += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                            $total_parts_price += $line_item['qtvi_qtv_item_price'] * $line_item['qtvi_qtv_item_qty'];
                        }
                    }

                    // Adjust the item names to include "and" before the last item
                    if (count($item_names) > 1) {
                        $last_item = array_pop($item_names);
                        $item_names_str = implode(', ', $item_names) . ' and ' . $last_item;
                    } else {
                        $item_names_str = implode(', ', $item_names);
                    }

                    $priority_style = '';
                    if ($group_priority == 'High') {
                        $priority_style = 'color: red; font-weight: bold;';
                    } elseif ($group_priority == 'Medium') {
                        $priority_style = 'color: #f0f00e; font-weight: bold;';
                    }
                ?>
                    <tr>
                        <td><?php echo $group_count; ?></td>
                        <td style="text-align:left;"><?php echo $item_names_str; ?></td>
                        <td><?php echo $group_condition; ?></td>
                        <td style="text-align:center; <?php echo $priority_style; ?>"><?php echo $group_priority; ?></td>
                        <td style="text-align:right;"><?php echo number_format($group_price, 2); ?></td>
                    </tr>
                <?php
                }
                $vat_labour = $total_labour_price * 0.05;
                $vat_parts = $total_parts_price * 0.05;
                ?>
            </tbody>
        <?php } ?>

        <tfoot>
            <?php if ($type == 1) { ?>
                <tr>
                    <td colspan="6">
                        <hr style="border-top: 1px solid #eee;">
                    </td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="3" class="text-center" style="text-align: right;">Sub Total</td>
                    <td class="text-center" style="text-align: right;"><b><?= $qt_versions['qvm_spare_total'] + $qt_versions['qvm_labour_total']; ?></b></td>
                </tr>
            <?php } ?>
            <?php if ($type == 2) { ?>
                <tr>
                    <td colspan="6">
                        <hr class="mb-0" style="border-top: 1px solid #eee">
                    </td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                    <td style="text-align:center; font-weight:bold;">Total:</td>
                    <td style="text-align:right;"><?php echo sprintf("%.2f", $total_labour_price); ?></td>
                    <td style="text-align:right;"><?php echo sprintf("%.2f", $total_parts_price); ?></td>
                </tr>
                <tr>
                    <td colspan="6">
                        <hr style="border-top: 1px solid #eee">
                    </td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                    <td style="text-align:center; font-weight:bold;">Vat(5%):</td>
                    <td style="text-align:right;"><?php echo sprintf("%.2f", $vat_labour); ?></td>
                    <td style="text-align:right;"><?php echo sprintf("%.2f", $vat_parts); ?></td>
                </tr>
                <tr>
                    <td colspan="6">
                        <hr style="border-top: 1px solid #eee">
                    </td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td class="text-center" style="text-align: right;">
                        <b>
                            <?php
                            $labour_sum = $total_labour_price + $vat_labour;
                            echo sprintf("%.2f", $labour_sum);
                            ?>
                        </b>
                    </td>
                    <td class="text-center" style="text-align: right;">
                        <b>
                            <?php
                            $parts_sum = $total_parts_price + $vat_parts;
                            echo sprintf("%.2f", $parts_sum);
                            ?>
                        </b>
                    </td>
                </tr>
                <tr>
                    <td colspan="6">
                        <hr style="border-top: 1px solid #eee">
                    </td>
                </tr>
            <?php } ?>
            <?php if ($type == 3) { ?>
                <tr>
                    <td colspan="5">
                        <hr style="border-top: 1px solid #eee">
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align:right; font-weight:bold;">Total</td>
                    <td style="text-align:right;">
                        <?php
                        $total_sum = $total_labour_price + $total_parts_price;
                        echo sprintf("%.2f", $total_sum);
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align:right; font-weight:bold;">Vat(5%)</td>
                    <td style="text-align:right;">
                        <?php
                        $total_vat = $vat_labour + $vat_parts;
                        echo sprintf("%.2f", $total_vat);
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align:right; font-weight:bold;">Discount</td>
                    <td style="text-align: right;"><?= $qt_versions['qvm_discount']; ?></td>
                </tr>
                <!-- <tr>
                    <td colspan="4"></td>
                    <td class="text-center" style="text-align: right;">
                        <b>
                            <?php
                            $labour_sum = $total_labour_price + $vat_labour;
                            echo sprintf("%.2f", $labour_sum);
                            ?>
                        </b>
                    </td>
                    <td class="text-center" style="text-align: right;">
                        <b>
                            <?php
                            $parts_sum = $total_parts_price + $vat_parts;
                            echo sprintf("%.2f", $parts_sum);
                            ?>
                        </b>
                    </td>
                </tr> -->
                <tr>
                    <td colspan="5">
                        <hr style="border-top: 1px solid #eee">
                    </td>
                </tr>
            <?php } ?>

            <!-- <tr>
                    <td colspan="2"></td>
                    <td colspan="3" class="text-center" style="text-align: right;">Spare Total</td>
                    <td class="text-center" style="text-align: right;"><b><?= $qt_versions['qvm_spare_total']; ?></b></td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="3" class="text-center" style="text-align: right;">Labour Total</td>
                    <td class="text-center" style="text-align: right;"><b><?= $qt_versions['qvm_labour_total']; ?></b></td>
                </tr> -->

            <?php if ($type == 1) { ?>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="3" class="text-center" style="text-align: right;">VAT (5%)</td>
                    <td class="text-center" style="text-align: right;"><b><?php echo number_format((float)$sub_total * 0.05, 2, '.', '') ?></b></td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td colspan="3" class="text-center" style="text-align: right;">Discount</td>
                    <td class="text-center" style="text-align: right;"><b>
                            <?= $qt_versions['qvm_discount']; ?></b></td>
                </tr>
                <!-- <?php if (floatval($qt_versions['qvm_discount']) > 0) { ?><tr>
                        <td colspan="2"></td>
                        <td colspan="3" class="text-center" style="text-align: right;">Discount</td>
                        <td class="text-center" style="text-align: right;"><b>
                                <?= $qt_versions['qvm_discount']; ?></b></td>
                    </tr><?php } ?> -->
                <tr>
                    <td colspan="2"></td>
                    <td colspan="3" class="text-center" style="text-align: right;"><b>Grand Total</b></td>
                    <td class="text-center" style="text-align: right;">
                        <h3><?php echo number_format((float)$sub_total + (float)$sub_total * 0.05 - (float)$qt_versions['qvm_discount'], 2, '.', '')  ?></h3>
                    </td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: right;text-transform: capitalize;"><strong>
                            <?php $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                            echo $f->format(number_format((float)$sub_total + (float)$sub_total * 0.05 - (float)$qt_versions['qvm_discount'], 2, '.', '')) . " Dirhams Only";
                            ?>
                        </strong></td>

                </tr>
            <?php } ?>
            <?php if ($type == 2) { ?>
                <tr>
                    <td colspan="5" style="text-align: right;"><b>Grand Total</b></td>
                    <td style="text-align: right;">
                        <h3><?php echo number_format((float)$sub_total + (float)$sub_total * 0.05 - (float)$qt_versions['qvm_discount'], 2, '.', '')  ?></h3>
                    </td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: right;text-transform: capitalize;"><strong>
                            <?php $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                            echo $f->format(number_format((float)$sub_total + (float)$sub_total * 0.05 - (float)$qt_versions['qvm_discount'], 2, '.', '')) . " Dirhams Only";
                            ?>
                        </strong></td>

                </tr>
            <?php } ?>
            <?php if ($type == 3) { ?>
                <tr>
                    <td colspan="4" style="text-align: right;"><b>Grand Total</b></td>
                    <td style="text-align: right;">
                        <h3><?php echo number_format((float)$sub_total + (float)$sub_total * 0.05 - (float)$qt_versions['qvm_discount'], 2, '.', '')  ?></h3>
                    </td>
                </tr>
                <tr>
                    <td colspan="5" style="text-align: right;text-transform: capitalize;"><strong>
                            <?php $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                            echo $f->format(number_format((float)$sub_total + (float)$sub_total * 0.05 - (float)$qt_versions['qvm_discount'], 2, '.', '')) . " Dirhams Only";
                            ?>
                        </strong></td>

                </tr>
            <?php } ?>

        </tfoot>
    </table>

    <table class="customer" style="margin-left: 40px;margin-right: 40px;">
        <tr>
            <td>
                <hr>
            </td>
        </tr>
    </table>
    <table class="customer" style="margin-left: 40px;margin-right: 40px;">
        <!-- <tr>
            <td>
                <hr>
            </td>
        </tr> -->

        <tr>
            <td><span class="body_master" style=" font-family: DejaVu Sans, sans-serif;margin-left: 40px;margin-right: 40px;">
                    Notes</span></td>
        </tr>
        <tr>
            <td><span class="body_master" style=" font-family: DejaVu Sans, sans-serif;font-size:12px;text-align: justify; text-justify: inter-word;">
                    <?= $qt_versions['qvm_note']; ?>
                </span></td>
        </tr>
        <tr>
            <td style="padding-top: 5%;"><span style=" font-family: DejaVu Sans, sans-serif;font-size:12px;text-align: justify; text-justify: inter-word;">
                    All information contained within this quote is valid only during this offer period. Thereafter, all prices are subject to change.
                </span></td>
        </tr>
    </table>
    <!-- <div>
        <h6 style="text-align: center;letter-spacing: 2px;font-family: DejaVu Sans, sans-serif;">www.benzuae.com </h4>
    </div> -->
</body>

</html>