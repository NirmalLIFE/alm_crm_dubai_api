<?php

namespace App\Controllers\User;

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
use App\Models\Customer\LostCustomerModel;
use App\Models\Dissatisfied\DissatisfiedMasterModel;
use App\Models\Dissatisfied\DissatisfiedLogModel;
use App\Models\Settings\WhatsappMessageModel;
use App\Models\PSFModule\PSFCallHistoryModel;
use App\Models\PSFModule\PSFMasterModel;
use App\Models\PSFModule\PSFstatusTrackModel;
use App\Models\Customer\MaraghiJobcardModel;
use DateTime;


class StaffDash extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function dashCounts()
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
            $uid = $tokendata['uid'];
            $start = $this->request->getVar('startdate');
            $end = $this->request->getVar('enddate');



            //ASSIGNED PENDING LEADS
            $builder = $this->db->table('leads');
            $builder->select('count(lead_id) as lc');
            $builder->where('assigned', $uid);
            $builder->Where('status_id', 1);
            $builder->Where('DATE(lead_createdon) >=', $start);
            $builder->Where('DATE(lead_createdon) <=', $end);
            $query = $builder->get();
            $result = $query->getRow();
            $assigned =  $result->lc;

            //COUNTS BASED ON CALL PURPOSE CRATED BY LOGGED USER
            $builder = $this->db->table('leads');
            $builder->select('sum(case when purpose_id = 1 then 1 else 0 end ) As appoint, sum(case when purpose_id = 2 then 1 else 0 end ) As camp, sum(case when purpose_id = 3 then 1 else 0 end ) As service,
        sum(case when purpose_id = 4 then 1 else 0 end ) As parts,sum(case when purpose_id = 5 then 1 else 0 end ) As compl,sum(case when purpose_id = 6 then 1 else 0 end ) As feedback,sum(case when purpose_id = 7 then 1 else 0 end ) As other', FALSE);
            $builder->Where('DATE(lead_createdon) >=', $start);
            $builder->Where('DATE(lead_createdon) <=', $end);
            $builder->where('lead_createdby', $uid);
            $query = $builder->get();
            $result = $query->getResultArray();


            //NEW CUSTOMERS
            $builder = $this->db->table('leads');
            $builder->select('count(lead_id) as new');
            $builder->where('cus_id', 0);
            $builder->where('lead_createdby', $uid);
            $builder->Where('DATE(lead_createdon) >=', $start);
            $builder->Where('DATE(lead_createdon) <=', $end);
            $query = $builder->get();
            $resultn = $query->getRow();
            $new =  $resultn->new;

            //Existing CUSTOMERS
            $builder = $this->db->table('leads');
            $builder->select('count(lead_id) as ext');
            $builder->where('cus_id !=', 0);
            $builder->where('lead_createdby', $uid);
            $builder->Where('DATE(lead_createdon) >=', $start);
            $builder->Where('DATE(lead_createdon) <=', $end);
            $query = $builder->get();
            $resultn = $query->getRow();
            $ext =  $resultn->ext;

            //ACTIVE CUSTOMERS
            $builder = $this->db->table('leads');
            $builder->select('count(lead_id) as active');
            $builder->where('jc_status', 1);
            $builder->where('lead_createdby', $uid);
            $builder->Where('DATE(lead_createdon) >=', $start);
            $builder->Where('DATE(lead_createdon) <=', $end);
            $query = $builder->get();
            $resultn = $query->getRow();
            $active =  $resultn->active;

            //LOST LEADS
            $builder = $this->db->table('leads');
            $builder->select('count(lead_id) as lost');
            $builder->where('status_id', 6);
            $builder->where('conv_cust_by', $uid);
            $builder->Where('DATE(lead_createdon) >=', $start);
            $builder->Where('DATE(lead_createdon) <=', $end);
            $query = $builder->get();
            $resultn = $query->getRow();
            $lost =  $resultn->lost;

            //CONVERTED LEADS
            $builder = $this->db->table('leads');
            $builder->select('count(lead_id) as conv');
            $builder->where('status_id', 5);
            $builder->where('lead_createdby', $uid);
            $builder->Where('conv_cust_on >=', $start);
            $builder->Where('conv_cust_on <=', $end);
            $query = $builder->get();
            $resultn = $query->getRow();
            $conv =  $resultn->conv;

            $response = [
                'ret_data' => 'success',
                'appointLead' => $result[0]['appoint'],
                'campLead' => $result[0]['camp'],
                'servLead' => $result[0]['service'],
                'partsLead' => $result[0]['parts'],
                'complLead' => $result[0]['compl'],
                'feedLead' => $result[0]['feedback'],
                'otherLead' => $result[0]['other'],
                'assignLead' => $assigned,
                'lostLead' => $lost,
                'convLead' => $conv,
                'newCus' => $new,
                'extCus' => $ext,
                'activeCus' => $active
            ];
            return $this->respond($response, 200);
        }
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

    public function getDashCounts()
    {
        $lostcustomermodel = new LostCustomerModel();
        $dissatisfiedmastermodel = new DissatisfiedMasterModel();
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

            $start = $this->request->getVar('start_day');
            $due = $this->request->getVar('end_day');

            $lostlistconverted = $lostcustomermodel->where("str_to_date(lcst_due_date, '%d/%m/%Y')  >=", $start)
                ->where("str_to_date(lcst_due_date_to, '%d/%m/%Y')  <=", $due)
                ->where('job_status', 'INV')
                ->where("str_to_date(job_open_date, '%d-%M-%y')  >=", $start)
                ->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no=customer_code', 'left')
                ->groupby('customer_code')
                ->orderby('customer_code', 'DESC')
                ->select('customer_code')
                ->limit(1)
                ->countAllResults();

            $dissatisfied_cust = $dissatisfiedmastermodel->where('ldm_created_on >=', $start)
                ->where('ldm_created_on <=', $due)
                ->select('ldm_status', 'ldm_assign', 'ldm_psf_id', 'ldm_ldl_id')
                ->findAll();



            if ($lostlistconverted && $dissatisfied_cust) {
                $response = [
                    'ret_data' => 'success',
                    'lostlistconverted' =>  $lostlistconverted,
                    'dissatisfied_cust' => $dissatisfied_cust

                ];
                return $this->respond($response, 200);
            } else if ($dissatisfied_cust) {
                $response = [
                    'ret_data' => 'success',
                    'lostlistconverted' =>  0,
                    'dissatisfied_cust' => $dissatisfied_cust

                ];
                return $this->respond($response, 200);
            } else if ($lostlistconverted) {
                $response = [
                    'ret_data' => 'success',
                    'lostlistconverted' =>  $lostlistconverted,
                    'dissatisfied_cust' => []

                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'lostlistconverted' => 0,
                    'dissatisfied_cust' => []

                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function leadstocust()
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

            $leads = new  LeadModel();
            $start_date = $this->request->getVar('start_date');
            $end_date = $this->request->getVar('end_date');


            $leads_convert = $leads->select('lead_id,lead_code,purpose_id,customer_master.cust_name,source_id,status_id,DATE(lead_createdon) as lead_createdon,DATE(lead_updatedon) as lead_updatedon,RIGHT(phone,7) as phon_uniq,customer_master.cust_alm_code,cust_job_data_laabs.job_status,customer_master.cust_phone as phone,cust_job_data_laabs.invoice_date')
                ->join('customer_master', 'RIGHT(customer_master.cust_phone,9)= RIGHT(phone,9)')
                ->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no=customer_master.cust_alm_code')
                ->orderBy('lead_id', 'desc')
                ->groupBy('RIGHT(phone,9)')
                ->where('status_id', 5)
                ->where('cust_job_data_laabs.job_status', 'INV')
                ->where("str_to_date(cust_job_data_laabs.invoice_date, '%d-%M-%y')  >=  DATE(lead_createdon) ")
                ->where('DATE(lead_createdon) >=', $start_date)
                ->where('DATE(lead_createdon) <=', $end_date)
                ->where('lead_delete_flag', 0)
                ->findAll();


            if (sizeof($leads_convert) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'customers' => $leads_convert,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getAllLeads()
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

            $leads = new  LeadModel();
            $start_date = $this->request->getVar('start_date');
            $end_date = $this->request->getVar('end_date');

            $leads = $leads->select('purpose_id,lead_id,lead_code,phone,status_id')
                ->orderBy('lead_id', 'desc')
                ->groupBy('RIGHT(phone,9)')
                ->where('DATE(lead_createdon) >=', $start_date)
                ->where('DATE(lead_createdon) <=', $end_date)
                ->where('lead_delete_flag', 0)
                ->findAll();


            if (sizeof($leads) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'leads' => $leads,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    // public function getNewCustomers()
    // {
    //     $common = new Common();
    //     $valid = new Validation();
    //     $custmastermodel = new CustomerMasterModel();
    //     $custdatalaabs = new MaragiCustomerModel();

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
    //         $start_date = $this->request->getVar('start_date');
    //         $end_date = $this->request->getVar('end_date');
    //         $newCustomers = $custdatalaabs->where("DATE(cust_data_laabs.created_on) >=", $start_date)
    //             ->where("DATE(cust_data_laabs.created_on) <=", $end_date)
    //             ->select("customer_name,phone,customer_code,created_on,MONTH(created_on) as cmonth")
    //             ->orderBy("customer_code", "desc")
    //             ->groupBy("customer_code")
    //             ->findAll();

    //         if (sizeof($newCustomers) > 0) {
    //             $response = [
    //                 'ret_data' => 'success',
    //                 'newCustomers' => $newCustomers,
    //             ];
    //         } else {
    //             $response = [
    //                 'ret_data' => 'fail',
    //             ];
    //         }
    //         return $this->respond($response, 200);
    //     }
    // }

    public function getMonthlyDissatisfied()
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

            $wb_model = new WhatsappMessageModel();
            $psf_master = new PSFMasterModel();
            $psfstatustrackModel = new PSFstatusTrackModel();
            $dateFrom = $this->request->getVar('dateFrom');
            $dateTo = $this->request->getVar('dateTo');
            // $previousMonth = $this->request->getVar('previousMonth');

            $messages_currentMonth = $wb_model->where("wb_message_source", 1)
                ->select('whatsapp_message_master.*,cust_data_laabs.*,psf_master.*,users.us_firstname as sa,users.us_id as sa_id')
                ->join("cust_data_laabs", "cust_data_laabs.customer_code=wb_customer_id", "left")
                ->join("psf_master", "psf_master.psfm_primary_whatsapp_id=wb_id", "left")
                //->join("dissatisfied_master", "dissatisfied_master.ldm_psf_id=psf_master.psfm_id", "left")
                //->join('dissatisfied_log', 'dissatisfied_log.ldl_ldm_id =dissatisfied_master.ldm_id', 'left')
                //->join('psf_reason', 'psfr_id=dissatisfied_log.ldl_response', 'left')
                ->join("users", "users.us_laabs_id=psf_master.psfm_sa_id", "left")
                ->where('psfm_sa_id', $this->request->getVar('id'))
                ->whereIn('wb_replay_body', [1, 2])
                //->where('ldl_delete_flag !=', 1)
                ->where("DATE(wb_created_on)>=", $dateFrom)
                ->where("DATE(wb_created_on)<=",  $dateTo)
                ->findAll();


            $subquery = $this->db->table('psf_call_history')
                ->select('MAX(psf_call_id) as latest_call_id, psf_id')
                ->groupBy('psf_id')
                ->getCompiledSelect();

            // Main query
            $dissatisfied_cust = $this->db->table('psf_master')
                ->where('psfm_delete_flag', 0)
                ->where("DATE(psfm_psf_assign_date) >=", $dateFrom)
                ->where("DATE(psfm_psf_assign_date) <=", $dateTo)
                ->where('psfm_sa_id', $this->request->getVar('id'))
                ->join('cust_data_laabs', 'cust_data_laabs.customer_code = psf_master.psfm_customer_code', 'left')
                ->join('users', 'users.us_id = psf_master.psfm_cre_id', 'left')
                ->join('psf_status_tracker', 'psf_status_tracker.pst_psf_id = psf_master.psfm_id', 'left')
                ->join("($subquery) as latest_calls", 'latest_calls.psf_id = psf_master.psfm_id', 'left')
                ->join('psf_call_history', 'psf_call_history.psf_call_id = latest_calls.latest_call_id', 'left')
                ->join('psf_reason', 'psf_reason.psfr_id = psf_call_history.psf_reason', 'left')
                ->where('psf_status_tracker.pst_psf_call_type', 0)
                ->where('psf_status_tracker.pst_response', 5)
                ->where('psf_call_history.psf_response', 5)
                ->select('cust_data_laabs.*, users.us_id, users.us_firstname, users.us_phone, users.us_role_id, psf_reason.psfr_name as response, psf_master.*, psf_status_tracker.*, psf_call_history.*, psf_call_history.psf_remark as ldl_note')
                ->groupBy('psf_master.psfm_id')
                ->get()
                ->getResult();



            // $dissatisfied_cust = $psf_master->where('psfm_delete_flag', 0)
            //     ->where("DATE(psfm_psf_assign_date)>=", $dateFrom)
            //     ->where("DATE(psfm_psf_assign_date)<=", $dateTo)
            //     ->where('psfm_sa_id', $this->request->getVar('id'))
            //     ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
            //     ->join('users', 'users.us_id=psfm_cre_id', 'left')
            //     ->join('psf_status_tracker', 'pst_psf_id = psfm_id', 'left')
            //     ->join('psf_call_history', 'psf_call_history.psf_id = psfm_id', 'left')
            //     ->join('psf_reason', 'psfr_id=psf_call_history.psf_reason', 'left')
            //     ->where('pst_psf_call_type', 0)
            //     ->where('pst_response', 5)
            //     ->where('psf_response', 5)
            //     // ->join("dissatisfied_master", "dissatisfied_master.ldm_psf_id=psf_master.psfm_id", "left")
            //     // ->join('dissatisfied_log', 'dissatisfied_log.ldl_ldm_id =dissatisfied_master.ldm_id', 'left')
            //     // ->join('psf_reason', 'psfr_id=dissatisfied_log.ldl_response', 'left')
            //     //->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
            //     //->where("rm_id", 5)
            //     // ->where('ldl_delete_flag !=', 1)
            //     ->select('cust_data_laabs.*,us_id,
            //     us_firstname,us_phone,us_role_id,
            //     psfr_name as response,psf_master.*,psf_status_tracker.*,psf_call_history.*,psf_remark as ldl_note')
            //     //->orderBy('pst_id', 'DESC')
            //     ->groupBy('psfm_id')
            //     ->findAll();


            // $index = 0;
            // foreach ($dissatisfied_cust as &$dis_cust) {
            //     $psfstatustracker = $psfstatustrackModel
            //         ->where("pst_psf_id", $dis_cust['psfm_id'])
            //         ->where('pst_psf_call_type', 0)
            //         ->orderBy('pst_id', 'DESC')
            //         ->join('psf_response_master', 'rm_id=pst_response', 'left')
            //         ->select('psf_status_tracker.*,psf_response_master.*')
            //         ->first();
            //     $dis_cust = array_merge($dis_cust, $psfstatustracker);
            //     $index++;
            // }

            // $messages_previousMonth = $wb_model->where("wb_message_source", 1)
            //     ->select('whatsapp_message_master.*,cust_data_laabs.*,psf_master.*,users.us_firstname as sa,users.us_id as sa_id')
            //     ->join("cust_data_laabs", "cust_data_laabs.customer_code=wb_customer_id", "left")
            //     ->join("psf_master", "psf_master.psfm_primary_whatsapp_id=wb_id", "left")
            //     ->join("users", "users.us_laabs_id=psf_master.psfm_sa_id", "left")
            //     ->where('psfm_sa_id', $this->request->getVar('id'))
            //     ->whereIn('wb_replay_body', [1, 2])
            //     ->where("MONTH(STR_TO_DATE(psfm_invoice_date, '%d-%b-%y'))", $previousMonth)
            //     ->findAll();



            if (sizeof($messages_currentMonth) > 0 && $dissatisfied_cust) {
                $response = [
                    'ret_data' => 'success',
                    'messages_currentMonth' => $messages_currentMonth,
                    'dis_cust' => $dissatisfied_cust,
                ];
            } else if (sizeof($messages_currentMonth) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'messages_currentMonth' => $messages_currentMonth,
                    'dis_cust' => [],
                ];
            } else if ($dissatisfied_cust) {
                $response = [
                    'ret_data' => 'success',
                    'messages_currentMonth' => [],
                    'dis_cust' => $dissatisfied_cust,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'messages_currentMonth' => [],
                    'dis_cust' => [],

                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getSaRating()
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

            $wb_model = new WhatsappMessageModel();
            $psfHistoryModel = new PSFCallHistoryModel();

            $subquery = $this->db->table('psf_call_history')
                ->select('MAX(psf_call_id) as latest_call_id, psf_id')
                ->groupBy('psf_id')
                ->getCompiledSelect();



            $messages = $this->db->table('whatsapp_message_master')
                ->where('wb_message_source', 1)
                ->join('cust_data_laabs', 'cust_data_laabs.customer_code = wb_customer_id', 'left')
                ->join('psf_master', 'psf_master.psfm_primary_whatsapp_id = wb_id')
                ->join('users', 'users.us_laabs_id = psf_master.psfm_sa_id', 'left')
                ->join("($subquery) as latest_calls", 'latest_calls.psf_id = psf_master.psfm_id', 'left')
                ->join('psf_call_history', 'psf_call_history.psf_call_id = latest_calls.latest_call_id', 'left')
                ->where('DATE(psfm_cre_assign_date) >=', $this->request->getVar('dateFrom'))
                ->where('DATE(psfm_cre_assign_date) <=', $this->request->getVar('dateTo'))
                ->where('psf_master.psfm_sa_id', $this->request->getVar('id'))
                ->orderBy('psfm_cre_assign_date', 'desc')
                ->groupBy('psfm_id')
                ->select('whatsapp_message_master.*, cust_data_laabs.*, psf_master.*, users.us_firstname as sa, users.us_id as sa_id, psf_call_history.psf_response')
                ->get()
                ->getResult();

            // $messages = $wb_model->where("wb_message_source", 1)
            //     ->join("cust_data_laabs", "cust_data_laabs.customer_code=wb_customer_id", "left")
            //     ->join("psf_master", "psf_master.psfm_primary_whatsapp_id=wb_id")
            //     ->join("users", "users.us_laabs_id=psf_master.psfm_sa_id", "left")
            //     ->join("psf_call_history", "psf_call_history.psf_id=psf_master.psfm_id", "left")
            //     ->where("DATE(psfm_psf_assign_date)>=", $this->request->getVar('dateFrom'))
            //     ->where("DATE(psfm_psf_assign_date)<=", $this->request->getVar('dateTo'))
            //     ->where("psf_master.psfm_sa_id =", $this->request->getVar('id'))
            //     ->orderBy('psfm_psf_assign_date', 'desc')
            //     ->groupBy('psfm_id')
            //     ->select('whatsapp_message_master.*,cust_data_laabs.*,psf_master.*,users.us_firstname as sa,users.us_id as sa_id,psf_call_history.psf_response')
            //     ->findAll();

            if ($messages) {
                $response = [
                    'ret_data' => 'success',
                    'messages' => $messages,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'messages' => [],
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getRetentionCustomers()
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
        if ($tokendata) {
            $us_laab_id = $this->request->getVar('id');

            $cust_job_data_laabs_model = new MaraghiJobcardModel();

            // Subquery to get latest invoice per CUSTOMER (not vehicle)
            $subQuery = "
            SELECT
            customer_no,
            MAX(STR_TO_DATE(invoice_date, '%d-%b-%y')) AS max_inv_date
        FROM cust_job_data_laabs
        WHERE job_status = 'INV'
        GROUP BY customer_no
        ";

            // Fetch customers with their last invoice details
            $customers = $cust_job_data_laabs_model
                ->select("
            cust_job_data_laabs.*,
            STR_TO_DATE(cust_job_data_laabs.invoice_date, '%d-%b-%y') as invoice_date_d,
            cust_data_laabs.customer_name,
            cust_data_laabs.mobile,
            cust_data_laabs.customer_type,
            s.max_inv_date
        ")
                ->join("($subQuery) as s", "s.customer_no = cust_job_data_laabs.customer_no 
                AND STR_TO_DATE(cust_job_data_laabs.invoice_date, '%d-%b-%y') = s.max_inv_date")
                ->join("cust_data_laabs", "cust_data_laabs.customer_code = cust_job_data_laabs.customer_no", "left")
                ->where('cust_job_data_laabs.sa_emp_id', $us_laab_id)
                ->groupBy('cust_job_data_laabs.customer_no')
                ->orderBy("s.max_inv_date", "ASC", false)
                ->findAll();

            // ----------------------
            // Grouping Logic
            // ----------------------
            $currentMonth = new DateTime(); // e.g. Sep 2025
            $oneYearAgo   = (clone $currentMonth)->modify('-12 months'); // Aug 2024
            $twoYearsAgo  = (clone $currentMonth)->modify('-24 months'); // Sep 2023

            $grouped = [];
            $totalCount = 0;

            foreach ($customers as $cust) {
                $lastInvoiceDate = new DateTime($cust['invoice_date_d']);
                $label = $lastInvoiceDate->format('M y'); // Example: "Sep 24"

                if ($lastInvoiceDate >= $twoYearsAgo && $lastInvoiceDate < $oneYearAgo) {
                    // Month-wise group
                    if (!isset($grouped[$label])) {
                        $grouped[$label] = [];
                    }
                    $grouped[$label][] = $cust;
                    $totalCount++;
                } elseif ($lastInvoiceDate < $twoYearsAgo) {
                    // Old bucket
                    if (!isset($grouped['Old'])) {
                        $grouped['Old'] = [];
                    }
                    $grouped['Old'][] = $cust;
                    $totalCount++;
                }

            }


            // ----------------------
            // Rebuild ordered output (force clean order)
            // ----------------------
            $ordered = [];

            // Collect all month keys from grouped (skip Old)
            $monthKeys = array_keys($grouped);
            $monthKeys = array_filter($monthKeys, fn($k) => $k !== 'Old');

            // Sort keys by date descending (latest first)
            usort($monthKeys, function ($a, $b) {
                $da = \DateTime::createFromFormat('M y', $a);
                $db = \DateTime::createFromFormat('M y', $b);
                return $db <=> $da; // latest first
            });

            // Rebuild clean
            foreach ($monthKeys as $key) {
                $ordered[$key] = $grouped[$key];
            }

            // Add Old bucket if exists
            if (isset($grouped['Old'])) {
                $ordered['Old'] = $grouped['Old'];
            }

            // Add totalCount
            $ordered['totalCount'] = $totalCount;


            if ($grouped) {
                $response = [
                    'ret_data' => 'success',
                    'customers' => $ordered,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'customers' => [],
                ];
            }
            return $this->respond($response, 200);
        }
    }
}
