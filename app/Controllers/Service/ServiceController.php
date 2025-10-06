<?php

namespace App\Controllers\Service;

use CodeIgniter\RESTful\ResourceController;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\Service\VehicleMaster;
use App\Models\Service\ServiceMaster;
use App\Models\Service\ServiceEngines;
use App\Models\Service\ServiceVin;
use App\Models\Service\ServiceKM;
use App\Models\Service\ServiceParts;
use App\Models\Service\ServiceLabour;
use App\Models\ServicePackage\EngineMasterModel;




class ServiceController extends ResourceController
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
        $EngineMasterModel = new EngineMasterModel();
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

            // $engineNo = $VehicleMaster->where("veh_delete_flag !=", 1)
            //     ->distinct()
            //     ->select('veh_enginemaster')
            //     ->findAll();
            
            $engineNo = $EngineMasterModel->where("eng_delete_flag", 0)
                ->select('eng_id,eng_no,eng_labour_factor')
                ->findAll();

            if ($engineNo) {
                $response = [
                    'ret_data' => 'success',
                    'engineNo' => $engineNo,
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

    public function getVinGroups()
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

            $engineNo = $this->request->getVar('selectedEngines');

            $Vingroups = $VehicleMaster->where("veh_delete_flag !=", 1)
                ->whereIn("veh_enginemaster", $engineNo)
                ->distinct()
                ->select('veh_vingroup_master')
                ->findAll();

            if ($Vingroups) {
                $response = [
                    'ret_data' => 'success',
                    'vingroups' => $Vingroups,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getVehicleVariants()
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

            $engineNo = $this->request->getVar('selectedEngines');
            $VinNo = $this->request->getVar('selectedVinGroups');


            $vehicleVariant = $VehicleMaster->where("veh_delete_flag !=", 1)
                ->whereIn("veh_enginemaster", $engineNo)
                ->whereIn("veh_vingroup_master", $VinNo)
                ->distinct()
                ->select('veh_variant_master')
                ->findAll();

            if ($vehicleVariant) {
                $response = [
                    'ret_data' => 'success',
                    'vehicleVariant' => $vehicleVariant,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function saveServices()
    {
        $ServiceMaster = new ServiceMaster();
        $ServiceEngines = new ServiceEngines();
        $ServiceVin = new ServiceVin();
        $ServiceKM = new ServiceKM();
        $ServiceParts = new ServiceParts();
        $ServiceLabour = new ServiceLabour();
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



            $builder = $this->db->table('sequence_data');
            $builder->selectMax('service_seq');
            $query = $builder->get();
            $row = $query->getRow();
            $code = $row->service_seq;
            $seqvalfinal = $row->service_seq;
            if (strlen($row->service_seq) == 1) {
                $code = "ALMSC-000" . $row->service_seq;
            } else if (strlen($row->service_seq) == 2) {
                $code = "ALMSC-00" . $row->service_seq;
            } else if (strlen($row->service_seq) == 3) {
                $code = "ALMSC-0" . $row->service_seq;
            } else {
                $code = "ALMSC-" . $row->service_seq;
            }


            $masterdata = [
                'sm_name' => $this->request->getVar('selectedservice'),
                'sm_code' => $code,
                'sm_created_on' => date("Y-m-d H:i:s"),
                'sm_created_by' => $tokendata['uid'],
                'sm_updated_on' => date("Y-m-d H:i:s"),
                'sm_updated_by' => $tokendata['uid'],
            ];

            $sm_id = $ServiceMaster->insert($masterdata);

            if ($sm_id) {
                $builder = $this->db->table('sequence_data');
                $builder->set('service_seq', ++$seqvalfinal);
                $builder->update();

                $selectedEngines = $this->request->getVar('selectedEngines');
                $selectedVinGroups = $this->request->getVar('selectedVinGroups');
                $serviceKilometer = $this->request->getVar('serviceKM');
                $parts = $this->request->getVar('parts');
                $Labour = $this->request->getVar('Labour');



                $service_engine = array();
                foreach ($selectedEngines as $engineNo) {
                    $service_engine[] = array(
                        'se_sm_id' => $sm_id,
                        'se_numbers' => $engineNo,
                        'se_created_on' => date("Y-m-d H:i:s"),
                        'se_created_by' => $tokendata['uid'],
                        'se_updated_on' => date("Y-m-d H:i:s"),
                        'se_updated_by' => $tokendata['uid'],
                    );
                }
                $service_engine_id = $ServiceEngines->insertBatch($service_engine);


                $service_vin = array();
                foreach ($selectedVinGroups as $vin) {
                    $service_vin[] = array(
                        'sv_sm_id' => $sm_id,
                        'sv_numbers' => $vin,
                        'sv_created_on' => date("Y-m-d H:i:s"),
                        'sv_created_by' => $tokendata['uid'],
                        'sv_updated_on' => date("Y-m-d H:i:s"),
                        'sv_updated_by' => $tokendata['uid'],
                    );
                }
                $service_vin_groups = $ServiceVin->insertBatch($service_vin);

                $service_kilometer = array();
                foreach ($serviceKilometer as $km) {
                    $service_kilometer[] = array(
                        'skm_sm_id' => $sm_id,
                        'skm_kilometers' => $km,
                        'skm_created_on' => date("Y-m-d H:i:s"),
                        'skm_created_by' => $tokendata['uid'],
                        'skm_updated_on' => date("Y-m-d H:i:s"),
                        'skm_updated_by' => $tokendata['uid'],
                    );
                }
                $service_km = $ServiceKM->insertBatch($service_kilometer);



                $service_parts = array();
                foreach ($parts as $serviceparts) {
                    $service_parts[] = array(
                        'sp_sm_id' => $sm_id,
                        'sp_pm_id' => $serviceparts->id,
                        'sp_qty' => $serviceparts->qty,
                        'sp_created_on' => date("Y-m-d H:i:s"),
                        'sp_created_by' => $tokendata['uid'],
                        'sp_updated_on' => date("Y-m-d H:i:s"),
                        'sp_updated_by' => $tokendata['uid'],
                    );
                }
                $service_part = $ServiceParts->insertBatch($service_parts);



                if (!empty($Labour)) {
                    $service_Labour = array();
                    foreach ($Labour as $servicelabour) {
                        $service_Labour[] = array(
                            'sl_sm_id' => $sm_id,
                            'sl_lm_id' => $servicelabour->id,
                            'sl_price' => $servicelabour->PRICE,
                            'sl_created_on' => date("Y-m-d H:i:s"),
                            'sl_created_by' => $tokendata['uid'],
                            'sl_updated_on' => date("Y-m-d H:i:s"),
                            'sl_updated_by' => $tokendata['uid'],
                        );
                    }
                    $service_labour = $ServiceLabour->insertBatch($service_Labour);
                }
            }

            if ($service_part) {
                $response = [
                    'ret_data' => 'success',
                    'service_part' => $service_part,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getAllServices()
    {
        $ServiceMaster = new ServiceMaster();
        $ServiceEngines = new ServiceEngines();
        $ServiceVin = new ServiceVin();
        $ServiceKM = new ServiceKM();
        $ServiceParts = new ServiceParts();
        $ServiceLabour = new ServiceLabour();
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

            $services = $ServiceMaster->distinct()
                ->select('service_master.*')
                ->where('sm_delete_flag !=', 1)
                ->findAll();

            $service_details = [];
            if (sizeof($services) > 0) {
                foreach ($services as $service) {
                    $service['Engine_No'] = [];
                    $service['Vin_no'] = [];
                    $service['km'] = [];
                    $engine = $ServiceEngines->select("service_engines.*")
                        ->where('se_sm_id', $service['sm_id'])
                        ->where('se_delete_flag !=', 1)
                        ->findAll();
                    $service['Engine_No'] = $engine ? $engine : [];
                    $vin = $ServiceVin->select("service_vin.*")
                        ->where('sv_sm_id', $service['sm_id'])
                        ->where('sv_delete_flag !=', 1)
                        ->findAll();
                    $service['Vin_no'] = $vin ? $vin : [];
                    $km = $ServiceKM->select("service_kilometers.*")
                        ->where('skm_sm_id', $service['sm_id'])
                        ->where('skm_delete_flag !=', 1)
                        ->findAll();
                    $service['km'] = $km ? $km : [];
                    $parts = $ServiceParts->select("service_parts.*,parts_master.*")
                        ->join('parts_master', 'parts_master.pm_id=sp_pm_id', 'left')
                        ->where('sp_sm_id', $service['sm_id'])
                        ->where('sp_delete_flag !=', 1)
                        ->findAll();
                    $service['parts'] = $parts ? $parts : [];
                    $labour = $ServiceLabour->select("service_labour.*,labour_master.*")
                        ->join('labour_master', 'labour_master.lm_id=sl_lm_id', 'left')
                        ->where('sl_sm_id', $service['sm_id'])
                        ->where('sl_delete_flag !=', 1)
                        ->findAll();
                    $service['labour'] = $labour ? $labour : [];
                    array_push($service_details, $service);
                }
            }

            if ($service_details) {
                $response = [
                    'ret_data' => 'success',
                    'services' => $service_details,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getServiceDetails()
    {

        $ServiceMaster = new ServiceMaster();
        $ServiceEngines = new ServiceEngines();
        $ServiceVin = new ServiceVin();
        $ServiceKM = new ServiceKM();
        $ServiceParts = new ServiceParts();
        $ServiceLabour = new ServiceLabour();
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

            $service_id =  $this->request->getVar('id');

            $services = $ServiceMaster->select('service_master.*')
                ->where('sm_id ', $service_id)
                ->where('sm_delete_flag !=', 1)->first();
            if (sizeof($services) > 0) {
                $services['Engine_No'] = [];
                $services['Vin_no'] = [];
                $services['km'] = [];
                $engine = $ServiceEngines->where('se_sm_id', $service_id)->where('se_delete_flag !=', 1)
                    ->findAll();
                $services['Engine_No'] = $engine ? $engine : [];
                $vin = $ServiceVin->where('sv_sm_id', $service_id)->where('sv_delete_flag !=', 1)
                    ->findAll();
                $services['Vin_no'] = $vin ? $vin : [];
                $km = $ServiceKM->where('skm_sm_id', $service_id)->where('skm_delete_flag !=', 1)
                    ->findAll();
                $services['km'] = $km ? $km : [];
                $parts = $ServiceParts->select("service_parts.*,parts_master.*,brand_list.brand_name,brand_list.brand_code,")
                    ->join('parts_master', 'parts_master.pm_id=sp_pm_id', 'left')
                    ->join('brand_list', 'brand_list.brand_id=pm_brand', 'left')
                    ->where('sp_sm_id', $service_id)
                    ->where('sp_delete_flag !=', 1)
                    ->findAll();
                $services['parts'] = $parts ? $parts : [];
                $labour = $ServiceLabour->select("service_labour.*,labour_master.*")
                    ->join('labour_master', 'labour_master.lm_id=sl_lm_id', 'left')
                    ->where('sl_sm_id', $service_id)
                    ->where('sl_delete_flag !=', 1)
                    ->findAll();
                $services['labour'] = $labour ? $labour : [];
            }

            if ($services) {
                $response = [
                    'ret_data' => 'success',
                    'services' => $services,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function updateServices()
    {
        $ServiceMaster = new ServiceMaster();
        $ServiceEngines = new ServiceEngines();
        $ServiceVin = new ServiceVin();
        $ServiceKM = new ServiceKM();
        $ServiceParts = new ServiceParts();
        $ServiceLabour = new ServiceLabour();
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
            $sm_id = $this->request->getVar('sm_id');

            $masterdata = [
                'sm_name' => $this->request->getVar('sm_name'),
                'sm_updated_on' => date("Y-m-d H:i:s"),
                'sm_updated_by' => $tokendata['uid'],
            ];

            $ServiceMaster_update = $ServiceMaster->where('sm_id', $sm_id)->set($masterdata)->update();

            $engines = $this->request->getVar('Engine_No');
            $vin_nos = $this->request->getVar('Vin_no');
            $serviceKilometer = $this->request->getVar('km');
            $parts = $this->request->getVar('parts');
            $labour = $this->request->getVar('labour');

            $service_engine = array();
            $service_engine_insert_data = array();
            foreach ($engines as $engineNo) {
                if ($engineNo->se_id != '0') {
                    $service_engine[] = array(
                        'se_id' => $engineNo->se_id,
                        'se_sm_id' => $sm_id,
                        'se_numbers' => $engineNo->se_numbers,
                        'se_delete_flag' => $engineNo->se_delete_flag,
                        'se_updated_on' => date("Y-m-d H:i:s"),
                        'se_updated_by' => $tokendata['uid'],
                    );
                } else {
                    if ($engineNo->se_delete_flag != '1') {
                        $service_engine_insert_data[] = array(
                            'se_sm_id' => $sm_id,
                            'se_numbers' => $engineNo->se_numbers,
                            'se_created_on' => date("Y-m-d H:i:s"),
                            'se_created_by' => $tokendata['uid'],
                            'se_updated_on' => date("Y-m-d H:i:s"),
                            'se_updated_by' => $tokendata['uid'],
                        );
                    }
                }
            }
            if (!empty($service_engine_insert_data)) {
                $ServiceEngines->insertBatch($service_engine_insert_data);
            }
            if (!empty($service_engine)) {
                $ServiceEngines->updateBatch($service_engine, 'se_id');
            }

            $service_vin_data = array();
            $service_vin_insert_data = array();
            foreach ($vin_nos as $vin) {
                if ($vin->sv_id != '0') {
                    $service_vin_data[] = array(
                        'sv_id' => $vin->sv_id,
                        'sv_sm_id' => $sm_id,
                        'sv_numbers' => $vin->sv_numbers,
                        'sv_delete_flag' => $vin->sv_delete_flag,
                        'sv_updated_on' => date("Y-m-d H:i:s"),
                        'sv_updated_by' => $tokendata['uid'],
                    );
                } else {
                    if ($vin->sv_delete_flag != '1') {
                        $service_vin_insert_data[] = array(
                            'sv_sm_id' => $sm_id,
                            'sv_numbers' => $vin->sv_numbers,
                            'sv_created_on' => date("Y-m-d H:i:s"),
                            'sv_created_by' => $tokendata['uid'],
                            'sv_updated_on' => date("Y-m-d H:i:s"),
                            'sv_updated_by' => $tokendata['uid'],
                        );
                    }
                }
            }
            if (!empty($service_vin_insert_data)) {
                $ServiceVin->insertBatch($service_vin_insert_data);
            }
            if (!empty($service_vin_data)) {
                $ServiceVin->updateBatch($service_vin_data, 'sv_id');
            }


            $service_kilometer = array();
            $service_kilometer_insert_data = array();
            foreach ($serviceKilometer as $km) {
                if ($km->skm_id != '0') {
                    $service_kilometer[] = array(
                        'skm_id' =>  $km->skm_id,
                        'skm_sm_id' =>  $sm_id,
                        'skm_kilometers' => $km->skm_kilometers,
                        'skm_delete_flag' => $km->skm_delete_flag,
                        'skm_updated_on' => date("Y-m-d H:i:s"),
                        'skm_updated_by' => $tokendata['uid'],
                    );
                } else {
                    if ($km->skm_delete_flag != '1') {
                        $service_kilometer_insert_data[] = array(
                            'skm_sm_id' => $sm_id,
                            'skm_kilometers' => $km->skm_kilometers,
                            'skm_updated_on' => date("Y-m-d H:i:s"),
                            'skm_updated_by' => $tokendata['uid'],
                            'skm_created_by' => $tokendata['uid'],
                            'skm_created_on' => date("Y-m-d H:i:s"),
                        );
                    }
                }
            }
            if (!empty($service_kilometer_insert_data)) {
                $ServiceKM->insertBatch($service_kilometer_insert_data);
            }
            if (!empty($service_kilometer)) {
                $ServiceKM->updateBatch($service_kilometer, 'skm_id');
            }


            $service_parts = array();
            $service_parts_insert_data = array();

            foreach ($parts as $serviceparts) {
                if ($serviceparts->sp_id != '0') {
                    $service_parts[] = array(
                        'sp_id'  => $serviceparts->sp_id,
                        'sp_sm_id' => $sm_id,
                        'sp_pm_id' => $serviceparts->pm_id,
                        'sp_qty' => $serviceparts->sp_qty,
                        'sp_delete_flag' => $serviceparts->sp_delete_flag,
                        'sp_updated_on' => date("Y-m-d H:i:s"),
                        'sp_updated_by' => $tokendata['uid'],
                    );
                } else {
                    if ($serviceparts->sp_delete_flag != '1') {
                        $service_parts_insert_data[] = array(
                            'sp_sm_id' => $sm_id,
                            'sp_pm_id' => $serviceparts->pm_id,
                            'sp_qty' => $serviceparts->sp_qty,
                            'sp_created_on' => date("Y-m-d H:i:s"),
                            'sp_created_by' => $tokendata['uid'],
                            'sp_updated_on' => date("Y-m-d H:i:s"),
                            'sp_updated_by' => $tokendata['uid'],
                        );
                    }
                }
            }

            if (!empty($service_parts)) {
                $ServiceParts->updateBatch($service_parts, 'sp_id');
            }

            if (!empty($service_parts_insert_data)) {
                $ServiceParts->insertBatch($service_parts_insert_data);
            }

            // $ServiceParts->updateBatch($service_parts, 'sp_id');
            //$service_part = $ServiceParts->insertBatch($service_parts);

            $service_Labour = array();
            $service_Labour_insert_data = array();
            foreach ($labour as $servicelabour) {
                if ($servicelabour->sl_id != '0') {
                    $service_Labour[] = array(
                        'sl_id' => $servicelabour->sl_id,
                        'sl_sm_id' => $sm_id,
                        'sl_lm_id' => $servicelabour->lm_id,
                        'sl_delete_flag' => $servicelabour->sl_delete_flag,
                        'sl_price' => $servicelabour->sl_price,
                        'sl_updated_on' => date("Y-m-d H:i:s"),
                        'sl_updated_by' => $tokendata['uid'],
                    );
                } else {
                    if ($servicelabour->sl_delete_flag != '1') {
                        $service_Labour_insert_data[] = array(
                            'sl_sm_id' => $sm_id,
                            'sl_lm_id' => $servicelabour->lm_id,
                            'sl_price' => $servicelabour->sl_price,
                            'sl_created_on' => date("Y-m-d H:i:s"),
                            'sl_created_by' => $tokendata['uid'],
                            'sl_updated_on' => date("Y-m-d H:i:s"),
                            'sl_updated_by' => $tokendata['uid'],
                        );
                    }
                }
            }
            if (!empty($service_Labour)) {
                $ServiceLabour->updateBatch($service_Labour, 'sl_id');
            }

            if (!empty($service_Labour_insert_data)) {
                $ServiceLabour->insertBatch($service_Labour_insert_data);
            }

            if ($ServiceMaster_update) {
                $response = [
                    'ret_data' => 'success',
                    'ServiceMaster_update' => $ServiceMaster_update,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function deleteService()
    {
        $ServiceMaster = new ServiceMaster();
        $ServiceEngines = new ServiceEngines();
        $ServiceVin = new ServiceVin();
        $ServiceKM = new ServiceKM();
        $ServiceParts = new ServiceParts();
        $ServiceLabour = new ServiceLabour();
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

            $sm_id = $this->request->getVar('sm_id');

            $masterdata = [
                'sm_delete_flag' => '1',
                'sm_updated_by' => $tokendata['uid'],
                'sm_updated_on' => date("Y-m-d H:i:s"),
            ];

            $ServiceMaster_update = $ServiceMaster->where('sm_id', $sm_id)->set($masterdata)->update();
            if ($ServiceMaster_update) {
                $response = [
                    'ret_data' => 'success',
                    'ServiceMaster_update' => $ServiceMaster_update,
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
