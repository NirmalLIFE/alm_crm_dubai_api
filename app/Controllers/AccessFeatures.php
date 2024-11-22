<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\AccessFeaturesModel;
use App\Models\SuperAdminModel;
use Config\Common;
use Config\Validation;
use App\Models\UserModel;

class AccessFeatures extends ResourceController
{
      /**
     * @api {get} AccessFeatures  Feature list
     * @apiName Feature list
     * @apiGroup Super Admin
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   feature feature Object containing feature list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */    
    public function index()
    {
        $model = new AccessFeaturesModel();
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
        $resf= $model->where('ft_deleteflag', 0)->findAll();
        
        if($resf)
        {
            $response = [
                'ret_data'=>'success',
                'feature_list'=>$resf
            ];
            return $this->respond($response,200);
        }
    }
    }

   /**
     * @api {get} AccessFeatures/:id  Feature details by  id
     * @apiName Feature details by  id
     * @apiGroup Super Admin
     * @apiPermission  Super Admin
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}    feature object with feature details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
    {
        $model = new AccessFeaturesModel();
        $common =new Common();
        $valid=new Validation();        

        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));       

        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata){

            $res = $model->where('ft_id', $id)->first();
            $response = [
                'ret_data'=>'Success',
                'feature'=>$res
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


    /**
     * @api {post} AccessFeatures/create Feature create
     * @apiName Feature create
     * @apiGroup super admin
     * @apiPermission super admin
     *
     *
     * @apiBody {String} feature feature name
     * @apiBody {String} featuredesc feature description
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */

    public function create()
    {
      
        $model = new AccessFeaturesModel();
        $common =new Common();
        $valid=new Validation();        
        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));   
        
        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata){
            $rules = [
                'feature'=>'required',
                'featuredesc'=>'required',
               
            ];
            
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
          
            $data = [
                'ft_name' => $this->request->getVar('feature'),
                'ft_description' => $this->request->getVar('featuredesc'),
                'ft_created_by '=>$tokendata['uid']
            ];
        
            if ($model->insert($data) === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'Invalid Inputs'
                ];

                return $this->fail($response, 409);
            }
            return $this->respond(['ret_data' => 'Feature Inserted Successfully'], 201);
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


     /**
     * @api {post} AccessFeatures/update Feature update
     * @apiName Feature update
     * @apiGroup super admin
     * @apiPermission super admin
     *
     *
     * @apiBody {String} feature feature name
     * @apiBody {String} featuredesc feature description
     * @apiBody {String} id feature id
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */

    public function update($id = null)
    {
        $model = new AccessFeaturesModel();
        $common =new Common();
        $valid=new Validation();        
        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));   
        
        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
          
            if(!$super) return $this->fail("invalid user",400);        
        }else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata){

            $data = [
                'ft_name' => $this->request->getVar('feature'),
                'ft_description' => $this->request->getVar('featuredesc'),
            ];
            $id = $this->request->getVar('id');
            $model->where('ft_id', $id)->set($data)->update();
            return $this->respond(['ret_data' => 'Updated Successfully'], 201);

         }
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
