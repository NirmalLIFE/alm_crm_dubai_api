<?php

namespace App\Models\ServicePackage;

use CodeIgniter\Model;

class EngineMasterModel extends Model
{
    protected $DBGroup          = 'commonDB';
    protected $table            = 'engine_master';
    protected $primaryKey       = 'eng_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['eng_id', 'eng_no', 'eng_labour_factor', 'eng_created_on', 'eng_created_by', 'eng_delete_flag'];
}