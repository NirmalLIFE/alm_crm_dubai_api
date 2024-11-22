<?php

namespace App\Models;

use CodeIgniter\Model;

class AccessPermissionModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'feature_role_mapping';
    protected $primaryKey       = 'ap_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['frm_id','frm_role_id','frm_feature_id','frm_action_id','frm_created_by','frm_created_on','frm_updated_by','frm_updated_on','frm_deleteflag'];

    
}
