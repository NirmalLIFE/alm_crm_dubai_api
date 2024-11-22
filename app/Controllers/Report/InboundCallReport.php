<?php

namespace App\Controllers\Report;

use CodeIgniter\RESTful\ResourceController;
use App\Models\User\UserModel;
use CodeIgniter\API\ResponseTrait;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Leads\LeadModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Leads\LeadCallLogModel;
use App\Models\Commonutils\CommonNumberModel;


class InboundCallReport extends ResourceController
{
    use ResponseTrait;
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }


    public function InboundReport()
    {

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
            echo "hiii";
            $report=$this->request->getVar("report");
            var_dump($report);die;
            $response = [
                'ret_data'=>'success',
                'lead'=>$report
            ];
            return $this->respond($response,200);
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
