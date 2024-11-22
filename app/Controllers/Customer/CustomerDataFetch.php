<?php

namespace App\Controllers\Customer;

use App\Models\Customer\MaraghiJobModel;
use App\Models\Customer\MaraghiVehicleModel;
use CodeIgniter\RESTful\ResourceController;
use App\Models\Customer\MaragiCustomerModel;
use CodeIgniter\API\ResponseTrait;
use App\Models\SuperAdminModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;
use App\Models\Customer\JobSubStatusTrackerModel;

class CustomerDataFetch extends ResourceController
{

    use ResponseTrait;
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    /**
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function customer_create()
    {
        $common = new Common();
        $valid = new Validation();

        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin') {
            $result = $this->request->getVar('customers');
            $updateCus = array();
            $insertCus = array();
            $laabscus = new MaragiCustomerModel();
            $last_cus = $laabscus->select('customer_code')->orderby('customer_code', "desc")->limit(1)->first();
            foreach (json_decode($result) as $value) {
                if ($value->CUSTOMER_CODE <= $last_cus['customer_code']) {
                    $updateCus[] = array(
                        'customer_code' => $value->CUSTOMER_CODE,
                        'customer_type' => $value->CUSTOMER_TYPE,
                        'customer_title' => $value->CUSTOMER_TITLE,
                        'customer_name' => $value->CUSTOMER_NAME,
                        'addr1' => $value->ADDR1,
                        'po_box' => $value->PO_BOX,
                        'city' => $value->CITY,
                        'country' => $value->COUNTRY,
                        'phone' => $value->PHONE,
                        'mobile' => $value->MOBILE,
                        'sms_option' => $value->SMS_OPTION,
                        'contact_person' => $value->CONTACT_PERSON,
                        'contact_phone' => $value->CONTACT_PHONE,
                        'labs_created_on' => $value->CREATED_ON,
                        'lang_pref' => $value->LANG_PREF,
                        'cust_scn_id' => $value->cust_scn_id
                    );
                } else {
                    $insertCus[] = array(
                        'customer_code' => $value->CUSTOMER_CODE,
                        'customer_type' => $value->CUSTOMER_TYPE,
                        'customer_title' => $value->CUSTOMER_TITLE,
                        'customer_name' => $value->CUSTOMER_NAME,
                        'addr1' => $value->ADDR1,
                        'po_box' => $value->PO_BOX,
                        'city' => $value->CITY,
                        'country' => $value->COUNTRY,
                        'phone' => $value->PHONE,
                        'mobile' => $value->MOBILE,
                        'sms_option' => $value->SMS_OPTION,
                        'contact_person' => $value->CONTACT_PERSON,
                        'contact_phone' => $value->CONTACT_PHONE,
                        'labs_created_on' => $value->CREATED_ON,
                        'lang_pref' => $value->LANG_PREF,
                        'cust_scn_id' => $value->cust_scn_id,
                        'phon_uniq' => substr($value->PHONE, -7)
                    );
                }
            }
            $this->db->transBegin();
            $builder = $this->db->table('cust_data_laabs');
            if (sizeof($updateCus) > 0) {
                $builder->updateBatch($updateCus, 'customer_code');
            }
            if (sizeof($insertCus) > 0) {
                $builder->insertBatch($insertCus);
            }
            $ret_data['ret_data'] = "fail";
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
            } else {
                $this->db->transCommit();

                $this->insertUserLog('New Customer Created ', $tokendata['uid']);

                $ret_data['ret_data'] = "success";
            }
            return $this->respond($ret_data, 200);
        }
    }

    public function customer_vehicles()
    {
        $common = new Common();
        $valid = new Validation();

        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin') {
            $result = $this->request->getVar('vehicles');
            $updateVeh = array();
            $insertVeh = array();
            $laabsveh = new MaraghiVehicleModel();
            $last_veh = $laabsveh->select('vehicle_id')->orderby('vehicle_id', "desc")->limit(1)->first();
            foreach (json_decode($result) as $value) {
                if ($value->VEHICLE_ID <= $last_veh['vehicle_id']) {
                    $updateVeh[] = array(
                        'vehicle_id' => $value->VEHICLE_ID,
                        'reg_no' => $value->REG_NO,
                        'model_name' => $value->MODEL_NAME,
                        'family_code' => $value->FAMILY_CODE,
                        'family_name' => $value->FAMILY_NAME,
                        'chassis_no' => $value->CHASSIS_NO,
                        'brand_code' => $value->BRAND_CODE,
                        'model_year' => $value->MODEL_YEAR,
                        'customer_code' => $value->CUSTOMER_CODE,
                        'miles_done' => $value->MILES_DONE,
                        'creation_date' => $value->CREATION_DATE,
                        'veh_scn' => $value->veh_scn
                    );
                } else {
                    $insertVeh[] = array(
                        'vehicle_id' => $value->VEHICLE_ID,
                        'reg_no' => $value->REG_NO,
                        'model_name' => $value->MODEL_NAME,
                        'family_code' => $value->FAMILY_CODE,
                        'family_name' => $value->FAMILY_NAME,
                        'chassis_no' => $value->CHASSIS_NO,
                        'brand_code' => $value->BRAND_CODE,
                        'model_year' => $value->MODEL_YEAR,
                        'customer_code' => $value->CUSTOMER_CODE,
                        'miles_done' => $value->MILES_DONE,
                        'creation_date' => $value->CREATION_DATE,
                        'veh_scn' => $value->veh_scn
                    );
                }
            }
            $this->db->transBegin();
            $builder = $this->db->table('cust_veh_data_laabs');
            if (sizeof($updateVeh) > 0) {
                $builder->updateBatch($updateVeh, 'vehicle_id');
            }
            if (sizeof($insertVeh) > 0) {
                $builder->insertBatch($insertVeh);
            }
            $ret_data['ret_data'] = "fail";
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
            } else {
                $this->db->transCommit();
                $ret_data['ret_data'] = "success";
            }
            return $this->respond($ret_data, 200);
        }
    }

    // public function customer_jobcards()
    // {
    //     $common = new Common();
    //     $valid = new Validation();

    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata['aud'] == 'superadmin') {
    //         $result = $this->request->getVar('jobs');
    //         $updateVeh = array();
    //         $insertVeh = array();
    //         $laabsveh = new MaraghiJobModel();
    //         $last_veh = $laabsveh->select('job_no,job_status')->orderby('job_no', "desc")->limit(1)->first();
    //         foreach (json_decode($result) as $value) {
    //             if ($value->JOB_NO <= $last_veh['job_no']) {
    //                 if ($last_veh['job_status'] != $value->JOB_STATUS) {
    //                     $subStatusTracker = new JobSubStatusTrackerModel();
    //                     $sub_stat_desc = "";
    //                     $sub_stat = 1;
    //                     if ($value->JOB_STATUS == "OPN") {
    //                         $sub_stat_desc = "Job opened in laabs";
    //                         $sub_stat = 1;
    //                     } else if ($value->JOB_STATUS == "WIP") {
    //                         $sub_stat_desc = "Updated to work in progress(LAABS)";
    //                         $sub_stat = 2;
    //                     } else if ($value->JOB_STATUS == "TST") {
    //                         $sub_stat_desc = "Updated to road test(LAABS)";
    //                         $sub_stat = 14;
    //                     } else if ($value->JOB_STATUS == "SUS") {
    //                         $sub_stat_desc = "Updated to suspended(LAABS)";
    //                         $sub_stat = 19;
    //                     } else if ($value->JOB_STATUS == "COM") {
    //                         $sub_stat_desc = "Updated to completed(LAABS)";
    //                         $sub_stat = 15;
    //                     } else if ($value->JOB_STATUS == "CAN") {
    //                         $sub_stat_desc = "Updated to cancelled(LAABS)";
    //                         $sub_stat = 18;
    //                     } else if ($value->JOB_STATUS == "CLO") {
    //                         $sub_stat_desc = "Updated to closed(LAABS)";
    //                         $sub_stat = 16;
    //                     } else if ($value->JOB_STATUS == "INV") {
    //                         $sub_stat_desc = "Updated to invoiced(LAABS)";
    //                         $sub_stat = 17;
    //                     }
    //                     $data = [
    //                         'jbsc_job_no' =>$value->JOB_NO,
    //                         'jbsc_main_status' => $value->JOB_STATUS,
    //                         'jbsc_sub_status' => $sub_stat,
    //                         'jbsc_sub_description' => $sub_stat_desc,
    //                         'jbsc_updated_by' => 1,
    //                         'jbsc_updated_on' => date("Y-m-d H:i:s"),
    //                     ];
    //                     //$subinsert = $subStatusTracker->insert($data);
    //                 }
    //                 $updateVeh[] = array(
    //                     'job_no' => $value->JOB_NO,
    //                     'customer_no' => $value->CUSTOMER_NO,
    //                     'vehicle_id' => $value->VEHICLE_ID,
    //                     'car_reg_no' => $value->CAR_REG_NO,
    //                     'job_open_date' => $value->JOB_OPEN_DATE,
    //                     'job_close_date' => $value->JOB_CLOSE_DATE,
    //                     'received_date' => $value->RECEIVED_DATE,
    //                     'promised_date' => $value->PROMISED_DATE,
    //                     'extended_promise_date' => $value->EXTENDED_PROMISE_DATE,
    //                     'speedometer_reading' => $value->SPEEDOMETER_READING,
    //                     'delivered_date' => $value->DELIVERED_DATE,
    //                     'invoice_no' => $value->INVOICE_NO,
    //                     'invoice_date' => $value->INVOICE_DATE,
    //                     'sa_emp_id' => $value->SA_EMP_ID,
    //                     'user_name' => $value->USER_NAME,
    //                     'job_status' => $value->JOB_STATUS,
    //                     'job_scn_id' => $value->job_scn_id
    //                 );
    //             } else {
    //                 $insertVeh[] = array(
    //                     'job_no' => $value->JOB_NO,
    //                     'customer_no' => $value->CUSTOMER_NO,
    //                     'vehicle_id' => $value->VEHICLE_ID,
    //                     'car_reg_no' => $value->CAR_REG_NO,
    //                     'job_open_date' => $value->JOB_OPEN_DATE,
    //                     'job_close_date' => $value->JOB_CLOSE_DATE,
    //                     'received_date' => $value->RECEIVED_DATE,
    //                     'promised_date' => $value->PROMISED_DATE,
    //                     'extended_promise_date' => $value->EXTENDED_PROMISE_DATE,
    //                     'speedometer_reading' => $value->SPEEDOMETER_READING,
    //                     'delivered_date' => $value->DELIVERED_DATE,
    //                     'invoice_no' => $value->INVOICE_NO,
    //                     'invoice_date' => $value->INVOICE_DATE,
    //                     'sa_emp_id' => $value->SA_EMP_ID,
    //                     'user_name' => $value->USER_NAME,
    //                     'job_status' => $value->JOB_STATUS,
    //                     'job_scn_id' => $value->job_scn_id
    //                 );
    //                 $subStatusTracker = new JobSubStatusTrackerModel();
    //                 $sub_stat_desc = "";
    //                 $sub_stat = 1;
    //                 if ($value->JOB_STATUS == "OPN") {
    //                     $sub_stat_desc = "Job opened in laabs";
    //                     $sub_stat = 1;
    //                 } else if ($value->JOB_STATUS == "WIP") {
    //                     $sub_stat_desc = "Updated to work in progress(LAABS)";
    //                     $sub_stat = 2;
    //                 } else if ($value->JOB_STATUS == "TST") {
    //                     $sub_stat_desc = "Updated to road test(LAABS)";
    //                     $sub_stat = 14;
    //                 } else if ($value->JOB_STATUS == "SUS") {
    //                     $sub_stat_desc = "Updated to suspended(LAABS)";
    //                     $sub_stat = 19;
    //                 } else if ($value->JOB_STATUS == "COM") {
    //                     $sub_stat_desc = "Updated to completed(LAABS)";
    //                     $sub_stat = 15;
    //                 } else if ($value->JOB_STATUS == "CAN") {
    //                     $sub_stat_desc = "Updated to cancelled(LAABS)";
    //                     $sub_stat = 18;
    //                 } else if ($value->JOB_STATUS == "CLO") {
    //                     $sub_stat_desc = "Updated to closed(LAABS)";
    //                     $sub_stat = 16;
    //                 } else if ($value->JOB_STATUS == "INV") {
    //                     $sub_stat_desc = "Updated to invoiced(LAABS)";
    //                     $sub_stat = 17;
    //                 }
    //                 $data = [
    //                     'jbsc_job_no' =>$value->JOB_NO,
    //                     'jbsc_main_status' => $value->JOB_STATUS,
    //                     'jbsc_sub_status' => $sub_stat,
    //                     'jbsc_sub_description' => $sub_stat_desc,
    //                     'jbsc_updated_by' => 1,
    //                     'jbsc_updated_on' => date("Y-m-d H:i:s"),
    //                 ];
    //                 //$subinsert = $subStatusTracker->insert($data);
    //             }
    //         }
    //         $this->db->transBegin();
    //         $builder = $this->db->table('cust_job_data_laabs');
    //         if (sizeof($updateVeh) > 0) {
    //             $builder->updateBatch($updateVeh, 'job_no');
    //         }
    //         if (sizeof($insertVeh) > 0) {
    //             $builder->insertBatch($insertVeh);
    //         }
    //         $ret_data['ret_data'] = "fail";
    //         if ($this->db->transStatus() === false) {
    //             $this->db->transRollback();
    //         } else {
    //             $this->db->transCommit();
    //             $ret_data['ret_data'] = "success";
    //         }
    //         return $this->respond($ret_data, 200);
    //     }
    // }

    public function customer_jobcards()
    {
        $common = new Common();
        $valid = new Validation();

        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin') {
            $result = $this->request->getVar('jobs');
            $updateVeh = array();
            $insertVeh = array();
            $laabsveh = new MaraghiJobModel();
            $last_veh = $laabsveh->select('job_no,job_status')->orderby('job_no', "desc")->limit(1)->first();
            foreach (json_decode($result) as $value) {
                if ($value->JOB_NO <= $last_veh['job_no']) {
                    $job_card =  $laabsveh->select('job_no,job_status,jb_sub_status')->where('job_no', $value->JOB_NO)->find();

                    $master_stat = null;
                    if (sizeof($job_card) > 0) {
                        $master_stat = $job_card[0]['jb_sub_status'];
                        if ($job_card[0]['job_status'] != $value->JOB_STATUS) {
                            $subStatusTracker = new JobSubStatusTrackerModel();

                            $sub_stat_desc = "";
                            $sub_stat = 1;
                            if ($value->JOB_STATUS == "OPN") {
                                $sub_stat_desc = "Job opened in laabs";
                                $sub_stat = 1;
                            } else if ($value->JOB_STATUS == "OPN") {
                                $sub_stat_desc = "Job opened in laabs";
                                $sub_stat = 1;
                            } else if ($value->JOB_STATUS == "WIP") {
                                $sub_stat_desc = "Updated to work in progress(LAABS)";
                                $sub_stat = 2;
                            } else if ($value->JOB_STATUS == "TST") {
                                $sub_stat_desc = "Updated to road test(LAABS)";
                                $sub_stat = 14;
                            } else if ($value->JOB_STATUS == "SUS") {
                                $sub_stat_desc = "Updated to suspended(LAABS)";
                                $sub_stat = 19;
                            } else if ($value->JOB_STATUS == "COM") {
                                $sub_stat_desc = "Updated to completed(LAABS)";
                                $sub_stat = 15;
                            } else if ($value->JOB_STATUS == "CAN") {
                                $sub_stat_desc = "Updated to cancelled(LAABS)";
                                $sub_stat = 18;
                            } else if ($value->JOB_STATUS == "CLO") {
                                $sub_stat_desc = "Updated to closed(LAABS)";
                                $sub_stat = 16;
                            } else if ($value->JOB_STATUS == "INV") {
                                $sub_stat_desc = "Updated to invoiced(LAABS)";
                                $sub_stat = 17;
                            }
                            $data = [
                                'jbsc_job_no' => $value->JOB_NO,
                                'jbsc_main_status' => $value->JOB_STATUS,
                                'jbsc_sub_status' => $sub_stat,
                                'jbsc_sub_description' => $sub_stat_desc,
                                'jbsc_updated_by' => 1,
                                'jbsc_updated_on' => date("Y-m-d H:i:s"),
                            ];
                            $subinsert = $subStatusTracker->insert($data);
                            $master_stat = $sub_stat;
                        }
                    }
                    $updateVeh[] = array(
                        'job_no' => $value->JOB_NO,
                        'customer_no' => $value->CUSTOMER_NO,
                        'vehicle_id' => $value->VEHICLE_ID,
                        'car_reg_no' => $value->CAR_REG_NO,
                        'job_open_date' => $value->JOB_OPEN_DATE,
                        'job_close_date' => $value->JOB_CLOSE_DATE,
                        'received_date' => $value->RECEIVED_DATE,
                        'promised_date' => $value->PROMISED_DATE,
                        'extended_promise_date' => $value->EXTENDED_PROMISE_DATE,
                        'speedometer_reading' => $value->SPEEDOMETER_READING,
                        'delivered_date' => $value->DELIVERED_DATE,
                        'invoice_no' => $value->INVOICE_NO,
                        'invoice_date' => $value->INVOICE_DATE,
                        'sa_emp_id' => $value->SA_EMP_ID,
                        'user_name' => $value->USER_NAME,
                        'job_status' => $value->JOB_STATUS,
                        'job_scn_id' => $value->job_scn_id,
                        'jb_sub_status' =>  $master_stat,
                    );
                    // log_message('error', $value->JOB_NO."----". $master_stat);
                    // if ($last_veh['job_status'] != $value->JOB_STATUS) {
                    //     $subStatusTracker = new JobSubStatusTrackerModel();
                    //     $sub_stat_desc = "";
                    //     $sub_stat = 1;
                    //     if ($value->JOB_STATUS == "OPN") {
                    //         $sub_stat_desc = "Job opened in laabs";
                    //         $sub_stat = 1;
                    //     } else if ($value->JOB_STATUS == "WIP") {
                    //         $sub_stat_desc = "Updated to work in progress(LAABS)";
                    //         $sub_stat = 2;
                    //     } else if ($value->JOB_STATUS == "TST") {
                    //         $sub_stat_desc = "Updated to road test(LAABS)";
                    //         $sub_stat = 14;
                    //     } else if ($value->JOB_STATUS == "SUS") {
                    //         $sub_stat_desc = "Updated to suspended(LAABS)";
                    //         $sub_stat = 19;
                    //     } else if ($value->JOB_STATUS == "COM") {
                    //         $sub_stat_desc = "Updated to completed(LAABS)";
                    //         $sub_stat = 15;
                    //     } else if ($value->JOB_STATUS == "CAN") {
                    //         $sub_stat_desc = "Updated to cancelled(LAABS)";
                    //         $sub_stat = 18;
                    //     } else if ($value->JOB_STATUS == "CLO") {
                    //         $sub_stat_desc = "Updated to closed(LAABS)";
                    //         $sub_stat = 16;
                    //     } else if ($value->JOB_STATUS == "INV") {
                    //         $sub_stat_desc = "Updated to invoiced(LAABS)";
                    //         $sub_stat = 17;
                    //     }
                    //     $data = [
                    //         'jbsc_job_no' =>$value->JOB_NO,
                    //         'jbsc_main_status' => $value->JOB_STATUS,
                    //         'jbsc_sub_status' => $sub_stat,
                    //         'jbsc_sub_description' => $sub_stat_desc,
                    //         'jbsc_updated_by' => 1,
                    //         'jbsc_updated_on' => date("Y-m-d H:i:s"),
                    //     ];
                    //     //$subinsert = $subStatusTracker->insert($data);
                    // }
                    // $updateVeh[] = array(
                    //     'job_no' => $value->JOB_NO,
                    //     'customer_no' => $value->CUSTOMER_NO,
                    //     'vehicle_id' => $value->VEHICLE_ID,
                    //     'car_reg_no' => $value->CAR_REG_NO,
                    //     'job_open_date' => $value->JOB_OPEN_DATE,
                    //     'job_close_date' => $value->JOB_CLOSE_DATE,
                    //     'received_date' => $value->RECEIVED_DATE,
                    //     'promised_date' => $value->PROMISED_DATE,
                    //     'extended_promise_date' => $value->EXTENDED_PROMISE_DATE,
                    //     'speedometer_reading' => $value->SPEEDOMETER_READING,
                    //     'delivered_date' => $value->DELIVERED_DATE,
                    //     'invoice_no' => $value->INVOICE_NO,
                    //     'invoice_date' => $value->INVOICE_DATE,
                    //     'sa_emp_id' => $value->SA_EMP_ID,
                    //     'user_name' => $value->USER_NAME,
                    //     'job_status' => $value->JOB_STATUS,
                    //     'job_scn_id' => $value->job_scn_id
                    // );
                } else {

                    $subStatusTracker = new JobSubStatusTrackerModel();
                    $sub_stat_desc = "";
                    $sub_stat = 1;
                    if ($value->JOB_STATUS == "OPN") {
                        $sub_stat_desc = "Job opened in laabs";
                        $sub_stat = 1;
                    } else if ($value->JOB_STATUS == "WIP") {
                        $sub_stat_desc = "Updated to work in progress(LAABS)";
                        $sub_stat = 2;
                    } else if ($value->JOB_STATUS == "TST") {
                        $sub_stat_desc = "Updated to road test(LAABS)";
                        $sub_stat = 14;
                    } else if ($value->JOB_STATUS == "SUS") {
                        $sub_stat_desc = "Updated to suspended(LAABS)";
                        $sub_stat = 19;
                    } else if ($value->JOB_STATUS == "COM") {
                        $sub_stat_desc = "Updated to completed(LAABS)";
                        $sub_stat = 15;
                    } else if ($value->JOB_STATUS == "CAN") {
                        $sub_stat_desc = "Updated to cancelled(LAABS)";
                        $sub_stat = 18;
                    } else if ($value->JOB_STATUS == "CLO") {
                        $sub_stat_desc = "Updated to closed(LAABS)";
                        $sub_stat = 16;
                    } else if ($value->JOB_STATUS == "INV") {
                        $sub_stat_desc = "Updated to invoiced(LAABS)";
                        $sub_stat = 17;
                    }
                    $data = [
                        'jbsc_job_no' => $value->JOB_NO,
                        'jbsc_main_status' => $value->JOB_STATUS,
                        'jbsc_sub_status' => $sub_stat,
                        'jbsc_sub_description' => $sub_stat_desc,
                        'jbsc_updated_by' => 1,
                        'jbsc_updated_on' => date("Y-m-d H:i:s"),
                    ];
                    $subinsert = $subStatusTracker->insert($data);
                    $insertVeh[] = array(
                        'job_no' => $value->JOB_NO,
                        'customer_no' => $value->CUSTOMER_NO,
                        'vehicle_id' => $value->VEHICLE_ID,
                        'car_reg_no' => $value->CAR_REG_NO,
                        'job_open_date' => $value->JOB_OPEN_DATE,
                        'job_close_date' => $value->JOB_CLOSE_DATE,
                        'promised_date' => $value->PROMISED_DATE,
                        'extended_promise_date' => $value->EXTENDED_PROMISE_DATE,
                        'speedometer_reading' => $value->SPEEDOMETER_READING,
                        'delivered_date' => $value->DELIVERED_DATE,
                        'invoice_no' => $value->INVOICE_NO,
                        'invoice_date' => $value->INVOICE_DATE,
                        'sa_emp_id' => $value->SA_EMP_ID,
                        'user_name' => $value->USER_NAME,
                        'job_status' => $value->JOB_STATUS,
                        'job_scn_id' => $value->job_scn_id,
                        'received_date' => $value->RECEIVED_DATE,
                        'jb_sub_status' =>  $sub_stat,
                    );
                }
            }
            $this->db->transBegin();
            $builder = $this->db->table('cust_job_data_laabs');
            if (sizeof($updateVeh) > 0) {
                $builder->updateBatch($updateVeh, 'job_no');
            }
            if (sizeof($insertVeh) > 0) {
                $builder->insertBatch($insertVeh);
            }
            $ret_data['ret_data'] = "fail";
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
            } else {
                $this->db->transCommit();
                $ret_data['ret_data'] = "success";
            }
            return $this->respond($ret_data, 200);
        }
    }

    // public function customer_jobcards()
    // {
    //     $common = new Common();
    //     $valid = new Validation();

    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata['aud'] == 'superadmin') {
    //         $result = $this->request->getVar('jobs');
    //         $updateVeh = array();
    //         $insertVeh = array();
    //         $laabsveh = new MaraghiJobModel();
    //         $last_veh = $laabsveh->select('job_no,job_status')->orderby('job_no', "desc")->limit(1)->first();
    //         foreach (json_decode($result) as $value) {
    //             if ($value->JOB_NO <= $last_veh['job_no']) {
    //                 $job_card =  $laabsveh->select('job_no,job_status,jb_sub_status')->where('job_no', $value->JOB_NO)->first();
    //                 $master_stat = $job_card['jb_sub_status'];
    //                 if ($job_card['job_status'] != $value->JOB_STATUS) {
    //                     $subStatusTracker = new JobSubStatusTrackerModel();

    //                     $sub_stat_desc = "";
    //                     $sub_stat = 1;
    //                     if ($value->JOB_STATUS == "OPN") {
    //                         $sub_stat_desc = "Job opened in laabs";
    //                         $sub_stat = 1;
    //                     } else if ($value->JOB_STATUS == "WIP") {
    //                         $sub_stat_desc = "Updated to work in progress(LAABS)";
    //                         $sub_stat = 2;
    //                     } else if ($value->JOB_STATUS == "TST") {
    //                         $sub_stat_desc = "Updated to road test(LAABS)";
    //                         $sub_stat = 14;
    //                     } else if ($value->JOB_STATUS == "SUS") {
    //                         $sub_stat_desc = "Updated to suspended(LAABS)";
    //                         $sub_stat = 19;
    //                     } else if ($value->JOB_STATUS == "COM") {
    //                         $sub_stat_desc = "Updated to completed(LAABS)";
    //                         $sub_stat = 15;
    //                     } else if ($value->JOB_STATUS == "CAN") {
    //                         $sub_stat_desc = "Updated to cancelled(LAABS)";
    //                         $sub_stat = 18;
    //                     } else if ($value->JOB_STATUS == "CLO") {
    //                         $sub_stat_desc = "Updated to closed(LAABS)";
    //                         $sub_stat = 16;
    //                     } else if ($value->JOB_STATUS == "INV") {
    //                         $sub_stat_desc = "Updated to invoiced(LAABS)";
    //                         $sub_stat = 17;
    //                     }
    //                     $data = [
    //                         'jbsc_job_no' => $value->JOB_NO,
    //                         'jbsc_main_status' => $value->JOB_STATUS,
    //                         'jbsc_sub_status' => $sub_stat,
    //                         'jbsc_sub_description' => $sub_stat_desc,
    //                         'jbsc_updated_by' => 1,
    //                         'jbsc_updated_on' => date("Y-m-d H:i:s"),
    //                     ];
    //                     $subinsert = $subStatusTracker->insert($data);
    //                     $master_stat = $sub_stat;
    //                 }
    //                 $updateVeh[] = array(
    //                     'job_no' => $value->JOB_NO,
    //                     'customer_no' => $value->CUSTOMER_NO,
    //                     'vehicle_id' => $value->VEHICLE_ID,
    //                     'car_reg_no' => $value->CAR_REG_NO,
    //                     'job_open_date' => $value->JOB_OPEN_DATE,
    //                     'job_close_date' => $value->JOB_CLOSE_DATE,
    //                     'received_date' => $value->RECEIVED_DATE,
    //                     'promised_date' => $value->PROMISED_DATE,
    //                     'extended_promise_date' => $value->EXTENDED_PROMISE_DATE,
    //                     'speedometer_reading' => $value->SPEEDOMETER_READING,
    //                     'delivered_date' => $value->DELIVERED_DATE,
    //                     'invoice_no' => $value->INVOICE_NO,
    //                     'invoice_date' => $value->INVOICE_DATE,
    //                     'sa_emp_id' => $value->SA_EMP_ID,
    //                     'user_name' => $value->USER_NAME,
    //                     'job_status' => $value->JOB_STATUS,
    //                     'job_scn_id' => $value->job_scn_id,
    //                     'jb_sub_status' =>  $master_stat,
    //                 );
    //                 // if ($last_veh['job_status'] != $value->JOB_STATUS) {
    //                 //     $subStatusTracker = new JobSubStatusTrackerModel();
    //                 //     $sub_stat_desc = "";
    //                 //     $sub_stat = 1;
    //                 //     if ($value->JOB_STATUS == "OPN") {
    //                 //         $sub_stat_desc = "Job opened in laabs";
    //                 //         $sub_stat = 1;
    //                 //     } else if ($value->JOB_STATUS == "WIP") {
    //                 //         $sub_stat_desc = "Updated to work in progress(LAABS)";
    //                 //         $sub_stat = 2;
    //                 //     } else if ($value->JOB_STATUS == "TST") {
    //                 //         $sub_stat_desc = "Updated to road test(LAABS)";
    //                 //         $sub_stat = 14;
    //                 //     } else if ($value->JOB_STATUS == "SUS") {
    //                 //         $sub_stat_desc = "Updated to suspended(LAABS)";
    //                 //         $sub_stat = 19;
    //                 //     } else if ($value->JOB_STATUS == "COM") {
    //                 //         $sub_stat_desc = "Updated to completed(LAABS)";
    //                 //         $sub_stat = 15;
    //                 //     } else if ($value->JOB_STATUS == "CAN") {
    //                 //         $sub_stat_desc = "Updated to cancelled(LAABS)";
    //                 //         $sub_stat = 18;
    //                 //     } else if ($value->JOB_STATUS == "CLO") {
    //                 //         $sub_stat_desc = "Updated to closed(LAABS)";
    //                 //         $sub_stat = 16;
    //                 //     } else if ($value->JOB_STATUS == "INV") {
    //                 //         $sub_stat_desc = "Updated to invoiced(LAABS)";
    //                 //         $sub_stat = 17;
    //                 //     }
    //                 //     $data = [
    //                 //         'jbsc_job_no' =>$value->JOB_NO,
    //                 //         'jbsc_main_status' => $value->JOB_STATUS,
    //                 //         'jbsc_sub_status' => $sub_stat,
    //                 //         'jbsc_sub_description' => $sub_stat_desc,
    //                 //         'jbsc_updated_by' => 1,
    //                 //         'jbsc_updated_on' => date("Y-m-d H:i:s"),
    //                 //     ];
    //                 //     //$subinsert = $subStatusTracker->insert($data);
    //                 // }
    //                 // $updateVeh[] = array(
    //                 //     'job_no' => $value->JOB_NO,
    //                 //     'customer_no' => $value->CUSTOMER_NO,
    //                 //     'vehicle_id' => $value->VEHICLE_ID,
    //                 //     'car_reg_no' => $value->CAR_REG_NO,
    //                 //     'job_open_date' => $value->JOB_OPEN_DATE,
    //                 //     'job_close_date' => $value->JOB_CLOSE_DATE,
    //                 //     'received_date' => $value->RECEIVED_DATE,
    //                 //     'promised_date' => $value->PROMISED_DATE,
    //                 //     'extended_promise_date' => $value->EXTENDED_PROMISE_DATE,
    //                 //     'speedometer_reading' => $value->SPEEDOMETER_READING,
    //                 //     'delivered_date' => $value->DELIVERED_DATE,
    //                 //     'invoice_no' => $value->INVOICE_NO,
    //                 //     'invoice_date' => $value->INVOICE_DATE,
    //                 //     'sa_emp_id' => $value->SA_EMP_ID,
    //                 //     'user_name' => $value->USER_NAME,
    //                 //     'job_status' => $value->JOB_STATUS,
    //                 //     'job_scn_id' => $value->job_scn_id
    //                 // );
    //             } else {

    //                 $subStatusTracker = new JobSubStatusTrackerModel();
    //                 $sub_stat_desc = "";
    //                 $sub_stat = 1;
    //                 if ($value->JOB_STATUS == "OPN") {
    //                     $sub_stat_desc = "Job opened in laabs";
    //                     $sub_stat = 1;
    //                 } else if ($value->JOB_STATUS == "WIP") {
    //                     $sub_stat_desc = "Updated to work in progress(LAABS)";
    //                     $sub_stat = 2;
    //                 } else if ($value->JOB_STATUS == "TST") {
    //                     $sub_stat_desc = "Updated to road test(LAABS)";
    //                     $sub_stat = 14;
    //                 } else if ($value->JOB_STATUS == "SUS") {
    //                     $sub_stat_desc = "Updated to suspended(LAABS)";
    //                     $sub_stat = 19;
    //                 } else if ($value->JOB_STATUS == "COM") {
    //                     $sub_stat_desc = "Updated to completed(LAABS)";
    //                     $sub_stat = 15;
    //                 } else if ($value->JOB_STATUS == "CAN") {
    //                     $sub_stat_desc = "Updated to cancelled(LAABS)";
    //                     $sub_stat = 18;
    //                 } else if ($value->JOB_STATUS == "CLO") {
    //                     $sub_stat_desc = "Updated to closed(LAABS)";
    //                     $sub_stat = 16;
    //                 } else if ($value->JOB_STATUS == "INV") {
    //                     $sub_stat_desc = "Updated to invoiced(LAABS)";
    //                     $sub_stat = 17;
    //                 }
    //                 $data = [
    //                     'jbsc_job_no' => $value->JOB_NO,
    //                     'jbsc_main_status' => $value->JOB_STATUS,
    //                     'jbsc_sub_status' => $sub_stat,
    //                     'jbsc_sub_description' => $sub_stat_desc,
    //                     'jbsc_updated_by' => 1,
    //                     'jbsc_updated_on' => date("Y-m-d H:i:s"),
    //                 ];
    //                 $subinsert = $subStatusTracker->insert($data);
    //                 $insertVeh[] = array(
    //                     'job_no' => $value->JOB_NO,
    //                     'customer_no' => $value->CUSTOMER_NO,
    //                     'vehicle_id' => $value->VEHICLE_ID,
    //                     'car_reg_no' => $value->CAR_REG_NO,
    //                     'job_open_date' => $value->JOB_OPEN_DATE,
    //                     'job_close_date' => $value->JOB_CLOSE_DATE,
    //                     'promised_date' => $value->PROMISED_DATE,
    //                     'extended_promise_date' => $value->EXTENDED_PROMISE_DATE,
    //                     'speedometer_reading' => $value->SPEEDOMETER_READING,
    //                     'delivered_date' => $value->DELIVERED_DATE,
    //                     'invoice_no' => $value->INVOICE_NO,
    //                     'invoice_date' => $value->INVOICE_DATE,
    //                     'sa_emp_id' => $value->SA_EMP_ID,
    //                     'user_name' => $value->USER_NAME,
    //                     'job_status' => $value->JOB_STATUS,
    //                     'job_scn_id' => $value->job_scn_id,
    //                     'received_date' => $value->RECEIVED_DATE,
    //                     'jb_sub_status' =>  $sub_stat,
    //                 );
    //             }
    //         }
    //         $this->db->transBegin();
    //         $builder = $this->db->table('cust_job_data_laabs');
    //         if (sizeof($updateVeh) > 0) {
    //             $builder->updateBatch($updateVeh, 'job_no');
    //         }
    //         if (sizeof($insertVeh) > 0) {
    //             $builder->insertBatch($insertVeh);
    //         }
    //         $ret_data['ret_data'] = "fail";
    //         if ($this->db->transStatus() === false) {
    //             $this->db->transRollback();
    //         } else {
    //             $this->db->transCommit();
    //             $ret_data['ret_data'] = "success";
    //         }
    //         return $this->respond($ret_data, 200);
    //     }
    // }

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
}
