<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class MaraghiJobcardModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'cust_job_data_laabs';
    protected $primaryKey       = 'job_no';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['job_no','customer_no','vehicle_id','car_reg_no','job_open_date','job_close_date','received_date','promised_date','extended_promise_date','speedometer_reading','delivered_date','invoice_no','invoice_date','sa_emp_id','user_name','job_status','job_scn_id','created_on','updated_on'];

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
