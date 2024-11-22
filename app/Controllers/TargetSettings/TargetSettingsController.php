<?php

namespace App\Controllers\TargetSettings;

use App\Models\TargetSettings\TargetSettingsModel;
use App\Models\SuperAdminModel;
use App\Models\User\UserModel;
use Config\Common;
use Config\Validation;

use CodeIgniter\RESTful\ResourceController;

class TargetSettingsController extends ResourceController
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
    // public function create()
    // {
    //     $targetsettingsModel = new TargetSettingsModel();
    //     $validModel = new Validation();
    //     $Common = new Common();
    //     $heddata = $this->request->headers();
    //     $tokendata = $Common->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
    //         $targetdata = $this->request->getVar("targetdata");
    //         $roleid = $this->request->getVar("role_id");
    //         $year = $this->request->getVar("year");

    //         if (sizeof($targetdata) > 0) {
    //             $array_data = array();
    //             foreach ($targetdata as $eachdata) {
    //                 $existingRow = $targetsettingsModel->where('ts_year', $year)
    //                     ->where('ts_roleid', $roleid)->where('ts_month', $eachdata->monthvalue)
    //                     ->first();
    //                 if ($existingRow) {
    //                     $existingRow1 = (object) $existingRow;
    //                     $ext_ts_id = $existingRow1->ts_id;
    //                 }

    //                 $in_data = [
    //                     'ts_roleid' => $roleid,
    //                     'ts_month' => $eachdata->monthvalue,
    //                     'ts_year' => $year,
    //                     'ts_createdby' => $tokendata['uid'],
    //                     'ts_updatedby' => $tokendata['uid'],
    //                 ];

    //                 if ($eachdata->newcust !== '' && $eachdata->newcust !== 0 && $eachdata->newcust !== null ) {
    //                     $in_data['ts_newinbound'] = $eachdata->newcust;
    //                 }

    //                 if ($eachdata->extcust !== '' && $eachdata->extcust !== 0 && $eachdata->extcust !== null) {
    //                     $in_data['ts_exinbound'] = $eachdata->extcust;
    //                 }

    //                 if ($eachdata->target !== '' && $eachdata->target !== 0 && $eachdata->target !== null) {
    //                     $in_data['ts_lostarget'] = $eachdata->target;
    //                 }
                    
    //                 if ($existingRow) {
    //                     $targetsettingsModel->where('ts_id', $ext_ts_id)->set($in_data)->update();
    //                 } else {
    //                     $targetsettingsModel->insert($in_data);
    //                 }
    //             }

    //             // foreach ($targetdata as $eachdata) {
    //             //     if ($eachdata->newcust != '' && $eachdata->extcust != '' && $eachdata->target != '') {
    //             //         $in_data = [
    //             //             'ts_roleid' => $roleid,
    //             //             'ts_month' => $eachdata->monthvalue,
    //             //             'ts_year' => $year,
    //             //             'ts_newinbound' => $eachdata->newcust,
    //             //             'ts_exinbound' => $eachdata->extcust,
    //             //             'ts_lostarget' => $eachdata->target,
    //             //             'ts_createdby' => $tokendata['uid'],
    //             //             'ts_updatedby' => $tokendata['uid'],
    //             //         ];
    //             //         array_push($array_data, $in_data);
    //             //     } else if ($eachdata->newcust != '' && $eachdata->extcust != '') {
    //             //         $in_data = [
    //             //             'ts_roleid' => $roleid,
    //             //             'ts_month' => $eachdata->monthvalue,
    //             //             'ts_year' => $year,
    //             //             'ts_newinbound' => $eachdata->newcust,
    //             //             'ts_exinbound' => $eachdata->extcust,
    //             //             // 'ts_lostarget' => $eachdata->target,
    //             //             'ts_createdby' => $tokendata['uid'],
    //             //             'ts_updatedby' => $tokendata['uid'],
    //             //         ];
    //             //         array_push($array_data, $in_data);
    //             //     } else if ($eachdata->target != '') {
    //             //         $in_data = [
    //             //             'ts_roleid' => $roleid,
    //             //             'ts_month' => $eachdata->monthvalue,
    //             //             'ts_year' => $year,
    //             //             //'ts_newinbound' => $eachdata->newcust,
    //             //             // 'ts_exinbound' => $eachdata->extcust,
    //             //             'ts_lostarget' => $eachdata->target,
    //             //             'ts_createdby' => $tokendata['uid'],
    //             //             'ts_updatedby' => $tokendata['uid'],
    //             //         ];
    //             //         array_push($array_data, $in_data);
    //             //     }
    //             // }
    //             // $ret = $targetsettingsModel->insertBatch($array_data);
    //             $data['ret_data'] = 'success';
    //             return $this->respond($data, 200);
    //         } else {
    //             $data['ret_data'] = "fail";
    //             return $this->respond($data, 200);
    //         }
    //     }
    // }
    public function create()
    {
        $targetsettingsModel = new TargetSettingsModel();
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
            $targetdata=$this->request->getVar("targetdata");
            if(sizeof($targetdata)>0){
                $insertarray_data=array();
                $updatearray_data=array();
                foreach ($targetdata as $eachdata) {
                    if($eachdata->ts_id=='0'){
                    $insert_data = [
                        'ts_roleid'=>$this->request->getVar("role_id"),
                        'ts_month'=>$eachdata->ts_month,
                        'ts_year' =>$this->request->getVar("year"),
                        'ts_newinbound' =>$eachdata->ts_newinbound,
                        'ts_exinbound' =>$eachdata->ts_exinbound,
                        'ts_lostarget' =>$eachdata->ts_lostarget,
                        'ts_createdby' =>$tokendata['uid'],
                        'ts_updatedby' =>$tokendata['uid'],
                    ];
                    array_push($insertarray_data,$insert_data);

                }else{
                    $update_data = [
                        'ts_id'=>$eachdata->ts_id,
                        'ts_roleid'=>$this->request->getVar("role_id"),
                        'ts_month'=>$eachdata->ts_month,
                        'ts_year' =>$this->request->getVar("year"),
                        'ts_newinbound' =>$eachdata->ts_newinbound,
                        'ts_exinbound' =>$eachdata->ts_exinbound,
                        'ts_lostarget' =>$eachdata->ts_lostarget,
                        'ts_createdby' =>$tokendata['uid'],
                        'ts_updatedby' =>$tokendata['uid'],
                    ];
                    array_push($updatearray_data,$update_data);
                }
                }
                sizeof($insertarray_data)>0?$targetsettingsModel->insertBatch($insertarray_data):"";
                sizeof($updatearray_data)>0?$targetsettingsModel->updateBatch($updatearray_data,'ts_id'):"";
                $data['ret_data']='success';
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

    public function gettarget_details()
    {

        $targetsettingsModel = new TargetSettingsModel();
        $validModel = new Validation();
        $Common = new Common();
        $heddata = $this->request->headers();
        $tokendata = $Common->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
            $rules = [
                'role_id' => 'required',
                'year' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $result = $targetsettingsModel->where('ts_roleid', $this->request->getVar('role_id'))
                ->where('ts_year', $this->request->getVar('year'))
                ->findAll();
            if (sizeof($result) > 0) {
                $data['ret_data'] = "success";
                $data['result'] = $result;
                return $this->respond($data, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        }
    }

    public function savetargetdata()
    {
        $targetsettingsModel = new TargetSettingsModel();
        $validModel = new Validation();
        $Common = new Common();
        $heddata = $this->request->headers();
        $tokendata = $Common->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata['aud'] == 'superadmin' || $tokendata['aud'] == 'user') {
        }
    }
}
