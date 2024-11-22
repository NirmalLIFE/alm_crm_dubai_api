<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Commonutils\PermittedIPModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;

class PermittedIP extends ResourceController
{
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

      /**
     * @api {get} permittedIP  IP list
     * @apiName IP list
     * @apiGroup IP
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   iplist  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */ 
    public function index()
    {
        $model = new PermittedIPModel();
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

            $res= $model->where('pip_delete_flag', 0)->orderBy('pip_id','desc')->select('pip_id,pip_address,pip_reason,ip_source_type,pip_created_on,pip_updated_on')->findAll();
            $this->insertUserLog('View IP List',$tokendata['uid']);
            if($res)
            {
                $response = [
                    'ret_data'=>'success',
                    'iplist'=>$res
                ];
                return $this->respond($response,200);
            }else{
                $response = [
                    'ret_data'=>'success',
                    'iplist'=>[]
                ];
                return $this->respond($response,200); 
            }

        }
    }

   /**
     * @api {get}permittedIP/:id  IP by  id
     * @apiName IP by  id
     * @apiGroup IP
     * @apiPermission  Super Admin, User
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}   iplist object with lead source details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
    {
        
        $model = new PermittedIPModel();
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
            $res = $model->where('pip_id ',$this->db->escapeString($id))->select('pip_id,pip_address,pip_reason,ip_source_type,pip_created_on,pip_updated_on')->first();
            if($res)
            {
                $this->insertUserLog('View IP data For Update',$tokendata['uid']);
                $response = [
                    'ret_data'=>'success',
                    'iplist'=>$res
                ];
                return $this->respond($response,200);
            }
            else{
                $response = [
                    'ret_data'=>'fail',
                    'iplist'=>[]
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
     * @api {post} permittedIP Parts Brand create
     * @apiName IP create
     * @apiGroup IP
     * @apiPermission super admin
     *
     *@apiBody {String} ip IP Address
     *@apiBody {String} reason Permiited Reason    
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function create()
    {
        $model = new PermittedIPModel();
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
                'ip'=>'required', 
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $ip=$this->request->getVar('ip');
            if(!filter_var($ip,FILTER_VALIDATE_IP))
            {
                $response = [
                    'msg' => "Invalid IP",
                    'ret_data' => 'fail'
                ];
               
                return $this->respond($response);
            }
            else{
                $data = [
                    'pip_address' => $this->request->getVar('ip'),
                    'pip_reason' => $this->request->getVar('reason'), 
                    'ip_source_type'=>'Manual',
                    'pip_created_by' => $tokendata['uid']             
                ];
                $id = $model->insert($data);
                if (!$id) 
                 {
                    $response = [
                        'errors' => $model->errors(),
                        'ret_data' => 'fail',
                        'msg' => "Some error occurred please try again",
                        'data'=>[]
                    ];
    
                    return $this->respond($response, 200);
                }
                else{
                    $this->insertUserLog('Create New IP '.$this->request->getVar('ip'),$tokendata['uid']);
                    $data = $model->where('pip_id ',$id)->select('pip_id,pip_address,pip_reason,ip_source_type,pip_created_on,pip_updated_on')->first();
                    return $this->respond(['ret_data' => 'success', 'data'=>$data], 200);
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
        
    }

   /**
     * @api {post} permittedIP/update IP Update
     * @apiName IP Update
     * @apiGroup IP
     * @apiPermission super admin
     *
     *@apiBody {String} ip IP Address
     *@apiBody {String} reason Permiited Reason    
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/
    public function update($id = null)
    {
        $model = new PermittedIPModel();
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
                'ip'=>'required', 
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'pip_address' => $this->request->getVar('ip'),
                'pip_reason' => $this->request->getVar('reason'), 
                'ip_source_type'=>'Manual',
                'pip_updated_by' => $tokendata['uid']             
            ];
            if ( $model->where('pip_id',  $this->db->escapeString($this->request->getVar('id')))->set($data)->update() === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            }
            else{
                $this->insertUserLog('Update IP '.$this->request->getVar('ip'),$tokendata['uid']);
                $dataa = $model->where('pip_id ',$this->db->escapeString($this->request->getVar('id')))->select('pip_id,pip_address,pip_reason,ip_source_type,pip_created_on,pip_updated_on')->first();
                return $this->respond(['ret_data' => 'success','data'=> $dataa], 200);
            }


        }
        
    }

    /**
     * @api {post} permittedIP/delete IP delete
     * @apiName IP delete
     * @apiGroup IP
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} id   id of the ip to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $model = new PermittedIPModel();
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
                'pip_delete_flag' => 1,                
            ];
           if($model->where('pip_id',  $this->db->escapeString($id))->set($data)->update() === false )
            {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->respond($response, 200);
            }
            else
            {
                $this->insertUserLog('IP Deleted ',$tokendata['uid']);
                $response = [
                    'ret_data' => 'success'
                ];
                return $this->respond($response, 200);

            }
        }
    }
    public function getPermittedIps()
    {
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
            $permittedIp=new PermittedIPModel();
            $keydetails=$permittedIp->where("pip_delete_flag",0)->orderBy('pip_id','desc')->findAll();
            $data['ret_data']="success";
            $data['pips']=$keydetails;
            return $this->respond($data,200);
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
