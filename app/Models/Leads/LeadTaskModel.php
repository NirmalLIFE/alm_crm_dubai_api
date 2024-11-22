<?php

namespace App\Models\Leads;

use CodeIgniter\Model;

class LeadTaskModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'lead_task';
    protected $primaryKey       = 'lt_id';
    protected $useAutoIncrement = true;
     protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['lt_id','task_name','lt_start_date','lt_due_date','lt_assigned','lt_desc','lt_lead_id','lt_created_on','lt_created_by','lt_updated_on','lt_updated_by','lt_status','lt_delete_flag'];

   
}
