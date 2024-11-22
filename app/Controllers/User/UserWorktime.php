<?php

namespace App\Controllers\User;

use App\Models\Settings\WorkingTimeModel;
use App\Models\User\UserWorktimeModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use App\Models\UserActivityLog;
use App\Models\User\UserNotificationModel;


class UserWorktime extends ResourceController
{
    use ResponseTrait;
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
        $validModel=new Validation();
        $commonutils=new Common();
        $model = new UserWorktimeModel();

        $heddata=$this->request->headers();
        $tokendata=$commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
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
         //   $this->insertUserLog('View Users List',$tokendata['uid']);
            $result = $model ->where('uwt_delete_flag', 0)->select('uwt_id,uwt_day,uwt_user_id,uwt_user_ext,uwt_fn_starttime as fn_start,uwt_fn_endtime as fn_end,uwt_an_endtime as an_end,uwt_an_starttime as an_start,')->findAll();
            if($result){
            
                $data['ret_data']="success";
                $data['worktimeList']=$result;
                return $this->respond($data,200);
            }
            else {
                $data['ret_data']="fail";
                return $this->respond($data,200);
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
        $model = new UserWorktimeModel();
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

                $res= $model->select("uwt_day as day,CONCAT('Tue Aug 30 2022 ', uwt_fn_starttime,' GMT+0530 (Gulf Standard Time)') as fnstart,CONCAT('Tue Aug 30 2022 ', uwt_fn_endtime,' GMT+0530 (Gulf Standard Time)') as fnend,CONCAT('Tue Aug 30 2022 ', uwt_an_starttime,' GMT+0530 (India Standard Time)') as anstart,CONCAT('Tue Aug 30 2022 ', uwt_an_endtime,' GMT+0530 (Gulf Standard Time)') as anend")
                //->join('users','users.us_id=uwt_user_id','left')
                ->where('uwt_delete_flag', 0)
                ->where('uwt_user_id', $id)  
                ->findAll();
               
                if($res)
                {
                   $this->insertUserLog('Edit Users Work  Time',$tokendata['uid']);
                    $response = [
                        'ret_data'=>'success',
                        'worktime'=>$res
                    ];
                    return $this->respond($response,200);
                }
                else{
                    $response = [
                        'ret_data'=>'fail',
                        'worktime'=>[]
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
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {
        $model = new UserWorktimeModel();
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
                $items = $this->request->getVar("work");
                $ext = $this->request->getVar("user_ext");
                $userid = $this->request->getVar("user");
                $in_data=array();
                $UPdata=['uwt_delete_flag'=>'1'];
                
               

                foreach($userid as $usid)
                {
                    $model->where('uwt_user_id ', $usid)->set($UPdata)->update();

                    $builder = $this->db->table('users');
                    $builder->select('ext_number');
                    $builder->where('us_id', $usid);
                    $query = $builder->get();
                    $result = $query->getRow();
                    $ext=  $result->ext_number; 

                    foreach ($items as $item) {
                        $insdata=array();
                        $insdata = [
                            'uwt_day'=>$item->day,
                            'uwt_fn_starttime'=>$item->fnstart,
                            'uwt_fn_endtime'=>$item->fnend,
                            'uwt_an_starttime'=>$item->anstart,
                            'uwt_an_endtime'=>$item->anend,
                            'uwt_user_id'=> $usid,  
                            'uwt_user_ext' =>  $ext,           
                            'uwtcreated_by' => $tokendata['uid']             
                        ];   
                        array_push($in_data,$insdata); 
                    }

                }

               
                 $ret= $model->insertBatch($in_data);
                 if($ret)
                 {
                    $this->insertUserLog('New WorkTime For User Added',$tokendata['uid']);
                    $data=array('un_title'=>'New Work Time','un_note'=>'Work Time Added For You','un_to'=>$user,'un_from'=>$tokendata['uid'],'un_link'=>'','un_link_id'=> '');
                  //  $this->insertUserNoti($data);
                    $data['ret_data']="success";
                    return $this->respond($data,200);
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
        $model = new UserWorktimeModel();
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
                $items = $this->request->getVar("work");
                $user = $this->request->getVar("user");
                $in_data=array();
                $data=['uwt_delete_flag'=>'1'];
                $model->where('uwt_user_id ', $user)->set($data)->update();
                foreach ($items as $item) {
                    $insdata=array();
                    $insdata = [
                        'uwt_day'=>$item->day,
                        'uwt_fn_starttime'=>$item->fnstart,
                        'uwt_fn_endtime'=>$item->fnend,
                        'uwt_an_starttime'=>$item->anstart,
                        'uwt_an_endtime'=>$item->anend,
                        'uwt_user_id'=>$user,               
                        'uwtcreated_by' => $tokendata['uid']             
                    ];   
                    array_push($in_data,$insdata); 
                }
                 $ret= $model->insertBatch($in_data);
                 if($ret)
                 {
                    $this->insertUserLog('New WorkTime For User Added',$tokendata['uid']);
                    $data=array('un_title'=>'New Work Time','un_note'=>'Work Time Added For You','un_to'=>$user,'un_from'=>$tokendata['uid'],'un_link'=>'','un_link_id'=> '');
                  //  $this->insertUserNoti($data);
                    $data['ret_data']="success";
                    return $this->respond($data,200);
                 }
            }
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
    function getWorkTimeByDay()
    {
        $model = new UserWorktimeModel();
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
                $day = $this->request->getVar("day");
                $user = $this->request->getVar("user");
                $res= $model->select("uwt_fn_starttime as fnstart,uwt_fn_endtime as fnend,uwt_an_starttime as anstart,uwt_an_endtime as anend")
                ->where('uwt_delete_flag', 0)
                ->where('uwt_user_id', $user)  
                ->where('uwt_day', $day) 
                ->findAll();
                if($res)
                {
                  
                    $response = [
                        'ret_data'=>'success',
                        'worktime'=>$res
                    ];
                    return $this->respond($response,200);
                }
                else{
                    $response = [
                        'ret_data'=>'fail',
                        'worktime'=>[]
                    ];
                    return $this->respond($response,200);
                }
            }

    }
}
