<?php

namespace App\Models\ServicePackage;

use CodeIgniter\Model;

class ServicePackageSpareModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'sp_spares';
    protected $primaryKey       = 'sp_spare_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['sp_spare_id', 'sp_spare_spmc_id', 'sp_spare_pm_id', 'sp_spare_qty', 'sp_spare_category', 'sp_spare_group_seq', 'sp_spare_labour_unit', 'sp_spare_applicable', 'sp_spare_optional_flag', 'sp_spare_created_on', 'sp_spare_created_by', 'sp_spare_updated_on', 'sp_spare_updated_by', 'sp_spare_delete_flag'];
}
