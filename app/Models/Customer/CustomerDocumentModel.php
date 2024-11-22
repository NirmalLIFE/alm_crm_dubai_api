<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class CustomerDocumentModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customer_document';
    protected $primaryKey       = 'cust_doc_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['cust_doc_id','cust_doc_name','cust_doc_path','cust_doc_desc','cus_id','cust_doc_thumbnail','cust_doc_created_on','cust_doc_created_by','cust_doc_updated_on','cust_doc_updated_by','cust_doc_delete_flag'];

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
