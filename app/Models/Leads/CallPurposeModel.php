<?php

namespace App\Models\Leads;

use CodeIgniter\Model;

class CallPurposeModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'call_purposes';
    protected $primaryKey       = 'cp_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['cp_id ','call_purpose','cp_desc','cp_createdon','cp_createdby','cp_updatedon','cp_updatedby','new_cus_display','cp_delete_flag','veh_need'];

    
}
