<?php

namespace App\Models\ServicePackage;

use CodeIgniter\Model;

class ServiceItemGroupModel extends Model
{
    protected $DBGroup          = 'commonDB';
    protected $table            = 'sp_item_group';
    protected $primaryKey       = 'sp_ig_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['sp_ig_id','sp_ig_spim_id','sp_ig_group_seq','sp_ig_created_by','sp_ig_updated_by','sp_ig_updated_on','sp_ig_created_on','sp_ig_delete_flag'];

}
