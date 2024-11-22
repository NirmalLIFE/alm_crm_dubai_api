<?php

namespace App\Models\User;

use CodeIgniter\Model;

class CallAssignListModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'call_assign_list';
    protected $primaryKey       = 'cagn_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['cagn_id','cagn_user_id','cagn_date_from','cagn_date_to','cagn_created_by','cagn_created_on','cagn_updated_by','cagn_updated_on','cagn_delete_flag','miss_call_time','miss_call_to','miss_call_from','cagn_note','cagn_lead_id','cagn_status','cagn_appoint_date'];

    
}
