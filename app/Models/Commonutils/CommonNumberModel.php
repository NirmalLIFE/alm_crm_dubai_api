<?php

namespace App\Models\Commonutils;

use CodeIgniter\Model;

class CommonNumberModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'common_number_list';
    protected $primaryKey       = 'cn_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['cn_id','cn_number','cn_user','cn_reason','cn_created_by','cn_created_on','cn_updated_by','cn_updated_on','cn_delete_flag'];

   
}
