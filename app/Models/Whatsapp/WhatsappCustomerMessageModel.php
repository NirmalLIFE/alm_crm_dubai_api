<?php

namespace App\Models\Whatsapp;

use CodeIgniter\Model;

class WhatsappCustomerMessageModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'alm_whatsapp_cus_messages';
    protected $primaryKey       = 'alm_wb_msg_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['alm_wb_msg_id', 'alm_wb_msg_master_id', 'alm_wb_msg_source', 'alm_wb_msg_staff_id', 'alm_wb_msg_type', 'alm_wb_msg_content', 'alm_wb_msg_caption', 'alm_wb_msg_status', 'alm_wb_msg_customer', 'alm_wb_msg_reply_id','alm_wb_msg_camp_type', 'alm_wb_msg_created_on', 'alm_wb_msg_updated_on', 'alm_wb_msg_delete_flag'];
}
