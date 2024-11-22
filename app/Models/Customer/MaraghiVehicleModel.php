<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class MaraghiVehicleModel extends Model
{
    protected $table            = 'cust_veh_data_laabs';
    protected $primaryKey       = 'vehicle_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['vehicle_id','reg_no','model_name','family_code','family_name','chassis_no','brand_code','model_year','customer_code','miles_done','creation_date','veh_scn'];

}
