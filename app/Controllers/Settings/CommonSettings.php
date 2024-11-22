<?php

namespace App\Controllers\Settings;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Settings\CommonSettingsModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;
use App\Models\Commonutils\YeastarKeysModel;
use App\Models\Commonutils\PartsInvoiceMaster;
use App\Models\Commonutils\PartsInvoiceItems;
use App\Models\Commonutils\PartsInvoiceLog;
use App\Models\Settings\CompanyHolidayModel;
use App\Models\PSFModule\PSFAssignedStaffModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Customer\MaraghiVehicleModel;
use App\Models\Settings\SparePartsMarginModel;
use App\Models\Customer\MaraghiJobcardModel;
use App\Models\Commonutils\SparePartsSuppliers;
use App\Models\PSFModule\PSFCallHistoryModel;
use App\Models\Commonutils\AniversaryCustomerModel;
use App\Models\Customer\MaraghiJobModel;
use App\Models\Customer\JobSubStatusModel;
use App\Models\Customer\JobSubStatusTrackerModel;
use App\Models\User\UserRoleMarginLimitModel;




use App\Models\Settings\WhatsappMessageModel;

class CommonSettings extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    public function addVerificationNumber()
    {
        $model = new CommonSettingsModel();
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

            $rules = [
                'number' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'verification_number' => $this->request->getVar('number'),
            ];
            if ($model->where('cst_id', 1)->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            } else {
                $this->insertUserLog('New Verification Number Added ', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];

                return $this->respond($response, 200);
            }
        }
    }
    public function getCommonSettings()
    {
        $model = new CommonSettingsModel();
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
            $res = $model->first();
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'settings' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'settings' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function changeLandlineStatus()
    {
        $model = new CommonSettingsModel();
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

            // $rules = [
            //     'instatus'=>'required',
            // ];
            // if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());

            $updata = [
                'landline_include_status' => $this->db->escapeString($this->request->getVar('instatus')),
            ];

            $results = $model->where('cst_id', 1)->set($updata)->update();
            if ($results) {
                $this->insertUserLog('Change Landline Include Status ', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'status' => $this->request->getVar('instatus')
                ];

                return $this->respond($response, 200);
            }
        }
    }


    public function addWorkingTime()
    {
        $model = new CommonSettingsModel();
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

            $rules = [
                'starttime' => 'required',
                'endtime' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'working_time_start' => $this->db->escapeString($this->request->getVar('starttime')),
                'working_time_end' => $this->db->escapeString($this->request->getVar('endtime')),
            ];
            if ($model->where('cst_id', 1)->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            } else {
                $this->insertUserLog('New Working Time Added ', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];

                return $this->respond($response, 200);
            }
        }
    }


    public function insertUserLog($log, $uid)
    {
        $logmodel = new UserActivityLog();
        $ip = $this->request->getIPAddress();
        $indata = [
            'log_user'    =>  $uid,
            'log_ip'   =>  $ip,
            'log_activity' => $log
        ];
        $results = $logmodel->insert($indata);
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

    public function yeastarAccessTokenUpdate()
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
            $yeastar = new YeastarKeysModel();
            $keydetails = $yeastar->where("key_id", 1)->first();
            if ($keydetails['yeastar_user'] != "" && $keydetails['yeastar_user'] != null) {
                date_default_timezone_set('Asia/Dubai');
                $datetime1 = date_create($keydetails['yeastar_token_time']);
                $datetime2 = date('Y-m-d H:i:s');
                $difference = $datetime1->diff(date_create($datetime2));

                if ($difference->d > 0) {
                    $postData = [
                        'username' => base64_decode(base64_decode(base64_decode($keydetails['yeastar_user']))),
                        'password' => base64_decode(base64_decode(base64_decode($keydetails['yeastar_pass'])))
                    ];

                    // $url = 'https://almaragy.ras.yeastar.com/openapi/v1.0/get_token';
                    $url = 'https://almaraghidxb.ras.yeastar.com/openapi/v1.0/get_token';
                    $ch = curl_init($url);
                    $headers = [
                        'Content-Type: text/plain',
                        'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3'
                    ];
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                    $result = json_decode(curl_exec($ch));
                    curl_close($ch);
                    if ($result->errmsg == "SUCCESS") {
                        $data = [
                            'yeastar_token' => base64_encode(base64_encode(base64_encode($result->access_token))),
                            'yeastar_refresh_token' => base64_encode(base64_encode(base64_encode($result->refresh_token))),
                            'yeastar_token_time' => $datetime2
                        ];
                        $yeastar->where('key_id', 1)->set($data)->update();

                        return $this->respond("new", 200);
                    } else {
                        return $this->fail($result, 400);
                    }
                } else {
                    $postData = [
                        'refresh_token' => base64_decode(base64_decode(base64_decode($keydetails['yeastar_refresh_token']))),
                    ];
                    // $url = 'https://almaragy.ras.yeastar.com/openapi/v1.0/refresh_token';
                    $url = 'https://almaraghidxb.ras.yeastar.com/openapi/v1.0/refresh_token';
                    $ch = curl_init($url);
                    $headers = [
                        'Content-Type: text/plain',
                        'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 5_0 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/5.1 Mobile/9A334 Safari/7534.48.3'
                    ];
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                    $result = json_decode(curl_exec($ch));
                    curl_close($ch);
                    if ($result->errmsg == "SUCCESS") {
                        $data = [
                            'yeastar_token' => base64_encode(base64_encode(base64_encode($result->access_token))),
                            'yeastar_refresh_token' => base64_encode(base64_encode(base64_encode($result->refresh_token))),
                            'yeastar_token_time' => $datetime2
                        ];
                        $yeastar->where('key_id', 1)->set($data)->update();

                        return $this->respond("same", 200);
                    } else {
                        return $this->fail($result, 400);
                    }
                }


                // if(){}




                // echo json_encode($data);
                // var_dump($result);
                // die;
            }
            // return $this->respond($result, 200);
        }
    }
    public function addBufferTime()
    {
        $model = new CommonSettingsModel();
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

            $rules = [
                'bTime' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            //  $time = "0:".$this->db->escapeString($this->request->getVar('bTime')).":00";
            $time = $this->db->escapeString($this->request->getVar('bTime'));
            $data = [
                'mis_buffer_time' => $time,
            ];
            if ($model->where('cst_id', 1)->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            } else {
                $this->insertUserLog('Buffer TIme ', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];

                return $this->respond($response, 200);
            }
        }
    }

    public function addHoliday()
    {
        $model = new CompanyHolidayModel();
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

            $rules = [
                'start' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

            $data = [
                'chl_start' => strtotime($this->db->escapeString($this->request->getVar('start'))),
                'chl_end' => strtotime($this->db->escapeString($this->request->getVar('end'))),
                'chl_reason' => $this->request->getVar('reason'),
            ];




            if ($model->insert($data) === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            } else {
                $this->insertUserLog(' AddCompany Holiday ', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];

                return $this->respond($response, 200);
            }
        }
    }

    public function getHolidays()
    {
        $model = new CompanyHolidayModel();
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
            $res = $model->where('chl_delete_flag', 0)->select("chl_id,chl_reason,DATE_FORMAT(FROM_UNIXTIME(`chl_start`), '%e %b %Y') as startdate,DATE_FORMAT(FROM_UNIXTIME(`chl_end`), '%e %b %Y') as enddate,DATE_FORMAT(FROM_UNIXTIME(`chl_start`), '%Y-%m-%d') as hstart,DATE_FORMAT(FROM_UNIXTIME(`chl_end`), '%Y-%m-%d') as hend")->orderBy('chl_start')->findAll();

            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'holi' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'holi' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function deleteHoliday()
    {
        $model = new CompanyHolidayModel();
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
            $id = $this->db->escapeString($this->request->getVar('hl_id'));
            $data = [
                'chl_delete_flag' => 1,
            ];
            if ($model->where('chl_id', $id)->set($data)->update() === false) {
                $response = [
                    'ret_data' => 'fail',
                    'errors' => $model->errors(),
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function addWorkBufferTime()
    {
        $model = new CommonSettingsModel();
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

            $rules = [
                'wbTime' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            //  $time = "0:".$this->db->escapeString($this->request->getVar('bTime')).":00";
            $time = $this->db->escapeString($this->request->getVar('wbTime'));
            $dataa = [
                'workhour_mis_buffer_time' => $time,
            ];
            if ($model->where('cst_id', 1)->set($dataa)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            } else {
                $this->insertUserLog('Buffer TIme ', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];

                return $this->respond($response, 200);
            }
        }
    }
    public function getHoliday_report()
    {
        $model = new CompanyHolidayModel();
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
            $res = $model->where('chl_delete_flag', 0)->select("chl_start,chl_end,chl_id,chl_reason,DATE_FORMAT(FROM_UNIXTIME(`chl_start`), '%e %b %Y') as startdate,DATE_FORMAT(FROM_UNIXTIME(`chl_end`), '%e %b %Y') as enddate,DATE_FORMAT(FROM_UNIXTIME(`chl_start`), '%Y-%m-%d') as hstart,DATE_FORMAT(FROM_UNIXTIME(`chl_end`), '%Y-%m-%d') as hend")->orderBy('chl_start')->findAll();

            if ($res) {
                $array = array();
                foreach ($res as $re) {


                    for (
                        $currentDate = $re['chl_start'];
                        $currentDate <= $re['chl_end'];
                        $currentDate += (86400)
                    ) {

                        $Store = date('d/m/Y', $currentDate);
                        $array[] = $Store;
                    }
                }


                $response = [
                    'ret_data' => 'success',
                    'holi' => $res,
                    'dates' => array_unique($array)
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'holi' => [],
                    'dates' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function updaetMaxPSFDays()
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
        $rules = [
            'psf_allowed_days' => 'required',
            'type' => 'required'
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            if ($this->request->getVar('type') == '0') {
                $builder = $this->db->table('sequence_data');
                $builder->set('psf_allowed_days', $this->db->escapeString($this->request->getVar('psf_allowed_days')));
                $builder->update();
                $response = [
                    'ret_data' => 'success',
                ];
            } else if ($this->request->getVar('type') == '2') {
                $builder = $this->db->table('sequence_data');
                $builder->set('psf_feedback_assign_days', $this->db->escapeString($this->request->getVar('psf_feedback_assign_days')));
                $builder->update();
                $response = [
                    'ret_data' => 'success',
                ];
            } else if ($this->request->getVar('type') == '3') {
                $builder = $this->db->table('sequence_data');
                $builder->set('psf_feedback_assign_days_after_sa', $this->db->escapeString($this->request->getVar('psf_feedback_assign_days_after_sa')));
                $builder->update();
                $response = [
                    'ret_data' => 'success',
                ];
            } else {
                $builder = $this->db->table('sequence_data');
                $builder->set('psf_buffer_days', $this->db->escapeString($this->request->getVar('psf_buffer_days')));
                $builder->update();
                $response = [
                    'ret_data' => 'success',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function retrivePSFSettingsData()
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
            $builder->select('psf_allowed_days,psf_assign_type,psf_buffer_days,psf_feedback_assign_days,psf_feedback_assign_days_after_sa');
            $query = $builder->get();
            $row = $query->getRow();
            $psfStaff = new PSFAssignedStaffModel();
            $assigned_staff = $psfStaff->select('psfs_id,psfs_assigned_staff,psfs_psf_type,users.us_firstname,users.ext_number')
                ->where('psfs_delete_flag', 0)->join('users', 'users.us_id=psf_assigned_staffs.psfs_assigned_staff', 'left')->findAll();
            $response = [
                'ret_data' => 'success',
                'psf_data' => $row,
                'psf_staff' => $assigned_staff
            ];
            return $this->respond($response, 200);
        }
    }

    public function assignPSFStaff()
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
        $rules = [
            'psf_type' => 'required',
            'psf_assigned_staff' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $psfStaff = new PSFAssignedStaffModel();
            $data = [
                'psfs_assigned_staff' => $this->request->getVar('psf_assigned_staff'),
                'psfs_psf_type' => $this->request->getVar('psf_type'),
                'psfs_created_by' => $tokendata['uid'],
                'psfs_updated_by' => $tokendata['uid']
            ];
            $res = $psfStaff->insert($data);
            if ($res <= 0) {
                $response = [
                    'errors' => $psfStaff->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->fail($response, 409);
            } else {
                $response = [
                    'ret_data' => 'success',
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function removePSFStaff()
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
        $rules = [
            'psfs_id' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $psfStaff = new PSFAssignedStaffModel();
            $data = [
                'psfs_delete_flag' => 1,
                'psfs_updated_by' => $tokendata['uid']
            ];
            $res = $psfStaff->update($this->db->escapeString($this->request->getVar('psfs_id')), $data);
            if ($res <= 0) {
                $response = [
                    'errors' => $psfStaff->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->fail($response, 409);
            } else {
                $response = [
                    'ret_data' => 'success',
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function updatePSFMethod()
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
            $builder->set('psf_assign_type', $this->db->escapeString($this->request->getVar('psf_assign_type')));
            $ret = $builder->update();
            if ($ret) {
                $response = [
                    'ret_data' => 'success',
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getNMInvoiceDetails()
    {

        $validModel = new Validation();
        $commonutils = new Common();
        $heddata = $this->request->headers();
        $UserModel = new UserModel();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $user = $UserModel->where("us_id", $tokendata['uid'])->join('user_roles', 'user_roles.role_id=us_role_id')->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        $rules = [
            'inv_no' => 'required',
            'branchcode' => 'required'
        ];
        if (!$this->validate($rules))  return $this->fail($this->validator->getErrors());
        $nm_spare = $this->getNMSpares($this->request->getVar("inv_no"), $this->request->getVar("branchcode"));
        if ($nm_spare) {
            $data['ret_data'] = "success";
            $data['booking'] = json_decode($nm_spare);
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "fail";
            return $this->fail($data, 200);
        }
    }

    public function getNMSpares($inv_no, $branchcode)
    {
        $fields = array(
            'inv_no' => $inv_no,
            'branchcode' =>  $branchcode,
        );
        $fields = json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://nasermohsin.fortidyndns.com:35146/nm_spare_fetch/index.php/datafetch/getInvoiceDetails");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function getWIPTaskStatus()
    {
        $validModel = new Validation();
        $commonutils = new Common();
        $heddata = $this->request->headers();
        $UserModel = new UserModel();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $user = $UserModel->where("us_id", $tokendata['uid'])->join('user_roles', 'user_roles.role_id=us_role_id')->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        $rules = [
            'jobs' => 'required',
        ];
        if (!$this->validate($rules))  return $this->fail($this->validator->getErrors());
        $fields["jobs"] = $this->request->getVar('jobs');
        $fields = json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://almaraghi.fortiddns.com:35147/maraghi_lead_connection/index.php/DataFetch/getJobcardTasksLatestData");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $curlResponse = json_decode(curl_exec($ch));
        curl_close($ch);
        
        if ($curlResponse && sizeof($curlResponse) > 0) {
            $response = [
                'ret_data' => 'success',
                'job_data'=> $curlResponse
            ];
        } else {
            $response = [
                'ret_data' => 'success',
                'job_data'=>[]
            ];
        }
        return $this->respond( $response, 200);
    }

    public function updatePartsMargin()
    {
        $model = new CommonSettingsModel();
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

            if ($this->request->getVar('margin')) {

                $updata = [
                    'parts_margin' => $this->db->escapeString($this->request->getVar('margin')),
                ];

                $results = $model->where('cst_id', 1)->set($updata)->update();
                if ($results) {
                    $response = [
                        'ret_data' => 'success',
                    ];
                } else {
                    $response = [
                        'ret_data' => 'fail',
                    ];
                }
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }

            return $this->respond($response, 200);
        }
    }

    public function createSpareInvoice()
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
            $rules = [
                'invoice_purchase_type' => 'required',
                'invoice_type' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $invMaster = new PartsInvoiceMaster();
            $invItems = new PartsInvoiceItems();
            $invLog = new PartsInvoiceLog();
            $this->db->transStart();
            if (intval($this->request->getVar('invoice_type')) != 0) {
                $inv_nm_branch = $this->request->getVar('nm_branch') ?? 0;
                $inv_nm_discount = $this->request->getVar('nm_discount') ?? Null;
                $masterdata = [
                    'inv_nm_id' => $this->request->getVar('nm_invoice'),
                    'inv_nm_supplier_id' => $this->request->getVar('supplier_id'),
                    'inv_customer_id' => $this->request->getVar('customerCode'),
                    'inv_vehicle_id' => $this->request->getVar('vehicle_id'),
                    'inv_jobcard_no' => $this->request->getVar('jc_no'),
                    'inv_nm_branch' =>  $inv_nm_branch,
                    'inv_nm_sub_total' => $this->request->getVar('nm_sub_total'),
                    'inv_nm_vat_total' => $this->request->getVar('nm_vat_total'),
                    'inv_nm_discount' => $inv_nm_discount,
                    'inv_nm_inv_date' => $this->request->getVar('nm_inv_date'),
                    'inv_alm_margin_total' => $this->request->getVar('alm_margin_total'),
                    'inv_old_alm_margin_total' => $this->request->getVar('alm_margin_total'),
                    'inv_alm_discount' => $this->request->getVar('alm_discount'),
                    'inv_old_alm_discount' => $this->request->getVar('alm_discount'),
                    'inv_created_by' => $tokendata['uid'],
                    'inv_created_on' => date("Y-m-d H:i:s"),
                    'inv_updated_by' => $tokendata['uid'],
                    'inv_updated_on' => date("Y-m-d H:i:s"),
                    'inv_nm_status' => $this->request->getVar('invoice_status'),
                    'inv_nm_type' => $this->request->getVar('invoice_type'),
                    'inv_nm_purchase_type' => $this->request->getVar('invoice_purchase_type'),
                    'inv_nm_description' =>  $this->request->getVar('invoice_description'),
                ];
                $master = $invMaster->insert($masterdata);
                if ($master) {
                    $insdata = array();
                    foreach ($this->request->getVar('invoice_items') as $item) {
                        $insdata[] = array(
                            'inv_item_part_number' => $item->PART_NO,
                            'inv_item_master' => $master,
                            'inv_item_qty' => $item->ITEM_QTY,
                            'inv_item_nm_unit_price' => $item->UNIT_PRICE,
                            'inv_item_nm_vat' => $item->VAT_AMOUNT,
                            'inv_item_description' => $item->DESCRIPTION,
                            'inv_item_nm_discount' => $item->DISCOUNT_AMOUNT,
                            'inv_item_margin' => $item->margin_applied,
                            'inv_item_margin_amount' => $item->margin_total,
                            'inv_old_item_margin' => $item->margin_applied,
                            'inv_old_item_margin_amount' => $item->margin_total,
                        );
                    };
                    if (sizeof($insdata) > 0) {
                        $invItems->insertBatch($insdata);
                    }
                    $invlogdata = [
                        'inv_log_master_id' =>  $master,
                        'inv_log_note' => ($this->request->getVar('invoice_status') === '1') ? "Invoice Created" : "Invoice Created And Send To Admin Approval",
                        'inv_log_created_on' => date("Y-m-d H:i:s"),
                        'inv_log_created_by' => $tokendata['uid'],
                    ];
                    $log = $invLog->insert($invlogdata);

                    if ($this->db->transStatus() === false) {
                        $this->db->transRollback();
                        $data['ret_data'] = "fail";
                        return $this->respond($data, 200);
                    } else {
                        $this->db->transCommit();
                        $data['ret_data'] = "success";
                        return $this->respond($data, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail',
                    ];
                    return $this->respond($response, 200);
                }
            } else {
            }
        }
    }

    public function getSpareInvoices()
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
            $invMaster = new PartsInvoiceMaster();

            $checkStatus =  $this->request->getVar('checked');

            $supplierId = $this->request->getVar('supplier_id');
            $purchaseType = $this->request->getVar('purchaseType');
            $inv_type = $this->request->getVar('inv_type');
            $selectedSA = $this->request->getVar('selectedSA');
            $jbstatus = $this->request->getVar('jbstatus');
            $start_date = $this->request->getVar('start_date');
            $end_date = $this->request->getVar('end_date');

            if ($checkStatus == true) {
                $builder = $this->db->table('alm_spare_invoice_master');
                $builder->select('inv_id, inv_nm_id, inv_nm_supplier_id, inv_nm_description, inv_customer_id, inv_vehicle_id, inv_jobcard_no,
                inv_nm_status, inv_nm_type, inv_nm_purchase_type, inv_nm_branch, inv_nm_sub_total, inv_nm_vat_total,
                inv_nm_discount, inv_nm_inv_date, inv_alm_margin_total, inv_alm_discount, inv_created_by, inv_created_on,
                inv_updated_by, inv_updated_on, cvl.vehicle_id as veh_data_vehicle_id, model_name, reg_no, chassis_no,
                job_no, car_reg_no, cjl.vehicle_id as job_vehicle_id, cjl.user_name as job_user_name, job_open_date,
                job_close_date, job_status, invoice_date, invoice_no, us_firstname, DATE(inv_created_on) as sold_inv_date');
                $builder->join('cust_data_laabs cdl', 'cdl.customer_code = inv_customer_id', 'left');
                $builder->join('cust_veh_data_laabs cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left');
                $builder->join('cust_job_data_laabs cjl', 'cjl.job_no = inv_jobcard_no', 'left');
                $builder->join('users u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');
                $builder->where('inv_delete_flag', 0);

                if ($supplierId != 0) {
                    $builder->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $builder->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $builder->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $builder->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $builder->where('cjl.job_status', $jbstatus);
                }
                if (!empty($start_date) && !empty($end_date)) {
                    $builder->where('DATE(inv_created_on) >=', $start_date);
                    $builder->where('DATE(inv_created_on) <=', $end_date);
                }

                $builder->orderBy('inv_id', 'desc');
                $invoices = $builder->get()->getResultArray();
            } else {
                $builder = $this->db->table('alm_spare_invoice_master');
                $builder->select('inv_id, inv_nm_id, inv_nm_supplier_id, inv_nm_description, inv_customer_id, inv_vehicle_id, inv_jobcard_no,
                inv_nm_status, inv_nm_type, inv_nm_purchase_type, inv_nm_branch, inv_nm_sub_total, inv_nm_vat_total,
                inv_nm_discount, inv_nm_inv_date, inv_alm_margin_total, inv_alm_discount, inv_created_by, inv_created_on,
                inv_updated_by, inv_updated_on, cvl.vehicle_id as veh_data_vehicle_id, model_name, reg_no, chassis_no,
                job_no, car_reg_no, cjl.vehicle_id as job_vehicle_id, cjl.user_name as job_user_name, job_open_date,
                job_close_date, job_status, invoice_date, invoice_no, us_firstname, DATE(inv_created_on) as sold_inv_date');
                $builder->join('cust_data_laabs cdl', 'cdl.customer_code = inv_customer_id', 'left');
                $builder->join('cust_veh_data_laabs cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left');
                $builder->join('cust_job_data_laabs cjl', 'cjl.job_no = inv_jobcard_no', 'left');
                $builder->join('users u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');
                $builder->where('inv_delete_flag', 0);

                if ($supplierId != 0) {
                    $builder->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $builder->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $builder->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $builder->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $builder->where('cjl.job_status', $jbstatus);
                }
                if (!empty($start_date) && !empty($end_date)) {
                    $builder->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date);
                    $builder->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date);
                }

                $builder->orderBy('inv_id', 'desc');
                $invoices = $builder->get()->getResultArray();
            }



            if ($invoices) {
                $data = [
                    'ret_data' => "success",
                    'invoice' => $invoices,
                ];
                return $this->respond($data, 200);
            } else {
                $data = [
                    'ret_data' => "success",
                    'invoice' =>  [],
                ];
                return $this->respond($data, 200);
            }
        }
    }

    public function getSpareInvoiceById()
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
            $rules = [
                'spareInvId' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $invMaster = new PartsInvoiceMaster();
            $invItems = new PartsInvoiceItems();
            $invLog = new PartsInvoiceLog();
            $invoices = $invMaster->join("cust_data_laabs", 'cust_data_laabs.customer_code=inv_customer_id', 'left')
                ->join("cust_veh_data_laabs", "vehicle_id=inv_vehicle_id", 'left')
                ->join("users", "us_id=inv_created_by", 'left')
                ->where("inv_delete_flag", 0)->where("inv_id", $this->request->getVar('spareInvId'))->first();
            $invoices['items'] = $invItems->where("inv_item_delete_flag", 0)
                ->where("inv_item_master", $this->request->getVar('spareInvId'))->findAll();

            $invLogs = $invLog->where("inv_log_master_id", $this->request->getVar('spareInvId'))
                ->join("users", "us_id=	inv_log_created_by", 'left')
                ->select('inv_log_master_id,inv_log_note,inv_log_created_on,inv_log_created_by,us_firstname')
                ->findAll();

            if (sizeof($invoices) > 0) {
                $data['ret_data'] = "success";
                $data['invoice'] = $invoices;
                $data['logs'] = $invLogs;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "success";
                $data['invoice'] = [];
                $data['logs'] = [];
                return $this->respond($data, 200);
            }
        }
    }
    public function getAlmInvoiceComman()
    {
        $model = new CommonSettingsModel();
        $common = new Common();
        $valid = new Validation();
        $almCustomer = new MaragiCustomerModel();
        $almJobcards = new MaraghiJobcardModel();

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
            $res = $model->first();
            $customer = $almCustomer->select('customer_code,CONCAT(customer_name, " (", phone, ")") AS customer')->orderBy('customer_code', 'desc')->findAll();
            $jobCards = $almJobcards->select('job_no')
                ->whereIn('job_status', ['WIP', 'OPN', 'CAN', 'SUS', 'CLO', 'COM'])
                ->orderBy('job_no', 'DESC')
                ->findAll();
            if ($res && $customer) {
                $response = [
                    'ret_data' => 'success',
                    'settings' => $res,
                    'customers' => $customer,
                    'jobCards' => $jobCards
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'settings' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function getCustomerVehicles()
    {
        $common = new Common();
        $valid = new Validation();
        $almVehicle = new MaraghiVehicleModel();

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
            $rules = [
                'customerCode' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $vehicles = $almVehicle->where("customer_code", $this->request->getVar('customerCode'))->findAll();
            if ($vehicles) {
                $response = [
                    'ret_data' => 'success',
                    'vehicles' => $vehicles
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'vehicles' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    function getPSFWhatsappReport()
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
            $rules = [
                'dateFrom' => 'required',
                'dateTo' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $wb_model = new WhatsappMessageModel();
            $psfHistoryModel = new PSFCallHistoryModel();
            $messages = $wb_model->where("wb_message_source", 1)
                ->select('whatsapp_message_master.*,cust_data_laabs.*,psf_master.*,users.us_firstname as sa,users.us_id as sa_id') //,psf_call_history.psf_response
                ->join("cust_data_laabs", "cust_data_laabs.customer_code=wb_customer_id", "left")
                ->join("psf_master", "psf_master.psfm_primary_whatsapp_id=wb_id")
                ->join("users", "users.us_laabs_id=psf_master.psfm_sa_id", "left")
                //->join("(select * from psf_call_history where psf_call_type = 0 and  psf_id=psfm_id  order by psf_call_id desc limit 1 ) AS psf_call_history", "psf_call_history.psf_id = psf_master.psfm_id", "left")
                //->join("psf_call_history", "psf_call_history.psf_id=psf_master.psfm_id", "left")
                ->where("DATE(wb_created_on)>=", $this->request->getVar('dateFrom'))
                ->where("DATE(wb_created_on)<=", $this->request->getVar('dateTo'))
                ->orderBy('wb_created_on', 'desc')
                ->findAll();



            // foreach ($messages as &$psf) {
            //     $psf_response = $psfHistoryModel
            //         ->select('psf_response')
            //         ->where('psf_id', $psf['psfm_id'])
            //         ->where('psf_call_type', 0)
            //         ->orderBy('psf_call_id', 'desc')
            //         ->limit(1)
            //         ->first();
            //     $psf['psf_response'] = $psf_response ? $psf_response['psf_response'] : 'NIL';
            // }

            // $messages = $wb_model->where("wb_message_source", 1)
            //     ->select('whatsapp_message_master.*,cust_data_laabs.*,psf_master.*,users.us_firstname as sa,users.us_id as sa_id')
            //     ->join("cust_data_laabs", "cust_data_laabs.customer_code=wb_customer_id", "left")
            //     ->join("psf_master", "psf_master.psfm_primary_whatsapp_id=wb_id", "left")
            //     ->join("users", "users.us_laabs_id=psf_master.psfm_sa_id", "left")
            //     ->where("DATE(wb_created_on)>=", $this->request->getVar('dateFrom'))
            //     ->where("DATE(wb_created_on)<=", $this->request->getVar('dateTo'))->orderBy('wb_created_on', 'desc')->findAll();


            if (sizeof($messages) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'messages' => $messages
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'messages' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function getSparePartsMargin()
    {
        $common = new Common();
        $valid = new Validation();
        $spmModel = new SparePartsMarginModel();

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


            $sparePartsMargin = $spmModel->where("spm_delete_flag", 0)->findAll();
            if ($sparePartsMargin) {
                $response = [
                    'ret_data' => 'success',
                    'sparePartsMargin' => $sparePartsMargin
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'sparePartsMargin' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function saveSparePartsMargin()
    {
        $common = new Common();
        $valid = new Validation();
        $spmModel = new SparePartsMarginModel();

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

            $margin = $this->request->getVar('sparepartsmargin');
            if (sizeof($margin) > 0) {
                $updatearray_data = array();
                foreach ($margin as $eachdata) {
                    if (!empty($eachdata->spm_id)) {
                        $update_data = [
                            'spm_id' => $eachdata->spm_id,
                            'spm_start_price' => $eachdata->spm_start_price,
                            'spm_end_price' => $eachdata->spm_end_price,
                            'spm_price' => $eachdata->spm_price,
                        ];
                        $updatearray_data[] = $update_data;
                    } else {
                        $insert_data = [
                            'spm_start_price' => $eachdata->spm_start_price,
                            'spm_end_price' => $eachdata->spm_end_price,
                            'spm_price' => $eachdata->spm_price,
                        ];
                        $insertarray_data[] = $insert_data;
                    }
                }

                if (!empty($updatearray_data)) {
                    $spmModel->updateBatch($updatearray_data, 'spm_id');
                }

                if (!empty($insertarray_data)) {
                    $spmModel->insertBatch($insertarray_data);
                }

                $data['ret_data'] = 'success';
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        }
    }

    public function deleteSparePartsMargin()
    {
        $common = new Common();
        $valid = new Validation();
        $spmModel = new SparePartsMarginModel();

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

            $spm_id = $this->request->getVar('spm_id');
            $update_data = [
                'spm_delete_flag' => 1,
            ];
            $sp_delete = $spmModel->where('spm_id', $spm_id)->set($update_data)->update();

            if ($sp_delete) {
                $data['ret_data'] = 'success';
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        }
    }

    public function getCustomerJobcards()
    {
        $common = new Common();
        $valid = new Validation();
        $almCustomer = new MaragiCustomerModel();
        $almJobcards = new MaraghiJobcardModel();

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
            $rules = [
                'jobcardno' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());

            $customer = $almJobcards->where("job_no", $this->request->getVar('jobcardno'))
                ->join('cust_data_laabs', 'cust_data_laabs.customer_code = customer_no')
                ->join('cust_veh_data_laabs', 'cust_veh_data_laabs.vehicle_id = cust_job_data_laabs.vehicle_id')
                ->select('customer_name,job_no,customer_no,phone,cust_job_data_laabs.vehicle_id,chassis_no,car_reg_no') //chassis_no
                ->findAll();
            if ($customer) {
                $response = [
                    'ret_data' => 'success',
                    'customer' => $customer
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customer' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function UpdateSpareInvoiceById()
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
            $rules = [
                'inv_id' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $invMaster = new PartsInvoiceMaster();
            $invLog = new PartsInvoiceLog();
            $masterdata = [
                'inv_nm_status' => $this->request->getVar('nm_inv_status'),
            ];
            $master = $invMaster->where("inv_id", $this->request->getVar('inv_id'))->set($masterdata)->update();

            if ($master) {
                $invlogdata = [
                    'inv_log_master_id' => $this->request->getVar('inv_id'),
                    'inv_log_note' => ($this->request->getVar('nm_inv_status') === '4') ? "Invoice Rejected" : "Invoice Approved",
                    'inv_log_created_on' => date("Y-m-d H:i:s"),
                    'inv_log_created_by' => $tokendata['uid'],
                ];
                $log = $invLog->insert($invlogdata);
            }

            if ($master) {
                $data['ret_data'] = "success";
                $data['invoice'] = $master;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "success";
                $data['invoice'] = [];
                return $this->respond($data, 200);
            }
        }
    }

    public function createSupplierDetails()
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
            $rules = [
                'supplierName' => 'required',
                'supplierType' => 'required',
            ];

            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $sparePartsSuppliers = new SparePartsSuppliers();
            $this->db->transStart();
            if (intval($this->request->getVar('supplierType')) != 0) {
                $masterdata = [
                    'ss_name' => $this->request->getVar('supplierName'),
                    'ss_type' => $this->request->getVar('supplierType'),
                    'ss_address' => $this->request->getVar('Address'),
                    'ss_trn_no' => $this->request->getVar('Trn_No'),
                    'ss_contact_no' => $this->request->getVar('contact_no'),
                ];
                $master = $sparePartsSuppliers->insert($masterdata);
                if ($master) {
                    if ($this->db->transStatus() === false) {
                        $this->db->transRollback();
                        $data['ret_data'] = "fail";
                        return $this->respond($data, 200);
                    } else {
                        $this->db->transCommit();
                        $data['ret_data'] = "success";
                        return $this->respond($data, 200);
                    }
                } else {
                    $response = [
                        'ret_data' => 'fail',
                    ];
                    return $this->respond($response, 200);
                }
            }
        }
    }

    public function getSupplierDetails()
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
            $sparePartsSuppliers = new SparePartsSuppliers();
            $Suppliers = $sparePartsSuppliers->where("ss_delete_flag", 0)->orderBy('ss_id', 'desc')->findAll();
            if (sizeof($Suppliers) > 0) {
                $data['ret_data'] = "success";
                $data['Suppliers'] = $Suppliers;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "success";
                $data['Suppliers'] = [];
                return $this->respond($data, 200);
            }
        }
    }

    public function updateSupplierDetails()
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
            $sparePartsSuppliers = new SparePartsSuppliers();
            $id = $this->request->getVar('id');
            $masterdata = [
                'ss_name' => $this->request->getVar('supplierName'),
                'ss_type' => $this->request->getVar('supplierType'),
                'ss_address' => $this->request->getVar('Address'),
                'ss_trn_no' => $this->request->getVar('Trn_No'),
                'ss_contact_no' => $this->request->getVar('contact_no'),
            ];
            $master =  $sparePartsSuppliers->where('ss_id', $id)->set($masterdata)->update();

            if ($master) {
                $data['ret_data'] = "success";
                $data['updated'] = $master;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "success";
                $data['updated'] = [];
                return $this->respond($data, 200);
            }
        }
    }

    public function deleteSupplier()
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
            $sparePartsSuppliers = new SparePartsSuppliers();
            $id = $this->request->getVar('id');

            $masterdata = [
                'ss_delete_flag' => 1,
            ];
            $master =  $sparePartsSuppliers->where('ss_id', $id)->set($masterdata)->update();

            if ($master) {
                $data['ret_data'] = "success";
                $data['updated'] = $master;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "success";
                $data['updated'] = [];
                return $this->respond($data, 200);
            }
        }
    }

    public function getSparePartsDesandPart()
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

            $invItems = new PartsInvoiceItems();

            $descriptions = $invItems->where("inv_item_delete_flag", 0)
                ->select('inv_item_description')
                ->distinct()
                ->findAll();
            $partNo = $invItems->where("inv_item_delete_flag", 0)
                ->select('inv_item_part_number')
                ->distinct()
                ->findAll();


            if ($partNo && $descriptions) {
                $data['ret_data'] = "success";
                $data['descriptions'] = $descriptions;
                $data['partNo'] = $partNo;
                return $this->respond($data, 200);
            } else if ($partNo) {
                $data['ret_data'] = "success";
                $data['descriptions'] = [];
                $data['partNo'] = $partNo;
                return $this->respond($data, 200);
            } else if ($descriptions) {
                $data['ret_data'] = "success";
                $data['descriptions'] = $descriptions;
                $data['partNo'] = [];
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "success";
                $data['descriptions'] = [];
                $data['partNo'] = [];
                return $this->respond($data, 200);
            }
        }
    }

    public function getTotalInvoices()
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

            $invMaster = new PartsInvoiceMaster();

            // $invoices = $invMaster->where("inv_delete_flag", 0)
            //     ->join('cust_job_data_laabs', 'cust_job_data_laabs.job_no=inv_jobcard_no', 'left')
            //     ->select('FORMAT((inv_alm_margin_total - inv_alm_discount) ,2) as grand_total,job_status,inv_jobcard_no,inv_id,
            //     inv_nm_sub_total,inv_nm_vat_total,inv_alm_margin_total,inv_alm_discount')
            //     ->orderBy('inv_id', 'desc')->findAll();

            $supplierId = $this->request->getVar('supplier_id');
            $purchaseType = $this->request->getVar('purchaseType');
            $inv_type = $this->request->getVar('inv_type');
            $selectedSA = $this->request->getVar('selectedSA');
            $jbstatus = $this->request->getVar('jbstatus');
            $start_date = $this->request->getVar('start_date');
            $end_date = $this->request->getVar('end_date');

            $checkStatus =  $this->request->getVar('checked');

            if ($checkStatus == true) {

                $builder = $this->db->table('alm_spare_invoice_master');
                $builder->select('inv_id, inv_nm_id, inv_nm_supplier_id, inv_nm_description, inv_customer_id, inv_vehicle_id, inv_jobcard_no,
                inv_nm_status, inv_nm_type, inv_nm_purchase_type, inv_nm_branch, inv_nm_sub_total, inv_nm_vat_total,
                inv_nm_discount, inv_nm_inv_date, inv_alm_margin_total, inv_alm_discount, inv_created_by, inv_created_on,
                inv_updated_by, inv_updated_on, cvl.vehicle_id as veh_data_vehicle_id, model_name, reg_no, chassis_no,
                job_no, car_reg_no, cjl.vehicle_id as job_vehicle_id, cjl.user_name as job_user_name, job_open_date,
                job_close_date, job_status, invoice_date, invoice_no, us_firstname, DATE(inv_created_on) as sold_inv_date,STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") as strdate,
                FORMAT((inv_alm_margin_total - inv_alm_discount) ,2) as grand_total');
                $builder->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left');
                $builder->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left');
                $builder->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left');
                $builder->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');
                $builder->where('inv_delete_flag', 0);

                if ($supplierId != 0) {
                    $builder->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $builder->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $builder->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $builder->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $builder->where('cjl.job_status', $jbstatus);
                }
                if (!empty($start_date) && !empty($end_date)) {
                    // $builder->where('inv_nm_inv_date >=', $start_date);
                    // $builder->where('inv_nm_inv_date <=', $end_date);
                    $start_date_formatted = date('Y-m-d', strtotime($start_date));
                    $end_date_formatted = date('Y-m-d', strtotime($end_date));
                    $builder->where('DATE(inv_created_on) >=', $start_date_formatted);
                    $builder->where('DATE(inv_created_on) <=', $end_date_formatted);
                }

                $builder->orderBy('inv_id', 'desc');
                $invoices = $builder->get()->getResultArray();

                $total_inv_cost = $invMaster->where("inv_delete_flag", 0)
                    ->where('DATE(inv_created_on) >=', $start_date)
                    ->where('DATE(inv_created_on) <=', $end_date)
                    ->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left')
                    ->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left')
                    ->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left')
                    ->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');

                if ($supplierId != 0) {
                    $total_inv_cost->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $total_inv_cost->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $total_inv_cost->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $total_inv_cost->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $total_inv_cost->where('cjl.job_status', $jbstatus);
                }

                $total_inv_cost = $total_inv_cost->select('sum(inv_alm_margin_total - inv_alm_discount) as total_inv_cost')
                    ->first();

                $total_inv_cost_without_margin = $invMaster->where("inv_delete_flag", 0)
                    ->where('DATE(inv_created_on) >=', $start_date)
                    ->where('DATE(inv_created_on) <=', $end_date)
                    ->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left')
                    ->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left')
                    ->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left')
                    ->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');

                if ($supplierId != 0) {
                    $total_inv_cost_without_margin->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $total_inv_cost_without_margin->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $total_inv_cost_without_margin->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $total_inv_cost_without_margin->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $total_inv_cost_without_margin->where('cjl.job_status', $jbstatus);
                }

                $total_inv_cost_without_margin = $total_inv_cost_without_margin->select('sum(inv_nm_sub_total + inv_nm_vat_total) as total_inv_cost_without_margin')
                    ->first();

                $total_inv_cost_without_jobcard = $invMaster->where("inv_delete_flag", 0)
                    // ->where("inv_jobcard_no", NULL)
                    ->Where("inv_jobcard_no", '')
                    ->where('DATE(inv_created_on) >=', $start_date)
                    ->where('DATE(inv_created_on) <=', $end_date)
                    ->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left')
                    ->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left')
                    ->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left')
                    ->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');

                if ($supplierId != 0) {
                    $total_inv_cost_without_jobcard->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $total_inv_cost_without_jobcard->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $total_inv_cost_without_jobcard->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $total_inv_cost_without_jobcard->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $total_inv_cost_without_jobcard->where('cjl.job_status', $jbstatus);
                }
                $total_inv_cost_without_jobcard =  $total_inv_cost_without_jobcard->select('sum(inv_alm_margin_total - inv_alm_discount) as total_inv_cost_without_jobcard')
                    ->first();


                $total_inv_cost_without_jobcard_margin = $invMaster->where("inv_delete_flag", 0)
                    ->where("inv_jobcard_no", '')
                    ->where('DATE(inv_created_on)>=', $start_date)
                    ->where('DATE(inv_created_on) <=', $end_date)
                    ->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left')
                    ->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left')
                    ->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left')
                    ->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');

                if ($supplierId != 0) {
                    $total_inv_cost_without_jobcard_margin->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $total_inv_cost_without_jobcard_margin->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $total_inv_cost_without_jobcard_margin->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $total_inv_cost_without_jobcard_margin->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $total_inv_cost_without_jobcard_margin->where('cjl.job_status', $jbstatus);
                }

                $total_inv_cost_without_jobcard_margin = $total_inv_cost_without_jobcard_margin->select('sum(inv_nm_sub_total + inv_nm_vat_total) as total_inv_cost_without_jobcard_margin')
                    ->first();

                $total_inv_cost_with_jobcard = $invMaster->where("inv_delete_flag", 0)
                    ->where("inv_jobcard_no !=", '')
                    ->where('DATE(inv_created_on) >=', $start_date)
                    ->where('DATE(inv_created_on) <=', $end_date)
                    ->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left')
                    ->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left')
                    ->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left')
                    ->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');

                if ($supplierId != 0) {
                    $total_inv_cost_with_jobcard->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $total_inv_cost_with_jobcard->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $total_inv_cost_with_jobcard->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $total_inv_cost_with_jobcard->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $total_inv_cost_with_jobcard->where('cjl.job_status', $jbstatus);
                }

                $total_inv_cost_with_jobcard = $total_inv_cost_with_jobcard->select('sum(inv_alm_margin_total - inv_alm_discount) as total_inv_cost_with_jobcard')
                    ->first();


                $total_inv_cost_with_jobcard_margin = $invMaster->where("inv_delete_flag", 0)
                    ->where("inv_jobcard_no !=", '')
                    ->where('DATE(inv_created_on) >=', $start_date)
                    ->where('DATE(inv_created_on) <=', $end_date)
                    ->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left')
                    ->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left')
                    ->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left')
                    ->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');

                if ($supplierId != 0) {
                    $total_inv_cost_with_jobcard_margin->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $total_inv_cost_with_jobcard_margin->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $total_inv_cost_with_jobcard_margin->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $total_inv_cost_with_jobcard_margin->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $total_inv_cost_with_jobcard_margin->where('cjl.job_status', $jbstatus);
                }

                $total_inv_cost_with_jobcard_margin = $total_inv_cost_with_jobcard_margin->select('sum(inv_nm_sub_total + inv_nm_vat_total) as total_inv_cost_with_jobcard_margin')
                    ->first();
            } else {
                $builder = $this->db->table('alm_spare_invoice_master');
                $builder->select('inv_id, inv_nm_id, inv_nm_supplier_id, inv_nm_description, inv_customer_id, inv_vehicle_id, inv_jobcard_no,
                inv_nm_status, inv_nm_type, inv_nm_purchase_type, inv_nm_branch, inv_nm_sub_total, inv_nm_vat_total,
                inv_nm_discount, inv_nm_inv_date, inv_alm_margin_total, inv_alm_discount, inv_created_by, inv_created_on,
                inv_updated_by, inv_updated_on, cvl.vehicle_id as veh_data_vehicle_id, model_name, reg_no, chassis_no,
                job_no, car_reg_no, cjl.vehicle_id as job_vehicle_id, cjl.user_name as job_user_name, job_open_date,
                job_close_date, job_status, invoice_date, invoice_no, us_firstname, DATE(inv_created_on) as sold_inv_date,STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") as strdate,
                FORMAT((inv_alm_margin_total - inv_alm_discount) ,2) as grand_total');
                $builder->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left');
                $builder->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left');
                $builder->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left');
                $builder->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');
                $builder->where('inv_delete_flag', 0);

                if ($supplierId != 0) {
                    $builder->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $builder->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $builder->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $builder->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $builder->where('cjl.job_status', $jbstatus);
                }
                if (!empty($start_date) && !empty($end_date)) {
                    // $builder->where('inv_nm_inv_date >=', $start_date);
                    // $builder->where('inv_nm_inv_date <=', $end_date);
                    $start_date_formatted = date('Y-m-d', strtotime($start_date));
                    $end_date_formatted = date('Y-m-d', strtotime($end_date));
                    $builder->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date_formatted);
                    $builder->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date_formatted);
                }

                $builder->orderBy('inv_id', 'desc');
                $invoices = $builder->get()->getResultArray();

                $total_inv_cost = $invMaster->where("inv_delete_flag", 0)
                    ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date)
                    ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date)
                    ->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left')
                    ->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left')
                    ->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left')
                    ->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');

                if ($supplierId != 0) {
                    $total_inv_cost->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $total_inv_cost->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $total_inv_cost->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $total_inv_cost->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $total_inv_cost->where('cjl.job_status', $jbstatus);
                }

                $total_inv_cost = $total_inv_cost->select('sum(inv_alm_margin_total - inv_alm_discount) as total_inv_cost')
                    ->first();

                $total_inv_cost_without_margin = $invMaster->where("inv_delete_flag", 0)
                    ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date)
                    ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date)
                    ->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left')
                    ->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left')
                    ->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left')
                    ->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');

                if ($supplierId != 0) {
                    $total_inv_cost_without_margin->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $total_inv_cost_without_margin->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $total_inv_cost_without_margin->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $total_inv_cost_without_margin->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $total_inv_cost_without_margin->where('cjl.job_status', $jbstatus);
                }

                $total_inv_cost_without_margin = $total_inv_cost_without_margin->select('sum(inv_nm_sub_total + inv_nm_vat_total) as total_inv_cost_without_margin')
                    ->first();

                $total_inv_cost_without_jobcard = $invMaster->where("inv_delete_flag", 0)
                    // ->where("inv_jobcard_no", NULL)
                    ->Where("inv_jobcard_no", '')
                    ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date)
                    ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date)
                    ->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left')
                    ->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left')
                    ->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left')
                    ->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');

                if ($supplierId != 0) {
                    $total_inv_cost_without_jobcard->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $total_inv_cost_without_jobcard->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $total_inv_cost_without_jobcard->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $total_inv_cost_without_jobcard->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $total_inv_cost_without_jobcard->where('cjl.job_status', $jbstatus);
                }
                $total_inv_cost_without_jobcard =  $total_inv_cost_without_jobcard->select('sum(inv_alm_margin_total - inv_alm_discount) as total_inv_cost_without_jobcard')
                    ->first();


                $total_inv_cost_without_jobcard_margin = $invMaster->where("inv_delete_flag", 0)
                    ->where("inv_jobcard_no", '')
                    ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date)
                    ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date)
                    ->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left')
                    ->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left')
                    ->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left')
                    ->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');

                if ($supplierId != 0) {
                    $total_inv_cost_without_jobcard_margin->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $total_inv_cost_without_jobcard_margin->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $total_inv_cost_without_jobcard_margin->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $total_inv_cost_without_jobcard_margin->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $total_inv_cost_without_jobcard_margin->where('cjl.job_status', $jbstatus);
                }

                $total_inv_cost_without_jobcard_margin = $total_inv_cost_without_jobcard_margin->select('sum(inv_nm_sub_total + inv_nm_vat_total) as total_inv_cost_without_jobcard_margin')
                    ->first();

                $total_inv_cost_with_jobcard = $invMaster->where("inv_delete_flag", 0)
                    ->where("inv_jobcard_no !=", '')
                    ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date)
                    ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date)
                    ->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left')
                    ->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left')
                    ->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left')
                    ->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');

                if ($supplierId != 0) {
                    $total_inv_cost_with_jobcard->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $total_inv_cost_with_jobcard->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $total_inv_cost_with_jobcard->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $total_inv_cost_with_jobcard->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $total_inv_cost_with_jobcard->where('cjl.job_status', $jbstatus);
                }

                $total_inv_cost_with_jobcard = $total_inv_cost_with_jobcard->select('sum(inv_alm_margin_total - inv_alm_discount) as total_inv_cost_with_jobcard')
                    ->first();


                $total_inv_cost_with_jobcard_margin = $invMaster->where("inv_delete_flag", 0)
                    ->where("inv_jobcard_no !=", '')
                    ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date)
                    ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date)
                    ->join('cust_data_laabs as cdl', 'cdl.customer_code = inv_customer_id', 'left')
                    ->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = inv_vehicle_id', 'left')
                    ->join('cust_job_data_laabs as cjl', 'cjl.job_no = inv_jobcard_no', 'left')
                    ->join('users as u', 'u.us_laabs_id = cjl.sa_emp_id', 'left');

                if ($supplierId != 0) {
                    $total_inv_cost_with_jobcard_margin->where('inv_nm_supplier_id', $supplierId);
                }
                if ($purchaseType != 0) {
                    $total_inv_cost_with_jobcard_margin->where('inv_nm_purchase_type', $purchaseType);
                }
                if ($inv_type != 0) {
                    $total_inv_cost_with_jobcard_margin->where('inv_nm_type', $inv_type);
                }
                if ($selectedSA != 0) {
                    $total_inv_cost_with_jobcard_margin->where('cjl.sa_emp_id', $selectedSA);
                }
                if ($jbstatus != 0) {
                    $total_inv_cost_with_jobcard_margin->where('cjl.job_status', $jbstatus);
                }

                $total_inv_cost_with_jobcard_margin = $total_inv_cost_with_jobcard_margin->select('sum(inv_nm_sub_total + inv_nm_vat_total) as total_inv_cost_with_jobcard_margin')
                    ->first();
            }





            // $total_inv_cost = $invMaster->where("inv_delete_flag", 0)
            //     ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date)->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date)
            //     ->select('sum(inv_alm_margin_total - inv_alm_discount) as total_inv_cost')
            //     ->first();

            // $total_inv_cost_without_margin = $invMaster->where("inv_delete_flag", 0)
            //     ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date)->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date)
            //     ->select('sum(inv_nm_sub_total + inv_nm_vat_total) as total_inv_cost_without_margin')
            //     ->first();

            // $total_inv_cost_without_jobcard = $invMaster->where("inv_delete_flag", 0)
            //     // ->where("inv_jobcard_no", NULL)
            //     ->Where("inv_jobcard_no", '')
            //     ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date)
            //     ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date)
            //     ->select('sum(inv_alm_margin_total - inv_alm_discount) as total_inv_cost_without_jobcard')
            //     ->first();

            // $total_inv_cost_without_jobcard_margin = $invMaster->where("inv_delete_flag", 0)
            //     //->where("inv_jobcard_no", NULL)
            //     ->Where("inv_jobcard_no", '')
            //     ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date)->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date)
            //     ->select('sum(inv_nm_sub_total + inv_nm_vat_total) as total_inv_cost_without_jobcard_margin')
            //     ->first();

            // $total_inv_cost_with_jobcard = $invMaster->where("inv_delete_flag", 0)
            //     // ->where("inv_jobcard_no !=", NULL)
            //     ->where("inv_jobcard_no !=", '')
            //     ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date)->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date)
            //     ->select('sum(inv_alm_margin_total - inv_alm_discount) as total_inv_cost_with_jobcard')
            //     ->first();

            // $total_inv_cost_with_jobcard_margin = $invMaster->where("inv_delete_flag", 0)
            //     //->where("inv_jobcard_no !=", NULL)
            //     ->where("inv_jobcard_no !=", '')
            //     ->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") >=', $start_date)->where('STR_TO_DATE(inv_nm_inv_date, "%d-%b-%y") <=', $end_date)
            //     ->select('sum(inv_nm_sub_total + inv_nm_vat_total) as total_inv_cost_with_jobcard_margin')
            //     ->first();


            if ($invoices && $total_inv_cost) {
                $data = [
                    'ret_data' => "success",
                    'invoices' => $invoices,
                    'total_inv_cost' => $total_inv_cost,
                    'total_inv_cost_without_jobcard' => $total_inv_cost_without_jobcard,
                    'total_inv_cost_without_margin' => $total_inv_cost_without_margin,
                    'total_inv_cost_without_jobcard_margin' => $total_inv_cost_without_jobcard_margin,
                    'total_inv_cost_with_jobcard' => $total_inv_cost_with_jobcard,
                    'total_inv_cost_with_jobcard_margin' => $total_inv_cost_with_jobcard_margin,
                ];
                return $this->respond($data, 200);
            } else {
                $data = [
                    'ret_data' => "success",
                    'invoice' =>  [],
                    'total_inv_cost' => 0.00,
                    'total_inv_cost_without_jobcard' => 0.00,
                    'total_inv_cost_without_margin' => 0.00,
                    'total_inv_cost_without_jobcard_margin' => 0.00,
                    'total_inv_cost_with_jobcard' => 0.00,
                    'total_inv_cost_with_jobcard_margin' => 0.00,
                ];
                return $this->respond($data, 200);
            }
        }
    }

    public function getAdminApprovalInvoices()
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
            $invMaster = new PartsInvoiceMaster();
            $invoices = $invMaster->join("cust_data_laabs", 'cust_data_laabs.customer_code=inv_customer_id', 'left')
                ->join("cust_veh_data_laabs", "vehicle_id=inv_vehicle_id", 'left')
                ->join("cust_job_data_laabs", "cust_job_data_laabs.job_no=inv_jobcard_no", 'left')
                ->join("users", "users.us_laabs_id=cust_job_data_laabs.sa_emp_id", 'left')
                ->select('inv_id,inv_nm_id,inv_nm_supplier_id,inv_nm_description,inv_customer_id,inv_vehicle_id,
                    inv_jobcard_no,inv_nm_status,inv_nm_type, inv_nm_purchase_type, inv_nm_branch,inv_nm_sub_total,
                    inv_nm_vat_total,inv_nm_discount,inv_nm_inv_date,inv_alm_margin_total,inv_alm_discount,inv_created_by
                    ,inv_created_on,inv_updated_by,inv_updated_on,cust_veh_data_laabs.vehicle_id as veh_data_vehicle_id,
                    model_name,reg_no,chassis_no,job_no,car_reg_no,sa_emp_id,
                    cust_job_data_laabs.vehicle_id as job_vehicle_id,cust_job_data_laabs.user_name as job_user_name,
                    job_open_date,job_close_date,job_status,invoice_date,
                    invoice_no,us_firstname,DATE(inv_created_on) as sold_inv_date')
                ->where("inv_delete_flag", 0)->where("inv_nm_status", 2)->orderBy('inv_id', 'desc')
                ->findAll();

            if ($invoices) {
                $data = [
                    'ret_data' => "success",
                    'invoice' => $invoices,
                ];
                return $this->respond($data, 200);
            } else {
                $data = [
                    'ret_data' => "success",
                    'invoice' =>  [],
                ];
                return $this->respond($data, 200);
            }
        }
    }

    public function updateSpareInvoice()
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
            $invMaster = new PartsInvoiceMaster();
            $invItems = new PartsInvoiceItems();
            $invLog = new PartsInvoiceLog();

            $masterdata = [
                'inv_alm_margin_total' => $this->request->getVar('inv_alm_margin_total'),
                'inv_alm_discount' => $this->request->getVar('inv_alm_discount'),
                'inv_updated_by' => $tokendata['uid'],
                'inv_updated_on' => date("Y-m-d H:i:s"),
                'inv_nm_return_total' => $this->request->getVar('inv_nm_return_total'),
            ];
            $master = $invMaster->where('inv_id', $this->request->getVar('inv_id'))->set($masterdata)->update();

            if ($master) {
                $updatedata = $this->request->getVar('invoice_items');
                if (sizeof($updatedata) > 0) {
                    $updatearray_data = array();
                    foreach ($updatedata as $eachdata) {
                        $update_data = [
                            'inv_item_id' => $eachdata->inv_item_id,
                            'inv_item_return_qty' => $eachdata->ITEM_QTY_RETURN,
                            'inv_item_margin' => $eachdata->inv_item_margin,
                            'inv_item_margin_amount' => $eachdata->inv_item_margin_amount,
                        ];
                        array_push($updatearray_data, $update_data);
                    }
                    sizeof($updatearray_data) > 0 ? $invItems->updateBatch($updatearray_data, 'inv_item_id') : "";
                }
                $invlogdata = [
                    'inv_log_master_id' =>  $master,
                    'inv_log_note' => "Invoice Updated",
                    'inv_log_created_on' => date("Y-m-d H:i:s"),
                    'inv_log_created_by' => $tokendata['uid'],
                ];
                $log = $invLog->insert($invlogdata);
            }

            if ($master) {
                $data = [
                    'ret_data' => "success",
                    'invoice' => $master,
                ];
                return $this->respond($data, 200);
            } else {
                $data = [
                    'ret_data' => "success",
                    'invoice' =>  [],
                ];
                return $this->respond($data, 200);
            }
        }
    }

    public function getNMInvoiceList()
    {
        $validModel = new Validation();
        $commonutils = new Common();
        $heddata = $this->request->headers();
        $UserModel = new UserModel();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'user') {
            $user = $UserModel->where("us_id", $tokendata['uid'])->join('user_roles', 'user_roles.role_id=us_role_id')->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        $rules = [
            'startdate' => 'required',
            'enddate' => 'required',
        ];
        if (!$this->validate($rules))  return $this->fail($this->validator->getErrors());
        $nm_spare = $this->getNMSparesList($this->request->getVar("startdate"), $this->request->getVar("enddate"));
        if ($nm_spare) {
            $data['ret_data'] = "success";
            $data['booking'] = json_decode($nm_spare);
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "fail";
            return $this->fail($data, 200);
        }
    }



    public function getNMSparesList($start_date, $end_date)
    {
        $fields = array(
            'start_date' => $start_date,
            'end_date' =>  $end_date,
        );
        $fields = json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://nasermohsin.fortidyndns.com:35146/nm_spare_fetch/index.php/datafetch/getInvoiceMaraghiInvoices");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function getNMInvoicePostedList()
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


            $invMaster = new PartsInvoiceMaster();

            $invoicesNo = $invMaster->select('inv_nm_id')
                ->where("inv_delete_flag", 0)->orderBy('inv_id', 'desc')
                ->findAll();


            if ($invoicesNo) {
                $data = [
                    'ret_data' => "success",
                    'invoicesNo' => $invoicesNo,
                ];
                return $this->respond($data, 200);
            } else {
                $data = [
                    'ret_data' => "success",
                    'invoicesNo' =>  [],
                ];
                return $this->respond($data, 200);
            }
        }
    }
    public function sendAnniversaryMessage()
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
            $anv_customers = new AniversaryCustomerModel();
            $customers = $anv_customers->where('send_flag', 0)->findAll();
            foreach ($customers as $value) {
                $messageData = array(
                    "messaging_product" => "whatsapp",
                    "recipient_type" => "individual",
                    "to" => $value['customer_mobile'],
                    "type" => "template",
                    'template' => array("name" => "anniversary_message", 'language' => array("code" => "ar"), 'components' =>
                    array(
                        array(
                            "type" => "header",
                            "parameters" => array(
                                array("type" => "video", "video" => array("link" => "https://benzuae.ae/anv_video.mp4"))
                            )
                        ),
                        array(
                            "type" => "body"
                        )
                    ))
                );
                $return = $common->sendWhatsappMessage($messageData, '971509766075');
                // return $this->respond($return->messages[0]->message_status, 200);
                if (isset($return->messages)) {
                    $wb_model = new WhatsappMessageModel();
                    if ($return->messages[0]->message_status == "accepted") {
                        $wb_data = [
                            'wb_message_id' => $return->messages[0]->id,
                            'wb_message_source' => 2,
                            'wb_customer_id' => $value['customer_mobile'],
                            'wb_message_status' => 1,
                            'wb_phone' => $value['customer_mobile'],
                            'wb_created_on' => date("Y-m-d H:i:s"),
                            'wb_updated_on' => date("Y-m-d H:i:s")
                        ];
                    } else {
                        $wb_data = [
                            'wb_message_id' => $return->messages[0]->id,
                            'wb_message_source' => 2,
                            'wb_customer_id' => $value['customer_mobile'],
                            'wb_message_status' => 0,
                            'wb_phone' => $value['customer_mobile'],
                            'wb_created_on' => date("Y-m-d H:i:s"),
                            'wb_updated_on' => date("Y-m-d H:i:s")
                        ];
                    }
                    $wb_id = $wb_model->insert($wb_data);
                    $anv_customers->set('send_flag', 1)->where('customer_mobile', $value['customer_mobile'])->update();
                }
            }
            $data = [
                'ret_data' => "success",
                'invoicesNo' =>  $customers,
            ];
            return $this->respond($data, 200);
        }
    }

    public function getAllJobCardStatus()
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
            $laabsJob = new MaraghiJobModel();
            $selectedSA = $this->request->getVar('selectedSA');
            $selectedStatus = $this->request->getVar('selectedStatus');
            $selectedSubStatus = $this->request->getVar('selectedSubStatus');

            $builder = $this->db->table('cust_job_data_laabs');
            $builder->select('cust_job_data_laabs.*, cdl.*, cvl.*');
            $builder->join('cust_data_laabs as cdl', 'cdl.customer_code = cust_job_data_laabs.customer_no', 'left');
            $builder->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = cust_job_data_laabs.vehicle_id', 'left');
            $builder->where("str_to_date(job_open_date, '%d-%M-%y')  >", '2023-01-01');
            $builder->where('job_status !=', "INV");
            $builder->where('job_status !=', "CAN");
            $builder->where('job_status !=', "SUS");

            if ($selectedSA != 0) {
                $builder->where('sa_emp_id', $selectedSA);
            }
            if ($selectedStatus != 0) {
                $builder->where('job_status', $selectedStatus);
            }
            if ($selectedSubStatus != 0) {
                $builder->where('jb_sub_status', $selectedSubStatus);
            }

            $builder->orderBy('job_no', 'desc');
            $jobs_list = $builder->get()->getResultArray();

            // $jobs_list = $laabsJob
            //     // ->where("str_to_date(job_open_date, '%d-%M-%y')  >=", $this->request->getVar('start_date'))
            //     // ->where("str_to_date(job_open_date, '%d-%M-%y')  <=", $this->request->getVar('end_date'))
            //     ->where("job_status !=","INV")
            //     ->where("job_status !=","CAN")
            //     ->where("job_status !=","SUS")
            //     ->join('cust_data_laabs', 'cust_data_laabs.customer_code=cust_job_data_laabs.customer_no', 'left')
            //     ->join('cust_veh_data_laabs', 'cust_veh_data_laabs.vehicle_id=cust_job_data_laabs.vehicle_id', 'left')
            //     ->orderby('job_no', "desc")->findAll();
            if (sizeof($jobs_list)) {
                $response = [
                    'ret_data' => 'success',
                    'customers' => $jobs_list
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customers' =>  []
                ];
            }
        }
        return $this->respond($response, 200);
    }

    public function getJobCardsAgeData()
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
            $laabsJob = new MaraghiJobModel();
            $jobs_list = $laabsJob->where("str_to_date(job_open_date, '%d-%M-%y')  >=", $this->request->getVar('start_date'))
                ->where("str_to_date(job_open_date, '%d-%M-%y')  <=", $this->request->getVar('end_date'))
                ->join('cust_data_laabs', 'cust_data_laabs.customer_code=cust_job_data_laabs.customer_no', 'left')
                ->orderby('job_no', "desc")->findAll();
            $uniqueArray = $this->removeDuplicates($jobs_list, 'vehicle_id');
            $job_cards = [];
            foreach ($uniqueArray as $temp) {
                $temp['old_jobcard'] = $laabsJob->where("str_to_date(job_open_date, '%d-%M-%y')  <", $this->request->getVar('start_date'))
                    ->where('vehicle_id', $temp['vehicle_id'])->where('job_status', 'INV')->orderby('job_no', "desc")->first();
                array_push($job_cards, $temp);
            }
            if (sizeof($jobs_list)) {
                $response = [
                    'ret_data' => 'success',
                    'customers' => $job_cards
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customers' =>  []
                ];
            }
            return $this->respond($response, 200);
        }
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

    public function saveSubStatus()
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
            $rules = [
                'master_status' => 'required',
                'sub_status' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $jb_status = new JobSubStatusModel();
            $jb_data = [
                'jbs_master_status' => $this->request->getVar('master_status'),
                'jbs_sub_status' => $this->request->getVar('sub_status'),
                'jbs_created_by' => $user['us_id'],
                'jbs_updated_by' => $user['us_id'],
                'jbs_created_on' => date("Y-m-d H:i:s"),
                'jbs_updated_on' => date("Y-m-d H:i:s"),
            ];
            $insert_id = $jb_status->insert($jb_data);
            if ($insert_id) {
                $response = [
                    'ret_data' => 'success',
                    'status_id' => $insert_id
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'status_id' =>  '0'
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getAllSubStatus()
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
            $jb_status = new JobSubStatusModel();
            $sub_statuses =  $jb_status->where("jbs_delete_flag", 0)->findAll();
            if (sizeof($sub_statuses)) {
                $response = [
                    'ret_data' => 'success',
                    'status_list' => $sub_statuses
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'status_list' => []
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function updateJobSubStatus()
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
            $rules = [
                'job_no' => 'required',
                'job_sub_status' => 'required',
                'job_status' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $laabsJob = new MaraghiJobModel();
            $subStatusTracker = new JobSubStatusTrackerModel();
            $update_lab = $laabsJob->set("jb_sub_status", $this->request->getVar('job_sub_status'))->where("job_no", $this->request->getVar('job_no'))->update();
            if ($update_lab) {
                $data = [
                    'jbsc_job_no' => $this->request->getVar('job_no'),
                    'jbsc_main_status' => $this->request->getVar('job_status'),
                    'jbsc_sub_status' => $this->request->getVar('job_sub_status'),
                    'jbsc_updated_by' => $user['us_id'],
                    'jbsc_updated_on' => date("Y-m-d H:i:s"),
                ];
                $subinsert = $subStatusTracker->insert($data);
                if ($subinsert) {
                    $response = [
                        'ret_data' => 'success',
                        'status_list' => $subinsert
                    ];
                } else {
                    $response = [
                        'ret_data' => 'fail',
                        'status_list' => $subinsert
                    ];
                }
            }
            return $this->respond($response, 200);
        }
    }

    public function getJobStusChangeHistory()
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
            $rules = [
                'job_no' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $subStatusTracker = new JobSubStatusTrackerModel();
            $status_data = $subStatusTracker
                ->select('job_sub_status_changes.*,us_firstname,job_sub_statuses.*')
                ->where("jbsc_job_no", $this->request->getVar('job_no'))
                ->join("users", "us_id=jbsc_updated_by", 'left')
                ->join("job_sub_statuses", "jbs_status_id=jbsc_sub_status", 'left')->findAll();
            if ($status_data) {
                $response = [
                    'ret_data' => 'success',
                    'status_list' => $status_data
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'status_list' => []
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function saveUserRoleMargin()
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

            $userRoleMarginLimit = new UserRoleMarginLimitModel();
            $role_id = $this->request->getVar('role_id');

            $existingRecord = $userRoleMarginLimit->where('ml_role_id', $role_id)->where("ml_delete_flag !=", 1)->first();

            if ($existingRecord) {
                $data = [
                    'ml_minimum_margin' => $this->request->getVar('minimum_margin'),
                    'ml_maximum_margin' => $this->request->getVar('maximum_margin'),
                ];

                $result = $userRoleMarginLimit->update($existingRecord['ml_id'], $data);
            } else {
                $data = [
                    'ml_role_id' => $role_id,
                    'ml_minimum_margin' => $this->request->getVar('minimum_margin'),
                    'ml_maximum_margin' => $this->request->getVar('maximum_margin'),
                ];

                $result = $userRoleMarginLimit->insert($data);
            }
            // $data = [
            //     'ml_role_id' => $this->request->getVar('role_id'),
            //     'ml_minimum_margin' => $this->request->getVar('minimum_margin'),
            //     'ml_maximum_margin' => $this->request->getVar('maximum_margin'),
            // ];

            // $result = $userRoleMarginLimit->insert($data);

            if ($result) {
                $response = [
                    'ret_data' => 'success',
                    'status' => $result
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'status' => []
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getUserRoleMargin()
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

            $userRoleMarginLimit = new UserRoleMarginLimitModel();
            $userRoleMargins = $userRoleMarginLimit->select('user_role_margin_limit.*,user_roles.role_name')->where("ml_delete_flag !=", 1)
                ->join("user_roles", "user_roles.role_id=ml_role_id", 'left')
                ->findAll();

            if ($userRoleMargins) {
                $response = [
                    'ret_data' => 'success',
                    'userRoleMargins' => $userRoleMargins
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'userRoleMargins' => []
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function deleteUserRoleMargin()
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

            $userRoleMarginLimit = new UserRoleMarginLimitModel();
            $ml_id  = $this->request->getVar('ml_id ');
            $update_data = [
                'ml_delete_flag' => 1,
            ];
            $ml_delete = $userRoleMarginLimit->where('spm_id', $ml_id)->set($update_data)->update();

            if ($ml_delete) {
                $response = [
                    'ret_data' => 'success',
                    'status' => $ml_delete
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'status' => []
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getUserRoleMarginLimit()
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

            $userRoleMarginLimit = new UserRoleMarginLimitModel();
            $role_id = $this->request->getVar('ml_role_id');

            $userRoleMargins = $userRoleMarginLimit->select('*')
                ->where("ml_delete_flag !=", 1)
                ->where("ml_role_id", $role_id)
                ->first();

            if ($userRoleMargins) {
                $response = [
                    'ret_data' => 'success',
                    'userRoleMargin' => $userRoleMargins
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'userRoleMargin' => []
                ];
            }
            return $this->respond($response, 200);
        }
    }


    public function getAllJobCards()
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
            $laabsJob = new MaraghiJobModel();
            $subStatusTracker = new JobSubStatusTrackerModel();
            $dateFrom = $this->request->getVar('dateFrom');
            $dateTo = $this->request->getVar('dateTo');

            $builder = $this->db->table('cust_job_data_laabs');
            $builder->select('cust_job_data_laabs.*, cdl.*, cvl.*');
            $builder->join('cust_data_laabs as cdl', 'cdl.customer_code = cust_job_data_laabs.customer_no', 'left');
            $builder->join('cust_veh_data_laabs as cvl', 'cvl.vehicle_id = cust_job_data_laabs.vehicle_id', 'left');
            $builder->where('job_status !=', "INV");
            $builder->where('job_status !=', "CAN");
            $builder->where('job_status !=', "SUS");

            if (!empty($dateFrom) && !empty($dateTo)) {
                $builder->where("STR_TO_DATE(job_open_date, '%d-%b-%y')  >=", $dateFrom);
                $builder->where("STR_TO_DATE(job_open_date, '%d-%b-%y')  <=", $dateTo);
            }

            $builder->orderBy('job_no', 'desc');
            $jobs_list = $builder->get()->getResultArray();

            $index = 0;
            foreach ($jobs_list as $jobs) {
                $jobs_list[$index]["subStatusTracker"] = $subStatusTracker->where("jbsc_job_no", $jobs['job_no'])
                    ->orderBy('jbsc_updated_on', 'DESC')->findAll();
                $index++;
            }


            if (sizeof($jobs_list)) {
                $response = [
                    'ret_data' => 'success',
                    'jobs' => $jobs_list
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customers' =>  []
                ];
            }
        }
        return $this->respond($response, 200);
    }
}
