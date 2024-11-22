<?php

namespace App\Controllers\User;

use CodeIgniter\RESTful\ResourceController;
use App\Models\User\UserModel;
use App\Models\User\UserroleModel;
use CodeIgniter\API\ResponseTrait;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\UserActivityLog;
use App\Models\Leads\LeadCallLogModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Leads\LeadModel;
use App\Models\Leads\LeadActivityModel;
use App\Models\Customer\MaraghiVehicleModel;
use App\Models\Customer\MaraghiJobcardModel;
use App\Models\Quotes\QuotesMasterModel;

class UserDetail extends ResourceController
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
            $uidd = $this->db->escapeString($id);
            $user = $usmodel->where("ext_number", $uidd )->join('user_roles','user_roles.role_id=us_role_id','left')->join('department','department.dept_id=users.us_dept_id','left')->select('us_id,us_firstname,us_lastname,us_phone,us_email,us_role_id,user_roles.role_name,us_date_of_joining,us_status_flag,tr_grp_status,us_ext_name,ext_number,us_dept_id,dept_name')->first();
            
            $uid =  $user['us_id'];


            $builder = $this->db->table('leads');
            $builder->select('count(leads.lead_id) as crt_lead');
            $builder->where('lead_createdby',$uid ); 
            $builder->where('status_id !=',7);                  
            $query = $builder->get();
            $result = $query->getRow();
            $crt_lead=  $result->crt_lead; 

            $builder = $this->db->table('leads');
            $builder->select('count(leads.lead_id) as assn_lead');
            $builder->where('assigned',$uid ); 
            $builder->where('status_id !=',7);                  
            $query = $builder->get();
            $result = $query->getRow();
            $assn_lead=  $result->assn_lead; 
            
            if($user){
                $response = [
                    'ret_data'=>'success',
                    'user_details'=>$user,
                    'crt_lead'=>$crt_lead,
                    'assn_lead'=>$assn_lead
                ]; 
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'success',
                    'user_details'=>[],
                    'crt_lead'=>0,
                    'assn_lead'=>0 
                ]; 
                return $this->respond($response,200);
            }
        }
    }

    public function custDetails()
    {
        $marcustmodel = new MaragiCustomerModel();        
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();  
        $leadlogmodel = new LeadCallLogModel();  
        new MaraghiVehicleModel();
      
      
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

            $call_to = $this->request->getVar('call_to');
            $call_id = $this->request->getVar('call_id');
            $num_list = $this->request->getVar('num_list');
            $i=0;
            $customer=[];
            $lead = [];
            $leadlog=[];

            $lead= $leadmodel->whereIn('RIGHT(phone,7)',$num_list)->where('status_id !=', 7)->select('lead_id ,name as customer_name,phone as mobile,lead_code,RIGHT(phone,7) as phon_uniq,lead_creted_date,close_time')->find();
            $leadlog = $leadlogmodel-> whereIn('ystar_call_id',$call_id)->where('lcl_call_to',$call_to)->where('lcl_pupose_id !=',0)->join('call_purposes','call_purposes.cp_id =lead_call_log.lcl_pupose_id','left')->select("lcl_id,lcl_time,lcl_lead_id,RIGHT(lcl_phone,7) as phon_uniq,lcl_purpose_note,ystar_call_id,lcl_call_to,call_purpose")->find();
            
            foreach ($num_list as $num)
            {
    
                $marag_cus_res= $marcustmodel->where('RIGHT(phone,7)',$num)->select('customer_code,customer_name,city,mobile,RIGHT(mobile,7) as phon_uniq')->first();
                if($marag_cus_res)
                {
                    $marag_cus_res['type']='M';
                    $marag_cus_res['customer_name']=strtoupper( $marag_cus_res['customer_name']);
                    $customer[$i] = $marag_cus_res;
                    $i++;
                }
                else{
                    $cust_master_res= $custmastermodel->where('RIGHT(cust_phone,7)',$num)->select('cus_id,cust_alm_code,cust_name as customer_name,cust_city as city,cust_phone as mobile,RIGHT(cust_phone,7) as phon_uniq')->first();
                    if($cust_master_res)
                    {
                        $cust_master_res['customer_name']=strtoupper( $cust_master_res['customer_name']);
                        $cust_master_res['type']='C';
                        $customer[$i] = $cust_master_res;
                        $i++;
                    }
                    // else{
                    //     $lead_res= $leadmodel->where('RIGHT(phone,7)',$num)->where('status_id !=', 7)->select('lead_id ,name as customer_name,phone as mobile,RIGHT(phone,7) as phon_uniq')->first();
                    //     if($lead_res)
                    //     {
                    //         $lead_res['customer_name']=strtoupper( $lead_res['customer_name']);
                    //         $lead_res['type']='C';
                    //         $customers[$i] = $lead_res;
                    //         $i++;
                    //     }
                
                    
                    // }
        
                }
   
            }
            
          $response = [
            'ret_data'=>'success',
            'lead'=>$lead ,
            'leadlog'=>$leadlog,
            'customers'=>$customer,                 
            ];
            return $this->respond($response,200);
        }
    }
    public function leadDetail()
    {
        $model = new LeadModel();
        $logmodel = new LeadActivityModel();
        $callmodel = new LeadCallLogModel();

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

            $id = $this->request->getVar('lead_id');
           
            $res= $model->where('lead_id', $id)
                        ->join('users cr','cr.us_id =lead_createdby','left')
                        ->join('users as','as.us_id =assigned','left')
                        ->join('call_purposes','call_purposes.cp_id =purpose_id','left')
                        ->join('prefer_language','prefer_language.pl_id =lang_id','left')
                        ->join('lead_source','lead_source.ld_src_id =source_id','left')
                        ->join('lead_status','lead_status.ld_sts_id =status_id','left')
                        ->select('leads.*,lead_creted_date,cr.us_firstname as creted,as.us_firstname as assign,lead_source.ld_src,lead_status.ld_sts,prefer_language.prefer_lang,call_purposes.call_purpose')->first();
           
            $lead_call_log = $callmodel->where('lcl_lead_id',$id)->where('lcl_pupose_id !=',0)
                            ->join('users','users.ext_number =lcl_call_to','left')
                            ->join('user_roles','users.us_role_id =user_roles.role_id ','left')
                             ->join('call_purposes','call_purposes.cp_id =lcl_pupose_id','left')
                            ->select('lcl_time,lcl_pupose_id,lcl_call_to,us_firstname,role_name,call_purpose,lcl_purpose_note')
                            ->orderBy('lcl_id','desc')
                            ->findAll();
           
            $lead_log = $logmodel->where('lac_lead_id', $id)->join('users','users.us_id =lac_activity_by','left')->select('lead_activities.*,users.us_firstname')->orderBy('lac_id','desc')->findAll();
            
            $response = [
                    'ret_data'=>'success',
                    'lead'=>$res,
                    'leadlog'=>$lead_log,
                    'leadcalllog'=>  $lead_call_log
                ];
                return $this->respond($response,200);
        } 
    }

    public function getCustomerDetail()
    {
        $marcustmodel = new MaragiCustomerModel();
        $marvehmodel = new MaraghiVehicleModel();
        $marjcmodel = new MaraghiJobcardModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();
        $quotmodel = new QuotesMasterModel();
        $logmodel = new LeadCallLogModel();
      
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

                $phone = $this->request->getVar('phone');
              // $leadid = $this->request->getVar('leadid');
                $ph = substr($phone, -7);
                $patern = $ph; 

                $cust_master_res= $custmastermodel
                ->where('RIGHT(cust_phone,7)', $patern)
                ->select('cus_id,cust_alm_code,cust_name as customer_name,cust_city as city,cust_phone as mobile')->first();
                $marag_cus_res= $marcustmodel
                ->where('RIGHT(phone,7)', $patern)
                ->orWhere('RIGHT(mobile,7)', $patern)
                ->select('customer_code,customer_name,city,mobile')->first();
                $lead_res= $leadmodel->where('RIGHT(phone,7)', $patern)->where('status_id !=', 7)->select('lead_id ,name as customer_name,phone as mobile')->first();
                $lead_log = $logmodel->where('RIGHT(lcl_phone,7)',$patern )->where('lcl_pupose_id !=',0)->join('users','users.ext_number =lcl_call_to','left')
                            ->join('user_roles','users.us_role_id =user_roles.role_id ','left')
                            ->join('call_purposes','call_purposes.cp_id =lcl_pupose_id','left')
                            ->select('lcl_time,lcl_pupose_id,lcl_call_to,us_firstname,role_name,call_purpose,lcl_purpose_note')
                            ->orderBy('lcl_id','desc')
                            ->findAll();                          
                          //  $logmodel->where('lcl_lead_id',$leadid)

                if($cust_master_res) // Phone number found in CRM customer master table
                {
                   
                    $cust_code = $cust_master_res['cust_alm_code'];
                    $cust_id = $cust_master_res['cus_id'];
                    
                   if($cust_code !=0) // Phone number found in CRM customer master table && alm code !=0
                   {
                        $result =  $this->getMaraghiData($cust_code);
                        $response = [
                            'ret_data'=>'success',
                            'customer'=>$marag_cus_res,
                            'vehicle'=>$result['vehicle'],
                            'jobcard'=>$result['jobcard'],
                            'lead'=>$result['lead'],
                            'quot'=>$result['quot'],
                            'totalLead'=>$result['resCount'],
                            'pendLead'=>$result['penCount'], 
                            'pendLeadId'=>  $result['penLeadId'] ,
                            'leadLog'  =>$lead_log ,
                            'JCS'=>$result['JCS'],
                            'JCY'=>$result['JCY'],                        
                        ];
                    
                       return $this->respond($response,200);
                   }
                   else{ // Phone number found in CRM customer master table && alm code == 0
                   
                        if($marag_cus_res) //Phone number found in Maraghi customer  table
                        {
                            $cust_code = $marag_cus_res['customer_code']; 
                             $data = [
                                'cust_alm_code' => $cust_code,                        
                            ];

                            $custmastermodel->where('cus_id', $cust_id)->set($data)->update(); //update alm code in CRM Customer master table
                            $result =  $this->getMaraghiDataInfo($cust_code);    
                            $response = [
                                'ret_data'=>'success',
                                'customer'=>$marag_cus_res,
                                'vehicle'=>$result['vehicle'],
                                'jobcard'=>$result['jobcard'],
                                'lead'=>$result['lead'],
                                'quot'=>$result['quot'],
                                'totalLead'=>$result['resCount'],
                                'pendLead'=>$result['penCount'], 
                                'pendLeadId'=>  $result['penLeadId'],
                                'leadLog'  =>$lead_log ,
                                'JCS'=>$result['JCS'],
                                'JCY'=>$result['JCY'],        
                                
                            ];                        
                            return $this->respond($response,200);
                        }
                        else //Phone number not found in Maraghi customer  table
                        {                            
                            $resV= $leadmodel->where('cus_id', $cust_id)->where('register_number IS NOT NULL', null, false)->select('register_number as reg_no,vehicle_model as model_name')->findAll();
                            $resL= $leadmodel->where('cus_id', $cust_id)->where('status_id !=',7)->orderBy('lead_id', "desc")->join('lead_source','lead_source.ld_src_id =source_id','left')->join('lead_status','lead_status.ld_sts_id =status_id','left')->select('DATE(lead_createdon) as created,lead_code,vehicle_model,lead_note,lead_source.ld_src,lead_status.ld_sts')->findAll();
                            $resQ= $quotmodel->where('qt_cus_id', $cust_id)->select('qt_code,qt_reg_no,qt_type,qt_total')->findAll();
                            
                            $builder = $this->db->table('customer_master');
                            $builder->select('count(leads.cus_id) as lc');
                            $builder->where('customer_master.cus_id', $cust_id);
                            $builder->where('status_id !=', 7);
                            $builder->join('leads','leads.cus_id = customer_master.cus_id');
                            $query = $builder->get();
                            $result = $query->getRow();
                            $resCount=  $result->lc; 
                                                
                            $builder = $this->db->table('customer_master');
                            $builder->select('count(leads.cus_id) as lc,lead_id');                 
                            $builder->join('leads','leads.cus_id = customer_master.cus_id');
                            $builder->where('customer_master.cus_id', $cust_id);
                            $builder->where('status_id', '1');
                          //  $builder->orWhere('status_id', '1');      
                            $query = $builder->get();
                            $result = $query->getRow();
                            $penCount=  $result->lc; 
                            $penLeadId = $result->lead_id;
            
                            $response = [
                                'ret_data'=>'success',
                                'customer'=>$cust_master_res,
                                'vehicle'=>$resV,
                                'quot'=>$resQ,
                                'jobcard'=>[],
                                'lead'=>$resL,
                                'totalLead'=>$resCount,
                                'pendLead'=>$penCount,
                                'penLeadId'=> $penLeadId,
                                'leadLog'  =>$lead_log,
                                'JCS'=>[],
                                'JCY'=>[],      
                                    
                            ];
                            return $this->respond($response,200);
                        }

                   }                  

                }
                else if($marag_cus_res) //Phone number found in Maraghi customer  table
                {
                   
                    $cust_code = $marag_cus_res['customer_code'];
                    $result =  $this->getMaraghiDataInfo($cust_code);    
                    $response = [
                        'ret_data'=>'success',
                        'customer'=>$marag_cus_res,
                        'vehicle'=>$result['vehicle'],
                        'jobcard'=>$result['jobcard'],
                        'lead'=>$result['lead'],
                        'quot'=>$result['quot'],
                        'totalLead'=>$result['resCount'],
                        'pendLead'=>$result['penCount'], 
                        'pendLeadId'=>  $result['penLeadId'],
                        'leadLog'  =>$lead_log,
                        'JCS'=>$result['JCS'] ,
                        'JCY'=>$result['JCY'],       
                        
                    ];                        
                    return $this->respond($response,200);
                }
                 else if($lead_res) //Phone number found in Lead table
                {
                   
                    $lead_res['city'] = '';
                    $lead_id = $lead_res['lead_id'];
                    $resV= $leadmodel->where('RIGHT(phone,7)', $patern)->where('register_number IS NOT NULL', null, false)->select('register_number as reg_no,vehicle_model as model_name')->findAll();
                    $resL= $leadmodel->where('status_id !=',7)->where('RIGHT(phone,7)', $patern)->orderBy('lead_id', "desc")->join('lead_source','lead_source.ld_src_id =source_id','left')->join('lead_status','lead_status.ld_sts_id =status_id','left')->select('DATE(lead_createdon) as created,lead_code,vehicle_model,lead_note,lead_source.ld_src,lead_status.ld_sts')->findAll();

                    $builder = $this->db->table('leads');
                    $builder->select('count(leads.lead_id) as lc');
                    $builder->where('RIGHT(phone,7)', $patern); 
                    $builder->where('status_id !=', 7);

                    $query = $builder->get();
                    $result = $query->getRow();
                    $resCount=  $result->lc; 
                                    
                    $builder = $this->db->table('leads');
                    $builder->select('count(leads.lead_id) as pc,lead_id');                 
                    $builder->where('RIGHT(phone,7)', $patern);     
                    $builder->where('status_id', '1');
                  //  $builder->orwhere('status_id', '2');
                    $queryp = $builder->get();
                    $resultp = $queryp->getRow();
                    $penCount=  $resultp->pc;
                    $penLeadId = $resultp->lead_id;
                    
                    $response = [
                        'ret_data'=>'success',
                        'customer'=>$lead_res,
                        'vehicle'=>$resV,
                         'jobcard'=>[],
                         'quot'=>[],
                         'lead'=>$resL,
                         'totalLead'=>$resCount,
                         'pendLead'=>$penCount,
                         'penLeadId'=>$penLeadId,
                         'leadLog'  =>$lead_log,
                         'JCS'=>[],
                         'JCY'=>[],                                
                    ];
                    return $this->respond($response,200);
                }
                else{
                    
                    $response = [
                        'ret_data'=>'fail',
                        'customer'=>[],
                        'vehicle'=>[],
                        'jobcard'=>[],
                        'lead'=>[],
                        'totalLead'=>0,
                        'pendLead'=>0,
                        'leadLog'  =>$lead_log,
                        'JCS'=>[] ,
                        'JCY'=>[],       
                    ];
                    return $this->respond($response,200);
                }
              
               
            }
    }
    public function getMaraghiData($cust_code)
    {
      
        $marvehmodel = new MaraghiVehicleModel();
        $marjcmodel = new MaraghiJobcardModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();

        $resV= $marvehmodel->where('customer_code', $cust_code)->where('reg_no IS NOT NULL', null, false)->select('reg_no,family_name,brand_code,model_name,model_year,miles_done')->findAll();
        $resJ= $marjcmodel->where('customer_no', $cust_code)->orderBy('job_no', "desc")->join('cust_veh_data_laabs','cust_veh_data_laabs.reg_no =car_reg_no','left')->select('DATE(cust_job_data_laabs.created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no,,family_name,brand_code,model_name,model_year,miles_done')->findAll();
        $resL= $custmastermodel->where('cust_alm_code', $cust_code)->where('status_id !=',7)->orderBy('lead_id', "desc")->join('leads','leads.cus_id = customer_master.cus_id')->join('lead_source','lead_source.ld_src_id =source_id','left')->join('lead_status','lead_status.ld_sts_id =status_id','left')->select('DATE(lead_createdon) as created,vehicle_model,register_number,source_id,purpose_id,lang_id,lead_note,status_id,ld_brand,ld_src,ld_sts,lead_code')->findAll();
        $resQ= $custmastermodel->where('cust_alm_code', $cust_code)->join('quotes_master','quotes_master.qt_cus_id = customer_master.cus_id')->findAll();
        $resJC= $marjcmodel->where('customer_no', $cust_code)->orderBy('job_no', "desc")->select('DATE(created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no')->first();
       // $resJY= $marjcmodel->where('customer_no', $cust_code)->select('substrinoig(invce_date, 7, 10) as year')->findAll();
        $resJY= $marjcmodel->where('customer_no', $cust_code)->where('invoice_date !=','')->groupBy('RIGHT(invoice_date, 4)')->select('RIGHT(invoice_date, 4) as year,count(job_no) as jy')->limit(4)->findAll();


        $builder = $this->db->table('customer_master');
        $builder->select('count(leads.lead_id) as lc');
        $builder->where('cust_alm_code', $cust_code);
        $builder->where('leads.status_id !=', 7);
        $builder->join('leads','leads.cus_id = customer_master.cus_id');
        $query = $builder->get();
        $result = $query->getRow();
        $resCount=  $result->lc; 
           
        $builder = $this->db->table('customer_master');
        $builder->select('count(leads.cus_id) as pc,lead_id');                 
        $builder->join('leads','leads.cus_id = customer_master.cus_id'); 
        $builder->where('cust_alm_code', $cust_code); 
        $builder->where('status_id', '1');
       // $builder->orWhere('status_id', '1');      
        $queryp = $builder->get();
        $resultp = $queryp->getRow();
        $penCount=  $resultp->pc;
        $penLeadId = $resultp->lead_id;

        $response = [
            'vehicle'=>$resV,
            'jobcard'=>$resJ,
            'lead'=>$resL,
            'quot'=>$resQ,
            'resCount'=>$resCount,
            'penCount'=>$penCount,
            'penLeadId'=>$penLeadId,
            'JCS'=>$resJC,
            'JCY'=>array_reverse($resJY)
            
        ];

        return $response;
    }
    public function getMaraghiDataInfo($cust_code)
    {
      
        $marvehmodel = new MaraghiVehicleModel();
        $marjcmodel = new MaraghiJobcardModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();

        $resV= $marvehmodel->where('customer_code', $cust_code)->where('reg_no IS NOT NULL', null, false)->select('reg_no,family_name,brand_code,model_name,model_year,miles_done')->findAll();
        $resJ= $marjcmodel->where('customer_no', $cust_code)->orderBy('job_no', "desc")->join('cust_veh_data_laabs','cust_veh_data_laabs.reg_no =car_reg_no','left')->select('DATE(cust_job_data_laabs.created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no,,family_name,brand_code,model_name,model_year,miles_done')->findAll();
        $resL= $custmastermodel->where('cust_alm_code', $cust_code)->where('status_id !=',7)->orderBy('lead_id', "desc")->join('leads','leads.cus_id = customer_master.cus_id')->join('lead_source','lead_source.ld_src_id =source_id','left')->join('lead_status','lead_status.ld_sts_id =status_id','left')->select('DATE(lead_createdon) as created,vehicle_model,register_number,source_id,purpose_id,lang_id,lead_note,status_id,ld_brand,ld_src,ld_sts,lead_code')->findAll();
        $resQ= $custmastermodel->where('cust_alm_code', $cust_code)->join('quotes_master','quotes_master.qt_cus_id = customer_master.cus_id')->findAll();
        $resJC= $marjcmodel->where('customer_no', $cust_code)->orderBy('job_no', "desc")->select('DATE(created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no')->first();
        $resJY= $marjcmodel->where('customer_no', $cust_code)->where('invoice_date !=','')->groupBy('RIGHT(invoice_date, 4)')->select('RIGHT(invoice_date, 4) as year,count(job_no) as jy')->limit(4)->findAll();


        $builder = $this->db->table('customer_master');
        $builder->select('count(leads.lead_id) as lc');
        $builder->where('cust_alm_code', $cust_code);
        $builder->where('leads.status_id !=', 7);
        $builder->join('leads','leads.cus_id = customer_master.cus_id');
        $query = $builder->get();
        $result = $query->getRow();
        $resCount=  $result->lc; 
           
        $builder = $this->db->table('customer_master');
        $builder->select('count(leads.cus_id) as pc,lead_id');                 
        $builder->join('leads','leads.cus_id = customer_master.cus_id'); 
        $builder->where('cust_alm_code', $cust_code); 
        $builder->where('status_id', '1');
       // $builder->orWhere('status_id', '1');      
        $queryp = $builder->get();
        $resultp = $queryp->getRow();
        $penCount=  $resultp->pc;
        $penLeadId = $resultp->lead_id;

        $response = [
            'vehicle'=>$resV,
            'jobcard'=>$resJ,
            'lead'=>$resL,
            'quot'=>$resQ,
            'resCount'=>$resCount,
            'penCount'=>$penCount,
            'penLeadId'=>$penLeadId,
            'JCS'=>$resJC,
            'JCY'=>array_reverse($resJY)
            
        ];

        return $response;
    }

    public function getReportChartData()
    {
        
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
        if($tokendata['aud']=='superadmin' || $tokendata['aud']=='user' ){  
            $crr = strtotime("now");
         
              
              $startdate = $this->request->getVar('startdate');
              $enddate = $this->request->getVar('enddate');
                
                // Today's Total Leads
                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as td_tot_Ld');
                $builder->where('DATE(lead_createdon) >=',$startdate); 
                $builder->where('DATE(lead_createdon) <=',$enddate); 
                $builder->where('status_id !=',7);                  
                $query = $builder->get();
                $result = $query->getRow();
                $td_tot_Ld=  $result->td_tot_Ld; 

                // Total Pending Leads
                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as plc');
                $builder->where('status_id',1); 
                $builder->where('DATE(lead_createdon) >=',$startdate); 
                $builder->where('DATE(lead_createdon) <=',$enddate); 
                $query = $builder->get();
                $result = $query->getRow();
                $penleadCount=  $result->plc;

                //Lead Converted Today

                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as lct');
                $builder->where('status_id',5);
                $builder->where('conv_cust_on >=',$startdate); 
                $builder->where('conv_cust_on <=',$enddate); 
                $query = $builder->get();
                $result = $query->getRow();
                $toLead=  $result->lct;  
                //Total Lost Lead
                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as lct');
                $builder->where('status_id',6);
                $builder->where('DATE(lead_createdon) >=',$startdate); 
                $builder->where('DATE(lead_createdon) <=',$enddate); 
                $query = $builder->get();
                $result = $query->getRow();
                $lostLead=  $result->lct;  

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
                $builder->where('job_status','OPN');    
                $builder->orWhere('job_status','WIP');             
                $query = $builder->get();
                $result = $query->getRow();
                $jcCount=  $result->jc; 

                 // Today's Total Leads Creted By Logged User
                 $builder = $this->db->table('leads');
                 $builder->select('count(leads.lead_id) as us_tot_Ld');
                 $builder->where('DATE(lead_createdon) >=',$startdate); 
                 $builder->where('DATE(lead_createdon) <=',$enddate); 
                 $builder->where('lead_createdby',$tokendata['uid']); 
                 $builder->where('status_id !=',7);                    
                 $query = $builder->get();
                 $result = $query->getRow();
                 $us_tot_Ld=  $result->us_tot_Ld;

                //Total Pending Leads Assigned to the logged user
                $builder = $this->db->table('leads');
                $builder->select('count(leads.lead_id) as ulc');
                $builder->where('status_id',1);  
                $builder->where('assigned',$tokendata['uid']); 
                $query = $builder->get();
                $result = $query->getRow();
                $UsleadCount=  $result->ulc;
                $response = [
                    'ret_data'=>'success',
                    'todayTotLd'=>$td_tot_Ld,
                    'totPendLd' => $penleadCount,
                    'todayCallToLead'=>$toLead,
                    'todayTotLdUs'=>$us_tot_Ld,
                    'jcCount'=> $jcCount,
                    'totPendLdUs'=> $UsleadCount,
                    'totalostlead'=> $lostLead                          
                ];
                return $this->respond($response,200);









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
    public function leadByUser($id = null)
    {
        //
    }
    
}
