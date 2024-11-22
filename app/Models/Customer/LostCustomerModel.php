<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class LostCustomerModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = ' lost_customer_list';
    protected $primaryKey       = 'lcst_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['lcst_id','customer_code','customer_name','phone','sms_mobile','sms_option','email','reg_no','chasis','brand','model_code','model_name','model_year','miles_done','visits','invoice_date','created_on','lcst_status','lcst_note','lcst_assign','lcst_assigned_on','lcst_note_date','lcst_assigned_upto','lcst_due_date','lcst_file_id','lcst_file_type','lcst_filter_by','lcst_due_date_to','appointment_date','lcst_updated_by','lcst_call_time','lcst_code','lcst_ring_status'];

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
