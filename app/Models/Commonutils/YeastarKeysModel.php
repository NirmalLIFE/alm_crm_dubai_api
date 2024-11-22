<?php

namespace App\Models\Commonutils;

use CodeIgniter\Model;

class YeastarKeysModel extends Model
{
    protected $table            = 'key_master';
    protected $primaryKey       = 'key_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['key_id','yeastar_user','yeastar_pass', 'yeastar_token', 'yeastar_refresh_token', 'yeastar_token_time'];
}
