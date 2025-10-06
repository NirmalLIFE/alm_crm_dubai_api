<?php

namespace App\Models\Whatsapp;

use CodeIgniter\Model;

class WhatsappAutomessage extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'whatsapp_automated_message_content';
    protected $primaryKey       = 'wamc_id ';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['wamc_id', 'wamc_message_id', 'wamc_message_content', 'wamc_created_at', 'wamc_updated_at', 'wamc_created_by', 'wamc_updated_by', 'wamc_delete_flag'];

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
