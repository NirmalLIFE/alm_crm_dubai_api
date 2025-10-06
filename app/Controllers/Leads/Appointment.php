<?php

namespace App\Controllers\Leads;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Leads\LeadModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use App\Models\Leads\AppointmentMasterModel;
use App\Models\Leads\AppointmentModel;
use App\Models\Leads\AppointmentLogModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Customer\MaraghiVehicleModel;
use App\Models\Customer\MaraghiJobcardModel;
use Config\Common;
use Config\Validation;

class Appointment extends ResourceController
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
        //create an appointment
        $leadmodel = new LeadModel();
        $ApptMaster = new AppointmentMasterModel();
        $Appoint = new AppointmentModel();
        $Appointmentlog = new AppointmentLogModel();
        $cust_mastr_model = new CustomerMasterModel();
        $maraghi_cust_model = new MaragiCustomerModel();
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
            $ph = substr($phone, -9);
            $patern = $ph;
            $builder = $this->db->table('sequence_data');
            $builder->selectMax('current_seq');
            $query = $builder->get();
            $row_lead = $query->getRow();
            $code = $row_lead->current_seq;
            $seqvalfinallead = $row_lead->current_seq;
            if (strlen($row_lead->current_seq) == 1) {
                $code = "ALMLD-000" . $row_lead->current_seq;
            } else if (strlen($row_lead->current_seq) == 2) {
                $code = "ALMLD-00" . $row_lead->current_seq;
            } else if (strlen($row_lead->current_seq) == 3) {
                $code = "ALMLD-0" . $row_lead->current_seq;
            } else {
                $code = "ALMLD-" . $row_lead->current_seq;
            }
            $this->db->transStart();
            $cust_id = 0;
            $social_Media_Source = $this->request->getVar('social_media_source') ? $this->request->getVar('social_media_source') : '0';
            $smc_id = $this->request->getVar('social_media_camp') ? $this->request->getVar('social_media_camp') : '0';
            $data = [
                //'lead_code' => $code,
                'phone' => $this->request->getVar('phone'),
                'status_id' => 1,
                'source_id' => $this->request->getVar('source'),  // From Lead Source
                'lead_social_media_source' => $social_Media_Source,
                'lead_social_media_mapping' => $smc_id,
                'purpose_id' => 1,  // Appointment
                'lang_id' => 1,
                'cus_id' =>  $cust_id,
                'vehicle_model' => $this->request->getVar('vehicle_model'),
                'register_number' => $this->request->getVar('reg_no'),
                'lead_note' => $this->request->getVar('appt_note'),
                'assigned' => $this->request->getVar('appt_assign_to'),
                'ld_appoint_time' => $this->request->getVar('appt_time'),
                'ld_appoint_date' => $this->request->getVar('appt_date'),
                'lead_createdby' => $tokendata['uid'],
                'lead_createdon' => date("Y-m-d H:i:s"),
                'lead_creted_date' => date("Y-m-d H:i:s"),
                'lead_updatedon' => date("Y-m-d H:i:s"),

            ];
            $resC = $cust_mastr_model->where('RIGHT(cust_phone,9)', $patern)->first();
            if ($resC) {
                $resC['cust_address'] = $resC['cust_address'] == "" ? "Nil" : $resC['cust_address'];
                // return $this->respond($resC['cust_address']==null?"hh":"nn", 200);
                $merge = [
                    'cus_id' =>  $resC['cus_id'],
                    'name' => $resC['cust_name'],
                    'address' => $resC['cust_address'],
                ];
                $data = array_merge($data, $merge);
            } else {
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
                    $data = array_merge($data, $custId);
                    // $maraghi_data = $maraghi_cust_model->where('RIGHT(phone,9)', $patern)
                    //     ->join('customer_type', 'customer_type.cst_code = customer_type')
                    //     ->join('country_master', 'country_master.country_code = country')
                    //     ->select('customer_code,cst_id,customer_title,customer_name,addr1,city,country_master.id,phone')
                    //     ->first();
                    // if ($maraghi_data) {

                    // }
                } else {
                    $custData = [
                        'cust_name' => $this->request->getVar('cust_name'),
                        'cust_phone' => $this->request->getVar('phone'),
                        'cust_alternate_no' => $this->request->getVar('phone'),
                        'cust_source' =>  $this->request->getVar('source')
                    ];
                    $ins_id = $cust_mastr_model->insert($custData);
                    $custId = [
                        'cus_id' =>  $ins_id,
                        'name' => $this->request->getVar('cust_name'),
                        'cust_phone' => $this->request->getVar('phone'),
                    ];
                    $data = array_merge($data, $custId);
                }
            }

            $activeLead_id = $leadmodel->where('RIGHT(phone,9)', $ph)->select('lead_id')
                ->where('status_id', 1)->first();

            if ($activeLead_id) {
                $leadmodel->where('lead_id', $activeLead_id)->set($data)->update();
                $lead_id = $leadmodel->where('lead_id', $activeLead_id)->select('lead_id')->first();
            } else {
                $insertLeadCode = [
                    'lead_code' => $code,
                ];
                $data = array_merge($data, $insertLeadCode);
                $lead_id = $leadmodel->insert($data);
                $builder = $this->db->table('sequence_data');
                $builder->set('current_seq', ++$seqvalfinallead);
                $builder->update();
            }



            if ($lead_id) {
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
                    'apptm_transport_service' =>  $this->request->getVar('apptm_transport_service'),
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
                        'appt_date' => $this->request->getVar('appt_date'),
                        'appt_time' => $this->request->getVar('appt_time'),
                        'appt_assign_to' => $this->request->getVar('appt_assign_to'),
                        'appt_note' => $this->request->getVar('appt_note'),
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
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->db->transCommit();
                $data = [
                    'ret_data' => 'success',
                    'appointments' => $result,
                    'users' => $lead_id,
                ];
                return $this->respond($data, 200);
            }
            // if ($result) {
            //     $response = [
            //         'ret_data' => 'success',
            //         'appointments' => $result,
            //         'users' => $lead_id,
            //     ];
            // } else {
            //     $response = [
            //         'ret_data' => 'fail',
            //     ];
            // }
            // return $this->respond($response, 200);
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


    public function getAppointmentCalls()
    {

        $model = new LeadModel();
        $appointment = new AppointmentMasterModel();
        $appoint = new AppointmentModel();
        $maraghiJobcards = new MaraghiJobcardModel();
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
            // $Appt_day = $this->request->getVar('Appt_day');
            // if ($Appt_day) {
            //     $Apptcalls = $appointment->whereIn('apptm_status', $status)
            //         // ->where('DATE(apptm_created_on) <=', $Appt_day)
            //         ->where('appointment.appt_date =', $Appt_day)
            //         ->where('appointment.appt_status =', 0)
            //         ->where('apptm_delete_flag!=', 1)
            //         ->join('leads', 'leads.lead_id = apptm_lead_id', 'left')
            //         ->join('appointment', 'appointment.appt_apptm_id = apptm_id', 'left')
            //         ->select('apptm_id,apptm_lead_id,apptm_status,apptm_transport_service,apptm_created_on,
            //     apptm_created_by,apptm_updated_on,apptm_updated_by,apptm_delete_flag,lead_code,
            //     name as cust_name,phone as call_from,status_id as lead_status,appt_assign_to,appt_note,appt_time,appt_date,appt_created_by')
            //         ->findAll();

            //     foreach ($Apptcalls as $appt) {
            //         $countAppointments = $appoint->where("appt_apptm_id", $appt['apptm_id'])->countAllResults();
            //         $appt['appt_count'] = $countAppointments;
            //     }
            // } else {

            $dateFrom = $this->request->getVar('dateFrom');
            $dateTo = $this->request->getVar('dateTo');
            $status = $this->request->getVar('status');
            $us_id  = $this->request->getVar('us_id');
            $ap_type  = $this->request->getVar('type');

            if ($ap_type == "1") {
                $Apptcalls = $appointment->whereIn('apptm_status', $status)
                    ->where('appointment.appt_status =', 0)
                    ->where('apptm_delete_flag !=', 1)
                    // ->where('DATE(appt_date)>=', $this->request->getVar('dateFrom'))
                    // ->where('DATE(appt_date)<=', $this->request->getVar('dateTo'))
                    ->where('appt_assign_to', $us_id)
                    ->join('leads', 'leads.lead_id = apptm_lead_id', 'left')
                    ->join('lead_source', 'lead_source.ld_src_id = leads.source_id', 'left')
                    ->join('appointment', 'appointment.appt_apptm_id = apptm_id', 'left')
                    ->join('users', 'users.us_id =appointment.appt_assign_to', 'left')
                    ->join('cust_data_laabs', 'cust_data_laabs.customer_code=apptm_customer_code', 'left')
                    ->join('dissatisfied_master', 'dissatisfied_master.ldm_id=apptm_diss_id', 'left')
                    ->join('psf_master', 'psf_master.psfm_id=ldm_psf_id', 'left')
                    ->orderBy('appt_id', 'DESC')
                    ->select('apptm_id,apptm_lead_id,apptm_status,apptm_transport_service,apptm_created_on,source_id,
                apptm_created_by,apptm_updated_on,apptm_updated_by,apptm_delete_flag,lead_code,users.us_firstname,
                name ,leads.phone as lphone,status_id as lead_status,apptm_code,appt_assign_to,appt_note,apptm_group,
                appt_time,appt_date,apptm_customer_code,apptm_pickup_mode,appt_created_by,apptm_type,cust_data_laabs.customer_name,
                cust_data_laabs.phone as cphone,ld_src as lead_source,vehicle_model,register_number,ldm_psf_id,psfm_reg_no,
                ld_verify_flag');
                // ->findAll();

                if (!empty($dateFrom)) {
                    $Apptcalls->where('DATE(appt_date) >=', $dateFrom);
                }

                if (!empty($dateTo)) {
                    $Apptcalls->where('DATE(appt_date) <=', $dateTo);
                }

                $Apptcalls = $Apptcalls->findAll();
                $index = 0;
                foreach ($Apptcalls as $appt) {
                    $countAppointments = $appoint->where("appt_apptm_id", $appt['apptm_id'])->countAllResults();
                    $Apptcalls[$index]["appt_count"] = $countAppointments;
                    $index++;
                }
            } else {

                // 1) First‑job sub‑query
                $firstJobSub = $this->db
                    ->table('cust_job_data_laabs')
                    ->select("
                    customer_no,
                    MIN(STR_TO_DATE(job_open_date, '%d-%b-%y')) AS first_job_open_date
                ")
                    ->groupBy('customer_no');



                $appointment->select([
                    'apptm_id',
                    'apptm_lead_id',
                    'apptm_status',
                    'apptm_transport_service',
                    'apptm_created_on',
                    'source_id',
                    'apptm_created_by',
                    'apptm_updated_on',
                    'apptm_updated_by',
                    'apptm_delete_flag',
                    'lead_code',
                    'users.us_firstname',
                    'name',
                    'leads.phone AS lphone',
                    'status_id AS lead_status',
                    'apptm_code',
                    'appt_assign_to',
                    'appt_note',
                    'apptm_group',
                    'appt_time',
                    'appt_date',
                    'appt_created_by',
                    'apptm_type',
                    'cust_data_laabs.customer_name',
                    'cust_data_laabs.phone AS cphone',
                    'ld_src AS lead_source',
                    'apptm_pickup_mode',
                    'vehicle_model',
                    'register_number',
                    'ldm_psf_id',
                    'psfm_reg_no',
                    'cust_data_laabs.customer_code',
                    'fj.first_job_open_date',
                    'appt_created_on',
                    'ld_verify_flag',
                    "CASE
                  WHEN fj.first_job_open_date >= appointment.appt_date
                  OR cust_data_laabs.customer_code IS NULL
                  THEN 'NEW'
                  ELSE 'EXISTING'
                  END AS customer_type"
                ])
                    ->whereIn('apptm_status', $status)
                    ->where('appointment.appt_status', 0)
                    ->where('apptm_delete_flag !=', 1)
                    ->join(
                        'leads',
                        'leads.lead_id = apptm_lead_id',
                        'left'
                    )
                    ->join(
                        'lead_source',
                        'lead_source.ld_src_id = leads.source_id',
                        'left'
                    )
                    ->join(
                        'appointment',
                        'appointment.appt_apptm_id = apptm_id',
                        'left'
                    )
                    ->join(
                        'users',
                        'users.us_id = appointment.appt_assign_to',
                        'left'
                    )
                    ->join(
                        'cust_data_laabs',
                        'RIGHT(cust_data_laabs.phone,9) = RIGHT(leads.phone,9)',
                        'left'
                    )
                    ->join(
                        'dissatisfied_master',
                        'dissatisfied_master.ldm_id = apptm_diss_id',
                        'left'
                    )
                    ->join(
                        'psf_master',
                        'psf_master.psfm_id = ldm_psf_id',
                        'left'
                    )
                    // inject the sub‑query as "fj" (disable automatic escaping)
                    ->join(
                        '(' . $firstJobSub->getCompiledSelect() . ') AS fj',
                        'fj.customer_no = cust_data_laabs.customer_code',
                        'left',
                        false
                    )
                    ->orderBy('appt_id', 'DESC');

                // 3) Apply your date filters with plain if‑statements
                if (! empty($dateFrom)) {
                    $appointment->where('DATE(appt_date) >=', $dateFrom);
                }
                if (! empty($dateTo)) {
                    $appointment->where('DATE(appt_date) <=', $dateTo);
                }

                // 4) Finally fetch all rows
                $Apptcalls = $appointment->findAll();


                // currently working query

                //     $Apptcalls = $appointment->whereIn('apptm_status', $status)
                //         ->where('appointment.appt_status', 0)
                //         ->where('apptm_delete_flag !=', 1)
                //         ->join('leads', 'leads.lead_id = apptm_lead_id', 'left')
                //         ->join('lead_source', 'lead_source.ld_src_id = leads.source_id', 'left')
                //         ->join('appointment', 'appointment.appt_apptm_id = apptm_id', 'left')
                //         ->join('users', 'users.us_id = appointment.appt_assign_to', 'left')
                //         ->join('cust_data_laabs', 'RIGHT(cust_data_laabs.phone, 9) = RIGHT(leads.phone, 9)', 'left')
                //         ->join('dissatisfied_master', 'dissatisfied_master.ldm_id = apptm_diss_id', 'left')
                //         ->join('psf_master', 'psf_master.psfm_id = ldm_psf_id', 'left')
                //         ->orderBy('appt_id', 'DESC')
                //         ->groupBy('appt_id')
                //         ->select('apptm_id, apptm_lead_id, apptm_status, apptm_transport_service, apptm_created_on,source_id,
                //   apptm_created_by, apptm_updated_on, apptm_updated_by, apptm_delete_flag, lead_code, users.us_firstname,
                //   name, leads.phone as lphone, status_id as lead_status, apptm_code, appt_assign_to, appt_note, apptm_group,
                //   appt_time, appt_date, appt_created_by, apptm_type, cust_data_laabs.customer_name, cust_data_laabs.phone as cphone,
                //   ld_src as lead_source,apptm_pickup_mode, vehicle_model, register_number, ldm_psf_id, psfm_reg_no, cust_data_laabs.customer_code,');

                //     if (!empty($dateFrom)) {
                //         $Apptcalls->where('DATE(appt_date) >=', $dateFrom);
                //     }

                //     if (!empty($dateTo)) {
                //         $Apptcalls->where('DATE(appt_date) <=', $dateTo);
                //     }

                //     $Apptcalls = $Apptcalls->findAll();

                //Old  Query

                // $Apptcalls = $appointment->whereIn('apptm_status', $status)
                //     ->where('appointment.appt_status =', 0)
                //     ->where('apptm_delete_flag!=', 1)
                //     // ->where('DATE(appt_date)>=', $this->request->getVar('dateFrom'))
                //     // ->where('DATE(appt_date)<=', $this->request->getVar('dateTo'))
                //     ->join('leads', 'leads.lead_id = apptm_lead_id', 'left')
                //     ->join('lead_source', 'lead_source.ld_src_id = leads.source_id', 'left')
                //     ->join('appointment', 'appointment.appt_apptm_id = apptm_id', 'left')
                //     ->join('users', 'users.us_id =appointment.appt_assign_to', 'left')
                //     // ->join('cust_data_laabs', 'cust_data_laabs.customer_code=apptm_customer_code', 'left')
                //     ->join('cust_data_laabs', 'RIGHT(cust_data_laabs.phone,9)=RIGHT(leads.phone,9)', 'left')
                //     ->join('(SELECT * FROM cust_job_data_laabs cj1 
                //                   WHERE cj1.job_no = (SELECT MAX(cj2.job_no) 
                //                 FROM cust_job_data_laabs cj2 
                //                 WHERE cj2.customer_no = cj1.customer_no)
                //             ) AS latest_job', 'latest_job.customer_no = cust_data_laabs.customer_code', 'left')
                //     ->join('dissatisfied_master', 'dissatisfied_master.ldm_id=apptm_diss_id', 'left')
                //     ->join('psf_master', 'psf_master.psfm_id=ldm_psf_id', 'left')
                //     ->orderBy('appt_id', 'DESC')
                //     ->groupBy('appt_id')
                //     ->select('apptm_id,apptm_lead_id,apptm_status,apptm_transport_service,apptm_created_on,
                //     apptm_created_by,apptm_updated_on,apptm_updated_by,apptm_delete_flag,lead_code,users.us_firstname,
                //     name ,leads.phone as lphone,status_id as lead_status,apptm_code,appt_assign_to,appt_note,apptm_group,
                //     appt_time,appt_date,appt_created_by,apptm_type,cust_data_laabs.customer_name,cust_data_laabs.phone as cphone,
                //     ld_src as lead_source,vehicle_model,register_number,ldm_psf_id,psfm_reg_no,cust_data_laabs.customer_code,
                //     latest_job.job_no as latest_job_no, latest_job.job_open_date');
                // if (!empty($dateFrom)) {
                //     $Apptcalls->where('DATE(appt_date) >=', $dateFrom);
                // }

                // if (!empty($dateTo)) {
                //     $Apptcalls->where('DATE(appt_date) <=', $dateTo);
                // }

                // $Apptcalls = $Apptcalls->findAll();
                $index = 0;
                foreach ($Apptcalls as $appt) {
                    //$Apptcalls[$index]["jobcards"] = [];
                    // $jobcards =  $maraghiJobcards->where("customer_no", $appt['customer_code'])->orderBy('job_no', 'DESC')->findAll();
                    $countAppointments = $appoint->where("appt_apptm_id", $appt['apptm_id'])->countAllResults();
                    $Apptcalls[$index]["appt_count"] = $countAppointments;
                    // $Apptcalls[$index]["jobcards"] = $jobcards;
                    $index++;
                }
            }

            // }

            // $index = 0;
            // foreach ($Apptcalls as $appt) {
            //     $Apptcalls[$index]["appoints"] = $appoint->select('appt_assign_to,appt_note,appt_time,appt_date,appt_created_by')
            //         ->where("appt_apptm_id", $appt['apptm_id'])
            //         // ->where(" appt_date >=", $appt['DATE(apptm_created_on)'])
            //         ->orderBy('appt_created_on', 'DESC')->findAll();
            //     $index++;
            // }
            $userdetails = $usmodel->select('us_id,us_firstname,us_role_id')->findAll();

            if (sizeof($Apptcalls) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'appointments' => $Apptcalls,
                    'users' => $userdetails,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getAppointmentDetails()
    {
        $ApptMaster = new AppointmentMasterModel();
        $Appoint = new AppointmentModel();
        $Appointmentlog = new AppointmentLogModel();

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

            $id = $this->request->getVar('apptm_id');

            $customer = $ApptMaster->where('apptm_id', $id)
                ->where('appointment.appt_status =', 0)
                ->where('apptm_delete_flag!=', 1)
                ->join('leads', 'leads.lead_id = apptm_lead_id', 'left')
                ->join('lead_source', 'lead_source.ld_src_id = leads.source_id', 'left')
                ->join('appointment', 'appointment.appt_apptm_id = apptm_id', 'left')
                ->join('cust_data_laabs', 'cust_data_laabs.customer_code=apptm_customer_code', 'left')
                ->join('social_media_campaign_source', 'social_media_campaign_source.smcs_id=lead_social_media_source', 'left')
                ->join('social_media_campaign', 'social_media_campaign.smc_id=lead_social_media_mapping', 'left')
                ->select('apptm_customer_code,apptm_id,apptm_code,apptm_status,apptm_lead_id,apptm_status,
                apptm_transport_service,apptm_created_on,apptm_created_by,apptm_updated_on,apptm_updated_by,
                apptm_delete_flag,lead_code,name,apptm_type,leads.phone as lphone,status_id as lead_status,
                appt_date,appt_time,appt_note,appt_assign_to,leads.lead_code,cust_data_laabs.customer_name,apptm_group,
                cust_data_laabs.phone as cphone,ld_src as lead_source,leads.register_number,leads.lead_note,lead_social_media_source,lead_social_media_mapping,smc_name,smc_message,smcs_name,source_id,ld_verify_flag,lead_id')
                ->first();

            $AllAppoints = $Appoint->select('appt_assign_to,appt_note,appt_time,appt_date,appt_created_by')
                ->where("appt_apptm_id", $id)
                ->orderBy('appt_created_on', 'DESC')
                ->findAll();

            $AppointLog = $Appointmentlog->select('appointment_log.*,users.us_firstname')->where('applg_apptm_id', $id)->join('users', 'users.us_id =applg_created_by', 'left')->findAll();

            if ($customer) {
                $response = [
                    'ret_data' => 'success',
                    'details' => $customer,
                    'Appoints' => $AllAppoints,
                    'log' => $AppointLog,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }
    public function updateAppointmentDetails()
    {

        $leadmodel = new LeadModel();
        $ApptMaster = new AppointmentMasterModel();
        $Appoint = new AppointmentModel();
        $Appointmentlog = new AppointmentLogModel();

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
            $id = $this->request->getVar('appointment_id');
            $lead_id = $this->request->getVar('appointment_lead');
            $app_status = $this->request->getVar('appointment_status');
            $status = intval($app_status);

            if ($app_status == "3") //Reschedule
            {
                $data = [
                    'name' => $this->request->getVar('customer_name'),
                    'register_number' => $this->request->getVar('register_number'),
                    'assigned' => $this->request->getVar('rescheduleAssignee'),
                    'lead_updatedby' => $tokendata['uid'],
                    'ld_appoint_date' => $this->request->getVar('rescheduleDate'),
                    'ld_appoint_time' => $this->request->getVar('rescheduleTime'),
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                ];

                $leadupdate = $leadmodel->where('lead_id', $lead_id)->set($data)->update();

                $ApptMdata = [
                    'apptm_status' =>  $status,
                    'apptm_transport_service' =>  $this->request->getVar('reschedulePickDrop'),
                    'apptm_updated_by' => $tokendata['uid'],
                    'apptm_updated_on' => date("Y-m-d H:i:s"),
                ];
                $result = $ApptMaster->where('apptm_id', $id)->set($ApptMdata)->update();

                if ($result) {
                    $statusdata = [
                        'appt_status' => 1
                    ];
                    $appt_delete = $Appoint->where('appt_apptm_id', $id)->set($statusdata)->update();

                    if ($appt_delete) {
                        $Apptdata = [
                            'appt_apptm_id' => $id,
                            'appt_date' => $this->request->getVar('rescheduleDate'),
                            'appt_time' => $this->request->getVar('rescheduleTime'),
                            'appt_assign_to' => $this->request->getVar('rescheduleAssignee'),
                            // 'appt_note' => $this->request->getVar('note'),
                            'appt_created_by' => $tokendata['uid'],
                            'appt_created_on' => date("Y-m-d H:i:s"),
                        ];
                        $result1 = $Appoint->insert($Apptdata);
                    }
                }
                $note = "Appointment Rescheduled to " . $this->request->getVar('rescheduleDate') . " " . $this->request->getVar('rescheduleTime');
                $Logdata = [
                    'applg_apptm_id' => $id,
                    'applg_note' => $note,
                    'applg_created_by' => $tokendata['uid'],
                    'applg_created_on' => date("Y-m-d H:i:s"),
                    'applg_time' => date("Y-m-d H:i:s"),
                ];
                $logentry = $Appointmentlog->insert($Logdata);
            } else if ($app_status == "4") //Cancelled
            {
                $leaddata = [
                    'name' => $this->request->getVar('customer_name'),
                    'register_number' => $this->request->getVar('register_number'),
                    'status_id' => 6,
                    'lead_updatedby' => $tokendata['uid'],
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                ];
                $leadupdate = $leadmodel->where('lead_id', $lead_id)->set($leaddata)->update();

                $ApptMdata = [
                    'apptm_status' =>  $status,
                    'apptm_updated_by' => $tokendata['uid'],
                    'apptm_updated_on' => date("Y-m-d H:i:s"),
                ];
                $result = $ApptMaster->where('apptm_id', $id)->set($ApptMdata)->update();
                $Logdata = [
                    'applg_apptm_id' => $id,
                    'applg_note' => "Appointment Cancelled. The reason is " . $this->request->getVar('cancelReason'),
                    'applg_created_by' => $tokendata['uid'],
                    'applg_created_on' => date("Y-m-d H:i:s"),
                    'applg_time' => date("Y-m-d H:i:s"),
                ];

                $logentry = $Appointmentlog->insert($Logdata);
            } else if ($app_status == "5") //completed
            {
                $leaddata = [
                    'name' => $this->request->getVar('customer_name'),
                    'register_number' => $this->request->getVar('register_number'),
                    'status_id' => 5,
                    'lead_updatedby' => $tokendata['uid'],
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                ];
                $leadupdate = $leadmodel->where('lead_id', $lead_id)->set($leaddata)->update();

                $ApptMdata = [
                    'apptm_status' =>  $status,
                    'apptm_alternate_no' => $this->request->getVar('alternate_no'),
                    'apptm_updated_by' => $tokendata['uid'],
                    'apptm_updated_on' => date("Y-m-d H:i:s"),
                ];
                $result = $ApptMaster->where('apptm_id', $id)->set($ApptMdata)->update();

                $Logdata = [
                    'applg_apptm_id' => $id,
                    'applg_note' => "Appointment Completed. Job card number:" . $this->request->getVar('confirmJobNo'),
                    'applg_created_by' => $tokendata['uid'],
                    'applg_created_on' => date("Y-m-d H:i:s"),
                    'applg_time' => date("Y-m-d H:i:s"),
                    'applg_job_no' => $this->request->getVar('confirmJobNo'),
                ];
                $logentry = $Appointmentlog->insert($Logdata);
            } else if ($app_status == "6") //Appointment General Enquiry
            {
                $data = [
                    'name' => $this->request->getVar('cust_name'),
                    'phone' => $this->request->getVar('call_from'),
                    'lead_updatedby' => $tokendata['uid'],
                    'lead_note' => $this->request->getVar('lead_note'),
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                ];
                $leadupdateonly = $leadmodel->where('lead_id', $lead_id)->set($data)->update();

                $Logdata = [
                    'applg_apptm_id' => $id,
                    'applg_note' => "Appointment General Enquiry",
                    'applg_created_by' => $tokendata['uid'],
                    'applg_created_on' => date("Y-m-d H:i:s"),
                    'applg_time' => date("Y-m-d H:i:s"),
                ];

                $logentry = $Appointmentlog->insert($Logdata);
            } else {
                // Confirmed
                $leaddata = [
                    'name' => $this->request->getVar('customer_name'),
                    'register_number' => $this->request->getVar('register_number'),
                    'lead_updatedby' => $tokendata['uid'],
                    'lead_updatedon' => date("Y-m-d H:i:s"),
                ];
                $leadupdate = $leadmodel->where('lead_id', $lead_id)->set($leaddata)->update();

                $ApptMdata = [
                    'apptm_status' => $status,
                    //'apptm_updated_on' => date('Y-m-d H:i:s', time()),
                    'apptm_updated_by' => $tokendata['uid'],
                    'apptm_updated_on' => date("Y-m-d H:i:s"),
                ];
                $result = $ApptMaster->where('apptm_id', $id)->set($ApptMdata)->update();

                $Logdata = [
                    'applg_apptm_id' => $id,
                    'applg_note' => "Appointment Confirmed",
                    'applg_created_by' => $tokendata['uid'],
                    'applg_created_on' => date("Y-m-d H:i:s"),
                    'applg_time' => date("Y-m-d H:i:s"),
                ];

                $logentry = $Appointmentlog->insert($Logdata);
            }

            if (isset($result)) {
                if (isset($result1)) {
                    $response = [
                        'ret_data' => 'success',
                        'lead' => $leadupdate,
                        'apptM' => $result,
                        'Appt' => $result1,
                    ];
                } else {
                    $response = [
                        'ret_data' => 'success',
                        'apptM' => $result,
                    ];
                }
            } else if (isset($leadupdateonly)) {
                $response = [
                    'ret_data' => 'success',
                    'Leads' => $leadupdateonly,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }

            return $this->respond($response, 200);
        }
    }

    public function getDailyAppointments()
    {

        $leadmodel = new LeadModel();
        $ApptMaster = new AppointmentMasterModel();
        $Appoint = new AppointmentModel();

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

            // $start_day = $this->request->getVar('start_day');
            // $end_day = $this->request->getVar('end_day');
            $status = $this->request->getVar('status');
            $Appt_day = $this->request->getVar('Appt_day');
            $user_id = $tokendata['uid'];
            if ($Appt_day) {
                $Apptcalls = $ApptMaster->whereIn('apptm_status', $status)
                    // ->where('DATE(apptm_created_on) <=', $Appt_day)
                    ->where('appointment.appt_date =', $Appt_day)
                    ->where('appointment.appt_status =', 0)
                    ->where('apptm_delete_flag!=', 1)
                    ->where('appointment.appt_assign_to', $user_id)
                    ->join('leads', 'leads.lead_id = apptm_lead_id', 'left')
                    ->join('appointment', 'appointment.appt_apptm_id = apptm_id', 'left')
                    ->select('apptm_id,apptm_lead_id,apptm_status,apptm_transport_service,apptm_created_on,
                apptm_created_by,apptm_updated_on,apptm_updated_by,apptm_delete_flag,lead_code,
                name as cust_name,phone as call_from,status_id as lead_status,appt_date,appt_time,appt_note,')
                    ->findAll();
            } else {
                $Apptcalls = $ApptMaster->whereIn('apptm_status', $status)
                    // ->where('DATE(apptm_created_on) <=', $Appt_day)
                    // ->where('appointment.appt_date =', $Appt_day)
                    ->where('appointment.appt_status =', 0)
                    ->where('apptm_delete_flag!=', 1)
                    ->where('appointment.appt_assign_to', $user_id)
                    ->join('leads', 'leads.lead_id = apptm_lead_id', 'left')
                    ->join('appointment', 'appointment.appt_apptm_id = apptm_id', 'left')
                    ->select('apptm_id,apptm_lead_id,apptm_status,apptm_transport_service,apptm_created_on,
                    apptm_created_by,apptm_updated_on,apptm_updated_by,apptm_delete_flag,lead_code,
                    name as cust_name,phone as call_from,status_id as lead_status,appt_date,appt_time,appt_note,')
                    ->findAll();
            }

            if ($Apptcalls) {
                $response = [
                    'ret_data' => 'success',
                    'Appoints' => $Apptcalls,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function isExistingCustOrNot()
    {
        $leadmodel = new LeadModel();
        $cust_mastr_model = new CustomerMasterModel();
        $maraghi_cust_model = new MaragiCustomerModel();
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
            $ph = substr($phone, -9);

            $leads = $leadmodel->where('RIGHT(phone,9)', $ph)->where('status_id', 1)
                ->select('lead_id,apptm_code,apptm_id,lead_code,name,source_id,purpose_id,lead_note,assigned,lead_createdby,ld_appoint_time,ld_appoint_date,us.us_firstname as created,users.us_firstname as assigned_to,call_purpose,lead_source.ld_src,lead_status.ld_sts')
                ->join('call_purposes', 'call_purposes.cp_id=purpose_id', 'left')
                ->join('lead_source', 'lead_source.ld_src_id =source_id', 'left')
                ->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left')
                ->join('appointment_master', 'appointment_master.apptm_lead_id = lead_id', 'left')
                ->join('users as us', 'us.us_id =lead_createdby', 'left')
                ->join('users', 'users.us_id =assigned', 'left')
                ->findAll();


            $customer = $cust_mastr_model->where('RIGHT(cust_phone,9)', $ph)
                ->select('cus_id,cust_type,cust_name,cust_salutation,cust_address,cust_city,cust_phone,cust_alm_code as cust_code')
                ->first();
            if (!$customer) {
                $customer = $maraghi_cust_model->where('RIGHT(phone,9)', $ph)
                    ->join('customer_type', 'customer_type.cst_code = customer_type')
                    ->join('country_master', 'country_master.country_code = country')
                    ->select('customer_code as cust_code,cst_id,customer_title,customer_name as cust_name,addr1,city as cust_city,phone as cust_phone')
                    ->first();
            }


            if ($customer && $leads) {
                $response = [
                    'ret_data' => 'success',
                    'customer' => $customer,
                    'leads' => $leads,
                ];
            } else if ($customer) {
                $response = [
                    'ret_data' => 'success',
                    'customer' => $customer,
                    'leads' => [],
                ];
            } else if ($leads) {
                $response = [
                    'ret_data' => 'success',
                    'customer' => [],
                    'leads' => $leads,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'customer' => [],
                    'leads' => [],
                ];
            }
            return $this->respond($response, 200);
        }
    }


    public function getAppointmentReports()
    {
        $appointment = new AppointmentMasterModel();
        $appoint = new AppointmentModel();
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
            $us_id  = $this->request->getVar('us_id');
            $Apptcalls = $appointment->where('appointment.appt_status =', 0)
                ->where('apptm_delete_flag !=', 1)
                ->join('leads', 'leads.lead_id = apptm_lead_id', 'left')
                ->join('lead_call_log', 'lead_call_log.lcl_lead_id = apptm_lead_id', 'left')
                ->join('lead_source', 'lead_source.ld_src_id = leads.source_id', 'left')
                ->join('appointment', 'appointment.appt_apptm_id = apptm_id', 'left')
                ->join('users', 'users.us_id =appointment.appt_assign_to', 'left')
                ->join('cust_data_laabs', 'cust_data_laabs.customer_code=apptm_customer_code', 'left')
                ->join('dissatisfied_master', 'dissatisfied_master.ldm_id=apptm_diss_id', 'left')
                ->join('psf_master', 'psf_master.psfm_id=ldm_psf_id', 'left')
                ->orderBy('appt_id', 'DESC')
                ->groupBy('apptm_id')
                ->select('apptm_id,apptm_lead_id,apptm_status,apptm_transport_service,apptm_created_on,
                apptm_created_by,apptm_updated_on,apptm_updated_by,apptm_delete_flag,lead_code,users.us_firstname,
                name ,leads.phone as lphone,status_id as lead_status,apptm_code,appt_assign_to,appt_note,apptm_group,
                appt_time,appt_date,apptm_customer_code,appt_created_by,apptm_type,cust_data_laabs.customer_name,
                cust_data_laabs.phone as cphone,ld_src as lead_source,vehicle_model,register_number,ldm_psf_id,
                psfm_reg_no,lcl_call_source,lcl_phone,source_id');
            if (!empty($dateFrom)) {
                $Apptcalls->where('DATE(appt_date) >=', $dateFrom);
            }
            if ($us_id != 0) {
                $Apptcalls->where('appt_assign_to', $us_id);
            }
            if (!empty($dateTo)) {
                $Apptcalls->where('DATE(appt_date) <=', $dateTo);
            }
            $Apptcalls = $Apptcalls->findAll();
            $index = 0;
            foreach ($Apptcalls as $appt) {
                $countAppointments = $appoint->where("appt_apptm_id", $appt['apptm_id'])->countAllResults();
                $Apptcalls[$index]["appt_count"] = $countAppointments;
                $index++;
            }

            if (sizeof($Apptcalls) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'appointments' => $Apptcalls,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function Last7DaysAppointments()
    {
        $appointment = new AppointmentMasterModel();
        $appoint = new AppointmentModel();
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
            $us_id  = $this->request->getVar('us_id');
            $Apptcalls = $appointment->where('appointment.appt_status =', 0)
                ->where('apptm_delete_flag !=', 1)
                ->join('leads', 'leads.lead_id = apptm_lead_id', 'left')
                ->join('lead_call_log', 'lead_call_log.lcl_lead_id = apptm_lead_id', 'left')
                ->join('lead_source', 'lead_source.ld_src_id = leads.source_id', 'left')
                ->join('appointment', 'appointment.appt_apptm_id = apptm_id', 'left')
                ->join('users', 'users.us_id = apptm_created_by', 'left')
                ->groupBy('apptm_id')
                ->select('apptm_id,apptm_lead_id,apptm_status,apptm_transport_service,apptm_created_on,
                apptm_created_by,apptm_updated_on,apptm_updated_by,apptm_delete_flag,lead_code,users.us_firstname,
                name ,leads.phone as lphone,status_id as lead_status,apptm_code,appt_assign_to,appt_note,apptm_group,
                appt_time,appt_date,apptm_customer_code,appt_created_by,apptm_type,,ld_src as lead_source,vehicle_model,register_number,
                lcl_call_source,lcl_phone,source_id');
            if (!empty($dateFrom)) {
                $Apptcalls->where('DATE(appt_date) >=', $dateFrom);
            }
            if ($us_id != 0) {
                $Apptcalls->where('appt_assign_to', $us_id);
            }
            if (!empty($dateTo)) {
                $Apptcalls->where('DATE(appt_date) <=', $dateTo);
            }
            $Apptcalls = $Apptcalls->findAll();

            if (sizeof($Apptcalls) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'Last7DaysMarketingAppointment' => $Apptcalls,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function updateAppointmentRegNo()
    {

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

            $Reg_No = $this->request->getVar('Reg_No');
            $lead_id = $this->request->getVar('lead_id');


            $data = [
                'register_number' =>  $Reg_No,
                'lead_updatedby' => $tokendata['uid'],
                'lead_updatedon' => date("Y-m-d H:i:s"),
            ];

            $leadupdate = $leadmodel->where('lead_id', $lead_id)->set($data)->update();

            if ($leadupdate) {
                $response = [
                    'ret_data' => 'success',
                    'leadupdate' => $leadupdate,
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'leadupdate' => []
                ];
            }

            return $this->respond($response, 200);
        }
    }

    public function getLatestJobCard()
    {

        $model = new LeadModel();
        $appointment = new AppointmentMasterModel();
        $appoint = new AppointmentModel();
        $maraghiJobcards = new MaraghiJobcardModel();
        $customerModel = new MaragiCustomerModel();
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

            $codes = $this->request->getVar('customerCodes');
            $phones =  $this->request->getVar('phones');

            $latestJobcards = [];

            if (!empty($phones)) {
                $lastNineDigitsArray = array_map(function ($phone) {
                    return substr($phone, -9);
                }, $phones);

                // Fetch jobcards for all phones in a single query
                $latestJobcards = $customerModel
                    ->select('cust_job_data_laabs.*, cust_data_laabs.*') // Adjust fields as necessary
                    ->join('cust_job_data_laabs', 'customer_no = customer_code', 'left')
                    ->whereIn('RIGHT(phone, 9)', $lastNineDigitsArray)
                    ->orderBy('job_no', 'desc')
                    ->findAll();

                // Optionally filter or transform the result if needed
                $latestJobcards = array_filter($latestJobcards, function ($jobcard) {
                    return !empty($jobcard); // Adjust condition if required
                });
            } else {
                $latestJobcards = [];
            }

            // foreach ($phones as $phone) {
            //     $lastNineDigits = substr($phone, -9);
            //     $jobcard = $customerModel->where('RIGHT(phone,9)', $lastNineDigits)
            //         ->join('cust_job_data_laabs ', 'customer_no=customer_code', 'left')
            //         ->orderBy('job_no', 'desc')
            //         ->first();
            //     // $jobcard = $maraghiJobcards->where('customer_no', $code)
            //     //     ->orderBy('job_no', 'desc')
            //     //     ->first();
            //     if ($jobcard) {
            //         array_push($latestJobcards, $jobcard);
            //     }
            // }

            if (sizeof($latestJobcards) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'jobcards' => $latestJobcards,
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
