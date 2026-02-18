<?php

namespace App\Controllers\ServiceContract;

use CodeIgniter\RESTful\ResourceController;

use App\Controllers\BaseController;
use App\Models\SuperAdminModel;
use Config\Common;
use Config\Validation;
use App\Models\UserModel;
use App\Models\ServiceContract\ServiceContractVehicleModel;
use App\Models\ServiceContract\ServiceContractModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\ServicePackage\ServicePackageModelCodeModel;
use App\Models\ServicePackage\ServicePackageEnginesModel;
use App\Models\ServicePackage\ServicePackageSpareModel;
use App\Models\ServicePackage\ServicePackageLabourModel;
use App\Models\ServicePackage\ServicePackageKmPriceModel;
use App\Models\ServicePackage\ServicePackageModelCodeLabourModel;
use App\Models\ServiceContract\ServiceContractTierModel;
use App\Models\ServicePackage\KilometerMasterModel;
use App\Models\Customer\MaraghiVehicleModel;
use DateTime;
use App\Models\ServiceContract\ServiceContractKmModel;

class ServiceContractController extends ResourceController
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

            $contracttier = new ServiceContractTierModel();
            $tier = $contracttier->where('sct_delete_flag', 0)->findAll();

            if ($tier) {
                $response = [
                    'ret_data' => 'success',
                    'tier' => $tier
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'tier' => []
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
    public function show($id = null) {}

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
            $service_contract_vehicle = new  ServiceContractVehicleModel();
            $service_contract = new ServiceContractModel();
            $date = new DateTime();
            $customerModel = new MaragiCustomerModel();
            $ServiceContractKmModel = new ServiceContractKmModel();


            $checkedKmList = $this->request->getVar('checkedKmList');


            $data_veh = [
                'scv_vin_no' => $this->request->getVar('vin_no'),
                'scv_reg_no' =>  $this->request->getVar('reg_no'),
                'scv_model_year' => $this->request->getVar('model_year'),
                'scv_vehicle_model' => $this->request->getVar('vehicle_model'),
                'scv_kilometer_from' => $this->request->getVar('kilometer'),
                'scv_created_by' =>  $tokendata['uid'],
                'scv_created_on' => $date->format('Y-m-d H:i:s'),
                'scv_updated_by' =>  $tokendata['uid'],
                'scv_updated_on' => $date->format('Y-m-d H:i:s'),
            ];

            $service_contract_vehicle->insert($data_veh);
            $sc_id = $service_contract_vehicle->getInsertID();


            $svcId = $this->request->getVar('svcId');

            $validUpto = new DateTime();
            if ($svcId == 1) {
                $validUpto->modify('+1 year')->modify('-1 day');
            } elseif ($svcId == 2) {
                $validUpto->modify('+2 years')->modify('-1 day');
            } elseif ($svcId == 3) {
                $validUpto->modify('+3 years')->modify('-1 day');
            }

            $validUptoFormatted = $validUpto->format('Y-m-d');
            $validFromFormatted = $date->format('Y-m-d');

            foreach ($checkedKmList as $item) {
                $km_data = [
                    'sck_scv_id'            => $sc_id,
                    'sck_km_id'             => $item->km_id ?? null,
                    'sck_price'             => $item->higherTotal ?? 0,
                    'sck_created_on'        => $date->format('Y-m-d H:i:s'),
                    'sck_created_by'        => $tokendata['uid'],
                    'sck_updated_on'        => $date->format('Y-m-d H:i:s'),
                    'sck_updated_by'        => $tokendata['uid'],
                ];
                $ServiceContractKmModel->insert($km_data);
            }

            $data_con = [
                'sc_v_id' => $sc_id,
                'sc_contract_tier_id' => $this->request->getVar('svcId'),
                'sc_type' => $this->request->getVar('sc_type'),
                'sc_cust_id' => $this->request->getVar('customerId'),
                'sc_valid_from' =>  $validFromFormatted,
                'sc_valid_upto' => $validUptoFormatted,
                'sc_contract_duration' => $this->request->getVar('svcId'),
                'sc_contract_price' => $this->request->getVar('price'),
                'sc_created_on' => $date->format('Y-m-d H:i:s'),
                'sc_created_by' => $tokendata['uid'],
                'sc_updated_on' => $date->format('Y-m-d H:i:s'),
                'sc_updated_by' => $tokendata['uid'],
            ];
            $contract = $service_contract->insert($data_con);

            if ($contract) {
                $response = [
                    'ret_data' => 'success',
                    'contract' => $contract,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'contract' => [],
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


    public function checkVehicleServiceContract()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        // Auth checks
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

        if (! $tokendata) {
            return $this->respond(['ret_data' => 'unauthorized'], 401);
        }

        // Models
        $service_contract_vehicle = new ServiceContractVehicleModel();
        $service_contract = new ServiceContractModel();
        $veh_model = new MaraghiVehicleModel();

        // Read searchMode and determine vehicle lookup
        $searchMode = $this->request->getVar('searchMode');
        $contract_veh = null;
        $contract_details = null;

        if ($searchMode == 'vinNo') {
            $vin_no = $this->request->getVar('vinNo');
            if (!empty($vin_no)) {
                $contract_veh = $service_contract_vehicle->where('scv_vin_no', $vin_no)->where('scv_delete_flag', 0)->get()->getRow('scv_id');
            }
        } elseif ($searchMode == 'regNo') {
            $reg_no = $this->request->getVar('regNo');
            if (!empty($reg_no)) {
                $contract_veh = $service_contract_vehicle->where('scv_reg_no', $reg_no)->where('scv_delete_flag', 0)->get()->getRow('scv_id');
            }
        } else {
            return $this->respond([
                'ret_data' => 'fail',
                'message' => 'Invalid search mode provided.'
            ], 200);
        }

        if ($contract_veh) {
            $contract_main = $service_contract
                ->select('service_contract.*, service_contract_tier.*, service_contract_vehicle.*, cust_data_laabs.*')
                ->where('sc_v_id', $contract_veh)
                ->where('sc_delete_flag', 0)
                ->join('service_contract_tier', 'service_contract_tier.sct_id = service_contract.sc_contract_tier_id', 'left')
                ->join('service_contract_vehicle', 'service_contract_vehicle.scv_id = service_contract.sc_v_id', 'left')
                ->join('cust_data_laabs', 'cust_data_laabs.customer_code = service_contract.sc_cust_id', 'left')
                ->first();

            // Step 2: Get all km entries linked to this contract vehicle
            $km_details = $service_contract
                ->db
                ->table('service_contract_km')
                ->select('service_contract_km.*, kilometer_master.km_value')
                ->join('kilometer_master', 'kilometer_master.km_id = service_contract_km.sck_km_id', 'left')
                ->where('service_contract_km.sck_scv_id', $contract_main['sc_v_id']) // ✅ link to same vehicle
                ->get()
                ->getResultArray();

            $contract_main['km_details'] = $km_details;
        }

        if (!empty($contract_main)) {
            $response = [
                'ret_data' => 'success',
                'contract_details' => $contract_main,
            ];
            return $this->respond($response, 200);
        }

        $vin_no = $this->request->getVar('vinNo') ?? '';
        $kilometer = $this->request->getVar('kilometer') ?? '';

        if (empty($vin_no) && $searchMode == 'regNo') {
            $reg_no = $this->request->getVar('regNo');
            if (!empty($reg_no)) {
                $vin_no = $veh_model
                    ->where('reg_no', $reg_no)
                    ->get()
                    ->getRow('chassis_no');
            }
        }

        if (empty($vin_no)) {
            return $this->respond(['ret_data' => 'fail', 'message' => 'No VIN available to derive model code.'], 200);
        }

        $modelCode = '';
        if (strlen($vin_no) >= 9) {
            // 4th to 9th characters → substr with 0-based index
            $modelCode = substr($vin_no, 3, 6);
        }

        if (empty($modelCode)) {
            return $this->respond(['ret_data' => 'fail', 'message' => 'Unable to determine model code from VIN'], 200);
        }

        $ServicePackageModelCodeModel = new ServicePackageModelCodeModel();
        $SP_Parts_Model = new ServicePackageSpareModel();
        $SP_Labours_Model = new ServicePackageLabourModel();
        $ServicePackageEnginesModel = new ServicePackageEnginesModel();
        $Sp_KmPrice_Model = new ServicePackageKmPriceModel();
        $ServicePackageModelCodeLabourModel = new ServicePackageModelCodeLabourModel();
        $KmMasterModel = new KilometerMasterModel();

        $includeLowerKmPackages = $this->request->getVar('includeLowerKmPackages');

        if (!empty($kilometer)) {
            // Find the closest km_id for the given kilometer
            $kmRow = $KmMasterModel
                ->select('km_id, km_value')
                ->where('km_value >=', (int)$kilometer)
                ->orderBy('CAST(km_value AS UNSIGNED)', 'ASC')
                ->where('km_delete_flag', 0)
                ->first();

            // log_message("error", json_encode($kmRow));
            $selectedKmIds = [];

            if (!empty($kmRow)) {

                $currentKmId = (int) $kmRow['km_id'];

                $allKmRows = $KmMasterModel
                    ->select('km_id, km_value')
                    ->where('km_delete_flag', 0)
                    ->orderBy('km_id', 'ASC')
                    ->findAll();
                // log_message("error", json_encode($allKmRows));

                $kmIds = array_column($allKmRows, 'km_id');

                $currentIndex = array_search($currentKmId, $kmIds);

                if ($currentIndex !== false) {
                    if (!empty($includeLowerKmPackages) && $currentIndex > 0) {
                        // Include the previous KM + next (4) KMs to make total 5
                        $selectedKmIds = array_slice($kmIds, $currentIndex - 1, 5);
                    } else {
                        // Include only the current and next (4) KMs
                        $selectedKmIds = array_slice($kmIds, $currentIndex, 5);
                    }
                }
            }
        } else {
            $selectedKmIds = $KmMasterModel
                ->select('km_id')
                ->where('km_id >=', 7)
                ->where('km_delete_flag', 0)
                ->orderBy('km_id', 'ASC')
                ->findColumn('km_id');
        }

        $modelDataList = $ServicePackageModelCodeModel
            ->where('spmc_delete_flag', 0)
            ->where('spmc_status_flag', 5)
            ->where('spmc_value', $modelCode)
            ->findAll();

        if (empty($modelDataList)) {
            return $this->respond(['ret_data' => 'fail'], 200);
        }

        $modelLabourData = $ServicePackageModelCodeLabourModel
            ->select('spmcl_type, labour_rate, spmcl_inc_pct')
            ->where('model_code', $modelCode)
            ->where('spmcl_delete_flag', 0)
            ->first();

        $modelsToProcess = ($modelLabourData && $modelLabourData['spmcl_type'] == '1')
            ? $modelDataList
            : [reset($modelDataList)];

        $finalModels = [];

        foreach ($modelsToProcess as $modelData) {

            $labourFactor = 0;
            $mLab = $ServicePackageModelCodeLabourModel
                ->where('model_code', $modelData['spmc_value'])
                ->where('spmcl_delete_flag', 0)
                ->first();

            if (!empty($mLab)) {
                $labourRate = (float) ($mLab['labour_rate'] ?? 0);
                $increasePct = (float) ($mLab['spmcl_inc_pct'] ?? 0);
                $labourFactor = $labourRate + ($labourRate * $increasePct / 100);
            }

            $model_id = $modelData['spmc_id'];

            $engineDetails = $ServicePackageEnginesModel
                ->select('eng_id, eng_no, speng_spmc_id, eng_labour_factor')
                ->where('speng_delete_flag', 0)
                ->where('speng_spmc_id', $model_id)
                ->join('engine_master', 'engine_master.eng_id = speng_eng_id', 'left')
                ->first();

            $kmPriceMapRows = $Sp_KmPrice_Model->table('sp_km_price_map')
                ->select('spkmp_spkm_id, spkmp_markup_price, spkmp_display_price')
                ->where('spkmp_spmc_id', $model_id)
                ->get()
                ->getResultArray();

            $kmPriceMapById = [];
            foreach ($kmPriceMapRows as $row) {
                $kmPriceMapById[$row['spkmp_spkm_id']] = [
                    'markup_price' => $row['spkmp_markup_price'],
                    'display_price' => $row['spkmp_display_price'],
                ];
            }

            // spares for model
            $spares = $SP_Parts_Model
                ->select('spim_name, pm_price, spkm_km_optional_flag, pm_code, sp_spare_category, sp_spare_qty, sp_spare_id, sp_spare_optional_flag, spkm_km_id, sp_spare_group_seq, sp_spare_labour_unit, km_value, sp_km_item_map.spkm_delete_flag')
                ->where('sp_spare_spmc_id', $model_id)
                ->where('sp_spare_delete_flag', 0)
                ->join('parts_master', 'parts_master.pm_id = sp_spare_pm_id', 'left')
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = pm_sp_pm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                ->join('sp_km_item_map', 'sp_km_item_map.spkm_item_id = sp_spare_id AND sp_km_item_map.spkm_item_type = 0 AND sp_km_item_map.spkm_delete_flag = 0', 'left')
                ->join('kilometer_master', 'kilometer_master.km_id = spkm_km_id', 'left')
                ->whereIn('kilometer_master.km_id', $selectedKmIds)
                ->findAll();

            // labours for model
            $labours = $SP_Labours_Model
                ->select('spim_name, spkm_km_optional_flag, sp_pm_category, sp_labour_qty, sp_labour_id, sp_labour_optional_flag, sp_labour_group_seq, spkm_km_id, sp_labour_unit, km_value, sp_km_item_map.spkm_delete_flag')
                ->where('sp_labour_spmc_id', $model_id)
                ->where('sp_labour_delete_flag', 0)
                ->join('sp_parts_master', 'sp_parts_master.sp_pm_id = sp_labour_lm_id', 'left')
                ->join('sp_item_master', 'sp_item_master.spim_id = sp_pm_spim_id', 'left')
                ->join('sp_km_item_map', 'sp_km_item_map.spkm_item_id = sp_labour_id AND sp_km_item_map.spkm_item_type = 1 AND sp_km_item_map.spkm_delete_flag = 0', 'left')
                ->join('kilometer_master', 'kilometer_master.km_id = spkm_km_id', 'left')
                ->whereIn('kilometer_master.km_id', $selectedKmIds)
                ->findAll();

            // combine by km
            $combinedByKm = [];

            foreach ($spares as $spare) {
                if (!empty($spare['spkm_km_id'])) {
                    $km_id = $spare['spkm_km_id'];
                    $spare['item_type'] = 0;
                    $combinedByKm[$km_id]['items'][] = $spare;
                    $combinedByKm[$km_id]['km_value'] = $spare['km_value'] ?? '';
                }
            }

            foreach ($labours as $labour) {
                if (!empty($labour['spkm_km_id'])) {
                    $km_id = $labour['spkm_km_id'];
                    $labour['item_type'] = 1;
                    $combinedByKm[$km_id]['items'][] = $labour;
                    $combinedByKm[$km_id]['km_value'] = $labour['km_value'] ?? '';
                }
            }

            $final = [];
            $overallTotals = 0.0;
            foreach ($combinedByKm as $km_id => $data) {

                $itemsWithPrices = [];
                $groupTotal = 0.0;

                foreach ($data['items'] as $item) {
                    $type = (int)($item['item_type'] ?? 0);

                    if ($type === 0) {
                        $unitPrice = (float)($item['pm_price'] ?? 0);
                        $qty = (float)($item['sp_spare_qty'] ?? 0);
                        $itemTotal = round($unitPrice * $qty, 2);
                    } else {
                        $unitPrice = (float)($item['sp_labour_unit'] ?? 0);
                        $itemTotal = round($unitPrice * $labourFactor, 2);
                    }

                    $itemsWithPrices[] = $item;
                    $groupTotal += $itemTotal;
                }

                $overallTotals += $groupTotal;

                $displayPrice = (float)($kmPriceMapById[$km_id]['display_price'] ?? 0);
                $higherTotal = max($groupTotal, $displayPrice);

                $final[] = [
                    'km_id' => $km_id,
                    'km_value' => $data['km_value'] ?? '',
                    'actual_price' => $kmPriceMapById[$km_id]['markup_price'] ?? 0,
                    'display_price' => $kmPriceMapById[$km_id]['display_price'] ?? 0,
                    'group_total' => round($groupTotal, 2),
                    'higherTotal' => $higherTotal,
                    // 'items' => $data['items'],
                ];
            }

            if (!empty($selectedKmIds)) {
                // Determine silver, gold, platinum kmIds dynamically
                $silverKmIds = array_slice($selectedKmIds, 0, 2);
                $goldKmIds = array_slice($selectedKmIds, 0, 3);
                $platinumKmIds = $selectedKmIds; // all selected kmIds, usually 5

                $silverTotal = 0;
                $goldTotal = 0;
                $platinumTotal = 0;

                foreach ($final as $entry) {
                    $kmId = (int)$entry['km_id'];
                    $higherTotal = (float)$entry['higherTotal'];

                    if (in_array($kmId, $silverKmIds)) {
                        $silverTotal += $higherTotal;
                    }
                    if (in_array($kmId, $goldKmIds)) {
                        $goldTotal += $higherTotal;
                    }
                    if (in_array($kmId, $platinumKmIds)) {
                        $platinumTotal += $higherTotal;
                    }
                }

                $silverTotal = round($silverTotal, 2);
                $goldTotal = round($goldTotal, 2);
                $platinumTotal = round($platinumTotal, 2);
            } else {
                // If no kilometer is given, you can keep totals as 0 or calculate using default kmIds
                $silverTotal = $goldTotal = $platinumTotal = 0;
            }

            $finalModels[] = [
                'modelId' => $modelData['spmc_id'],
                'modelYearUsed' => $modelData['spmc_model_year'] ?? null,
                'Vin_No' => $modelData['spmc_vin_no'] ?? null,
                'spmc_type' => $modelData['spmc_type'] ?? null,
                'spmc_status_flag' => $modelData['spmc_status_flag'] ?? null,
                'packageTotals' => [
                    'silver' => $silverTotal,
                    'gold' => $goldTotal,
                    'platinum' => $platinumTotal,
                ],
                'servicePackage' => $final,
                'engineDetails' => $engineDetails,
                'labourFactor' => $labourFactor,
            ];
        }

        // final response
        $response = [
            'ret_data' => 'success',
            'models' => $finalModels,
            'spmcl_type' => ($modelLabourData['spmcl_type'] ?? 0)
        ];

        return $this->respond($response, 200);
    }



    public function updateContractTierDetails()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        // Auth checks
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

        if (! $tokendata) {
            return $this->respond(['ret_data' => 'unauthorized'], 401);
        }

        $contract_tier = new ServiceContractTierModel();
        $date = new DateTime();

        $id = $this->request->getVar('sct_id');
        $sct_name = $this->request->getVar('sct_name');
        $sct_services = $this->request->getVar('sct_services');
        $sct_pickup_and_drop = $this->request->getVar('sct_pickup_and_drop');
        $sct_excess_discount = $this->request->getVar('sct_excess_discount');
        $sct_sa_incentive = $this->request->getVar('sct_sa_incentive');
        $sct_discount = $this->request->getVar('sct_discount');
        $sct_visit_frequency_month = $this->request->getVar('sct_visit_frequency_month');


        $data = [
            "sct_id" => $id,
            "sct_name" => $sct_name,
            "sct_services" => $sct_services,
            "sct_pickup_and_drop" => $sct_pickup_and_drop,
            "sct_excess_discount" => $sct_excess_discount,
            "sct_sa_incentive" => $sct_sa_incentive,
            "sct_discount" => $sct_discount,
            'sct_visit_frequency_month' => $sct_visit_frequency_month,
            'sct_updated_by' => $tokendata['uid'],
            'sct_updated_on' => $date->format('Y-m-d H:i:s'),
        ];

        $updateTier = $contract_tier->where('sct_id', $id)->set($data)->update();


        // $updateTier = $contract_tier->update($id, $data);

        if ($updateTier) {
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


    public function checkCustomerByPhone()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        // Auth checks
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

        if (! $tokendata) {
            return $this->respond(['ret_data' => 'unauthorized'], 401);
        }

        $phone =  $this->request->getVar('phone');
        $customerModel = new MaragiCustomerModel();

        $customer = $customerModel->select('customer_code,customer_name')
            ->groupStart()
            ->where('phone', $phone)
            ->orWhere('mobile', $phone)
            ->groupEnd()
            ->first();


        if ($customer) {
            $response = [
                'ret_data' => 'success',
                'data' => $customer
            ];
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
        }

        return $this->respond($response, 200);
    }

    public function createServiceContractTier()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        // Auth checks
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

        if (! $tokendata) {
            return $this->respond(['ret_data' => 'unauthorized'], 401);
        }

        $contract_tier = new ServiceContractTierModel();
        $date = new DateTime();


        $sct_name = $this->request->getVar('sct_name');
        $sct_services = $this->request->getVar('sct_services');
        $sct_pickup_and_drop = $this->request->getVar('sct_pickup_and_drop');
        $sct_excess_discount = $this->request->getVar('sct_excess_discount');
        $sct_sa_incentive = $this->request->getVar('sct_sa_incentive');
        $sct_discount = $this->request->getVar('sct_discount');
        $sct_visit_frequency_month = $this->request->getVar('sct_visit_frequency_month');


        $data = [
            "sct_name" => $sct_name,
            "sct_services" => $sct_services,
            "sct_pickup_and_drop" => $sct_pickup_and_drop,
            "sct_excess_discount" => $sct_excess_discount,
            "sct_sa_incentive" => $sct_sa_incentive,
            "sct_discount" => $sct_discount,
            'sct_visit_frequency_month' => $sct_visit_frequency_month,
            'sct_created_on' => $date->format('Y-m-d H:i:s'),
            'sct_created_by' => $tokendata['uid'],
            'sct_updated_by' => $tokendata['uid'],
            'sct_updated_on' => $date->format('Y-m-d H:i:s'),
            'sct_delete_flag' => 0,
        ];

        $InsertedTier = $contract_tier->insert($data);

        if ($InsertedTier) {
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

    public function deleteServiceContractTier()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        // Auth checks
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

        if (! $tokendata) {
            return $this->respond(['ret_data' => 'unauthorized'], 401);
        }

        $contract_tier = new ServiceContractTierModel();
        $date = new DateTime();


        $id = $this->request->getVar('sct_id');


        $data = [
            'sct_id' => $id,
            'sct_updated_by' => $tokendata['uid'],
            'sct_updated_on' => $date->format('Y-m-d H:i:s'),
            'sct_delete_flag' => 1,
        ];

        $InsertedTier = $contract_tier->where('sct_id', $id)->set($data)->update();

        if ($InsertedTier) {
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


    public function getServiceContractCustomers()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        // Auth checks
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

        if (! $tokendata) {
            return $this->respond(['ret_data' => 'unauthorized'], 401);
        }

        $service_contract = new ServiceContractModel();

        $cutomersContracts = $service_contract
            ->select('service_contract.*, service_contract_tier.sct_name, service_contract_vehicle.*, cust_data_laabs.*')
            ->where('sc_delete_flag', 0)
            ->join('service_contract_tier', 'service_contract_tier.sct_id=service_contract.sc_contract_tier_id', 'left')
            ->join('service_contract_vehicle', 'service_contract_vehicle.scv_id=service_contract.sc_v_id', 'left')
            ->join('cust_data_laabs', 'cust_data_laabs.customer_code=service_contract.sc_cust_id', 'left')
            ->findAll();



        if ($cutomersContracts) {
            $response = [
                'ret_data' => 'success',
                'cutomersContracts' => $cutomersContracts,
            ];
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
        }

        return $this->respond($response, 200);
    }


    public function getVehicleDetailsByVinNo()
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

            $url = "http://almaraghi.fortiddns.com:35147/maraghi_lead_test/index.php/DataFetch/getVehicleDetailsByRegNo"; // Replace with your actual API URL

            $data = json_encode([
                'vin_No' => $this->request->getVar('vin_No'),
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
                    'CHASSIS_NO'  => $curlResponse['data']['CHASSIS_NO'] ?? null,
                    'MODEL_YEAR'  => $curlResponse['data']['MODEL_YEAR'] ?? null,
                    'REG_NO'  => $curlResponse['data']['REG_NO'] ?? null,
                    'CUSTOMER_NAME'  => $curlResponse['data']['CUSTOMER_NAME'] ?? null,
                    'CUSTOMER_CODE'  => $curlResponse['data']['CUSTOMER_CODE'] ?? null,
                    'PHONE'  => $curlResponse['data']['PHONE'] ?? null,
                    'MODEL_NAME'  => $curlResponse['data']['MODEL_NAME'] ?? null,
                ],
                'ret_data' => 'success'
            ];

            return $this->response->setJSON($response);
        }
    }
}
