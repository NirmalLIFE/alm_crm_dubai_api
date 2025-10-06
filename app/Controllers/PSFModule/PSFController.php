<?php

namespace App\Controllers\PSFModule;

use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\User\UserModel;
use App\Models\PSFModule\PSFMasterModel;
use App\Models\Customer\MaraghiJobcardModel;
use App\Models\Customer\MaraghiJobModel;
use App\Models\PSFModule\CREQuestionMappingModel;
use App\Models\PSFModule\PSFCallHistoryModel;
use App\Models\PSFModule\PSFreasonModel;
use App\Models\PSFModule\PSFresponseModel;
use App\Models\PSFModule\PSFstatusTrackModel;
use App\Models\PSFModule\PSFAssignedStaffModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Dissatisfied\DissatisfiedMasterModel;
use App\Models\Dissatisfied\DissatisfiedLogModel;
use App\Models\Settings\WhatsappMessageModel;


class PSFController extends ResourceController
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
        $rules = [
            'psf_id' => 'required',
            'call_response' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $psfMasterModel = new PSFMasterModel();
        $psfHistoryModel = new PSFCallHistoryModel();
        $psfstatustrackModel = new PSFstatusTrackModel();
        $PSFAssignedStaffModel = new PSFAssignedStaffModel();
        $dissatisfiedlog = new DissatisfiedLogModel();
        $dissatisfiedmaster = new DissatisfiedMasterModel();

        $builder = $this->db->table('sequence_data');
        $builder->select('psf_buffer_days');
        $query = $builder->get();
        $row = $query->getRow();

        $cre_count = $PSFAssignedStaffModel->where('psfs_psf_type', '1')
            ->where('psfs_delete_flag', '0')->findAll();


        if (sizeof($cre_count) > 1) {
            $last_assign_cre_id = $psfMasterModel
                ->orderBy('psfm_updated_on', 'desc')
                ->where('psfm_cre_id !=', '')
                ->select('psfm_cre_id')->first();
            $cre_id = $PSFAssignedStaffModel->where('psfs_psf_type', 1)
                ->where('psfs_delete_flag', 0)->where('psfs_assigned_staff !=', $last_assign_cre_id)
                ->select('psfs_assigned_staff')->first();
        } else if (sizeof($cre_count) == 1) {
            $cre_id = $PSFAssignedStaffModel->where('psfs_psf_type', 1)
                ->where('psfs_delete_flag', 0)
                ->select('psfs_assigned_staff')->first();
        } else {
            $cre_id = 19;
        }



        if ($this->request->getVar('call_response') == 1) {
            $psf_master_data = [
                'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                'psfm_cre_assign_date' => date('Y-m-d', strtotime("+10 day")),
                'psfm_cre_id' => $cre_id,
                'psfm_status' => 2,
                'psfm_sa_rating' => $this->request->getVar('call_rating'),
                'psfm_current_assignee' => $cre_id,
                'psfm_lastresponse' => $this->request->getVar('call_response'),
                'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                'psfm_updated_by' => $user['us_id'],
                'psfm_updated_on' => date("Y-m-d H:i:s"),
            ];
            $task = "Completed with " . $this->request->getVar('call_rating') . " star rating";
            $status = 2;
            $reason = null;
            $remark = $this->request->getVar('call_remark');
        } else if ($this->request->getVar('call_response') == 2 || $this->request->getVar('call_response') == 3) {
            $psf_master_data = [
                'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                'psfm_status' => 2,
                'psfm_sa_rating' => $this->request->getVar('call_rating'),
                'psfm_cre_assign_date' => date('Y-m-d', strtotime("+10 day")),
                'psfm_cre_id' => $cre_id,
                'psfm_current_assignee' => $cre_id,
                'psfm_lastresponse' => $this->request->getVar('call_response'),
                'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                'psfm_updated_by' => $user['us_id'],
                'psfm_updated_on' => date("Y-m-d H:i:s"),
            ];
            $task = "Completed with a positive customer response";
            $status = 2;
            $reason = null;
            $remark = $this->request->getVar('call_remark');
        } else if ($this->request->getVar('call_response') == 4) {
            $reason = $this->request->getVar('response_reason');
            $remark = $this->request->getVar('call_remark');
            if ($this->request->getVar('response_reason') == 6 || $this->request->getVar('response_reason') == 7 || $this->request->getVar('response_reason') == 11) {
                $psf_master_data = [
                    'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                    'psfm_current_assignee' => $user['us_id'],
                    'psfm_cre_id' => null,
                    'psfm_status' => $this->request->getVar('response_reason') == 6 ? 12 : ($this->request->getVar('response_reason') == 7 ? 12 : 13),
                    'psfm_lastresponse' => $this->request->getVar('call_response'),
                    'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                    'psfm_updated_by' => $user['us_id'],
                    'psfm_updated_on' => date("Y-m-d H:i:s"),
                ];
                $task = "Closed since customer not reachable";
                $status =  $this->request->getVar('response_reason') == 6 ? 12 : ($this->request->getVar('response_reason') == 7 ? 12 : 13);
                $reason = $this->request->getVar('response_reason');
                $remark = $this->request->getVar('call_remark');
            } else {
                $psf_master_data = [
                    'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                    'psfm_current_assignee' => $this->request->getVar('psfm_num_of_attempts') == 2 ? $cre_id : $user['us_id'],
                    'psfm_cre_assign_date' => $this->request->getVar('psfm_num_of_attempts') == 2 ? date('Y-m-d', strtotime("+10 day")) : "",
                    'psfm_cre_id' => $this->request->getVar('psfm_num_of_attempts') == 2 ? $cre_id : null,
                    'psfm_status' => $this->request->getVar('psfm_num_of_attempts') == 2 ? 16 : 1,
                    'psfm_lastresponse' => $this->request->getVar('call_response'),
                    'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                    'psfm_updated_by' => $user['us_id'],
                    'psfm_updated_on' => date("Y-m-d H:i:s"),
                ];
                $task = $this->request->getVar('psfm_num_of_attempts') == 2 ? "Closed after max attempt, open for CRE" : "Tried to call but no answer";
                $status = $this->request->getVar('psfm_num_of_attempts') == 2 ? 16 : 1;
                $reason = $this->request->getVar('response_reason');
                $remark = $this->request->getVar('call_remark');
            }
        } else if ($this->request->getVar('call_response') == 5) {
            $reason = $this->request->getVar('response_reason');
            $remark = $this->request->getVar('call_remark');

            if ($this->request->getVar('call_action') == 1) {
                $psf_master_data = [
                    'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                    'psfm_status' => 3,
                    'psfm_transfer_flag' => 1,
                    'psfm_lastresponse' => $this->request->getVar('call_response'),
                    'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                    'psfm_current_assignee' => $this->request->getVar('transfer_id'),
                    'psfm_updated_by' => $user['us_id'],
                    'psfm_updated_on' => date("Y-m-d H:i:s"),
                ];
                $task = "PSF tranferred to the Head " . $this->request->getVar('receipt_name');
                $status = 3;
                $reason = $this->request->getVar('response_reason');
                $destid = $this->request->getVar('transfer_id');
                $Dissmasterdata = [
                    'ldm_psf_id' => $this->request->getVar('psf_id'),
                    'ldm_status' => 1,
                    'ldm_type' => 1,  // psf primary attempt
                    'ldm_created_by' => $tokendata['uid'],
                ];

                $Dissmasterentry = $dissatisfiedmaster->insert($Dissmasterdata);

                if ($Dissmasterentry) {

                    $Logdata = [
                        'ldl_ldm_id' => $Dissmasterentry,
                        'ldl_response' =>  $this->request->getVar('response_reason'),
                        'ldl_note' => $this->request->getVar('call_remark'),
                        'ldl_action' => $this->request->getVar('call_action'),
                        'ldl_activity' => "PSF Disatisfied log entered",
                        'ldl_created_by' => $tokendata['uid'],
                    ];

                    $logentry = $dissatisfiedlog->insert($Logdata);
                }
            } else if ($this->request->getVar('call_action') == 3) {
                $psf_master_data = [
                    'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                    'psfm_status' => 14,
                    'psfm_lastresponse' => $this->request->getVar('call_response'),
                    'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                    'psfm_updated_by' => $user['us_id'],
                    'psfm_updated_on' => date("Y-m-d H:i:s"),
                ];
                $task = "PSF closed with approval";
                $status = 14;
                $reason = $this->request->getVar('response_reason');
                $Dissmasterdata = [
                    'ldm_psf_id' => $this->request->getVar('psf_id'),
                    'ldm_status' => 4,
                    'ldm_type' => 1,  // psf primary attempt
                    'ldm_created_by' => $tokendata['uid'],
                ];

                $Dissmasterentry = $dissatisfiedmaster->insert($Dissmasterdata);

                if ($Dissmasterentry) {

                    $Logdata = [
                        'ldl_ldm_id' => $Dissmasterentry,
                        'ldl_response' =>  $this->request->getVar('response_reason'),
                        'ldl_note' => $this->request->getVar('call_remark'),
                        'ldl_action' => $this->request->getVar('call_action'),
                        'ldl_activity' => "PSF Disatisfied log entered and closed with approval",
                        'ldl_created_by' => $tokendata['uid'],
                    ];

                    $logentry = $dissatisfiedlog->insert($Logdata);
                }
            } else if ($this->request->getVar('call_action') == 4) {
                $psf_master_data = [
                    'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                    'psfm_status' => 15,
                    'psfm_lastresponse' => $this->request->getVar('call_response'),
                    'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                    'psfm_updated_by' => $user['us_id'],
                    'psfm_updated_on' => date("Y-m-d H:i:s"),
                ];
                $task = "Customer requested for a Revisit";
                $status = 15;
                $Dissmasterdata = [
                    'ldm_psf_id' => $this->request->getVar('psf_id'),
                    'ldm_status' => 1,
                    'ldm_type' => 1,  // psf primary attempt
                    'ldm_created_by' => $tokendata['uid'],
                ];

                $Dissmasterentry = $dissatisfiedmaster->insert($Dissmasterdata);

                if ($Dissmasterentry) {

                    $Logdata = [
                        'ldl_ldm_id' => $Dissmasterentry,
                        'ldl_response' =>  $this->request->getVar('response_reason'),
                        'ldl_note' => $this->request->getVar('call_remark'),
                        'ldl_action' => $this->request->getVar('call_action'),
                        'ldl_activity' => "PSF Disatisfied log entered ",
                        'ldl_created_by' => $tokendata['uid'],
                    ];

                    $logentry = $dissatisfiedlog->insert($Logdata);
                }
            } else {
                $psf_master_data = [
                    'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                    'psfm_cre_assign_date' => date('Y-m-d', strtotime("+10 day")),
                    'psfm_current_assignee' => $cre_id,
                    'psfm_cre_id' => $cre_id,
                    'psfm_status' => 2,
                    'psfm_lastresponse' => $this->request->getVar('call_response'),
                    //  'psfm_current_assignee' => 19,
                    'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                    'psfm_updated_by' => $user['us_id'],
                    'psfm_updated_on' => date("Y-m-d H:i:s"),
                ];
                if ($this->request->getVar('call_action') == 2) {
                    $task = "PSF closed with the action Educated and convinced";
                    $status = 2;
                } else {
                    $task = "PSF closed with the action Apology over phone/mail";
                    $status = 2;
                }

                $Dissmasterdata = [
                    'ldm_psf_id' => $this->request->getVar('psf_id'),
                    'ldm_status' => 4,
                    'ldm_type' => 1,  // psf primary attempt
                    'ldm_created_by' => $tokendata['uid'],
                ];

                $Dissmasterentry = $dissatisfiedmaster->insert($Dissmasterdata);

                if ($Dissmasterentry) {

                    $Logdata = [
                        'ldl_ldm_id' => $Dissmasterentry,
                        'ldl_response' =>  $this->request->getVar('response_reason'),
                        'ldl_note' => $this->request->getVar('call_remark'),
                        'ldl_action' => $this->request->getVar('call_action'),
                        'ldl_activity' => "PSF Disatisfied log entered and closed with approval",
                        'ldl_created_by' => $tokendata['uid'],
                    ];

                    $logentry = $dissatisfiedlog->insert($Logdata);
                }
            }
        } else if ($this->request->getVar('call_response') == 6) {
            $psf_master_data = [
                'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                'psfm_status' =>  18, //psf not applicable
                'psfm_lastresponse' => $this->request->getVar('call_response'),
                'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                'psfm_updated_by' => $user['us_id'],
                'psfm_updated_on' => date("Y-m-d H:i:s"),
            ];
            $task = "PSF Not applicable for the customer";
            $status = 18;
            $reason = $this->request->getVar('response_reason');
            $remark = $this->request->getVar('call_remark');
        } else if ($this->request->getVar('call_response') == 7) {
            $psf_master_data = [
                'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                'psfm_status' =>  0, //psf not applicable
                'psfm_lastresponse' => $this->request->getVar('call_response'),
                'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                'psfm_updated_by' => $user['us_id'],
                'psfm_psf_assign_date' => date('Y-m-d', strtotime("+$row->psf_buffer_days day")),
                'psfm_updated_on' => date("Y-m-d H:i:s"),
            ];
            $task = "Customer not driven vehicle. Call assigned to PSF after 5 days";
            $status = 0;
            $reason = null;
            $remark = $this->request->getVar('call_remark');
        } else if ($this->request->getVar('psf_is_wipjobcard') == true) {
            $psf_master_data = [
                'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')),
                'psfm_status' => $this->request->getVar('call_close_status') ? 19 : 1,
                'psfm_lastresponse' => $this->request->getVar('call_response'),
                'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                'psfm_updated_by' => $user['us_id'],
                'psfm_updated_on' => date("Y-m-d H:i:s"),
            ];
            $task = "PSF has been closed,Vehicle work is still in progress";
            $status = $this->request->getVar('call_close_status') ? 19 : 1;
            $reason = $this->request->getVar('response_reason');
            $remark = $this->request->getVar('call_remark');
        } else {
            $psf_master_data = [
                'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                'psfm_status' => $this->request->getVar('call_close_status') ? 14 : 1,
                'psfm_lastresponse' => $this->request->getVar('call_response'),
                'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                'psfm_updated_by' => $user['us_id'],
                'psfm_updated_on' => date("Y-m-d H:i:s"),
            ];
            $task = "PSF closed with reason others";
            $status = $this->request->getVar('call_close_status') ? 14 : 1;
            $reason = $this->request->getVar('response_reason');
            $remark = $this->request->getVar('call_remark');
        }
        $psfmaster_ret = $psfMasterModel->update($this->request->getVar('psf_id'), $psf_master_data);
        if ($psfmaster_ret) {
            if ($this->request->getVar('psf_is_wipjobcard') != true) {
                $inData = [
                    'psf_id' => $this->request->getVar('psf_id'),
                    'psf_user_id' => $user['us_id'],
                    'psf_call_date' => $this->request->getVar('psf_call_date'),
                    'psf_response' => $this->request->getVar('call_response'),
                    'psf_reason' => $reason,
                    'psf_remark' => $remark,
                    'psf_action' => $this->request->getVar('call_action'),
                    'psf_call_type' => $this->request->getVar('psf_call_type'),
                    'psf_created_on' => date("Y-m-d H:i:s"),
                ];


                $result = $psfHistoryModel->insert($inData);
            }

            if ($this->request->getVar('transfer_id')) {
                $destid = $this->request->getVar('transfer_id');
            } else {
                $destid = $user['us_id'];
            }
            $tracker_data = [
                'pst_task' => $task,
                'pst_psf_status' => $status,
                'pst_response' => $this->request->getVar('call_response'),
                'pst_sourceid' => $user['us_id'],
                'pst_destid' => $destid,
                'pst_psf_id' => $this->request->getVar('psf_id'),
                'pst_created_by' => $user['us_id'],
                'pst_psf_call_type' => 0,
                'pst_created_on' => date("Y-m-d H:i:s"),
            ];
            $tracker = $psfstatustrackModel->insert($tracker_data);
        }
        if ($this->request->getVar('psf_is_wipjobcard') != true) {
            if ($result) {
                $response["ret_data"] = "success";
                return $this->respond($response, 200);
            } else {
                $response["ret_data"] = "fail";
                return $this->respond($response, 200);
            }
        } else {
            if ($this->request->getVar('psf_is_wipjobcard') == true) {
                $response["ret_data"] = "success";
                return $this->respond($response, 200);
            } else {
                $response["ret_data"] = "fail";
                return $this->respond($response, 200);
            }
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
        $rules = [
            'psf_id' => 'required',
            'call_response' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $psfMasterModel = new PSFMasterModel();
        $psfHistoryModel = new PSFCallHistoryModel();
        $psfstatustrackModel = new PSFstatusTrackModel();
        $questionModel = new CREQuestionMappingModel();
        $dissatisfiedlog = new DissatisfiedLogModel();
        $dissatisfiedmaster = new DissatisfiedMasterModel();
        $qdata = $this->request->getVar('qdata');
        if ($this->request->getVar('call_response') == 1) {
            $psf_master_data = [
                'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                'psfm_status' => 7,
                'psfm_cre_rating' => 5,
                'psfm_lastresponse' => $this->request->getVar('call_response'),
                'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                'psfm_created_by' => $user['us_id'],
                'psfm_updated_by' => $user['us_id'],
                'psfm_updated_on' => date("Y-m-d H:i:s"),
            ];
            if (intval($this->request->getVar('psfm_num_of_attempts')) == 3) {
                $task = "Completed in First attempt with " . $this->request->getVar('call_rating') . " star rating";
            } else {
                $task = "Completed with " . $this->request->getVar('call_rating') . " star rating";
            }
            $status = 7;
            $reason = null;
            $remark = $this->request->getVar('call_remark');
        } else if ($this->request->getVar('call_response') == 2 || $this->request->getVar('call_response') == 3) {
            $psf_master_data = [
                'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                'psfm_status' => 7,
                'psfm_cre_rating' => $this->request->getVar('call_response') == 2 ? 4 : 3,
                'psfm_lastresponse' => $this->request->getVar('call_response'),
                'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                'psfm_created_by' => $user['us_id'],
                'psfm_updated_by' => $user['us_id'],
                'psfm_updated_on' => date("Y-m-d H:i:s"),
            ];
            if (intval($this->request->getVar('psfm_num_of_attempts')) == 3) {
                $task = "Completed in First attempt with " . $this->request->getVar('call_rating') . " star rating";
            } else {
                $task = "Completed with " . $this->request->getVar('call_rating') . " star rating";
            }

            $status = 7;
            $reason = null;
            $remark = $this->request->getVar('call_remark');
        } else if ($this->request->getVar('call_response') == 4) {
            $reason = $this->request->getVar('response_reason');
            $remark = $this->request->getVar('call_remark');

            if ($this->request->getVar('response_reason') == 6) {
                $psf_master_data = [
                    'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                    'psfm_status' => $this->request->getVar('attempts') == 3 ? 7 : 12,
                    'psfm_lastresponse' => $this->request->getVar('call_response'),
                    'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                    'psfm_created_by' => $user['us_id'],
                    'psfm_updated_by' => $user['us_id'],
                    'psfm_updated_on' => date("Y-m-d H:i:s"),
                ];
                $task = "Couldn't connect with Incomplete number ";
                $status = $this->request->getVar('attempts') == 3 ? 7 : 12;
            } else if ($this->request->getVar('response_reason') == 11) {
                $psf_master_data = [
                    'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                    'psfm_status' => $this->request->getVar('attempts') == 3 ? 7 : 13,
                    'psfm_lastresponse' => $this->request->getVar('call_response'),
                    'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                    'psfm_created_by' => $user['us_id'],
                    'psfm_updated_by' => $user['us_id'],
                    'psfm_updated_on' => date("Y-m-d H:i:s"),
                ];
                $task = "Couldn't connect with wrong number ";
                $status = $this->request->getVar('attempts') == 3 ? 7 : 13;
            } else {
                $psf_master_data = [
                    'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                    'psfm_status' => $this->request->getVar('attempts') == 3 ? 17 : 6,
                    'psfm_lastresponse' => $this->request->getVar('call_response'),
                    'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                    'psfm_created_by' => $user['us_id'],
                    'psfm_updated_by' => $user['us_id'],
                    'psfm_updated_on' => date("Y-m-d H:i:s"),
                ];
                $task = "Tried to Call but no answer ";
                $status = $this->request->getVar('attempts') == 3 ? 17 : 6;
            }
        } else if ($this->request->getVar('call_response') == 5) {
            $reason = $this->request->getVar('response_reason');
            $remark = $this->request->getVar('call_remark');


            $Dissmasterdata = [
                'ldm_psf_id' => $this->request->getVar('psf_id'),
                'ldm_status' => 1,
                'ldm_type' => 1,
                'ldm_created_by' => $tokendata['uid'],
            ];

            $Dissmasterentry = $dissatisfiedmaster->insert($Dissmasterdata);

            if ($Dissmasterentry) {

                $Logdata = [
                    'ldl_ldm_id' => $Dissmasterentry,
                    //  'ldl_response' =>  $this->request->getVar('response_reason'),
                    'ldl_note' => $this->request->getVar('call_remark'),
                    //  'ldl_action' => $this->request->getVar('call_action'),
                    'ldl_activity' => "PSF Disatisfied log entered",
                    'ldl_created_by' => $tokendata['uid'],
                ];

                $logentry = $dissatisfiedlog->insert($Logdata);
            }


            $psf_master_data = [
                'psfm_num_of_attempts' => intval($this->request->getVar('psfm_num_of_attempts')) + 1,
                'psfm_status' => 7,
                'psfm_lastresponse' => $this->request->getVar('call_response'),
                'psfm_current_assignee' => 19,
                'psfm_last_attempted_date' => $this->request->getVar('psf_call_date'),
                'psfm_created_by' => $user['us_id'],
                'psfm_updated_by' => $user['us_id'],
                'psfm_updated_on' => date("Y-m-d H:i:s"),
            ];
            $task = "PSF closed by CRE (Dissatisfied Cutomer)";
            $status = 7;
        }
        $psfmaster_ret = $psfMasterModel->update($this->request->getVar('psf_id'), $psf_master_data);
        if ($psfmaster_ret) {
            $in_q_data = array();
            if (count($qdata) > 0) {
                for ($i = 0; $i < count($qdata); $i++) {
                    $inqdata = [
                        'cq_psfid'   => $this->request->getVar('psf_id'),
                        'cq_qid' => $qdata[$i]->cqm_id,
                        'cq_answer' => $qdata[$i]->selected_option,
                        'cq_user_id' => $user['us_id'],
                        'cq_created_by' => $user['us_id'],
                    ];
                    array_push($in_q_data, $inqdata);
                }
                $questionModel->insertBatch($in_q_data);
            }
            $inData = [
                'psf_id' => $this->request->getVar('psf_id'),
                'psf_user_id' => $user['us_id'],
                'psf_call_date' => $this->request->getVar('psf_call_date'),
                'psf_response' => $this->request->getVar('call_response'),
                'psf_reason' => $reason,
                'psf_remark' => $remark,
                'psf_action' => $this->request->getVar('call_action'),
                'psf_call_type' => $this->request->getVar('psf_call_type'),
                'psf_created_on' => date("Y-m-d H:i:s"),
            ];
            $result = $psfHistoryModel->insert($inData);
            if ($this->request->getVar('transfer_id')) {
                $destid = $this->request->getVar('transfer_id');
            } else {
                $destid = $user['us_id'];
            }
            $tracker_data = [
                'pst_task' => $task,
                'pst_psf_status' => $status,
                'pst_response' => $this->request->getVar('call_response'),
                'pst_sourceid' => $user['us_id'],
                'pst_destid' => $destid,
                'pst_psf_id' => $this->request->getVar('psf_id'),
                'pst_created_by' => $user['us_id'],
                'pst_psf_call_type' => 1,
                'pst_created_on' => date("Y-m-d H:i:s"),
            ];
            $tracker = $psfstatustrackModel->insert($tracker_data);
        }
        if ($result) {
            $response["ret_data"] = "success";
            return $this->respond($response, 200);
        } else {
            $response["ret_data"] = "fail";
            return $this->respond($response, 200);
        }
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

    public function crmDailyPSFUpdate()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", "1")->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] != "") {

            $builder = $this->db->table('sequence_data');
            $builder->select('psf_feedback_assign_days_after_sa');
            $query = $builder->get();
            $row = $query->getRow();
            $daysToSubtract = $row->psf_feedback_assign_days_after_sa;
            $today = strtoupper(date('d-M-y', strtotime("-$daysToSubtract day")));
            // $today = strtoupper(date('d-M-y', strtotime("-3 day")));
            $laabs_job = new MaraghiJobcardModel();
            $psf_tracker = new PSFstatusTrackModel();
            $laabs_psf = $laabs_job->select('job_no,customer_no,vehicle_id,car_reg_no,invoice_date,sa_emp_id,job_status,phone,lang_pref')
                ->join('cust_data_laabs', 'cust_data_laabs.customer_code=customer_no')
                ->where('job_status', 'INV')->where('feedback_flag', '0')->where('invoice_date', $today)->orderBy('job_no', 'desc')
                ->groupBy('vehicle_id,customer_no')
                ->findAll();


            if (sizeof($laabs_psf) > 0) {
                $builder = $this->db->table('sequence_data');
                $builder->select('psf_assign_type');
                $query = $builder->get();
                $row = $query->getRow();
                if ($row->psf_assign_type == 1) {
                    $psfStaff = new PSFAssignedStaffModel();
                    $assigned_staff = $psfStaff->select('psfs_id,psfs_assigned_staff,psfs_psf_type')
                        ->where('psfs_delete_flag', 0)->where('psfs_psf_type', 1)->findAll(); // to get secondary assign psf
                    $split_data = array_chunk($laabs_psf, ceil(count($laabs_psf) / sizeof($assigned_staff)));
                    $staff_index = 0;
                    foreach ($split_data as $values) {
                        foreach ($values as $psf_item) {
                            $startDate = strtotime($psf_item['invoice_date']) - (60 * 60 * 24 * 20);  // 20 days
                            $laabs_psf_dupe = $laabs_job
                                ->where('vehicle_id', $psf_item['vehicle_id'])->where("STR_TO_DATE(job_open_date, '%d-%b-%y')>",  date('Y-m-d', $startDate))
                                ->where('job_status', 'INV')
                                ->countAllResults();



                            if ($laabs_psf_dupe == 1) {
                                $messageData = $psf_item['lang_pref'] == 'AR' ? array(
                                    "messaging_product" => "whatsapp",
                                    "recipient_type" => "individual",
                                    "to" => "971" . substr($psf_item['phone'], -9),
                                    "type" => "template",
                                    'template' => array("name" => "service_feedback_arabic", 'language' => array("code" => "ar"), 'components' =>
                                    array(
                                        // array(
                                        //     "type" => "header",
                                        //     "parameters" => array(
                                        //         array("type" => "image", "image" => array("link" => "https://benzuae.ae/feedback.jpg"))
                                        //     )
                                        // ),
                                        array(
                                            "type" => "body"
                                        )
                                    ))
                                ) : array(
                                    "messaging_product" => "whatsapp",
                                    "recipient_type" => "individual",
                                    "to" => "971" . substr($psf_item['phone'], -9),
                                    "type" => "template",
                                    'template' => array("name" => "service_feedback_third_day", 'language' => array("code" => "en"), 'components' =>
                                    array(
                                        // array(
                                        //     "type" => "header",
                                        //     "parameters" => array(
                                        //         array("type" => "image", "image" => array("link" => "https://benzuae.ae/feedback.jpg"))
                                        //     )
                                        // ),
                                        array(
                                            "type" => "body"
                                        )
                                    ))
                                );
                                $return = $common->sendWhatsappMessage($messageData, '971509766075');
                                $wb_id = 0;
                                if (isset($return->messages)) {
                                    $wb_model = new WhatsappMessageModel();
                                    if ($return->messages[0]->message_status == "accepted") {
                                        $wb_data = [
                                            'wb_message_id' => $return->messages[0]->id,
                                            'wb_message_source' => 1,
                                            'wb_customer_id' => $psf_item['customer_no'],
                                            'wb_message_status' => 1,
                                            'wb_phone' => "971" . substr($psf_item['phone'], -9),
                                            'wb_created_on' => date("Y-m-d H:i:s"),
                                            'wb_updated_on' => date("Y-m-d H:i:s")
                                        ];
                                    } else {
                                        $wb_data = [
                                            'wb_message_id' => $return->messages[0]->id,
                                            'wb_message_source' => 1,
                                            'wb_customer_id' => $psf_item['customer_no'],
                                            'wb_message_status' => 0,
                                            'wb_phone' => "971" . substr($psf_item['phone'], -9),
                                            'wb_created_on' => date("Y-m-d H:i:s"),
                                            'wb_updated_on' => date("Y-m-d H:i:s")
                                        ];
                                    }
                                    $wb_id = $wb_model->insert($wb_data);
                                }
                                $psfMasterInsert = [
                                    'psfm_customer_code' => $psf_item['customer_no'],
                                    'psfm_job_no' => $psf_item['job_no'],
                                    'psfm_vehicle_no' => $psf_item['vehicle_id'],
                                    'psfm_reg_no' => $psf_item['car_reg_no'],
                                    'psfm_invoice_date' => $psf_item['invoice_date'],
                                    'psfm_sa_id' => $psf_item['sa_emp_id'],
                                    // 'psfm_psf_assign_date' => date('Y-m-d'),
                                    // 'psfm_psf_assign_date' =>'2023-04-25',
                                    // 'psfm_primary_assignee' => $assigned_staff[$staff_index]['psfs_assigned_staff'],
                                    'psfm_current_assignee' => $assigned_staff[$staff_index]['psfs_assigned_staff'],
                                    'psfm_created_by' => $user['us_id'],
                                    'psfm_updated_by' => $user['us_id'],
                                    'psfm_primary_whatsapp_id' => $wb_id,
                                    'psfm_created_on' => date("Y-m-d H:i:s"),
                                    'psfm_updated_on' => date("Y-m-d H:i:s"),
                                    'psfm_cre_assign_date' => date('Y-m-d'),
                                    'psfm_cre_id' => $assigned_staff[$staff_index]['psfs_assigned_staff'],
                                    'psfm_status' => 2,
                                    'psfm_assign_flag' => 2,

                                ];
                                $psf_master = new PSFMasterModel();
                                $insert_id = $psf_master->insert($psfMasterInsert);
                                $tracker_data = [
                                    'pst_task' => 'PSF Customer assigned to CRE',
                                    'pst_psf_status' => 2,
                                    'pst_psf_call_type' => 2,
                                    'pst_sourceid' => $assigned_staff[$staff_index]['psfs_assigned_staff'],
                                    'pst_destid' => $assigned_staff[$staff_index]['psfs_assigned_staff'],
                                    'pst_psf_id' => $insert_id,
                                    'pst_created_by' => 0,
                                    'pst_created_on' => date("Y-m-d H:i:s"),
                                ];
                                $tracker = $psf_tracker->insert($tracker_data);
                            } else {
                                $insert_id = 0;
                            }
                        }
                        $staff_index++;
                    }
                    $response["ret_data"] = $laabs_psf;
                } else {
                    foreach ($laabs_psf as $value) {
                        $laabs_psf_dupe = $laabs_job
                            ->where('vehicle_id', $value['vehicle_id'])->where('DATE(job_open_date)>',  date('Y-m-d', strtotime($value['invoice_date'])))
                            ->countAllResults();
                        date('y-m-d', strtotime('-30 days'));
                        if ($laabs_psf_dupe == 0) {
                            $psf_user = $usmodel->where("us_laabs_id",  $value['sa_emp_id'])->first();
                            $psfMasterInsert = [
                                'psfm_customer_code' => $value['customer_no'],
                                'psfm_job_no' => $value['job_no'],
                                'psfm_vehicle_no' => $value['vehicle_id'],
                                'psfm_reg_no' => $value['car_reg_no'],
                                'psfm_invoice_date' => $value['invoice_date'],
                                // 'psfm_sa_id' => $value['sa_emp_id'],
                                // 'psfm_psf_assign_date' => date('Y-m-d'),
                                // 'psfm_psf_assign_date' =>'2023-04-25',
                                //'psfm_primary_assignee' => $psf_user['us_id'],
                                'psfm_current_assignee' => 19,
                                'psfm_created_by' => $user['us_id'],
                                'psfm_updated_by' => $user['us_id'],
                                'psfm_created_on' => date("Y-m-d H:i:s"),
                                'psfm_updated_on' => date("Y-m-d H:i:s"),
                                'psfm_cre_assign_date' => date('Y-m-d'),
                                'psfm_cre_id' => 19,
                                'psfm_status' => 2,
                                'psfm_assign_flag' => 2,

                            ];
                            $psf_master = new PSFMasterModel();
                            $insert_id = $psf_master->insert($psfMasterInsert);
                            $tracker_data = [
                                'pst_task' => 'PSF Customer assigned to CRE',
                                'pst_psf_status' => 2,
                                'pst_sourceid' => 19,
                                'pst_destid' => 19,
                                'pst_psf_id' => $insert_id,
                                'pst_created_by' => 0,
                                'pst_created_on' => date("Y-m-d H:i:s"),
                            ];
                            $tracker = $psf_tracker->insert($tracker_data);
                        } else {
                            $insert_id = 0;
                        }
                    }
                }
                if ($insert_id > 0) {
                    $response["ret_data"] = "success";
                    return $this->respond($response, 200);
                } else {
                    $response["ret_data"] = "fail";
                    return $this->respond($response, 200);
                }
            } else {
                $response["ret_data"] = "No data";
                return $this->respond($response, 200);
            }
        } else {
            $response["ret_data"] = "fail";
            return $this->respond($response, 200);
        }
    }


    // old working is below new is above only for secondary psf

    // public function crmDailyPSFUpdate()
    // {
    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", "1")->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $tokendata['uid'])->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata['aud'] != "") {

    //         $builder = $this->db->table('sequence_data');
    //         $builder->select('psf_feedback_assign_days');
    //         $query = $builder->get();
    //         $row = $query->getRow();
    //         $daysToSubtract = $row->psf_feedback_assign_days;
    //         $today = strtoupper(date('d-M-y', strtotime("-$daysToSubtract day")));
    //         // $today = strtoupper(date('d-M-y', strtotime("-3 day")));
    //         $laabs_job = new MaraghiJobcardModel();
    //         $psf_tracker = new PSFstatusTrackModel();
    //         $laabs_psf = $laabs_job->select('job_no,customer_no,vehicle_id,car_reg_no,invoice_date,sa_emp_id,job_status,phone,lang_pref')
    //             ->join('cust_data_laabs', 'cust_data_laabs.customer_code=customer_no')
    //             ->where('job_status', 'INV')->where('feedback_flag', '0')->where('invoice_date', $today)->orderBy('job_no', 'desc')
    //             ->groupBy('vehicle_id,customer_no')
    //             ->findAll();
    //         if (sizeof($laabs_psf) > 0) {
    //             $builder = $this->db->table('sequence_data');
    //             $builder->select('psf_assign_type');
    //             $query = $builder->get();
    //             $row = $query->getRow();
    //             if ($row->psf_assign_type == 1) {
    //                 $psfStaff = new PSFAssignedStaffModel();
    //                 $assigned_staff = $psfStaff->select('psfs_id,psfs_assigned_staff,psfs_psf_type')
    //                     ->where('psfs_delete_flag', 0)->where('psfs_psf_type', 0)->findAll();
    //                 $split_data = array_chunk($laabs_psf, ceil(count($laabs_psf) / sizeof($assigned_staff)));
    //                 $staff_index = 0;
    //                 foreach ($split_data as $values) {
    //                     foreach ($values as $psf_item) {
    //                         $startDate = strtotime($psf_item['invoice_date']) - (60 * 60 * 24 * 20);
    //                         $laabs_psf_dupe = $laabs_job
    //                             ->where('vehicle_id', $psf_item['vehicle_id'])->where("STR_TO_DATE(job_open_date, '%d-%b-%y')>",  date('Y-m-d', $startDate))
    //                             ->where('job_status', 'INV')
    //                             ->countAllResults();

    //                         if ($laabs_psf_dupe == 1) {
    //                             $messageData = $psf_item['lang_pref'] == 'AR' ? array(
    //                                 "messaging_product" => "whatsapp",
    //                                 "recipient_type" => "individual",
    //                                 "to" => "971" . substr($psf_item['phone'], -9),
    //                                 "type" => "template",
    //                                 'template' => array("name" => "service_feedback_arabic", 'language' => array("code" => "ar"), 'components' =>
    //                                 array(
    //                                     // array(
    //                                     //     "type" => "header",
    //                                     //     "parameters" => array(
    //                                     //         array("type" => "image", "image" => array("link" => "https://benzuae.ae/feedback.jpg"))
    //                                     //     )
    //                                     // ),
    //                                     array(
    //                                         "type" => "body"
    //                                     )
    //                                 ))
    //                             ) : array(
    //                                 "messaging_product" => "whatsapp",
    //                                 "recipient_type" => "individual",
    //                                 "to" => "971" . substr($psf_item['phone'], -9),
    //                                 "type" => "template",
    //                                 'template' => array("name" => "service_feedback_third_day", 'language' => array("code" => "en"), 'components' =>
    //                                 array(
    //                                     // array(
    //                                     //     "type" => "header",
    //                                     //     "parameters" => array(
    //                                     //         array("type" => "image", "image" => array("link" => "https://benzuae.ae/feedback.jpg"))
    //                                     //     )
    //                                     // ),
    //                                     array(
    //                                         "type" => "body"
    //                                     )
    //                                 ))
    //                             );
    //                             $return = $common->sendWhatsappMessage($messageData, '971509766075');
    //                             $wb_id = 0;
    //                             if (isset($return->messages)) {
    //                                 $wb_model = new WhatsappMessageModel();
    //                                 if ($return->messages[0]->message_status == "accepted") {
    //                                     $wb_data = [
    //                                         'wb_message_id' => $return->messages[0]->id,
    //                                         'wb_message_source' => 1,
    //                                         'wb_customer_id' => $psf_item['customer_no'],
    //                                         'wb_message_status' => 1,
    //                                         'wb_phone' => "971" . substr($psf_item['phone'], -9),
    //                                         'wb_created_on' => date("Y-m-d H:i:s"),
    //                                         'wb_updated_on' => date("Y-m-d H:i:s")
    //                                     ];
    //                                 } else {
    //                                     $wb_data = [
    //                                         'wb_message_id' => $return->messages[0]->id,
    //                                         'wb_message_source' => 1,
    //                                         'wb_customer_id' => $psf_item['customer_no'],
    //                                         'wb_message_status' => 0,
    //                                         'wb_phone' => "971" . substr($psf_item['phone'], -9),
    //                                         'wb_created_on' => date("Y-m-d H:i:s"),
    //                                         'wb_updated_on' => date("Y-m-d H:i:s")
    //                                     ];
    //                                 }
    //                                 $wb_id = $wb_model->insert($wb_data);
    //                             }
    //                             $psfMasterInsert = [
    //                                 'psfm_customer_code' => $psf_item['customer_no'],
    //                                 'psfm_job_no' => $psf_item['job_no'],
    //                                 'psfm_vehicle_no' => $psf_item['vehicle_id'],
    //                                 'psfm_reg_no' => $psf_item['car_reg_no'],
    //                                 'psfm_invoice_date' => $psf_item['invoice_date'],
    //                                 'psfm_sa_id' => $psf_item['sa_emp_id'],
    //                                 'psfm_psf_assign_date' => date('Y-m-d'),
    //                                 // 'psfm_psf_assign_date' =>'2023-04-25',
    //                                 'psfm_primary_assignee' => $assigned_staff[$staff_index]['psfs_assigned_staff'],
    //                                 'psfm_current_assignee' => $assigned_staff[$staff_index]['psfs_assigned_staff'],
    //                                 'psfm_created_by' => $user['us_id'],
    //                                 'psfm_updated_by' => $user['us_id'],
    //                                 'psfm_primary_whatsapp_id' => $wb_id,
    //                                 'psfm_created_on' => date("Y-m-d H:i:s"),
    //                                 'psfm_updated_on' => date("Y-m-d H:i:s"),
    //                             ];
    //                             $psf_master = new PSFMasterModel();
    //                             $insert_id = $psf_master->insert($psfMasterInsert);
    //                             $tracker_data = [
    //                                 'pst_task' => 'PSF Customer assigned to Staff',
    //                                 'pst_psf_status' => 0,
    //                                 'pst_psf_call_type' => 0,
    //                                 'pst_sourceid' => $assigned_staff[$staff_index]['psfs_assigned_staff'],
    //                                 'pst_destid' => $assigned_staff[$staff_index]['psfs_assigned_staff'],
    //                                 'pst_psf_id' => $insert_id,
    //                                 'pst_created_by' => 0,
    //                                 'pst_created_on' => date("Y-m-d H:i:s"),
    //                             ];
    //                             $tracker = $psf_tracker->insert($tracker_data);
    //                         } else {
    //                             $insert_id = 0;
    //                         }
    //                     }
    //                     $staff_index++;
    //                 }
    //                 $response["ret_data"] = $laabs_psf;
    //             } else {
    //                 foreach ($laabs_psf as $value) {
    //                     $laabs_psf_dupe = $laabs_job
    //                         ->where('vehicle_id', $value['vehicle_id'])->where('DATE(job_open_date)>',  date('Y-m-d', strtotime($value['invoice_date'])))
    //                         ->countAllResults();
    //                     date('y-m-d', strtotime('-30 days'));
    //                     if ($laabs_psf_dupe == 0) {
    //                         $psf_user = $usmodel->where("us_laabs_id",  $value['sa_emp_id'])->first();
    //                         $psfMasterInsert = [
    //                             'psfm_customer_code' => $value['customer_no'],
    //                             'psfm_job_no' => $value['job_no'],
    //                             'psfm_vehicle_no' => $value['vehicle_id'],
    //                             'psfm_reg_no' => $value['car_reg_no'],
    //                             'psfm_invoice_date' => $value['invoice_date'],
    //                             'psfm_sa_id' => $value['sa_emp_id'],
    //                             'psfm_psf_assign_date' => date('Y-m-d'),
    //                             // 'psfm_psf_assign_date' =>'2023-04-25',
    //                             'psfm_primary_assignee' => $psf_user['us_id'],
    //                             'psfm_current_assignee' => $psf_user['us_id'],
    //                             'psfm_created_by' => $user['us_id'],
    //                             'psfm_updated_by' => $user['us_id'],
    //                             'psfm_created_on' => date("Y-m-d H:i:s"),
    //                             'psfm_updated_on' => date("Y-m-d H:i:s"),
    //                         ];
    //                         $psf_master = new PSFMasterModel();
    //                         $insert_id = $psf_master->insert($psfMasterInsert);
    //                         $tracker_data = [
    //                             'pst_task' => 'PSF Customer assigned to SA',
    //                             'pst_psf_status' => 0,
    //                             'pst_sourceid' => $psf_user['us_id'],
    //                             'pst_destid' => $psf_user['us_id'],
    //                             'pst_psf_id' => $insert_id,
    //                             'pst_created_by' => 0,
    //                             'pst_created_on' => date("Y-m-d H:i:s"),
    //                         ];
    //                         $tracker = $psf_tracker->insert($tracker_data);
    //                     } else {
    //                         $insert_id = 0;
    //                     }
    //                 }
    //             }
    //             if ($insert_id > 0) {
    //                 $response["ret_data"] = "success";
    //                 return $this->respond($response, 200);
    //             } else {
    //                 $response["ret_data"] = "fail";
    //                 return $this->respond($response, 200);
    //             }
    //         } else {
    //             $response["ret_data"] = "No data";
    //             return $this->respond($response, 200);
    //         }
    //     } else {
    //         $response["ret_data"] = "fail";
    //         return $this->respond($response, 200);
    //     }
    // }

    public function crmDailyPSFRoutineUpdate()
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
        if ($tokendata['aud'] != "") {
            $psf_master = new PSFMasterModel();
            $PSFAssignedStaffModel = new PSFAssignedStaffModel();
            $psfMasterModel = new PSFMasterModel();

            $cre_count = $PSFAssignedStaffModel->where('psfs_psf_type', '1')
                ->where('psfs_delete_flag', '0')->findAll();


            if (sizeof($cre_count) > 1) {
                $last_assign_cre_id = $psfMasterModel
                    ->orderBy('psfm_updated_on', 'desc')
                    ->where('psfm_cre_id !=', '')
                    ->select('psfm_cre_id')->first();


                $cre_id = $PSFAssignedStaffModel->where('psfs_psf_type', 1)
                    ->where('psfs_delete_flag', 0)->where('psfs_assigned_staff !=', $last_assign_cre_id)
                    ->select('psfs_assigned_staff')->first();
            } else if (sizeof($cre_count) == 1) {
                $cre_id = $PSFAssignedStaffModel->where('psfs_psf_type', 1)
                    ->where('psfs_delete_flag', 0)
                    ->select('psfs_assigned_staff')->first();
            } else {
                $cre_id = 19;
            }


            $expiring_calls = $psf_master->where("DATE(psfm_psf_assign_date) <=", date('Y-m-d', strtotime("-6 day")))
                ->where('psfm_status<', 2)->where('psfm_delete_flag', 0)->findAll();
            if (sizeof($expiring_calls) > 0) {
                foreach ($expiring_calls as $value) {
                    $data = [
                        'psfm_status' => 4,
                        'psfm_cre_assign_date' => date('Y-m-d', strtotime("+10 day")),
                        'psfm_cre_id' =>  $cre_id,
                        'psfm_current_assignee' =>  $cre_id,
                        'psfm_updated_by' => $user['us_id'],
                        'psfm_updated_on' => date("Y-m-d H:i:s"),
                    ];
                    $update_status = $psf_master->update($value['psfm_id'], $data);
                    if ($update_status) {
                        $psf_tracker = new PSFstatusTrackModel();
                        $psf_user = $usmodel->where("us_laabs_id",  $value['psfm_sa_id'])->first();
                        $tracker_data = [
                            'pst_task' => 'Closed Incomplete & Assigned to CRE',
                            'pst_psf_status' => 4,
                            'pst_sourceid' => $psf_user['us_id'],
                            'pst_destid' =>  $cre_id,
                            'pst_psf_id' => $value['psfm_id'],
                            'pst_created_by' => 0,
                            'pst_psf_call_type' => 0,
                            'pst_created_on' => date("Y-m-d H:i:s"),
                        ];
                        $tracker = $psf_tracker->insert($tracker_data);
                    }
                }
            }
            $cre_expiring_calls = $psf_master->where("DATE(psfm_cre_assign_date) <=", date('Y-m-d', strtotime("-6 day")))
                ->where('(psfm_status=2 or psfm_status=4 or psfm_status=5 or psfm_status=6)')->where('psfm_delete_flag', 0)->findAll();
            if (sizeof($cre_expiring_calls) > 0) {
                foreach ($cre_expiring_calls as $value) {
                    $data = [
                        'psfm_status' => 10,
                        'psfm_updated_by' => 0,
                        'psfm_updated_on' => date("Y-m-d H:i:s"),
                    ];
                    $update_status = $psf_master->update($value['psfm_id'], $data);
                    if ($update_status) {
                        $psf_tracker = new PSFstatusTrackModel();
                        $tracker_data = [
                            'pst_task' => 'Closed incomplete attempt by CRE',
                            'pst_psf_status' => 10,
                            'pst_sourceid' => 19,
                            'pst_destid' => 19,
                            'pst_psf_id' => $value['psfm_id'],
                            'pst_created_by' => 1,
                            'pst_psf_call_type' => 1,
                            'pst_created_on' => date("Y-m-d H:i:s"),
                        ];
                        $tracker = $psf_tracker->insert($tracker_data);
                    }
                }
            }

            $response["ret_data"] = "success";
            return $this->respond($response, 200);
        } else {
            $response["ret_data"] = "fail";
            return $this->respond($response, 200);
        }
    }

    public function get_crmDailyUserPSFCalls()
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
        if ($tokendata['aud'] != "") {

            $psf_master = new PSFMasterModel();
            $psf_tracker = new PSFstatusTrackModel();
            $user_monthly_count = 0;
            $builder = $this->db->table('sequence_data');
            // $builder->select('psf_allowed_days');
            $query = $builder->get();
            $row = $query->getRow();
            $date = $this->request->getvar('date');
            $assigndate = date('Y-m-d', strtotime("-1 days", strtotime($date)));
            $sixdaysago = date('Y-m-d', strtotime("-" . $row->psf_allowed_days . " days", strtotime($date)));
            $month = date('m', strtotime($date));
            $year = date('Y', strtotime($date));
            //$row->psf_allowed_days


            $user_psf = $psf_master->select('cust_data_laabs.customer_name,cust_data_laabs.phone,psfm_id,psfm_job_no,psfm_reg_no,psfm_invoice_date,psfm_psf_assign_date,psfm_status,psfm_num_of_attempts,psfm_sa_rating,psfm_cre_rating,rm_id,rm_name')
                ->where('psfm_current_assignee', $user['us_id'])
                ->where('psfm_status>=0 && psfm_status<2')
                ->where("DATE(psfm_psf_assign_date)  <=", $assigndate)
                ->where("DATE(psfm_psf_assign_date)  >", $sixdaysago)
                ->where('psfm_delete_flag', 0)
                ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                ->orderBy('psfm_psf_assign_date', 'asc')
                ->findAll();
            // $user_whatsapp_psf=
            $user_monthly_total_count = $psf_master->select('COUNT(*) as total_count')->where('psfm_primary_assignee', $user['us_id'])
                ->where("MONTH(psfm_created_on)=", $month)
                ->where("YEAR(psfm_created_on)=", $year)
                ->where("psfm_primary_response_type != 1")
                ->where('psfm_delete_flag', 0)
                ->where("DATE(psfm_psf_assign_date)  <=", $assigndate)->first();
            $user_monthly_count = $psf_tracker->select('COUNT(*) as exp_count')->where('pst_sourceid', $user['us_id'])
                ->where('pst_psf_status', 4)->where("MONTH(pst_created_on)=", $month)
                ->where("YEAR(pst_created_on)=", $year)->first();
            $user_monthly_count_success = $psf_tracker->select('COUNT(*) as success_count')->where('pst_sourceid',  $user['us_id'])
                ->where('pst_psf_status', 2)->where("MONTH(psfm_created_on)=", $month)
                ->where("YEAR(psfm_created_on)=", $year)
                ->where("MONTH(psfm_created_on)=", $month)
                ->where("DATE(psfm_psf_assign_date)  <=", $assigndate)
                ->join("psf_master", "psfm_id=pst_psf_id", "left")
                ->where('psfm_delete_flag', 0)
                ->first();

            $user_month_data = [
                intval($user_monthly_total_count['total_count']) - intval($user_monthly_count['exp_count']) - intval($user_monthly_count_success['success_count']),
                $user_monthly_count_success['success_count'],
                $user_monthly_count['exp_count'],
            ];

            // if ($user['us_role_id'] == 1) {
            //     $user_psf = $psf_master->select('cust_data_laabs.customer_name,cust_data_laabs.phone,psfm_id,psfm_job_no,psfm_reg_no,psfm_invoice_date,psfm_psf_assign_date,psfm_status,psfm_num_of_attempts,psfm_sa_rating,psfm_cre_rating')
            //         ->where('psfm_current_assignee', $user['us_id'])
            //         ->where('psfm_delete_flag', 0)
            //         ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
            //         ->where("DATE(psfm_psf_assign_date)  <=", date('Y-m-d'))
            //         ->where("DATE(psfm_psf_assign_date)  >", date('Y-m-d', strtotime("-6 day")))
            //         ->orderBy('psfm_psf_assign_date', 'asc')
            //         ->findAll();
            // } else if ($user['us_role_id'] == 11) {

            // }

            if (sizeof($user_psf) > 0) {
                $response["ret_data"] = "success";
                $response["user_psf"] = $user_psf;
                $response["user_monthly_total_count"] = $user_month_data;
                return $this->respond($response, 200);
            } else {
                $response["ret_data"] = "success";
                $response["user_psf"] = [];
                $response["user_monthly_total_count"] = $user_month_data;
                return $this->respond($response, 200);
            }
        }
    }
    public function get_PSFresponseMaster()
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
        if ($tokendata['aud'] != "") {
            $psfresponseModel = new PSFresponseModel();
            $response_master = $psfresponseModel->select('rm_id,rm_name')
                ->orderBy('rm_id', 'asc')
                ->findAll();
            if (sizeof($response_master) > 0) {
                $response["ret_data"] = "success";
                $response["response_master"] = $response_master;
                return $this->respond($response, 200);
            } else {
                $response["ret_data"] = "fail";
                return $this->respond($response, 200);
            }
        }
    }
    public function get_PSFreasonMaster()
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
        $rules = [
            'type_id' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        if ($tokendata['aud'] != "") {
            $psfreasonModel = new PSFreasonModel();
            $reason_master = $psfreasonModel->select('psfr_id,psfr_name,psfr_mreason')->where('psfr_typeid', $this->request->getVar('type_id'))
                ->findAll();
            if (sizeof($reason_master) > 0) {
                $response["ret_data"] = "success";
                $response["reason_master"] = $reason_master;
                return $this->respond($response, 200);
            } else {
                $response["ret_data"] = "fail";
                return $this->respond($response, 200);
            }
        }
    }
    public function get_PSFrecord_info()
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
        $rules = [
            'psf_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        if ($tokendata['aud'] != "") {
            $psf_historyModel = new PSFCallHistoryModel();
            $psf_master = new PSFMasterModel();
            $maraghijobmodel = new MaraghiJobcardModel();
            $psf_data = $psf_master->select("CONCAT(cust_data_laabs.customer_title,' ',cust_data_laabs.customer_name) as cus_name,cust_data_laabs.phone,cust_data_laabs.contact_phone,psfm_id,psfm_vehicle_no,psfm_job_no,psfm_reg_no,psfm_invoice_date,psfm_psf_assign_date,psfm_status,psfm_num_of_attempts,psfm_customer_code,cust_veh_data_laabs.model_name,users.us_firstname,psfm_last_attempted_date,psfm_sa_rating,psfm_cre_rating")
                ->where('psfm_id', $this->request->getVar('psf_id'))
                ->where('psfm_delete_flag', 0)
                ->join('users', 'us_laabs_id=psfm_sa_id', 'left')
                ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                ->join('cust_veh_data_laabs', 'cust_veh_data_laabs.vehicle_id=psfm_vehicle_no', 'left')->first();
            if ($psf_data) {
                // if ($user['us_role_id'] == 1) {
                $psf_data['psf_history'] = $psf_historyModel
                    ->select('psf_call_history.*,psf_reason.*,psf_response_master.*,users.us_firstname')
                    ->where('psf_id', $this->request->getVar('psf_id'))
                    ->join('psf_reason', 'psf_reason.psfr_id=psf_call_history.psf_reason', 'left')
                    ->join('psf_response_master', 'psf_response_master.rm_id=psf_call_history.psf_response', 'left')
                    ->join('users', 'users.us_id=psf_call_history.psf_user_id', 'left')
                    ->findAll();
                // } else if ($user['us_role_id'] == 11) {
                // }
                $psf_tracker = new PSFstatusTrackModel();
                $psf_data['wb_response'] =  $psf_tracker->where('pst_psf_id', $this->request->getVar('psf_id'))->where("pst_psf_call_type", 2)->orderBy("pst_id", "DESC")->first();
                $psf_data['current_jobcard'] = $maraghijobmodel->where('job_status', 'WIP')
                    ->where('customer_no', $psf_data['psfm_customer_code'])
                    ->where('vehicle_id', $psf_data['psfm_vehicle_no'])
                    ->findAll();
            }


            if ($psf_data['psfm_id'] > 0) {
                $maraghiJobcardModel = new MaraghiJobModel();
                $psf_data['jobcount'] = $maraghiJobcardModel->where('vehicle_id', $psf_data['psfm_vehicle_no'])
                    ->where('job_status', 'INV')->countAllResults();
                $response["ret_data"] = "success";
                $response["psf_info"] = $psf_data;
                return $this->respond($response, 200);
            } else {
                $response["ret_data"] = "fail";
                return $this->respond($response, 200);
            }
        }
    }
    public function get_psfReport()
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
        if ($tokendata['aud'] != "") {
            $user_list = $usmodel->where('us_delete_flag', 0)
                ->whereIn('us_role_id', [11, 2])
                ->join('user_roles', 'user_roles.role_id=us_role_id', 'left')
                ->select('us_id,us_firstname,us_role_id,user_roles.role_name,us_laabs_id')
                ->orderBy('us_firstname', 'ASC')
                ->findAll();

            $org_psf_data = [];
            if (sizeof($user_list) > 0) {
                $psf_master = new PSFMasterModel();
                $start_date = $this->request->getvar('startDate');
                $end_date = $this->request->getvar('endDate');
                foreach ($user_list as $selected_user) {
                    $selected_user["user_psf_calls"] = $psf_master->where('psfm_delete_flag', 0)
                        ->where("psfm_sa_id", $selected_user['us_laabs_id'])
                        ->where("psfm_psf_assign_date >=", $start_date)
                        ->where("psfm_psf_assign_date <=", $end_date)->findAll();


                    $selected_user["user_psf_calls_not_driven"] = $psf_master->where('psfm_delete_flag', 0)
                        ->where("psfm_sa_id", $selected_user['us_laabs_id'])
                        ->where("DATE(psfm_created_on) >=", $start_date)
                        ->where("DATE(psfm_created_on) <=", $end_date)->findAll();
                    $psf_tracker = new PSFstatusTrackModel();
                    $index = 0;

                    foreach ($selected_user["user_psf_calls"] as $psf_items) {
                        $sa_calls = $psf_tracker->where('pst_psf_call_type', 0)
                            ->where("pst_psf_id", $psf_items['psfm_id'])->orderBy('pst_created_on', 'DESC')->findAll();
                        $selected_user["user_psf_calls"][$index]["last_call_status"] = $sa_calls[0];
                        $selected_user["user_psf_calls"][$index]["last_call_status"]["attempt_count"] = sizeof($sa_calls) - 1;
                        $index++;
                    }
                    $index = 0;
                    foreach ($selected_user["user_psf_calls_not_driven"] as $psf_items) {
                        $sa_calls = $psf_tracker->where('pst_psf_call_type', 0)
                            ->where("pst_psf_id", $psf_items['psfm_id'])->orderBy('pst_created_on', 'DESC')->findAll();
                        if (!empty($sa_calls)) {
                            $selected_user["user_psf_calls_not_driven"][$index]["last_call_status"] = $sa_calls[0];
                        } else {
                            $selected_user["user_psf_calls_not_driven"][$index]["last_call_status"] = []; // or any fallback
                        }
                        $index++;
                    }
                    array_push($org_psf_data, $selected_user);
                }
            }
            $response["ret_data"] = "success";
            $response["users_psf_list"] =  $org_psf_data;
            return $this->respond($response, 200);
        } else {
            $response["ret_data"] = "fail";
            return $this->respond($response, 200);
        }
    }

    public function get_psfReport_cre()
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
        if ($tokendata['aud'] != "") {
            $user_list = $usmodel->where('us_delete_flag', 0)
                ->where('us_role_id', '9')
                ->join('user_roles', 'user_roles.role_id=us_role_id', 'left')
                ->select('us_id,us_firstname,us_role_id,user_roles.role_name,us_laabs_id')
                ->orderBy('us_firstname', 'ASC')
                ->findAll();

            $org_psf_data = [];
            if (sizeof($user_list) > 0) {
                $psf_master = new PSFMasterModel();
                $start_date = $this->request->getvar('startDate');
                $end_date = $this->request->getvar('endDate');
                foreach ($user_list as $selected_user) {
                    $selected_user["user_psf_calls"] = $psf_master->where('psfm_delete_flag', 0)
                        ->where("psfm_current_assignee", $selected_user['us_id'])
                        ->where("psfm_cre_assign_date >=", $start_date)
                        ->where("psfm_cre_assign_date <=", $end_date)->findAll();
                    $psf_tracker = new PSFstatusTrackModel();
                    $index = 0;
                    foreach ($selected_user["user_psf_calls"] as $psf_items) {
                        $sa_calls = $psf_tracker->where('pst_psf_call_type', 1)
                            ->where("pst_psf_id", $psf_items['psfm_id'])->orderBy('pst_created_on', 'DESC')->findAll();
                        if (sizeof($sa_calls) > 0) {
                            $selected_user["user_psf_calls"][$index]["last_call_status"] = $sa_calls[0];
                            $selected_user["user_psf_calls"][$index]["last_call_status"]["attempt_count"] = sizeof($sa_calls);
                        } else {
                            $selected_user["user_psf_calls"][$index]["last_call_status"] = null;
                            $selected_user["user_psf_calls"][$index]["last_call_status"]["attempt_count"] = 0;
                        }
                        $index++;
                    }
                    array_push($org_psf_data, $selected_user);
                }
            }
            $response["ret_data"] = "success";
            $response["users_psf_list"] =  $org_psf_data;
            return $this->respond($response, 200);
        } else {
            $response["ret_data"] = "fail";
            return $this->respond($response, 200);
        }
    }

    public function get_userPsfReport()
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
        if ($tokendata['aud'] != "") {
            $rules = [
                'user_id' => 'required',
                'startDate' => 'required',
                'endDate' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $selected_user = $usmodel->select('us_firstname,us_role_id,us_id,us_laabs_id,ext_number')->where("us_id", base64_decode(base64_decode(base64_decode($this->request->getvar('user_id')))))->first();
            $psf_master = new PSFMasterModel();
            $start_date = $this->request->getvar('startDate');
            $end_date = $this->request->getvar('endDate');
            if (base64_decode(base64_decode($this->request->getvar('usertype'))) == 11 || base64_decode(base64_decode($this->request->getvar('usertype'))) == 2) {
                $selected_user["user_psf_calls"] = $psf_master->where('psfm_delete_flag', 0)
                    ->where("psfm_sa_id", base64_decode(base64_decode(base64_decode($this->request->getvar('us_laabs_id')))))
                    ->where("psfm_psf_assign_date >=", $start_date)
                    ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                    ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                    ->where("psfm_psf_assign_date <=", $end_date)->findAll();
                $psf_tracker = new PSFstatusTrackModel();
                $index = 0;
                foreach ($selected_user["user_psf_calls"] as $psf_items) {
                    // $psf_tracker->where('pst_sourceid', $selected_user['us_id'])
                    $selected_user["user_psf_calls"][$index]["psf_calls"] = $psf_tracker
                        ->where("pst_psf_id", $psf_items['psfm_id'])
                        ->where("DATE(pst_created_on) >=", $psf_items['psfm_psf_assign_date'])
                        ->join('psf_response_master', 'rm_id=pst_response', 'left')
                        ->orderBy('pst_created_on', 'DESC')->findAll();
                    $index++;
                }
                //return $this->respond($selected_user, 200);
            } else {
                $selected_user["user_psf_calls"] = $psf_master->where('psfm_delete_flag', 0)
                    ->where("psfm_current_assignee", base64_decode(base64_decode(base64_decode($this->request->getvar('user_id')))))
                    ->where("psfm_cre_assign_date >=", $start_date)
                    ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                    ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                    ->where("psfm_cre_assign_date <=", $end_date)->findAll();
                $psf_tracker = new PSFstatusTrackModel();
                $index = 0;
                foreach ($selected_user["user_psf_calls"] as $psf_items) {
                    $selected_user["user_psf_calls"][$index]["psf_calls"] = $psf_tracker->where("pst_psf_id", $psf_items['psfm_id'])
                        ->where("pst_psf_id", $psf_items['psfm_id'])
                        ->where("DATE(pst_created_on) >=", $psf_items['psfm_cre_assign_date'])
                        ->join('psf_response_master', 'rm_id=pst_response', 'left')
                        ->orderBy('pst_created_on', 'DESC')->findAll();
                    $index++;
                }
            }
            $response["ret_data"] = "success";
            $response["user_psfdetails"] = $selected_user;
            return $this->respond($response, 200);
        } else {
            $response["ret_data"] = "fail";
            return $this->respond($response, 200);
        }
    }

    public function get_specificCallData()
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
        if ($tokendata['aud'] != "") {
            $rules = [
                'startDate' => 'required',
                'endDate' => 'required',
                // 'psf_type' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $psf_master = new PSFMasterModel();
            $start_date = $this->request->getvar('startDate');
            $end_date = $this->request->getvar('endDate');
            $psf_type = $this->request->getvar('psf_type'); // 0 - disatified, 1 - expired
            $psf_call_type = $this->request->getvar('psf_call_type'); // 0 - SA 1 - CRE
            $psf_staff = $this->request->getvar('psf_staff');
            if ($psf_type == '0') {
                $psf_tracker = new PSFstatusTrackModel();
                $call_details["user_psf_calls"] = $psf_tracker
                    //->where('pst_psf_status', 2) 
                    ->where('pst_response', 5)->where('pst_delete_flag', 0)
                    ->where('pst_psf_call_type', $psf_call_type)
                    ->where("psfm_psf_assign_date >=", $start_date)
                    ->where("psfm_psf_assign_date <=", $end_date)
                    ->where('psfm_delete_flag', 0)
                    ->join('psf_master', 'psfm_id=pst_psf_id', 'left')
                    ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                    ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                    ->join('users as cre', 'cre.us_id=psfm_cre_id', 'left')
                    ->join('users as sa', 'sa.us_id=pst_sourceid', 'left')
                    ->select('psf_status_tracker.*')
                    ->select('psf_master.*')
                    ->select('cust_data_laabs.*')
                    ->select('psf_response_master.*')
                    ->select('cre.us_id as cre_id,cre.us_firstname as cre_name,cre.us_role_id as cre_role_id')
                    ->select('sa.us_id as sa_id,sa.us_firstname as sa_name,sa.us_role_id as sa_role_id')
                    ->findAll();
            } else {
                $psf_tracker = new PSFstatusTrackModel();
                $call_details["user_psf_calls"] = $psf_tracker->where('pst_psf_status', 4) //expired
                    ->where('pst_response', 0)->where('pst_delete_flag', 0)
                    ->where('pst_psf_call_type', $psf_call_type)                         // 0 - SA Calls , 1 - CRE Calls
                    ->where("psfm_psf_assign_date >=", $start_date)
                    ->where("psfm_psf_assign_date <=", $end_date)
                    ->where('psfm_delete_flag', 0)
                    ->join('psf_master', 'psfm_id=pst_psf_id', 'left')
                    ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                    ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                    ->join('users as cre', 'cre.us_id=psfm_cre_id', 'left')
                    ->join('users as sa', 'sa.us_id=pst_sourceid', 'left')
                    ->select('psf_status_tracker.*')
                    ->select('psf_master.*')
                    ->select('cust_data_laabs.*')
                    ->select('psf_response_master.*')
                    ->select('cre.us_id as cre_id,cre.us_firstname as cre_name,cre.us_role_id as cre_role_id')
                    ->select('sa.us_id as sa_id,sa.us_firstname as sa_name,sa.us_role_id as sa_role_id')
                    ->findAll();
            }
            $response["ret_data"] = "success";
            $response["user_psfdetails"] = $call_details;
            return $this->respond($response, 200);
        }
    }





    public function get_expiredandissatisfied()
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
        if ($tokendata['aud'] != "") {
            $rules = [
                'startDate' => 'required',
                'endDate' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $psf_master = new PSFMasterModel();
            $start_date = $this->request->getvar('startDate');
            $end_date = $this->request->getvar('endDate');
            $call_details["user_psf_calls"] = $psf_master->where('psfm_delete_flag', 0)
                ->where("psfm_psf_assign_date >=", $start_date)
                ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                ->join('users', 'users.us_id=psfm_cre_id', 'left')
                ->where("psfm_psf_assign_date <=", $end_date)
                ->select('cust_data_laabs.*')
                ->select('psf_response_master.*')
                ->select('us_id,us_firstname,us_phone,us_role_id')
                ->select('psf_master.*')
                ->findAll();

            $psf_tracker = new PSFstatusTrackModel();
            $index = 0;
            foreach ($call_details["user_psf_calls"] as $psf_items) {
                $call_details["user_psf_calls"][$index]["psf_calls"] = $psf_tracker->where("pst_psf_id", $psf_items['psfm_id'])
                    ->join('psf_response_master', 'rm_id=pst_response', 'left')
                    ->join('users', 'users.us_id=pst_sourceid', 'left')
                    ->orderBy('pst_created_on', 'DESC')
                    ->select('psf_response_master.*')
                    ->select('psf_status_tracker.*')
                    ->select('us_id,us_firstname,us_phone,us_role_id')
                    ->findAll();
                $index++;
            }
            $response["ret_data"] = "success";
            $response["user_psfdetails"] = $call_details;
            return $this->respond($response, 200);
        } else {
            $response["ret_data"] = "fail";
            return $this->respond($response, 200);
        }
    }

    public function get_expiredandissatisfiedcre()
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
        if ($tokendata['aud'] != "") {
            $rules = [
                'startDate' => 'required',
                'endDate' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $psf_master = new PSFMasterModel();
            $start_date = $this->request->getvar('startDate');
            $end_date = $this->request->getvar('endDate');
            $call_details["user_psf_calls"] = $psf_master->where('psfm_delete_flag', 0)
                ->where("psfm_cre_assign_date >=", $start_date)
                ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                ->join('users', 'users.us_id=psfm_primary_assignee', 'left')
                ->select('cust_data_laabs.*')
                ->select('psf_response_master.*')
                ->select('us_id,us_firstname,us_phone,us_role_id')
                ->select('psf_master.*')
                ->where("psfm_cre_assign_date <=", $end_date)->findAll();
            $psf_tracker = new PSFstatusTrackModel();
            $index = 0;
            foreach ($call_details["user_psf_calls"] as $psf_items) {
                $call_details["user_psf_calls"][$index]["psf_calls"] = $psf_tracker->where("pst_psf_id", $psf_items['psfm_id'])
                    ->join('psf_response_master', 'rm_id=pst_response', 'left')
                    ->join('users', 'users.us_id=pst_sourceid', 'left')
                    ->select('psf_response_master.*')
                    ->select('psf_status_tracker.*')
                    ->select('us_id,us_firstname,us_phone,us_role_id')
                    ->orderBy('pst_created_on', 'DESC')->findAll();
                $index++;
            }
            $response["ret_data"] = "success";
            $response["user_psfdetails"] = $call_details;
            return $this->respond($response, 200);
        } else {
            $response["ret_data"] = "fail";
            return $this->respond($response, 200);
        }
    }
    public function get_psfReportsa()
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
        if ($tokendata['aud'] != "") {
            $user_list = $usmodel->where('us_delete_flag', 0)
                ->whereIn('us_role_id', [11, 2])
                ->join('user_roles', 'user_roles.role_id=us_role_id', 'left')
                ->select('us_id,us_firstname,us_role_id,user_roles.role_name,us_laabs_id')
                ->orderBy('us_firstname', 'ASC')
                ->findAll();

            $org_psf_data = [];
            if (sizeof($user_list) > 0) {
                $psf_master = new PSFMasterModel();
                $start_date = $this->request->getvar('startDate');
                $end_date = $this->request->getvar('endDate');
                foreach ($user_list as $selected_user) {
                    $selected_user["user_psf_callsSA"] = $psf_master->where('psfm_delete_flag', 0)
                        ->where("psfm_sa_id", $selected_user['us_laabs_id'])
                        ->where("psfm_cre_assign_date >=", $start_date)
                        ->where("psfm_cre_assign_date <=", $end_date)->findAll();
                    $psf_tracker = new PSFstatusTrackModel();
                    $index = 0;

                    foreach ($selected_user["user_psf_callsSA"] as $psf_items) {
                        $sa_calls = $psf_tracker->where('pst_psf_call_type', 0)
                            ->where("pst_psf_id", $psf_items['psfm_id'])->orderBy('pst_created_on', 'DESC')->findAll();
                        if (!empty($sa_calls)) {
                            $selected_user["user_psf_callsSA"][$index]["last_call_status"] = $sa_calls[0];
                            $selected_user["user_psf_callsSA"][$index]["last_call_status"]["attempt_count"] = sizeof($sa_calls) - 1;
                        } else {
                            // Optional: Set default values if there are no SA calls
                            $selected_user["user_psf_callsSA"][$index]["last_call_status"] = [];
                            $selected_user["user_psf_callsSA"][$index]["attempt_count"] = 0;
                        }

                        $index++;
                    }
                    array_push($org_psf_data, $selected_user);
                }
            }
            $response["ret_data"] = "success";
            $response["users_psf_list"] =  $org_psf_data;
            return $this->respond($response, 200);
        } else {
            $response["ret_data"] = "fail";
            return $this->respond($response, 200);
        }
    }

    public function get_userPsfReportsa()
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
        if ($tokendata['aud'] != "") {
            $rules = [
                'user_id' => 'required',
                'startDate' => 'required',
                'endDate' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $selected_user = $usmodel->select('us_firstname,us_role_id,us_id,us_laabs_id,ext_number')->where("us_id", base64_decode(base64_decode(base64_decode($this->request->getvar('user_id')))))->first();
            $psf_master = new PSFMasterModel();
            $start_date = $this->request->getvar('startDate');
            $end_date = $this->request->getvar('endDate');
            if (base64_decode(base64_decode($this->request->getvar('usertype'))) == 11 || base64_decode(base64_decode($this->request->getvar('usertype'))) == 2) {
                $selected_user["user_psf_calls"] = $psf_master->where('psfm_delete_flag', 0)
                    ->where("psfm_primary_assignee", base64_decode(base64_decode(base64_decode($this->request->getvar('user_id')))))
                    ->where("psfm_cre_assign_date >=", $start_date)
                    ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                    ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                    ->where("psfm_cre_assign_date <=", $end_date)->findAll();
                $psf_tracker = new PSFstatusTrackModel();
                $index = 0;
                foreach ($selected_user["user_psf_calls"] as $psf_items) {
                    // $psf_tracker->where('pst_sourceid', $selected_user['us_id'])
                    $selected_user["user_psf_calls"][$index]["psf_calls"] = $psf_tracker->where("pst_psf_id", $psf_items['psfm_id'])
                        ->join('psf_response_master', 'rm_id=pst_response', 'left')
                        ->orderBy('pst_created_on', 'DESC')->findAll();
                    $index++;
                }
            } else {
                $selected_user["user_psf_calls"] = $psf_master->where('psfm_delete_flag', 0)
                    ->where("psfm_current_assignee", base64_decode(base64_decode(base64_decode($this->request->getvar('user_id')))))
                    ->where("psfm_cre_assign_date >=", $start_date)
                    ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                    ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                    ->where("psfm_cre_assign_date <=", $end_date)->findAll();
                $psf_tracker = new PSFstatusTrackModel();
                $index = 0;
                foreach ($selected_user["user_psf_calls"] as $psf_items) {
                    $selected_user["user_psf_calls"][$index]["psf_calls"] = $psf_tracker->where("pst_psf_id", $psf_items['psfm_id'])
                        ->where("pst_psf_id", $psf_items['psfm_id'])
                        ->join('psf_response_master', 'rm_id=pst_response', 'left')
                        ->orderBy('pst_created_on', 'DESC')->findAll();
                    $index++;
                }
            }
            $response["ret_data"] = "success";
            $response["user_psfdetails"] = $selected_user;
            return $this->respond($response, 200);
        } else {
            $response["ret_data"] = "fail";
            return $this->respond($response, 200);
        }
    }

    public function get_psfTodayCalls()
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
        if ($tokendata['aud'] != "") {

            $psf_historyModel = new PSFCallHistoryModel();
            $psf_master = new PSFMasterModel();
            $psf_tracker = new PSFstatusTrackModel();
            $user = new UserModel();
            $user_monthly_count = 0;
            $user_id = (base64_decode(base64_decode(base64_decode($this->request->getvar('id')))));
            $status = $this->request->getvar('status');
            $date = $this->request->getvar('date');
            $sixdaysago = date('Y-m-d', strtotime("-6 days", strtotime($date)));
            $month = date('m', strtotime($date));

            // $usercalls= $psf_historyModel->select("psf_id,psf_call_date,psf_response,psfm_customer_code,psfm_job_no,psfm_invoice_date,customer_name,rm_name as response,phone")
            // ->where('psf_call_date',$date)
            // ->where('psf_user_id',$user_id)
            // ->join('psf_master', 'psf_master.psfm_id =psf_id',)
            // ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
            // ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
            // ->findAll();

            if ($status != "-1") {
                $user_psf = $psf_master->select('cust_data_laabs.customer_name,cust_data_laabs.phone,psfm_id,psfm_job_no,psfm_reg_no,psfm_invoice_date,psfm_psf_assign_date,psfm_status,psfm_num_of_attempts,psfm_sa_rating,psfm_cre_rating,rm_id,rm_name')
                    ->where('psfm_primary_assignee', $user_id)
                    ->where('psfm_status =', $status)
                    ->where("DATE(psfm_psf_assign_date)  <=", $date)
                    ->where("DATE(psfm_psf_assign_date)  >", $sixdaysago)
                    ->where('psfm_delete_flag', 0)
                    ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                    ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                    ->orderBy('psfm_psf_assign_date', 'asc')
                    ->findAll();
                $index = 0;
                foreach ($user_psf as $psf_items) {
                    $user_psf[$index]["psf_calls"] = $psf_tracker->where("pst_psf_id", $psf_items['psfm_id'])
                        ->where("pst_psf_id", $psf_items['psfm_id'])
                        ->where("DATE(pst_created_on) >=", $psf_items['psfm_psf_assign_date'])
                        ->where("pst_psf_call_type", 0)
                        ->join('psf_response_master', 'rm_id=pst_response', 'left')
                        ->orderBy('pst_created_on', 'DESC')->findAll();
                    $index++;
                }
            } else {
                $user_psf = $psf_master->select('cust_data_laabs.customer_name,cust_data_laabs.phone,psfm_id,psfm_job_no,psfm_reg_no,psfm_invoice_date,psfm_psf_assign_date,psfm_status,psfm_num_of_attempts,psfm_sa_rating,psfm_cre_rating,rm_id,rm_name')
                    ->where('psfm_primary_assignee', $user_id)
                    // ->where('psfm_status>=0 && psfm_status<2')
                    ->where("DATE(psfm_psf_assign_date)  <=", $date)
                    ->where("DATE(psfm_psf_assign_date)  >", $sixdaysago)
                    ->where('psfm_delete_flag', 0)
                    ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                    ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                    ->orderBy('psfm_psf_assign_date', 'asc')
                    ->findAll();
                $index = 0;
                foreach ($user_psf as $psf_items) {
                    $user_psf[$index]["psf_calls"] = $psf_tracker->where("pst_psf_id", $psf_items['psfm_id'])
                        ->where("pst_psf_id", $psf_items['psfm_id'])
                        ->where("DATE(pst_created_on) >=", $psf_items['psfm_psf_assign_date'])
                        ->where("pst_psf_call_type", 0)
                        ->join('psf_response_master', 'rm_id=pst_response', 'left')
                        ->orderBy('pst_created_on', 'DESC')->findAll();
                    $index++;
                }
            }

            $userdetails = $user->select('us_id,us_firstname,us_role_id')
                ->where('us_id', $user_id)
                ->first();







            if (sizeof($user_psf) > 0) {
                $response["ret_data"] = "success";
                $response["usercalls"] = $user_psf;
                $response['userdetails'] = $userdetails;
                $response["user_id"] = $user_id;
                return $this->respond($response, 200);
            } else {
                $response["ret_data"] = "fail";
                return $this->respond($response, 200);
            }
        }
    }
    public function get_customerdata()
    {
        $marcustmodel = new MaragiCustomerModel();
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

            $code_list = [$this->request->getVar('custcode')];


            // $start_date = $this->request->getVar('start_date');
            // $end_date = $this->request->getVar('end_date');
            // $hours =  date('Y-m-d', strtotime($start_date));
            if (sizeof($code_list) > 0) {
                $customer = [];
                foreach ($code_list as $code) {
                    $marag_cus_res = $marcustmodel
                        ->whereIn('customer_code', $code)
                        ->select("customer_code,UPPER(customer_name) as customer_name,city,
                        phone,RIGHT(mobile,7) as mob_uniq,'M' as type")
                        ->findAll();
                    if ($marag_cus_res == null) {
                        $marag_cus_res = $custmastermodel->whereIn('cust_alm_code', $code)
                            ->select("cus_id, cust_alm_code, UPPER(cust_name) as customer_name, 
                        cust_city as city,cust_phone as phone, 'C' as type, 
                        RIGHT(cust_alternate_contact, 7) as alt_num_uniq")
                            ->findAll();
                        if ($marag_cus_res == null) {
                            //     $marag_cus_res = $leadmodel->like('phone', $number, 'before')->where('lead_createdon <', $hours)
                            //         ->select("IF(IFNULL(name, '') = '', 'EXISTS', name) as customer_name,phone as mobile,RIGHT(phone,7) as phon_uniq,'L' as type")->first();
                            //     if ($marag_cus_res == null) {
                            //         $marag_cus_res = [];
                            //    }
                            $marag_cus_res = [];
                        }
                    }
                    array_push($customer, $marag_cus_res);
                }
                $response = [
                    'ret_data' => 'success',
                    'customers' => $customer,

                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customers' => [],
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function get3rdDayPsfCallData()
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
            $psf_historyModel = new PSFCallHistoryModel();
            $psf_master = new PSFMasterModel();
            $psf_tracker = new PSFstatusTrackModel();

            $start_date = $this->request->getvar('startDate');
            $end_date = $this->request->getvar('endDate');
            $us_id = $this->request->getvar('us_id');


            $thirdDayCalls = $psf_master->where("DATE(psfm_psf_assign_date)>=", $start_date)
                ->where("DATE(psfm_psf_assign_date)<=", $end_date)
                ->where('psfm_delete_flag', 0)
                ->where('psfm_primary_assignee', $us_id)
                ->findAll();
            $index = 0;
            foreach ($thirdDayCalls as $calls) {
                $thirdDayCalls[$index]["psf_calls"] = $psf_tracker->where("pst_psf_id", $calls['psfm_id'])
                    ->where("pst_psf_id", $calls['psfm_id'])
                    ->where("DATE(pst_created_on) >=", $calls['psfm_psf_assign_date'])
                    ->where("pst_psf_call_type", 0)
                    ->join('psf_response_master', 'rm_id=pst_response', 'left')
                    ->orderBy('pst_created_on', 'DESC')->findAll();
                $index++;
            }

            if (sizeof($thirdDayCalls) > 0) {

                $response = [
                    'ret_data' => 'success',
                    'thirdDayCalls' => $thirdDayCalls,
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'thirdDayCalls' => [],
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function get7thDayPsfCallData()
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
            $psf_historyModel = new PSFCallHistoryModel();
            $psf_master = new PSFMasterModel();
            $psf_tracker = new PSFstatusTrackModel();

            $start_date = $this->request->getvar('startDate');
            $end_date = $this->request->getvar('endDate');
            $us_id = $this->request->getvar('us_id');

            $seventhDAyCalls = $psf_master->where('psfm_current_assignee', $us_id)
                // ->where('(psfm_status=2 or psfm_status=4 or psfm_status=5 or psfm_status=6 or psfm_status=16)')
                ->where('DATE(psfm_cre_assign_date) >= ',  $start_date)
                ->where("DATE(psfm_cre_assign_date)  <=", $end_date)
                ->where('psfm_delete_flag', 0)
                ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                ->orderBy('psfm_cre_assign_date', 'asc')
                ->findAll();
            $index = 0;
            foreach ($seventhDAyCalls as $calls) {
                $seventhDAyCalls[$index]["psf_calls"] = $psf_tracker->where("pst_psf_id", $calls['psfm_id'])
                    ->where("pst_psf_id", $calls['psfm_id'])
                    ->where("DATE(pst_created_on) >=", $calls['psfm_cre_assign_date'])
                    ->where("pst_psf_call_type", 1)
                    ->join('psf_response_master', 'rm_id=pst_response', 'left')
                    ->orderBy('pst_created_on', 'DESC')->findAll();
                $index++;
            }

            if (sizeof($seventhDAyCalls) > 0) {

                $response = [
                    'ret_data' => 'success',
                    'seventhDAyCalls' => $seventhDAyCalls,
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'seventhDAyCalls' => [],
                ];
            }
            return $this->respond($response, 200);
        }
    }
}
