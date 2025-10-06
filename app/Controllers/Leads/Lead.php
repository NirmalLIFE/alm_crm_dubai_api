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
use App\Models\UserActivityLog;
use App\Models\Leads\LeadCallLogModel;
use App\Models\User\UserNotificationModel;
use App\Models\Leads\CallPurposeModel;
use App\Models\Leads\AppointmentMasterModel;
use App\Models\Leads\AppointmentModel;
use App\Models\Leads\AppointmentLogModel;
use App\Models\Leads\LeadQuoteLogModel;
use App\Models\Dissatisfied\DissatisfiedMasterModel;
use App\Models\Dissatisfied\DissatisfiedLogModel;
use App\Models\Customer\MaraghiJobcardModel;
use App\Models\Quotes\QuotesMasterModel;
use Twilio\TwiML\Voice\Start;
use DateTime;

class Lead extends ResourceController
{

    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    /**
     * @api {get} leads/lead  Leads list
     * @apiName LLeads list
     * @apiGroup Super Admin
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   lead  Object containing leads list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */
    public function index()
    {
        $model = new LeadModel();
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


            $builder = $this->db->table('leads');
            $builder->select('lead_id,lead_code,name, phone,vehicle_model,lead_note,source_id,DATE(lead_createdon) as lead_createdon ,status_id,lead_source.ld_src,lead_status.ld_sts,users.us_firstname,ld_appoint_date,assigned,camp_name,ld_camp_id,ld_brand,us.us_firstname as created,call_purpose,purpose_id,ld_appoint_time,apptm_id');
            $builder->join('users', 'users.us_id =assigned', 'left');
            $builder->join('users as us', 'us.us_id =lead_createdby', 'left');
            $builder->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left');
            $builder->join('lead_source', 'lead_source.ld_src_id =source_id', 'left');
            $builder->join('call_purposes', 'call_purposes.cp_id =purpose_id', 'left');
            $builder->join('campaign', 'campaign.camp_id =ld_camp_id', 'left');
            $builder->join('appointment_master', 'appointment_master.apptm_lead_id =lead_id', 'left');
            $builder->where('lead_delete_flag', 0);
            $builder->where('status_id !=', 7);
            $builder->orderby('lead_id', 'desc');
            $builder->limit(2000);
            $query = $builder->get();
            $res = $query->getResultArray();

            //  $res= $model->where('lead_delete_flag', 0)->where('status_id !=', 7)->join('users','users.us_id =assigned','left')->join('lead_source','lead_source.ld_src_id =source_id','left')->join('lead_status','lead_status.ld_sts_id =status_id','left')->join('call_purposes','call_purposes.cp_id =purpose_id','left')->orderby('lead_id','desc')->select('lead_id,lead_code,name,CONCAT("*****",RIGHT(phone,4)) as phone,vehicle_model,lead_note,source_id,DATE(lead_createdon) as lead_createdon,status_id,purpose_id,lead_source.ld_src,lead_status.ld_sts,users.us_firstname,call_purpose')->findAll();


            if ($res) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'lead' => $res
                ];
                return $this->respond($response, 200);
            }
        }
    }

    /**
     * @api {get} leads/lead/:id  Leads by  id
     * @apiName Leads by  id
     * @apiGroup Leads
     * @apiPermission  Super Admin, User
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}   lead object with lead source details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
    {
        $model = new LeadModel();
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
            //  $res= $model->where('lead_id', $id)->join('call_purposes','call_purposes.cp_id =purpose_id','left')->join('prefer_language','prefer_language.pl_id =lang_id','left')->join('lead_source','lead_source.ld_src_id =source_id','left')->join('lead_status','lead_status.ld_sts_id =status_id','left')->select('leads.*,lead_source.ld_src,lead_status.ld_sts,prefer_language.prefer_lang,call_purposes.call_purpose')->first();
            $res = $model->where('lead_id', $id)
                ->join('call_purposes', 'call_purposes.cp_id =purpose_id', 'left')
                ->join('social_media_campaign_source', 'social_media_campaign_source.smcs_id =lead_social_media_source', 'left')
                ->join('social_media_campaign', 'social_media_campaign.smc_id =lead_social_media_mapping', 'left')
                ->first();
            $lead_log =
                //  $this->insertUserLog('Lead data For Update',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'lead' => $res
                ];
            return $this->respond($response, 200);
        }
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
     * @api {post} leads/lead Lead create
     * @apiName Lead create
     * @apiGroup super admin
     * @apiPermission super admin,User
     *
     *@apiBody {String} name Name
     *@apiBody {String} phone Phone
     *@apiBody {String} vehicle_model Vehicle Model
     *@apiBody {String} lead_note Lead Note
     *@apiBody {String} status_id Lead Status ID
     *@apiBody {String} lang_id Preferred Language ID
     *@apiBody {String} purpose_id Call Purpose ID
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
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
            $social_Media_Source = $this->request->getVar('social_media_source') ? $this->request->getVar('social_media_source') : '0';
            $smc_id = $this->request->getVar('social_media_camp') ? $this->request->getVar('social_media_camp') : '0';
            $cust_id = 0;
            $this->db->transStart();
            $data = [
                //'name' => $this->request->getVar('customerName'),
                //  'phone' => $this->request->getVar('phone'),
                'lead_code' => $lead_code,
                'lead_note' => $this->request->getVar('call_note'),
                'lang_id' => 1,
                'purpose_id' => $purpose_id,
                'register_number' => $this->request->getVar('reg_no'),
                'vehicle_model' => $this->request->getVar('vehicle_model'),
                'source_id' => $this->request->getVar('source_id'),
                'lead_social_media_source' => $social_Media_Source,
                'lead_social_media_mapping' => $smc_id,
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
                    // $lead_id = $model->insert($data);
                    // if ($res <= 0) {

                    //     $response = [
                    //         'errors' => $model->errors(),
                    //         'ret_data' => 'fail'
                    //     ];

                    //     return $this->fail($response, 409);
                    // } else {
                    //     $acdata = [
                    //         'lac_activity' => 'Created  Lead ' . $code,
                    //         'lac_activity_by' => $tokendata['uid'],
                    //         'lac_lead_id' => $res,
                    //     ];
                    //     $acmodel->insert($acdata);
                    //     $builder = $this->db->table('sequence_data');
                    //     $builder->set('current_seq', ++$seqvalfinal);
                    //     $builder->update();

                    //     //$this->insertUserLog('New Lead Created '.$code,$tokendata['uid']);

                    //     return $this->respond(['ret_data' => 'success', 'insert_id' => $res], 201);
                    // }
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
                        'appt_time' => $this->request->getVar('appointment_time'),
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
            // if ($lead_id) {
            //     $leadactivitydata = [
            //         'lac_activity' => 'Created Lead ' . $lead_code,
            //         'lac_activity_by' => $tokendata['uid'],
            //         'lac_lead_id' => $lead_id,
            //     ];
            //     $leadactivity = $leadAcModel->insert($leadactivitydata);

            //     $builder = $this->db->table('sequence_data');
            //     $builder->set('current_seq', ++$leadSeqvalfinal);
            //     $builder->update();
            //     return $this->respond(['ret_data' => 'success', 'data' => $data], 201);
            // } else {
            //     $response = [
            //         'errors' => $model->errors(),
            //         'ret_data' => 'fail'
            //     ];
            //     return $this->fail($response, 409);
            // }
            // if ($res <= 0) {

            //     $response = [
            //         'errors' => $model->errors(),
            //         'ret_data' => 'fail'
            //     ];

            //     return $this->fail($response, 409);
            // } else {

            //     $acdata = [
            //         'lac_activity' => 'Created  Lead',
            //         'lac_activity_by' => $tokendata['uid'],
            //         'lac_lead_id' => 0,
            //     ];
            //     $acmodel->insert($acdata);
            //     //     $this->insertUserLog('New Lead Created '.$code,$tokendata['uid']);
            //     return $this->respond(['ret_data' => 'success', 'insert_id' => $res], 201);
            // }
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
     * @api {post} leads/lead/update Lead Update
     * @apiName Lead Update
     * @apiGroup super admin
     * @apiPermission super admin,User
     *
     *@apiBody {String} name Name
     *@apiBody {String} phone Phone
     *@apiBody {String} vehicle_model Vehicle Model
     *@apiBody {String} lead_note Lead Note
     *@apiBody {String} status_id Lead Status ID
     *@apiBody {String} lang_id Preferred Language ID
     *@apiBody {String} purpose_id Call Purpose ID
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function update($id = null)
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();

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
                'name' => 'required',
                'phone' => 'required',

            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'name' => $this->request->getVar('name'),
                'phone' => $this->request->getVar('phone'),
                'vehicle_model' => $this->request->getVar('vehicle_model'),
                'lead_note' => $this->request->getVar('lead_note'),
                'status_id' => $this->request->getVar('status_id'),
                'lang_id' => $this->request->getVar('lang_id'),
                'purpose_id' => $this->request->getVar('purpose_id'),
                'email' => $this->request->getVar('email'),
                'register_number' => $this->request->getVar('register_number'),
                'source_id' => $this->request->getVar('source_id'),
                'sourceid' => $this->request->getVar('sourceid'),
                'assigned' => $this->request->getVar('assigned'),
                'assign' => $this->request->getVar('assign'),
                'address' => $this->request->getVar('address'),
                'lead_updatedby' => $tokendata['uid'],
                'lead_updatedon' => date("Y-m-d H:i:s"),


            ];
            $id = $this->db->escapeString($this->request->getVar('id'));
            if ($model->where('lead_id', $id)->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            } else {
                $in_data = array();
                if ($data['lang_id'] != $this->request->getVar('langid')) {

                    $infdata = [
                        'lac_activity' => 'Changed Preferred Language',
                        'lac_activity_by' => $tokendata['uid'],
                        'lac_lead_id' => $id,
                    ];
                    array_push($in_data, $infdata);
                }
                if ($data['status_id'] != $this->request->getVar('statusid')) {
                    $builder = $this->db->table('lead_status');
                    $builder->select('ld_sts');
                    $builder->where('ld_sts_id', $data['status_id']);
                    $query = $builder->get();
                    $row = $query->getRow();
                    $new = $row->ld_sts;

                    $builder = $this->db->table('lead_status');
                    $builder->select('ld_sts');
                    $builder->where('ld_sts_id', $this->request->getVar('statusid'));
                    $query = $builder->get();
                    $row = $query->getRow();
                    $old = $row->ld_sts;

                    $infdata = [
                        'lac_activity' => 'Changed Lead Status ' . $old . ' to ' . $new,
                        'lac_activity_by' => $tokendata['uid'],
                        'lac_lead_id' => $id,
                    ];
                    array_push($in_data, $infdata);
                }
                if ($data['purpose_id'] != $this->request->getVar('purposeid')) {

                    $builder = $this->db->table('call_purposes');
                    $builder->select('call_purpose');
                    $builder->where('cp_id', $data['status_id']);
                    $query = $builder->get();
                    $row = $query->getRow();
                    $new = $row->call_purpose;

                    $builder = $this->db->table('call_purposes');
                    $builder->select('call_purpose');
                    $builder->where('cp_id', $this->request->getVar('statusid'));
                    $query = $builder->get();
                    $row = $query->getRow();
                    $old = $row->call_purpose;


                    $infdata = [
                        'lac_activity' => 'Changed Call Purpose ' . $old . ' to ' . $new,
                        'lac_activity_by' => $tokendata['uid'],
                        'lac_lead_id' => $id,
                    ];
                    array_push($in_data, $infdata);
                }
                if ($data['source_id'] != $this->request->getVar('sourceid')) {
                    $old = '';
                    $new = '';
                    $builder = $this->db->table('lead_source');
                    $builder->select('ld_src');
                    $builder->where('ld_src_id', $data['source_id']);
                    $query = $builder->get();
                    $row = $query->getRow();
                    $new = $row->ld_src;
                    if ($this->request->getVar('sourceid') != 0) {
                        $builder = $this->db->table('lead_source');
                        $builder->select('ld_src');
                        $builder->where('ld_src_id', $this->request->getVar('sourceid'));
                        $query = $builder->get();
                        $row = $query->getRow();
                        $old = $row->ld_src;
                    }


                    $infdata = [
                        'lac_activity' => 'Changed Lead Source ' . $old . ' to ' . $new,
                        'lac_activity_by' => $tokendata['uid'],
                        'lac_lead_id' => $id,
                    ];
                    array_push($in_data, $infdata);
                }
                if ($data['assign'] != $this->request->getVar('assigned')) {

                    $builder = $this->db->table('users');
                    $builder->select('us_firstname');
                    $builder->where('us_id', $this->request->getVar('assigned'));
                    $query = $builder->get();
                    $row = $query->getRow();
                    $new = $row->us_firstname;

                    $infdata = [
                        'lac_activity' => 'Lead Assigned to ' . $new,
                        'lac_activity_by' => $tokendata['uid'],
                        'lac_lead_id' => $id,
                    ];
                    array_push($in_data, $infdata);
                    $data = array('un_title' => 'New Lead', 'un_note' => 'New Lead  Assigned to you', 'un_to' => $this->request->getVar('assigned'), 'un_from' => $tokendata['uid'], 'un_link' => 'pages/user/leads/lead-management/', 'un_link_id' => $id);
                    $this->insertUserNoti($data);
                }
                if (!empty($in_data)) {
                    $acmodel->insertBatch($in_data);
                }
            }
            //   $this->insertUserLog('Lead Uodated',$tokendata['uid']);
            return $this->respond(['ret_data' => 'success'], 201);
        }
    }


    /**
     * @api {post} leads/leads/delete Lead Delete
     * @apiName Lead Delete
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  lead id of the lead to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();


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
            $data = [
                'lead_delete_flag' => 1,
            ];
            $id = $this->db->escapeString($this->request->getVar('id'));

            if ($model->where('lead_id', $id)->set($data)->update() === false) {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            } else {
                $acdata = [
                    'lac_activity' => 'Deleted Lead',
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $id,
                ];

                $acmodel->insert($acdata);
                //   $this->insertUserLog('Lead Deleted',$tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 201);
            }
        }
    }
    /**
     * @api {post} leads/lead/update Lead Update
     * @apiName Lead Update
     * @apiGroup Leads
     * @apiPermission super admin,User
     *
     *@apiBody {String} name Name
     *@apiBody {String} phone Phone
     *@apiBody {String} vehicle_model Vehicle Model
     *@apiBody {String} lead_note Lead Note
     *@apiBody {String} status_id Lead Status ID
     *@apiBody {String} lang_id Preferred Language ID
     *@apiBody {String} purpose_id Call Purpose ID
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function leadupdate($id = null)
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();

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
                'name' => 'required',
                'phone' => 'required',

            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'name' => $this->request->getVar('name'),
                'phone' => $this->request->getVar('phone'),
                'vehicle_model' => $this->request->getVar('vehicle_model'),
                'lead_note' => $this->request->getVar('lead_note'),
                'status_id' => $this->request->getVar('status_id'),
                'lang_id' => $this->request->getVar('lang_id'),
                'purpose_id' => $this->request->getVar('purpose_id'),
                'email' => $this->request->getVar('email'),
                'register_number' => $this->request->getVar('register_number'),
                'source_id' => $this->request->getVar('source_id'),
                'assigned' => $this->request->getVar('assigned'),
                'address' => $this->request->getVar('address'),
                'lead_updatedby' => $tokendata['uid'],
                'lead_updatedon' => date("Y-m-d H:i:s"),

            ];
            $id = $this->db->escapeString($this->request->getVar('id'));
            if ($model->where('lead_id', $id)->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            } else {
                $acdata = [
                    'lac_activity' => 'Updated Lead',
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $id,

                ];

                $acmodel->insert($acdata);
                $data = array('un_title' => 'New Lead', 'un_note' => 'New Lead  Assigned to you', 'un_to' => $this->request->getVar('assigned'), 'un_from' => $tokendata['uid'], 'un_link' => 'pages/user/leads/lead-management/', 'un_link_id' => $id);
                $this->insertUserNoti($data);
                // $this->insertUserLog('Lead Updated',$tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 201);
            }
        }
    }




    // public function popupleadupdate($id = null)
    // {
    //     $model = new LeadModel();
    //     $common = new Common();
    //     $valid = new Validation();
    //     $acmodel = new LeadActivityModel();
    //     $log = new LeadCallLogModel();

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
    //         $rules = [

    //             'phone' => 'required',

    //         ];
    //         if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

    //         if ($this->request->getVar('lead_note') == '' || $this->request->getVar('vehicle_model') != '') {
    //             $barnd =  $this->request->getVar('ld_brand') . "-" . $this->request->getVar('vehicle_model');
    //         } else {
    //             $barnd = $this->request->getVar('ld_brand');
    //         }


    //         $data = [
    //             'name' => $this->request->getVar('name'),
    //             'phone' => $this->request->getVar('phone'),
    //             'lead_note' => $this->request->getVar('lead_note'),
    //             'status_id' => $this->request->getVar('status_id'),
    //             'lang_id' => $this->request->getVar('lang_id'),
    //             'purpose_id' => $this->request->getVar('purpose_idd'),
    //             'email' => $this->request->getVar('email'),
    //             'register_number' => $this->request->getVar('register_number'),
    //             'source_id' => $this->request->getVar('source_id'),
    //             'assigned' => $this->request->getVar('assigned'),
    //             'address' => $this->request->getVar('address'),
    //             'lead_updatedby' => $tokendata['uid'],
    //             'ld_brand' =>  $this->request->getVar('ld_brand'),
    //             'lead_from' =>  $this->request->getVar('leadFrom'),
    //             'lead_creted_date' => $this->request->getVar('createdDate'),
    //             'close_time' => $this->request->getVar('createdDate'),
    //             'ld_appoint_date' => $this->request->getVar('dateField'),
    //             'ld_appoint_time' => $this->request->getVar('appTime'),
    //             'ld_camp_id' => $this->request->getVar('camp'),
    //             'reason_to_close' => $this->request->getVar('other_reason'),
    //             'assigned' => $this->request->getVar('forward_to'),
    //             'rating' => $this->request->getVar('rating')
    //         ];
    //         $id = $this->request->getVar('id');
    //         if ($this->request->getVar('status_id') == '6') {
    //             $data1 = ['reason_to_close' => $this->request->getVar('lead_note')];
    //             $data = array_merge($data, $data1);
    //         }
    //         if ($model->where('lead_id', $id)->set($data)->update() === false) {

    //             $response = [
    //                 'errors' => $model->errors(),
    //                 'ret_data' => 'fail'
    //             ];

    //             return $this->fail($response, 409);
    //         } else {

    //             $logdata = [
    //                 'lcl_lead_id' => $id,
    //                 'lcl_pupose_id' => $this->request->getVar('purpose_idd'),
    //                 'lcl_purpose_note' => $this->request->getVar('lead_note'),
    //                 'lcl_call_to' => $this->request->getVar('call_to'),
    //                 'lcl_phone' => $this->request->getVar('phone'),
    //                 'lcl_call_type' => $this->request->getVar('call_type'),
    //                 //  'lcl_call_time'=>$this->request->getVar('call_time'),
    //                 'ystar_call_id' => $this->db->escapeString($this->request->getVar('call_id')),
    //                 // 'lcl_time'=>$this->request->getVar('today')
    //             ];
    //             $log->insert($logdata);
    //             $acdata = [
    //                 'lac_activity' => 'Updated Lead',
    //                 'lac_activity_by' => $tokendata['uid'],
    //                 'lac_lead_id' => $id,

    //             ];

    //             $acmodel->insert($acdata);
    //             // $this->insertUserLog('Lead Updated',$tokendata['uid']);
    //             return $this->respond(['ret_data' => 'success', 'data' => $this->request->getVar('leadFrom')], 201);
    //         }
    //     }
    // }

    public function popupleadupdate($id = null)
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();
        $log = new LeadCallLogModel();
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
                'lead_id' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

            $purpose_id = $this->request->getVar('call_purpose');
            $assigned = $this->request->getVar('assigned_to');
            $lead_id = $this->request->getVar('lead_id');

            // return $this->respond( $purpose_id, 201);

            $data = [
                'name' => $this->request->getVar('customerName'),
                // 'lead_note' => $this->request->getVar('call_note'),
                'lang_id' => 1,
                'purpose_id' => $purpose_id,
                'register_number' => $this->request->getVar('reg_no'),
                'vehicle_model' => $this->request->getVar('vehicle_model'),
                'source_id' => 1,
                'lead_updatedby' => $tokendata['uid'],
                'lead_from' =>  "P",
                'lead_updatedon' => date("Y-m-d H:i:s"),
                'lead_category' => $this->request->getVar('lead_category'),

            ];

            $appoint_id = $ApptMaster->where('apptm_lead_id', $lead_id)
                ->whereIn('apptm_status', [1, 2, 3])
                ->select('apptm_id')
                ->first();

            if ($appoint_id) {
                $Logdata = [
                    'applg_apptm_id' => $appoint_id,
                    'applg_note' => "Customer Called For " . $this->request->getVar('call_note'),
                    'applg_created_by' => $tokendata['uid'],
                    'applg_created_on' => date("Y-m-d H:i:s"),
                    'applg_time' => date("Y-m-d H:i:s"),
                ];
                $logentry = $Appointmentlog->insert($Logdata);
            }
            if ($purpose_id == "1") {
                $temp_data = [
                    'status_id' => 1,
                    'lead_createdon' => date("Y-m-d H:i:s"),
                    'lead_creted_date' => date("Y-m-d H:i:s"),
                    'lead_note' => $this->request->getVar('call_note'),
                    'ld_appoint_date' => $this->request->getVar('appointment_date'),
                    'ld_appoint_time' => $this->request->getVar('appointment_time'),
                    'assigned' => $assigned,
                ];
                $data = array_merge($data, $temp_data);
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
                    'apptm_lead_id' => $this->request->getVar('lead_id'),
                    'apptm_status' => '1', //Appointment Scheduled
                    'apptm_transport_service' => $this->request->getVar('pick_drop') ?  $this->request->getVar('pick_drop') : 0,
                    'apptm_created_by' =>  $tokendata['uid'],
                    'apptm_updated_by' =>  $tokendata['uid'],
                    'apptm_type' => 1,
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
                        'appt_time' => $this->request->getVar('appointment_time'),
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
                // $acdata = [
                //     'lac_activity' => 'Created Lead' . $this->request->getVar('lead_code'),
                //     'lac_activity_by' => $tokendata['uid'],
                //     'lac_lead_id' => $this->request->getVar('lead_id'),
                // ];
                // $acmodel->insert($acdata);
            } else if ($purpose_id == "2") {
                $temp_data = [
                    'status_id' => 1,
                    'lead_createdon' => date("Y-m-d H:i:s"),
                    'lead_creted_date' => date("Y-m-d H:i:s"),
                    'lead_note' => $this->request->getVar('call_note'),
                    'ld_camp_id' => $this->request->getVar('campaign_data'),
                    'assigned' => $assigned,
                ];
                $data = array_merge($data, $temp_data);
                // $acdata = [
                //     'lac_activity' => 'Created Lead ' . $this->request->getVar('lead_code'),
                //     'lac_activity_by' => $tokendata['uid'],
                //     'lac_lead_id' => $this->request->getVar('lead_id'),
                // ];
                // $acmodel->insert($acdata);
            } else if ($purpose_id == "3") {
                $temp_data = [
                    'status_id' => 1,
                    'lead_createdon' => date("Y-m-d H:i:s"),
                    'lead_creted_date' => date("Y-m-d H:i:s"),
                    'lead_note' => $this->request->getVar('call_note'),
                    'assigned' => $assigned,
                ];
                $data = array_merge($data, $temp_data);
                $quotedata = [
                    'lql_lead_id' => $this->request->getVar('lead_id'),
                    'lql_type' => 0,
                    'lql_source' => 1,
                    'lql_note' =>  $this->request->getVar('call_note'),
                    'lql_created_by' => $tokendata['uid'],
                    'lql_activity' => "Quotation Requested By Customer",
                    'lql_created_on' => date("Y-m-d H:i:s"),
                ];
                $Quoteentry = $Quotemodel->insert($quotedata);
                // $acdata = [
                //     'lac_activity' => 'Created Lead ' . $this->request->getVar('lead_code'),
                //     'lac_activity_by' => $tokendata['uid'],
                //     'lac_lead_id' => $this->request->getVar('lead_id'),
                // ];
                // $acmodel->insert($acdata);
            } else if ($purpose_id == "4") {
                $temp_data = [
                    'lead_createdon' => date("Y-m-d H:i:s"),
                    'lead_creted_date' => date("Y-m-d H:i:s"),
                    'lead_note' => $this->request->getVar('call_note'),
                    'status_id' => $this->request->getVar('lead_status'),
                ];
                $data = array_merge($data, $temp_data);
                // $acdata = [
                //     'lac_activity' => 'Created Lead ' . $this->request->getVar('lead_code'),
                //     'lac_activity_by' => $tokendata['uid'],
                //     'lac_lead_id' => $this->request->getVar('lead_id'),
                // ];
                // $acmodel->insert($acdata);
            } else if ($purpose_id == "7" || $purpose_id == "9" || $purpose_id == "10") {
                $temp_data = [
                    'status_id' => 6,
                    'lead_creted_date' => date("Y-m-d H:i:s"),
                    'lead_note' => $this->request->getVar('call_note'),
                    'lead_createdon' => date("Y-m-d H:i:s"),
                ];
                $data = array_merge($data, $temp_data);
                // $acdata = [
                //     'lac_activity' => 'Created Lead and Closed due to ' . $this->request->getVar('lead_code'),
                //     'lac_activity_by' => $tokendata['uid'],
                //     'lac_lead_id' => $this->request->getVar('lead_id'),
                // ];
                // $acmodel->insert($acdata);
            } else if ($purpose_id == "6" || $purpose_id == "8") {
                $temp_data = [
                    'status_id' => 6,
                    'lead_creted_date' => date("Y-m-d H:i:s"),
                    'lead_note' => $this->request->getVar('call_note'),
                    'lead_createdon' => date("Y-m-d H:i:s"),
                ];
                $data = array_merge($data, $temp_data);
                // $acdata = [
                //     'lac_activity' => 'Created Lead and Closed due to ' . $this->request->getVar('lead_code'),
                //     'lac_activity_by' => $tokendata['uid'],
                //     'lac_lead_id' => $this->request->getVar('lead_id'),
                // ];
                // $acmodel->insert($acdata);
            }
            if ($model->where('lead_id', $this->request->getVar('lead_id'))->set($data)->update() === false) {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->fail($response, 409);
            } else {
                $call_log_id = $this->request->getVar('call_log_id');
                $logdata = [
                    'lcl_lead_id' => $this->request->getVar('lead_id'),
                    'lcl_pupose_id' => $purpose_id,
                    'lcl_purpose_note' => $this->request->getVar('call_note'),
                    'lcl_time' => date("Y-m-d H:i:s"),
                    // 'lcl_time' => date("Y-m-d h:i"),
                    // 'lcl_call_type' => 0,
                    // 'lcl_created_on'=> date("Y-m-d H:i:s"),
                ];
                $temp = $log->where('lcl_id', $call_log_id)->set($logdata)->update();
                return $this->respond(['ret_data' => 'success', 'data' => $data, 'temp' => $temp], 201);
            }
        }
    }




    public function closeLead()
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();
        $cust_mastr_model = new CustomerMasterModel();
        $maraghi_cust_model = new MaragiCustomerModel();
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
                'name' => 'required',
                'mobile' => 'required',

            ];
            $leadid = $this->request->getVar('leadid');
            if ($leadid != 0) {
                $data = [
                    'reason_to_close' => $this->request->getVar('reason'),
                    'status_id' => 6,
                    'lead_updatedby' => $tokendata['uid'],
                    'lead_updatedon' => date("Y-m-d H:i:s"),

                ];
                $model->where('lead_id', $leadid)->set($data)->update();
                $acdata = [
                    'lac_activity' => 'Lead Closed due to ' . $this->request->getVar('reason'),
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $leadid,
                ];
                $acmodel->insert($acdata);
                $this->insertUserLog('Lead Closed', $tokendata['uid']);
                return $this->respond(['ret_data' => 'success', 'insert_id' => $leadid], 201);
            } else {
                $builder = $this->db->table('sequence_data');
                $builder->selectMax('current_seq');
                $query = $builder->get();
                $row = $query->getRow();
                $code = $row->current_seq;
                $seqvalfinal = $row->current_seq;
                if (strlen($row->current_seq) == 1) {
                    $code = "ALMLD-000" . $row->current_seq;
                } else if (strlen($row->current_seq) == 2) {
                    $code = "ALMLD-00" . $row->current_seq;
                } else if (strlen($row->current_seq) == 3) {
                    $code = "ALMLD-0" . $row->current_seq;
                } else {
                    $code = "ALMLD-" . $row->current_seq;
                }
                if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

                $phone = $this->request->getVar('mobile');
                $reason = $this->request->getVar('reason');

                $cust_id = 0;
                $resC = $cust_mastr_model->where('cust_phone', $phone)->first();
                $data = [
                    'lead_code' => $code,
                    'name' => $this->request->getVar('name'),
                    'phone' => $this->request->getVar('mobile'),
                    'vehicle_model' => $this->request->getVar('model'),
                    'reason_to_close' => $this->request->getVar('reason'),
                    'cus_id' =>  $cust_id,
                    'status_id' => 6,
                    'lead_createdby' => $tokendata['uid'],
                    'lead_createdon' => date("Y-m-d H:i:s"),
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                    'lead_creted_date' => date("Y-m-d H:i:s"),

                ];

                if ($resC) {
                    $cust_id = $resC['cus_id'];
                    $custId = ['cus_id' =>  $cust_id];
                    $data = array_merge($data, $custId);
                    $res = $model->insert($data);
                    if ($res <= 0) {

                        $response = [
                            'errors' => $model->errors(),
                            'ret_data' => 'fail'
                        ];

                        return $this->fail($response, 409);
                    } else {
                        $acdata = [
                            'lac_activity' => 'Created Lead and Closed due to ' . $reason,
                            'lac_activity_by' => $tokendata['uid'],
                            'lac_lead_id' => $res,
                        ];
                        $acmodel->insert($acdata);
                        $builder = $this->db->table('sequence_data');
                        $builder->set('current_seq', ++$seqvalfinal);
                        $builder->update();

                        $this->insertUserLog('Created Lead and Closed ' . $code, $tokendata['uid']);


                        return $this->respond(['ret_data' => 'success', 'insert_id' => $res], 201);
                    }
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
                            'cust_source' => 0
                            // 'lead_createdby' => $tokendata['uid'],

                        ];
                        $ins_id = $cust_mastr_model->insert($custData);
                        $custId = ['cus_id' =>  $ins_id];
                        $data = array_merge($data, $custId);
                        $res = $model->insert($data);
                        if ($res <= 0) {
                            $response = [
                                'errors' => $model->errors(),
                                'ret_data' => 'fail'
                            ];

                            return $this->fail($response, 409);
                        } else {
                            $acdata = [
                                'lac_activity' => 'Created Lead and Closed due to' . $reason,
                                'lac_activity_by' => $tokendata['uid'],
                                'lac_lead_id' => $res,
                            ];
                            $acmodel->insert($acdata);
                            $builder = $this->db->table('sequence_data');
                            $builder->set('current_seq', ++$seqvalfinal);
                            $builder->update();
                            $this->insertUserLog('Created Lead and Closed ' . $code, $tokendata['uid']);
                            return $this->respond(['ret_data' => 'success', 'insert_id' => $res], 201);
                        }
                    } else {

                        $res = $model->insert($data);
                        if ($res <= 0) {
                            $response = [
                                'errors' => $model->errors(),
                                'ret_data' => 'fail'
                            ];
                            return $this->fail($response, 409);
                        } else {
                            $acdata = [
                                'lac_activity' => 'Created  Lead and Cosed due to ' . $reason,
                                'lac_activity_by' => $tokendata['uid'],
                                'lac_lead_id' => $res,
                            ];
                            $acmodel->insert($acdata);
                            $builder = $this->db->table('sequence_data');
                            $builder->set('current_seq', ++$seqvalfinal);
                            $builder->update();

                            $this->insertUserLog('Created Lead and Closed ' . $code, $tokendata['uid']);

                            return $this->respond(['ret_data' => 'success', 'insert_id' => $res], 200);
                        }
                    }
                }
                $res = $model->insert($data);
                if ($res <= 0) {

                    $response = [
                        'errors' => $model->errors(),
                        'ret_data' => 'fail'
                    ];

                    return $this->fail($response, 409);
                } else {
                    $acdata = [
                        'lac_activity' => 'Created  Lead and Closed due to ' . $reason,
                        'lac_activity_by' => $tokendata['uid'],
                        'lac_lead_id' => $res,
                    ];
                    $acmodel->insert($acdata);
                    $this->insertUserLog('Created Lead and Closed ' . $code, $tokendata['uid']);
                    return $this->respond(['ret_data' => 'success', 'insert_id' => $res], 201);
                }
            }
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
    public function addLeadLog()
    {
        $log = new LeadCallLogModel();
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        $dataL = [

            'lead_note' => $this->request->getVar('lead_note'),
            'status_id' => $this->request->getVar('status_id'),
            'purpose_id' => $this->request->getVar('purpose_idd'),
            'source_id' => $this->request->getVar('source_id'),
            'assigned' => $this->request->getVar('assigned'),
            'lead_updatedby' => $tokendata['uid'],
            'ld_brand' =>  $this->request->getVar('ld_brand'),
            'lead_from' =>  $this->request->getVar('leadFrom'),
            'lead_creted_date' => $this->request->getVar('createdDate'),
            'close_time' => $this->request->getVar('createdDate'),
            'ld_appoint_date' => $this->request->getVar('dateField'),
            'ld_appoint_time' => $this->request->getVar('appTime'),
            'ld_camp_id' => $this->request->getVar('camp'),
            'reason_to_close' => $this->request->getVar('other_reason'),
            'assigned' => $this->request->getVar('forward_to'),
            'rating' => $this->request->getVar('rating')
        ];










        if ($this->request->getVar('vehicle_model') != '') {
            $note = "(Brand - " . $this->request->getVar('ld_brand') . " )" . $this->request->getVar('vehicle_model');
            //  $note = "Vehicle Brand - ". $this->request->getVar('ld_brand') ." , Model - ".$this->request->getVar('vehicle_model');
        } else {
            $note = $this->request->getVar('lead_note');
        }
        $logdata = [
            'lcl_lead_id' => $this->db->escapeString($this->request->getVar('id')),
            'lcl_pupose_id' => $this->db->escapeString($this->request->getVar('purpose_idd')),
            'lcl_purpose_note' => $note,
            'lcl_call_to' => $this->db->escapeString($this->request->getVar('call_to')),
            'lcl_phone' => $this->db->escapeString($this->request->getVar('phone')),
            'lcl_call_type' => $this->request->getVar('call_type'),
            //   'lcl_call_time'=>$this->request->getVar('call_time'),
            'ystar_call_id' => $this->db->escapeString($this->request->getVar('call_id')),
            //'lcl_time'=>$this->request->getVar('today')
        ];
        $log->insert($logdata);
        $model->where('lead_id', $this->request->getVar('id'))->set($dataL)->update();
        $response = [
            'ret_data' => 'success',
        ];

        return $this->respond($response, 200);
    }
    public function getLeadLog()
    {

        $custmastermodel = new CustomerMasterModel();
        $logmodel = new LeadCallLogModel();
        $leadAcModel = new LeadActivityModel();
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

            $leadid = $this->db->escapeString($this->request->getVar('id'));
            $lead_log = $logmodel->where('lcl_lead_id', $leadid)
                ->where('lcl_pupose_id !=', 0)
                ->join('users', 'users.ext_number =lcl_call_to', 'left')
                ->join('user_roles', 'users.us_role_id =user_roles.role_id ', 'left')
                ->join('call_purposes', 'call_purposes.cp_id =lcl_pupose_id', 'left')
                ->join('lead_activities', 'lead_activities.lac_lead_id = lcl_lead_id', 'left')
                ->select('lcl_time,lcl_pupose_id,lcl_call_to,us_firstname,role_name,call_purpose,lcl_purpose_note,lcl_lead_id,lac_activity,lac_activity_by,lac_created_on')
                ->orderBy('lcl_id', 'desc')
                ->findAll();
            if ($lead_log) {
                $response = [
                    'ret_data' => 'success',
                    'leadlog' => $lead_log,
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'leadlog' => []
                ];
            }

            return $this->respond($response, 200);
        }
    }

    public function insertUserNoti($data)
    {
        $model = new UserNotificationModel();
        $results = $model->insert($data);

        $builder = $this->db->table('users');
        $builder->select('FCM_token');
        $builder->where('us_id', $data['un_to']);
        $query = $builder->get();
        $row = $query->getRow();
        if ($row == '') {
            $token = 0;
        } else {
            $token = $row->FCM_token;
        }

        $post_data = '{
            "to" : "' . $token . '",
            "data" : {
              "body" : "",
              "title" : "' . $data['un_title'] . '",
              "message" : "' . $data['un_note'] . '",
            },
            "notification" : {
                 "body" : "' . $data['un_note'] . '",
                 "title" : "' . $data['un_title'] . '",                   
                 "message" : "' . $data['un_note'] . '",
                "icon" : "new",
                "sound" : "default"
                },

          }';
        $URL = 'https://fcm.googleapis.com/fcm/send';
        $ch = curl_init($URL);
        $headers = array(
            "Content-type: application/json",
            "Authorization: key=AAAA90QtRBg:APA91bFY1RSS4WP7QpR74Th23iTGMWIzZ0EaDc6tVEpeyGbr-HFXjzyP1UdHf2vySK5g0cyQCtuOQnIswuFbr2Ml2o5nSzL6R6eP5gbdeVNW-M1nFhvz2D7ivy3HrRneAaoRq4SfPisx",
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);
    }
    public function checkLeadAvail()
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

            $phone = $this->request->getVar('phone');
            $ph = substr($phone, -7);
            $patern = $ph;
            // $lead_res= $model->like('phone', $patern)->where('status_id', 1)->select('lead_id ,name as customer_name,phone as mobile')->first();
            $builder = $this->db->table('leads');
            $builder->select('count(leads.cus_id) as lc,lead_id');
            $builder->like('phone', $patern);
            $builder->where('status_id', '1');
            //  $builder->orWhere('status_id', '1');      
            $query = $builder->get();
            $result = $query->getRow();
            $penCount =  $result->lc;
            $penLeadId = $result->lead_id;
            $response = [
                'ret_data' => 'success',
                'lead_res' => $penLeadId
            ];

            return $this->respond($response, 200);
        }
    }

    public function leadCountDash()
    {
        $model = new LeadModel();
        $validModel = new Validation();
        $commonutils = new Common();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
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

            $builder = $this->db->table('leads');
            $builder->select('count(leads.lead_id) as lc,lead_id');
            $builder->where('lead_createdby', $tokendata['uid']);
            $builder->where('status_id', '7');
            //  $builder->orWhere('status_id', '1');      
            $query = $builder->get();
            $result = $query->getRow();
            $Count =  $result->lc;
            $response = [
                'ret_data' => 'success',
                'count' => $Count
            ];

            return $this->respond($response, 200);
        }
    }

    function updateLeadCloseTime()
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();
        $log = new LeadCallLogModel();

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

                'time' => 'required',

            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());


            $data = [
                'close_time' => $this->request->getVar('time'),
                'lead_from' => $this->request->getVar('leadFrom'),

            ];
            $id = $this->request->getVar('id');

            if ($model->where('lead_id', $id)->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            } else {
                return $this->respond(['ret_data' => 'success'], 201);
            }
        }
    }
    public function modalleadupdate($id = null)
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();
        $log = new LeadCallLogModel();

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

                'phone' => 'required',

            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

            if ($this->request->getVar('lead_note') == '' || $this->request->getVar('vehicle_model') != '') {
                $note = "(Brand - " . $this->request->getVar('ld_brand') . " )" . $this->request->getVar('vehicle_model');
            } else {
                $note = $this->request->getVar('lead_note');
            }


            $data = [
                'name' => $this->request->getVar('name'),
                'phone' => $this->request->getVar('phone'),
                'lead_note' => $note,
                //'lead_note' => $this->request->getVar('lead_note'),  
                'status_id' => $this->request->getVar('status_id'),
                'lang_id' => $this->request->getVar('lang_id'),
                'purpose_id' => $this->request->getVar('purpose_id'),
                'email' => $this->request->getVar('email'),
                'register_number' => $this->request->getVar('register_number'),
                'source_id' => $this->request->getVar('source_id'),
                'assigned' => $this->request->getVar('assigned'),
                'address' => $this->request->getVar('address'),
                'lead_updatedby' => $tokendata['uid'],
                'ld_brand' =>  $this->request->getVar('ld_brand'),
                'lead_from' =>  $this->request->getVar('leadFrom'),
                //'lead_creted_date' => $this->request->getVar('createdDate'),
                'appoint_date' => $this->request->getVar('dateField'),
                // 'lead_creted_date' => date("Y-m-d H:i:s"),
                'lead_updatedon' => date("Y-m-d H:i:s"),

            ];
            $id = $this->request->getVar('id');
            if ($this->request->getVar('status_id') == '6') {
                $data1 = ['reason_to_close' => $this->request->getVar('lead_note')];
                $data = array_merge($data, $data1);
            } else {
                $data1 = ['lead_note' => $this->request->getVar('lead_note')];
                $data = array_merge($data, $data1);
            }
            if ($model->where('lead_id', $id)->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            } else {

                $logdata = [
                    'lcl_lead_id' => $id,
                    'lcl_pupose_id' => $this->request->getVar('purpose_id'),
                    'lcl_purpose_note' => $note,
                    'lcl_call_to' => $this->request->getVar('call_to'),
                    'lcl_phone' => $this->request->getVar('phone'),
                    //  'lcl_call_time'=>$this->request->getVar('calltime'),
                    'ystar_call_id' => $this->request->getVar('call_id')

                    //'lcl_call_time'=>$this->request->getVar('call_time'),
                    // 'lcl_time'=>$this->request->getVar('today')
                ];
                $log->insert($logdata);
                $acdata = [
                    'lac_activity' => 'Updated Lead',
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $id,

                ];

                $acmodel->insert($acdata);
                // $this->insertUserLog('Lead Updated',$tokendata['uid']);
                return $this->respond(['ret_data' => 'success', 'data' => $this->request->getVar('leadFrom')], 201);
            }
        }
    }
    public function getCallLog()
    {

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

            $call_id = $this->request->getVar('call_id');
            $call_to = $this->request->getVar('call_to');
            $leadlog = $leadlogmodel->whereIn('ystar_call_id', $call_id)->where('lcl_pupose_id !=', '0')
                ->join('call_purposes', 'call_purposes.cp_id =lead_call_log.lcl_pupose_id', 'left')
                ->select("lcl_id,lcl_time,lcl_lead_id,RIGHT(lcl_phone,7) as phon_uniq,lcl_purpose_note,
            ystar_call_id,lcl_call_to,call_purpose")->find();
            $response = [
                'ret_data' => 'success',
                'leadlog' => $leadlog,
            ];
            return $this->respond($response, 200);
        }
    }

    public function LeadsByDate()
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();
        $log = new LeadCallLogModel();

        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        if ($tokendata) {

            $start =  $this->request->getVar('startdate');
            $end =  $this->request->getVar('enddate');
            $res = $model->where('lead_delete_flag', 0)->where('status_id', 1)->where('DATE(lead_createdon) >=', $start)->where('DATE(lead_createdon) <=', $end)->groupBy('DATE(lead_createdon)')
                ->select('count(lead_id) as count,DATE(lead_createdon) as dd')->findAll();

            //  $res= $model->where('lead_delete_flag', 0)->findAll();
            if ($res) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'lead' => $res
                ];
                return $this->respond($response, 200);
            } else {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'lead' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }


    public function modalleadupdateinbound($id = null)
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();
        $log = new LeadCallLogModel();
        $custmastermodel = new CustomerMasterModel();

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

                'phone' => 'required',

            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

            if ($this->request->getVar('lead_note') == '' || $this->request->getVar('vehicle_model') != '') {
                $barnd =  $this->request->getVar('ld_brand') . "-" . $this->request->getVar('vehicle_model');
            } else {
                $barnd = $this->request->getVar('ld_brand');
            }


            $data = [
                'name' => $this->request->getVar('name'),
                'phone' => $this->request->getVar('phone'),
                'lead_note' => $this->request->getVar('lead_note'),
                'status_id' => $this->request->getVar('status_id'),
                'lang_id' => $this->request->getVar('lang_id'),
                'purpose_id' => $this->request->getVar('purpose_id'),
                'email' => $this->request->getVar('email'),
                'register_number' => $this->request->getVar('register_number'),
                'source_id' => $this->request->getVar('source_id'),
                'assigned' => $this->request->getVar('assigned'),
                'address' => $this->request->getVar('address'),
                'lead_updatedby' => $tokendata['uid'],
                'ld_brand' =>  $barnd,
                'lead_from' =>  $this->request->getVar('leadFrom'),
                'lead_creted_date' => $this->request->getVar('createdDate'),
                'ld_appoint_date' => $this->request->getVar('dateField'),
                'ld_appoint_time' => $this->request->getVar('appTime'),
                'ld_camp_id' => $this->request->getVar('camp'),
                'reason_to_close' => $this->request->getVar('other_reason'),
                'assigned' => $this->request->getVar('forward_to'),
                'rating' => $this->request->getVar('rating'),
                //'lead_creted_date' => date("Y-m-d H:i:s"),
                'lead_updatedon' => date("Y-m-d H:i:s"),

            ];
            $id = $this->request->getVar('id');

            if ($model->where('lead_id', $id)->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            } else {
                if ($this->request->getVar('customer_code') != 0) {
                    $ins = ['cust_alternate_contact' => $this->request->getVar('alt_number')];
                    $custmastermodel->where('cust_alm_code', $this->request->getVar('customer_code'))->set($ins)->update();
                }

                $logdata = [
                    'lcl_lead_id' => $id,
                    'lcl_pupose_id' => $this->request->getVar('purpose_id'),
                    'lcl_purpose_note' => $this->request->getVar('lead_note'),
                    'lcl_call_to' => $this->request->getVar('call_to'),
                    'lcl_phone' => $this->request->getVar('phone'),
                    'lcl_call_type' => $this->request->getVar('call_type'),
                    //  'lcl_call_time'=>$this->request->getVar('calltime'),
                    'ystar_call_id' => $this->request->getVar('call_id'),
                    'lcl_created_on' => date("Y-m-d H:i:s"),
                    //'lcl_call_time'=>$this->request->getVar('call_time'),
                    // 'lcl_time'=>$this->request->getVar('today')
                ];
                $log->insert($logdata);
                $acdata = [
                    'lac_activity' => 'Updated Lead',
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $id,

                ];

                $acmodel->insert($acdata);
                $return_data = $model->where('lead_id', $id)->first();
                $data['leadFrom'] = $this->request->getVar('leadFrom');
                $data['leadData'] = $return_data;
                return $this->respond(['ret_data' => 'success', 'data' => $data], 201);
            }
        }
    }

    public function LeadByPurpose()
    {
        $model = new LeadModel();
        $UserModel = new UserModel();
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


            $userdept = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->select('us_dept_id,us_dept_head,us_role_id')->first();
            if ($userdept['us_role_id'] == 1) {
                $builder = $this->db->table('leads');
                $builder->select('lead_id,lead_code,name,CONCAT("*****",RIGHT(phone,4)) as phone,vehicle_model,lead_note,source_id,DATE(lead_createdon) as lead_createdon ,status_id,lead_source.ld_src,lead_status.ld_sts,users.us_firstname,ld_appoint_date,assigned,camp_name,ld_camp_id,ld_brand,us.us_firstname as created');
                $builder->join('users', 'users.us_id =assigned', 'left');
                $builder->join('users as us', 'us.us_id =lead_createdby', 'left');
                $builder->join('lead_source', 'lead_source.ld_src_id =source_id', 'left');
                $builder->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left');
                $builder->join('campaign', 'campaign.camp_id =ld_camp_id', 'left');
                $builder->where('lead_delete_flag', 0);
                $builder->where('purpose_id', $this->request->getVar('pid'));
                $builder->orderBy('ld_appoint_date', 'ASC');
                $query = $builder->get();
                $res = $query->getResultArray();
            } else if ($userdept['us_dept_head'] == true && $userdept['us_role_id'] != 1) {
                //     $res= $model->where('lead_delete_flag', 0)
                //     ->where('status_id !=', 7)
                //     ->where('purpose_id', $this->request->getVar('pid'))               
                //     ->join('users','users.us_id =assigned','left')
                //     ->join('lead_source','lead_source.ld_src_id =source_id','left')
                //     ->join('lead_status','lead_status.ld_sts_id =status_id','left')
                //     ->orderBy('ld_appoint_date', 'ASC')
                //    ->findAll();


                $builder = $this->db->table('leads');
                $builder->select('lead_id,lead_code,name,CONCAT("*****",RIGHT(phone,4)) as phone,vehicle_model,lead_note,source_id,DATE(lead_createdon) as lead_createdon,status_id,lead_source.ld_src,lead_status.ld_sts,users.us_firstname,ld_appoint_date,assigned,camp_name,ld_camp_id,ld_brand,us.us_firstname as created');
                $builder->join('users', 'users.us_id =assigned', 'left');
                $builder->join('users as us', 'us.us_id =lead_createdby', 'left');
                $builder->join('lead_source', 'lead_source.ld_src_id =source_id', 'left');
                $builder->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left');
                $builder->where('lead_delete_flag', 0);
                $builder->where('purpose_id', $this->request->getVar('pid'));
                $builder->orderBy('ld_appoint_date', 'ASC');
                $builder->where('us_dept_id', $userdept['us_dept_id']);
                $query = $builder->get();
                $res = $query->getResultArray();
            } else {

                $builder = $this->db->table('leads');
                $builder->select('lead_id,lead_code,name,CONCAT("*****",RIGHT(phone,4)) as phone,vehicle_model,lead_note,source_id,DATE(lead_createdon) as lead_createdon,status_id,lead_source.ld_src,lead_status.ld_sts,users.us_firstname,ld_appoint_date,assigned,ld_brand,us.us_firstname as created');
                $builder->join('users', 'users.us_id =assigned', 'left');
                $builder->join('users as us', 'us.us_id =lead_createdby', 'left');
                $builder->join('lead_source', 'lead_source.ld_src_id =source_id', 'left');
                $builder->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left');
                $builder->where('lead_delete_flag', 0);
                $builder->where('purpose_id', $this->request->getVar('pid'));
                $builder->orderBy('ld_appoint_date', 'ASC');
                $builder->where('assigned', $tokendata['uid']);
                $query = $builder->get();
                $res = $query->getResultArray();
            }


            if ($res) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'lead' => $res
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function AssignLead()
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();
        $log = new LeadCallLogModel();

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

            if ($this->request->getVar('assigned') != null) {
                $data = [
                    'assigned' => $this->request->getVar('assigned'),

                ];
            } else if ($this->request->getVar('status_id') != null) {
                $data = [
                    'status_id' => $this->request->getVar('status_id'),

                ];
            }
            $id = $this->request->getVar('id');

            if ($model->where('lead_id', $id)->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            } else {


                $acdata = [
                    'lac_activity' => 'Lead Assigned',
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $id,

                ];

                $acmodel->insert($acdata);
                // $this->insertUserLog('Lead Updated',$tokendata['uid']);
                return $this->respond(['ret_data' => 'success', 'data' => $this->request->getVar('leadFrom')], 201);
            }
        }
    }
    public function NotUpdatedLead()
    {
        $model = new LeadModel();
        $UserModel = new UserModel();
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

            $app_id = $this->request->getVar('app_id');
            $serv_id = $this->request->getVar('serv_id');

            $builder = $this->db->table('leads');
            $builder->select('lead_id,DATEDIFF(NOW(),lead_updatedon) as date_diff');
            $builder->where('lead_delete_flag', 0);
            $builder->where('purpose_id', $serv_id);
            //   $builder->where('assigned',0); 
            $builder->where('status_id', 1);
            //  $builder->where('DATEDIFF(lead_updatedon, lead_createdon) >=',2);    
            $builder->where('DATEDIFF(NOW(),lead_updatedon) >=', 2);
            $query = $builder->get();
            $Ser_res = $query->getResultArray();

            $builder = $this->db->table('leads');
            $builder->select('lead_id,DATEDIFF(NOW(),ld_appoint_date) as date_diff');
            $builder->where('lead_delete_flag', 0);
            $builder->where('purpose_id', $app_id);
            //  $builder->where('assigned',0); 
            $builder->where('status_id', 1);
            //  $builder->where('DATEDIFF(lead_updatedon, lead_createdon) >=',2);    
            $builder->where('DATEDIFF(NOW(),ld_appoint_date) >=', 2);
            $query = $builder->get();
            $app_res = $query->getResultArray();

            $res = array_merge($Ser_res, $app_res);
            if ($res) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'delaylead' => $res
                ];
                return $this->respond($response, 200);
            } else {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'delaylead' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function modalleadupdateoutbound($id = null)
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();
        $log = new LeadCallLogModel();

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

                'phone' => 'required',

            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

            if ($this->request->getVar('lead_note') == '' || $this->request->getVar('vehicle_model') != '') {
                $barnd =  $this->request->getVar('ld_brand') . "-" . $this->request->getVar('vehicle_model');
            } else {
                $barnd = $this->request->getVar('ld_brand');
            }


            $data = [
                'name' => $this->request->getVar('name'),
                'phone' => $this->request->getVar('phone'),
                'lead_note' => $this->request->getVar('lead_note'),
                'status_id' => $this->request->getVar('status_id'),
                'lang_id' => $this->request->getVar('lang_id'),
                'purpose_id' => $this->request->getVar('purpose_id'),
                'email' => $this->request->getVar('email'),
                'register_number' => $this->request->getVar('register_number'),
                'source_id' => $this->request->getVar('source_id'),
                'assigned' => $this->request->getVar('assigned'),
                'address' => $this->request->getVar('address'),
                'lead_updatedby' => $tokendata['uid'],
                'ld_brand' =>  $barnd,
                'lead_from' =>  $this->request->getVar('leadFrom'),
                'lead_creted_date' => $this->request->getVar('createdDate'),
                'ld_appoint_date' => $this->request->getVar('dateField'),
                'ld_camp_id' => $this->request->getVar('camp'),
                'reason_to_close' => $this->request->getVar('other_reason'),
                'assigned' => $this->request->getVar('forward_to'),
                'rating' => $this->request->getVar('rating'),
                'outbound_lead' => '1',
                //'lead_creted_date' => date("Y-m-d H:i:s"),
                'lead_updatedon' => date("Y-m-d H:i:s"),

            ];
            $id = $this->request->getVar('id');

            if ($model->where('lead_id', $id)->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            } else {

                $logdata = [
                    'lcl_lead_id' => $id,
                    'lcl_pupose_id' => $this->request->getVar('purpose_id'),
                    'lcl_purpose_note' => $this->request->getVar('lead_note'),
                    'lcl_call_to' => $this->request->getVar('call_to'),
                    'lcl_phone' => $this->request->getVar('phone'),
                    //  'lcl_call_time'=>$this->request->getVar('calltime'),
                    'ystar_call_id' => $this->request->getVar('call_id'),
                    'lcl_created_on' => date("Y-m-d H:i:s"),
                    //'lcl_call_time'=>$this->request->getVar('call_time'),
                    // 'lcl_time'=>$this->request->getVar('today')
                ];
                $log->insert($logdata);
                $acdata = [
                    'lac_activity' => 'Updated Lead',
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $id,

                ];

                $acmodel->insert($acdata);
                $return_data = $model->where('lead_id', $id)->first();
                $data['leadFrom'] = $this->request->getVar('leadFrom');
                $data['leadData'] = $return_data;
                return $this->respond(['ret_data' => 'success', 'data' => $data], 201);
            }
        }
    }


    function getAppointmentLeads()
    {
        $model = new LeadModel();
        $UserModel = new UserModel();
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


            $userdept = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->select('us_dept_id,us_dept_head,us_role_id')->first();
            if ($userdept['us_role_id'] == 1) {
                $builder = $this->db->table('leads');
                $builder->select("lead_id,lead_code,name,phone as phone_number,vehicle_model,lead_note,source_id,DATE(lead_createdon) as lead_createdon ,status_id,lead_source.ld_src,lead_status.ld_sts,users.us_firstname,ld_appoint_date,assigned,camp_name,ld_camp_id,ld_brand,us.us_firstname as created,str_to_date(lead_creted_date, '%Y-%m-%d') as cr_date,ld_appoint_time");
                $builder->join('users', 'users.us_id =assigned', 'left');
                $builder->join('users as us', 'us.us_id =lead_createdby', 'left');
                $builder->join('lead_source', 'lead_source.ld_src_id =source_id', 'left');
                $builder->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left');
                $builder->join('campaign', 'campaign.camp_id =ld_camp_id', 'left');
                $builder->where('lead_delete_flag', 0);
                $builder->where('purpose_id', 1);
                $builder->where("str_to_date(lead_creted_date, '%d/%m/%Y')  >=", $this->request->getVar('sdate'));
                $builder->where("str_to_date(lead_creted_date, '%d/%m/%Y')  <=", $this->request->getVar('edate'));
                $builder->orderBy('lead_createdon', 'DESC');
                $query = $builder->get();
                $res = $query->getResultArray();
            } else if ($userdept['us_dept_head'] == true && $userdept['us_role_id'] != 1) {
                //     $res= $model->where('lead_delete_flag', 0)
                //     ->where('status_id !=', 7)
                //     ->where('purpose_id', $this->request->getVar('pid'))               
                //     ->join('users','users.us_id =assigned','left')
                //     ->join('lead_source','lead_source.ld_src_id =source_id','left')
                //     ->join('lead_status','lead_status.ld_sts_id =status_id','left')
                //     ->orderBy('ld_appoint_date', 'ASC')
                //    ->findAll();


                $builder = $this->db->table('leads');
                $builder->select("lead_id,lead_code,name,,phone as phone_number,vehicle_model,lead_note,source_id,DATE(lead_createdon) as lead_createdon,status_id,lead_source.ld_src,lead_status.ld_sts,users.us_firstname,ld_appoint_date,assigned,camp_name,ld_camp_id,ld_brand,us.us_firstname as created,str_to_date(lead_creted_date, '%Y-%m-%d') as cr_date,ld_appoint_time");
                $builder->join('users', 'users.us_id =assigned', 'left');
                $builder->join('users', 'users.us_id =assigned', 'left');
                $builder->join('users as us', 'us.us_id =lead_createdby', 'left');
                $builder->join('lead_source', 'lead_source.ld_src_id =source_id', 'left');
                $builder->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left');
                $builder->where('lead_delete_flag', 0);
                $builder->where('purpose_id', 1);
                $builder->where("str_to_date(lead_creted_date, '%d/%m/%Y')  >=", $this->request->getVar('sdate'));
                $builder->where("str_to_date(lead_creted_date, '%d/%m/%Y')  <=", $this->request->getVar('edate'));
                $builder->where('us_dept_id', $userdept['us_dept_id']);
                $builder->orderBy('lead_createdon', 'DESC');
                $query = $builder->get();
                $res = $query->getResultArray();
            } else {

                $builder = $this->db->table('leads');
                $builder->select('lead_id,lead_code,name,phone as phone_number,vehicle_model,lead_note,source_id,DATE(lead_createdon) as lead_createdon,status_id,lead_source.ld_src,lead_status.ld_sts,users.us_firstname,ld_appoint_date,assigned,ld_brand,us.us_firstname as created,ld_appoint_time');
                $builder->join('users', 'users.us_id =assigned', 'left');
                $builder->join('users as us', 'us.us_id =lead_createdby', 'left');
                $builder->join('lead_source', 'lead_source.ld_src_id =source_id', 'left');
                $builder->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left');
                $builder->where('lead_delete_flag', 0);
                $builder->where('purpose_id', 1);
                $builder->where("str_to_date(lead_creted_date, '%d/%m/%Y')  >=", $this->request->getVar('sdate'));
                $builder->where("str_to_date(lead_creted_date, '%d/%m/%Y')  <=", $this->request->getVar('edate'));
                $builder->where('assigned', $tokendata['uid']);
                $builder->orderBy('lead_createdon', 'DESC');
                $query = $builder->get();
                $res = $query->getResultArray();
            }


            //    $builder = $this->db->table('leads');
            //    $builder->select('sum(case when status_id = 1 then 1 else 0 end ) As newLead, sum(case when assigned = 0 then 1 else 0 end ) As saAss, sum(case when status_id = 3 then 1 else 0 end ) As completed,
            //    sum(case when status_id = 6 then 1 else 0 end ) As closed,sum(case when status_id = 4 then 1 else 0 end ) As cancelled',FALSE);   
            //    $builder->where("str_to_date(lead_creted_date, '%d/%m/%Y')  >=", $this->request->getVar('sdate'));
            //    $builder->where("str_to_date(lead_creted_date, '%d/%m/%Y')  <=", $this->request->getVar('edate'));
            //    $builder->where('purpose_id', 1);
            //    $query = $builder->get();
            //    $result = $query->getResultArray();
            if ($res) {

                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'lead' => $res,
                    // 'newLead'=> $result[0]['newLead'],
                    // 'saAss'=> $result[0]['saAss'],
                    // 'completed'=> $result[0]['completed'],
                    // 'closed'=> $result[0]['closed'],
                    // 'cancelled'=> $result[0]['cancelled'],
                    // 'leadCount'=> $result,


                ];
                return $this->respond($response, 200);
            } else {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'lead' => [],
                    // 'newLead'=> 0,
                    // 'saAss'=> 0,
                    // 'completed'=>0,
                    // 'closed'=> 0,
                    // 'cancelled'=> 0,


                ];
                return $this->respond($response, 200);
            }
        }
    }


    public function updateAppointLead()
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();

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

            $data = [

                'lead_note' => $this->request->getVar('lead_note'),
                'status_id' => $this->request->getVar('status_id'),
                'assigned' => $this->request->getVar('assigned'),
                'ld_appoint_date' => $this->request->getVar('dateField'),
                'ld_appoint_time' => $this->request->getVar('appTime'),
                'lead_updatedby' => $tokendata['uid'],

            ];
            $id = $this->db->escapeString($this->request->getVar('id'));
            if ($model->where('lead_id', $id)->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            } else {
                return $this->respond(['ret_data' => 'success'], 200);
            }
        }
    }


    public function leadPendingCount()
    {
        $leadmodel = new LeadModel();
        $leadlogModel = new LeadCallLogModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();

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
            $today = date("Y-m-d");

            $res = $leadlogModel->where('DATE(lcl_time)', $today)->where('lcl_pupose_id', 0)->where('lcl_call_to', 200)->select('ystar_call_id,lcl_phone')->findAll();
            $call_id = array();
            foreach ($res as $row) {
                $call_id[] = $row['ystar_call_id'];
            }


            $result = $leadlogModel->where('DATE(lcl_time)', $today)->whereIn('ystar_call_id',  $call_id)->where('lcl_pupose_id !=', 0)->select('ystar_call_id,lcl_phone')->findAll();
            $lpc = count($call_id) - count($result);

            // $builder = $this->db->table('leads');
            // $builder ->select('lead_id ,name,phone,lead_createdon,lead_updatedon,TIMEDIFF(lead_createdon,lead_updatedon) as time_diff');        
            // $builder->where('lead_delete_flag', 0);
            // $builder->where('status_id', 7);                
            // $builder->where('DATE(lead_createdon)',$today);
            // $builder->where('TIMEDIFF(lead_updatedon,lead_createdon) >=','00:15:00'); 
            // $query = $builder->get();
            // $lead_res = $query->getResultArray();

            //      $result=[];
            //      if($lead_res){
            //      foreach($lead_res as $lead){
            //          $ret=$leadlogModel->where('lcl_lead_id',$lead['lead_id'])->first();
            //          $ret? $lead['call_id']=$ret['ystar_call_id']:$lead['call_id']=0;
            //          array_push($result,$lead);

            //      }
            //  }

            $response = [
                'ret_data' => 'success',
                'LPcount' => $lpc,

            ];









            return $this->respond($response, 200);

            // $builder = $this->db->table('lead_call_log');
            // $builder ->select('ystar_call_id,lcl_phone');  
            //  $builder->where('DATE(lcl_time)', $today);
            // // $builder->where('lcl_pupose_id', 0);
            // $builder->where("ystar_call_id NOT IN (select ystar_call_id from lead_call_log where lcl_pupose_id != 0 AND DATE(lcl_time) = $today )");
            // $query = $builder->get();
            // $res = $query->getResultArray();






            // $builder = $this->db->table('lead_call_log');
            // $builder ->select('ystar_call_id');        
            // $builder->where('lcl_pupose_id', 0);
            // $builder->where('DATE(lcl_time)', $today);
            // $builder->groupBy('ystar_call_id'); 
            // $query = $builder->get();
            // $res = $query->getResultArray();
            // print_r($res);
            // $this->db->where("id NOT IN (select rooms,total_guests from res_hotel where (check_in <= '$check_in' AND check_out >= '$check_in') OR (check_in <= '$check_out' AND check_out >= '$check_out') OR (check_in >= '$check_in' AND check_out <= '$check_out' ) ) ");
            // $builder = $this->db->table('lead_call_log');
            // $builder ->select('ystar_call_id');        
            // $builder->where('lcl_pupose_id !=', 0);
            // $builder->where('DATE(lcl_time)', $today);
            // $builder->groupBy('ystar_call_id'); 
            // $query = $builder->get();
            // $result = $query->getResultArray();
            // print_r($result);

            //     $lead_res= $leadmodel->where('status_id', 7)->where('DATE(lead_createdon)',$today)->where('lead_delete_flag',0)->where('lead_createdby',$tokendata['uid'])->select('lead_id ,name,phone,lead_createdon')->findAll();
            //     $result=[];
            //     if($lead_res){
            //     foreach($lead_res as $lead){
            //         $ret=$leadlogModel->where('lcl_lead_id',$lead['lead_id'])->first();
            //         $ret? $lead['call_id']=$ret['ystar_call_id']:$lead['call_id']=0;
            //         array_push($result,$lead);

            //     }


            // }    
        }
    }

    public function getPreloadDatas()
    {
        $model = new LeadModel();
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
            $notes = $model->select("lead_note")
                ->where("lead_note != null ")
                ->orWhere("lead_note != ''")
                ->groupBy("lead_note")
                ->findAll();
            $cpmodel = new CallPurposeModel();
            $purpose = $cpmodel->where('cp_delete_flag', 0)->orderby('call_purpose', 'ASC')->findAll();
            if (sizeof($purpose) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'notes' => $notes,
                    'purpose' => $purpose,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function LeadCallLog()
    {
        $log = new LeadCallLogModel();
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

            $user_id = $this->request->getVar('user_id');
            $start_date = $this->request->getVar('start_day');
            $end_date = $this->request->getVar('end_day');


            $calls = $log->select("lcl_call_to as call_to,lcl_phone as Call_from,lcl_createdby,lcl_id,lcl_created_on,
            lcl_purpose_note as purpose_note,lcl_pupose_id as pupose_id,ystar_call_id,lcl_lead_id,leads.lead_code,leads.name,lcl_time,lcl_call_source")
                ->join('leads', 'leads.lead_id =lcl_lead_id', 'left')
                // ->where('lcl_createdby', $user_id)
                ->where('lcl_call_type', 0)
                ->where('lcl_created_on >=', $start_date)
                ->where('lcl_created_on <=', $end_date)
                ->findAll();

            if (sizeof($calls) > 0) {

                $response = [
                    'ret_data' => 'success',
                    'calls' => $calls,
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'calls' => [],
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getCallReports()
    {

        $log = new LeadCallLogModel();
        $quotelogmodel = new LeadQuoteLogModel();
        $model = new LeadModel();
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
            $purpose = $this->request->getVar('purpose');
            $start_day = $this->request->getVar('start_day');
            $end_day = $this->request->getVar('end_day');
            $us_id  = $this->request->getVar('us_id');
            $quote_type  = $this->request->getVar('type');


            if ($quote_type == "0") {
                $ngcalls = $log->whereIn('lcl_pupose_id', $purpose)
                    // ->where('lcl_created_on >=', $start_day)
                    // ->where('lcl_created_on <=', $end_day)
                    ->select('lcl_pupose_id,lcl_purpose_note,lcl_call_to,lcl_created_on,lcl_createdby,
                lcl_phone as call_from,lcl_lead_id,lead_code,name,phone,status_id,lead_note,
                call_purposes.call_purpose,call_purposes.cp_id,us_firstname,ystar_call_id,cust_name,assigned,lql_status,lql_status,lql_type')
                    ->join('leads', 'leads.lead_id =lead_call_log.lcl_lead_id', 'left')
                    ->join('users', 'users.us_id =leads.assigned', 'left')   // finding lead created user
                    ->join('customer_master', 'customer_master.cust_phone =lead_call_log.lcl_phone', 'left') //added just for fetch customer names
                    ->join('call_purposes', 'call_purposes.cp_id =lead_call_log.lcl_pupose_id', 'left')
                    ->join('lead_quote_log', 'lead_quote_log.lql_lead_id =lead_call_log.lcl_lead_id', 'left')
                    ->where('lql_delete_flag', 0)
                    ->findAll();

                $index = 0;
                foreach ($ngcalls as $leads) {
                    $countQuoteProvided = $quotelogmodel->where("lql_lead_id", $leads['lcl_lead_id'])
                        ->where('lql_status', 1)->countAllResults();
                    // $Quotestatus = $quotelogmodel->where("lql_lead_id", $leads['lcl_lead_id'])
                    //     ->where('lql_delete_flag', 0)->select("lql_status")->first();
                    // $Quotetype = $quotelogmodel->where("lql_lead_id", $leads['lcl_lead_id'])
                    //     ->where('lql_delete_flag', 0)->select("lql_type")->first();
                    // $Quotenote= $quotelogmodel->where("lql_lead_id", $leads['lcl_lead_id'])
                    //     ->where('lql_delete_flag', 0)->select("lql_type")->first();
                    // $quotelogmodel->where([
                    //     'lql_lead_id' =>$leads['lcl_lead_id'],
                    //     'lql_delete_flag' => 0,
                    //     'lql_status !=' => 0
                    // ])->countAllResults();
                    // $ngcalls[$index]["lql_status"] = $Quotestatus['lql_status'];
                    // $ngcalls[$index]["lql_type"] = $Quotetype['lql_type'];
                    $ngcalls[$index]["quote_count"] = $countQuoteProvided > 0 ? $countQuoteProvided : 0;
                    $index++;
                }
            } else {
                $ngcalls = $log->whereIn('lcl_pupose_id', $purpose)
                    ->where('lcl_created_on >=', $start_day)
                    ->where('lcl_created_on <=', $end_day)
                    ->where('assigned', $us_id)
                    ->select('lcl_pupose_id,lcl_purpose_note,lcl_call_to,lcl_created_on,lcl_createdby,
            lcl_phone as call_from,lcl_lead_id,lead_code,name,phone,status_id,lead_note,
            call_purposes.call_purpose,call_purposes.cp_id,us_firstname,ystar_call_id,cust_name,assigned')
                    ->join('leads', 'leads.lead_id =lead_call_log.lcl_lead_id', 'left')
                    ->join('users', 'users.us_id =leads.assigned', 'left')   // finding lead created user
                    ->join('customer_master', 'customer_master.cust_phone =lead_call_log.lcl_phone', 'left') //added just for fetch customer names
                    ->join('call_purposes', 'call_purposes.cp_id =lead_call_log.lcl_pupose_id', 'left')
                    ->findAll();

                $index = 0;
                foreach ($ngcalls as $leads) {
                    $countQuoteProvided = $quotelogmodel->where("lql_lead_id", $leads['lcl_lead_id'])->where('lql_status', 1)
                        ->countAllResults();

                    $Quotestatus = $quotelogmodel->where("lql_lead_id", $leads['lcl_lead_id'])
                        ->where('lql_delete_flag', 0)->select("lql_status")->first();
                    $Quotetype = $quotelogmodel->where("lql_lead_id", $leads['lcl_lead_id'])
                        ->where('lql_delete_flag', 0)->select("lql_type")->first();


                    // $quotelogmodel->where([
                    //     'lql_lead_id' =>$leads['lcl_lead_id'],
                    //     'lql_delete_flag' => 0,
                    //     'lql_status !=' => 0
                    // ])->countAllResults();
                    $ngcalls[$index]["lql_status"] = $Quotestatus['lql_status'];
                    $ngcalls[$index]["lql_type"] = $Quotetype['lql_type'];
                    $ngcalls[$index]["quote_count"] = $countQuoteProvided > 0 ? $countQuoteProvided : 0;
                    $index++;
                }
            }



            if (sizeof($ngcalls) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'calls' => $ngcalls,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }



    public function getLeadQuote()
    {
        $leadmodel = new LeadModel();
        $quotelogmodel = new LeadQuoteLogModel();
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

            $lead_id = $this->request->getVar('lead_id');

            $res = $leadmodel->where('lead_id', $lead_id)
                ->join('lead_quote_log', 'lead_quote_log.lql_lead_id =lead_id', 'left')
                ->join('lead_source', 'lead_source.ld_src_id = leads.source_id', 'left')
                ->join('users', 'users.us_id = assigned', 'left')
                ->join('social_media_campaign_source', 'social_media_campaign_source.smcs_id =lead_social_media_source', 'left')
                ->join('social_media_campaign', 'social_media_campaign.smc_id =lead_social_media_mapping', 'left')
                ->where('lql_delete_flag !=', 1)
                ->select('lql_activity,lql_code,lql_created_by,lql_created_on,lql_delete_flag,lql_id,
                lql_lead_id,lql_note,lql_reason,lql_status,lql_type,us_firstname as quote_assigned,cus_id,
                jc_status,ld_appoint_date,ld_appoint_time,ld_cust_response,ld_further_action,lead_code,
                lead_createdby,lead_createdon,lead_creted_date,lead_from,lead_id,lead_note,
                lead_updatedby,lead_updatedon,name,outbound_lead,phone,purpose_id,register_number,
                rating,reason_to_close,source_id,status_id,vehicle_model,assigned,lql_source,ld_src as lead_source,
                lead_social_media_source,lead_social_media_mapping,smc_message,smc_name,smcs_name,ld_verify_flag')
                ->first();

            $res_log =  $quotelogmodel->where('lql_lead_id', $lead_id)
                ->join('users', 'users.us_id = lql_created_by', 'left')
                ->join('quotes_master', 'quotes_master.qt_id = lql_code', 'left')
                ->join('appointment_master', 'appointment_master.apptm_lead_id = lql_lead_id', 'left')
                ->select('lql_activity,lql_code,lql_created_by,lql_created_on,lql_delete_flag,lql_id,
                lql_lead_id,lql_note,lql_reason,lql_status,lql_type,us_firstname,qt_code')
                ->findAll();

            $count =  $quotelogmodel->where([
                'lql_lead_id' => $lead_id,
                'lql_delete_flag' => 0,
                'lql_status !=' => 0
            ])->countAllResults();

            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'lead' => $res,
                    'quoteLog' => $res_log,
                    'naCount' => $count,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function leaadQuoteAppoint()
    {

        $ApptMaster = new AppointmentMasterModel();
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

            $lead_id = $this->request->getVar('id');


            // $appt_id = $ApptMaster->where('apptm_lead_id', $lead_id)->select('*')->FindAll();

            $appt_id = $ApptMaster->where('apptm_lead_id', $lead_id)
                ->where('appointment.appt_status =', 0)
                ->where('apptm_delete_flag!=', 1)
                ->join('appointment', 'appointment.appt_apptm_id = apptm_id', 'left')
                ->select('*')
                ->first();

            if ($appt_id) {
                $response = [
                    'ret_data' => 'success',
                    'appt_id' => $appt_id,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function leadQuoteUpdate()
    {

        $leadmodel = new LeadModel();
        $quotelogmodel = new LeadQuoteLogModel();
        $ApptMaster = new AppointmentMasterModel();
        $Appoint = new AppointmentModel();
        $Appointmentlog = new AppointmentLogModel();
        $lead_log_model = new LeadActivityModel();
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
            $lead_id = $this->request->getVar('lql_lead_id');
            $quote_status = $this->request->getVar('verify');
            $purpose_id = $this->request->getVar('purpose_idd');
            $status = intval($quote_status);
            $this->db->transStart();
            // 1=Quotation Provided
            // 2=Quotation Accepted
            // 3=Revise quote Requested
            // 4=Not Answering
            // 5=Quotation Rejected
            // 6=Customer Not Responding

            if ($quote_status == "1") {
                $statusdata = [
                    'lql_delete_flag' => 1
                ];
                $quote_delete = $quotelogmodel->where('lql_lead_id', $lead_id)->set($statusdata)->update();

                if ($quote_delete) {
                    $type = $this->request->getVar('quote_type');
                    //1=Quotation Provided
                    $quote_log_data = [
                        'lql_code' => $this->request->getVar('quote_no'),
                        'lql_lead_id' => $lead_id,
                        'lql_type' => $type,
                        'lql_status' => 1,  //1=Quotation Provided
                        'lql_created_by' => $tokendata['uid'],
                        'lql_note' => $this->request->getVar('Remarks'),
                        'lql_activity' => "Quotation Provided",
                        'lql_created_on' => date("Y-m-d H:i:s"),
                    ];

                    $quote_log = $quotelogmodel->insert($quote_log_data);
                }
            } else if ($quote_status == "2") {
                // Quotation Accepted
                $data = [
                    // 'name' => $this->request->getVar('name'),
                    // 'phone' => $this->request->getVar('phone'),
                    // 'vehicle_model' => $this->request->getVar('vehicle_model'),
                    'register_number' => $this->request->getVar('reg_no'),
                    'assigned' => $this->request->getVar('assigned'),
                    'lead_updatedby' => $tokendata['uid'],
                    'ld_appoint_date' => $this->request->getVar('dateField'),
                    'ld_appoint_time' => $this->request->getVar('appTime'),
                    'lead_note' => $this->request->getVar('Remarks'),
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                ];
                $leadupdate = $leadmodel->where('lead_id', $lead_id)->set($data)->update();

                if ($leadupdate) { //lql_delete_flag

                    $quote_code = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                        ->select("lql_code")->first();
                    $quote_type = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                        ->select("lql_type")->first();
                    $quote_source = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                        ->select("lql_source")->first();
                    $statusdata = [
                        'lql_delete_flag' => 1
                    ];
                    $quote_delete = $quotelogmodel->where('lql_lead_id', $lead_id)->set($statusdata)->update();

                    if ($quote_delete) {
                        $quote_log_data = [
                            'lql_code' => $quote_code,
                            'lql_type' => $quote_type,
                            'lql_source' => $quote_source,
                            'lql_lead_id' => $lead_id,
                            'lql_status' => 2,
                            'lql_created_on' => date("Y-m-d H:i:s"),
                            'lql_created_by' => $tokendata['uid'],
                            'lql_note' => $this->request->getVar('Remarks'),
                            'lql_activity' => "Quotation Accepted",
                        ];
                        $quote_log = $quotelogmodel->insert($quote_log_data);
                    }
                }


                // setting appointment
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
                $ApptMdata = [
                    //'apptm_customer_code'=>   ,
                    'apptm_code' => $code,
                    'apptm_lead_id' => $lead_id,
                    'apptm_status' => '1', //Appointment Scheduled
                    'apptm_group' => $this->request->getVar('apptm_group'),
                    'apptm_transport_service' =>  $this->request->getVar('transportation_service'),
                    'apptm_created_by' =>  $tokendata['uid'],
                    'apptm_created_on' => date("Y-m-d H:i:s"),
                    'apptm_updated_on' => date("Y-m-d H:i:s"),
                ];

                $result = $ApptMaster->insert($ApptMdata);

                if ($result) {
                    $builder = $this->db->table('sequence_data');
                    $builder->set('appt_seq', ++$seqvalfinal);
                    $builder->update();
                    $Apptdata = [
                        'appt_apptm_id' => $result,
                        'appt_date' => $this->request->getVar('dateField'),
                        'appt_time' => $this->request->getVar('appTime'),
                        'appt_assign_to' =>  $this->request->getVar('assigned'),
                        'appt_note' => $this->request->getVar('Remarks'),
                        'appt_created_by' => $tokendata['uid'],
                        'appt_created_on' => date("Y-m-d H:i:s"),
                    ];

                    $result1 = $Appoint->insert($Apptdata);
                }

                $Logdata = [
                    'applg_apptm_id' => $result,
                    'applg_note' => "Appointment Scheduled",
                    'applg_created_by' => $tokendata['uid'],
                    'applg_created_on' => date("Y-m-d H:i:s"),
                    'applg_time' => date("Y-m-d H:i:s"),
                ];

                $logentry = $Appointmentlog->insert($Logdata);
            } else if ($quote_status == "3") {
                //Revise Quote Requested
                $data = [
                    // 'name' => $this->request->getVar('cust_name'),
                    // 'phone' => $this->request->getVar('call_from'),
                    'lead_updatedby' => $tokendata['uid'],
                    'lead_note' => $this->request->getVar('Remarks'),
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                ];
                $leadupdate = $leadmodel->where('lead_id', $lead_id)->set($data)->update();
                $quote_source = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                    ->select("lql_source")->first();
                $statusdata = [
                    'lql_delete_flag' => 1
                ];
                $quote_delete = $quotelogmodel->where('lql_lead_id', $lead_id)->set($statusdata)->update();
                if ($quote_delete) {
                    $quote_log_data = [
                        'lql_lead_id' => $lead_id,
                        'lql_status' => 3,
                        'lql_source' => $quote_source,
                        'lql_created_on' => date("Y-m-d H:i:s"),
                        'lql_created_by' => $tokendata['uid'],
                        'lql_note' => $this->request->getVar('Remarks'),
                        'lql_activity' => "Revise Quotation Requested",
                        'lql_reason' => $this->request->getVar('reason'),
                    ];

                    $quote_log = $quotelogmodel->insert($quote_log_data);
                }
            } else if ($quote_status == "4") {
                // Not Answering

                // $data = [
                //     'name' => $this->request->getVar('cust_name'),
                //     'phone' => $this->request->getVar('call_from'),
                //     'assigned' => $this->request->getVar('assigned'),
                //     'lead_updatedby' => $tokendata['uid'],
                //     'lead_note' => $this->request->getVar('lead_note'),
                // ];
                // $leadupdate = $leadmodel->where('lead_id', $lead_id)->set($data)->update();

                $quote_code = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                    ->select("lql_code")->first();
                $quote_type = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                    ->select("lql_type")->first();
                $quote_source = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                    ->select("lql_source")->first();
                $statusdata = [
                    'lql_delete_flag' => 1
                ];
                $quote_delete = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_status !=', 4)
                    ->set($statusdata)->update();

                if ($quote_delete) {


                    $quote_log_data = [
                        'lql_code' => $quote_code,
                        'lql_type' => $quote_type,
                        'lql_source' => $quote_source,
                        'lql_lead_id' => $lead_id,
                        'lql_status' => 4,
                        'lql_created_on' => date("Y-m-d H:i:s"),
                        'lql_created_by' => $tokendata['uid'],
                        'lql_note' => $this->request->getVar('Remarks'),
                        'lql_activity' => "Customer Not Answering"
                    ];

                    $quote_log = $quotelogmodel->insert($quote_log_data);
                }
            } else if ($quote_status == "5") {
                //Quote Cancelled or Rejected
                $data = [
                    // 'name' => $this->request->getVar('cust_name'),
                    // 'phone' => $this->request->getVar('call_from'),
                    'status_id' => 6,  //lead closed
                    'lead_updatedby' => $tokendata['uid'],
                    'lead_note' => $this->request->getVar('Remarks'),
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                ];
                $leadupdate = $leadmodel->where('lead_id', $lead_id)->set($data)->update();

                $lead_log = [
                    'lac_activity' => 'Lead Closed due to Quote Cancelled or Rejected',
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $lead_id,
                ];
                $lead_log_model->insert($lead_log);

                $quote_code = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                    ->select("lql_code")->first();
                $quote_type = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                    ->select("lql_type")->first();
                $quote_source = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                    ->select("lql_source")->first();
                $statusdata = [
                    'lql_delete_flag' => 1
                ];
                $quote_delete = $quotelogmodel->where('lql_lead_id', $lead_id)->set($statusdata)->update();
                if ($quote_delete) {
                    $quote_log_data = [
                        'lql_code' =>  $quote_code,
                        'lql_type' => $quote_type,
                        'lql_source' => $quote_source,
                        'lql_lead_id' => $lead_id,
                        'lql_status' => 5,
                        'lql_created_on' => date("Y-m-d H:i:s"),
                        'lql_created_by' => $tokendata['uid'],
                        'lql_note' => $this->request->getVar('Remarks'),
                        'lql_activity' => "Quotation Cancelled",
                        'lql_reason' => $this->request->getVar('reason'),
                    ];

                    $quote_log = $quotelogmodel->insert($quote_log_data);
                }
            } else if (isset($purpose_id) && $purpose_id == "11") {
                $quote_code = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                    ->select("lql_code")->first();
                $quote_type = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                    ->select("lql_type")->first();
                $quote_source = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                    ->select("lql_source")->first();
                $quote_log_data = [
                    'lql_code' =>  $quote_code,
                    'lql_type' => $quote_type,
                    'lql_source' => $quote_source,
                    'lql_lead_id' => $lead_id,
                    'lql_status' => 7,
                    'lql_created_on' => date("Y-m-d H:i:s"),
                    'lql_created_by' => $tokendata['uid'],
                    'lql_note' => $this->request->getVar('Remarks'),
                    'lql_activity' => "Quotation Enquiry"
                ];

                $quote_log = $quotelogmodel->insert($quote_log_data);
            } else {
                // 6 = customer Not responding

                $data = [
                    // 'name' => $this->request->getVar('cust_name'),
                    // 'phone' => $this->request->getVar('call_from'),
                    'status_id' => 6,   //lead closed
                    'lead_updatedby' => $tokendata['uid'],
                    'lead_note' => $this->request->getVar('Remarks'),
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                ];
                $leadupdate = $leadmodel->where('lead_id', $lead_id)->set($data)->update();

                $lead_log = [
                    'lac_activity' => 'Lead Closed due to customer Not responding',
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $lead_id,
                ];
                $lead_log_model->insert($lead_log);

                $quote_code = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                    ->select("lql_code")->first();
                $quote_type = $quotelogmodel->where('lql_lead_id', $lead_id)->where('lql_delete_flag !=', 1)
                    ->select("lql_type")->first();

                $statusdata = [
                    'lql_delete_flag' => 1
                ];
                $quote_delete = $quotelogmodel->where('lql_lead_id', $lead_id)->set($statusdata)->update();
                if ($quote_delete) {


                    $quote_log_data = [
                        'lql_code' =>  $quote_code,
                        'lql_type' => $quote_type,
                        'lql_lead_id' => $lead_id,
                        'lql_status' => 6,
                        'lql_created_on' => date("Y-m-d H:i:s"),
                        'lql_created_by' => $tokendata['uid'],
                        'lql_note' => $this->request->getVar('Remarks'),
                        'lql_activity' => "Customer Not Responding"
                    ];

                    $quote_log = $quotelogmodel->insert($quote_log_data);
                }
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->db->transCommit();
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            }

            // if (isset($leadupdate)) {
            //     if (isset($quote_log)) {
            //         if (isset($result)) {
            //             if (isset($result1)) {
            //                 $response = [
            //                     'ret_data' => 'success',
            //                     'lead' => $leadupdate,
            //                     'apptM' => $result,
            //                     'Appt' => $result1,
            //                 ];
            //             } else {
            //                 $response = [
            //                     'ret_data' => 'success',
            //                     'apptM' => $result,
            //                 ];
            //             }
            //         } else {
            //             $response = [
            //                 'ret_data' => 'success',
            //                 'lead' => $leadupdate,
            //                 'quote_log' => $quote_log,
            //             ];
            //         }
            //     } else {
            //         $response = [
            //             'ret_data' => 'success',
            //             'lead' => $leadupdate,
            //         ];
            //     }
            // } else if (isset($quote_log)) {
            //     $response = [
            //         'ret_data' => 'success',
            //         'quote_log' => $quote_log,
            //     ];
            // } else {
            //     $response = [
            //         'ret_data' => 'fail',
            //     ];
            // }
            // return $this->respond($response, 200);
        }
    }






    public function getQuoteDetails()
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

            $modelQ = new QuotesMasterModel();
            $res = $modelQ->select('qt_id,qt_code,qt_type')
                ->where('qt_delete_flag', 0)
                ->orderBy('qt_id', 'desc')
                ->findAll();
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'quotes' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'quotes' => []
                ];
                return $this->fail($response, 409);
            }
        }
    }


    public function getJobNumbers()
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

            $marjobmodel = new MaraghiJobcardModel();


            $jobno = $marjobmodel->select("job_no,customer_no,phone")
                ->join('cust_data_laabs', 'cust_data_laabs.customer_code =customer_no', 'left')
                ->orderby('cust_job_data_laabs.created_on', 'desc')->limit(1000)->find();

            if ($jobno) {
                $response = [
                    'ret_data' => 'success',
                    'jobno' => $jobno
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'jobno' => []
                ];
                return $this->fail($response, 409);
            }
        }
    }

    public function createQuotation()
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
            return $this->fail("Invalid user", 400);
        }

        $phone = $this->request->getVar('phone');
        $ph = substr($phone, -9);
        $patern = $ph;

        // Begin transaction
        $this->db->transStart();

        try {
            // Check for existing customer
            $cust_mastr_model = new CustomerMasterModel();
            $cust_id = 0;
            $resC = $cust_mastr_model->where('RIGHT(cust_phone,9)', $patern)->first();

            if ($resC) {
                $resC['cust_address'] = $resC['cust_address'] == "" ? "Nil" : $resC['cust_address'];
                $merge = [
                    'cus_id' =>  $resC['cus_id'],
                    'name' => $resC['cust_name'],
                    'address' => $resC['cust_address'],
                ];
            } else {
                // Handle new customer or fetch from external source
                $maraghi_cust_model = new MaragiCustomerModel();
                $maraghi_data = $maraghi_cust_model->where('RIGHT(phone,9)', $patern)
                    ->join('customer_type', 'customer_type.cst_code = customer_type')
                    ->join('country_master', 'country_master.country_code = country')
                    ->select('customer_code,cst_id,customer_title,customer_name,addr1,city,country_master.id,phone')
                    ->first();

                if ($maraghi_data) {
                    $custData = [
                        'cust_type' => $maraghi_data['cst_id'],
                        'name' => $maraghi_data['customer_name'],
                        'cust_salutation' => $maraghi_data['customer_title'],
                        'cust_address' => $maraghi_data['addr1'],
                        'cust_emirates' => $maraghi_data['city'],
                        'cust_city' => $maraghi_data['city'],
                        'cust_country' => $maraghi_data['id'],
                        'cust_phone' =>  $maraghi_data['phone'],
                        'cust_alternate_no' => $maraghi_data['phone'],
                        'cust_alm_code' => $maraghi_data['customer_code'],
                        'cust_source' => 0
                    ];
                    $ins_id = $cust_mastr_model->insert($custData);
                    $custId = [
                        'cus_id' =>  $ins_id,
                        'name' => $maraghi_data['customer_name'],
                        'address' => $maraghi_data['addr1']
                    ];
                } else {
                    $custData = [
                        'cust_name' => $this->request->getVar('cust_name'),
                        'cust_phone' => $this->request->getVar('phone'),
                        'cust_alternate_no' => $this->request->getVar('phone'),
                        'cust_source' => $this->request->getVar('source')
                    ];
                    $ins_id = $cust_mastr_model->insert($custData);
                    $custId = [
                        'cus_id' =>  $ins_id,
                        'name' => $this->request->getVar('cust_name'),
                        'address' => $this->request->getVar('cust_name')
                    ];
                }
                $merge = array_merge($custData, $custId);
            }

            // Prepare lead data
            $leadmodel = new LeadModel();
            $quotelogmodel = new LeadQuoteLogModel();

            $data = [
                'phone' => $this->request->getVar('phone'),
                'status_id' => 1,
                'source_id' =>  $this->request->getVar('source'),
                'lead_social_media_source' => $this->request->getVar('social_media_source') ?: '0',
                'lead_social_media_mapping' => $this->request->getVar('social_media_camp') ?: '0',
                'purpose_id' => 3,  // Service Request/Quotation
                'lang_id' => 1,
                'cus_id' =>  $cust_id,
                'lead_note' => $this->request->getVar('customer_note'),
                'register_number' => $this->request->getVar('reg_no'),
                'vehicle_model' => $this->request->getVar('vehicle_model'),
                'assigned' => $this->request->getVar('assigned_to'),
                'lead_createdby' =>  $tokendata['uid'],
                'lead_createdon' => date("Y-m-d H:i:s"),
                'lead_updatedon' => date("Y-m-d H:i:s"),
                'lead_creted_date' => date("Y-m-d H:i:s"),
            ];

            if (isset($merge)) {
                $data = array_merge($data, $merge);
            }

            // Generate a unique lead code
            $builder = $this->db->table('sequence_data');
            $builder->selectMax('current_seq');
            $query = $builder->get();
            $row_lead = $query->getRow();
            $seqvalfinallead = $row_lead->current_seq;
            $code = sprintf("ALMLD-%04d", $row_lead->current_seq);
            $insertLeadCode = ['lead_code' => $code];
            $data = array_merge($data, $insertLeadCode);

            // Check for existing active lead
            $activeLead = $leadmodel->where('RIGHT(phone,9)', $ph)
                ->where('status_id', 1)
                ->first();

            if ($activeLead) {
                // If there's already an active lead, update it
                $leadmodel->update($activeLead['lead_id'], $data);
                $lead_id = $activeLead['lead_id'];
            } else {
                // Insert new lead
                $lead_id = $leadmodel->insert($data);

                // Increment sequence
                $builder->set('current_seq', ++$seqvalfinallead);
                $builder->update();
            }

            // Lead activity log
            $acmodel = new LeadActivityModel();
            $acdata = [
                'lac_activity' => 'Created Lead ' . $code,
                'lac_activity_by' => $tokendata['uid'],
                'lac_lead_id' => $lead_id,
            ];
            $acmodel->insert($acdata);

            // Quote log handling
            $existingQuoteLog = $quotelogmodel->where('lql_lead_id', $lead_id)->first();

            if (!$existingQuoteLog) {
                $quotedata = [
                    'lql_lead_id' => $lead_id,
                    'lql_type' => 0,
                    'lql_source' => 1,
                    'lql_note' =>  $this->request->getVar('customer_note'),
                    'lql_created_by' => $tokendata['uid'],
                    'lql_activity' => "Quotation Requested By Customer",
                    'lql_created_on' => date("Y-m-d H:i:s"),
                ];
                $quotelogmodel->insert($quotedata);
            } else {
                return $this->respond(['ret_data' => 'Quote already requested'], 200);
            }

            // Commit transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception("Transaction Failed");
            }

            return $this->respond(['ret_data' => 'success'], 200);
        } catch (\Exception $e) {
            // Rollback transaction in case of error
            $this->db->transRollback();
            return $this->respond(['ret_data' => 'fail', 'error' => $e->getMessage()], 500);
        }
    }

    // public function createQuotation()
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

    //         $leadmodel = new LeadModel();
    //         $quotelogmodel = new LeadQuoteLogModel();
    //         $cust_mastr_model = new CustomerMasterModel();
    //         $maraghi_cust_model = new MaragiCustomerModel();
    //         $acmodel = new LeadActivityModel();


    //         $phone = $this->request->getVar('phone');
    //         $ph = substr($phone, -9);
    //         $patern = $ph;
    //         $builder = $this->db->table('sequence_data');
    //         $builder->selectMax('current_seq');
    //         $query = $builder->get();
    //         $row_lead = $query->getRow();
    //         $code = $row_lead->current_seq;
    //         $seqvalfinallead = $row_lead->current_seq;

    //         if (strlen($row_lead->current_seq) == 1) {
    //             $code = "ALMLD-000" . $row_lead->current_seq;
    //         } else if (strlen($row_lead->current_seq) == 2) {
    //             $code = "ALMLD-00" . $row_lead->current_seq;
    //         } else if (strlen($row_lead->current_seq) == 3) {
    //             $code = "ALMLD-0" . $row_lead->current_seq;
    //         } else {
    //             $code = "ALMLD-" . $row_lead->current_seq;
    //         }
    //         $cust_id = 0;
    //         $social_Media_Source = $this->request->getVar('social_media_source') ? $this->request->getVar('social_media_source') : '0';
    //         $smc_id = $this->request->getVar('social_media_camp') ? $this->request->getVar('social_media_camp') : '0';

    //         $data = [
    //             //'lead_code' => $code,
    //             'phone' => $this->request->getVar('phone'),
    //             'status_id' => 1,
    //             'source_id' =>  $this->request->getVar('source'),  // Direct Lead 
    //             'lead_social_media_source' => $social_Media_Source,
    //             'lead_social_media_mapping' => $smc_id,
    //             'purpose_id' => 3,  // Service Request/Quotation
    //             'lang_id' => 1,
    //             'cus_id' =>  $cust_id,
    //             'lead_note' => $this->request->getVar('customer_note'),
    //             'register_number' => $this->request->getVar('reg_no'),
    //             'vehicle_model' => $this->request->getVar('vehicle_model'),
    //             'assigned' => $this->request->getVar('assigned_to'),
    //             'lead_createdby' =>  $tokendata['uid'],
    //             'lead_createdon' => date("Y-m-d H:i:s"),
    //             'lead_updatedon' => date("Y-m-d H:i:s"),
    //             'lead_creted_date' => date("Y-m-d H:i:s"),

    //         ];
    //         $resC = $cust_mastr_model->where('RIGHT(cust_phone,9)', $patern)->first();
    //         if ($resC) {
    //             $resC['cust_address'] = $resC['cust_address'] == "" ? "Nil" : $resC['cust_address'];
    //             // return $this->respond($resC['cust_address']==null?"hh":"nn", 200);
    //             $merge = [
    //                 'cus_id' =>  $resC['cus_id'],
    //                 'name' => $resC['cust_name'],
    //                 'address' => $resC['cust_address'],
    //             ];
    //             $data = array_merge($data, $merge);
    //         } else {
    //             $maraghi_data = $maraghi_cust_model->where('RIGHT(phone,9)', $patern)
    //                 ->join('customer_type', 'customer_type.cst_code = customer_type')
    //                 ->join('country_master', 'country_master.country_code = country')
    //                 ->select('customer_code,cst_id,customer_title,customer_name,addr1,city,country_master.id,phone')
    //                 ->first();
    //             if ($maraghi_data) {
    //                 $maraghi_data = $maraghi_cust_model->where('RIGHT(phone,9)', $patern)
    //                     ->join('customer_type', 'customer_type.cst_code = customer_type')
    //                     ->join('country_master', 'country_master.country_code = country')
    //                     ->select('customer_code,cst_id,customer_title,customer_name,addr1,city,country_master.id,phone')
    //                     ->first();
    //                 if ($maraghi_data) {
    //                     $custData = [
    //                         'cust_type' => $maraghi_data['cst_id'],
    //                         'name' => $maraghi_data['customer_name'],
    //                         'cust_salutation' => $maraghi_data['customer_title'],
    //                         'cust_address' => $maraghi_data['addr1'],
    //                         'cust_emirates' => $maraghi_data['city'],
    //                         'cust_city' => $maraghi_data['city'],
    //                         'cust_country' => $maraghi_data['id'],
    //                         'cust_phone' =>  $maraghi_data['phone'],
    //                         'cust_alternate_no' => $maraghi_data['phone'],
    //                         'cust_alm_code' => $maraghi_data['customer_code'],
    //                     ];
    //                     $ins_id = $cust_mastr_model->insert($custData);
    //                     $custId = [
    //                         'cus_id' =>  $ins_id,
    //                         'name' => $maraghi_data['customer_name'],
    //                         'address' => $maraghi_data['addr1']
    //                     ];
    //                     $data = array_merge($data, $custId);
    //                 }
    //             } else {
    //                 $custData = [
    //                     'cust_name' => $this->request->getVar('cust_name'),
    //                     'cust_phone' => $this->request->getVar('phone'),
    //                     'cust_alternate_no' => $this->request->getVar('phone'),
    //                 ];
    //                 $ins_id = $cust_mastr_model->insert($custData);
    //                 $custId = [
    //                     'cus_id' =>  $ins_id,
    //                     'name' => $this->request->getVar('cust_name'),
    //                     'cust_phone' => $this->request->getVar('phone'),
    //                 ];
    //                 $data = array_merge($data, $custId);
    //             }
    //         }

    //         $this->db->transStart();

    //         $activeLead_id = $leadmodel->where('RIGHT(phone,9)', $ph)->select('lead_id')
    //             ->where('status_id', 1)->first();
    //         $existingQuoteLog = $quotelogmodel->where('lql_lead_id', $activeLead_id)->first();

    //         if ($existingQuoteLog) {
    //             return $this->respond(['ret_data' => 'Quote already requested'], 200);
    //         }

    //         if ($activeLead_id) {
    //             $leadmodel->where('lead_id', $activeLead_id)->set($data)->update();
    //             $lead_id = $leadmodel->where('lead_id', $activeLead_id)->select('lead_id')->first();
    //         } else {
    //             $insertLeadCode = [
    //                 'lead_code' => $code,
    //             ];
    //             $data = array_merge($data, $insertLeadCode);

    //             $lead_id = $leadmodel->insert($data);
    //             $builder = $this->db->table('sequence_data');
    //             $builder->set('current_seq', ++$seqvalfinallead);
    //             $builder->update();
    //         }

    //         if ($activeLead_id) {
    //         } else {
    //             $acdata = [
    //                 'lac_activity' => 'Created Lead ' . $code,
    //                 'lac_activity_by' => $tokendata['uid'],
    //                 'lac_lead_id' => $lead_id,
    //             ];

    //             $acmodel->insert($acdata);  //Lead Activity Log
    //             $statusdata = [
    //                 'lql_delete_flag' => 1
    //             ];
    //             $quote_delete = $quotelogmodel->where('lql_lead_id', $lead_id)->set($statusdata)->update();

    //             if ($quote_delete) {
    //                 $quotedata = [
    //                     'lql_lead_id' => $lead_id,
    //                     'lql_type' => 0,
    //                     'lql_source' => 1,
    //                     'lql_note' =>  $this->request->getVar('customer_note'),
    //                     'lql_created_by' => $tokendata['uid'],
    //                     'lql_activity' => "Quotation Requested By Customer",
    //                     'lql_created_on' => date("Y-m-d H:i:s"),
    //                 ];
    //                 $Quoteentry = $quotelogmodel->insert($quotedata);
    //             }
    //         }
    //     }

    //     if ($this->db->transStatus() === false) {
    //         $this->db->transRollback();
    //         $data['ret_data'] = "fail";
    //         return $this->respond($data, 200);
    //     } else {
    //         $this->db->transCommit();
    //         $data = [
    //             'ret_data' => 'success',
    //         ];
    //         return $this->respond($data, 200);
    //     }
    // }

    // public function getleadCallLogs()
    // {
    //     $log = new LeadCallLogModel();
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

    //         $user_id = $this->request->getVar('user_id');
    //         $call_id = $this->request->getVar('call_id');


    //         $calls = $log->select("*")
    //             ->where('lcl_createdby', $user_id)
    //             ->whereIn('ystar_call_id', $call_id)
    //             ->findAll();

    //         if (sizeof($calls) > 0) {

    //             $response = [
    //                 'ret_data' => 'success',
    //                 'calls' => $calls,
    //             ];
    //         } else {
    //             $response = [
    //                 'ret_data' => 'success',
    //                 'calls' => [],
    //             ];
    //         }
    //         return $this->respond($response, 200);
    //     }
    // }


    public function getQuotations()
    {
        $log = new LeadCallLogModel();
        $quotelogmodel = new LeadQuoteLogModel();
        $model = new LeadModel();
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
            $purpose = $this->request->getVar('purpose');
            $start_day = $this->request->getVar('start_day');
            $end_day = $this->request->getVar('end_day');
            $us_id  = $this->request->getVar('us_id');
            $quote_type  = $this->request->getVar('type');
            $status = $this->request->getVar('status');


            if ($quote_type == "0") {
                // $ngcalls = $log->whereIn('lcl_pupose_id', $purpose)
                //     // ->where('lcl_created_on >=', $start_day)
                //     // ->where('lcl_created_on <=', $end_day)
                //     ->select('lcl_pupose_id,lcl_purpose_note,lcl_call_to,lcl_created_on,lcl_createdby,
                // lcl_phone as call_from,lcl_lead_id,lead_code,name,phone,status_id,lead_note,
                // call_purposes.call_purpose,call_purposes.cp_id,us_firstname,ystar_call_id,cust_name,assigned,lql_status,lql_status,lql_type')
                //     ->join('leads', 'leads.lead_id =lead_call_log.lcl_lead_id', 'left')
                //     ->join('users', 'users.us_id =leads.assigned', 'left')   // finding lead created user
                //     ->join('customer_master', 'customer_master.cust_phone =lead_call_log.lcl_phone', 'left') //added just for fetch customer names
                //     ->join('call_purposes', 'call_purposes.cp_id =lead_call_log.lcl_pupose_id', 'left')
                //     ->join('lead_quote_log', 'lead_quote_log.lql_lead_id =lead_call_log.lcl_lead_id', 'left')
                //     ->where('lql_delete_flag', 0)
                //     ->findAll();

                $ngcalls = $model->whereIn('purpose_id', $purpose)
                    ->select('purpose_id,lead_note,lcl_pupose_id,lcl_purpose_note,lcl_call_to,
                lcl_created_on,lcl_createdby,lcl_phone as call_from,lcl_lead_id,lead_code,name,phone,status_id,lead_createdon,
                call_purposes.call_purpose,call_purposes.cp_id,us_firstname,ystar_call_id,cust_name,assigned,lql_status,lead_id,
                lql_status,lql_type,ld_src as lead_source,lead_createdby,ld_verify_flag')
                    ->join('lead_call_log', 'lead_call_log.lcl_lead_id=leads.lead_id', 'left')
                    ->join('users', 'users.us_id =leads.assigned', 'left')   // finding lead created user
                    ->join('customer_master', 'customer_master.cust_phone =phone', 'left') //added just for fetch customer names
                    ->join('lead_source', 'lead_source.ld_src_id = leads.source_id', 'left')
                    ->join('call_purposes', 'call_purposes.cp_id =purpose_id', 'left')
                    ->join('lead_quote_log', 'lead_quote_log.lql_lead_id =lead_id', 'left')
                    // ->where('DATE(lead_createdon)>=', $start_day)
                    // ->where('DATE(lead_createdon)<=',  $end_day)
                    ->where('lql_delete_flag', 0)
                    ->orderby('lead_id', 'desc')
                    //->groupby('lead_id');
                    ->groupby('lead_code');
                // ->findAll();

                if (!empty($start_day)) {
                    $ngcalls->where('DATE(lead_createdon) >=', $start_day);
                }

                if (!empty($end_day)) {
                    $ngcalls->where('DATE(lead_createdon) <=', $end_day);
                }
                if (!empty($status)) {
                    $ngcalls->whereIn('status_id', $status);
                }


                $ngcalls = $ngcalls->findAll();

                $index = 0;
                foreach ($ngcalls as $leads) {
                    $countQuoteProvided = $quotelogmodel->where("lql_lead_id", $leads['lcl_lead_id'])
                        ->where('lql_status', 1)->countAllResults();
                    // $Quotestatus = $quotelogmodel->where("lql_lead_id", $leads['lcl_lead_id'])
                    //     ->where('lql_delete_flag', 0)->select("lql_status")->first();
                    // $Quotetype = $quotelogmodel->where("lql_lead_id", $leads['lcl_lead_id'])
                    //     ->where('lql_delete_flag', 0)->select("lql_type")->first();
                    // $Quotenote= $quotelogmodel->where("lql_lead_id", $leads['lcl_lead_id'])
                    //     ->where('lql_delete_flag', 0)->select("lql_type")->first();
                    // $quotelogmodel->where([
                    //     'lql_lead_id' =>$leads['lcl_lead_id'],
                    //     'lql_delete_flag' => 0,
                    //     'lql_status !=' => 0
                    // ])->countAllResults();
                    // $ngcalls[$index]["lql_status"] = $Quotestatus['lql_status'];
                    // $ngcalls[$index]["lql_type"] = $Quotetype['lql_type'];
                    $ngcalls[$index]["quote_count"] = $countQuoteProvided > 0 ? $countQuoteProvided : 0;
                    $index++;
                }
            } else {
                $ngcalls = $model->whereIn('purpose_id', $purpose)->where('assigned', $us_id)
                    ->select('purpose_id,lead_note,lcl_pupose_id,lcl_purpose_note,lcl_call_to,lead_createdon,
                lcl_created_on,lcl_createdby,lcl_phone as call_from,lcl_lead_id,lead_code,name,phone,status_id,lead_id,
                call_purposes.call_purpose,call_purposes.cp_id,us_firstname,ystar_call_id,cust_name,assigned,lql_status,
                lql_status,lql_type,ld_src as lead_source,lead_createdby')
                    ->join('lead_call_log', 'lead_call_log.lcl_lead_id=leads.lead_id', 'left')
                    ->join('users', 'users.us_id =leads.assigned', 'left')   // finding lead created user
                    ->join('customer_master', 'customer_master.cust_phone =phone', 'left') //added just for fetch customer names
                    ->join('lead_source', 'lead_source.ld_src_id = leads.source_id', 'left')
                    ->join('call_purposes', 'call_purposes.cp_id =purpose_id', 'left')
                    ->join('lead_quote_log', 'lead_quote_log.lql_lead_id =lead_id', 'left')
                    // ->where('DATE(lead_createdon)>=', $start_day)
                    // ->where('DATE(lead_createdon)<=',  $end_day)
                    ->where('lql_delete_flag', 0)
                    ->orderby('lead_id', 'desc')
                    // ->groupby('lead_id');
                    ->groupby('lead_code');


                if (!empty($start_day)) {
                    $ngcalls->where('DATE(lead_createdon) >=', $start_day);
                }

                if (!empty($end_day)) {
                    $ngcalls->where('DATE(lead_createdon) <=', $end_day);
                }
                if (!empty($status)) {
                    $ngcalls->whereIn('status_id', $status);
                }

                $ngcalls = $ngcalls->findAll();
                //     $ngcalls = $log->whereIn('lcl_pupose_id', $purpose)
                //         ->where('lcl_created_on >=', $start_day)
                //         ->where('lcl_created_on <=', $end_day)

                //         ->select('lcl_pupose_id,lcl_purpose_note,lcl_call_to,lcl_created_on,lcl_createdby,
                // lcl_phone as call_from,lcl_lead_id,lead_code,name,phone,status_id,lead_note,
                // call_purposes.call_purpose,call_purposes.cp_id,us_firstname,ystar_call_id,cust_name,assigned')
                //         ->join('leads', 'leads.lead_id =lead_call_log.lcl_lead_id', 'left')
                //         ->join('users', 'users.us_id =leads.assigned', 'left')   // finding lead created user
                //         ->join('customer_master', 'customer_master.cust_phone =lead_call_log.lcl_phone', 'left') //added just for fetch customer names
                //         ->join('call_purposes', 'call_purposes.cp_id =lead_call_log.lcl_pupose_id', 'left')
                //         ->findAll();

                $index = 0;
                foreach ($ngcalls as $leads) {
                    $countQuoteProvided = $quotelogmodel->where("lql_lead_id", $leads['lead_id'])->where('lql_status', 1)
                        ->countAllResults();

                    $Quotestatus = $quotelogmodel->where("lql_lead_id", $leads['lead_id'])
                        ->where('lql_delete_flag', 0)->select("lql_status")->first();
                    $Quotetype = $quotelogmodel->where("lql_lead_id", $leads['lead_id'])
                        ->where('lql_delete_flag', 0)->select("lql_type")->first();


                    // $quotelogmodel->where([
                    //     'lql_lead_id' =>$leads['lcl_lead_id'],
                    //     'lql_delete_flag' => 0,
                    //     'lql_status !=' => 0
                    // ])->countAllResults();
                    $ngcalls[$index]["lql_status"] = $Quotestatus['lql_status'];
                    $ngcalls[$index]["lql_type"] = $Quotetype['lql_type'];
                    $ngcalls[$index]["quote_count"] = $countQuoteProvided > 0 ? $countQuoteProvided : 0;
                    $index++;
                }
            }



            if (sizeof($ngcalls) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'calls' => $ngcalls,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getLeadActivityLog()
    {

        $custmastermodel = new CustomerMasterModel();
        $logmodel = new LeadCallLogModel();
        $leadAcModel = new LeadActivityModel();
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

            $leadid = $this->request->getVar('id');
            $lead_log = $leadAcModel->where('lac_lead_id', $leadid)
                ->join('users', 'users.us_id =lac_activity_by', 'left')
                ->select('us_firstname,lac_activity,lac_activity_by,lac_created_on,lac_lead_id,lac_id')
                ->orderBy('lac_id', 'desc')
                ->findAll();
            $leadNotAnsCount =  $leadAcModel->where([
                'lac_lead_id' => $leadid,
                'lac_na_flag !=' => 0
            ])->countAllResults();



            if ($lead_log) {
                $response = [
                    'ret_data' => 'success',
                    'leadlog' => $lead_log,
                    'leadNotAnsCount' => $leadNotAnsCount,
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'leadlog' => []
                ];
            }

            return $this->respond($response, 200);
        }
    }

    public function leadUpdateById()
    {
        $leadmodel = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $leadAcModel = new LeadActivityModel();
        $log = new LeadCallLogModel();
        $ApptMaster = new AppointmentMasterModel();
        $Appoint = new AppointmentModel();
        $Appointmentlog = new AppointmentLogModel();

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
            // $rules = [
            //     'lead_id' => 'required',
            // ];
            // if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $new_status = $this->request->getVar('new_status');
            $assigned = $this->request->getVar('new_assigned');
            $reason = $this->request->getVar('cancelReason');

            $leadactivitydata = [
                'lac_activity_by' => $tokendata['uid'],
                'lac_lead_id' => $this->request->getVar('lead_id'),
                //  'lac_created_on' =>  date("Y-m-d H:i:s"),

            ];


            $lead_data = [

                'lead_note' => $this->request->getVar('Remarks'),
                'lead_updatedby' => $tokendata['uid'],
                'lead_updatedon' => date("Y-m-d H:i:s"),
            ];


            if ($new_status == "1") {

                $statusdata = [
                    'lac_na_flag' => 0
                ];
                $lead_activity_flag = $leadAcModel->where('lac_lead_id', $this->request->getVar('lead_id'))->set($statusdata)->update();

                if ($lead_activity_flag) {
                    $temp_data = [
                        'lac_activity' => "Updated Lead To Appointment",
                    ];
                    $leadactivitydata = array_merge($leadactivitydata, $temp_data);
                }
                $lead_temp_data = [
                    'register_number' => $this->request->getVar('reg_no'),
                    'vehicle_model' => $this->request->getVar('vehicle_model'),
                    'assigned' =>  $assigned,
                    'ld_appoint_date' => $this->request->getVar('dateField'),
                    'ld_appoint_time' => $this->request->getVar('appTime'),
                ];
                $lead_data = array_merge($lead_data, $lead_temp_data);

                $lead_call_log_data = [
                    //'lcl_time'=>date("Y-m-d H:i:s"),
                    'lcl_lead_id' => $this->request->getVar('lead_id'),
                    'lcl_pupose_id' => 1,
                    'lcl_purpose_note' => $this->request->getVar('Remarks'),
                    'lcl_createdby' => $tokendata['uid'],
                    'lcl_created_on' => date("Y-m-d H:i:s"),
                    'lcl_call_type' => 2,
                    // 'lcl_call_to' =>  $this->request->getVar('phone'),
                    // 'lcl_phone' =>  $this->request->getVar('phone'),
                ];

                $lcl_insert = $log->insert($lead_call_log_data);

                //Appointment Tables
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
                    'apptm_lead_id' => $this->request->getVar('lead_id'),
                    'apptm_status' => '1', //Appointment Scheduled
                    'apptm_transport_service' =>  $this->request->getVar('transportation_service'),
                    'apptm_created_by' =>  $tokendata['uid'],
                    'apptm_updated_by' =>  $tokendata['uid'],
                    'apptm_type' => 5, // new type from inbound campaign to appoint
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
                        'appt_date' => $this->request->getVar('dateField'),
                        'appt_time' => $this->request->getVar('appTime'),
                        'appt_assign_to' =>  $assigned,
                        'appt_note' => $this->request->getVar('Remarks'),
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
            } else if ($new_status == "2") {
                $statusdata = [
                    'lac_na_flag' => 0
                ];
                $lead_activity_flag = $leadAcModel->where('lac_lead_id', $this->request->getVar('lead_id'))->set($statusdata)->update();

                if ($lead_activity_flag) {
                    $temp_data = [
                        'lac_activity' => 'Lead Closed due to' . $reason,
                    ];
                    $leadactivitydata = array_merge($leadactivitydata, $temp_data);
                }

                $lead_temp_data = [
                    'status_id' =>  6,
                ];
                $lead_data = array_merge($lead_data, $lead_temp_data);
            } else if ($new_status == "3") {
                $temp_data = [
                    'lac_activity' => "Customer Not Answering",
                    'lac_na_flag' => 1,
                ];
                $leadactivitydata = array_merge($leadactivitydata, $temp_data);
            } else {
                $statusdata = [
                    'lac_na_flag' => 0
                ];
                $lead_activity_flag = $leadAcModel->where('lac_lead_id', $this->request->getVar('lead_id'))->set($statusdata)->update();

                if ($lead_activity_flag) {
                    $temp_data = [
                        'lac_activity' => "Lead Closed due to Customer Not Responding",
                    ];
                    $leadactivitydata = array_merge($leadactivitydata, $temp_data);
                }

                $lead_temp_data = [
                    'status_id' =>  6,
                ];
                $lead_data = array_merge($lead_data, $lead_temp_data);
            }
            if ($lead_data) {
                $leadUpdate = $leadmodel->where('lead_id', $this->request->getVar('lead_id'))->set($lead_data)->update();
            }
            $leadactivity = $leadAcModel->insert($leadactivitydata);

            if ($leadactivity && $leadUpdate) {
                $response = [
                    'ret_data' => 'success',
                    'leadUpdate' => $leadUpdate,
                    'leadactivity' => $leadactivity,
                ];
            } else if ($leadactivity) {
                $response = [
                    'ret_data' => 'success',
                    'leadUpdate' => '0',
                    'leadactivity' => $leadactivity,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function fetchAllLeads()
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $maraghi_cust_model = new MaragiCustomerModel();


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

            $source = $this->request->getVar('source');
            $purpose = $this->request->getVar('purpose');
            $dateFrom = $this->request->getVar('dateFrom');
            $dateTo = $this->request->getVar('dateTo');


            $builder = $this->db->table('leads');
            $builder->select("lead_id,lead_code, COALESCE(NULLIF(name, ''), 'NEW') as name, phone,vehicle_model,lead_note,source_id,DATE(lead_createdon) as lead_createdon ,status_id,lead_source.ld_src,lead_status.ld_sts,users.us_firstname,ld_appoint_date,assigned,camp_name,ld_camp_id,ld_brand,us.us_firstname as created,call_purpose,purpose_id,ld_appoint_time,apptm_id,ld_verify_flag");
            $builder->join('users', 'users.us_id =assigned', 'left');
            $builder->join('users as us', 'us.us_id =lead_createdby', 'left');
            $builder->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left');
            $builder->join('lead_source', 'lead_source.ld_src_id =source_id', 'left');
            $builder->join('call_purposes', 'call_purposes.cp_id =purpose_id', 'left');
            $builder->join('campaign', 'campaign.camp_id =ld_camp_id', 'left');
            $builder->join('appointment_master', 'appointment_master.apptm_lead_id =lead_id', 'left');
            $builder->where('lead_delete_flag', 0);
            // $builder->where('DATE(lead_createdon) >=', $this->request->getVar('dateFrom'));
            // $builder->where('DATE(lead_createdon) <=', $this->request->getVar('dateTo'));
            $builder->where('status_id !=', 7);
            $builder->groupBy('lead_id');
            $builder->orderby('lead_id', 'desc');
            $builder->limit(2000);

            if (!empty($dateFrom)) {
                $builder->where('DATE(lead_createdon) >=', $dateFrom);
            }

            if (!empty($dateTo)) {
                $builder->where('DATE(lead_createdon) <=', $dateTo);
            }

            if ($source != 0) {
                $builder->where('ld_src_id', $source);
            }
            if ($purpose != 0) {
                $builder->where('purpose_id', $purpose);
            }
            $res = $builder->get()->getResultArray();

            foreach ($res as &$lead) {
                $phoneLastNineDigits = substr($lead['phone'], -9);
                $customer = $maraghi_cust_model->where('RIGHT(phone, 9)', $phoneLastNineDigits)
                    ->where('created_on <', $lead['lead_createdon'])
                    ->select('created_on, phone')
                    ->first();

                if ($customer) {
                    $lead['customer_Type'] = 'Existing';
                } else {
                    $lead['customer_Type'] = 'New';
                }
            }

            if ($res) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'lead' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'lead' => [],
                ];
                return $this->respond($response, 200);
            }
        }
    }


    public function fetchInvoicedCustomers()
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

            $maraghi_cust_model = new MaragiCustomerModel();
            $phoneNum = $this->request->getVar('num');
            $dateFrom = $this->request->getVar('dateFrom');
            $dateTo = $this->request->getVar('dateTo');


            $res = $maraghi_cust_model->distinct()
                ->select('phone, mobile')
                ->where('DATE(created_on) >=', $dateFrom)
                ->where('DATE(created_on) <=', $dateTo)
                ->findAll();



            if ($res) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'res' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'res' => [],
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function whatsappleadupdate()
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
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $purpose_id = $this->request->getVar('call_purpose');
            $assigned = $this->request->getVar('assigned_to');
            $social_Media_Source = $this->request->getVar('social_media_source') ? $this->request->getVar('social_media_source') : '0';
            $smc_id = $this->request->getVar('social_media_camp') ? $this->request->getVar('social_media_camp') : '0';
            $this->db->transStart();
            $data = [
                'lead_id' => $this->request->getVar('lead_id'),
                'name' => $this->request->getVar('customerName'),
                'lead_note' => $this->request->getVar('call_note'),
                'lang_id' => 1,
                'purpose_id' => $purpose_id,
                'register_number' => $this->request->getVar('reg_no'),
                'vehicle_model' => $this->request->getVar('vehicle_model'),
                'lead_createdby' => $tokendata['uid'],
                'lead_creted_date' => date("Y-m-d H:i:s"),
                'lead_updatedon' => date("Y-m-d H:i:s"),
            ];
            if ($purpose_id == "1") {
                $temp_data = [
                    'status_id' => 1,
                    'ld_appoint_date' => $this->request->getVar('appointment_date'),
                    'ld_appoint_time' => $this->request->getVar('appointment_time'),
                    'assigned' => $assigned,
                ];
                $data = array_merge($data, $temp_data);
                $lead_id = $model->save($data);
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
                    'apptm_lead_id' => $this->request->getVar('lead_id'),
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
                        'appt_time' => $this->request->getVar('appointment_time'),
                        'appt_assign_to' =>  $assigned,
                        'appt_note' => $this->request->getVar('call_note'),
                        'appt_created_by' => $tokendata['uid'],
                        'appt_created_on' => date("Y-m-d H:i:s"),
                    ];
                    $result1 = $Appoint->insert($Apptdata);
                    $Logdata = [
                        'applg_apptm_id' => $result,
                        'applg_note' => "Appointment Scheduled from whatsapp chat",
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
                $lead_id = $model->save($data);
            } else if ($purpose_id == "3") {
                $temp_data = [
                    'status_id' => 1,
                    'assigned' => $assigned,
                ];
                $data = array_merge($data, $temp_data);
                $lead_id = $model->save($data);
                $quotedata = [
                    'lql_lead_id' => $this->request->getVar('lead_id'),
                    'lql_type' => 0,
                    'lql_source' => 2, //from Direct Lead
                    'lql_note' =>  $this->request->getVar('call_note'),
                    'lql_created_by' => $tokendata['uid'],
                    'lql_activity' => "Quotation Requested By Customer From Whatsapp Chat",
                    'lql_created_on' => date("Y-m-d H:i:s"),
                ];
                $Quoteentry = $Quotemodel->insert($quotedata);
            } else if ($purpose_id == "7" || $purpose_id == "9" || $purpose_id == "10") {
                $temp_data = [
                    'status_id' => 6,
                ];
                $data = array_merge($data, $temp_data);
                $lead_id = $model->save($data);
            } else if ($purpose_id == "4") {
                $temp_data = [
                    'status_id' => $this->request->getVar('lead_status'),
                ];
                $data = array_merge($data, $temp_data);
                $lead_id = $model->save($data);
            } else if ($purpose_id == "6" || $purpose_id == "8") {
                $temp_data = [
                    'status_id' => 6,
                ];
                $data = array_merge($data, $temp_data);
                $lead_id = $model->save($data);
            }
            // $logdata = [
            //     'lcl_lead_id' => $this->request->getVar('lead_id'),
            //     'lcl_purpose_note' =>$this->request->getVar('call_note'),
            //     'lcl_call_to' => "",
            //     'lcl_phone' => "",
            //     'lcl_createdby' =>$tokendata['uid'],
            //     'ystar_call_id' => "",
            //     'lcl_user_id' => "",
            //     "lcl_lead_type" => 1,
            //     "lcl_call_source" => "",
            //     'lcl_pupose_id' => 10,
            //     'lcl_created_on'=> date("Y-m-d H:i:s"),
            // ];
            // $call_log = $log->insert($logdata);
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->db->transCommit();
                $leadactivitydata = [
                    'lac_activity' => 'Updated lead purpose from whatsapp chat',
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' =>  $this->request->getVar('lead_id'),
                    'lac_lead_purpose' => $purpose_id
                ];
                $leadactivity = $leadAcModel->insert($leadactivitydata);
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            }
        }
    }

    public function getWhatsappLeadsList()
    {
        $model = new LeadModel();
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

            $dateFrom = $this->request->getVar('dateFrom');
            $dateTo = $this->request->getVar('dateTo');


            $builder = $this->db->table('leads');
            $builder->select('lead_id,lead_code,name, phone,vehicle_model,lead_note,source_id,DATE(lead_createdon) as lead_createdon ,
            status_id,lead_source.ld_src,lead_status.ld_sts,users.us_firstname,ld_appoint_date,assigned,
            ld_brand,us.us_firstname as created,ld_appoint_time,apptm_id,lead_social_media_mapping,smc_code,purpose_id,call_purpose,
            smc_message,smc_name,smc_status,smc_start_date,smc_end_date,smc_source,smc_owner,CONCAT(smc_message, "(", smc_name, ")") as campaign_displayname');
            $builder->join('call_purposes', 'call_purposes.cp_id =purpose_id', 'left');
            $builder->join('users', 'users.us_id =assigned', 'left');
            $builder->join('users as us', 'us.us_id =lead_createdby', 'left');
            $builder->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left');
            $builder->join('lead_source', 'lead_source.ld_src_id =source_id', 'left');
            $builder->join('social_media_campaign', 'social_media_campaign.smc_id =lead_social_media_mapping', 'left');
            $builder->join('appointment_master', 'appointment_master.apptm_lead_id =lead_id', 'left');
            $builder->where('lead_delete_flag', 0);
            $builder->whereIn('status_id',  [8, 1, 6, 5]);    // $builder->where('status_id', 8);
            $builder->whereIn('source_id', [8, 9]);
            $builder->orderby('lead_id', 'desc');
            $builder->groupBy('lead_id');
            $builder->limit(1000);
            // $query = $builder->get();
            // $res = $query->getResultArray();

            if (!empty($dateFrom)) {
                $builder->where('DATE(lead_createdon) >=', $dateFrom);
            }

            if (!empty($dateTo)) {
                $builder->where('DATE(lead_createdon) <=', $dateTo);
            }


            $res = $builder->get()->getResultArray();

            //  $res= $model->where('lead_delete_flag', 0)->where('status_id !=', 7)->join('users','users.us_id =assigned','left')->join('lead_source','lead_source.ld_src_id =source_id','left')->join('lead_status','lead_status.ld_sts_id =status_id','left')->join('call_purposes','call_purposes.cp_id =purpose_id','left')->orderby('lead_id','desc')->select('lead_id,lead_code,name,CONCAT("*****",RIGHT(phone,4)) as phone,vehicle_model,lead_note,source_id,DATE(lead_createdon) as lead_createdon,status_id,purpose_id,lead_source.ld_src,lead_status.ld_sts,users.us_firstname,call_purpose')->findAll();


            if ($res) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'whatsappLeadList' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        }
    }


    public function getLeadsList()
    {
        $model = new LeadModel();
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

            $dateFrom = $this->request->getVar('dateFrom');
            $dateTo = $this->request->getVar('dateTo');
            $source = $this->request->getVar('source');
            $leadSource = $this->request->getVar('leadSource');

            $date = new DateTime();
            $currentDate = $date->format('Y-m-d');



            if ($source == '0') {
                // Leads Wise Data

                //     $subquery = "(SELECT MAX(job_no) AS latest_job_no, customer_no 
                //   FROM cust_job_data_laabs 
                //   GROUP BY customer_no) AS latest_jobs";


                //     $builder = $this->db->table('leads');
                //     $builder->select('lead_id, lead_code, name, leads.phone, vehicle_model, lead_note, source_id, DATE(lead_createdon) as lead_createdon,
                //     status_id, lead_source.ld_src, lead_status.ld_sts, users.us_firstname, ld_appoint_date, assigned,
                //     ld_brand, us.us_firstname as created, ld_appoint_time, purpose_id, call_purpose ,cust_data_laabs.customer_code');
                //     $builder->join('call_purposes', 'call_purposes.cp_id = purpose_id', 'left');
                //     $builder->join('users', 'users.us_id = assigned', 'left');
                //     $builder->join('users as us', 'us.us_id = lead_createdby', 'left');
                //     $builder->join('lead_status', 'lead_status.ld_sts_id = status_id', 'left');
                //     $builder->join('lead_source', 'lead_source.ld_src_id = source_id', 'left');
                //     $builder->join('cust_data_laabs', 'SUBSTRING(cust_data_laabs.phone, -9) = SUBSTRING(leads.phone, -9)', 'left');
                //     $builder->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no = cust_data_laabs.customer_code', 'left');
                //     $builder->join($subquery, 'latest_jobs.latest_job_no = cust_job_data_laabs.job_no AND latest_jobs.customer_no = cust_job_data_laabs.customer_no', 'left'); // Join with the subquery
                //     $builder->groupStart(); // Start a grouped condition
                //     $builder->where('cust_job_data_laabs.job_open_date IS NULL');
                //     $builder->orWhere('DATE(STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d-%b-%y")) <', $dateFrom);
                //     $builder->orWhere('DATE(STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d-%b-%y")) >', $currentDate);
                //     $builder->groupEnd(); // End the grouped condition
                //     $builder->where('lead_delete_flag', 0);
                //     $builder->whereIn('status_id', [8, 1]);
                //     $builder->orderBy('lead_id', 'desc');
                //     $builder->groupBy('leads.phone');
                //     $builder->limit(2000);


                $currentMonth = date('Y-m');

                // Subquery to get the latest alm_wb_camp_msg_id for each phone number
                $subQuery = $this->db->table('alm_whatsapp_camp_cus_messages')
                    ->select('MAX(alm_wb_camp_msg_id) as latest_msg_id, SUBSTRING(alm_wb_camp_msg_cus_phone, -9) as phone_last_9')
                    ->groupBy('phone_last_9')
                    ->getCompiledSelect(); // Convert the subquery to a string

                // Main query
                $builder = $this->db->table('leads');
                $builder->select('lead_id, lead_code, name, leads.phone, vehicle_model, lead_note, source_id, DATE(lead_createdon) as lead_createdon,
                status_id, lead_source.ld_src, lead_status.ld_sts, users.us_firstname, ld_appoint_date, assigned,
                ld_brand, us.us_firstname as created, ld_appoint_time, purpose_id, call_purpose ,cust_data_laabs.customer_code, alm_whatsapp_camp_cus_messages.*');
                $builder->join('call_purposes', 'call_purposes.cp_id = purpose_id', 'left');
                $builder->join('users', 'users.us_id = assigned', 'left');
                $builder->join('users as us', 'us.us_id = lead_createdby', 'left');
                $builder->join('lead_status', 'lead_status.ld_sts_id = status_id', 'left');
                $builder->join('lead_source', 'lead_source.ld_src_id = source_id', 'left');
                $builder->join('cust_data_laabs', 'SUBSTRING(cust_data_laabs.phone, -9) = SUBSTRING(leads.phone, -9)', 'left');
                $builder->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no = cust_data_laabs.customer_code', 'left');

                // Join the subquery as 'latest_msg'
                $builder->join("($subQuery) as latest_msg", 'SUBSTRING(leads.phone, -9) = latest_msg.phone_last_9', 'left');

                // Join the alm_whatsapp_camp_cus_messages using the latest message ID
                $builder->join('alm_whatsapp_camp_cus_messages', 'alm_whatsapp_camp_cus_messages.alm_wb_camp_msg_id = latest_msg.latest_msg_id', 'left');

                $builder->where('cust_job_data_laabs.customer_no IS NULL');  // Exclude leads with customer_no in cust_job_data_laabs
                $builder->where('lead_delete_flag', 0);
                $builder->whereIn('status_id', [8, 1]);

                $builder->groupStart();
                $builder->where("DATE_FORMAT(alm_whatsapp_camp_cus_messages.alm_wb_camp_msg_created_on, '%Y-%m') != '$currentMonth'", NULL, FALSE);
                $builder->orWhere('alm_whatsapp_camp_cus_messages.alm_wb_camp_msg_id IS NULL');
                $builder->groupEnd();

                $builder->orderBy('lead_id', 'desc');
                $builder->groupBy('leads.phone');
                $builder->limit(2000);



                if (!empty($leadSource)) {
                    $builder->whereIn('source_id', $leadSource);
                }
                if (!empty($dateFrom)) {
                    $builder->where('DATE(lead_createdon) >=', $dateFrom);
                }
                if (!empty($dateTo)) {
                    $builder->where('DATE(lead_createdon) <=', $dateTo);
                }
                $res = $builder->get()->getResultArray();
            } else {

                $builder = $this->db->table('cust_job_data_laabs');
                $dateFrom = $this->request->getVar('dateFrom');
                $dateTo = $this->request->getVar('dateTo');
                $currentMonth = date('Y-m');

                $subQuery_latest_message = $this->db->table('alm_whatsapp_camp_cus_messages')
                    ->select('MAX(alm_wb_camp_msg_id) as latest_msg_id, SUBSTRING(cust_data_laabs.phone, -9) as phone_last_9')
                    ->join('cust_data_laabs', 'SUBSTRING(cust_data_laabs.phone, -9) = SUBSTRING(alm_whatsapp_camp_cus_messages.alm_wb_camp_msg_cus_phone, -9)', 'left')
                    ->groupBy('phone_last_9')
                    ->getCompiledSelect();


                $subQuery = $this->db->table('cust_job_data_laabs')
                    ->select('customer_no')
                    ->where("STR_TO_DATE(invoice_date, '%d-%b-%y') > STR_TO_DATE('$dateTo', '%Y-%m-%d')")
                    ->groupBy('customer_no')
                    ->get()
                    ->getResultArray();

                $excludedCustomers = array_column($subQuery, 'customer_no');

                $query = $builder
                    ->select('cust_job_data_laabs.*, cust_data_laabs.*')
                    ->join('cust_data_laabs', 'cust_data_laabs.customer_code = cust_job_data_laabs.customer_no', 'left')
                    ->join("($subQuery_latest_message) as latest_msg", 'SUBSTRING(cust_data_laabs.phone, -9) = latest_msg.phone_last_9', 'left')
                    ->join('alm_whatsapp_camp_cus_messages', 'alm_whatsapp_camp_cus_messages.alm_wb_camp_msg_id = latest_msg.latest_msg_id', 'left')
                    // ->join('alm_whatsapp_camp_cus_messages', 'SUBSTRING(alm_whatsapp_camp_cus_messages.alm_wb_camp_msg_cus_phone, -9) = SUBSTRING(cust_data_laabs.phone, -9)', 'left')
                    ->where("STR_TO_DATE(invoice_date, '%d-%b-%y') >= STR_TO_DATE('$dateFrom', '%Y-%m-%d')")
                    ->where("STR_TO_DATE(invoice_date, '%d-%b-%y') <= STR_TO_DATE('$dateTo', '%Y-%m-%d')")
                    ->whereNotIn('customer_no', $excludedCustomers)
                    ->groupStart() // Group conditions for alm_whatsapp_camp_cus_messages
                    ->where("DATE_FORMAT(alm_whatsapp_camp_cus_messages.alm_wb_camp_msg_created_on, '%Y-%m') != '$currentMonth'", NULL, FALSE) // Use the correct column name
                    ->orWhere('alm_whatsapp_camp_cus_messages.alm_wb_camp_msg_id IS NULL') // Include rows where there is no corresponding entry in alm_whatsapp_camp_cus_messages
                    ->groupEnd()
                    ->groupBy('cust_job_data_laabs.customer_no')
                    ->get();

                $res = $query->getResultArray();
            }
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'LeadList' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function fetchAllLeadsByPhone()
    {
        $model = new LeadModel();
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

            $phone = $this->request->getVar('phone');

            $res = $model
                ->where('RIGHT(phone, 9)', substr($phone, -9)) // match only last 9 digits
                ->where('lead_delete_flag', 0)
                ->where('status_id !=', 7)
                ->join('call_purposes', 'call_purposes.cp_id = purpose_id', 'left')
                ->join('lead_status', 'lead_status.ld_sts_id = status_id', 'left')
                ->join('lead_source', 'lead_source.ld_src_id = source_id', 'left')
                ->findAll();

            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'LeadList' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'LeadList' => ''
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function updateLeadVerificationFlag()
    {
        $model = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();

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

            $data = [
                'ld_verify_flag' => $this->request->getVar('ld_verify_flag'),
            ];
            $id = $this->db->escapeString($this->request->getVar('lead_id'));
            if ($model->where('lead_id', $id)->set($data)->update() === false) {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            } else {
                return $this->respond(['ret_data' => 'success'], 200);
            }
        }
    }

    public function updateCategoryOfLead()
    {
        $model = new LeadModel();
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
            $data = [
                'lead_category' => $this->request->getVar('type')
            ];
            $lead_id = $this->request->getVar('lead_id');
            $lead_ipdate = $model->update($lead_id, $data);
            if ($lead_ipdate) {
                return $this->respond(['ret_data' => 'success'], 200);
            } else {
                return $this->respond(['ret_data' => 'fail'], 200);
            }
        }
    }
}
