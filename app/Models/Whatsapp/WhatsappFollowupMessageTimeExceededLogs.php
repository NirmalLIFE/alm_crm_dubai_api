<?php

namespace App\Models\Whatsapp;

use CodeIgniter\Model;

class WhatsappFollowupMessageTimeExceededLogs extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'alm_whatsapp_cus_msg_follow_up_time_exceed_logs';
    protected $primaryKey       = 'alm_wb_msg_fut_exc_id ';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['alm_wb_msg_fut_exc_id ','alm_wb_msg_fut_exc_wb_cus_id','alm_wb_msg_fut_exc_wb_msg_id','alm_wb_msg_fut_exc_time','alm_wb_msg_fut_exc_by','alm_wb_msg_fut_exc_delete_flag'];

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
