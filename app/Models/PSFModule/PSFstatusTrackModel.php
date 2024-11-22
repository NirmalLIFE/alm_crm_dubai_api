<?php

namespace App\Models\PSFModule;

use CodeIgniter\Model;

class PSFstatusTrackModel extends Model
{
    protected $table            = 'psf_status_tracker';
    protected $primaryKey       = 'pst_id';
    protected $allowedFields    = ['pst_id','pst_task','pst_sourceid','pst_destid','pst_psf_id','pst_psf_status','pst_created_on','pst_created_by','pst_psf_call_type','pst_response','pst_delete_flag'];
}
