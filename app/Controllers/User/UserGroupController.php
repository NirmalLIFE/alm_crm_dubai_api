<?php

namespace App\Controllers\User;

use App\Models\User\UsergroupModel;
use App\Models\User\UserModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;

class UserGroupController extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        $validModel=new Validation();
        $commonutils=new Common();
        $heddata=$this->request->headers();
        $tokendata=$commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$user) return $this->fail("invalid user",400); 
        }
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata['aud']=='superadmin' || $tokendata['aud']=='user'){
            $usergroupModel = new UsergroupModel();
            $result = $usergroupModel->where('ug_delete_flag', 0)
                    ->findAll();
            if($result){
               $data['ret_data']="success";
               $data['groupList']=$result;
               return $this->respond($data,200);
            }
            else {
                $data['ret_data']="fail";
                return $this->respond($data,200);
            }
        }
    }
}
