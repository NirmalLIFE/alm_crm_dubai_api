<?php

namespace App\Models\Commonutils;

use CodeIgniter\Model;

class PartsInvoiceMaster extends Model
{
    protected $table            = 'alm_spare_invoice_master';
    protected $primaryKey       = 'inv_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'inv_id',
        'inv_nm_id',
        'inv_nm_supplier_id',
        'inv_nm_description',
        'inv_customer_id',
        'inv_vehicle_id',
        'inv_jobcard_no',
        'inv_nm_status',
        'inv_nm_type',
        'inv_nm_purchase_type',
        'inv_nm_branch',
        'inv_nm_sub_total',
        'inv_nm_vat_total',
        'inv_nm_discount',
        'inv_nm_inv_date',
        'inv_alm_margin_total',
        'inv_alm_discount',
        'inv_created_by',
        'inv_created_on',
        'inv_updated_by',
        'inv_updated_on',
        'inv_delete_flag',
        'inv_old_alm_margin_total',
        'inv_old_alm_discount',
        'inv_nm_return_total'
    ];
}
