<?php

namespace App\Controllers\Callpopup;

use App\Models\Customer\CustomerMasterModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use App\Models\Leads\LeadModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Customer\MaraghiVehicleModel;
use App\Models\Customer\MaraghiJobcardModel;
use App\Models\Quotes\QuotesMasterModel;
use App\Models\Calllogs\CustomerCallsModel;
use App\Models\Leads\LeadCallLogModel;
use App\Models\PSFModule\PSFMasterModel;
use App\Models\Leads\AppointmentMasterModel;
use App\Models\Leads\AppointmentModel;
use App\Models\Leads\AppointmentLogModel;

use Config\Common;
use Config\Validation;

use App\Models\UserActivityLog;

class ViewDetail extends ResourceController
{
    use ResponseTrait;
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    public function getPendingCall()
    {

        $leadmodel = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        $leadlogModel = new LeadCallLogModel();
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
        $today = date("Y-m-d");
        $call_to = $this->request->getVar('call_to');
        $call_id = $this->request->getVar('call_id');

        $lead_res = $leadmodel->where('status_id', 7)->where('DATE(lead_createdon)', $today)->where('lead_delete_flag', 0)->where('lead_createdby', $tokendata['uid'])->select('lead_id ,name,phone,lead_createdon')->findAll();
        $result = [];
        if ($lead_res) {
            foreach ($lead_res as $lead) {
                $ret = $leadlogModel->where('lcl_lead_id', $lead['lead_id'])->first();
                $ret ? $lead['call_id'] = $ret['ystar_call_id'] : $lead['call_id'] = 0;
                array_push($result, $lead);
            }
        }

        // $lead_re= $leadmodel->where('status_id', 7)->where('DATE(lead_createdon)',$today)->where('lead_delete_flag',0)->where('lead_createdby',$tokendata['uid'])->select('lead_id ,name,phone,lead_createdon')->findAll();
        //     $count_lp=[];
        //     if($lead_re){
        //     foreach($lead_res as $lead){
        //         $ret=$leadlogModel->where('lcl_lead_id',$lead['lead_id'])->first();
        //         $ret? $lead['call_id']=$ret['ystar_call_id']:$lead['call_id']=0;
        //         array_push($count_lp,$lead);

        //     }
        // }

        // $builder = $this->db->table('leads');
        // $builder ->select('lead_id ,name,phone,lead_createdon,lead_updatedon,TIMEDIFF(lead_createdon,lead_updatedon) as time_diff');        
        // $builder->where('lead_delete_flag', 0);
        // $builder->where('status_id', 7);                
        // $builder->where('DATE(lead_createdon)',$today);
        // $builder->where('TIMEDIFF(lead_updatedon,NOW()) <=','00:15:00'); 
        // $query = $builder->get();
        // $count_lp = $query->getResultArray();


        //$lead_res= $leadmodel->where('status_id', 7)->where('DATE(lead_createdon)',$today)->where('lead_createdby',$tokendata['uid'])->select('lead_id ,name,phone')->findAll();
        $today = date("Y-m-d");
        $lpc = 0;
        //                $res = $leadlogModel->where('DATE(lcl_time)', $today)->where('lcl_pupose_id', 0)->where('lcl_call_to', $call_to)->select('ystar_call_id,lcl_phone')->groupBy('ystar_call_id')->findAll();
        //                $call_id= array();
        //                foreach($res as $row){
        //                   $call_id[] = $row['ystar_call_id'];
        //                 }

        //    if(count($call_id) > 0)
        //    {
        //     $result = $leadlogModel->where('DATE(lcl_time)', $today)->whereIn('ystar_call_id',  $call_id)->where('lcl_pupose_id !=', 0)->select('ystar_call_id,lcl_phone')->groupBy('ystar_call_id')->findAll();
        //     $lpc = count($call_id) - count($result);
        //    }
        //   if(sizeof( $call_id) > 0)
        //   {
        //     $builder = $this->db->table('lead_call_log');
        //     $builder ->select('ystar_call_id,lcl_phone,TIMEDIFF(lcl_call_time,NOW()) as time_diff');
        //     $builder->whereIn('ystar_call_id', $call_id);
        //     $builder->where('lcl_call_to', $call_to);
        //     $builder->where('lcl_pupose_id', 0);
        //     $builder->where('TIMEDIFF(lcl_call_time,NOW()) <=','00:15:00');
        //     $query = $builder->get();
        //     $res = $query->getResultArray();

        //     $builder = $this->db->table('lead_call_log');
        //     $builder ->select('ystar_call_id,lcl_phone,TIMEDIFF(lcl_call_time,NOW()) as time_diff');
        //     $builder->whereIn('ystar_call_id', $call_id);
        //     $builder->where('lcl_call_to', $call_to);
        //     $builder->where('lcl_pupose_id !=', 0);
        //     $builder->where('TIMEDIFF(lcl_call_time,NOW()) <=','00:15:00');
        //     $query = $builder->get();
        //     $result = $query->getResultArray();

        //     $lpc = count($res) - count($result);
        //   }



        if ($result) {
            $response = [
                'ret_data' => 'success',
                'lead' => $result,
                'LPcount' => $lpc,
            ];
            return $this->respond($response, 200);
        } else {
            $response = [
                'ret_data' => 'success',
                'lead' => [],
                'LPcount' => $lpc,
            ];
            return $this->respond($response, 200);
        }
    }
    public function getCustomerDetails()
    {
        $marcustmodel = new MaragiCustomerModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();
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
            $i = 0;
            $customers = [];
            foreach ($num_list as $num) {

                $marag_cus_res = $marcustmodel->where('RIGHT(phone,7)', $num)->select('customer_code,customer_name,city,mobile,RIGHT(mobile,7) as phon_uniq')->first();
                if ($marag_cus_res) {
                    $marag_cus_res['type'] = 'M';
                    $marag_cus_res['customer_name'] = strtoupper($marag_cus_res['customer_name']);
                    $customers[$i] = $marag_cus_res;
                    $i++;
                } else {
                    $cust_master_res = $custmastermodel->where('RIGHT(cust_phone,7)', $num)->select('cus_id,cust_alm_code,cust_name as customer_name,cust_city as city,cust_phone as mobile,RIGHT(cust_phone,7) as phon_uniq')->first();
                    if ($cust_master_res) {
                        $cust_master_res['customer_name'] = strtoupper($cust_master_res['customer_name']);
                        $cust_master_res['type'] = 'C';
                        $customers[$i] = $cust_master_res;
                        $i++;
                    }
                    // else{
                    //     $lead_res= $leadmodel->where('RIGHT(phone,7)',$num)->where('status_id !=', 7)->select('lead_id ,name as customer_name,phone as mobile,RIGHT(phone,7) as phon_uniq')->first();
                    //     if($lead_res)
                    //     {
                    //         $lead_res['customer_name']=strtoupper( $lead_res['customer_name']);
                    //         $lead_res['type']='C';
                    //         $customers[$i] = $lead_res;
                    //         $i++;
                    //     }


                    // }

                }
            }

            $response = [
                'ret_data' => 'success',
                'customers' => $customers,
            ];
            return $this->respond($response, 200);







            // $response = [
            //     'ret_data'=>'success',
            //     'customers'=>$num_list,                      
            // ];
            // return $this->respond($response,200);
            // $marag_cus_res= $marcustmodel->whereIn('phon_uniq',$num_list)->find();


            // if($marag_cus_res) // Phone number found in CRM customer master table
            // {
            //     $response = [
            //         'ret_data'=>'success',
            //         'customers'=>$marag_cus_res,                      
            //     ];
            //     return $this->respond($response,200);
            // }else{
            //     $response = [
            //         'ret_data'=>'fail',
            //         'customers'=>[],                      
            //     ];
            //     return $this->respond($response,200);
            // }
        }
    }

    /**
     * @api {get} callpopup/viewdetail/viewDetailFromPopup  Detail by phone number
     * @apiName  Customer detail by phone number
     * @apiGroup Callpopup
     * @apiPermission  Super Admin, User
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   customer  Object containing customer detail
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */
    public function viewDetailFromPopup()
    {
        $marcustmodel = new MaragiCustomerModel();
        $marvehmodel = new MaraghiVehicleModel();
        $marjcmodel = new MaraghiJobcardModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();
        $quotmodel = new QuotesMasterModel();
        $logmodel = new LeadCallLogModel();
        $psf_master = new PSFMasterModel();
        $appointmentMaster = new AppointmentMasterModel();

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
            $phone = $this->request->getVar('phone');
            $leadid = $this->request->getVar('leadid');
            $phoneLastDigits = substr($phone, -9);
            $cust_master_res = $custmastermodel->like('cust_phone', $phoneLastDigits)
                ->orLike('cust_alternate_contact', $phoneLastDigits)
                ->select('cust_alternate_contact,cus_id,cust_alm_code,cust_name as customer_name,
                cust_city as city,cust_phone as mobile')
                ->first();
            $retData['current_lead'] = $leadmodel->where('lead_id', $leadid)->select('leads.*,us_firstname,call_purpose')
                ->join('users', 'users.us_id =assigned', 'left')
                ->join('call_purposes', 'call_purposes.cp_id =purpose_id', 'left')
                ->first();
            if($retData['current_lead']['call_purpose']=="1"){
                $cust_master_res['pending_appointments'] = $appointmentMaster->where('apptm_lead_id', $leadid)
                ->where('appointment.appt_status =', 0)
                ->where('apptm_delete_flag!=', 1)
                ->join('appointment', 'appointment.appt_apptm_id = apptm_id', 'left')
                ->select('apptm_id,apptm_code,apptm_status,apptm_lead_id,apptm_status,apptm_transport_service,apptm_created_on,
        apptm_created_by,apptm_updated_on,apptm_updated_by,apptm_delete_flag,appt_date,appt_time,appt_note,appt_assign_to')
                ->findAll();
            }
            if (($cust_master_res && $cust_master_res['cust_alm_code'])) {
                $retData['lead_log'] = $logmodel->like('lcl_phone', $phoneLastDigits)
                    ->where('lcl_pupose_id !=', 0)->join('users', 'users.ext_number =lcl_call_to', 'left')
                    ->join('user_roles', 'users.us_role_id =user_roles.role_id ', 'left')
                    ->join('call_purposes', 'call_purposes.cp_id =lcl_pupose_id', 'left')
                    ->select('lcl_id,lcl_time,lcl_pupose_id,lcl_call_to,us_firstname,role_name,call_purpose,lcl_purpose_note,ystar_call_id')
                    ->orderBy('lcl_id', 'desc')
                    ->findAll();
                $retData['leadCount'] = $leadmodel->select('count(lead_id) as lc')
                    ->where('cus_id', $cust_master_res['cus_id'])
                    ->where('status_id !=', 7)->find();
                if ($cust_master_res['cust_alm_code'] != 0 && $cust_master_res['cust_alm_code'] != "") {
                    $marvehmodel = new MaraghiVehicleModel();
                    $marjcmodel = new MaraghiJobcardModel();
                    $tempVeh = $marvehmodel->where('customer_code', $cust_master_res['cust_alm_code'])->where('reg_no IS NOT NULL', null, false)->select('reg_no')->findAll();
                    if (sizeof($tempVeh) > 0) {
                        $cust_master_res['vehicle_history'] = array_map(function ($item) {
                            return $item['reg_no'];
                        }, $tempVeh);
                    } else {
                        $cust_master_res['vehicle_history'] = [];
                    }
                    $cust_master_res['job_history'] = $marjcmodel->where('customer_no', $cust_master_res['cust_alm_code'])->orderBy('job_no', "desc")->select('DATE(created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no')->findAll();
                }
                
                $retData['customer_details'] = $cust_master_res;
                $retData['ret_data'] = "success";
            } else {
                $retData['customer_details'] = null;
                $retData['lead_log'] = $logmodel->like('lcl_phone', $phoneLastDigits)
                    ->where('lcl_pupose_id !=', 0)->join('users', 'users.ext_number =lcl_call_to', 'left')
                    ->join('user_roles', 'users.us_role_id =user_roles.role_id ', 'left')
                    ->join('call_purposes', 'call_purposes.cp_id =lcl_pupose_id', 'left')
                    ->select('lcl_time,lcl_pupose_id,lcl_call_to,us_firstname,role_name,call_purpose,lcl_purpose_note')
                    ->orderBy('lcl_id', 'desc')
                    ->findAll();
                $retData['leadCount'] = 0;
                $retData['ret_data'] = "success";
            }
            return $this->respond($retData, 200);
        }
    }
    // public function viewDetailFromPopup()
    // {
    //     $marcustmodel = new MaragiCustomerModel();
    //     $marvehmodel = new MaraghiVehicleModel();
    //     $marjcmodel = new MaraghiJobcardModel();
    //     $leadmodel = new LeadModel();
    //     $custmastermodel = new CustomerMasterModel();
    //     $quotmodel = new QuotesMasterModel();
    //     $logmodel = new LeadCallLogModel();
    //     $psf_master = new PSFMasterModel();
    //     $ApptMaster = new AppointmentMasterModel();

    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {

    //         $phone = $this->request->getVar('phone');
    //         $leadid = $this->request->getVar('leadid');

    //         $Appointment = $ApptMaster->where('apptm_lead_id', $leadid)
    //             ->where('appointment.appt_status =', 0)
    //             ->where('apptm_delete_flag!=', 1)
    //             ->join('appointment', 'appointment.appt_apptm_id = apptm_id', 'left')
    //             ->select('apptm_id,apptm_code,apptm_status,apptm_lead_id,apptm_status,apptm_transport_service,apptm_created_on,
    //         apptm_created_by,apptm_updated_on,apptm_updated_by,apptm_delete_flag,appt_date,appt_time,appt_note,appt_assign_to')
    //             ->findAll();


    //         $ph = substr($phone, -9);
    //         $patern = $ph;
    //         $patern_a = $ph;
    //         $hours =  date('Y-m-d', strtotime('-1 day'));
    //         $cust_master_res = $custmastermodel->like('cust_phone', $patern)
    //             ->orLike('cust_alternate_contact', $patern)
    //             ->select('cust_alternate_contact,cus_id,cust_alm_code,cust_name as customer_name,
    //             cust_city as city,cust_phone as mobile')
    //             ->first();
    //         // if($cust_master_res && $cust_master_res['cust_alternate_contact'] != NULL)
    //         // {
    //         //     $patern = substr($cust_master_res['mobile'], -7);
    //         //     $patern_a = substr($cust_master_res['cust_alternate_contact'], -7);
    //         // }

    //         // print_r( $cust_master_res);die;
    //         $marag_cus_res = $marcustmodel->like('phone', $patern)
    //             ->orLike('mobile', $patern)
    //             ->select('customer_code,customer_name,city,mobile')->first();

    //         if (($cust_master_res && $cust_master_res['cust_alm_code']) || ($marag_cus_res && $marag_cus_res['customer_code'])) {
    //             $statusarr = [0, 1, 2, 3, 6, 8, 16];
    //             if ($cust_master_res && $cust_master_res['cust_alm_code']) {
    //                 $mcode = $cust_master_res['cust_alm_code'];
    //                 $psf = $psf_master->where('psfm_customer_code', $mcode)
    //                     ->whereIn('psfm_status', $statusarr)->select('psfm_status')->first();
    //             } else if ($marag_cus_res && $marag_cus_res['customer_code']) {
    //                 $mrcode = $marag_cus_res['customer_code'];
    //                 $psf = $psf_master->Where('psfm_customer_code', $mrcode)
    //                     ->whereIn('psfm_status', $statusarr)->select('psfm_status')->first();
    //             }
    //         } else {
    //             $psf = [];
    //         }

    //         $lead_res = $leadmodel->like('phone', $patern)->where('status_id !=', 7)
    //             ->where('DATE(lead_updatedon) >=', $hours)
    //             ->select('lead_id ,name as customer_name,phone as mobile,status_id,assigned,ld_appoint_date,purpose_id,ld_appoint_time,lead_note,purpose_id')
    //             ->first();
    //         $lead_log = $logmodel->like('lcl_phone', $patern)->orLike('lcl_phone', $patern_a)
    //             ->where('lcl_pupose_id !=', 0)->join('users', 'users.ext_number =lcl_call_to', 'left')
    //             ->join('user_roles', 'users.us_role_id =user_roles.role_id ', 'left')
    //             ->join('call_purposes', 'call_purposes.cp_id =lcl_pupose_id', 'left')
    //             ->select('lcl_time,lcl_pupose_id,lcl_call_to,us_firstname,role_name,call_purpose,lcl_purpose_note')
    //             ->orderBy('lcl_id', 'desc')
    //             ->findAll();

    //         //  $logmodel->where('lcl_lead_id',$leadid)

    //         if ($cust_master_res) // Phone number found in CRM customer master table
    //         {
    //             $this->insertUserLog('View Details From Call Popup', $tokendata['uid']);
    //             $cust_code = $cust_master_res['cust_alm_code'];
    //             $cust_id = $cust_master_res['cus_id'];

    //             if ($cust_code != 0) // Phone number found in CRM customer master table && alm code !=0
    //             {
    //                 $result =  $this->getMaraghiData($cust_code);
    //                 $response = [
    //                     'ret_data' => 'success',
    //                     'message' => 'Customer Master',
    //                     'customer' => $marag_cus_res,
    //                     'vehicle' => $result['vehicle'],
    //                     'jobcard' => $result['jobcard'],
    //                     'lead' => $result['lead'],
    //                     'quot' => $result['quot'],
    //                     'totalLead' => $result['resCount'],
    //                     'pendLead' => $result['penCount'],
    //                     'pendLeadId' =>  $result['penLeadId'],
    //                     'leadLog'  => $lead_log,
    //                     'JCS' => $result['JCS'],
    //                     'JCY' => $result['JCY'],
    //                     'psf' => $psf,
    //                     'Appointment' => $Appointment,

    //                 ];

    //                 return $this->respond($response, 200);
    //             } else { // Phone number found in CRM customer master table && alm code == 0

    //                 if ($marag_cus_res) //Phone number found in Maraghi customer  table
    //                 {
    //                     $cust_code = $marag_cus_res['customer_code'];
    //                     $data = [
    //                         'cust_alm_code' => $cust_code,
    //                     ];

    //                     $custmastermodel->where('cus_id', $cust_id)->set($data)->update(); //update alm code in CRM Customer master table
    //                     $result =  $this->getMaraghiData($cust_code);
    //                     $response = [
    //                         'ret_data' => 'success',
    //                         'message' => 'Maraghi Customer',
    //                         'customer' => $marag_cus_res,
    //                         'vehicle' => $result['vehicle'],
    //                         'jobcard' => $result['jobcard'],
    //                         'lead' => $result['lead'],
    //                         'quot' => $result['quot'],
    //                         'totalLead' => $result['resCount'],
    //                         'pendLead' => $result['penCount'],
    //                         'pendLeadId' =>  $result['penLeadId'],
    //                         'leadLog'  => $lead_log,
    //                         'JCS' => $result['JCS'],
    //                         'JCY' => $result['JCY'],
    //                         'psf' => $psf,
    //                         'Appointment' => $Appointment

    //                     ];
    //                     return $this->respond($response, 200);
    //                 } else //Phone number not found in Maraghi customer  table
    //                 {
    //                     $resV = $leadmodel->where('cus_id', $cust_id)
    //                         ->where('register_number IS NOT NULL', null, false)
    //                         ->select('register_number as reg_no,vehicle_model as model_name')->findAll();
    //                     $resL = $leadmodel->where('cus_id', $cust_id)
    //                         ->where('status_id !=', 7)->orderBy('lead_id', "desc")
    //                         ->join('lead_source', 'lead_source.ld_src_id =source_id', 'left')
    //                         ->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left')
    //                         ->select('DATE(lead_createdon) as created,lead_code,vehicle_model,lead_note,
    //                     lead_source.ld_src,lead_status.ld_sts,status_id,assigned,ld_appoint_date,ld_appoint_time,lead_note,purpose_id')->findAll();
    //                     $resQ = $quotmodel->where('qt_cus_id', $cust_id)->select('qt_code,qt_reg_no,qt_type,qt_total')->findAll();

    //                     $builder = $this->db->table('customer_master');
    //                     $builder->select('count(leads.cus_id) as lc');
    //                     $builder->where('customer_master.cus_id', $cust_id);
    //                     $builder->where('status_id !=', 7);
    //                     $builder->join('leads', 'leads.cus_id = customer_master.cus_id');
    //                     $query = $builder->get();
    //                     $result = $query->getRow();
    //                     $resCount =  $result->lc;

    //                     $builder = $this->db->table('customer_master');
    //                     $builder->select('count(leads.cus_id) as lc,lead_id');
    //                     $builder->join('leads', 'leads.cus_id = customer_master.cus_id');
    //                     $builder->where('customer_master.cus_id', $cust_id);
    //                     $builder->where('status_id', '1');
    //                     //  $builder->orWhere('status_id', '1');      
    //                     $query = $builder->get();
    //                     $result = $query->getRow();
    //                     $penCount =  $result->lc;
    //                     $penLeadId = $result->lead_id;

    //                     $response = [
    //                         'ret_data' => 'success',
    //                         'message' => 'Maraghi Customer',
    //                         'customer' => $cust_master_res,
    //                         'vehicle' => $resV,
    //                         'quot' => $resQ,
    //                         'jobcard' => [],
    //                         'lead' => $resL,
    //                         'totalLead' => $resCount,
    //                         'pendLead' => $penCount,
    //                         'penLeadId' => $penLeadId,
    //                         'leadLog'  => $lead_log,
    //                         'JCS' => [],
    //                         'JCY' => [],
    //                         'psf' => $psf,
    //                         'Appointment' => $Appointment


    //                     ];
    //                     return $this->respond($response, 200);
    //                 }
    //             }
    //         } else if ($marag_cus_res) //Phone number found in Maraghi customer  table
    //         {
    //             $this->insertUserLog('View Details From Call Popup', $tokendata['uid']);
    //             $cust_code = $marag_cus_res['customer_code'];
    //             $result =  $this->getMaraghiData($cust_code);
    //             $response = [
    //                 'ret_data' => 'success',
    //                 'message' => 'Maraghi customer',
    //                 'customer' => $marag_cus_res,
    //                 'vehicle' => $result['vehicle'],
    //                 'jobcard' => $result['jobcard'],
    //                 'lead' => $result['lead'],
    //                 'quot' => $result['quot'],
    //                 'totalLead' => $result['resCount'],
    //                 'pendLead' => $result['penCount'],
    //                 'pendLeadId' =>  $result['penLeadId'],
    //                 'leadLog'  => $lead_log,
    //                 'JCS' => $result['JCS'],
    //                 'JCY' => $result['JCY'],
    //                 'psf' => $psf,
    //                 'Appointment' => $Appointment

    //             ];
    //             return $this->respond($response, 200);
    //         } else if ($lead_res) //Phone number found in Lead table
    //         {
    //             $this->insertUserLog('View Details From Call Popup', $tokendata['uid']);
    //             $lead_res['city'] = '';
    //             $lead_id = $lead_res['lead_id'];
    //             $resV = $leadmodel->like('phone', $patern)->where('register_number IS NOT NULL', null, false)->select('register_number as reg_no,vehicle_model as model_name')->findAll();
    //             $resL = $leadmodel->where('status_id !=', 7)->like('phone', $patern)->orderBy('lead_id', "desc")
    //                 ->join('lead_source', 'lead_source.ld_src_id =source_id', 'left')
    //                 ->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left')
    //                 ->select('DATE(lead_createdon) as created,lead_code,vehicle_model,lead_note,lead_source.ld_src,
    //             lead_status.ld_sts,ld_appoint_date,status_id,assigned,ld_appoint_time,lead_note,purpose_id')->findAll();

    //             $builder = $this->db->table('leads');
    //             $builder->select('count(leads.lead_id) as lc');
    //             $builder->like('phone', $patern);
    //             $builder->where('status_id !=', 7);

    //             $query = $builder->get();
    //             $result = $query->getRow();
    //             $resCount =  $result->lc;

    //             $builder = $this->db->table('leads');
    //             $builder->select('count(leads.lead_id) as pc,lead_id');
    //             $builder->like('phone', $patern);
    //             $builder->where('status_id', '1');
    //             //  $builder->orwhere('status_id', '2');
    //             $queryp = $builder->get();
    //             $resultp = $queryp->getRow();
    //             $penCount =  $resultp->pc;
    //             $penLeadId = $resultp->lead_id;

    //             $response = [
    //                 'ret_data' => 'success',
    //                 'message' => 'Lead customer',
    //                 'customer' => $lead_res,
    //                 'vehicle' => $resV,
    //                 'jobcard' => [],
    //                 'quot' => [],
    //                 'lead' => $resL,
    //                 'totalLead' => $resCount,
    //                 'pendLead' => $penCount,
    //                 'penLeadId' => $penLeadId,
    //                 'leadLog'  => $lead_log,
    //                 'JCS' => [],
    //                 'JCY' => [],
    //                 'psf' => $psf,
    //                 'Appointment' => $Appointment
    //             ];
    //             return $this->respond($response, 200);
    //         } else {
    //             $this->insertUserLog('View Details From Call Popup', $tokendata['uid']);
    //             $response = [
    //                 'ret_data' => 'fail',
    //                 'customer' => [],
    //                 'vehicle' => [],
    //                 'jobcard' => [],
    //                 'lead' => [],
    //                 'totalLead' => 0,
    //                 'pendLead' => 0,
    //                 'leadLog'  => $lead_log,
    //                 'JCS' => [],
    //                 'JCY' => [],
    //                 'psf' => $psf,
    //             ];
    //             return $this->respond($response, 200);
    //         }
    //     }
    // }

    public function JobCardList()
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
            $builder = $this->db->table('cust_job_data_laabs');
            $builder->select('cust_job_data_laabs.*,cust_data_laabs.customer_name,speedometer_reading');
            $builder->join('cust_data_laabs', 'cust_data_laabs.customer_code = cust_job_data_laabs.customer_no', 'INNER JOIN');
            $builder->orderBy('cust_job_data_laabs.job_no', 'desc');
            $builder->limit(5000);
            // $builder->join('cust_veh_data_laabs', 'cust_veh_data_laabs.reg_no = cust_job_data_laabs.car_reg_no', 'INNER JOIN');
            $query = $builder->get();
            $resJ = $query->getResultArray();
            if ($resJ) {
                $response = [
                    'ret_data' => 'success',
                    'jobcard' => $resJ
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
                'customer' => []
            ];
            return $this->respond($response, 200);
        }
    }

    public function JobCardDetail()
    {
        $model = new MaragiCustomerModel();
        $modelJ = new MaraghiJobcardModel();
        $leadmodel = new LeadModel();
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
            $phone = $this->request->getVar('phone');
            $ph = substr($phone, -7);
            $patern = '%' . $ph;
            $resC = $model->where('RIGHT(phone, 7) =', $ph)->first();
            if ($resC) {
                $cust_id = $resC['customer_code'];
                $builder = $this->db->table('cust_job_data_laabs');
                $builder->select('cust_job_data_laabs.*,cust_data_laabs.customer_name,speedometer_reading');
                $builder->where('customer_no', $cust_id);
                $builder->join('cust_data_laabs', 'cust_data_laabs.customer_code = cust_job_data_laabs.customer_no', 'INNER JOIN');
                $builder->orderBy('cust_job_data_laabs.job_no', 'desc');
                // $builder->join('cust_veh_data_laabs', 'cust_veh_data_laabs.reg_no = cust_job_data_laabs.car_reg_no', 'INNER JOIN');
                $query = $builder->get();
                $resJ = $query->getResultArray();

                // $builder->where('frm_role_id', base64_decode($id));
                // $builder->join('user_roles', 'user_roles.role_id = feature_role_mapping.frm_role_id', 'INNER JOIN');



                // $resJ= $modelJ->where('customer_no', $cust_id)->orderBy('job_no', "desc")->join('cust_data_laabs','cust_data_laabs.customer_code =customer_no','left')->join('cust_veh_data_laabs','cust_veh_data_laabs.reg_no =reg_no','left')->select('cust_veh_data_laabs.reg_no');
                $response = [
                    'ret_data' => 'success',
                    'jobcard' => $resJ
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'customer' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function leadlistByCust($id = null)
    {
        $model = new LeadModel();
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
            $phone = $this->db->escapeString($this->request->getVar('phone'));
            $res = $model->where('phone', $phone)
            ->join('lead_source', 'lead_source.ld_src_id =source_id', 'left')
            ->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left')
            ->select('leads.*,lead_source.ld_src,lead_status.ld_sts')
            ->findAll(3);
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'lead' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'lead' => []
                ];
                return $this->fail($response, 400);
            }
        }
    }
    public function getMaraghiData($cust_code)
    {

        $marvehmodel = new MaraghiVehicleModel();
        $marjcmodel = new MaraghiJobcardModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();

        $resV = $marvehmodel->where('customer_code', $cust_code)->where('reg_no IS NOT NULL', null, false)->select('reg_no,family_name,brand_code,model_name,model_year,miles_done')->findAll();
        $resJ = $marjcmodel->where('customer_no', $cust_code)->orderBy('job_no', "desc")->select('DATE(created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no')->findAll();
        $resL = $custmastermodel->where('cust_alm_code', $cust_code)->where('status_id !=', 7)->orderBy('lead_id', "desc")->join('leads', 'leads.cus_id = customer_master.cus_id')->join('lead_source', 'lead_source.ld_src_id =source_id', 'left')->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left')->select('DATE(lead_createdon) as created,vehicle_model,register_number,source_id,purpose_id,lang_id,lead_note,status_id,assigned,ld_brand,ld_src,ld_sts,lead_code,ld_appoint_date')->findAll();
        $resQ = $custmastermodel->where('cust_alm_code', $cust_code)->join('quotes_master', 'quotes_master.qt_cus_id = customer_master.cus_id')->findAll();
        $resJC = $marjcmodel->where('customer_no', $cust_code)->orderBy('job_no', "desc")->select('DATE(created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no')->first();
        // $resJY= $marjcmodel->where('customer_no', $cust_code)->select('substrinoig(invce_date, 7, 10) as year')->findAll();
        $resJY = $marjcmodel->where('customer_no', $cust_code)->where('invoice_date !=', '')->groupBy('substring(invoice_date, 7, 10)')->select('substring(invoice_date, 7, 10) as year,count(job_no) as jy')->limit(4)->findAll();


        $builder = $this->db->table('customer_master');
        $builder->select('count(leads.lead_id) as lc');
        $builder->where('cust_alm_code', $cust_code);
        $builder->where('leads.status_id !=', 7);
        $builder->join('leads', 'leads.cus_id = customer_master.cus_id');
        $query = $builder->get();
        $result = $query->getRow();
        $resCount =  $result->lc;

        $builder = $this->db->table('customer_master');
        $builder->select('count(leads.cus_id) as pc,lead_id');
        $builder->join('leads', 'leads.cus_id = customer_master.cus_id');
        $builder->where('cust_alm_code', $cust_code);
        $builder->where('status_id', '1');
        // $builder->orWhere('status_id', '1');      
        $queryp = $builder->get();
        $resultp = $queryp->getRow();
        $penCount =  $resultp->pc;
        $penLeadId = $resultp->lead_id;

        $response = [
            'vehicle' => $resV,
            'jobcard' => $resJ,
            'lead' => $resL,
            'quot' => $resQ,
            'resCount' => $resCount,
            'penCount' => $penCount,
            'penLeadId' => $penLeadId,
            'JCS' => $resJC,
            'JCY' => array_reverse($resJY)

        ];

        return $response;
    }
    public function getMaraghiDataInfo($cust_code)
    {

        $marvehmodel = new MaraghiVehicleModel();
        $marjcmodel = new MaraghiJobcardModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();

        $resV = $marvehmodel->where('customer_code', $cust_code)->where('reg_no IS NOT NULL', null, false)->select('reg_no,family_name,brand_code,model_name,model_year,miles_done')->findAll();
        $resJ = $marjcmodel->where('customer_no', $cust_code)->orderBy('job_no', "desc")->select('DATE(created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no')->findAll();
        $resL = $custmastermodel->where('cust_alm_code', $cust_code)->where('status_id !=', 7)->orderBy('lead_id', "desc")->join('leads', 'leads.cus_id = customer_master.cus_id')->join('lead_source', 'lead_source.ld_src_id =source_id', 'left')->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left')->select('DATE(lead_createdon) as created,vehicle_model,register_number,source_id,purpose_id,lang_id,lead_note,status_id,ld_brand,ld_src,ld_sts,lead_code')->findAll();
        $resQ = $custmastermodel->where('cust_alm_code', $cust_code)->join('quotes_master', 'quotes_master.qt_cus_id = customer_master.cus_id')->findAll();
        $resJC = $marjcmodel->where('customer_no', $cust_code)->orderBy('job_no', "desc")->select('DATE(created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no')->first();
        $resJY = $marjcmodel->where('customer_no', $cust_code)->where('invoice_date !=', '')->groupBy('substring(invoice_date, 7, 10)')->select('substring(invoice_date, 7, 10) as year,count(job_no) as jy')->limit(4)->findAll();


        $builder = $this->db->table('customer_master');
        $builder->select('count(leads.lead_id) as lc');
        $builder->where('cust_alm_code', $cust_code);
        $builder->where('leads.status_id !=', 7);
        $builder->join('leads', 'leads.cus_id = customer_master.cus_id');
        $query = $builder->get();
        $result = $query->getRow();
        $resCount =  $result->lc;

        $builder = $this->db->table('customer_master');
        $builder->select('count(leads.cus_id) as pc,lead_id');
        $builder->join('leads', 'leads.cus_id = customer_master.cus_id');
        $builder->where('cust_alm_code', $cust_code);
        $builder->where('status_id', '1');
        // $builder->orWhere('status_id', '1');      
        $queryp = $builder->get();
        $resultp = $queryp->getRow();
        $penCount =  $resultp->pc;
        $penLeadId = $resultp->lead_id;

        $response = [
            'vehicle' => $resV,
            'jobcard' => $resJ,
            'lead' => $resL,
            'quot' => $resQ,
            'resCount' => $resCount,
            'penCount' => $penCount,
            'penLeadId' => $penLeadId,
            'JCS' => $resJC,
            'JCY' => array_reverse($resJY)

        ];

        return $response;
    }
    public function call_logs()
    {
        $logmodel = new CustomerCallsModel();
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

            $phone = $this->request->getVar('phone');
            $ph = substr($phone, -7);
            $patern = '%' . $ph;

            $incoming = $logmodel->like('call_from', $patern)->join('users', 'users.ext_number=call_to', 'left')->select('us_firstname,DATE(cust_call_logs.created_on) as created,TIME(cust_call_logs.created_on) as time,cust_call_logs.*')->limit(20)->findAll();
            $out = $logmodel->like('call_to', $patern)->join('users', 'users.ext_number=call_from', 'left')->select('us_firstname,DATE(cust_call_logs.created_on) as created,TIME(cust_call_logs.created_on) as time,cust_call_logs.*')->limit(20)->findAll();
            // $log = array_merge($incoming,$out);

            $builder = $this->db->table('cust_call_logs');
            $builder->select('us.us_firstname as to,usr.us_firstname as from,DATE(cust_call_logs.created_on) as created,cust_call_logs.*');
            $builder->like('call_from', $patern);
            $builder->orLike('call_to', $patern);
            $builder->join('users as us', 'us.ext_number=call_to', 'left');
            $builder->join('users as usr', 'usr.ext_number=call_from', 'left');
            $builder->orderBy('cust_call_logs.call_id', 'desc');
            $builder->limit(20);
            $query = $builder->get();
            $log = $query->getResultArray();


            $response = [
                'ret_data' => 'success',
                'incoming' => $incoming,
                'out' => $out,
                'log' => $log
            ];
            return $this->respond($response, 200);
        }
    }

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
    public function CallInfo()
    {
        $marcustmodel = new MaragiCustomerModel();
        $marvehmodel = new MaraghiVehicleModel();
        $marjcmodel = new MaraghiJobcardModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();
        $quotmodel = new QuotesMasterModel();
        $logmodel = new LeadCallLogModel();

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

            $phone = $this->request->getVar('phone');
            $leadid = $this->request->getVar('leadid');
            $ph = substr($phone, -8);
            $patern = $ph;

            $cust_master_res = $custmastermodel->where('RIGHT(cust_phone,8)', $patern)
                ->select('cus_id,cust_alm_code,cust_name as customer_name,cust_city as city,cust_phone as mobile,customer_cat_type,cust_alm_code as customer_code')
                ->join('cust_data_laabs', 'cust_data_laabs.customer_code =cust_alm_code')
                ->first();
            $marag_cus_res = $marcustmodel->where('RIGHT(phone,8)', $patern)
                ->orWhere('RIGHT(mobile,8)', $patern)->select('customer_code,customer_name,city,phone,mobile,customer_cat_type,customer_code as cust_alm_code')
                ->first();
            $lead_res = $leadmodel->like('phone', $patern)->where('status_id !=', 7)->select('lead_id ,name as customer_name,phone as mobile')->first();
            $lead_log = $logmodel->like('lcl_phone', $patern)->where('lcl_pupose_id !=', 0)->join('users', 'users.ext_number =lcl_call_to', 'left')
                ->join('user_roles', 'users.us_role_id =user_roles.role_id ', 'left')
                ->join('call_purposes', 'call_purposes.cp_id =lcl_pupose_id', 'left')
                ->select('lcl_time,lcl_pupose_id,lcl_call_to,us_firstname,role_name,call_purpose,lcl_purpose_note')
                ->orderBy('lcl_id', 'desc')
                ->findAll();
            //  $logmodel->where('lcl_lead_id',$leadid)

            if ($cust_master_res) // Phone number found in CRM customer master table
            {

                $cust_code = $cust_master_res['cust_alm_code'];
                $cust_id = $cust_master_res['cus_id'];

                if ($cust_code != 0) // Phone number found in CRM customer master table && alm code !=0
                {
                    $result =  $this->getMaraghiData($cust_code);
                    $response = [
                        'ret_data' => 'success',
                        'customer' => $cust_master_res,
                        'vehicle' => $result['vehicle'],
                        'jobcard' => $result['jobcard'],
                        'lead' => $result['lead'],
                        'quot' => $result['quot'],
                        'totalLead' => $result['resCount'],
                        'pendLead' => $result['penCount'],
                        'pendLeadId' =>  $result['penLeadId'],
                        'leadLog'  => $lead_log,
                        'JCS' => $result['JCS'],
                        'JCY' => $result['JCY'],
                    ];

                    return $this->respond($response, 200);
                } else { // Phone number found in CRM customer master table && alm code == 0

                    if ($marag_cus_res) //Phone number found in Maraghi customer  table
                    {
                        $cust_code = $marag_cus_res['customer_code'];
                        $data = [
                            'cust_alm_code' => $cust_code,
                        ];

                        $custmastermodel->where('cus_id', $cust_id)->set($data)->update(); //update alm code in CRM Customer master table
                        $result =  $this->getMaraghiDataInfo($cust_code);
                        $response = [
                            'ret_data' => 'success',
                            'customer' => $marag_cus_res,
                            'vehicle' => $result['vehicle'],
                            'jobcard' => $result['jobcard'],
                            'lead' => $result['lead'],
                            'quot' => $result['quot'],
                            'totalLead' => $result['resCount'],
                            'pendLead' => $result['penCount'],
                            'pendLeadId' =>  $result['penLeadId'],
                            'leadLog'  => $lead_log,
                            'JCS' => $result['JCS'],
                            'JCY' => $result['JCY'],

                        ];
                        return $this->respond($response, 200);
                    } else //Phone number not found in Maraghi customer  table
                    {
                        $resV = $leadmodel->where('cus_id', $cust_id)->where('register_number IS NOT NULL', null, false)->select('register_number as reg_no,vehicle_model as model_name')->findAll();
                        $resL = $leadmodel->where('cus_id', $cust_id)->where('status_id !=', 7)->orderBy('lead_id', "desc")->join('lead_source', 'lead_source.ld_src_id =source_id', 'left')->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left')->select('DATE(lead_createdon) as created,lead_code,vehicle_model,lead_note,lead_source.ld_src,lead_status.ld_sts')->findAll();
                        $resQ = $quotmodel->where('qt_cus_id', $cust_id)->select('qt_code,qt_reg_no,qt_type,qt_total')->findAll();

                        $builder = $this->db->table('customer_master');
                        $builder->select('count(leads.cus_id) as lc');
                        $builder->where('customer_master.cus_id', $cust_id);
                        $builder->where('status_id !=', 7);
                        $builder->join('leads', 'leads.cus_id = customer_master.cus_id');
                        $query = $builder->get();
                        $result = $query->getRow();
                        $resCount =  $result->lc;

                        $builder = $this->db->table('customer_master');
                        $builder->select('count(leads.cus_id) as lc,lead_id');
                        $builder->join('leads', 'leads.cus_id = customer_master.cus_id');
                        $builder->where('customer_master.cus_id', $cust_id);
                        $builder->where('status_id', '1');
                        //  $builder->orWhere('status_id', '1');      
                        $query = $builder->get();
                        $result = $query->getRow();
                        $penCount =  $result->lc;
                        $penLeadId = $result->lead_id;

                        $response = [
                            'ret_data' => 'success',
                            'customer' => $cust_master_res,
                            'vehicle' => $resV,
                            'quot' => $resQ,
                            'jobcard' => [],
                            'lead' => $resL,
                            'totalLead' => $resCount,
                            'pendLead' => $penCount,
                            'penLeadId' => $penLeadId,
                            'leadLog'  => $lead_log,
                            'JCS' => [],
                            'JCY' => [],

                        ];
                        return $this->respond($response, 200);
                    }
                }
            } else if ($marag_cus_res) //Phone number found in Maraghi customer  table
            {

                $cust_code = $marag_cus_res['customer_code'];
                $result =  $this->getMaraghiDataInfo($cust_code);
                $response = [
                    'ret_data' => 'success',
                    'customer' => $marag_cus_res,
                    'vehicle' => $result['vehicle'],
                    'jobcard' => $result['jobcard'],
                    'lead' => $result['lead'],
                    'quot' => $result['quot'],
                    'totalLead' => $result['resCount'],
                    'pendLead' => $result['penCount'],
                    'pendLeadId' =>  $result['penLeadId'],
                    'leadLog'  => $lead_log,
                    'JCS' => $result['JCS'],
                    'JCY' => $result['JCY'],

                ];
                return $this->respond($response, 200);
            } else if ($lead_res) //Phone number found in Lead table
            {

                $lead_res['city'] = '';
                $lead_id = $lead_res['lead_id'];
                $resV = $leadmodel->like('phone', $patern)->where('register_number IS NOT NULL', null, false)->select('register_number as reg_no,vehicle_model as model_name')->findAll();
                $resL = $leadmodel->where('status_id !=', 7)->like('phone', $patern)->orderBy('lead_id', "desc")->join('lead_source', 'lead_source.ld_src_id =source_id', 'left')->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left')->select('DATE(lead_createdon) as created,lead_code,vehicle_model,lead_note,lead_source.ld_src,lead_status.ld_sts')->findAll();

                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as lc');
                $builder->like('phone', $patern);
                $builder->where('status_id !=', 7);

                $query = $builder->get();
                $result = $query->getRow();
                $resCount =  $result->lc;

                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as pc,lead_id');
                $builder->like('phone', $patern);
                $builder->where('status_id', '1');
                //  $builder->orwhere('status_id', '2');
                $queryp = $builder->get();
                $resultp = $queryp->getRow();
                $penCount =  $resultp->pc;
                $penLeadId = $resultp->lead_id;

                $response = [
                    'ret_data' => 'success',
                    'customer' => $lead_res,
                    'vehicle' => $resV,
                    'jobcard' => [],
                    'quot' => [],
                    'lead' => $resL,
                    'totalLead' => $resCount,
                    'pendLead' => $penCount,
                    'penLeadId' => $penLeadId,
                    'leadLog'  => $lead_log,
                    'JCS' => [],
                    'JCY' => [],
                ];
                return $this->respond($response, 200);
            } else {

                $response = [
                    'ret_data' => 'fail',
                    'customer' => [],
                    'vehicle' => [],
                    'jobcard' => [],
                    'lead' => [],
                    'totalLead' => 0,
                    'pendLead' => 0,
                    'leadLog'  => $lead_log,
                    'JCS' => [],
                    'JCY' => [],
                ];
                return $this->respond($response, 200);
            }
        }
    }
}
