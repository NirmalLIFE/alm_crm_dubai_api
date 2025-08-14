<?php

namespace App\Models\ServicePackage;

use CodeIgniter\Model;

class ServicePackagePartsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'sp_parts_master';
    protected $primaryKey       = 'sp_pm_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['sp_pm_id', 'sp_pm_spim_id', 'sp_pm_name', 'sp_pm_code', 'sp_pm_brand', 'sp_pm_category', 'sp_pm_groupname', 'sp_pm_unit_type', 'sp_pm_access', 'sp_pm_price', 'sp_pm_ordering', 'sp_pm_delete_flag'];
}
