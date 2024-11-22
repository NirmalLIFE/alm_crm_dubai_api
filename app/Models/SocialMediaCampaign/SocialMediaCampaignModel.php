<?php

namespace App\Models\SocialMediaCampaign;

use CodeIgniter\Model;

class SocialMediaCampaignModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'social_media_campaign';
    protected $primaryKey       = 'smc_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['smc_id', 'smc_code', 'smc_ad_id', 'smc_name', 'smc_message', 'smc_status', 'smc_start_date', 'smc_end_date', 'smc_source', 'smc_owner','smc_created_on','smc_created_by','smc_updated_on','smc_updated_by', 'smc_delete_flag'];

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
