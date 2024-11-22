<?php

namespace App\Controllers\Leads;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Leads\PreferLanguageModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;

class PreferLanguage extends ResourceController
{

    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * @api {get} leads/preferlangauge Language list
     * @apiName Language list
     * @apiGroup Leads
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   language  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */  
    public function index()
    {
        $model = new PreferLanguageModel();
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

            $res= $model->where('pl_delete_flag', 0)->findAll();
            if($res)
            {
               // $this->insertUserLog('View Preferred Language List',$tokendata['uid']);
                $response = [
                    'ret_data'=>'success',
                    'language'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'success',
                    'language'=>[]
                ];
                return $this->respond($response,200);
            }

        }
    }

   
 /**
     * @api {get} leads/preferlangauge/:id  Language by  id
     * @apiName Language by  id
     * @apiGroup Leads
     * @apiPermission  Super Admin, User
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}   language object with lead source details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
    {
        $model = new PreferLanguageModel();
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

            $res = $model->where('pl_id', $this->db->escapeString($id))->first();
            if($res)
            {
              //  $this->insertUserLog('View Preferred Language data for Update',$tokendata['uid']);
                $response = [
                    'ret_data'=>'success',
                    'language'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'success',
                    'language'=>[]
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
     * @api {post} Leads/PreferLanguage PreferLanguage create
     * @apiName PreferLanguage create
     * @apiGroup Leads
     * @apiPermission super admin,User
     *
     *@apiBody {String} language Language Name       
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function create()
    {
        $model = new PreferLanguageModel();
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
                'language'=>'required',   
            ];

            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'prefer_lang' => $this->request->getVar('language'), 
                'prefer_lang_code' => strtoupper($this->request->getVar('languagecode')), 
                'pl_createdby' => $tokendata['uid']                        
            ];
        
            if ($model->insert($data) === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            }
            else{
               // $this->insertUserLog('Add New Preferred language '.$this->request->getVar('language'),$tokendata['uid']);
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
     * @api {post} Leads/PreferLanguage PreferLanguage create
     * @apiName PreferLanguage create
     * @apiGroup Leads
     * @apiPermission super admin,User
     *
     *@apiBody {String} language Language Name   
     *@apiBody {String} id Language ID     
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function update($id = null)
    {
        $model = new PreferLanguageModel();
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
                'language'=>'required', 
                'id'=>'required',     
                     
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'prefer_lang' => $this->request->getVar('language'), 
                'prefer_lang_code' => strtoupper($this->request->getVar('languagecode')),
                'pl_updatedby' => $tokendata['uid']                        
            ];          
            if (  $model->where('pl_id', $this->db->escapeString($this->request->getVar('id')))->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->respond($response, 200);
            }
            else{
               // $this->insertUserLog('Update Preferred language '.$this->request->getVar('language'),$tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];
                return $this->respond($response, 200); 
            }
           
        }
    }

   /**
     * @api {post} leads/preferlanguage/delete Language delete
     * @apiName Language delete
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id   id of the language to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new PreferLanguageModel();
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
                'pl_delete_flag' => 1,                
            ];
           if($model->where('pl_id', $this->db->escapeString($this->request->getVar('id')))->set($data)->update() === false )
            {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->respond($response, 200);
            }
            else
            {
              //  $this->insertUserLog('Delete Preferred language',$tokendata['uid']);
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
