<?php

namespace App\Models\Leads;

use CodeIgniter\Model;

class AppointmentMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'appointment_master';
    protected $primaryKey       = 'apptm_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['apptm_id', 'apptm_code', 'apptm_customer_code', 'apptm_diss_id', 'apptm_lead_id', 'apptm_status', 'apptm_transport_service', 'apptm_pickup_mode', 'apptm_created_on', 'apptm_created_by', 'apptm_updated_on', 'apptm_updated_by', 'apptm_delete_flag', 'apptm_type', 'apptm_group', 'apptm_alternate_no'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
