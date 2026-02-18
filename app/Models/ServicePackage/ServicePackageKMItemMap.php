<?php

namespace App\Models\ServicePackage;

use CodeIgniter\Model;

class ServicePackageKMItemMap extends Model
{
    protected $DBGroup          = 'commonDB';
    protected $table            = 'sp_km_item_map';
    protected $primaryKey       = 'spkm_id ';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['spkm_id', 'spkm_km_id', 'spkm_item_id', 'spkm_item_type','spkm_km_optional_flag', 'spkm_created_on', 'spkm_created_by', 'spkm_updated_on', 'spkm_updated_by', 'spkm_delete_flag'];
}
