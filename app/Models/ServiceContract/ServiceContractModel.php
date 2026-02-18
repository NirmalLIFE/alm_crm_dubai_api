<?php

namespace App\Models\ServiceContract;

use CodeIgniter\Model;

class ServiceContractModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'service_contract';
    protected $primaryKey       = 'sc_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['sc_id', 'sc_v_id', 'sc_contract_tier_id', 'sc_type', 'sc_cust_id', 'sc_valid_from', 'sc_valid_upto', 'sc_contract_duration', 'sc_contract_price', 'sc_created_on', 'sc_created_by', 'sc_updated_on', 'sc_updated_by', 'sc_delete_flag'];

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
