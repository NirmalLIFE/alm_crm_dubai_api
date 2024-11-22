<?php

namespace App\Models\Leads;

use CodeIgniter\Model;

class LeadDocumentModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'lead_document';
    protected $primaryKey       = 'ldoc_id ';
    protected $useAutoIncrement = true;
     protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ldoc_id ','ldoc_lead_id','ldoc_path','ldoc_name','ldoc_desc','ldoc_created_on','ldoc_created_by','ldoc_updated_on','ldoc_updated_by','ldoc_delete_flag','ldoc_thumbnail'];
   
}
