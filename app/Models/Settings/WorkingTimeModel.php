<?php

namespace App\Models\Settings;

use CodeIgniter\Model;

class WorkingTimeModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'company_working_time';
    protected $primaryKey       = 'wt_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['wt_id','wt_start','wt_end','wt_created_on','wt_created_by','wt_updated_on','wt_updated_by','wt_delete_flag'];
}
