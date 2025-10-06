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
use App\Models\Customer\MaraghiJobModel;


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
        $us_id  = $this->request->getVar('us_id');

        $laabsJob = new MaraghiJobModel();
        $customers = $laabsJob
            // ->where("str_to_date(job_open_date, '%d-%M-%y')  >=", $this->request->getVar('dateFrom'))
            // ->where("str_to_date(job_open_date, '%d-%M-%y')  <=", $this->request->getVar('dateTo'))
            ->join('cust_data_laabs', 'cust_data_laabs.customer_code=cust_job_data_laabs.customer_no', 'left')
            ->join('customer_master', 'customer_master.cust_alm_code=cust_job_data_laabs.customer_no', 'left')
            ->orderby('job_no', "desc");
        // ->findAll();

        if ($us_id != 0) {
            $customers->where('sa_emp_id', $us_id);
        }
        if (!empty($dateFrom)) {
            $customers->where("str_to_date(job_open_date, '%d-%M-%y')  >=", $dateFrom);
        }
        if (!empty($dateTo)) {
            $customers->where("str_to_date(job_open_date, '%d-%M-%y')  <=", $dateTo);
        }

        $customers = $customers->findAll();


        $uniqueArray = $customers;
        $job_cards = [];
        $job_cards1 = [];


        $startDate = $this->request->getVar('dateFrom');
        $laabsJobs = $laabsJob->select('*')
            ->where("str_to_date(job_open_date, '%d-%M-%y') <", $startDate)
            ->where('job_status', 'INV')
            ->orderby('job_no', "desc")
            ->findAll();


        $customerId = [];
        foreach ($laabsJobs as $job) {
            $customer_no = $job['customer_no'];
            if (!isset($customerId[$customer_no])) {
                $customerId[$customer_no] = [];
            }
            $customerId[$customer_no][] = $job;
        }

        foreach ($uniqueArray as &$temp) {
            $customer_no = $temp['customer_no'];
            $temp['old_jobcard'] = null;

            if (isset($customerId[$customer_no])) {

                $temp['old_jobcard'] = reset($customerId[$customer_no]);
            }


            array_push($job_cards, $temp);
        }

        $uniqueArray1 = $this->removeDuplicates($job_cards, 'customer_no');

        if (sizeof($customers) > 0) {
            $response = [
                'ret_data' => 'success',
                'customers' => $uniqueArray1,
            ];
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
        }
        return $this->respond($response, 200);
    }

    public function removeDuplicates($array, $key)
    {
        $tempArray = [];
        $uniqueArray = [];

        foreach ($array as $val) {
            if (!in_array($val[$key], $tempArray)) {
                $tempArray[] = $val[$key];
                $uniqueArray[] = $val;
            }
        }

        return $uniqueArray;
    }

    public function fetchAllNewCustomers()
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
            $laabsJob = new MaraghiJobModel();
            $leadmodel = new LeadModel();

            // new  leads then to  customer jobcards

            // $dateFrom = $this->request->getVar('dateFrom'); // Example: "2025-03-01"
            // $dateTo = $this->request->getVar('dateTo'); // Example: "2025-04-30"
            // $sourceId = $this->request->getVar('sourceId'); // Source ID from request

            // $leadsQuery = $leadmodel->select("leads.lead_id, leads.phone, leads.source_id, first_leads.lead_phone,leads.lead_createdon")
            //     ->join("(
            //     SELECT MIN(lead_id) as lead_id, SUBSTRING(phone, -9) as lead_phone
            //     FROM leads
            //     WHERE lead_delete_flag = 0
            //         AND DATE(lead_createdon) >= '$dateFrom'
            //         AND DATE(lead_createdon) <= '$dateTo'
            //     GROUP BY lead_phone
            // ) as first_leads", "first_leads.lead_id = leads.lead_id", "inner")
            //     ->where("DATE(leads.lead_createdon) >=", $dateFrom)
            //     ->where("DATE(leads.lead_createdon) <=", $dateTo)
            //     ->where("leads.lead_delete_flag", 0);

            // if (!empty($sourceId) && $sourceId != "0") {
            //     $leadsQuery->where("leads.source_id", $sourceId);
            // }

            // $leadsSubQuery = $leadsQuery->getCompiledSelect();

            // $oldCustomers = $laabsJob->select("customer_no")
            //     ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') <", $dateFrom)
            //     ->groupBy("customer_no")
            //     ->findAll();

            // $oldCustomerIds = array_column($oldCustomers, 'customer_no');

            // $newCustomersSubQuery = $laabsJob->select("customer_no, MIN(STR_TO_DATE(job_open_date, '%d-%M-%y')) as first_job_date")
            //     ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') >=", $dateFrom)
            //     // ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') <=", $dateTo)
            //     ->groupBy("customer_no")
            //     ->having("MIN(STR_TO_DATE(job_open_date, '%d-%M-%y')) >=", $dateFrom);

            // if (!empty($oldCustomerIds)) {
            //     $newCustomersSubQuery->whereNotIn("customer_no", $oldCustomerIds);
            // }

            // $newCustomersSubQuery = $newCustomersSubQuery->getCompiledSelect();

            // $jobs_list = $laabsJob->select("cust_job_data_laabs.*, cust_data_laabs.*, leads.*")
            //     ->join("cust_data_laabs", "cust_data_laabs.customer_code = cust_job_data_laabs.customer_no", "left")
            //     ->join(
            //         "($newCustomersSubQuery) as new_customers",
            //         "new_customers.customer_no = cust_job_data_laabs.customer_no 
            //         AND STR_TO_DATE(job_open_date, '%d-%M-%y') = new_customers.first_job_date",
            //         "inner"
            //     )
            //     ->join(
            //         "($leadsSubQuery) as first_leads",
            //         "first_leads.lead_phone = SUBSTRING(cust_data_laabs.phone, -9)
            //         AND STR_TO_DATE(job_open_date, '%d-%M-%y') >= DATE(first_leads.lead_createdon)",
            //         "left"
            //     )
            //     ->join("leads", "leads.lead_id = first_leads.lead_id", "left")
            //     ->orderBy("job_no", "desc")
            //     ->groupBy("cust_job_data_laabs.customer_no")
            //     ->findAll();


            // new customer jobcards then to leads 

            $dateFrom = $this->request->getVar('dateFrom');
            $dateTo   = $this->request->getVar('dateTo');
            $sourceId = $this->request->getVar('sourceId');

            // Step 1: Get Old Customers
            $oldCustomers = $laabsJob->select("customer_no")
                ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') <", $dateFrom)
                ->groupBy("customer_no")
                ->findAll();

            $oldCustomerIds = array_column($oldCustomers, 'customer_no');
            // log_message('error', 'First oldCustomerIds Result Array: ' . json_encode($oldCustomerIds));

            // Step 2: Get New Customers
            $newCustomersSubQuery = $laabsJob->select("customer_no, MIN(STR_TO_DATE(job_open_date, '%d-%M-%y')) as first_job_date")
                ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') >=", $dateFrom)
                ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') <=", $dateTo)
                ->groupBy("customer_no")
                ->having("MIN(STR_TO_DATE(job_open_date, '%d-%M-%y')) >=", $dateFrom);

            if (!empty($oldCustomerIds)) {
                $newCustomersSubQuery->whereNotIn("customer_no", $oldCustomerIds);
            }


            $newCustomersSubQuery = $newCustomersSubQuery->getCompiledSelect();
            // log_message('error', 'First newCustomersSubQuery Result Array: ' . json_encode($newCustomersSubQuery));

            // Step 3: Get First Leads (only first lead per phone, no source filter here)
            $firstLeadsQuery = $leadmodel->select("MIN(lead_id) as first_lead_id, MIN(lead_createdon) as first_lead_on, SUBSTRING(phone, -9) as lead_phone")
                ->where("lead_delete_flag", 0)
                ->where('lead_createdon IS NOT NULL', null, false)
                ->where('status_id <>', 7)
                ->groupBy("lead_phone");

            // $firstLeadsResult = $firstLeadsQuery->get()->getResultArray();
            // log_message('error', 'First Leads Result Array: ' . json_encode($firstLeadsResult));

            $firstLeadsSubQuery = $firstLeadsQuery->getCompiledSelect();

            // Step 4: Fetch New Customers with First Lead + Apply Source ID here
            $jobs_list = $laabsJob->select("cust_job_data_laabs.*, cust_data_laabs.*, leads.*,cust_data_laabs.phone as cphone")
                ->join("cust_data_laabs", "cust_data_laabs.customer_code = cust_job_data_laabs.customer_no", "left")
                ->join("($newCustomersSubQuery) as new_customers", "new_customers.customer_no = cust_job_data_laabs.customer_no 
                AND STR_TO_DATE(cust_job_data_laabs.job_open_date, '%d-%b-%y') = new_customers.first_job_date", "inner")
                ->join("($firstLeadsSubQuery) as first_leads", "first_leads.lead_phone = SUBSTRING(cust_data_laabs.phone, -9)", "left")
                ->join("leads", "leads.lead_id = first_leads.first_lead_id AND DATE(leads.lead_createdon) <= STR_TO_DATE(cust_job_data_laabs.job_open_date, '%d-%b-%y')", "left");
            // ->join("leads", "leads.lead_id = first_leads.first_lead_id", "left")
            // ->where("leads.lead_id IS NOT NULL");

            if (!empty($sourceId) && $sourceId != "0") {
                $jobs_list->where("leads.source_id", $sourceId);
            }

            $jobs_list = $jobs_list->orderBy("job_no", "desc")
                ->findAll();



            $involdCustomers = $laabsJob->select("customer_no")
                ->where("STR_TO_DATE(invoice_date, '%d-%M-%y') <", $dateFrom)
                ->groupBy("customer_no")
                ->findAll();

            $involdCustomerIds = array_column($involdCustomers, 'customer_no');
            // log_message('error', 'First oldCustomerIds Result Array: ' . json_encode($involdCustomerIds));

            // Step 2: Get New Customers
            $invnewCustomersSubQuery = $laabsJob->select("customer_no, MIN(STR_TO_DATE(invoice_date, '%d-%M-%y')) as first_job_date")
                ->where("STR_TO_DATE(invoice_date, '%d-%M-%y') >=", $dateFrom)
                ->where("STR_TO_DATE(invoice_date, '%d-%M-%y') <=", $dateTo)
                ->groupBy("customer_no")
                ->having("MIN(STR_TO_DATE(invoice_date, '%d-%M-%y')) >=", $dateFrom);

            if (!empty($involdCustomerIds)) {
                $invnewCustomersSubQuery->whereNotIn("customer_no", $involdCustomerIds);
            }


            $invnewCustomersSubQuery = $invnewCustomersSubQuery->getCompiledSelect();
            // log_message('error', 'First invnewCustomersSubQuery  invnewCustomersSubQuery  invnewCustomersSubQuery  Result Array: ' . json_encode($invnewCustomersSubQuery));

            // Step 3: Get First Leads (only first lead per phone, no source filter here)
            $invfirstLeadsQuery = $leadmodel->select("MIN(lead_id) as first_lead_id, MIN(lead_createdon) as first_lead_on, SUBSTRING(phone, -9) as lead_phone")
                ->where("lead_delete_flag", 0)
                ->where('lead_createdon IS NOT NULL', null, false)
                ->where('status_id <>', 7)
                ->groupBy("lead_phone");

            // $firstLeadsResult = $firstLeadsQuery->get()->getResultArray();
            // log_message('error', 'First Leads Result Array: ' . json_encode($firstLeadsResult));

            $invfirstLeadsQuery = $invfirstLeadsQuery->getCompiledSelect();

            // Step 4: Fetch New Customers with First Lead + Apply Source ID here
            $inv_jobs_list = $laabsJob->select("cust_job_data_laabs.*, cust_data_laabs.*, leads.*,cust_data_laabs.phone as cphone, IF(appointment_log.applg_id IS NOT NULL, 1, 0) AS cust_flag_mismatch")
                ->join("cust_data_laabs", "cust_data_laabs.customer_code = cust_job_data_laabs.customer_no", "left")
                ->join("($invnewCustomersSubQuery) as new_customers", "new_customers.customer_no = cust_job_data_laabs.customer_no 
                AND STR_TO_DATE(cust_job_data_laabs.invoice_date, '%d-%b-%y') = new_customers.first_job_date", "inner")
                ->join("($invfirstLeadsQuery) as first_leads", "first_leads.lead_phone = SUBSTRING(cust_data_laabs.phone, -9)", "left")
                ->join("leads", "leads.lead_id = first_leads.first_lead_id AND DATE(leads.lead_createdon) <= STR_TO_DATE(cust_job_data_laabs.invoice_date, '%d-%b-%y')", "left")
                ->join("appointment_log","appointment_log.applg_job_no = cust_job_data_laabs.job_no","left")
                ->groupBy("new_customers.customer_no");
            // ->join("leads", "leads.lead_id = first_leads.first_lead_id", "left")
            // ->where("leads.lead_id IS NOT NULL");

            if (!empty($sourceId) && $sourceId != "0") {
                $inv_jobs_list->where("leads.source_id", $sourceId);
            }

            $inv_jobs_list = $inv_jobs_list->orderBy("job_no", "desc")
                ->findAll();






            // $dateFrom = $this->request->getVar('dateFrom'); // Example: "2025-03-01"
            // $dateTo = $this->request->getVar('dateTo'); // Example: "2025-04-30"
            // $sourceId = $this->request->getVar('sourceId'); // Source ID from request

            // // ✅ Step 1: Get Old Customers (customers with jobs before $dateFrom)
            // $oldCustomers = $laabsJob->select("customer_no")
            //     ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') <", $dateFrom)
            //     ->groupBy("customer_no")
            //     ->findAll();

            // // Extract old customer IDs into an array
            // $oldCustomerIds = array_column($oldCustomers, 'customer_no');

            // // ✅ Step 2: Get New Customers (first job falls inside the date range)
            // $newCustomersSubQuery = $laabsJob->select("customer_no, MIN(STR_TO_DATE(job_open_date, '%d-%M-%y')) as first_job_date")
            //     ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') >=", $dateFrom)
            //     ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') <=", $dateTo)
            //     ->groupBy("customer_no")
            //     ->having("MIN(STR_TO_DATE(job_open_date, '%d-%M-%y')) >=", $dateFrom); // ✅ First job must be in range

            // // ✅ Step 3: Exclude Old Customers
            // if (!empty($oldCustomerIds)) {
            //     $newCustomersSubQuery->whereNotIn("customer_no", $oldCustomerIds);
            // }

            // // Compile the subquery
            // $newCustomersSubQuery = $newCustomersSubQuery->getCompiledSelect();

            // // ✅ Step 4: Get First Lead for Each Customer (Matching Last 9 Digits of Phone)
            // $firstLeadsQuery = $leadmodel->select("MIN(lead_id) as first_lead_id, SUBSTRING(phone, -9) as lead_phone")
            //     ->where("lead_delete_flag", 0);

            // // ✅ Step 5: Apply `source_id` filter **only if it's not 0**
            // if (!empty($sourceId) && $sourceId != "0") {
            //     $firstLeadsQuery->where("source_id", $sourceId);
            // }

            // $firstLeadsSubQuery = $firstLeadsQuery->groupBy("lead_phone")->getCompiledSelect();

            // // ✅ Step 6: Fetch New Customers, Their First Job, and First Lead (Filtered by `source_id`)
            // $jobs_list = $laabsJob->select("cust_job_data_laabs.*, cust_data_laabs.*, leads.*")
            //     ->join("cust_data_laabs", "cust_data_laabs.customer_code = cust_job_data_laabs.customer_no", "left")
            //     ->join("($newCustomersSubQuery) as new_customers", "new_customers.customer_no = cust_job_data_laabs.customer_no 
            //     AND STR_TO_DATE(job_open_date, '%d-%M-%y') = new_customers.first_job_date", "inner")
            //     ->join("($firstLeadsSubQuery) as first_leads", "first_leads.lead_phone = SUBSTRING(cust_data_laabs.phone, -9)", "left") // ✅ Match last 9 digits
            //     ->join("leads", "leads.lead_id = first_leads.first_lead_id", "left") // ✅ Get first lead details
            //     ->orderBy("job_no", "desc")
            //     ->findAll();



            // $dateFrom = $this->request->getVar('dateFrom'); // Example: "2025-03-01"
            // $dateTo = $this->request->getVar('dateTo'); // Example: "2025-04-30"

            // // ✅ Step 1: Get all customers who have jobs before $dateFrom (Old Customers)
            // $oldCustomers = $laabsJob->select("customer_no")
            //     ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') <", $dateFrom)
            //     ->groupBy("customer_no")
            //     ->findAll();

            // // Extract old customer IDs into an array
            // $oldCustomerIds = array_column($oldCustomers, 'customer_no');

            // // ✅ Step 2: Get customers whose first-ever job falls inside the range (New Customers)
            // $newCustomersSubQuery = $laabsJob->select("customer_no, MIN(STR_TO_DATE(job_open_date, '%d-%M-%y')) as first_job_date")
            //     ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') >=", $dateFrom)
            //     ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') <=", $dateTo)
            //     ->groupBy("customer_no")
            //     ->having("MIN(STR_TO_DATE(job_open_date, '%d-%M-%y')) >=", $dateFrom); // ✅ First job must be in range

            // // ✅ Step 3: Exclude Old Customers
            // if (!empty($oldCustomerIds)) {
            //     $newCustomersSubQuery->whereNotIn("customer_no", $oldCustomerIds); // ✅ Now it works!
            // }

            // // Compile the subquery
            // $newCustomersSubQuery = $newCustomersSubQuery->getCompiledSelect();

            // // ✅ Step 4: Get details of the first job for new customers
            // $jobs_list = $laabsJob->select("cust_job_data_laabs.*, cust_data_laabs.*")
            //     ->join("cust_data_laabs", "cust_data_laabs.customer_code = cust_job_data_laabs.customer_no", "left")
            //     ->join("($newCustomersSubQuery) as new_customers", "new_customers.customer_no = cust_job_data_laabs.customer_no 
            //     AND STR_TO_DATE(job_open_date, '%d-%M-%y') = new_customers.first_job_date", "inner")
            //     ->orderBy("job_no", "desc")
            //     ->findAll();




            if (sizeof($jobs_list) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'customers' => $jobs_list,
                    'invCustomers' => $inv_jobs_list
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function fetchAllNewCustomerLeads()
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
            $laabsJob = new MaraghiJobModel();
            $leadmodel = new LeadModel();
            $marcustmodel = new MaragiCustomerModel();


            $dateFrom = $this->request->getVar('dateFrom');
            $dateTo   = $this->request->getVar('dateTo');
            $sourceId = $this->request->getVar('sourceId');

            // Step 1: Get Old Customers' Phones
            $oldCustomers = $laabsJob->select("cust_data_laabs.phone")
                ->join("cust_data_laabs", "cust_data_laabs.customer_code = cust_job_data_laabs.customer_no", "left")
                ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') <", $dateFrom)
                ->groupBy("cust_data_laabs.phone")
                ->findAll();

            // Extract last 9 digits of old phones
            $oldPhones9Digits = array_filter(array_map(function ($row) {
                return isset($row['phone']) ? substr(preg_replace('/\D/', '', $row['phone']), -9) : null;
            }, $oldCustomers));

            // Step 2: Get New Customers
            $newCustomersSubQuery = $laabsJob->select("
             customer_no,
             car_reg_no,
             job_status,
             MIN(STR_TO_DATE(job_open_date, '%d-%M-%y')) as first_job_date,
             cust_data_laabs.phone,
             cust_data_laabs.customer_name
             ")
                ->join("cust_data_laabs", "cust_data_laabs.customer_code = cust_job_data_laabs.customer_no", "left")
                ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') >=", $dateFrom)
                ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') <=", $dateTo)
                ->groupBy("customer_no, cust_data_laabs.phone")
                ->having("MIN(STR_TO_DATE(job_open_date, '%d-%M-%y')) >=", $dateFrom);

            $compiledNewCustomers = $newCustomersSubQuery->getCompiledSelect();

            // Step 3: Join leads with new customers by last 9 digits of phone
            $db = \Config\Database::connect();
            $builder = $db->table("leads");

            $builder->select("
             leads.*,
             leads.phone AS lead_phone,
             SUBSTRING(leads.phone, -9) AS short_lead_phone,
             new_custs.customer_no,
             new_custs.phone AS cust_phone,
             SUBSTRING(new_custs.phone, -9) AS short_cust_phone,
             new_custs.first_job_date,
             new_custs.car_reg_no,
             new_custs.job_status,
             new_custs.customer_name,
             lead_call_log.*,
             alm_whatsapp_customers.wb_cus_lead_category,
            ");

            $builder->join(
                "($compiledNewCustomers) AS new_custs",
                "SUBSTRING(leads.phone, -9) = SUBSTRING(new_custs.phone, -9) AND new_custs.first_job_date >= DATE(leads.lead_createdon)",
                "left"
            );
            $builder->join("lead_call_log", "lead_call_log.lcl_lead_id = leads.lead_id", "left");
            $builder->join("alm_whatsapp_customers", "SUBSTRING(alm_whatsapp_customers.wb_cus_mobile , -9) = SUBSTRING(leads.phone, -9)", "left");
            $builder->groupBy("leads.phone");

            // Exclude leads that match any old customer phone (last 9 digits)
            if (!empty($oldPhones9Digits)) {
                $builder->whereNotIn("SUBSTRING(leads.phone, -9)", $oldPhones9Digits);
            }

            $builder->where('leads.lead_delete_flag', 0);
            $builder->where('DATE(leads.lead_createdon) >=', $dateFrom);
            $builder->where('DATE(leads.lead_createdon) <=', $dateTo);

            if (!empty($sourceId) && $sourceId !== '0') {
                $builder->where('leads.source_id', $sourceId);
            }

            $results = $builder->get()->getResult();




            if (sizeof($results) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'customers' => $results,
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
