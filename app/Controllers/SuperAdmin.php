<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\SuperAdminModel;
use Config\Common;

class SuperAdmin extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        $model = new SuperAdminModel();
        return $this->respond($model->findAll(), 200);
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
/**
     * @api {post} superadmin/ create super admin
     * @apiName create super admin
     * @apiGroup super admin
     * @apiPermission super admin
     *
     *
     * @apiBody {String} name admin name
     * @apiBody {String} email admin email
     * @apiBody {String} phone admin phone
     * @apiBody {String} password admin password
     *  
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */

    public function create()
    {
        $model = new SuperAdminModel();
        $rules = [
            'name'=>'required',
            'email'=>'required',
            'phone'=>'required',
            'password'=>'required',
        ];
        if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $data = [
            's_adm_name' => $this->request->getPost('name'),
            's_adm_email' => $this->request->getPost('email'),
            's_adm_contact' => $this->request->getPost('phone'),
            's_adm_password' => $this->request->getPost('password'),           
        ];
        if($model->insert($data) === false)
        {
            $response = ['ret_data'=>'Invalid Inputs'];
            return  $this->fail($response,409);
        }
        else{
                $response = ['ret_data'=>'Created Successfully'];
                return $this->respond($response,200);
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
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        //
    }

/**
     * @api {post} superadmin/ super_admin_login
     * @apiName superadminlogin 
     * @apiGroup super admin
     * @apiPermission super admin
     *
     *
     * @apiBody {String} adm_email  email
     * @apiBody {String} adm_password password
     *  
     * @apiSuccess {String}   ret_data success or fail.
     * @apiSuccess {Object}   admindata Object containing admin details
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */

    public function super_admin_login()
    {
        $model = new SuperAdminModel();
        $common =new Common();
        $rules = [
            'adm_email'=>'required',            
            'adm_password'=>'required',
        ];
        if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        
        $res = $model->where('s_adm_email', $this->request->getVar('adm_email'))->first();
        if(!$res)
        {
            $response = [
                'ret_data'=>'Not Found',
            ];
            return $this->fail($response,409);
        }
        else
        {
            if($this->request->getPost('adm_password') == $res['s_adm_password']) 
            {
                
                $token=$common->generate_superadmin_jwt_token($res['s_adm_id']);
                $res['token'] = $token;            
                $response = [
                    'ret_data'=>'Login Success',
                    'admindata'=>$res
                ];
                return $this->respond($response,200);
            }
            else
            {
                $response = [
                    'ret_data'=>'Login Failed',
                ];
                return $this->fail($response,409);
            }
        }
    }


}
