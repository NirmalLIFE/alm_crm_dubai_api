<?php

namespace App\Controllers\YeaStar;

use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\UserModel;

class YeaStarController extends ResourceController
{

    private $db;
    public function __construct()
    {
        $third_DB =  \Config\Database::connect('secondary');
    }
    public function getCDRDetails()
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
            $call_type = $this->request->getVar('call_type');
            $start_day = $this->request->getVar('start_day');
            $end_day = $this->request->getVar('end_day');
            $disposition = $this->request->getVar('disposition');
            $call_to = $this->request->getVar('call_to');
            $call_from = $this->request->getVar('call_from');
            $selected_trunk = $this->request->getVar('selected_trunk');
            if (isset($call_type)) {
                $fields['call_type'] = $call_type;
                if (isset($start_day) && isset($end_day)) {
                    $fields['start_day'] = $start_day;
                    $fields['end_day'] = $end_day;
                }
                if (isset($call_to)) {
                    $fields['call_to'] = $call_to;
                }
                if (isset($call_from)) {
                    $fields['call_from'] = $call_from;
                }
                if (isset($disposition)) {
                    $fields['disposition'] = $disposition;
                }



                //start 
              
        
                $third_DB =  \Config\Database::connect('secondary');
                
                if (isset($call_type)) {
                    $sql = "select id,datetime,timestamp,uid,src,dst,srctrunk,dsttrunk,duration,ringduration,talkduration,disposition,calltype,uniqueid from cdr.cdr where displayonweb=1";
                    if ($call_type == "All") {
                        $sql = $sql . " and calltype!='Internal'";
                    } else {
                        $sql = $sql . ' and calltype=' . $third_DB->escape($call_type);
                    }
                    if (isset($start_day) && isset($end_day)) {
                        $sql = $sql . ' and datetime between ' . $third_DB->escape($start_day) . ' and ' . $third_DB->escape($end_day);
                    }
                    if (isset($disposition)) {
                        $sql = $sql . ' and disposition=' . $third_DB->escape($disposition);
                    }
                    if (isset($call_to)) {
                        $sql = $sql . ' and dst=' . $third_DB->escape($call_to);
                    }
                    if (isset($call_from)) {
                        $sql = $sql . ' and src=' . $third_DB->escape($call_from);
                    }
                    if (isset($selected_trunk)) {
                        $sql = $sql . ' and srctrunk in (' . $third_DB->escape($selected_trunk) . ')';
                    }
                    $sql = $sql . " order by datetime desc";
                    $maindata =  $third_DB->query($sql);
                    
                    if ($maindata->getNumRows() > 0) {
                        $result['call_data'] = $maindata->getResultArray();
                    } else {
                        $result['call_data'] = [];
                    }
        
                    $result['ret_data'] = "success";
                } else {
                    $result['call_data'] = [];
                    $result['ret_data'] = "fail";
                }
                return $this->respond($result, 200);
                //end
                // $fields = array(
                //     'inv_no' => $inv_no,
                //     'branchcode' =>  $branchcode,
                // );

                // $fields = json_encode($fields);
                // $ch = curl_init();
                // curl_setopt($ch, CURLOPT_URL, "http://almaraghi.fortiddns.com:35147/maraghi_lead_connection/index.php/DataFetch/getLatestCallReportData");
                // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                //     'Content-Type: application/json; charset=utf-8'
                // ));
                // curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                // curl_setopt($ch, CURLOPT_HEADER, FALSE);
                // curl_setopt($ch, CURLOPT_POST, TRUE);
                // curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

                // $curlResponse = json_decode(curl_exec($ch));
                // $response['call_data'] = $curlResponse->call_data;


            //     $response['ret_data'] = 'success';
            //     curl_close($ch);
            // } else {
            //     $response['call_data'] = [];
            //     $response['ret_data'] = 'fail';
            // }

        }
    }
    }

    public function getCDRDetailsByNumber()
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

            $phoneNumber = $this->request->getVar('phoneNumber');
            $mobilenumber = $this->request->getVar('mobile');
            $start_day = $this->request->getVar('start_day');
            $end_day = $this->request->getVar('end_day');
            $call_type = $this->request->getVar('call_type');


            $disposition = $this->request->getVar('disposition');
            $call_to = $this->request->getVar('call_to');
            $call_from = $this->request->getVar('call_from');
            $selected_trunk = $this->request->getVar('selected_trunk');
            if (isset($phoneNumber)) {
                $fields['phoneNumber'] = $phoneNumber;
                if (isset($mobilenumber)) {
                    $fields['phoneNumber'] = $mobilenumber;
                }
                if (isset($start_day) && isset($end_day)) {
                    $fields['start_day'] = $start_day;
                    $fields['end_day'] = $end_day;
                }
                if (isset($call_type)) {
                    $fields['call_type'] = $call_type;
                }
                // $fields = array(
                //     'inv_no' => $inv_no,
                //     'branchcode' =>  $branchcode,
                // );

            //     $fields = json_encode($fields);
            //     $ch = curl_init();
            //     curl_setopt($ch, CURLOPT_URL, "http://almaraghi.fortiddns.com:35147/maraghi_lead_connection/index.php/DataFetch/getLatestCallReportByNumber");
            //     curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            //         'Content-Type: application/json; charset=utf-8'
            //     ));
            //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            //     curl_setopt($ch, CURLOPT_HEADER, FALSE);
            //     curl_setopt($ch, CURLOPT_POST, TRUE);
            //     curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            //     $curlResponse = json_decode(curl_exec($ch));
            //     $response['call_data'] = $curlResponse->call_data;
            //     $response['ret_data'] = 'success';
            //     curl_close($ch);
            // } else {
            //     $response['call_data'] = [];
            //     $response['ret_data'] = 'fail';
            // }


            //start

            if (isset($phoneNumber)) {
                // $third_DB = $this->load->database('yeaStar', TRUE);
               
                $sql = "select id,datetime,timestamp,uid,src,dst,srctrunk,dsttrunk,duration,ringduration,talkduration,disposition,calltype,uniqueid from cdr.cdr where displayonweb=1 and calltype!='Internal'";
    
                if (isset($start_day) && isset($end_day)) {
                    $sql = $sql . ' and datetime between ' . $third_DB->escape($start_day) . ' and ' . $third_DB->escape($end_day);
                }
                if (isset($disposition)) {
                    $sql = $sql . ' and disposition=' . $third_DB->escape($disposition);
                }
                $sql = $sql . ' and (src=' . $third_DB->escape($phoneNumber) . ' or dst=' . $third_DB->escape($phoneNumber) . ')';
                $sql = $sql . " order by datetime desc limit 1000";
                $maindata =  $third_DB->query($sql);
                if ($maindata->getNumRows() > 0) {
                    $result['call_data'] = $maindata->getResultArray();
                } else {
                    $result['call_data'] = [];
                }
    
                $result['ret_data'] = "success";
            } else {
                $result['call_data'] = [];
                $result['ret_data'] = "fail";
            }
            //end

            return $this->respond($result, 200);
        }
    }
    }


    public function getCDRInboundByNumberlist()
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

            $inboundcalldata = []; // Array to store customer call data
            $start_day = $this->request->getVar('start_day');
            $end_day = $this->request->getVar('end_day');
            $call_type = $this->request->getVar('call_type');
            // foreach ($this->request->getVar('customers') as $eachphone) {
            // }
            $fields = [];
            $fields['phoneNumber'] = $this->request->getVar('customers');
            if (isset($call_type)) {
                $fields['call_type'] = $call_type;
                if (isset($start_day) && isset($end_day)) {
                    $fields['start_day'] = $start_day;
                    $fields['end_day'] = $end_day;
                }

                if (isset($call_type)) {
                    $fields['call_type'] = $call_type;
                }
            }

            // $fields = json_encode($fields);

            // $ch = curl_init();
            // curl_setopt($ch, CURLOPT_URL, "http://almaraghi.fortiddns.com:35147/maraghi_lead_connection/index.php/DataFetch/getLatestCallReportByNumberList");
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            //     'Content-Type: application/json; charset=utf-8'
            // ));
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            // curl_setopt($ch, CURLOPT_HEADER, FALSE);
            // curl_setopt($ch, CURLOPT_POST, TRUE);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            // $curlResponse = json_decode(curl_exec($ch));
            // curl_close($ch);

            // if ($curlResponse->call_data && sizeof($curlResponse->call_data) > 0) {
            //     $inboundcalldata = array_merge($inboundcalldata, $curlResponse->call_data);
            // }
            // $response = [];

            // if (sizeof($inboundcalldata) > 0) {
            //     $response['customer'] = $inboundcalldata;
            //     $response['ret_data'] = 'success';
            // } else {
            //     $response['customer'] = [];
            //     $response['ret_data'] = 'fail';
            // }


            //start
            if (isset($phoneNumberList) && sizeof($phoneNumberList) > 0) {
                $result['call_data'] = [];
                foreach ($phoneNumberList as $phoneNumber) {
                    $third_DB =  \Config\Database::connect('secondary');
                    $sql = "select id,datetime,timestamp,uid,src,dst,srctrunk,dsttrunk,duration,ringduration,talkduration,disposition,calltype,uniqueid from cdr.cdr where displayonweb=1 and calltype!='Internal'";
    
                    if (isset($start_day) && isset($end_day)) {
                        $sql = $sql . ' and datetime between ' . $third_DB->escape($start_day) . ' and ' . $third_DB->escape($end_day);
                    }
                    if (isset($disposition)) {
                        $sql = $sql . ' and disposition=' . $third_DB->escape($disposition);
                    }
                    $sql = $sql . ' and (src=' . $third_DB->escape($phoneNumber) . ' or dst=' . $third_DB->escape($phoneNumber) . ')';
                    $sql = $sql . " order by datetime desc limit 1000";
                    $maindata =  $third_DB->query($sql);
                    if ($maindata->getNumRows() > 0) {
                        $call_data = $maindata->getResultArray();
                        array_push($result['call_data'], $call_data);
                    }
                }
                $result['ret_data'] = "success";
            } else {
                $result['call_data'] = [];
                $result['ret_data'] = "fail";
            }

            //end

            return $this->respond($result, 200);
        }
    }

    public function getCDRByNumberlist()
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

            $inboundcalldata = []; // Array to store customer call data
            $start_day = $this->request->getVar('start_day');
            $end_day = $this->request->getVar('end_day');
            $call_type = $this->request->getVar('call_type');
            // foreach ($this->request->getVar('customers') as $eachphone) {
            // }
            $fields = [];
            $fields['phoneNumber'] = $this->request->getVar('customers');
            if (isset($start_day) && isset($end_day)) {
                $fields['start_day'] = $start_day;
                $fields['end_day'] = $end_day;
            }
            // $fields = json_encode($fields);

            // $ch = curl_init();
            // curl_setopt($ch, CURLOPT_URL, "http://almaraghi.fortiddns.com:35147/maraghi_lead_connection/index.php/DataFetch/getLatestCallReportByNumberList");
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            //     'Content-Type: application/json; charset=utf-8'
            // ));
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            // curl_setopt($ch, CURLOPT_HEADER, FALSE);
            // curl_setopt($ch, CURLOPT_POST, TRUE);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            // $curlResponse = json_decode(curl_exec($ch));
            // curl_close($ch);

            // if ($curlResponse->call_data && sizeof($curlResponse->call_data) > 0) {
            //     $inboundcalldata = array_merge($inboundcalldata, $curlResponse->call_data);
            // }
            // $response = [];

            // if (sizeof($inboundcalldata) > 0) {
            //     $response['customer'] = $inboundcalldata;
            //     $response['ret_data'] = 'success';
            // } else {
            //     $response['customer'] = [];
            //     $response['ret_data'] = 'fail';
            // }

            //start

            if (isset($phoneNumberList) && sizeof($phoneNumberList) > 0) {
                $result['call_data'] = [];
                foreach ($phoneNumberList as $phoneNumber) {
                   
                    $sql = "select id,datetime,timestamp,uid,src,dst,srctrunk,dsttrunk,duration,ringduration,talkduration,disposition,calltype,uniqueid from cdr.cdr where displayonweb=1 and calltype!='Internal'";
    
                    if (isset($start_day) && isset($end_day)) {
                        $sql = $sql . ' and datetime between ' . $third_DB->escape($start_day) . ' and ' . $third_DB->escape($end_day);
                    }
                    if (isset($disposition)) {
                        $sql = $sql . ' and disposition=' . $third_DB->escape($disposition);
                    }
                    $sql = $sql . ' and (src=' . $third_DB->escape($phoneNumber) . ' or dst=' . $third_DB->escape($phoneNumber) . ')';
                    $sql = $sql . " order by datetime desc limit 1000";
                    $maindata =  $third_DB->query($sql);
                    if ($maindata->getNumRows() > 0) {
                        $call_data = $maindata->getResultArray();
                        array_push($result['call_data'], $call_data);
                    }
                }
                $result['ret_data'] = "success";
            } else {
                $result['call_data'] = [];
                $result['ret_data'] = "fail";
            }
            //end

            return $this->respond($result, 200);
        }
    }

    public function getCDRInboundByNumberlistByMonth()
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

            $month = $this->request->getVar('month');
            $call_type = $this->request->getVar('calltype');
            $selected_trunk = $this->request->getVar('srctrunk');
            $customers = $this->request->getvar('customers');

            $inboundcalldata = [];


            $fields = [];
            $fields['phoneNumber'] = $this->request->getVar('customers');
            if (isset($call_type)) {
                $fields['call_type'] = $call_type;
                if (isset($start_day) && isset($end_day)) {
                    $fields['start_day'] = $start_day;
                    $fields['end_day'] = $end_day;
                }

                if (isset($call_type)) {
                    $fields['call_type'] = $call_type;
                }
            }
            // $fields = json_encode($fields);
            // $ch = curl_init();
            // curl_setopt($ch, CURLOPT_URL, "http://almaraghi.fortiddns.com:35147/maraghi_lead_connection/index.php/DataFetch/getLatestCallReportByNumberList");
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            //     'Content-Type: application/json; charset=utf-8'
            // ));
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            // curl_setopt($ch, CURLOPT_HEADER, FALSE);
            // curl_setopt($ch, CURLOPT_POST, TRUE);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            // $curlResponse = json_decode(curl_exec($ch));
            // curl_close($ch);
            // if ($curlResponse->call_data && sizeof($curlResponse->call_data) > 0) {
            //     $inboundcalldata = array_merge($inboundcalldata, $curlResponse->call_data);
            // }


            // //  }

            // if (sizeof($inboundcalldata) > 0) {
            //     $response['customer'] = $inboundcalldata;
            //     $response['ret_data'] = 'success';
            // } else {
            //     $response['call_data'] = [];
            //     $response['ret_data'] = 'fail';
            // }


            //start

            if (isset($phoneNumberList) && sizeof($phoneNumberList) > 0) {
                $result['call_data'] = [];
                foreach ($phoneNumberList as $phoneNumber) {
              
                    $sql = "select id,datetime,timestamp,uid,src,dst,srctrunk,dsttrunk,duration,ringduration,talkduration,disposition,calltype,uniqueid from cdr.cdr where displayonweb=1 and calltype!='Internal'";
    
                    if (isset($start_day) && isset($end_day)) {
                        $sql = $sql . ' and datetime between ' . $third_DB->escape($start_day) . ' and ' . $third_DB->escape($end_day);
                    }
                    if (isset($disposition)) {
                        $sql = $sql . ' and disposition=' . $third_DB->escape($disposition);
                    }
                    $sql = $sql . ' and (src=' . $third_DB->escape($phoneNumber) . ' or dst=' . $third_DB->escape($phoneNumber) . ')';
                    $sql = $sql . " order by datetime desc limit 1000";
                    $maindata =  $third_DB->query($sql);
                    if ($maindata->getNumRows() > 0) {
                        $call_data = $maindata->getResultArray();
                        array_push($result['call_data'], $call_data);
                    }
                }
                $result['ret_data'] = "success";
            } else {
                $result['call_data'] = [];
                $result['ret_data'] = "fail";
            }
            //end

            return $this->respond($result, 200);
        }
    }

    public function getOutboundCalls()
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
            //$customers = $this->request->getvar('customers');
            //  $call_type =$this->request->getvar('call_type');
            $call_to = $this->request->getVar('call_to');
            $start_day = $this->request->getVar('start_day');
            $end_day = $this->request->getVar('end_day');

            $outboundcalldata = [];

            // $fields['phoneNumber'] = $this->request->getVar('call_to');
            // if (isset($start_day) && isset($end_day)) {
            //     $fields['start_day'] = $start_day;
            //     $fields['end_day'] = $end_day;
            // }
            // $fields = json_encode($fields);
            // $ch = curl_init();
            // curl_setopt($ch, CURLOPT_URL, "http://almaraghi.fortiddns.com:35147/maraghi_lead_connection/index.php/DataFetch/getLatestCallReportByNumberList");
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            //     'Content-Type: application/json; charset=utf-8'
            // ));
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            // curl_setopt($ch, CURLOPT_HEADER, FALSE);
            // curl_setopt($ch, CURLOPT_POST, TRUE);
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            // $curlResponse = json_decode(curl_exec($ch));
            // curl_close($ch);

            // if ($curlResponse->call_data && sizeof($curlResponse->call_data) > 0) {
            //     $outboundcalldata = array_merge($outboundcalldata, $curlResponse->call_data);
            // }


            // if (sizeof($outboundcalldata) > 0) {
            //     $response['customer'] = $outboundcalldata;
            //     $response['ret_data'] = 'success';
            // } else {
            //     $response['call_data'] = [];
            //     $response['ret_data'] = 'fail';
            // }

            //start
            if (isset($phoneNumberList) && sizeof($phoneNumberList) > 0) {
                $result['call_data'] = [];
                foreach ($phoneNumberList as $phoneNumber) {
                 
                    $sql = "select id,datetime,timestamp,uid,src,dst,srctrunk,dsttrunk,duration,ringduration,talkduration,disposition,calltype,uniqueid from cdr.cdr where displayonweb=1 and calltype!='Internal'";
    
                    if (isset($start_day) && isset($end_day)) {
                        $sql = $sql . ' and datetime between ' . $third_DB->escape($start_day) . ' and ' . $third_DB->escape($end_day);
                    }
                    if (isset($disposition)) {
                        $sql = $sql . ' and disposition=' . $third_DB->escape($disposition);
                    }
                    $sql = $sql . ' and (src=' . $third_DB->escape($phoneNumber) . ' or dst=' . $third_DB->escape($phoneNumber) . ')';
                    $sql = $sql . " order by datetime desc limit 1000";
                    $maindata =  $third_DB->query($sql);
                    if ($maindata->getNumRows() > 0) {
                        $call_data = $maindata->getResultArray();
                        array_push($result['call_data'], $call_data);
                    }
                }
                $result['ret_data'] = "success";
            } else {
                $result['call_data'] = [];
                $result['ret_data'] = "fail";
            }
            //end

            return $this->respond($result, 200);
        }
    }
}
