<?php

namespace App\Models;

use CodeIgniter\Model;

class SuperAdminModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'superadmin';
    protected $primaryKey       = 's_adm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['s_adm_id','s_adm_name','s_adm_email','s_adm_contact','s_adm_password','s_adm_created_on','s_adm_created_by','s_adm_updated_on','s_adm_updated_by'];

    
}
