<?php

namespace App\Controllers\Leads;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\Leads\LeadTaskModel;
use App\Models\Leads\LeadActivityModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;
use App\Models\User\UserNotificationModel;

class LeadTask extends ResourceController
{
    use ResponseTrait;
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * @api {get} leads/leadtask  Lead Task list
     * @apiName Lead Task list
     * @apiGroup Leads
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   task  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */   
    public function index()
    {
        $model = new LeadTaskModel();
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

            $res= $model->where('lt_delete_flag', 0)->orderBy("lt_id", "desc")->join('users','users.us_id =lt_assigned','left')->select('lead_task.*,users.us_firstname')->findAll();
            if($res)
            {

                $this->insertUserLog('View lead task',$tokendata['uid']);
                $response = [
                    'ret_data'=>'success',
                    'task'=>$res
                ];
                return $this->respond($response,200);
            }
           else
            {
                $response = [
                    'ret_data'=>'fail',
                    'task'=>[]
                ];
                return $this->respond($response,200);
            }

        }
        
    }
   /**
     * @api {post} leads/leadtask/getLeadTask Task List By Lead ID
     * @apiName Task List By Lead ID
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  Lead ID
     * 
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   task  Object containing action list
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function getLeadTask()
    {
        $model = new LeadTaskModel();
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

            $res= $model->where('lt_delete_flag', 0)->where('lt_lead_id', $this->db->escapeString($this->request->getVar('id')))->orderBy("lt_id", "desc")->join('users','users.us_id =lt_assigned','left')->select('lead_task.*,users.us_firstname')->findAll();
            $this->insertUserLog('View lead task',$tokendata['uid']);
            if($res)
            {
               

                $response = [
                    'ret_data'=>'success',
                    'task'=>$res
                ];
                return $this->respond($response,200);
            }
            else
            { 
                $response = [
                    'ret_data'=>'fail',
                    'task'=>[]
                ];
                return $this->respond($response, 200);

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
     * @api {post} leads/leadtask Lead Task create
     * @apiName Lead Task create
     * @apiGroup Leads
     * @apiPermission super admin,User
     *
     *@apiBody {String} task Task Name 
     *@apiBody {String} startdate Task Start Date
     *@apiBody {String} duedate Task Due Date
     *@apiBody {String} assigned Task Assigned
     *@apiBody {String} description Task Description
     *@apiBody {String} leadid Lead ID
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function create()
    {
        $model = new LeadTaskModel();
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
                'task'=>'required', 
                'startdate'=>'required', 
                'duedate'=>'required', 
                'assigned'=>'required',                
                'leadid'=>'required', 
                    
                     
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $lead_id =  $this->db->escapeString($this->request->getVar('leadid'));
        
            $data = [
                'task_name' => $this->request->getVar('task'),
                'lt_start_date' => $this->request->getVar('startdate'), 
                'lt_due_date' => $this->request->getVar('duedate'), 
                'lt_assigned' => $this->request->getVar('assigned'),
                'lt_desc' => $this->request->getVar('description'),
                'lt_lead_id' => $lead_id,
                'lt_status' => $this->request->getVar('status'),
                'lt_created_by' => $tokendata['uid']             
            ];

            $builder = $this->db->table('users');
            $builder->select('us_firstname');
            $builder->where('us_id',$this->request->getVar('assigned'));
            $query = $builder->get();
            $row = $query->getRow();
            $new=$row->us_firstname ;
            $acdata = [
                'lac_activity' => 'Created Task '.$this->request->getVar('task').' assigned to '. $new,
                'lac_activity_by' => $tokendata['uid'], 
                'lac_lead_id' => $lead_id,
                           
            ];
            if ($model->insert($data) === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            }
            else
            {
                $acmodel->insert($acdata);

                $this->insertUserLog('Create new lead task '.$this->request->getVar('task'),$tokendata['uid']);
                if($tokendata['uid'] != $this->request->getVar('assigned')){
                $data=array('un_title'=>'Lead Task','un_note'=>'Lead Task Assigned to you','un_to'=>$this->request->getVar('assigned'),'un_from'=>$tokendata['uid'],'un_link'=>'pages/user/leads/lead-management/','un_link_id'=> $lead_id);
                $this->insertUserNoti($data);
                }
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
     * @api {post} leads/leadtask/delete Lead Task delete
     * @apiName Lead Task delete
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  task id of the lead source to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new LeadTaskModel();
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
                'lt_delete_flag' => 1,                
            ];
            $id=$this->db->escapeString($this->request->getVar('id'));
            $builder = $this->db->table('lead_task');
            $builder->select('task_name,lt_lead_id');
            $builder->where('lt_id',$id);
            $query = $builder->get();
            $row = $query->getRow();
            $new=$row->task_name ;
            $lead_id=$row->lt_lead_id ;
            $acdata = [
                'lac_activity' => 'Deleted Task '.$new,
                'lac_activity_by' => $tokendata['uid'], 
                'lac_lead_id' => $lead_id,
                           
            ];
           
           
            if($model->where('lt_id', $this->db->escapeString(($this->request->getVar('id'))))->set($data)->update() === false )
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
                $response = [
                    'ret_data' => 'success'
                ];
                $this->insertUserLog('Delete lead task',$tokendata['uid']);
                return $this->respond($response, 200);

            }

        }

    }

/**
     * @api {post} leads/leadtask/taskStatusChange Lead Task Status Change
     * @apiName Lead Task Status Change
     * @apiGroup Leads
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id  task id 
     * @apiParam {String} status  status
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */

    public function taskStatusChange($id = null)
    {
        $model = new LeadTaskModel();
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
                'lt_status' =>  $status,                
            ];
            $acdata = [
                'lac_activity' => 'Changed Task Status to '.$status,
                'lac_activity_by' => $tokendata['uid'], 
                'lac_lead_id' => $leadid,
                           
            ];
            if($model->where('lt_id', $id)->set($data)->update() === false )
            {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            }
            else
            {
                $this->insertUserLog('Lead task status Changed',$tokendata['uid']);
                $acmodel->insert($acdata);
                $response = [
                   'ret_data' => 'success'
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
