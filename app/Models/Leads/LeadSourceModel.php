<?php

namespace App\Models\Leads;

use CodeIgniter\Model;

class LeadSourceModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'lead_source';
    protected $primaryKey       = 'ld_src_id';
    protected $useAutoIncrement = true;
     protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ld_src_id','ld_src','ld_src_desc','ld_src_createdon','ld_src_createdby','ld_src_updatedon','ld_src_updatedby','ld_src_dlt'];

    
}
