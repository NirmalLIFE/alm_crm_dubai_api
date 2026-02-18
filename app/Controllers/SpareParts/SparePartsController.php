<?php

namespace App\Controllers\SpareParts;

use CodeIgniter\RESTful\ResourceController;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\SpareParts\SparePartsMaster;
use App\Models\SpareParts\SpareCategory;
use App\Models\ServicePackage\ServicePackageModelCodeModel;
use App\Models\ServicePackage\ServicePackageSpareModel;
use App\Models\ServicePackage\ServicePackageKMItemMap;
use App\Models\ServicePackage\ServicePackageKmPriceModel;
use App\Models\ServicePackage\PartsMasterLogModel;


class SparePartsController extends ResourceController
{

    protected $db;         // default DB
    protected $dbCommon;   // common DB

    public function __construct()
    {
        // Default DB (Abu Dhabi / Dubai)
        $this->db = \Config\Database::connect('default');

        // Common DB (shared database)
        $this->dbCommon = \Config\Database::connect('commonDB');
    }
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

            $parts = $SparePartsMaster->where("pm_delete_flag !=", 1)
                ->join('brand_list', 'brand_list.brand_id=pm_brand', 'left')
                ->join('spare_category', 'spare_category.spc_id=pm_category', 'left')
                ->select('parts_master.*, brand_list.brand_name,brand_list.brand_code,spare_category.spc_name')
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

