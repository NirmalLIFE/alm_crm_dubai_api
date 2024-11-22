<?php

namespace App\Controllers\User;

use CodeIgniter\API\ResponseTrait;
use App\Models\System\FeaturerolemappingModel;
use App\Models\User\UserModel;
use App\Models\User\UserroleModel;
use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\UserActivityLog;

class UserroleController extends ResourceController
{
   
    use ResponseTrait;
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
     /**
     * @api {get} user/userrolecontroller  User role list
     * @apiName User role list
     * @apiGroup User
     * @apiPermission users
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   role_list user role Object containing user role list 
     * @apiError Unauthorized user with this Unauthorized token.
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
        $validModel=new Validation();
        $Common=new Common();
        $heddata=$this->request->headers();
        $tokendata=$Common->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
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
           // $this->insertUserLog('View User Role List',$tokendata['uid']);
            $userroleModel = new UserroleModel();

            $result= $userroleModel->select('role_id ,role_name,role_description,role_groupid,role_created_on,role_created_by,role_updated_by ,role_deleteflag,role_dept_id,dept_name')
            ->join('department','department.dept_id=user_roles.role_dept_id')
            ->where('role_deleteflag', 0)
            ->findAll();


         
            if($result){

               $data['ret_data']="success";
               $data['roleList']=$result;
               return $this->respond($data,200);
            }
            else {
               $data['ret_data']="fail";
               return $this->respond($data,200);
            }
        }
    }

      /**
     * @api {get} user/userrolecontroller/:id  User role edit
     * @apiName User role edit
     * @apiGroup User
     * @apiPermission User
     *
     *
     * @@apiParam {String} id user role id  to be edited
     *
     * @apiSuccess {String}    ret_data Success or fail
     * @apiSuccess {Object}    userrole object with user role details
     * @apiSuccess {Object}    feature object with user feature details
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
    {
        $validModel=new Validation();
        $Common=new Common();
        $heddata=$this->request->headers();
        $tokendata=$Common->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
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
            $UserroleModel = new UserroleModel();
            $userrole = $UserroleModel->where("role_id", base64_decode($id))->where("role_deleteflag", 0)
            ->join('user_group','user_group.ug_id=role_groupid','left')->first();
            $builder = $this->db->table('feature_role_mapping');
            $builder->select('features_list.ft_id,features_list.ft_name,feature_actions.fa_id,feature_actions.fa_name,user_roles.role_name,user_roles.role_dept_id,user_roles.dept_head_status');
            $builder->where('frm_role_id', $this->db->escapeString(base64_decode($id)));
            $builder->join('user_roles', 'user_roles.role_id = feature_role_mapping.frm_role_id', 'INNER JOIN');
            $builder->join('features_list', 'features_list.ft_id =feature_role_mapping.frm_feature_id', 'INNER JOIN');
            $builder->join('feature_actions', 'feature_actions.fa_id=feature_role_mapping.frm_action_id', 'INNER JOIN');
            $builder->orderBy('frm_feature_id');
            $query = $builder->get();
            $features = $query->getResultArray();
            if($userrole){
                $this->insertUserLog('View User Role List For Update',$tokendata['uid']);
                $data['ret_data']="success";
                $data['userrole']=$userrole;
                $data['feature']=$features;
                return $this->respond($data,200);
            }else{
                $data['ret_data']="fail";
                return $this->respond($data,200);
            }
        }
    }

     /**
     * @api {post} user/cuserrolecontroller User role add 
     * @apiName User role add 
     * @apiGroup User
     * @apiPermission user
     *
     *
     * @apiBody {String} rname  User role name
     * @apiBody {String} rdesc User role description
     * @apiBody {String} frm_feature_id Feature id
     * @apiBody {String} frm_action_id Feature action id
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function create()
    {  
        $userroleModel = new UserroleModel();
        $FeatureModel = new FeaturerolemappingModel();
        $validModel=new Validation();
        $Common=new Common();
        $heddata=$this->request->headers();
        $tokendata=$Common->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
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
            $features=$this->request->getVar("features");
            $indata=[
                'role_name'    => $this->request->getVar('rname'),
                'role_description'   => $this->request->getVar('rdesc'),
                'role_dept_id'   => $this->request->getVar('rdept_id'),
                'dept_head_status'=>$this->request->getVar('hdvalue'),
                'role_created_by'    => $tokendata['uid'],
                'role_updated_by'   => $tokendata['uid']
            ];
             //var_dump($indata);die;
            $results=$userroleModel->insert($indata);
            if($results){
               
                foreach ($features as $feature) {
                    $in_data=array();
                    for($i=0;$i<count($feature->actions);$i++){
        
                        $infdata=[
                            'frm_role_id'   =>$results,
                            'frm_feature_id'=>$feature->featureId,
                            'frm_action_id' =>$feature->actions[$i],
                        ];
                        array_push($in_data,$infdata);
                       
                    }
                    $ret= $FeatureModel->insertBatch($in_data);
                }
                $this->insertUserLog('New User Role Created'.$this->request->getVar('rname'),$tokendata['uid']);
                $data['ret_data']='success';
                return $this->respond($data,200);
            }else{
                $data['ret_data']="fail";
                return $this->respond($data,200);
            }
        }
    }
       /**
     * @api {post} user/cuserrolecontroller/update User role update 
     * @apiName User role update 
     * @apiGroup User
     * @apiPermission user
     *
     * @apiBody {String} roleid  User role id
     * @apiBody {String} rname  User role name
     * @apiBody {String} rdesc User role description
     * @apiBody {String} frm_id Feature role mapping id
     * @apiBody {String} frm_feature_id Feature id
     * @apiBody {String} frm_action_id Feature action id
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function update($id = null)
    {
        $features=$this->request->getVar("features");
        $userroleModel = new UserroleModel();
        $FeatureModel = new FeaturerolemappingModel();
        $validModel=new Validation();
        $Common=new Common();
        $heddata=$this->request->headers();
        $tokendata=$Common->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
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
                'roleid'=>'required',
                'rname' => 'required',
              
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $indata=[
                'role_name'    => $this->request->getVar('rname'),
                'role_groupid'    => $this->request->getVar('groupid'),
                'role_dept_id'    => $this->request->getVar('rdeptid'),                
                'role_description'   => $this->request->getVar('rdesc'),
                'role_created_by'    => $tokendata['uid'],
                'role_updated_by'   => $tokendata['uid'],
                'dept_head_status'=>$this->request->getVar('hdvalue'),
            ];
            $results=$userroleModel->update($this->db->escapeString($this->request->getVar('roleid')),$indata);
            if($results){
                $ret_result=$FeatureModel->where('frm_role_id',$this->db->escapeString($this->request->getVar('roleid')))->delete();
                foreach ($features as $feature) {
                    $in_data=array();
                    for($i=0;$i<count($feature->actions);$i++){
                        $infdata=[
                            'frm_role_id'   =>$this->request->getVar('roleid'),
                            'frm_feature_id'=>$feature->featureId,
                            'frm_action_id' =>$feature->actions[$i],
                        ];
                        array_push($in_data,$infdata);
                    }
                    $ret= $FeatureModel->insertBatch($in_data);
                }
                if($ret){
                    $this->insertUserLog('User Role Updated'.$this->request->getVar('rname'),$tokendata['uid']);
                    $data['ret_data']="success";
                    return $this->respond($data,200);
                }else{
                    $data['ret_data']="fail";
                    return $this->respond($data,200);
                }
               
            }else{
                $data['ret_data']="fail";
                return $this->respond($data,200);
            }
        }
    }

 /**
     * @api {get} user/userrolecontroller/delete User role delete
     * @apiName User role delete
     * @apiGroup User
     * @apiPermission User
     *
     * 
     * @apiParam {String} id  User role id of the user role to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function delete($id = null)
    {
        $userroleModel = new UserroleModel();
        $validModel=new Validation();
        $Common=new Common();
        $heddata=$this->request->headers();
        $tokendata=$Common->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
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
            if($id){
                $data = [
                    'role_deleteflag' => 1
                ];
            }
            $results=$userroleModel->update($this->db->escapeString(base64_decode($id)),$data);
            if($results){
                $this->insertUserLog('User Role Deleted',$tokendata['uid']);
                $data['ret_data']="success";
                return $this->respond($data,200);
            }
            else {
                $data['ret_data']="fail";
                return $this->respond($data,200);
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
