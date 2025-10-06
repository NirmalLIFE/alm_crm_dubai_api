<?php

namespace App\Models\ServicePackage;

use CodeIgniter\Model;

class ServicePackageModelCodeModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'sp_model_code';
    protected $primaryKey       = 'spmc_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['spmc_id', 'spmc_value', 'spmc_vin_no', 'spmc_model_year', 'spmc_variant', 'spmc_status_flag', 'spmc_session_us_id', 'spmc_pa_return_note', 'spmc_sv_return_note', 'spmc_draft_flag', 'spmc_session_flag', 'spmc_created_on', 'spmc_created_by', 'spmc_updated_on', 'spmc_updated_by', 'spmc_delete_flag'];
}
