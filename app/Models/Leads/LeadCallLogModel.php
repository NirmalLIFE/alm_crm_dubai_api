<?php

namespace App\Models\Leads;

use CodeIgniter\Model;

class LeadCallLogModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'lead_call_log';
    protected $primaryKey       = 'lcl_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['lcl_id','lcl_time','lcl_lead_id','lcl_pupose_id','lcl_purpose_note','lcl_call_to','lcl_created_on','lcl_createdby','lcl_phone','lcl_call_time','ystar_call_id','lcl_jc_status','lcl_call_type','lcl_call_source'];

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
