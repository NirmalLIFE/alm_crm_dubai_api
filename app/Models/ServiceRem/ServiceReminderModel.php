<?php

namespace App\Models\ServiceRem;

use CodeIgniter\Model;

class ServiceReminderModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'alm_service_remainder_master';
    protected $primaryKey       = 'srm_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'srm_id',
        'srm_file_name',
        'srm_year',
        'srm_month',
        'srm_created_on',
        'srm_created_by',
        'srm_updated_on	',
        'srm_updated_by',
        'srm_delete_flag'
    ];
}
