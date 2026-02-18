<?php

namespace App\Controllers\ServicePackage;

use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\User\UserModel;
use App\Models\ServicePackage\ServicePackageModelCodeModel;
use App\Models\ServicePackage\KilometerMasterModel;
use App\Models\ServicePackage\ServicePackageEnginesModel;
use App\Models\ServicePackage\ServicePackageSpareModel;
use App\Models\ServicePackage\ServicePackageLabourModel;
use App\Models\ServicePackage\ServicePackageKMItemMap;
use App\Models\Service\VehicleMaster;
use App\Models\ServicePackage\EngineMasterModel;
use App\Models\SpareParts\SparePartsMaster;
use App\Models\ServicePackage\ServicePackagePartsModel;
use App\Models\ServicePackage\ServicePackageKmPriceModel;
use App\Models\ServicePackage\ServicePackageModelCodeLabourModel;
use App\Models\ServicePackage\SPItemMaster;
use App\Models\ServicePackage\ServiceItemGroupModel;
use App\Models\ServicePackage\ServicePackageLogModel;
use App\Models\Quotes\BrandModel;
use App\Models\Customer\MaraghiJobcardModel;


class ServicePackageController extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */

    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();

            // $requestedServicePackage = $ServicePackageModelCodeModel->where("spmc_delete_flag", 0)
            //     ->whereNotIn("spmc_status_flag", [5, 6])
            //     ->join('users', 'users.us_id = spmc_created_by', 'left')
            //     ->join('users', 'users.us_id = spmc_updated_by', 'left')
            //     ->select('sp_model_code.*,us_firstname')
            //     ->findAll();

            $requestedServicePackage = $ServicePackageModelCodeModel
                ->where("spmc_delete_flag", 0)
                ->whereNotIn("spmc_status_flag", [5, 6])
                ->join('users as creator', 'creator.us_id = spmc_created_by', 'left')
                ->join('users as updater', 'updater.us_id = spmc_updated_by', 'left')
                ->select('sp_model_code.*, creator.us_firstname as created_by_name, updater.us_firstname as updated_by_name')
                ->findAll();

            if ($requestedServicePackage) {
                $response = [
                    'ret_data' => 'success',
                    'requestedServicePackage' => $requestedServicePackage,
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $SpLogTable = new ServicePackageLogModel();
            $userModel = new UserModel();

            $modelCode = $this->request->getVar('modelCode');
            $vinNo = $this->request->getVar('vinNo');
            $modelYear = $this->request->getVar('modelYear');
            $variant = $this->request->getVar('variant');
            $req_user = $this->request->getVar('user_id');

            $data = [
                'spmc_value' => $modelCode,
                'spmc_vin_no' =>  $vinNo,
                'spmc_model_year' => $modelYear,
                'spmc_variant' => $variant,
                'spmc_status_flag' => 0,
                'spmc_created_by' => $this->request->getVar('user_id'),
                'spmc_created_on' =>  date("Y-m-d H:i:s"),
                'spmc_updated_by' => $this->request->getVar('user_id'),
                'spmc_updated_on' =>  date("Y-m-d H:i:s"),
            ];
            $user = $userModel->where('us_id', $req_user)->first();

            $userName = $user ? $user['us_firstname'] : null;


            $servicePackage = $ServicePackageModelCodeModel->insert($data);


            if ($servicePackage) {
                $logNote = "Service package requested for Model Code {$modelCode} (VIN: {$vinNo}, Variant: {$variant}, Year: {$modelYear}), requested by {$userName}.";

                $logData = [
                    'sp_log_spmc_id'   => $servicePackage,
                    'sp_log_notes'     => $logNote,
                    'sp_log_created_by' => $this->request->getVar('user_id'),
                    'sp_log_created_on' => date("Y-m-d H:i:s"),
                ];

                $SpLogTable->insert($logData);

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

    // public function update($id = null)
    // {
    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $tokendata['uid'])->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata) {

    //         $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
    //         $ServicePackageEnginesModel = new ServicePackageEnginesModel();
    //         $SP_Parts_Model = new ServicePackageSpareModel();
    //         $SparePartsMasterModel = new SparePartsMaster();


    //         $modelCode = $this->request->getVar('model_code');

    //         $modelData = $ServicePackageModelCodeModel->select('spmc_id')->where('spmc_value', $modelCode)
    //             ->where('spmc_delete_flag', 0)->first();

    //         $model_id = $modelData['spmc_id'];
    //         $userId = $this->request->getVar('user_id');
    //         $engine_id = $this->request->getVar('engine_id');
    //         $partsData =  $this->request->getVar('parts');

    //         $this->db->transStart();

    //         foreach ($partsData as $part) {
    //             if ($part->applicable == 1) {
    //                 $insertSpareParts = [];
    //                 if ($part->id == 0) {
    //                     $insertSpareParts = [
    //                         'pm_code' => $part->PART_NO,
    //                         'pm_name' => $part->DESCRIPTION,
    //                         'pm_unit_type' => $part->unit_type,
    //                         'pm_brand' => $part->Brand,
    //                         'pm_price' => $part->PRICE,
    //                         'pm_created_on' => date("Y-m-d H:i:s"),
    //                         'pm_created_by' => $userId,
    //                         'pm_updated_on' =>  date("Y-m-d H:i:s"),
    //                         'pm_updated_by' => $userId,
    //                     ];
    //                     $SparePartsMasterModel->insert($insertSpareParts);
    //                     $insertedId = $SparePartsMasterModel->insertID();
    //                     // Now assign this ID back to $part
    //                     $part->id = $insertedId;
    //                 } else {

    //                     if ($part->old_price !== null && $part->old_price !== '' && $part->old_price != 0 && $part->old_price != '0') {

    //                         $updateSpareParts = [
    //                             'pm_price' => $part->old_price,
    //                             'pm_new_price' => $part->PRICE,
    //                             'pm_updated_on' =>  date("Y-m-d H:i:s"),
    //                             'pm_updated_by' => $userId,
    //                         ];

    //                         $SparePartsMasterModel->where('pm_id', $part->id)->set($updateSpareParts)->update();
    //                     }
    //                 }
    //             }
    //         }


    //         $existingRecord = $ServicePackageEnginesModel
    //             ->where('speng_spmc_id', $model_id)
    //             ->first();

    //         $insertOrUpdateData = [
    //             'speng_eng_id' => $engine_id,
    //             'speng_spmc_id' => $model_id,
    //             'speng_created_on' => date("Y-m-d H:i:s"),
    //         ];

    //         if ($existingRecord) {
    //             // Update the existing row
    //             $servicePackageEngine =  $ServicePackageEnginesModel->where('speng_spmc_id', $model_id)
    //                 ->set($insertOrUpdateData)->update();
    //         } else {
    //             // Insert new row
    //             $servicePackageEngine =  $ServicePackageEnginesModel->insert($insertOrUpdateData);
    //         }

    //         if ($servicePackageEngine) {
    //             $insertData = [];
    //             $partsUpdateData = [];
    //             foreach ($partsData as $part) {
    //                 if ($part->applicable == 1) {
    //                     if ($part->sp_spare_id == 0) {
    //                         $insertData[] = [
    //                             'sp_spare_spmc_id' => $model_id,
    //                             'sp_spare_pm_id' => $part->id,
    //                             'sp_spare_qty' => $part->qty,
    //                             'sp_spare_created_on' => date("Y-m-d H:i:s"),
    //                             'sp_spare_created_by' => $userId,
    //                             'sp_spare_updated_on' => date("Y-m-d H:i:s"),
    //                             'sp_spare_updated_by' => $userId,
    //                         ];
    //                     } else {
    //                         $PartsUpdateData[] = [
    //                             'sp_spare_id' => $part->sp_spare_id,
    //                             'sp_spare_pm_id' => $part->id,
    //                             'sp_spare_qty' => $part->qty,
    //                             // 'sp_spare_delete_flag' => $part->sp_spare_delete_flag,
    //                             'sp_spare_updated_on' => date("Y-m-d H:i:s"),
    //                             'sp_spare_updated_by' => $userId,
    //                         ];
    //                     }
    //                 }
    //             }

    //             if (!empty($insertData)) {
    //                 $servicePackageParts = $SP_Parts_Model->insertBatch($insertData);
    //             }

    //             if (!empty($PartsUpdateData)) {
    //                 $servicePackageParts = $SP_Parts_Model->updateBatch($PartsUpdateData, 'sp_spare_id');
    //             }

    //             $insertModelData = [
    //                 'spmc_status_flag' => $this->request->getVar('spmc_status_flag'),
    //                 'spmc_session_flag' => 0,
    //                 'spmc_updated_by' => $userId,
    //                 'spmc_updated_on' =>  date("Y-m-d H:i:s"),
    //             ];

    //             $ServicePackageModelCodeModel->where('spmc_id', $model_id)->set($insertModelData)->update();
    //         }

    //         $this->db->transComplete();

    //         if ($this->db->transStatus() === false) {
    //             $this->db->transRollback();
    //             $response['ret_data'] = "fail";
    //             return $this->respond($response, 200);
    //         } else {
    //             $this->db->transCommit();
    //             $response = [
    //                 'ret_data' => 'success',
    //             ];
    //             return $this->respond($response, 200);
    //         }
    //     }
    // }


    //CHANGESSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSS
    // public function update($id = null)
    // {
    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $tokendata['uid'])->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata) {

    //         $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
    //         $ServicePackageEnginesModel = new ServicePackageEnginesModel();
    //         $SP_Parts_Model = new ServicePackageSpareModel();
    //         $SparePartsMasterModel = new SparePartsMaster();
    //         $SpLogTable = new ServicePackageLogModel();


    //         $modelCode = $this->request->getVar('model_code');

    //         $modelData = $ServicePackageModelCodeModel->select('spmc_id')->where('spmc_value', $modelCode)
    //             ->where('spmc_delete_flag', 0)->first();

    //         $model_id = $modelData['spmc_id'];
    //         $userId = $this->request->getVar('user_id');
    //         $engine_id = $this->request->getVar('engine_id');
    //         $partsData =  $this->request->getVar('parts');

    //         $action = ($this->request->getVar('draft_flag') == 1)
    //             ? "drafted"
    //             : "saved";

    //         $this->db->transStart();


    //         foreach ($partsData as $part) {
    //             if ($part->applicable == '1') {
    //                 $insertSpareParts = [];
    //                 if ($part->id == 0) {
    //                     $insertSpareParts = [
    //                         'pm_code' => $part->PART_NO,
    //                         // 'pm_name' => $part->DESCRIPTION,
    //                         'pm_sp_pm_id' => $part->sp_pm_id,
    //                         'pm_unit_type' => $part->unit_type,
    //                         'pm_brand' => $part->Brand,
    //                         'pm_price' => $part->PRICE,
    //                         'pm_created_on' => date("Y-m-d H:i:s"),
    //                         'pm_created_by' => $userId,
    //                         'pm_updated_on' =>  date("Y-m-d H:i:s"),
    //                         'pm_updated_by' => $userId,
    //                     ];
    //                     $SparePartsMasterModel->insert($insertSpareParts);
    //                     $insertedId = $SparePartsMasterModel->insertID();
    //                     // Now assign this ID back to $part
    //                     $part->id = $insertedId;
    //                 } else {

    //                     $updateSpareParts = [
    //                         'pm_code'       => $part->PART_NO,
    //                         // 'pm_name'       => $part->DESCRIPTION,
    //                         'pm_sp_pm_id' => $part->sp_pm_id,
    //                         'pm_unit_type'  => $part->unit_type,
    //                         'pm_brand'      => $part->Brand,
    //                         'pm_updated_on' => date("Y-m-d H:i:s"),
    //                         'pm_updated_by' => $userId,
    //                     ];

    //                     if ($part->old_price !== null && $part->old_price !== '' && $part->old_price != 0 && $part->old_price != '0') {
    //                         $updateSpareParts['pm_price'] = $part->old_price;
    //                         $updateSpareParts['pm_new_price'] = $part->PRICE;
    //                     } else {
    //                         $updateSpareParts['pm_price'] = $part->PRICE;
    //                     }
    //                     $SparePartsMasterModel->where('pm_id', $part->id)->set($updateSpareParts)->update();
    //                 }
    //             }
    //         }


    //         $existingRecord = $ServicePackageEnginesModel
    //             ->where('speng_spmc_id', $model_id)
    //             ->first();

    //         $insertOrUpdateData = [
    //             'speng_eng_id' => $engine_id,
    //             'speng_spmc_id' => $model_id,
    //             'speng_created_on' => date("Y-m-d H:i:s"),
    //         ];

    //         if ($existingRecord) {
    //             // Update the existing row
    //             $servicePackageEngine =  $ServicePackageEnginesModel->where('speng_spmc_id', $model_id)
    //                 ->set($insertOrUpdateData)->update();
    //         } else {
    //             // Insert new row
    //             $servicePackageEngine =  $ServicePackageEnginesModel->insert($insertOrUpdateData);
    //         }

    //         if ($servicePackageEngine) {
    //             $insertData = [];
    //             $PartsUpdateData = []; // it was used in for loop and not been declared early.
    //             foreach ($partsData as $part) {
    //                 //only enter applicable parts
    //                 if ($part->applicable == '1') {
    //                     if ($part->sp_spare_id == 0) {
    //                         $insertData[] = [
    //                             'sp_spare_spmc_id' => $model_id,
    //                             'sp_spare_pm_id' => $part->id,
    //                             'sp_spare_qty' => $part->qty,
    //                             'sp_spare_applicable' => $part->applicable,
    //                             'sp_spare_created_on' => date("Y-m-d H:i:s"),
    //                             'sp_spare_created_by' => $userId,
    //                             'sp_spare_updated_on' => date("Y-m-d H:i:s"),
    //                             'sp_spare_updated_by' => $userId,
    //                             'sp_spare_group_seq' => $part->group_seq,
    //                         ];
    //                     } else {
    //                         $PartsUpdateData[] = [
    //                             'sp_spare_id' => $part->sp_spare_id,
    //                             'sp_spare_pm_id' => $part->id,
    //                             'sp_spare_applicable' => $part->applicable,
    //                             'sp_spare_qty' => $part->qty,
    //                             // 'sp_spare_delete_flag' => $part->sp_spare_delete_flag,
    //                             'sp_spare_updated_on' => date("Y-m-d H:i:s"),
    //                             'sp_spare_updated_by' => $userId,
    //                             'sp_spare_group_seq' => $part->group_seq,
    //                         ];
    //                     }
    //                 } else if ($part->applicable == '0') {
    //                     if ($part->sp_spare_id != 0) {
    //                         $PartsUpdateData[] = [
    //                             'sp_spare_id' => $part->sp_spare_id,
    //                             'sp_spare_applicable' => $part->applicable,
    //                             'sp_spare_updated_on' => date("Y-m-d H:i:s"),
    //                             'sp_spare_updated_by' => $userId,
    //                             'sp_spare_delete_flag' => 1,
    //                         ];
    //                     }
    //                 }
    //             }

    //             if (!empty($insertData)) {
    //                 $servicePackageParts = $SP_Parts_Model->insertBatch($insertData);
    //             }

    //             if (!empty($PartsUpdateData)) {
    //                 $servicePackageParts = $SP_Parts_Model->updateBatch($PartsUpdateData, 'sp_spare_id');
    //             }
    //             $insertModelData = [
    //                 'spmc_status_flag' => $this->request->getVar('spmc_status_flag'),
    //                 'spmc_draft_flag' => $this->request->getVar('draft_flag'),
    //                 'spmc_session_flag' => 0,
    //                 'spmc_updated_by' => $userId,
    //                 'spmc_updated_on' =>  date("Y-m-d H:i:s"),
    //             ];

    //             $ServicePackageModelCodeModel->where('spmc_id', $model_id)->set($insertModelData)->update();
    //         }

    //         $brandModel = new BrandModel();
    //         $brands = $brandModel->findAll();
    //         $brandMap = array_column($brands, 'brand_name', 'brand_id');
    //         // [1 => 'Bosch', 2 => 'Shell', ...]

    //         $itemDescriptions = [];
    //         foreach ($partsData as $part) {
    //             if ($part->applicable == '1') {
    //                 $brandId = $part->Brand ?? null;
    //                 $brand   = $brandMap[$brandId] ?? 'N/A';
    //                 $price   = $part->PRICE ?? 'N/A';
    //                 $qty     = $part->qty ?? 'N/A';
    //                 $itemDescriptions[] = $part->DESCRIPTION
    //                     . " (Part No: {$part->PART_NO}, Brand: {$brand}, Qty: {$qty}, Unit Price: AED {$price})";
    //             }
    //         }

    //         $itemList = implode("; ", $itemDescriptions);

    //         $engId = $this->request->getVar('eng_id');
    //         $engineNo = 'N/A';
    //         if ($engId) {
    //             $engineModel = new EngineMasterModel();
    //             $engine = $engineModel->where('eng_id', $engId)->first();
    //             if ($engine) {
    //                 $engineNo = $engine['eng_no']; // or $engine->eng_no if object
    //             }
    //         }

    //         $logNote = "Service package for Model Code {$modelCode} (Engine No: {$engineNo}) has been {$action} with the following parts: {$itemList}.";
    //         $logData = [
    //             'sp_log_spmc_id'   => $model_id,
    //             'sp_log_notes'     => $logNote,
    //             'sp_log_created_by' => $userId,
    //             'sp_log_created_on' => date("Y-m-d H:i:s"),
    //         ];

    //         $SpLogTable->insert($logData);


    //         $this->db->transComplete();

    //         if ($this->db->transStatus() === false) {
    //             $this->db->transRollback();
    //             $response['ret_data'] = "fail";
    //             return $this->respond($response, 200);
    //         } else {
    //             $this->db->transCommit();
    //             $response = [
    //                 'ret_data' => 'success',
    //             ];
    //             return $this->respond($response, 200);
    //         }
    //     }
    // }


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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $ServicePackageEnginesModel = new ServicePackageEnginesModel();
            $SP_Parts_Model = new ServicePackageSpareModel();
            $SparePartsMasterModel = new SparePartsMaster();
            $SpLogTable = new ServicePackageLogModel();

            $modelCode = $this->request->getVar('model_code');

            $modelData = $ServicePackageModelCodeModel->select('spmc_id')->where('spmc_value', $modelCode)
                ->where('spmc_delete_flag', 0)->first();

            $model_id = $modelData['spmc_id'];
            $userId = $this->request->getVar('user_id');
            $engine_id = $this->request->getVar('engine_id'); // use this consistently
            $partsData =  $this->request->getVar('parts');

            $action = ($this->request->getVar('draft_flag') == 1) ? "Drafted" : "Saved";

            $this->db->transStart();

            // -------- PRELOAD existing service_package_spares rows for this model ----------
            $existingSpares = [];
            $existingSparesByPmId = [];

            $spareRows = $SP_Parts_Model
                ->select('sp_spares.*, parts_master.*')
                ->join('parts_master', 'parts_master.pm_id = sp_spares.sp_spare_pm_id', 'left')
                ->where('sp_spare_spmc_id', $model_id)
                ->findAll();
            foreach ($spareRows as $r) {
                $existingSpares[(int)$r['sp_spare_id']] = $r;
                if (!empty($r['sp_spare_pm_id'])) {
                    $existingSparesByPmId[(int)$r['sp_spare_pm_id']] = $r;
                }
            }
            // ------------------------------------------------------------------------------

            // ---- Update / Insert master parts, but capture old master values BEFORE update ----
            foreach ($partsData as $part) {
                if ($part->applicable == '1') {
                    if ($part->id == 0) {
                        // insert master
                        $insertSpareParts = [
                            'pm_code' => $part->PART_NO,
                            'pm_sp_pm_id' => $part->sp_pm_id,
                            'pm_unit_type' => $part->unit_type,
                            'pm_brand' => $part->Brand,
                            'pm_price' => $part->PRICE,
                            'pm_created_on' => date("Y-m-d H:i:s"),
                            'pm_created_by' => $userId,
                            'pm_updated_on' =>  date("Y-m-d H:i:s"),
                            'pm_updated_by' => $userId,
                        ];
                        $SparePartsMasterModel->insert($insertSpareParts);
                        $insertedId = $SparePartsMasterModel->insertID();
                        // assign this id back to the part so later sp_spare_pm_id references it
                        $part->id = $insertedId;
                        // Since it's new master, old values are null
                        $part->old_pm_code = null;
                        $part->old_pm_price = null;
                    } else {
                        // BEFORE updating master fetch the current master by choosing the correct pm_id
                        $pmBefore = null;

                        // try to find the spare row that references this master (sp_spare_pm_id)
                        if (!empty($part->sp_spare_id) && isset($existingSpares[(int)$part->sp_spare_id])) {
                            $spareExisting = $existingSpares[(int)$part->sp_spare_id];
                            if (!empty($spareExisting['sp_spare_pm_id'])) {
                                $pmBefore = $SparePartsMasterModel->where('pm_id', $spareExisting['sp_spare_pm_id'])->first();
                            }
                        }

                        // fallback: try by pm id (incoming $part->id is master pm id)
                        if ($pmBefore === null) {
                            $pmBefore = $SparePartsMasterModel->where('pm_id', $part->id)->first();
                        }

                        $part->old_pm_code  = $pmBefore['pm_code']  ?? null;
                        $part->old_pm_price = $pmBefore['pm_price'] ?? null;

                        // now do update on master
                        $updateSpareParts = [
                            'pm_code'       => $part->PART_NO,
                            'pm_sp_pm_id' => $part->sp_pm_id,
                            'pm_unit_type'  => $part->unit_type,
                            'pm_brand'      => $part->Brand,
                            'pm_updated_on' => date("Y-m-d H:i:s"),
                            'pm_updated_by' => $userId,
                        ];
                        if (isset($part->old_price) && $part->old_price !== null && $part->old_price !== '' && $part->old_price != 0 && $part->old_price != '0') {
                            $updateSpareParts['pm_price'] = $part->old_price;
                            $updateSpareParts['pm_new_price'] = $part->PRICE;
                        } else {
                            $updateSpareParts['pm_price'] = $part->PRICE;
                        }
                        $SparePartsMasterModel->where('pm_id', $part->id)->set($updateSpareParts)->update();
                    }
                }
            }

            // ================= Engine assign/update =================
            $existingRecord = $ServicePackageEnginesModel
                ->where('speng_spmc_id', $model_id)
                ->first();

            $insertOrUpdateData = [
                'speng_eng_id' => $engine_id,
                'speng_spmc_id' => $model_id,
                'speng_created_on' => date("Y-m-d H:i:s"),
            ];

            if ($existingRecord) {
                $servicePackageEngine =  $ServicePackageEnginesModel->where('speng_spmc_id', $model_id)
                    ->set($insertOrUpdateData)->update();
            } else {
                $servicePackageEngine =  $ServicePackageEnginesModel->insert($insertOrUpdateData);
            }

            // ---------------- Insert/Update spare rows (service_package_spares) ----------------
            $insertData = [];
            $PartsUpdateData = [];

            foreach ($partsData as $part) {
                if ($part->applicable == '1') {
                    if ($part->sp_spare_id == 0) {
                        $insertData[] = [
                            'sp_spare_spmc_id' => $model_id,
                            'sp_spare_pm_id' => $part->id,
                            'sp_spare_qty' => $part->qty,
                            'sp_spare_applicable' => $part->applicable,
                            'sp_spare_created_on' => date("Y-m-d H:i:s"),
                            'sp_spare_created_by' => $userId,
                            'sp_spare_updated_on' => date("Y-m-d H:i:s"),
                            'sp_spare_updated_by' => $userId,
                            'sp_spare_group_seq' => $part->group_seq,
                            'unit'  => $part->unit_type,
                            'brand' => $part->Brand,
                            'partname' => $part->DESCRIPTION,
                        ];
                    } else {
                        $PartsUpdateData[] = [
                            'sp_spare_id' => $part->sp_spare_id,
                            'sp_spare_pm_id' => $part->id,
                            'sp_spare_applicable' => $part->applicable,
                            'sp_spare_qty' => $part->qty,
                            'sp_spare_updated_on' => date("Y-m-d H:i:s"),
                            'sp_spare_updated_by' => $userId,
                            'sp_spare_group_seq' => $part->group_seq,
                            // pass old master values so checksDataParts() can detect code/price diffs
                            'OLD_PM_CODE'  => $part->old_pm_code  ?? null,
                            'OLD_PM_PRICE' => $part->old_pm_price ?? null,
                            // include PART_NO and PRICE as the "new" values for comparison
                            'PART_NO' => $part->PART_NO,
                            'PRICE'   => $part->PRICE,
                            'unit'  => $part->unit_type,
                            'brand' => $part->Brand,
                            'partname' => $part->DESCRIPTION,
                        ];
                    }
                } else if ($part->applicable == '0') {
                    if ($part->sp_spare_id != 0) {
                        $PartsUpdateData[] = [
                            'sp_spare_id' => $part->sp_spare_id,
                            'sp_spare_applicable' => $part->applicable,
                            'sp_spare_updated_on' => date("Y-m-d H:i:s"),
                            'sp_spare_updated_by' => $userId,
                            'sp_spare_delete_flag' => 1,
                            'sp_spare_pm_id' => $part->id,
                            'sp_spare_qty' => $part->qty,
                            // include old master values if available
                            'OLD_PM_CODE'  => $part->old_pm_code  ?? null,
                            'OLD_PM_PRICE' => $part->old_pm_price ?? null,
                            'PART_NO' => $part->PART_NO ?? null,
                            'PRICE'   => $part->PRICE ?? null,
                            'unit'  => $part->unit_type,
                            'brand' => $part->Brand,
                            'partname' => $part->DESCRIPTION,
                        ];
                    }
                }
            }

            if (!empty($insertData)) {
                $servicePackageParts = $SP_Parts_Model->insertBatch($insertData);
            }

            if (!empty($PartsUpdateData)) {
                $servicePackageParts = $SP_Parts_Model->updateBatch($PartsUpdateData, 'sp_spare_id');
            }

            // update model flags
            $insertModelData = [
                'spmc_status_flag' => $this->request->getVar('spmc_status_flag'),
                'spmc_draft_flag' => $this->request->getVar('draft_flag'),
                'spmc_session_flag' => 0,
                'spmc_updated_by' => $userId,
                'spmc_updated_on' =>  date("Y-m-d H:i:s"),
            ];
            $ServicePackageModelCodeModel->where('spmc_id', $model_id)->set($insertModelData)->update();

            $insertNotes = [];
            $updateNotes = [];

            // Index incoming parts by pm id and by sp_spare_id for quick lookup
            $partsByPmId = [];
            $partsBySpId = [];
            foreach ($partsData as $p) {
                $partsByPmId[(int)$p->id][] = $p;
                if (!empty($p->sp_spare_id)) $partsBySpId[(int)$p->sp_spare_id] = $p;
            }

            // Inserted: parts where sp_spare_id was 0
            foreach ($partsData as $p) {
                if ($p->applicable == '1' && (int)$p->sp_spare_id === 0) {
                    $brandModel = new BrandModel();
                    // get brand name safely (we'll do a single fetch earlier in heavy load scenarios)
                    $brandMap = [];
                    $brands = $brandModel->findAll();
                    $brandMap = array_column($brands, 'brand_name', 'brand_id');
                    $brandId = $p->Brand ?? null;
                    $brand   = $brandMap[$brandId] ?? 'N/A';
                    $price   = $p->PRICE ?? 'N/A';
                    $qty     = $p->qty ?? 'N/A';
                    $insertNotes[] = "{$p->DESCRIPTION} (Part No: {$p->PART_NO}, Brand: {$brand}, Qty: {$qty}, Unit Price: AED {$price})";
                }
            }

            // Updated: iterate $PartsUpdateData which contains OLD_PM_CODE etc.
            // build lookup of old data by spare_id
            $oldPartsById = [];
            foreach ($spareRows as $row) {
                $oldPartsById[(int)$row['sp_spare_id']] = $row;
            }

            if (!empty($PartsUpdateData)) {
                foreach ($PartsUpdateData as $upd) {
                    if (empty($upd['sp_spare_id'])) continue;

                    $oldRow = $oldPartsById[(int)$upd['sp_spare_id']] ?? null;
                    if (!$oldRow) continue; // no match found

                    // description for logging
                    $desc = $upd['partname'] ?? 'N/A';

                    // now send full old + new rows into checksDataParts
                    $changeNote = $this->checksDataParts($oldRow, $desc, $upd);

                    if ($changeNote) {
                        $updateNotes[] = $changeNote;
                    }
                }
            }


            // Combine notes into a final log message
            $allNotes = [];
            if (!empty($insertNotes)) {
                $allNotes[] = "Inserted Parts: " . implode("; ", $insertNotes);
            }
            if (!empty($updateNotes)) {
                $allNotes[] = "Updated Parts: " . implode("; ", $updateNotes);
            }


            $finalDetails = implode(" | ", $allNotes);

            // get engine number for the log message (use engine_id)
            $engineNo = 'N/A';
            if (!empty($engine_id)) {
                $engineModel = new EngineMasterModel();
                $engine = $engineModel->where('eng_id', $engine_id)->first();
                if ($engine) $engineNo = $engine['eng_no'];
            }

            // Compose final log message and insert with actor and action via addSpLog()
            $detail = "(Engine No: {$engineNo}) " . $finalDetails;
            $this->addSpLog($SpLogTable, $model_id, $userId, $detail, $action, $modelCode);

            // Finish transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $response['ret_data'] = "fail";
                return $this->respond($response, 200);
            } else {
                $this->db->transCommit();
                $response = [
                    'ret_data' => 'success',
                ];
                return $this->respond($response, 200);
            }
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

        public function getServicePackage()
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $kmModel = new KilometerMasterModel();
            $SP_Parts_Model = new ServicePackageSpareModel();
            $SP_Labours_Model = new ServicePackageLabourModel();
            $ServicePackageEnginesModel = new ServicePackageEnginesModel();
            $Sp_KmPrice_Model = new ServicePackageKmPriceModel();
            $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();

            $modelCode = $this->request->getVar('modelCode');
            $modelYear = $this->request->getVar('modelYear');
            $variant = $this->request->getVar('variant');
            $kilometer = $this->request->getVar('kilometer');
            $model_id = null;

            // Case 1: Search by Model Code
            if (!empty($modelCode)) {
                $modelDataList = $ServicePackageModelCodeModel
                    ->where('spmc_delete_flag', 0)
                    ->where('spmc_value', $modelCode)
                    ->findAll(); // Return all model codes with same code
            }
            // Case 2: Search by Year and Variant
            else if (!empty($modelYear) && !empty($variant)) {
                $singleModel = $ServicePackageModelCodeModel
                    ->where('spmc_delete_flag', 0)
                    ->where('spmc_model_year', $modelYear)
                    ->where('spmc_variant', $variant)
                    ->first();

                $modelDataList = $singleModel ? [$singleModel] : [];
            } else {
                $modelDataList = [];
            }

            // If models found
            if (!empty($modelDataList)) {

                // Fetch labour data for the selected model
                $modelLabourData = $ServicePackageModelCodeLabourModel
                    ->select('spmcl_type')
                    ->where('model_code', $modelCode)
                    ->where('spmcl_delete_flag', 0)
                    ->first();
                // Initialize final array
                $finalModels = [];

                if ($modelLabourData && $modelLabourData['spmcl_type'] == '1') {
                    // ✅ CASE 1: spmcl_type == 1 → take ALL model packages
                    $modelsToProcess = $modelDataList;
                } else {
                    // ✅ CASE 2: spmcl_type != 1 → take ONLY the first package
                    $modelsToProcess = [reset($modelDataList)]; // reset() returns the first element
                }

                // Loop through selected models (based on above condition)
                foreach ($modelsToProcess as $modelData) {

                    // 🔹 Fetch labour factor
                    $modelLabourData = $ServicePackageModelCodeLabourModel
                        ->where('model_code', $modelData['spmc_value'])
                        ->where('spmcl_delete_flag', 0)
                        ->first();

                    $labourFactor = 0;
                    if (!empty($modelLabourData)) {
                        $labourRate = (float) $modelLabourData['labour_rate'];
                        $increasePct = (float) $modelLabourData['spmcl_inc_pct'];
                        $labourFactor = $labourRate + ($labourRate * $increasePct / 100);
                    }

                    $model_id = $modelData['spmc_id'];

                    // 🔹 Engine details
                    $engineDetails = $ServicePackageEnginesModel->select('eng_id,eng_no,speng_spmc_id,eng_labour_factor')
                        ->where("speng_delete_flag", 0)
                        ->where("speng_spmc_id", $model_id)
                        ->join('engine_master', 'engine_master.eng_id = speng_eng_id', 'left')
                        ->first();

                    // 🔹 Fetch price map for each km
                    $kmPriceMap = $Sp_KmPrice_Model->table('sp_km_price_map')
                        ->select('spkmp_spkm_id, spkmp_markup_price, spkmp_display_price')
                        ->where('spkmp_spmc_id', $model_id)
                        ->get()
                        ->getResultArray();

                    $kmPriceMapById = [];
                    foreach ($kmPriceMap as $row) {
                        $kmPriceMapById[$row['spkmp_spkm_id']] = [
                            'markup_price' => $row['spkmp_markup_price'],
                            'display_price' => $row['spkmp_display_price'],
                        ];
                    }

                    // 🔹 Get Spares
                    $spares = $SP_Parts_Model
                        ->select('spim_name, pm_price, spkm_km_optional_flag, pm_code, sp_spare_category, sp_spare_qty, sp_spare_id, sp_spare_optional_flag, spkm_km_id, sp_spare_group_seq, sp_spare_labour_unit, km_value, sp_km_item_map.spkm_delete_flag')
                        ->where('sp_spare_spmc_id', $model_id)
                        ->where('sp_spare_delete_flag', 0)
                        ->join('parts_master', 'parts_master.pm_id = sp_spare_pm_id', 'left')
                        ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
                        ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                        ->join('sp_km_item_map', 'sp_km_item_map.spkm_item_id = sp_spare_id AND sp_km_item_map.spkm_item_type = 0 AND sp_km_item_map.spkm_delete_flag = 0', 'left')
                        ->join('kilometer_master', 'kilometer_master.km_id = spkm_km_id', 'left')
                        ->findAll();

                    // 🔹 Get Labours
                    $labours = $SP_Labours_Model
                        ->select('spim_name, spkm_km_optional_flag, sp_pm_category, sp_labour_qty, sp_labour_id, sp_labour_optional_flag, sp_labour_group_seq, spkm_km_id, sp_labour_unit, km_value, sp_km_item_map.spkm_delete_flag')
                        ->where('sp_labour_spmc_id', $model_id)
                        ->where('sp_labour_delete_flag', 0)
                        ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = sp_labour_lm_id', 'left')
                        ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                        ->join(
                            'sp_km_item_map',
                            'sp_km_item_map.spkm_item_id = sp_labour_id AND sp_km_item_map.spkm_item_type = 1 AND sp_km_item_map.spkm_delete_flag = 0',
                            'left'
                        )
                        ->join('kilometer_master', 'kilometer_master.km_id = spkm_km_id', 'left')
                        ->findAll();

                    // 🔹 Combine and group by km_id
                    $combinedByKm = [];

                    foreach ($spares as $spare) {
                        if (!empty($spare['spkm_km_id'])) {
                            $km_id = $spare['spkm_km_id'];
                            $spare['item_type'] = 0;
                            $combinedByKm[$km_id]['items'][] = $spare;
                            $combinedByKm[$km_id]['km_value'] = $spare['km_value'];
                        }
                    }

                    foreach ($labours as $labour) {
                        if (!empty($labour['spkm_km_id'])) {
                            $km_id = $labour['spkm_km_id'];
                            $labour['item_type'] = 1;
                            $combinedByKm[$km_id]['items'][] = $labour;
                            $combinedByKm[$km_id]['km_value'] = $labour['km_value'];
                        }
                    }

                    // 🔹 Build final structure per model
                    $final = [];
                    foreach ($combinedByKm as $km_id => $data) {
                        $final[] = [
                            'km_id' => $km_id,
                            'km_value' => $data['km_value'] ?? '',
                            'actual_price' => $kmPriceMapById[$km_id]['markup_price'] ?? 0,
                            'display_price' => $kmPriceMapById[$km_id]['display_price'] ?? 0,
                            'items' => $data['items'],
                        ];
                    }

                    // 🔹 Add to final array
                    $finalModels[] = [
                        'modelId' => $modelData['spmc_id'],
                        'modelYearUsed' => $modelData['spmc_model_year'] ?? null,
                        'Vin_No' => $modelData['spmc_vin_no'] ?? null,
                        'spmc_type' => $modelData['spmc_type'] ?? null,
                        'spmc_status_flag' => $modelData['spmc_status_flag'] ?? null,
                        'servicePackage' => $final,
                        'engineDetails' => $engineDetails,
                        'labourFactor' => $labourFactor,
                    ];
                }

                if ($modelLabourData && $modelLabourData['spmcl_type'] == '1') {
                    // ✅ CASE 1: spmcl_type == 1 → take ALL model packages
                    $modelsToProcess = $modelDataList;
                    $response = [
                        'ret_data' => 'success',
                        'models' => $finalModels,
                        'spmcl_type' => $modelLabourData['spmcl_type'],
                    ];
                } else {
                    $response = [
                        'ret_data' => 'success',
                        'models' => $finalModels,
                        'spmcl_type' => 0,
                    ];
                }
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }

            return $this->respond($response, 200);
        }
    }

    // public function getServicePackage()
    // {

    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $tokendata['uid'])->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata) {

    //         $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
    //         $kmModel = new KilometerMasterModel();
    //         $SP_Parts_Model = new ServicePackageSpareModel();
    //         $SP_Labours_Model = new ServicePackageLabourModel();
    //         $ServicePackageEnginesModel = new ServicePackageEnginesModel();
    //         $Sp_KmPrice_Model = new ServicePackageKmPriceModel();
    //         $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();


    //         $modelCode = $this->request->getVar('modelCode');
    //         $modelYear = $this->request->getVar('modelYear');
    //         $variant = $this->request->getVar('variant');

    //         $kilometer = $this->request->getVar('kilometer');

    //         // $km_id = $this->request->getVar('kilometer');
    //         $model_id = null;

    //         // $kmData = $kmModel->where('km_value', $kilometer)->where('km_delete_flag', 0)->first();
    //         // if ($kmData) {
    //         //     $km_id = $kmData['km_id'];
    //         // }

    //         //PRIVOUS CHECHING FOR THE MODEL CODE............
    //         // $modelData = $ServicePackageModelCodeModel->where("spmc_delete_flag", 0)
    //         //     ->where("spmc_value", $modelCode)
    //         //     ->first();

    //         if (!empty($modelCode)) {
    //             $modelData = $ServicePackageModelCodeModel
    //                 ->where('spmc_delete_flag', 0)
    //                 ->where('spmc_value', $modelCode)
    //                 ->first();
    //         }
    //         // Case 2: Search by Year and Variant only
    //         else if (!empty($modelYear) && !empty($variant)) {
    //             $modelData = $ServicePackageModelCodeModel
    //                 ->where('spmc_delete_flag', 0)
    //                 ->where('spmc_model_year', $modelYear)
    //                 ->where('spmc_variant', $variant)
    //                 ->first();
    //         }

    //         if (!empty($modelData)) {
    //             $modelLabourData = $ServicePackageModelCodeLabourModel
    //                 ->where('model_code', $modelData['spmc_value'])
    //                 ->where('spmcl_delete_flag', 0)
    //                 ->first();
    //         }
    //         $labourFactor = 0;

    //         if (!empty($modelLabourData)) {
    //             $labourRate = (float) $modelLabourData['labour_rate'];
    //             $increasePct = (float) $modelLabourData['spmcl_inc_pct'];

    //             // Add increased percentage to labour rate
    //             $labourFactor = $labourRate + ($labourRate * $increasePct / 100);
    //         }

    //         // if (!$modelData) {
    //         //     $possibleMatches = $ServicePackageModelCodeModel
    //         //         ->where('spmc_delete_flag', 0)
    //         //         ->where('spmc_value', $modelCode)
    //         //         ->where('spmc_variant', $variant)
    //         //         ->findAll();

    //         //     $closest = null;
    //         //     $smallestDiff = null;

    //         //     foreach ($possibleMatches as $match) {
    //         //         $year = (int)$match['spmc_model_year'];
    //         //         $diff = abs($year - (int)$modelYear);

    //         //         if (is_null($smallestDiff) || $diff < $smallestDiff) {
    //         //             $closest = $match;
    //         //             $smallestDiff = $diff;
    //         //         }
    //         //     }
    //         //     if (!$modelData && $closest) {
    //         //         $modelData = $closest;
    //         //     }
    //         // }

    //         if (!empty($modelData)) {

    //             $model_id = $modelData['spmc_id'];

    //             $engineDetails = $ServicePackageEnginesModel->select('eng_id,eng_no,speng_spmc_id,eng_labour_factor')
    //                 ->where("speng_delete_flag", 0)
    //                 ->where("speng_spmc_id", $model_id)
    //                 ->join('engine_master', 'engine_master.eng_id = speng_eng_id', 'left')
    //                 ->first();


    //             // Fetch price map for each km
    //             $kmPriceMap = $Sp_KmPrice_Model->table('sp_km_price_map')
    //                 ->select('spkmp_spkm_id, spkmp_markup_price, spkmp_display_price')
    //                 ->where('spkmp_spmc_id', $model_id)
    //                 ->get()
    //                 ->getResultArray();

    //             $kmPriceMapById = [];
    //             foreach ($kmPriceMap as $row) {
    //                 $kmPriceMapById[$row['spkmp_spkm_id']] = [
    //                     'markup_price' => $row['spkmp_markup_price'],
    //                     'display_price' => $row['spkmp_display_price'],
    //                 ];
    //             }

    //             // Get Spares
    //             $spares = $SP_Parts_Model
    //                 ->select('spim_name, pm_price,spkm_km_optional_flag,pm_code,sp_spare_category, sp_spare_qty, sp_spare_id, sp_spare_optional_flag, spkm_km_id,sp_spare_group_seq, sp_spare_labour_unit, km_value')
    //                 ->where('sp_spare_spmc_id', $model_id)
    //                 ->where('sp_spare_delete_flag', 0)
    //                 ->join('parts_master', 'parts_master.pm_id = sp_spare_pm_id', 'left')
    //                 ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
    //                 ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
    //                 ->join('sp_km_item_map', 'sp_km_item_map.spkm_item_id = sp_spare_id AND sp_km_item_map.spkm_item_type = 0 AND sp_km_item_map.spkm_delete_flag = 0', 'left')
    //                 ->join('kilometer_master', 'kilometer_master.km_id = spkm_km_id', 'left')
    //                 ->findAll();

    //             // Get Labours
    //             $labours = $SP_Labours_Model
    //                 ->select('spim_name,spkm_km_optional_flag, sp_pm_category, sp_labour_qty, sp_labour_id, sp_labour_optional_flag,sp_labour_group_seq, spkm_km_id, sp_labour_unit, km_value')
    //                 ->where('sp_labour_spmc_id', $model_id)
    //                 ->where('sp_labour_delete_flag', 0)
    //                 ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = sp_labour_lm_id', 'left')
    //                 ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
    //                 ->join(
    //                     'sp_km_item_map',
    //                     'sp_km_item_map.spkm_item_id = sp_labour_id AND sp_km_item_map.spkm_item_type = 1 AND sp_km_item_map.spkm_delete_flag = 0',
    //                     'left'
    //                 )
    //                 ->join('kilometer_master', 'kilometer_master.km_id = spkm_km_id', 'left')
    //                 ->findAll();

    //             // Combine and group by km_id
    //             $combinedByKm = [];

    //             foreach ($spares as $spare) {
    //                 if (!empty($spare['spkm_km_id'])) {
    //                     $km_id = $spare['spkm_km_id'];
    //                     $spare['item_type'] = 0;
    //                     $combinedByKm[$km_id]['items'][] = $spare;
    //                     $combinedByKm[$km_id]['km_value'] = $spare['km_value'];
    //                 }
    //             }

    //             foreach ($labours as $labour) {
    //                 if (!empty($labour['spkm_km_id'])) {
    //                     $km_id = $labour['spkm_km_id'];
    //                     $labour['item_type'] = 1;
    //                     $combinedByKm[$km_id]['items'][] = $labour;
    //                     $combinedByKm[$km_id]['km_value'] = $labour['km_value'];
    //                 }
    //             }

    //             // Final structure
    //             $final = [];
    //             foreach ($combinedByKm as $km_id => $data) {
    //                 $final[] = [
    //                     'km_id' => $km_id,
    //                     'km_value' => $data['km_value'] ?? '',
    //                     'actual_price' => $kmPriceMapById[$km_id]['markup_price'] ?? 0,
    //                     'display_price' => $kmPriceMapById[$km_id]['display_price'] ?? 0,
    //                     'items' => $data['items'],
    //                 ];
    //             }
    //         } else {
    //             $final = [];
    //         }


    //         if (!empty($final)) {
    //             $response = [
    //                 'ret_data' => 'success',
    //                 'servicePackage' => $final,
    //                 'engineDetails' => $engineDetails,
    //                 'labourFactor' => $labourFactor,
    //                 'modelId' => $modelData['spmc_id'],
    //                 'modelYearUsed' => $modelData['spmc_model_year'] ?? null, // Optional: show used year
    //                 'Vin_No' => $modelData['spmc_vin_no'] ?? null, // Optional: show used Vin No
    //             ];
    //         } else if (!empty($modelData)) {
    //             $response = [
    //                 'ret_data' => 'success',
    //                 'modelData' => $modelData,
    //                 'modelYearUsed' => $modelData['spmc_model_year'] ?? null,
    //                 'message' => 'Model found, but no service package items available.',
    //                 'Vin_No' => $modelData['spmc_vin_no'] ?? null, // Optional: show used Vin No
    //             ];
    //         } else {
    //             $response = [
    //                 'ret_data' => 'fail',

    //             ];
    //         }
    //         return $this->respond($response, 200);
    //     }
    // }
    public function getPartsForEngineNo()
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

            $ServicePackageEnginesModel = new ServicePackageEnginesModel();
            $eng_id = $this->request->getVar('eng_id');

            $partsList = $ServicePackageEnginesModel->where("speng_delete_flag", 0)
                ->where("speng_eng_id", $eng_id)
                ->join('sp_model_code', 'sp_model_code.spmc_id=speng_spmc_id', 'left')
                ->join('sp_spares', 'sp_spares.sp_spare_spmc_id=speng_spmc_id', 'left')
                ->join('parts_master', 'parts_master.pm_id=sp_spares.sp_spare_pm_id', 'left')
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id=parts_master.pm_sp_pm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id=sp_pm_spim_id', 'left')
                ->join('brand_list', 'brand_list.brand_id=pm_brand', 'left')
                ->where("sp_spare_category", 0)
                ->where('sp_model_code.spmc_status_flag', 5)
                ->groupby('sp_spare_id')
                ->findAll();

            if ($partsList) {
                $response = [
                    'ret_data' => 'success',
                    'partsList' => $partsList,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getEngineAndSparesByModelCode()
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $ServicePackageEnginesModel = new ServicePackageEnginesModel();
            $SP_Parts_Model = new ServicePackageSpareModel();
            $SP_Labours_Model = new ServicePackageLabourModel();
            $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();


            $modelCode = $this->request->getVar('model_code');
            $modelData = $ServicePackageModelCodeModel->select('spmc_id,spmc_draft_flag')->where('spmc_value', $modelCode)->where('spmc_delete_flag', 0)->first();
            $model_id = $modelData['spmc_id'];

            $modelLabourData = $ServicePackageModelCodeLabourModel
                ->where('model_code', $modelCode)
                ->where('spmcl_delete_flag', 0)
                ->first();

            $labourFactor = 0;

            if ($modelLabourData) {
                $labourRate = (float) $modelLabourData['labour_rate'];
                $increasePct = (float) $modelLabourData['spmcl_inc_pct'];

                // Add increased percentage to labour rate
                $labourFactor = $labourRate + ($labourRate * $increasePct / 100);
            }

            $engData = $ServicePackageEnginesModel
                ->select('speng_eng_id, eng_id, eng_no, eng_labour_factor')
                ->where('speng_spmc_id', $model_id)
                ->where('speng_delete_flag', 0)
                ->join('engine_master', 'engine_master.eng_id = speng_eng_id', 'left')
                ->where('eng_delete_flag', 0)
                ->first();

            // $spareData = $SP_Parts_Model->where('sp_spare_spmc_id', $model_id)->where('sp_spare_delete_flag', 0)
            //     ->join('parts_master', 'parts_master.pm_id=sp_spare_pm_id', 'left')
            //     ->join('sp_parts_master', 'sp_parts_master.sp_pm_id=pm_sp_pm_id', 'left')
            //     ->join('sp_item_master', 'sp_item_master.spim_id=sp_pm_spim_id', 'left')
            //     ->join('brand_list', 'brand_list.brand_id=pm_brand', 'left')
            //     ->join('sp_item_group', 'sp_item_group.sp_ig_spim_id = sp_item_master.spim_id', 'left')
            //     ->groupBy('sp_item_master.spim_id') // So we don’t get duplicate rows
            //     ->findAll();
            $spareData = $SP_Parts_Model
                ->select('sp_spares.*,sp_item_master.*, parts_master.*, sp_parts_master.*, brand_list.*, GROUP_CONCAT(DISTINCT sp_item_group.sp_ig_group_seq) as group_seqs')
                ->where('sp_spare_spmc_id', $model_id)
                ->where('sp_spare_delete_flag', 0)
                ->where('sp_spare_applicable', 1)
                ->join('parts_master', 'parts_master.pm_id = sp_spare_pm_id', 'left')
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                ->join('brand_list', 'brand_list.brand_id = pm_brand', 'left')
                ->join(
                    'sp_item_group',
                    'sp_item_group.sp_ig_spim_id = sp_item_master.spim_id AND sp_item_group.sp_ig_delete_flag = 0',
                    'left'
                )
                ->groupBy('sp_item_master.spim_id')
                ->findAll();


            // $labourData = $SP_Labours_Model->where('sp_labour_spmc_id', $model_id)->where('sp_labour_delete_flag', 0)
            //     ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = sp_labour_lm_id', 'left')
            //     ->join('sp_item_master', 'sp_item_master.spim_id=sp_pm_spim_id', 'left')
            //     ->join('sp_item_group', 'sp_item_group.sp_ig_spim_id = sp_item_master.spim_id', 'left')
            //     ->groupBy('sp_item_master.spim_id') // So we don’t get duplicate rows
            //     ->findAll();

            $labourData = $SP_Labours_Model
                ->select('sp_labours.*,sp_item_master.*, sp_parts_master.*, GROUP_CONCAT(DISTINCT sp_item_group.sp_ig_group_seq) as group_seqs')
                ->where('sp_labour_spmc_id', $model_id)
                ->where('sp_labour_delete_flag', 0)
                ->where('sp_labour_applicable', 1)
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = sp_labour_lm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                ->join(
                    'sp_item_group',
                    'sp_item_group.sp_ig_spim_id = sp_item_master.spim_id AND sp_item_group.sp_ig_delete_flag = 0',
                    'left'
                )
                ->groupBy('sp_item_master.spim_id')
                ->findAll();



            $response = [
                'ret_data' => 'fail', // default response
            ];

            // If spare or labour exists, update response
            if (!empty($spareData) || !empty($labourData)) {
                $response['ret_data'] = 'success';
                $response['engData'] = $engData ?? null;
                $response['labourFactor'] = $labourFactor ?? null;
                $response['modelData'] = $modelData ?? null;


                if (!empty($spareData)) {
                    $response['spareData'] = $spareData;
                }

                if (!empty($labourData)) {
                    $response['labourData'] = $labourData;
                }
            } else if (!empty($labourFactor) && $labourFactor != 0) {
                $response['labourFactor'] = $labourFactor ?? null;
                $response['ret_data'] = 'success';
            }

            return $this->respond($response, 200);
        }
    }

    // public function saveServicePackageLabours()
    // {
    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $tokendata['uid'])->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata) {

    //         $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
    //         $ServicePackageEnginesModel = new ServicePackageEnginesModel();
    //         $SP_Labours_Model = new ServicePackageLabourModel();
    //         $SP_Parts_Model = new ServicePackageSpareModel();
    //         $SparePartsMasterModel = new SparePartsMaster();
    //         $SpLogTable = new ServicePackageLogModel();

    //         $action = ($this->request->getVar('draft_flag') == 1)
    //             ? "drafted"
    //             : "saved";

    //         $modelCode = $this->request->getVar('model_code');
    //         $modelData = $ServicePackageModelCodeModel->select('spmc_id,spmc_session_flag,spmc_draft_flag')->where('spmc_value', $modelCode)->where('spmc_delete_flag', 0)->first();

    //         $model_id = $modelData['spmc_id'];
    //         $userId = $this->request->getVar('user_id');
    //         $laboursData =  $this->request->getVar('Labour');
    //         $partsData =  $this->request->getVar('parts');
    //         $engine_id = $this->request->getVar('engine_id');
    //         $consumablesData =  $this->request->getVar('consumables');

    //         $this->db->transStart();

    //         $now = date('Y-m-d H:i:s');

    //         $partsData = is_array($partsData) ? $partsData : [];
    //         $consumablesData = is_array($consumablesData) ? $consumablesData : [];

    //         $updateLogs = [];

    //         $pmIdsToPrefetch = [];
    //         foreach (array_merge($partsData, $consumablesData) as $part) {
    //             if (!empty($part->id) && (int)$part->id > 0) {
    //                 $pmIdsToPrefetch[] = (int)$part->id;
    //             }
    //         }
    //         $pmIdsToPrefetch = array_values(array_unique($pmIdsToPrefetch));
    //         $existingPmMap = [];
    //         if (!empty($pmIdsToPrefetch)) {
    //             $rows = $SparePartsMasterModel->whereIn('pm_id', $pmIdsToPrefetch)->findAll();
    //             foreach ($rows as $r) $existingPmMap[(int)$r['pm_id']] = $r;
    //         }

    //         // // Merge both parts and consumables into one loop
    //         foreach (array_merge($partsData, $consumablesData) as $part) {
    //             if ((int) $part->applicable !== 1) {
    //                 continue;
    //             }

    //             // Shared column data
    //             $common = [
    //                 'pm_code'       => $part->PART_NO,
    //                 // 'pm_name'       => $part->DESCRIPTION,
    //                 'pm_unit_type'  => $part->unit_type,
    //                 'pm_brand'      => $part->Brand,
    //                 'pm_updated_on' => $now,
    //                 'pm_updated_by' => $userId,
    //                 'pm_sp_pm_id' => $part->sp_pm_id,
    //             ];

    //             if (empty($part->id) || $part->id == 0) {
    //                 // INSERT new record (we do NOT log inserts as "updates")
    //                 $insertData = $common + [
    //                     'pm_price'      => $part->PRICE,
    //                     'pm_created_on' => $now,
    //                     'pm_created_by' => $userId,
    //                 ];
    //                 $SparePartsMasterModel->insert($insertData);
    //                 $part->id = $SparePartsMasterModel->insertID();
    //             } else {
    //                 // UPDATE existing record
    //                 // Determine intended final price for comparison:
    //                 $intendedPrice = null;
    //                 if (!empty($part->old_price) && $part->old_price != 0) {
    //                     // your logic sets pm_price to old_price and stores new into pm_new_price
    //                     $intendedPrice = (float)$part->old_price;
    //                 } else {
    //                     $intendedPrice = isset($part->PRICE) ? (float)$part->PRICE : null;
    //                 }

    //                 // compare with existing master row (if present)
    //                 $pmId = (int)$part->id;
    //                 if (isset($existingPmMap[$pmId])) {
    //                     $existingPm = $existingPmMap[$pmId];
    //                     $oldPrice = isset($existingPm['pm_price']) ? (float)$existingPm['pm_price'] : null;
    //                     $oldUnit = $existingPm['pm_unit_type'] ?? null;
    //                     $oldBrand = $existingPm['pm_brand'] ?? null;
    //                     $oldCode = $existingPm['pm_code'] ?? null;

    //                     $changedFields = [];
    //                     if ($oldPrice !== $intendedPrice) {
    //                         $changedFields[] = "price: " . ($oldPrice === null ? 'NULL' : $oldPrice) . " → " . ($intendedPrice === null ? 'NULL' : $intendedPrice);
    //                     }
    //                     $newUnit = $part->unit_type ?? null;
    //                     if ((string)$oldUnit !== (string)$newUnit) {
    //                         $changedFields[] = "unit_type: " . ($oldUnit ?? 'NULL') . " → " . ($newUnit ?? 'NULL');
    //                     }
    //                     $newBrand = $part->Brand ?? null;
    //                     if ((string)$oldBrand !== (string)$newBrand) {
    //                         $changedFields[] = "brand: " . ($oldBrand ?? 'NULL') . " → " . ($newBrand ?? 'NULL');
    //                     }
    //                     $newCode = $part->PART_NO ?? null;
    //                     if ((string)$oldCode !== (string)$newCode) {
    //                         $changedFields[] = "part_no: " . ($oldCode ?? 'NULL') . " → " . ($newCode ?? 'NULL');
    //                     }

    //                     if (!empty($changedFields)) {
    //                         $label = $part->DESCRIPTION ?? $part->PART_NO ?? ("pm_id:" . $pmId);
    //                         $updateLogs[] = "Spare master updated: {$label} (pm_id: {$pmId}) — " . implode(', ', $changedFields);
    //                     }
    //                 }

    //                 // prepare update data (your original logic)
    //                 if (!empty($part->old_price) && $part->old_price != 0) {
    //                     // track price change
    //                     $updateData = [
    //                         'pm_price'     => $part->old_price,
    //                         'pm_new_price' => $part->PRICE,
    //                     ] + $common;
    //                 } else {
    //                     // full overwrite
    //                     $updateData = $common + [
    //                         'pm_price' => $part->PRICE,
    //                     ];
    //                 }

    //                 $SparePartsMasterModel
    //                     ->where('pm_id', $part->id)
    //                     ->set($updateData)
    //                     ->update();
    //             }
    //         }

    //         // ----------------------
    //         // Engine handling (detect change and later update as you already do)
    //         // ----------------------
    //         $engineUpdateData = [];
    //         $engData = $ServicePackageEnginesModel->where('speng_spmc_id', $model_id)->where('speng_delete_flag', 0)->first();
    //         // detect engine change BEFORE we update DB
    //         if ($engData) {
    //             if ($engData['speng_eng_id'] != $engine_id) {
    //                 // Engine has changed, so log it
    //                 $oldEngId = $engData['speng_eng_id'];
    //                 $EngineModel = new EngineMasterModel();
    //                 $oldEngine = $EngineModel->where('eng_id', $oldEngId)->first();
    //                 $newEngine = $EngineModel->where('eng_id', $engine_id)->first();
    //                 $oldLabel = $oldEngine['eng_no'] ?? ('eng_id:' . $oldEngId);
    //                 $newLabel = $newEngine['eng_no'] ?? ('eng_id:' . $engine_id);
    //                 $updateLogs[] = "Engine changed: {$oldLabel} → {$newLabel} (speng_id: {$engData['speng_id']})";

    //                 // perform update as before
    //                 $speng_id = $engData['speng_id'];
    //                 $engineUpdateData = [
    //                     'speng_eng_id' => $engine_id,
    //                 ];

    //                 if (!empty($engineUpdateData)) {
    //                     $ServicePackageEnginesModel->where('speng_id', $speng_id)->set($engineUpdateData)->update();
    //                 }
    //             }
    //         } else {
    //             $engineInsertData = [
    //                 'speng_spmc_id' => $model_id,
    //                 'speng_eng_id' => $engine_id,
    //                 'speng_created_on' => date("Y-m-d H:i:s"),
    //                 'speng_created_by' => $userId,
    //             ];

    //             $ServicePackageEnginesModel->insert($engineInsertData);
    //             // Note: insertion is not logged as an "update" per your request
    //         }

    //         // ----------------------
    //         // Prepare PartsInsertData & PartsUpdateData and detect SP_Parts updates
    //         // ----------------------

    //         $PartsInsertData   = [];
    //         $PartsUpdateData   = [];

    //         // collect sp_spare ids to prefetch existing rows for comparison
    //         $spIdsToPrefetch = [];
    //         foreach (array_merge($partsData, $consumablesData) as $part) {
    //             if (!empty($part->sp_spare_id) && (int)$part->sp_spare_id != 0) {
    //                 $spIdsToPrefetch[] = (int)$part->sp_spare_id;
    //             }
    //         }
    //         $spIdsToPrefetch = array_values(array_unique($spIdsToPrefetch));
    //         $existingSpMap = [];
    //         if (!empty($spIdsToPrefetch)) {
    //             $rows = $SP_Parts_Model->whereIn('sp_spare_id', $spIdsToPrefetch)->findAll();
    //             foreach ($rows as $r) $existingSpMap[(int)$r['sp_spare_id']] = $r;
    //         }

    //         // merge both arrays and process in one pass
    //         foreach (array_merge($partsData, $consumablesData) as $part) {
    //             if ($part->id != 0) {
    //                 // shared columns + new category field
    //                 $commonData = [
    //                     'sp_spare_spmc_id'      => $model_id,
    //                     'sp_spare_pm_id'        => $part->id,
    //                     'sp_spare_qty'          => $part->qty,
    //                     'sp_spare_labour_unit'  => $part->sp_spare_labour_unit,
    //                     'sp_spare_category'        => $part->sp_pm_category,
    //                     'sp_spare_applicable'        => $part->applicable,
    //                     'sp_spare_group_seq'        => $part->spim_group_seq,
    //                     'sp_spare_updated_on'   => $now,
    //                     'sp_spare_updated_by'   => $userId,
    //                 ];

    //                 if ((int)$part->sp_spare_id == 0) {
    //                     // new row
    //                     $PartsInsertData[] = array_merge($commonData, [
    //                         'sp_spare_created_on'  => $now,
    //                         'sp_spare_created_by'  => $userId,
    //                     ]);
    //                 } else {
    //                     // detect changes for update rows BEFORE update
    //                     $sid = (int)$part->sp_spare_id;
    //                     if (isset($existingSpMap[$sid])) {
    //                         $existingSp = $existingSpMap[$sid];
    //                         $changed = [];

    //                         $oldQty = isset($existingSp['sp_spare_qty']) ? (float)$existingSp['sp_spare_qty'] : null;
    //                         $newQty = isset($part->qty) ? (float)$part->qty : null;
    //                         if ($oldQty !== $newQty) $changed[] = "qty: " . ($oldQty ?? 'NULL') . " → " . ($newQty ?? 'NULL');

    //                         $oldLabUnit = $existingSp['sp_spare_labour_unit'] ?? null;
    //                         $newLabUnit = $part->sp_spare_labour_unit ?? null;
    //                         if ((string)$oldLabUnit !== (string)$newLabUnit) $changed[] = "labour_unit: " . ($oldLabUnit ?? 'NULL') . " → " . ($newLabUnit ?? 'NULL');

    //                         $oldCat = $existingSp['sp_spare_category'] ?? null;
    //                         $newCat = $part->sp_pm_category ?? null;
    //                         if ((string)$oldCat !== (string)$newCat) $changed[] = "category: " . ($oldCat ?? 'NULL') . " → " . ($newCat ?? 'NULL');

    //                         $oldApp = ($existingSp['sp_spare_applicable'] ?? 0);
    //                         $newApp = ($part->applicable ?? 0);
    //                         if ($oldApp !== $newApp) {
    //                             if ($oldApp == 1 && $newApp == 0) {
    //                                 $changed[] = "removed from package";
    //                             } elseif ($newApp == 1 && $oldApp == 0) {
    //                                 $changed[] = "added to package";
    //                             }
    //                         }
    //                         $oldGroup = $existingSp['sp_spare_group_seq'] ?? null;
    //                         $newGroup = $part->spim_group_seq ?? null;
    //                         if ((string)$oldGroup !== (string)$newGroup) $changed[] = "group_seq: " . ($oldGroup ?? 'NULL') . " → " . ($newGroup ?? 'NULL');


    //                         $newDel = (int)($part->sp_spare_applicable ?? 0);

    //                         if (!empty($changed)) {
    //                             $partLabel = $part->DESCRIPTION ?? $part->PART_NO ?? "sp_spare_id:{$sid}";
    //                             if ($newDel == 0) {
    //                                 $updateLogs[] = "Service package part deleted: {$partLabel}";
    //                             } else {
    //                                 $updateLogs[] = "Service package part updated: {$partLabel} (sp_spare_id: {$sid}) — " . implode(', ', $changed);
    //                             }
    //                         }
    //                     }

    //                     // update existing row
    //                     $PartsUpdateData[] = array_merge($commonData, [
    //                         'sp_spare_id'          => $part->sp_spare_id,
    //                         'sp_spare_delete_flag' => $part->sp_spare_delete_flag,
    //                     ]);
    //                 }
    //             }
    //         }

    //         // Insert new records
    //         if (!empty($PartsInsertData)) {
    //             $servicePackagePartsInserted = $SP_Parts_Model->insertBatch($PartsInsertData);
    //         }

    //         // Update existing records
    //         if (!empty($PartsUpdateData)) {
    //             $servicePackagePartsUpdated = $SP_Parts_Model->updateBatch($PartsUpdateData, 'sp_spare_id');
    //         }

    //         // ----------------------
    //         // Labours: prefetch existing labours and detect updates
    //         // ----------------------
    //         $LaboursinsertData       = [];
    //         $LaboursUpdateData       = [];

    //         $labourIdsToPrefetch = [];
    //         if (!empty($laboursData)) {
    //             foreach ($laboursData as $labour) {
    //                 if (!empty($labour->sp_labour_id) && (int)$labour->sp_labour_id != 0) {
    //                     $labourIdsToPrefetch[] = (int)$labour->sp_labour_id;
    //                 }
    //             }
    //         }
    //         $labourIdsToPrefetch = array_values(array_unique($labourIdsToPrefetch));
    //         $existingLabMap = [];
    //         if (!empty($labourIdsToPrefetch)) {
    //             $rows = $SP_Labours_Model->whereIn('sp_labour_id', $labourIdsToPrefetch)->findAll();
    //             foreach ($rows as $r) $existingLabMap[(int)$r['sp_labour_id']] = $r;
    //         }

    //         if (!empty($laboursData)) {
    //             $now = date("Y-m-d H:i:s"); // set once for consistency
    //             foreach ($laboursData as $labour) {
    //                 $commonLabourData = [
    //                     'sp_labour_spmc_id'     => $model_id,
    //                     'sp_labour_lm_id'       => $labour->Name,
    //                     'sp_labour_unit'        => $labour->unit,
    //                     'sp_labour_updated_on'  => $now,
    //                     'sp_labour_updated_by'  => $userId,
    //                     'sp_labour_applicable'  => $labour->applicable,
    //                     'sp_labour_group_seq'        => $labour->spim_group_seq,
    //                 ];

    //                 if ($labour->sp_labour_id == 0 && $labour->applicable == '1') {
    //                     $LaboursinsertData[] = array_merge($commonLabourData, [
    //                         'sp_labour_created_on' => $now,
    //                         'sp_labour_created_by' => $userId,
    //                     ]);
    //                 } elseif ($labour->sp_labour_id != 0) {
    //                     // detect update by comparing $existingLabMap
    //                     $lid = (int)$labour->sp_labour_id;
    //                     if (isset($existingLabMap[$lid])) {
    //                         $existingL = $existingLabMap[$lid];
    //                         $changed = [];

    //                         $oldLm = $existingL['sp_labour_lm_id'] ?? null;
    //                         $newLm = $labour->Name ?? null;
    //                         if ((string)$oldLm !== (string)$newLm) $changed[] = "lm_id: " . ($oldLm ?? 'NULL') . " → " . ($newLm ?? 'NULL');

    //                         $oldUnit = $existingL['sp_labour_unit'] ?? null;
    //                         $newUnit = $labour->unit ?? null;
    //                         if ((string)$oldUnit !== (string)$newUnit) $changed[] = "unit: " . ($oldUnit ?? 'NULL') . " → " . ($newUnit ?? 'NULL');

    //                         $oldApp = (int)($existingL['sp_labour_applicable'] ?? 0);
    //                         $newApp = (int)($labour->applicable ?? 0);

    //                         if ($oldApp !== $newApp) {
    //                             if ($oldApp == 1 && $newApp == 0) {
    //                                 $changed[] = "removed from package";
    //                             } elseif ($oldApp == 0 && $newApp == 1) {
    //                                 $changed[] = "added to package";
    //                             }
    //                         }



    //                         $oldGroup = $existingL['sp_labour_group_seq'] ?? null;
    //                         $newGroup = $labour->spim_group_seq ?? null;
    //                         if ((string)$oldGroup !== (string)$newGroup) $changed[] = "group_seq: " . ($oldGroup ?? 'NULL') . " → " . ($newGroup ?? 'NULL');

    //                         $newDel = (int)($labour->sp_labour_applicable ?? 0);

    //                         if (!empty($changed)) {
    //                             if ($newDel == 1) {
    //                                 $updateLogs[] = "Labour deleted: sp_labour_id: {$labour->Description}";
    //                             } else {
    //                                 $labDesc = $labour->Description ?? ("sp_labour_id:" . $lid);
    //                                 $updateLogs[] = "Labour updated: {$labDesc} (sp_labour_id: {$labour->Description}) — " . implode(', ', $changed);
    //                             }
    //                         }
    //                     }

    //                     $LaboursUpdateData[] = array_merge($commonLabourData, [
    //                         'sp_labour_id'          => $labour->sp_labour_id,
    //                         'sp_labour_delete_flag' => $labour->sp_labour_delete_flag,
    //                     ]);
    //                 }
    //             }

    //             // Perform DB operations
    //             if (!empty($LaboursinsertData)) {
    //                 $SP_Labours_Model->insertBatch($LaboursinsertData);
    //             }
    //             if (!empty($LaboursUpdateData)) {
    //                 $SP_Labours_Model->updateBatch($LaboursUpdateData, 'sp_labour_id');
    //             }
    //         }

    //         // pull it once
    //         $vin = $this->request->getVar('vin_no');

    //         // start with all the always-updated fields
    //         $updateModelData = [
    //             'spmc_status_flag'  => $this->request->getVar('spmc_status_flag'),
    //             'spmc_draft_flag'   => $this->request->getVar('draft_flag'),
    //             'spmc_session_flag' => 0,
    //             'spmc_updated_by'   => $userId,
    //             'spmc_updated_on'   => date('Y-m-d H:i:s'),
    //         ];

    //         // only include VIN if it was provided
    //         if (! empty($vin)) {
    //             $updateModelData['spmc_vin_no'] = $vin;
    //         }

    //         $ServicePackageModelCodeModel->where('spmc_id', $model_id)->set($updateModelData)->update();

    //         // Load brands into map
    //         $brandModel = new BrandModel();
    //         $brands     = $brandModel->findAll();
    //         $brandMap   = array_column($brands, 'brand_name', 'brand_id');

    //         $itemDescriptions = [];

    //         // 🔹 Parts
    //         if (!empty($partsData)) {
    //             $partItems = [];
    //             foreach ($partsData as $part) {
    //                 if (!empty($part->applicable) && $part->applicable == '1') {
    //                     $brandId = $part->Brand ?? null;
    //                     $brand   = $brandMap[$brandId] ?? 'N/A';
    //                     $price   = $part->PRICE ?? 'N/A';
    //                     $qty     = $part->qty ?? 'N/A';
    //                     $partItems[] = "{$part->DESCRIPTION} (Part No: {$part->PART_NO}, Brand: {$brand}, Qty: {$qty}, Unit Price: AED {$price})";
    //                 }
    //             }
    //             if (!empty($partItems)) {
    //                 $itemDescriptions[] = "Parts: " . implode("; ", $partItems);
    //             }
    //         }

    //         // 🔹 Consumables
    //         if (!empty($consumablesData)) {
    //             $consumableItems = [];
    //             foreach ($consumablesData as $part) {
    //                 if (!empty($part->applicable) && $part->applicable == '1') {
    //                     $brandId = $part->Brand ?? null;
    //                     $brand   = $brandMap[$brandId] ?? 'N/A';
    //                     $price   = $part->PRICE ?? 'N/A';
    //                     $qty     = $part->qty ?? 'N/A';
    //                     $consumableItems[] = "{$part->DESCRIPTION} (Part No: {$part->PART_NO}, Brand: {$brand}, Qty: {$qty}, Unit Price: AED {$price})";
    //                 }
    //             }
    //             if (!empty($consumableItems)) {
    //                 $itemDescriptions[] = "Consumables: " . implode("; ", $consumableItems);
    //             }
    //         }

    //         // 🔹 Labour
    //         if (!empty($laboursData)) {
    //             $labourItems = [];
    //             foreach ($laboursData as $labour) {
    //                 if (!empty($labour->applicable) && $labour->applicable == '1') {
    //                     $price = $labour->PRICE ?? 'N/A';
    //                     $labourItems[] = "{$labour->Description} (Unit Price: AED {$price})";
    //                 }
    //             }
    //             if (!empty($labourItems)) {
    //                 $itemDescriptions[] = "Labour: " . implode("; ", $labourItems);
    //             }
    //         }

    //         $itemList = !empty($itemDescriptions) ? implode("; ", $itemDescriptions) : 'No items entered';

    //         // 🔹 Engine lookup
    //         $engId     = $this->request->getVar('engine_id'); // ✅ consistent name
    //         $engineNo  = 'N/A';
    //         if (!empty($engId)) {
    //             $engineModel = new EngineMasterModel();
    //             $engine      = $engineModel->where('eng_id', $engId)->first();
    //             if ($engine) {
    //                 $engineNo = $engine['eng_no'];
    //             }
    //         }

    //         // ----------------------
    //         // ONLY-ON-UPDATES LOG: if $updateLogs not empty, insert a single sp_log row
    //         // ----------------------
    //         if (!empty($updateLogs)) {
    //             $now = date('Y-m-d H:i:s');
    //             $summaryHeader = "Service package for Model Code {$modelCode}, Engine No {$engineNo} has been {$action} by user {$userId} on {$now}. Updated items:";
    //             // number the lines for readability
    //             $bodyLines = [];
    //             $i = 1;
    //             foreach ($updateLogs as $line) {
    //                 $bodyLines[] = "  {$i}. {$line}";
    //                 $i++;
    //             }
    //             $logNote = $summaryHeader . "\n" . implode("\n", $bodyLines);

    //             $logData = [
    //                 'sp_log_spmc_id'    => $model_id,
    //                 'sp_log_notes'      => $logNote,
    //                 'sp_log_created_by' => $userId,
    //                 'sp_log_created_on' => $now,
    //                 'sp_log_delete_flag' => 0,
    //             ];

    //             try {
    //                 $SpLogTable->insert($logData);
    //             } catch (\Exception $e) {
    //                 error_log('sp_log insert failed: ' . $e->getMessage());
    //             }
    //         }

    //         $this->db->transComplete();

    //         if ($this->db->transStatus() === false) {
    //             $this->db->transRollback();
    //             $response['ret_data'] = "fail";
    //             return $this->respond($response, 200);
    //         } else {
    //             $this->db->transCommit();
    //             $response = [
    //                 'ret_data' => 'success',
    //             ];
    //             return $this->respond($response, 200);
    //         }
    //     }
    // }
    public function checkDifference($existingSpares, $allSparesData) {}

    public function saveServicePackageLabours()
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
            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $ServicePackageEnginesModel   = new ServicePackageEnginesModel();
            $SP_Labours_Model             = new ServicePackageLabourModel();
            $SP_Parts_Model               = new ServicePackageSpareModel();
            $SparePartsMasterModel        = new SparePartsMaster();
            $SpLogTable                   = new ServicePackageLogModel();

            $action    = ($this->request->getVar('draft_flag') == 1) ? "Drafted" : "Saved";
            $modelCode = $this->request->getVar('model_code');

            $modelData = $ServicePackageModelCodeModel
                ->select('spmc_id,spmc_session_flag,spmc_draft_flag')
                ->where('spmc_value', $modelCode)
                ->where('spmc_delete_flag', 0)
                ->first();

            $model_id        = $modelData['spmc_id'];
            $userId          = $this->request->getVar('user_id');
            $laboursData     = $this->request->getVar('Labour');
            $partsData       = $this->request->getVar('parts');
            $engine_id       = $this->request->getVar('engine_id');
            $consumablesData = $this->request->getVar('consumables');

            $this->db->transStart();
            $now = date('Y-m-d H:i:s');

            $partsData       = is_array($partsData) ? $partsData : [];
            $consumablesData = is_array($consumablesData) ? $consumablesData : [];

            // ---------- PRELOAD EXISTING SPARES FOR MODEL (place BEFORE parts master loop) ----------
            $existingSpares = [];
            $existingSparesByPmId = [];
            // $spareRows = $SP_Parts_Model->where('sp_spare_spmc_id', $model_id)->findAll();
            $spareRows = $SP_Parts_Model
                ->select('sp_spares.*, parts_master.*')
                ->join('parts_master', 'parts_master.pm_id = sp_spares.sp_spare_pm_id', 'left')
                ->where('sp_spare_spmc_id', $model_id)
                ->findAll();

            $allSparesData = array_merge($partsData, $consumablesData);
            //             echo("this is existing");
            //             print_r($spareRows);
            // echo("this is new");
            // print_r($allSparesData);

            $resultset = $this->checkDifference($spareRows, $allSparesData);

            foreach ($spareRows as $r) {
                $existingSpares[(int)$r['sp_spare_id']] = $r;
                if (!empty($r['sp_spare_pm_id'])) {
                    $existingSparesByPmId[(int)$r['sp_spare_pm_id']] = $r;
                }
            }


            // ======================= PARTS MASTER INSERT/UPDATE =======================
            foreach (array_merge($partsData, $consumablesData) as $part) {
                $part->old_brand = isset($part->old_brand) ? $part->old_brand : ($part->Brand ?? null);
                $part->old_unit = $part->unit_type;

                $common = [
                    'pm_code'       => $part->PART_NO,
                    'pm_unit_type'  => $part->unit_type,
                    'pm_brand'      => $part->Brand,
                    'pm_updated_on' => $now,
                    'pm_updated_by' => $userId,
                    'pm_sp_pm_id'   => $part->sp_pm_id,
                    // $part->old_brand    = $part->Brand,
                ];

                if (empty($part->id) || $part->id == 0) {
                    // New master entry
                    $insertData = $common + [
                        'pm_price'      => $part->PRICE,
                        'pm_created_on' => $now,
                        'pm_created_by' => $userId,
                    ];
                    $SparePartsMasterModel->insert($insertData);
                    $part->id = $SparePartsMasterModel->insertID();
                } else {
                    // === Find the true previous master record for THIS service-package-spare ===
                    $pmBefore = null;

                    // Prefer spare-level mapping if incoming payload contains sp_spare_id
                    if (!empty($part->sp_spare_id) && isset($existingSpares[(int)$part->sp_spare_id])) {
                        $spareExisting = $existingSpares[(int)$part->sp_spare_id];
                        if (!empty($spareExisting['sp_spare_pm_id'])) {
                            $pmBefore = $SparePartsMasterModel->where('pm_id', $spareExisting['sp_spare_pm_id'])->first();
                        }
                    }

                    // fallback: try by pm id (the incoming $part->id is master pm id)
                    if ($pmBefore === null) {
                        $pmBefore = $SparePartsMasterModel->where('pm_id', $part->id)->first();
                    }
                    // store old values on the $part object for later (used in PartsUpdateData)
                    $part->old_pm_code  = $pmBefore['pm_code']  ?? null;
                    $part->old_pm_price = $pmBefore['pm_price'] ?? null;
                    $part->old_brand    = $pmBefore['pm_brand'] ?? null;
                    $part->old_unit_type    = $pmBefore['pm_unit_type'] ?? null;
                    // ===================================================

                    if (!empty($part->old_price) && $part->old_price != 0) {
                        $updateData = [
                            'pm_price'     => $part->old_price,
                            'pm_new_price' => $part->PRICE,
                        ] + $common;
                    } else {
                        $updateData = $common + [
                            'pm_price' => $part->PRICE,
                        ];
                    }

                    $SparePartsMasterModel->where('pm_id', $part->id)->set($updateData)->update();
                }
            }


            // ======================= ENGINE ASSIGNMENT =======================
            $engData = $ServicePackageEnginesModel
                ->where('speng_spmc_id', $model_id)
                ->where('speng_delete_flag', 0)
                ->first();

            $engineModel = new EngineMasterModel();
            $engine      = $engineModel->where('eng_id', $engine_id)->first();
            if ($engine) $engineNo = $engine['eng_no'];

            if ($engData) {
                if ($engData['speng_eng_id'] != $engine_id) {
                    $speng_id = $engData['speng_id'];
                    $ServicePackageEnginesModel
                        ->where('speng_id', $speng_id)
                        ->set(['speng_eng_id' => $engine_id])
                        ->update();

                    $note = "{$modelCode} Engine updated to ID {$engineNo}";
                    $this->addSpLog($SpLogTable, $model_id, $userId, $note);
                }
            } else {
                $engineInsertData = [
                    'speng_spmc_id'   => $model_id,
                    'speng_eng_id'    => $engine_id,
                    'speng_created_on' => $now,
                    'speng_created_by' => $userId,
                ];
                $ServicePackageEnginesModel->insert($engineInsertData);

                $detail = "Engine assigned {$engineNo}";
                $this->addSpLog($SpLogTable, $model_id, $userId, $detail, $action, $modelCode);
            }

            // ======================= PARTS INSERT/UPDATE =======================
            $PartsInsertData = [];
            $PartsUpdateData = [];

            foreach (array_merge($partsData, $consumablesData) as $part) {

                $commonData = [
                    'sp_spare_spmc_id'     => $model_id,
                    'sp_spare_pm_id'       => $part->id,
                    'sp_spare_qty'         => $part->qty,
                    'sp_spare_labour_unit' => $part->sp_spare_labour_unit,
                    'sp_spare_category'    => $part->sp_pm_category,
                    'sp_spare_applicable'  => $part->applicable,
                    'sp_spare_group_seq'   => $part->spim_group_seq,
                    'sp_spare_updated_on'  => $now,
                    'sp_spare_updated_by'  => $userId,
                ];

                if ((int)$part->sp_spare_id === 0 && (int)$part->applicable === 1) {
                    // ✅ NEW PART → go to INSERT
                    $PartsInsertData[] = array_merge($commonData, [
                        'sp_spare_created_on' => $now,
                        'sp_spare_created_by' => $userId,
                    ]);
                } else {
                    // ✅ EXISTING PART → go to UPDATE
                    $PartsUpdateData[] = array_merge($commonData, [
                        'sp_spare_id'           => $part->sp_spare_id,
                        'sp_spare_applicable'   => $part->applicable,
                        'sp_spare_delete_flag'  => $part->sp_spare_delete_flag,
                        'PART_NO'               => $part->PART_NO,
                        'PRICE'                 => $part->PRICE,
                        'OLD_PM_CODE'           => $part->old_pm_code  ?? null,
                        'OLD_PM_PRICE'          => $part->old_pm_price ?? null,
                        'pm_brand'              => $part->Brand,
                        'incoming_pm_brand'     => $part->Brand ?? null,
                        'old_unit'              => $part->unit_type ?? null,
                    ]);
                }
            }


            // ---- Insert ----
            if (!empty($PartsInsertData)) {
                $SP_Parts_Model->insertBatch($PartsInsertData);

                $notes = [];
                foreach ($PartsInsertData as $insert) {
                    $matched = null;
                    foreach (array_merge($partsData, $consumablesData) as $part) {
                        if ((int)$part->id === (int)$insert['sp_spare_pm_id']) {
                            $matched = $part;
                            break;
                        }
                    }

                    if ($matched) {
                        $desc     = $matched->DESCRIPTION ?? 'N/A';
                        $partCode = $matched->PART_NO ?? 'N/A';
                        $qty      = $insert['sp_spare_qty'] ?? 'N/A';
                        $price    = $matched->PRICE ?? 'N/A';

                        $notes[] = "Inserted Part {$desc} (Part Code: {$partCode}, Qty: {$qty}, Unit Price: AED {$price})";
                    }
                }

                if (!empty($notes)) {
                    $detail = "Inserted Parts: " . implode("; ", $notes);
                    $this->addSpLog($SpLogTable, $model_id, $userId, $detail, $action, $modelCode);
                }
            }


            // ---- Update ----
            if (!empty($PartsUpdateData)) {

                $oldSpares = [];
                foreach ($spareRows as $row) {
                    $oldSpares[$row['sp_spare_id']] = $row;
                }

                $allLogs = []; // collect all logs

                foreach ($PartsUpdateData as $newData) {
                    $spareId = $newData['sp_spare_id'] ?? null;

                    if ($spareId && isset($oldSpares[$spareId])) {
                        $existingData = $oldSpares[$spareId];
                        // print_r($newData);

                        // Call change log ONCE per spare
                        $result = $this->prepareSpareChangeLog($existingData, $newData);

                        if (!empty($result['note'])) {
                            $allLogs[] = $result['note'];
                        }

                        // echo "=============================\n\n";
                    } else {
                        // echo "\n❌ No match found for newData spareId = {$spareId}\n";
                        // print_r($newData);
                    }
                }

                // Save all logs together
                if (!empty($allLogs)) {
                    $detail = "Updated Parts: " . implode("; ", $allLogs);
                    $this->addSpLog($SpLogTable, $model_id, $userId, $detail, $action, $modelCode);
                }


                $SP_Parts_Model->updateBatch($PartsUpdateData, 'sp_spare_id');

                //write lognote here
                // echo '-------------------------';
                $notes = [];
                foreach ($PartsUpdateData as $update) {
                    $matched = null;
                    foreach (array_merge($partsData, $consumablesData) as $part) {
                        if ((int)$part->sp_spare_id === (int)$update['sp_spare_id']) {
                            $matched = $part;
                            break;
                        }
                    }
                }

                if (!empty($notes)) {
                    $detail = "Updated Parts: " . implode("; ", $notes);
                    $this->addSpLog($SpLogTable, $model_id, $userId, $detail, $action, $modelCode);
                }
            }



            // ======================= LABOURS INSERT/UPDATE =======================
            $LaboursinsertData = [];
            $LaboursUpdateData = [];

            if (!empty($laboursData)) {
                foreach ($laboursData as $labour) {
                    $commonLabourData = [
                        'sp_labour_spmc_id'    => $model_id,
                        'sp_labour_lm_id'      => $labour->Name,
                        'sp_labour_unit'       => $labour->unit,
                        'sp_labour_updated_on' => $now,
                        'sp_labour_updated_by' => $userId,
                        'sp_labour_applicable' => $labour->applicable,
                        'sp_labour_group_seq'  => $labour->spim_group_seq,
                    ];

                    if ($labour->sp_labour_id == 0 && $labour->applicable == '1') {
                        $LaboursinsertData[] = array_merge($commonLabourData, [
                            'sp_labour_created_on' => $now,
                            'sp_labour_created_by' => $userId,
                        ]);
                    } elseif ($labour->sp_labour_id != 0) {
                        $LaboursUpdateData[] = array_merge($commonLabourData, [
                            'sp_labour_id'          => $labour->sp_labour_id,
                            'sp_labour_delete_flag' => $labour->sp_labour_delete_flag,
                        ]);
                    }
                }

                // ---- Insert ----
                if (!empty($LaboursinsertData)) {
                    $SP_Labours_Model->insertBatch($LaboursinsertData);

                    $notes = [];
                    foreach ($LaboursinsertData as $insert) {
                        $matched = null;
                        foreach ($laboursData as $labour) {
                            if ((int)$labour->Name === (int)$insert['sp_labour_lm_id']) {
                                $matched = $labour;
                                break;
                            }
                        }

                        if ($matched) {
                            $desc  = $matched->Description ?? 'N/A';
                            $unit  = $insert['sp_labour_unit'] ?? 'N/A';
                            $notes[] = "{$desc} (Labour Unit: {$unit})";
                        } else {
                            // fallback log (prevents silent skip)
                            $notes[] = "Inserted Labour ID {$insert['sp_labour_lm_id']} (Unit: {$insert['sp_labour_unit']})";
                        }
                    }

                    if (!empty($notes)) {
                        $detail = "Inserted Labours: " . implode("; ", $notes);
                        $this->addSpLog($SpLogTable, $model_id, $userId, $detail, $action, $modelCode);
                    }
                }


                // ---- Update ----
                if (!empty($LaboursUpdateData)) {
                    $SP_Labours_Model->updateBatch($LaboursUpdateData, 'sp_labour_id');

                    $notes = [];
                    foreach ($LaboursUpdateData as $update) {
                        $matched = null;
                        foreach ($laboursData as $labour) {
                            if ((int)$labour->sp_labour_id === (int)$update['sp_labour_id']) {
                                $matched = $labour;
                                break;
                            }
                        }

                        if ($matched) {
                            $desc = $matched->Description ?? 'N/A';
                            $changeNote = $this->checksDataLabours($update['sp_labour_id'], $desc, $update);

                            // ✅ Handle removed case
                            if (
                                isset($update['sp_labour_applicable'])
                                && $update['sp_labour_applicable'] == 0
                                && $matched->applicable == 0
                            ) {
                                $notes[] = "Removed Labour {$desc}";
                            }

                            if ($changeNote) {
                                $notes[] = $changeNote;
                            }
                        }
                    }

                    if (!empty($notes)) {
                        $detail = "Updated Labours: " . implode("; ", $notes);
                        $this->addSpLog($SpLogTable, $model_id, $userId, $detail, $action, $modelCode);
                    }
                }
            }

            // ======================= MODEL UPDATE =======================
            $vin = $this->request->getVar('vin_no');
            $updateModelData = [
                'spmc_status_flag'  => $this->request->getVar('spmc_status_flag'),
                'spmc_draft_flag'   => $this->request->getVar('draft_flag'),
                'spmc_session_flag' => 0,
                'spmc_updated_by'   => $userId,
                'spmc_updated_on'   => $now,
            ];
            if (!empty($vin)) $updateModelData['spmc_vin_no'] = $vin;

            $ServicePackageModelCodeModel->where('spmc_id', $model_id)->set($updateModelData)->update();

            // ======================= TRANSACTION END =======================
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return $this->respond(['ret_data' => 'fail'], 200);
            } else {
                $this->db->transCommit();
                return $this->respond(['ret_data' => 'success'], 200);
            }
        }
    }

    /**
     * Prepare change-note and structured diffs for a spare row by comparing DB vs incoming data.
     *
     * @param int   $spareId  sp_spare_id to compare
     * @param array $newData  incoming array (from PartsUpdateData)
     * @return array          ['note' => string|null, 'changes' => array]
     */
    public function prepareSpareChangeLog($existingData, $newData)
    {
        $SP_Parts_Model   = new ServicePackageSpareModel();
        $PartsMasterModel = new SparePartsMaster();
        $SpareMasterModel = new ServicePackagePartsModel();

        $notes = [];

        $expartsData = $PartsMasterModel
            ->where('pm_id', $existingData['sp_spare_pm_id'])
            ->where('pm_delete_flag', 0)
            ->first();

        // print_r($existingData);
        // print_r($newData);

        if ($existingData['sp_spare_pm_id'] != $newData['sp_spare_pm_id']) {

            $spareExdata = $SpareMasterModel->where('sp_pm_id', $expartsData['pm_sp_pm_id'])->where('sp_pm_delete_flag', 0)->first();

            $oldCode = $expartsData['pm_code'] ?? 'unknown';
            $notes[] = "'{$spareExdata['sp_pm_name']}' partcode changed from '{$oldCode}' to '{$newData['PART_NO']}'";
        }
        if ((int)$existingData['pm_brand'] !== (int)$newData['pm_brand']) {
            $spareExdata = $SpareMasterModel
                ->where('sp_pm_id', $expartsData['pm_sp_pm_id'] ?? 0)
                ->where('sp_pm_delete_flag', 0)
                ->first();

            $BrandModel = new BrandModel();
            $brandOld = $BrandModel->where('brand_id', $existingData['pm_brand'])->first();
            $brandNew = $BrandModel->where('brand_id', $newData['pm_brand'])->first();

            $brandOldName = $brandOld['brand_name'] ?? 'unknown';
            $brandNewName = $brandNew['brand_name'] ?? 'unknown';

            if ($brandOldName !== $brandNewName) {   // ✅ double check by name
                $notes[] = "'{$spareExdata['sp_pm_name']}' Brand changed from '{$brandOldName}' to '{$brandNewName}'";
            }
        }


        if ($existingData['sp_spare_applicable'] != $newData['sp_spare_applicable']) {

            $spareExdata = $SpareMasterModel->where('sp_pm_id', $expartsData['pm_sp_pm_id'])->where('sp_pm_delete_flag', 0)->first();

            if ($existingData['sp_spare_applicable'] == 1 && $newData['sp_spare_applicable'] == 0) {
                $notes[] = "'{$spareExdata['sp_pm_name']}' is removed. '";
            }
        }

        if ($existingData['sp_spare_qty'] != $newData['sp_spare_qty']) {

            $spareExdata = $SpareMasterModel->where('sp_pm_id', $expartsData['pm_sp_pm_id'])->where('sp_pm_delete_flag', 0)->first();
            $notes[] = "'{$spareExdata['sp_pm_name']}' Qty is changed from  '{$existingData['sp_spare_qty']} to  '{$newData['sp_spare_qty']}' '";
        }

        if ($existingData['pm_unit_type'] != $newData['old_unit']) {

            $spareExdata = $SpareMasterModel->where('sp_pm_id', $expartsData['pm_sp_pm_id'])->where('sp_pm_delete_flag', 0)->first();
            if ($newData['old_unit'] == 1) {
                $notes[] = "'{$spareExdata['sp_pm_name']}' unit is changed from LITRE to UNIT ";
            } else {
                $notes[] = "'{$spareExdata['sp_pm_name']}' unit is changed from UNIT to LITRE ";
            }
        }

        $note = $notes ? implode("; ", $notes) : '';

        return [
            'note' => $note,
        ];
    }



    private function checksDataParts($spareId, $desc, $newData)
    {
        $SP_Parts_Model = new ServicePackageSpareModel();
        $SparePartsMasterModel = new SparePartsMaster();
        $brandModel = new BrandModel();
        // echo ("tgis is old");
        // print_r($spareId);
        // echo ("this is new");
        // print_r($newData);
        // print_r($desc);
        // // die;

        $note = [];
        if ($spareId['sp_spare_pm_id'] != $newData['sp_spare_pm_id']) {
            $note[] = "{$desc} partcode changed from {$spareId['pm_code']} to {$newData['PART_NO']}.";
        }
        if ($spareId['pm_brand'] != $newData['brand']) {
            $brandModel = new BrandModel();
            $oldBrand = $brandModel->where('brand_id', $spareId['pm_brand'])->first();
            $newBrand = $brandModel->where('brand_id', $newData['brand'])->first();
            $oldBrandName = $oldBrand['brand_name'] ?? 'N/A';
            $newBrandName = $newBrand['brand_name'] ?? 'N/A';

            $note[] = "{$desc} brand changed from {$oldBrandName} to {$newBrandName}";
        }

        if ($spareId['pm_unit_type'] != $newData['unit']) {
            if ($spareId['pm_unit_type'] == 1) {
                $note[] = "{$desc} unit changed from UNIT to LITRE.";
            } else {
                $note[] = "{$desc} unit changed from LITRE to UNIT.";
            }
        }
        if ($spareId['sp_spare_qty'] != $newData['sp_spare_qty']) {
            $note[] = "{$desc} Quantity changed from {$spareId['sp_spare_qty']} to {$newData['sp_spare_qty']}.";
        }
        if ($spareId['pm_price'] != $newData['PRICE']) {
            $note[] = "{$desc} Quantity changed from {$spareId['pm_price']} to {$newData['PRICE']}.";
        }
        if ($spareId['sp_spare_applicable'] != $newData['sp_spare_applicable']) {
            if ($spareId['sp_spare_applicable'] == 1) {
                $note[] = "{$desc} is Removed.";
            } else {
                $note[] = "{$desc} is Added.";
            }
        }


        if (!empty($note)) {
            return " — " . implode(" — ", $note);
        }

        return '';
    }




    private function checksDataLabours($labourId, $desc, $newData)
    {
        $SP_Labours_Model = new ServicePackageLabourModel();
        $existing = $SP_Labours_Model->where('sp_labour_id', $labourId)->first();

        if (!$existing) {
            return null;
        }

        $changes = [];

        $fields = [
            'sp_labour_unit'      => 'Unit',
            'sp_labour_applicable' => 'Applicable',
            'sp_labour_group_seq' => 'Group Seq'
        ];

        foreach ($fields as $field => $label) {
            $oldVal = $existing[$field] ?? null;
            $newVal = $newData[$field] ?? null;

            if ($oldVal != $newVal) {
                if ($field === 'sp_labour_applicable') {
                    if ($oldVal == 1 && $newVal == 0) {
                        $changes[] = "Marked as Removed";
                    } elseif ($oldVal == 0 && $newVal == 1) {
                        $changes[] = "Marked as Added";
                    } else {
                        $changes[] = "{$label}: {$oldVal} → {$newVal}";
                    }
                } else {
                    $changes[] = "{$label}: {$oldVal} → {$newVal}";
                }
            }
        }

        if (!empty($changes)) {
            return "Updated Labour {$desc} (" . implode(", ", $changes) . ")";
        }

        return null;
    }

    private function addSpLog($SpLogTable, $modelId, $userId, $detailNote, $action = null, $modelCode = null)
    {
        // Resolve user first name (fallback to us_name or 'Unknown')
        $UserModel = new UserModel();
        $u = $UserModel->where('us_id', $userId)->first();
        $actorName = trim($u['us_firstname'] ?? $u['us_name'] ?? 'Unknown');

        // Normalize action string (Drafted / Saved)
        $actionText = null;
        if (!empty($action)) {
            $actionText = trim($action);
        }

        // Build prefix: "Arun (Saved) Model Code ABC123 — "
        $prefixParts = [];
        $prefixParts[] = $actorName;
        if ($actionText) $prefixParts[] = "({$actionText})";
        if (!empty($modelCode)) $prefixParts[] = "Model Code {$modelCode}";

        $prefix = implode(' ', $prefixParts);
        if (!empty($prefix)) $prefix .= ' — ';

        $finalNote = $prefix . $detailNote;

        try {
            $SpLogTable->insert([
                'sp_log_spmc_id'    => $modelId,
                'sp_log_notes'      => $finalNote,
                'sp_log_created_by' => $userId,
                'sp_log_created_on' => date("Y-m-d H:i:s"),
                'sp_log_delete_flag' => 0,
            ]);
        } catch (\Exception $e) {
            error_log("addSpLog insert failed: " . $e->getMessage());
        }
    }





    // // Load brands into map
    // $brandModel = new BrandModel();
    // $brands     = $brandModel->findAll();
    // $brandMap   = array_column($brands, 'brand_name', 'brand_id');

    // $itemDescriptions = [];

    // // 🔹 Parts
    // if (!empty($partsData)) {
    //     $partItems = [];
    //     foreach ($partsData as $part) {
    //         if (!empty($part->applicable) && $part->applicable == '1') {
    //             $brandId = $part->Brand ?? null;
    //             $brand   = $brandMap[$brandId] ?? 'N/A';
    //             $price   = $part->PRICE ?? 'N/A';
    //             $qty     = $part->qty ?? 'N/A';
    //             $partItems[] = "{$part->DESCRIPTION} (Part No: {$part->PART_NO}, Brand: {$brand}, Qty: {$qty}, Unit Price: AED {$price})";
    //         }
    //     }
    //     if (!empty($partItems)) {
    //         $itemDescriptions[] = "Parts: " . implode("; ", $partItems);
    //     }
    // }

    // // 🔹 Consumables
    // if (!empty($consumablesData)) {
    //     $consumableItems = [];
    //     foreach ($consumablesData as $part) {
    //         if (!empty($part->applicable) && $part->applicable == '1') {
    //             $brandId = $part->Brand ?? null;
    //             $brand   = $brandMap[$brandId] ?? 'N/A';
    //             $price   = $part->PRICE ?? 'N/A';
    //             $qty     = $part->qty ?? 'N/A';
    //             $consumableItems[] = "{$part->DESCRIPTION} (Part No: {$part->PART_NO}, Brand: {$brand}, Qty: {$qty}, Unit Price: AED {$price})";
    //         }
    //     }
    //     if (!empty($consumableItems)) {
    //         $itemDescriptions[] = "Consumables: " . implode("; ", $consumableItems);
    //     }
    // }

    // // 🔹 Labour
    // if (!empty($laboursData)) {
    //     $labourItems = [];
    //     foreach ($laboursData as $labour) {
    //         if (!empty($labour->applicable) && $labour->applicable == '1') {
    //             $price = $labour->PRICE ?? 'N/A';
    //             $labourItems[] = "{$labour->Description} (Unit Price: AED {$price})";
    //         }
    //     }
    //     if (!empty($labourItems)) {
    //         $itemDescriptions[] = "Labour: " . implode("; ", $labourItems);
    //     }
    // }

    // $itemList = !empty($itemDescriptions) ? implode("; ", $itemDescriptions) : 'No items entered';

    // // 🔹 Engine lookup
    // $engId     = $this->request->getVar('engine_id');
    // $engineNo  = 'N/A';
    // if (!empty($engId)) {
    //     $engineModel = new EngineMasterModel();
    //     $engine      = $engineModel->where('eng_id', $engId)->first();
    //     if ($engine) {
    //         $engineNo = $engine['eng_no'];
    //     }
    // }

    // // 🔹 Build log
    // $logNote = "Service package for Model Code {$modelCode}, Engine No {$engineNo} has been {$action}"
    //     . " with the following entries: {$itemList}.";

    // $logData = [
    //     'sp_log_spmc_id'    => $model_id,
    //     'sp_log_notes'      => $logNote,
    //     'sp_log_created_by' => $userId,
    //     'sp_log_created_on' => date("Y-m-d H:i:s"),
    // ];

    // $SpLogTable->insert($logData);


    public function getESLByModelCode()
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $ServicePackageEnginesModel = new ServicePackageEnginesModel();
            $SP_Parts_Model = new ServicePackageSpareModel();
            $SP_Labours_Model = new ServicePackageLabourModel();
            $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();



            $modelCode = $this->request->getVar('model_code');
            $modelData = $ServicePackageModelCodeModel->select('spmc_id')->where('spmc_value', $modelCode)->where('spmc_delete_flag', 0)->first();
            $model_id = $modelData['spmc_id'];

            $modelLabourData = $ServicePackageModelCodeLabourModel
                ->where('model_code', $modelCode)
                ->where('spmcl_delete_flag', 0)
                ->first();

            $labourFactor = 0;

            if ($modelLabourData) {
                $labourRate = (float) $modelLabourData['labour_rate'];
                $increasePct = (float) $modelLabourData['spmcl_inc_pct'];

                // Add increased percentage to labour rate
                $labourFactor = $labourRate + ($labourRate * $increasePct / 100);
            }

            $engData = $ServicePackageEnginesModel->select('speng_eng_id')->where('speng_spmc_id', $model_id)->where('speng_delete_flag', 0)->first();

            // Get Spares
            // $spares = $SP_Parts_Model->select('pm_name, pm_price,sp_spare_qty,sp_spare_id,sp_spare_labour_unit,pm_brand')
            //     ->where('sp_spare_spmc_id', $model_id)
            //     ->where('sp_spare_delete_flag', 0)
            //     ->join('parts_master', 'parts_master.pm_id = sp_spare_pm_id', 'left')
            //     ->join('brand_list', 'brand_list.brand_id = pm_brand', 'left')
            //     ->findAll();

            $sparesRaw = $SP_Parts_Model
                ->select('spim_name, pm_price, sp_spare_optional_flag,sp_spare_group_seq,spkm_km_optional_flag, sp_spare_qty, sp_spare_id, sp_spare_labour_unit, pm_brand, sp_km_item_map.spkm_km_id')
                ->where('sp_spare_spmc_id', $model_id)
                ->where('sp_spare_delete_flag', 0)
                ->where('sp_spare_applicable', 1)
                ->join('parts_master', 'parts_master.pm_id = sp_spare_pm_id', 'left')
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                ->join('brand_list', 'brand_list.brand_id = pm_brand', 'left')
                ->join(
                    'sp_km_item_map',
                    'sp_km_item_map.spkm_item_id = sp_spare_id 
            AND sp_km_item_map.spkm_item_type = 0 
            AND sp_km_item_map.spkm_delete_flag = 0',
                    'left'
                )
                ->findAll();

            $spares = [];
            foreach ($sparesRaw as $row) {
                $id = $row['sp_spare_id'];

                if (!isset($spares[$id])) {
                    // Initialize the spare data
                    $spares[$id] = [
                        'sp_spare_id'         => $row['sp_spare_id'],
                        'pm_name'             => $row['spim_name'],
                        'pm_price'            => $row['pm_price'],
                        'sp_spare_qty'        => $row['sp_spare_qty'],
                        'sp_spare_optional_flag'        => $row['sp_spare_optional_flag'],
                        'sp_spare_group_seq'        => $row['sp_spare_group_seq'],
                        'sp_spare_labour_unit' => $row['sp_spare_labour_unit'],
                        'pm_brand'            => $row['pm_brand'],
                        'selected_km_ids'     => [],
                    ];
                }

                // Add km id if present
                // if (!is_null($row['spkm_km_id'])) {
                //     $kmId = (int) $row['spkm_km_id'];
                //     if (!in_array($kmId, $spares[$id]['selected_km_ids'])) {
                //         $spares[$id]['selected_km_ids'][] = $kmId;
                //     }
                // }
                if (!is_null($row['spkm_km_id'])) {
                    $kmId = (int) $row['spkm_km_id'];
                    $optionalFlag = (int) $row['spkm_km_optional_flag'];

                    // Check if this km_id already exists to avoid duplicates
                    $existing = array_filter($spares[$id]['selected_km_ids'], function ($km) use ($kmId) {
                        return $km['km_id'] === $kmId;
                    });

                    if (empty($existing)) {
                        $spares[$id]['selected_km_ids'][] = [
                            'km_id' => $kmId,
                            'optional_flag' => $optionalFlag
                        ];
                    }
                }
            }

            // Reindex array if needed
            $spares = array_values($spares);


            // Get Labours
            // $labours = $SP_Labours_Model->where('sp_labour_spmc_id', $model_id)
            //     ->where('sp_labour_delete_flag', 0)
            //     ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = sp_labour_lm_id', 'left')
            //     ->findAll();

            $laboursRaw = $SP_Labours_Model
                ->select('sp_labours.sp_labour_id,sp_labour_optional_flag, sp_labour_group_seq,spkm_km_optional_flag,sp_labours.sp_labour_lm_id,sp_labours.sp_labour_unit, sp_labours.sp_labour_spmc_id, spim_name as sp_pm_name, sp_km_item_map.spkm_km_id')
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = sp_labours.sp_labour_lm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                ->join(
                    'sp_km_item_map',
                    'sp_km_item_map.spkm_item_id = sp_labours.sp_labour_id 
            AND sp_km_item_map.spkm_item_type = 1 
            AND sp_km_item_map.spkm_delete_flag = 0',
                    'left'
                )
                ->where('sp_labours.sp_labour_spmc_id', $model_id)
                ->where('sp_labours.sp_labour_delete_flag', 0)
                ->where('sp_labours.sp_labour_applicable', 1)
                ->findAll();


            $labours = [];

            foreach ($laboursRaw as $row) {
                $id = $row['sp_labour_id'];

                if (!isset($labours[$id])) {
                    $labours[$id] = [
                        'sp_labour_id'     => $row['sp_labour_id'],
                        'sp_labour_lm_id'  => $row['sp_labour_lm_id'],
                        'sp_labour_spmc_id' => $row['sp_labour_spmc_id'],
                        'sp_labour_optional_flag' => $row['sp_labour_optional_flag'],
                        'sp_labour_group_seq' => $row['sp_labour_group_seq'],
                        'sp_pm_name'       => $row['sp_pm_name'],
                        'sp_labour_unit' => $row['sp_labour_unit'],
                        'selected_km_ids'  => [],
                    ];
                }

                if (!is_null($row['spkm_km_id'])) {
                    $kmId = (int) $row['spkm_km_id'];
                    $optionalFlag = (int) $row['spkm_km_optional_flag'];

                    // Avoid duplicate km_id entries
                    $existing = array_filter($labours[$id]['selected_km_ids'], function ($km) use ($kmId) {
                        return $km['km_id'] === $kmId;
                    });

                    if (empty($existing)) {
                        $labours[$id]['selected_km_ids'][] = [
                            'km_id' => $kmId,
                            'optional_flag' => $optionalFlag
                        ];
                    }
                }
            }

            $labours = array_values($labours); // Re-index the array




            // Combine in PHP
            $spareAndLabourData = [
                'spares' => $spares,
                'labours' => $labours,
                'labourFactor' => $labourFactor
            ];



            if ($spareAndLabourData) {
                $response = [
                    'ret_data' => 'success',
                    'engData' => $engData,
                    'spareAndLabourData' => $spareAndLabourData,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getSPkilometer()
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

            $kmModel = new KilometerMasterModel();

            $kmData = $kmModel->where('km_delete_flag', 0)->findAll();


            if ($kmData) {
                $response = [
                    'ret_data' => 'success',
                    'kmData' => $kmData,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function saveSPKM()
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

            $ServicePackageKMItemMap = new ServicePackageKMItemMap();
            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $SP_Parts_Model = new ServicePackageSpareModel();
            $SP_Labours_Model = new ServicePackageLabourModel();



            $spareAndLabourKMMapped = $this->request->getVar('spareAndLabourKMMapped');
            $modelCode = $spareAndLabourKMMapped->model_code;
            $userId = $spareAndLabourKMMapped->user_id;


            // Extract the items array from the object
            $spareAndLabouritems = $spareAndLabourKMMapped->items;
            $spmc_status_flag = $spareAndLabourKMMapped->spmc_status_flag;
            $draft_flag = $spareAndLabourKMMapped->draft_flag;


            $modelData = $ServicePackageModelCodeModel->select('spmc_id')->where('spmc_value', $modelCode)->where('spmc_delete_flag', 0)->first();
            $model_id = $modelData['spmc_id'];


            $this->db->transStart();
            $insertData = [];
            $partsUpdateData = [];
            $labourUpdateData = [];
            $kmOptionMap = [];

            // --- PRELOAD EXISTING KM MAPPINGS (so we can diff later) ---
            $existingKmMap = []; // shape: [item_type][item_id] => [km_id, km_id, ...]
            $itemIdsByType = [];

            foreach ($spareAndLabouritems as $it) {
                $itemType = (int)$it->type;
                $itemId   = (int)$it->item_id;
                $itemIdsByType[$itemType][] = $itemId;
            }

            if (!empty($itemIdsByType)) {
                foreach ($itemIdsByType as $type => $ids) {
                    $rows = $ServicePackageKMItemMap
                        ->whereIn('spkm_item_id', $ids)
                        ->where('spkm_item_type', $type)
                        ->where('spkm_delete_flag', 0)
                        ->findAll();

                    foreach ($rows as $r) {
                        $t = (int)$r['spkm_item_type'];
                        $id = (int)$r['spkm_item_id'];
                        $existingKmMap[$t][$id][] = (int)$r['spkm_km_id'];
                    }
                }
            }
            // --- end preload ---


            foreach ($spareAndLabouritems as $item) {
                if ($item->type == 0) {
                    $partsUpdateData = [
                        'sp_spare_optional_flag' => $item->optional_flag
                    ];
                    $SP_Parts_Model->where('sp_spare_id', $item->item_id)->set($partsUpdateData)->update();
                } else if ($item->type == 1) {
                    $labourUpdateData = [
                        'sp_labour_optional_flag' => $item->optional_flag
                    ];
                    $SP_Labours_Model->where('sp_labour_id', $item->item_id)->set($labourUpdateData)->update();
                }
                $selectedKmIds = $item->selectedKmIds;

                $ServicePackageKMItemMap
                    ->where('spkm_item_id', $item->item_id)
                    ->where('spkm_item_type', $item->type)
                    ->set([
                        'spkm_delete_flag' => 1,
                        'spkm_updated_by'  => $userId,
                        'spkm_updated_on'  => date("Y-m-d H:i:s"),
                    ])
                    ->update();

                $kmOptionMap = [];

                foreach ($item->km_options as $option) {
                    $kmOptionMap[$option->km_id] = $option->optional_flag;
                }
                // Loop through selected km IDs and build insert data
                foreach ($selectedKmIds as $kmId) {
                    $optionalFlag = isset($kmOptionMap[$kmId]) ? $kmOptionMap[$kmId] : 0;

                    $insertData[] = [
                        'spkm_km_id'              => $kmId,
                        'spkm_item_id'           => $item->item_id,
                        'spkm_item_type'         => $item->type,
                        'spkm_created_by'        => $userId,
                        'spkm_created_on'        => date("Y-m-d H:i:s"),
                        'spkm_updated_by'        => $userId,
                        'spkm_updated_on'        => date("Y-m-d H:i:s"),
                        'spkm_km_optional_flag'  => $optionalFlag,
                    ];
                }
            }

            $servicePackageKM = $ServicePackageKMItemMap->insertBatch($insertData);

            $insertModelData = [
                'spmc_status_flag' => $spmc_status_flag,
                'spmc_draft_flag' => $draft_flag,
                'spmc_session_flag' => 0,
                'spmc_updated_by' =>  $userId,
                'spmc_updated_on' =>  date("Y-m-d H:i:s"),
            ];

            $ServicePackageModelCodeModel->where('spmc_id', $model_id)->set($insertModelData)->update();

            $this->db->transComplete();
            // --- SMART sp_log insert (robust + debug) ---
            $UserModel = new UserModel();
            $SpLogTable = new ServicePackageLogModel();
            $userRow = $UserModel->where('us_id', $userId)->first();
            $actorName = trim($userRow['us_firstname'] ?? $userRow['us_name'] ?? 'Unknown');
            $actionText = ($draft_flag == 1) ? 'Drafted' : 'Saved';

            $staticKmMap = [
                1  => 'Quick Lube',
                2  => '15k',
                3  => '30k',
                4  => '45k',
                5  => '60k',
                6  => '75k',
                7  => '90k',
                8  => '105k',
                9  => '120k',
                10 => '135k',
                11 => '150k',
                12 => '165k',
                13 => '180k',
                14 => '195k',
                15 => '210k',
                16 => '225k',
                17 => '240k',
                18 => '255k',
                19 => '270k',
                20 => '285k',
                21 => '300k',
            ];
            $resolveKmLabel = function ($id) use ($staticKmMap) {
                return $staticKmMap[(int)$id] ?? null;
            };

            // build newKmMap (from insertData)
            $newKmMap = [];
            if (!empty($insertData)) {
                foreach ($insertData as $r) {
                    $t = (int)$r['spkm_item_type'];
                    $id = (int)$r['spkm_item_id'];
                    $newKmMap[$t][$id][] = (int)$r['spkm_km_id'];
                }
            }

            // compute displayChanges (same logic, kept for clarity)
            $displayChanges = [];
            foreach ($spareAndLabouritems as $item) {
                $name = $item->name ?? 'Unnamed item';
                $t = (int)$item->type;
                $id = (int)$item->item_id;

                $oldIds = isset($existingKmMap[$t][$id]) ? array_map('intval', $existingKmMap[$t][$id]) : [];
                $newIds = isset($newKmMap[$t][$id])      ? array_map('intval', $newKmMap[$t][$id])      : [];

                $oldIds = array_values(array_unique($oldIds));
                $newIds = array_values(array_unique($newIds));

                $added   = array_values(array_diff($newIds, $oldIds));
                $removed = array_values(array_diff($oldIds, $newIds));

                if (!empty($added)) {
                    $labels = array_filter(array_map($resolveKmLabel, $added));
                    $displayChanges[] = $labels ? "{$name} → Added: " . implode(', ', $labels)
                        : "{$name} → Added (KM IDs): " . implode(', ', $added);
                }
                if (!empty($removed)) {
                    $labels = array_filter(array_map($resolveKmLabel, $removed));
                    $displayChanges[] = $labels ? "{$name} → Removed: " . implode(', ', $labels)
                        : "{$name} → Removed (KM IDs): " . implode(', ', $removed);
                }
            }

            // If you want always-audit (even when no change), uncomment this next block
            if (empty($displayChanges)) {
                // either skip insertion (if you don't want logs for no-change) or create a 'No changes' note
                $displayChanges[] = "No KM mapping changes detected.";
            }

            $itemsText = implode("\n", $displayChanges);
            $summaryNote = "{$actorName} ({$actionText}) updated Service package for Model Code {$modelCode}. Changes:\n";
            $logNote = $summaryNote . $itemsText;

            $logData = [
                'sp_log_spmc_id'     => $model_id,
                'sp_log_notes'       => $logNote,
                'sp_log_created_by'  => $userId,
                'sp_log_created_on'  => date("Y-m-d H:i:s"),
                'sp_log_delete_flag' => 0,
            ];

            // Attempt insert using model, then check DB diagnostics
            try {
                $SpLogTable->insert($logData);
                $insertId = $SpLogTable->insertID();
                $affected = $this->db->affectedRows();
                $dbError  = $this->db->error();

                if ($insertId > 0 || $affected > 0) {
                    // success - optionally write a debug log to PHP log
                    error_log("sp_log inserted id={$insertId}, affected={$affected}");
                } else {
                    // no rows inserted — surface diagnostics
                    $errMsg = "sp_log insert reported no affected rows. insertID={$insertId}, dbError=" . json_encode($dbError);
                    error_log($errMsg);
                    // try fallback direct table insert and capture error
                    $ok = $this->db->table('sp_log_table')->insert($logData);
                    $errAfter = $this->db->error();
                    if (!empty($errAfter['code'])) {
                        error_log("Fallback insert failed: " . json_encode($errAfter));
                    } else {
                        error_log("Fallback direct insert succeeded (no affectedRows reported).");
                    }
                }
            } catch (\Exception $e) {
                // truly unexpected error — capture detail
                $err = "Exception when inserting sp_log: " . $e->getMessage() . " ; payload: " . substr(json_encode($logData), 0, 1000);
                error_log($err);

                // Optional: attempt a minimal, safe log to capture the fact of failure
                try {
                    $fallback = [
                        'sp_log_spmc_id'    => $model_id,
                        'sp_log_notes'      => $actorName . " attempted to update Model Code {$modelCode} but sp_log insert failed.",
                        'sp_log_created_by' => $userId,
                        'sp_log_created_on' => date("Y-m-d H:i:s"),
                        'sp_log_delete_flag' => 0,
                    ];
                    $SpLogTable->insert($fallback);
                } catch (\Exception $inner) {
                    error_log("Fallback minimal sp_log also failed: " . $inner->getMessage());
                }
            }
            // --- end robust sp_log insert ---


            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $response['ret_data'] = "fail";
                return $this->respond($response, 200);
            } else {
                $this->db->transCommit();
                $response = [
                    'ret_data' => 'success',
                    'servicePackageKM' => $servicePackageKM,
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function getModelCodes()
    {
        $VehicleMaster = new VehicleMaster();
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

            $modelCodes = $VehicleMaster->where("veh_delete_flag !=", 1)
                ->distinct()
                ->select('veh_vingroup_master')
                ->findAll();

            if ($modelCodes) {
                $response = [
                    'ret_data' => 'success',
                    'modelCodes' => $modelCodes,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }


    public function setSPSessionLock()
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();

            $modelCode = $this->request->getVar('modelCode');
            $sessionLock = $this->request->getVar('sessionLock');
            $userId = $this->request->getVar('user_id');
            $modelData = $ServicePackageModelCodeModel->select('spmc_id')->where('spmc_value', $modelCode)->where('spmc_delete_flag', 0)->first();
            $model_id = $modelData['spmc_id'];

            $updateModelCodeData = [
                'spmc_value' => $modelCode,
                'spmc_session_flag' =>  $sessionLock,
                'spmc_session_us_id' => $userId,
                'spmc_updated_on' => date("Y-m-d H:i:s"),
            ];

            $servicePackage =  $ServicePackageModelCodeModel->where('spmc_id', $model_id)->set($updateModelCodeData)->update();


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

    public function checkSPSessionLock()
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();

            $modelCode = $this->request->getVar('modelCode');
            $modelData = $ServicePackageModelCodeModel->select('spmc_id,spmc_session_flag')->where('spmc_value', $modelCode)
                ->where('spmc_session_flag', 1)
                ->where('spmc_delete_flag', 0)
                ->first();


            if ($modelData) {
                $response = [
                    'ret_data' => 'success',
                    'servicePackageModelData' => $modelData,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getAllEnginesList()
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

    public function getAllPartsDetails()
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

            // $SparePartsMasterModel = new SparePartsMaster();

            // $spareParts = $SparePartsMasterModel->where("pm_delete_flag", 0)
            //     ->join('brand_list', 'brand_list.brand_id=pm_brand', 'left')
            //     ->findAll();

            $ServicePackagePartsModel = new ServicePackagePartsModel();
            $spareParts = $ServicePackagePartsModel
                ->select('sp_item_master.*, brand_list.*, sp_item_group.sp_ig_group_seq, GROUP_CONCAT(sp_item_group.sp_ig_group_seq) as group_seqs')
                ->where("sp_pm_delete_flag", 0)
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                ->join('brand_list', 'brand_list.brand_id = sp_pm_brand', 'left')
                ->join(
                    'sp_item_group',
                    'sp_item_group.sp_ig_spim_id = sp_item_master.spim_id AND sp_item_group.sp_ig_delete_flag = 0',
                    'left'
                )
                ->groupBy('sp_item_master.spim_id') // So we don’t get duplicate rows
                ->findAll();



            if ($spareParts) {
                $response = [
                    'ret_data' => 'success',
                    'spareParts' => $spareParts,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function checkPartPrice()
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

            $part_id = $this->request->getVar('part_id');
            $pm_price = $this->request->getVar('pm_price');

            $SparePartsMasterModel = new SparePartsMaster();

            $spareParts = $SparePartsMasterModel
                ->select('pm_price')
                ->where('pm_delete_flag', 0)
                ->where('pm_id', $part_id)
                ->where('pm_price !=', $pm_price)
                ->first();


            if ($spareParts) {
                $price = $spareParts['pm_price'];
                $response = [
                    'ret_data' => 'success',
                    'old_price' => $price,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }

            return $this->respond($response, 200);
        }
    }

    public function getServicePackageParts()
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

            // Step 1: Get all spare parts joined with item master via sp_pm_spim_id
            $SPSpareParts = $ServicePackagePartsModel
                ->where("sp_pm_delete_flag", 0)
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_parts_master.sp_pm_spim_id', 'left')
                ->select('sp_parts_master.*, 
              sp_item_master.spim_group_seq,
              sp_item_master.spim_name,
              sp_item_master.spim_id,
              GROUP_CONCAT(DISTINCT sp_item_group.sp_ig_group_seq) as group_seqs, brand_name')
                ->join('sp_item_group', 'sp_item_group.sp_ig_spim_id = sp_item_master.spim_id', 'left')
                ->join('brand_list', 'brand_list.brand_id = sp_pm_brand', 'left')
                ->groupBy("sp_item_master.spim_id,sp_pm_code, COALESCE(sp_pm_brand, '_NO_BRAND_')", false) // false = no escaping
                ->findAll();


            // Step 2: Optional - fetch all items if you still need them
            $SPItems = $SPItemMaster->where("spim_delete_flag", 0)->findAll();

            // Step 4: If you still want to inject spim_group_seq in response explicitly:
            foreach ($SPSpareParts as &$part) {
                if (!isset($part['spim_group_seq'])) {
                    $part['spim_group_seq'] = 0; // ensure it's present even if null
                }
            }
            unset($part);

            // Step 5: Return response
            if ($SPSpareParts) {
                $response = [
                    'ret_data' => 'success',
                    'SPSpareParts' => $SPSpareParts,
                    'SPItems' => $SPItems,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getServicePackagePartsById()
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

            $SPSparePart = $ServicePackagePartsModel->where("sp_pm_delete_flag", 0)
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_parts_master.sp_pm_spim_id', 'left')
                ->where("sp_pm_id", $this->request->getVar('sp_pm_id'))
                ->first();

            if ($SPSparePart) {
                $response = [
                    'ret_data' => 'success',
                    'SPSparePart' => $SPSparePart,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function updateServicePackagePartsById()
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
            $SPItemMasterModel = new SPItemMaster();

            $sp_pm_id = $this->request->getVar('sp_pm_id');
            $newName  = trim($this->request->getVar('spim_name'));
            $newCode  = trim($this->request->getVar('sp_pm_code'));
            $newBrand = trim($this->request->getVar('sp_pm_brand'));

            // Fetch the part data first
            $partsData = $ServicePackagePartsModel
                ->where('sp_pm_id', $sp_pm_id)
                ->where('sp_pm_delete_flag', 0)
                ->first();

            if (!$partsData) {
                return $this->response->setJSON(['status' => false, 'message' => 'Part not found.']);
            }

            // Duplicate check
            $duplicate = $ServicePackagePartsModel
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_parts_master.sp_pm_spim_id', 'left')
                ->where('sp_pm_code', $newCode)
                ->where('sp_pm_brand', $newBrand)
                ->where('LOWER(spim_name)', strtolower($newName))
                ->where('sp_pm_id !=', $sp_pm_id) // exclude current item
                ->where('sp_pm_delete_flag', 0)
                ->first();

            if ($duplicate) {
                return $this->response->setJSON([
                    'ret_data'  => 'duplicate',
                    'message' => 'Duplicate entry found with same name, code, and brand.'
                ]);
            }

            // Fetch the part data first
            $partsData = $ServicePackagePartsModel
                ->where('sp_pm_id', $sp_pm_id)
                ->where('sp_pm_delete_flag', 0)
                ->first();

            $updatePartsData = [
                'sp_pm_code'       => $this->request->getVar('sp_pm_code'),
                'sp_pm_brand'      => $this->request->getVar('sp_pm_brand'),
                'sp_pm_unit_type'  => $this->request->getVar('sp_pm_unit_type'),
                'sp_pm_access'     => $this->request->getVar('sp_pm_access'),
                'sp_pm_price'      => $this->request->getVar('sp_pm_price'),
                'sp_pm_ordering'   => $this->request->getVar('sp_pm_ordering'),
            ];

            if ($sp_pm_id && $partsData) {
                // Get the linked spim_id from sp_pm_spim_id
                $spim_id = $partsData['sp_pm_spim_id'];

                // Get the new name to update in SPItemMaster
                $newName = $this->request->getVar('spim_name');

                // Update name in SPItemMaster
                if ($spim_id && $newName) {
                    $SPItemMasterModel->update($spim_id, ['spim_name' => $newName]);
                }

                // Update remaining part details in ServicePackagePartsModel
                $servicePackage = $ServicePackagePartsModel->update($sp_pm_id, $updatePartsData);
            }


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

    public function deleteServicePackagePartsById()
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

            $sp_pm_id = $this->request->getVar('sp_pm_id');

            $updatePartsData = [
                'sp_pm_delete_flag' => 1,
            ];

            if ($sp_pm_id) {
                $servicePackage =  $ServicePackagePartsModel->update($sp_pm_id, $updatePartsData);
            }


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


    public function getSPByKm()
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $ServicePackageEnginesModel = new ServicePackageEnginesModel();
            $SP_Parts_Model = new ServicePackageSpareModel();
            $SP_Labours_Model = new ServicePackageLabourModel();
            $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();
            $ServicePackageKmPriceModel = new ServicePackageKmPriceModel();
            $ServicePackageItemKmModel = new ServicePackageKMItemMap();


            $model_id = $this->request->getVar('model_id');

            $modelData = $ServicePackageModelCodeModel->select('spmc_value')->where('spmc_id', $model_id)->where('spmc_delete_flag', 0)->first();
            $modelCode = $modelData['spmc_value'];

            $modelLabourData = $ServicePackageModelCodeLabourModel
                ->where('model_code', $modelCode)
                ->where('spmcl_delete_flag', 0)
                ->first();

            $labourFactor = 0;

            if ($modelLabourData) {
                $labourRate = (float) $modelLabourData['labour_rate'];
                $increasePct = (float) $modelLabourData['spmcl_inc_pct'];

                // Add increased percentage to labour rate
                $labourFactor = $labourRate + ($labourRate * $increasePct / 100);
            }

            $engData = $ServicePackageEnginesModel->select('speng_eng_id')->where('speng_spmc_id', $model_id)->where('speng_delete_flag', 0)->first();

            // Get Spares
            $spares = $SP_Parts_Model
                ->select("spim_name as pm_name, sp_spare_id, pm_price, sp_spare_qty,sp_spare_group_seq, sp_spare_labour_unit,sp_spare_optional_flag, pm_brand,GROUP_CONCAT(CONCAT(spkm_km_id, ':', sp_km_item_map.spkm_km_optional_flag)) as km_data")
                ->where('sp_spare_spmc_id', $model_id)
                ->where('sp_spare_delete_flag', 0)
                ->where('sp_spare_applicable', 1)
                ->join('parts_master', 'parts_master.pm_id = sp_spare_pm_id', 'left')
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                ->join('brand_list', 'brand_list.brand_id = pm_brand', 'left')
                ->join('sp_km_item_map', 'sp_km_item_map.spkm_item_id = sp_spare_id AND spkm_item_type = 0 AND spkm_delete_flag = 0', 'left')
                ->groupBy('sp_spare_id')
                ->findAll();

            foreach ($spares as &$spare) {
                $kmDetails = [];

                if (!empty($spare['km_data'])) {
                    $pairs = explode(',', $spare['km_data']); // e.g., "1:1,2:0,3:1"
                    foreach ($pairs as $pair) {
                        [$kmId, $optionalFlag] = explode(':', $pair);
                        $kmDetails[] = [
                            'km_id' => (int)$kmId,
                            'optional_flag' => (int)$optionalFlag,
                        ];
                    }
                }

                $spare['kilometer_data'] = $kmDetails;
                unset($spare['km_data']); // optional cleanup
            }

            // Get Labours
            $labours = $SP_Labours_Model
                ->select("
                spim_name as sp_pm_name,
                sp_labour_id,
                sp_labour_qty,
                sp_labour_group_seq,
                sp_pm_category,
                spkm_km_optional_flag,
                sp_labour_unit,
                sp_labour_optional_flag,
                GROUP_CONCAT(CONCAT(spkm_km_id, ':', spkm_km_optional_flag)) as km_data
                ")
                ->where('sp_labour_spmc_id', $model_id)
                ->where('sp_labour_delete_flag', 0)
                ->where('sp_labours.sp_labour_applicable', 1)
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = sp_labour_lm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                ->join(
                    'sp_km_item_map',
                    'sp_km_item_map.spkm_item_id = sp_labour_id AND spkm_item_type = 1 AND spkm_delete_flag = 0',
                    'left'
                )
                // ->join('sp_km_item_map', 'sp_km_item_map.spkm_item_id = sp_labour_id AND spkm_item_type = 1', 'left')
                ->groupBy('sp_labour_id')
                ->findAll();


            foreach ($labours as &$labour) {
                $kmDetails = [];

                if (!empty($labour['km_data'])) {
                    $pairs = explode(',', $labour['km_data']); // "1:1,2:0"

                    foreach ($pairs as $pair) {
                        [$kmId, $optionalFlag] = explode(':', $pair);
                        $kmDetails[] = [
                            'km_id' => (int)$kmId,
                            'optional_flag' => (int)$optionalFlag,
                        ];
                    }
                }

                $labour['kilometer_data'] = $kmDetails;
                unset($labour['km_data']); // optional
            }


            $kmPiceMap = $ServicePackageKmPriceModel->where('spkmp_spmc_id', $model_id)->where('spkmp_delete_flag', 0)->findAll();


            // Combine in PHP
            $spareAndLabourData = [
                'spares' => $spares,
                'labours' => $labours,
                'labourFactor' => $labourFactor,
                'kmPiceMap' => $kmPiceMap,
            ];

            if ($spareAndLabourData) {
                $response = [
                    'ret_data' => 'success',
                    'engData' => $engData,
                    'spareAndLabourData' => $spareAndLabourData,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }


    // public function getPricesForEngNo()
    // {
    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $tokendata['uid'])->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata) {

    //         $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
    //         $ServicePackageEnginesModel = new ServicePackageEnginesModel();
    //         $SP_Parts_Model = new ServicePackageSpareModel();
    //         $SP_Labours_Model = new ServicePackageLabourModel();
    //         $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();
    //         $ServicePackageKmPriceModel = new ServicePackageKmPriceModel();

    //         $model_id = $this->request->getVar('model_id');
    //         $engineNO = $this->request->getVar('engineNO');

    //         // $modelData = $ServicePackageModelCodeModel->select('spmc_value')->where('spmc_id !=', $model_id)->where('spmc_delete_flag', 0)->findAll();
    //         // $modelCode = $modelData['spmc_value'];

    //         $matchingEngineModels = $ServicePackageEnginesModel
    //             ->select('speng_spmc_id,sp_model_code.spmc_value')
    //             ->join('sp_model_code', 'sp_model_code.spmc_id = speng_spmc_id', 'left')
    //             ->where('speng_eng_id', $engineNO)
    //             ->where('speng_spmc_id !=', $model_id)
    //             ->where('sp_model_code.spmc_status_flag', 5)
    //             ->where('speng_delete_flag', 0)
    //             ->findAll();

    //         $engineMatchedModelIds = array_column($matchingEngineModels, 'speng_spmc_id');

    //         // Step 2: Get spareA and labourA (for input model)
    //         $spares = $SP_Parts_Model
    //             ->select('sp_spare_pm_id')
    //             ->where('sp_spare_spmc_id', $model_id)
    //             ->where('sp_spare_delete_flag', 0)
    //             ->where('sp_spare_applicable', 1)
    //             ->findAll();
    //         $spareA = array_column($spares, 'sp_spare_pm_id');
    //         sort($spareA);
    //         $spareAFingerprint = implode(',', $spareA);

    //         $labours = $SP_Labours_Model
    //             ->select('sp_labour_lm_id')
    //             ->where('sp_labour_spmc_id', $model_id)
    //             ->where('sp_labour_delete_flag', 0)
    //             ->where('sp_labour_applicable', 1)
    //             ->findAll();
    //         $labourA = array_column($labours, 'sp_labour_lm_id');
    //         sort($labourA);
    //         $labourAFingerprint = implode(',', $labourA);

    //         // Step 3: Compare with other models using same engine
    //         $matchedModels = [];
    //         foreach ($engineMatchedModelIds as $otherModelId) {

    //             // Compare spareB
    //             $sparesB = $SP_Parts_Model
    //                 ->select('sp_spare_pm_id')
    //                 ->where('sp_spare_spmc_id', $otherModelId)
    //                 ->where('sp_spare_delete_flag', 0)
    //                 ->where('sp_spare_applicable', 1)
    //                 ->findAll();
    //             $spareB = array_column($sparesB, 'sp_spare_pm_id');
    //             sort($spareB);
    //             if (implode(',', $spareB) !== $spareAFingerprint) continue;

    //             // Compare labourB
    //             $laboursB = $SP_Labours_Model
    //                 ->select('sp_labour_lm_id')
    //                 ->where('sp_labour_spmc_id', $otherModelId)
    //                 ->where('sp_labour_delete_flag', 0)
    //                 ->where('sp_labour_applicable', 1)
    //                 ->findAll();
    //             $labourB = array_column($laboursB, 'sp_labour_lm_id');
    //             sort($labourB);
    //             if (implode(',', $labourB) !== $labourAFingerprint) continue;

    //             // If both match
    //             $matchedModels[] = $otherModelId;
    //         }

    //         // Step 4: Get display & markup prices for matched models
    //         $matchedPrices = [];
    //         if (!empty($matchedModels)) {
    //             $matchedPrices = $ServicePackageKmPriceModel
    //                 ->select('spkmp_spmc_id,spkmp_spkm_id, spkmp_display_price, spkmp_markup_price')
    //                 ->whereIn('spkmp_spmc_id', $matchedModels)
    //                 ->where('spkmp_delete_flag', 0)
    //                 ->findAll();
    //         }
    //         $matchedModelInfo = [];
    //         foreach ($matchingEngineModels as $row) {
    //             if (in_array($row['speng_spmc_id'], $matchedModels)) {
    //                 $matchedModelInfo[] = [
    //                     'id' => $row['speng_spmc_id'],
    //                     'model_code' => $row['spmc_value'],
    //                 ];
    //             }
    //         }

    //         if (empty($matchedModelInfo)) {
    //             return $this->respond([
    //                 'ret_data' => 'fail',
    //             ], 200);
    //         }

    //         return $this->respond([
    //             'ret_data' => 'success',
    //             'matched_models' => $matchedModelInfo,
    //             'prices' => $matchedPrices,
    //         ], 200);
    //     }
    // }

    public function getPricesForEngNo()
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $ServicePackageEnginesModel = new ServicePackageEnginesModel();
            $SP_Parts_Model = new ServicePackageSpareModel();
            $SP_Labours_Model = new ServicePackageLabourModel();
            $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();
            $ServicePackageKmPriceModel = new ServicePackageKmPriceModel();

            $model_id = $this->request->getVar('model_id');
            $engineNO = $this->request->getVar('engineNO');

            // $modelData = $ServicePackageModelCodeModel->select('spmc_value')->where('spmc_id !=', $model_id)->where('spmc_delete_flag', 0)->findAll();
            // $modelCode = $modelData['spmc_value'];

            // Step 1: Get all models with same engine (excluding current one)
            $matchingEngineModels = $ServicePackageEnginesModel
                ->select(
                    'speng_spmc_id, sp_model_code.spmc_value,sp_model_code.spmc_model_year'
                )
                ->join('sp_model_code', 'sp_model_code.spmc_id = speng_spmc_id', 'left')
                ->where('speng_eng_id', $engineNO)
                ->where('speng_spmc_id !=', $model_id)
                ->where('sp_model_code.spmc_status_flag', 5)
                ->where('speng_delete_flag', 0)
                ->findAll();

            $engineMatchedModelIds = array_column($matchingEngineModels, 'speng_spmc_id');

            $matchedModelInfo = [];

            if (!empty($engineMatchedModelIds)) {
                // Step 2: Get KM pricing for those models
                $matchedPrices = $ServicePackageKmPriceModel
                    ->select('spkmp_spmc_id, spkmp_spkm_id, spkmp_display_price, spkmp_markup_price')
                    ->whereIn('spkmp_spmc_id', $engineMatchedModelIds)
                    ->where('spkmp_delete_flag', 0)
                    ->findAll();

                // Group prices by model ID
                $groupedPrices = [];
                foreach ($matchedPrices as $price) {
                    $groupedPrices[$price['spkmp_spmc_id']][] = [
                        'km_id' => $price['spkmp_spkm_id'],
                        'display_price' => $price['spkmp_display_price'],
                        'markup_price' => $price['spkmp_markup_price'],
                    ];
                }

                // Prepare final output
                foreach ($matchingEngineModels as $row) {
                    $modelId = $row['speng_spmc_id'];
                    $matchedModelInfo[] = [
                        'id' => $modelId,
                        'model_code' => $row['spmc_value'],
                        'spmc_model_year' => $row['spmc_model_year'],
                        'prices' => $groupedPrices[$modelId] ?? [],
                    ];
                }
            }

            if (empty($matchedModelInfo)) {
                return $this->respond([
                    'ret_data' => 'fail',
                ], 200);
            }

            return $this->respond([
                'ret_data' => 'success',
                'matched_models' => $matchedModelInfo,
                'prices' => $matchedPrices,
            ], 200);
        }
    }



    // public function saveServicePackageKmPriceMap()
    // {
    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $tokendata['uid'])->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata) {

    //         $Sp_KmPrice_Model = new ServicePackageKmPriceModel();
    //         $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();



    //         $model_id = $this->request->getVar('model_id');
    //         $km_price_map = $this->request->getVar('km_price_map');
    //         $user_id = $this->request->getVar('user_id');



    //         $this->db->transStart();

    //         $existing = $Sp_KmPrice_Model
    //             ->where('spkmp_spmc_id', $model_id)
    //             ->where('spkmp_delete_flag', 0)
    //             ->findAll();

    //         $existingMap = [];
    //         foreach ($existing as $row) {
    //             $existingMap[$row['spkmp_spkm_id']] = $row;
    //         }

    //         $newKmIds = array_map(function ($x) {
    //             return $x->km_id;
    //         }, $km_price_map);

    //         $insertData = [];
    //         $updateData = [];

    //         foreach ($km_price_map as $item) {
    //             if (isset($existingMap[$item->km_id])) {
    //                 // Prepare update
    //                 $updateData[] = [
    //                     'spkmp_id' => $existingMap[$item->km_id]['spkmp_id'], // assuming primary key
    //                     'spkmp_markup_price' => $item->markup_price,
    //                     'spkmp_display_price' => $item->display_price,
    //                     'spkmp_updated_by' => $user_id,
    //                     'spkmp_updated_on' => date("Y-m-d H:i:s"),
    //                 ];
    //             } else {
    //                 // Prepare insert
    //                 $insertData[] = [
    //                     'spkmp_spmc_id' => $model_id,
    //                     'spkmp_spkm_id' => $item->km_id,
    //                     'spkmp_markup_price' => $item->markup_price,
    //                     'spkmp_display_price' => $item->display_price,
    //                     'spkmp_created_by' => $user_id,
    //                     'spkmp_created_on' => date("Y-m-d H:i:s"),
    //                     'spkmp_updated_by' => $user_id,
    //                     'spkmp_updated_on' => date("Y-m-d H:i:s"),
    //                 ];
    //             }
    //         }


    //         foreach ($updateData as $row) {
    //             $Sp_KmPrice_Model->update($row['spkmp_id'], $row);
    //         }


    //         $Sp_KmPrice_Model
    //             ->where('spkmp_spmc_id', $model_id)
    //             ->whereNotIn('spkmp_spkm_id', $newKmIds)
    //             ->set(['spkmp_delete_flag' => 1, 'spkmp_updated_by' => $user_id, 'spkmp_updated_on' => date("Y-m-d H:i:s")])
    //             ->update();


    //         $servicePackageKMMap = null;
    //         if (!empty($insertData)) {
    //             $servicePackageKMMap = $Sp_KmPrice_Model->insertBatch($insertData);
    //         }

    //         $inserModelData = [
    //             'spmc_status_flag' => 5,
    //             'spmc_updated_by' => $user_id,
    //             'spmc_updated_on' =>  date("Y-m-d H:i:s"),
    //         ];

    //         $ServicePackageModelCodeModel->where('spmc_id', $model_id)->set($inserModelData)->update();



    //         $this->db->transComplete();

    //         // --- START: build and insert sp_log summary for km_price_map changes ---
    //         try {
    //             // resolve human label for km id
    //             $resolveKmLabel = function ($id) {
    //                 $id = (int)$id;
    //                 if ($id === 1) return 'Quick Lube';
    //                 // 15k increments after id 1
    //                 if ($id >= 2) return (($id - 1) * 15) . 'k';
    //                 return 'km#' . $id;
    //             };

    //             // fetch model code for readable header if possible
    //             $modelRow = $ServicePackageModelCodeModel->select('spmc_value')->where('spmc_id', $model_id)->first();
    //             $modelCode = isset($modelRow['spmc_value']) ? $modelRow['spmc_value'] : ('model_id:' . $model_id);

    //             // Build lists: inserted, updated, deleted
    //             $insertedList = []; // from $insertData
    //             foreach ($insertData as $r) {
    //                 // insertData has keys: spkmp_spkm_id, spkmp_markup_price, spkmp_display_price
    //                 $kmId = isset($r['spkmp_spkm_id']) ? (int)$r['spkmp_spkm_id'] : null;
    //                 if ($kmId === null) continue;
    //                 $insertedList[] = [
    //                     'km_id' => $kmId,
    //                     'label' => $resolveKmLabel($kmId),
    //                     'markup_price' => $r['spkmp_markup_price'],
    //                     'display_price' => $r['spkmp_display_price'],
    //                 ];
    //             }

    //             // updated: reconstruct from incoming km_price_map and existingMap
    //             $updatedList = [];
    //             foreach ($km_price_map as $item) {
    //                 $kmId = (int)$item->km_id;
    //                 if (isset($existingMap[$kmId])) {
    //                     $old = $existingMap[$kmId];
    //                     $updatedList[] = [
    //                         'km_id' => $kmId,
    //                         'label' => $resolveKmLabel($kmId),
    //                         'old_markup' => isset($old['spkmp_markup_price']) ? $old['spkmp_markup_price'] : null,
    //                         'new_markup' => $item->markup_price,
    //                         'old_display' => isset($old['spkmp_display_price']) ? $old['spkmp_display_price'] : null,
    //                         'new_display' => $item->display_price,
    //                     ];
    //                 }
    //             }

    //             // deleted: those in existingMap but not in new km ids
    //             $newKmIds = array_map(function ($x) {
    //                 return (int)$x->km_id;
    //             }, $km_price_map);
    //             $deletedList = [];
    //             foreach ($existingMap as $kmId => $row) {
    //                 if (!in_array((int)$kmId, $newKmIds, true)) {
    //                     $deletedList[] = [
    //                         'km_id' => (int)$kmId,
    //                         'label' => $resolveKmLabel((int)$kmId),
    //                         'old_markup' => isset($row['spkmp_markup_price']) ? $row['spkmp_markup_price'] : null,
    //                         'old_display' => isset($row['spkmp_display_price']) ? $row['spkmp_display_price'] : null,
    //                     ];
    //                 }
    //             }

    //             // Build a human-friendly note
    //             $now = date('Y-m-d H:i:s');
    //             $noteLines = [];
    //             $noteLines[] = "KM price map updated for Model Code {$modelCode} (spmc_id: {$model_id}) by user {$user_id} on {$now}.";
    //             $noteLines[] = ''; // blank line

    //             // Inserted
    //             // $noteLines[] = "Inserted (" . count($insertedList) . "):";
    //             if (empty($insertedList)) {
    //                 $noteLines[] = "  - none";
    //             } else {
    //                 foreach ($insertedList as $it) {
    //                     $noteLines[] = sprintf(
    //                         "  - %s (km_id:%d): markup=%s, display=%s",
    //                         $it['label'],
    //                         $it['km_id'],
    //                         $it['markup_price'],
    //                         $it['display_price']
    //                     );
    //                 }
    //             }
    //             $noteLines[] = ''; // blank line

    //             // Updated
    //             // $noteLines[] = "Updated (" . count($updatedList) . "):";
    //             if (empty($updatedList)) {
    //                 $noteLines[] = "  - none";
    //             } else {
    //                 foreach ($updatedList as $it) {
    //                     $noteLines[] = sprintf(
    //                         "  - %s (km_id:%d): markup %s → %s, display %s → %s",
    //                         $it['label'],
    //                         $it['km_id'],
    //                         $it['old_markup'],
    //                         $it['new_markup'],
    //                         $it['old_display'],
    //                         $it['new_display']
    //                     );
    //                 }
    //             }
    //             $noteLines[] = '';

    //             // Deleted
    //             $noteLines[] = "Deleted (" . count($deletedList) . "):";
    //             if (empty($deletedList)) {
    //                 $noteLines[] = "  - none";
    //             } else {
    //                 foreach ($deletedList as $it) {
    //                     $noteLines[] = sprintf(
    //                         "  - %s (km_id:%d): old_markup=%s, old_display=%s",
    //                         $it['label'],
    //                         $it['km_id'],
    //                         $it['old_markup'],
    //                         $it['old_display']
    //                     );
    //                 }
    //             }

    //             // Final note string
    //             $logNote = implode("\n", $noteLines);

    //             // Insert into sp_log (allowed fields only)
    //             $logData = [
    //                 'sp_log_spmc_id'    => $model_id,
    //                 'sp_log_notes'      => $logNote,
    //                 'sp_log_created_by' => $user_id,
    //                 'sp_log_created_on' => $now,
    //                 'sp_log_delete_flag' => 0,
    //             ];

    //             $this->db->table('sp_log_table')->insert($logData);
    //             // optional capture: $spLogId = $this->db->insertID();
    //         } catch (\Exception $e) {
    //             // Don't fail the transaction because logging failed — write to server log for debugging
    //             error_log('sp_log_table km_price_map insert failed: ' . $e->getMessage());
    //         }
    //         // --- END: build and insert sp_log summary ---

    //         if ($this->db->transStatus() === false) {
    //             $this->db->transRollback();
    //             $response['ret_data'] = "fail";
    //             return $this->respond($response, 200);
    //         } else {
    //             // $this->db->transCommit();
    //             $response = [
    //                 'ret_data' => 'success',
    //                 'servicePackageKMMap' => $servicePackageKMMap,
    //             ];
    //             return $this->respond($response, 200);
    //         }
    //     }
    // }
    public function saveServicePackageKmPriceMap()
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

            $Sp_KmPrice_Model = new ServicePackageKmPriceModel();
            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();

            $model_id = $this->request->getVar('model_id');
            $km_price_map = $this->request->getVar('km_price_map');
            $user_id = $this->request->getVar('user_id');

            $UserModel = new UserModel();
            $user = $UserModel->select('us_firstname')
                ->where('us_id', $user_id)
                ->first();

            $userName = $user['us_firstname'] ?? 'Unknown User';

            $this->db->transStart();

            $existing = $Sp_KmPrice_Model
                ->where('spkmp_spmc_id', $model_id)
                ->where('spkmp_delete_flag', 0)
                ->findAll();

            $existingMap = [];
            foreach ($existing as $row) {
                $existingMap[$row['spkmp_spkm_id']] = $row;
            }

            $newKmIds = array_map(function ($x) {
                return $x->km_id;
            }, $km_price_map);

            $insertData = [];
            $updateData = [];

            foreach ($km_price_map as $item) {
                if (isset($existingMap[$item->km_id])) {
                    // Prepare update
                    $updateData[] = [
                        'spkmp_id' => $existingMap[$item->km_id]['spkmp_id'], // assuming primary key
                        'spkmp_markup_price' => $item->markup_price,
                        'spkmp_display_price' => $item->display_price,
                        'spkmp_updated_by' => $user_id,
                        'spkmp_updated_on' => date("Y-m-d H:i:s"),
                    ];
                } else {
                    // Prepare insert
                    $insertData[] = [
                        'spkmp_spmc_id' => $model_id,
                        'spkmp_spkm_id' => $item->km_id,
                        'spkmp_markup_price' => $item->markup_price,
                        'spkmp_display_price' => $item->display_price,
                        'spkmp_created_by' => $user_id,
                        'spkmp_created_on' => date("Y-m-d H:i:s"),
                        'spkmp_updated_by' => $user_id,
                        'spkmp_updated_on' => date("Y-m-d H:i:s"),
                    ];
                }
            }

            foreach ($updateData as $row) {
                $Sp_KmPrice_Model->update($row['spkmp_id'], $row);
            }

            $Sp_KmPrice_Model
                ->where('spkmp_spmc_id', $model_id)
                ->whereNotIn('spkmp_spkm_id', $newKmIds)
                ->set(['spkmp_delete_flag' => 1, 'spkmp_updated_by' => $userName, 'spkmp_updated_on' => date("Y-m-d H:i:s")])
                ->update();

            $servicePackageKMMap = null;
            if (!empty($insertData)) {
                $servicePackageKMMap = $Sp_KmPrice_Model->insertBatch($insertData);
            }

            $inserModelData = [
                'spmc_status_flag' => 5,
                'spmc_updated_by' => $user_id,
                'spmc_updated_on' =>  date("Y-m-d H:i:s"),
            ];

            $ServicePackageModelCodeModel->where('spmc_id', $model_id)->set($inserModelData)->update();

            $this->db->transComplete();

            // --- START: build and insert sp_log summary for km_price_map changes ---
            try {
                $resolveKmLabel = function ($id) {
                    $id = (int)$id;
                    if ($id === 1) return 'Quick Lube';
                    if ($id >= 2) return (($id - 1) * 15) . 'k';
                    return 'km#' . $id;
                };

                $modelRow = $ServicePackageModelCodeModel->select('spmc_value')->where('spmc_id', $model_id)->first();
                $modelCode = isset($modelRow['spmc_value']) ? $modelRow['spmc_value'] : ('model_id:' . $model_id);

                $insertedList = [];
                foreach ($insertData as $r) {
                    $kmId = isset($r['spkmp_spkm_id']) ? (int)$r['spkmp_spkm_id'] : null;
                    if ($kmId === null) continue;
                    $insertedList[] = sprintf(
                        "  - %s (km_id:%d): markup=%s, display=%s",
                        $resolveKmLabel($kmId),
                        $kmId,
                        $r['spkmp_markup_price'],
                        $r['spkmp_display_price']
                    );
                }

                $updatedList = [];
                foreach ($km_price_map as $item) {
                    $kmId = (int)$item->km_id;
                    if (isset($existingMap[$kmId])) {
                        $old = $existingMap[$kmId];
                        // log only if something actually changed
                        if ($old['spkmp_markup_price'] != $item->markup_price || $old['spkmp_display_price'] != $item->display_price) {
                            $updatedList[] = sprintf(
                                "  - %s (km_id:%d): markup %s → %s, display %s → %s",
                                $resolveKmLabel($kmId),
                                $kmId,
                                $old['spkmp_markup_price'],
                                $item->markup_price,
                                $old['spkmp_display_price'],
                                $item->display_price
                            );
                        }
                    }
                }

                $deletedList = [];
                foreach ($existingMap as $kmId => $row) {
                    if (!in_array((int)$kmId, $newKmIds, true)) {
                        $deletedList[] = sprintf(
                            "  - %s (km_id:%d): old_markup=%s, old_display=%s",
                            $resolveKmLabel((int)$kmId),
                            $kmId,
                            $row['spkmp_markup_price'],
                            $row['spkmp_display_price']
                        );
                    }
                }

                $now = date('Y-m-d H:i:s');
                $noteLines = [];
                $noteLines[] = "KM price map updated for Model Code {$modelCode} (spmc_id: {$model_id}) by user {$userName} on {$now}.";
                $noteLines[] = '';

                if (!empty($insertedList)) {
                    $noteLines[] = "Inserted:";
                    $noteLines = array_merge($noteLines, $insertedList);
                    $noteLines[] = '';
                }

                if (!empty($updatedList)) {
                    $noteLines[] = "Updated:";
                    $noteLines = array_merge($noteLines, $updatedList);
                    $noteLines[] = '';
                }

                if (!empty($deletedList)) {
                    $noteLines[] = "Deleted:";
                    $noteLines = array_merge($noteLines, $deletedList);
                    $noteLines[] = '';
                }

                $logNote = implode("\n", $noteLines);

                $logData = [
                    'sp_log_spmc_id'    => $model_id,
                    'sp_log_notes'      => $logNote,
                    'sp_log_created_by' => $user_id,
                    'sp_log_created_on' => $now,
                    'sp_log_delete_flag' => 0,
                ];

                $this->db->table('sp_log_table')->insert($logData);
            } catch (\Exception $e) {
                error_log('sp_log_table km_price_map insert failed: ' . $e->getMessage());
            }
            // --- END ---

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $response['ret_data'] = "fail";
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'servicePackageKMMap' => $servicePackageKMMap,
                ];
                return $this->respond($response, 200);
            }
        }
    }


    public function getDraftItems()
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
            $spModelCode = new ServicePackageModelCodeModel;
            $ServicePackageEnginesModel = new ServicePackageEnginesModel();
            $SP_Parts_Model = new ServicePackageSpareModel();
            $modelId = $this->request->getVar('modelId');
            $draftItems = [];
            $engine = $ServicePackageEnginesModel
                ->where('speng_spmc_id', $modelId)
                ->where('speng_delete_flag', 0)
                ->first();


            $parts = $SP_Parts_Model
                ->where('sp_spare_spmc_id', $modelId)
                ->join(
                    'parts_master',
                    'parts_master.pm_id = sp_spare_pm_id',
                )
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                ->where('sp_spare_delete_flag', 0)
                ->findAll();

            $hasData = ! empty($engine) || ! empty($parts);


            // combine into draftItems
            $draftItems = [
                'engines' => $engine,
                'parts'   => $parts,
            ];

            if ($hasData) {
                $response = [
                    'ret_data' => 'success',
                    'draftItems' => $draftItems,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'draftItems' => [],
                ];
            }


            return $this->respond($response, 200);
        }
    }

    public function checkDuplicateOrdering()
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

            $category = $this->request->getVar('sp_pm_category');
            $ordering = $this->request->getVar('sp_pm_ordering');
            $sp_pm_id = $this->request->getVar('sp_pm_id');

            $builder = $ServicePackagePartsModel
                ->where('sp_pm_ordering', $ordering)
                ->where('sp_pm_category', $category)
                ->where('sp_pm_delete_flag', 0);

            if (!empty($sp_pm_id) && $sp_pm_id != '0') {
                $builder->where('sp_pm_id !=', $sp_pm_id);
            }

            $duplicate = $builder->first();

            if ($duplicate) {
                $response = [
                    'ret_data' => 'success',
                    'message' => 'Duplicate ordering exists in the selected category.',
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'draftItems' => [],
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getConsumablePrice()
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
            $partsMasterModel = new SparePartsMaster();

            $part_no = $this->request->getVar('part_no');

            $partsData = $ServicePackagePartsModel
                ->where('sp_pm_code', $part_no)
                ->where('sp_pm_delete_flag', 0)
                ->join('brand_list', 'brand_list.brand_id=sp_pm_brand', 'left')
                ->findAll();

            // if ($partsData) {
            //     $fullPartsdata = $partsMasterModel->where('pm_code', $partsData['sp_pm_code'])->where('pm_brand', $partsData['sp_pm_brand'])->first();

            //     if ($fullPartsdata && isset($fullPartsdata['pm_id'])) {
            //         $partsData['pm_id'] = $fullPartsdata['pm_id'];
            //     } else {
            //         $partsData[' '] = '';
            //     }
            // }
            foreach ($partsData as &$part) {
                $fullPartsdata = $partsMasterModel
                    ->where('pm_code', $part['sp_pm_code'])
                    ->where('pm_brand', $part['sp_pm_brand'])
                    ->first();

                $part['pm_id'] = $fullPartsdata['pm_id'] ?? '';
            }
            unset($part);

            if (!empty($partsData)) {
                $response = [
                    'ret_data' => count($partsData) > 0 ? 'success' : 'fail',
                    'partsData' => $partsData
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'partsData' => []
                ];
            }
            return $this->respond($response, 200);
        }
    }


    public function createGroup()
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
            // Step 1: Determine next group sequence
            $SpItemGroupModel = new ServiceItemGroupModel();
            $lastGroup = $SpItemGroupModel
                ->selectMax('sp_ig_group_seq')
                ->first();
            $groupSeq = $lastGroup && $lastGroup['sp_ig_group_seq']
                ? $lastGroup['sp_ig_group_seq'] + 1
                : 1;

            // Step 2: Get request data
            $parts       = $this->request->getVar('groupPartsId');
            $consumables = $this->request->getVar('groupConsumables');
            $operation   = $this->request->getVar('groupOperation');
            $user_id     = $this->request->getVar('user_id');
            $now         = date('Y-m-d H:i:s');

            // Step 3: Combine all incoming item IDs into one sorted, unique CSV
            $allItems = array_unique(array_merge(
                is_array($parts)       ? $parts       : [],
                is_array($consumables) ? $consumables : [],
                is_array($operation)   ? $operation   : []
            ));
            sort($allItems);
            $itemListStr = implode(',', $allItems); // e.g. "3,7,12"

            // Step 4: SQL-based duplicate check using GROUP_CONCAT
            $duplicate = $SpItemGroupModel
                ->select("sp_ig_group_seq, GROUP_CONCAT(DISTINCT sp_ig_spim_id ORDER BY sp_ig_spim_id) AS items")
                ->where('sp_ig_delete_flag', 0)
                ->groupBy('sp_ig_group_seq')
                ->having('items', $itemListStr)
                ->first();

            if ($duplicate) {
                $response = [
                    'ret_data' => 'duplicate',
                    'message' => "Duplicate group already exists (Group No: {$duplicate['sp_ig_group_seq']})."
                ];
                return $this->respond($response, 200);
            }

            // Step 5: Prepare insertData for batch insert
            $insertData = [];

            if (! empty($parts) && is_array($parts)) {
                foreach ($parts as $partId) {
                    $insertData[] = [
                        'sp_ig_spim_id'     => $partId,
                        'sp_ig_group_seq'   => $groupSeq,
                        'sp_ig_created_by'  => $user_id,
                        'sp_ig_updated_by'  => $user_id,
                        'sp_ig_created_on' => $now,
                        'sp_ig_updated_on' => $now,
                        'sp_ig_delete_flag' => 0,
                    ];
                }
            }

            if (! empty($consumables) && is_array($consumables)) {
                foreach ($consumables as $consumableId) {
                    $insertData[] = [
                        'sp_ig_spim_id'     => $consumableId,
                        'sp_ig_group_seq'   => $groupSeq,
                        'sp_ig_created_by'  => $user_id,
                        'sp_ig_updated_by'  => $user_id,
                        'sp_ig_created_on' => $now,
                        'sp_ig_updated_on' => $now,
                        'sp_ig_delete_flag' => 0,
                    ];
                }
            }

            if (! empty($operation) && is_array($operation)) {
                foreach ($operation as $op) {
                    $insertData[] = [
                        'sp_ig_spim_id'     => $op,
                        'sp_ig_group_seq'   => $groupSeq,
                        'sp_ig_created_by'  => $user_id,
                        'sp_ig_updated_by'  => $user_id,
                        'sp_ig_created_on' => $now,
                        'sp_ig_updated_on' => $now,
                        'sp_ig_delete_flag' => 0,
                    ];
                }
            }

            // Step 6: DB transaction & batch insert
            $this->db->transStart();
            if (! empty($insertData)) {
                $SpItemGroupModel->insertBatch($insertData);
            }
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return $this->respond(['ret_data' => 'fail'], 200);
            } else {
                $this->db->transCommit();
                return $this->respond(['ret_data' => 'success'], 200);
            }
        } else {
            return $this->respond(['ret_data' => 'unauthorized'], 401);
        }
    }
    // public function createItemGroup()
    // {

    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $tokendata['uid'])->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata) {
    //         $SPItemMaster = new SPItemMaster();

    //         $builder = $this->db->table('sequence_data');
    //         $builder->selectMax('sp_group_no');
    //         $query = $builder->get();
    //         $row = $query->getRow();
    //         $groupNo = $row->sp_group_no;
    //         $seqvalfinal = $row->sp_group_no;


    //         $parts = $this->request->getVar('groupPartsId');
    //         $consumables = $this->request->getVar('groupConsumables');
    //         $operation = $this->request->getVar('groupOperation');
    //         $now = date('Y-m-d H:i:s');
    //         $user_id = $this->request->getVar('user_id');


    //         $this->db->transStart();

    //         $updateItems = [];

    //         // Add Parts
    //         if (!empty($parts) && is_array($parts)) {
    //             foreach ($parts as $partId) {
    //                 $updateItems[] = [
    //                     'spim_id' => $partId, // Primary key
    //                     'spim_group_seq' => $groupNo,
    //                     'spim_updated_on' => $now,
    //                     'spim_updated_by' => $user_id
    //                 ];
    //             }
    //         }

    //         // Add Consumables
    //         if (!empty($consumables) && is_array($consumables)) {
    //             foreach ($consumables as $consumableId) {
    //                 $updateItems[] = [
    //                     'spim_id' => $consumableId,
    //                     'spim_group_seq' => $groupNo,
    //                     'spim_updated_on' => $now,
    //                     'spim_updated_by' => $user_id
    //                 ];
    //             }
    //         }

    //         // Add Operation
    //         if (!empty($operation)) {
    //             $updateItems[] = [
    //                 'spim_id' => $operation,
    //                 'spim_group_seq' => $groupNo,
    //                 'spim_updated_on' => $now,
    //                 'spim_updated_by' => $user_id
    //             ];
    //         }


    //         $SPItemMaster->updateBatch($updateItems, 'spim_id');

    //         $this->db->transComplete();



    //         if ($this->db->transStatus() === false) {
    //             $this->db->transRollback();
    //             $response['ret_data'] = "fail";
    //             return $this->respond($response, 200);
    //         } else {
    //             $this->db->transCommit();
    //             $builder = $this->db->table('sequence_data');
    //             $builder->set('sp_group_no', ++$seqvalfinal);
    //             $builder->update();

    //             $response = [
    //                 'ret_data' => 'success',
    //             ];
    //             return $this->respond($response, 200);
    //         }
    //     }
    // }

    public function CheckItemHasGroup()
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
            $SPItemMaster = new SPItemMaster();

            // Get inputs
            $parts = $this->request->getVar('parts');             // can be array or single ID
            $consumables = $this->request->getVar('consumables'); // can be array or single ID
            $operation = $this->request->getVar('operation');     // single ID or null

            // Normalize parts and consumables to arrays
            $parts = is_array($parts) ? $parts : ($parts !== null ? [$parts] : []);
            $consumables = is_array($consumables) ? $consumables : ($consumables !== null ? [$consumables] : []);

            $allIds = array_merge($parts, $consumables);

            // ✅ Check operation directly if provided
            if (!empty($operation)) {
                $opGrouped = $SPItemMaster
                    ->where('spim_id', $operation)
                    ->where('spim_group_seq !=', 0)
                    ->countAllResults();

                if ($opGrouped > 0) {
                    return $this->respond(['ret_data' => 'success'], 200);
                }
            }

            // ✅ Check parts + consumables
            if (!empty($allIds)) {
                $count = $SPItemMaster
                    ->whereIn('spim_id', $allIds)
                    ->where('spim_group_seq !=', 0)
                    ->countAllResults();

                if ($count > 0) {
                    return $this->respond(['ret_data' => 'success'], 200);
                }
            }
        }
    }

    public function getSPItemsById()
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

            $SPItemMaster = new SPItemMaster();

            $SPSItems = $SPItemMaster->where("spim_delete_flag", 0)->where("spim_id", $this->request->getVar('spim_id'),)
                ->first();

            if ($SPSItems) {
                $response = [
                    'ret_data' => 'success',
                    'SPSItems' => $SPSItems,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function updateSPGroupById()
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

            $SPItemMaster = new SPItemMaster();

            $spim_id = $this->request->getVar('spim_id');

            $updateItemData = [
                'spim_id'       => $this->request->getVar('spim_id'),
                'spim_group_seq' => $this->request->getVar('spim_group_seq'),
            ];

            if ($spim_id) {
                $servicePackage =  $SPItemMaster->update($spim_id, $updateItemData);
            }


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

    public function checkDuplicateModelCode()
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $kmModel = new KilometerMasterModel();
            $SP_Parts_Model = new ServicePackageSpareModel();
            $SP_Labours_Model = new ServicePackageLabourModel();
            $ServicePackageEnginesModel = new ServicePackageEnginesModel();
            $Sp_KmPrice_Model = new ServicePackageKmPriceModel();
            $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();

            $modelYear = $this->request->getVar('modelYear');
            $variant = $this->request->getVar('variant');

            $modelData = $ServicePackageModelCodeModel
                ->where('spmc_delete_flag', 0)
                ->where('spmc_model_year', $modelYear)
                ->where('spmc_variant', $variant)
                ->findAll();

            if (count($modelData) > 1) {
                return $this->respond([
                    'ret_data' => 'success',
                    'duplicate_flag' => 1,
                ], 200);
            } else {

                $modelData = $ServicePackageModelCodeModel
                    ->where('spmc_delete_flag', 0)
                    ->where('spmc_model_year', $modelYear)
                    ->where('spmc_variant', $variant)
                    ->first();

                if (!empty($modelData)) {
                    $modelLabourData = $ServicePackageModelCodeLabourModel
                        ->where('model_code', $modelData['spmc_value'])
                        ->where('spmcl_delete_flag', 0)
                        ->first();
                }
                $labourFactor = 0;

                if (!empty($modelLabourData)) {
                    $labourRate = (float) $modelLabourData['labour_rate'];
                    $increasePct = (float) $modelLabourData['spmcl_inc_pct'];

                    $labourFactor = $labourRate + ($labourRate * $increasePct / 100);
                }

                if (!empty($modelData)) {

                    $model_id = $modelData['spmc_id'];

                    $engineDetails = $ServicePackageEnginesModel->select('eng_id,eng_no,speng_spmc_id,eng_labour_factor')
                        ->where("speng_delete_flag", 0)
                        ->where("speng_spmc_id", $model_id)
                        ->join('engine_master', 'engine_master.eng_id = speng_eng_id', 'left')
                        ->first();


                    // Fetch price map for each km
                    $kmPriceMap = $Sp_KmPrice_Model->table('sp_km_price_map')
                        ->select('spkmp_spkm_id, spkmp_markup_price, spkmp_display_price')
                        ->where('spkmp_spmc_id', $model_id)
                        ->get()
                        ->getResultArray();

                    $kmPriceMapById = [];
                    foreach ($kmPriceMap as $row) {
                        $kmPriceMapById[$row['spkmp_spkm_id']] = [
                            'markup_price' => $row['spkmp_markup_price'],
                            'display_price' => $row['spkmp_display_price'],
                        ];
                    }

                    // Get Spares
                    $spares = $SP_Parts_Model
                        ->select('spim_name, pm_price, sp_spare_qty,pm_code,sp_spare_category,spkm_km_optional_flag, sp_spare_id, sp_spare_optional_flag, spkm_km_id,sp_spare_group_seq, sp_spare_labour_unit, km_value')
                        ->where('sp_spare_spmc_id', $model_id)
                        ->where('sp_spare_delete_flag', 0)
                        ->join('parts_master', 'parts_master.pm_id = sp_spare_pm_id', 'left')
                        ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
                        ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                        ->join('sp_km_item_map', 'sp_km_item_map.spkm_item_id = sp_spare_id AND sp_km_item_map.spkm_item_type = 0 AND sp_km_item_map.spkm_delete_flag = 0', 'left')
                        ->join('kilometer_master', 'kilometer_master.km_id = spkm_km_id', 'left')
                        ->findAll();

                    // Get Labours
                    $labours = $SP_Labours_Model
                        ->select('spim_name, sp_pm_category, sp_labour_qty,spkm_km_optional_flag, sp_labour_id, sp_labour_optional_flag,sp_labour_group_seq, spkm_km_id, sp_labour_unit, km_value')
                        ->where('sp_labour_spmc_id', $model_id)
                        ->where('sp_labour_delete_flag', 0)
                        ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = sp_labour_lm_id', 'left')
                        ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                        ->join(
                            'sp_km_item_map',
                            'sp_km_item_map.spkm_item_id = sp_labour_id AND sp_km_item_map.spkm_item_type = 1 AND sp_km_item_map.spkm_delete_flag = 0',
                            'left'
                        )
                        ->join('kilometer_master', 'kilometer_master.km_id = spkm_km_id', 'left')
                        ->findAll();

                    // Combine and group by km_id
                    $combinedByKm = [];

                    foreach ($spares as $spare) {
                        if (!empty($spare['spkm_km_id'])) {
                            $km_id = $spare['spkm_km_id'];
                            $spare['item_type'] = 0;
                            $combinedByKm[$km_id]['items'][] = $spare;
                            $combinedByKm[$km_id]['km_value'] = $spare['km_value'];
                        }
                    }

                    foreach ($labours as $labour) {
                        if (!empty($labour['spkm_km_id'])) {
                            $km_id = $labour['spkm_km_id'];
                            $labour['item_type'] = 1;
                            $combinedByKm[$km_id]['items'][] = $labour;
                            $combinedByKm[$km_id]['km_value'] = $labour['km_value'];
                        }
                    }

                    // Final structure
                    $final = [];
                    foreach ($combinedByKm as $km_id => $data) {
                        $final[] = [
                            'km_id' => $km_id,
                            'km_value' => $data['km_value'] ?? '',
                            'actual_price' => $kmPriceMapById[$km_id]['markup_price'] ?? 0,
                            'display_price' => $kmPriceMapById[$km_id]['display_price'] ?? 0,
                            'items' => $data['items'],
                        ];
                    }
                } else {
                    $final = [];
                }


                if (!empty($final)) {
                    $response = [
                        'ret_data' => 'success',
                        'servicePackage' => $final,
                        'engineDetails' => $engineDetails,
                        'labourFactor' => $labourFactor,
                        'duplicate_flag' => 0,
                        'modelId' => $modelData['spmc_id'],
                    ];
                } else if (!empty($modelData)) {
                    $response = [
                        'ret_data' => 'fail',
                        'modelData' => $modelData,
                        'duplicate_flag' => 0,
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


    public function getServicePackageByVin()
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $kmModel = new KilometerMasterModel();
            $SP_Parts_Model = new ServicePackageSpareModel();
            $SP_Labours_Model = new ServicePackageLabourModel();
            $ServicePackageEnginesModel = new ServicePackageEnginesModel();
            $Sp_KmPrice_Model = new ServicePackageKmPriceModel();
            $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();

            $vinNo = $this->request->getVar('vin');
            $model_id = null;
            $modelData = $ServicePackageModelCodeModel
                ->where('spmc_delete_flag', 0)
                ->where('spmc_vin_no', $vinNo)
                ->first();


            if (!empty($modelData)) {
                $modelLabourData = $ServicePackageModelCodeLabourModel
                    ->where('model_code', $modelData['spmc_value'])
                    ->where('spmcl_delete_flag', 0)
                    ->first();
            }
            $labourFactor = 0;

            if (!empty($modelLabourData)) {
                $labourRate = (float) $modelLabourData['labour_rate'];
                $increasePct = (float) $modelLabourData['spmcl_inc_pct'];

                $labourFactor = $labourRate + ($labourRate * $increasePct / 100);
            }

            if (!empty($modelData)) {

                $model_id = $modelData['spmc_id'];

                $engineDetails = $ServicePackageEnginesModel->select('eng_id,eng_no,speng_spmc_id,eng_labour_factor')
                    ->where("speng_delete_flag", 0)
                    ->where("speng_spmc_id", $model_id)
                    ->join('engine_master', 'engine_master.eng_id = speng_eng_id', 'left')
                    ->first();


                // Fetch price map for each km
                $kmPriceMap = $Sp_KmPrice_Model->table('sp_km_price_map')
                    ->select('spkmp_spkm_id, spkmp_markup_price, spkmp_display_price')
                    ->where('spkmp_spmc_id', $model_id)
                    ->get()
                    ->getResultArray();

                $kmPriceMapById = [];
                foreach ($kmPriceMap as $row) {
                    $kmPriceMapById[$row['spkmp_spkm_id']] = [
                        'markup_price' => $row['spkmp_markup_price'],
                        'display_price' => $row['spkmp_display_price'],
                    ];
                }

                // Get Spares
                $spares = $SP_Parts_Model
                    ->select('spim_name, pm_price, sp_spare_qty,spkm_km_optional_flag, sp_spare_id, sp_spare_optional_flag, spkm_km_id,sp_spare_group_seq, sp_spare_labour_unit, km_value')
                    ->where('sp_spare_spmc_id', $model_id)
                    ->where('sp_spare_delete_flag', 0)
                    ->join('parts_master', 'parts_master.pm_id = sp_spare_pm_id', 'left')
                    ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
                    ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                    ->join('sp_km_item_map', 'sp_km_item_map.spkm_item_id = sp_spare_id AND sp_km_item_map.spkm_item_type = 0 AND sp_km_item_map.spkm_delete_flag = 0', 'left')
                    ->join('kilometer_master', 'kilometer_master.km_id = spkm_km_id', 'left')
                    ->findAll();

                // Get Labours
                $labours = $SP_Labours_Model
                    ->select('spim_name, sp_pm_category, sp_labour_qty,spkm_km_optional_flag, sp_labour_id, sp_labour_optional_flag,sp_labour_group_seq, spkm_km_id, sp_labour_unit, km_value')
                    ->where('sp_labour_spmc_id', $model_id)
                    ->where('sp_labour_delete_flag', 0)
                    ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = sp_labour_lm_id', 'left')
                    ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                    ->join(
                        'sp_km_item_map',
                        'sp_km_item_map.spkm_item_id = sp_labour_id AND sp_km_item_map.spkm_item_type = 1 AND sp_km_item_map.spkm_delete_flag = 0',
                        'left'
                    )
                    ->join('kilometer_master', 'kilometer_master.km_id = spkm_km_id', 'left')
                    ->findAll();

                // Combine and group by km_id
                $combinedByKm = [];

                foreach ($spares as $spare) {
                    if (!empty($spare['spkm_km_id'])) {
                        $km_id = $spare['spkm_km_id'];
                        $spare['item_type'] = 0;
                        $combinedByKm[$km_id]['items'][] = $spare;
                        $combinedByKm[$km_id]['km_value'] = $spare['km_value'];
                    }
                }

                foreach ($labours as $labour) {
                    if (!empty($labour['spkm_km_id'])) {
                        $km_id = $labour['spkm_km_id'];
                        $labour['item_type'] = 1;
                        $combinedByKm[$km_id]['items'][] = $labour;
                        $combinedByKm[$km_id]['km_value'] = $labour['km_value'];
                    }
                }

                // Final structure
                $final = [];
                foreach ($combinedByKm as $km_id => $data) {
                    $final[] = [
                        'km_id' => $km_id,
                        'km_value' => $data['km_value'] ?? '',
                        'actual_price' => $kmPriceMapById[$km_id]['markup_price'] ?? 0,
                        'display_price' => $kmPriceMapById[$km_id]['display_price'] ?? 0,
                        'items' => $data['items'],
                    ];
                }
            } else {
                $final = [];
            }


            if (!empty($final)) {
                $response = [
                    'ret_data' => 'success',
                    'servicePackage' => $final,
                    'engineDetails' => $engineDetails,
                    'labourFactor' => $labourFactor,
                    'modelId' => $modelData['spmc_id'],
                ];
            } else if (!empty($modelData)) {
                $response = [
                    'ret_data' => 'success',
                    'modelData' => $modelData,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getSpareForEngineNo()
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

            $ServicePackageEnginesModel = new ServicePackageEnginesModel();
            $SP_Labours_Model = new ServicePackageLabourModel();
            $eng_id = $this->request->getVar('eng_id');

            $consumableList = $ServicePackageEnginesModel->where("speng_delete_flag", 0)
                ->where("speng_eng_id", $eng_id)
                ->join('sp_model_code', 'sp_model_code.spmc_id=speng_spmc_id', 'left')
                ->join('sp_spares', 'sp_spares.sp_spare_spmc_id=speng_spmc_id', 'left')
                ->join('parts_master', 'parts_master.pm_id=sp_spares.sp_spare_pm_id', 'left')
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                ->join('brand_list', 'brand_list.brand_id=pm_brand', 'left')
                ->where("sp_spare_category", 1)
                ->where("sp_spare_applicable", 1)
                ->where('sp_model_code.spmc_status_flag', 5)
                ->groupby('sp_spare_id')
                ->findAll();

            $labourList = $ServicePackageEnginesModel->where("speng_delete_flag", 0)
                ->where("speng_eng_id", $eng_id)
                ->join('sp_model_code', 'sp_model_code.spmc_id=speng_spmc_id', 'left')
                ->join('sp_labours', 'sp_labours.sp_labour_spmc_id=speng_spmc_id', 'left')
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id=sp_labours.sp_labour_lm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_parts_master.sp_pm_spim_id', 'left')
                ->join('brand_list', 'brand_list.brand_id=sp_pm_brand', 'left')
                ->groupby('sp_labour_id')
                ->where('sp_model_code.spmc_status_flag', 5)
                ->where('sp_labours.sp_labour_applicable', 1)
                ->where('sp_labours.sp_labour_delete_flag', 0)
                ->findAll();

            $partsList = [
                'consumables' => $consumableList,
                'labours'     => $labourList,
            ];

            if ($partsList) {
                $response = [
                    'ret_data' => 'success',
                    'partsList' => $partsList,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    // public function checkEngineHasSameSPItems()
    // {
    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $tokendata['uid'])->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }

    //     if ($tokendata) {
    //         $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
    //         $ServicePackageEnginesModel   = new ServicePackageEnginesModel();
    //         $SP_Parts_Model               = new ServicePackageSpareModel();
    //         $SP_Labours_Model              = new ServicePackageLabourModel();
    //         $ServicePackageKMItemMap       = new ServicePackageKMItemMap();

    //         $eng_id       = $this->request->getVar('eng_id');
    //         $payloadItems = $this->request->getVar('items');
    //         $limit = (int) $this->request->getVar('limit') ?? 8;
    //         $offset = (int) $this->request->getVar('offset') ?? 0;


    //         if (!is_array($payloadItems)) {
    //             return $this->fail("Invalid payload items", 400);
    //         }

    //         $matches = [];
    //         $addedCount = 0;

    //         $modelCodes = $ServicePackageEnginesModel
    //             ->select('speng_spmc_id, spmc_value,spmc_model_year')
    //             ->join('sp_model_code', 'sp_model_code.spmc_id = speng_spmc_id')
    //             ->where('speng_eng_id', $eng_id)
    //             ->where('speng_delete_flag', 0)
    //             ->where('spmc_delete_flag', 0)
    //             ->where('spmc_status_flag', 5)
    //             ->findAll();

    //         // log_message('error', "Total Model Codes Found for Engine ID $eng_id: " . count($modelCodes));

    //         foreach ($modelCodes as $model) {
    //             if ($addedCount >=  $limit) break; // stop after current page

    //             $model_id = $model['speng_spmc_id'];
    //             // log_message('error', "Processing Model Code: [{$model['spmc_value']}] (ID: $model_id)");

    //             // Fetch Spares
    //             $spares = $SP_Parts_Model
    //                 ->select('spim_name, sp_spare_id, sp_spare_group_seq, sp_spare_labour_unit, sp_spare_optional_flag, sp_km_item_map.spkm_km_id, sp_km_item_map.spkm_km_optional_flag')
    //                 ->join('parts_master', 'parts_master.pm_id = sp_spare_pm_id', 'left')
    //                 ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
    //                 ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
    //                 ->join('sp_km_item_map', 'sp_km_item_map.spkm_item_id = sp_spare_id AND spkm_item_type = 0 AND spkm_delete_flag = 0', 'left')
    //                 ->where('sp_spare_spmc_id', $model_id)
    //                 ->where('sp_spare_delete_flag', 0)
    //                 ->findAll();

    //             // Fetch Labours
    //             $labours = $SP_Labours_Model
    //                 ->select('sp_labour_id, sp_labour_group_seq, sp_labour_unit, sp_labour_optional_flag, spim_name, sp_km_item_map.spkm_km_id, sp_km_item_map.spkm_km_optional_flag')
    //                 ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = sp_labour_lm_id', 'left')
    //                 ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
    //                 ->join('sp_km_item_map', 'sp_km_item_map.spkm_item_id = sp_labour_id AND spkm_item_type = 1 AND spkm_delete_flag = 0', 'left')
    //                 ->where('sp_labour_spmc_id', $model_id)
    //                 ->where('sp_labour_delete_flag', 0)
    //                 ->findAll();

    //             $sparesGrouped = [];
    //             foreach ($spares as $sp) {
    //                 $name = $this->cleanString($sp['spim_name']);
    //                 if (!isset($sparesGrouped[$name])) {
    //                     $sparesGrouped[$name] = [
    //                         'selectedKmIds' => [],
    //                         'spkm_km_optional_flag' => [],
    //                     ];
    //                 }

    //                 if (!is_null($sp['spkm_km_id'])) {
    //                     $sparesGrouped[$name]['selectedKmIds'][] = $sp['spkm_km_id'];
    //                     $sparesGrouped[$name]['spkm_km_optional_flag'][$sp['spkm_km_id']] = (int)$sp['spkm_km_optional_flag'];
    //                 } else {
    //                     // log_message('error', "Spare Item [$sp[spim_name]] has no KM Mapping (spkm_km_id is NULL)");
    //                 }
    //             }

    //             $laboursGrouped = [];
    //             foreach ($labours as $lb) {
    //                 $name = $this->cleanString($lb['spim_name']);
    //                 if (!isset($laboursGrouped[$name])) {
    //                     $laboursGrouped[$name] = [
    //                         'selectedKmIds' => [],
    //                         'spkm_km_optional_flag' => [],
    //                     ];
    //                 }

    //                 if (!is_null($lb['spkm_km_id'])) {
    //                     $laboursGrouped[$name]['selectedKmIds'][] = $lb['spkm_km_id'];
    //                     $laboursGrouped[$name]['spkm_km_optional_flag'][$lb['spkm_km_id']] = (int)$lb['spkm_km_optional_flag'];
    //                 } else {
    //                     // log_message('error', "Labour Item [$lb[spim_name]] has no KM Mapping (spkm_km_id is NULL)");
    //                 }
    //             }

    //             // // Log all grouped names for debugging
    //             // foreach (array_keys($sparesGrouped) as $name) {
    //             //     log_message('error', "Available Spare Group Name: [$name]");
    //             // }
    //             // foreach (array_keys($laboursGrouped) as $name) {
    //             //     log_message('error', "Available Labour Group Name: [$name]");
    //             // }

    //             $finalItems = [];
    //             foreach ($payloadItems as $item) {
    //                 $type = (string)$item->type;
    //                 $name = $this->cleanString((string)$item->name);

    //                 $group = ($type === '0') ? ($sparesGrouped[$name] ?? null) : ($laboursGrouped[$name] ?? null);

    //                 // // Debug Logs — Matching Attempt
    //                 // log_message('error', "Matching Payload Name: [$name] Type: $type | Exists in Spare? " . (isset($sparesGrouped[$name]) ? 'Yes' : 'No') . " | Exists in Labour? " . (isset($laboursGrouped[$name]) ? 'Yes' : 'No'));

    //                 // if ($group) {
    //                 //     log_message('error', "Matched KM Mapping for Name: [$name] => " . json_encode($group));
    //                 // } else {
    //                 //     log_message('error', "No KM Mapping found for Name: [$name]");
    //                 // }

    //                 $item->selectedKmIds = $group['selectedKmIds'] ?? [];
    //                 $item->spkm_km_optional_flag = $group['spkm_km_optional_flag'] ?? [];
    //                 $item->customerSelectedKmIds = array_keys(array_filter($item->spkm_km_optional_flag, fn($v) => $v === 1));

    //                 $finalItems[] = clone $item;
    //             }

    //             $hasMapping = array_filter($finalItems, fn($it) => !empty($it->selectedKmIds));
    //             if (count($hasMapping) > 0) {
    //                 $addedCount++;
    //                 log_message("error",$addedCount);
    //                 if ($addedCount > $offset) {
    //                     $matches[] = [
    //                         'modelcode' => $model['spmc_value'],
    //                         'spmc_model_year' => $model['spmc_model_year'],
    //                         'engine_id' => $eng_id,
    //                         'items'     => $finalItems,
    //                     ];
    //                 }
    //             }
    //         }

    //         // Final Response
    //         if (!empty($matches)) {
    //             // log_message('error', "Matched Models Found: " . json_encode(array_column($matches, 'modelcode')));
    //             return $this->response->setJSON([
    //                 'ret_data' => 'success',
    //                 'matches'  => $matches,
    //             ]);
    //         } else {
    //             // log_message('error', 'No Service Package models matched for Engine ID: ' . $eng_id);
    //             return $this->response->setJSON([
    //                 'ret_data' => 'fail',
    //                 'message'  => 'No matched service package found',
    //                 'matches'  => [],
    //             ]);
    //         }
    //     }
    // }
    public function checkEngineHasSameSPItems()
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
            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $ServicePackageEnginesModel   = new ServicePackageEnginesModel();
            $SP_Parts_Model               = new ServicePackageSpareModel();
            $SP_Labours_Model             = new ServicePackageLabourModel();
            $ServicePackageKMItemMap      = new ServicePackageKMItemMap();

            $eng_id       = $this->request->getVar('eng_id');
            $payloadItems = $this->request->getVar('items');

            // Properly parse limit & offset with defaults
            $rawLimit = $this->request->getVar('limit');
            $rawOffset = $this->request->getVar('offset');

            $limit = (is_null($rawLimit) || $rawLimit === '') ? 8 : (int)$rawLimit;
            $offset = (is_null($rawOffset) || $rawOffset === '') ? 0 : (int)$rawOffset;
            if ($limit < 1) $limit = 8;
            if ($offset < 0) $offset = 0;

            if (!is_array($payloadItems)) {
                return $this->fail("Invalid payload items", 400);
            }

            $matches = [];
            // counters:
            // $foundCount = how many matched models we've encountered (used to apply offset)
            // $collected = how many items we've pushed to $matches (stop when reaches $limit)
            $foundCount = 0;
            $collected = 0;

            $modelCodes = $ServicePackageEnginesModel
                ->select('speng_spmc_id, spmc_value,spmc_model_year')
                ->join('sp_model_code', 'sp_model_code.spmc_id = speng_spmc_id')
                ->where('speng_eng_id', $eng_id)
                ->where('speng_delete_flag', 0)
                ->where('spmc_delete_flag', 0)
                ->where('spmc_status_flag', 5)
                ->findAll();

            foreach ($modelCodes as $model) {
                // If we've already collected required number of matches, stop.
                if ($collected >= $limit) break;

                $model_id = $model['speng_spmc_id'];

                // Fetch Spares
                $spares = $SP_Parts_Model
                    ->select('spim_name, sp_spare_id, sp_spare_group_seq, sp_spare_labour_unit, sp_spare_optional_flag, sp_km_item_map.spkm_km_id, sp_km_item_map.spkm_km_optional_flag')
                    ->join('parts_master', 'parts_master.pm_id = sp_spare_pm_id', 'left')
                    ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
                    ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                    ->join('sp_km_item_map', 'sp_km_item_map.spkm_item_id = sp_spare_id AND spkm_item_type = 0 AND spkm_delete_flag = 0', 'left')
                    ->where('sp_spare_spmc_id', $model_id)
                    ->where('sp_spare_delete_flag', 0)
                    ->findAll();

                // Fetch Labours
                $labours = $SP_Labours_Model
                    ->select('sp_labour_id, sp_labour_group_seq, sp_labour_unit, sp_labour_optional_flag, spim_name, sp_km_item_map.spkm_km_id, sp_km_item_map.spkm_km_optional_flag')
                    ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = sp_labour_lm_id', 'left')
                    ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                    ->join('sp_km_item_map', 'sp_km_item_map.spkm_item_id = sp_labour_id AND spkm_item_type = 1 AND spkm_delete_flag = 0', 'left')
                    ->where('sp_labour_spmc_id', $model_id)
                    ->where('sp_labour_delete_flag', 0)
                    ->findAll();

                $sparesGrouped = [];
                foreach ($spares as $sp) {
                    $name = $this->cleanString($sp['spim_name']);
                    if (!isset($sparesGrouped[$name])) {
                        $sparesGrouped[$name] = [
                            'selectedKmIds' => [],
                            'spkm_km_optional_flag' => [],
                        ];
                    }

                    if (!is_null($sp['spkm_km_id'])) {
                        $sparesGrouped[$name]['selectedKmIds'][] = $sp['spkm_km_id'];
                        $sparesGrouped[$name]['spkm_km_optional_flag'][$sp['spkm_km_id']] = (int)$sp['spkm_km_optional_flag'];
                    }
                }

                $laboursGrouped = [];
                foreach ($labours as $lb) {
                    $name = $this->cleanString($lb['spim_name']);
                    if (!isset($laboursGrouped[$name])) {
                        $laboursGrouped[$name] = [
                            'selectedKmIds' => [],
                            'spkm_km_optional_flag' => [],
                        ];
                    }

                    if (!is_null($lb['spkm_km_id'])) {
                        $laboursGrouped[$name]['selectedKmIds'][] = $lb['spkm_km_id'];
                        $laboursGrouped[$name]['spkm_km_optional_flag'][$lb['spkm_km_id']] = (int)$lb['spkm_km_optional_flag'];
                    }
                }

                $finalItems = [];
                foreach ($payloadItems as $item) {
                    $type = (string)$item->type;
                    $name = $this->cleanString((string)$item->name);

                    $group = ($type === '0') ? ($sparesGrouped[$name] ?? null) : ($laboursGrouped[$name] ?? null);

                    $item->selectedKmIds = $group['selectedKmIds'] ?? [];
                    $item->spkm_km_optional_flag = $group['spkm_km_optional_flag'] ?? [];
                    $item->customerSelectedKmIds = array_keys(array_filter($item->spkm_km_optional_flag, fn($v) => $v === 1));

                    $finalItems[] = clone $item;
                }

                // Determine if this model has at least one item with mapping
                $hasMapping = array_filter($finalItems, fn($it) => !empty($it->selectedKmIds));
                if (count($hasMapping) > 0) {
                    // One more matched model encountered
                    $foundCount++;

                    // If this matched model is still within the offset skip, continue.
                    if ($foundCount <= $offset) {
                        // still skipping due to offset
                        continue;
                    }

                    // Push to results because we are past offset and haven't reached limit yet
                    $matches[] = [
                        'modelcode' => $model['spmc_value'],
                        'spmc_model_year' => $model['spmc_model_year'],
                        'engine_id' => $eng_id,
                        'items'     => $finalItems,
                    ];

                    $collected++;

                    // If collected enough for this page, break out.
                    if ($collected >= $limit) {
                        break;
                    }
                }
            }

            // Final Response
            if (!empty($matches)) {
                return $this->response->setJSON([
                    'ret_data' => 'success',
                    'matches'  => $matches,
                ]);
            } else {
                return $this->response->setJSON([
                    'ret_data' => 'fail',
                    'message'  => 'No matched service package found',
                    'matches'  => [],
                ]);
            }
        }
    }


    public function returnToSupervisor()
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
            $SPModelCodeModel = new ServicePackageModelCodeModel();
            $SpLogTable = new ServicePackageLogModel();

            $modelCode = $this->request->getVar('modelcode');
            $getType = $this->request->getVar('returnType');

            $status = $SPModelCodeModel->select('spmc_id,spmc_status_flag')->where('spmc_delete_flag', 0)
                ->where('spmc_value', $modelCode)
                ->first();


            //1- return to part advsiro and 2 for return to supervisor
            if ($getType == '2') {

                $selectedLabour = $this->request->getVar('selectLabourName');
                $note = $this->request->getVar('note');

                if (is_array($selectedLabour)) {
                    $partNames = implode(', ', $selectedLabour);
                } else {
                    $partNames = $selectedLabour;
                }

                $insertData = [
                    'spmc_value' => $modelCode,
                    'spmc_status_flag' => in_array($status['spmc_status_flag'], [7, 8]) ? 9 : 8,
                    'spmc_sv_return_note' => "Add : {$partNames}.{$note}",
                ];
            } else if ($getType == '1') {

                $selectedLabour = $this->request->getVar('selectpartsName');
                $note = $this->request->getVar('note');

                if (is_array($selectedLabour)) {
                    $partNames = implode(', ', $selectedLabour);
                } else {
                    $partNames = $selectedLabour;
                }

                $insertData = [
                    'spmc_value' => $modelCode,
                    'spmc_status_flag' => in_array($status['spmc_status_flag'], [7, 8]) ? 9 : 7,
                    'spmc_pa_return_note' => "Add parts: {$partNames}. {$note}",
                ];
            }
            $servicePackage = $SPModelCodeModel->where('spmc_value', $modelCode)->set($insertData)->update();

            $action = $getType == '2' ? 'Supervisor' : 'Part Advisor';
            $itemsLabel = $getType == '2' ? 'Labour(s)' : 'Part(s)';
            $items = $partNames ?: 'None';
            $noteText = $note ?: '';

            $logNote = "Service package for Model Code {$modelCode} was returned to {$action}. "
                . "Requested to add {$itemsLabel}: {$items}. "
                . "Note: {$noteText}. ";

            $logData = [
                'sp_log_spmc_id'    => $status['spmc_id'],
                'sp_log_notes'      => $logNote,
                'sp_log_created_by' => $tokendata['uid'] ?? null,
                'sp_log_created_on' => date("Y-m-d H:i:s"),
            ];

            $SpLogTable->insert($logData);


            if (!empty($servicePackage)) {
                return $this->response->setJSON([
                    'ret_data' => 'success',
                    'servicePackage'  => $servicePackage,
                ]);
            } else {
                return $this->response->setJSON([
                    'ret_data'   => 'fail',
                ]);
            }
            return $this->respond($response, 200);
        }
    }

    public function getServicePackageByModelCode()
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();

            $modelCode = $this->request->getVar('modelCode');

            $requestedServicePackage = $ServicePackageModelCodeModel
                ->where("spmc_delete_flag", 0)
                ->where("spmc_value", $modelCode)
                ->join('users as creator', 'creator.us_id = spmc_created_by', 'left')
                ->join('users as updater', 'updater.us_id = spmc_updated_by', 'left')
                ->select('sp_model_code.*, creator.us_firstname as created_by_name, updater.us_firstname as updated_by_name')
                ->findAll();

            if ($requestedServicePackage) {
                $response = [
                    'ret_data' => 'success',
                    'requestedServicePackage' => $requestedServicePackage,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getModelcodeList()
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

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
            $ServicePackageKmPriceModel = new ServicePackageKmPriceModel();
            $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();

            $modellist = $ServicePackageModelCodeModel->select('spmc_value,spmc_id,spmc_model_year,model_name,eng_no,spkmp_display_price')
                ->where('spmc_status_flag', 5)
                ->where('spmc_delete_flag', 0)
                ->join('sp_model_code_labour', 'sp_model_code_labour.model_code = spmc_value', 'left')
                ->join('sp_km_price_map', 'sp_km_price_map.spkmp_spmc_id = spmc_id', 'left')
                ->join('sp_engines', 'sp_engines.speng_spmc_id = spmc_id', 'left')
                ->join('engine_master', 'engine_master.eng_id = speng_eng_id', 'left')
                ->groupBy('spmc_id')
                ->findAll();

            if ($modellist) {
                $response = [
                    'ret_data' => 'success',
                    'modellist' => $modellist,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }

            return $this->respond($response, 200);
        }
    }

    public function getItemGroup()
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

            $itemGroup = new ServiceItemGroupModel();
            $spItemMaster = new SPItemMaster();

            $groups = $itemGroup->select('sp_item_group.sp_ig_group_seq, sp_item_master.spim_name')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_item_group.sp_ig_spim_id')
                ->orderBy('sp_item_group.sp_ig_group_seq')
                ->where('sp_ig_delete_flag', 0)
                ->findAll();


            $temp = [];
            foreach ($groups as $row) {
                $temp[$row['sp_ig_group_seq']][] = $row['spim_name'];
            }

            $groupedItems = [];
            foreach ($temp as $groupSeq => $names) {
                $groupedItems[] = [
                    'sp_ig_group_seq' => $groupSeq,
                    'spim_name' => implode(', ', $names)
                ];
            }

            if ($groupedItems) {
                $response = [
                    'ret_data' => 'success',
                    'groupedItems' => $groupedItems,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }

            return $this->respond($response, 200);
        }
    }

    public function getGroupsByPmId()
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
            $spID = $this->request->getVar('sp_pm_id');

            $itemGroup = new ServiceItemGroupModel();

            $groupSeqs = $itemGroup->select('sp_ig_group_seq')
                ->where('sp_ig_spim_id', $spID)
                ->distinct()
                ->findAll();

            $groupIds = array_map(fn($g) => $g['sp_ig_group_seq'], $groupSeqs);

            $groupedItems = [];

            if (!empty($groupIds)) {
                $groups = $itemGroup->select('sp_item_group.sp_ig_group_seq, sp_item_master.spim_name, sp_item_master.spim_id')
                    ->whereIn('sp_item_group.sp_ig_group_seq', $groupIds)
                    ->join('sp_item_master', 'sp_item_master.spim_id = sp_item_group.sp_ig_spim_id')
                    ->where('sp_ig_delete_flag', 0)
                    ->orderBy('sp_item_group.sp_ig_group_seq')
                    ->findAll();

                foreach ($groups as $row) {
                    $groupSeq = $row['sp_ig_group_seq'];
                    $groupedItems[$groupSeq][] = [
                        'spim_id' => $row['spim_id'],
                        'spim_name' => $row['spim_name']
                    ];
                }
            }

            if ($groupedItems) {
                $response = [
                    'ret_data' => 'success',
                    'groupedItems' => $groupedItems,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }

            return $this->respond($response, 200);
        }
    }

    public function getItemsBySpimIds()
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
            return $this->fail("Invalid user", 400);
        }

        $spim_id = $this->request->getVar('spim_ids');

        $itemGroup = new ServiceItemGroupModel();
        $groupSeqs = $itemGroup
            ->select('sp_ig_group_seq')
            ->whereIn('sp_ig_spim_id', $spim_id)
            ->where('sp_ig_delete_flag', 0)
            ->get()
            ->getResultArray();

        $groupSeqValues = [];
        foreach ($groupSeqs as $row) {
            $groupSeqValues[] = $row['sp_ig_group_seq'];
        }

        if (empty($groupSeqValues)) {
            return $this->respond([
                'ret_data' => 'fail',
            ], 200);
        }


        $builder = $itemGroup
            ->select('m.*')
            ->join('sp_item_master m', 'm.spim_id = sp_item_group.sp_ig_spim_id')
            ->whereIn('sp_item_group.sp_ig_group_seq', $groupSeqValues)
            ->where('sp_ig_delete_flag', 0)
            ->get();

        $items = $builder->getResultArray();
        if ($items) {
            return $this->respond([
                'ret_data' => 'success',
                'data' => $items,
            ], 200);
        } else {
            return $this->respond([
                'ret_data' => 'fail',
            ], 200);
        }
    }

    public function deleteGroup()
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
            return $this->fail("Invalid user", 400);
        }

        $sp_ig_group_seq = $this->request->getVar('sp_ig_group_seq');

        $itemGroupModel = new ServiceItemGroupModel();

        $deleted = $itemGroupModel
            ->where('sp_ig_group_seq', $sp_ig_group_seq)
            ->set(['sp_ig_delete_flag' => 1])
            ->update();
        if ($deleted) {
            return $this->respond([
                'ret_data' => 'success',
            ], 200);
        } else {
            return $this->respond([
                'ret_data' => 'fail',
            ], 200);
        }
    }


    function cleanString($str)
    {
        // Convert special Unicode spaces to normal space
        $str = preg_replace('/[\x{00A0}\x{200B}]/u', ' ', $str); // Non-breaking spaces, zero-width spaces

        // Remove all extra spaces (trim + internal double spaces)
        $str = preg_replace('/\s+/', ' ', $str);

        // Final trim
        $str = trim($str);

        return $str;
    }


    /** 
     * Function to get the price change that is requested to the admin,
     * 
     * @param $the details of the items are getting in this function
     * 
     * @return  $ success or failed is returned**/
    public function getPartcodeprice()
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
            return $this->fail("Invalid user", 400);
        }


        $partMasterModel = new SparePartsMaster();
        $partcode = $this->request->getVar('searchText');

        $partsDetails = $partMasterModel->select('pm_unit_type,pm_price,pm_id,pm_code')->where('pm_code', $partcode)->groupBy('pm_code,pm_brand')->where('pm_delete_flag', 0)->findAll();


        if ($partsDetails) {
            return $this->respond([
                'ret_data' => 'success',
                'partdetails' => $partsDetails,
            ], 200);
        } else {
            return $this->respond([
                'ret_data' => 'fail',
            ], 200);
        }
    }


    public function getLogsByModelCode()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) {
                return $this->respond([
                    'ret_data' => 'fail',
                    'message'  => 'Invalid superadmin user',
                ], 401);
            }
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) {
                return $this->respond([
                    'ret_data' => 'fail',
                    'message'  => 'Invalid user',
                ], 401);
            }
        } else {
            return $this->respond([
                'ret_data' => 'fail',
                'message'  => 'Invalid token audience',
            ], 401);
        }


        $modelCodeId = $this->request->getVar('spmc_id');

        $SpLogs = new ServicePackageLogModel();

        $results = $SpLogs->select('sp_log_notes, sp_log_created_by, sp_log_created_on')->where('sp_log_delete_flag', 0)->where('sp_log_spmc_id', $modelCodeId)->findAll();


        if ($results) {
            return $this->respond([
                'ret_data' => 'success',
                'logs'     => $results,
            ], 200);
        } else {
            return $this->respond([
                'ret_data' => 'fail',
                'message'  => 'No logs found for this model code',
            ], 200);
        }
    }

    public function deleteServicePackageModelCode()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) {
                return $this->respond([
                    'ret_data' => 'fail',
                    'message'  => 'Invalid superadmin user',
                ], 401);
            }
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) {
                return $this->respond([
                    'ret_data' => 'fail',
                    'message'  => 'Invalid user',
                ], 401);
            }
        } else {
            return $this->respond([
                'ret_data' => 'fail',
                'message'  => 'Invalid token audience',
            ], 401);
        }


        if ($tokendata) {

            $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();

            $spmc_id = $this->request->getVar('spmc_id');

            $data = [
                'spmc_id' => $spmc_id,
                'spmc_delete_flag' => 1,
            ];

            if ($spmc_id) {
                $updated = $ServicePackageModelCodeModel->where('spmc_id', $spmc_id)->set($data)->update();
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

    public function updateModelCodeFromLaabs()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) {
                return $this->respond([
                    'ret_data' => 'fail',
                    'message'  => 'Invalid superadmin user',
                ], 401);
            }
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) {
                return $this->respond([
                    'ret_data' => 'fail',
                    'message'  => 'Invalid user',
                ], 401);
            }
        } else {
            return $this->respond([
                'ret_data' => 'fail',
                'message'  => 'Invalid token audience',
            ], 401);
        }


        if ($tokendata) {


            $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();


            $url = "http://almaraghi.fortiddns.com:35147/maraghi_lead_test/index.php/DataFetch/getModelCodeDetails"; // Replace with your actual API URL

            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            // Execute
            $curlResponse = json_decode(curl_exec($ch), true);
            curl_close($ch);


            // Process response
            if (!empty($curlResponse['data'])) {
                $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();
                // Send API response + current user id (tokendata['uid']) to model
                $result = $ServicePackageModelCodeLabourModel->syncApiData($curlResponse['data'], $tokendata['uid']);
                return $this->response->setJSON([
                    'ret_data'  => 'success',
                    'message' => 'Data synced successfully',
                    'inserted_count' => $result['inserted'],
                    'updated_count'  => $result['updated'],
                ]);
            } else {
                return $this->response->setJSON([
                    'ret_data'  => 'error',
                    'message' => 'No data found in API response'
                ]);
            }
        }
    }

    public function getServicePackageByRegNo()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) {
                return $this->respond([
                    'ret_data' => 'fail',
                    'message'  => 'Invalid superadmin user',
                ], 401);
            }
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) {
                return $this->respond([
                    'ret_data' => 'fail',
                    'message'  => 'Invalid user',
                ], 401);
            }
        } else {
            return $this->respond([
                'ret_data' => 'fail',
                'message'  => 'Invalid token audience',
            ], 401);
        }


        if ($tokendata) {

            $url = "http://almaraghi.fortiddns.com:35147/maraghi_lead_test/index.php/DataFetch/getModelCodeByRegNo"; // Replace with your actual API URL

            $data = json_encode([
                'regNo' => $this->request->getVar('regNo'),
            ]); // Convert to JSON format

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8'
            ]);
            curl_setopt($ch, CURLOPT_POST, TRUE); // Set POST method
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Send JSON data
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            $curlResponse = json_decode(curl_exec($ch), true);
            curl_close($ch);

            $response = [
                'data' => [
                    'MODEL_CODE' => $curlResponse['data']['MODEL_CODE'] ?? null,
                    'suggestions' => $curlResponse['data']['suggestions'] ?? [],
                    'CHASSIS_NO'  => $curlResponse['data']['CHASSIS_NO'] ?? null,
                    'MODEL_YEAR'  => $curlResponse['data']['MODEL_YEAR'] ?? null,
                ],
                'ret_data' => 'success'
            ];

            return $this->response->setJSON($response);
        }
    }

    public function getCustomerRegNo()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) {
                return $this->respond([
                    'ret_data' => 'fail',
                    'message'  => 'Invalid superadmin user',
                ], 401);
            }
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) {
                return $this->respond([
                    'ret_data' => 'fail',
                    'message'  => 'Invalid user',
                ], 401);
            }
        } else {
            return $this->respond([
                'ret_data' => 'fail',
                'message'  => 'Invalid token audience',
            ], 401);
        }


        if ($tokendata) {

            $marjobmodel = new MaraghiJobcardModel();

            $reg_no = $this->request->getVar('regNo');


            // Query only distinct car_reg_no 
            $cust_convert = $marjobmodel
                ->distinct()
                ->select("car_reg_no")
                ->like('car_reg_no', $reg_no)
                ->findAll(10);

            // Extract flat array
            $car_reg_list = array_column($cust_convert, 'car_reg_no');

            // Response
            $response = [
                'suggestions' => $car_reg_list,
                'ret_data' => 'success'
            ];

            return $this->response->setJSON($response);
        }
    }
}
