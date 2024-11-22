<?php

namespace App\Models\User;

use CodeIgniter\Model;

class UserLogTableModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'user_log_table';
    protected $primaryKey       = 'ulg_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ulg_id','ulg_user','ulg_ip','ulg_time','ulg_created_on','ulg_activity','ulg_file'];

   
}
