<?php

namespace App\Models\Leads;

use CodeIgniter\Model;

class LeadStatusModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'lead_status';
    protected $primaryKey       = 'ld_sts_id ';
    protected $useAutoIncrement = true;   
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ld_sts_id','ld_sts','ld_sts_desc','ld_sts_createdon','ld_sts_createdby','ld_sts_updatedon','ld_sts_updatedby','ld_sts_edit_flag','ld_sts_deleteflag'];

    
}
