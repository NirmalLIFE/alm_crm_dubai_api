<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'users';
    protected $primaryKey       = 'us_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['us_id', 'us_firstname', 'us_lastname', 'us_email', 'us_phone', 'us_password', 'us_role_id ', 'us_date_of_joining', 'us_fcm_token_web', 'us_fcm_token_mob', 'us_status_flag', 'us_branch_id', 'us_lastlogin', 'us_created_on', 'us_createdby', 'us_updated_on', 'us_updated_by', 'login_status', 'last_login', 'activeJwt', 'FCM_token'];
}
