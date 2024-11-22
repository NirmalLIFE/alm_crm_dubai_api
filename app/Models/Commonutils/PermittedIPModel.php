<?php

namespace App\Models\Commonutils;

use CodeIgniter\Model;

class PermittedIPModel extends Model
{
    protected $table            = 'permitted_ip_list';
    protected $primaryKey       = 'pip_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['pip_id','pip_address','pip_reason','pip_created_by','ip_source_type','pip_created_on','pip_updated_by','pip_updated_on','pip_delete_flag'];
}
