<?php

namespace App\Controllers\User;

use CodeIgniter\RESTful\ResourceController;
use App\Models\User\UserNotificationModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;

class UserNotification extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }



    /**
     * @api {get} User/UserNotificationr  Notification List
     * @apiName User/UserNotification
     * @apiGroup Super Admin
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   unlist  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */  
    public function index()
    {
        
        $model = new UserNotificationModel();
        $common =new Common();
        $valid=new Validation();  
        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));  
        
        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$user) return $this->fail("invalid user",400); 
        }
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
      
        if($tokendata['aud']=='superadmin' || $tokendata['aud']=='user'){

            $res= $model->where('un_delete_flag', 0)->where('un_status', 0)->where('un_to', $tokendata['uid'])->join('users','users.us_id = un_from')->select('DATE(un_created_on) as un_created,un_title,un_note,un_from,us_firstname,un_id,un_link_id,un_link')->findAll();
            if($res)
            {
                $response = [
                    'ret_data'=>'success',
                    'unlist'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'success',
                    'unlist'=>[]
                ];
                return $this->respond($response,200);
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
        $model = new UserNotificationModel();
        $common =new Common();
        $valid=new Validation();  
        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));  
        
        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$user) return $this->fail("invalid user",400); 
        }
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
      
        if($tokendata['aud']=='superadmin' || $tokendata['aud']=='user'){

        

            $res = $model->where('un_id',$this->db->escapeString($id))->first();
            if($res)
            {
                $response = [
                    'ret_data'=>'success',
                    'unlist'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'success',
                    'unlist'=>[]
                ];
                return $this->respond($response,200);
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
     * @api {post} User/UserNotification User Notification create
     * @apiName User Notification create
     * @apiGroup super admin
     * @apiPermission super admin
     *
     *@apiBody {String} note_to Notification to 
     *@apiBody {String} note_title Notification Title
     *@apiBody {String} note Notification
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function create()
    {
        $model = new UserNotificationModel();
        $common =new Common();
        $valid=new Validation();  
        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));  
        
        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$user) return $this->fail("invalid user",400); 
        }
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata['aud']=='superadmin' || $tokendata['aud']=='user'){

            $rules = [
                'note'=>'required',
                'note_to'=>'required',
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'un_title'=> $this->request->getVar('note_title'),
                'un_note'=> $this->request->getVar('note'),
                'un_to'=> $this->request->getVar('note_to'),
                'un_from'=>$tokendata['uid'] ,
                'un_created_by'=>$tokendata['uid'] 
            ];
        
            if ($model->insert($data) === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            }
            else{
           
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
    function changeUserNotiStatus()
    {
        $model = new UserNotificationModel();
        $common =new Common();
        $valid=new Validation();  
        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));  
        
        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$user) return $this->fail("invalid user",400); 
        }
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata['aud']=='superadmin' || $tokendata['aud']=='user'){

            $data = [
                'un_status' => 1,                
            ];
           if($model->where('un_id',  $this->db->escapeString($this->request->getVar('id')))->set($data)->update() === false )
            {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
                
            }
            else
            {
              // $this->insertUserLog('Change',$tokendata['uid']);
              $res= $model->where('un_delete_flag', 0)->where('un_status', 0)->where('un_to', $tokendata['uid'])->join('users','users.us_id = un_from')->select('DATE(un_created_on) as un_created,un_title,un_note,un_from,us_firstname,un_id')->findAll();
              
                $response = [
                    'ret_data' => 'success',
                    'unlist'=>  $res
                ];
                return $this->respond($response, 200);

            }
        }

    }
    function notificationCount()
    {
        
        $model = new UserNotificationModel();
        $common =new Common();
        $valid=new Validation();  
        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));  
        
        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$user) return $this->fail("invalid user",400); 
        }
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata['aud']=='superadmin' || $tokendata['aud']=='user'){

            $builder = $this->db->table('user_notification');
            $builder->select('count(un_id ) as nct');               
            $builder->where('un_status',0); 
            $builder->where('un_delete_flag',0); 
            $builder->where('un_to',$tokendata['uid'] );           
            $query = $builder->get();
            $result = $query->getRow();
            $nct=  $result->nct; 
            
            $response = [
                'ret_data'=>'success',
                'notiCount'=> $nct                       
            ];
            return $this->respond($response,200);


        }
    }
    public function sendPushNotification()
    {
       
        // $sender_id = 'c8cW05Vlq6x-e7xVbB29HG:APA91bFCDCYtpz13WITTPcf5Ohgte42NCL3fzSdcBdVMlibv5a-quJxm6ak62IrOWwqN8vd8sOgtCnZcXe0kA-VtCBXHxpMu30srLW2U6q8moIVTrZnCA8uQi_5EfIpjbJvRkSELm5_r';
        
        // $title = 'Task Assigned'; 
        // $message = 'New Task Assigned';
     
 
        // $accesstoken = 'key=AAAA90QtRBg:APA91bFY1RSS4WP7QpR74Th23iTGMWIzZ0EaDc6tVEpeyGbr-HFXjzyP1UdHf2vySK5g0cyQCtuOQnIswuFbr2Ml2o5nSzL6R6eP5gbdeVNW-M1nFhvz2D7ivy3HrRneAaoRq4SfPisx';
 
  //      $URL = 'https://fcm.googleapis.com/fcm/send';
 
        // $post_data = array(
        //     'to' =>  "c8cW05Vlq6x-e7xVbB29HG:APA91bFCDCYtpz13WITTPcf5Ohgte42NCL3fzSdcBdVMlibv5a-quJxm6ak62IrOWwqN8vd8sOgtCnZcXe0kA-VtCBXHxpMu30srLW2U6q8moIVTrZnCA8uQi_5EfIpjbJvRkSELm5_r",
        //     'title' => "Task Assigned",                   
        //     "message" => "New Task Assigned",
        // );
            // $post_data = '{
            //     "to" : "c8cW05Vlq6x-e7xVbB29HG:APA91bFCDCYtpz13WITTPcf5Ohgte42NCL3fzSdcBdVMlibv5a-quJxm6ak62IrOWwqN8vd8sOgtCnZcXe0kA-VtCBXHxpMu30srLW2U6q8moIVTrZnCA8uQi_5EfIpjbJvRkSELm5_r",
            //     "data" : {
            //       "body" : "",
            //       "title" : "Task Assigned",
            //       "message" : "New Task Assigned",
            //     },
            //     "notification" : {
            //          "body" : "New Task Assigned",
            //          "title" : "Task Assigned",                   
            //          "message" : "New Task Assigned",
            //         "icon" : "new",
            //         "sound" : "default"
            //         },
 
            //   }';
           //  print_r($post_data);die;
 
        // $crl = curl_init();
        // $headr = array(
        //     "Content-type: application/json",
        //     "Authorization: key=AAAA90QtRBg:APA91bFY1RSS4WP7QpR74Th23iTGMWIzZ0EaDc6tVEpeyGbr-HFXjzyP1UdHf2vySK5g0cyQCtuOQnIswuFbr2Ml2o5nSzL6R6eP5gbdeVNW-M1nFhvz2D7ivy3HrRneAaoRq4SfPisx",
        //  );
        // $headr = array();
        // $headr[] = 'Content-type: application/json';
        // $headr[] = 'Authorization: ' . $accesstoken;
        // curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, false);
 
        // curl_setopt($crl, CURLOPT_URL, $URL);
        // curl_setopt($crl, CURLOPT_HTTPHEADER, $headr);
 
        // curl_setopt($crl, CURLOPT_POST, true);
        // curl_setopt($crl, CURLOPT_POSTFIELDS, $post_data);
        // curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
 
        // $rest = curl_exec($crl);
        // if ($rest === false) {
            // throw new Exception('Curl error: ' . curl_error($crl));
        //     print_r('Curl error: ' . curl_error($crl));
        //     $result_noti = 0;
        // } else {
 
        //     $result_noti = 1;
        // }
 
        
        $post_data = '{
            "to" : "c8cW05Vlq6x-e7xVbB29HG:APA91bFCDCYtpz13WITTPcf5Ohgte42NCL3fzSdcBdVMlibv5a-quJxm6ak62IrOWwqN8vd8sOgtCnZcXe0kA-VtCBXHxpMu30srLW2U6q8moIVTrZnCA8uQi_5EfIpjbJvRkSELm5_r",
            "data" : {
              "body" : "",
              "title" : "Task Assigned",
              "message" : "New Task Assigned",
            },
            "notification" : {
                 "body" : "New Task Assigned",
                 "title" : "Task Assigned",                   
                 "message" : "New Task Assigned",
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
curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = json_decode(curl_exec($ch));

curl_close($ch);
var_dump($result);
        
        $response = [
            'ret_data'=>$result, 
          

        ];
        return $this->respond($response,200);
    }
    function updateFCMToken()
    {
        $model = new UserModel();
        $common =new Common();
        $valid=new Validation();  
        $heddata=$this->request->headers();
        $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));  
        
        if($tokendata['aud']=='superadmin'){
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$super) return $this->fail("invalid user",400);        
        }else if($tokendata['aud']=='user'){
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if(!$user) return $this->fail("invalid user",400); 
        }
        else{          
            $data['ret_data']="Invalid user";
            return $this->fail($data,400);
        }
        if($tokendata['aud']=='superadmin' || $tokendata['aud']=='user'){

            $data = [
                'FCM_token' => $this->db->escapeString($this->request->getVar('token')),                
            ];
           if($model->where('us_id',  $this->db->escapeString($tokendata['uid']))->set($data)->update() === false )
            {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
                
            }
            else
            {              
                $response = [
                    'ret_data' => 'success',
                ];
                return $this->respond($response, 200);

            }
    }
}
}
