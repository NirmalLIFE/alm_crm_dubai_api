<?php

namespace App\Models\User;

use CodeIgniter\Model;

class UserPerformanceModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'user_performance';
    protected $primaryKey       = 'up_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['up_id','up_user_id','up_point','up_date','up_created_on','up_created_by'];
}
