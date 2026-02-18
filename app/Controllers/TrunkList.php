<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\TrunkListModel;
use App\Models\DepartmentModel;
use App\Models\DepartmentFeatureModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;
use App\Models\AccessFeaturesModel;

class TrunkList extends ResourceController
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
        $model = new TrunkListModel();
        $deptmodel = new DepartmentModel();
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
            $res = $deptmodel->select('department.dept_id,dept_name,dept_desc,dept_created_at,dept_created_by,dept_updated_at,dept_updated_by,dept_delete_flag,tr_id,trunk_id,trunk_name,tr_created_at,tr_created_by,tr_updated_at,tr_updated_by,tr_delete_flag')
                ->join('trunk_list', 'trunk_list.dept_id=department.dept_id', 'left')
                ->where('dept_delete_flag', 0)
                ->orderBy('dept_id', 'desc')
                ->findAll();
            if ($res) {
                $this->insertUserLog('View Trunk Call Log', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'quotes' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'quotes' => []
                ];
                return $this->fail($response, 409);
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

        $trmodel = new TrunkListModel();
        $deptmodel = new DepartmentModel();
        $afmodel = new AccessFeaturesModel();
        $common = new Common();
        $valid = new Validation();
        $depftr = array();

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

            $res = $deptmodel->where('dept_id', $this->db->escapeString($id))->first();
            $tr = $trmodel->where('dept_id', $this->db->escapeString($id))->findAll();



            $builder = $this->db->table('department_features');
            $builder->select('department_features.df_ft_id  as dft_id');
            $builder->where('df_dept_id', $this->db->escapeString($id));
            $data = $builder->get()->getResultArray();
            foreach ($data as $k) {
                $depftr[] = $k['dft_id'];
            }

            $builder = $this->db->table('features_list');
            $builder->select('ft_id,ft_name,ft_description,ft_created_on,ft_created_by,ft_updated_on,ft_updated_by,ft_deleteflag');
            $builder->where('ft_deleteflag', 0);
            $ftr = $builder->get()->getResultArray();
            if (!empty($depftr)) {
                foreach ($ftr as $key => $value) {

                    if (in_array($value['ft_id'], $depftr)) {
                        $ftr[$key]['checked'] = true;
                    } else {
                        $ftr[$key]['checked'] = false;
                    }
                }
            }


            $builder = $this->db->table('trunk_list');
            $builder->select('trunk_list.trunk_name  as name');
            $builder->where('dept_id', $this->db->escapeString($id));
            $data = $builder->get()->getResultArray();
            foreach ($data as $k) {
                $result[] = $k['name'];
            }

            $response = [
                'ret_data' => 'success',
                'dept' => $res,
                'trunk' => $tr,
                // 'trunk_name' => $result,
                'featurelist' => $ftr,
                'dept_ft' => $depftr
            ];
            return $this->respond($response, 200);
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
        $model = new TrunkListModel();
        $deptmodel = new DepartmentModel();
        $deptFtmodel = new DepartmentFeatureModel();
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
                'dept_name' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

            $trunk = $this->request->getVar('trunk');
            $ftr = $this->request->getVar('feature');

            $indata = [
                'dept_name'    => $this->request->getVar('dept_name'),
                'dept_desc'   => $this->request->getVar('dept_desc'),
                'dept_created_by'    => $tokendata['uid'],
            ];
            $results = $deptmodel->insert($indata);
            if ($results) {
                $in_data = array();
                for ($i = 0; $i < count($trunk); $i++) {
                    $infdata = [
                        'dept_id'   => $results,
                        'trunk_name' => trim($trunk[$i]),
                        'tr_created_by' => $tokendata['uid'],
                    ];
                    array_push($in_data, $infdata);
                }
                if(sizeof($in_data)>0){
                    $ret = $model->insertBatch($in_data);
                }
                $in_data = array();
                if (!empty($ftr)) {
                    for ($i = 0; $i < count($ftr); $i++) {
                        $infdata = [
                            'df_dept_id'   => $results,
                            'df_ft_id' => $ftr[$i],
                            'df_created_by' => $tokendata['uid'],
                        ];
                        array_push($in_data, $infdata);
                    }
                    $ret = $deptFtmodel->insertBatch($in_data);
                }
            }
            $response = [
                'ret_data' => 'success',
            ];
            return $this->respond($response, 200);
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
        $model = new TrunkListModel();
        $deptmodel = new DepartmentModel();
        $deptFtmodel = new DepartmentFeatureModel();
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
                'dept_name' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

            $trunk = $this->request->getVar('trunk');
            $ftr = $this->request->getVar('feature');

            $did = $this->db->escapeString($this->request->getVar('did'));
            $data = [
                'dept_name'    => $this->request->getVar('dept_name'),
                'dept_desc'   => $this->request->getVar('dept_desc'),
                'dept_updated_by'    => $tokendata['uid'],
            ];

            $results = $deptmodel->where('dept_id',  $this->db->escapeString($this->request->getVar('did')))->set($data)->update();
            if ($results) {
                if (!empty($trunk)) {
                    $resd = $model->where('dept_id', $did)->delete();
                    $in_data = array();
                    for ($i = 0; $i < count($trunk); $i++) {

                        $infdata = [
                            'dept_id'   => $did,
                            'trunk_name' => trim($trunk[$i]),
                            'tr_created_by' => $tokendata['uid'],
                        ];
                        array_push($in_data, $infdata);
                    }
                    $ret = $model->insertBatch($in_data);
                }
                if (!empty($ftr)) {
                    $resf = $deptFtmodel->where('df_dept_id', $did)->delete();

                    $in_data = array();
                    for ($i = 0; $i < count($ftr); $i++) {

                        $infdata = [
                            'df_dept_id'   => $did,
                            'df_ft_id' => $ftr[$i],
                            'df_created_by' => $tokendata['uid'],
                        ];
                        array_push($in_data, $infdata);
                    }
                    $ret = $deptFtmodel->insertBatch($in_data);
                }
            }
            $response = [
                'ret_data' => 'success',
            ];
            return $this->respond($response, 200);
        }
    }

    /**
     * @api {get} TrunkList/delete Trunk Department delete
     * @apiName Trunk Department delete
     * @apiGroup Trunk
     * @apiPermission User,Admin
     *
     * 
     * @apiParam {String} id  departmrnt id to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new TrunkListModel();
        $deptmodel = new DepartmentModel();
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
            //  $id = $this->db->escape($this->request->getVar('id'));

            $updata = [
                'dept_delete_flag' => 1,
            ];
            $res = $deptmodel->where('dept_id', $id)->set($updata)->update();
            if ($res) {
                $response = [
                    'ret_data' => 'success'
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
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
    public function getDept()
    {
        $model = new TrunkListModel();
        $deptmodel = new DepartmentModel();
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



            $res = $deptmodel->select('department.dept_id,dept_name,dept_desc,dept_created_at,dept_created_by,dept_updated_at,dept_updated_by,dept_delete_flag')
                ->where('dept_delete_flag', 0)
                ->orderBy('dept_id', 'desc')
                ->findAll();
            //    for($i=0;$i<count($res);$i++)
            //    {
            //     $builder = $this->db->table('trunk_list');
            //     $builder->select('GROUP_CONCAT(trunk_list.trunk_name SEPARATOR ",") as trunk_name'); 
            //     $builder->where('dept_id', $res[$i]['dept_id']);
            //     $data = $builder->get()->getRow();    
            //     $res[$i]['trunkname'] = $data->trunk_name;

            //    }

            if ($res) {
                $this->insertUserLog('View Trunk Call Log', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'dept' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'dept' => []
                ];
                return $this->fail($response, 409);
            }
        }
    }
    function FeatureListByDept()
    {
        $model = new TrunkListModel();
        $deptmodel = new DepartmentModel();
        $deptFtmodel = new DepartmentFeatureModel();
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

            $dept_id = $this->request->getVar('dept_id');
            $res = $deptFtmodel->select('df_id,df_dept_id,df_ft_id,df_created_on,df_created_by,df_updated_on,df_updated_by,df_delete_flag,ft_name')
                ->join('features_list', 'features_list.ft_id=department_features.df_ft_id')
                ->where('df_dept_id', $dept_id)
                ->findAll();
            if ($res) {
                $this->insertUserLog('View Trunk Call Log', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'feature_list' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'feature_list' => []
                ];
                return $this->fail($response, 200);
            }
        }
    }
}
