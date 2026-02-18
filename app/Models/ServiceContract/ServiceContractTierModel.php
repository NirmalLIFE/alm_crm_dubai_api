<?php

namespace App\Models\ServiceContract;

use CodeIgniter\Model;

class ServiceContractTierModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'service_contract_tier';
    protected $primaryKey       = 'sct_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['sct_id', 'sct_name', 'sct_services', 'sct_pickup_and_drop', 'sct_excess_discount', 'sct_sa_incentive', 'sct_discount', 'sct_visit_frequency_month', 'sct_created_by', 'sct_created_on', 'sct_updated_by', 'sct_updated_on', 'sct_delete_flag'];

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