            $Sparedata = [
                'pm_code' => $this->request->getVar('part_code'),
                'pm_name' => $this->request->getVar('part_name'),
                'pm_category' => $this->request->getVar('part_category'),
                'pm_brand' => $this->request->getVar('part_brand'),
                'pm_unit_type' => $this->request->getVar('pm_unit_type'),
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
        $spareModel = new ServicePackageSpareModel();
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
            $pm_id = $this->request->getVar('pm_id');
            $branch_id = $this->request->getVar('branch_id');
            $pm_code = $this->request->getVar('pm_code');
            $pm_name = $this->request->getVar('sp_pm_name');

            $branchMap = [
                0 => 'Abu Dhabi',
                1 => 'Dubai'
            ];
            $branch_name = $branchMap[$branch_id];

            $existingPart = $SparePartsMaster
                ->where('pm_id', $pm_id)
                ->first();

            if (!$existingPart) {
                return $this->fail("Part not found", 400);
            }

            $spmc_models = $spareModel
                ->select('sp_model_code.spmc_value')
                ->where('sp_spare_pm_id', $pm_id)
                ->join('sp_model_code', 'sp_model_code.spmc_id = sp_spares.sp_spare_spmc_id', 'left')
                ->findAll();

            $spmc_models = array_column($spmc_models, 'spmc_value');

            $old_price = floatval($existingPart['pm_price']);
            $new_price = $this->request->getVar('pm_price');

            $userModel = new UserModel();
            $PartsMasterLog = new PartsMasterLogModel();

            $user = $userModel->select('us_role_id, us_firstname')
                ->where('us_id', $tokendata['uid'])
                ->first();

            if (!$user) {
                return $this->fail("Invalid user", 400);
            }

            $role_id = $user['us_role_id'];

            if (in_array($role_id, [1, 10])) {
                $Sparedata = [
                    'pm_id' => $pm_id,
                    'pm_price' => $new_price,
                    'pm_new_price' => 0,
                    'pm_updated_on' => date("Y-m-d H:i:s"),
                    'pm_updated_by' => $tokendata['uid'],
                ];

                $updateParts = $SparePartsMaster->where('pm_id', $pm_id)->set($Sparedata)->update();

                if ($updateParts) {
                    $logNotes = "Admin Changed Price of "
                        . $pm_name . "(" . $pm_code . ")"
                        . " From  (Old Price): "
                        . number_format($old_price, 2)
                        . "  (New Price): "
                        . number_format($new_price, 2)
                        . " .Updated By User: "
                        . $user['us_firstname']
                        . " in " . $branch_name
                        . " .Affected Models are: "
                        . implode(", ", $spmc_models);

                    $PartsMasterLog->insert([
                        'pm_log_pm_id' => $pm_id,
                        'pm_log_notes' => $logNotes,
                        'pm_log_created_by' => $user['us_firstname'],
                        'pm_log_created_on' => date("Y-m-d H:i:s"),
                        'pm_log_branch' => $branch_id,
                        'pm_log_delete_flag' => 0
                    ]);

                    return $this->respond([
                        'ret_data' => 'admin_approved',
                        'message' => 'Price updated successfully.'
                    ], 200);
                } else {
                    return $this->respond([
                        'ret_data' => 'fail',
                        'message' => 'Failed to update the price.'
                    ], 200);
                }
            }

            // Else normal request-to-admin flow

            if ($existingPart && !empty($existingPart['pm_new_price']) && floatval($existingPart['pm_new_price']) > 0) {
                return $this->respond([
                    'ret_data' => 'duplicate',
                    'message' => 'A request for this price (' . number_format($existingPart['pm_new_price'], 2) . ') has already been sent to the admin. You can only create a new request once the admin accepts or delete the existing one.'
                ], 200);
            }

            $Sparedata = [
                'pm_id' => $pm_id,
                'pm_new_price' => $new_price,
                'pm_updated_on' => date("Y-m-d H:i:s"),
                'pm_updated_by' => $tokendata['uid'],
                'pm_price_requested_by' => $tokendata['uid'],
            ];

            $updateParts = $SparePartsMaster->where('pm_id', $pm_id)->set($Sparedata)->update();

            if ($updateParts) {
                $logNotes = "Price Change Requested for "
                    . $pm_name . "(" . $pm_code . ")"
                    . ". Requested By User: "
                    . $user['us_firstname']
                    . " (Old Price): "
                    . number_format($old_price, 2)
                    . " (New Price): "
                    . number_format($new_price, 2)
                    . " in " . $branch_name;

                $PartsMasterLog->insert([
                    'pm_log_pm_id' => $pm_id,
                    'pm_log_notes' => $logNotes,
                    'pm_log_created_by' => $user['us_firstname'],
                    'pm_log_created_on' => date("Y-m-d H:i:s"),
                    'pm_log_branch' => $branch_id,
                    'pm_log_delete_flag' => 0
                ]);
                return $this->respond(['ret_data' => 'success'], 200);
            } else {
                return $this->respond(['ret_data' => 'fail'], 200);
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
        $SparePartsMaster = new SparePartsMaster();
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

    public function servicePkgPartsList()
    {
        $SparePartsMaster = new SparePartsMaster();
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

            $parts = $SparePartsMaster->where("pm_delete_flag ", 0)
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id=pm_sp_pm_id', 'left')
                ->join('brand_list', 'brand_list.brand_id=pm_brand', 'left')
                ->select('pm_id,pm_code,sp_pm_name,brand_name,pm_price,brand_id')
                ->groupBy('pm_code, brand_id')
                ->findAll();

            // Filter out null or empty fields and keep only unique combinations
            $uniqueParts = [];
            $seen = [];

            foreach ($parts as $part) {
                $pm_code = trim($part['pm_code'] ?? '');
                $sp_pm_name = trim($part['sp_pm_name'] ?? '');
                $brand_name = trim($part['brand_name'] ?? '');
                $brand_id = $part['brand_id'] ?? null;
                // Skip if any field is null/empty
                if ($pm_code === '' || $sp_pm_name === '' || $brand_id === '') {
                    continue;
                }

                // Create a unique key combining pm_code and brand_name
                $key = "{$pm_code}_{$brand_id}";

                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $uniqueParts[] = $part;
                }
            }

            if (count($uniqueParts) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'parts' => $uniqueParts,
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
     * Function to get the price change that is requested to the admin,
     * 
     * @param 
     * 
     * @return $partname , partcode, spim name , requested new price, old price, requested by who? **/
    public function servicePkgPartsListRequested()
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

            $SparePartsMaster = new SparePartsMaster();

            $requestedparts = $SparePartsMaster
                ->select('parts_master.*, brand_list.brand_name, sp_item_master.spim_name, pm_price_requested_by')
                ->where('pm_delete_flag', 0)
                ->join('brand_list', 'brand_list.brand_id = pm_brand', 'left')
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                ->where('pm_new_price IS NOT NULL')
                ->where('pm_new_price >', 0)
                ->where('pm_new_price != pm_price')
                ->findAll();

            // Fetch users from main DB
            $userIds = array_unique(array_column($requestedparts, 'pm_price_requested_by'));

            if (!empty($userIds)) {
                $users = $this->db
                    ->table('users')
                    ->select('us_id, us_firstname')
                    ->whereIn('us_id', $userIds)
                    ->get()
                    ->getResultArray();

                $userMap = array_column($users, 'us_firstname', 'us_id');

                foreach ($requestedparts as &$row) {
                    $row['us_firstname'] = $userMap[$row['pm_price_requested_by']] ?? null;
                }
            }
            // $requestedparts = $SparePartsMaster
            //     ->select('parts_master.*, brand_list.brand_name, users.us_firstname, sp_item_master.spim_name')
            //     ->where('pm_delete_flag', 0)
            //     ->join('brand_list', 'brand_list.brand_id = pm_brand', 'left')
            //     ->join('users', 'users.us_id = pm_price_requested_by', 'left')
            //     ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
            //     ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
            //     ->where('pm_new_price IS NOT NULL')
            //     ->where('pm_new_price >', 0)
            //     ->where('pm_new_price != pm_price')
            //     ->findAll();

            if (count($requestedparts) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'requests' => $requestedparts,
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
     * Function to get the price change that is requested to the admin,
     * 
     * @param $the details of the items are getting in this function
     * 
     * @return  $ success or failed is returned**/

    //  old code
    // public function acceptPrice()
    // {
    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
    //         if (!$super)
    //             return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $tokendata['uid'])->first();
    //         if (!$user)
    //             return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata) {

    //         $SparePartsMaster = new SparePartsMaster();
    //         $pm_code = $this->request->getVar('pm_code');
    //         $pm_brand = $this->request->getVar('pm_brand');
    //         $pm_new_price = $this->request->getVar('pm_new_price');
    //         $old_price  = $this->request->getVar('pm_price');


    //         // 1️⃣ Get all distinct sp_spare_spmc_id in one query
    //         $requestedparts = $SparePartsMaster
    //             ->distinct()
    //             ->select('sp_spares.sp_spare_spmc_id')
    //             ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = parts_master.pm_id')
    //             ->join('sp_spares', 'sp_spares.sp_spare_pm_id = sp_parts_master.sp_pm_spim_id')
    //             ->where('parts_master.pm_delete_flag', 0)
    //             ->where('parts_master.pm_code', $pm_code)
    //             ->where('parts_master.pm_brand', $pm_brand)
    //             ->findAll();

    //         $requestedparts = array_column($requestedparts, 'sp_spare_spmc_id'); // Flatten array

    //         // 2️⃣ Update matching parts prices
    //         if (!empty($requestedparts)) {
    //             $SparePartsMaster
    //                 ->where('pm_delete_flag', 0)
    //                 ->where('pm_code', $pm_code)
    //                 ->where('pm_brand', $pm_brand)
    //                 ->set('pm_price', $pm_new_price)
    //                 ->set('pm_new_price', 0)
    //                 ->update();


    //             // 3️⃣ Final response (send details for SweetAlert)
    //             $response = [
    //                 'ret_data'      => 'success',
    //                 'requests'      => $requestedparts,
    //                 'pm_code'       => $pm_code,
    //                 'brand_name'    => $pm_brand,
    //                 'pm_new_price'  => $pm_new_price
    //             ];
    //         } else {
    //             $response = [
    //                 'ret_data' => 'fail',
    //             ];
    //         }
    //         return $this->respond($response, 200);
    //     }
    // }


    //new trying to solve bug code
    public function acceptPrice()
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

            $SparePartsMaster = new SparePartsMaster();
            $spareModel = new ServicePackageSpareModel();

            $pm_id = $this->request->getVar('pm_id');
            $branch_id = $this->request->getVar('branch_id');
            $pm_code = $this->request->getVar('pm_code');
            $pm_name = $this->request->getVar('pm_name');


            $branchMap = [
                0 => 'Abu Dhabi',
                1 => 'Dubai'
            ];
            $branch_name = $branchMap[$branch_id];

            $existingPart = $SparePartsMaster->select('*')
                ->where('pm_delete_flag', 0)
                ->where('pm_id', $pm_id)
                ->first();

            // echo "<pre>";
            // print_r($existingPart);
            // exit;

            if (!$existingPart) {
                return $this->fail("Part not found", 400);
            }

            $spmc_models = $spareModel
                ->select('sp_model_code.spmc_value')
                ->where('sp_spare_pm_id', $pm_id)
                ->join('sp_model_code', 'sp_model_code.spmc_id = sp_spares.sp_spare_spmc_id', 'left')
                ->findAll();

            $spmc_models = array_column($spmc_models, 'spmc_value');

            $old_price = floatval($existingPart['pm_price']);
            $new_price = $this->request->getVar('pm_price');

            $userModel = new UserModel();
            $PartsMasterLog = new PartsMasterLogModel();

            $pm_code = $this->request->getVar('pm_code');
            $pm_brand = $this->request->getVar('pm_brand');
            $pm_new_price = $this->request->getVar('pm_new_price');
            $old_price = $this->request->getVar('pm_price');
            $pm_id = $this->request->getVar('pm_id');
            $multiple = $this->request->getVar('multiple');     // e.g. 10
            $rounding = $this->request->getVar('rounding');     // e.g. 'nearest_threshold'
            $threshold = $this->request->getVar('threshold');    // e.g. 5


            $price_diff = $pm_new_price - $old_price; // can be negative



            $builder = $SparePartsMaster->db->table('parts_master pm');

            $exists = $builder->select('pm.pm_id')
                ->join('sp_spares sp', 'sp.sp_spare_pm_id = pm.pm_id')
                ->where('pm.pm_delete_flag', 0)
                ->where('sp.sp_spare_delete_flag', 0)
                ->where('pm.pm_code', trim($pm_code))
                ->where('pm.pm_brand', trim($pm_brand))
                ->get()
                ->getRowArray(); // returns first matching row or null

            if ($exists) {
                // row exists and is not deleted
                // Get SPMC and KM IDs from SparePartsMaster
                $ids = $SparePartsMaster->updatePricesByIds($pm_id, $rounding, $threshold, $price_diff);

                if (!empty($ids['spmcIds']) && !empty($ids['kmIds'])) {

                    // Now call ServicePackageKmPriceModel with returned IDs
                    $spKmPriceModel = new ServicePackageKmPriceModel();
                    $summary = $spKmPriceModel->processUniqueIds(
                        $ids['spmcIds'],
                        $ids['kmIds'],
                        $ids['rounding'],
                        $ids['threshold'],
                        $ids['price_diff'],
                    );

                    if (!empty($summary)) {

                        $SparePartsMaster
                            ->where('pm_delete_flag', 0)
                            ->where('pm_code', $pm_code)
                            ->where('pm_brand', $pm_brand)
                            ->set('pm_price', $pm_new_price)
                            ->set('pm_new_price', 0)
                            ->update();

                        $response = [
                            'ret_data' => 'success',
                            'pm_code' => $pm_code,
                            'brand_name' => $pm_brand,
                            'pm_new_price' => $pm_new_price,
                            'summary' => $summary
                        ];

                        $logNotes = "Admin Changed Price of "
                            . $pm_name . "(" . $pm_code . ")"
                            . " From  (Old Price): "
                            . number_format($old_price, 2)
                            . "  (New Price): "
                            . number_format($new_price, 2)
                            . ". Updated By User: "
                            . $user['us_firstname']
                            . " in " . $branch_name
                            . ". Affected Models: "
                            . implode(", ", $spmc_models);

                        $PartsMasterLog->insert([
                            'pm_log_pm_id' => $pm_id,
                            'pm_log_notes' => $logNotes,
                            'pm_log_created_by' => $user['us_firstname'],
                            'pm_log_created_on' => date("Y-m-d H:i:s"),
                            'pm_log_branch' => $branch_id,
                            'pm_log_delete_flag' => 0
                        ]);


                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail',
                            'pm_code' => $pm_code,
                            'brand_name' => $pm_brand,
                            'pm_new_price' => $pm_new_price,
                            'summary' => "failed from Servicepackage Km price map Model"
                        ];
                        return $this->respond($response, 200);
                    }
                }
                // // 3️⃣ Final response (send details for SweetAlert)
                // $response = [
                //     'ret_data'      => 'success',
                //     'requests'      => $requestedparts,
                //     'pm_code'       => $pm_code,
                //     'brand_name'    => $pm_brand,
                //     'pm_new_price'  => $pm_new_price
                // ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        }
    }



    // new code

    // public function acceptPrice()
    // {
    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

    //     // --- auth checks (unchanged) ---
    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $tokendata['uid'])->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         return $this->fail(["ret_data" => "Invalid user"], 400);
    //     }

    //     // Models (adjust if your model names/locations differ)
    //     $SparePartsMaster = new SparePartsMaster();              // parts_master
    //     $SP_Parts_Model    = new ServicePackageSpareModel(); // sp_spares
    //     $SP_KmItemMap      =  new ServicePackageKMItemMap(); // sp_km_item_map
    //     $SP_KmPriceModel   =  new ServicePackageKmPriceModel();   // sp_km_price_map
    //     // $BrandsModel       = model('App\Models\BrandsModel');    // optional brand lookup

    //     // Inputs
    //     $pm_code      = $this->request->getVar('pm_code');
    //     $pm_brand     = $this->request->getVar('pm_brand'); // may be id or name
    //     $pm_new_price = (float) $this->request->getVar('pm_new_price');
    //     $old_price    = (float) $this->request->getVar('pm_price'); // old price sent by frontend
    //     $pm_id        = (int) $this->request->getVar('pm_id');

    //     // Rounding options (optional from frontend)
    //     $rounding = $this->request->getVar('rounding') ?? 'nearest_threshold'; // ceil_up | nearest | nearest_threshold | none
    //     $multiple = (int) ($this->request->getVar('multiple') ?? 10);
    //     $threshold = (float) ($this->request->getVar('threshold') ?? 5.0);

    //     if (empty($pm_code) || !$pm_id || ($pm_new_price === $old_price)) {
    //         return $this->respond([
    //             'ret_data' => 'no_change',
    //             'message'  => 'Missing data or price unchanged'
    //         ], 200);
    //     }

    //     // // Resolve brand name if numeric id
    //     // $brandName = $pm_brand;
    //     // if (is_numeric($pm_brand)) {
    //     //     $brandRow = $BrandsModel->where('brand_id', (int)$pm_brand)->first();
    //     //     if ($brandRow && isset($brandRow['brand_name'])) $brandName = $brandRow['brand_name'];
    //     // }

    //     // 1) get all distinct sp_spare_spmc_id (model codes) affected by this part
    //     $requestedparts = $SparePartsMaster
    //         ->distinct()
    //         ->select('sp_spares.sp_spare_spmc_id')
    //         ->join('sp_spares', 'sp_spares.sp_spare_pm_id = parts_master.pm_id')
    //         ->where('parts_master.pm_delete_flag', 0)
    //         ->where('parts_master.pm_code', $pm_code)
    //         ->where('parts_master.pm_brand', $pm_brand)
    //         ->findAll();

    //     $requestedparts = array_column($requestedparts, 'sp_spare_spmc_id');

    //     if (empty($requestedparts)) {
    //         return $this->respond([
    //             'ret_data' => 'fail',
    //             'message'  => 'No related models found for this part/brand'
    //         ], 200);
    //     }

    //     // 2) update pm_price in parts_master (same as your current)
    //     $SparePartsMaster
    //         ->where('pm_delete_flag', 0)
    //         ->where('pm_code', $pm_code)
    //         ->where('pm_brand', $pm_brand)
    //         ->set('pm_price', $pm_new_price)
    //         ->set('pm_new_price', 0)
    //         ->update();

    //     // 3) compute deltas per model -> per km and update sp_km_price_map accordingly
    //     $price_diff = $pm_new_price - $old_price; // can be negative

    //     $db = \Config\Database::connect();
    //     $db->transStart();

    //     $updatesDone = [];
    //     $skipped = [];

    //     foreach ($requestedparts as $model_id_raw) {
    //         $model_id = (int)$model_id_raw;
    //         if (!$model_id) continue;

    //         // get spares in sp_spares where sp_spare_spmc_id = model and sp_spare_pm_id = $pm_id
    //         $spares = $SP_Parts_Model
    //             ->select('sp_spare_id, sp_spare_qty')
    //             ->where('sp_spare_spmc_id', $model_id)
    //             ->where('sp_spare_pm_id', $pm_id)
    //             ->where('sp_spare_delete_flag', 0)
    //             ->findAll();

    //         if (empty($spares)) {
    //             $skipped[] = [
    //                 'model_id' => $model_id,
    //                 'reason' => 'no_spare_found_for_pm_id'
    //             ];
    //             continue;
    //         }

    //         // accumulate delta per km
    //         $kmDeltas = [];
    //         foreach ($spares as $s) {
    //             $spare_id = (int)$s['sp_spare_id'];
    //             $qty = isset($s['sp_spare_qty']) ? (float)$s['sp_spare_qty'] : 1.0;

    //             $maps = $SP_KmItemMap
    //                 ->select('spkm_id')
    //                 ->where('spkm_item_id', $spare_id)
    //                 ->where('spkm_item_type', 0)
    //                 ->where('spkm_delete_flag', 0)
    //                 ->findAll();

    //             if (empty($maps)) {
    //                 // SAFE: skip; do not apply to KM rows that don't map
    //                 continue;
    //             }

    //             $contribution = $qty * $price_diff;
    //             foreach ($maps as $m) {
    //                 $km_id = (int)$m['spkm_id'];
    //                 if (!$km_id) continue;
    //                 if (!isset($kmDeltas[$km_id])) $kmDeltas[$km_id] = 0.0;
    //                 $kmDeltas[$km_id] += $contribution;
    //             }
    //         }

    //         if (empty($kmDeltas)) {
    //             $skipped[] = [
    //                 'model_id' => $model_id,
    //                 'reason' => 'no_km_mapping_for_spare'
    //             ];
    //             continue;
    //         }

    //         // update each km row
    //         foreach ($kmDeltas as $km_id => $deltaAmount) {
    //             $row = $SP_KmPriceModel
    //                 ->where('spkmp_spmc_id', $model_id)
    //                 ->where('spkmp_id', $km_id)
    //                 ->get()
    //                 ->getRowArray();

    //             if (!$row) continue;

    //             $oldDisplay = (float) ($row['spkmp_display_price'] ?? 0.0);
    //             $computedDisplay = $oldDisplay + (float)$deltaAmount;

    //             // decide final value based on rounding option
    //             switch ($rounding) {
    //                 case 'none':
    //                     $finalDisplay = round($computedDisplay, 2);
    //                     break;

    //                 case 'ceil_up':
    //                     if ($computedDisplay >= $oldDisplay) {
    //                         $finalDisplay = (float) (ceil($computedDisplay / $multiple) * $multiple);
    //                     } else {
    //                         $finalDisplay = (float) (floor($computedDisplay / $multiple) * $multiple);
    //                     }
    //                     break;

    //                 case 'nearest':
    //                     $finalDisplay = (float) (round($computedDisplay / $multiple) * $multiple);
    //                     break;

    //                 case 'nearest_threshold':
    //                 default:
    //                     $absDelta = abs($computedDisplay - $oldDisplay);
    //                     if ($absDelta >= $threshold) {
    //                         $finalDisplay = (float) (round($computedDisplay / $multiple) * $multiple);
    //                     } else {
    //                         $finalDisplay = round($computedDisplay, 2);
    //                     }
    //                     break;
    //             }

    //             if ($finalDisplay < 0) $finalDisplay = 0.0;

    //             if (abs($finalDisplay - $oldDisplay) > 0.0001) {
    //                 $SP_KmPriceModel
    //                     ->where('spkmp_spmc_id', $model_id)
    //                     ->where('spkmp_id', $km_id)
    //                     ->set('spkmp_display_price', $finalDisplay)
    //                     ->update();

    //                 $updatesDone[] = [
    //                     'model_id' => $model_id,
    //                     'km_id' => $km_id,
    //                     'old_display' => round($oldDisplay, 2),
    //                     'computed_display_before_round' => round($computedDisplay, 2),
    //                     'rounded_display' => round($finalDisplay, 2),
    //                     'delta' => round($deltaAmount, 2),
    //                 ];
    //             } else {
    //                 $updatesDone[] = [
    //                     'model_id' => $model_id,
    //                     'km_id' => $km_id,
    //                     'old_display' => round($oldDisplay, 2),
    //                     'computed_display_before_round' => round($computedDisplay, 2),
    //                     'rounded_display' => round($finalDisplay, 2),
    //                     'delta' => round($deltaAmount, 2),
    //                     'note' => 'no_update_needed'
    //                 ];
    //             }
    //         } // end km loop
    //     } // end model loop

    //     $db->transComplete();

    //     $response = [
    //         'ret_data' => 'success',
    //         'requests' => array_values($requestedparts),
    //         'pm_code' => $pm_code,
    //         // 'brand_name' => $brandName,
    //         'pm_old_price' => round($old_price, 2),
    //         'pm_new_price' => round($pm_new_price, 2),
    //         'price_diff' => round($price_diff, 2),
    //         'rounding' => $rounding,
    //         'multiple' => $multiple,
    //         'threshold' => $threshold,
    //         'updated' => $updatesDone,
    //         'skipped' => $skipped
    //     ];

    //     return $this->respond($response, 200);
    // }




    /** 
     * Function to get the price change that is requested to the admin,
     * 
     * @param $get the id of the part master
     * 
     * @return   $failed or successs**/
    public function cancelPrice()
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

            $spareModel = new ServicePackageSpareModel();
            $SparePartsMaster = new SparePartsMaster();
            $pm_code = $this->request->getVar('pm_code');
            $pm_brand = $this->request->getVar('pm_brand');

            $pm_id = $this->request->getVar('pm_id');
            $branch_id = $this->request->getVar('branch_id');
            $pm_name = $this->request->getVar('pm_name');

            $branchMap = [
                0 => 'Abu Dhabi',
                1 => 'Dubai'
            ];
            $branch_name = $branchMap[$branch_id];

            $existingPart = $SparePartsMaster
                ->where('pm_id', $pm_id)
                ->first();


            $old_price = floatval($existingPart['pm_price']);

            $userModel = new UserModel();
            $PartsMasterLog = new PartsMasterLogModel();

            $user = $userModel->select('us_role_id, us_firstname')
                ->where('us_id', $tokendata['uid'])
                ->first();

            if (!$user) {
                return $this->fail("Invalid user", 400);
            }


            $requestedparts = $SparePartsMaster
                ->where('pm_delete_flag', 0)
                ->where('pm_code ', $pm_code)
                ->where('pm_brand ', $pm_brand)
                ->findAll();

            if ($requestedparts) {

                $SparePartsMaster
                    ->where('pm_delete_flag', 0)
                    ->where('pm_code', $pm_code)
                    ->where('pm_brand', $pm_brand)
                    ->set('pm_new_price', 0)
                    ->update();

                $logNotes = "Admin Declined Price Change of "
                    . $pm_name . "(" . $pm_code . ")"
                    . " Existing Price: "
                    . number_format($old_price, 2)
                    . ". Updated By User: "
                    . $user['us_firstname']
                    . " in " . $branch_name;

                $PartsMasterLog->insert([
                    'pm_log_pm_id' => $pm_id,
                    'pm_log_notes' => $logNotes,
                    'pm_log_created_by' => $user['us_firstname'],
                    'pm_log_created_on' => date("Y-m-d H:i:s"),
                    'pm_log_branch' => $branch_id,
                    'pm_log_delete_flag' => 0
                ]);

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

    public function getPartsLog()
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
            $partsLogModel = new PartsMasterLogModel();
            $pm_id = $this->request->getVar('pm_id');

            $partsLog = $partsLogModel
                ->where('pm_log_pm_id', $pm_id)
                ->where('pm_log_delete_flag', 0)
                ->orderBy('pm_log_created_on', 'DESC')
                ->findAll();

            if (count($partsLog) > 0) {

                $response = [
                    'ret_data' => 'success',
                    'partsLog' => $partsLog,
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
