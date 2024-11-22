<?php

namespace App\Controllers\Quotes;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Quotes\CampaignModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;

class Campaign extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    /**
        
     * @api {get} quotes/campaign  Campaign list
     * @apiName Campaign list
     * @apiGroup Quotation
     * @apiPermission  Super Admin, User
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   camp  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */    
    public function index()
    {
        $model = new CampaignModel();
        $common =new Common();
        $valid=new Validation();        

        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));       

        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if(!$user) return $this->fail("invalid user",400); 
        }
        
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata){
            $res= $model->where('camp_delete_flag', 0)->findAll();
            if($res)
            {
              //  $this->insertUserLog('View Campaign List',$tokendata['uid']);
                $response = [
                    'ret_data'=>'success',
                    'camp'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'fail',
                    'camp'=>[]
                ];
                return $this->fail($response,400);
            }
        }
    }

   /**
     * @api {get} quotes/campaign/:id Campaign by  id
     * @apiName Campaign by  id
     * @apiGroup Quotation
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
        $model = new CampaignModel();
        $common =new Common();
        $valid=new Validation();        

        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));       

        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if(!$user) return $this->fail("invalid user",400); 
        }else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata){
            $res = $model->where('camp_id', $this->db->escapeString($id))->first();
            if($res)
            {
                $this->insertUserLog('View Campaign Data For Update',$tokendata['uid']);
                $response = [
                    'ret_data'=>'success',
                    'camp'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'fail',
                    'camp'=>[]
                ];
                return $this->fail($response,400);
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
     * @api {post} quotes/campaign Campaigne create
     * @apiName  Campaigne create
     * @apiGroup Quotation
     * @apiPermission super admin,User
     *
     *@apiBody {String} name Campaign Name
     *@apiBody {String} date_from Campign Start Date
     *@apiBody {String} date_to Campign End Date
     *@apiBody {String} camp_desc Campign Description
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function create()
    {
        $model = new CampaignModel();
        $common =new Common();
        $valid=new Validation();        

        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));       

        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if(!$user) return $this->fail("invalid user",400); 
        }
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata)
        {
            $rules = [
                'name'=>'required', 
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'camp_name' => $this->request->getVar('name'),
                'camp_date_from' => $this->request->getVar('date_from'),  
                'camp_date_to' => $this->request->getVar('date_to'),
                //'camp_desc' => $this->request->getVar('camp_desc'),    
                'camp_created_by' => $tokendata['uid']             
            ];
            if ($model->insert($data) === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            }
            else{
                $this->insertUserLog('Add New Campaign '.$this->request->getVar('name'),$tokendata['uid']);
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
     * @api {post} quotes/campaign/update Campaigne Update
     * @apiName  Campaigne Update
     * @apiGroup Quotation
     * @apiPermission super admin,User
     *
     *@apiBody {String} name Campaign Name
     *@apiBody {String} date_from Campign Start Date
     *@apiBody {String} date_to Campign End Date
     *@apiBody {String} camp_desc Campign Description
     *@apiBody {String} id Campign ID
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function update($id = null)
    {
        $model = new CampaignModel();
        $common =new Common();
        $valid=new Validation();        

        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));       

        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if(!$user) return $this->fail("invalid user",400); 
        }
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata)
        {
            $rules = [
                'name'=>'required',              
                     
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'camp_name' => $this->request->getVar('name'),
                'camp_date_from' => $this->request->getVar('date_from'),  
                'camp_date_to' => $this->request->getVar('date_to'),
               // 'camp_desc' => $this->request->getVar('camp_desc'),    
                'camp_updated_by' => $tokendata['uid']             
            ];
            $id = $this->db->escapeString($this->request->getVar('id'));
            if (  $model->where('camp_id', $id)->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            }
            else{

                $this->insertUserLog('Update Campaign '.$this->request->getVar('name'),$tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 201);
            }
         
        }
    }

     /**
     * @api {post} quotes/campaign/delete Campaign delete
     * @apiName Campaign delete
     * @apiGroup Quotation
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id campaignid of the lead source to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new CampaignModel();
        $common =new Common();
        $valid=new Validation();        

        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));       

        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if(!$user) return $this->fail("invalid user",400); 
        }
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata)
        {
            $data = [
                'camp_delete_flag' => 1,                
            ];
            $id=$this->db->escapeString($this->request->getVar('id'));
           
            if($model->where('camp_id', $id)->set($data)->update() === false )
            {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            }
            else
            {
                $this->insertUserLog('Delete Campaign ',$tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 201);

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
