<?php

namespace App\Controllers\Leads;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Leads\LeadSourceModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;

class LeadSource extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
     /**
     * @api {get} Leads/LeadSource  LeadSource list
     * @apiName LeadSource list
     * @apiGroup Super Admin
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   source  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */    
    public function index()
    {
        $model = new LeadSourceModel();
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
          //  $this->insertUserLog('View Lead Source List',$tokendata['uid']);
            $res= $model->where('ld_src_dlt', 0)->orderby('ld_src_id','DESC')->findAll();
            if($res)
            {
                $response = [
                    'ret_data'=>'success',
                    'source'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'success',
                    'source'=>[]
                ];
                return $this->respond($response,200);
            }

        }
        
    }

 /**
     * @api {get} leads/leadsource/:id  Lead Source by  id
     * @apiName Lead Source by  id
     * @apiGroup Leads
     * @apiPermission  Super Admin, User
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}    ld_source object with lead source details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
    {
        $model = new LeadSourceModel();
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

           $this->insertUserLog('View Lead Source data for Update',$tokendata['uid']);

            $res = $model->where('ld_src_id',$this->db->escapeString($id))->first();
            if($res)
            {
                $response = [
                    'ret_data'=>'success',
                    'ld_source'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'success',
                    'ld_source'=>[]
                ];
                return $this->respond($response,200);
            }
           
        }
        
    }

    /**
     * Return a new resource object, with default properties
     *
     * @return mixed
     */
    public function new()
    {
        //
    }

   /**
     * @api {post} Leads/LeadSource LeadSource create
     * @apiName LeadSource create
     * @apiGroup super admin
     * @apiPermission super admin,User
     *
     *@apiBody {String} source Lead Source  
     *@apiBody {String} description Lead Source Description
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function create()
    {
        $model = new LeadSourceModel();
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
                'source'=>'required',
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'ld_src' => $this->request->getVar('source'),
                'ld_src_desc' => $this->request->getVar('description'),  
                'ld_src_createdby' => $tokendata['uid']             
            ];
        
            if ($model->insert($data) === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            }
            else{
             $this->insertUserLog('New Lead Source Created '.$this->request->getVar('source'),$tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];

                return $this->respond($response, 200);
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
     * @api {post} leads/leadsource/update Lead Source Update
     * @apiName Lead Source Update
     * @apiGroup Leads
     * @apiPermission super admin, User
     *
     *
     * @apiBody {String} source Lead Source  
     *@apiBody {String} description Lead Source Description
     * @apiBody {String} id Source id
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function update($id = null)
    {
        $model = new LeadSourceModel();
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
                'source'=>'required',            
                     
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'ld_src' => $this->request->getVar('source'),
                'ld_src_desc' => $this->request->getVar('description'),  
                'ld_src_updatedby' => $tokendata['uid']             
            ];
            if (  $model->where('ld_src_id',  $this->db->escapeString($this->request->getVar('id')))->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            }
            else
            {
                $this->insertUserLog('Lead Source Updated ',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];

                return $this->respond($response, 200);
            }
          

        }
    }

     /**
     * @api {post} leads/leadsource/delete Lead Source delete
     * @apiName Lead Source delete
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  source id of the lead source to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new LeadSourceModel();
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
                'ld_src_dlt' => 1,                
            ];
           if($model->where('ld_src_id',  $this->db->escapeString($this->request->getVar('id')))->set($data)->update() === false )
            {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            }
            else
            {
               $this->insertUserLog('Lead Source Deleted ',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];
                return $this->respond($response, 200);

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
