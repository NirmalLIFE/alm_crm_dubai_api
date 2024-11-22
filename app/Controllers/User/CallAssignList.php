<?php

namespace App\Controllers\User;

use App\Models\User\CallAssignListModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use App\Models\UserActivityLog;
use App\Models\User\UserNotificationModel;
use App\Models\Leads\LeadModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Customer\MaraghiVehicleModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\User\UserLogTableModel;
use App\Models\Leads\LeadCallLogModel;

class CallAssignList extends ResourceController
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

     public function UserCallAssignList()
     {
        $clmodel = new CallAssignListModel();
        $validModel=new Validation();
        $commonutils=new Common();
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

            // $UserModel = new UserModel();        
            // $userdept = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->select('us_dept_id')->first();

            // $builder = $this->db->table('call_assign_list');
            // $builder->select('CONCAT("*****",RIGHT(miss_call_from,7)) as trim_phone,cagn_id,cagn_user_id,cagn_date_from,cagn_date_to,cagn_created_by,cagn_created_on,cagn_updated_by,cagn_updated_on,cagn_delete_flag,miss_call_time,miss_call_to,miss_call_from,cagn_note,cagn_lead_id,cagn_status,cagn_appoint_date,us_firstname');
            // $builder->join('users as us','us.us_id=cagn_user_id');           
            // $builder->where('cagn_delete_flag', 0);
            // $builder->where('cagn_date_from', $this->request->getVar('assigndate'));  
            // $builder->where('us_dept_id',$userdept);   
            // $query = $builder->get();
            // $result = $query->getResultArray();  





            $result = $clmodel->where('cagn_user_id', $tokendata['uid'])->where('cagn_delete_flag', 0)->where('cagn_date_from', $this->request->getVar('assigndate'))->join('users as us','us.us_id=cagn_user_id',)->select('CONCAT("*****",RIGHT(miss_call_from,7)) as trim_phone,cagn_id,cagn_user_id,cagn_date_from,cagn_date_to,cagn_created_by,cagn_created_on,cagn_updated_by,cagn_updated_on,cagn_delete_flag,miss_call_time,miss_call_to,miss_call_from,cagn_note,cagn_lead_id,us_firstname')->findAll();
            if($result){
            
                $response = [
                    'ret_data'=>'success',
                    'assign'=>$result,
                ];
                return $this->respond($response,200);
            }
            else {
                $response = [
                    'ret_data'=>'fail',
                    'assign'=>[],
                ];
                return $this->respond($response,200);
            }

         }
    }
    public function getAssignedDateList()
    {
        $UserModel = new UserModel();
        $clmodel = new CallAssignListModel();
        $validModel=new Validation();
        $commonutils=new Common();
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

            // $userdept = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->select('us_dept_id')->first();

            // $builder = $this->db->table('call_assign_list');
            // $builder->select('DISTINCT(cagn_date_from) as cagndate');
            // $builder->join('users as us','us.us_id=cagn_user_id',);           
            // $builder->where('cagn_delete_flag', 0);   
            // $builder->where('us_dept_id',$userdept);   
            // $query = $builder->get();
            // $date = $query->getResultArray();   


         $date= $clmodel->where('cagn_user_id', $tokendata['uid'])->where('cagn_delete_flag', 0)->select('DISTINCT(cagn_date_from) as cagndate')->findAll();
           
            if($date){
            
                $response = [
                    'ret_data'=>'success',
                    'asndate'=> $date
                ];
                return $this->respond($response,200);
            }
            else {
                $response = [
                    'ret_data'=>'success',
                     'asndate'=>[]
                ];
                return $this->respond($response,200);
            }

         }

    }
    public function index()
    {
        $clmodel = new CallAssignListModel();
        $validModel=new Validation();
        $commonutils=new Common();
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
            $this->insertUserLog('View Users List',$tokendata['uid']);
            $result = $usmodel->where('cagn_user_id', $tokendata['uid'])->findAll();
            if($result){
            
                $response = [
                    'ret_data'=>'success',
                    'customer'=>$result,
                ];
                return $this->respond($response,200);
            }
            else {
                $response = [
                    'ret_data'=>'fail',
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
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {
        $clmodel = new CallAssignListModel();
        $leadmodel = new LeadModel();
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
                $in_data=array();
                $cl_res= $clmodel->where('cagn_date_from', $this->request->getVar('assignDate'))->select('cagn_id ')->first();
                if($cl_res)
                {
                    $data['ret_data']="false";
                    return $this->respond($data,200);
                }
                else{
                    $report = $this->request->getVar('report');


                    foreach ($report as $item) {
                        $insdata=array();
                        $lead = $leadmodel->where('RIGHT(phone,7)',substr($item->call_from,-7))->where('status_id',1)->select('lead_id')->first();
                        $l = ($lead)?$lead['lead_id']:0;
                       
                        $insdata = [
                            'cagn_user_id'=> $this->db->escapeString($this->request->getVar('assignUser')),
                            'cagn_date_from'=> $this->request->getVar('assignDate'),
                            'cagn_date_to'=> $this->request->getVar('assignDate'),                    
                            'cagn_created_by'=>$tokendata['uid'],
                            'miss_call_from'=>$item->call_from,
                            'miss_call_time'=>$item->time,
                            'miss_call_to'=>$item->call_to, 
                            'cagn_lead_id'  =>$l      
                        ];   
                       
                        array_push($in_data,$insdata); 
                    }
                    
                     $result= $clmodel->insertBatch($in_data);
                   
              
               if($result){
                   $this->insertUserLog('Missed Call Assigned',$tokendata['uid']);
                   $data=array('un_title'=>'Missed call Assigned','un_note'=>'Missed call Assigned to you','un_to'=>$user,'un_from'=>$tokendata['uid'],'un_link'=>'','un_link_id'=> '');
                //   $this->insertUserNoti($data);
                    $response['ret_data']="success";
                    return $this->respond($response,200);
    
               }else{
                $response['ret_data']="fail";
                   return $this->respond($response,200);
               }
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
    public function addMisscallNote()
    {
        $clmodel = new CallAssignListModel();
        $leadmodel = new LeadModel();
        $log = new UserLogTableModel();
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
                
                    $id = $this->request->getVar('asn_id');
                    $call_note = $this->request->getVar('call_note');
                    $status = $this->request->getVar("status");
                
                    $updata = [
                        'cagn_note' => $call_note,
                        'cagn_status'=>$status,
                        'cagn_appoint_date'=> $this->request->getVar("date")
                    ];            
                    $res = $clmodel->where('cagn_id ',$id)->set($updata)->update();
              
                    if($res){
                   $this->insertUserLog('Missed Note Added',$tokendata['uid']);      
                   
                   $row = $clmodel->where('cagn_id', $this->db->escapeString($id))->select('miss_call_from,miss_call_time')->first();
                   $ip=$this->request->getIPAddress();       
                   $dataL=['ulg_user'=>$tokendata['uid'],'ulg_activity'=>'Add Misscall Note, '.$call_note,'ulg_file'=>'for number'.$row['miss_call_from'].'on '.$row['miss_call_time'],'ulg_ip'=>$ip];
                   $res =  $log->insert($dataL);                  



                    $response['ret_data']="success";
                    return $this->respond($response,200);
    
               }else{
                $response['ret_data']="fail";
                   return $this->respond($response,200);
               }
            
            }
    }
    public function getMisscallDetails()
    {
        $clmodel = new CallAssignListModel();
        $marcustmodel = new MaragiCustomerModel();
        $leadmodel = new LeadModel();
        $log = new LeadCallLogModel();
        $custmastermodel = new CustomerMasterModel();  
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
            $num_list = $this->request->getVar('num_list'); 
            $time_list = $this->request->getVar('time_list'); 
            $start_date = $this->request->getVar('startdate').' 00:00:00'; 
            $end_date = $this->request->getVar('enddate').' 00:00:00'; 
            $call_id=$this->request->getVar('ycallid');
            $hours = date('Y-m-d', strtotime(str_replace('/', '-', $this->request->getVar('startdate'))));

            $i=0;
            $customers=[];
          if($start_date ==  $end_date)
          {
            $note = $clmodel->like('miss_call_time',$this->request->getVar('startdate'))->select('RIGHT(miss_call_from,7) as phon_uniq,miss_call_time,cagn_note,cagn_status,cagn_appoint_date')->find();
          }
          else{
            $note = $clmodel->where('miss_call_time >=',$start_date)->where('miss_call_time <=',$end_date)->select('RIGHT(miss_call_from,7) as phon_uniq,miss_call_time,cagn_note,cagn_status,cagn_appoint_date')->find();
          }
          $out_call_note=[];
          if($call_id != null){
            $out_call_note = $log->select('lcl_id,lcl_time,lcl_lead_id,call_purpose,lcl_purpose_note,lcl_call_to,ystar_call_id,cp_id')
            ->join('call_purposes', 'call_purposes.cp_id =lead_call_log.lcl_pupose_id')
            ->where('lcl_pupose_id !=', 0)
            ->whereIn('ystar_call_id', $call_id)->find();
          }
          
            foreach ($num_list as $num)
            {
                
                $marag_cus_res= $marcustmodel
                ->where('RIGHT(phone,7)',$num)
                ->orWhere('RIGHT(mobile,7)', $num)
                ->select('customer_code,customer_name,city,mobile,RIGHT(phone,7) as phon_uniq,RIGHT(mobile,7) as mob_uniq')->first();
                if($marag_cus_res)
                {
                    $marag_cus_res['type']='M';
                    $marag_cus_res['customer_name']=strtoupper( $marag_cus_res['customer_name']);
                    $customers[$i] = $marag_cus_res;
                    $i++;
                }
                else{
                    $cust_master_res= $custmastermodel->where('RIGHT(cust_phone,7)',$num)->select('cus_id,cust_alm_code,cust_name as customer_name,cust_city as city,cust_phone as mobile,RIGHT(cust_phone,7) as phon_uniq,RIGHT(cust_alternate_contact,7) as alt_num_uniq')->first();
                    if($cust_master_res)
                    {
                        $cust_master_res['customer_name']= strtoupper( $cust_master_res['customer_name']);
                        $cust_master_res['type']='C';
                        $customers[$i] = $cust_master_res;
                        $i++;
                    }
                    else{
                        $lead_res= $leadmodel->like('phone', $num, 'before')->where('lead_createdon <', $hours)
                        ->select("IF(IFNULL(name, '') = '', 'EXISTS', name) as customer_name,phone as mobile,RIGHT(phone,7) as phon_uniq,'L' as type")->first();
                        if($lead_res)
                        {
                            $lead_res['customer_name']=strtoupper( $lead_res['customer_name']);
                            $lead_res['type']='L';
                            $customers[$i] = $lead_res;
                            $i++;
                        }
                   
                    
                    }
                   
                }
               
            }
           

             $response = [
                        'ret_data'=>'success',
                        'customers'=>$customers,
                        'note'=> $note,
                        'outcallnote'=>$out_call_note                      
                    ];
                    return $this->respond($response,200);
             






            // $response = [
            //     'ret_data'=>'success',
            //     'customers'=>$num_list,                      
            // ];
            // return $this->respond($response,200);
            // $marag_cus_res= $marcustmodel->whereIn('phon_uniq',$num_list)->find();
            
            
            // if($marag_cus_res) // Phone number found in CRM customer master table
            // {
            //     $response = [
            //         'ret_data'=>'success',
            //         'customers'=>$marag_cus_res,                      
            //     ];
            //     return $this->respond($response,200);
            // }else{
            //     $response = [
            //         'ret_data'=>'fail',
            //         'customers'=>[],                      
            //     ];
            //     return $this->respond($response,200);
            // }
        }
    }
}
