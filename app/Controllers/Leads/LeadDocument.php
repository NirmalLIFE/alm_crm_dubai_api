<?php

namespace App\Controllers\Leads;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\Leads\LeadDocumentModel;
use App\Models\Leads\LeadActivityModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;

class LeadDocument extends ResourceController{

    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }


   /**
     * @api {get} leads/leaddocument  Lead Document list
     * @apiName Lead Document list
     * @apiGroup Leads
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   doc  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */ 
    public function index()
    {
        $model = new LeadDocumentModel();
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

            $res= $model->where('ldoc_delete_flag', 0)->orderBy("ldoc_id", "desc")->findAll();
            $this->insertUserLog('View Lead Document',$tokendata['uid']);
            if($res)
            {

                $response = [
                    'ret_data'=>'success',
                    'doc'=>$res
                ];
                return $this->respond($response,200);
            }
            else
            {
                $response = [
                    'ret_data'=>'success',
                    'doc'=>[]
                ];
                return $this->respond($response,200);

            }
        } 
    }

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        //
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
     * @api {post} leads/leaddocument Lead Document create
     * @apiName Lead Document create
     * @apiGroup Leads
     * @apiPermission super admin,User
     *
     *@apiBody {String} ldoc_url Document URL 
     *@apiBody {String} ldoc_desc Description
     *@apiBody {String} ldoc_lead_id Lead ID     
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function create()
    {
        $model = new LeadDocumentModel();
        $common =new Common();
        $valid=new Validation();        
        $acmodel= new LeadActivityModel(); 

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
                'ldoc_path' => $this->request->getVar('path'),
                'ldoc_name' => $this->request->getVar('docname'),
                'ldoc_lead_id' => $this->request->getVar('leadid'), 
                'ldoc_created_by' => $tokendata['uid']             
            ];
            if ($model->insert($data) === false) {

                $response = [
                            'errors' => $model->errors(),
                            'ret_data' => 'fail'
                        ];
                    return $this->respond($response, 200);
            }
            else{
                $acdata = [
                    'lac_activity' => 'Attached File',
                    'lac_activity_by' => $tokendata['uid'], 
                    'lac_lead_id' => 1,
                    ];
               $acmodel->insert($acdata);

               $this->insertUserLog('Lead Document Attached',$tokendata['uid']);

               return $this->respond(['ret_data' => 'success'], 200);
           }


        }

    }

 public function imageupload()
    {
        helper(['form', 'url']);
        $UserModel = new UserModel();
        $validModel = new Validation();
        $commonutils = new Common();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
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
            
            $imageFile = $this->request->getFile('attachment');          
            $profile_image = $imageFile->getName();
            $imageFile->move(ROOTPATH . 'public/uploads/LeadDocument');
            $data = [
                'img_name' => $imageFile->getName(),
                'file'  => $imageFile->getClientMimeType(),
                'path' => ROOTPATH,
                'docpath' => 'LeadDocument\\',
            ];
            $data['ret_data'] = "success";
            return $this->respond($data, 200);
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
     * Add or update a model resource, from "posted" properties
     *
     * @return mixed
     */
    public function update($id = null)
    {
        //
    }

   /**
     * @api {post} leads/leaddocument/delete Lead Document delete
     * @apiName Lead Document delete
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  Document id of the lead source to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new LeadDocumentModel();
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
                'ldoc_delete_flag' => 1,                
            ];         
           
            if($model->where('ldoc_id', $this->db->escapeString($this->request->getVar('id')))->set($data)->update() === false )
            {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->respond($response, 200);
            }
            else
            {   
                $this->insertUserLog('Lead Document Deleted',$tokendata['uid']);             
                return $this->respond(['ret_data' => 'success'], 200);
            }

        }
    }


    public function attachDoc()
    {
        helper(['form', 'url']);
        $model = new LeadDocumentModel();
        $common =new Common();
        $valid=new Validation();        
        $acmodel= new LeadActivityModel(); 

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
            
            $file = $this->request->getFile('attachment');
            $profile_image = $file->getName();             
            $file_ext = pathinfo($profile_image,PATHINFO_EXTENSION);
            if($file_ext == 'docx' || $file_ext=='pdf')
            {
               $prev= "LeadDocument\\doc-preview.png";
            }
           else{
               $prev= "LeadDocument\\". $profile_image;
           }
          
        //   $file->move(ROOTPATH . 'public/uploads/LeadDocument');
           $data = [
               'ldoc_path' => "uploads\\LeadDocument\\". $profile_image,
               'ldoc_desc' => $this->request->getVar('desc'), 
               'ldoc_lead_id' => $this->request->getVar('leadid'),
               'ldoc_name' => $profile_image,    
               'ldoc_thumbnail' => $prev,    
               'ldoc_created_by' => $tokendata['uid'] 
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
               $acdata = [
                   'lac_activity' => 'Uploaded Document',
                   'lac_activity_by' => $tokendata['uid'], 
                   'lac_lead_id' => $this->request->getVar('leadid'),
                              
               ];
               $acmodel->insert($acdata);
               return $this->respond(['ret_data' => 'success','res'=> $file], 200);

           }

        }
        // $model = new LeadDocumentModel();
        // $file = $this->request->getFile('attach');
        // $profile_image = $file->getName();
        // if ($file->move("uploads\\LeadDocument\\", $profile_image)) {
            
        //     $data = [
        //         'ldoc_url' => "uploads\\LeadDocument\\". $profile_image,
        //         'ldoc_desc' => $this->request->getVar('ldoc_desc'), 
        //         'ldoc_lead_id' => $this->request->getVar('ldoc_lead_id'),                 
                       
        //     ];
            
        //     if ($model->insert($data) === false) {
            
        //         $response = [
        //             'errors' => $model->errors(),
        //             'ret_data' => 'fail'
        //         ];
            
        //         return $this->fail($response, 409);
        //     }
        // }
        
    }
    public function getAttach()
    {
        $model = new LeadDocumentModel();
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

            $res= $model->where('ldoc_lead_id', $this->db->escapeString($this->request->getVar('id')))->orderBy("ldoc_id", "desc")->findAll();    
            $this->insertUserLog('View Lead Document List',$tokendata['uid']);       
            if($res)
            {
                $response = [
                    'ret_data'=>'success',
                    'doc'=>$res
                ];
                return $this->respond($response,200);
            }
            else
            { 
                $response = [
                    'ret_data'=>'fail',
                    'doc'=>[]
                ];
                return $this->respond($response,200);
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
