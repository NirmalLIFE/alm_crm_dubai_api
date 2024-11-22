<?php

namespace App\Controllers\SpareParts;

use CodeIgniter\RESTful\ResourceController;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\SpareParts\SparePartsMaster;
use App\Models\SpareParts\SpareCategory;



class SparePartsController extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     * Get Method Without Data
     * @return mixed
     */
    public function index()
    {
        $SparePartsMaster = new SparePartsMaster();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {

            $parts = $SparePartsMaster->where("pm_delete_flag !=", 1)
                ->join('brand_list', 'brand_list.brand_id=pm_brand', 'left')
                ->join('spare_category', 'spare_category.spc_id=pm_category', 'left')
                ->select('parts_master.*, brand_list.brand_name,brand_list.brand_code,spare_category.spc_name',)
                ->findAll();

            if (sizeof($parts) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'parts' => $parts,
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
        $SparePartsMaster = new SparePartsMaster();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {

            $Sparedata = [
                'pm_code' => $this->request->getVar('part_code'),
                'pm_name' =>  $this->request->getVar('part_name'),
                'pm_category' => $this->request->getVar('part_category'),
                'pm_brand' => $this->request->getVar('part_brand'),
                'pm_price' => $this->request->getVar('part_price'),
                'pm_created_on' => date("Y-m-d H:i:s"),
                'pm_created_by' => $tokendata['uid'],
                'pm_updated_on' => date("Y-m-d H:i:s"),
                'pm_updated_by' => $tokendata['uid'],
            ];

            $partsentry = $SparePartsMaster->insert($Sparedata);



            if ($partsentry) {
                $response = [
                    'ret_data' => 'success',
                    'parts' => $partsentry,
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
        $SparePartsMaster = new SparePartsMaster();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {


            $pm_id = $this->request->getVar('pm_id');

            $Sparedata = [
                'pm_id' => $this->request->getVar('pm_id'),
                'pm_code' => $this->request->getVar('pm_code'),
                'pm_name' =>  $this->request->getVar('pm_name'),
                'pm_category' => $this->request->getVar('pm_category'),
                'pm_brand' => $this->request->getVar('pm_brand'),
                'pm_price' => $this->request->getVar('pm_price'),
                'pm_updated_on' => date("Y-m-d H:i:s"),
                'pm_updated_by' => $tokendata['uid'],
            ];

            $UpdateParts = $SparePartsMaster->where('pm_id', $pm_id)->set($Sparedata)->update();



            if ($UpdateParts) {
                $response = [
                    'ret_data' => 'success',
                    'UpdateParts' => $UpdateParts,
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
        $SparePartsMaster = new SparePartsMaster();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {


            $pm_id = $this->request->getVar('pm_id');

            $Sparedata = [
                'pm_delete_flag' => 1,
                'pm_updated_on' => date("Y-m-d H:i:s"),
                'pm_updated_by' => $tokendata['uid'],
            ];

            $DeleteParts = $SparePartsMaster->where('pm_id', $pm_id)->set($Sparedata)->update();



            if ($DeleteParts) {
                $response = [
                    'ret_data' => 'success',
                    'DeleteParts' => $DeleteParts,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getSpareCategory()
    {
        $SpareCategory = new SpareCategory();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {

            $SpareCategory = $SpareCategory->where("spc_deleteflag !=", 1)
                ->select('spc_name,spc_id,spc_displayname,spc_description',)
                ->findAll();

            if ($SpareCategory) {
                $response = [
                    'ret_data' => 'success',
                    'SpareCategory' => $SpareCategory,
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
