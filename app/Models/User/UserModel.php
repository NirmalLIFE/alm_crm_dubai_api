<?php

namespace App\Models\User;

use CodeIgniter\Model;

class UserModel extends Model
{
    
    protected $table            = 'users';
    protected $primaryKey       = 'us_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['us_id','us_firstname','us_lastname','us_password','us_phone','us_email','us_role_id','us_laabs_id','us_date_of_joining','us_status_flag','us_branch_id','us_createdby','us_updated_by','us_delete_flag','ext_number','us_ext_name','tr_grp_status','us_dept_id','us_dept_head'];

    public function get_userById($user_id){
        $builder = $this->db->table($this->table);
        $builder->where("us_id",$user_id);
        $builder->where("us_status_flag",0);
        $query = $builder->get();
        $result = $query->getRow();
        if($result){
            return $result;
        }else{
            return null;
        }
        
    }

}
