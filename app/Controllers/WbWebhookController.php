<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Settings\WhatsappMessageModel;
use Netflie\WhatsAppCloudApi\WebHook;
use App\Models\ServiceRem\ServiceReminderCustomersModel;
use App\Models\PSFModule\PSFMasterModel;
use App\Models\PSFModule\PSFstatusTrackModel;
use CodeIgniter\Log\Exceptions\LogException;
use Config\Common;
use DateTime;

use App\Models\Leads\LeadModel;
use App\Models\Leads\LeadActivityModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\MaragiCustomerModel;

class WbWebhookController extends ResourceController
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

        $payload = $_GET;
        $mode = $payload['hub_mode'] ?? null;
        $token = $payload['hub_verify_token'] ?? null;
        $challenge = $payload['hub_challenge'] ?? '';

        if ('subscribe' == $mode && $token == "almaraghibenzuae!@#") {
            return $this->respond(intval($challenge), 200);
        } else {
            return $this->respond($challenge, 403);
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

        // file_put_contents('test1.txt', file_get_contents('php://input'));
        // $payload = json_decode(file_get_contents('php://input'), true);
        // $entry = $payload['entry'][0] ?? [];
        // $message = $entry['changes'][0]['value']['messages'][0] ?? [];
        // $status = $entry['changes'][0]['value']['statuses'][0] ?? [];
        // $contact = $entry['changes'][0]['value']['contacts'][0] ?? [];
        // $metadata = $entry['changes'][0]['value']['metadata'] ?? [];
        // if ($status) {
        //     $save_status = 0;
        //     if ($status['status'] == "failed") {
        //         $save_status = 5;
        //     } else if ($status['status'] == "delivered") {
        //         $save_status = 2;
        //     } else if ($status['status'] == "read") {
        //         $save_status = 3;
        //     } else if ($status['status'] == "sent") {
        //         $save_status = 1;
        //     }
        //     $seRemCustomer = new ServiceReminderCustomersModel();
        //     $seRemCustomer->set('src_wb_message_flag', $save_status)->set('src_send_date', $status['timestamp'])->where('src_wb_id', $status['id'])->update();
        // }
        // if ($message) {
        //     file_put_contents('test3.txt', $message['context']['id']);
        //     if ($message['type'] == 'button' && $message['button']['payload'] == 'BOOK NOW') {

        //         $seRemCustomer = new ServiceReminderCustomersModel();
        //         $seRemCustomer->set('src_wb_message_flag', 5)->set('src_send_date', $message['timestamp'])->where('src_wb_id', $message['context']['id'])->update();
        //     }
        // }

        $payload = json_decode(file_get_contents('php://input'), true);
        $entry = $payload['entry'][0] ?? [];
        $message = $entry['changes'][0]['value']['messages'][0] ?? [];
        $status = $entry['changes'][0]['value']['statuses'][0] ?? [];
        $contact = $entry['changes'][0]['value']['contacts'][0] ?? [];
        $metadata = $entry['changes'][0]['value']['metadata'] ?? [];

        $wb_model = new WhatsappMessageModel();
        $common = new Common();
        if ($status) {
            $wb_id = $status['id'];
            $wb_message = $wb_model->where('wb_message_id', $status['id'])->first();
        } else if (isset($message['context'])) {
            $wb_id = $message['context']['id'];
            $wb_message = $wb_model->where('wb_message_id', $message['context']['id'])->first();
        } else {

            $messageData = array(
                "messaging_product" => "whatsapp",
                "recipient_type" => "individual",
                "to" => $message['from'],
                "type" => "text",
                'text' => array("preview_url" => true, 'body' => "Hi there! Thank you for contacting *Al Maraghi Auto Repairs*.\nWe understand you have questions, and we're happy to help! To connect with a representative who can assist you further, please click on the following link:\nhttps://wa.me/971508502892")
            );
            $return = $common->sendWhatsappMessage($messageData, '971509766075');
        }

        if (sizeof($status) > 0) {
            log_message('error', "I am here");
            $save_status = 0;
            $save_response = 0;
            if ($status['status'] == "failed") {
                $save_status = 5;
            } else if ($status['status'] == "delivered") {
                $save_status = 2;
            } else if ($status['status'] == "read") {
                $save_status = 3;
            } else if ($status['status'] == "sent") {
                $save_status = 1;
            }
            $wb_data = [
                'wb_replay_body' => $save_response,
                'wb_message_status' => $save_status,
                'wb_updated_on' => date("Y-m-d H:i:s")
            ];
            $wb_model->where('wb_message_id', $wb_id)->set($wb_data)->update();
        }
        if (isset($message['context'])) {
            if ($wb_message && $wb_message['wb_message_source'] == 1) {


                if (sizeof($message) > 0) {
                    if ($message['type'] == 'button' && $message['button']['payload'] == '5 (Extremely Satisfied)') {
                        $save_status = 4;
                        $save_response = 5;
                    } else if ($message['type'] == 'button' && $message['button']['payload'] == '4 (Very Satisfied)') {
                        $save_status = 4;
                        $save_response = 4;
                    } else if ($message['type'] == 'button' && $message['button']['payload'] == '3 (Satisfied)') {
                        $save_status = 4;
                        $save_response = 3;
                    } else if ($message['type'] == 'button' && $message['button']['payload'] == '2 (Dissatisfied)') {
                        $save_status = 4;
                        $save_response = 2;
                    } else if ($message['type'] == 'button' && $message['button']['payload'] == '1 (Very Dissatisfied)') {
                        $save_status = 4;
                        $save_response = 1;
                    }
                }
                $wb_data = [
                    'wb_replay_body' => $save_response,
                    'wb_message_status' => $save_status
                ];
                $wb_model->where('wb_message_id', $wb_id)->set($wb_data)->update();

                try {
                    $wb_master_id = $wb_model->select('wb_id,wb_phone,wb_created_on')->where('wb_message_id', $wb_id)->first();
                    $created_on = new DateTime($wb_master_id['wb_created_on']);
                    $now = new DateTime();
                    // Calculate the difference in hours
                    $interval = $created_on->diff($now);
                    $hours_diff = $interval->h + ($interval->days * 24);
                    // Check if it's more than 24 hours
                    $is_over_24_hours = $hours_diff > 24;

                    if (sizeof($message) > 0 && !$is_over_24_hours) {
                        $psf_master = new PSFMasterModel();
                        if ($message['type'] == 'button' && $message['button']['payload'] == '5 (Extremely Satisfied)') {
                            $psf_master_data = [
                                'psfm_status' => 20,
                                'psfm_sa_rating' => 5,
                                'psfm_lastresponse' => 1,
                                'psfm_last_attempted_date' => date("Y-m-d H:i:s"),
                                'psfm_updated_by' => 1,
                                'psfm_updated_on' => date("Y-m-d H:i:s"),
                                'psfm_primary_response_type' => 1
                            ];
                            $task = "Responded in whatsapp with 5 star rating";
                            $status = 20;
                            $response = 1;
                            $messageData = array(
                                "messaging_product" => "whatsapp",
                                "recipient_type" => "individual",
                                "to" => $wb_master_id['wb_phone'],
                                "type" => "text",
                                'text' => array("preview_url" => false, 'body' => 'Your satisfaction is the fuel that keeps our engines running! Thanks for the awesome feedback!')
                            );
                            $return = $common->sendWhatsappMessage($messageData, '971509766075');
                        } else if ($message['type'] == 'button' && $message['button']['payload'] == '4 (Very Satisfied)') {
                            $psf_master_data = [
                                'psfm_status' => 0,
                                'psfm_lastresponse' => 2,
                                'psfm_last_attempted_date' => date("Y-m-d H:i:s"),
                                'psfm_updated_by' => 1,
                                'psfm_updated_on' => date("Y-m-d H:i:s"),
                                'psfm_primary_response_type' => 0
                            ];
                            $task = "Responded in whatsapp with 4 star rating";
                            $status = 0;
                            $response = 2;
                            $messageData = array(
                                "messaging_product" => "whatsapp",
                                "recipient_type" => "individual",
                                "to" => $wb_master_id['wb_phone'],
                                "type" => "text",
                                'text' => array("preview_url" => false, 'body' => "We are glad you were mostly satisfied with your experience, and we hope you'll give us another chance to impress you in the future. Our representative, will be in touch with you shortly to discuss your experience further and ensure your next visit is exceptional.")
                            );
                            $return = $common->sendWhatsappMessage($messageData, '971509766075');
                        } else if ($message['type'] == 'button' && $message['button']['payload'] == '3 (Satisfied)') {
                            $psf_master_data = [
                                'psfm_status' => 0,
                                'psfm_lastresponse' => 3,
                                'psfm_last_attempted_date' => date("Y-m-d H:i:s"),
                                'psfm_updated_by' => 1,
                                'psfm_updated_on' => date("Y-m-d H:i:s"),
                                'psfm_primary_response_type' => 0
                            ];
                            $task = "Responded in whatsapp with 3 star rating";
                            $status = 0;
                            $response = 3;
                            $messageData = array(
                                "messaging_product" => "whatsapp",
                                "recipient_type" => "individual",
                                "to" => $wb_master_id['wb_phone'],
                                "type" => "text",
                                'text' => array("preview_url" => false, 'body' => "We are glad you were satisfied with your experience, and we hope you'll give us another chance to impress you in the future. Our representative, will be in touch with you shortly to discuss your experience further and ensure your next visit is exceptional.")
                            );
                            $return = $common->sendWhatsappMessage($messageData, '971509766075');
                        } else if ($message['type'] == 'button' && $message['button']['payload'] == '2 (Dissatisfied)') {
                            $psf_master_data = [
                                'psfm_status' => 0,
                                'psfm_lastresponse' => 5,
                                'psfm_last_attempted_date' => date("Y-m-d H:i:s"),
                                'psfm_updated_by' => 1,
                                'psfm_updated_on' => date("Y-m-d H:i:s"),
                                'psfm_primary_response_type' => 0
                            ];
                            $task = "Responded in whatsapp as dissatisfied";
                            $status = 0;
                            $response = 5;
                            $messageData = array(
                                "messaging_product" => "whatsapp",
                                "recipient_type" => "individual",
                                "to" => $wb_master_id['wb_phone'],
                                "type" => "text",
                                'text' => array("preview_url" => false, 'body' => "We are committed to providing excellent service to every customer, and we are deeply disappointed that we fell short of your expectations. We sincerely apologize for any inconvenience caused. Our representative, will be in touch with you shortly to discuss your experience further and ensure your next visit with us is exceptional.")
                            );
                            $return = $common->sendWhatsappMessage($messageData, '971509766075');
                        } else if ($message['type'] == 'button' && $message['button']['payload'] == '1 (Very Dissatisfied)') {
                            $psf_master_data = [
                                'psfm_status' => 0,
                                'psfm_lastresponse' => 5,
                                'psfm_last_attempted_date' => date("Y-m-d H:i:s"),
                                'psfm_updated_by' => 1,
                                'psfm_updated_on' => date("Y-m-d H:i:s"),
                                'psfm_primary_response_type' => 0
                            ];
                            $task = "Responded in whatsapp as very dissatisfied";
                            $status = 0;
                            $response = 5;
                            $messageData = array(
                                "messaging_product" => "whatsapp",
                                "recipient_type" => "individual",
                                "to" => $wb_master_id['wb_phone'],
                                "type" => "text",
                                'text' => array("preview_url" => false, 'body' => "We are committed to providing excellent service to every customer, and we are deeply disappointed that we fell short of your expectations. We sincerely apologize for any inconvenience caused. Our representative, will be in touch with you shortly to discuss your experience further and ensure your next visit with us is exceptional.")
                            );
                            $return = $common->sendWhatsappMessage($messageData, '971509766075');
                        }
                        $this->db->transStart();
                        $temp = $psf_master->where('psfm_primary_whatsapp_id', $wb_master_id['wb_id'])->first();
                        $psfmaster_ret = $psf_master->update($temp["psfm_id"], $psf_master_data);
                        $psf_id = $psf_master->where('psfm_primary_whatsapp_id', $wb_master_id['wb_id'])->first();
                        $tracker_data = [
                            'pst_task' => $task,
                            'pst_psf_status' => $status,
                            'pst_response' => $response,
                            'pst_sourceid' => 1,
                            'pst_destid' => 1,
                            'pst_psf_id' => $psf_id['psfm_id'],
                            'pst_created_by' => 1,
                            'pst_psf_call_type' => 2,
                            'pst_created_on' => date("Y-m-d H:i:s"),
                        ];
                        $psfstatustrackModel = new PSFstatusTrackModel();
                        $tracker = $psfstatustrackModel->insert($tracker_data);

                        if ($this->db->transStatus() === false) {
                            $this->db->transRollback();
                        } else {
                            $this->db->transCommit();
                        }
                    }
                } catch (LogException $e) {
                    log_message('error', 'Webhook Error: ' . $e->getMessage());
                }
            }
        }
    }

    // public function createLeadFromWhatsapp()
    // {
    //     log_message('error', 'Payload: Iam here');
    //     $payload = json_decode(file_get_contents('php://input'), true);
    //     log_message('error', 'Payload: ' .  json_encode($payload));
    //     $message = $payload['message']; //$payload['message']['message']['text']
    //     $contact = $payload['contact'];
    //     try {
    //         $model = new LeadModel();
    //         $leadAcModel = new LeadActivityModel();
    //         $cust_mastr_model = new CustomerMasterModel();
    //         $maraghi_cust_model = new MaragiCustomerModel();


    //         $phone = $contact['phone'];
    //         $builder = $this->db->table('sequence_data');
    //         $builder->selectMax('current_seq');
    //         $query = $builder->get();
    //         $row = $query->getRow();
    //         $lead_code = $row->current_seq;
    //         $leadSeqvalfinal = $row->current_seq;
    //         if (strlen($row->current_seq) == 1) {
    //             $lead_code = "ALMLD-000" . $row->current_seq;
    //         } else if (strlen($row->current_seq) == 2) {
    //             $lead_code = "ALMLD-00" . $row->current_seq;
    //         } else if (strlen($row->current_seq) == 3) {
    //             $lead_code = "ALMLD-0" . $row->current_seq;
    //         } else {
    //             $lead_code = "ALMLD-" . $row->current_seq;
    //         }
    //         $assigned = $this->request->getVar('assigned_to');
    //         $social_Media_Source = $this->request->getVar('social_media_source') ? $this->request->getVar('social_media_source') : '0';
    //         $smc_id = $this->request->getVar('social_media_camp') ? $this->request->getVar('social_media_camp') : '0';
    //         $cust_id = 0;
    //         $this->db->transStart();
    //         $data = [
    //             'lead_code' => $lead_code,
    //             'lead_note' => $message['message']['text'],
    //             'lang_id' => 1,
    //             'purpose_id' => 10,
    //             'register_number' => '',
    //             'vehicle_model' => '',
    //             'source_id' => 7,
    //             'lead_social_media_source' => 0,
    //             'lead_social_media_mapping' => 0,
    //             'lead_createdby' => 1,
    //             'lead_createdon' => date("Y-m-d H:i:s"),
    //             'lead_creted_date' => date("Y-m-d H:i:s"),
    //             'lead_updatedon' => date("Y-m-d H:i:s"),
    //             'status_id' => 6,
    //         ];
    //         $resC = $cust_mastr_model->where('cust_phone', $phone)->first();
    //         if ($resC) {
    //             $cust_id = $resC['cus_id'];
    //             $custId = [
    //                 'cus_id' =>  $cust_id,
    //                 'name' => $resC['cust_name'],
    //                 'phone' => $resC['cust_phone'],
    //             ];
    //             $data = array_merge($data, $custId);
    //         } else {
    //             $maraghi_data = $maraghi_cust_model->where('phone', $phone)->join('customer_type', 'customer_type.cst_code = customer_type')->join('country_master', 'country_master.country_code = country')->select('customer_code,cst_id,customer_title,customer_name,addr1,city,country_master.id,phone')->first();
    //             if ($maraghi_data) {
    //                 $custData = [
    //                     'cust_type' => $maraghi_data['cst_id'],
    //                     'cust_name' => $maraghi_data['customer_name'],
    //                     'cust_salutation' => $maraghi_data['customer_title'],
    //                     'cust_address' => $maraghi_data['addr1'],
    //                     'cust_emirates' => $maraghi_data['city'],
    //                     'cust_city' => $maraghi_data['city'],
    //                     'cust_country' => $maraghi_data['id'],
    //                     'cust_phone' =>  $maraghi_data['phone'],
    //                     'cust_alternate_no' => $maraghi_data['phone'],
    //                     'cust_alm_code' => $maraghi_data['customer_code'],
    //                     'lead_createdby' => 1,
    //                     'cust_created_on' => date("Y-m-d H:i:s"),

    //                 ];
    //                 $ins_id = $cust_mastr_model->insert($custData);
    //                 $custId = [
    //                     'cus_id' =>  $ins_id,
    //                     'name' => $maraghi_data['customer_name'],
    //                     'phone' => $maraghi_data['phone'],
    //                 ];
    //                 $data = array_merge($data, $custId);
    //             } else {
    //                 $custData = [
    //                     'cust_name' => $contact['firstName'],
    //                     'cust_phone' => $phone,
    //                     'cust_alternate_no' => '',
    //                 ];
    //                 $ins_id = $cust_mastr_model->insert($custData);
    //                 $custId = [
    //                     'cus_id' =>  $ins_id,
    //                     'name' => $contact['firstName'],
    //                     'phone' => $phone,
    //                 ];
    //                 $data = array_merge($data, $custId);
    //             }
    //         }
    //         $lead_id = $model->insert($data);
    //         if ($this->db->transStatus() === false) {
    //             $this->db->transRollback();
    //             $data['ret_data'] = "fail";
    //             return $this->respond($data, 200);
    //         } else {
    //             $this->db->transCommit();
    //             $leadactivitydata = [
    //                 'lac_activity' => 'Created Lead ' . $lead_code,
    //                 'lac_activity_by' => 1,
    //                 'lac_lead_id' => $lead_id,
    //             ];
    //             $leadactivity = $leadAcModel->insert($leadactivitydata);
    //             $builder = $this->db->table('sequence_data');
    //             $builder->set('current_seq', ++$leadSeqvalfinal);
    //             $builder->update();
    //             $data['ret_data'] = "success";
    //             return $this->respond($data, 200);
    //         }
    //     } catch (LogException $e) {
    //         log_message('error', 'Webhook Error: ' . $e->getMessage());
    //     }

    //     return "success";
    // }

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
}
