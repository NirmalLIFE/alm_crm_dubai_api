<?php

namespace App\Controllers\Quotes;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Quotes\QuotesItemModel;
use App\Models\Quotes\QuotesMasterModel;
use App\Models\SuperAdminModel;
use App\Models\Leads\LeadActivityModel;
use App\Models\UserModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\MaragiCustomerModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;

class SplQuotation extends ResourceController
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
        $modelQ = new QuotesMasterModel();
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
            $res = $modelQ->select('qt_id,qt_code,qt_cus_name,qt_cus_contact,qt_vin,qt_reg_no,qt_chasis,qt_make,qt_odometer,qt_service_adv,qt_parts_adv,qt_jc_no,qt_cus_id,qt_lead_id,qt_type,qt_amount,qt_tax,qt_total,sau.us_firstname as sa_name,sau.us_email as sa_email,pau.us_firstname as pa_name,pau.us_email as pa_email')
                ->where('qt_delete_flag', 0)
                ->where('qt_type', 2)
                ->join('users sau', 'sau.us_id=qt_service_adv', 'left')
                ->join('users pau', 'pau.us_id=qt_parts_adv', 'left')
                ->orderBy('qt_id', 'desc')
                ->findAll();
            if ($res) {
                $this->insertUserLog('View Normal Quotation List', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'quotes' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'quotes' => []
                ];
                return $this->fail($response, 409);
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
        $modelQT = new QuotesItemModel();
        $modelQ = new QuotesMasterModel();
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
            $res = $modelQ->select('qt_id,qt_code,qt_type,qt_cus_name,qt_cus_contact,qt_vin,qt_reg_no,qt_chasis,qt_make,qt_odometer,qt_service_adv,qt_parts_adv,qt_jc_no,qt_cus_id,qt_lead_id,qt_type,qt_amount,qt_tax,qt_total,part_code_print,avail_print,part_type_print,brand_print,qt_camp_id,sau.us_firstname as sa_name,sau.us_email as sa_email,pau.us_firstname as pa_name,pau.us_email as pa_email')
                ->where('qt_id', $id)
                ->where('qt_delete_flag', 0)
                ->join('users sau', 'sau.us_id=qt_service_adv', 'left')
                ->join('users pau', 'pau.us_id=qt_parts_adv', 'left')
                ->join('campaign', 'campaign.camp_id=qt_camp_id', 'left')
                ->orderBy('qt_id', 'desc')
                ->first();
            if ($res) {
                $qt_items = $modelQT->where('qt_id', $id)
                    ->where('item_delete_flag', 0)
                    ->findAll();
                // $this->insertUserLog('View Normal Quotation Data for Update', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'quotation' => $res,
                    'qt_items' => $qt_items
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'quotation' => []
                ];
                return $this->fail($response, 409);
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
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        $sa = 0;
        $pa = 0;
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();

            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if ($user['us_role_id'] == 11) {
                $sa = $user['us_id'];
            } else if ($user['us_role_id'] == 17) {
                $pa = $user['us_id'];
            }
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {
            $rules = [
                'chasis_no' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $modelQT = new QuotesItemModel();
            // $modelQTType = new QuoteItemTypesModel();
            $modelQ = new QuotesMasterModel();
            $items = $this->request->getVar("items");
            // $builder = $this->db->table('sequence_data');
            $builder = $this->db->table('quot_seq_data');
            $builder->selectMax('current_special_seq');
            $query = $builder->get();
            $row = $query->getRow();
            $sequnceval = $row->current_special_seq;
            $seqvalfinal = $row->current_special_seq;
            if (strlen($row->current_special_seq) == 1) {
                $sequnceval = "ALMSQ-000" . $row->current_special_seq;
            } else if (strlen($row->current_special_seq) == 2) {
                $sequnceval = "ALMSQ-00" . $row->current_special_seq;
            } else if (strlen($row->current_special_seq) == 3) {
                $sequnceval = "ALMSQ-0" . $row->current_special_seq;
            } else {
                $sequnceval = "ALMSQ-" . $row->current_special_seq;
            }
            $data = [
                'qt_cus_name' => ucwords($this->request->getVar('cust_name')),
                'qt_chasis' => strtoupper($this->request->getVar('chasis_no')),
                'qt_jc_no' => strtoupper($this->request->getVar('jc_no')),
                'qt_reg_no' => strtoupper($this->request->getVar('reg_no')),
                'qt_amount' => $this->request->getVar('quot_total'),
                'qt_odometer' => $this->request->getVar('odometer'),
                'qt_tax' => $this->request->getVar('tax_amount'),
                'qt_total' => $this->request->getVar('quot_total') + $this->request->getVar('tax_amount'),
                'qt_created_by' => $tokendata['uid'],
                'qt_make' =>  strtoupper($this->request->getVar('model')),
                'qt_cus_contact' =>  $this->request->getVar('contact'),
                'qt_code' => $sequnceval,
                'qt_service_adv'  => $sa,
                'qt_parts_adv' => $pa,
                'qt_type' => 2,
                'part_type_print' => 0,
                'avail_print' => 0,
                'part_code_print' => 0,
                'qt_cus_id' => null,
                'qt_lead_id' => null
            ];
            $this->db->transStart();
            $id = $modelQ->insert($data);
            if ($id) {
                $insdata = array();
                foreach ($items as $item) {
                    $insdata[] = array(
                        'item_type' => $item->type,
                        'item_code' => "",
                        'item_name' => strtoupper($item->item_name),
                        'item_note' => "",
                        'item_qty' => $item->quantity,
                        'item_special_avail' => $item->availability,
                        'unit_price' => $item->unit_price,
                        'disc_amount' => $item->discount_amt,
                        'qt_id' => $id,
                        'item_seq' => 0,
                        'item_group' => 0,
                        'item_created_by' => $tokendata['uid']
                    );
                }
                $ret = $modelQT->insertBatch($insdata);
                $builder = $this->db->table('quot_seq_data');
                $builder->set('current_special_seq', ++$seqvalfinal);
                $builder->update();

                $this->insertUserLog('Create New Special Quotation ' . $sequnceval, $tokendata['uid']);
                if ($this->db->transStatus() === false) {
                    $this->db->transRollback();
                    $data['ret_data'] = "fail";
                    return $this->respond($data, 200);
                } else {
                    $this->db->transCommit();
                    $data['ret_data'] = "success";
                    return $this->respond($data, 200);
                }
            } else {
                $data['ret_data'] = "fail";
                return $this->fail($data, 200);
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
        $modelQT = new QuotesItemModel();
        $modelQ = new QuotesMasterModel();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        $sa = 0;
        $pa = 0;
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            $sa = 0;
            $pa = 0;
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
            $items = $this->request->getVar("items");
            $data = [
                'qt_cus_name' => ucwords($this->request->getVar('cust_name')),
                'qt_chasis' => strtoupper($this->request->getVar('chasis_no')),
                'qt_jc_no' => strtoupper($this->request->getVar('jc_no')),
                'qt_reg_no' => strtoupper($this->request->getVar('reg_no')),
                'qt_amount' => $this->request->getVar('quot_total'),
                'qt_odometer' => $this->request->getVar('odometer'),
                'qt_tax' => $this->request->getVar('tax_amount'),
                'qt_total' => $this->request->getVar('quot_total') + $this->request->getVar('tax_amount'),
                'qt_created_by' => $tokendata['uid'],
                'qt_make' =>  strtoupper($this->request->getVar('make')),
                'qt_cus_contact' =>  $this->request->getVar('contact'),
                'qt_type' => 2,
                'part_type_print' => 0,
                'avail_print' => 0,
                'part_code_print' => 0,
                'qt_cus_id' => null,
                'qt_lead_id' => null
            ];
            $res = $modelQ->update($this->request->getVar('qt_id'), $data);
            if ($res) {
                if ($user['us_role_id'] == 11) {
                    $datas = [
                        'qt_service_adv'  => $user['us_id'],
                    ];
                    $res = $modelQ->update($this->request->getVar('qt_id'), $datas);
                } else if ($user['us_role_id'] == 17) {
                    $datap = [
                        'qt_parts_adv' => $user['us_id']
                    ];
                    $res = $modelQ->update($this->request->getVar('qt_id'), $datap);
                }
                //$result=$modelQ->where('qt_cus_id',$this->request->getVar('customerid'))->delete();
                $mdatat = array();
                $insdata = array();

                foreach ($items as $item) {
                    if ($item->item_id > 0) {
                        $mdatat[] = array(
                            'item_id' => $item->item_id,
                            'item_type' => $item->item_type,
                            'item_code' => "",
                            'item_name' => strtoupper($item->item_name),
                            'item_qty' => $item->item_qty,
                            'item_special_avail' => $item->item_special_avail,
                            'unit_price' => $item->unit_price,
                            'disc_amount' => $item->disc_amount,
                            'qt_id' =>  $this->request->getVar('qt_id'),
                            'item_seq' => $item->item_seq,
                            'item_group' => 0,
                            'item_created_by' => $tokendata['uid'],
                            'item_delete_flag' => $item->item_delete_flag,
                            'item_updated_by' => $tokendata['uid'],
                        );
                    } else {
                        $insdata[] = array(
                            'item_type' => $item->item_type,
                            'item_code' => "",
                            'item_name' => strtoupper($item->item_name),
                            'item_qty' => $item->item_qty,
                            'unit_price' => $item->unit_price,
                            'item_special_avail' => $item->item_special_avail,
                            'disc_amount' => $item->disc_amount,
                            'qt_id' =>  $this->request->getVar('qt_id'),
                            'item_seq' => 0,
                            'item_group' => 0,
                            'item_created_by' => $tokendata['uid'],
                            'item_delete_flag' => 0,
                            'item_updated_by' => $tokendata['uid'],
                        );
                    }
                }
                if (sizeof($mdatat) > 0) {
                    $ret = $modelQT->updateBatch($mdatat, 'item_id');
                }
                if (sizeof($insdata) > 0) {
                    $ret = $modelQT->insertBatch($insdata);
                }
                $this->insertUserLog('Update Special Quotation', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                ];
                if ($this->db->transStatus() === false) {
                    $this->db->transRollback();
                    $data['ret_data'] = "fail";
                    return $this->respond($data, 200);
                } else {
                    $this->db->transCommit();
                    $data['ret_data'] = "success";
                    return $this->respond($data, 200);
                }
            } else {
                $response = [
                    'ret_data' => 'fail',

                ];
                return $this->fail($response, 400);
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
        $modelQT = new QuotesItemModel();
        $modelQ = new QuotesMasterModel();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        $sa = 0;
        $pa = 0;
        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            $sa = 0;
            $pa = 0;
            if (!$super) return $this->fail("invalid user", 400);
        } else if ($tokendata['aud'] == 'user') {
            $usmodel = new UserModel();
            $user = $usmodel->where("us_id", $tokendata['uid'])->first();
            if ($user['us_role_id'] == 2) {
                $sa = $user['us_id'];
            } else if ($user['us_role_id'] == 3) {
                $pa = $user['us_id'];
            }
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {
            $id = $this->request->getVar('id');
            $data = [
                'qt_delete_flag' => 1,
            ];
            if ($modelQ->where('qt_id', $id)->set($data)->update() === false) {
                $response = [
                    'errors' => $modelQ->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->fail($response, 200);
            } else {
                $this->insertUserLog('Delete Special Quotation', $tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 200);
            }
        }
    }

    public function insertUserLog($log, $id)
    {
        $logmodel = new UserActivityLog();
        $ip = $this->request->getIPAddress();
        $indata = [
            'log_user'    => $id,
            'log_ip'   =>  $ip,
            'log_activity' => $log
        ];
        $results = $logmodel->insert($indata);
    }

    public function getSplQuotesList()
    {
        $modelQ = new QuotesMasterModel();
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

            $start_date =  $this->request->getVar("dateFrom");
            $end_date =  $this->request->getVar("dateTo");
            $sa_id =  $this->request->getVar("sa_id");

            if ($sa_id != '0') {
                $res = $modelQ->select('qt_id,qt_code,qt_cus_name,qt_cus_contact,qt_vin,qt_reg_no,qt_chasis,qt_make,qt_odometer,qt_service_adv,qt_parts_adv,qt_jc_no,qt_cus_id,qt_lead_id,qt_created_on,qt_type,qt_amount,qt_tax,qt_total,sau.us_firstname as sa_name,sau.us_email as sa_email,pau.us_firstname as pa_name,pau.us_email as pa_email')
                ->where('qt_delete_flag', 0)
                ->where('qt_type', 2)
                ->where('DATE_FORMAT(qt_created_on, "%Y-%m-%d") >=',  $start_date)
                ->where('DATE_FORMAT(qt_created_on, "%Y-%m-%d") <=',  $end_date)
                ->where('qt_service_adv',  $sa_id)
                ->join('users sau', 'sau.us_id=qt_service_adv', 'left')
                ->join('users pau', 'pau.us_id=qt_parts_adv', 'left')
                ->orderBy('qt_id', 'desc')
                ->findAll();
            }else{
                $res = $modelQ->select('qt_id,qt_code,qt_cus_name,qt_cus_contact,qt_vin,qt_reg_no,qt_chasis,qt_make,qt_odometer,qt_service_adv,qt_parts_adv,qt_jc_no,qt_created_on,qt_cus_id,qt_lead_id,qt_type,qt_amount,qt_tax,qt_total,sau.us_firstname as sa_name,sau.us_email as sa_email,pau.us_firstname as pa_name,pau.us_email as pa_email')
                ->where('qt_delete_flag', 0)
                ->where('qt_type', 2)
                ->where('DATE_FORMAT(qt_created_on, "%Y-%m-%d") >=',  $start_date)
                ->where('DATE_FORMAT(qt_created_on, "%Y-%m-%d") <=',  $end_date)
                ->join('users sau', 'sau.us_id=qt_service_adv', 'left')
                ->join('users pau', 'pau.us_id=qt_parts_adv', 'left')
                ->orderBy('qt_id', 'desc')
                ->findAll();
            }
           
            if ($res) {
                // $this->insertUserLog('View Normal Quotation List', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'quotes' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'quotes' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
}
