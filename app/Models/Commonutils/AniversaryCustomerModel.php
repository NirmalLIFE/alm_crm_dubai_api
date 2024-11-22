<?php

namespace App\Models\Commonutils;

use CodeIgniter\Model;

class AniversaryCustomerModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'alm_anv_mkarketing_customers';
    protected $primaryKey       = 'anv_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['anv_id','customer_mobile','send_flag'];

   
}
