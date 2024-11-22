<?php

namespace App\Controllers\Commonutils;

use App\Models\Commonutils\YeastarKeysModel;
use App\Models\Commonutils\PermittedIPModel;
use CodeIgniter\RESTful\ResourceController;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;

class GetConfigDatas extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    public function getYeastarKeys(){
        $common =new Common();
        $valid=new Validation();        

        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
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
            $yeastar=new YeastarKeysModel();
            $keydetails=$yeastar->where("key_id",1)->first();
            $data['ret_data']="success";
            $data['yeastar_data']=$keydetails;
            return $this->respond($data,200);
        }
    }

    public function getPermittedIps()
    {
        $common =new Common();
        $valid=new Validation();        

        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
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
            $permittedIp=new PermittedIPModel();
            $keydetails=$permittedIp->where("pip_delete_flag",0)->orderBy('pip_id','desc')->findAll();
            $data['ret_data']="success";
            $data['pips']=$keydetails;
            return $this->respond($data,200);
        }
    }

    public function PermittedIpSync()
    {
        $common =new Common();
        $valid=new Validation();        

        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$super) return $this->fail("invalid user",400);        
        }
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata['aud']=='superadmin'){
            $rules = [
                'ip_address'=>'required',
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $ipData = [
                'pip_address' => $this->request->getVar('ip_address'),
            ];
            $permittedIp=new PermittedIPModel();
            // $ins_id= $permittedIp->insert($ipData);
            $ins_id=$permittedIp->where('ip_source_type', 'Maraghi')->set($ipData)->update();
            if($ins_id){
                $data['ret_data']="success";
                return $this->respond($data,200);
            }else{
                $data['ret_data']="fail";
                return $this->respond($data,200);
            }
        }
    }
}
