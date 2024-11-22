<?php

namespace App\Models\PSFModule;

use CodeIgniter\Model;

class PSFreasonModel extends Model
{
    protected $table            = 'psf_reason';
    protected $primaryKey       = 'psfr_id';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['psfr_id','psfr_name','psfr_typeid','psfr_mreason'];

}
