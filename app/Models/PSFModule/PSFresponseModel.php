<?php

namespace App\Models\PSFModule;

use CodeIgniter\Model;

class PSFresponseModel extends Model
{
    protected $table            = 'psf_response_master';
    protected $primaryKey       = 'rm_id';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['rm_id','rm_name'];
}
