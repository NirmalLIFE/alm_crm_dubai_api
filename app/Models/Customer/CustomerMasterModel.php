<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_master';
    protected $primaryKey       = 'cus_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['cus_id','cust_type','cust_name','cust_salutation','cust_address','cust_emirates','cust_city','cust_country','cust_phone','cust_alternate_no','cust_email','cust_sms','cust_lang','cust_alm_code','cust_created_by','cust_created_on','cust_updated_by','cust_updated_on','cust_delete_flag','cust_whatsapp','cust_alternate_contact','cust_source'];

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
