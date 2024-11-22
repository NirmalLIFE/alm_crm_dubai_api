<?php

namespace App\Models\Leads;

use CodeIgniter\Model;

class LeadReminderModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'lead_reminder';
    protected $primaryKey       = 'lr_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['lr_id','lr_date','lr_assigned','lr_desc','lr_lead_id','lr_created_on','lr_created_by','lr_updated_on','lr_updated_by','lr_status','lr_delete_flag'];
}
