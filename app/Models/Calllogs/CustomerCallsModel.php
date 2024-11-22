<?php

namespace App\Models\Calllogs;

use CodeIgniter\Model;

class CustomerCallsModel extends Model
{
    protected $table            = 'cust_call_logs';
    protected $primaryKey       = 'call_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['call_id','time_start','call_type','call_from','call_to','call_duration','talk_duration','status','call_delete_flag','created_on'];
}
