<?php

namespace App\Controllers\Leads;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Leads\CallPurposeModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;

class CallPurpose extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
     /**
     * @api {get} leads/callpurpose Call Purpose list
     * @apiName Call Purpose list
     * @apiGroup Super Admin
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   purpose  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */   
    public function index()
    {
        $model = new CallPurposeModel();
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

           

            $res= $model->where('cp_delete_flag', 0)->orderby('cp_id','DESC')->findAll();
            if($res)
            {
               // $this->insertUserLog('View Call Purpose List',$tokendata['uid']);
                $response = [
                    'ret_data'=>'success',
                    'purpose'=>$res
                ];
                return $this->respond($response,200);
            }else{
                $response = [
                    'ret_data'=>'success',
                    'purpose'=>[]
                ];
                return $this->respond($response,200);
            }
        }
    }

   /**
     * @api {get} leads/callpurpose/:id  Call Purpose by  id
     * @apiName  Call Purpose by  id
     * @apiGroup Leads
     * @apiPermission  Super Admin, User
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}   purpose object with call purpose details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
    {
        $model = new CallPurposeModel();
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
            $res = $model->where('cp_id', $this->db->escapeString($id))->first();
            if($res)
            {
                $this->insertUserLog('View Call Purpose data for Update',$tokendata['uid']);  
            $response = [
                'ret_data'=>'success',
                'purpose'=>$res
            ];
            return $this->respond($response,200);
        }
        else{
            $response = [
                'ret_data'=>'success',
                'purpose'=>[]
            ];
            return $this->respond($response,200);
        }
        }
    }

     /**
     * @api {post} leads/callpurpose Call Purpose create
     * @apiName Call Purpose create
     * @apiGroupLeads
     * @apiPermission super admin,User
     *
     *@apiBody {String} callpurpose Call Purpose  
     *@apiBody {String} description Call Purpose Description
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function create()
    {
        $model = new CallPurposeModel();
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
            $rules = [
                'purpose'=>'required',  
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'call_purpose' => $this->request->getVar('purpose'),
                'cp_desc' => $this->request->getVar('description'), 
                'veh_need' => $this->request->getVar('checkvalue'), 
                'cp_createdby' => $tokendata['uid']             
            ];
            if ($model->insert($data) === false) {
                $data['ret_data']="fail";
                return $this->respond($data,200);
            }else{
                $this->insertUserLog('New Call Purpose Created '.$this->request->getVar('purpose'),$tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 200);
            } 
        }
    }

    /**
     * Return the editable properties of a resource object
     *
     * @return mixed
     */
    public function edit($id = null)
    {
        //
    }

     /**
     * @api {post} leads/callpurpose/update Call Purpose Update
     * @apiName  Call Purpose Update
     * @apiGroup Leads
     * @apiPermission super admin, User
     *
     *
     *@apiBody {String} callpurpose Call Purpose  
     *@apiBody {String} description Call Purpose Description
     * @apiBody {String} id Call Purpose ID
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function update($id = null)
    {
        $model = new CallPurposeModel();
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
            $rules = [
                'purpose'=>'required',         
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'call_purpose' => $this->request->getVar('purpose'),
                'cp_desc' => $this->request->getVar('description'), 
                'veh_need' => $this->request->getVar('checkvalue'),  
                'cp_updatedby' => $tokendata['uid']             
            ];
            $id = $this->db->escapeString($this->request->getVar('id'));
            if (  $model->where('cp_id', $id)->set($data)->update() === false) {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->respond($response, 200);
            }
            else{
                $this->insertUserLog('Call Purpose Updated '.$this->request->getVar('callpurpose'),$tokendata['uid']);
            return $this->respond(['ret_data' => 'success'], 200);
            }
        }
    }

     /**
     * @api {post} leads/callpurpose/delete Call Purpose delete
     * @apiName Call Purpose delete
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  call purpose id of the call purpose to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new CallPurposeModel();
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
            $data = [
                'cp_delete_flag' => 1,                
            ];
            $id=$this->db->escapeString($this->request->getVar('id'));
           
            if($model->where('cp_id', $id)->set($data)->update() === false )
            {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->respond($response, 200);
            }
            else
            {
                $this->insertUserLog('Call Purpose Deleted',$tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 200);

            }
        }
    }
    public function insertUserLog($log,$id)
    {
        $logmodel = new UserActivityLog();
        $ip=$this->request->getIPAddress();       
        $indata=[
            'log_user'    => $id,
            'log_ip'   =>  $ip,
            'log_activity' =>$log            
        ];        
        $results=$logmodel->insert($indata);
    }
}
