<?php

namespace App\Models\ServicePackage;

use CodeIgniter\Model;

class KilometerMasterModel extends Model
{
    protected $DBGroup          = 'commonDB';
    protected $table            = 'kilometer_master';
    protected $primaryKey       = 'km_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['km_id', 'km_value', 'km_created_on', 'km_created_by', 'km_updated_on', 'km_updated_by', 'km_delete_flag'];
}
