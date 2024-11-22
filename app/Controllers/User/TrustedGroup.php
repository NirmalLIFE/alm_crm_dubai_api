<?php

namespace App\Controllers\User;

use CodeIgniter\RESTful\ResourceController;
use App\Models\User\UserModel;
use App\Models\User\UserroleModel;
use CodeIgniter\API\ResponseTrait;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\UserActivityLog;

class TrustedGroup extends ResourceController
{
    use ResponseTrait;
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    /**
     * @api {get} User/TrustedGroup Number List in Trusted Group
     * @apiName Number List in Trusted Group
     * @apiGroup Admin
     * @apiPermission Admin
     *
     * @apiSuccess {String}   ret_data success or fail.
     * @apiSuccess {Object}   tgList Object containing user details
     *
    * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function index()
    {
        $validModel=new Validation();
        $commonutils=new Common();
        $heddata=$this->request->headers();
        $usmodel = new UserModel();
        $tokendata=$commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
             $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$user) return $this->fail("invalid user",400); 
        }
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata['aud']=='superadmin' || $tokendata['aud']=='user'){

            $result = $usmodel->where('us_delete_flag', 0)->where('tr_grp_status', 1)->join('user_roles','user_roles.role_id=us_role_id','left')->join('user_group','user_group.ug_id=user_roles.role_groupid','left')->select('us_id,us_firstname,us_lastname,us_password,us_phone,us_email,us_role_id,user_roles.role_name,us_date_of_joining,us_status_flag,user_group.ug_code')
            ->findAll();
            if($result){
            
                $data['ret_data']="success";
                $data['tgList']=$result;
                return $this->respond($data,200);
            }
            else {
                $data['ret_data']="success";
                $data['tgList']=[];
                return $this->respond($data,200);
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
}
