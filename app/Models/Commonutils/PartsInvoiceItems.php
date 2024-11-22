<?php

namespace App\Models\Commonutils;

use CodeIgniter\Model;

class PartsInvoiceItems extends Model
{
    protected $table            = 'alm_spare_invoice_items';
    protected $primaryKey       = 'inv_item_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'inv_item_id',
        'inv_item_master',
        'inv_item_part_number',
        'inv_item_qty',
        'inv_item_return_qty',
        'inv_item_nm_unit_price',
        'inv_item_nm_vat',
        'inv_item_description',
        'inv_item_nm_discount',
        'inv_item_margin',
        'inv_old_item_margin',
        'inv_item_margin_amount',
        'inv_old_item_margin_amount',
        'inv_item_delete_flag'
    ];
}
