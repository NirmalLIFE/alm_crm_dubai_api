<?php

namespace App\Controllers\Whatsapp;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Whatsapp\WhatsappCustomerMasterModel;
use App\Models\Whatsapp\WhatsappCustomerMessageModel;
use CodeIgniter\Log\Exceptions\LogException;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\Leads\LeadModel;
use App\Models\Leads\LeadActivityModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\SocialMediaCampaign\SocialMediaCampaignModel;
use \DateTime;
use App\Models\Whatsapp\WhatsappFollowUpTimeModel;
use App\Models\Whatsapp\WhatsappAssignedStaffsModel;
use App\Models\Whatsapp\WhatsappFollowupMessageTimeExceededLogs;
use App\Models\Whatsapp\WhatsappCampaignMessageModel;
use App\Models\Whatsapp\WhatsappCampaignModel;
use App\Models\Leads\AppointmentMasterModel;
use App\Models\Leads\AppointmentModel;
use App\Models\Leads\AppointmentLogModel;
use App\Models\Dissatisfied\DissatisfiedMasterModel;
use App\Models\Dissatisfied\DissatisfiedLogModel;

class WhatsappChatController extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

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

    public function create()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        log_message('error', 'Payload2: Iam here');
        log_message('error',  json_encode($payload));
        $entry = $payload['entry'][0] ?? [];
        $message = $entry['changes'][0]['value']['messages'][0] ?? [];
        $msg_status = $entry['changes'][0]['value']['statuses'][0] ?? [];
        $contact = $entry['changes'][0]['value']['contacts'][0] ?? [];
        $metadata = $entry['changes'][0]['value']['metadata'] ?? [];
        $common = new Common();
        try {
            if ($contact && $entry["id"] == 449653888221729) {
                $wb_customer = new WhatsappCustomerMasterModel();
                $wb_message = new WhatsappCustomerMessageModel();
                $wb_camp_message = new WhatsappCampaignMessageModel();
                $msg_customer = $wb_customer->where('wb_cus_mobile', $contact['wa_id'])->first();
                if (!$msg_customer) {
                    $tracker_data = [
                        'wb_cus_name' => $contact['profile']['name'] ? $contact['profile']['name'] : 'Nil',
                        'wb_cus_mobile' => $contact['wa_id'],
                        'wb_cus_profile_pic' => '',
                        'wb_cus_follow_up' => 1,
                    ];

                    $customer_id = $wb_customer->insert($tracker_data);
                    $msg_customer = $wb_customer->where('wb_cus_id', $customer_id)->first();
                    $msg_customer['new_flag'] = true;
                    $msg_customer['wb_cus_block'] = false;
                } else {
                    $msg_customer['new_flag'] = false;
                    $tracker_data = [
                        'wb_cus_follow_up' => 1,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $contact['wa_id'])->set($tracker_data)->update();
                }
                // log_message('error',  "I am here" . json_encode($msg_customer));
                if (!$msg_customer['wb_cus_block']) {
                    $status = [1, 7, 8];    //->orWhere('status_id', '7')
                    $lmodel = new LeadModel();
                    $lead_list = $lmodel->whereIn('status_id', $status)
                        ->where('RIGHT(phone,9)',  substr($msg_customer['wb_cus_mobile'], -9))->orderBy('lead_id', 'desc')->findAll();
                    $last_lead = $lmodel->where('RIGHT(phone,9)',  substr($msg_customer['wb_cus_mobile'], -9))->orderBy('lead_id', 'desc')->first();
                    $exist_msg = $wb_message->where('alm_wb_msg_master_id', $message['id'])->findAll();
                    // log_message('error',  "check last lead list" . json_encode($last_lead));
                    // log_message('error',  "I am here nil" . json_encode($exist_msg));
                    if ($message && sizeof($exist_msg) == 0) {

                        // $oneHourAgo = (new DateTime())->modify('-1 hour')->format('Y-m-d H:i:s'); // One hour ago
                        $row = $this->db->table('sequence_data')
                            ->select('whatsapp_auto_msg_hours')
                            ->get()
                            ->getRow();
                        $hoursAgo = $row ? (int) $row->whatsapp_auto_msg_hours : 1;
                        $timeAgo = (new DateTime())->modify("-{$hoursAgo} hour")->format('Y-m-d H:i:s');

                        $currentDateTime = new DateTime();
                        $currentDayOfWeek = $currentDateTime->format('w'); // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
                        $currentTime = $currentDateTime->format('H:i:s'); // HH:MM:SS
                        $currentDate = $currentDateTime->format('Y-m-d');

                        log_message('error',  "I am oneHourAgo oneHourAgo" . json_encode($timeAgo));

                        $workingHours = [
                            'Monday-Saturday' => [['08:15:00', '19:10:00']],
                            'Friday'          => [['08:15:00', '19:10:00']]
                        ];
                        $isNonWorkingHours = true; // Default to non-working

                        // Check for Sunday timings (fully closed)
                        if ($currentDayOfWeek == 0) {
                            $isNonWorkingHours = true;
                        } else {
                            foreach ($workingHours as $days => $timeSlots) {
                                if (
                                    ($days === 'Monday-Saturday' && ($currentDayOfWeek >= 1 && $currentDayOfWeek <= 4) || $currentDayOfWeek == 6) ||
                                    ($days === 'Friday' && $currentDayOfWeek == 5)
                                ) {
                                    foreach ($timeSlots as [$start, $end]) {
                                        if ($currentTime >= $start && $currentTime <= $end) {
                                            $isNonWorkingHours = false; // Inside working hours
                                            break 2; // Exit both loops
                                        }
                                    }
                                }
                            }
                        }

                        $wb_customer_data = $wb_customer->where('wb_cus_mobile', $contact['wa_id'])->select('wb_cus_id')->first();
                        $wb_cus_id = $wb_customer_data ? $wb_customer_data['wb_cus_id'] : null;

                        $messaged = $wb_message->where('alm_wb_msg_customer', $wb_cus_id)
                            ->like('alm_wb_msg_content', "Thank you for reaching out")
                            ->where('alm_wb_msg_created_on >=', $timeAgo)
                            ->orderBy('alm_wb_msg_id', 'DESC')
                            ->first();


                        $microtime = microtime(true);
                        $seconds = floor($microtime);
                        $milliseconds = round(($microtime - $seconds) * 1000);
                        $formattedDate = date('Y-m-d H:i:s', $seconds) . sprintf('.%03d', $milliseconds);
                        if ($message['type'] == 'text') {
                            $message_data = [
                                'alm_wb_msg_master_id' => $message['id'],
                                'alm_wb_msg_source' => 1,
                                'alm_wb_msg_type' => 4,
                                'alm_wb_msg_content' => $message['text']['body'],
                                'alm_wb_msg_status' => 2,
                                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                'alm_wb_msg_reply_id' => '',
                                'alm_wb_msg_created_on' => $formattedDate,
                                'alm_wb_msg_updated_on' => $formattedDate,
                            ];
                            $wb_message->insert($message_data);
                            $data = [
                                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                'message_type' => 4,
                                'message_source' => 1,
                                'message' => $message['text']['body'],
                                'time' => date('Y-m-d H:i:s'),
                                'toUserId' => 0,
                                'fromUserId' => $msg_customer
                            ];
                            $this->sendSocketMessage($data);
                            // $this->sendWelcomeMessage($msg_customer);
                        }
                        if ($message['type'] == 'button') {
                            // $replyId = $wb_message->select('alm_wb_msg_id')->where('alm_wb_msg_master_id',$message['context']['id'])->first();
                            $replyId = $message['context']['id'];
                            $message_data = [
                                'alm_wb_msg_master_id' => $message['id'],
                                'alm_wb_msg_source' => 1,
                                'alm_wb_msg_type' => 4,
                                'alm_wb_msg_content' => $message['button']['text'],
                                'alm_wb_msg_status' => 2,
                                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                'alm_wb_msg_reply_id' => '', // $replyId ['alm_wb_msg_id']
                                'alm_wb_msg_created_on' => $formattedDate,
                                'alm_wb_msg_updated_on' => $formattedDate,
                            ];
                            $wb_message->insert($message_data);
                            $data = [
                                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                'message_type' => 4,
                                'message_source' => 1,
                                'message' => $message['button']['text'],
                                'time' => date('Y-m-d H:i:s'),
                                'toUserId' => 0,
                                'fromUserId' => $msg_customer
                            ];
                            $this->sendSocketMessage($data);
                            // $this->sendWelcomeMessage($msg_customer);

                            $one_year_after_sr_ids = $wb_message->where('alm_wb_msg_customer', $msg_customer['wb_cus_id'])
                                ->like('alm_wb_msg_content', 'Get your Free Pick up & Drop')
                                ->select('alm_wb_msg_master_id')
                                ->findAll();

                            $one_year_sr_ids = $wb_message->where('alm_wb_msg_customer', $msg_customer['wb_cus_id'])
                                ->like('alm_wb_msg_content', 'Time for Your Factory Recommended Service')
                                ->select('alm_wb_msg_master_id')
                                ->findAll();

                            $eight_month_sr_ids = $wb_message->where('alm_wb_msg_customer', $msg_customer['wb_cus_id'])
                                ->like('alm_wb_msg_content', 'Service Reminder')
                                ->select('alm_wb_msg_master_id')
                                ->findAll();


                            $one_year_after_sr_ids = array_column($one_year_after_sr_ids, 'alm_wb_msg_master_id');
                            $one_year_sr_ids = array_column($one_year_sr_ids, 'alm_wb_msg_master_id');
                            $eight_month_sr_ids = array_column($eight_month_sr_ids, 'alm_wb_msg_master_id');


                            // Check if reply_id matches one_year_sr_ids
                            if (in_array($replyId, $one_year_after_sr_ids)) {
                                if ($message['button']['text'] == 'Book An Appointment') {
                                    $this->oneYearExceededPickupAndDropoff($contact, $msg_customer);
                                    $contact_last_9 = substr($contact['wa_id'], -9);
                                    $reg_no = $wb_camp_message
                                        ->where("RIGHT(alm_wb_camp_msg_cus_phone, 9)", $contact_last_9)
                                        ->select('alm_wb_camp_msg_cus_reg_no')
                                        ->orderBy('alm_wb_camp_msg_id', 'DESC')
                                        ->first();

                                    $alertMessage = "🔔 Appointment Selection Alert\n\n"
                                        . "Hello,\n\n"
                                        . "Customer Number [ " . $contact['wa_id'] . " ] has selected the *Book an Appointment* option.\n\n"
                                        . "🚗 Reg No: " . ($reg_no ? $reg_no['alm_wb_camp_msg_cus_reg_no'] : 'N/A') . "\n\n"
                                        . "Please take note. ✅";


                                    $messageData = array(
                                        "messaging_product" => "whatsapp",
                                        "recipient_type" => "individual",
                                        "to" => "+918138055705",
                                        "type" => "text",
                                        "text" => array(
                                            "body" => $alertMessage
                                        )
                                    );

                                    $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                                } else if ($message['button']['text'] == 'Remind Me Later') {
                                    // $this->oneYearExceededRemindMeLater($contact, $msg_customer);
                                    // Calculate the reminder date
                                    $wb_cus_remind_date = date('Y-m-d', strtotime("+30 days"));
                                    $messageData = array(
                                        "messaging_product" => "whatsapp",
                                        "recipient_type" => "individual",
                                        "to" => $contact['wa_id'],
                                        "type" => "text",
                                        "text" => array(
                                            "body" => "⏳ We will remind you in *🕰️ 30 days (" . $wb_cus_remind_date . ").* Please stay tuned! 📲"
                                        )
                                    );
                                    $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                                    if (isset($returnMsg->messages)) {
                                        if ($returnMsg->messages[0]->id != "") {

                                            $message_data = [
                                                'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                                                'alm_wb_msg_source' => 2,
                                                'alm_wb_msg_type' => 4,
                                                'alm_wb_msg_content' => $messageData['text']['body'],
                                                'alm_wb_msg_status' => 1,
                                                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                                'alm_wb_msg_reply_id' => '',
                                                'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                                                'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                                                'alm_wb_msg_staff_id' => 1,
                                            ];
                                            $result = $wb_message->insert($message_data);
                                            $data = [
                                                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                                'message_type' => 4,
                                                'message_source' => 2,
                                                'message' => $messageData['text']['body'],
                                                'time' => date('Y-m-d H:i:s'),
                                                'toUserId' => 1,
                                                'fromUserId' => $msg_customer
                                            ];
                                            $this->sendSocketMessage($data);
                                            $mobile = $msg_customer['wb_cus_mobile'];
                                            $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                                            if ($currentFollowUp) {
                                                $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                                                if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                                                    $followUpCount++;
                                                } else if ($followUpCount == 3) {
                                                    $followUpCount = 6;
                                                } else if ($followUpCount == 1) {
                                                    $followUpCount = 2;
                                                }
                                                $tracker_data = [
                                                    'wb_cus_follow_up' => $followUpCount,
                                                    'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                                                    'wb_cus_remind_date' => $wb_cus_remind_date,
                                                    'wb_cus_remind_flag' => 1,  // Need To Remind
                                                    'wb_cus_assigned' => 19
                                                ];
                                                $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                                            }
                                        }
                                    }
                                } else if ($message['button']['text'] == 'Skip For This Time') {
                                    $this->notInterestedOption($contact, $msg_customer);
                                }
                                $tracker_reminder_data = [
                                    'wb_cus_id ' => $msg_customer['wb_cus_id'],
                                    'wb_cus_reminder' => 5,  //Third Service Reminder
                                ];
                                $wb_customer->where('wb_cus_id', $msg_customer['wb_cus_id'])->set($tracker_reminder_data)->update();
                                // log_message('error',  "I one year service reminder >>>>>");
                            } else if (in_array($replyId, $one_year_sr_ids)) {
                                if ($message['button']['text'] == 'Book An Appointment') {
                                    $this->oneYearPickupAndDropoff($contact, $msg_customer);
                                    $contact_last_9 = substr($contact['wa_id'], -9);
                                    $reg_no = $wb_camp_message
                                        ->where("RIGHT(alm_wb_camp_msg_cus_phone, 9)", $contact_last_9)
                                        ->select('alm_wb_camp_msg_cus_reg_no')
                                        ->orderBy('alm_wb_camp_msg_id', 'DESC')
                                        ->first();

                                    $alertMessage = "🔔 Appointment Selection Alert\n\n"
                                        . "Hello,\n\n"
                                        . "Customer Number [ " . $contact['wa_id'] . " ] has selected the *Book an Appointment* option.\n\n"
                                        . "🚗 Reg No: " . ($reg_no ? $reg_no['alm_wb_camp_msg_cus_reg_no'] : 'N/A') . "\n\n"
                                        . "Please take note. ✅";
                                    $messageData = array(
                                        "messaging_product" => "whatsapp",
                                        "recipient_type" => "individual",
                                        "to" => "+918138055705",
                                        "type" => "text",
                                        "text" => array(
                                            "body" => $alertMessage
                                        )
                                    );

                                    $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                                } else if ($message['button']['text'] == 'Remind Me Later') {
                                    // $this->oneYearRemindMeLater($contact, $msg_customer);
                                    // Calculate the reminder date
                                    $wb_cus_remind_date = date('Y-m-d', strtotime("+30 days"));
                                    $messageData = array(
                                        "messaging_product" => "whatsapp",
                                        "recipient_type" => "individual",
                                        "to" => $contact['wa_id'],
                                        "type" => "text",
                                        "text" => array(
                                            "body" => "⏳ We will remind you in *🕰️ 30 days (" . $wb_cus_remind_date . ").* Please stay tuned! 📲"
                                        )
                                    );
                                    $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                                    if (isset($returnMsg->messages)) {
                                        if ($returnMsg->messages[0]->id != "") {

                                            $message_data = [
                                                'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                                                'alm_wb_msg_source' => 2,
                                                'alm_wb_msg_type' => 4,
                                                'alm_wb_msg_content' => $messageData['text']['body'],
                                                'alm_wb_msg_status' => 1,
                                                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                                'alm_wb_msg_reply_id' => '',
                                                'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                                                'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                                                'alm_wb_msg_staff_id' => 1,
                                            ];
                                            $result = $wb_message->insert($message_data);
                                            $data = [
                                                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                                'message_type' => 4,
                                                'message_source' => 2,
                                                'message' => $messageData['text']['body'],
                                                'time' => date('Y-m-d H:i:s'),
                                                'toUserId' => 1,
                                                'fromUserId' => $msg_customer
                                            ];
                                            $this->sendSocketMessage($data);
                                            $mobile = $msg_customer['wb_cus_mobile'];
                                            $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                                            if ($currentFollowUp) {
                                                $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                                                if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                                                    $followUpCount++;
                                                } else if ($followUpCount == 3) {
                                                    $followUpCount = 6;
                                                } else if ($followUpCount == 1) {
                                                    $followUpCount = 2;
                                                }
                                                $tracker_data = [
                                                    'wb_cus_follow_up' => $followUpCount,
                                                    'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                                                    'wb_cus_remind_date' => $wb_cus_remind_date,
                                                    'wb_cus_remind_flag' => 1,  // Need To Remind
                                                    'wb_cus_assigned' => 19
                                                ];
                                                $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                                            }
                                        }
                                    }
                                } else if ($message['button']['text'] == 'Skip For This Time') {
                                    $this->notInterestedOption($contact, $msg_customer);
                                }
                                $tracker_reminder_data = [
                                    'wb_cus_id ' => $msg_customer['wb_cus_id'],
                                    'wb_cus_reminder' => 4,  //Second Service Reminder
                                ];
                                $wb_customer->where('wb_cus_id', $msg_customer['wb_cus_id'])->set($tracker_reminder_data)->update();
                            } else if (in_array($replyId, $eight_month_sr_ids)) {
                                if ($message['button']['text'] == 'Book An Appointment') {
                                    $this->pickupAndDropoff($contact, $msg_customer);
                                    $contact_last_9 = substr($contact['wa_id'], -9);
                                    $reg_no = $wb_camp_message
                                        ->where("RIGHT(alm_wb_camp_msg_cus_phone, 9)", $contact_last_9)
                                        ->select('alm_wb_camp_msg_cus_reg_no')
                                        ->orderBy('alm_wb_camp_msg_id', 'DESC')
                                        ->first();

                                    $alertMessage = "🔔 Appointment Selection Alert\n\n"
                                        . "Hello,\n\n"
                                        . "Customer Number [ " . $contact['wa_id'] . " ] has selected the *Book an Appointment* option.\n\n"
                                        . "🚗 Reg No: " . ($reg_no ? $reg_no['alm_wb_camp_msg_cus_reg_no'] : 'N/A') . "\n\n"
                                        . "Please take note. ✅";
                                    $messageData = array(
                                        "messaging_product" => "whatsapp",
                                        "recipient_type" => "individual",
                                        "to" => "+918138055705",
                                        "type" => "text",
                                        "text" => array(
                                            "body" => $alertMessage
                                        )
                                    );

                                    $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                                } else if ($message['button']['text'] == 'Remind Me Later') {
                                    // $this->remindMeLater($contact, $msg_customer);
                                    // Calculate the reminder date
                                    $wb_cus_remind_date = date('Y-m-d', strtotime("+30 days"));
                                    $messageData = array(
                                        "messaging_product" => "whatsapp",
                                        "recipient_type" => "individual",
                                        "to" => $contact['wa_id'],
                                        "type" => "text",
                                        "text" => array(
                                            "body" => "⏳ We will remind you in *🕰️ 30 days (" . $wb_cus_remind_date . ").* Please stay tuned! 📲"
                                        )
                                    );
                                    $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                                    if (isset($returnMsg->messages)) {
                                        if ($returnMsg->messages[0]->id != "") {

                                            $message_data = [
                                                'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                                                'alm_wb_msg_source' => 2,
                                                'alm_wb_msg_type' => 4,
                                                'alm_wb_msg_content' => $messageData['text']['body'],
                                                'alm_wb_msg_status' => 1,
                                                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                                'alm_wb_msg_reply_id' => '',
                                                'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                                                'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                                                'alm_wb_msg_staff_id' => 1,
                                            ];
                                            $result = $wb_message->insert($message_data);
                                            $data = [
                                                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                                'message_type' => 4,
                                                'message_source' => 2,
                                                'message' => $messageData['text']['body'],
                                                'time' => date('Y-m-d H:i:s'),
                                                'toUserId' => 1,
                                                'fromUserId' => $msg_customer
                                            ];
                                            $this->sendSocketMessage($data);
                                            $mobile = $msg_customer['wb_cus_mobile'];
                                            $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                                            if ($currentFollowUp) {
                                                $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                                                if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                                                    $followUpCount++;
                                                } else if ($followUpCount == 3) {
                                                    $followUpCount = 6;
                                                } else if ($followUpCount == 1) {
                                                    $followUpCount = 2;
                                                }
                                                $tracker_data = [
                                                    'wb_cus_follow_up' => $followUpCount,
                                                    'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                                                    'wb_cus_remind_date' => $wb_cus_remind_date,
                                                    'wb_cus_remind_flag' => 1,  // Need To Remind
                                                    'wb_cus_assigned' => 19
                                                ];
                                                $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                                            }
                                        }
                                    }
                                } else if ($message['button']['text'] == 'Skip For This Time') {
                                    $this->notInterestedOption($contact, $msg_customer);
                                }
                                $tracker_reminder_data = [
                                    'wb_cus_id ' => $msg_customer['wb_cus_id'],
                                    'wb_cus_reminder' => 3,  //First Service Reminder
                                ];
                                $wb_customer->where('wb_cus_id', $msg_customer['wb_cus_id'])->set($tracker_reminder_data)->update();
                            }
                        } else if ($message['type'] == 'image') {
                            $media_url = $common->downloadWhatsappMedia($message['image']['id'], 5);
                            log_message('error', 'Webhook Error: ' .  $media_url);
                            $message_data = [
                                'alm_wb_msg_master_id' => $message['id'],
                                'alm_wb_msg_source' => 1,
                                'alm_wb_msg_type' => 5,
                                'alm_wb_msg_caption' => isset($message['image']['caption']) ? $message['image']['caption'] : '',
                                'alm_wb_msg_content' => $media_url,
                                'alm_wb_msg_status' => 2,
                                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                'alm_wb_msg_reply_id' => '',
                                'alm_wb_msg_created_on' => $formattedDate,
                                'alm_wb_msg_updated_on' => $formattedDate,
                            ];
                            $wb_message->insert($message_data);
                            $data = [
                                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                'message_type' => 5,
                                'message' => $media_url,
                                'message_source' => 1,
                                'time' => date('Y-m-d H:i:s'),
                                'toUserId' => 0,
                                'fromUserId' => $msg_customer
                            ];
                            $this->sendSocketMessage($data);
                            // $this->sendWelcomeMessage($msg_customer);
                        } else if ($message['type'] == 'audio') {
                            $media_url = $common->downloadWhatsappMedia($message['audio']['id'], 1);
                            $message_data = [
                                'alm_wb_msg_master_id' => $message['id'],
                                'alm_wb_msg_source' => 1,
                                'alm_wb_msg_type' => 1,
                                'alm_wb_msg_content' => $media_url,
                                'alm_wb_msg_status' => 2,
                                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                'alm_wb_msg_reply_id' => '',
                                'alm_wb_msg_created_on' => $formattedDate,
                                'alm_wb_msg_updated_on' => $formattedDate,
                            ];
                            $wb_message->insert($message_data);
                            $data = [
                                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                'message_type' => 1,
                                'message' => $media_url,
                                'message_source' => 1,
                                'time' => date('Y-m-d H:i:s'),
                                'toUserId' => 0,
                                'fromUserId' => $msg_customer
                            ];
                            $this->sendSocketMessage($data);
                            // $this->sendWelcomeMessage($msg_customer);
                        } else if ($message['type'] == 'video') {
                            $media_url = $common->downloadWhatsappMedia($message['video']['id'], 1);
                            $message_data = [
                                'alm_wb_msg_master_id' => $message['id'],
                                'alm_wb_msg_source' => 1,
                                'alm_wb_msg_type' => 11,
                                'alm_wb_msg_content' => $media_url,
                                'alm_wb_msg_caption' => isset($message['video']['caption']) ? $message['video']['caption'] : '',
                                'alm_wb_msg_status' => 2,
                                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                'alm_wb_msg_reply_id' => '',
                                'alm_wb_msg_created_on' => $formattedDate,
                                'alm_wb_msg_updated_on' => $formattedDate,
                            ];
                            $wb_message->insert($message_data);
                            $data = [
                                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                'message_type' => 11,
                                'message_source' => 1,
                                'message' => $media_url,
                                'time' => date('Y-m-d H:i:s'),
                                'toUserId' => 0,
                                'fromUserId' => $msg_customer
                            ];
                            $this->sendSocketMessage($data);
                            // $this->sendWelcomeMessage($msg_customer);
                        } else if ($message['type'] == 'sticker') {
                            $media_url = $common->downloadWhatsappMedia($message['sticker']['id'], 1);
                            $message_data = [
                                'alm_wb_msg_master_id' => $message['id'],
                                'alm_wb_msg_source' => 1,
                                'alm_wb_msg_type' => 8,
                                'alm_wb_msg_content' => $media_url,
                                'alm_wb_msg_status' => 2,
                                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                'alm_wb_msg_reply_id' => '',
                                'alm_wb_msg_created_on' => $formattedDate,
                                'alm_wb_msg_updated_on' => $formattedDate,
                            ];
                            $wb_message->insert($message_data);
                            $data = [
                                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                'message_type' => 8,
                                'message_source' => 1,
                                'message' => $media_url,
                                'time' => date('Y-m-d H:i:s'),
                                'toUserId' => 0,
                                'fromUserId' => $msg_customer
                            ];
                            $this->sendSocketMessage($data);
                            // $this->sendWelcomeMessage($msg_customer);
                        } else if ($message['type'] == 'location') {
                            $message_data = [
                                'alm_wb_msg_master_id' => $message['id'],
                                'alm_wb_msg_source' => 1,
                                'alm_wb_msg_type' => 12,
                                'alm_wb_msg_content' => json_encode(["lat" => $message['location']['latitude'], "lng" => $message['location']['longitude']]),
                                'alm_wb_msg_status' => 2,
                                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                'alm_wb_msg_reply_id' => '',
                                'alm_wb_msg_created_on' => $formattedDate,
                                'alm_wb_msg_updated_on' => $formattedDate,
                            ];
                            $wb_message->insert($message_data);
                            $data = [
                                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                'message_type' => 12,
                                'message_source' => 1,
                                'message' => json_encode(["lat" => $message['location']['latitude'], "lng" => $message['location']['longitude']]),
                                'time' => date('Y-m-d H:i:s'),
                                'toUserId' => 0,
                                'fromUserId' => $msg_customer
                            ];
                            $this->sendSocketMessage($data);
                            // $this->sendWelcomeMessage($msg_customer);

                            $eightMonthQuery = $this->db->table('alm_whatsapp_cus_messages')
                                ->select('alm_wb_msg_id, alm_wb_msg_content')
                                ->where('alm_wb_msg_customer', $msg_customer['wb_cus_id'])
                                ->where('alm_wb_msg_source', 1)
                                ->orderBy('alm_wb_msg_id', 'DESC')
                                ->limit(1, 2)
                                ->get();
                            $oneYearQuery = $this->db->table('alm_whatsapp_cus_messages')
                                ->select('alm_wb_msg_id, alm_wb_msg_content')
                                ->where('alm_wb_msg_customer', $msg_customer['wb_cus_id'])
                                ->where('alm_wb_msg_source', 1)
                                ->orderBy('alm_wb_msg_id', 'DESC')
                                ->limit(1, 2)
                                ->get();

                            $service_Reminder = $wb_customer->select('wb_cus_reminder')
                                ->where('wb_cus_id', $msg_customer['wb_cus_id'])
                                ->get()
                                ->getRow('wb_cus_reminder');

                            $secondLastMessage = $eightMonthQuery->getRow();
                            $thirdLastMessage = $oneYearQuery->getRow();

                            if (strpos($secondLastMessage->alm_wb_msg_content, 'Paid Pick & Drop') !== false && $service_Reminder != '5') {
                                $this->getCustomerAppointmentDate($contact, $msg_customer);
                            } elseif (strpos($thirdLastMessage->alm_wb_msg_content, 'Free Pick & Drop') !== false && $service_Reminder != '5') {
                                $this->isCustomerWithin50KM($contact, $msg_customer, json_encode([
                                    "lat" => $message['location']['latitude'],
                                    "lng" => $message['location']['longitude']
                                ]));
                            } else if ($service_Reminder == '5') {
                                $this->getCustomerAppointmentDate($contact, $msg_customer);
                            }
                        } else if ($message['type'] == "document") {
                            $media_url = $common->downloadWhatsappMedia($message['document']['id'], 3);
                            $message_data = [
                                'alm_wb_msg_master_id' => $message['id'],
                                'alm_wb_msg_source' => 1,
                                'alm_wb_msg_type' => 3,
                                'alm_wb_msg_content' => $media_url,
                                'alm_wb_msg_status' => 2,
                                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                'alm_wb_msg_reply_id' => '',
                                'alm_wb_msg_created_on' => $formattedDate,
                                'alm_wb_msg_updated_on' => $formattedDate,
                            ];
                            $wb_message->insert($message_data);
                            $data = [
                                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                'message_type' => 12,
                                'message_source' => 1,
                                'message' => $media_url,
                                'time' => date('Y-m-d H:i:s'),
                                'toUserId' => 0,
                                'fromUserId' => $msg_customer
                            ];
                            $this->sendSocketMessage($data);
                            // $this->sendWelcomeMessage($msg_customer);
                        } else if ($message['type'] == "interactive") {

                            if ($message['interactive']['type'] == 'button_reply') {
                                $messageBody = isset($message['interactive']['button_reply']['title'])
                                    ? $message['interactive']['button_reply']['title']
                                    : 'No message title';

                                $message_data = [
                                    'alm_wb_msg_master_id' => $message['id'],
                                    'alm_wb_msg_source' => 1,
                                    'alm_wb_msg_type' => 4,
                                    'alm_wb_msg_content' => $message['interactive']['button_reply']['title'],
                                    'alm_wb_msg_status' => 2,
                                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                    'alm_wb_msg_reply_id' => '',
                                    'alm_wb_msg_created_on' => $formattedDate,
                                    'alm_wb_msg_updated_on' => $formattedDate,
                                ];
                                $wb_message->insert($message_data);
                                $data = [
                                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                    'message_type' => 4,
                                    'message_source' => 1,
                                    'message' => $message['interactive']['button_reply']['title'],
                                    'time' => date('Y-m-d H:i:s'),
                                    'toUserId' => 0,
                                    'fromUserId' => $msg_customer
                                ];
                                $this->sendSocketMessage($data);

                                if ($message['interactive']['button_reply']['id'] == 'Paid_Pick_&_Drop') {

                                    log_message('error',  "I am here in line 730" . json_encode($msg_customer));

                                    $tracker_pick_drop_data = [
                                        'wb_cus_id ' => $msg_customer['wb_cus_id'],
                                        'wb_cus_pick_drop' => 5,  //5- Paid Pick & Drop
                                    ];

                                    $wb_customer->where('wb_cus_id', $msg_customer['wb_cus_id'])->set($tracker_pick_drop_data)->update();
                                    log_message('error',  "I am here in line 737" . json_encode($msg_customer));


                                    $this->driverOrRecoveryPickUp($contact, $msg_customer);

                                    $pickDropOption = $message['interactive']['button_reply']['title'];

                                    $contact_last_9 = substr($contact['wa_id'], -9);

                                    $reg_no = $wb_camp_message
                                        ->where("RIGHT(alm_wb_camp_msg_cus_phone, 9)", $contact_last_9)
                                        ->select('alm_wb_camp_msg_cus_reg_no')
                                        ->orderBy('alm_wb_camp_msg_id', 'DESC')
                                        ->first();

                                    $alertMessage = "🚗 Pick & Drop-Off Alert\n\n"
                                        . "Hello,\n\n"
                                        . "Customer Number [ " . $contact['wa_id'] . " ] has selected *" . $pickDropOption . "* for their appointment.\n\n"
                                        . "🚗 Reg No: " . ($reg_no ? $reg_no['alm_wb_camp_msg_cus_reg_no'] : 'N/A') . "\n\n"
                                        . "Please take note. ✅";

                                    $messageData = array(
                                        "messaging_product" => "whatsapp",
                                        "recipient_type" => "individual",
                                        "to" => "+918138055705",
                                        "type" => "text",
                                        "text" => array(
                                            "body" =>  $alertMessage
                                        )
                                    );

                                    $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                                } else if ($message['interactive']['button_reply']['id'] == 'Drop-off') {
                                    $tracker_pick_drop_data = [
                                        'wb_cus_id ' => $msg_customer['wb_cus_id'],
                                        'wb_cus_pick_drop' => 8,  //8- Drop Off (Self)
                                        'wb_cus_pickup_mode' => 0,
                                    ];
                                    $wb_customer->where('wb_cus_id', $msg_customer['wb_cus_id'])->set($tracker_pick_drop_data)->update();
                                    // get customer appointment dates flow
                                    $this->getCustomerAppointmentDate($contact, $msg_customer);
                                    $pickDropOption = $message['interactive']['button_reply']['title'];

                                    $contact_last_9 = substr($contact['wa_id'], -9);
                                    $reg_no = $wb_camp_message
                                        ->where("RIGHT(alm_wb_camp_msg_cus_phone, 9)", $contact_last_9)
                                        ->select('alm_wb_camp_msg_cus_reg_no')
                                        ->orderBy('alm_wb_camp_msg_id', 'DESC')
                                        ->first();

                                    $alertMessage = "🚗 Pick & Drop-Off Alert\n\n"
                                        . "Hello,\n\n"
                                        . "Customer Number [ " . $contact['wa_id'] . " ] has selected *" . $pickDropOption . "* for their appointment.\n\n"
                                        . "🚗 Reg No: " . ($reg_no ? $reg_no['alm_wb_camp_msg_cus_reg_no'] : 'N/A') . "\n\n"
                                        . "Please take note. ✅";

                                    $messageData = array(
                                        "messaging_product" => "whatsapp",
                                        "recipient_type" => "individual",
                                        "to" => "+918138055705",
                                        "type" => "text",
                                        "text" => array(
                                            "body" =>  $alertMessage
                                        )
                                    );

                                    $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                                } else if ($message['interactive']['button_reply']['id'] == 'driver' || $message['interactive']['button_reply']['id'] == 'recovery') {

                                    $pickup_mode = ($message['interactive']['button_reply']['id'] == 'driver') ? 1 : 2;
                                    $tracker_pick_drop_data = [
                                        'wb_cus_id' => $msg_customer['wb_cus_id'],
                                        'wb_cus_pickup_mode' => $pickup_mode,
                                    ];

                                    $wb_customer->where('wb_cus_id', $msg_customer['wb_cus_id'])
                                        ->set($tracker_pick_drop_data)
                                        ->update();
                                    // driver and recovery Pick-up Flow
                                    $this->getCustomerLocation($contact, $msg_customer);
                                } else if ($message['interactive']['button_reply']['id'] == 'yes_escalate') {
                                    $this->transferToDissatisfiedModule($contact, $msg_customer);
                                } else if ($message['interactive']['button_reply']['id'] == 'no_escalate') {
                                    $this->sendThankYouMessage($contact, $msg_customer);
                                } else if (
                                    $message['interactive']['button_reply']['id'] == 'one_Free_Pick_&_Drop' ||
                                    $message['interactive']['button_reply']['id'] == 'Free_Pick_&_Drop'
                                ) {
                                    $tracker_pick_drop_data = [
                                        'wb_cus_id' => $msg_customer['wb_cus_id'], // Fixed extra space issue
                                        'wb_cus_pick_drop' => 1,  // 1 - Free Pick & Drop
                                    ];

                                    $wb_customer->where('wb_cus_id', $msg_customer['wb_cus_id'])
                                        ->set($tracker_pick_drop_data)
                                        ->update();

                                    $this->driverPickUp($contact, $msg_customer);
                                    $pickDropOption = $message['interactive']['button_reply']['title'];

                                    $contact_last_9 = substr($contact['wa_id'], -9);
                                    $reg_no = $wb_camp_message
                                        ->where("RIGHT(alm_wb_camp_msg_cus_phone, 9)", $contact_last_9)
                                        ->select('alm_wb_camp_msg_cus_reg_no')
                                        ->orderBy('alm_wb_camp_msg_id', 'DESC')
                                        ->first();

                                    $alertMessage = "🚗 Pick & Drop-Off Alert\n\n"
                                        . "Hello,\n\n"
                                        . "Customer Number [ " . $contact['wa_id'] . " ] has selected *" . $pickDropOption . "* for their appointment.\n\n"
                                        . "🚗 Reg No: " . ($reg_no ? $reg_no['alm_wb_camp_msg_cus_reg_no'] : 'N/A') . "\n\n"
                                        . "Please take note. ✅";

                                    $messageData = array(
                                        "messaging_product" => "whatsapp",
                                        "recipient_type" => "individual",
                                        "to" => "+918138055705",
                                        "type" => "text",
                                        "text" => array(
                                            "body" =>  $alertMessage
                                        )
                                    );

                                    $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                                } else if ($message['interactive']['button_reply']['id'] == 'yes_paid') {
                                    $tracker_pick_drop_data = [
                                        'wb_cus_id ' => $msg_customer['wb_cus_id'],
                                        'wb_cus_pick_drop' => 5,  //5- Paid Pick & Drop
                                    ];
                                    $wb_customer->where('wb_cus_id', $msg_customer['wb_cus_id'])->set($tracker_pick_drop_data)->update();
                                    $this->getCustomerAppointmentDate($contact, $msg_customer);
                                } else if ($message['interactive']['button_reply']['id'] == 'no_cancel') {
                                    $this->sendThankYouMessage($contact, $msg_customer);
                                } else if ($message['interactive']['button_reply']['id'] == 'Vehicle-Sold') {
                                    $this->sendThankYouMessage($contact, $msg_customer);
                                } else if ($message['interactive']['button_reply']['id'] == 'Disappointed') {
                                    $this->notInterestedCustomers($contact, $msg_customer);
                                } else if ($message['interactive']['button_reply']['id'] == 'others-not-interest') {
                                    $this->notInterestedCustomers($contact, $msg_customer);
                                } else if ($message['interactive']['button_reply']['id'] == 'Book_An_Appointment') {
                                    $this->pickupAndDropoff($contact, $msg_customer);
                                    $alertMessage = "🔔 Appointment Selection Alert\n\nHello,\n\nCustomer Number [ " . $contact['wa_id'] . " ] has selected the *Book an Appointment* option.\n\nPlease take note. ✅";

                                    $messageData = array(
                                        "messaging_product" => "whatsapp",
                                        "recipient_type" => "individual",
                                        "to" => "+918138055705",
                                        "type" => "text",
                                        "text" => array(
                                            "body" => $alertMessage
                                        )
                                    );

                                    $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                                    $tracker_reminder_data = [
                                        'wb_cus_id ' => $msg_customer['wb_cus_id'],
                                        'wb_cus_reminder' => 6,  //Non Working Hours Appointment
                                    ];
                                    $wb_customer->where('wb_cus_id', $msg_customer['wb_cus_id'])->set($tracker_reminder_data)->update();
                                } else if ($message['interactive']['button_reply']['id'] == 'chat_with_us') {
                                    $awayData = [
                                        "alm_wb_msg_source"   => "2",
                                        "alm_wb_msg_staff_id" => "18",
                                        "alm_wb_msg_type"     => "4",
                                        "alm_wb_msg_content" => "We are currently unavailable but will get back to you during our working hours.\n
Working Hours:\n
Monday to Thursday & Saturday:\n⏰ *8:30 AM to 7:00 PM*\n
Friday:\n⏰ *8:30 AM to 12:30 PM*\n⏰ *2:30 PM to 7:00 PM*",
                                        "alm_wb_msg_status"   => 1,
                                        "alm_wb_msg_customer" => $msg_customer['wb_cus_id'],
                                        "alm_wb_msg_mobile"   => $contact['wa_id'],
                                    ];

                                    $awayMessaged = $wb_message->where('alm_wb_msg_customer', $wb_cus_id)
                                        ->like('alm_wb_msg_content', "We are currently unavailable")
                                        ->where('alm_wb_msg_created_on >=', $timeAgo)
                                        ->orderBy('alm_wb_msg_id', 'DESC')
                                        ->first();

                                    if ($awayMessaged) {
                                        $this->sendAwayMessageToCustomer($awayData);
                                    }
                                }
                            } else if ($message['interactive']['type'] == 'list_reply') {
                                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $message['interactive']['list_reply']['id'])) {
                                    // log_message('error',  "Ready to create an appointment");
                                    $message_data = [
                                        'alm_wb_msg_master_id' => $message['id'],
                                        'alm_wb_msg_source' => 1,
                                        'alm_wb_msg_type' => 4,
                                        'alm_wb_msg_content' => $message['interactive']['list_reply']['title'],
                                        'alm_wb_msg_status' => 2,
                                        'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                        'alm_wb_msg_reply_id' => '',
                                        'alm_wb_msg_created_on' => $formattedDate,
                                        'alm_wb_msg_updated_on' => $formattedDate,
                                    ];
                                    $wb_message->insert($message_data);
                                    $data = [
                                        'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                        'message_type' => 4,
                                        'message_source' => 1,
                                        'message' => $message['interactive']['list_reply']['title'],
                                        'time' => date('Y-m-d H:i:s'),
                                        'toUserId' => 0,
                                        'fromUserId' => $msg_customer
                                    ];
                                    $this->sendSocketMessage($data);
                                    $appdate = $message['interactive']['list_reply']['id'];
                                    $this->getCustomerAppointmentTimeSlots($contact, $msg_customer,  $appdate);
                                    // $this->createAutoAppointment($contact, $msg_customer,  $appdate);
                                } else if (preg_match('/^\d{1,2}(am|pm)-\d{1,2}(am|pm)$/', $message['interactive']['list_reply']['id'])) {
                                    $apptdatearr = $this->db->table('alm_whatsapp_cus_messages')
                                        ->select('alm_wb_msg_id, alm_wb_msg_content')
                                        ->where('alm_wb_msg_customer', $msg_customer['wb_cus_id'])
                                        ->where('alm_wb_msg_source', 1)
                                        ->orderBy('alm_wb_msg_id', 'DESC')
                                        ->limit(1)
                                        ->get()
                                        ->getRow();

                                    // log_message('error',  "I am msg_customer['wb_cus_id']   581 >>>>>" . json_encode($msg_customer['wb_cus_id']));
                                    // log_message('error',  "I am apptdate->alm_wb_msg_content   581 >>>>>" . json_encode($apptdatearr->alm_wb_msg_content));


                                    // log_message('error',  "Ready to create an appointment");
                                    $message_data = [
                                        'alm_wb_msg_master_id' => $message['id'],
                                        'alm_wb_msg_source' => 1,
                                        'alm_wb_msg_type' => 4,
                                        'alm_wb_msg_content' => $message['interactive']['list_reply']['title'],
                                        'alm_wb_msg_status' => 2,
                                        'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                        'alm_wb_msg_reply_id' => '',
                                        'alm_wb_msg_created_on' => $formattedDate,
                                        'alm_wb_msg_updated_on' => $formattedDate,
                                    ];
                                    $wb_message->insert($message_data);
                                    $data = [
                                        'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                        'message_type' => 4,
                                        'message_source' => 1,
                                        'message' => $message['interactive']['list_reply']['title'],
                                        'time' => date('Y-m-d H:i:s'),
                                        'toUserId' => 0,
                                        'fromUserId' => $msg_customer
                                    ];
                                    $this->sendSocketMessage($data);
                                    if (isset($apptdatearr->alm_wb_msg_content)) {
                                        preg_match('/\d{4}-\d{2}-\d{2}/', $apptdatearr->alm_wb_msg_content, $matches);
                                        $dateOnly = $matches[0] ?? null;
                                        $apptTime = $message['interactive']['list_reply']['id'];
                                        $this->createAutoAppointment($contact, $msg_customer, $dateOnly, $apptTime);
                                    } else {
                                    }
                                } else if ($message['interactive']['list_reply']['title'] == 'Other') {
                                    $message_data = [
                                        'alm_wb_msg_master_id' => $message['id'],
                                        'alm_wb_msg_source' => 1,
                                        'alm_wb_msg_type' => 4,
                                        'alm_wb_msg_content' => 'Other',
                                        'alm_wb_msg_status' => 2,
                                        'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                        'alm_wb_msg_reply_id' => '',
                                        'alm_wb_msg_created_on' => $formattedDate,
                                        'alm_wb_msg_updated_on' => $formattedDate,
                                    ];
                                    $wb_message->insert($message_data);
                                    $data = [
                                        'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                        'message_type' => 4,
                                        'message_source' => 1,
                                        'message' => 'Other',
                                        'time' => date('Y-m-d H:i:s'),
                                        'toUserId' => 0,
                                        'fromUserId' => $msg_customer
                                    ];
                                    $this->sendSocketMessage($data);
                                    $messageData = array(
                                        "messaging_product" => "whatsapp",
                                        "recipient_type" => "individual",
                                        "to" => $contact['wa_id'],
                                        "type" => "text",
                                        "text" => array(
                                            "body" => "📞 Our executive will contact you soon. ⏳ Please stay tuned!"
                                        )
                                    );
                                    $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                                    if (isset($returnMsg->messages)) {
                                        if ($returnMsg->messages[0]->id != "") {

                                            $message_data = [
                                                'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                                                'alm_wb_msg_source' => 2,
                                                'alm_wb_msg_type' => 4,
                                                'alm_wb_msg_content' => $messageData['text']['body'],
                                                'alm_wb_msg_status' => 1,
                                                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                                                'alm_wb_msg_reply_id' => '',
                                                'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                                                'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                                                'alm_wb_msg_staff_id' => 1,
                                            ];
                                            $result = $wb_message->insert($message_data);
                                            $data = [
                                                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                                                'message_type' => 4,
                                                'message_source' => 2,
                                                'message' => $messageData['text']['body'],
                                                'time' => date('Y-m-d H:i:s'),
                                                'toUserId' => 1,
                                                'fromUserId' => $msg_customer
                                            ];
                                            $this->sendSocketMessage($data);
                                            $mobile = $msg_customer['wb_cus_mobile'];
                                            $tracker_data = [
                                                'wb_cus_remind_flag' => 2,  //Appointment Assistance
                                                'wb_cus_updated_on' => date('Y-m-d H-i-s'),
                                                'wb_cus_assigned' => 19
                                            ];
                                            $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                                            $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                                            if ($currentFollowUp) {
                                                $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                                                if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                                                    $followUpCount++;
                                                } else if ($followUpCount == 3) {
                                                    $followUpCount = 6;
                                                } else if ($followUpCount == 1) {
                                                    $followUpCount = 2;
                                                }
                                                $tracker_data = [
                                                    'wb_cus_follow_up' => $followUpCount,
                                                    'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                                                ];
                                                $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if (sizeof($lead_list) == 0) {
                            if ($last_lead) {
                                $builder = $this->db->table('sequence_data');
                                $builder->selectMax('whatsapp_lead_reopen_hours');
                                $query = $builder->get();
                                $row = $query->getRow();
                                // Retrieve the reopen hours and lead created date
                                $reOpenHours = $row->whatsapp_lead_reopen_hours;
                                $reOpenHours = (int)$reOpenHours;
                                $lead_creted_date = $last_lead['lead_creted_date'];
                                // Convert lead created date to a DateTime object
                                $leadCreatedDateTime = new DateTime($lead_creted_date);
                                $currentDateTime = new DateTime();
                                // Calculate the difference in hours
                                $interval = $leadCreatedDateTime->diff($currentDateTime);
                                log_message('error',  "currentDateTime" . json_encode($currentDateTime));
                                log_message('error',  "leadCreatedDateTime" . json_encode($leadCreatedDateTime));
                                $hoursDifference = ($interval->days * 24) + $interval->h;
                                log_message('error',  "hoursDifference" . json_encode($hoursDifference));
                                log_message('error',  "reOpenHours" . json_encode($reOpenHours));
                            }

                            // Get the current date and time

                            $leadData = [];
                            $this->db->transStart();
                            $phone = $msg_customer['wb_cus_mobile'];

                            $source_type = 9;
                            $social_source = 0;
                            $social_camp_id = 0;
                            if (isset($message['referral'])) {
                                if ($message['referral']['source_type'] == 'ad') {
                                    $campaign_data = $common->getAdCampaignId($message['referral']['source_id']);
                                    $socialMediaCampaign = new SocialMediaCampaignModel();
                                    $current_camp = $socialMediaCampaign->where('smc_ad_id', $campaign_data->campaign_id)->where('smc_status', 0)->first();
                                    if (isset($current_camp)) {
                                        $source_type = 8;
                                        $social_source = 3;
                                        $social_camp_id = $current_camp['smc_id'];
                                    }
                                }
                            }


                            if ($last_lead && $hoursDifference < $reOpenHours && $last_lead['purpose_id'] == '10') {
                                log_message('error',  "Leads Can be reopened---- last Lead details" . json_encode($last_lead));
                                $lead_code = $last_lead['lead_code'];
                                $lead_id = $last_lead['lead_id'];
                                $lead_source = $last_lead['source_id'];
                                $leadData = [
                                    'lead_id' => $lead_id,
                                    'lead_code' => $lead_code,
                                    'lead_note' => $message['type'] == 'text' ? $message['text']['body'] : "New lead created from whatsapp enquiry",
                                    'lang_id' => 1,
                                    'purpose_id' => 10,
                                    'register_number' => '',
                                    'vehicle_model' => '',
                                    'source_id' => $source_type,
                                    'lead_social_media_source' =>  $social_source,
                                    'lead_social_media_mapping' => $social_camp_id,
                                    'lead_createdby' => 1,
                                    'lead_createdon' => date("Y-m-d H:i:s.u"),
                                    'lead_creted_date' => date("Y-m-d H:i:s.u"),
                                    'lead_updatedon' => date("Y-m-d H:i:s.u"),
                                    'status_id' => 8,
                                ];
                                $lead_update = $lmodel->where('lead_id', $lead_id)->set($leadData)->update();

                                $leadactivitydata = [
                                    'lac_activity' => 'Lead Reopened ' . $lead_code . ' from whatsapp',
                                    'lac_activity_by' => 1,
                                    'lac_lead_id' => $lead_id,
                                    'lac_lead_purpose' => 10,
                                    'lac_lead_source' => $lead_source,
                                ];
                                $leadAcModel = new LeadActivityModel();
                                $leadactivity = $leadAcModel->insert($leadactivitydata);
                            } else {
                                log_message('error',  "Leads Can't be reopened" . json_encode($last_lead));
                                $builder = $this->db->table('sequence_data');
                                $builder->selectMax('current_seq');
                                $query = $builder->get();
                                $row = $query->getRow();
                                $lead_code = $row->current_seq;
                                $leadSeqvalfinal = $row->current_seq;
                                if (strlen($row->current_seq) == 1) {
                                    $lead_code = "ALMLD-000" . $row->current_seq;
                                } else if (strlen($row->current_seq) == 2) {
                                    $lead_code = "ALMLD-00" . $row->current_seq;
                                } else if (strlen($row->current_seq) == 3) {
                                    $lead_code = "ALMLD-0" . $row->current_seq;
                                } else {
                                    $lead_code = "ALMLD-" . $row->current_seq;
                                }

                                $leadData = [
                                    'lead_code' => $lead_code,
                                    'lead_note' => $message['type'] == 'text' ? $message['text']['body'] : "New lead created from whatsapp enquiry",
                                    'lang_id' => 1,
                                    'purpose_id' => 10,
                                    'register_number' => '',
                                    'vehicle_model' => '',
                                    'source_id' => $source_type,
                                    'lead_social_media_source' =>  $social_source,
                                    'lead_social_media_mapping' => $social_camp_id,
                                    'lead_createdby' => 1,
                                    'lead_createdon' => date("Y-m-d H:i:s.u"),
                                    'lead_creted_date' => date("Y-m-d H:i:s.u"),
                                    'lead_updatedon' => date("Y-m-d H:i:s.u"),
                                    'status_id' => 8,
                                ];
                                $cust_mastr_model = new CustomerMasterModel();
                                $resC = $cust_mastr_model->where('cust_phone', $phone)->first();
                                if ($resC) {
                                    $cust_id = $resC['cus_id'];
                                    $custId = [
                                        'cus_id' =>  $cust_id,
                                        'name' => $resC['cust_name'],
                                        'phone' => $resC['cust_phone'],
                                    ];
                                    $leadData = array_merge($leadData, $custId);
                                } else {
                                    $maraghi_cust_model = new MaragiCustomerModel();
                                    $maraghi_data = $maraghi_cust_model->where('phone', $phone)->join('customer_type', 'customer_type.cst_code = customer_type')->join('country_master', 'country_master.country_code = country')->select('customer_code,cst_id,customer_title,customer_name,addr1,city,country_master.id,phone')->first();
                                    if ($maraghi_data) {
                                        $custData = [
                                            'cust_type' => $maraghi_data['cst_id'],
                                            'cust_name' => $maraghi_data['customer_name'],
                                            'cust_salutation' => $maraghi_data['customer_title'],
                                            'cust_address' => $maraghi_data['addr1'],
                                            'cust_emirates' => $maraghi_data['city'],
                                            'cust_city' => $maraghi_data['city'],
                                            'cust_country' => $maraghi_data['id'],
                                            'cust_phone' =>  $maraghi_data['phone'],
                                            'cust_alternate_no' => $maraghi_data['phone'],
                                            'cust_alm_code' => $maraghi_data['customer_code'],
                                            'lead_createdby' => 1,
                                            'cust_created_on' => date("Y-m-d H:i:s.u"),
                                            'cust_source' => 0
                                        ];
                                        $ins_id = $cust_mastr_model->insert($custData);
                                        $custId = [
                                            'cus_id' =>  $ins_id,
                                            'name' => $maraghi_data['customer_name'],
                                            'phone' => $maraghi_data['phone'],
                                        ];
                                        $leadData = array_merge($leadData, $custId);
                                    } else {
                                        $custData = [
                                            'cust_name' => $contact['profile']['name'] ? $contact['profile']['name'] : 'Nil',
                                            'cust_phone' => $phone,
                                            'cust_alternate_no' => '',
                                            'cust_source' => $source_type
                                        ];
                                        $ins_id = $cust_mastr_model->insert($custData);
                                        $custId = [
                                            'cus_id' =>  $ins_id,
                                            'name' => $contact['profile']['name'] ? $contact['profile']['name'] : 'Nil',
                                            'phone' => $phone,
                                        ];
                                        $leadData = array_merge($leadData, $custId);
                                    }
                                }
                                $lead_id = $lmodel->insert($leadData);
                                $leadactivitydata = [
                                    'lac_activity' => 'Created Lead ' . $lead_code . ' from whatsapp',
                                    'lac_activity_by' => 1,
                                    'lac_lead_id' => $lead_id,
                                    'lac_lead_purpose' => 10,
                                    'lac_lead_source' => 9,
                                ];
                                $leadAcModel = new LeadActivityModel();
                                $leadactivity = $leadAcModel->insert($leadactivitydata);
                                $builder = $this->db->table('sequence_data');
                                $builder->set('current_seq', ++$leadSeqvalfinal);
                                $builder->update();
                            }


                            if ($this->db->transStatus() === false) {
                                $this->db->transRollback();
                            } else {
                                $this->db->transCommit();
                            }
                        }

                        if ($messaged && !$isNonWorkingHours && $message['type'] != "interactive" && $message['type'] != "button" && $message['type'] != "location") {
                            // log_message('error', "The current time falls within ramdan time range. Sending message...");
                            $cacheKey = "away_msg_lock_" . $wb_cus_id;
                            // Check if the lock exists
                            if (!cache($cacheKey)) {
                                // Set a temporary lock for 5 seconds to prevent duplicates
                                cache()->save($cacheKey, true, 5);
                                // Send away message
                                $response = $this->SendAwayButtonMessage($contact, $msg_customer);
                            }
                        } else {
                            // log_message('error', "The current time is outside the specified range or message was already sent within the last hour.");
                        }
                    }
                } else {
                    return;
                }
            }
            if (isset($msg_status) && sizeof($msg_status) > 0) {
                $current_status = 1;
                $wb_message = new WhatsappCustomerMessageModel();
                if ($msg_status['status'] == 'sent') {
                    $current_status = 1;
                } else if ($msg_status['status'] == 'delivered') {
                    $current_status = 2;
                } else if ($msg_status['status'] == 'read') {
                    $current_status = 3;
                } else {
                    $current_status = 4;
                }

                $wb_message->set("alm_wb_msg_status", $current_status)->where("alm_wb_msg_master_id", $msg_status['id'])->update();
                return;
            }
        } catch (LogException $e) {
            log_message('error', 'Webhook Error: ' . $e->getMessage());
        }
    }

    public function sendWelcomeMessage($msg_customer)
    {
        if ($msg_customer['new_flag'] == true) {
            $wbData = [
                'messaging_product' => 'whatsapp',
                "to" => $msg_customer['wb_cus_mobile'], // Replace with recipient's phone number
                'type' => 'text',
                'text' => [
                    'body' => "Greetings team Al-Maraghi here..how can we assist you 😊"
                ]
            ];
            $common = new Common();
            $return = $common->sendCustomerWhatsappMessage($wbData, '971509766075');
            $microtime = microtime(true);

            // Extract seconds and milliseconds
            $seconds = floor($microtime);
            $milliseconds = round(($microtime - $seconds) * 1000);

            // Format the date with milliseconds
            $formattedDate = date('Y-m-d H:i:s', $seconds) . sprintf('.%03d', $milliseconds);
            $message_data = [
                'alm_wb_msg_master_id' => $return->messages[0]->id,
                'alm_wb_msg_source' => 2,
                'alm_wb_msg_type' => 4,
                'alm_wb_msg_content' => "Greetings team Al-Maraghi here..how can we assist you 😊",
                'alm_wb_msg_status' => 1,
                'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                'alm_wb_msg_reply_id' => '',
                'alm_wb_msg_created_on' => $formattedDate,
                'alm_wb_msg_updated_on' => $formattedDate,
            ];
            $wb_message = new WhatsappCustomerMessageModel();
            $wb_message->insert($message_data);
            $data = [
                'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                'message_type' => 4,
                'message_source' => 2,
                'message' => "Greetings team Al-Maraghi here..how can we assist you 😊",
                'time' => $formattedDate,
                'toUserId' => 1,
                'fromUserId' => $msg_customer
            ];
            $this->sendSocketMessage($data);
        }
    }

    public function sendSocketMessage($data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "http://localhost:3000/api/send_message"); // https://chatramsserver-production.up.railway.app/api/send_message
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded' // Change to form-urlencoded
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);

        // Convert data to form-urlencoded format
        $postData = http_build_query($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        // Handle potential SSL certificate issues (not recommended for production)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        log_message('error', 'Webhook Error: ' . json_encode($response));
    }

    public function getWhatsappCustomers()
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
            $wb_customer = new WhatsappCustomerMasterModel();

            $limit = $this->request->getVar('limit');
            $offset = $this->request->getVar('offset');
            $limit = (int) $limit;
            $offset = (int) $offset;
            $customer_list = $wb_customer->getCustomerWithLastMessageAndUnreadCount($offset, $limit);
            if ($customer_list) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'wb_customers' => $customer_list
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'wb_customers' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function getWhatsappCustomerMessages()
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
            $wb_cus_id = base64_decode(base64_decode($this->request->getVar('customerId')));
            $wb_customer = new WhatsappCustomerMasterModel();
            $curr_customer = $wb_customer->where("wb_cus_id", $wb_cus_id)->first();
            $wb_message = new WhatsappCustomerMessageModel();
            $wb_messages = $wb_message->select('alm_whatsapp_cus_messages.*,users.us_firstname')->where("alm_wb_msg_customer", $wb_cus_id)->join('users', 'users.us_id = alm_wb_msg_staff_id', 'left')->orderBy('alm_wb_msg_id', 'asc')->findAll();
            $status = [1, 7, 8];    //->orWhere('status_id', '7')
            $lmodel = new LeadModel();
            $lead_details = $lmodel->whereIn('status_id', $status)
                ->where('RIGHT(phone,9)',  substr($curr_customer['wb_cus_mobile'], -9))->join('appointment_master', 'appointment_master.apptm_lead_id =lead_id', 'left')->orderBy('lead_id', 'desc')->first();
            if ($wb_messages) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $wb_message->where("alm_wb_msg_customer", $wb_cus_id)->where("alm_wb_msg_source", 1)->set('alm_wb_msg_status', 3)->update();
                $response = [
                    'ret_data' => 'success',
                    'wb_customer_messages' => $wb_messages,
                    'wb_lead_details' => $lead_details
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'wb_customer_messages' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function sendMessageToCustomer()
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
            $wb_message = new WhatsappCustomerMessageModel();
            $wb_customer = new WhatsappCustomerMasterModel();
            $last_message = $wb_message->where("alm_wb_msg_customer", $this->request->getVar('alm_wb_msg_customer'))->where("alm_wb_msg_source", 1)->orderBy('alm_wb_msg_created_on', 'desc')->first();
            if ($last_message) {
                $last_time = $last_message['alm_wb_msg_created_on'];
                $dateTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $last_time);
                $timestamp = $dateTime->getTimestamp();
                $currentTime = time();
                $timeDifference = $currentTime - $timestamp;
            } else {
                $timeDifference = 86400;
            }

            if ($timeDifference > 86300) {
                $messageData = array(
                    "messaging_product" => "whatsapp",
                    "to" => $this->request->getVar("alm_wb_msg_mobile"),
                    "type" => "template",
                    "template" => array(
                        "name" => "session_expiry_message", // Replace with your template name
                        "language" => array(
                            "code" => "en" // Replace with the language code of your template
                        ),
                        "components" => array(
                            array(
                                "type" => "body",
                                "parameters" => array(
                                    array(
                                        "type" => "text",
                                        "text" => $this->request->getVar("alm_wb_msg_content") // Replace or add as per your template's placeholders
                                    ),
                                )
                            )
                        )
                    )
                );
            } else {
                $messageData = array(
                    "messaging_product" => "whatsapp",
                    "to" => $this->request->getVar("alm_wb_msg_mobile"),
                    "type" => "text",
                    'text' => [
                        'body' => $this->request->getVar('alm_wb_msg_content')
                    ]
                );
            }
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => $this->request->getVar('alm_wb_msg_type'),
                        'alm_wb_msg_content' => $this->request->getVar('alm_wb_msg_content'),
                        'alm_wb_msg_status' => $this->request->getVar('alm_wb_msg_status'),
                        'alm_wb_msg_customer' => $this->request->getVar('alm_wb_msg_customer'),
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    $mobile = $this->request->getVar("alm_wb_msg_mobile");
                    $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                    if ($currentFollowUp) {
                        $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                        if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                            $followUpCount++;
                        } else if ($followUpCount == 3) {
                            $followUpCount = 6;
                        } else if ($followUpCount == 1) {
                            $followUpCount = 2;
                        }
                        $tracker_data = [
                            'wb_cus_follow_up' => $followUpCount,
                            'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                        ];
                        $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                    }
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                    'data' => $last_message
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function  sendMessageWithMedia()
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
            $file = $this->request->getFile('media');
            $file_name =  $this->request->getVar('name');
            $file_type = $this->request->getVar('type');
            $file_mime_type = $file->getMimeType();
            if ($file != null) {
                $wb_message = new WhatsappCustomerMessageModel();
                $last_message = $wb_message->where("alm_wb_msg_customer", $this->request->getVar('customer_id'))->where("alm_wb_msg_source", 1)->orderBy('alm_wb_msg_created_on', 'desc')->first();
                if ($last_message) {
                    $last_time = $last_message['alm_wb_msg_created_on'];
                    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $last_time);
                    $timestamp = $dateTime->getTimestamp();
                    $currentTime = time();
                    $timeDifference = $currentTime - $timestamp;
                } else {
                    $timeDifference = 86400;
                }
                $image_url = $common->uploadImageToAws($file, $file_name, $file_mime_type);
                if ($timeDifference > 86300) {
                    $messageData = array(
                        "messaging_product" => "whatsapp",
                        "to" => $this->request->getVar("customer_number"),
                        "type" => "template",
                        "template" => array(
                            "name" => "session_expiry_media_message", // Replace with your template name
                            "language" => array(
                                "code" => "en" // Replace with the language code of your template
                            ),
                            "components" => array(
                                array(
                                    "type" => "header",
                                    "parameters" => array(
                                        array(
                                            'type' => 'image',
                                            'image' => [
                                                'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/' . $image_url, // Replace with the image URL// Optional: Add a caption to the image
                                            ]
                                        ),
                                    )
                                ),
                                array(
                                    "type" => "body",
                                    "parameters" => array(
                                        array(
                                            "type" => "text",
                                            "text" => $this->request->getVar("message") // Replace or add as per your template's placeholders
                                        ),
                                    )
                                )
                            )
                        )
                    );
                } else {
                    if ($file_type == 1) {
                        $messageData = array(
                            "messaging_product" => "whatsapp",
                            "to" => $this->request->getVar("customer_number"),
                            'type' => 'image',
                            'image' => [
                                'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/' . $image_url, // Replace with the image URL
                                'caption' => $this->request->getVar("message") // Optional: Add a caption to the image
                            ],
                        );
                    } else {
                        $messageData = array(
                            "messaging_product" => "whatsapp",
                            "to" => $this->request->getVar("customer_number"),
                            'type' => 'video',
                            'video' => [
                                'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/' . $image_url, // Replace with the image URL
                                'caption' => $this->request->getVar("message") // Optional: Add a caption to the image
                            ],
                        );
                    }
                }
                $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                if (isset($returnMsg->messages)) {
                    if ($returnMsg->messages[0]->id != "") {
                        log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                        $message_data = [
                            'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                            'alm_wb_msg_source' => 2,
                            'alm_wb_msg_type' => $file_type == 1 ? 5 : 11,
                            'alm_wb_msg_content' => $image_url,
                            'alm_wb_msg_caption' => $this->request->getVar("message"),
                            'alm_wb_msg_status' => 1,
                            'alm_wb_msg_customer' => $this->request->getVar('customer_id'),
                            'alm_wb_msg_reply_id' => '',
                            'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                            'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                            'alm_wb_msg_staff_id' => $tokendata['uid']
                        ];
                        $result = $wb_message->insert($message_data);
                        if ($result) {
                            //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                            $response = [
                                'ret_data' => 'success',
                            ];
                            return $this->respond($response, 200);
                        } else {
                            $response = [
                                'ret_data' => 'fail',
                            ];
                            return $this->respond($response, 200);
                        }
                    } else {
                        $response = [
                            'ret_data' => 'fail',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => $returnMsg,
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => 'fail no file',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }
    public function  sendWhatsappDocument()
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
            $file = $this->request->getFile('media');
            $file_name =  $this->request->getVar('name');
            $file_type = $this->request->getVar('type');
            $file_mime_type = $file->getMimeType();
            if ($file != null) {
                $wb_message = new WhatsappCustomerMessageModel();
                $last_message = $wb_message->where("alm_wb_msg_customer", $this->request->getVar('customer_id'))->where("alm_wb_msg_source", 1)->orderBy('alm_wb_msg_created_on', 'desc')->first();
                if ($last_message) {
                    $last_time = $last_message['alm_wb_msg_created_on'];
                    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $last_time);
                    $timestamp = $dateTime->getTimestamp();
                    $currentTime = time();
                    $timeDifference = $currentTime - $timestamp;
                } else {
                    $timeDifference = 86400;
                }
                $image_url = $common->uploadImageToAws($file, $file_name, $file_mime_type);
                if ($timeDifference > 86300) {

                    $messageData = array(

                        "messaging_product" => "whatsapp",

                        "to" => $this->request->getVar("customer_number"),

                        "type" => "template",

                        "template" => array(

                            "name" => "session_expiry_message_document", // Replace with your template name

                            "language" => array(

                                "code" => "en" // Replace with the language code of your template

                            ),

                            "components" => array(

                                array(

                                    "type" => "header",

                                    "parameters" => array(

                                        array(

                                            "type" => "document",

                                            'document' => [

                                                'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/' . $image_url,

                                            ]



                                        ),

                                    )

                                )

                            )

                        )

                    );
                } else {
                    $messageData = array(
                        "messaging_product" => "whatsapp",
                        "to" => $this->request->getVar("customer_number"),
                        'type' => 'document',
                        'document' => [
                            'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/' . $image_url,
                            'filename' => $file_name,
                        ]
                    );
                }
                $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                if (isset($returnMsg->messages)) {
                    if ($returnMsg->messages[0]->id != "") {
                        log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                        $message_data = [
                            'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                            'alm_wb_msg_source' => 2,
                            'alm_wb_msg_type' => 3,
                            'alm_wb_msg_content' => $image_url,
                            'alm_wb_msg_status' => 1,
                            'alm_wb_msg_customer' => $this->request->getVar('customer_id'),
                            'alm_wb_msg_reply_id' => '',
                            'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                            'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                            'alm_wb_msg_staff_id' => $tokendata['uid']
                        ];
                        $result = $wb_message->insert($message_data);
                        if ($result) {
                            //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                            $response = [
                                'ret_data' => 'success',
                            ];
                            return $this->respond($response, 200);
                        } else {
                            $response = [
                                'ret_data' => 'fail',
                            ];
                            return $this->respond($response, 200);
                        }
                    } else {
                        $response = [
                            'ret_data' => 'fail',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => $returnMsg,
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => 'fail no file',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function sendNewCustomerMessage()
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
            $wb_message = new WhatsappCustomerMessageModel();
            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => $this->request->getVar("country_code") . $this->request->getVar("mobile_number"),
                "type" => "template",
                "template" => array(
                    "name" => "customer_chat_start", // Replace with your template name
                    "language" => array(
                        "code" => "en" // Replace with the language code of your template
                    ),
                    "components" => array(
                        array(
                            "type" => "body",
                            "parameters" => array(
                                array(
                                    "type" => "text",
                                    "text" => $this->request->getVar("message") // Replace or add as per your template's placeholders
                                ),
                            )
                        )
                    )
                )
            );
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $wb_customer = new WhatsappCustomerMasterModel();
                    $wb_message = new WhatsappCustomerMessageModel();
                    $msg_customer = $wb_customer->where('wb_cus_mobile', $this->request->getVar("country_code") . $this->request->getVar("mobile_number"))->first();

                    if (!$msg_customer) {
                        $tracker_data = [
                            'wb_cus_name' => $this->request->getVar("customer_name") != "" ? $this->request->getVar("customer_name") : 'New Customer',
                            'wb_cus_mobile' => $this->request->getVar("country_code") . $this->request->getVar("mobile_number"),
                            'wb_cus_profile_pic' => ''
                        ];

                        $customer_id = $wb_customer->insert($tracker_data);
                    } else {
                        $customer_id = $msg_customer['wb_cus_id'];
                    }
                    log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_content' => "Hi sir Welcome To Al Maraghi Automotive We're excited to have you with us, " . $this->request->getVar("message"),
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => $customer_id,
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail1',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail2',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function blockContactFromWhatsapp()
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
            if (intval($this->request->getVar("wb_cus_id")) > 0) {
                $wb_customer = new WhatsappCustomerMasterModel();
                $wb_customer->set('wb_cus_block', true)->where('wb_cus_id', $this->request->getVar("wb_cus_id"))->update();
                if (intval($this->request->getVar("lead_status") == 8)) {
                    $leadModel = new LeadModel();
                    $leadModel->set('status_id', 6)->where('lead_id', $this->request->getVar("lead_id"))->update();
                }
                $response = [
                    'ret_data' => 'success',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function deleteCustomerMessage()
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
            $wb_customer = new WhatsappCustomerMasterModel();
            $return_data = $common->deleteMessage($this->request->getVar("alm_wb_msg_master_id"));
            $response = [
                'ret_data' => 'success',
                'data' => $return_data
            ];
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function sendAppointmentMessage()
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
            $date = strtotime($this->request->getVar("date"));
            $formattedDate = date('d/m/Y', $date);
            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => $this->request->getVar("cust_mobile"),
                "type" => "template",
                "template" => array(
                    "name" => "appointment_template", // Replace with your template name
                    "language" => array(
                        "code" => "en" // Replace with the language code of your template
                    ),
                    "components" => array(
                        array(
                            "type" => "header",
                            "parameters" => array(
                                array(
                                    'type' => 'image',
                                    'image' => [
                                        'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/common_use/playstore.png', // Replace with the image URL// Optional: Add a caption to the image
                                    ]
                                ),
                            )
                        ),
                        array(
                            "type" => "body",
                            "parameters" => array(
                                array(
                                    "type" => "text",
                                    "text" => $formattedDate // Replace or add as per your template's placeholders
                                ),
                                array(
                                    "type" => "text",
                                    "text" => $this->request->getVar("timeFrom") . ' TO ' . $this->request->getVar("timeTo") // Replace or add as per your template's placeholders
                                ),
                            )
                        )
                    )
                )
            );
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $wb_message = new WhatsappCustomerMessageModel();
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 5,
                        'alm_wb_msg_content' => 'common_use/playstore.png',
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_caption' => 'Greetings from Al Maraghi Auto Repairs. 
                        We wanted to confirm your upcoming appointment for your Mercedes-Benz service.
                        Here are the details: 
                        DATE: ' . $formattedDate . '
                        TIME: ' . $this->request->getVar("timeFrom") . ' TO ' . $this->request->getVar("timeTo") . '
                        location : https://maps.app.goo.gl/HBd9ZUbGmh9rtZUCA',
                        'alm_wb_msg_customer' => $this->request->getVar("cust_id"),
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail1',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail2',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }
    public function updateCustomerCategory()
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
            $wb_customer = new WhatsappCustomerMasterModel();
            $wb_customer->set('wb_cus_category', $this->request->getVar("wb_cus_category"))->where('wb_cus_id', $this->request->getVar("wb_cus_id"))->update();
            $response = [
                'ret_data' => 'success',
            ];
            return $this->respond($response, 200);
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function forwardWhatsappMessage()
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

            $wb_message = new WhatsappCustomerMessageModel();

            $customers = $this->request->getVar("alm_wb_msg_customers");
            $forwardMessage = $this->request->getVar("alm_wb_msg_content");
            $messageId = $this->request->getVar("messageId");
            // return $this->respond($cust_mobile, 200);

            // not correct 

            foreach ($customers as $cust) {
                // $messageData = array(
                //     "messaging_product" => "whatsapp",
                //     "to" => $cust->mobile,
                //     "type" => "text",
                //     'text' => [
                //         'body' =>  $forwardMessage
                //     ]
                // );

                $messageData = [
                    'messaging_product' => 'whatsapp',
                    'to' => $cust->mobile,
                    'type' => 'text',
                    'text' => [
                        'body' => $forwardMessage // Customize as needed
                    ],
                    'context' => [
                        'message_id' => $messageId, // This references the original message
                    ]
                ];

                $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                if (isset($returnMsg->messages)) {
                    if ($returnMsg->messages[0]->id != "") {
                        log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                        $message_data = [
                            'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                            'alm_wb_msg_source' => 2,
                            'alm_wb_msg_type' => $this->request->getVar('alm_wb_msg_type'),
                            'alm_wb_msg_content' =>  $forwardMessage,
                            'alm_wb_msg_status' => $this->request->getVar('alm_wb_msg_status'),
                            'alm_wb_msg_customer' => $cust->cus_id,
                            'alm_wb_msg_reply_id' => '',
                            'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                            'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        ];
                        $result = $wb_message->insert($message_data);
                    } else {
                        $response = [
                            'ret_data' => 'fail',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => $returnMsg,
                    ];
                    return $this->respond($response, 200);
                }
            }

            if ($result) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function replyMessageToCustomer()
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
            $wb_message = new WhatsappCustomerMessageModel();
            $last_message = $wb_message->where("alm_wb_msg_customer", $this->request->getVar('alm_wb_msg_customer'))->where("alm_wb_msg_source", 1)->orderBy('alm_wb_msg_created_on', 'desc')->first();
            if ($last_message) {
                $last_time = $last_message['alm_wb_msg_created_on'];
                $dateTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $last_time);
                $timestamp = $dateTime->getTimestamp();
                $currentTime = time();
                $timeDifference = $currentTime - $timestamp;
            } else {
                $timeDifference = 86400;
            }

            // if ($timeDifference > 86300) {
            //     $messageData = array(
            //         "messaging_product" => "whatsapp",
            //         "to" => $this->request->getVar("alm_wb_msg_mobile"),
            //         "type" => "template",
            //         "template" => array(
            //             "name" => "session_expiry_message", // Replace with your template name
            //             "language" => array(
            //                 "code" => "en" // Replace with the language code of your template
            //             ),
            //             "components" => array(
            //                 array(
            //                     "type" => "body",
            //                     "parameters" => array(
            //                         array(
            //                             "type" => "text",
            //                             "text" => $this->request->getVar("alm_wb_msg_content") // Replace or add as per your template's placeholders
            //                         ),
            //                     )
            //                 )
            //             )
            //         )
            //     );
            // } else {
            //     $messageData = array(
            //         "messaging_product" => "whatsapp",
            //         "to" => $this->request->getVar("alm_wb_msg_mobile"),
            //         "type" => "text",
            //         'text' => [
            //             'body' => $this->request->getVar('alm_wb_msg_content')
            //         ]
            //     );
            // }

            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => $this->request->getVar("alm_wb_msg_mobile"),
                "context" =>
                [
                    "message_id" =>  $this->request->getVar("msgId"),
                ],
                "type" => "text",
                'text' => [
                    'body' => $this->request->getVar('alm_wb_msg_content')
                ]
            );


            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => $this->request->getVar('alm_wb_msg_type'),
                        'alm_wb_msg_content' => $this->request->getVar('alm_wb_msg_content'),
                        'alm_wb_msg_status' => $this->request->getVar('alm_wb_msg_status'),
                        'alm_wb_msg_customer' => $this->request->getVar('alm_wb_msg_customer'),
                        'alm_wb_msg_reply_id' =>  $this->request->getVar('alm_wb_msg_reply_id'),
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                    'data' => $last_message
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function getWhatsappCustomersCounts()
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

            $wb_customer = new WhatsappCustomerMasterModel();
            $wb_customer_messages = new WhatsappCustomerMasterModel();


            // $customer_counts = $wb_customer->getCustomerMessageCounts();

            $customerCounts = [];

            // potential customers count
            // $customerCounts['message_status_2_count'] = $wb_customer
            //     ->where('wb_cus_category', 1)
            //     ->selectCount('alm_whatsapp_customers.wb_cus_id', 'message_status_2_count')
            //     ->join('alm_whatsapp_cus_messages', 'alm_whatsapp_cus_messages.alm_wb_msg_customer = alm_whatsapp_customers.wb_cus_id', 'left')
            //     ->where('alm_whatsapp_cus_messages.alm_wb_msg_source', 1)
            //     ->orderBy('alm_whatsapp_cus_messages.alm_wb_msg_created_on', 'DESC')
            //     ->first()['message_status_2_count'];



            // Count messages in the last 30 minutes
            $customerCounts['count_last_30_minutes'] = $wb_customer->where('wb_cus_category', 1)
                ->where('wb_cus_block', 0)
                ->select('COUNT(DISTINCT alm_whatsapp_customers.wb_cus_id) AS count_last_30_minutes') // Use COUNT with DISTINCT
                ->join('alm_whatsapp_cus_messages', 'alm_whatsapp_cus_messages.alm_wb_msg_customer = alm_whatsapp_customers.wb_cus_id', 'left')
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_source', 1)
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_created_on >=', date('Y-m-d H:i:s', strtotime('-30 minutes')))
                ->orderBy('alm_whatsapp_cus_messages.alm_wb_msg_created_on', 'DESC')
                ->first();

            $customerCounts['count_last_30_minutes'] = $customerCounts['count_last_30_minutes'] ? $customerCounts['count_last_30_minutes']['count_last_30_minutes'] : 0; // Handle null case


            // Count messages in the last 1 hour
            $customerCounts['count_last_1_hour'] = $wb_customer->where('wb_cus_category', 1)
                ->where('wb_cus_block', 0)
                ->select('COUNT(DISTINCT alm_whatsapp_customers.wb_cus_id) AS count_last_1_hour')
                ->join('alm_whatsapp_cus_messages', 'alm_whatsapp_cus_messages.alm_wb_msg_customer = alm_whatsapp_customers.wb_cus_id', 'left')
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_source', 1)
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_created_on >', date('Y-m-d H:i:s', strtotime('-1 hour')))
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_created_on <=', date('Y-m-d H:i:s', strtotime('-30 minutes')))
                ->orderBy('alm_whatsapp_cus_messages.alm_wb_msg_created_on', 'DESC')
                ->first();

            $customerCounts['count_last_1_hour'] = $customerCounts['count_last_1_hour'] ? $customerCounts['count_last_1_hour']['count_last_1_hour'] : 0; // Handle null case


            // Count messages in the last 3 hours
            $customerCounts['count_last_3_hours'] = $wb_customer->where('wb_cus_category', 1)
                ->where('wb_cus_block', 0)
                ->select('COUNT(DISTINCT alm_whatsapp_customers.wb_cus_id) AS count_last_3_hours')
                ->join('alm_whatsapp_cus_messages', 'alm_whatsapp_cus_messages.alm_wb_msg_customer = alm_whatsapp_customers.wb_cus_id', 'left')
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_source', 1)
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_created_on >', date('Y-m-d H:i:s', strtotime('-3 hours')))
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_created_on <=', date('Y-m-d H:i:s', strtotime('-1 hour')))
                ->orderBy('alm_whatsapp_cus_messages.alm_wb_msg_created_on', 'DESC')
                ->first();
            $customerCounts['count_last_3_hours'] = $customerCounts['count_last_3_hours'] ? $customerCounts['count_last_3_hours']['count_last_3_hours'] : 0; // Handle null case


            // Count messages in the last 1 day
            $customerCounts['count_last_1_day'] = $wb_customer->where('wb_cus_category', 1)
                ->where('wb_cus_block', 0)
                ->select('COUNT(DISTINCT alm_whatsapp_customers.wb_cus_id) AS count_last_1_day')
                ->join('alm_whatsapp_cus_messages', 'alm_whatsapp_cus_messages.alm_wb_msg_customer = alm_whatsapp_customers.wb_cus_id', 'left')
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_source', 1)
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_created_on >', date('Y-m-d H:i:s', strtotime('-1 day')))
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_created_on <=', date('Y-m-d H:i:s', strtotime('-3 hours')))
                ->orderBy('alm_whatsapp_cus_messages.alm_wb_msg_created_on', 'DESC')
                ->first();
            $customerCounts['count_last_1_day'] = $customerCounts['count_last_1_day'] ? $customerCounts['count_last_1_day']['count_last_1_day'] : 0; // Handle null case



            // Count messages in the last 3 days
            $customerCounts['count_last_3_days'] = $wb_customer->where('wb_cus_category', 1)
                ->where('wb_cus_block', 0)
                ->select('COUNT(DISTINCT alm_whatsapp_customers.wb_cus_id) AS count_last_3_days')
                ->join('alm_whatsapp_cus_messages', 'alm_whatsapp_cus_messages.alm_wb_msg_customer = alm_whatsapp_customers.wb_cus_id', 'left')
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_source', 1)
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_created_on >', date('Y-m-d H:i:s', strtotime('-3 days')))
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_created_on <=', date('Y-m-d H:i:s', strtotime('-1 day')))
                ->orderBy('alm_whatsapp_cus_messages.alm_wb_msg_created_on', 'DESC')
                ->first();

            $customerCounts['count_last_3_days'] = $customerCounts['count_last_3_days'] ? $customerCounts['count_last_3_days']['count_last_3_days'] : 0; // Handle null case




            if (count($customerCounts) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'wb_customers_count' => $customerCounts,
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'wb_customers' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function getWhatsappCustomersChatsByTime()
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

            $wb_customer = new WhatsappCustomerMasterModel();
            $wb_customer_messages = new WhatsappCustomerMasterModel();


            $timeInterval = $this->request->getVar('timeInterval');
            $timeInterval2 = $this->request->getVar('timeInterval2');
            $start_time = date('Y-m-d H:i:s', strtotime($timeInterval));
            $end_time = date('Y-m-d H:i:s', strtotime($timeInterval2));


            // $customer_counts = $wb_customer->getCustomerMessageCounts();
            $customerData = $wb_customer->where('wb_cus_category', 1)
                ->where('wb_cus_block', 0)
                ->select('alm_whatsapp_customers.*, alm_whatsapp_cus_messages.*')
                ->join('alm_whatsapp_cus_messages', 'alm_whatsapp_cus_messages.alm_wb_msg_customer = alm_whatsapp_customers.wb_cus_id', 'left')
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_source', 1)
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_created_on >', $start_time)
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_created_on <=', $end_time)
                ->groupBy('alm_whatsapp_customers.wb_cus_id')
                ->orderBy('alm_whatsapp_cus_messages.alm_wb_msg_created_on', 'DESC')
                ->findAll();



            if ($customerData) {
                $response = [
                    'ret_data' => 'success',
                    'wb_customers' => $customerData
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'wb_customers' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function getTemporaryLostWhatsappCustomers()
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

            $wb_customer = new WhatsappCustomerMasterModel();
            $wb_customer_messages = new WhatsappCustomerMasterModel();
            $WhatsappFollowUpTimeModel = new WhatsappFollowUpTimeModel();
            $WhatsappAssignedStaffsModel = new WhatsappAssignedStaffsModel();

            $customers = $wb_customer->select('alm_whatsapp_cus_messages.*, alm_whatsapp_customers.*')
                ->join('alm_whatsapp_cus_messages', 'alm_whatsapp_cus_messages.alm_wb_msg_customer = wb_cus_id', 'left')
                ->where('wb_cus_category', 1)
                ->where('wb_cus_block', 0)
                ->where('wb_cus_follow_up', 0)
                ->groupBy('wb_cus_id')
                ->orderBy('alm_whatsapp_cus_messages.alm_wb_msg_created_on', 'DESC')
                ->findAll();


            if ($customers) {
                $response = [
                    'ret_data' => 'success',
                    'wb_customers' => $customers
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'wb_customers' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function getWhatsappCustomerCategorize()
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

            $wb_customer = new WhatsappCustomerMasterModel();
            $wb_customer_messages = new WhatsappCustomerMasterModel();

            $category = $this->request->getVar('categorizes');



            $customers = $wb_customer->where('wb_cus_category', $category)
                ->join('alm_whatsapp_cus_messages', 'alm_whatsapp_cus_messages.alm_wb_msg_customer = wb_cus_id', 'left')
                ->groupBy('wb_cus_id')
                ->orderBy('alm_whatsapp_cus_messages.alm_wb_msg_created_on', 'DESC')
                ->where('wb_cus_block', 0)
                ->select('alm_whatsapp_customers.*,alm_whatsapp_cus_messages.*')
                ->findAll();



            if ($customers) {
                $response = [
                    'ret_data' => 'success',
                    'wb_customers' => $customers
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'wb_customers' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }


    public function getWhatsappCustomerCategorizeCounts()
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

            $wb_customer = new WhatsappCustomerMasterModel();
            $wb_customer_messages = new WhatsappCustomerMasterModel();

            $customerCounts = $wb_customer->select('wb_cus_category, COUNT(*) as count')
                ->whereIn('wb_cus_category', [1, 2, 3, 4, 5])
                ->groupBy('wb_cus_category')
                ->where('wb_cus_block', 0)
                ->findAll();

            $resultCounts = [
                'potential_Customer' => 0,
                'active_Customer' => 0,
                'appointment' => 0,
                'quotation' => 0,
                'irrelevant' => 0,
            ];

            foreach ($customerCounts as $row) {
                switch ($row['wb_cus_category']) {
                    case 1:
                        $resultCounts['potential_Customer'] = (int)$row['count'];
                        break;
                    case 2:
                        $resultCounts['active_Customer'] = (int)$row['count'];
                        break;
                    case 3:
                        $resultCounts['appointment'] = (int)$row['count'];
                        break;
                    case 4:
                        $resultCounts['quotation'] = (int)$row['count'];
                        break;
                    case 5:
                        $resultCounts['irrelevant'] = (int)$row['count'];
                        break;
                }
            }

            if ($resultCounts) {
                $response = [
                    'ret_data' => 'success',
                    'wb_customers_count' => $resultCounts
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'wb_customers_count' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }


    public function forwardLocationToCustomer()
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
            $wb_message = new WhatsappCustomerMessageModel();

            $customers = $this->request->getVar("alm_wb_msg_customers");

            foreach ($customers as $cust) {
                $messageData = array(
                    "messaging_product" => "whatsapp",
                    "recipient_type" => "individual",
                    "to" => $cust->mobile,
                    "type" => "location",
                    "location" =>  [
                        "latitude" => $this->request->getVar('alm_wb_msg_latitude'),
                        "longitude" => $this->request->getVar('alm_wb_msg_longitude'),
                        // "name" => $this->request->getVar('alm_wb_msg_name'),
                        // "address" => $this->request->getVar('alm_wb_msg_address'),
                    ]

                );
                $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                if (isset($returnMsg->messages)) {
                    if ($returnMsg->messages[0]->id != "") {
                        log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                        $message_data = [
                            'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                            'alm_wb_msg_source' => 2,
                            'alm_wb_msg_type' => $this->request->getVar('alm_wb_msg_type'),
                            'alm_wb_msg_content' => json_encode(["lat" => $this->request->getVar('alm_wb_msg_latitude'), "lng" => $this->request->getVar('alm_wb_msg_longitude')]),
                            'alm_wb_msg_status' => $this->request->getVar('alm_wb_msg_status'),
                            'alm_wb_msg_customer' => $cust->cus_id,
                            'alm_wb_msg_reply_id' =>  $this->request->getVar('alm_wb_msg_reply_id'),
                            'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                            'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        ];
                        $result = $wb_message->insert($message_data);
                    } else {
                        $response = [
                            'ret_data' => 'fail',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => $returnMsg,
                    ];
                    return $this->respond($response, 200);
                }
            }

            if ($result) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function  forwardMessageWithMedia()
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
            // $file = $this->request->getFile('media');
            // $file_name =  $this->request->getVar('name');
            // $file_type = $this->request->getVar('type');
            // $file_mime_type = $file->getMimeType();
            $wb_message = new WhatsappCustomerMessageModel();
            // $last_message = $wb_message->where("alm_wb_msg_customer", $this->request->getVar('customer_id'))->where("alm_wb_msg_source", 1)->orderBy('alm_wb_msg_created_on', 'desc')->first();
            // if ($last_message) {
            //     $last_time = $last_message['alm_wb_msg_created_on'];
            //     $dateTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $last_time);
            //     $timestamp = $dateTime->getTimestamp();
            //     $currentTime = time();
            //     $timeDifference = $currentTime - $timestamp;
            // } else {
            //     $timeDifference = 86400;
            // }

            // if ($timeDifference > 86300) {
            //     $messageData = array(
            //         "messaging_product" => "whatsapp",
            //         "to" => $this->request->getVar("customer_number"),
            //         "type" => "template",
            //         "template" => array(
            //             "name" => "session_expiry_media_message", // Replace with your template name
            //             "language" => array(
            //                 "code" => "en" // Replace with the language code of your template
            //             ),
            //             "components" => array(
            //                 array(
            //                     "type" => "header",
            //                     "parameters" => array(
            //                         array(
            //                             'type' => 'image',
            //                             'image' => [
            //                                 'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/' . $image_url, // Replace with the image URL// Optional: Add a caption to the image
            //                             ]
            //                         ),
            //                     )
            //                 ),
            //                 array(
            //                     "type" => "body",
            //                     "parameters" => array(
            //                         array(
            //                             "type" => "text",
            //                             "text" => $this->request->getVar("message") // Replace or add as per your template's placeholders
            //                         ),
            //                     )
            //                 )
            //             )
            //         )
            //     );
            // } 
            // else {

            // }

            $cust_array = $this->request->getVar('alm_wb_msg_customers');
            $image_url = $this->request->getVar('message');

            foreach ($cust_array as $cust) {

                $messageData = array(
                    "messaging_product" => "whatsapp",
                    "to" =>  $cust->mobile,
                    'type' => 'image',
                    'image' => [
                        'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/' . $image_url, // Replace with the image URL
                        //'caption' => $this->request->getVar("message") // Optional: Add a caption to the image
                    ],
                );

                $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                if (isset($returnMsg->messages)) {
                    if ($returnMsg->messages[0]->id != "") {
                        log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                        $message_data = [
                            'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                            'alm_wb_msg_source' => 2,
                            'alm_wb_msg_type' =>  5,
                            'alm_wb_msg_content' => $image_url,
                            'alm_wb_msg_caption' => '',
                            'alm_wb_msg_status' => 1,
                            'alm_wb_msg_customer' =>  $cust->cus_id,
                            'alm_wb_msg_reply_id' => '',
                            'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                            'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                            'alm_wb_msg_staff_id' => $tokendata['uid']
                        ];
                        $result = $wb_message->insert($message_data);
                    } else {
                        $response = [
                            'ret_data' => 'fail',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail',
                        'returnMsg' => $returnMsg
                    ];
                    return $this->respond($response, 200);
                }
            }


            if ($result) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function  forwardMessageWithAudio()
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
            $wb_message = new WhatsappCustomerMessageModel();
            // $last_message = $wb_message->where("alm_wb_msg_customer", $this->request->getVar('customer_id'))->where("alm_wb_msg_source", 1)->orderBy('alm_wb_msg_created_on', 'desc')->first();
            // if ($last_message) {
            //     $last_time = $last_message['alm_wb_msg_created_on'];
            //     $dateTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $last_time);
            //     $timestamp = $dateTime->getTimestamp();
            //     $currentTime = time();
            //     $timeDifference = $currentTime - $timestamp;
            // } else {
            //     $timeDifference = 86400;
            // }

            // if ($timeDifference > 86300) {
            //     $messageData = array(
            //         "messaging_product" => "whatsapp",
            //         "to" => $this->request->getVar("customer_number"),
            //         "type" => "template",
            //         "template" => array(
            //             "name" => "session_expiry_media_message", // Replace with your template name
            //             "language" => array(
            //                 "code" => "en" // Replace with the language code of your template
            //             ),
            //             "components" => array(
            //                 array(
            //                     "type" => "header",
            //                     "parameters" => array(
            //                         array(
            //                             'type' => 'image',
            //                             'image' => [
            //                                 'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/' . $image_url, // Replace with the image URL// Optional: Add a caption to the image
            //                             ]
            //                         ),
            //                     )
            //                 ),
            //                 array(
            //                     "type" => "body",
            //                     "parameters" => array(
            //                         array(
            //                             "type" => "text",
            //                             "text" => $this->request->getVar("message") // Replace or add as per your template's placeholders
            //                         ),
            //                     )
            //                 )
            //             )
            //         )
            //     );
            // } 
            // else {

            // }

            $customers = $this->request->getVar("alm_wb_msg_customers");
            $audio_url = $this->request->getVar("alm_wb_msg_content");
            $messageId = $this->request->getVar("messageId");
            foreach ($customers as $cust) {
                $messageData = array(
                    "messaging_product" => "whatsapp",
                    "to" =>  $cust->mobile,
                    'type' => 'audio',
                    'audio' => [
                        'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/' . $audio_url, // Replace with the image URL
                        // 'caption' => $this->request->getVar("message") // Optional: Add a caption to the image
                    ],
                );
                $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                if (isset($returnMsg->messages)) {
                    if ($returnMsg->messages[0]->id != "") {
                        log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                        $message_data = [
                            'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                            'alm_wb_msg_source' => 2,
                            'alm_wb_msg_type' => 1,
                            'alm_wb_msg_content' => $audio_url,
                            'alm_wb_msg_status' => 1,
                            'alm_wb_msg_customer' =>  $cust->cus_id,
                            'alm_wb_msg_reply_id' => '',
                            'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                            'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                            'alm_wb_msg_staff_id' => $tokendata['uid']
                        ];
                        $result = $wb_message->insert($message_data);
                    } else {
                        $response = [
                            'ret_data' => 'fail',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail',
                        '$returnMsg' => $returnMsg,
                    ];
                    return $this->respond($response, 200);
                }
            }
            if ($result) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function getFollowUpAlertTime()
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
            $WhatsappFollowUpTimeModel = new WhatsappFollowUpTimeModel();
            $WhatsappAssignedStaffsModel = new WhatsappAssignedStaffsModel();

            $result = $WhatsappFollowUpTimeModel->where("wb_msg_fut_delete_flag", 0)->findAll();
            $staff = $WhatsappAssignedStaffsModel->select('users.us_firstname,whatsapp_assigned_staffs.*')
                ->where("was_delete_flag", 0)
                ->join('users', 'users.us_id=was_assigned_staff', 'left')
                ->findAll();

            if ($result) {
                $response = [
                    'ret_data' => 'success',
                    'followUpTimes' => $result,
                    'staffs' => $staff

                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function updateWhatsAppMessageExpiration()
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
            $WhatsappFollowUpTimeModel = new WhatsappFollowUpTimeModel();

            $data = [
                'wb_msg_fut_time' => $this->request->getVar('followUpAlertTime'),
                'was_mfut_interval' => $this->request->getVar('followUpAlertInterval'),
            ];
            $res = $WhatsappFollowUpTimeModel->update($this->request->getVar('wb_msg_fut_id'), $data);
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'res' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function addStaffToWhatsapp()
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
            $WhatsappAssignedStaffsModel = new WhatsappAssignedStaffsModel();

            $data = [
                'was_assigned_staff' => $this->request->getVar('staff_id'),
                'was_assigned_staff_type' => $this->request->getVar('type'),
                'was_created_by' => $tokendata['uid'],
                'was_updated_by' => $tokendata['uid'],
            ];
            $res = $WhatsappAssignedStaffsModel->insert($data);
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'res' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function deleteWhatsappAssignedStaff()
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
            $WhatsappAssignedStaffsModel = new WhatsappAssignedStaffsModel();

            $data = [
                'was_delete_flag' => 1,
                'was_updated_by' => $tokendata['uid'],
            ];
            $res = $WhatsappAssignedStaffsModel->update($this->request->getVar('was_id'), $data);
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'res' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function getUnreadMessages()
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

            $wb_message = new WhatsappCustomerMessageModel();
            $wb_customer = new WhatsappCustomerMasterModel();

            // Get all customers that meet your criteria and join with the latest message
            // $customers = $wb_message->where('alm_whatsapp_cus_messages.alm_wb_msg_source', 1)
            //     ->where('alm_whatsapp_cus_messages.alm_wb_msg_status', 2)
            //     ->join('alm_whatsapp_customers', 'alm_whatsapp_customers.wb_cus_id=alm_wb_msg_customer', 'left')
            //     ->join('(SELECT * FROM alm_whatsapp_cus_messages AS wm1 
            //  WHERE wm1.alm_wb_msg_created_on = 
            //  (SELECT MAX(alm_wb_msg_created_on) 
            //   FROM alm_whatsapp_cus_messages AS wm2 
            //   WHERE wm2.alm_wb_msg_customer = wm1.alm_wb_msg_customer) 
            //  ) AS latest_message', 'latest_message.alm_wb_msg_customer=alm_whatsapp_customers.wb_cus_id', 'left')
            //     ->where('alm_whatsapp_customers.wb_cus_block', 0)
            //     ->select('alm_whatsapp_customers.*, latest_message.*') // Select all fields from both tables
            //     ->groupBy('alm_whatsapp_customers.wb_cus_id')
            //     ->findAll();

            // foreach ($customers as &$customer) { // Note the reference '&' to modify each customer in place
            //     $customer['message_status_2_count'] = $wb_message
            //         ->where('alm_wb_msg_source', 1)
            //         ->where('alm_wb_msg_status', 2)
            //         ->where('alm_wb_msg_customer', $customer['wb_cus_id']) // Filter by specific customer
            //         ->countAllResults();
            // }

            // Now $customers will include all fields from the latest message for each customer

            $wb_message = new WhatsappCustomerMessageModel();
            $wb_customer = new WhatsappCustomerMasterModel();

            // Get all customers that meet your criteria and join with the latest message
            $customers = $wb_message->select('alm_whatsapp_customers.*, latest_message.*')
                ->join('alm_whatsapp_customers', 'alm_whatsapp_customers.wb_cus_id = alm_wb_msg_customer', 'left')
                ->join(
                    '(SELECT wm1.* 
          FROM alm_whatsapp_cus_messages AS wm1
          WHERE wm1.alm_wb_msg_source = 1 
          AND wm1.alm_wb_msg_status = 2 
          AND wm1.alm_wb_msg_created_on = (
              SELECT MAX(wm2.alm_wb_msg_created_on) 
              FROM alm_whatsapp_cus_messages AS wm2 
              WHERE wm2.alm_wb_msg_customer = wm1.alm_wb_msg_customer
              AND wm2.alm_wb_msg_source = 1 
              AND wm2.alm_wb_msg_status = 2
          )
        ) AS latest_message',
                    'latest_message.alm_wb_msg_customer = alm_whatsapp_customers.wb_cus_id',
                    'left'
                )
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_source', 1)
                ->where('alm_whatsapp_cus_messages.alm_wb_msg_status', 2)
                ->where('alm_whatsapp_customers.wb_cus_block', 0)
                ->groupBy('alm_whatsapp_customers.wb_cus_id')
                ->orderBy('latest_message.alm_wb_msg_created_on', 'DESC')
                ->findAll();

            // Count messages for each customer
            foreach ($customers as &$customer) {
                $customer['message_status_2_count'] = $wb_message
                    ->where('alm_wb_msg_source', 1)
                    ->where('alm_wb_msg_status', 2)
                    ->where('alm_wb_msg_customer', $customer['wb_cus_id'])
                    ->countAllResults();
            }


            if ($customers) {
                $response = [
                    'ret_data' => 'success',
                    'customers' => $customers,
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }
    public function sendNewCustomerCampaignMessage()
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
            $wb_message = new WhatsappCustomerMessageModel();
            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => $this->request->getVar("country_code") . $this->request->getVar("mobile_number"),
                "type" => "template",
                "template" => array(
                    "name" => "campaign_twmplate_alm_auh", // Replace with your template name
                    "language" => array(
                        "code" => "en" // Replace with the language code of your template
                    ),
                    "components" => array(
                        array(
                            "type" => "header",
                            "parameters" => array(
                                array(
                                    'type' => 'image',
                                    'image' => [
                                        'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/crm_auh_whatsapp_media/campaign.png'
                                    ]
                                ),
                            )
                        ),
                        array(
                            "type" => "body",
                            "parameters" => array(
                                array(
                                    "type" => "text",
                                    "text" => "23 -28 Sep 2024" // Replace or add as per your template's placeholders
                                ),
                            )
                        )
                    )
                )
            );
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $wb_customer = new WhatsappCustomerMasterModel();
                    $wb_message = new WhatsappCustomerMessageModel();
                    $msg_customer = $wb_customer->where('wb_cus_mobile', $this->request->getVar("country_code") . $this->request->getVar("mobile_number"))->first();

                    if (!$msg_customer) {
                        $tracker_data = [
                            'wb_cus_name' => $this->request->getVar("customer_name") != "" ? $this->request->getVar("customer_name") : 'New Customer',
                            'wb_cus_mobile' => $this->request->getVar("country_code") . $this->request->getVar("mobile_number"),
                            'wb_cus_profile_pic' => ''
                        ];

                        $customer_id = $wb_customer->insert($tracker_data);
                    } else {
                        $customer_id = $msg_customer['wb_cus_id'];
                    }
                    log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 5,
                        'alm_wb_msg_content' => "crm_auh_whatsapp_media/campaign.png",
                        'alm_wb_msg_caption' => "Beat the High-Cost Estimates: Affordable Excellence Awaits You

тнР FREE Full  Check-Up
тнР 40% Off On Labour Costs
тнР 40% Off On Painting
тнР Best Deals on Genuine Parts

Exclusively your Mercedes Benz For Maintenance
From 23 - 28 Sep 2024тП▒

Offer Applicable only With Appointments ЁЯУй
T&C Apply",
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => $customer_id,
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail1',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail2',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function sendNewEngagementMessage()
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
            $wb_message = new WhatsappCustomerMessageModel();
            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => $this->request->getVar("mobile_number"),
                "type" => "template",
                "template" => array(
                    "name" => "campaign_influencer_maraghi", // Replace with your template name
                    "language" => array(
                        "code" => "en" // Replace with the language code of your template
                    ),
                    "components" => array(
                        array(
                            "type" => "header",
                            "parameters" => array(
                                array(
                                    'type' => 'video',
                                    'video' => [
                                        'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/' . $this->request->getVar("video_link")
                                    ]
                                ),
                            )
                        )
                    )
                )
            );
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $wb_customer = new WhatsappCustomerMasterModel();
                    $wb_message = new WhatsappCustomerMessageModel();
                    $msg_customer = $wb_customer->where('wb_cus_mobile', $this->request->getVar("mobile_number"))->first();

                    if (!$msg_customer) {
                        $tracker_data = [
                            'wb_cus_name' => $this->request->getVar("customer_name") != "" ? $this->request->getVar("customer_name") : 'New Customer',
                            'wb_cus_mobile' => $this->request->getVar("mobile_number"),
                            'wb_cus_profile_pic' => ''
                        ];

                        $customer_id = $wb_customer->insert($tracker_data);
                    } else {
                        $customer_id = $msg_customer['wb_cus_id'];
                    }
                    log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 11,
                        'alm_wb_msg_content' => $this->request->getVar("video_link"),
                        'alm_wb_msg_caption' => "
Al Maraghi Independent Mercedes Benz Service Centre - Dubai

Location: https://maps.app.goo.gl/dStmGXJeFPkDFoRR6?g_st=aw

Address: Al Quoz - Al Quoz Industrial Area 2 - Dubai - United Arab Emirates.     
 
Working Hours:
Mon - Thu and Sat : 8:30am - 7pm
Friday: 8:30 am -12 pm, 2:15pm-7:00pm
Sunday: Off day",
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => $customer_id,
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail1',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail2',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function checkFollowUpOverdue()
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

            $wb_message = new WhatsappCustomerMessageModel();
            $wb_customer = new WhatsappCustomerMasterModel();

            $timeLimit = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $overdueFollowUp = $wb_customer->where('wb_cus_follow_up', 6)
                ->where('wb_cus_follow_up_time <', $timeLimit)
                ->findAll();

            if (!empty($overdueRecords)) {

                foreach ($overdueRecords as $customer) {
                    $updData[] = array(
                        'wb_cus_id' => $customer->wb_cus_id,
                        'wb_cus_follow_up' => 7,
                    );
                }
                if (sizeof($updData) > 0) {
                    $updateddata = $wb_customer->updateBatch($updData, 'wb_cus_id');
                }
            }

            if ($updateddata) {
                $response = [
                    'ret_data' => 'success',
                    'updateddata' => $updateddata,

                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }


    public function sendLocationMessage()
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
            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => $this->request->getVar("cust_mobile"),
                "type" => "template",
                "template" => array(
                    "name" => "garage_location", // Replace with your template name
                    "language" => array(
                        "code" => "en" // Replace with the language code of your template
                    ),
                )
            );
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $wb_message = new WhatsappCustomerMessageModel();
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_caption' => '',
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_content' => 'Al Maraghi Independent Mercedes Benz Service Centre Dubai

                        Location: https://maps.app.goo.gl/dStmGXJeFPkDFoRR6?g_st=aw

                        Address: Al Quoz - Al Quoz Industrial Area 2 - Dubai - United Arab Emirates.

                        Working Hours: 
                        Mon - Thu and Sat : 8:30am - 7pm
                        Friday: 8:30 am -12 pm, 2:15pm-7:00pm
                        Sunday : Off day ',

                        'alm_wb_msg_customer' => $this->request->getVar("cust_id"),
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail1',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail2',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function sendAppointmentRemainderMessage()
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
            $date = strtotime($this->request->getVar("date"));
            $formattedDate = date('d/m/Y', $date);
            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => $this->request->getVar("cust_mobile"),
                "type" => "template",
                "template" => array(
                    "name" => "appointment_remainder", // Replace with your template name
                    "language" => array(
                        "code" => "en" // Replace with the language code of your template
                    ),
                    "components" => array(
                        array(
                            "type" => "body",
                            "parameters" => array(
                                array(
                                    "type" => "text",
                                    "text" => $formattedDate // Replace or add as per your template's placeholders
                                ),
                                array(
                                    "type" => "text",
                                    "text" => $this->request->getVar("time") // Replace or add as per your template's placeholders
                                ),
                            )
                        )
                    )
                )
            );
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $wb_message = new WhatsappCustomerMessageModel();
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_caption' => '',
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_content' => 'We’d like to remind you about your appointment at ALMARAGHI Automotives.

                        DATE: ' . $formattedDate . ', TIME: ' . $this->request->getVar("time") . '

                        Location: https://maps.app.goo.gl/dStmGXJeFPkDFoRR6?g_st=aw 
                        Address: Al Quoz - Al Quoz Industrial Area 2 - Dubai - United Arab Emirates.

                        Al Maraghi Independent Mercedes Benz Service Centre Dubai',

                        'alm_wb_msg_customer' => $this->request->getVar("cust_id"),
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail1',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail2',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function sendCustomerReEngMessage()
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
            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => $this->request->getVar("cust_mobile"),
                "type" => "template",
                "template" => array(
                    "name" => "customer_re_engage_text", // Replace with your template name
                    "language" => array(
                        "code" => "en" // Replace with the language code of your template
                    ),
                )
            );
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $wb_message = new WhatsappCustomerMessageModel();
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_caption' => '',
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_content' => 'Hi there! 😊

                      Thank you for contacting Al-Maraghi Automotives! Do you have any questions about our Mercedes-Benz services? 🚘🔧

                      Feel free to reply here or call us at +971 50 588 2207📞

                      Best regards
                      Al-Maraghi Automotives Dubai 🌐',

                        'alm_wb_msg_customer' => $this->request->getVar("cust_id"),
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail1',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail2',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }


    public function  whatsappMessageExpiredFollowupLogs()
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
            $WhatsappFollowupMessageTimeExceededLogs = new WhatsappFollowupMessageTimeExceededLogs();

            $customers = $this->request->getVar("followUpTimeExceededCustomers");
            $insData = array();

            foreach ($customers as $cust) {
                $exists = $WhatsappFollowupMessageTimeExceededLogs
                    ->where('alm_wb_msg_fut_exc_wb_msg_id', $cust->alm_wb_msg_id)
                    ->first();
                if (!$exists) {
                    $insData[] = array(
                        'alm_wb_msg_fut_exc_wb_cus_id' => $cust->wb_cus_id,
                        'alm_wb_msg_fut_exc_wb_msg_id' => $cust->alm_wb_msg_id,
                        'alm_wb_msg_fut_exc_time' => date("Y-m-d h:i"),
                        'alm_wb_msg_fut_exc_by' => $cust->alm_wb_msg_staff_id,
                    );
                }
            }


            if (sizeof($insData) > 0) {
                $dataInserted = $WhatsappFollowupMessageTimeExceededLogs->insertBatch($insData);
            } else {
                $dataInserted = false;
            }


            if ($dataInserted) {
                $response = [
                    'ret_data' => 'success',
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }


    public function getWhatsappLeadReOpenHours()
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
            $builder->select('whatsapp_lead_reopen_hours,whatsapp_auto_msg_hours');
            $query = $builder->get();
            $row = $query->getRow();

            if ($row) {
                $response = [
                    'ret_data' => 'success',
                    'leadReopenHours' => $row,

                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function updateWhatsappLeadReOpenHours()
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
            $builder = $this->db->table('sequence_data');
            $builder->set('whatsapp_lead_reopen_hours', $this->db->escapeString($this->request->getVar('whatsapp_lead_reopen_hours')));
            $builder->update();

            $response = [
                'ret_data' => 'success',
            ];
            return $this->respond($response, 200);
        }
    }

    public function sendCampaignMessage()
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
            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => $this->request->getVar("cust_mobile"),
                "type" => "template",
                "template" => array(
                    "name" => "campaign_message", // Replace with your template name
                    "language" => array(
                        "code" => "en" // Replace with the language code of your template
                    ),
                )
            );
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $wb_message = new WhatsappCustomerMessageModel();
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_caption' => '',
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_content' => '*Exclusive Offer: 100-Point Health & Computer Check-Up!*

Keep your vehicle in top shape with our comprehensive inspection and computer diagnostic.

*Here’s what’s included:*

- *Engine Compartment (25 Points):*
Detailed inspection of belts, hoses, fluids, and engine components.

- *Suspension & Brakes (35 Points):*
Thorough check of suspension, brake pads, rotors, and fluids for road safety.

- *Underbody (22 Points):*
Examine chassis, exhaust, and drivetrain for corrosion or damage.

- *Electrical System (7 Points):*
Battery, lights, and wiring check to prevent failures.

- *General (11 Points):*
Fluids, air filters, and essentials to keep your car running smoothly.

- *Bonus: Computer diagnostic for error codes and system checks.*

- *Book Now and Drive Confidently!*

Limited-time offer – ensure your car’s in expert hands today!',

                        'alm_wb_msg_customer' => $this->request->getVar("cust_id"),
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail1',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail2',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function sendNewCustomerCampaignNewMessage()
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
            $wb_message = new WhatsappCustomerMessageModel();
            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => $this->request->getVar("country_code") . $this->request->getVar("mobile_number"),
                "type" => "template",
                "template" => array(
                    "name" => "campaign_message", // Replace with your template name
                    "language" => array(
                        "code" => "en" // Replace with the language code of your template
                    ),
                )
            );
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $wb_customer = new WhatsappCustomerMasterModel();
                    $wb_message = new WhatsappCustomerMessageModel();
                    $msg_customer = $wb_customer->where('wb_cus_mobile', $this->request->getVar("country_code") . $this->request->getVar("mobile_number"))->first();

                    if (!$msg_customer) {
                        $tracker_data = [
                            'wb_cus_name' => $this->request->getVar("customer_name") != "" ? $this->request->getVar("customer_name") : 'New Customer',
                            'wb_cus_mobile' => $this->request->getVar("country_code") . $this->request->getVar("mobile_number"),
                            'wb_cus_profile_pic' => ''
                        ];

                        $customer_id = $wb_customer->insert($tracker_data);
                    } else {
                        $customer_id = $msg_customer['wb_cus_id'];
                    }
                    log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_content' => '*Exclusive Offer: 100-Point Health & Computer Check-Up!*

                        Keep your vehicle in top shape with our comprehensive inspection and computer diagnostic.
                        
                        *Here’s what’s included:*
                        
                        - *Engine Compartment (25 Points):*
                        Detailed inspection of belts, hoses, fluids, and engine components.
                        
                        - *Suspension & Brakes (35 Points):*
                        Thorough check of suspension, brake pads, rotors, and fluids for road safety.
                        
                        - *Underbody (22 Points):*
                        Examine chassis, exhaust, and drivetrain for corrosion or damage.
                        
                        - *Electrical System (7 Points):*
                        Battery, lights, and wiring check to prevent failures.
                        
                        - *General (11 Points):*
                        Fluids, air filters, and essentials to keep your car running smoothly.
                        
                        - *Bonus: Computer diagnostic for error codes and system checks.*
                        
                        - *Book Now and Drive Confidently!*
                        
                        Limited-time offer – ensure your car’s in expert hands today!',
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => $customer_id,
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail1',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail2',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function sendtemporaryMessage()
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
            $wb_message = new WhatsappCustomerMessageModel();
            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => '971523076155',
                "type" => "template",
                "template" => array(
                    "name" => "temporary_2", // Replace with your template name
                    "language" => array(
                        "code" => "en" // Replace with the language code of your template
                    ),
                )
            );
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    // $wb_customer = new WhatsappCustomerMasterModel();
                    // $wb_message = new WhatsappCustomerMessageModel();
                    // $msg_customer = $wb_customer->where('wb_cus_mobile', $this->request->getVar("country_code") . $this->request->getVar("mobile_number"))->first();

                    // if (!$msg_customer) {
                    //     $tracker_data = [
                    //         'wb_cus_name' => $this->request->getVar("customer_name") != "" ? $this->request->getVar("customer_name") : 'New Customer',
                    //         'wb_cus_mobile' => $this->request->getVar("country_code") . $this->request->getVar("mobile_number"),
                    //         'wb_cus_profile_pic' => ''
                    //     ];

                    //     $customer_id = $wb_customer->insert($tracker_data);
                    // } else {
                    //     $customer_id = $msg_customer['wb_cus_id'];
                    // }
                    // log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_content' => "AL MARAGHI AUTOMOTIVE
Independent Mercedes-Benz Service Centre

Price: 550 AED + tax

We use Mercedes-Benz's recommended fully synthetic oil (15,000 km interval).

Complementary services:
- Air filter cleaning
- Service interval resetting
- Body wash and vacuum cleaning",
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => 695,
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail1',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail2',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function sendlocationTimingMessage()
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
            $wb_message = new WhatsappCustomerMessageModel();
            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => '971588991145',
                "type" => "template",
                "template" => array(
                    "name" => "garage_location_template ", // Replace with your template name
                    "language" => array(
                        "code" => "en" // Replace with the language code of your template
                    ),
                )
            );
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    // $wb_customer = new WhatsappCustomerMasterModel();
                    // $wb_message = new WhatsappCustomerMessageModel();
                    // $msg_customer = $wb_customer->where('wb_cus_mobile', $this->request->getVar("country_code") . $this->request->getVar("mobile_number"))->first();

                    // if (!$msg_customer) {
                    //     $tracker_data = [
                    //         'wb_cus_name' => $this->request->getVar("customer_name") != "" ? $this->request->getVar("customer_name") : 'New Customer',
                    //         'wb_cus_mobile' => $this->request->getVar("country_code") . $this->request->getVar("mobile_number"),
                    //         'wb_cus_profile_pic' => ''
                    //     ];

                    //     $customer_id = $wb_customer->insert($tracker_data);
                    // } else {
                    //     $customer_id = $msg_customer['wb_cus_id'];
                    // }
                    // log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_content' => "Al Maraghi Independent Mercedes Benz Service Centre Dubai
 
Location: https://maps.app.goo.gl/dStmGXJeFPkDFoRR6?g_st=aw

Address: Al Quoz - Al Quoz Industrial Area 2 - Dubai - United Arab Emirates.

Working Hours: 
Mon - Thu and Sat : 8:30am - 7pm
Friday: 8:30 am -12 pm, 2:15pm-7:00pm
Sunday : Off day",
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => 849,
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail1',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail2',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        }
    }


    public function sendAppointmentReminderMessage()
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
            // $date = strtotime($this->request->getVar("date"));
            // $formattedDate = date('d/m/Y', $date);
            $messageData = array(
                "messaging_product" => "whatsapp",
                "to" => '971509272212',
                "type" => "template",
                "template" => array(
                    "name" => "appointment_reminder", // Replace with your template name
                    "language" => array(
                        "code" => "en" // Replace with the language code of your template
                    ),
                    "components" => array(
                        array(
                            "type" => "body",
                            "parameters" => array(
                                array(
                                    "type" => "text",
                                    "text" => '14/10/2024' // Replace or add as per your template's placeholders
                                ),
                                array(
                                    "type" => "text",
                                    "text" => "11:00 AM TO 11:30 AM" // Replace or add as per your template's placeholders
                                ),
                            )
                        )
                    )
                )
            );
            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $wb_message = new WhatsappCustomerMessageModel();
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_content' => "We'd Like To Remind You About Your Appointment In ALMARAGHI Automotives
14/10/2024 11:00 AM TO 11:30 AM
Location https://maps.app.goo.gl/dStmGXJeFPkDFoRR6?g_st=aw Address: Al Quoz - Al Quoz Industrial Area 2 - Dubai - United Arab Emirates.
Al Maraghi Independent Mercedes Benz Service Centre Dubai",
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => 410,
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => $tokendata['uid']
                    ];
                    $result = $wb_message->insert($message_data);
                    if ($result) {
                        //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                        $response = [
                            'ret_data' => 'success',
                        ];
                        return $this->respond($response, 200);
                    } else {
                        $response = [
                            'ret_data' => 'fail1',
                        ];
                        return $this->respond($response, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail2',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function searchWhatsappCustomer()
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

            $wb_message = new WhatsappCustomerMessageModel();
            $wb_customer = new WhatsappCustomerMasterModel();

            $searchText = $this->request->getVar("serachText");

            // Step 1: Fetch all customers using LIKE
            $customers = $wb_customer
                ->groupStart()
                ->like('wb_cus_name', $searchText)
                ->orLike('wb_cus_mobile', $searchText)
                ->groupEnd()
                ->limit(10)
                ->get()
                ->getResultArray();

            // Step 2: Fetch the latest message for each customer and merge its fields
            foreach ($customers as &$customer) {
                $latestMessage = $wb_message
                    ->where('alm_wb_msg_customer', $customer['wb_cus_id'])
                    ->orderBy('alm_wb_msg_created_on', 'DESC')
                    ->first();

                if ($latestMessage) {
                    // Merge latest message fields directly into the customer array
                    foreach ($latestMessage as $key => $value) {
                        $customer[$key] = $value;
                    }
                }
            }






            if ($customers) {
                $response = [
                    'ret_data' => 'success',
                    'customers' => $customers,

                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }

    public function getWhatsappCustomersFollowups()
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

            $wb_message = new WhatsappCustomerMessageModel();
            $wb_customer = new WhatsappCustomerMasterModel();
            $WhatsappFollowUpTimeModel = new WhatsappFollowUpTimeModel();

            $followUpResults = $WhatsappFollowUpTimeModel->where("wb_msg_fut_delete_flag", 0)->findAll();

            $followupCustomerMessages = [];
            $db = \Config\Database::connect();

            foreach ($followUpResults as $followUp) {
                $wb_msg_fut_seq = $followUp['wb_msg_fut_seq'];



                // Raw SQL Query
                $sql = "
                         SELECT c.*, l.status_id, l.phone
                         FROM alm_whatsapp_customers c
                         INNER JOIN (
                         SELECT phone, MAX(lead_creted_date) AS latest_lead_date
                         FROM leads
                         GROUP BY RIGHT(phone, 9)
                         ) latest_leads
                         ON RIGHT(c.wb_cus_mobile, 9) = RIGHT(latest_leads.phone, 9)
                         INNER JOIN leads l
                         ON RIGHT(l.phone, 9) = RIGHT(latest_leads.phone, 9) AND l.lead_creted_date = latest_leads.latest_lead_date
                         WHERE l.status_id IN (8, 1)
                         AND c.wb_cus_follow_up = ?
                         AND c.wb_cus_block = 0
                         AND c.wb_cus_delete_flag = 0
                          GROUP BY c.wb_cus_id
                        ";

                // Bind variables (if any)
                $query = $db->query($sql, [$wb_msg_fut_seq]);

                // Fetch results
                $customers = $query->getResultArray();

                // $customers = $wb_customer->select('alm_whatsapp_customers.*, leads.status_id, leads.phone')
                //     ->join('leads', 'RIGHT(wb_cus_mobile, -9) = RIGHT(leads.phone, -9)')
                //     ->where("leads.status_id", 8)
                //     ->where("wb_cus_follow_up", $wb_msg_fut_seq)
                //     ->where("wb_cus_block", 0)
                //     ->where("wb_cus_delete_flag", 0)
                //     ->findAll();

                if (!isset($followupCustomerMessages[$wb_msg_fut_seq])) {
                    $followupCustomerMessages[$wb_msg_fut_seq] = [];
                }
                foreach ($customers as $customer) {
                    $latestMessage = $wb_message
                        ->where('alm_wb_msg_customer', $customer['wb_cus_id'])
                        ->orderBy('alm_wb_msg_created_on', 'desc')
                        ->limit(1)
                        ->get()
                        ->getRowArray();
                    if ($latestMessage) {
                        $temp = array_merge($customer, $latestMessage);
                        $followupCustomerMessages[$wb_msg_fut_seq][] = $temp;
                    }
                }
            }

            log_message('error', print_r($followupCustomerMessages, true));

            if ($followupCustomerMessages) {
                $response = [
                    'ret_data' => 'success',
                    'followupCustomers' => (array)$followupCustomerMessages,

                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        }
    }



    public function sendCustomerServiceReminderCampaignMessage($customers, $type)
    {
        $common = new Common();
        $wb_camp = new WhatsappCampaignModel();
        $wb_camp_message = new WhatsappCampaignMessageModel();

        $builder = $this->db->table('sequence_data');
        $builder->select('first_service_remainder_days,second_service_remainder_days,third_service_remainder_days');
        $query = $builder->get();
        $row = $query->getRow();

        $campaignName = 'SRC_' . $type . '_' . date('d-m-Y');

        foreach ($customers as $cust) {

            $phone = !empty($cust['MOBILE']) ? $cust['MOBILE'] : $cust['PHONE'];
            $last9Digits = substr($phone, -10);
            $whatsappNumber = '91' . $last9Digits;

            $templateName = "";
            $alm_wb_msg_content = "";

            $nextServiceKM = $cust['SPEEDOMETER_READING'] + 15000;
            $customerName = ucfirst(strtolower($cust['CUSTOMER_NAME']));

            if ($type == $row->first_service_remainder_days) {
                $templateName = "first_service_reminder";
                $alm_wb_msg_content = "🔧 Service Reminder - Mercedes {$cust['CAR_REG_NO']}

                Hi {$customerName},
                
                Greetings from Al Maraghi Auto Repairs!  
                It’s been 8 months since your last service at Al Maraghi Auto Repairs for your Mercedes.  
                If your car has crossed {$nextServiceKM} KM, it's time for a service to keep it running smoothly!
                
                Let us know when you'd like to schedule your service! 🚗

www.benzuae.com

↪ Book An Appointment  
↪ Remind Me Later ";
                $alm_wb_camp_type = 3;
                $messageData = array(
                    "messaging_product" => "whatsapp",
                    "to" => $whatsappNumber,
                    "type" => "template",
                    "template" => array(
                        "name" => $templateName,
                        "language" => array(
                            "code" => "en"
                        ),
                        "components" => array(
                            array(
                                "type" => "body",
                                "parameters" => array(
                                    array(
                                        "type" => "text",
                                        "text" => $cust['CAR_REG_NO'] // Replace or add as per your template's placeholders
                                    ),
                                    array(
                                        "type" => "text",
                                        "text" => $customerName // Replace or add as per your template's placeholders
                                    ),
                                    array(
                                        "type" => "text",
                                        "text" => $nextServiceKM // Replace or add as per your template's placeholders
                                    ),
                                )
                            )
                        )
                    )
                );
            } else if ($type == $row->second_service_remainder_days) {
                $templateName = "factory_service_reminder";
                $alm_wb_msg_content = "🔧 Time for Your Factory Recommended Service -Mercedes {$cust['CAR_REG_NO']}

Hi {$customerName},

Greetings from Al Maraghi Auto Repairs!🚗✨

It’s been 360 days since your last service, and your Mercedes is due for its factory recommended service—required once a year or every 15,000 km (whichever comes first).

Regular maintenance keeps your car performing at its best and prevents costly repairs. Let's schedule your service today to keep your Mercedes running smoothly!

Looking forward to servicing your Mercedes!

www.benzuae.com

↪ Book An Appointment  
↪ Remind Me Later  
↪ Skip For This Time
";
                $alm_wb_camp_type = 4;
                $messageData = array(
                    "messaging_product" => "whatsapp",
                    "to" => $whatsappNumber,
                    "type" => "template",
                    "template" => array(
                        "name" => $templateName,
                        "language" => array(
                            "code" => "en"
                        ),
                        "components" => array(
                            array(
                                "type" => "body",
                                "parameters" => array(
                                    array(
                                        "type" => "text",
                                        "text" => $cust['CAR_REG_NO'] // Replace or add as per your template's placeholders
                                    ),
                                    array(
                                        "type" => "text",
                                        "text" => $customerName // Replace or add as per your template's placeholders
                                    ),
                                )
                            )
                        )
                    )
                );
            } else if ($type == $row->third_service_remainder_days) {
                $templateName = "third_service_reminder";
                $alm_wb_msg_content = "Get your Free Pick up & Drop 

🔧 Service Reminder - {$cust['CAR_REG_NO']}

Hi {$customerName},

Greetings from Al Maraghi Auto Repairs!🚗✨

Your service is overdue! It has exceeded 1 year since your last service, and your Mercedes is due for maintenance.

Timely servicing enhances performance, extends your car’s lifespan, and helps prevent costly repairs. Let’s schedule your service today to keep your Mercedes running smoothly!

Looking forward to servicing your Mercedes!
www.benzuae.com

↪ Book An Appointment  
↪ Remind Me Later  
↪ Skip For This Time
";
                $alm_wb_camp_type = 5;
                $messageData = array(
                    "messaging_product" => "whatsapp",
                    "to" => $whatsappNumber,
                    "type" => "template",
                    "template" => array(
                        "name" => $templateName,
                        "language" => array(
                            "code" => "en"
                        ),
                        "components" => array(
                            array(
                                "type" => "body",
                                "parameters" => array(
                                    array(
                                        "type" => "text",
                                        "text" => $cust['CAR_REG_NO'] // Replace or add as per your template's placeholders
                                    ),
                                    array(
                                        "type" => "text",
                                        "text" => $customerName // Replace or add as per your template's placeholders
                                    ),
                                )
                            )
                        )
                    )
                );
            }
            // else {
            //     // Assuming $type == 366
            //     $templateName = "campaign_template_jan_25";
            //     $alm_wb_msg_content = "crm_auh_whatsapp_media/jan25.png";
            //     $alm_wb_camp_type = 5;
            // }


            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $wb_customer = new WhatsappCustomerMasterModel();
                    $wb_message = new WhatsappCustomerMessageModel();

                    $msg_customer = $wb_customer->where('SUBSTRING(wb_cus_mobile, -10)', $last9Digits)->first();

                    if (!$msg_customer) {
                        $tracker_data = [
                            'wb_cus_name' =>  $customerName != "" ?  $customerName : '',
                            'wb_cus_mobile' => $whatsappNumber,
                            'wb_cus_profile_pic' => '',
                        ];
                        $customer_id = $wb_customer->insert($tracker_data);
                    } else {
                        $customer_id = $msg_customer['wb_cus_id'];

                        if (!empty($customerName)) {
                            $update_data = ['wb_cus_name' =>  $customerName];

                            $wb_customer->where('wb_cus_id', $msg_customer['wb_cus_id'])->set($update_data)->update();
                        }
                    }

                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_content' =>  $alm_wb_msg_content,
                        'alm_wb_msg_caption' =>  '',
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => $customer_id,
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_camp_type' => $alm_wb_camp_type,
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    ];
                    $result = $wb_message->insert($message_data);
                    $exist_camp = $wb_camp->where("LOWER(alm_wb_camp_name)", $campaignName)->get()->getRowObject();

                    if (!$exist_camp) {
                        $campaign_data = [
                            'alm_wb_camp_name' => 'SRC_' . $type . '_' . date('d-m-Y'),
                            'alm_wb_camp_type' => $alm_wb_camp_type,
                            'alm_wb_camp_cust_count' => 1,
                            'alm_wb_camp_date_from' => date('Y-m-d'),
                            'alm_wb_camp_date_to' => date('Y-m-d'),
                            'alm_wb_camp_created_by' => 18,
                            'alm_wb_camp_created_on' => date("Y-m-d H:i:s.u"),
                        ];
                        $camp_data = $wb_camp->insert($campaign_data);
                        log_message('error', 'Campaign New inserted' . $camp_data);

                        $campaign_msg_data = [
                            'alm_wb_camp_msg_wb_cus_id' => $customer_id,
                            'alm_wb_camp_msg_wb_camp_id' => $camp_data,
                            'alm_wb_camp_msg_wb_msg_id' => $result,
                            'alm_wb_camp_msg_cus_phone' => $whatsappNumber,
                            'alm_wb_camp_msg_cus_reg_no' => $cust['CAR_REG_NO'],
                            'alm_wb_camp_msg_created_on' => date("Y-m-d H:i:s.u"),
                        ];
                        $camp_msg_data = $wb_camp_message->insert($campaign_msg_data);
                    } else {

                        $totalCount = $exist_camp->alm_wb_camp_cust_count + 1;
                        $campaign_data = [

                            'alm_wb_camp_id' => $exist_camp->alm_wb_camp_id,
                            'alm_wb_camp_cust_count' => $totalCount,
                        ];

                        $camp_data = $wb_camp->where('alm_wb_camp_id', $exist_camp->alm_wb_camp_id)->set($campaign_data)->update();

                        $campaign_msg_data = [
                            'alm_wb_camp_msg_wb_cus_id' => $customer_id,
                            'alm_wb_camp_msg_wb_camp_id' => $exist_camp->alm_wb_camp_id,
                            'alm_wb_camp_msg_wb_msg_id' => $result,
                            'alm_wb_camp_msg_cus_phone' => $whatsappNumber,
                            'alm_wb_camp_msg_cus_reg_no' => $cust['CAR_REG_NO'],
                            'alm_wb_camp_msg_created_on' => date("Y-m-d H:i:s.u"),
                        ];
                        $camp_msg_data = $wb_camp_message->insert($campaign_msg_data);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => $returnMsg,
                ];
                return $this->respond($response, 200);
            }
        }
    }


    public function pickupAndDropoff($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $contact['wa_id'],
            "type" => "interactive",
            "interactive" => array(
                "type" => "button",
                "body" => array(
                    "text" => "Please select one of the following options:"
                ),
                "footer" => array(
                    "text" => "Tap an option to respond."
                ),
                "action" => array(
                    "buttons" => array(
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "Paid_Pick_&_Drop",
                                "title" => "🚗 Paid Pick & Drop"
                            )
                        ),
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "Drop-off",
                                "title" => "📍 Drop-off (self)"
                            )
                        )
                    )
                )
            )
        );

        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

        // log_message('error',  "I am here at button pickupAndDropoff >>>>>" . json_encode($returnMsg));

        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {
                $bodyText = $messageData['interactive']['body']['text'];
                $buttons = $messageData['interactive']['action']['buttons'];

                $buttonTexts = [];
                foreach ($buttons as $button) {
                    $buttonTexts[] = $button['reply']['title'];
                }

                // Format the message with a line break before options
                $formattedText = $bodyText . "\n\n" . implode("\n", $buttonTexts);
                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $formattedText,
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $formattedText,
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }

    public function driverOrRecoveryPickUp($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $contact['wa_id'],
            "type" => "interactive",
            "interactive" => array(
                "type" => "button",
                "body" => array(
                    "text" => "Please select one of the following options:"
                ),
                "footer" => array(
                    "text" => "Tap an option to respond."
                ),
                "action" => array(
                    "buttons" => array(
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "driver",
                                "title" => "🧑🏻‍✈️ Driver Pickup"
                            )
                        ),
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "recovery",
                                "title" => "🚛 Recovery Pickup"
                            )
                        )
                    )
                )
            )
        );

        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

        // log_message('error',  "I am here at button driverOrRecoveryPickUp >>>>>" . json_encode($returnMsg));

        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {
                $bodyText = $messageData['interactive']['body']['text'];
                $buttons = $messageData['interactive']['action']['buttons'];

                $buttonTexts = [];
                foreach ($buttons as $button) {
                    $buttonTexts[] = $button['reply']['title'];
                }

                // Format the message with a line break before options
                $formattedText = $bodyText . "\n\n" . implode("\n", $buttonTexts);
                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $formattedText,
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $formattedText,
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }
    public function driverPickUp($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $contact['wa_id'],
            "type" => "interactive",
            "interactive" => array(
                "type" => "button",
                "body" => array(
                    "text" => "Please select the option below:"
                ),
                "footer" => array(
                    "text" => "Tap an option to respond."
                ),
                "action" => array(
                    "buttons" => array(
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "driver",
                                "title" => "🧑🏻‍✈️ Driver Pickup"
                            )
                        ),
                        // array(
                        //     "type" => "reply",
                        //     "reply" => array(
                        //         "id" => "recovery",
                        //         "title" => "🛠️ Recovery Pickup"
                        //     )
                        // )
                    )
                )
            )
        );

        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

        // log_message('error',  "I am here at button driverOrRecoveryPickUp >>>>>" . json_encode($returnMsg));

        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {
                $bodyText = $messageData['interactive']['body']['text'];
                $buttons = $messageData['interactive']['action']['buttons'];

                $buttonTexts = [];
                foreach ($buttons as $button) {
                    $buttonTexts[] = $button['reply']['title'];
                }

                // Format the message with a line break before options
                $formattedText = $bodyText . "\n\n" . implode("\n", $buttonTexts);
                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $formattedText,
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $formattedText,
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }

    public function getCustomerAppointmentDate($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $currentHour = (int) date("H");
        $startDate = ($currentHour >= 17) ? strtotime("+1 day") : strtotime("today");
        $startDateStr = date("Y-m-d", $startDate);
        $startDateObj = new \DateTime($startDateStr, new \DateTimeZone("Asia/Dubai"));
        $dateRows = [];

        $i = 0;
        $daysAdded = 0;

        while ($daysAdded < 7) { // Iterate until we get 7 valid days
            $date = clone $startDateObj;
            $date->modify("+$i day");

            if ($date->format("w") != 0) { // Exclude Sundays (0 = Sunday)
                $dateRows[] = [
                    "id" => $date->format("Y-m-d"),
                    "title" => $date->format("l, Y-m-d") // Include day name (e.g., Monday, 2024-02-26)
                ];
                $daysAdded++; // Increment only if not Sunday
            }
            $i++; // Always increment to check the next day
        }

        // Add "Other" option
        $dateRows[] = [
            "id" => "date_other",
            "title" => "Other"
        ];
        // log_message('error',  "Entered In If condition >>>>>" . json_encode($dateRows));
        $messageData = [
            "messaging_product" => "whatsapp",
            "to" => $contact['wa_id'], // Ensure $contact['wa_id'] is defined
            "type" => "interactive",
            "interactive" => [
                "type" => "list",
                "body" => [
                    "text" => "Please choose a date for your appointment. 🗓️"
                ],
                "footer" => [
                    "text" => "Tap a date to select."
                ],
                "action" => [
                    "button" => "Choose a Date",
                    "sections" => [
                        [
                            "title" => "Available Pickup Dates",
                            "rows" => $dateRows
                        ]
                    ]
                ]
            ]
        ];
        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
        $dateTextMessage = "📅 *Please choose a date for your appointment:*\n" . implode("\n", array_column($dateRows, 'title'));
        // log_message('error',  "I am here at button Location >>>>>" . json_encode($dateTextMessage));
        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {

                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $dateTextMessage,
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $dateTextMessage,
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }
    public function getCustomerAppointmentTimeSlots($contact, $msg_customer, $selectedDate)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        // Convert selected date to day of the week
        $selectedDateObj = new \DateTime($selectedDate, new \DateTimeZone("Asia/Dubai"));
        $dayOfWeek = $selectedDateObj->format("w"); // 0 = Sunday, 1 = Monday, ..., 6 = Saturday

        $timeSlots = [];

        $timeSlots = $this->getTimeSlots($dayOfWeek);


        // Add "Other" option
        // $timeSlots[] = [
        //     "id" => "time_other",
        //     "title" => "Other"
        // ];

        // log_message('error', "Generated Time Slots >>>>>" . json_encode($timeSlots));

        // Prepare the interactive message
        $messageData = [
            "messaging_product" => "whatsapp",
            "to" => $contact['wa_id'],
            "type" => "interactive",
            "interactive" => [
                "type" => "list",
                "body" => [
                    "text" => "Please choose a time slot on " . $selectedDate
                ],
                "footer" => [
                    "text" => "Tap a time slot to select."
                ],
                "action" => [
                    "button" => "Choose a Time",
                    "sections" => [
                        [
                            "title" => "Available Time Slots",
                            "rows" => $timeSlots
                        ]
                    ]
                ]
            ]
        ];

        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

        // log_message('error', "sendCustomerWhatsappMessage Time Slots >>>>>" . json_encode($returnMsg));
        $timeSlotTextMessage = "🕒 *Please choose a time slot for pickup:*\n" . implode("\n", array_column($timeSlots, 'title'));

        // log_message('error', "Sending Time Slots >>>>>" . json_encode($timeSlotTextMessage));

        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {
                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $timeSlotTextMessage,
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $wb_message->insert($message_data);

                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $timeSlotTextMessage,
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
            }
        }
    }


    public function getTimeSlots($dayOfWeek)
    {
        $timeSlots = [];

        // Define time ranges based on the day
        if (in_array($dayOfWeek, [1, 2, 3, 4, 6])) { // Monday to Saturday
            $timeRanges = [
                ["start" => "08:00", "end" => "10:00", "interval" => 2], // First slot from 8 AM to 10 AM
                ["start" => "10:00", "end" => "19:00", "interval" => 1]  // 1-hour slots from 10 AM to 7 PM
            ];
        } elseif ($dayOfWeek == 5) { // Friday
            $timeRanges = [
                ["start" => "08:00", "end" => "12:30", "interval" => 1], // 1-hour slots from 8 AM to 12:30 PM
                ["start" => "14:30", "end" => "19:00", "interval" => 1]  // 1-hour slots from 2:30 PM to 7 PM
            ];
        } else {
            return $timeSlots; // Return empty for Sunday
        }

        // Generate time slots based on defined intervals
        foreach ($timeRanges as $range) {
            $startTime = new DateTime($range["start"]);

            // Adjust end time if it's "00:00" so it represents midnight of the next day.
            if ($range["end"] == "00:00") {
                $endTime = new DateTime("tomorrow 00:00");
            } else {
                $endTime = new DateTime($range["end"]);
            }

            while ($startTime < $endTime) {
                $nextTime = clone $startTime;
                $nextTime->modify("+" . $range["interval"] . " hours");

                // Prevent overshooting the range
                if ($nextTime > $endTime) {
                    $nextTime = $endTime;
                }

                $timeSlots[] = [
                    "id" => strtolower($startTime->format("ga")) . "-" . strtolower($nextTime->format("ga")),
                    "title" => $startTime->format("g:i A") . " - " . $nextTime->format("g:i A")
                ];

                if ($nextTime == $endTime) break;

                $startTime = $nextTime;
            }
        }

        return $timeSlots;
    }







    public function getCustomerLocation($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $contact['wa_id'],
            "type" => "text",
            "text" => array(
                "body" => "Please share your pick-up/drop-off location by tapping the 📍 (location) button in WhatsApp."
            )
        );

        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

        // log_message('error',  "I am here at button Location >>>>>" . json_encode($returnMsg));

        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {

                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $messageData['text']['body'],
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $messageData['text']['body'],
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }

    public function createAutoAppointment($contact, $msg_customer, $appdate, $apptTime)
    {
        preg_match('/-(\d{1,2})(am|pm)/i', $apptTime, $matches);

        if (!empty($matches)) {
            $hour = (int) $matches[1]; // Extract hour
            $meridian = strtolower($matches[2]); // Extract AM/PM

            // Convert to 24-hour format
            if ($meridian === 'pm' && $hour < 12) {
                $hour += 12;
            } elseif ($meridian === 'am' && $hour == 12) {
                $hour = 23; // Change midnight (12 AM) to 23
            }

            $formattedTime = sprintf('%02d:00:00', $hour); // Convert to HH:00:00 format
        }
        // log_message('error',  "Last Lead in createAutoAppointment  formattedTime >>>>>>>>>" . json_encode($formattedTime));

        $common = new Common();
        $wb_customer = new WhatsappCustomerMasterModel();
        $wb_message = new WhatsappCustomerMessageModel();
        $Leadmodel = new LeadModel();
        $ApptMaster = new AppointmentMasterModel();
        $Appoint = new AppointmentModel();
        $Appointmentlog = new AppointmentLogModel();
        $wb_camp_message = new WhatsappCampaignMessageModel();

        $result = $this->checkCustomerHasAppointment($msg_customer);
        $reminderData = $this->isCustomerSetRemindMeLater($msg_customer);




        $last_lead = $result['last_lead'];
        $isAppointment = $result['isAppointment'];







        if ($last_lead && !$isAppointment && !$reminderData['is_reminder_set']) {

            $contact_last_9 = substr($contact['wa_id'], -9);
            $reg_no = $wb_camp_message
                ->where("RIGHT(alm_wb_camp_msg_cus_phone, 9)", $contact_last_9)
                ->select('alm_wb_camp_msg_cus_reg_no')
                ->orderBy('alm_wb_camp_msg_id', 'DESC')
                ->first();

            log_message('error',  "Last Lead in createAutoAppointment  reg_no >>>>>>>>>" . json_encode($reg_no));

            $alertMessage = "🔔 New Appointment Alert\n\nHello,\n\nCustomer Number [ " . $contact['wa_id'] . " ] has booked an appointment.\n\n"
                . "📅 Date: " . $appdate . "\n"
                . "⏰ Time: " . $formattedTime . "\n"
                . "🚗 Reg No: " . ($reg_no ? $reg_no['alm_wb_camp_msg_cus_reg_no'] : 'N/A') . "\n\n"
                . "Please take note. ✅";

            $messageData = array(
                "messaging_product" => "whatsapp",
                "recipient_type" => "individual",
                "to" => "+918138055705",
                "type" => "text",
                "text" => array(
                    "body" => $alertMessage
                )
            );

            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

            $service_Reminder = $wb_customer->select('wb_cus_reminder')
                ->where('wb_cus_id', $msg_customer['wb_cus_id'])
                ->get()
                ->getRow('wb_cus_reminder');

            log_message('error',  "Last Lead in createAutoAppointment  service_Reminder >>>>>>>>>" . json_encode($service_Reminder));



            if ($service_Reminder == '3') {
                $apptm_type = 7;
            } elseif ($service_Reminder == '4') {
                $apptm_type = 8;
            } elseif ($service_Reminder == '5') {
                $apptm_type = 9;
            } else {
                $apptm_type = 0;
            }

            if ($apptm_type != 0) {
                $data = [
                    //'lead_code' => $code,
                    'phone' => $contact['wa_id'],
                    'status_id' => 1,
                    'source_id' => 10,
                    'purpose_id' => 1,  // Appointment
                    'lang_id' => 1,
                    'lead_note' => 'Appointment from service reminder due',
                    'assigned' => 19,
                    'ld_appoint_date' =>  $appdate,
                    'ld_appoint_time' =>  $formattedTime,
                    'register_number' =>  $reg_no['alm_wb_camp_msg_cus_reg_no'],
                    'lead_createdby' => 1,
                    'lead_updatedon' => date("Y-m-d H:i:s"),

                ];
            } else {
                $data = [
                    //'lead_code' => $code,
                    'phone' => $contact['wa_id'],
                    'status_id' => 1,
                    'purpose_id' => 1,  // Appointment
                    'lang_id' => 1,
                    'lead_note' => 'Appointment from non working hours',
                    'assigned' => 19,
                    'ld_appoint_date' =>  $appdate,
                    'ld_appoint_time' =>  $formattedTime,
                    'lead_createdby' => 1,
                    'lead_updatedon' => date("Y-m-d H:i:s"),

                ];
            }


            $Leadmodel->where('lead_id', $last_lead['lead_id'])->set($data)->update();
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
            $transportService = $wb_customer->where('wb_cus_id', $msg_customer['wb_cus_id'])
                ->select('wb_cus_pick_drop')
                ->first();

            $pickup_mode = $wb_customer->where('wb_cus_id', $msg_customer['wb_cus_id'])
                ->select('wb_cus_pickup_mode')
                ->first();



            log_message('error',  "Last Lead in createAutoAppointment  pickup_mode >>>>>>>>>" . json_encode($pickup_mode));
            log_message('error',  "Last Lead in createAutoAppointment  pickup_mode >>>>>>>>>" . json_encode($pickup_mode['wb_cus_pickup_mode']));


            $apptMdata = [
                'apptm_code' => $code,
                'apptm_alternate_no' => $contact['wa_id'],
                'apptm_lead_id' => $last_lead['lead_id'],
                'apptm_status' => '1',
                'apptm_transport_service' => $transportService ? $transportService['wb_cus_pick_drop'] : 4,
                'apptm_pickup_mode' => $pickup_mode ? $pickup_mode['wb_cus_pickup_mode'] : 0,
                'apptm_created_by' =>  1,
                'apptm_updated_by' =>  1,
                'apptm_type' => $apptm_type, //WhatsApp Ser Due
                'apptm_group' => 1,
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
                    'appt_date' => $appdate,
                    'appt_time' => $formattedTime,
                    'appt_assign_to' => 19,
                    'appt_note' => 'Appointment from service reminder',
                    'appt_created_by' => 1,
                    'appt_created_on' => date("Y-m-d H:i:s"),
                ];
                $result1 = $Appoint->insert($Apptdata);
                $Logdata = [
                    'applg_apptm_id' => $result,
                    'applg_note' => "Appointment Scheduled",
                    'applg_created_by' => 1,
                    'applg_created_on' => date("Y-m-d H:i:s"),
                    'applg_time' => date("Y-m-d H:i:s"),
                ];
                $logentry = $Appointmentlog->insert($Logdata);

                $messageData = array(
                    "messaging_product" => "whatsapp",
                    "to" => $contact['wa_id'],
                    "type" => "template",
                    "template" => array(
                        "name" => "appointment_template", // Replace with your template name
                        "language" => array(
                            "code" => "en" // Replace with the language code of your template
                        ),
                        "components" => array(
                            array(
                                "type" => "header",
                                "parameters" => array(
                                    array(
                                        'type' => 'image',
                                        'image' => [
                                            'link' => 'https://autoversa-media.s3.me-central-1.amazonaws.com/common_use/playstore.png', // Replace with the image URL// Optional: Add a caption to the image
                                        ]
                                    ),
                                )
                            ),
                            array(
                                "type" => "body",
                                "parameters" => array(
                                    array(
                                        "type" => "text",
                                        "text" => $appdate
                                    ),
                                    array(
                                        "type" => "text",
                                        "text" =>  $apptTime, //"$this->request->getVar("timeFrom")"
                                    ),
                                    array(
                                        "type" => "text",
                                        "text" =>  $reg_no['alm_wb_camp_msg_cus_reg_no'],
                                    ),
                                )
                            )
                        )
                    )
                );
                // log_message('error',  "Last Lead in createAutoAppointment  message >>>>>>>>>" . json_encode($messageData));
                $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
                if (isset($returnMsg->messages)) {
                    if ($returnMsg->messages[0]->id != "") {
                        $wb_message = new WhatsappCustomerMessageModel();
                        $message_data = [
                            'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                            'alm_wb_msg_source' => 2,
                            'alm_wb_msg_type' => 5,
                            'alm_wb_msg_content' => 'common_use/playstore.png',
                            'alm_wb_msg_status' => 1,
                            'alm_wb_msg_caption' => 'Greetings from Al Maraghi Auto Repairs. 
                        We wanted to confirm your upcoming appointment for your Mercedes-Benz service.
                        Here are the details: 
                        DATE: ' . $appdate . '
                        TIME: ' . $apptTime . '
                        REG NO: ' .  $reg_no['alm_wb_camp_msg_cus_reg_no'] . '
                        Location: https://maps.app.goo.gl/HBd9ZUbGmh9rtZUCA',

                            'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                            'alm_wb_msg_reply_id' => '',
                            'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                            'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                            'alm_wb_msg_staff_id' => 1
                        ];
                        $result = $wb_message->insert($message_data);
                        $data = [
                            'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                            'message_type' => 4,
                            'message_source' => 2,
                            'message' => 'Greetings from Al Maraghi Auto Repairs. 
                        We wanted to confirm your upcoming appointment for your Mercedes-Benz service.
                        Here are the details: 
                        DATE: ' . $appdate . '
                        TIME: ' . $apptTime . '
                        REG NO: ' .  $reg_no['alm_wb_camp_msg_cus_reg_no'] . '
                        Location: https://maps.app.goo.gl/HBd9ZUbGmh9rtZUCA',
                            'time' => date('Y-m-d H:i:s'),
                            'toUserId' => 0,
                            'fromUserId' => $msg_customer
                        ];
                        $this->sendSocketMessage($data);

                        $currentFollowUp = $wb_customer->where('wb_cus_mobile', $contact['wa_id'])->select('wb_cus_follow_up')->first();
                        if ($currentFollowUp) {
                            $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                            if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                                $followUpCount++;
                            } else if ($followUpCount == 3) {
                                $followUpCount = 6;
                            } else if ($followUpCount == 1) {
                                $followUpCount = 2;
                            }
                            $tracker_data = [
                                'wb_cus_follow_up' => $followUpCount,
                                'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                                'wb_cus_category' => 3,
                            ];
                            $wb_customer->where('wb_cus_mobile', $contact['wa_id'])->set($tracker_data)->update();
                        }
                    }
                }
            }
        } else if ($reminderData['is_reminder_set']) {
            $this->reminderAlreadySetMessage($contact, $msg_customer, $reminderData['remind_date']);
        } else {
            $this->appointmentScheduledMessage($contact, $msg_customer, $last_lead);
        }
    }

    public function remindMeLater($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();


        $result = $this->checkCustomerHasAppointment($msg_customer);

        $last_lead = $result['last_lead'];
        $isAppointment = $result['isAppointment'];

        if (!$isAppointment) {
            $messageData = array(
                "messaging_product" => "whatsapp",
                "recipient_type" => "individual",
                "to" => $contact['wa_id'],
                "type" => "interactive",
                "interactive" => array(
                    "type" => "list",
                    "body" => array(
                        "text" => "Next Reminder Date:"
                    ),
                    "footer" => array(
                        "text" => "Tap an option to respond."
                    ),
                    "action" => array(
                        "button" => "Choose Duration",
                        "sections" => array(
                            array(
                                "title" => "Available Durations",
                                "rows" => array(
                                    array(
                                        "id" => "8M_7",
                                        "title" => "⏰ 7 Days",
                                        // "description" => "Reminder in 7 days"
                                    ),
                                    array(
                                        "id" => "8M_14",
                                        "title" => "⏰ 14 Days",
                                        // "description" => "Reminder in 14 days"
                                    ),
                                    array(
                                        "id" => "8M_21",
                                        "title" => "⏰ 21 Days",
                                        // "description" => "Reminder in 21 days"
                                    ),
                                    array(
                                        "id" => "8M_30",
                                        "title" => "🕰️ 30 Days",
                                        // "description" => "Reminder in 30 days"
                                    )
                                )
                            )
                        )
                    )
                )
            );


            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

            // log_message('error',  "I am here at button Remind Me Later >>>>>" . json_encode($returnMsg));

            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $bodyText = $messageData['interactive']['body']['text'];
                    $sections = $messageData['interactive']['action']['sections'];

                    $reminderOptions = [];

                    // Extract list options correctly
                    foreach ($sections as $section) {
                        foreach ($section['rows'] as $row) {
                            $reminderOptions[] = $row['title'];
                        }
                    }

                    // Format the message with a line break before options
                    $formattedText = $bodyText . "\n\n" . implode("\n", $reminderOptions);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_content' => $formattedText,
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => 1,
                    ];
                    $result = $wb_message->insert($message_data);
                    $data = [
                        'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                        'message_type' => 4,
                        'message_source' => 2,
                        'message' => $formattedText,
                        'time' => date('Y-m-d H:i:s'),
                        'toUserId' => 1,
                        'fromUserId' => $msg_customer
                    ];
                    $this->sendSocketMessage($data);
                    $mobile = $msg_customer['wb_cus_mobile'];
                    $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                    if ($currentFollowUp) {
                        $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                        if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                            $followUpCount++;
                        } else if ($followUpCount == 3) {
                            $followUpCount = 6;
                        } else if ($followUpCount == 1) {
                            $followUpCount = 2;
                        }
                        $tracker_data = [
                            'wb_cus_follow_up' => $followUpCount,
                            'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                        ];
                        $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                    }
                }
            }
        } else {
            $this->appointmentScheduledMessage($contact, $msg_customer, $last_lead);
        }
    }

    public function oneYearRemindMeLater($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();


        $result = $this->checkCustomerHasAppointment($msg_customer);

        $last_lead = $result['last_lead'];
        $isAppointment = $result['isAppointment'];

        if (!$isAppointment) {
            $messageData = array(
                "messaging_product" => "whatsapp",
                "recipient_type" => "individual",
                "to" => $contact['wa_id'],
                "type" => "interactive",
                "interactive" => array(
                    "type" => "list",
                    "body" => array(
                        "text" => "Next Reminder Date:"
                    ),
                    "footer" => array(
                        "text" => "Tap an option to respond."
                    ),
                    "action" => array(
                        "button" => "Choose Duration",
                        "sections" => array(
                            array(
                                "title" => "Available Durations",
                                "rows" => array(
                                    array(
                                        "id" => "1Y_7",
                                        "title" => "⏰ 7 Days",
                                        // "description" => "Reminder in 7 days"
                                    ),
                                    array(
                                        "id" => "1Y_14",
                                        "title" => "⏰ 14 Days",
                                        // "description" => "Reminder in 14 days"
                                    ),
                                    array(
                                        "id" => "1Y_21",
                                        "title" => "⏰ 21 Days",
                                        // "description" => "Reminder in 21 days"
                                    ),
                                    array(
                                        "id" => "1Y_30",
                                        "title" => "🕰️ 30 Days",
                                        // "description" => "Reminder in 30 days"
                                    )
                                )
                            )
                        )
                    )
                )
            );


            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

            // log_message('error',  "I am here at button Remind Me Later >>>>>" . json_encode($returnMsg));

            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $bodyText = $messageData['interactive']['body']['text'];
                    $sections = $messageData['interactive']['action']['sections'];

                    $reminderOptions = [];

                    // Extract list options correctly
                    foreach ($sections as $section) {
                        foreach ($section['rows'] as $row) {
                            $reminderOptions[] = $row['title'];
                        }
                    }

                    // Format the message with a line break before options
                    $formattedText = $bodyText . "\n\n" . implode("\n", $reminderOptions);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_content' => $formattedText,
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => 1,
                    ];
                    $result = $wb_message->insert($message_data);
                    $data = [
                        'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                        'message_type' => 4,
                        'message_source' => 2,
                        'message' => $formattedText,
                        'time' => date('Y-m-d H:i:s'),
                        'toUserId' => 1,
                        'fromUserId' => $msg_customer
                    ];
                    $this->sendSocketMessage($data);
                    $mobile = $msg_customer['wb_cus_mobile'];
                    $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                    if ($currentFollowUp) {
                        $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                        if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                            $followUpCount++;
                        } else if ($followUpCount == 3) {
                            $followUpCount = 6;
                        } else if ($followUpCount == 1) {
                            $followUpCount = 2;
                        }
                        $tracker_data = [
                            'wb_cus_follow_up' => $followUpCount,
                            'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                        ];
                        $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                    }
                }
            }
        } else {
            $this->appointmentScheduledMessage($contact, $msg_customer, $last_lead);
        }
    }

    public function notInterestedCustomers($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $result = $this->checkCustomerHasAppointment($msg_customer);
        $reminderData = $this->isCustomerSetRemindMeLater($msg_customer);

        $last_lead = $result['last_lead'];
        $isAppointment = $result['isAppointment'];

        if (!$isAppointment && !$reminderData['is_reminder_set']) {
            $messageData = array(
                "messaging_product" => "whatsapp",
                "recipient_type" => "individual",
                "to" => $contact['wa_id'],
                "type" => "interactive",
                "interactive" => array(
                    "type" => "button",
                    "body" => array(
                        "text" => "Escalate with our Team"
                    ),
                    "footer" => array(
                        "text" => "Tap an option to respond."
                    ),
                    "action" => array(
                        "buttons" => array(
                            array(
                                "type" => "reply",
                                "reply" => array(
                                    "id" => "yes_escalate",
                                    "title" => "✅ Yes"
                                )
                            ),
                            array(
                                "type" => "reply",
                                "reply" => array(
                                    "id" => "no_escalate",
                                    "title" => "❌ No"
                                )
                            )
                        )
                    )
                )
            );


            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

            // log_message('error',  "I am here at button Escalte to Our Representative >>>>>" . json_encode($returnMsg));

            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $bodyText = $messageData['interactive']['body']['text'];
                    $buttons = $messageData['interactive']['action']['buttons'];

                    $buttonTexts = [];
                    foreach ($buttons as $button) {
                        $buttonTexts[] = $button['reply']['title'];
                    }

                    // Format the message with a line break before options
                    $formattedText = $bodyText . "\n\n" . implode("\n", $buttonTexts);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_content' => $formattedText,
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => 1,
                    ];
                    $result = $wb_message->insert($message_data);
                    $data = [
                        'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                        'message_type' => 4,
                        'message_source' => 2,
                        'message' => $formattedText,
                        'time' => date('Y-m-d H:i:s'),
                        'toUserId' => 1,
                        'fromUserId' => $msg_customer
                    ];
                    $this->sendSocketMessage($data);
                    $mobile = $msg_customer['wb_cus_mobile'];
                    $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                    if ($currentFollowUp) {
                        $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                        if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                            $followUpCount++;
                        } else if ($followUpCount == 3) {
                            $followUpCount = 6;
                        } else if ($followUpCount == 1) {
                            $followUpCount = 2;
                        }
                        $tracker_data = [
                            'wb_cus_follow_up' => $followUpCount,
                            'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                        ];
                        $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                    }
                }
            }
        } else if ($reminderData['is_reminder_set']) {
            $this->reminderAlreadySetMessage($contact, $msg_customer, $reminderData['remind_date']);
        } else {
            $this->appointmentScheduledMessage($contact, $msg_customer, $last_lead);
        }
    }

    public function sendThankYouMessage($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $contact['wa_id'],
            "type" => "text",
            "text" => array(
                "body" => "Thank you for reaching out! If you ever need assistance in the future, don’t hesitate to contact us at +97125503556. We're always here to help! 😊"
            )
        );

        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

        // log_message('error',  "I am here at SendThankYouMessage >>>>>" . json_encode($returnMsg));

        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {

                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $messageData['text']['body'],
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $messageData['text']['body'],
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }

    public function transferToDissatisfiedModule($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();
        $dissatisfiedmaster = new DissatisfiedMasterModel();
        $dissatisfiedlog = new DissatisfiedLogModel();
        $Leadmodel = new LeadModel();



        $status = [1, 7, 8];
        $lead_list = $Leadmodel->whereIn('status_id', $status)
            ->where('RIGHT(phone,9)',  substr($msg_customer['wb_cus_mobile'], -9))->orderBy('lead_id', 'desc')->findAll();
        $last_lead = $Leadmodel->where('RIGHT(phone,9)',  substr($msg_customer['wb_cus_mobile'], -9))->orderBy('lead_id', 'desc')->first();

        // log_message('error',  "I am here at transferToDissatisfiedModule lead_list>>>>>" . json_encode($lead_list));


        if (sizeof($lead_list) == 0) {
            if ($last_lead) {
                $builder = $this->db->table('sequence_data');
                $builder->selectMax('whatsapp_lead_reopen_hours');
                $query = $builder->get();
                $row = $query->getRow();
                // Retrieve the reopen hours and lead created date
                $reOpenHours = $row->whatsapp_lead_reopen_hours;
                $reOpenHours = (int)$reOpenHours;
                $lead_creted_date = $last_lead['lead_creted_date'];
                // Convert lead created date to a DateTime object
                $leadCreatedDateTime = new DateTime($lead_creted_date);
                $currentDateTime = new DateTime();
                // Calculate the difference in hours
                $interval = $leadCreatedDateTime->diff($currentDateTime);
                $hoursDifference = ($interval->days * 24) + $interval->h;
            }
            if ($last_lead && $hoursDifference < $reOpenHours && $last_lead['purpose_id'] == '10') {
                $lead_code = $last_lead['lead_code'];
                $lead_id = $last_lead['lead_id'];
            }
        } else {
            $lead_id = $lead_list[0]['lead_id'];
        }


        $existingRecord = $dissatisfiedmaster->where('ldm_ldl_id', $lead_id)
            ->whereNotIn('ldm_status', [4, 5])
            ->first();

        if (!$existingRecord) { // Only insert if no such record exists
            $dissatisfied_data = [
                'ldm_ldl_id' => $lead_id,
                'ldm_psf_id' => 0,
                'ldm_status' => 1,
                'ldm_type' => 4,  // From Service Reminder
                'ldm_assign' => 19,
                'ldm_created_on' => date("Y-m-d H:i:s.u"),
                'ldm_created_by' => 1,
                'ldm_updated_on' => date("Y-m-d H:i:s.u"),
                'ldm_updated_by' => 1,
            ];

            $dissatisfied_cust = $dissatisfiedmaster->insert($dissatisfied_data);

            // log_message('error',  "I am here at transferToDissatisfiedModule >>>>>" . json_encode($dissatisfied_cust));

            if ($dissatisfied_cust) {
                $Logdata = [
                    'ldl_ldm_id' => $dissatisfied_cust,
                    // 'ldl_response' => $ldl_response,
                    'ldl_note' => "Transfered From Whatsapp Service Reminder",
                    'ldl_action' => 0,
                    'ldl_activity' => "Service Reminder Dissatisfied log entered",
                    'ldl_created_by' => 1,
                ];
                $logentry = $dissatisfiedlog->insert($Logdata);
            }
        }


        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $contact['wa_id'],
            "type" => "text",
            "text" => array(
                "body" => "Thank you! Our representative will contact you soon to assist with your request. Please stay tuned, and we appreciate your patience! 😊"
            )
        );

        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
        // log_message('error',  "I am here at transferToDissatisfiedModule >>>>>" . json_encode($returnMsg));
        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {

                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $messageData['text']['body'],
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $messageData['text']['body'],
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }

    public function checkCustomerHasAppointment($msg_customer)
    {
        $Leadmodel = new LeadModel();
        $ApptMaster = new AppointmentMasterModel();

        $status = [1, 7, 8];
        $lead_list = $Leadmodel->whereIn('status_id', $status)
            ->where('RIGHT(phone,9)', substr($msg_customer['wb_cus_mobile'], -9))
            ->orderBy('lead_id', 'desc')
            ->findAll();

        $last_lead = $Leadmodel->where('RIGHT(phone,9)', substr($msg_customer['wb_cus_mobile'], -9))
            ->orderBy('lead_id', 'desc')
            ->first();

        $lead_id = null; // Initialize lead_id
        $isAppointment = false; // Default to false

        if (empty($lead_list)) {
            if ($last_lead) {
                $builder = $this->db->table('sequence_data');
                $builder->selectMax('whatsapp_lead_reopen_hours');
                $query = $builder->get();
                $row = $query->getRow();

                $reOpenHours = (int)$row->whatsapp_lead_reopen_hours;
                $lead_created_date = $last_lead['lead_creted_date'];

                $leadCreatedDateTime = new DateTime($lead_created_date);
                $currentDateTime = new DateTime();
                $interval = $leadCreatedDateTime->diff($currentDateTime);
                $hoursDifference = ($interval->days * 24) + $interval->h;

                if ($hoursDifference < $reOpenHours && $last_lead['purpose_id'] == '10') {
                    $lead_id = $last_lead['lead_id'];
                }
            }
        } else {
            $lead_id = $lead_list[0]['lead_id'];
        }

        // log_message('error', "Last Lead in createAutoAppointment message >>>>>>>>>" . json_encode($last_lead));

        if ($lead_id) {
            $apptm_status = [1, 2, 3];
            $isAppointment = $ApptMaster->where('apptm_lead_id', $lead_id)
                ->whereIn('apptm_status', $apptm_status)
                ->orderBy('apptm_id', 'desc')
                ->first();
        }

        return [
            'last_lead' => $last_lead,
            'isAppointment' => $isAppointment
        ];
    }

    public function appointmentScheduledMessage($contact, $msg_customer, $last_lead)
    {

        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $contact['wa_id'],
            "type" => "text",
            "text" => array(
                "body" => "🗓️ You already scheduled an appointment on *" .
                    ($last_lead['ld_appoint_date']
                        ? date('Y-m-d (l)', strtotime($last_lead['ld_appoint_date']))
                        : 'N/A') .
                    ".* If you need any changes, feel free to contact us at +971 2 550 3556. ✅"
            )


        );
        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {

                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $messageData['text']['body'],
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $messageData['text']['body'],
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }

    public function isCustomerSetRemindMeLater($msg_customer)
    {
        $wb_customer = new WhatsappCustomerMasterModel();

        $reminderDetails = $wb_customer->select(['wb_cus_remind_date'])
            ->whereIn('wb_cus_remind_flag', [1])
            ->where('STR_TO_DATE(wb_cus_remind_date, "%Y-%m-%d") >=', date('Y-m-d')) // Convert VARCHAR to DATE
            ->where('RIGHT(wb_cus_mobile,9)', substr($msg_customer['wb_cus_mobile'], -9))
            ->findAll();

        // log_message('error', "Last Lead in createAutoAppointment message >>>>>>>>>" . json_encode($reminderDetails));

        if (!empty($reminderDetails)) {
            return [
                'is_reminder_set' => true,
                'remind_date' => $reminderDetails[0]['wb_cus_remind_date'] ?? null  // Use new variable name
            ];
        }

        return [
            'is_reminder_set' => false,
            'remind_date' => null
        ];
    }


    public function reminderAlreadySetMessage($contact, $msg_customer, $remind_date)
    {

        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $remind_date = $remind_date; // Retrieved from function
        $days_diff = null;
        $days_text = "";

        // Calculate days difference if a reminder date exists
        if ($remind_date) {
            $current_date = new DateTime(); // Today's date
            $reminder_date = new DateTime($remind_date); // Convert reminder date to DateTime
            $days_diff = $current_date->diff($reminder_date)->days; // Get difference in days

            // Add day(s) text
            $days_text = " (" . $days_diff . " days from now)";
        }

        $message_body = "⏳ You’ve already set a reminder for *" . $remind_date . "*" . $days_text . ".  
If you’d like to update it, feel free to reach out! 😊📲";


        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $contact['wa_id'],
            "type" => "text",
            "text" => array(
                "body" => $message_body
            )

        );
        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {

                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $message_body,
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $message_body,
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }

    public function oneYearPickupAndDropoff($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $contact['wa_id'],
            "type" => "interactive",
            "interactive" => array(
                "type" => "button",
                "body" => array(
                    "text" => "Please select one of the following options:"
                ),
                "footer" => array(
                    "text" => "Tap an option to respond."
                ),
                "action" => array(
                    "buttons" => array(
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "Free_Pick_&_Drop",
                                "title" => "🚗 Free Pick & Drop"
                            )
                        ),
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "Drop-off",
                                "title" => "📍 Drop-off (self)"
                            )
                        ),
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "Paid_Pick_&_Drop",
                                "title" => "🚗 Paid Pick & Drop"
                            )
                        ),
                    )
                )
            )
        );

        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

        // log_message('error',  "I am here at button pickupAndDropoff >>>>>" . json_encode($returnMsg));

        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {
                $bodyText = $messageData['interactive']['body']['text'];
                $buttons = $messageData['interactive']['action']['buttons'];

                $buttonTexts = [];
                foreach ($buttons as $button) {
                    $buttonTexts[] = $button['reply']['title'];
                }

                // Format the message with a line break before options
                $formattedText = $bodyText . "\n\n" . implode("\n", $buttonTexts);
                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $formattedText,
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $formattedText,
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }

    public function isCustomerWithin50KM($contact, $msg_customer, $location)
    {

        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $decodedLocation = json_decode($location, true);

        if (is_array($decodedLocation) && isset($decodedLocation['lat'], $decodedLocation['lng'])) {
            $lessthan50Km = $this->isWithinDubai((float) $decodedLocation['lat'], (float) $decodedLocation['lng']);
        } else {
            $lessthan50Km = false;
        }

        if ($lessthan50Km) {
            $this->getCustomerAppointmentDate($contact, $msg_customer);
        } else {
            $messageData = array(
                "messaging_product" => "whatsapp",
                "recipient_type" => "individual",
                "to" => $contact['wa_id'],
                "type" => "interactive",
                "interactive" => array(
                    "type" => "button",
                    "body" => array(
                        "text" => "🚗 Free pickup & drop-off is not available. Would you like to continue with the paid service? 💰"
                    ),
                    "action" => array(
                        "buttons" => array(
                            array(
                                "type" => "reply",
                                "reply" => array(
                                    "id" => "yes_paid",
                                    "title" => "✅ Yes, Continue"
                                )
                            ),
                            array(
                                "type" => "reply",
                                "reply" => array(
                                    "id" => "no_cancel",
                                    "title" => "❌ No, Cancel"
                                )
                            )
                        )
                    )
                )
            );

            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

            // log_message('error',  "I am here at button Escalte to Our Representative >>>>>" . json_encode($returnMsg));

            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $bodyText = $messageData['interactive']['body']['text'];
                    $buttons = $messageData['interactive']['action']['buttons'];

                    $buttonTexts = [];
                    foreach ($buttons as $button) {
                        $buttonTexts[] = $button['reply']['title'];
                    }

                    // Format the message with a line break before options
                    $formattedText = $bodyText . "\n\n" . implode("\n", $buttonTexts);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_content' => $formattedText,
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => 1,
                    ];
                    $result = $wb_message->insert($message_data);
                    $data = [
                        'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                        'message_type' => 4,
                        'message_source' => 2,
                        'message' => $formattedText,
                        'time' => date('Y-m-d H:i:s'),
                        'toUserId' => 1,
                        'fromUserId' => $msg_customer
                    ];
                    $this->sendSocketMessage($data);
                    $mobile = $msg_customer['wb_cus_mobile'];
                    $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                    if ($currentFollowUp) {
                        $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                        if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                            $followUpCount++;
                        } else if ($followUpCount == 3) {
                            $followUpCount = 6;
                        } else if ($followUpCount == 1) {
                            $followUpCount = 2;
                        }
                        $tracker_data = [
                            'wb_cus_follow_up' => $followUpCount,
                            'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                        ];
                        $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                    }
                }
            }
        }
    }

    public function notInterestedOption($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $contact['wa_id'],
            "type" => "interactive",
            "interactive" => array(
                "type" => "button",
                "body" => array(
                    "text" => "Your feedback matters please choose a reason:"
                ),
                "footer" => array(
                    "text" => "Tap an option to respond."
                ),
                "action" => array(
                    "buttons" => array(
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "Vehicle-Sold",
                                "title" => "🚘 Vehicle Sold"
                            )
                        ),
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "Disappointed",
                                "title" => "😞 Disappointed"
                            )
                        ),
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "others-not-interest",
                                "title" => "❓ Others"
                            )
                        )
                    )
                )
            )
        );

        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

        // log_message('error',  "I am here at button pickupAndDropoff >>>>>" . json_encode($returnMsg));

        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {
                $bodyText = $messageData['interactive']['body']['text'];
                $buttons = $messageData['interactive']['action']['buttons'];

                $buttonTexts = [];
                foreach ($buttons as $button) {
                    $buttonTexts[] = $button['reply']['title'];
                }

                // Format the message with a line break before options
                $formattedText = $bodyText . "\n\n" . implode("\n", $buttonTexts);
                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $formattedText,
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $formattedText,
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }


    public function isWithinDubai($latitude, $longitude)
    {
        // Approximate boundary of Dubai
        $minLat = 24.8;
        $maxLat = 25.4;
        $minLng = 54.8;
        $maxLng = 55.6;

        return ($latitude >= $minLat && $latitude <= $maxLat) &&
            ($longitude >= $minLng && $longitude <= $maxLng);
    }


    public function oneYearExceededPickupAndDropoff($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $contact['wa_id'],
            "type" => "interactive",
            "interactive" => array(
                "type" => "button",
                "body" => array(
                    "text" => "Please select one of the following options:"
                ),
                "footer" => array(
                    "text" => "Tap an option to respond."
                ),
                "action" => array(
                    "buttons" => array(
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "one_Free_Pick_&_Drop",
                                "title" => "🚗 Free Pick & Drop"
                            )
                        ),
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "Drop-off",
                                "title" => "📍 Drop-off (self)"
                            )
                        ),
                    )
                )
            )
        );

        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

        // log_message('error',  "I am here at button pickupAndDropoff >>>>>" . json_encode($returnMsg));

        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {
                $bodyText = $messageData['interactive']['body']['text'];
                $buttons = $messageData['interactive']['action']['buttons'];

                $buttonTexts = [];
                foreach ($buttons as $button) {
                    $buttonTexts[] = $button['reply']['title'];
                }

                // Format the message with a line break before options
                $formattedText = $bodyText . "\n\n" . implode("\n", $buttonTexts);
                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $formattedText,
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $formattedText,
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }

    public function oneYearExceededRemindMeLater($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();


        $result = $this->checkCustomerHasAppointment($msg_customer);

        $last_lead = $result['last_lead'];
        $isAppointment = $result['isAppointment'];

        if (!$isAppointment) {
            $messageData = array(
                "messaging_product" => "whatsapp",
                "recipient_type" => "individual",
                "to" => $contact['wa_id'],
                "type" => "interactive",
                "interactive" => array(
                    "type" => "list",
                    "body" => array(
                        "text" => "Next Reminder Date:"
                    ),
                    "footer" => array(
                        "text" => "Tap an option to respond."
                    ),
                    "action" => array(
                        "button" => "Choose Duration",
                        "sections" => array(
                            array(
                                "title" => "Available Durations",
                                "rows" => array(
                                    array(
                                        "id" => "1YAF_7",
                                        "title" => "⏰ 7 Days",
                                        // "description" => "Reminder in 7 days"
                                    ),
                                    array(
                                        "id" => "1YAF_14",
                                        "title" => "⏰ 14 Days",
                                        // "description" => "Reminder in 14 days"
                                    ),
                                    array(
                                        "id" => "1YAF_21",
                                        "title" => "⏰ 21 Days",
                                        // "description" => "Reminder in 21 days"
                                    ),
                                    array(
                                        "id" => "1YAF_30",
                                        "title" => "🕰️ 30 Days",
                                        // "description" => "Reminder in 30 days"
                                    )
                                )
                            )
                        )
                    )
                )
            );


            $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

            // log_message('error',  "I am here at button Remind Me Later >>>>>" . json_encode($returnMsg));

            if (isset($returnMsg->messages)) {
                if ($returnMsg->messages[0]->id != "") {
                    $bodyText = $messageData['interactive']['body']['text'];
                    $sections = $messageData['interactive']['action']['sections'];

                    $reminderOptions = [];

                    // Extract list options correctly
                    foreach ($sections as $section) {
                        foreach ($section['rows'] as $row) {
                            $reminderOptions[] = $row['title'];
                        }
                    }

                    // Format the message with a line break before options
                    $formattedText = $bodyText . "\n\n" . implode("\n", $reminderOptions);
                    $message_data = [
                        'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                        'alm_wb_msg_source' => 2,
                        'alm_wb_msg_type' => 4,
                        'alm_wb_msg_content' => $formattedText,
                        'alm_wb_msg_status' => 1,
                        'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                        'alm_wb_msg_reply_id' => '',
                        'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                        'alm_wb_msg_staff_id' => 1,
                    ];
                    $result = $wb_message->insert($message_data);
                    $data = [
                        'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                        'message_type' => 4,
                        'message_source' => 2,
                        'message' => $formattedText,
                        'time' => date('Y-m-d H:i:s'),
                        'toUserId' => 1,
                        'fromUserId' => $msg_customer
                    ];
                    $this->sendSocketMessage($data);
                    $mobile = $msg_customer['wb_cus_mobile'];
                    $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                    if ($currentFollowUp) {
                        $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                        if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                            $followUpCount++;
                        } else if ($followUpCount == 3) {
                            $followUpCount = 6;
                        } else if ($followUpCount == 1) {
                            $followUpCount = 2;
                        }
                        $tracker_data = [
                            'wb_cus_follow_up' => $followUpCount,
                            'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                        ];
                        $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                    }
                }
            }
        } else {
            $this->appointmentScheduledMessage($contact, $msg_customer, $last_lead);
        }
    }

    public function SendAwayButtonMessage($contact, $msg_customer)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $contact['wa_id'],
            "type" => "interactive",
            "interactive" => array(
                "type" => "button",
                "body" => array(
                    "text" => "Thank you for reaching out! We're currently unavailable, but we’ll get back to you soon.\nIn the meantime, please select an option below:"
                ),
                "footer" => array(
                    "text" => "Tap an option to respond."
                ),
                "action" => array(
                    "buttons" => array(
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "Book_An_Appointment",
                                "title" => "📅 Book Appointment"
                            )
                        ),
                        array(
                            "type" => "reply",
                            "reply" => array(
                                "id" => "chat_with_us",
                                "title" => "💬 Chat with Us"
                            )
                        )
                    )
                )
            )
        );

        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');

        // log_message('error',  "I am here at button pickupAndDropoff >>>>>" . json_encode($returnMsg));

        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {
                $bodyText = $messageData['interactive']['body']['text'];
                $buttons = $messageData['interactive']['action']['buttons'];

                $buttonTexts = [];
                foreach ($buttons as $button) {
                    $buttonTexts[] = $button['reply']['title'];
                }

                // Format the message with a line break before options
                $formattedText = $bodyText . "\n\n" . implode("\n", $buttonTexts);
                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => 4,
                    'alm_wb_msg_content' => $formattedText,
                    'alm_wb_msg_status' => 1,
                    'alm_wb_msg_customer' => $msg_customer['wb_cus_id'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $data = [
                    'room_id' => 'crm_' . $msg_customer['wb_cus_mobile'],
                    'message_type' => 4,
                    'message_source' => 2,
                    'message' => $formattedText,
                    'time' => date('Y-m-d H:i:s'),
                    'toUserId' => 1,
                    'fromUserId' => $msg_customer
                ];
                $this->sendSocketMessage($data);
                $mobile = $msg_customer['wb_cus_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
            }
        }
    }

    public function sendAwayMessageToCustomer($data)
    {
        $common = new Common();
        $wb_message = new WhatsappCustomerMessageModel();
        $wb_customer = new WhatsappCustomerMasterModel();

        $messageData = array(
            "messaging_product" => "whatsapp",
            "to" => $data['alm_wb_msg_mobile'],
            "type" => "text",
            'text' => [
                'body' => $data['alm_wb_msg_content']
            ]
        );
        $returnMsg = $common->sendCustomerWhatsappMessage($messageData, '971509766075');
        if (isset($returnMsg->messages)) {
            if ($returnMsg->messages[0]->id != "") {
                log_message('error', 'Webhook Error: ' . $returnMsg->messages[0]->id);
                $message_data = [
                    'alm_wb_msg_master_id' => $returnMsg->messages[0]->id,
                    'alm_wb_msg_source' => 2,
                    'alm_wb_msg_type' => $data['alm_wb_msg_type'],
                    'alm_wb_msg_content' => $data['alm_wb_msg_content'],
                    'alm_wb_msg_status' => $data['alm_wb_msg_status'],
                    'alm_wb_msg_customer' => $data['alm_wb_msg_customer'],
                    'alm_wb_msg_reply_id' => '',
                    'alm_wb_msg_created_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_updated_on' => date("Y-m-d H:i:s.u"),
                    'alm_wb_msg_staff_id' => 1,
                ];
                $result = $wb_message->insert($message_data);
                $mobile = $data['alm_wb_msg_mobile'];
                $currentFollowUp = $wb_customer->where('wb_cus_mobile', $mobile)->select('wb_cus_follow_up')->first();
                if ($currentFollowUp) {
                    $followUpCount = $currentFollowUp['wb_cus_follow_up'];
                    if ($followUpCount < 3 && $followUpCount != 1 && $followUpCount != 0) {
                        $followUpCount++;
                    } else if ($followUpCount == 3) {
                        $followUpCount = 6;
                    } else if ($followUpCount == 1) {
                        $followUpCount = 2;
                    }
                    $tracker_data = [
                        'wb_cus_follow_up' => $followUpCount,
                        'wb_cus_follow_up_time' => date('Y-m-d H-i-s'),
                    ];
                    $wb_customer->where('wb_cus_mobile', $mobile)->set($tracker_data)->update();
                }
                if ($result) {
                    $response = [
                        'ret_data' => 'success',
                    ];
                    return $this->respond($response, 200);
                } else {
                    $response = [
                        'ret_data' => 'fail',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
        } else {
            $response = [
                'ret_data' => $returnMsg,
            ];
            return $this->respond($response, 200);
        }
    }

    public function updateWhatsappAutoMessageHours()
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
            $builder = $this->db->table('sequence_data');
            $builder->set('whatsapp_auto_msg_hours', $this->db->escapeString($this->request->getVar('whatsapp_auto_msg_hours')));
            $builder->update();

            $response = [
                'ret_data' => 'success',
            ];
            return $this->respond($response, 200);
        }
    }
}
