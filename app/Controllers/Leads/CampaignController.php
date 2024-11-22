<?php

namespace App\Controllers\Leads;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Leads\LeadModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\Leads\LeadActivityModel;
use App\Models\Leads\AppointmentMasterModel;
use App\Models\Leads\AppointmentModel;
use App\Models\Leads\AppointmentLogModel;

class CampaignController extends ResourceController
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

    public function getCampaignEnquiry()
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

            $purpose_id = $this->request->getVar('purpose_id');
            $dateFrom = $this->request->getVar('dateFrom');
            $dateTo = $this->request->getVar('dateTo');
            $status = $this->request->getVar('status');


            $builder = $this->db->table('leads');
            $builder->select('lead_id,lead_code,name, phone,vehicle_model,lead_note,source_id,DATE(lead_createdon) as lead_createddate ,lead_createdon,status_id,lead_source.ld_src,lead_status.ld_sts,users.us_firstname,ld_appoint_date,assigned,camp_name,ld_camp_id,ld_brand,us.us_firstname as created,call_purpose,purpose_id,ld_appoint_time,apptm_id,ld_sts as lead_status,ld_src as lead_source,register_number as reg_no');
            $builder->join('users', 'users.us_id =assigned', 'left');
            $builder->join('users as us', 'us.us_id =lead_createdby', 'left');
            $builder->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left');
            $builder->join('lead_source', 'lead_source.ld_src_id =source_id', 'left');
            $builder->join('call_purposes', 'call_purposes.cp_id =purpose_id', 'left');
            $builder->join('campaign', 'campaign.camp_id =ld_camp_id', 'left');
            $builder->join('appointment_master', 'appointment_master.apptm_lead_id =lead_id', 'left');
            $builder->where('lead_delete_flag', 0);
            $builder->where('status_id !=', 7);
            $builder->where('purpose_id', $purpose_id);
            // $builder->where('DATE(lead_createdon) >=', $this->request->getVar('dateFrom'));
            // $builder->where('DATE(lead_createdon) <=', $this->request->getVar('dateTo'));
            $builder->orderby('lead_id', 'desc');
            $builder->limit(1000);
            // $query = $builder->get();
            // $res = $query->getResultArray();

            if (!empty($dateFrom)) {
                $builder->where('DATE(lead_createdon) >=', $dateFrom);
            }

            if (!empty($dateTo)) {
                $builder->where('DATE(lead_createdon) <=', $dateTo);
            }

            if (!empty($status)) {
                $builder->whereIn('status_id', $status);
            }

            $res = $builder->get()->getResultArray();

            if ($res) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'CampaignList' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'CampaignList' => [],
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function getLeadCampaignDetails()
    {
        $leadmodel = new LeadModel();
        $common = new Common();
        $valid = new Validation();
        $lead_log_model = new LeadActivityModel();

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
                ->join('lead_source', 'lead_source.ld_src_id = leads.source_id', 'left')
                ->join('lead_status', 'lead_status.ld_sts_id = leads.status_id', 'left')
                ->join('customer_master', 'RIGHT(customer_master.cust_phone,8) = RIGHT (leads.phone,8) ', 'left')
                ->join('cust_data_laabs', 'RIGHT(cust_data_laabs.phone,8) = RIGHT (leads.phone,8) ', 'left')
                ->join('users', 'users.us_id = assigned', 'left')
                ->join('social_media_campaign_source', 'social_media_campaign_source.smcs_id =lead_social_media_source', 'left')
                ->join('social_media_campaign', 'social_media_campaign.smc_id =lead_social_media_mapping', 'left')
                ->select('us_firstname as camp_assigned,leads.cus_id,
            jc_status,ld_appoint_date,ld_appoint_time,ld_cust_response,ld_further_action,lead_code,
            lead_createdby,lead_createdon,lead_creted_date,lead_from,lead_id,lead_note,
            lead_updatedby,lead_updatedon,outbound_lead,leads.phone,purpose_id,register_number,
            rating,reason_to_close,source_id,status_id,vehicle_model,assigned,ld_src as lead_source,ld_sts as lead_status,cust_name as name,customer_name,smc_name,smc_message,smc_status,smcs_name')
                ->first();

            $lead_logs = $lead_log_model->where('lac_lead_id', $lead_id)
                ->findAll();

            if ($res) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'lead' => $res,
                    'lead_logs' => $lead_logs,
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'lead' => [],
                    'lead_logs' => [],
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function updateLeadCampEnq()
    {
        //LeadLogModel 

        $leadmodel = new LeadModel();
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
            $lead_id = $this->request->getVar('lead_id');
            $lead_status = $this->request->getVar('verify');
            $this->db->transStart();
            // 1-Schedule An Appointment
            // 2-Quotation Provided
            // 3-Not Answering
            // 4-Enquiry Closed

            if ($lead_status == "1") {
                $data = [
                    // 'name' => $this->request->getVar('name'),
                    // 'phone' => $this->request->getVar('phone'),
                    // 'vehicle_model' => $this->request->getVar('vehicle_model'),
                    'name' => $this->request->getVar('cust_name'),
                    'register_number' => $this->request->getVar('reg_no'),
                    'assigned' => $this->request->getVar('assigned'),
                    'lead_updatedby' => $tokendata['uid'],
                    'ld_appoint_date' => $this->request->getVar('dateField'),
                    'ld_appoint_time' => $this->request->getVar('appTime'),
                    'lead_note' => $this->request->getVar('Remarks'),
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                ];
                $leadupdate = $leadmodel->where('lead_id', $lead_id)->set($data)->update();

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
                    'apptm_type' => 5,
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
                        'appt_assign_to' =>  $this->request->getVar('assigned_to'),
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
            } else if ($lead_status == "2") {
                // Quotation provided 

                $data = [
                    // 'phone' => $this->request->getVar('call_from'),
                    'name' => $this->request->getVar('cust_name'),
                    'lead_updatedby' => $tokendata['uid'],
                    'lead_note' => $this->request->getVar('Remarks'),
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                ];
                $leadupdate = $leadmodel->where('lead_id', $lead_id)->set($data)->update();
                if ($this->request->getVar('quote_type') == '3') {
                    $phone = $this->request->getVar('phone');
                    $camp_assigned = $this->request->getVar('camp_assigned');
                    $phone_last5 = substr($phone, -5);
                    $username_first5 = substr($camp_assigned, 0, 5);
                    $WAcode = "WA_" . $phone_last5 . "_" . $username_first5;
                } else {
                    $WAcode = $this->request->getVar('quote_no');
                }
                $lead_log = [
                    'lac_activity' => 'Quotation provided ' . $WAcode,
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $lead_id,
                ];
                $lead_log_model->insert($lead_log);
            } else if ($lead_status == "3") {
                //Lead Cancelled or Rejected
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
                    'lac_activity' => 'Lead Closed due to Customer Not Responding',
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $lead_id,
                ];
                $lead_log_model->insert($lead_log);
            } else {
                //Lead Cancelled or Rejected
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
                    'lac_activity' => 'Lead Closed due to Campaign Enquiry Closed',
                    'lac_activity_by' => $tokendata['uid'],
                    'lac_lead_id' => $lead_id,
                ];
                $lead_log_model->insert($lead_log);
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
        }
    }
}
