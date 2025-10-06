<?php

namespace App\Controllers\PSFModule;

use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use App\Models\PSFModule\PSFMasterModel;
use App\Models\Customer\MaraghiJobcardModel;
use App\Models\Customer\MaraghiJobModel;
use App\Models\PSFModule\PSFCallHistoryModel;
use App\Models\PSFModule\PSFreasonModel;
use App\Models\PSFModule\PSFresponseModel;
use App\Models\PSFModule\PSFstatusTrackModel;

class PSFCREAdminController extends ResourceController
{
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

    public function get_creDailyPSFCalls()
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
            $user_monthly_count = 0;
            $response["user_success_count"] = 0;
            $us_id = $this->request->getVar('us_id');
            if ($user['us_role_id'] == 9) {
                $user_psf = $psf_master->select('cust_data_laabs.customer_name,cust_data_laabs.phone,psfm_id,psfm_job_no,psfm_reg_no,psfm_invoice_date,psfm_psf_assign_date,psfm_cre_assign_date,psfm_status,psfm_num_of_attempts,psfm_sa_rating,psfm_cre_rating,rm_id,rm_name')
                    ->where('psfm_current_assignee',  $us_id)
                    ->where('psfm_delete_flag', 0)
                    ->where('psfm_assign_flag', 2)
                    ->where('(psfm_status=2 or psfm_status=4 or psfm_status=5 or psfm_status=6 or psfm_status=16)')
                    //->where('DATE(psfm_cre_assign_date) <= ','2023-05-02')
                    ->where('DATE(psfm_cre_assign_date) <= ', date('Y-m-d'))
                    ->where("DATE(psfm_cre_assign_date)  >", date('Y-m-d', strtotime("-6 day")))
                    ->join('cust_data_laabs', 'customer_code=psfm_customer_code', 'left')
                    ->join('psf_response_master', 'rm_id=psfm_lastresponse', 'left')
                    ->orderBy('psfm_cre_assign_date', 'asc')
                    ->findAll();
                $psf_tracker = new PSFstatusTrackModel();
                $user_monthly_count = $psf_tracker->select('COUNT(*) as exp_count')->where('pst_sourceid',  $us_id)
                    ->where('pst_psf_status', 10)->where("MONTH(pst_created_on)=", date('m'))
                    ->where("YEAR(pst_created_on)=", date('Y'))->first();
                $user_monthly_count_success = $psf_tracker->select('COUNT(*) as success_count')->where('pst_sourceid',  $us_id)
                    ->where('pst_psf_status', 7)->where("MONTH(pst_created_on)=", date('m'))
                    ->where("YEAR(pst_created_on)=", date('Y'))->first();
            } else {
                $user_psf = [];
            }
            if (sizeof($user_psf) > 0) {

                $response['query'] = $psf_master->getLastQuery()->getQuery();
                $psf_historyModel = new PSFCallHistoryModel();
                $i = 0;
                foreach ($user_psf as $value) {
                    $cre_count = $psf_historyModel
                        ->select('count(*) as cre_count')
                        ->where('psf_id', $value['psfm_id'])
                        ->where('psf_user_id', $us_id)
                        ->first();
                    $user_psf[$i]['cre_attempts'] = $cre_count['cre_count'];
                    $i++;
                }
                $response["ret_data"] = "success";
                $response["user_psf"] = $user_psf;
                $response["user_closed_count"] = $user_monthly_count['exp_count'];
                $response["user_success_count"] = $user_monthly_count_success['success_count'];
                return $this->respond($response, 200);
            } else {
                $response["ret_data"] = "success";
                $response["user_psf"] = [];
                $response["user_closed_count"] = 0;
                $response["user_success_count"] = 0;
                return $this->respond($response, 200);
            }
        }
    }

    public function get_CREPSFrecord_info()
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
            $psf_data = $psf_master->select("CONCAT(cust_data_laabs.customer_title,' ',cust_data_laabs.customer_name) as cus_name,cust_data_laabs.phone,psfm_id,psfm_vehicle_no,psfm_job_no,psfm_reg_no,psfm_invoice_date,psfm_psf_assign_date,psfm_status,psfm_num_of_attempts,psfm_customer_code,cust_veh_data_laabs.model_name,users.us_firstname,psfm_last_attempted_date,psfm_sa_rating")
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
                $cre_count = $psf_historyModel
                    ->select('count(*) as cre_count')
                    ->where('psf_id', $psf_data['psfm_id'])
                    ->where('psf_user_id', '19')
                    ->first();
                $psf_data['cre_attempts'] = $cre_count['cre_count'];
                $psf_data['current_jobcard'] = $maraghijobmodel->where('job_status', 'WIP')->where('customer_no', $psf_data['psfm_customer_code'])->findAll();

                // } else if ($user['us_role_id'] == 11) {
                // }
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
}
