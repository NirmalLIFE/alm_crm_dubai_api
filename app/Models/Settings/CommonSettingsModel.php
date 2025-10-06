<?php

namespace App\Models\Settings;

use CodeIgniter\Model;

class CommonSettingsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'common_settings';
    protected $primaryKey       = 'cst_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['cst_id', 'cst_created_by', 'cst_created_on', 'cst_updated_by', 'cst_updated_on', 'cst_delete_flag', 'verification_number', 'landline_include_status', 'working_time_json', 'working_time_start', 'working_time_end', 'off_days', 'away_message_content','mis_buffer_time', 'workhour_mis_buffer_time', 'parts_margin'];

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
