<?php

namespace App\Controllers\Calllogs;

use CodeIgniter\RESTful\ResourceController;

use App\Models\SuperAdminModel;
use App\Models\UserModel;
use App\Models\Calllogs\CustomerCallsModel;
use Config\Common;
use Config\Validation;

class CustomerCalls extends ResourceController
{

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
        //
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
        $callmodel = new CustomerCallsModel();
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
                'time_start'=>'required',
                'call_from'=>'required',
                'call_to'=>'required',
                'call_duration' => 'required',
                'talk_duration' => 'required',
                'status' => 'required',
            ];
            if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $inData=[
                'time_start'=>$this->request->getVar('time_start'),
                'call_from'=>$this->request->getVar('call_from'),
                'call_to'=>$this->request->getVar('call_to'),
                'call_duration' => $this->request->getVar('call_duration'),
                'talk_duration' => $this->request->getVar('talk_duration'),
                'status' => $this->request->getVar('status'),
                'call_type' => $this->request->getVar('type'),
            ];
            $result=$callmodel->insert($inData);
            if($result){
                $data['ret_data']="success";
                return $this->respond($data,200);

            }else{
                $data['ret_data']="fail";
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
}
