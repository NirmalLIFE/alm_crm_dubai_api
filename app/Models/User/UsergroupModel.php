<?php

namespace App\Models\User;

use CodeIgniter\Model;

class UsergroupModel extends Model
{
    protected $table            = 'user_group';
    protected $primaryKey       = 'ug_id';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['ug_id','ug_code','ug_groupname','ug_delete_flag'];


}
