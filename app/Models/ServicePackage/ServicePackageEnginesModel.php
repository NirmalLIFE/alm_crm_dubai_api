<?php

namespace App\Models\ServicePackage;

use CodeIgniter\Model;

class ServicePackageEnginesModel extends Model
{
    protected $DBGroup          = 'commonDB';
    protected $table            = 'sp_engines';
    protected $primaryKey       = 'speng_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['speng_id', 'speng_eng_id', 'speng_spmc_id', 'speng_created_on', 'speng_delete_flag'];
}
