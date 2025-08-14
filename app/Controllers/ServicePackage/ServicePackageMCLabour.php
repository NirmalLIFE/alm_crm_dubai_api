<?php

namespace App\Controllers\ServicePackage;

use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\User\UserModel;
use App\Models\ServicePackage\ServicePackageModelCodeLabourModel;


class ServicePackageMCLabour extends ResourceController
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

            $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();

            $modelCodeList = $ServicePackageModelCodeLabourModel
                ->where('spmcl_delete_flag', 0)
                ->join(
                    'sp_model_code_family',
                    'sp_model_code_family.spmcf_family_code = family_code AND sp_model_code_family.spmcf_brand_code = sp_model_code_labour.brand_code',
                    'left'
                )
                ->findAll();

            if ($modelCodeList) {
                $response = [
                    'ret_data' => 'success',
                    'modelCodeList' => $modelCodeList,
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

            $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();

            $family_code = $this->request->getVar('family_code');
            $spmcl_inc_pct = $this->request->getVar('spmcl_inc_pct');
            $user_id = $this->request->getVar('user_id');


            // Fetch all rows matching the family code and not deleted
            $records = $ServicePackageModelCodeLabourModel
                ->where('spmcl_delete_flag', 0)
                ->where('family_Code', $family_code)
                ->findAll();


            $updateData = [];
            foreach ($records as $row) {
                $updateData[] = [
                    'spmcl_id' => $row['spmcl_id'],
                    'spmcl_inc_pct' => $spmcl_inc_pct,
                    'spmcl_updated_on'  => date("Y-m-d H:i:s"),
                    'spmcl_updated_by'  => $user_id,
                ];
            }

            // Batch update by primary key
            $updated = $ServicePackageModelCodeLabourModel->updateBatch($updateData, 'spmcl_id');

            if ($updated) {
                $response = [
                    'ret_data' => 'success',
                    'modelCodeList' => $updated,
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

            $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();

            $family_code = $this->request->getVar('family_code');
            $spmcl_inc_pct = $this->request->getVar('spmcl_inc_pct');
            $spmcl_id  = $this->request->getVar('spmcl_id');
            $user_id = $this->request->getVar('user_id');


            $partsUpdateData = [
                'spmcl_id' => $spmcl_id,
                'family_code' => $family_code,
                'spmcl_inc_pct' => $spmcl_inc_pct,
                'spmcl_updated_on'  => date("Y-m-d H:i:s"),
                'spmcl_updated_by'  => $user_id,
            ];


            $updated = $ServicePackageModelCodeLabourModel->update($spmcl_id, $partsUpdateData);


            if ($updated) {
                $response = [
                    'ret_data' => 'success',
                    'modelCodeList' => $updated,
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
        //
    }
}
