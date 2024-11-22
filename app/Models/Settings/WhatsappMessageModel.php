<?php

namespace App\Models\Settings;

use CodeIgniter\Model;

class WhatsappMessageModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'whatsapp_message_master';
    protected $primaryKey       = 'wb_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['wb_id','wb_message_id','wb_message_source','wb_message_status','wb_customer_id','wb_phone','wb_replay_body','wb_created_on','wb_updated_on'];
}
