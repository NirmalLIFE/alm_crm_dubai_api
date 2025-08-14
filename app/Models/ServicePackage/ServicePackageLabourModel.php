<?php

namespace App\Models\ServicePackage;

use CodeIgniter\Model;

class ServicePackageLabourModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'sp_labours';
    protected $primaryKey       = 'sp_labour_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['sp_labour_id', 'sp_labour_lm_id', 'sp_labour_spmc_id', 'sp_labour_group_seq', 'sp_labour_qty', 'sp_labour_unit', 'sp_labour_applicable', 'sp_labour_optional_flag', 'sp_labour_created_on', 'sp_labour_created_by', 'sp_labour_updated_on', 'sp_labour_updated_by', 'sp_labour_delete_flag'];

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
