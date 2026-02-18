<?php

namespace App\Models\ServiceContract;

use CodeIgniter\Model;

class ServiceContractVehicleModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'service_contract_vehicle';
    protected $primaryKey       = 'scv_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['scv_id', 'scv_vid', 'scv_vin_no', 'scv_reg_no', 'scv_model_year', 'scv_vehicle_model', 'scv_kilometer_from', 'scv_created_by', 'scv_created_on', 'scv_updated_by', 'scv_updated_on', 'scv_delete_flag'];

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
