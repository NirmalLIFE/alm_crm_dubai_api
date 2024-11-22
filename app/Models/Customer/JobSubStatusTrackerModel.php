<?php
namespace App\Models\Customer;

use CodeIgniter\Model;

class JobSubStatusTrackerModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'job_sub_status_changes';
    protected $primaryKey       = 'jbs_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['jbsc_id','jbsc_job_no','jbsc_main_status','jbsc_sub_status','jbsc_sub_description','jbsc_updated_by','jbsc_updated_on','jbsc_delete_flag'];

}