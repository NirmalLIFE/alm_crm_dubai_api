<?php

namespace App\Controllers\ServicePackage;

use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\User\UserModel;
use App\Models\ServicePackage\EngineMasterModel;

class EngineListController extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super)
                return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user)
                return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {

            $EngineMasterModel = new EngineMasterModel();

            $enginesList = $EngineMasterModel->where("eng_delete_flag", 0)
                ->findAll();

            if ($enginesList) {
                $response = [
                    'ret_data' => 'success',
                    'enginesList' => $enginesList,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
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
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super)
                return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user)
                return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {

            $EngineMasterModel = new EngineMasterModel();
            $engineNum = $this->request->getVar('engineNo');

            if ($EngineMasterModel->where('eng_no', $engineNum)->where("eng_delete_flag", 0)->first()) {
                return $this->respond([
                    'ret_data' => 'duplicate',
                    'message' => 'Engine number already exists.'
                ], 200);
            }

            $data = [
                'eng_no' => $engineNum,
                'eng_created_on' => date('Y-m-d H:i:s'),
                'eng_created_by' => $tokendata['uid']
            ];

            $engineid = $EngineMasterModel->insert($data);

            if ($engineid) {
                $response = [
                    'ret_data' => 'success',
                    'engineNo' => $engineNum,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
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
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super)
                return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user)
                return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {

            $EngineMasterModel = new EngineMasterModel();

            $eng_no = $this->request->getVar('engineNo');
            $eng_id = $this->request->getVar('engineid');

            if ($EngineMasterModel->where('eng_no', $eng_no)->where("eng_delete_flag", 0)->first()) {
                return $this->respond([
                    'ret_data' => 'duplicate',
                    'message' => 'Engine number already exists.'
                ], 200);
            }


            $updateData = [
                'eng_id' => $eng_id,
                'eng_no' => $eng_no
            ];

            $updated = $EngineMasterModel->where('eng_id', $eng_id)->set($updateData)->update();



            if ($updated) {
                $response = [
                    'ret_data' => 'success',
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super)
                return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user)
                return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {

            $EngineMasterModel = new EngineMasterModel();

            $eng_id = $this->request->getVar('eng_id');

            $data = [
                'eng_id' => $eng_id,
                'eng_delete_flag' => 1,
            ];

            if ($eng_id) {
                $updated = $EngineMasterModel->where('eng_id', $eng_id)->set($data)->update();
            }


            if ($updated) {
                $response = [
                    'ret_data' => 'success',
                    'servicePackage' => $updated,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }
}
