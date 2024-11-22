<?php

namespace App\Models\User;

use CodeIgniter\Model;

class UserWorktimeModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'user_work_time';
    protected $primaryKey       = 'uwt_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['uwt_id','uwt_day','uwt_fn_starttime','uwt_fn_endtime','uwt_an_starttime','uwt_an_endtime','uwt_user_id','uwt_created_on','uwt_created_by','uwt_updated_on','uwt_updated_by','uwt_delete_flag','uwt_user_ext'];

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
