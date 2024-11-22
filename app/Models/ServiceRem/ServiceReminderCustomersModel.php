<?php

namespace App\Models\ServiceRem;

use CodeIgniter\Model;

class ServiceReminderCustomersModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'alm_service_reminder_customers';
    protected $primaryKey       = 'src_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'src_id',
        'src_customer_code',
        'src_rem_master_id',
        'src_chassis_number',
        'src_invoice_date',
        'src_wb_id',
        'src_wb_message_flag',
        'src_send_date',
        'src_delete_flag'
    ];			
}
