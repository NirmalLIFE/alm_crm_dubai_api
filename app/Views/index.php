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
        .img-container {

            margin-bottom: 10px;


        }

        table.header {
            margin-left: 40px;
            margin-right: 40px;
            margin-top: 5px;
            margin-bottom: 5px;
            border: none;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            width: 100%;
        }

        table.customer {
            margin-left: 40px;
            margin-right: 40px;
            margin-bottom: 5px;
            border: none;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            width: 100%;
        }

        table.customers {
            border-collapse: collapse;
            border: none;
            width: 100%;
            margin-top: 5px;
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
            font-size: 12px;
        }
    </style>
</head>

<body>

    <div class="abc" id="content">
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
        <h5 style="font-family: DejaVu Sans, sans-serif;text-align: center;">QUOTATION - <?= $cust['qt_code']; ?></h5>
        <table class="customer">
            <tr>
                <td>Customer: <b><?= $cust['qt_cus_name']; ?></b></td>
                <td align="right">Service Advisor: <?= $cust['sa_name']; ?></td>
            </tr>
            <tr>
                <td>Contact: <?= $cust['qt_cus_contact']; ?></td>
                <td align="right"><?= $cust['sa_email']; ?></td>
            </tr>
            <tr>
                <td>Make/Model/Year: <?= $cust['qt_make']; ?></td>
                <td align="right">Reference: <?= $cust['qt_jc_no']; ?></td>
            </tr>
            <tr>
                <td>Chassis Number: <?= $cust['qt_chasis']; ?></td>
                <td align="right">Date: <?= date_format(date_create($cust['qt_created_on']), "Y M d H:i:s"); ?></td>
            </tr>
            <tr>
                <td>Reg No: <?= $cust['qt_reg_no']; ?></td>
                <td align="right">TRN No: 100262598400003 </td>
            </tr>
        </table>

        <table class="customers">
            <thead>
                <tr>
                    <th class="text-center" style="text-align: left;">Type</th>
                    <th class="text-center" style="text-align: left;">Description</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center" style="text-align: right;">Unit Price</th>
                    <th class="text-center" style="text-align: right;">Total Price</th>
                    <th class="text-center" style="text-align: right;"> Discount(%)</th>
                    <th class="text-center" style="text-align: right;">Net Price</th>

                </tr>
            </thead>
            <tbody>
                <?php
                $grand_total = 0.00;
                $parts_total = 0.00;
                $labour_total = 0.00;
                ?>

                <?php foreach ($item as $it) {
                    $total = $it['unit_price'] *  $it['item_qty'];
                    $net = $total -  ($it['disc_amount'] *  $it['item_qty']);
                    $grand_total = $grand_total + $net;
                    $disc = round((($it['disc_amount'] *  $it['item_qty']) * 100) / $total);
                    if ($it['item_type'] == 1) {
                        $type = "PARTS";
                        $parts_total = $parts_total + $net;
                    } else {
                        $type = "LABOUR";
                        $labour_total = $labour_total + $net;
                    }
                ?>
                    <tr>
                        <td class="text-center" style="text-align: left;"><?= $type; ?></td>
                        <td class="text-center" style="text-align: left;"><?= $it['item_name']; ?></td>
                        <td class="text-center"><?= $it['item_qty']; ?></td>
                        <td class="text-center" style="text-align: right;"><?= number_format((float)$it['unit_price'], 2, '.', ''); ?></td>
                        <td class="text-center" style="text-align: right;"><?= number_format((float)$total, 2, '.', '') ?></td>
                        <td class="text-center" style="text-align: right;"><?= number_format((float)$disc, 2, '.', '') ?></td>
                        <td class="text-center" style="text-align: right;"><?= number_format((float)$net, 2, '.', '') ?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <!-- <td class="text-center">PART</td>
                <td class="text-center">Front torsion bar link</td>
                <td class="text-center">2</td>
                <td class="text-center">406.00</td>               
                <td class="text-center">812.00</td>
                <td class="text-center">50.00</td>	                
                <td class="text-center">406.00</td>            
                </tr>
                <tr>
                  <td class="text-center">PART</td>
                  <td class="text-center">Front Window Switch </td>
                  <td class="text-center">1</td>
                  <td class="text-center">1983.00</td>         
                  <td class="text-center">1983.00</td>
                  <td class="text-center">50.00</td>	
                  <td class="text-center">991.50</td>	            
                  </tr>
                  <tr>
                      <td class="text-center">PART</td>
                      <td class="text-center">Battery</td>
                      <td class="text-center">1</td>
                      <td class="text-center">800.00</td>               
                      <td class="text-center">800.00</td>
                      <td class="text-center">0</td>	                
                      <td class="text-center">800.00</td>            
                      </tr>
                    <tr></tr>
                    <tr>
                        <td class="text-center">SERVICE</td>
                        <td class="text-center">Labour Charges</td>
                        <td class="text-center"></td>
                        <td class="text-center">1850.00</td>               
                        <td class="text-center"></td>
                        <td class="text-center">40.00</td>	                
                        <td class="text-center">1110.00</td> 
                    </tr>                -->

            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7">
                        <hr style="border-top: 1px solid #eee">
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-center" style="text-align: right;">Parts Amount</td>
                    <td class="text-center" style="text-align: right;"><?php echo number_format((float)$parts_total, 2, '.', '') ?></td>

                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-center" style="text-align: right;">Labour Amount</td>
                    <td class="text-center" style="text-align: right;"><?php echo number_format((float)$labour_total, 2, '.', '') ?></td>

                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-center" style="text-align: right;">Subtotal</td>
                    <td class="text-center" style="text-align: right;"><?= number_format((float)$cust['qt_amount'], 2, '.', ''); ?></td>

                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-center" style="text-align: right;">VAT(5%)</td>
                    <td class="text-center" style="text-align: right;"><?= number_format((float)$cust['qt_tax'], 2, '.', ''); ?></td>

                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-center" style="text-align: right;"><strong>Grand Total</strong></td>
                    <td class="text-center" style="text-align: right;"><strong><?= number_format((float)$cust['qt_total'], 2, '.', ''); ?></strong></td>

                </tr>
                <tr>
                    <td colspan="7" style="text-align: right;text-transform: capitalize;"><strong>
                            <?php $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
                            echo $f->format(number_format((float)$cust['qt_total'], 2, '.', '')) . " Dirhams Only";
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

        <table class="customer" style="margin-left: 40px;margin-right: 40px;">
            <tr>
                <td><span style=" font-family: DejaVu Sans, sans-serif;">
                        Notes</span></td>
            </tr>
            <tr>
                <td tyle="padding-top: 5%;">
                    <span style=" font-family: DejaVu Sans, sans-serif;font-size:12px;text-align: justify; text-justify: inter-word;">All information contained within this quote is valid only during this offer period. Thereafter, all prices are subject to change.
                    </span>
                </td>
            </tr>
            <tr>
                <td style="text-align: center;"> 
                    <h5 style="font-family: DejaVu Sans, sans-serif;">Campaign Quotation </h4>
                </td>
            </tr>
        </table>

        <!-- <hr>
        <span style=" font-family: DejaVu Sans, sans-serif;">
            Notes</span>
        <br>
        <br>
        <span style=" font-family: DejaVu Sans, sans-serif;font-size:12px;text-align: justify; text-justify: inter-word;">All information contained within this quote is valid only during this offer period. Thereafter, all prices are subject to change.
        </span> <br>

        <div>
            <h5 style="text-align: center;font-family: DejaVu Sans, sans-serif;">Benz Care Week </h4>
        </div>
        <div>
            <h6 style="text-align: center;letter-spacing: 2px;font-family: DejaVu Sans, sans-serif;">www.benzuae.com </h4>
        </div> -->
</body>

</html>