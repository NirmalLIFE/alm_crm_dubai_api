<?php

namespace App\Controllers\Customer;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Customer\CustomerTypeModel;
use App\Models\Customer\CountryMasterModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Leads\LeadModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;

class LeadToCust extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function getCustomerType()
    {
        $model = new CustomerTypeModel();
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
            $res= $model->where('cst_delete_flag', 0)->findAll();
            if($res)
            {
                $response = [
                    'ret_data'=>'success',
                    'type'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'fail',
                    'type'=>[]
                ];
                return $this->respond($response,200);

            }
        }
    }
    public function getCountry()
    {
        $model = new CountryMasterModel();
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
            $res= $model->where('delete_flag', 0)->findAll();
            if($res)
            {
                $response = [
                    'ret_data'=>'success',
                    'country'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'fail',
                    'country'=>[]
                ];
                return $this->respond($response,200);
            }
        }
    }
     /**
     * @api {post} leads/lead/leadtocustomer Convert Lead To Customer
     * @apiName Convert Lead To Customer
     * @apiGroup Leads
     * @apiPermission super admin,User
     *
     *@apiBody {String} cust_type Customer Type
     *@apiBody {String} cust_name Name
     *@apiBody {String} cust_salutation Salutation
     *@apiBody {String} cust_address Address
     *@apiBody {String} cust_emirates Emirates
     *@apiBody {String} cust_city City
     *@apiBody {String} cust_country Country
     *@apiBody {String} cust_phone Phone
     *@apiBody {String} cust_alternate_no Alternate Number
     *@apiBody {String} cust_email Email
     *@apiBody {String} lead_id Lead ID
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function leadtocustomer($id = null)
    {
        $model = new CustomerMasterModel();
        $modelL = new LeadModel();
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
            $lead_id = $this->db->escapeString($this->request->getVar('lead_id'));
            $phone = $this->request->getVar('cust_phone');
            $cust_id=0;
            $resC = $model->where('cust_phone', $phone)->first();
            if($resC)
            {
                $cust_id = $resC['customer_code'];
            }
            $data = [
                'cust_type' => $this->request->getVar('cust_type'),
                'cust_name' => $this->request->getVar('cust_name'),  
                'cust_salutation' => $this->request->getVar('cust_salutation'),                 
                'cust_address' => $this->request->getVar('cust_address'),  
                'cust_emirates' => $this->request->getVar('cust_emirates'), 
                'cust_city' => $this->request->getVar('cust_city'),   
                'cust_country' => $this->request->getVar('cust_country'),   
                'cust_phone' => $this->request->getVar('cust_phone'),     
                'cust_alternate_no' => $this->request->getVar('cust_alternate_no'), 
                'cust_email' => $this->request->getVar('cust_email'),  
                'cust_lang' => $this->request->getVar('cust_lang'),   
                'cust_alm_code' =>  $cust_id,
                'cust_created_by' => $tokendata['uid']               
            ];
            $ins_id= $model->insert($data);
            $data = [
                'cus_id' => $ins_id, 
                'status_id'=>5,
                'conv_cust_by' =>$tokendata['uid'],
                'conv_cust_on' =>date('Y-m-d'),         
            ];  
           if($ins_id)
           {
                if($modelL->where('lead_id', $lead_id)->set($data)->update() === false )
                {
                    $response = [
                        'errors' => $model->errors(),
                        'ret_data' => 'fail'
                    ];
                    return $this->respond($response, 200);
                }
                else{
                    $this->insertUserLog('Lead Converted to Customer',$tokendata['uid']);
                    return $this->respond(['ret_data' => 'success','customer_id'=>$ins_id], 200);
                   } 
           }
           else{
            $this->insertUserLog('Lead Converted to Customer',$tokendata['uid']);
            return $this->respond(['ret_data' => 'success'], 200);
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

}
