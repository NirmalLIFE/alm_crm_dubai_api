<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <style>
        /* Page & Body */
        @page {
            margin: 20mm 15mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .body_master {
            padding: 0 15mm;
        }

        /* Header */
        table.header {
            width: 100%;
            margin-bottom: 10px;
            font-size: 10px;
        }

        table.header td {
            vertical-align: top;
        }

        /* Title */
        h5 {
            text-align: center;
            font-size: 14px;
            margin: 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 20px;
        }

        /* Service Items Table */
        table.service-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.service-table th,
        table.service-table td {
            padding: 6px 8px;
            border: 1px solid #ccc;
            font-size: 11px;
        }

        table.service-table th {
            background: #f5f5f5;
            text-align: center;
        }

        table.service-table td:first-child {
            width: 5%;
            text-align: center;
        }

        table.service-table td:nth-child(2) {
            width: 75%;
            text-align: left;
        }

        table.service-table td:nth-child(3) {
            width: 20%;
            text-align: right;
        }

        .even_row {
            background: #fafafa;
        }

        .odd_row {
            background: #fff;
        }

        /* Service Price */
        .service-price {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: #0056d2;
            margin-top: 15px;
        }

        .printed-on {
            position: fixed;
            bottom: 20mm;
            /* Adjust this as needed based on footer height */
            left: 0;
            right: 0;
            text-align: center;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <div class="body_master">

        <!-- Header -->
        <table class="header">
            <tr>
                <td>
                    P.O Box: 9573, 12th Street, Mussafah 33,<br />
                    Abu Dhabi, UAE<br />
                    Tel: (02) 5503552<br />
                    Reference No: <?php echo $ref_no; ?><br />
                </td>
                <td align="right" style="direction: rtl;">
                    صندوق بريد: 9573 ، شارع 12 ، مصفح 33 ،<br />
                    أبوظبي ، الإمارات العربية المتحدة<br />
                    هاتف: (02) 5503552<br />
                    رقم المرجع: <?php echo $ref_no; ?>
                </td>
            </tr>
        </table>

        <!-- Title -->
        <?php if (isset($km_id) && $km_id == 1): ?>
            <h5><?= htmlspecialchars($km_value) ?></h5>
        <?php else: ?>
            <h5>SERVICE FOR <?= htmlspecialchars($km_value) ?> KM</h5>
        <?php endif; ?>

        <?php if (!empty($items)): ?>
            <table class="service-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Item</th>
                        <?php if ($type != 0): ?>
                            <th>Price</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grandCost = 0;
                    $unselectedTotal = 0;
                    $groupedItems = []; // Holds grouped items

                    // Step 1: Group items by group_seq (skip unselected)
                    foreach ($items as $item) {
                        $isUnselected = false;

                        if ($item['item_type'] == 0 && in_array($item['sp_spare_id'], (array)$unselected_spare)) {
                            $isUnselected = true;
                            $unselectedTotal += ($item['sp_spare_qty'] ?? 0) * ($item['pm_price'] ?? 0);
                            continue;
                        }

                        if ($item['item_type'] == 1 && in_array($item['sp_labour_id'], (array)$unselected_labour)) {
                            $isUnselected = true;
                            $unselectedTotal += ($item['sp_labour_unit'] ?? 0) * ($labourFactor ?? 0);
                            continue;
                        }

                        // Get group sequence
                        $groupSeq = ($item['item_type'] == 0)
                            ? ($item['sp_spare_group_seq'] ?? 0)
                            : ($item['sp_labour_group_seq'] ?? 0);

                        $groupedItems[$groupSeq][] = $item;
                    }

                    // Step 2: Sort groups (0 last)
                    ksort($groupedItems);
                    if (isset($groupedItems[0])) {
                        $ungrouped = $groupedItems[0];
                        unset($groupedItems[0]);
                        $groupedItems[999999] = $ungrouped; // Move ungrouped to last
                    }

                    // Step 3: Display grouped items
                    $rowIndex = 0;
                    foreach ($groupedItems as $groupSeq => $itemsInGroup) {
                        foreach ($itemsInGroup as $item) {
                            $cls = ($rowIndex % 2 === 0) ? 'even_row' : 'odd_row';

                            if ($item['item_type'] == 0) {
                                $name = $item['pm_name'] ?? '';

                                // Append pm_code if spare category is "1"
                                if (($item['sp_spare_category'] ?? '') === '1' && !empty($item['pm_code'])) {
                                    $name .= ' (' . $item['pm_code'] . ')';
                                }

                                $total = ($item['sp_spare_qty'] ?? 0) * ($item['pm_price'] ?? 0);
                            } else {
                                $name  = $item['sp_pm_name'] ?? '';
                                $total = ($item['sp_labour_unit'] ?? 0) * ($labourFactor ?? 0);
                            }

                            $grandCost += $total;
                    ?>
                            <tr class="<?= $cls ?>">
                                <td></td>
                                <td><?= htmlspecialchars($name) ?></td>
                                <?php if ($type != 0): ?>
                                    <td><?= number_format($total, 2) ?></td>
                                <?php endif; ?>
                            </tr>
                    <?php
                            $rowIndex++;
                        }
                    }
                    ?>

                    <?php if ($type != 0): ?>
                        <tr>
                            <td colspan="2" style="text-align: right;"><strong>Cost Price</strong></td>
                            <td><strong><?= number_format($grandCost, 2) ?></strong></td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>

            <!-- Final Price -->
            <?php
            $adjustedDisplayPrice = ($display_price ?? 0) - $unselectedTotal;
            $finalPrice = max($grandCost, $adjustedDisplayPrice);
            ?>
            <?php if ($type != 0): ?>
                <div class="service-price" style="display: flex; align-items: center; font-size: 16px; font-weight: bold; color: #2563eb;">
                    <span>Service Price : AED <?= number_format($finalPrice, 2) ?></span>
                    <span style="border-left: 1px solid #ccc; margin-left: 8px; padding-left: 3px; font-size: 14px; font-weight: normal; color: #6b7280;">
                        Excluding VAT
                    </span>
                </div>
            <?php endif; ?>





        <?php else: ?>
            <p>No items available.</p>
        <?php endif; ?>


    </div>
    <div class="printed-on">
        <hr style="margin-top: 10px; border: 0; border-top: 1px solid #999;" />
        Print by: <?php echo $print_by; ?> &nbsp;|&nbsp;
        Printed on: <?= date('d-m-Y h:i A') ?>
    </div>
</body>

</html>