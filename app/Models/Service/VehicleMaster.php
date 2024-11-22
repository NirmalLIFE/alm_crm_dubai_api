<?php

namespace App\Models\Service;

use CodeIgniter\Model;

class VehicleMaster extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'vehicle_master';
    protected $primaryKey       = 'veh_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['veh_id', 'veh_brand', 'veh_model', 'veh_variant_master', 'veh_variant', 'model_car', 'veh_start_year', 'veh_end_year', 'veh_tech_info', 'veh_engine', 'veh_enginemaster', 'veh_ttc_typeid', 'veh_fuel', 'veh_vingroup_master', 'veh_vingroup', 'veh_vin', 'veh_created_on', 'veh_created_by', 'veh_updated_on', 'veh_delete_flag'];

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
