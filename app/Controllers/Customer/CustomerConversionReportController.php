<?php

namespace App\Controllers\Customer;

use App\Models\Customer\MaraghiJobcardModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Leads\LeadModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\Customer\CustomerMasterModel;


class CustomerConversionReportController extends ResourceController
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

    public function getcustomerdatas()
    {

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

        $start_date = $this->request->getVar('start_date');
        $end_date = $this->request->getVar('end_date');
        $marcustmodel = new MaragiCustomerModel();
        $marjobmodel = new MaraghiJobcardModel();
        $custData = $marcustmodel->select("vehicle_id,customer_code, customer_type, customer_name, CONCAT('05',RIGHT(`phone`,8)) as phn, MONTH(cust_data_laabs.created_on) as cmonth, phon_uniq,clab.*")
            ->join('cust_job_data_laabs clab', 'clab.customer_no=cust_data_laabs.customer_code', 'left')
            ->where("DATE(cust_data_laabs.created_on) >=", $start_date)
            ->where("DATE(cust_data_laabs.created_on) <=", $end_date)
            // ->where("clab.job_status", 'INV')
            ->orderBy("cust_data_laabs.customer_code", "desc")
            ->groupBy("cust_data_laabs.customer_code")
            // ->limit(1)
            ->findAll();

        // $custData = $marcustmodel->select("customer_code, customer_type, customer_name, phone, created_on, phon_uniq")
        //     ->where('DATE(created_on) >=', $start_date)
        //     ->where('DATE(created_on) <=', $end_date)
        //     ->findAll();
        // $cust_info = [];
        // if (sizeof($custData) > 0) {
        //     foreach ($custData as $eachcust) {
        //         $cust_convert = $marjobmodel->select("job_no,customer_no,job_open_date,str_to_date(job_open_date, '%d-%M-%y'),job_status,invoice_date")
        //             ->where('customer_no', $eachcust['customer_code'])
        //             // ->where("str_to_date(job_open_date, '%d-%M-%y')  >=", $start_date)
        //             // ->where("str_to_date(job_open_date, '%d-%M-%y')  <=", $end_date)
        //             ->first();
        //         $eachcust['phone'] = "05" . substr($eachcust['phone'], -8);
        //         $eachcust['jobcards'] = $cust_convert?$cust_convert:[];
        //         array_push($cust_info, $eachcust);
        //     }
        // }
        // array_push($cust_info, $custData);

        if (sizeof($custData) > 0) {
            $response = [
                'ret_data' => 'success',
                'customers' => $custData,
            ];
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
        }
        return $this->respond($response, 200);
    }
    public function getexistingcustomerdata()
    {

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

        $start_date = $this->request->getVar('start_date');
        $end_date = $this->request->getVar('end_date');
        $marjobmodel = new MaraghiJobcardModel();
        $cust_info = [];
        // foreach($custData as $eachcust){
        $cust_list = $marjobmodel
            ->select("job_no,customer_no,job_open_date,str_to_date(job_open_date, '%d-%M-%y'),job_status,invoice_date,(clab.customer_name) as customer_name,(clab.phone) as phn")
            ->join('cust_data_laabs clab', 'clab.customer_code=customer_no',)
            ->where("str_to_date(job_open_date, '%d-%M-%y')  >=", $start_date)
            ->where("str_to_date(job_open_date, '%d-%M-%y')  <=", $end_date)->findAll();

        // }
        if (sizeof($cust_list) > 0) {
            $response = [
                'ret_data' => 'success',
                'customers' => $cust_list,
            ];
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
        }
        return $this->respond($response, 200);
    }

    public function getPreviouscustomer()
    {
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
        $year = $this->request->getVar('year');
        $month = $this->request->getVar('month');
        $marcustmodel = new MaragiCustomerModel();
        $prev_cust = [];
        for ($i = $month; $i >= 1; $i--) {
            $previousMonths = $marcustmodel->select("customer_code, customer_type, customer_name, CONCAT('05',RIGHT(`phone`,8)) as phn, MONTH(cust_data_laabs.created_on) as cmonth, phon_uniq,clab.*")
                ->join('cust_job_data_laabs clab', 'clab.customer_no=cust_data_laabs.customer_code', 'left')
                ->where("YEAR(cust_data_laabs.created_on) =", $year)
                ->where("MONTH(cust_data_laabs.created_on) =", $i)
                // ->where("clab.job_status", 'INV')
                ->orderBy("cust_data_laabs.customer_code", "desc")
                ->groupBy("cust_data_laabs.customer_code")
                ->limit(1)
                ->findAll();
            $marjobmodel = new MaraghiJobcardModel();
            // foreach ($previousMonths as $customer) {
            //     $custinv = $marjobmodel->select("job_no,customer_no,job_open_date,str_to_date(job_open_date, '%d-%M-%y'),job_status,invoice_date")
            //         ->where('customer_no', $customer['customer_code'])
            //         ->where('job_status', 'INV')
            //         // ->where('MONTH(STR_TO_DATE(job_open_date, "%d-%M-%y"))',$cust->cmonth)            
            //         ->first();
            //     $customer['job_card'] = $custinv;
            //     array_push($prev_cust,$customer);
            // }
            // $data = $previousMonths;
            array_push($prev_cust, $previousMonths);
        }
        if (sizeof($prev_cust) > 0) {
            $response = [
                'ret_data' => 'success',
                'previous_customers' => $prev_cust,
            ];
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
        }
        return $this->respond($response, 200);
    }
    public function getPreviouscustomerJobcard()
    {
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

        $marjobmodel = new MaraghiJobcardModel();
        $pcust = $this->request->getVar('oldcust');
        $month = $this->request->getVar('month');
        $data = [];
        foreach ($pcust as $eachcust) {
            $cust_info = [];
            foreach ($eachcust as $cust) {
                $cust->jobcards = [];
                $custinv = $marjobmodel->select("job_no,customer_no,job_open_date,str_to_date(job_open_date, '%d-%M-%y'),job_status,invoice_date")
                    ->where('customer_no', $cust->customer_code)
                    ->where('job_status', 'INV')
                    ->where('MONTH(STR_TO_DATE(job_open_date, "%d-%M-%y"))', $cust->cmonth)
                    ->findAll();

                $cust->jobcards = $custinv;
                array_push($cust_info, $cust);
            }
            array_push($data, $cust_info);
        }
        $response = [
            'ret_data' => 'success',
            'previous_job_card' => $data,
        ];
        return $this->respond($response, 200);
    }

    public function getexistingcustomer()
    {

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

        $start_date = $this->request->getVar('start_date');
        $end_date = $this->request->getVar('end_date');
        $marjobmodel = new MaraghiJobcardModel();
        // foreach($custData as $eachcust){
        $cust_list = $marjobmodel->select("job_no,customer_no,job_open_date,str_to_date(job_open_date, '%d-%M-%y'),job_status,invoice_date")
            ->join('cust_data_laabs', 'cust_data_laabs.customer_code = customer_no')
            ->where('job_status', 'INV')
            ->where("str_to_date(invoice_date, '%d-%M-%y')  >=", $start_date)
            ->where("str_to_date(invoice_date, '%d-%M-%y')  <=", $end_date)
            ->findAll();

        // }
        if (sizeof($cust_list) > 0) {
            $response = [
                'ret_data' => 'success',
                'customers' => $cust_list,
            ];
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
        }
        return $this->respond($response, 200);
    }

    public function getcustomerconvert()
    {
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
        $leads = new  LeadModel();
        $start_date = $this->request->getVar('start_date');
        $end_date = $this->request->getVar('end_date');


        $leads_convert = $leads->select('lead_id,lead_code,purpose_id,customer_master.cust_name,source_id,status_id,DATE(lead_createdon) as lead_createdon,DATE(lead_updatedon) as lead_updatedon,RIGHT(phone,7) as phon_uniq,customer_master.cust_alm_code,cust_job_data_laabs.job_status,customer_master.cust_phone as phone,cust_job_data_laabs.invoice_date')
            ->join('customer_master', 'RIGHT(customer_master.cust_phone,7)= RIGHT(phone,7)')
            ->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no=customer_master.cust_alm_code')
            ->orderBy('lead_id', 'desc')
            ->groupBy('RIGHT(phone,7)')
            //   ->whereNotIn('purpose_id', [4, 5, 6, 8])
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

    public function getCustomerAnalysisReport()
    {
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
        $CustomerMasterModel = new CustomerMasterModel();

        $dateFrom = $this->request->getVar('dateFrom');
        $dateTo = $this->request->getVar('dateTo');
        $source = $this->request->getVar('source');

        $customers = $CustomerMasterModel->select('*, cust_data_laabs.*, cust_job_data_laabs.*, IF(cust_source = 0, "Existing", lead_source.ld_src) as ld_src, DATE(cust_created_on) as created_on')
            ->join('cust_data_laabs', 'RIGHT(cust_data_laabs.phone,9) = RIGHT(cust_phone,9)')
            ->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no = cust_data_laabs.customer_code')
            ->join('lead_source', 'lead_source.ld_src_id = cust_source', 'left')
            ->where('DATE(cust_created_on) >=', $dateFrom)
            ->where('DATE(cust_created_on) <=', $dateTo)
            ->where('STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d-%b-%y") >= DATE(cust_created_on)', null)
            ->orderBy('customer_master.cus_id', 'desc')
            ->groupBy('RIGHT(cust_data_laabs.phone,9)');

        if ($source == 0) {
            $customers = $customers->where('cust_source', 0);
        } else {
            $customers = $customers->where('cust_source !=', 0);
        }

        $customers = $customers->findAll();


        $builder = $this->db->table('leads');
        $builder->select('
            lead_id, lead_code, customer_master.*, name, phone, vehicle_model, lead_note, source_id, 
            DATE(lead_createdon) as lead_createdon, status_id, lead_source.ld_src, lead_status.ld_sts, 
            users.us_firstname, ld_appoint_date, assigned, camp_name, ld_camp_id, ld_brand, 
            us.us_firstname as created, call_purpose, purpose_id, ld_appoint_time, apptm_id
        ');
        $builder->join('customer_master', 'RIGHT(customer_master.cust_phone,9) = RIGHT(phone,9)');
        $builder->join('users', 'users.us_id =assigned', 'left');
        $builder->join('users as us', 'us.us_id =lead_createdby', 'left');
        $builder->join('lead_status', 'lead_status.ld_sts_id =status_id', 'left');
        $builder->join('lead_source', 'lead_source.ld_src_id =source_id', 'left');
        $builder->join('call_purposes', 'call_purposes.cp_id =purpose_id', 'left');
        $builder->join('campaign', 'campaign.camp_id =ld_camp_id', 'left');
        $builder->join('appointment_master', 'appointment_master.apptm_lead_id =lead_id', 'left');
        $builder->where('lead_delete_flag', 0);
        $builder->where('status_id !=', 7);
        $builder->where('DATE(lead_creted_date) >=', $dateFrom);
        $builder->where('DATE(lead_creted_date) <=', $dateTo);
        
        if ($source == 0) {
            $builder->where('cust_source', 0);
        } else {
            $builder->where('cust_source !=', 0);
        }
        
        $builder->orderBy('lead_id', 'desc');
        $builder->limit(2000);
        
        $query = $builder->get();
        $res = $query->getResultArray();
        




        if (sizeof($customers) > 0) {
            $response = [
                'ret_data' => 'success',
                'customers' => $customers,
                'leads' => $res,
            ];
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
        }
        return $this->respond($response, 200);
    }
}
