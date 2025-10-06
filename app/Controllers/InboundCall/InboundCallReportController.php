<?php

namespace App\Controllers\InboundCall;

use App\Models\Commonutils\CommonNumberModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\MaraghiJobcardModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Leads\LeadCallLogModel;
use App\Models\Leads\LeadModel;
use App\Models\SuperAdminModel;
use App\Models\User\UserModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;

use function PHPUnit\Framework\isEmpty;

class InboundCallReportController extends ResourceController
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
        //
        $model = new CommonNumberModel();
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

            // $res= $model->where('cn_delete_flag', 0)->findAll();
            $res = $model->select('cn_id,RIGHT(cn_number,7) as phon_uniq')->findAll();
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'numlist' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'numlist' => []
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

    public function getcustomerdata()
    {
        $marcustmodel = new MaragiCustomerModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();
        $marjobmodel = new MaraghiJobcardModel();

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

            $num_list = $this->request->getVar('num_list');
            $start_date = $this->request->getVar('start_date');
            $end_date = $this->request->getVar('end_date');
            $hours =  date('Y-m-d', strtotime($start_date));
            if (sizeof($num_list) > 0) {
                $customer = [];
                foreach ($num_list as $number) {
                    $marag_cus_res = $marcustmodel
                        ->where('RIGHT(phone,7)', $number)
                        ->orWhere('RIGHT(mobile,7)', $number)
                        ->select("customer_code,UPPER(customer_name) as customer_name,city,mobile,RIGHT(phone,7) as phon_uniq,RIGHT(mobile,7) as mob_uniq,'M' as type")->first();
                    if ($marag_cus_res == null) {
                        $marag_cus_res = $custmastermodel->where('RIGHT(cust_phone,7)', $number)
                            ->select("cus_id,cust_alm_code,UPPER(cust_name) as customer_name,cust_city as city,cust_phone as mobile,RIGHT(cust_phone,7) as phon_uniq,'C' as type,RIGHT(cust_alternate_contact,7) as alt_num_uniq")->first();
                        if ($marag_cus_res == null) {
                            $marag_cus_res = $leadmodel->like('phone', $number, 'before')->where('lead_createdon <', $hours)
                                ->select("IF(IFNULL(name, '') = '', 'EXISTS', name) as customer_name,phone as mobile,RIGHT(phone,7) as phon_uniq,'L' as type")->first();
                            if ($marag_cus_res == null) {
                                $marag_cus_res = [];
                            }
                        }
                    }
                    array_push($customer, $marag_cus_res);
                }
                $cust_convert = $marjobmodel->select("job_no,customer_no,job_open_date,str_to_date(job_open_date, '%d-%M-%y'),job_status,RIGHT(phone,7) as phon_uniq,invoice_date,cust_data_laabs.created_on")
                    ->join('cust_data_laabs', 'cust_data_laabs.customer_code = customer_no')
                    ->where('job_status', 'INV')
                    ->where("str_to_date(job_open_date, '%d-%M-%y')  >=", $start_date)
                    ->where("str_to_date(job_open_date, '%d-%M-%y')  <=", $end_date)
                    ->where("DATE(cust_data_laabs.created_on)  >=",  $start_date)
                    ->where("DATE(cust_data_laabs.created_on)  <=", $end_date)->findAll();


                $jobcard_res = $marjobmodel->where('job_status', 'WIP')
                    ->whereIn('RIGHT(cust_data_laabs.phone,7)', $num_list)
                    ->join('cust_data_laabs', 'cust_data_laabs.customer_code=customer_no')
                    ->select('job_no,customer_no,RIGHT(cust_data_laabs.phone,7) as phon_uniq,job_open_date,job_close_date')->find();
                $response = [
                    'ret_data' => 'success',
                    'customers' => $customer,
                    'cust_convert' => $cust_convert,
                    'jobcard' => $jobcard_res

                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customers' => [],
                    'cust_convert' => [],
                    'jobcard' => []
                ];
            }









            // if (sizeof($num_list) > 0) {
            //     // $num_unique = array_unique($num_list);
            //     $lead_res = $leadmodel->whereIn('RIGHT(phone,7)', $num_list)->where('DATE(lead_updatedon) >=', $hours)->select("IF(IFNULL(name, '') = '', 'EXISTS', name) as customer_name,phone as mobile,RIGHT(phone,7) as phon_uniq,'L' as type")->find();
            //     $cust_master_res = $custmastermodel->whereIn('RIGHT(cust_phone,7)', $num_list)->select("cus_id,cust_alm_code,UPPER(cust_name) as customer_name,cust_city as city,cust_phone as mobile,RIGHT(cust_phone,7) as phon_uniq,'C' as type,RIGHT(cust_alternate_contact,7) as alt_num_uniq")->find();
            //     $marag_cus_res = $marcustmodel->whereIn('RIGHT(phone,7)', $num_list)->select("customer_code,UPPER(customer_name) as customer_name,city,mobile,RIGHT(phone,7) as phon_uniq,'M' as type")->find();
            //     $customer = array_merge($marag_cus_res, $cust_master_res, $lead_res);

            //     $cust_convert = $marjobmodel->select("job_no,customer_no,job_open_date,str_to_date(job_open_date, '%d-%M-%y'),job_status,RIGHT(phone,7) as phon_uniq,invoice_date,cust_data_laabs.created_on")
            //         ->join('cust_data_laabs', 'cust_data_laabs.customer_code = customer_no')
            //         ->where('job_status', 'INV')
            //         ->where("str_to_date(job_open_date, '%d-%M-%y')  >=", $start_date)
            //         ->where("str_to_date(job_open_date, '%d-%M-%y')  <=", $end_date)
            //         ->where("DATE(cust_data_laabs.created_on)  >=",  $start_date)
            //         ->where("DATE(cust_data_laabs.created_on)  <=", $end_date)->findAll();


            //     $jobcard_res = $marjobmodel->where('job_status', 'WIP')
            //    ->whereIn('RIGHT(cust_data_laabs.phone,7)', $num_list)
            //    ->join('cust_data_laabs', 'cust_data_laabs.customer_code=customer_no')
            //    ->select('job_no,customer_no,RIGHT(cust_data_laabs.phone,7) as phon_uniq,job_open_date,job_close_date')->find();






            //     $response = [
            //         'ret_data' => 'success',
            //         'customers' => $customer,
            //         'cust_convert' => $cust_convert,
            //         'jobcard' => $jobcard_res

            //     ];
            // } else {
            //     $response = [
            //         'ret_data' => 'success',
            //         'customers' => [],
            //         'cust_convert' => [],
            //         'jobcard' => []
            //     ];
            // }

            return $this->respond($response, 200);
        }
    }

    public function getcustomerleads()
    {
        $leadmodel = new LeadModel();
        $leadlogmodel = new LeadCallLogModel();
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

            $num_list = $this->request->getVar('num_list');
            $callid_array = $this->request->getVar('callid_array');


            $cust_leads = $leadmodel->select('lead_id,lead_code,name,status_id,DATE(lead_createdon) as lead_createdon,DATE(lead_updatedon) as lead_updatedon,RIGHT(phone,7) as phon_uniq,cp_id,call_purpose,phone')
                ->join('call_purposes', 'call_purposes.cp_id=purpose_id')
                ->orderBy('lead_id', 'desc')
                ->whereIn('RIGHT(phone,7)', $num_list)
                ->whereIn('status_id', [1, 6])->findAll();

            $leadlogs = $leadlogmodel->select('lcl_id,lcl_time,lcl_lead_id,call_purpose,lcl_purpose_note,lcl_call_to,ystar_call_id,cp_id')
                ->join('call_purposes', 'call_purposes.cp_id =lead_call_log.lcl_pupose_id')
                ->where('lcl_pupose_id !=', 0)
                ->whereIn('ystar_call_id', $callid_array)->find();

            $response = [
                'ret_data' => 'success',
                'leads' => $cust_leads,
                'leadlogs' => $leadlogs
            ];
        } else {
            $response = [
                'ret_data' => 'success',
                'leads' => [],
                'leadlogs' => []
            ];
        }


        return $this->respond($response, 200);
    }

    public function getcustomerinfo()
    {
        $marcustmodel = new MaragiCustomerModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();
        $leadlogmodel = new LeadCallLogModel();
        $cnmodel = new CommonNumberModel();
        $common = new Common();
        $valid = new Validation();

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
            $num_list = $this->request->getvar('numbers');
            $call_id = $this->request->getVar('call_id');
            $start_day = $this->request->getVar('start_day');
            $end_day = date('Y-m-d H:i:s');
            $i = 0;
            $customer = [];
            if (sizeof($num_list) > 0) {
                $status = [1, 7];
                foreach ($num_list as $num) {
                    $lead_res = $leadmodel->where('RIGHT(phone,9)', substr($num, -9))->whereIn('status_id', $status)->orderBy('lead_id', 'desc')->first();
                    if ($lead_res) {
                        $lead_res['lead_call_logs'] = $leadlogmodel->where('lcl_lead_id', $lead_res['lead_id'])
                            ->join('users', 'users.us_id =lcl_createdby', 'left')
                            ->select('lead_call_log.*,users.us_firstname')->findAll();
                    } else {
                        $lead_res = [
                            "phone" => $num,
                            "lead_id" => "0",
                            "lead_code" => "",
                            "purpose_id" => "0",
                            "name" => "",
                            "lead_call_logs" => [],
                        ];
                    }
                    $fields = [];
                    $fields['phoneNumber'] = $num;
                    $fields['start_day'] = $start_day;
                    $fields['end_day'] = $end_day;
                    $fields = json_encode($fields);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "http://almaraghi.fortiddns.com:35147/maraghi_lead_connection/index.php/DataFetch/getLatestCallReportByNumber");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json; charset=utf-8'
                    ));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_HEADER, FALSE);
                    curl_setopt($ch, CURLOPT_POST, TRUE);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

                    $curlResponse = json_decode(curl_exec($ch));
                    $lead_res['yeastar_call_logs'] = $curlResponse->call_data;
                    curl_close($ch);
                    $customer[$i] = $lead_res;
                    $i++;
                }

                $response = [
                    'ret_data' => 'success',
                    '53243' => "$end_day",
                    'customers' => $customer
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customers' => []
                ];
            }
            return $this->respond($response, 200);
        }

        //     if (sizeof($num_list) > 0) {
        //         foreach ($num_list as $num) {
        //             $marag_cus_res = $marcustmodel
        //                 ->where('RIGHT(phone,7)', substr($num, -7))
        //                 ->orWhere('RIGHT(mobile,7)',  substr($num, -7))
        //                 ->select('customer_code,customer_name,city,mobile,RIGHT(phone,7) as phon_uniq,mobile as alt_num,RIGHT(mobile,7) as mob_uniq')->first();
        //             if ($marag_cus_res) {
        //                 $marag_cus_res['type'] = 'M';
        //                 $marag_cus_res['customer_name'] = strtoupper($marag_cus_res['customer_name']);
        //                 $customer[$i] = $marag_cus_res;
        //                 $i++;
        //             } else {
        //                 $cust_master_res = $custmastermodel->where('RIGHT(cust_phone,7)', substr($num, -7))
        //                     ->orWhere('RIGHT(cust_alternate_contact,7)', substr($num, -7))
        //                     ->select('cus_id,cust_alm_code,cust_name as customer_name,cust_phone as mobile,RIGHT(cust_phone,7) as phon_uniq,cust_alternate_contact as alt_num')->first();
        //                 if ($cust_master_res) {
        //                     $cust_master_res['customer_name'] = strtoupper($cust_master_res['customer_name']);
        //                     $cust_master_res['type'] = 'C';
        //                     $customer[$i] = $cust_master_res;
        //                     $i++;
        //                 } else {
        //                     $lead_res = $leadmodel->where('RIGHT(phone,7)', substr($num, -7))
        //                         ->select("IF(IFNULL(name, '') = '', 'EXISTS', name) as customer_name,phone as mobile,RIGHT(phone,7) as phon_uniq")->first();
        //                     if ($lead_res) {
        //                         $lead_res['customer_code'] = '0000';
        //                         $lead_res['type'] = 'L';
        //                         $customer[$i] = $lead_res;
        //                         $i++;
        //                     }
        //                 }
        //             }
        //         }
        //         $lead = $leadmodel->whereIn('RIGHT(phone,7)', $num_list)->join('call_purposes', 'call_purposes.cp_id=purpose_id')->orderBy('lead_id', 'desc')->select('lead_id ,call_purposes.cp_id,call_purposes.call_purpose,name as customer_name,phone as mobile,lead_code,RIGHT(phone,7) as phon_uniq,lead_creted_date,close_time,lead_updatedon')->find();
        //         $leadlog = $leadlogmodel->whereIn('ystar_call_id', $call_id)->where('lcl_pupose_id !=', 0)->join('call_purposes', 'call_purposes.cp_id =lead_call_log.lcl_pupose_id', 'left')->select("lcl_id,lcl_time,lcl_lead_id,RIGHT(lcl_phone,7) as phon_uniq,lcl_purpose_note,ystar_call_id,lcl_call_to,call_purpose,call_purposes.cp_id")->find();
        //         $common = $cnmodel->whereIn('RIGHT(cn_number,7)', $num_list)->select('cn_id,RIGHT(cn_number,7) as phon_uniq')->find();

        //         $response = [
        //             'ret_data' => 'success',
        //             'customers' => $customer,
        //             'lead' => $lead,
        //             'leadlog' => $leadlog,
        //             'common' => $common,
        //         ];
        //     } else {
        //         $response = [
        //             'ret_data' => 'success',
        //             'customers' => [],
        //             'lead' => [],
        //             'leadlog' => [],
        //             'common' => []
        //         ];
        //     }
        // }


        // return $this->respond($response, 200);
    }
    public function getMissedCustomerInfo()
    {
        $marcustmodel = new MaragiCustomerModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();
        $leadlogmodel = new LeadCallLogModel();
        $cnmodel = new CommonNumberModel();
        $common = new Common();
        $valid = new Validation();

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
            $num_list = $this->request->getvar('numbers');
            $call_id = $this->request->getVar('call_id');
            $start_day = $this->request->getVar('start_day');
            $end_day = date('Y-m-d H:i:s');
            $i = 0;
            $customer = [];
            if (sizeof($num_list) > 0) {
                foreach ($num_list as $num) {
                    $lead_res = $leadmodel->where('RIGHT(phone,9)', substr($num, -9))
                        //->where('status_id', 1)
                        ->orderBy('lead_id', 'desc')->first();
                    if ($lead_res) {
                        $lead_res['lead_call_logs'] = $leadlogmodel->where('lcl_lead_id', $lead_res['lead_id'])
                            ->join('users', 'users.us_id =lcl_createdby', 'left')
                            ->select('lead_call_log.*,users.us_firstname')->findAll();
                    } else {
                        $lead_res = [
                            "phone" => $num,
                            "lead_id" => "0",
                            "lead_code" => "",
                            "purpose_id" => "0",
                            "name" => "",
                            "lead_call_logs" => [],
                        ];
                    }
                    $fields = [];
                    $fields['phoneNumber'] = $num;
                    $fields['start_day'] = $start_day;
                    $fields['end_day'] = $end_day;
                    $fields = json_encode($fields);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "http://almaraghi.fortiddns.com:35147/maraghi_lead_connection/index.php/DataFetch/getLatestCallReportByNumber");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json; charset=utf-8'
                    ));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($ch, CURLOPT_HEADER, FALSE);
                    curl_setopt($ch, CURLOPT_POST, TRUE);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

                    $curlResponse = json_decode(curl_exec($ch));
                    $lead_res['yeastar_call_logs'] = $curlResponse->call_data;
                    curl_close($ch);
                    $customer[$i] = $lead_res;
                    $i++;
                }

                $response = [
                    'ret_data' => 'success',
                    '53243' => "$end_day",
                    'customers' => $customer
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customers' => []
                ];
            }
            return $this->respond($response, 200);
        }

        //     if (sizeof($num_list) > 0) {
        //         foreach ($num_list as $num) {
        //             $marag_cus_res = $marcustmodel
        //                 ->where('RIGHT(phone,7)', substr($num, -7))
        //                 ->orWhere('RIGHT(mobile,7)',  substr($num, -7))
        //                 ->select('customer_code,customer_name,city,mobile,RIGHT(phone,7) as phon_uniq,mobile as alt_num,RIGHT(mobile,7) as mob_uniq')->first();
        //             if ($marag_cus_res) {
        //                 $marag_cus_res['type'] = 'M';
        //                 $marag_cus_res['customer_name'] = strtoupper($marag_cus_res['customer_name']);
        //                 $customer[$i] = $marag_cus_res;
        //                 $i++;
        //             } else {
        //                 $cust_master_res = $custmastermodel->where('RIGHT(cust_phone,7)', substr($num, -7))
        //                     ->orWhere('RIGHT(cust_alternate_contact,7)', substr($num, -7))
        //                     ->select('cus_id,cust_alm_code,cust_name as customer_name,cust_phone as mobile,RIGHT(cust_phone,7) as phon_uniq,cust_alternate_contact as alt_num')->first();
        //                 if ($cust_master_res) {
        //                     $cust_master_res['customer_name'] = strtoupper($cust_master_res['customer_name']);
        //                     $cust_master_res['type'] = 'C';
        //                     $customer[$i] = $cust_master_res;
        //                     $i++;
        //                 } else {
        //                     $lead_res = $leadmodel->where('RIGHT(phone,7)', substr($num, -7))
        //                         ->select("IF(IFNULL(name, '') = '', 'EXISTS', name) as customer_name,phone as mobile,RIGHT(phone,7) as phon_uniq")->first();
        //                     if ($lead_res) {
        //                         $lead_res['customer_code'] = '0000';
        //                         $lead_res['type'] = 'L';
        //                         $customer[$i] = $lead_res;
        //                         $i++;
        //                     }
        //                 }
        //             }
        //         }
        //         $lead = $leadmodel->whereIn('RIGHT(phone,7)', $num_list)->join('call_purposes', 'call_purposes.cp_id=purpose_id')->orderBy('lead_id', 'desc')->select('lead_id ,call_purposes.cp_id,call_purposes.call_purpose,name as customer_name,phone as mobile,lead_code,RIGHT(phone,7) as phon_uniq,lead_creted_date,close_time,lead_updatedon')->find();
        //         $leadlog = $leadlogmodel->whereIn('ystar_call_id', $call_id)->where('lcl_pupose_id !=', 0)->join('call_purposes', 'call_purposes.cp_id =lead_call_log.lcl_pupose_id', 'left')->select("lcl_id,lcl_time,lcl_lead_id,RIGHT(lcl_phone,7) as phon_uniq,lcl_purpose_note,ystar_call_id,lcl_call_to,call_purpose,call_purposes.cp_id")->find();
        //         $common = $cnmodel->whereIn('RIGHT(cn_number,7)', $num_list)->select('cn_id,RIGHT(cn_number,7) as phon_uniq')->find();

        //         $response = [
        //             'ret_data' => 'success',
        //             'customers' => $customer,
        //             'lead' => $lead,
        //             'leadlog' => $leadlog,
        //             'common' => $common,
        //         ];
        //     } else {
        //         $response = [
        //             'ret_data' => 'success',
        //             'customers' => [],
        //             'lead' => [],
        //             'leadlog' => [],
        //             'common' => []
        //         ];
        //     }
        // }


        // return $this->respond($response, 200);
    }
    public function getcallleadlog()
    {
        $leadlogmodel = new LeadCallLogModel();
        $call_id = $this->request->getVar('call_id');

        if (sizeof($call_id) > 0) {
            $leadlog = $leadlogmodel->whereIn('ystar_call_id', $call_id)->where('lcl_pupose_id !=', 0)->join('call_purposes', 'call_purposes.cp_id =lead_call_log.lcl_pupose_id', 'left')->select("lcl_id,lcl_time,lcl_lead_id,RIGHT(lcl_phone,7) as phon_uniq,lcl_purpose_note,ystar_call_id,lcl_call_to,call_purpose,call_purposes.cp_id")->find();

            $response = [
                'ret_data' => 'success',
                'leadlog' => $leadlog,
            ];
        } else {
            $response = [
                'ret_data' => 'success',
                'leadlog' => [],
            ];
        }
        return $this->respond($response, 200);
    }

    public function getcustomerdataanalysis()
    {
        $marcustmodel = new MaragiCustomerModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();
        $marjobmodel = new MaraghiJobcardModel();

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

            $num_list = $this->request->getVar('num_list');
            $start_date = $this->request->getVar('start_date');
            $end_date = $this->request->getVar('end_date');
            $hours =  date('Y-m-d', strtotime($start_date));
            if (sizeof($num_list) > 0) {
                $cus_data_res = $marcustmodel
                    ->whereIn('RIGHT(phone,9)', $num_list)
                    ->orWhereIn('RIGHT(mobile,9)', $num_list)
                    ->select("customer_code,UPPER(customer_name) as customer_name,city,mobile,phone,RIGHT(phone,9) as phon_uniq,RIGHT(mobile,9) as mob_uniq,'M' as type")
                    ->groupBy('phon_uniq')
                    ->orderBy('customer_code', 'DESC')
                    ->findAll();
                $cus_res = $custmastermodel
                    ->whereIn('RIGHT(cust_phone,7)', $num_list)
                    ->select("cus_id,cust_alm_code,UPPER(cust_name) as customer_name,cust_city as city,cust_phone as phone,RIGHT(cust_phone,7) as phon_uniq,cust_alternate_no as mobile,'C' as type,RIGHT(cust_alternate_contact,7) as alt_num_uniq")
                    ->findAll();

                if ($cus_data_res || $cus_res) {

                    $response = [
                        'ret_data' => 'success',
                        'customers' => $cus_data_res,
                        'customer_master' => $cus_res
                    ];
                } else {
                    $response = [
                        'ret_data' => 'success',
                        'customers' => [],
                        'customer_master' => []
                    ];
                }
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'customers' => [],
                    'customer_master' => []
                ];
            }


            return $this->respond($response, 200);
        }
    }

    public function getCallsData()
    {
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
            $mark_customers = new MaragiCustomerModel();
            $customers = $mark_customers->whereIn('RIGHT(phone,9)', $this->request->getVar('numbers'))
                ->select('cust_data_laabs.*,cjl.job_no,cjl.vehicle_id,cjl.car_reg_no,cjl.job_status,cjl.job_open_date,cjl.invoice_date,cjl.sa_emp_id,cjl.user_name,cjl.job_close_date')
                ->join('cust_job_data_laabs as cjl', 'cjl.customer_no=cust_data_laabs.customer_code', 'left')->findAll();
            $groupedData = [];
            foreach ($customers as $item) {
                $key = $item['customer_code'];

                if (!array_key_exists($key, $groupedData)) {
                    $groupedData[$key] = [
                        'customer_code' => $item['customer_code'],
                        'customer_type' => $item['customer_type'],
                        'customer_cat_type' => $item['customer_cat_type'],
                        'customer_title' => $item['customer_title'],
                        'customer_name' => $item['customer_name'],
                        'addr1' => $item['addr1'],
                        'po_box' => $item['po_box'],
                        'city' => $item['city'],
                        'country' => $item['country'],
                        'phone' => $item['phone'],
                        'mobile' => $item['mobile'],
                        'sms_option' => $item['sms_option'],
                        'contact_person' => $item['contact_person'],
                        'contact_phone' => $item['contact_phone'],
                        'labs_created_on' => $item['labs_created_on'],
                        'lang_pref' => $item['lang_pref'],
                        'cust_scn_id' => $item['cust_scn_id'],
                        'created_on' => $item['created_on'],
                        'updated_on' => $item['updated_on'],
                        'phon_uniq' => $item['phon_uniq'],
                        'job_cards' => []
                    ];
                }

                // Add job card data to the respective customer
                $groupedData[$key]['job_cards'][] = [
                    'job_no' => $item['job_no'],
                    'vehicle_id' => $item['vehicle_id'],
                    'car_reg_no' => $item['car_reg_no'],
                    'job_status' => $item['job_status'],
                    'job_open_date' => $item['job_open_date'],
                    'invoice_date' => $item['invoice_date'],
                    'sa_emp_id' => $item['sa_emp_id'],
                    'user_name' => $item['user_name'],
                    'job_close_date' => $item['job_close_date']
                ];
            }

            $finalGroupedData = array_values($groupedData);

            $response = [
                'ret_data' => 'success',
                'customers' => $finalGroupedData
            ];
            return $this->respond($response, 200);
        }
    }
    public function getCustomerWithoutCallsData()
    {
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
            $mark_customers = new MaragiCustomerModel();
            $totalCustomers = $mark_customers->whereNotIn('RIGHT(phone,9)', $this->request->getVar('numbers'))
                ->where("DATE(cust_data_laabs.created_on) >=",  $this->request->getVar('start_date'))
                ->where("DATE(cust_data_laabs.created_on) <=",  $this->request->getVar('end_date'))
                ->where("job_status", "INV")
                ->select('cust_data_laabs.*,cjl.job_no,cjl.vehicle_id,cjl.car_reg_no,cjl.job_status,cjl.job_open_date,cjl.invoice_date,cjl.sa_emp_id,cjl.user_name,cjl.job_close_date')
                ->join('cust_job_data_laabs as cjl', 'cjl.customer_no=cust_data_laabs.customer_code', 'left')->findAll();
            $groupedData = [];
            foreach ($totalCustomers as $item) {
                $key = $item['customer_code'];
                if (!array_key_exists($key, $groupedData)) {
                    $groupedData[$key] = [
                        'customer_code' => $item['customer_code'],
                        'customer_type' => $item['customer_type'],
                        'customer_cat_type' => $item['customer_cat_type'],
                        'customer_title' => $item['customer_title'],
                        'customer_name' => $item['customer_name'],
                        'addr1' => $item['addr1'],
                        'po_box' => $item['po_box'],
                        'city' => $item['city'],
                        'country' => $item['country'],
                        'phone' => $item['phone'],
                        'mobile' => $item['mobile'],
                        'sms_option' => $item['sms_option'],
                        'contact_person' => $item['contact_person'],
                        'contact_phone' => $item['contact_phone'],
                        'labs_created_on' => $item['labs_created_on'],
                        'lang_pref' => $item['lang_pref'],
                        'cust_scn_id' => $item['cust_scn_id'],
                        'created_on' => $item['created_on'],
                        'updated_on' => $item['updated_on'],
                        'phon_uniq' => $item['phon_uniq'],
                        'job_cards' => []
                    ];
                }

                // Add job card data to the respective customer
                $groupedData[$key]['job_cards'][] = [
                    'job_no' => $item['job_no'],
                    'vehicle_id' => $item['vehicle_id'],
                    'car_reg_no' => $item['car_reg_no'],
                    'job_status' => $item['job_status'],
                    'job_open_date' => $item['job_open_date'],
                    'invoice_date' => $item['invoice_date'],
                    'sa_emp_id' => $item['sa_emp_id'],
                    'user_name' => $item['user_name'],
                    'job_close_date' => $item['job_close_date']
                ];
            }

            $finalGroupedData = array_values($groupedData);
            $response = [
                'ret_data' => 'success',
                'customers' => $finalGroupedData
            ];
            return $this->respond($response, 200);
        }
    }

    public function getCustomerDetailsfromPhoneNum()
    {

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
            $leadCallLog = new LeadCallLogModel();
            $ystarId = $this->request->getVar('callId');
            $customerWithId = $leadCallLog->whereIn('ystar_call_id', $ystarId)
                ->join('leads', 'leads.lead_id = lead_call_log.lcl_lead_id', 'left')
                ->join('call_purposes', 'call_purposes.cp_id = leads.purpose_id', 'left')
                ->join('lead_source', 'lead_source.ld_src_id = leads.source_id', 'left')
                ->select([
                    'lead_call_log.lcl_phone as phone',
                    'call_purposes.call_purpose as call_purpose',
                    'leads.name as name',
                    'leads.source_id',
                    'lead_source.ld_src',
                    'lead_call_log.ystar_call_id as ystar_call_id',
                    'purpose_id',
                    'lead_note',
                ])
                ->findAll();
            // $ystarIdData = $customerDetails->toArray();

            $custDatalabs = new MaragiCustomerModel();
            $custMasterTable = new CustomerMasterModel();
            $data = [];
            $phoneNums = $this->request->getVar('nums');
            // $customerDetails = $custDatalabs -> whereIn('RIGHT(phone,9)',$phoneNums)->findAll();

            foreach ($phoneNums as $key => $num) {
                $customerDetails = $custDatalabs->where('RIGHT(phone,9)', substr($num, -9))->first();
                if (empty($customerDetails)) {
                    $customerDetailstemp = $custMasterTable->where('RIGHT(cust_phone,9)', substr($num, -9))->first();
                    if (!empty($customerDetailstemp)) {
                        $customerDetails = [
                            'customer_name' => $customerDetailstemp['cust_name'],
                            'phone' => $customerDetailstemp['cust_phone'],
                        ];
                    }
                }
                if (empty($customerDetails)) {
                    $customerDetails = [
                        'customer_name' => 'new',
                        'phone' => $num
                    ];
                }

                if (!empty($customerDetails)) {
                    $data[$key] = $customerDetails;
                }
            }


            $response = [
                'ret_data' => 'success',
                'customers_with_id' => $customerWithId,
                'customers_without_id' => $data
            ];

            return $this->respond($response, 200);
        }
    }
}
