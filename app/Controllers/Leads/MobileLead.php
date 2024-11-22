<?php

namespace App\Controllers\Leads;




use CodeIgniter\RESTful\ResourceController;
use App\Models\Leads\LeadModel;
use App\Models\SuperAdminModel;
use App\Models\Leads\LeadActivityModel;
use App\Models\UserModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\MaragiCustomerModel;
use Config\Common;
use Config\Validation;
use App\Models\Leads\LeadCallLogModel;
use App\Models\Leads\AppointmentMasterModel;
use App\Models\Leads\AppointmentModel;
use App\Models\Leads\AppointmentLogModel;
use App\Models\Leads\LeadQuoteLogModel;

class MobileLead extends  ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    public function index()
    {
        //
    }


    public function existcalllog(){
       $call_log_model= new LeadCallLogModel();
       $common = new Common();
       $valid = new Validation();
       $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        $created_by = $tokendata['uid'];
        $created_on =$this->request->getVar('call_created_on');
        
        $created_on = date("Y-m-d", strtotime( $created_on));
        
        $call_log_data = $call_log_model->where('DATE(lcl_created_on)', $created_on)->where('lcl_createdby', $created_by)->select(['ystar_call_id','lcl_pupose_id'])->findAll();
        $data['ret_data'] = "success";
       
        if($call_log_data){
       
        $data['data'] = $call_log_data;
       
       
       }
       else{
        $data['data'] = [];
       }
       return $this->respond($data, 200);

    }

    public function create()
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $leadAcModel = new LeadActivityModel();
        $cust_mastr_model = new CustomerMasterModel();
        $maraghi_cust_model = new MaragiCustomerModel();
        $ApptMaster = new AppointmentMasterModel();
        $Appoint = new AppointmentModel();
        $Appointmentlog = new AppointmentLogModel();
        $Quotemodel = new LeadQuoteLogModel();
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
            $rules = [

                'customerNumber' => 'required',

            ];
            $phone = $this->request->getVar('customerNumber');
            $lead_id = $this->request->getVar('lead_id');
            if($lead_id!=null || $lead_id != ""){
                if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
                $purpose_id = $this->request->getVar('call_purpose');
                $this->db->transStart();
                $data = [                                    
                   
                    'lead_note' => $this->request->getVar('call_note'),
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                    'lead_updatedby' =>$tokendata['uid'],
                    
                ];
                $lead_note = $this->request->getVar('call_note');
                $lead_update = $model->where('lead_id',$lead_id)->first();
                if($lead_update) {
                    $model->update($lead_id, $data);
                }
                $coll_log_data = new LeadCallLogModel();
                $logdata = [
                 'lcl_lead_id' => $lead_id,
                 'lcl_pupose_id' => $purpose_id,
                 'lcl_purpose_note' => $lead_note,
                 'lcl_call_to' =>  $tokendata['uid'],
                 'lcl_phone' => $phone ,
                 'lcl_call_type' => $this->request->getVar('call_type'),
                 'lcl_created_on' =>  date("Y-m-d H:i:s"),
                 'lcl_createdby' =>$tokendata['uid'],
                 //   'lcl_call_time'=>$this->request->getVar('call_time'),
                 'ystar_call_id' =>  $this->request->getVar('call_timestamp'),
                'lcl_call_source' => 3
             ];
             $coll_log_data->insert($logdata);
             if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->db->transCommit();
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            }
               
                
              

            }
            else{
                $builder = $this->db->table('sequence_data');
                $builder->selectMax('current_seq');
                $query = $builder->get();
                $row = $query->getRow();
                $lead_code = $row->current_seq;
                $leadSeqvalfinal = $row->current_seq;
                if (strlen($row->current_seq) == 1) {
                    $lead_code = "ALMLD-000" . $row->current_seq;
                } else if (strlen($row->current_seq) == 2) {
                    $lead_code = "ALMLD-00" . $row->current_seq;
                } else if (strlen($row->current_seq) == 3) {
                    $lead_code = "ALMLD-0" . $row->current_seq;
                } else {
                    $lead_code = "ALMLD-" . $row->current_seq;
                }
                if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
    
                $phone = $this->request->getVar('customerNumber');
                $purpose_id = $this->request->getVar('call_purpose');
                $assigned = $this->request->getVar('assigned_to');
                $lead_note = $this->request->getVar('call_note');
                $cust_id = 0;
                $this->db->transStart();
                $data = [
                    // 'name' => $this->request->getVar('customerName'),
                    //  'phone' => $this->request->getVar('phone'),
                   
                    'lead_code' => $lead_code,
                    'lead_note' => $this->request->getVar('call_note'),
                    'lang_id' => 1,
                    'purpose_id' => $purpose_id,
                    'register_number' => $this->request->getVar('reg_no'),
                    'vehicle_model' => $this->request->getVar('vehicle_model'),
                    'source_id' => $this->request->getVar('source_id'),
                    'lead_createdby' => $tokendata['uid'],
                    // 'lead_from' =>  "P",
                    'lead_createdon' => date("Y-m-d H:i:s"),
                    'lead_creted_date' => date("Y-m-d H:i:s"),
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                ];
                // $data = [
                //     'lead_code' => $code,
                //     'name' => $this->request->getVar('name'),
                //     'phone' => $this->request->getVar('phone'),
                //     'vehicle_model' => $this->request->getVar('vehicle_model'),
                //     'lead_note' => $this->request->getVar('lead_note'),
                //     'status_id' => $this->request->getVar('status_id'),
                //     'lang_id' => $this->request->getVar('lang_id'),
                //     'purpose_id' => $this->request->getVar('purpose_id'),
                //     'cus_id' =>  $cust_id,
                //     'lead_createdby' => $tokendata['uid'],
                //     'ld_brand' => $this->request->getVar('ld_brand'),
                //     'source_id' => $this->request->getVar('source_id'),
                //     'ld_appoint_date' => $this->request->getVar('dateField'),
                //     'ld_appoint_time' => $this->request->getVar('appTime'),
                //     'ld_camp_id' => $this->request->getVar('camp'),
                //     'reason_to_close' => $this->request->getVar('other_reason'),
                //     'assigned' => $this->request->getVar('forward_to'),
                //     'rating' => $this->request->getVar('rating')
    
                // ];
                $resC = $cust_mastr_model->where('cust_phone', $phone)->first();
                if ($resC) {
                    $cust_id = $resC['cus_id'];
                    $custId = [
                        'cus_id' =>  $cust_id,
                        'name' => $resC['cust_name'],
                        'phone' => $resC['cust_phone'],
                    ];
                    $data = array_merge($data, $custId);
                    // $lead_id = $model->insert($data);
                    // if ($res <= 0) {
    
                    //     $response = [
                    //         'errors' => $model->errors(),
                    //         'ret_data' => 'fail'
                    //     ];
    
                    //     return $this->fail($response, 409);
                    // } else {
                    //     $acdata = [
                    //         'lac_activity' => 'Created Lead ' . $code,
                    //         'lac_activity_by' => $tokendata['uid'],
                    //         'lac_lead_id' => $res,
                    //     ];
                    //     $acmodel->insert($acdata);
                    //     $builder = $this->db->table('sequence_data');
                    //     $builder->set('current_seq', ++$seqvalfinal);
                    //     $builder->update();
                    //     // $this->insertUserLog('New Lead Created '.$code,$tokendata['uid']);
                    //     return $this->respond(['ret_data' => 'success', 'insert_id' => $res], 201);
                    // }
                } else {
                    $maraghi_data = $maraghi_cust_model->where('phone', $phone)->join('customer_type', 'customer_type.cst_code = customer_type')->join('country_master', 'country_master.country_code = country')->select('customer_code,cst_id,customer_title,customer_name,addr1,city,country_master.id,phone')->first();
                    if ($maraghi_data) {
                        $custData = [
                            'cust_type' => $maraghi_data['cst_id'],
                            'cust_name' => $maraghi_data['customer_name'],
                            'cust_salutation' => $maraghi_data['customer_title'],
                            'cust_address' => $maraghi_data['addr1'],
                            'cust_emirates' => $maraghi_data['city'],
                            'cust_city' => $maraghi_data['city'],
                            'cust_country' => $maraghi_data['id'],
                            'cust_phone' =>  $maraghi_data['phone'],
                            'cust_alternate_no' => $maraghi_data['phone'],
                            'cust_alm_code' => $maraghi_data['customer_code'],
                            'lead_createdby' => $tokendata['uid'],
                            'cust_created_on' => date("Y-m-d H:i:s"),
                            'cust_source' => 0
    
                        ];
                        $ins_id = $cust_mastr_model->insert($custData);
                        $custId = [
                            'cus_id' =>  $ins_id,
                            'name' => $maraghi_data['customer_name'],
                            'phone' => $maraghi_data['phone'],
                        ];
                        $data = array_merge($data, $custId);
                        // $lead_id = $model->insert($data);
                        // if ($res <= 0) {
    
                        //     $response = [
                        //         'errors' => $model->errors(),
                        //         'ret_data' => 'fail'
                        //     ];
    
                        //     return $this->fail($response, 409);
                        // } 
                        // else {
                        //     $acdata = [
                        //         'lac_activity' => 'Created New Lead ' . $code,
                        //         'lac_activity_by' => $tokendata['uid'],
                        //         'lac_lead_id' => $res,
                        //     ];
                        //     $acmodel->insert($acdata);
                        //     $builder = $this->db->table('sequence_data');
                        //     $builder->set('current_seq', ++$seqvalfinal);
                        //     $builder->update();
                        //     // $this->insertUserLog('New Lead Created '.$code,$tokendata['uid']);
                        //     return $this->respond(['ret_data' => 'success', 'insert_id' => $res], 201);
                        // }
                    } else {
                        $custData = [
                            'cust_name' => $this->request->getVar('customerName'),
                            'cust_phone' => $this->request->getVar('customerNumber'),
                            'cust_alternate_no' => $this->request->getVar('customerNumber'),
                            'cust_source' => $this->request->getVar('source_id')
                        ];
                        $ins_id = $cust_mastr_model->insert($custData);
                        $custId = [
                            'cus_id' =>  $ins_id,
                            'name' => $this->request->getVar('customerName'),
                            'phone' => $this->request->getVar('customerNumber'),
                        ];
                        $data = array_merge($data, $custId);
                   
                    }
                }
    
                if ($purpose_id == "1") {
                    $temp_data = [
                        'status_id' => 1,
                        'ld_appoint_date' => $this->request->getVar('appointment_date'),
                        'ld_appoint_time' => $this->request->getVar('appointment_time'),
                        'assigned' => $assigned,
                    ];
                    $data = array_merge($data, $temp_data);
                    $lead_id = $model->insert($data);
                    $builder = $this->db->table('sequence_data');
                    $builder->selectMax('appt_seq');
                    $query = $builder->get();
                    $row = $query->getRow();
                    $code = $row->appt_seq;
                    $seqvalfinal = $row->appt_seq;
                    if (strlen($row->appt_seq) == 1) {
                        $code = "ALMAP-000" . $row->appt_seq;
                    } else if (strlen($row->appt_seq) == 2) {
                        $code = "ALMAP-00" . $row->appt_seq;
                    } else if (strlen($row->appt_seq) == 3) {
                        $code = "ALMAP-0" . $row->appt_seq;
                    } else {
                        $code = "ALMAP-" . $row->appt_seq;
                    }
                    $apptMdata = [
                        //'apptm_customer_code'=>   ,
                        'apptm_code' => $code,
                        'apptm_lead_id' => $lead_id,
                        'apptm_status' => '1', //Appointment Scheduled
                        'apptm_transport_service' =>  $this->request->getVar('pick_drop'),
                        'apptm_created_by' =>  $tokendata['uid'],
                        'apptm_updated_by' =>  $tokendata['uid'],
                        'apptm_type' => 4,
                        'apptm_group' => $this->request->getVar('apptm_group'),
                        'apptm_created_on' => date("Y-m-d H:i:s"),
                        'apptm_updated_on' => date("Y-m-d H:i:s"),
                    ];
                    $result = $ApptMaster->insert($apptMdata);
                    if ($result) {
                        $builder = $this->db->table('sequence_data');
                        $builder->set('appt_seq', ++$seqvalfinal);
                        $builder->update();
                        $Apptdata = [
                            'appt_apptm_id' => $result,
                            'appt_date' => $this->request->getVar('appointment_date'),
                            'appt_time' => date('Y-m-d', strtotime($this->request->getVar('appointment_date'))),
                            'appt_assign_to' =>  $assigned,
                            'appt_note' => $this->request->getVar('call_note'),
                            'appt_created_by' => $tokendata['uid'],
                            'appt_created_on' => date("Y-m-d H:i:s"),
                        ];
                        $result1 = $Appoint->insert($Apptdata);
                        $Logdata = [
                            'applg_apptm_id' => $result,
                            'applg_note' => "Appointment Scheduled",
                            'applg_created_by' => $tokendata['uid'],
                            'applg_created_on' => date("Y-m-d H:i:s"),
                            'applg_time' => date("Y-m-d H:i:s"),
                        ];
                        $logentry = $Appointmentlog->insert($Logdata);
                    }
                } else if ($purpose_id == "2") {
                    $temp_data = [
                        'status_id' => 1,
                        'ld_camp_id' => $this->request->getVar('campaign_data'),
                        'assigned' => $assigned,
                    ];
                    $data = array_merge($data, $temp_data);
                    $lead_id = $model->insert($data);
                } else if ($purpose_id == "3") {
                    $temp_data = [
                        'status_id' => 1,
                        'assigned' => $assigned,
                    ];
                    $data = array_merge($data, $temp_data);
                    $lead_id = $model->insert($data);
                    $quotedata = [
                        'lql_lead_id' => $lead_id,
                        'lql_type' => 0,
                        'lql_source' => 2, //from Direct Lead
                        'lql_note' =>  $this->request->getVar('call_note'),
                        'lql_created_by' => $tokendata['uid'],
                        'lql_activity' => "Quotation Requested By Customer",
                        'lql_created_on' => date("Y-m-d H:i:s"),
                    ];
                    $Quoteentry = $Quotemodel->insert($quotedata);
                } else if ($purpose_id == "7" || $purpose_id == "9" || $purpose_id == "10") {
                    $temp_data = [
                        'status_id' => 6,
                    ];
                    $data = array_merge($data, $temp_data);
                    $lead_id = $model->insert($data);
                } else if ($purpose_id == "4") {
                    $temp_data = [
                        'status_id' => $this->request->getVar('lead_status'),
                    ];
                    $data = array_merge($data, $temp_data);
                    $lead_id = $model->insert($data);
                } else if ($purpose_id == "6" || $purpose_id == "8") {
                    $temp_data = [
                        'status_id' => 6,
                    ];
                    $data = array_merge($data, $temp_data);
                    $lead_id = $model->insert($data);
                }
                //start
               $coll_log_data = new LeadCallLogModel();
               $logdata = [
                'lcl_lead_id' => $lead_id,
                'lcl_pupose_id' => $purpose_id,
                'lcl_purpose_note' => $lead_note,
                'lcl_call_to' =>  $tokendata['uid'],
                'lcl_phone' => $phone ,
                'lcl_call_type' => $this->request->getVar('call_type'),
                'lcl_created_on' =>  date("Y-m-d H:i:s"),
                'lcl_createdby' =>$tokendata['uid'],
                //   'lcl_call_time'=>$this->request->getVar('call_time'),
                'ystar_call_id' =>  $this->request->getVar('call_timestamp'),
               'lcl_call_source' => 3
            ];
            $coll_log_data->insert($logdata);


            //end 
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->db->transCommit();
                $leadactivitydata = [
                    'lac_activity' => 'Created Lead ' . $lead_code,
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $lead_id,
                ];
                $leadactivity = $leadAcModel->insert($leadactivitydata);

                $builder = $this->db->table('sequence_data');
                $builder->set('current_seq', ++$leadSeqvalfinal);
                $builder->update();
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            }
           
            }
            

            
           
        }
    }

public function checkleadstatus(){
    $model = new LeadModel();
    $common = new Common();
    $valid = new Validation();
    $heddata = $this->request->headers();
     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
     $phone = $this->request->getVar('customerNumber');
     $lead_status_data = $model->where('phone',$phone)->where('status_id','!=','1')->select(['lead_id','name','purpose_id','status_id'])->orderBy('lead_id', 'desc')->first();
     $data['data'] = [];
     if($lead_status_data){
        $data['data'] = $lead_status_data;
     }
     $data['ret_data'] = "success";
     return $this->respond($data, 200);
}

}
