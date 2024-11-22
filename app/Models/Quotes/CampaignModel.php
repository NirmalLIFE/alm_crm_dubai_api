<?php

namespace App\Models\Quotes;

use CodeIgniter\Model;

class CampaignModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'campaign';
    protected $primaryKey       = 'camp_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['camp_id','camp_name','camp_date_from','camp_date_to','camp_desc','camp_created_on','camp_created_by','camp_updated_on','camp_updated_by','camp_delete_flag'];

    
}
