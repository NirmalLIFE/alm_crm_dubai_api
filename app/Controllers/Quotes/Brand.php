<?php

namespace App\Controllers\Quotes;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Quotes\BrandModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;


class Brand extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * @api {get} quotes/brand  Brand list
     * @apiName Brand list
     * @apiGroup Quotes
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   brand  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */
    public function index()
    {
        $model = new BrandModel();
        $common = new Common();
        $valid = new Validation();

        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {

            $res = $model->where('brand_delete_flag', 0)->findAll();
            //  $this->insertUserLog('View Brand List',$tokendata['uid']);
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'brand' => $res
                ];
                return $this->respond($response, 200);
            } else {


                $response = [
                    'ret_data' => 'success',
                    'brand' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    /**
     * @api {get} quotes/brand/:id  Brand by  id
     * @apiName Brand by  id
     * @apiGroup quotes
     * @apiPermission  Super Admin, User
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}    brand object with lead source details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
    {
        $model = new BrandModel();
        $common = new Common();
        $valid = new Validation();

        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $res = $model->where('brand_id', $this->db->escapeString($id))->first();
            if ($res) {
                $this->insertUserLog('View Brand data For Update', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'brand' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'brand' => []
                ];
                return $this->respond($response, 200);
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
     * @api {post} quotes/brand Parts Brand create
     * @apiName Parts Brand create
     * @apiGroup Quotes
     * @apiPermission super admin,User
     *
     *@apiBody {String} brand Brand Name    
     *@apiBody {String} code Brand Code     
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function create()
    {
        $model = new BrandModel();
        $common = new Common();
        $valid = new Validation();

        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $rules = [
                'brand' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'brand_name' => $this->request->getVar('brand'),
                'brand_code' => strtoupper($this->request->getVar('code')),
                'brand_created_by' => $tokendata['uid']
            ];
            if ($model->insert($data) === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            } else {
                $this->insertUserLog('Create New Brand ' . $this->request->getVar('brand'), $tokendata['uid']);
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
     * @api {post} quotes/brand/update Brand Update
     * @apiName Brand Update
     * @apiGroup Quotes
     * @apiPermission super admin, User
     *
     *
     *@apiBody {String} brand Brand Name
     *@apiBody {String} code Brand Code
     * @apiBody {String} id Brand id
     * 
     * @apiSuccess {String}   ret_data success or fail.     * 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function update($id = null)
    {
        $model = new BrandModel();
        $common = new Common();
        $valid = new Validation();

        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {

            $rules = [
                'brand' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

            $data = [
                'brand_name' => $this->request->getVar('brand'),
                'brand_code' => strtoupper($this->request->getVar('code')),
                'brand_updated_by' => $tokendata['uid']
            ];
            if ($model->where('brand_id',  $this->db->escapeString($this->request->getVar('id')))->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            } else {
                $this->insertUserLog('Update Brand ' . $this->request->getVar('brand'), $tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];

                return $this->respond($response, 200);
            }
        }
    }

    /**
     * @api {post} brand/brand/delete Brand delete
     * @apiName Brand delete
     * @apiGroup Quotes
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  Brand id of the brand to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new BrandModel();
        $common = new Common();
        $valid = new Validation();

        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $data = [
                'brand_delete_flag' => 1,
            ];
            if ($model->where('brand_id',  $this->db->escapeString($this->request->getVar('id')))->set($data)->update() === false) {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            } else {
                $this->insertUserLog('Delete Brand ', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function insertUserLog($log, $id)
    {
        $logmodel = new UserActivityLog();
        $ip = $this->request->getIPAddress();
        $indata = [
            'log_user'    => $id,
            'log_ip'   =>  $ip,
            'log_activity' => $log
        ];
        $results = $logmodel->insert($indata);
    }
}
