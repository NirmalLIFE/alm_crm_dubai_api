<?php

namespace App\Controllers\Customer;

use CodeIgniter\RESTful\ResourceController;

use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\Whatsapp\WhatsappCampaignMessageModel;
use App\Models\Whatsapp\WhatsappCampaignModel;
use App\Models\Whatsapp\WhatsappCustomerMasterModel;
use App\Models\Whatsapp\WhatsappCustomerMessageModel;
use App\Controllers\Whatsapp\WhatsappChatController;
use App\Models\Leads\LeadModel;
use App\Models\Leads\AppointmentMasterModel;
use App\Models\Customer\MaragiCustomerModel;


class CustomerReEngageCampaignController extends ResourceController
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
        $WhatsappCampaignModel = new WhatsappCampaignModel();
        $WhatsappCampaignMessageModel = new WhatsappCampaignMessageModel();
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

            $campaigns = $WhatsappCampaignModel->where('alm_wb_camp_delete_flag', 0)
                ->orderby('alm_wb_camp_id', 'desc')->findAll();


            if ($campaigns) {
                $response = [
                    'ret_data' => 'success',
                    'Campaigns' => $campaigns
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'Campaigns' => []
                ];
                return $this->respond($response, 200);
            }
        }
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

    public function getCustomerReEngageCampaignReport()
    {

        $WhatsappCampaignModel = new WhatsappCampaignModel();
        $WhatsappCampaignMessageModel = new WhatsappCampaignMessageModel();
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

            $campaignModel = new WhatsappCampaignMessageModel();

            $campaign_id = $this->request->getVar('campaign_id');
            $dateFrom = $this->request->getVar('dateFrom');
            $dateTo = $this->request->getVar('dateTo');
            $type = $this->request->getVar('type');

            $data['campaignMessages'] = $campaignModel->getCampaignMessages($campaign_id, $dateFrom, $dateTo);


            $campaigns = $data['campaignMessages'];


            // $subquery = $WhatsappCampaignMessageModel->db->table('alm_whatsapp_cus_messages')
            //     ->select('MAX(alm_wb_msg_id) AS latest_msg_id, alm_wb_msg_customer')
            //     ->groupBy('alm_wb_msg_customer')
            //     ->getCompiledSelect();

            // $campaigns = $WhatsappCampaignMessageModel->where('alm_wb_msg_camp_delete_flag', 0)
            //     ->join('alm_whatsapp_campaign', 'alm_whatsapp_campaign.alm_wb_camp_id = alm_wb_camp_msg_wb_camp_id', 'left')
            //     ->join('alm_whatsapp_customers', 'alm_whatsapp_customers.wb_cus_id = alm_wb_camp_msg_wb_cus_id', 'left')
            //     ->join("($subquery) AS latest_messages", 'latest_messages.alm_wb_msg_customer = alm_wb_camp_msg_wb_cus_id', 'left')
            //     ->join('alm_whatsapp_cus_messages AS messages1', 'messages1.alm_wb_msg_id = latest_messages.latest_msg_id', 'left')
            //     // ->join('leads', "RIGHT(leads.phone, 9) = RIGHT(alm_wb_camp_msg_cus_phone, 9) AND leads.lead_updatedon > alm_wb_camp_msg_created_on", 'left')
            //     ->join('alm_whatsapp_cus_messages AS messages2', 'messages2.alm_wb_msg_customer = alm_wb_camp_msg_wb_cus_id AND messages2.alm_wb_msg_created_on > alm_wb_camp_msg_created_on AND messages2.alm_wb_msg_source = 1', 'left')
            //     ->join('cust_data_laabs', 'cust_data_laabs.phone = alm_wb_camp_msg_cus_phone', 'left')
            //     ->join('cust_job_data_laabs', "cust_job_data_laabs.customer_no = cust_data_laabs.customer_code AND STR_TO_DATE(cust_job_data_laabs.job_open_date, '%d-%b-%y') BETWEEN STR_TO_DATE(alm_whatsapp_campaign.alm_wb_camp_date_from, '%Y-%m-%d') AND STR_TO_DATE(alm_whatsapp_campaign.alm_wb_camp_date_to, '%Y-%m-%d')", 'left')
            //     ->select([
            //         'alm_wb_camp_msg_created_on',
            //         'alm_whatsapp_campaign.*',
            //         'alm_whatsapp_customers.*',
            //         'messages1.*',
            //         'cust_data_laabs.customer_code',
            //         'cust_job_data_laabs.job_open_date',
            //         'messages2.alm_wb_msg_id AS customer_replied_message_id',
            //         "CASE WHEN messages2.alm_wb_msg_id IS NOT NULL THEN 'true' ELSE 'false' END AS customer_responded"
            //     ])
            //     ->where('alm_wb_camp_delete_flag', 0)
            //     ->groupBy('alm_whatsapp_customers.wb_cus_id')
            //     ->orderBy('alm_wb_camp_id', 'desc');

            // if ($campaign_id != '0') {
            //     $campaigns->where('alm_wb_camp_id', $campaign_id);
            // } else {
            //     $campaigns->whereIn('alm_wb_camp_type', $type);
            // }

            // if (!empty($dateFrom)) {
            //     $campaigns->where("DATE(alm_wb_camp_msg_created_on) >=", $dateFrom);
            // }

            // if (!empty($dateTo)) {
            //     $campaigns->where("DATE(alm_wb_camp_msg_created_on) <=", $dateTo);
            // }

            // $campaigns = $campaigns->findAll();


            // Retrieve input variables
            // Retrieve inputs
            //     $campaign_id = $this->request->getVar('campaign_id');
            //     $type        = $this->request->getVar('type');

            //     $dateFrom = !empty($this->request->getVar('dateFrom'))
            //         ? $this->request->getVar('dateFrom') . ' 00:00:00'
            //         : null;
            //     $dateTo   = !empty($this->request->getVar('dateTo'))
            //         ? $this->request->getVar('dateTo') . ' 23:59:59'
            //         : null;

            //     $page   = $this->request->getVar('page') ?? 1;
            //     $limit  = 10;
            //     $offset = ($page - 1) * $limit;

            //     $campaignsQuery = $WhatsappCampaignMessageModel
            //         ->select([
            //             'alm_wb_camp_msg_created_on',
            //             'alm_wb_camp_msg_wb_msg_id',
            //             'alm_wb_camp_msg_cus_phone',
            //             'alm_wb_msg_status',
            //             'alm_whatsapp_campaign.alm_wb_camp_id',
            //             'alm_whatsapp_campaign.alm_wb_camp_type',
            //             'alm_whatsapp_campaign.alm_wb_camp_name',
            //             'alm_whatsapp_campaign.alm_wb_camp_cust_count',
            //             'alm_whatsapp_campaign.alm_wb_camp_date_from',
            //             'alm_whatsapp_campaign.alm_wb_camp_date_to',
            //             'alm_whatsapp_campaign.alm_wb_camp_created_by',
            //             'alm_whatsapp_customers.wb_cus_id',
            //             'alm_whatsapp_customers.wb_cus_name',
            //             'alm_whatsapp_customers.wb_cus_mobile',
            //             'cust_data_laabs.customer_code',
            //             // Subquery to obtain job_open_date
            //             '(SELECT job_open_date FROM cust_job_data_laabs 
            //   WHERE customer_no = cust_data_laabs.customer_code 
            //     AND job_open_date BETWEEN alm_whatsapp_campaign.alm_wb_camp_date_from 
            //                           AND alm_whatsapp_campaign.alm_wb_camp_date_to 
            //   LIMIT 1) as job_open_date'
            //         ])
            //         ->where('alm_wb_msg_camp_delete_flag', 0);

            //     // Apply campaign or type filters
            //     if ($campaign_id != '0') {
            //         $campaignsQuery->where('alm_whatsapp_campaign.alm_wb_camp_id', $campaign_id);
            //     } else if (!empty($type)) {
            //         $campaignsQuery->whereIn('alm_whatsapp_campaign.alm_wb_camp_type', $type);
            //     }

            //     // Apply date filters on the message creation date
            //     if (!empty($dateFrom)) {
            //         $campaignsQuery->where('alm_wb_camp_msg_created_on >=', $dateFrom);
            //     }
            //     if (!empty($dateTo)) {
            //         $campaignsQuery->where('alm_wb_camp_msg_created_on <=', $dateTo);
            //     }

            //     // Join related tables for the remaining data
            //     $campaignsQuery
            //         ->join(
            //             'alm_whatsapp_campaign',
            //             'alm_whatsapp_campaign.alm_wb_camp_id = alm_wb_camp_msg_wb_camp_id',
            //             'left'
            //         )
            //         ->join(
            //             'alm_whatsapp_customers',
            //             'alm_whatsapp_customers.wb_cus_id = alm_wb_camp_msg_wb_cus_id',
            //             'left'
            //         )
            //         ->join(
            //             'alm_whatsapp_cus_messages',
            //             'alm_whatsapp_cus_messages.alm_wb_msg_id = alm_wb_camp_msg_wb_msg_id',
            //             'left'
            //         )
            //         ->join(
            //             'cust_data_laabs',
            //             'cust_data_laabs.phone = alm_wb_camp_msg_cus_phone',
            //             'left'
            //         );

            //     // Group, order and limit results
            //     $campaignsQuery
            //         ->groupBy([
            //             'alm_whatsapp_customers.wb_cus_id',
            //             'alm_whatsapp_campaign.alm_wb_camp_id'
            //         ])
            //         ->orderBy('alm_whatsapp_campaign.alm_wb_camp_id', 'desc')
            //         ->limit($limit, $offset);

            //     // Fetch results
            //     $campaigns = $campaignsQuery->findAll();







            if ($campaigns) {
                $response = [
                    'ret_data' => 'success',
                    'Campaigns' => $campaigns
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'Campaigns' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function sendSMCWithDays()
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
            $builder = $this->db->table('sequence_data');
            $builder->select('first_service_remainder_days,second_service_remainder_days,third_service_remainder_days');
            $query = $builder->get();
            $row = $query->getRow();

            if ($row) {
                $this->sendServiceRemainderCampaignMessages($row->first_service_remainder_days);
                $this->sendServiceRemainderCampaignMessages($row->second_service_remainder_days);
                $this->sendServiceRemainderCampaignMessages($row->third_service_remainder_days);
            }
        }
    }

    public function sendServiceRemainderCampaignMessages($type)
    {
        $WhatsappChatController = new WhatsappChatController;
        $wb_customer = new WhatsappCustomerMasterModel();

        $url = "http://almaraghi.fortiddns.com:35147/maraghi_lead_test/index.php/DataFetch/getDXBServiceRemainderCustomers";
        
        $type =  $type; // Set the type parameter

        $data = json_encode(['type' => $type]); // Convert to JSON format

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8'
        ]);
        curl_setopt($ch, CURLOPT_POST, TRUE); // Set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Send JSON data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $curlResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);


        $response = [
            'customers' => $curlResponse['data']['data'] ?? [],
            'ret_data' => 'success'
        ];

        // log_message('error',  "Log message on cron Job $type" . json_encode($curlResponse));


        $remindedMobiles = $wb_customer->select('wb_cus_mobile')
            ->where('wb_cus_remind_flag', '1')
            ->get()
            ->getResultArray();

        $remindMobileLast9 = [];

        foreach ($remindedMobiles as $row) {
            if (!empty($row['wb_cus_mobile'])) {
                $remindMobileLast9[] = substr(preg_replace('/\D/', '', $row['wb_cus_mobile']), -9); // sanitize & get last 9
            }
        }

        // 2. Initialize final filtered array
        $filteredResponse = [];

        if (!empty($curlResponse['data']['data']) && is_array($curlResponse['data']['data'])) {
            foreach ($curlResponse['data']['data'] as $item) {
                $phone = '';

                // Check MOBILE first, fallback to PHONE
                if (!empty($item['MOBILE'])) {
                    $phone = $item['MOBILE'];
                } elseif (!empty($item['PHONE'])) {
                    $phone = $item['PHONE'];
                }


                // Clean and get last 9 digits
                $phone = preg_replace('/\D/', '', $phone);
                $last9 = substr($phone, -9);

                // Exclude if remind flag is set
                if (!in_array($last9, $remindMobileLast9)) {
                    $filteredResponse[] = $item;
                }
            }
        }

        // log_message('error', "Log filteredResponse on cron job {$type}: " . json_encode($filteredResponse));


        // $customers = [
        //     [
        //         'CUSTOMER_NAME' => 'Nirmal',
        //         'PHONE' => '',
        //         'MOBILE' => '918138055705',
        //         'CAR_REG_NO' => '8801-R8',
        //         'SPEEDOMETER_READING' => '12500',
        //     ],
        //     // [
        //     //     'CUSTOMER_NAME' => 'Arun',
        //     //     'PHONE' => '918921529689',
        //     //     'MOBILE' => '918921529689',
        //     //     'CAR_REG_NO' => '9502-R88',
        //     //     'SPEEDOMETER_READING' => '30500',
        //     // ],
        //     // [
        //     //     'CUSTOMER_NAME' => 'Aby',
        //     //     'PHONE' => '919744608229',
        //     //     'CAR_REG_NO' => '9603-R99',
        //     //     'SPEEDOMETER_READING' => '40500',
        //     // ],
        //     // [
        //     //     'CUSTOMER_NAME' => 'Yadu',
        //     //     'PHONE' => '918330039403',
        //     //     'CAR_REG_NO' => '9704-R76',
        //     //     'SPEEDOMETER_READING' => '25500',
        //     // ],
        //     // [
        //     //     'CUSTOMER_NAME' => 'Arjun',
        //     //     'PHONE' => '91628200214',
        //     //     'CAR_REG_NO' => '9905-R23',
        //     //     'SPEEDOMETER_READING' => '24500',
        //     // ],
        //     // [
        //     //     'CUSTOMER_NAME' => 'Arjun Biju',
        //     //     'PHONE' => '919072843160',
        //     //     'CAR_REG_NO' => '9910-R56',
        //     //     'SPEEDOMETER_READING' => '25000',
        //     // ]
        // ];


        // $messages = $WhatsappChatController->sendCustomerServiceReminderCampaignMessage($customers, $type);

        $messages = $WhatsappChatController->sendCustomerServiceReminderCampaignMessage($curlResponse['data']['data'], $type);

        // return $this->respond($response, 200);
    }

    // public function getSRCReport()
    // {

    //     $WhatsappCampaignModel = new WhatsappCampaignModel();
    //     $WhatsappCampaignMessageModel = new WhatsappCampaignMessageModel();
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

    //         $campaign_id = $this->request->getVar('campaign_id');
    //         $dateFrom = $this->request->getVar('dateFrom');
    //         $dateTo = $this->request->getVar('dateTo');
    //         $type = $this->request->getVar('type');

    //         // // Build subquery to get the latest message per customer
    //         $subqueryLatestMsg = $WhatsappCampaignMessageModel->db->table('alm_whatsapp_cus_messages')
    //             ->select('MAX(alm_wb_msg_id) AS latest_msg_id, alm_wb_msg_customer')
    //             ->groupBy('alm_wb_msg_customer')
    //             ->getCompiledSelect();

    //         // Build subquery to get the latest job per customer
    //         $subqueryLatestJob = $WhatsappCampaignMessageModel->db->table('cust_job_data_laabs')
    //             ->select('customer_no, MAX(job_no) AS latest_job_no')
    //             ->groupBy('customer_no')
    //             ->where("STR_TO_DATE(cust_job_data_laabs.job_open_date, '%d-%b-%Y') >= ", $dateFrom)
    //             ->getCompiledSelect();

    //         $campaigns = $WhatsappCampaignMessageModel->where('alm_wb_msg_camp_delete_flag', 0)
    //             ->join('alm_whatsapp_campaign', 'alm_whatsapp_campaign.alm_wb_camp_id = alm_wb_camp_msg_wb_camp_id', 'left')
    //             ->join('alm_whatsapp_customers', 'alm_whatsapp_customers.wb_cus_id = alm_wb_camp_msg_wb_cus_id', 'left')
    //             // Join with the latest message subquery
    //             ->join("($subqueryLatestMsg) AS latest_messages", 'latest_messages.alm_wb_msg_customer = alm_wb_camp_msg_wb_cus_id', 'left')
    //             ->join('alm_whatsapp_cus_messages AS messages1', 'messages1.alm_wb_msg_id = latest_messages.latest_msg_id', 'left')
    //             // Join messages2 (customer replies)
    //             ->join('alm_whatsapp_cus_messages AS messages2', "messages2.alm_wb_msg_customer = alm_wb_camp_msg_wb_cus_id AND messages2.alm_wb_msg_created_on > alm_wb_camp_msg_created_on AND messages2.alm_wb_msg_source = 1", 'left')
    //             // Join customer data table
    //             ->join('cust_data_laabs', 'RIGHT(cust_data_laabs.phone, 9) = RIGHT(alm_wb_camp_msg_cus_phone, 9)', 'left')
    //             // Join with the latest job subquery
    //             ->join("($subqueryLatestJob) AS latest_jobs", 'latest_jobs.customer_no = cust_data_laabs.customer_code', 'left')
    //             // Join job data using the latest job_no with proper formatting (all on one line)
    //             ->join('cust_job_data_laabs', "cust_job_data_laabs.customer_no = latest_jobs.customer_no AND cust_job_data_laabs.job_no = latest_jobs.latest_job_no AND (messages2.alm_wb_msg_created_on IS NULL OR STR_TO_DATE(cust_job_data_laabs.job_open_date, '%d-%b-%Y') >= messages2.alm_wb_msg_created_on)", 'left')
    //             ->join('appointment_master', 'RIGHT(appointment_master.apptm_alternate_no, 9) = RIGHT(alm_whatsapp_customers.wb_cus_mobile, 9)', 'left')
    //             ->select([
    //                 'alm_wb_camp_msg_created_on',
    //                 'alm_whatsapp_campaign.*',
    //                 'alm_whatsapp_customers.*',
    //                 'messages1.*',
    //                 'cust_data_laabs.customer_code',
    //                 'cust_job_data_laabs.job_open_date',
    //                 'messages2.alm_wb_msg_id AS customer_replied_message_id',
    //                 "CASE WHEN messages2.alm_wb_msg_id IS NOT NULL THEN 'true' ELSE 'false' END AS customer_responded",
    //                 'appointment_master.apptm_type AS appointment_type',
    //                 "CASE 
    //     WHEN appointment_master.apptm_type = 7 THEN 'First Service Reminder' 
    //     WHEN appointment_master.apptm_type = 8 THEN 'Second Service Reminder'
    //     WHEN appointment_master.apptm_type = 9 THEN 'Third Service Reminder'
    //     ELSE 'Other' 
    //   END AS appointment_source"
    //             ])
    //             ->groupBy('alm_wb_camp_msg_wb_cus_id, alm_wb_camp_id')
    //             ->orderBy('alm_wb_camp_id', 'desc');

    //         if ($campaign_id != '0') {
    //             $campaigns->where('alm_wb_camp_id', $campaign_id);
    //         } else {
    //             $campaigns->whereIn('alm_wb_camp_type', $type);
    //         }

    //         if (!empty($dateFrom)) {
    //             $campaigns->where("DATE(alm_wb_camp_msg_created_on) >=", $dateFrom);
    //         }

    //         if (!empty($dateTo)) {
    //             $campaigns->where("DATE(alm_wb_camp_msg_created_on) <=", $dateTo);
    //         }


    //         $campaigns = $campaigns->findAll();



    //         if ($campaigns) {
    //             $response = [
    //                 'ret_data' => 'success',
    //                 'Campaigns' => $campaigns
    //             ];
    //             return $this->respond($response, 200);
    //         } else {
    //             $response = [
    //                 'ret_data' => 'fail',
    //                 'Campaigns' => []
    //             ];
    //             return $this->respond($response, 200);
    //         }
    //     }
    // }

    public function getSRCReport()
    {
        // Increase PHP execution time and memory (adjust values as needed)
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '1024M');

        // Instantiate models
        $WhatsappCampaignMessageModel = new WhatsappCampaignMessageModel();
        $campaignModel = new WhatsappCampaignMessageModel();
        $common = new Common();
        $valid = new Validation();
        $db = \Config\Database::connect();

        // Decode JWT token
        $heddata = $this->request->headers();
        $token = $valid->getbearertoken($heddata['Authorization'] ?? '');
        $tokendata = $common->decode_jwt_token($token);

        // Validate user type and existence
        $userModel = ($tokendata['aud'] === 'superadmin') ? new SuperAdminModel() : new UserModel();
        if (!$userModel->find($tokendata['uid'])) {
            return $this->fail("Invalid user", 400);
        }

        // Get request parameters
        $campaign_id = $this->request->getVar('campaign_id');
        $dateFrom = $this->request->getVar('dateFrom');
        $dateTo = $this->request->getVar('dateTo');
        $type = $this->request->getVar('type');

        $data = $WhatsappCampaignMessageModel->getCSR($campaign_id, $dateFrom, $dateTo, $type);

        return $this->respond([
            'ret_data' => !empty($data) ? 'success' : 'fail',
            'Campaigns' => $data
        ], 200);
    }

    public function getNewSRCReport()
    {
        // Increase PHP execution time and memory (adjust values as needed)
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '1024M');

        // Instantiate models
        $WhatsappCampaignMessageModel = new WhatsappCampaignMessageModel();
        $campaignModel = new WhatsappCampaignMessageModel();
        $common = new Common();
        $valid = new Validation();
        $db = \Config\Database::connect();

        // Decode JWT token
        $heddata = $this->request->headers();
        $token = $valid->getbearertoken($heddata['Authorization'] ?? '');
        $tokendata = $common->decode_jwt_token($token);

        // Validate user type and existence
        $userModel = ($tokendata['aud'] === 'superadmin') ? new SuperAdminModel() : new UserModel();
        if (!$userModel->find($tokendata['uid'])) {
            return $this->fail("Invalid user", 400);
        }

        // Get request parameters
        $campaign_id = $this->request->getVar('campaign_id');
        $dateFrom = $this->request->getVar('dateFrom');
        $dateTo = $this->request->getVar('dateTo');
        $type = $this->request->getVar('type');


        $builder = $this->db->table('sequence_data');
        $builder->select('first_service_remainder_days,second_service_remainder_days,third_service_remainder_days');
        $query = $builder->get();
        $row = $query->getRow();

        $firstDays  = $row->first_service_remainder_days;
        $secondDays = $row->second_service_remainder_days;
        $thirdDays  = $row->third_service_remainder_days;

        // First Service range (subtract days)
        $firstFrom = date('Y-m-d', strtotime($dateFrom . " - {$firstDays} days"));
        $firstTo   = date('Y-m-d', strtotime($dateTo . " - {$firstDays} days"));

        // Second Service range
        $secondFrom = date('Y-m-d', strtotime($dateFrom . " - {$secondDays} days"));
        $secondTo   = date('Y-m-d', strtotime($dateTo . " - {$secondDays} days"));

        // Third Service range
        $thirdFrom = date('Y-m-d', strtotime($dateFrom . " - {$thirdDays} days"));
        $thirdTo   = date('Y-m-d', strtotime($dateTo . " - {$thirdDays} days"));

        // Call campaign details for each service
        $firstSummary  = $this->getFirstServiceRemainderCampaignDetails($firstFrom, $firstTo, $firstDays);
        $secondSummary = $this->getSecondServiceRemainderCampaignDetails($secondFrom, $secondTo, $firstDays, $secondDays);
        $thirdSummary  = $this->getThirdServiceRemainderCampaignDetails($thirdFrom, $thirdTo, $firstDays, $secondDays, $thirdDays);


        // log_message('error', "secondFrom" . $thirdFrom);
        // log_message('error', "thirdTo" . $thirdTo);
        // log_message('error', "firstDays" . $firstDays);
        // log_message('error', "secondDays" . $secondDays);
        // log_message('error', "thirdDays" . $thirdDays);



        $data = $WhatsappCampaignMessageModel->getCSR($campaign_id, $dateFrom, $dateTo, $type);

        return $this->respond([
            'ret_data' => !empty($data) ? 'success' : 'fail',
            'Campaigns' => $data,
            'ServiceReminder' => [
                'first_service'  => $firstSummary['summary'],
                'second_service' => $secondSummary['summary'],
                'third_service'  => $thirdSummary['summary'],
            ]
        ], 200);
    }

    public function getAppointmentCustomersFromSRC()
    {

        $Leadmodel = new LeadModel();
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

            $Leadmodel = new LeadModel();
            $ApptMaster = new AppointmentMasterModel();

            $customerDetails = $this->request->getVar('customerDetails');

            if (!is_array($customerDetails)) {
                return $this->respond(['ret_data' => 'fail', 'message' => 'Invalid customer details'], 400);
            }

            $customers = []; // Store matched customers
            foreach ($customerDetails as $appt) {
                $matchedCustomer = $Leadmodel->select('leads.*, appointment_master.*')
                    ->join('appointment_master', 'appointment_master.apptm_lead_id = leads.lead_id', 'inner')
                    ->where('lead_delete_flag', 0)
                    ->where('DATE(apptm_created_on) >=', $appt->message_date)
                    ->where('RIGHT(phone, 9)', substr($appt->mobile, -9))
                    ->orderBy('appointment_master.apptm_id', 'DESC') // Get latest appointment
                    ->first(); // Only fetch one row, the latest

                if (!empty($matchedCustomer)) {
                    $customers[] = $matchedCustomer;
                }
            }

            // Prepare response
            $response = [
                'ret_data' => !empty($customers) ? 'success' : 'fail',
                'customer' => $customers
            ];

            return $this->respond($response, 200);
        }
    }


    public function fetchAllFollowUpCustomers()
    {
        $WhatsappCampaignModel = new WhatsappCampaignModel();
        $WhatsappCampaignMessageModel = new WhatsappCampaignMessageModel();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();
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


            $remindCustomersList = $wb_customer
                ->where('wb_cus_remind_flag', 1)
                ->where('wb_cus_block', 0)
                ->join('users', 'users.us_id = wb_cus_assigned', 'left')
                ->select('alm_whatsapp_customers.*, users.us_firstname');

            if (!empty($dateFrom)) {
                $remindCustomersList->where("DATE(wb_cus_remind_date) >=", $dateFrom);
            }
            if (!empty($dateTo)) {
                $remindCustomersList->where("DATE(wb_cus_remind_date) <=", $dateTo);
            }

            $remindCustomersList = $remindCustomersList->findAll();

            $assistanceRequiredCustomers = $wb_customer
                ->where('wb_cus_remind_flag', 2)
                ->where('wb_cus_block', 0)
                ->join('users', 'users.us_id = wb_cus_assigned', 'left')
                ->select('alm_whatsapp_customers.*, users.us_firstname');

            if (!empty($dateFrom)) {
                $assistanceRequiredCustomers->where("DATE(wb_cus_updated_on) >=", $dateFrom);
            }
            if (!empty($dateTo)) {
                $assistanceRequiredCustomers->where("DATE(wb_cus_updated_on) <=", $dateTo);
            }

            $assistanceRequiredCustomers = $assistanceRequiredCustomers->findAll();

            if ($remindCustomersList && $assistanceRequiredCustomers) {
                $response = [
                    'ret_data' => 'success',
                    'remindCustomersList' => $remindCustomersList,
                    'assistanceRequiredCustomers' => $assistanceRequiredCustomers
                ];
            } elseif ($remindCustomersList) {
                $response = [
                    'ret_data' => 'success',
                    'remindCustomersList' => $remindCustomersList,
                    'assistanceRequiredCustomers' => []
                ];
            } elseif ($assistanceRequiredCustomers) {
                $response = [
                    'ret_data' => 'success',
                    'remindCustomersList' => [],
                    'assistanceRequiredCustomers' => $assistanceRequiredCustomers
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'Customers' => []
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getCampaignCustomerJobcards()
    {

        $common = new Common();
        $valid = new Validation();
        $laabsCustomer = new MaragiCustomerModel();

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


            $jsonData = $this->request->getVar('customers');

            // Ensure $jsonData is an array or convert JSON string to an associative array
            if (is_string($jsonData)) {
                $Customers = json_decode($jsonData, true);
            } elseif (is_array($jsonData)) {
                $Customers = array_map(fn($item) => (array) $item, $jsonData); // Convert stdClass to array
            } else {
                return $this->response->setJSON(['error' => 'Invalid customer data format'])->setStatusCode(400);
            }

            // Extract mobile numbers (last 9 digits) and message dates
            $wb_cus_mobile = array_map(fn($customer) => substr($customer['wb_cus_mobile'], -9), $Customers);
            $message_dates = array_column($Customers, 'message_date');
            $minDate = min($message_dates); // Get the earliest message date


            // Fetch job card customers based on last 9 digits of phone number and minimum message date
            $JobcardCustomers = $laabsCustomer
                ->select('*')
                ->whereIn('RIGHT(phone,9)', $wb_cus_mobile)
                ->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no = customer_code', 'left')
                ->where("STR_TO_DATE(job_open_date, '%d-%M-%y') >=", $minDate)
                ->findAll();






            if ($JobcardCustomers) {
                $response = [
                    'ret_data' => 'success',
                    'JobcardCustomers' => $JobcardCustomers,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'JobcardCustomers' => []
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getFirstServiceRemainderCampaignDetails($dateFrom, $dateTo, $reminderDays)
    {

        $url = "http://almaraghi.fortiddns.com:35147/maraghi_lead_test/index.php/DataFetch/getFirstServiceRemainderCustomersDetails"; // Replace with your actual API URL

        $data = json_encode([
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
            'days'     => $reminderDays
        ]); // Convert to JSON format

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8'
        ]);
        curl_setopt($ch, CURLOPT_POST, TRUE); // Set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Send JSON data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $curlResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);


        $response = [
            'summary'  => $curlResponse['data'] ?? [],
            'ret_data' => 'success'
        ];

        return $response;
    }

    public function getSecondServiceRemainderCampaignDetails($dateFrom, $dateTo, $days, $secondDays)
    {

        $url = "http://almaraghi.fortiddns.com:35147/maraghi_lead_test/index.php/DataFetch/getSecondServiceRemainderCustomersDetails"; // Replace with your actual API URL

        $data = json_encode([
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
            'days'   => $days,
            'reminder_days' => $secondDays
        ]); // Convert to JSON format

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8'
        ]);
        curl_setopt($ch, CURLOPT_POST, TRUE); // Set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Send JSON data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $curlResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);


        $response = [
            'summary'  => $curlResponse['data'] ?? [],
            'ret_data' => 'success'
        ];

        return $response;
    }


    public function getThirdServiceRemainderCampaignDetails($dateFrom, $dateTo, $reminder_days_1, $reminder_days_2, $reminder_days_3)
    {

        $url = "http://almaraghi.fortiddns.com:35147/maraghi_lead_test/index.php/DataFetch/getThirdServiceRemainderCustomersDetails"; // Replace with your actual API URL

        $data = json_encode([
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
            'reminder_days_1'   => $reminder_days_1,
            'reminder_days_2'   => $reminder_days_2,
            'reminder_days_3'   => $reminder_days_3

        ]); // Convert to JSON format

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8'
        ]);
        curl_setopt($ch, CURLOPT_POST, TRUE); // Set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Send JSON data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $curlResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);


        $response = [
            'summary'  => $curlResponse['data'] ?? [],
            'ret_data' => 'success'
        ];

        return $response;
    }
}
