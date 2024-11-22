<?php

namespace App\Models\PSFModule;

use CodeIgniter\Model;

class PSFMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'psf_master';
    protected $primaryKey       = 'psfm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'psfm_id',
        'psfm_customer_code',
        'psfm_job_no',
        'psfm_vehicle_no',
        'psfm_reg_no',
        'psfm_invoice_date',
        'psfm_sa_id',
        'psfm_primary_assignee',
        'psfm_cre_id',
        'psfm_psf_assign_date',
        'psfm_cre_assign_date',
        'psfm_num_of_attempts',
        'psfm_status',
        'psfm_sa_rating',
        'psfm_cre_rating',
        'psfm_last_attempted_date',
        'psfm_transfer_flag',
        'psfm_current_assignee',
        'psfm_current_type',
        'psfm_call_transfer_level',
        'psfm_lastresponse',
        'psfm_created_on',
        'psfm_created_by',
        'psfm_updated_on',
        'psfm_updated_by',
        'psfm_primary_whatsapp_id',
        'psfm_followup_whatsapp_id',
        'psfm_primary_response_type',
        'psfm_folloup_response_type',
        'psfm_delete_flag'

    ];
}
