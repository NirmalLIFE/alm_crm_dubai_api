<?php

namespace App\Models\Leads;

use CodeIgniter\Model;

class PreferLanguageModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'prefer_language';
    protected $primaryKey       = 'pl_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['pl_id','prefer_lang','prefer_lang_code','pl_createdon','pl_createdby','pl_updatedon','pl_updatedby','pl_delete_flag'];
    
}
