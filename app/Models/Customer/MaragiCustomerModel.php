<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class MaragiCustomerModel extends Model
{
    protected $table            = 'cust_data_laabs';
    protected $primaryKey       = 'customer_code';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['customer_code','customer_type','customer_cat_type','customer_title','customer_name','addr1','po_box','city','country','phone','mobile','sms_option','contact_person','contact_phone','labs_created_on','lang_pref','cust_scn_id','created_on','updated_on','phon_uniq'];

}
