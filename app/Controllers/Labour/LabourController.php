<?php

namespace App\Controllers\Labour;

use CodeIgniter\RESTful\ResourceController;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\Labour\LabourMaster;


class LabourController extends ResourceController
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
        $labourMaster = new LabourMaster();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {

            $labour = $labourMaster->where("lm_delete_flag !=", 1)
                ->findAll();

            if (sizeof($labour) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'labour' => $labour,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
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
        $LabourMaster = new LabourMaster();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {

            $builder = $this->db->table('sequence_data');
            $builder->selectMax('labour_seq');
            $query = $builder->get();
            $row = $query->getRow();
            $code = $row->labour_seq;
            $seqvalfinal = $row->labour_seq;
            if (strlen($row->labour_seq) == 1) {
                $code = "ALMLB-000" . $row->labour_seq;
            } else if (strlen($row->labour_seq) == 2) {
                $code = "ALMLB-00" . $row->labour_seq;
            } else if (strlen($row->labour_seq) == 3) {
                $code = "ALMLB-0" . $row->labour_seq;
            } else {
                $code = "ALMLB-" . $row->labour_seq;
            }

            $labourdata = [
                'lm_code' => $code,
                'lm_name' =>  $this->request->getVar('labour_name'),
                'lm_description' => $this->request->getVar('labour_description'),
                'lm_created_on' => date("Y-m-d H:i:s"),
                'lm_created_by' => $tokendata['uid'],
                'lm_updated_on' => date("Y-m-d H:i:s"),
                'lm_updated_by' => $tokendata['uid'],
            ];

            $labourentry = $LabourMaster->insert($labourdata);
            if ($labourentry) {
                $builder = $this->db->table('sequence_data');
                $builder->set('labour_seq', ++$seqvalfinal);
                $builder->update();
            }



            if ($labourentry) {
                $response = [
                    'ret_data' => 'success',
                    'labour' => $labourentry,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
            }
            return $this->respond($response, 200);
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
