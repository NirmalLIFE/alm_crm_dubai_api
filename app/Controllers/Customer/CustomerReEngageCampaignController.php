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

        $url = "http://almaraghi.fortiddns.com:35147/maraghi_lead_test/index.php/DataFetch/getDXBServiceRemainderCustomers"; // Replace with your actual API URL

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
}
