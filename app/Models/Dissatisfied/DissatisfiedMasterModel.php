<?php

namespace App\Models\Dissatisfied;

use CodeIgniter\Model;

class DissatisfiedMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'dissatisfied_master';
    protected $primaryKey       = 'ldm_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ldm_id','ldm_ldl_id','ldm_psf_id','ldm_type','ldm_status','ldm_assign','ldm_created_on','ldm_created_by','ldm_updated_on','ldm_updated_by','ldm_delete_flag'];

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
