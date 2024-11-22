<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use App\Models\User\UserLogTableModel;
use App\Models\User\UserloginModel;



class UserLog extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        $model = new UserActivityLog();
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
            $today = DATE('Y-m-d');

            $res = $model->orderBy('log_time', 'desc')->join('users', 'users.us_id = log_user')->select('user_activity_log.*,us_firstname')->findAll();
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'actlog' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'actlog' => []
                ];
                return $this->respond($response, 200);
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
        $common = new Common();
        $valid = new Validation();

        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        $logmodel = new UserActivityLog();
        $ip = $this->request->getIPAddress();
        $indata = [
            'log_user'    => $tokendata['uid'],
            'log_ip'   =>  $ip,
            'log_activity' => $this->request->getVar('log')
        ];
        $results = $logmodel->insert($indata);
        $response = [
            'ret_data' => 'success'
        ];
        return $this->respond($response, 200);
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
    public function userLogFilter()
    {
        $model = new UserActivityLog();
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
            $res = $model->where('log_user', $this->db->escapeString($this->request->getVar('id')))->orderBy('log_time', 'desc')->join('users', 'users.us_id = log_user')->select('user_activity_log.*,us_firstname')->findAll();
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'actlog' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'actlog' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function ViewUserLog()
    {
        $model = new UserLogTableModel();
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
            $today = DATE('Y-m-d');

            $res = $model->orderBy('ulg_time', 'desc')->join('users', 'users.us_id = ulg_user')->select('user_log_table.*,us_firstname')->findAll();
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'userlog' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'userlog' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function userlogs()
    {
        $userLogin = new UserloginModel();
        $common = new Common();
        $valid = new Validation();
        $model = new UserModel();

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
            $action = $this->request->getVar('action');
            $name = $this->request->getVar('name');
            $role_id = $this->request->getVar('role_id');
            $us_id = $this->request->getVar('us_id');
            $us_lg_id = $this->request->getVar('us_login_id');
            $ext_no = $this->request->getVar('ext_no');
            if ($action === 'Login') {
                $data = [
                    'us_lg_username' => $name,
                    'us_lg_role_id' => $role_id,
                    'us_lg_us_id' => $us_id,
                    'us_lg_ext_number' => $ext_no,
                    'us_lg_login_time' => date('Y-m-d H:i:s', time())
                ];
                $result = $userLogin->insert($data);

                $response = [
                    'ret_data' => 'success',
                    'data' => $data,
                    'result' => $result
                ];
            } else {
                $dataa = [
                    'us_lg_logout_time' => date('Y-m-d H:i:s', time())
                ];
                $indata = [
                    'login_status'    => 0,
                ];

                $results = $model->update($us_id, $indata);
                if ($us_lg_id) {
                    $userLogin->where('us_lg_id', $us_lg_id)->set($dataa)->update();
                }

                $response = [
                    'ret_data' => 'success',
                    'data' => $dataa,
                ];
            }
            return $this->respond($response, 200);
        }
    }
}
