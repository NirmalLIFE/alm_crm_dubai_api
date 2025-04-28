<?php

namespace App\Models\Whatsapp;

use CodeIgniter\Model;

class WhatsappCampaignModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'alm_whatsapp_campaign';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['alm_wb_camp_id','alm_wb_camp_name','alm_wb_camp_type','alm_wb_camp_cust_count','alm_wb_camp_date_from','alm_wb_camp_date_to','alm_wb_camp_created_by','alm_wb_camp_created_on','alm_wb_camp_delete_flag'];

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
