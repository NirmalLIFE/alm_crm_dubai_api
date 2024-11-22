<?php

namespace App\Controllers\Leads;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\Leads\LeadTaskModel;
use App\Models\Leads\LeadActivityModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;

class LeadActivity extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
      /**
     * @api {get} leads/leadactivity  Lead Activity list
     * @apiName Lead Activity list
     * @apiGroup Leads
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   activity  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */   
    public function index()
    {
        $model = new LeadActivityModel();
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

            $res= $model->findAll();
            if($res)
            {
                $response = [
                    'ret_data'=>'success',
                    'task'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'fail',
                    'task'=>[]
                ];
                return $this->respond($response,200);
            }
        }
       
    }
    

 /**
     * @api {post} leads/leadactivity/:id  Lead activity by  id
     * @apiName  Lead activity by  Lead id
     * @apiGroup Leads
     * @apiPermission  Super Admin, User
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}   activity object with call purpose details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
    {
        $model = new LeadActivityModel();
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

            $res = $model->where('lac_lead_id', $this->db->escapeString($id))->join('users','users.us_id =lac_activity_by','left')->select('lead_activities.*,users.us_firstname')->first();
            $response = [
                'ret_data'=>'success',
                'activity'=>$res
            ];
            return $this->respond($response,200);

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
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {
        //
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
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        //
    }
    
     /**
     * @api {post} leads/leadactivity/getActivity Activity List By Lead ID
     * @apiName  Activity List By Lead ID
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  Lead ID
     * 
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   activity  Object containing action list
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function getActivity()
    {
        $model = new LeadActivityModel();
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
            
            $res= $model->where('lac_lead_id', $this->db->escapeString($this->request->getVar('id')))->orderBy("lac_id", "desc")->join('users','users.us_id =lac_activity_by','left')->select('lead_activities.*,users.us_firstname')->findAll();
            if($res)
            {
                $response = [
                    'ret_data'=>'success',
                    'activity'=>$res
                ];
                return $this->respond($response,200);
            }
            else
            { 
                $response = [
                    'ret_data'=>'fail',
                    'activity'=>[]
                ];
                return $this->respond($response,200);
            }
        }     
      
    }
}
