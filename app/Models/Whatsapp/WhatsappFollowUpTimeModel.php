<?php

namespace App\Models\Whatsapp;

use CodeIgniter\Model;

class WhatsappFollowUpTimeModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'whatsapp_message_follow_up_time';
    protected $primaryKey       = 'wb_msg_fut_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['wb_msg_fut_id','wb_msg_fut_seq','wb_msg_fut_type','was_mfut_interval','wb_msg_fut_time','wb_msg_fut_delete_flag'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
