<?php

namespace App\Controllers;


use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use App\Models\UserActivityLog;
use App\Models\Commonutils\PermittedIPModel;
use Config\Common;
use Config\Validation;
use CodeIgniter\API\ResponseTrait;
use Config\TwilioConfig;
use App\Models\User\UserroleModel;

class Login extends ResourceController
{
    use ResponseTrait;
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    public function user_login()
    {
        $model = new UserModel();
        $IPmodel = new PermittedIPModel();
        $logmodel = new UserActivityLog();
        $userroleModel = new UserroleModel();
        $common = new Common();
        $rules = [
            'email' => 'required',
            'password' => 'required',
        ];
        if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $res = $model->where('us_email', $this->request->getVar('email'))->where('us_status_flag', 0)->first();
        if (!$res) {
            $response = [
                'ret_data' => 'fail',
            ];
            return $this->respond($response, 200);
        } else {
            $encrypter = \Config\Services::encrypter();
            $builder = $this->db->table('system_datas');
            $builder->select('encryption_key');
            $query = $builder->get();
            $keydata = $query->getRow();
            $org_pass = $encrypter->decrypt(base64_decode($keydata->encryption_key));
            $aeskey = $common->aes_encryption($org_pass, $this->request->getVar('password'));
            $verify = strcmp(base64_encode($aeskey), $res['us_password']);
            // $verify=strcmp($org_pass,$this->request->getVar('userpassword'));
            if ($verify == 0) {
                // if ($res['tr_grp_status'] == 1) {
                //     $otpto = $res['us_phone'];
                //     $jwtres = $common->generate_user_jwt_token($res['us_id']);
                // } else {
                //     $builder = $this->db->table('common_settings');
                //     $query = $builder->get();
                //     $row = $query->getRow();
                //     $result = $row->verification_number;
                //     // $otpto =$result;
                //     $otpto = +971509766075;
                //     $jwtres = $common->generate_user_jwt_token($res['us_id']);
                //     // $jwtres = $common->generate_user_jwt_token($res['us_id']);
                // }





                // $token = $jwtres['token'];
                // if( $jwtres['iat'] < $res['last_login'] || $res['last_login'] =='')
                // {
                //     echo "iat".$jwtres['iat']; echo "\n";
                //     echo "last".$res['last_login'];echo "\n";
                //     echo "LOGIN NOW";
                // }  
                // else
                // {
                //     echo "iat".$jwtres['iat']; echo "\n";
                //     echo "last".$res['last_login'];echo "\n";
                //     echo "ALREADY LOGIN";
                // }    

                $twilioConfig = new TwilioConfig();
                $ip = $this->request->getIPAddress();

                // $ip='192.168.2.180';

                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    $data['ret_data'] = "fail";
                    return $this->respond($data);
                } else {

                    $re = $IPmodel->where('pip_address', $ip)->where('pip_delete_flag', 0)->select('pip_address,pip_id')->first();
                    if ($res['us_email'] == 'nirmal@life.com' || $res['us_email'] == 'jithin@life.com') {
                        $re = true;
                    }
                    $re = true; /// only for local
                    log_message('error',  "re 98 >>>>" . json_encode($re));
                    log_message('error',  "res 99 >>>>" . json_encode($res));

                    if (!$re) {
                        if ($res['tr_grp_status'] == '1') {
                            $originalPhone = $res['us_phone']; // Get the original phone number
                            $lastNineDigits = substr($originalPhone, -9); // Extract the last 9 digits
                            $otpto = '+971' . $lastNineDigits;
                        } else {
                            $data['ret_data'] = "fail";
                            return $this->respond($data);
                            // $builder = $this->db->table('verification_number');
                            // $builder->where('vn_delete_flag', 0);
                            // $query = $builder->get();
                            // $row = $query->getRow();
                            // $result = $row->vn_number;
                            // $otpto = $result;
                            //$otpto = +9710509766075;
                        }
                        $result = $twilioConfig->sendVerificationCode($otpto, "sms");
                        // $result = 'pending';
                        if ($result == 'pending') {
                            $jwtres = $common->generate_user_jwt_token($res['us_id']);
                            $token = $jwtres['token'];
                            $role_grp = $userroleModel->select('role_groupid')->where('role_id', $res['us_role_id'])->first();
                            $userdata = array(
                                "us_id" => $res['us_id'],
                                "us_firstname" => $res['us_firstname'],
                                "us_lastname" => $res['us_lastname'],
                                "us_email" => $res['us_email'],
                                "us_phone" => $res['us_phone'],
                                "us_role_id" => $res['us_role_id'],
                                "us_date_of_joining" => $res['us_date_of_joining'],
                                "us_status_flag" => $res['us_status_flag'],
                                "ext_number" => $res['ext_number'],
                                "us_token" => $token,
                                "us_ext_name" => $res['us_ext_name'],
                                "us_laabs_id" => $res['us_laabs_id'],
                                "us_role_grp" => $role_grp['role_groupid']
                            );
                            $data['user_details'] = $userdata;
                            $builder = $this->db->table('feature_role_mapping');
                            $builder->select('features_list.ft_id,features_list.ft_name,feature_actions.fa_id,feature_actions.fa_name');
                            $builder->where('frm_role_id', $res['us_role_id']);
                            $builder->join('user_roles', 'user_roles.role_id = feature_role_mapping.frm_role_id', 'INNER JOIN');
                            $builder->join('features_list', 'features_list.ft_id =feature_role_mapping.frm_feature_id', 'INNER JOIN');
                            $builder->join('feature_actions', 'feature_actions.fa_id=feature_role_mapping.frm_action_id', 'INNER JOIN');
                            $query = $builder->get();
                            $features = $query->getResultArray();

                            $data['access'] = $features;
                            $data['ret_data'] = "success";
                            $data['verify'] = 'false';
                            $data['us_phone'] = $res['us_phone'];

                            $indata = [
                                'login_status'    => 1,
                                'activeJwt'   =>  $token,
                                'last_login' => $jwtres['iat'],
                                'FCM_token'  =>  $this->request->getVar('fcm')
                            ];

                            $results = $model->update($res['us_id'], $indata);


                            $this->insertUserLog('Login', $res['us_id']);
                            return $this->respond($data, 200);
                        } else {
                            $data['ret_data'] = "fail";
                            return $this->respond($data);
                        }
                    } else {
                        $jwtres = $common->generate_user_jwt_token($res['us_id']);
                        $token = $jwtres['token'];
                        $role_grp = $userroleModel->select('role_groupid')->where('role_id', $res['us_role_id'])->first();
                        $userdata = array(
                            "us_id" => $res['us_id'],
                            "us_firstname" => $res['us_firstname'],
                            "us_lastname" => $res['us_lastname'],
                            "us_email" => $res['us_email'],
                            "us_phone" => $res['us_phone'],
                            "us_role_id" => $res['us_role_id'],
                            "us_date_of_joining" => $res['us_date_of_joining'],
                            "us_status_flag" => $res['us_status_flag'],
                            "ext_number" => $res['ext_number'],
                            "us_token" => $token,
                            "us_ext_name" => $res['us_ext_name'],
                            "us_laabs_id" => $res['us_laabs_id'],
                            "us_role_grp" => $role_grp['role_groupid']
                        );
                        $data['user_details'] = $userdata;
                        $builder = $this->db->table('feature_role_mapping');
                        $builder->select('features_list.ft_id,features_list.ft_name,feature_actions.fa_id,feature_actions.fa_name');
                        $builder->where('frm_role_id', $res['us_role_id']);
                        $builder->join('user_roles', 'user_roles.role_id = feature_role_mapping.frm_role_id', 'INNER JOIN');
                        $builder->join('features_list', 'features_list.ft_id =feature_role_mapping.frm_feature_id', 'INNER JOIN');
                        $builder->join('feature_actions', 'feature_actions.fa_id=feature_role_mapping.frm_action_id', 'INNER JOIN');
                        $query = $builder->get();
                        $features = $query->getResultArray();
                        $data['access'] = $features;
                        $data['ret_data'] = "success";
                        $data['verify'] = 'true';

                        $indata = [
                            'login_status'    => 1,
                            'activeJwt'   =>  $token,
                            'last_login' => $jwtres['iat'],
                            'FCM_token'  =>  $this->request->getVar('fcm')
                        ];

                        $results = $model->update($res['us_id'], $indata);
                        $this->insertUserLog('Login', $res['us_id']);
                        return $this->respond($data, 200);
                    }
                }
                // $result='pending';
                // if($result=='pending'){
                //     $token=$common->generate_user_jwt_token($res['us_id']);
                //     $userdata=array(
                //         "us_id"=> $res['us_id'],
                //         "us_firstname"=>$res['us_firstname'],
                //         "us_lastname"=> $res['us_lastname'],
                //         "us_email"=> $res['us_email'],
                //         "us_phone"=> $res['us_phone'],
                //         "us_role_id"=> $res['us_role_id'],
                //         "us_date_of_joining"=> $res['us_date_of_joining'],
                //         "us_status_flag"=> $res['us_status_flag'],
                //         "ext_number"=> $res['ext_number'],
                //         "us_token"=> $token,
                //     );
                //     $data['user_details']=$userdata;
                //     $builder = $this->db->table('feature_role_mapping');
                //     $builder->select('features_list.ft_id,features_list.ft_name,feature_actions.fa_id,feature_actions.fa_name');
                //     $builder->where('frm_role_id', $res['us_role_id']);
                //     $builder->join('user_roles', 'user_roles.role_id = feature_role_mapping.frm_role_id', 'INNER JOIN');
                //     $builder->join('features_list', 'features_list.ft_id =feature_role_mapping.frm_feature_id', 'INNER JOIN');
                //     $builder->join('feature_actions', 'feature_actions.fa_id=feature_role_mapping.frm_action_id', 'INNER JOIN');
                //     $query = $builder->get();
                //     $features = $query->getResultArray();
                //     $data['access']=$features;
                //     $data['ret_data']="success";      
                //     $response = [
                //         'ret_data'=>'success',
                //         'user_details'=>$res
                //     ];
                //     return $this->respond($data,200);
                // }else{
                //     $data['ret_data']="fail";
                // }

            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data);
            }
        }
    }
    public function verifyOTP()
    {
        $rules = [
            'otp' => 'required',
            'us_phone' => 'required',
        ];
        if (!$this->validate($rules)) return $data['ret_data'] = "fail";
        $twilioConfig = new TwilioConfig();
        $us_phone = $this->request->getVar('us_phone');
        $lastNineDigits = substr($us_phone, -9); // Get last 9 digits
        $newPhone = '+971' . $lastNineDigits;
        $verify = $twilioConfig->verifyVerificationCode($newPhone, $this->request->getVar('otp'));
        log_message('error',  "message >>>verify otp>>>>>>" . json_encode($verify));
        // $verify = "approved";
        if ($verify == "approved") {
            $data['ret_data'] = "success";
            return $this->respond($data);
        } else {
            $data['ret_data'] = "fail";
            return $this->respond($data);
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
}
