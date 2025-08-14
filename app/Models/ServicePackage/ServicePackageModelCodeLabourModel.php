<?php

namespace App\Models\ServicePackage;

use CodeIgniter\Model;

class ServicePackageModelCodeLabourModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'sp_model_code_labour';
    protected $primaryKey       = 'spmcl_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'spmcl_id',
        'brand_code',
        'model_code',
        'model_name',
        'family_code',
        'labour_rate',
        'spmcl_inc_pct',
        'spmcl_created_on',
        'spmcl_created_by',
        'spmcl_updated_on',
        'spmcl_updated_by',
        'spmcl_delete_flag'
    ];
}
