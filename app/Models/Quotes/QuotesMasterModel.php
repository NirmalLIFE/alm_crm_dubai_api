<?php

namespace App\Models\Quotes;

use CodeIgniter\Model;

class QuotesMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'quotes_master';
    protected $primaryKey       = 'qt_id ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['qt_id','qt_code','qt_cus_name','qt_cus_contact','qt_vin','qt_reg_no','qt_chasis','qt_make','qt_odometer','qt_vehicle_value','qt_service_adv','qt_parts_adv','qt_jc_no','qt_cus_id','qt_lead_id','qt_type','qt_amount','qt_tax','qt_total','qt_created_by','qt_created_on','qt_updated_by','qt_updated_on','qt_delete_flag','part_code_print','avail_print','part_type_print','brand_print','qt_camp_id'];

    
}
