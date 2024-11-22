<?php

namespace App\Controllers\Leads;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Leads\LeadStatusModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;

class LeadStatus extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
      /**
     * @api {get} leads/leadstatus  LeadSource list
     * @apiName LeadSource list
     * @apiGroup Leads
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   status  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */ 
    public function index()
    {
        $model = new LeadStatusModel();
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

          //  $this->insertUserLog('View Lead Status List',$tokendata['uid']);

            $res= $model->where('ld_sts_deleteflag', 0)->findAll();
            if($res)
            {
                $response = [
                    'ret_data'=>'success',
                    'status'=>$res
                ];
                return $this->respond($response,200);
            }else{
                $response = [
                    'ret_data'=>'success',
                    'status'=>[]
                ];
                return $this->respond($response,200); 
            }
        }
    }

   
 /**
     * @api {get} leads/leadstatus/:id  Lead Status by  id
     * @apiName ead Lead Status by  id
     * @apiGroup Leads
     * @apiPermission  Super Admin, User
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}    status object with lead status details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
    {
        $model = new LeadStatusModel();
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
            $res = $model->where('ld_sts_id', $this->db->escapeString($id))->first();
           $this->insertUserLog('View Lead Status for Update ',$tokendata['uid']);

            if($res){
                $response = [
                    'ret_data'=>'success',
                    'status'=>$res
                ];
                return $this->respond($response,200);
            }else{
                $response = [
                    'ret_data'=>'success',
                    'status'=>[]
                ];
                return $this->respond($response,200);
            }
            
        }
        

    }

   /**
     * @api {post} leads/leadstatus LeadStatus create
     * @apiName LeadStatus create
     * @apiGroup Leads
     * @apiPermission super admin,User
     *
     *@apiBody {String} status Lead Status  
     *@apiBody {String} description Lead Status Description
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function create()
    {
        $model = new LeadStatusModel();
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
                'status'=>'required', 
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'ld_sts' => $this->request->getVar('status'),
                'ld_sts_desc' => $this->request->getVar('description'),  
                'ld_sts_createdby' => $tokendata['uid']             
            ];
        
            if ($model->insert($data) === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            }
            else
            {
                $this->insertUserLog('Create New Lead Status '.$this->request->getVar('status'),$tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 200);

            }
           
        }
    }
    /**
     * @api {post} leads/leadstatus/update Lead Status Update
     * @apiName Lead Status Update
     * @apiGroup Leads
     * @apiPermission super admin, User
     *
     *
     * @apiBody {String} status Lead Source  
     *@apiBody {String} description Lead Source Description
     * @apiBody {String} id Status id
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    
    public function update($id = null)
    {
        $model = new LeadStatusModel();
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
                'status'=>'required',     
                     
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'ld_sts' => $this->request->getVar('status'),
                'ld_sts_desc' => $this->request->getVar('description'),  
                'ld_sts_updatedby' => $tokendata['uid']             
            ];
            $id = $this->db->escapeString($this->request->getVar('id'));
            $builder = $this->db->table('lead_status');
            $builder->select('ld_sts_edit_flag');
            $builder->where('ld_sts_id', $id);
            $query = $builder->get();
            $row = $query->getRow();
            $edit=$row->ld_sts_edit_flag ;
            if($edit == 1 )
            {
                $response = [
                   'ret_data' => 'fail'
                ];
                return $this->respond($response, 200);
            }
            else
            {
                if ($model->where('ld_sts_id', $id)->set($data)->update() === false) {
                    $response = [
                        'errors' => $model->errors(),
                        'ret_data' => 'fail'
                    ];
                    return $this->respond($response, 200);
                }
                else
                {
                    $this->insertUserLog('Update Lead Status '.$this->request->getVar('status'),$tokendata['uid']);
                    return $this->respond(['ret_data' => 'success'], 200);
                }
            }
        }
    }

     /**
     * @api {post} leads/leadstatusdelete Lead Status delete
     * @apiName Lead Status delete
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  status id of the lead source to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new LeadStatusModel();
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
                'ld_sts_deleteflag' => 1,                
            ];
            $id=$this->db->escapeString($this->request->getVar('id'));
            if($model->where('ld_sts_id', $id)->set($data)->update() === false )
            {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            }
            else
            {
                $this->insertUserLog('Delete Lead Status',$tokendata['uid']);
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
