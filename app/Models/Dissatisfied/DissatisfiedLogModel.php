<?php

namespace App\Models\Dissatisfied;

use CodeIgniter\Model;

class DissatisfiedLogModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'dissatisfied_log';
    protected $primaryKey       = 'ldl_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ldl_id','ldl_ldm_id','ldl_response','ldl_action','ldl_note','ldl_activity','ldl_created_on','ldl_created_by','ldl_delete_flag'];
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
