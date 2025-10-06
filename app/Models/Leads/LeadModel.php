<?php

namespace App\Models\Leads;

use CodeIgniter\Model;

class LeadModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'leads';
    protected $primaryKey       = 'lead_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['lead_id','lead_code','name','email','phone','address','vehicle_model','register_number','source_id','lead_social_media_source','lead_social_media_mapping','purpose_id','lang_id','status_id','lead_note','assigned','cus_id','reason_to_close','lead_createdon','lead_createdby','lead_updatedon','lead_updatedby','lead_delete_flag','ld_brand','lead_from','close_time','lead_creted_date','jc_status','conv_cust_by','conv_cust_on','ld_appoint_date','ld_camp_id','rating','outbound_lead','ld_appoint_time','ld_verify_flag','lead_category'];

   
}
