<?php

namespace App\Controllers\ServicePackage;

use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\User\UserModel;
use App\Models\ServicePackage\SPItemMaster;
use App\Models\ServicePackage\ServicePackagePartsModel;

class ServicePackageItemsMaster extends ResourceController
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

            $SPItemMasterModel = new SPItemMaster();

            $ItemsList = $SPItemMasterModel
                ->where('spim_delete_flag', 0)
                ->findAll();

            if ($ItemsList) {
                $response = [
                    'ret_data' => 'success',
                    'ItemsList' => $ItemsList,
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

            $ServicePackagePartsModel = new ServicePackagePartsModel();
            $SPItemMaster = new SPItemMaster();


            $category = $this->request->getVar('sp_pm_category');
            $unitType = $this->request->getVar('sp_pm_unit_type');

            // Get the name from request
            $partName = $this->request->getVar('spim_name');
            // Optional: clean and lowercase it if needed
            $partName = trim(strtolower($partName));
            // Query the DB to check for existence
            $existing = $SPItemMaster
                ->where('LOWER(spim_name)', $partName)
                ->first();

            // return $this->respond($existing['spim_id'], 200);


            if (!$existing) {
                $insertItemsData = [
                    'spim_name' => $this->request->getVar('spim_name'),
                    'spim_category' =>  $category,
                    'spim_created_on' =>  date('Y-m-d H:i:s'),
                    'spim_created_by' => $this->request->getVar('user_id'),
                    'spim_updated_on' =>  date('Y-m-d H:i:s'),
                    'spim_updated_by' => $this->request->getVar('user_id'),
                ];

                $SPItemMaster->insert($insertItemsData);
                $spim_id = $SPItemMaster->getInsertID();
            } else {
                $spim_id = $existing['spim_id'];
            }



            // If category is not 0 or 1, force unit_type to null
            if (!in_array($category, ['0', '1'])) {
                $unitType = null;
            }

            $insertPartsData = [
                'sp_pm_spim_id' => $spim_id,
                'sp_pm_code' => $this->request->getVar('sp_pm_code'),
                'sp_pm_brand' => $this->request->getVar('sp_pm_brand'),
                'sp_pm_unit_type' => $unitType,
                'sp_pm_access' => $this->request->getVar('sp_pm_access'),
                'sp_pm_category' => $category,
                'sp_pm_price' => $this->request->getVar('sp_pm_price'),
                'sp_pm_ordering' => $this->request->getVar('sp_pm_ordering'),
            ];


            $servicePackage =  $ServicePackagePartsModel->insert($insertPartsData);


            if ($servicePackage) {
                $response = [
                    'ret_data' => 'success',
                    'servicePackage' => $servicePackage,
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
