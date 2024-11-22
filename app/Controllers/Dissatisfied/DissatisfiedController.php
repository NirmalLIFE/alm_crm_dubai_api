<?php

namespace App\Controllers\Dissatisfied;

use CodeIgniter\RESTful\ResourceController;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\Dissatisfied\DissatisfiedMasterModel;
use App\Models\Dissatisfied\DissatisfiedLogModel;
use App\Models\Leads\AppointmentMasterModel;
use App\Models\Leads\AppointmentModel;
use App\Models\Leads\AppointmentLogModel;
use App\Models\Leads\LeadModel;
use App\Models\Leads\LeadActivityModel;
use App\Models\UserActivityLog;


class DissatisfiedController extends ResourceController
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
        //
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

    public function getdisatisfiedcust()
    {
        $dissatisfiedlog = new DissatisfiedLogModel();
        $dissatisfiedmaster = new DissatisfiedMasterModel();
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

            $status = $this->request->getVar('status');
            $start_date = $this->request->getVar('dateFrom');
            $end_date = $this->request->getVar('dateTo');

            // $dis_cust = $dissatisfiedmaster->whereIn('ldm_status', $status)
            //     ->join('dissatisfied_log', 'dissatisfied_log.ldl_ldm_id =ldm_id', 'left')
            //     ->where('ldl_delete_flag !=', 1)
            //     ->where('DATE(ldm_created_on) >=',$start_date)
            //     ->where('DATE(ldm_created_on) <=',$end_date)
            //     ->join('leads', 'leads.lead_id=ldm_ldl_id', 'left')
            //     ->join('psf_master', 'psf_master.psfm_id=ldm_psf_id', 'left')
            //     ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
            //     ->join('psf_reason', 'psfr_id=ldl_response', 'left')
            //     ->join('users', 'us_id=ldm_assign', 'left')
            //     ->select('ldm_id,leads.name as lname,ldm_type,leads.phone as lphone,lead_id,ldm_created_on,ldm_status,ldm_assign,
            //     ldl_note,ldl_response,ldl_action,psf_master.psfm_id,ldm_psf_id,customer_code,lead_code,
            //     psfm_customer_code,cust_data_laabs.customer_name,cust_data_laabs.phone as cphone,psfr_name as response,us_firstname as assign')->findAll();

            $dis_cust = $dissatisfiedmaster->whereIn('ldm_status', $status)
                ->join('dissatisfied_log AS latest_log', 'latest_log.ldl_ldm_id = ldm_id AND latest_log.ldl_created_on = (SELECT MAX(ldl_created_on) FROM dissatisfied_log WHERE dissatisfied_log.ldl_ldm_id = ldm_id)', 'left')
                ->where('DATE(ldm_created_on) >=', $start_date)
                ->where('DATE(ldm_created_on) <=', $end_date)
                //->where('latest_log.ldl_delete_flag !=', 1)
                ->join('leads', 'leads.lead_id = ldm_ldl_id', 'left')
                ->join('psf_master', 'psf_master.psfm_id = ldm_psf_id', 'left')
                ->join('cust_data_laabs', 'cust_data_laabs.customer_code = psf_master.psfm_customer_code', 'left')
                ->join('psf_reason', 'psf_reason.psfr_id = ldl_response', 'left')
                ->join('users', 'users.us_id = ldm_assign', 'left')
                ->select(
                    'ldm_id,leads.name as lname, ldm_type,leads.phone as lphone,ldm_ldl_id as lead_id,
                    ldm_created_on,ldm_status,ldm_assign,ldl_note,ldl_response,ldl_action,psf_master.psfm_id,
                    ldm_psf_id,cust_data_laabs.customer_code,lead_code,psf_master.psfm_customer_code,
                    cust_data_laabs.customer_name,cust_data_laabs.phone as cphone,psf_reason.psfr_name as response,
                    users.us_firstname as assign'
                )
                ->orderBy('ldm_id', 'desc')
                ->findAll();


            // $psf_dissatisfied=$leadmodel->where('purpose_id', $status)
            // ->join('lead_dissatisfied_log', 'lead_dissatisfied_log.ldl_lead_id =lead_id', 'left')
            // ->select('*')->findAll();

            if (sizeof($dis_cust) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'cust' => $dis_cust,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function disatisfiedUpdate()
    {
        $dissatisfiedlog = new DissatisfiedLogModel();
        $dissatisfiedmaster = new DissatisfiedMasterModel();
        $ApptMaster = new AppointmentMasterModel();
        $Appoint = new AppointmentModel();
        $Appointmentlog = new AppointmentLogModel();
        $leadmodel = new LeadModel();
        $acmodel = new LeadActivityModel();
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


            $id = $this->request->getVar('dis_id');
            $leadid = $this->request->getVar('lead_id');

            $this->db->transStart();

            if ($this->request->getVar('newresponseaction') == '2' || $this->request->getVar('newresponseaction') == '3' || $this->request->getVar('newresponseaction') == '5') {
                if ($leadid) {
                    $lead_data = [
                        'lead_note' => $this->request->getVar('newlead_note'),
                        'status_id' => 6,
                        'reason_to_close' => "Closed From Dissatisfied",
                        'lead_updatedby' => $tokendata['uid'],
                        'lead_updatedon' => date("Y-m-d H:i:s"),
                        // 'ld_further_action'=>$this->request->getVar('responseaction'),
                    ];
                    if ($leadid) {
                        $lead_update = $leadmodel->where('lead_id ', $leadid)->set($lead_data)->update();
                    }
                }


                $dissatisfied_data = [
                    'ldm_status' => 4,
                    'ldm_created_by' => $tokendata['uid'],
                ];

                $dissatisfied_cust = $dissatisfiedmaster->where('ldm_id', $id)->set($dissatisfied_data)->update();

                if ($dissatisfied_cust) {
                    $ldl_response = $dissatisfiedlog->where('ldl_ldm_id', $id)->where('ldl_delete_flag !=', 1)
                        ->select("ldl_response")->first();
                    $statusdata = [
                        'ldl_delete_flag' => 1
                    ];
                    $dis_log_delete = $dissatisfiedlog->where('ldl_ldm_id', $id)->set($statusdata)->update();
                    if ($dis_log_delete) {
                        $Logdata = [
                            'ldl_ldm_id' => $id,
                            'ldl_response' => $ldl_response,
                            'ldl_note' => $this->request->getVar('newlead_note'),
                            'ldl_action' => $this->request->getVar('newresponseaction'),
                            'ldl_activity' => "Resolved or completed Dissatisfied",
                            'ldl_created_by' => $tokendata['uid'],
                        ];

                        $logentry = $dissatisfiedlog->insert($Logdata);
                    }
                }
                $data = [
                    //   'reason_to_close' => $this->request->getVar('reason'),
                    'status_id' => 6,
                    'lead_updatedby' => $tokendata['uid'],
                    'lead_updatedon' => date("Y-m-d H:i:s"),

                ];
                $leadmodel->where('lead_id', $leadid)->set($data)->update();
                if ($leadid) {
                    $acdata = [
                        // 'lac_activity' => 'Lead Closed due to ' . $this->request->getVar('reason'),
                        'lac_activity' => 'Dissatisfied Lead Closed ',
                        'lac_activity_by' => $tokendata['uid'],
                        'lac_lead_id' => $leadid,
                    ];
                    $acmodel->insert($acdata);
                    $this->insertUserLog('Lead Closed', $tokendata['uid']);
                }
            } else if ($this->request->getVar('newresponseaction') == '4') {
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
                if ($leadid) {
                    $ApptMdata = [
                        'apptm_customer_code' => $this->request->getVar('psfm_customer_code'),
                        'apptm_diss_id' => $this->request->getVar('ldm_id'),
                        'apptm_code' => $code,
                        'apptm_lead_id' => $leadid,
                        'apptm_type' => '3', // PSF revisit Appointment
                        'apptm_status' => '1', //Appointment Scheduled
                        'apptm_group' => $this->request->getVar('apptm_group'),
                        'apptm_transport_service' =>  $this->request->getVar('newtransportation_service'),
                        'apptm_created_by' =>  $tokendata['uid'],
                        'apptm_created_on' => date("Y-m-d H:i:s"),
                    ];
                } else {
                    $ApptMdata = [
                        'apptm_customer_code' => $this->request->getVar('psfm_customer_code'),
                        'apptm_diss_id' => $this->request->getVar('ldm_id'),
                        'apptm_code' => $code,
                        // 'apptm_lead_id' => $leadid,
                        'apptm_type' => '3', // PSF revisit Appointment
                        'apptm_status' => '1', //Appointment Scheduled
                        'apptm_group' => $this->request->getVar('apptm_group'),
                        'apptm_transport_service' =>  $this->request->getVar('newtransportation_service'),
                        'apptm_created_by' =>  $tokendata['uid'],
                        'apptm_created_on' => date("Y-m-d H:i:s"),
                    ];
                }


                $result = $ApptMaster->insert($ApptMdata);

                if ($result) {
                    $builder = $this->db->table('sequence_data');
                    $builder->set('appt_seq', ++$seqvalfinal);
                    $builder->update();
                    $Apptdata = [
                        'appt_apptm_id' => $result,
                        'appt_date' => $this->request->getVar('newdateField'),
                        'appt_time' => $this->request->getVar('newappTime'),
                        'appt_assign_to' => $this->request->getVar('newassigned'),
                        'appt_note' => $this->request->getVar('newlead_note'),
                        'appt_created_by' => $tokendata['uid'],
                        'appt_created_on' => date("Y-m-d H:i:s"),
                    ];

                    $result1 = $Appoint->insert($Apptdata);
                }

                $Logdata = [
                    'applg_apptm_id' => $result,
                    'applg_note' => "Revisit Appointment Scheduled From Feedback/PSF",
                    'applg_created_by' => $tokendata['uid'],
                    'applg_created_on' => date("Y-m-d H:i:s"),
                    'applg_time' => date("Y-m-d H:i:s"),
                ];

                $logentry = $Appointmentlog->insert($Logdata);

                $dissatisfied_data = [
                    'ldm_status' => 3,
                    'ldm_created_by' => $tokendata['uid'],
                ];

                $dissatisfied_cust = $dissatisfiedmaster->where('ldm_id', $id)->set($dissatisfied_data)->update();

                if ($dissatisfied_cust) {
                    $ldl_response = $dissatisfiedlog->where('ldl_ldm_id', $id)->where('ldl_delete_flag !=', 1)
                        ->select("ldl_response")->first();
                    $statusdata = [
                        'ldl_delete_flag' => 1
                    ];
                    $dis_log_delete = $dissatisfiedlog->where('ldl_ldm_id', $id)->set($statusdata)->update();
                    if ($dis_log_delete) {

                        $Logdata = [
                            'ldl_ldm_id' => $id,
                            'ldl_response' => $ldl_response,
                            'ldl_note' => $this->request->getVar('newlead_note'),
                            'ldl_action' => $this->request->getVar('newresponseaction'),
                            'ldl_activity' => "Dissatisfied Appointment Scheduled",
                            'ldl_created_by' => $tokendata['uid'],
                        ];

                        $logentry = $dissatisfiedlog->insert($Logdata);
                    }
                }
            } else {
                $dissatisfied_data = [
                    'ldm_status' => 2,
                    'ldm_created_by' => $tokendata['uid'],
                ];

                $dissatisfied_cust = $dissatisfiedmaster->where('ldm_id', $id)->set($dissatisfied_data)->update();

                if ($dissatisfied_cust) {
                    $ldl_response = $dissatisfiedlog->where('ldl_ldm_id', $id)->where('ldl_delete_flag !=', 1)
                        ->select("ldl_response")->first();
                    $statusdata = [
                        'ldl_delete_flag' => 1
                    ];
                    $dis_log_delete = $dissatisfiedlog->where('ldl_ldm_id', $id)->set($statusdata)->update();
                    if ($dis_log_delete) {
                        $Logdata = [
                            'ldl_ldm_id' => $id,
                            'ldl_response' =>  $ldl_response,
                            'ldl_note' => $this->request->getVar('newlead_note'),
                            'ldl_action' => $this->request->getVar('newresponseaction'),
                            'ldl_activity' => "Dissatisfied log Updated",
                            'ldl_created_by' => $tokendata['uid'],
                        ];

                        $logentry = $dissatisfiedlog->insert($Logdata);
                    }
                }
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->db->transCommit();
                $response = [
                    'ret_data' => 'success',
                    'cust' => $dissatisfied_data,
                ];
                return $this->respond($response, 200);
            }
            // if (sizeof($dissatisfied_data) > 0) {
            //     $response = [
            //         'ret_data' => 'success',
            //         'cust' => $dissatisfied_data,
            //     ];
            // } else {
            //     $response = [
            //         'ret_data' => 'fail',
            //     ];
            // }
            // return $this->respond($response, 200);
        }
    }

    public function getdissatisfiedcustbyid()
    {
        $dissatisfiedlog = new DissatisfiedLogModel();
        $dissatisfiedmaster = new DissatisfiedMasterModel();
        $leadmodel = new LeadModel();
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

            $id = $this->request->getVar('id');

            $dis_cust = $dissatisfiedmaster->where('ldm_id', $id)
                //->join('dissatisfied_log', 'dissatisfied_log.ldl_ldm_id =ldm_id', 'left')
                ->join('dissatisfied_log AS latest_log', 'latest_log.ldl_ldm_id = ldm_id AND latest_log.ldl_created_on = (SELECT MAX(ldl_created_on) FROM dissatisfied_log WHERE dissatisfied_log.ldl_ldm_id = ldm_id)', 'left')
                //  ->where('ldl_delete_flag !=', 1)
                ->join('leads', 'leads.lead_id=ldm_ldl_id', 'left')
                ->join('psf_master', 'psf_master.psfm_id=ldm_psf_id', 'left')
                ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                ->join('psf_reason', 'psf_reason.psfr_id = ldl_response', 'left')
                ->select('ldm_id,leads.name as lname,ldm_type,leads.phone as lphone,lead_id,ldm_created_on,ldm_status,ldm_assign,
            ldl_note,ldl_response,ldl_action,psf_master.psfm_id,ldm_psf_id,customer_code,lead_code,psf_reason.psfr_name as response,
            psfm_customer_code,cust_data_laabs.customer_name,cust_data_laabs.phone as cphone,psf_master.psfm_current_assignee,psfm_reg_no')->first();

            $log = $dissatisfiedlog->where('ldl_ldm_id', $id)->select('*')->findAll();

            if (sizeof($dis_cust) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'cust' => $dis_cust,
                    'log' => $log,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function closeDissatisfiedcust()
    {
        $dissatisfiedlog = new DissatisfiedLogModel();
        $dissatisfiedmaster = new DissatisfiedMasterModel();
        $leadmodel = new LeadModel();
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

            $id = $this->request->getVar('id');
            $updatedata = [
                'ldm_status' => 5
            ];
            $closeDisCust = $dissatisfiedmaster->where('ldm_id', $id)->set($updatedata)->update();
            if ($closeDisCust) {
                $ldl_response = $dissatisfiedlog->where('ldl_ldm_id', $id)->where('ldl_delete_flag !=', 1)
                    ->select("ldl_response")->first();
                $ldl_action = $dissatisfiedlog->where('ldl_ldm_id', $id)->where('ldl_delete_flag !=', 1)
                    ->select("ldl_action")->first();
                $statusdata = [
                    'ldl_delete_flag' => 1
                ];
                $dis_log_delete = $dissatisfiedlog->where('ldl_ldm_id', $id)->set($statusdata)->update();
                if ($dis_log_delete) {
                    $Logdata = [
                        'ldl_ldm_id' => $id,
                        'ldl_response' =>  $ldl_response,
                        'ldl_action' => $ldl_action,
                        'ldl_note' => $this->request->getVar('note'),
                        'ldl_activity' => "Dissatisfied Closed",
                        'ldl_created_by' => $tokendata['uid'],
                    ];

                    $logentry = $dissatisfiedlog->insert($Logdata);
                }
            }
            if ($closeDisCust && $logentry) {
                $response = [
                    'ret_data' => 'success',
                    'cust' => $closeDisCust,
                    'log' => $logentry,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
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
}
