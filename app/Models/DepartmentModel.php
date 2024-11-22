<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartmentModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'department';
    protected $primaryKey       = 'dept_id ';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['dept_id','dept_name','dept_desc','dept_created_at','dept_created_by','dept_updated_at','dept_updated_by','dept_delete_flag'];

   
}
