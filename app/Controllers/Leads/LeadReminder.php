<?php

namespace App\Controllers\Leads;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Leads\LeadReminderModel;
use App\Models\SuperAdminModel;
use App\Models\Leads\LeadActivityModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;
use App\Models\User\UserNotificationModel;

class LeadReminder extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
      /**
     * @api {get} leads/leadreminder  Lead Reminder list
     * @apiName Lead Reminder list
     * @apiGroup Leads
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   reminder  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */  
    public function index()
    {
        $model = new LeadReminderModel();
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
            $res= $model->where('lr_delete_flag', 0)->orderBy("lr_id", "desc")->join('users','users.us_id =lr_assigned','left')->select('lead_reminder.*,users.us_firstname')->findAll();
            if($res)
            {
                $this->insertUserLog('View Lead Reminder List',$tokendata['uid']);
                $response = [
                    'ret_data'=>'success',
                    'task'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'success',
                    'task'=>[]
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
     * @api {post} leads/leadreminder Lead Task create
     * @apiName Lead Reminder create
     * @apiGroup Leads
     * @apiPermission super admin,User
     *
     *@apiBody {String} lr_date reminder Date      
     *@apiBody {String} lr_assigned Reminder Assigned
     *@apiBody {String} lr_desc Reminder Description
     *@apiBody {String }lr_lead_id Lead ID
     *@apiBody {String } lr_status Status
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function create()
    {
        $model = new LeadReminderModel();
        $acmodel= new LeadActivityModel();
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
                'lr_date'=>'required',                
                'lr_assigned'=>'required', 
                'lr_desc'=>'required',  
                'lr_lead_id'=>'required', 
            ];

            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());

            $lead_id = $this->db->escapeString($this->request->getVar('lr_lead_id'));
            $data = [
                'lr_date' => $this->request->getVar('lr_date'),
                'lr_assigned' => $this->request->getVar('lr_assigned'), 
                'lr_desc' => $this->request->getVar('lr_desc'),                 
                'lr_lead_id' => $lead_id,
                'lr_status' => 'Pending',
                'lr_created_by' => $tokendata['uid']             
            ];
        
            if ($model->insert($data) === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            }
            else{
                $acdata = [
                    'lac_activity' => 'Created Reminder',
                    'lac_activity_by' => $tokendata['uid'], 
                    'lac_lead_id' => $lead_id,
                               
                ];    
                $acmodel->insert($acdata);
                $this->insertUserLog('Created New Lead Reminder',$tokendata['uid']);
                if($tokendata['uid'] != $this->request->getVar('lr_assigned')){
                $data=array('un_title'=>'Lead Reminder','un_note'=>'Lead Reminder for you on '.$this->request->getVar('lr_date'),'un_to'=>$this->request->getVar('lr_assigned'),'un_from'=>$tokendata['uid'],'un_link'=>'pages/user/leads/lead-management/','un_link_id'=> $lead_id);
                $this->insertUserNoti($data);
                }
                return $this->respond(['ret_data' => 'success'], 200);
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
     * @api {post} leads/leadreminer/delete Lead Reminder delete
     * @apiName Lead Reminder delete
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  reminder id of the lead source to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new LeadReminderModel();
        $acmodel= new LeadActivityModel();
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
                'lr_delete_flag' => 1,                
            ];

            if($model->where('lr_id', $this->db->escapeString($this->request->getVar('id')))->set($data)->update() === false )
            {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->respond($response, 200);
            }
            else
            {
                $acdata = [
                    'lac_activity' => ' Deleted Reminder',
                    'lac_activity_by' => $tokendata['uid'], 
                    'lac_lead_id' => $this->db->escapeString($this->request->getVar('lead_id')),
                ];    
                $acmodel->insert($acdata);
                $this->insertUserLog('Reminder Deleted',$tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 200);

            }

        }
    }

    /**
     * @api {post} leads/leadreminder/reminderStatusChange Lead Reminder Status Change
     * @apiName Lead Reminder Status Change
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  reminder id 
     * @apiParam {String} status  status
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */

    public function reminderStatusChange($id = null)
    {
        $model = new LeadReminderModel();
        $acmodel= new LeadActivityModel();
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

            $id=$this->db->escapeString($this->request->getVar('id'));
            $leadid=$this->db->escapeString($this->request->getVar('leadid'));
            $status=$this->db->escapeString($this->request->getVar('status'));
            $data = [
                'lr_status' =>  $status,                
            ];
            $acdata = [
                'lac_activity' => 'Changed Reminder Status to'.$status,
                'lac_activity_by' => $tokendata['uid'], 
                'lac_lead_id' => $leadid,
                           
            ];
            if($model->where('lr_id', $id)->set($data)->update() === false )
            {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->respond($response, 200);
            }
            else
            {
                $acmodel->insert($acdata);
                $this->insertUserLog('Reminder Status Changed',$tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 200);

            }

        } 
    }

 /**
     * @api {post} leads/leadreminder/getLeadReminder Reminder List By Lead ID
     * @apiName Reminder List By Lead ID
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  Lead ID
     * 
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   rem  Object containing action list
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function getLeadReminder()
    {
        $model = new LeadReminderModel();
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

             $res= $model->where('lr_delete_flag', 0)->where('lr_lead_id', $this->db->escapeString($this->request->getVar('id')))->orderBy("lr_id", "desc")->join('users','users.us_id =lr_assigned','left')->select('lead_reminder.*,users.us_firstname')->findAll();
             $this->insertUserLog('View Reminder List',$tokendata['uid']);
             if($res)
            {
             
                $response=[
                    'ret_data'=>'success',
                    'rem'=>$res
                ];
                return $this->respond($response,200);
            }else{
                $response=[
                    'ret_data'=>'fail',
                    'rem'=>[]
                ];
                return $this->respond($response, 200);
            }

        }
        
    }
    public function insertUserLog($log,$id)
    {
        $logmodel = new UserActivityLog();
        $ip=$this->request->getIPAddress();       
        $indata=[
            'log_user'    => $id,
            'log_ip'   =>  $ip,
            'log_activity' =>$log            
        ];        
        $results=$logmodel->insert($indata);
    }
    public function insertUserNoti($data)
    {
        $model = new UserNotificationModel();
        $results=$model->insert($data);

        $builder = $this->db->table('users');
        $builder->select('FCM_token');
        $builder->where('us_id', $data['un_to']);
        $query = $builder->get();
        $row = $query->getRow();
        if($row =='')
        {
            $token=0;
        }
        else{
            $token=$row->FCM_token ;
        }

        $post_data = '{
            "to" : "'.$token.'",
            "data" : {
              "body" : "",
              "title" : "'.$data['un_title'].'",
              "message" : "'.$data['un_note'].'",
            },
            "notification" : {
                 "body" : "'.$data['un_note'].'",
                 "title" : "'.$data['un_title'].'",                   
                 "message" : "'.$data['un_note'].'",
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
    }

}
