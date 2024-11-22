<?php

namespace App\Controllers\ServiceRem;

use App\Models\ServiceRem\ServiceReminderCustomersModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use App\Models\ServiceRem\ServiceReminderModel;
use Config\Common;
use Config\Validation;

class ServiceReminderController extends ResourceController
{
    use ResponseTrait;
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
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $seRemMaster = new ServiceReminderModel();
            $result = $seRemMaster->where('srm_delete_flag', 0)->findAll();
            if ($result) {

                $data['ret_data'] = "success";
                $data['rem_customers'] = $result;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
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


    public function uploadServiceRemainderList()
    {
        helper(['form', 'url']);
        $UserModel = new UserModel();
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

        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $in_data = array();
            $file = $this->request->getFile('attachment');
            $file_name =  $this->request->getVar('file_name');
            $file_month =  $this->request->getVar('selected_month');


            $newName = mt_rand(1000, 9999) . "-" . $file->getName();
            $file->move('../public/uploads/serviceRem/', $newName);
            $file = fopen("../public/uploads/serviceRem/" . $newName, "r");
            $numberOfFields = 1;
            $csvArr = array();
            while (($filedata = fgetcsv($file, 10000, ",")) !== FALSE) {
                $num = count($filedata);
                if ($num == $numberOfFields && !empty($filedata[0])) {
                    $csv = explode('|', $filedata[0]);
                    $csvArr = array();
                    if (count($csv) == 16 && $csv[0] != 'CUSTOMER CODE') {
                        $csvArr['customer_code'] = $csv[0];
                        $csvArr['customer_name'] = $csv[1];
                        $csvArr['phone'] = $csv[2];
                        $csvArr['sms_mobile'] = $csv[3];
                        $csvArr['sms_option'] = $csv[4];
                        $csvArr['email'] = $csv[5];
                        $csvArr['reg_no'] = $csv[6];
                        $csvArr['chasis'] = $csv[7];
                        $csvArr['brand'] = $csv[8];
                        $csvArr['model_code'] = $csv[9];
                        $csvArr['model_name'] = $csv[10];
                        $csvArr['model_year'] = $csv[11];
                        $csvArr['miles_done'] = $csv[12];
                        $csvArr['visits'] = $csv[13];
                        $csvArr['invoice_date'] = $csv[14];
                        array_push($in_data, $csvArr);
                    }
                }
            }
            fclose($file);
            if (sizeof($in_data) > 0) {
                $seRemMaster = new ServiceReminderModel();
                $seRemCustomer = new ServiceReminderCustomersModel();
                $masterData = [
                    'srm_file_name' => $file_name,
                    'srm_year' => date("Y"),
                    'srm_month' => $file_month,
                    'srm_created_by' => $tokendata['uid'],
                    'srm_updated_by' => $tokendata['uid']
                ];
                $ret = $seRemMaster->insert($masterData);
                if ($ret > 0) {
                    $customers = [];
                    foreach ($in_data as $value) {
                        $customers[] = array(
                            'src_customer_code' => $value['customer_code'],
                            'src_rem_master_id' => $ret,
                            'src_chassis_number' => $value['chasis'],
                            'src_invoice_date' => $value['invoice_date'],
                            'src_wb_message_flag' => 0
                        );
                    }
                    $seRemCustomer->insertBatch($customers);
                    $data['ret_data'] = "success";
                    return $this->respond($data, 200);
                } else {
                    $data['ret_data'] = "fail";
                    $data['ret_message'] = "db entry error";
                    return $this->respond($data, 200);
                }
            } else {
                $data['ret_data'] = "fail";
                $data['ret_message'] = "no customer found";
                return $this->respond($data, 200);
            }
        } else {
            $data['ret_data'] = "fail";
            $data['ret_message'] = "incomplete data";
            return $this->respond($data, 200);
        }
    }

    public function serviceReminderCustomerList()
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
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $seRemMaster = new ServiceReminderModel();
            $result = $seRemMaster->where('srm_delete_flag', 0)->where('srm_id', base64_decode($this->request->getVar('reminder_id')))->first();
            if ($result) {
                $seRemCustomer = new ServiceReminderCustomersModel();
                $data['rem_customers'] = $seRemCustomer->where('src_delete_flag', 0)->where('src_rem_master_id', base64_decode($this->request->getVar('reminder_id')))
                    ->join('cust_data_laabs', 'cust_data_laabs.customer_code =alm_service_reminder_customers.src_customer_code', 'left')
                    ->join('cust_veh_data_laabs cv', 'cv.chassis_no= alm_service_reminder_customers.src_chassis_number', 'left')
                    ->findAll();
                $data['ret_data'] = "success";
                $data['rem_master'] = $result;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        } else {
            $data['ret_data'] = "fail";
            return $this->respond($data, 200);
        }
    }

    public function sendServiceReminders()
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
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $customers = $this->request->getVar('customer_list');
            $common = new Common();
            $seRemCustomer = new ServiceReminderCustomersModel();
            foreach ($customers as $key => $value) {
                $messageData = array(
                    "messaging_product" => "whatsapp",
                    "recipient_type" => "individual",
                    "to" => '971' . substr($value->phone, -9),
                    "type" => "template",
                    'template' => array("name" => "service_reminder", 'language' => array("code" => "en"), 'components' =>
                    array(
                        array(
                            "type" => "header",
                            "parameters" => array(
                                array("type" => "image", "image" => array("link" => "https://tmpfiles.org/dl/2881719/snipped.jpg"))
                            )
                        ),
                        array(
                            "type" => "body",
                            "parameters" => array(array("type" => "text", "text" => ucwords($value->customer_name)), array("type" => "text", "text" => strtoupper($value->reg_no)), array("type" => "text", "text" => $value->src_invoice_date))
                        )
                    ))
                );
                $return = $common->sendWhatsappMessage($messageData, '971509766075');
                // return $this->respond($return->messages[0]->message_status, 200);
                if (isset($return->messages)) {
                    if ($return->messages[0]->message_status == "accepted") {
                        $seRemCustomer->set('src_wb_id', $return->messages[0]->id)->set('src_wb_message_flag', 1)->where('src_id', $value->src_id)->update();
                    }
                }
            }
            $response["ret_data"] = "success";
            return $this->respond($response, 200);
        } else {
            $data['ret_data'] = "fail";
            return $this->respond($data, 200);
        }
    }
}
