<?php

namespace App\Controllers\User;

use App\Models\User\UserModel;
use App\Models\User\UserroleModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\UserActivityLog;
use App\Models\Settings\CommonSettingsModel;
use App\Models\User\UserNotificationModel;
use App\Models\Leads\LeadCallLogModel;
use App\Models\User\UserPerformanceModel;

use DateTime;

class UserController extends ResourceController
{

    use ResponseTrait;
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    /**
     * @api {get} user/usercontroller/ user list
     * @apiName User List 
     * @apiGroup User
     * @apiPermission User
     *
     * @apiSuccess {String}   ret_data success or fail.
     * @apiSuccess {Object}   userList Object containing user details
     *
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     * @apiErrorExample Response (example):
     *     HTTP/1.1 403 Forbidden
     *     {
     *       "error": "NotAuthenticated"
     *     }
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
            //   $this->insertUserLog('View Users List',$tokendata['uid']);
            $result = $usmodel->where('us_delete_flag', 0)
                ->join('user_roles', 'user_roles.role_id=us_role_id', 'left')
                ->join('user_group', 'user_group.ug_id=user_roles.role_groupid', 'left')
                ->select('us_id,us_firstname,us_lastname,us_password,us_phone,us_email,us_role_id,user_roles.role_name,us_laabs_id,us_date_of_joining,us_status_flag,user_group.ug_code,tr_grp_status,us_ext_name,ext_number,us_dept_id')
                ->findAll();
            if ($result) {

                $data['ret_data'] = "success";
                $data['userList'] = $result;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        }
    }

    /**
     * @api {get} user/usercontroller/:id  User details by user id
     * @apiName User details by user id
     * @apiGroup User
     * @apiPermission User
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}    User object with user details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
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
            $user = $usmodel->where("us_id", $this->db->escapeString(base64_decode($id)))
                ->select('us_id,us_firstname,us_lastname,us_phone,us_email,us_role_id,us_date_of_joining,us_status_flag,us_laabs_id,ext_number,us_dept_id,ur.role_name,dp.dept_name')
                ->join('user_roles ur', 'ur.role_id=us_role_id', 'left')
                ->join('department dp', 'dp.dept_id=us_dept_id', 'left')->first();
            // $this->insertUserLog('View User Data for Update ', $tokendata['uid']);
            if ($user) {
                $data['user_details'] = $user;
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail11";
                return $this->respond($data, 200);
            }
        }
    }



    /**
     * @api {post} user/usercontroller/ User add 
     * @apiName User add
     * @apiGroup user
     * @apiPermission user
     *
     *
     * @apiBody {String} us_firstname  user first name
     * @apiBody {String} us_lastname user second name
     * @apiBody {String} us_phone user phone number
     * @apiBody {String} us_password user password
     * @apiBody {String} us_role_id user role id
     * @apiBody {String} us_email user email
     * @apiBody {String} us_date_of_joining date of joining of the user
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function create()
    {
        $UserModel = new UserModel();
        $UserroleModel = new UserroleModel();
        $validModel = new Validation();
        $commonutils = new Common();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $user = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $rules = [
                'us_firstname' => 'required',
                'us_phone' => 'required|min_length[6]',
                'us_email' => 'required|valid_email',
                'us_password' => 'required',
                'us_role_id' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $encrypter = \Config\Services::encrypter();
            $builder = $this->db->table('system_datas');
            $builder->select('encryption_key');
            $query = $builder->get();
            $keydata = $query->getRow();
            $org_pass = $encrypter->decrypt(base64_decode($keydata->encryption_key));
            $user_pass = $commonutils->aes_encryption($org_pass, $this->request->getVar('us_password'));

            $userrole = $UserroleModel->where("role_id", $this->db->escapeString($this->request->getVar('us_role_id')))->first();
            $inData = [
                'us_firstname' => $this->request->getVar('us_firstname'),
                'us_lastname' => $this->request->getVar('us_lastname'),
                'us_phone' => $this->request->getVar('us_phone'),
                'us_email' => $this->request->getVar('us_email'),
                'us_role_id' => $this->request->getVar('us_role_id'),
                'us_date_of_joining' => $this->request->getVar('us_date_of_joining'),
                'us_password' => base64_encode($user_pass),
                'us_branch_id' => 1,
                'us_createdby' => $tokendata['uid'],
                'us_updated_by' => $tokendata['uid'],
                'ext_number' => $this->request->getVar('us_extension'),
                'us_ext_name' => $this->request->getVar('us_ext_name'),
                'tr_grp_status' => $this->request->getVar('tgvalue'),
                'us_dept_id' => $userrole['role_dept_id'],
                'us_dept_head' => $userrole['dept_head_status'],
                'us_laabs_id' => $this->request->getVar('us_laabs_id'),
            ];
            $result = $UserModel->insert($inData);
            if ($result) {
                $this->insertUserLog('New User Created ' . $this->request->getVar('us_firstname'), $tokendata['uid']);
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        }
    }

    /**
     * @api {post} user/usercontroller/update User update 
     * @apiName User update
     * @apiGroup user
     * @apiPermission user
     *
     *
     * @apiBody {String} us_id  user id
     * @apiBody {String} us_firstname  user first name
     * @apiBody {String} us_lastname user second name
     * @apiBody {String} us_phone user phone number
     * @apiBody {String} us_password user password
     * @apiBody {String} us_role_id user role id
     * @apiBody {String} us_email user email
     * @apiBody {String} us_date_of_joining date of joining of the user
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function update($id = null)
    {
        $UserModel = new UserModel();
        $UserroleModel = new UserroleModel();
        $validModel = new Validation();
        $commonutils = new Common();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $user = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $rules = [
                'us_id' => 'required',
                'us_firstname' => 'required',
                'us_email' => 'required|valid_email',
                'us_phone' => 'required|min_length[6]',
                'us_role_id' => 'required',
                'us_date_of_joining' => 'required'
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $encrypter = \Config\Services::encrypter();
            $builder = $this->db->table('system_datas');
            $builder->select('encryption_key');
            $query = $builder->get();
            // $keydata=$query->getRow();
            // $org_pass =$encrypter->decrypt(base64_decode($keydata->encryption_key));
            // $user_pass=$commonutils->aes_encryption($org_pass,$this->request->getVar('us_password'));

            $userrole = $UserroleModel->where("role_id", $this->db->escapeString($this->request->getVar('us_role_id')))->first();
            $inData = [
                'us_firstname' => $this->request->getVar('us_firstname'),
                'us_lastname' => $this->request->getVar('us_lastname'),
                'us_phone' => $this->request->getVar('us_phone'),
                'us_email' => $this->request->getVar('us_email'),
                'us_role_id' => $this->request->getVar('us_role_id'),
                'us_date_of_joining' => $this->request->getVar('us_date_of_joining'),
                // 'us_password'=>base64_encode($user_pass),
                'us_updated_by' => $tokendata['uid'],
                'ext_number' => $this->request->getVar('us_extension'),
                'us_ext_name' => $this->request->getVar('us_ext_name'),
                'us_dept_id' => $userrole['role_dept_id'],
                'us_dept_head' => $userrole['dept_head_status'],
                'us_laabs_id' => $this->request->getVar('us_laabs_id'),
            ];
            $result = $UserModel->update($this->db->escapeString($this->request->getVar('us_id')), $inData);
            if ($result) {
                $this->insertUserLog('Update User ' . $this->request->getVar('us_firstname'), $tokendata['uid']);
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        }
    }

    /**
     * @api {post} user/usercontroller/changeuserstatus user status change
     * @apiName User status change 
     * @apiGroup User
     * @apiPermission User
     *
     * @apiBody {String} user_id User id 
     * @apiBody {String} status status id 
     * 
     * @apiSuccess {String}   ret_data success or fail.
     *
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     * @apiErrorExample Response (example):
     *     HTTP/1.1 403 Forbidden
     *     {
     *       "error": "NotAuthenticated"
     *     }
     */
    public function changeuserstatus()
    {
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
            $user = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $data = [
                'us_status_flag' => $this->db->escapeString($this->request->getVar('status'))
            ];
            $results = $UserModel->update($this->db->escapeString(base64_decode($this->request->getVar('us_id'))), $data);
            if ($results) {
                $this->insertUserLog('Change User Status', $tokendata['uid']);
                $data['ret_data'] = "success";
                $data['status'] = $this->db->escapeString($this->request->getVar('status'));
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        }
    }


