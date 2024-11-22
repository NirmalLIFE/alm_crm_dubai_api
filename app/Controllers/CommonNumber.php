<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Commonutils\CommonNumberModel;
use App\Models\Leads\LeadModel;
use App\Models\Leads\LeadCallLogModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;
use App\Models\Leads\LeadActivityModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Customer\CustomerMasterModel;


class CommonNumber extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * @api {get} CommonNumber  Number List
     * @apiName CommonNumber
     * @apiGroup Super Admin
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   numlist  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */
    public function index()
    {
        $model = new CommonNumberModel();
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


            // $str = '0267';
            // if (substr($str, 0, 2) == '05')
            //             {
            // echo "match";
            //             }
            //             else{
            //                 echo "not";
            //             }
            //             die;
            $res = $model->where('cn_delete_flag', 0)->findAll();
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'numlist' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'numlist' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    /**
     * @api {get} CommonNumber/:id  Number list  id
     * @apiName Number list  id
     * @apiGroup Super Admin
     * @apiPermission  Super Admin
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}    numlist object with Number details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
    {
        $model = new CommonNumberModel();
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

            $this->insertUserLog('View number data for Update', $tokendata['uid']);

            $res = $model->where('cn_id', $this->db->escapeString($id))->first();
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'numlist' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'numlist' => []
                ];
                return $this->respond($response, 200);
            }
        }
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
     * @api {post} CommonNumber Common Number create
     * @apiName Common Number create
     * @apiGroup super admin
     * @apiPermission super admin
     *
     *@apiBody {String} number Number
     *@apiBody {String} user Number User
     *@apiBody {String} reason Reason to call
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function create()
    {
        $model = new CommonNumberModel();
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
                'cn_number' => $this->request->getVar('number'),
                'cn_user' => $this->request->getVar('user'),
                'cn_reason' => $this->request->getVar('reason'),
                'cn_created_by' => $tokendata['uid']
            ];

            if ($model->insert($data) === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            } else {
                $this->insertUserLog('New Number Added ' . $this->request->getVar('number'), $tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];

                return $this->respond($response, 200);
            }
        }
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
     * @api {post} CommonNumber/update Common Number Update
     * @apiName Common Number Update
     * @apiGroup super admin
     * @apiPermission super admin,User
     *
     *
     *@apiBody {String} number Number
     *@apiBody {String} user Number User
     *@apiBody {String} reason Reason to call
     *@apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function update($id = null)
    {
        $model = new CommonNumberModel();
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
                'cn_number' => $this->request->getVar('number'),
                'cn_user' => $this->request->getVar('user'),
                'cn_reason' => $this->request->getVar('reason'),
                'cn_updated_by' => $tokendata['uid']
            ];
            if ($model->where('cn_id',  $this->db->escapeString($this->request->getVar('id')))->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            } else {
                $this->insertUserLog(' Common Number Updated ', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];

                return $this->respond($response, 200);
            }
        }
    }

    /**
     * @api {delete} CommonNumber/:id Common Number delete
     * @apiName Common Number delete
     * @apiGroup Super Admin
     * @apiPermission Super Admin
     *
     * 
     * @apiParam {String} id  id of the number to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new CommonNumberModel();
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

            $data = [
                'cn_delete_flag' => 1,
            ];
            if ($model->where('cn_id',  $this->db->escapeString($id))->set($data)->update() === false) {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            } else {
                $this->insertUserLog('Common Number Deleted ', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function insertUserLog($log, $id)
    {
        $logmodel = new UserActivityLog();
        $ip = $this->request->getIPAddress();
        $indata = [
            'log_user'    => $id,
            'log_ip'   =>  $ip,
            'log_activity' => $log
        ];
        $results = $logmodel->insert($indata);
    }
    /**
     * @api {get} CommonNumber  Number List
     * @apiName CommonNumber
     * @apiGroup Super Admin
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   numlist  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */
    public function checkNumber()
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
            $model = new CommonNumberModel();
            $log = new LeadCallLogModel();
            $num = $this->request->getVar('phone');
            $res = $model->where('cn_number', $this->db->escapeString($num))->first();
            if ($res == null && (substr($num, 0, 2) == '05' || substr($num, 0, 5) == '97105' || substr($num, 0, 4) == '9715'|| substr($num, 0, 2) == '02' || substr($num, 0, 2) == '04')) {
                $lmodel = new LeadModel();
                $penCount = 0;
                $penLeadId = 0;
                $status = [1, 7];    //->orWhere('status_id', '7')
                $lead_list = $lmodel->whereIn('status_id', $status)
                    ->where('RIGHT(phone,9)',  substr($num, -9))->orderBy('lead_id', 'desc')->findAll();

                if (sizeof($lead_list) > 0) {
                    $penCount = sizeof($lead_list);
                    $penLeadId = $lead_list[0]['lead_id'];
                } else {
                    $penLeadId = $this->createLead($this->request->getVar('phone'));
                }
                $log_check = $log->where("lcl_phone", $this->request->getVar('phone'))
                    ->where("lcl_call_to", $this->request->getVar('call_to'))
                    ->where("ystar_call_id", $this->request->getVar('call_id'))->first();
                if (!$log_check) {
                    $logdata = [
                        'lcl_lead_id' => $penLeadId,
                        'lcl_purpose_note' => "",
                        'lcl_call_to' => $this->request->getVar('call_to'),
                        'lcl_phone' => $this->request->getVar('phone'),
                        'lcl_createdby' => $tokendata['uid'],
                        'ystar_call_id' => $this->request->getVar('call_id'),
                        'lcl_user_id' => $tokendata['uid'],
                        "lcl_call_type" => $this->request->getVar('lcl_call_type'),
                        "lcl_call_source" => $this->request->getVar('lcl_call_source'),
                        'lcl_created_on'=> date("Y-m-d H:i:s"),
                        // 'lcl_call_time'=>$this->request->getVar('calltime') 
                    ];
                    $call_log = $log->insert($logdata);
                } else {
                    $call_log = $log_check['lcl_id'];
                }

                $response = [
                    'ret_data' => 'success',
                    'pendCount' => $penCount,
                    'pendId' => $penLeadId,
                    'call_log_id' => $call_log,
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'pendCount' => 0,
                    'pendId' => 0,
                    'call_log_id' => 0,
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function checkNumberInfo()
    {
        $model = new CommonNumberModel();
        $modelL = new LeadModel();
        $log = new LeadCallLogModel();
        $common = new Common();
        $valid = new Validation();
        $acmodel = new LeadActivityModel();
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
            $num = $this->request->getVar('phone');
            $res = $model->where('cn_number', $this->db->escapeString($num))->first();
            if ($res == null && (substr($num, 0, 2) == '05' || substr($num, 0, 5) == '97105' || substr($num, 0, 5) == '97150')) {
                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as pc,lead_id');
                $builder->where('status_id', '1');
                $builder->where('phone',  $num);
                $queryp = $builder->get();
                $resultp = $queryp->getRow();
                $penCount =  $resultp->pc;
                $penLeadId = $resultp->lead_id;
                if ($penCount == 0) {
                    $builder = $this->db->table('leads');
                    $builder->select('count(leads.lead_id) as pc,lead_id');
                    $builder->where('status_id', '7');
                    $builder->where('phone',  $num);
                    $queryp = $builder->get();
                    $resultp = $queryp->getRow();
                    $penCount =  $resultp->pc;
                    $penLeadId = $resultp->lead_id;
                    if ($penCount == 0) {
                        $penLeadId = $this->createLead($this->request->getVar('phone'));
                    } else {
                        $penLeadId = $resultp->lead_id;
                    }
                    $logdata = [
                        'lcl_lead_id' => $penLeadId,
                        'lcl_purpose_note' => "First Pickup",
                        'lcl_call_to' => $this->request->getVar('call_to'),
                        // 'lcl_call_time'=>$this->request->getVar('calltime') ,
                        //'lcl_time'=>$this->request->getVar('calltime'),
                        'lcl_phone' => $this->request->getVar('phone'),
                        'lead_createdby' => $tokendata['uid'],
                        'ystar_call_id' => $this->request->getVar('call_id'),
                        'lcl_created_on'=> date("Y-m-d H:i:s"),
                    ];
                    $log->insert($logdata);
                }
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
                return $this->respond($response, 200);
            }
            $response = [
                'ret_data' => 'success',
                'pendCount' => $penCount,
                'pendId' => $penLeadId
            ];
            return $this->respond($response, 200);
        }
    }
    public function createLead($phone)
    {
        $acmodel = new LeadActivityModel();
        $modelL = new LeadModel();
        $cust_mastr_model = new CustomerMasterModel();
        $maraghi_cust_model = new MaragiCustomerModel();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        $builder = $this->db->table('sequence_data');
        $builder->selectMax('current_seq');
        $query = $builder->get();
        $row = $query->getRow();
        $code = $row->current_seq;
        $seqvalfinal = $row->current_seq;
        if (strlen($row->current_seq) == 1) {
            $code = "ALMLD-000" . $row->current_seq;
        } else if (strlen($row->current_seq) == 2) {
            $code = "ALMLD-00" . $row->current_seq;
        } else if (strlen($row->current_seq) == 3) {
            $code = "ALMLD-0" . $row->current_seq;
        } else {
            $code = "ALMLD-" . $row->current_seq;
        }
        $cust_id = 0;
        $data = [
            'lead_code' => $code,
            'phone' => $this->request->getVar('phone'),
            'status_id' => 7,
            'cus_id' =>  $cust_id,
            'source_id' => 1,
            'purpose_id' => 0,
            'lang_id' => 1,
            'lead_createdby' => $tokendata['uid'],
            'lead_creted_date' => date("Y-m-d H:i:s"),
            //'status_id'=>1,

        ];
        $ph = substr($phone, -9);
        $patern = $ph;
        $resC = $cust_mastr_model->like('cust_phone', $patern)->first();
        if ($resC) {
            $merge = [
                'cus_id' =>  $resC['cus_id'],
                'name' => $resC['cust_name'],
                'address' => $resC['cust_address'],
            ];
            $data = array_merge($data, $merge);
        } else {
            $maraghi_data = $maraghi_cust_model->like('phone', $patern)->join('customer_type', 'customer_type.cst_code = customer_type')->join('country_master', 'country_master.country_code = country')->select('customer_code,cst_id,customer_title,customer_name,addr1,city,country_master.id,phone')->first();
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
                    'cust_source' => 0

                ];
                $ins_id = $cust_mastr_model->insert($custData);
                $custId = ['cus_id' =>  $ins_id, 'name' => $maraghi_data['customer_name'], 'address' => $maraghi_data['addr1']];
                $data = array_merge($data, $custId);
            }
        }

        $penLeadId = $modelL->insert($data);
        $acdata = [
            'lac_activity' => 'Created Lead ' . $code,
            'lac_activity_by' => $tokendata['uid'],
            'lac_lead_id' =>  $penLeadId,
        ];
        $acmodel->insert($acdata);
        $builder = $this->db->table('sequence_data');
        $builder->set('current_seq', ++$seqvalfinal);
        $builder->update();

        return $penLeadId;
    }
}
