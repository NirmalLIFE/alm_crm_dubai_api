<?php

namespace App\Models\PSFModule;

use CodeIgniter\Model;

class PSFAssignedStaffModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'psf_assigned_staffs';
    protected $primaryKey       = 'psfs_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['psfs_id','psfs_assigned_staff','psfs_psf_type','psfs_created_by','psfs_updated_by','psfs_delete_flag'];
}
