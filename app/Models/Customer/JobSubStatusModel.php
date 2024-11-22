<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class JobSubStatusModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'job_sub_statuses';
    protected $primaryKey       = 'jbs_status_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['jbs_status_id',	'jbs_master_status','jbs_sub_status','jbs_created_by','jbs_created_on','jbs_updated_by','jbs_updated_on','jbs_delete_flag'];

}