    /**
     * @api {get} user/usercontroller/delete user delete
     * @apiName User delete 
     * @apiGroup User
     * @apiPermission User
     *
     * @apiSuccess {String}   ret_data success or fail.
     *
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     * @apiErrorExample Response (example):
     *     HTTP/1.1 403 Forbidden
     *     {
     *       "error": "NotAuthenticated"
     *     }
     */
    public function delete($id = null)
    {
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
            $user = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            if ($id) {
                $data = [
                    'us_status_flag' => 1,
                    'us_delete_flag' => 1
                ];
                $results = $UserModel->update($this->db->escapeString(base64_decode($id)), $data);
                if ($results) {
                    $this->insertUserLog('Delete User', $tokendata['uid']);
                    $data['ret_data'] = "success";
                    return $this->respond($data, 200);
                } else {
                    $data['ret_data'] = "fail";
                    return $this->respond($data, 200);
                }
            }
        }
    }

    /**
     * @api {get} user/usercontroller/ user list by role id
     * @apiName User List 
     * @apiGroup User
     * @apiPermission User
     *
     * @apiSuccess {String}   ret_data success or fail.
     * @apiSuccess {Object}   userList Object containing user details
     *
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     * @apiErrorExample Response (example):
     *     HTTP/1.1 403 Forbidden
     *     {
     *       "error": "NotAuthenticated"
     *     }
     */
    public function get_userlist_byroleid()
    {
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
            $user = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $result = $UserModel->where('us_status_flag', 0)->where('us_role_id',  $this->db->escapeString($this->request->getVar('role_id')))->join('user_roles', 'user_roles.role_id=us_role_id')->select('us_id,us_id,us_firstname,us_lastname,user_roles.role_name,us_dept_id')
                ->findAll();
            if ($result) {
                $data['ret_data'] = "success";
                $data['userList'] = $result;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        }
    }
    public function userDash()
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
            $crr = strtotime("now");

            if ($tokendata['iat'] < $user['last_login']) {

                $response = [
                    'verifylogin' => 'false',
                ];
                return $this->respond($response, 200);
            } else {
                $today = date('Y-m-d');

                // Today's Total Leads
                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as td_tot_Ld');
                $builder->where('DATE(lead_createdon)', $today);
                $builder->where('status_id !=', 7);
                $query = $builder->get();
                $result = $query->getRow();
                $td_tot_Ld =  $result->td_tot_Ld;

                // Total Pending Leads
                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as plc');
                $builder->where('status_id', 1);
                $query = $builder->get();
                $result = $query->getRow();
                $penleadCount =  $result->plc;

                //Lead Converted Today

                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as lct');
                $builder->where('status_id', 5);
                // $builder->where('DATE(lead_createdon)',$today);
                $query = $builder->get();
                $result = $query->getRow();
                $toLead =  $result->lct;
                //Total Lost Lead
                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as lct');
                $builder->where('status_id', 6);
                // $builder->where('DATE(lead_createdon)',$today);
                $query = $builder->get();
                $result = $query->getRow();
                $lostLead =  $result->lct;

                // $builder = $this->db->table('cust_call_logs');
                // $builder->select('count(call_id) as lc');
                // $builder->join('users','users.ext_number = call_to');
                // $builder->join('leads','leads.phone = call_from');
                // $builder->where('date(created_on)',$today);  
                // $builder->where('date(lead_createdon)',$today);  
                // $builder->where('status_id',5);
                // $query = $builder->get();
                // $result = $query->getRow();
                // $toLead=  $result->lc;

                // Total Active JobCards
                $builder = $this->db->table('cust_job_data_laabs');
                $builder->select('count(cust_job_data_laabs.job_no) as jc');
                $builder->where('job_status', 'OPN');
                $builder->orWhere('job_status', 'WIP');
                $query = $builder->get();
                $result = $query->getRow();
                $jcCount =  $result->jc;

                // Today's Total Leads Creted By Logged User
                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as us_tot_Ld');
                $builder->where('DATE(lead_createdon)', $today);
                $builder->where('lead_createdby', $tokendata['uid']);
                $builder->where('status_id !=', 7);
                $query = $builder->get();
                $result = $query->getRow();
                $us_tot_Ld =  $result->us_tot_Ld;

                //Total Pending Leads Assigned to the logged user
                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as ulc');
                $builder->where('status_id', 1);
                $builder->where('assigned', $tokendata['uid']);
                $query = $builder->get();
                $result = $query->getRow();
                $UsleadCount =  $result->ulc;
                $response = [
                    'ret_data' => 'success',
                    'todayTotLd' => $td_tot_Ld,
                    'totPendLd' => $penleadCount,
                    'todayCallToLead' => $toLead,
                    'todayTotLdUs' => $us_tot_Ld,
                    'jcCount' => $jcCount,
                    'totPendLdUs' => $UsleadCount,
                    'totalostlead' => $lostLead
                ];
                return $this->respond($response, 200);









                // // Today's Total pending Leads Count
                //         $builder = $this->db->table('leads');
                //         $builder->select('count(leads.lead_id) as lc');
                //         $builder->where('status_id',1); 
                //         $builder->where('date(lead_createdon)',$today);                   
                //         $query = $builder->get();
                //         $result = $query->getRow();
                //         $leadCount=  $result->lc; 

                // // Total Customers Count
                //         $builder = $this->db->table('customer_master');
                //         $builder->select('count(customer_master.cus_id) as cc');
                //         $builder->where('cust_delete_flag',0);                
                //         $query = $builder->get();
                //         $result = $query->getRow();
                //         $cusCount=  $result->cc; 

                // // Total Active JobCards
                //         $builder = $this->db->table('cust_job_data_laabs');
                //         $builder->select('count(cust_job_data_laabs.job_no) as jc');
                //         $builder->where('job_status','OPN');                
                //         $query = $builder->get();
                //         $result = $query->getRow();
                //         $jcCount=  $result->jc; 

                //  // Total Campaigns
                //         $builder = $this->db->table('campaign');
                //         $builder->select('count(campaign.camp_id ) as cnc');
                //         $builder->where('camp_delete_flag',0);                
                //         $query = $builder->get();
                //         $result = $query->getRow();
                //         $cmpCount=  $result->cnc; 

                // // Today's Total Leads Assigned to the logged user
                //         $builder = $this->db->table('leads');
                //         $builder->select('count(leads.lead_id) as ulc');
                //     // $builder->where('status_id',1);  
                //         $builder->where('assigned',$tokendata['uid']); 
                //         $builder->where('date(lead_createdon)',$today);       
                //         $query = $builder->get();
                //         $result = $query->getRow();
                //         $UsleadCount=  $result->ulc; 

                // // Total Pending Leads
                //         $builder = $this->db->table('leads');
                //         $builder->select('count(leads.lead_id) as plc');
                //         $builder->where('status_id',1); 
                //         $query = $builder->get();
                //         $result = $query->getRow();
                //         $penleadCount=  $result->plc;

                //         $response = [
                //             'ret_data'=>'success',
                //             'leadCount'=> $leadCount,
                //             'cusCount'=> $cusCount,  
                //             'jcCount'=> $jcCount,
                //             'cmpCount'=> $cmpCount ,
                //             'usleadCount'=> $UsleadCount, 
                //             'pendLead'=>$penleadCount                                
                //         ];
                //         return $this->respond($response,200);
            }
        }
    }
    public function verifyLogin()
    {
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
            $user = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['iat'] < $user['last_login'] || $user['last_login'] == '') {
            echo "iat" . $tokendata['iat'];
            echo "\n";
            echo "last" . $user['last_login'];
            echo "\n";
            echo "LOGIN NOW";
        } else {
            echo "iat" . $tokendata['iat'];
            echo "\n";
            echo "\n";
            echo "last" . $user['last_login'];
            echo "\n";
            echo "\n";
            echo "ALREADY LOGIN";
        }
        //         print_r($tokendata);
        //         print_r($heddata);
        // print_r($user );die;
    }
    public function call_count()
    {
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
            $user = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {

            $today = date('Y-m-d');
            $userid = $tokendata['uid'];
            $builder = $this->db->table('cust_call_logs');
            $builder->select('count(call_id) as tc');
            $builder->join('users', 'users.ext_number = call_to');
            $builder->where('date(created_on)', $today);
            $query = $builder->get();
            $result = $query->getRow();
            $totalCall =  $result->tc;

            $builder = $this->db->table('cust_call_logs');
            $builder->select('count(call_id) as ac');
            $builder->join('users', 'users.ext_number = call_to');
            $builder->where('date(created_on)', $today);
            $builder->where('status', 'ANSWERED');
            $query = $builder->get();
            $result = $query->getRow();
            $answerCall =  $result->ac;

            $builder = $this->db->table('cust_call_logs');
            $builder->select('count(call_id) as nc');
            $builder->join('users', 'users.ext_number = call_to');
            $builder->where('date(created_on)', $today);
            $builder->where('status', 'NO ANSWER');
            $query = $builder->get();
            $result = $query->getRow();
            $notansCall =  $result->nc;

            $builder = $this->db->table('cust_call_logs');
            $builder->select('count(call_id) as lc');
            $builder->join('users', 'users.ext_number = call_to');
            $builder->join('leads', 'leads.phone = call_from');
            $builder->where('date(created_on)', $today);
            $builder->where('date(lead_createdon)', $today);
            $query = $builder->get();
            $result = $query->getRow();
            $toLead =  $result->lc;

            $builder = $this->db->table('cust_call_logs');
            $builder->select('count(call_id) as unc');
            $builder->join('users', 'users.ext_number = call_to');
            $builder->where('date(created_on)', $today);
            $builder->where('status', 'NO ANSWER');
            $builder->where('users.us_id', $tokendata['uid']);
            $query = $builder->get();
            $result = $query->getRow();
            $notansCallUs =  $result->unc;

            $builder = $this->db->table('cust_call_logs');
            $builder->select('count(call_id) as tc');
            $builder->join('users', 'users.ext_number = call_to');
            $builder->where('date(created_on)', $today);
            $builder->where('users.us_id', $tokendata['uid']);
            $query = $builder->get();
            $result = $query->getRow();
            $totCallUs =  $result->tc;

            // $builder = $this->db->table('cust_call_logs');
            // $builder->select('count(call_id) as ulc');
            // $builder->join('users','users.ext_number = call_to');
            // $builder->join('leads','leads.phone = call_from');
            // $builder->where('date(created_on)',$today);  
            // $builder->where('date(lead_createdon)',$today); 
            // $builder->where('users.us_id',$tokendata['uid']);   
            // $query = $builder->get();
            // $result = $query->getRow();
            // $toLeadUS=  $result->ulc;

            //Lead Converted Today For User

            $builder = $this->db->table('leads');
            $builder->select('count(leads.lead_id) as lct');
            $builder->where('assigned', $tokendata['uid']);
            $builder->where('status_id', 5);
            $builder->where('date(lead_createdon)', $today);
            $query = $builder->get();
            $result = $query->getRow();
            $toLeadUS =  $result->lct;

            $response = [
                'ret_data' => 'success',
                'totalCall' => $totalCall,
                'ansCall' => $answerCall,
                'noAnsCall' => $notansCall,
                'callToLead' => $toLead,
                'notansCallUs' => $notansCallUs,
                'allCallUs' => $totCallUs,
                'toLeadUS' => $toLeadUS
            ];
            return $this->respond($response, 200);
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
     * @api {post} user/usercontroller/changeTrustedGrpStatus trusted group status change
     * @apiName Trusted group status change 
     * @apiGroup User
     * @apiPermission User
     *
     * @apiBody {String} user_id User id 
     * @apiBody {String} status status id 
     * 
     * @apiSuccess {String}   ret_data success or fail.
     *
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     * @apiErrorExample Response (example):
     *     HTTP/1.1 403 Forbidden
     *     {
     *       "error": "NotAuthenticated"
     *     }
     */
    public function changeTrustedGrpStatus()
    {
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
            $user = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $data = [
                'tr_grp_status' => $this->db->escapeString($this->request->getVar('status'))
            ];
            $results = $UserModel->update($this->db->escapeString(base64_decode($this->request->getVar('us_id'))), $data);
            if ($results) {
                $this->insertUserLog('Change Trusted Group Status', $tokendata['uid']);
                $data['ret_data'] = "success";
                $data['status'] = $this->db->escapeString($this->request->getVar('status'));
                if ($tokendata['uid'] != base64_decode($this->request->getVar('us_id'))) {
                    if ($this->request->getVar('status') == '1') {
                        $note = "You are Added To Trusted Group";
                    } else {
                        $note = "You are Removed From Trusted Group";
                    }

                    $indata = array('un_title' => 'Trusted Group', 'un_note' => $note, 'un_to' => $this->db->escapeString(base64_decode($this->request->getVar('us_id'))), 'un_from' => $tokendata['uid']);
                    $this->insertUserNoti($indata);
                }
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        }
    }



    /**
     * @api {post} User/getVerificationNumber Verification Number 
     * @apiName Verification Number 
     * @apiGroup super admin
     * @apiPermission super admin
     *
     *@apiBody {String} number Number
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function getVerificationNumber()
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

            // $result = $model->where('vn_delete_flag', 0)->first();
            $builder = $this->db->table('common_settings');
            $builder->where('cst_id', 1);
            $query = $builder->get();
            $row = $query->getRow();
            if ($row == '') {
                $result = 0;
            } else {
                $result = $row->verification_number;
            }

            $response = [
                'ret_data' => 'success',
                'number' => $result
            ];
            return $this->respond($response, 200);
        }
    }
    public function insertUserNoti($data)
    {
        $builder = $this->db->table('users');
        $builder->select('FCM_token');
        $builder->where('us_id', $data['un_to']);
        $query = $builder->get();
        $row = $query->getRow();
        if ($row == '') {
            $token = 0;
        } else {
            $token = $row->FCM_token;
        }

        $post_data = '{
            "to" : "' . $token . '",
            "data" : {
              "body" : "",
              "title" : "' . $data['un_title'] . '",
              "message" : "' . $data['un_note'] . '",
            },
            "notification" : {
                 "body" : "' . $data['un_note'] . '",
                 "title" : "' . $data['un_title'] . '",                   
                 "message" : "' . $data['un_note'] . '",
                "icon" : "new",
                "sound" : "default"
                },

          }';
        $URL = 'https://fcm.googleapis.com/fcm/send';
        $ch = curl_init($URL);
        $headers = array(
            "Content-type: application/json",
            "Authorization: key=AAAA90QtRBg:APA91bFY1RSS4WP7QpR74Th23iTGMWIzZ0EaDc6tVEpeyGbr-HFXjzyP1UdHf2vySK5g0cyQCtuOQnIswuFbr2Ml2o5nSzL6R6eP5gbdeVNW-M1nFhvz2D7ivy3HrRneAaoRq4SfPisx",
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);

        $model = new UserNotificationModel();
        $results = $model->insert($data);
    }
    public function dashboardCards()
    {

        $leadlogmodel = new LeadCallLogModel();


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

            // $phone = $this->request->getVar('phone');
            // $ph = substr($phone, -7);
            // $patern = $ph; 

            $call_to = $this->request->getVar('ext_no');

            $today = date("Y-m-d");


            $builder = $this->db->table('leads');
            $builder->select('count(leads.lead_id) as lct');
            $builder->where('lead_createdby', $tokendata['uid']);
            $builder->where('status_id', 7);
            $builder->where('date(lead_createdon)', $today);
            $query = $builder->get();
            $result = $query->getRow();
            $noLead =  $result->lct;

            $builder = $this->db->table('lead_call_log');
            $builder->select('count(lcl_id) as lc');
            $builder->where('lcl_pupose_id !=', 0);
            $builder->where('lcl_call_to', $call_to);
            $builder->where('DATE(lcl_created_on)', $today);
            $query = $builder->get();
            $result = $query->getRow();
            $noLog =  $result->lc;

            $builder = $this->db->table('leads');
            $builder->select('count(lead_id) as dl');
            $builder->where('lead_createdby', $tokendata['uid']);
            $builder->where('date(lead_createdon)', $today);
            $builder->where('close_time != lead_creted_date');
            $query = $builder->get();
            $result = $query->getRow();
            $delayLead =  $result->dl;

            //  $leadlog = $leadlogmodel-> whereIn('lcl_call_time',$call_time)->where('lcl_call_to',$call_to)->where('lcl_pupose_id !=',0)->select('lcl_id,lcl_time,lcl_lead_id,RIGHT(lcl_phone,7) as phon_uniq,lcl_call_time')->find();




            // $leadlog = $leadlogmodel-> whereIn('lcl_call_time',$call_time)->where('lcl_call_to',$call_to)->select('lcl_id,lcl_time,lcl_lead_id,RIGHT(lcl_phone,7) as phon_uniq,lcl_call_time')->find();

            $response = [
                'ret_data' => 'success',
                'noLead' => $noLead,
                'noLog' => $noLog,
                'dlLead' => $delayLead
            ];
            return $this->respond($response, 200);
        }
    }

    function best()
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

            $startdate = $this->request->getVar('startdate');
            $enddate = $this->request->getVar('enddate');


            $builder = $this->db->table('leads');
            $builder->select('COUNT(leads.lead_id) as con,lead_createdby,us_firstname,ext_number');
            $builder->join('users', 'users.us_id = lead_createdby');
            $builder->where('status_id', 5);
            $builder->where('conv_cust_on >=', $startdate);
            $builder->where('conv_cust_on <=', $enddate);
            $builder->groupBy('lead_createdby');
            $builder->groupBy('lead_createdby');
            $builder->orderBy('con', 'DESC');
            $builder->limit(1);
            $query = $builder->get();
            $result = $query->getRow();
            if ($result) {
                $user =  $result->lead_createdby;

                $builder = $this->db->table('leads');
                $builder->select('COUNT(leads.lead_id) as pending');
                $builder->where('status_id', 1);
                $builder->where('lead_createdon >=', $startdate);
                $builder->where('lead_createdon <=', $enddate);
                $builder->where('lead_createdby', $user);
                $builder->groupBy('lead_createdby');
                $query = $builder->get();
                $penresult = $query->getRow();
                $penresult ? $pending = $penresult->pending : $pending = 0;


                $res = ['pending' => $pending, 'convert' => $result->con, 'username' => $result->us_firstname, 'ext_number' => $result->ext_number];
                $response = [
                    'ret_data' => 'success',
                    'data' => $res,
                ];
                return $this->respond($response, 200);
            } else {
                $res = ['pending' => 0, 'convert' => 0, 'username' => null, 'ext_number' => 0];
                $response = [
                    'ret_data' => 'fail',
                    'data' => $res,
                ];
                return $this->respond($response, 200);
            }
        }










        //     $builder = $this->db->table('leads');
        //     $builder->select('IFNULL(COUNT(leads.lead_id), 0) as pending ,lead_createdby,MONTH(lead_createdon),us_firstname,ext_number');
        //     $builder->join('users','users.us_id = lead_createdby');
        //     $builder->where('status_id',1);   
        //     $builder->where('DATE(lead_createdon) >=',);            
        //     $builder->groupBy('lead_createdby'); 
        //     $builder->orderBy('pending','DESC'); 
        //     $builder->limit(1); 
        //     $query = $builder->get();
        //     $penresult = $query->getRow();

        //     $user =  $penresult->lead_createdby;           

        //     $builder = $this->db->table('leads');
        //     $builder->select('IFNULL(COUNT(leads.lead_id), 0) as con');
        //     $builder->where('status_id',5);   
        //     $builder->where('MONTH(lead_createdon)',10);
        //     $builder->where('lead_createdby',$user);  
        //     $builder->groupBy('lead_createdby');
        //     $query = $builder->get();
        //     $result = $query->getRow();
        //     $result?$con=$result->con:$con=0;          


        //     $res = ['pending'=> $penresult->pending,'convert'=> $con,'username'=>$penresult->us_firstname,'ext_number'=>$penresult->ext_number];
        //     $response = [
        //         'ret_data'=>'success',
        //         'data'=>$res,                  
        //         ];
        //         return $this->respond($response,200);
        // }

    }
    public function performance()
    {
        $model = new UserPerformanceModel();
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

            $today = date("Y-m-d");
            $toda = date("d-m-Y");
            $point = 0;

            $builder = $this->db->table('user_performance');
            $builder->select('up_point,up_id');
            $builder->where('up_date', $toda);
            $builder->where('up_user_id', $tokendata['uid']);
            $builder->limit(1);
            $query = $builder->get();
            $re = $query->getRow();

            $builder = $this->db->table('leads');
            $builder->select('COUNT(leads.lead_id) as con');
            $builder->join('users', 'users.us_id = lead_createdby');
            $builder->where('status_id', 5);
            $builder->where('conv_cust_on', $today);
            $builder->where('lead_createdby', $tokendata['uid']);
            $builder->limit(1);
            $query = $builder->get();
            $result = $query->getRow();

            $builder = $this->db->table('lost_customer_list');
            $builder->select("COUNT(lcst_id) as total_lc_count,str_to_date(lcst_due_date_to, '%d/%m/%Y')  as duedate");
            $builder->where("str_to_date(lcst_due_date_to, '%d/%m/%Y')", $today);
            $builder->where('lcst_assign', $tokendata['uid']);
            $query = $builder->get();
            $res = $query->getRow();

            $builder = $this->db->table('lost_customer_list');
            $builder->select("COUNT(lcst_id) as null_lc_count,str_to_date(lcst_due_date_to, '%d/%m/%Y')  as duedate");
            $builder->where("str_to_date(lcst_due_date_to, '%d/%m/%Y')", $today);
            $builder->where('lcst_assign', $tokendata['uid']);
            $builder->where('lcst_note !=', NULL);
            $query = $builder->get();
            $resN = $query->getRow();

            $lc_count =  $res->total_lc_count -  $resN->null_lc_count;
            $user_ext = $this->request->getVar('user_ext');
            $total_call = $this->request->getVar('total_call');
            $total_miss_call = $this->request->getVar('total_miss_call');
            $misscall_ring_30 = $this->request->getVar('misscall_ring_30');
            $misscall_ring_less = $this->request->getVar('misscall_ring_less'); // miss call ring duration <= 30

            $misscall_per = round(($total_miss_call / $total_call) * 100);
            if ($total_miss_call > 0) {
                $misscallring_per = round(($misscall_ring_30 / $total_miss_call) * 100);
                $misscallring_per_30 = round(($misscall_ring_less / $total_miss_call) * 100);
            } else {
                $misscallring_per = 50;
                $misscallring_per_30 = 50;
            }


            if ($lc_count > 5) // converted lead count
            {
                $point = $point + 1;
            }
            if ($misscallring_per <= 50) {
                $point = $point + 1;
            }
            if ($misscallring_per_30 >= 50) {
                $point = $point + 1;
            }
            if ($misscall_per >= 50) {
                $point = $point + 1;
            }
            if ($misscallring_per_30 >= 50) {
                $point = $point + 1;
            }

            $data = [
                'up_user_id'    =>  $tokendata['uid'],
                'up_point'   =>  $point,
                'up_date' => $toda,
                'up_created_by' =>  $tokendata['uid']
            ];
            if ($re) // userid and date exist update the point
            {
                $updata = ['up_point' => $re->up_point + $point];

                $res = $model->where("up_id",  $re->up_id)->set($updata)->update();
            } else {
                $results = $model->insert($data);
            }






            $response = [
                'ret_data' => 'success',
                'data' => $re,
            ];
            return $this->respond($response, 200);
        }
    }
    function getUserPerformData()
    {
        $model = new UserPerformanceModel();
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

            $start =  $this->request->getVar('startdate');
            $end =  $this->request->getVar('enddate');
            $us = $this->request->getVar('user_id');

            $endDate = date("d-m-Y", strtotime($end));


            $builder = $this->db->table('leads');
            $builder->select('COUNT(leads.lead_id) as con');
            $builder->join('users', 'users.us_id = lead_createdby');
            $builder->where('status_id', 5);
            $builder->where('conv_cust_on >=', $start);
            $builder->where('conv_cust_on <=', $end);
            $builder->where('lead_createdby', $us);
            $builder->limit(1);
            $query = $builder->get();
            $result = $query->getRow();


            $builder = $this->db->table('leads');
            $builder->select('COUNT(leads.lead_id) as new_cus_lead');
            $builder->join('users', 'users.us_id = lead_createdby');
            $builder->where('cus_id', 0);
            $builder->where('lead_updatedon >=', $start);
            $builder->where('lead_updatedon <=', $end);
            $builder->where('lead_createdby', $us);
            $builder->limit(1);
            $query = $builder->get();
            $res_new = $query->getRow();

            $builder = $this->db->table('leads');
            $builder->select('COUNT(leads.lead_id) as ext_cus_lead');
            $builder->join('users', 'users.us_id = lead_createdby');
            $builder->where('cus_id !=', 0);
            $builder->where('lead_updatedon >=', $start);
            $builder->where('lead_updatedon <=', $end);
            $builder->where('lead_createdby', $us);
            $builder->limit(1);
            $query = $builder->get();
            $res_ext = $query->getRow();

            $builder = $this->db->table('lost_customer_list');
            $builder->select("COUNT(lcst_id) as total_lc_count,str_to_date(lcst_due_date_to, '%d/%m/%Y')  as duedate");
            // $builder->where("str_to_date(lcst_due_date_to, '%d/%m/%Y') >=",$start);
            $builder->where("str_to_date(lcst_due_date_to, '%d/%m/%Y')", $end);
            $builder->where('lcst_assign', $us);
            $query = $builder->get();
            $res = $query->getRow();

            $builder = $this->db->table('lost_customer_list');
            $builder->select("COUNT(lcst_id) as null_lc_count,str_to_date(lcst_due_date_to, '%d/%m/%Y')  as duedate");
            //  $builder->where("str_to_date(lcst_due_date_to, '%d/%m/%Y') >=",$start);
            $builder->where("str_to_date(lcst_due_date_to, '%d/%m/%Y')", $end);
            $builder->where('lcst_assign', $us);
            $builder->where('lcst_note !=', NULL);
            $query = $builder->get();
            $resN = $query->getRow();

            $lc_count =  $res->total_lc_count -  $resN->null_lc_count;

            $resP = $model->where('up_user_id', $us)->where('DATE(up_created_on) >=', $start)->where('DATE(up_created_on) <=', $end)->groupBy('DATE(up_created_on)')
                ->select('SUM(up_point) as count,DATE(up_created_on) as dd')->findAll();
            if ($resP) {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'data' => $resP,
                    'lost_cust' => $lc_count,
                    'lost_cust_total' => $res->total_lc_count,
                    'lead_conv' => $result->con,
                    'ext_cus_lead' => $res_ext->ext_cus_lead,
                    'new_cus_lead' => $res_new->new_cus_lead,

                ];
                return $this->respond($response, 200);
            } else {
                //  $this->insertUserLog('View Lead List',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'data' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    function changeUserPassword()
    {
        $UserModel = new UserModel();
        $UserroleModel = new UserroleModel();
        $validModel = new Validation();
        $commonutils = new Common();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $user = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $rules = [
                'password' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $encrypter = \Config\Services::encrypter();
            $builder = $this->db->table('system_datas');
            $builder->select('encryption_key');
            $query = $builder->get();
            $keydata = $query->getRow();
            $org_pass = $encrypter->decrypt(base64_decode($keydata->encryption_key));
            $user_pass = $commonutils->aes_encryption($org_pass, $this->request->getVar('password'));
            $inData = [
                'us_password' => base64_encode($user_pass),
            ];

            $result = $UserModel->update($this->db->escapeString($this->db->escapeString(base64_decode($this->request->getVar('user_id')))), $inData);
            if ($result) {
                $this->insertUserLog('Change Password ' . $this->request->getVar('us_firstname'), $tokendata['uid']);
                $note = 'Password Changed';
                $ndata = array('un_title' => 'Change Password', 'un_note' => $note, 'un_to' => $this->db->escapeString(base64_decode($this->request->getVar('user_id'))), 'un_from' => $tokendata['uid']);
                $this->insertUserNoti($ndata);
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        }
    }

    function getSpecialUsers()
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
            //   $this->insertUserLog('View Users List',$tokendata['uid']);
            $result = $usmodel->where('us_delete_flag', 0)
                ->where('us_role_id=1 or us_role_id=2 or us_role_id=10 or us_role_id=13')
                ->join('user_roles', 'user_roles.role_id=us_role_id', 'left')
                ->join('user_group', 'user_group.ug_id=user_roles.role_groupid', 'left')
                ->select('us_id,us_firstname,us_lastname,us_password,us_phone,us_email,us_role_id,user_roles.role_name,us_laabs_id,us_date_of_joining,us_status_flag,user_group.ug_code,tr_grp_status,us_ext_name,ext_number,us_dept_id')
                ->findAll();
            if ($result) {

                $data['ret_data'] = "success";
                $data['userList'] = $result;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        }
    }

    public function checkTokenExpiry()
    {
        $validModel = new Validation();
        $commonutils = new Common();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
            else {
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            }
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
            else {
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            }
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
    }
}
