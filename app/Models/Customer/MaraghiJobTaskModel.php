<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class MaraghiJobTaskModel extends Model
{
    protected $table            = 'cust_job_task_data';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'job_no', 'sr_no', 'ops_code', 'work_status', 'overall_status', 'created_on', 'updated_on', 'delete_flag'
    ];
}
