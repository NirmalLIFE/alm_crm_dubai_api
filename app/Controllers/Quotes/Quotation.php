<?php

namespace App\Controllers\Quotes;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Quotes\QuotesItemModel;
use App\Models\Quotes\QuotesMasterModel;
use App\Models\Quotes\QuoteItemTypesModel;
use App\Models\Quotes\QuoteVersionMasterModel;
use App\Models\Quotes\QuoteVersionItemsModel;
use App\Models\SuperAdminModel;
use App\Models\Leads\LeadModel;
use App\Models\UserModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\MaragiCustomerModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;
use App\Models\Quotes\QuotesLabourCondition;
use App\Models\Quotes\QuotesLabourPriority;
use App\Models\Quotes\QuotesLog;



class Quotation extends ResourceController
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
            } else if ($user['us_role_id'] == 16) {
                $pa = $user['us_id'];
            }
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {
            $res = $modelQ->select('qt_id,qt_code,qt_cus_name,qt_cus_contact,qt_vin,qt_reg_no,qt_chasis,qt_make,qt_odometer,qt_service_adv,qt_parts_adv,qt_jc_no,qt_cus_id,qt_lead_id,qt_type,qt_amount,qt_tax,qt_total,sau.us_firstname as sa_name,sau.us_email as sa_email,pau.us_firstname as pa_name,pau.us_email as pa_email')
                ->where('qt_delete_flag', 0)
                ->where('qt_type', 1)
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
            $res = $modelQ->select('qt_id,qt_code,qt_type,qt_cus_name,qt_cus_contact,qt_vin,qt_reg_no,qt_chasis,qt_make,qt_odometer,qt_service_adv,qt_parts_adv,qt_jc_no,qt_cus_id,qt_lead_id,qt_type,qt_amount,qt_tax,qt_total,part_code_print,avail_print,part_type_print,brand_print,qt_camp_id,sau.us_firstname as sa_name,sau.us_email as sa_email,pau.us_firstname as pa_name,pau.us_email as pa_email,qt_vehicle_value')
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
                    // ->where('its.qit_delete_flag', 0)
                    ->join('quote_item_types its', 'its.qit_item_id=item_id', 'left')
                    ->join('brand_list as bl', 'bl.brand_id=its.qit_brand', 'left')
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
            $modelQTType = new QuoteItemTypesModel();
            $modelQ = new QuotesMasterModel();
            $quote_log_model = new QuotesLog();
            $items = $this->request->getVar("items");
            // $builder = $this->db->table('sequence_data');
            $builder = $this->db->table('quot_seq_data');
            $builder->selectMax('current_seq');
            $query = $builder->get();
            $row = $query->getRow();
            $sequnceval = $row->current_seq;
            $seqvalfinal = $row->current_seq;
            if (strlen($row->current_seq) == 1) {
                $sequnceval = "ALMQ-000" . $row->current_seq;
            } else if (strlen($row->current_seq) == 2) {
                $sequnceval = "ALMQ-00" . $row->current_seq;
            } else if (strlen($row->current_seq) == 3) {
                $sequnceval = "ALMQ-0" . $row->current_seq;
            } else {
                $sequnceval = "ALMQ-" . $row->current_seq;
            }
            $data = [
                'qt_cus_name' => ucwords($this->request->getVar('cust_name')),
                'qt_chasis' => strtoupper($this->request->getVar('chasis_no')),
                'qt_jc_no' => strtoupper($this->request->getVar('jc_no')),
                'qt_reg_no' => strtoupper($this->request->getVar('reg_no')),
                'qt_amount' => $this->request->getVar('quot_total'),
                'qt_odometer' => $this->request->getVar('odometer'),
                //'qt_vehicle_value' => $this->request->getVar('vehicle_value'),
                'qt_tax' => $this->request->getVar('tax_amount'),
                'qt_total' => $this->request->getVar('quot_total') + $this->request->getVar('tax_amount'),
                'qt_created_by' => $tokendata['uid'],
                'qt_make' =>  strtoupper($this->request->getVar('make')),
                'qt_cus_contact' =>  $this->request->getVar('contact'),
                'qt_code' => $sequnceval,
                'qt_service_adv'  => $sa,
                'qt_parts_adv' => $pa,
                'qt_type' => 1,
                'part_type_print' => $this->request->getVar('part_type_print'),
                'avail_print' => $this->request->getVar('avail_print'),
                'part_code_print' => $this->request->getVar('part_code_print'),
                'qt_cus_id' => $this->request->getVar('cus_id'),
                'qt_lead_id' => $this->request->getVar('lead_id')
            ];
            $this->db->transStart();
            $id = $modelQ->insert($data);
            if ($id) {
                foreach ($items as $item) {
                    $insdata = array();
                    $insdata = [
                        'item_type' => $item->item_type,
                        'item_code' => $item->item_type != "2" ? strtoupper($item->item_code) : "",
                        'item_name' => strtoupper($item->item_name),
                        'item_note' => isset($item->item_note) ? $item->item_note : "",
                        'item_qty' => $item->item_qty,
                        'item_condition' => $item->item_type != "2" ? "" : $item->condition,
                        'item_priority' => $item->item_type == "1" ? "" : $item->priority,
                        'unit_price' => $item->unit_price,
                        'disc_amount' => 0,
                        'qt_id' => $id,
                        'item_seq' => $item->item_seq,
                        'item_group' => 0,
                        'item_created_by' => $tokendata['uid']
                    ];
                    $ret = $modelQT->insert($insdata);
                    if ($ret > 0) {
                        $updData = array();
                        foreach ($item->item_p_types as $part_type) {
                            $updData[] = array(
                                'qit_item_id' => $ret,
                                'qit_qt_id' => $id,
                                'qit_brand' => $part_type->qit_brand,
                                'qit_type' => $part_type->qit_type,
                                'qit_availability' => $part_type->qit_availability,
                                'qit_unit_price' => $part_type->qit_unit_price,
                                'qit_discount' => 0,
                                'qit_created_by' => $tokendata['uid'],
                            );
                        }
                        if (sizeof($updData) > 0) {
                            $modelQTType->insertBatch($updData);
                        }
                    }
                }
                $builder = $this->db->table('quot_seq_data');
                $builder->set('current_seq', ++$seqvalfinal);
                $builder->update();

                $logdata = [
                    'ql_qt_id' =>  $id,
                    'ql_notes' =>  $sequnceval . ' Quotation Was Created',
                    'ql_created_on' => date('Y-m-d H-i-s'),
                    'ql_created_by' => $tokendata['uid'],
                ];

                $quote_log_model->insert($logdata);

                $this->insertUserLog('Create new Normal Quotation ' . $sequnceval, $tokendata['uid']);

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
    // public function update($id = null)
    // {
    //     $modelQT = new QuotesItemModel();
    //     $modelQ = new QuotesMasterModel();
    //     $modelQuoteLog = new QuotesLog();
    //     $common = new Common();
    //     $valid = new Validation();
    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
    //     $sa = 0;
    //     $pa = 0;
    //     if ($tokendata['aud'] == 'superadmin') {
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
    //         $sa = 0;
    //         $pa = 0;
    //         if (!$super) return $this->fail("invalid user", 400);
    //     } else if ($tokendata['aud'] == 'user') {
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $tokendata['uid'])->first();
    //         if (!$user) return $this->fail("invalid user", 400);
    //     } else {
    //         $data['ret_data'] = "Invalid user";
    //         return $this->fail($data, 400);
    //     }
    //     if ($tokendata) {
    //         $items = $this->request->getVar("items");
    //         $olditems =  $this->request->getVar("olditems");
    //         $quote_code = $this->request->getVar('qt_code');

    //         // $logs = $this->compareItems($items, $olditems, $quote_code);
    //         // $response = [
    //         //     'ret_data' => 'fail',
    //         //     'Logs' => $logs,

    //         // ];
    //         // return $this->respond($response, 200);


    //         $data = [
    //             'qt_cus_name' => ucwords($this->request->getVar('cust_name')),
    //             'qt_chasis' => strtoupper($this->request->getVar('chasis_no')),
    //             'qt_jc_no' => strtoupper($this->request->getVar('jc_no')),
    //             'qt_reg_no' => strtoupper($this->request->getVar('reg_no')),
    //             'qt_amount' => $this->request->getVar('quot_total'),
    //             'qt_vehicle_value' => $this->request->getVar('vehicle_value'),
    //             'qt_odometer' => $this->request->getVar('odometer'),
    //             'qt_tax' => $this->request->getVar('tax_amount'),
    //             'qt_total'  => $this->request->getVar('grand_total'),
    //             'qt_make' =>  strtoupper($this->request->getVar('make')),
    //             'qt_cus_contact' =>  $this->request->getVar('contact'),
    //             'part_type_print' => $this->request->getVar('part_type_print'),
    //             'avail_print' => $this->request->getVar('avail_print'),
    //             'part_code_print' => $this->request->getVar('part_code_print'),
    //             'qt_service_adv' => $this->request->getVar('qt_service_adv'),
    //         ];
    //         $res = $modelQ->update($this->request->getVar('qt_id'), $data);
    //         if ($res) {
    //             $modelQTType = new QuoteItemTypesModel();
    //             if ($user['us_role_id'] == 11) {
    //                 $datas = [
    //                     'qt_service_adv'  => $user['us_id'],
    //                 ];
    //                 $res = $modelQ->update($this->request->getVar('qt_id'), $datas);
    //             } else if ($user['us_role_id'] == 17) {
    //                 $datap = [
    //                     'qt_parts_adv' => $user['us_id']
    //                 ];
    //                 $res = $modelQ->update($this->request->getVar('qt_id'), $datap);
    //             }
    //             //$result=$modelQ->where('qt_cus_id',$this->request->getVar('customerid'))->delete();
    //             $mdatat = array();
    //             foreach ($items as $item) {
    //                 if ($item->item_id > 0) {
    //                     $mdatat = [

    //                         'item_id' => $item->item_id,
    //                         'item_type' => $item->item_type,
    //                         'item_code' => $item->item_type != "2" ? strtoupper($item->item_code) : "",
    //                         'item_name' => strtoupper($item->item_name),
    //                         'item_qty' => $item->item_qty,
    //                         'unit_price' => $item->unit_price,
    //                         'item_condition' => $item->item_type != "2" ? "" : $item->item_condition,
    //                         'item_priority' => $item->item_type == "1" ? "" : $item->item_priority,
    //                         'disc_amount' => 0,
    //                         'qt_id' =>  $this->request->getVar('qt_id'),
    //                         'item_seq' => $item->item_seq,
    //                         'item_group' => 0,
    //                         'item_created_by' => $tokendata['uid'],
    //                         'item_delete_flag' => $item->item_delete_flag,
    //                         'item_updated_by' => $tokendata['uid'],
    //                     ];
    //                     if ($item->item_type == "1") {
    //                         $insdData = array();
    //                         $updData = array();
    //                         foreach ($item->item_p_types as $part_type) {
    //                             if ($part_type->qit_id > 0) {
    //                                 $updData[] = array(
    //                                     'qit_id' => $part_type->qit_id,
    //                                     'qit_item_id' => $part_type->qit_item_id,
    //                                     'qit_qt_id' =>  $this->request->getVar('qt_id'),
    //                                     'qit_brand' => $part_type->qit_brand,
    //                                     'qit_type' => $part_type->qit_type,
    //                                     'qit_availability' => $part_type->qit_availability,
    //                                     'qit_unit_price' => $part_type->qit_unit_price,
    //                                     'qit_discount' => 0,
    //                                     'qit_created_by' => $tokendata['uid'],
    //                                     'qit_delete_flag' => $part_type->qit_delete_flag,
    //                                     'qit_old_margin_price' => $part_type->old_margin_total,
    //                                     'qit_margin_price' => $part_type->margin_total,
    //                                 );
    //                             } else {
    //                                 $insdData[] = array(
    //                                     'qit_item_id' => $item->item_id,
    //                                     'qit_qt_id' =>  $this->request->getVar('qt_id'),
    //                                     'qit_brand' => $part_type->qit_brand,
    //                                     'qit_type' => $part_type->qit_type,
    //                                     'qit_availability' => $part_type->qit_availability,
    //                                     'qit_unit_price' => $part_type->qit_unit_price,
    //                                     'qit_discount' => 0,
    //                                     'qit_created_by' => $tokendata['uid'],
    //                                     'qit_delete_flag' => $part_type->qit_delete_flag,
    //                                 );
    //                             }
    //                         }
    //                         if (sizeof($updData) > 0) {
    //                             $modelQTType->updateBatch($updData, 'qit_id');
    //                         }
    //                         if (sizeof($insdData) > 0) {
    //                             $modelQTType->insertBatch($insdData);
    //                         }
    //                     }
    //                     $ret = $modelQT->update($item->item_id, $mdatat);
    //                 } else {
    //                     $mdatat = array();
    //                     $mdatat = [
    //                         'item_type' => $item->item_type,
    //                         'item_code' => $item->item_type != "2" ? strtoupper($item->item_code) : "",
    //                         'item_name' => strtoupper($item->item_name),
    //                         'item_qty' => $item->item_qty,
    //                         'item_condition' => $item->item_type != "2" ? "" : $item->item_condition,
    //                         'item_priority' => $item->item_type == "1" ? "" : $item->item_priority,
    //                         'unit_price' => $item->unit_price,
    //                         'disc_amount' => 0,
    //                         'qt_id' =>  $this->request->getVar('qt_id'),
    //                         'item_seq' => $item->item_seq,
    //                         'item_group' => 0,
    //                         'item_created_by' => $tokendata['uid'],
    //                         'item_delete_flag' => $item->item_delete_flag,
    //                         'item_updated_by' => $tokendata['uid'],
    //                     ];
    //                     $ret = $modelQT->insert($mdatat);
    //                     if ($item->item_type == "1" &&  $ret > 0) {
    //                         $insdData = array();
    //                         foreach ($item->item_p_types as $part_type) {
    //                             $insdData[] = array(
    //                                 'qit_item_id' => $ret,
    //                                 'qit_qt_id' =>  $this->request->getVar('qt_id'),
    //                                 'qit_brand' => $part_type->qit_brand,
    //                                 'qit_type' => $part_type->qit_type,
    //                                 'qit_availability' => $part_type->qit_availability,
    //                                 'qit_unit_price' => $part_type->qit_unit_price,
    //                                 'qit_discount' => 0,
    //                                 'qit_created_by' => $tokendata['uid'],
    //                                 'qit_delete_flag' => $part_type->qit_delete_flag,
    //                             );
    //                         }
    //                         if (sizeof($insdData) > 0) {
    //                             $modelQTType->insertBatch($insdData);
    //                         }
    //                     }
    //                 }
    //             }
    //             $this->insertUserLog('Update Normal Quotation', $tokendata['uid']);

    //             $response = [
    //                 'ret_data' => 'success',

    //             ];
    //             if ($this->db->transStatus() === false) {
    //                 $this->db->transRollback();
    //                 $data['ret_data'] = "fail";
    //                 return $this->respond($data, 200);
    //             } else {
    //                 $this->db->transCommit();
    //                 $data['ret_data'] = "success";
    //                 return $this->respond($data, 200);
    //             }
    //         } else {
    //             $response = [
    //                 'ret_data' => 'fail',

    //             ];
    //             return $this->fail($response, 400);
    //         }
    //     }
    // }

    public function update($id = null)
    {
        $modelQT = new QuotesItemModel();
        $modelQ = new QuotesMasterModel();
        $modelQuoteLog = new QuotesLog();
        $common = new Common();
        $valid = new Validation();
        $logd_model = new QuotesLog();
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
            $quoteid = $this->request->getVar('qt_id');
            $items = $this->request->getVar("items");
            $quote_code = $this->request->getVar('qt_code');
            $new_name = $this->request->getVar('cust_name');
            $new_chasis = $this->request->getVar('chasis_no');
            $new_contact = $this->request->getVar('contact');
            $new_odometer = $this->request->getVar('odometer');
            $new_make = $this->request->getVar('make');
            $new_jc_no = $this->request->getVar('jc_no');
            $new_reg_no = $this->request->getVar('reg_no');
            $new_sa_id = $this->request->getVar('qt_service_adv');


            $result =  $this->getQuoteById($quoteid);
            $olditems =  $result['qt_items'];
            $olditemsAsObjects = json_decode(json_encode($olditems));
            $temp = $result['quotation'];
            $old_cust_detail = json_decode(json_encode($temp));

            // return $this->respond($new_sa_id, 200);

            if ($new_sa_id != '0') {
                $usmodel = new UserModel();
                $name = $usmodel->select('us_firstname')->where('us_id', $new_sa_id)->get()->getRow()->us_firstname;
            } else {
                $name = '';
            }


            $new_cus_data = [
                'cust_name' => $new_name,
                'chasis_no' => $new_chasis,
                'contact' => $new_contact,
                'odometer' => $new_odometer,
                'make' => $new_make,
                'jc_no' => $new_jc_no,
                'reg_no' => $new_reg_no,
                'sa_id' => $new_sa_id,
                'serv_name' => $name,
            ];


            $logs = $this->compareItems($items, $olditemsAsObjects, $quote_code, $old_cust_detail, $new_cus_data);
            if ($logs) {
                $logdata = array();
                foreach ($logs as $log) {
                    $logdata[] = array(

                        'ql_qt_id' => $this->request->getVar('qt_id'),
                        'ql_notes' => $log['message'],
                        'ql_created_on' => date('Y-m-d H-i-s'),
                        'ql_created_by' => $tokendata['uid'],
                    );
                }

                $l_data = $logd_model->insertBatch($logdata);
            }


            $data = [
                'qt_cus_name' => ucwords($this->request->getVar('cust_name')),
                'qt_chasis' => strtoupper($this->request->getVar('chasis_no')),
                'qt_jc_no' => strtoupper($this->request->getVar('jc_no')),
                'qt_reg_no' => strtoupper($this->request->getVar('reg_no')),
                'qt_amount' => $this->request->getVar('quot_total'),
                'qt_vehicle_value' => $this->request->getVar('vehicle_value'),
                'qt_odometer' => $this->request->getVar('odometer'),
                'qt_tax' => $this->request->getVar('tax_amount'),
                'qt_total'  => $this->request->getVar('grand_total'),
                'qt_make' =>  strtoupper($this->request->getVar('make')),
                'qt_cus_contact' =>  $this->request->getVar('contact'),
                'part_type_print' => $this->request->getVar('part_type_print'),
                'avail_print' => $this->request->getVar('avail_print'),
                'part_code_print' => $this->request->getVar('part_code_print'),
                'qt_service_adv' => $this->request->getVar('qt_service_adv'),
            ];
            $res = $modelQ->update($this->request->getVar('qt_id'), $data);
            if ($res) {
                $modelQTType = new QuoteItemTypesModel();
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
                foreach ($items as $item) {
                    if ($item->item_id > 0) {
                        $mdatat = [

                            'item_id' => $item->item_id,
                            'item_type' => $item->item_type,
                            'item_code' => $item->item_type != "2" ? strtoupper($item->item_code) : "",
                            'item_name' => strtoupper($item->item_name),
                            'item_qty' => $item->item_qty,
                            'unit_price' => $item->unit_price,
                            'item_condition' => $item->item_type != "2" ? "" : $item->item_condition,
                            'item_priority' => $item->item_type == "1" ? "" : $item->item_priority,
                            'disc_amount' => 0,
                            'qt_id' =>  $this->request->getVar('qt_id'),
                            'item_seq' => $item->item_seq,
                            'item_group' => 0,
                            'item_created_by' => $tokendata['uid'],
                            'item_delete_flag' => $item->item_delete_flag,
                            'item_updated_by' => $tokendata['uid'],
                        ];
                        if ($item->item_type == "1") {
                            $insdData = array();
                            $updData = array();
                            foreach ($item->item_p_types as $part_type) {
                                if ($part_type->qit_id > 0) {
                                    $updData[] = array(
                                        'qit_id' => $part_type->qit_id,
                                        'qit_item_id' => $part_type->qit_item_id,
                                        'qit_qt_id' =>  $this->request->getVar('qt_id'),
                                        'qit_brand' => $part_type->qit_brand,
                                        'qit_type' => $part_type->qit_type,
                                        'qit_availability' => $part_type->qit_availability,
                                        'qit_unit_price' => $part_type->qit_unit_price,
                                        'qit_discount' => 0,
                                        'qit_created_by' => $tokendata['uid'],
                                        'qit_delete_flag' => $part_type->qit_delete_flag,
                                        'qit_old_margin_price' => $part_type->old_margin_total,
                                        'qit_margin_price' => $part_type->margin_total,
                                    );
                                } else {
                                    $insdData[] = array(
                                        'qit_item_id' => $item->item_id,
                                        'qit_qt_id' =>  $this->request->getVar('qt_id'),
                                        'qit_brand' => $part_type->qit_brand,
                                        'qit_type' => $part_type->qit_type,
                                        'qit_availability' => $part_type->qit_availability,
                                        'qit_unit_price' => $part_type->qit_unit_price,
                                        'qit_discount' => 0,
                                        'qit_created_by' => $tokendata['uid'],
                                        'qit_delete_flag' => $part_type->qit_delete_flag,
                                    );
                                }
                            }
                            if (sizeof($updData) > 0) {
                                $modelQTType->updateBatch($updData, 'qit_id');
                            }
                            if (sizeof($insdData) > 0) {
                                $modelQTType->insertBatch($insdData);
                            }
                        }
                        $ret = $modelQT->update($item->item_id, $mdatat);
                    } else {
                        $mdatat = array();
                        $mdatat = [
                            'item_type' => $item->item_type,
                            'item_code' => $item->item_type != "2" ? strtoupper($item->item_code) : "",
                            'item_name' => strtoupper($item->item_name),
                            'item_qty' => $item->item_qty,
                            'item_condition' => $item->item_type != "2" ? "" : $item->item_condition,
                            'item_priority' => $item->item_type == "1" ? "" : $item->item_priority,
                            'unit_price' => $item->unit_price,
                            'disc_amount' => 0,
                            'qt_id' =>  $this->request->getVar('qt_id'),
                            'item_seq' => $item->item_seq,
                            'item_group' => 0,
                            'item_created_by' => $tokendata['uid'],
                            'item_delete_flag' => $item->item_delete_flag,
                            'item_updated_by' => $tokendata['uid'],
                        ];
                        $ret = $modelQT->insert($mdatat);
                        if ($item->item_type == "1" &&  $ret > 0) {
                            $insdData = array();
                            foreach ($item->item_p_types as $part_type) {
                                $insdData[] = array(
                                    'qit_item_id' => $ret,
                                    'qit_qt_id' =>  $this->request->getVar('qt_id'),
                                    'qit_brand' => $part_type->qit_brand,
                                    'qit_type' => $part_type->qit_type,
                                    'qit_availability' => $part_type->qit_availability,
                                    'qit_unit_price' => $part_type->qit_unit_price,
                                    'qit_discount' => 0,
                                    'qit_created_by' => $tokendata['uid'],
                                    'qit_delete_flag' => $part_type->qit_delete_flag,
                                );
                            }
                            if (sizeof($insdData) > 0) {
                                $modelQTType->insertBatch($insdData);
                            }
                        }
                    }
                }
                $this->insertUserLog('Update Normal Quotation', $tokendata['uid']);

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
                $this->insertUserLog('Delete Normal Quotation', $tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 200);
            }
        }
    }
    public function getCustData()
    {
        $modelQT = new QuotesItemModel();
        $modelQ = new QuotesMasterModel();
        $modelL = new LeadModel();
        $modelC = new CustomerMasterModel();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

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
            $source = $this->request->getVar('source');
            if ($source == 'lead') {
                $res = $modelL->where('lead_id', $id)->select('name as cust_name,phone as cust_phone,cus_id')->first();
            } else {
                $res = $modelC->where('cus_id', $id)->select('cust_name,cust_phone')->first();
            }
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'cust' => $res,

                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'cust' => []
                ];
                return $this->fail($response, 200);
            }
        }
    }
    public function quoteByLead()
    {
        $modelQ = new QuotesMasterModel();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

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

            //  $res= $modelQ->where('qt_lead_id', $this->db->escapeString($this->request->getVar('id')))->where('qt_delete_flag',0)->select('qt_id,qt_code,qt_reg_no,qt_chasis,qt_jc_no,qt_type,qt_total')->findAll();

            $res = $modelQ->select('qt_id,qt_code,qt_reg_no,qt_chasis,qt_jc_no,qt_type,qt_total,lead_code,cust_alm_code')
                ->where('qt_delete_flag', 0)
                ->where('qt_lead_id', $this->db->escapeString($this->request->getVar('id')))
                ->join('leads', 'leads.lead_id=qt_lead_id', 'left')
                ->join('customer_master', 'customer_master.cus_id=qt_cus_id', 'left')
                ->orderBy('qt_id', 'desc')
                ->findAll();

            if ($res) {
                $this->insertUserLog('View Lead Quotation list', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'quote' => $res,

                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'quote' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function quoteByCus()
    {
        $modelQ = new QuotesMasterModel();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

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

            //  $res= $modelQ->where('qt_lead_id', $this->db->escapeString($this->request->getVar('id')))->where('qt_delete_flag',0)->select('qt_id,qt_code,qt_reg_no,qt_chasis,qt_jc_no,qt_type,qt_total')->findAll();

            $res = $modelQ->select('qt_id,qt_code,qt_reg_no,qt_chasis,qt_jc_no,qt_type,qt_total,lead_code,cust_alm_code')
                ->where('qt_delete_flag', 0)
                ->where('qt_cus_id', $this->db->escapeString($this->request->getVar('id')))
                ->join('leads', 'leads.lead_id=qt_lead_id', 'left')
                ->join('customer_master', 'customer_master.cus_id=qt_cus_id', 'left')
                ->orderBy('qt_id', 'desc')
                ->findAll();

            if ($res) {
                $this->insertUserLog('View Customer Quotation list', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'quote' => $res,

                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'quote' => []
                ];
                return $this->respond($response, 200);
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

    public function createQuoteVersion()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            $sa = 0;
            $pa = 0;
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
                'qt_id' => 'required',
                'qt_version_count' => 'required',
                'qt_items' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $qt_v_master = new QuoteVersionMasterModel();
            $qt_v_items = new QuoteVersionItemsModel();
            if ($this->request->getVar("qt_id") > 0) {
                $qt_v_count = $qt_v_master->where('qvm_delete_flag', 0)->where('qvm_qt_id', $this->request->getVar("qt_id"))->countAllResults();
                $ref_version = $this->request->getVar("qvm_ref_qt_version");
                $in_data = [
                    'qvm_qt_id' => $this->request->getVar("qt_id"),
                    'qvm_version_no' => $qt_v_count + 1,
                    'qvm_sub_total' => $this->request->getVar("qvm_sub_total"),
                    'qvm_spare_total' => $this->request->getVar("qvm_spare_total"),
                    'qvm_labour_total' => $this->request->getVar("qvm_labour_total"),
                    'qvm_vat_total' => $this->request->getVar("qvm_vat_total"),
                    'qvm_discount' => $this->request->getVar("qvm_discount"),
                    'qvm_created_by' => $tokendata['uid'],
                    'qvm_updated_by' => $tokendata['uid'],
                    'qvm_note' => $this->request->getVar("qvm_note"),
                    'qvm_quote_label' => $this->request->getVar("qvm_quote_label"),
                    'qvm_reference_version' => isset($ref_version) ? $ref_version : 0,
                ];
                $this->db->transStart();
                $qt_v_id = $qt_v_master->insert($in_data);
                if ($qt_v_id > 0) {
                    $version_items = array();
                    foreach ($this->request->getVar("qt_items") as $item) {
                        foreach ($item as $line_item) {
                            $in_data_item = [
                                'qtvi_qtv_id' => $qt_v_id,
                                'qtvi_qtv_item_id' => $line_item->item_id,
                                'qtvi_qtv_item_price_type' => $line_item->qit_id != null ? $line_item->qit_id : 0,
                                'qtvi_qtv_item_qty' => $line_item->item_qty,
                                'qtvi_qtv_item_price' => $line_item->qtvi_qtv_item_price,
                                'qtvi_item_group' => $line_item->item_group,
                                'qtvi_created_by' => $tokendata['uid'],
                                'qtvi_updated_by' => $tokendata['uid'],
                            ];
                            array_push($version_items, $in_data_item);
                        }
                    }
                    $qt_v_items->insertBatch($version_items);
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
            } else {
                $data['ret_data'] = "fail";
                return $this->fail($data, 200);
            }
        }
    }

    public function getQuoteVersions()
    {
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
            $rules = [
                'qt_id' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $qt_v_master = new QuoteVersionMasterModel();
            $res = $qt_v_master->where('qvm_delete_flag', 0)
                ->where('qvm_qt_id', $this->request->getVar("qt_id"))
                ->join('quotes_master', 'quotes_master.qt_id = quotes_version_master.qvm_qt_id')
                ->select('quotes_version_master.*,quotes_master.qt_vehicle_value')
                ->findAll();
            if ($res > 0) {
                $data["qt_versions"] = $res;
            } else {
                $data["qt_versions"] = [];
            }
            $data['ret_data'] = "success";
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "fail";
            return $this->respond($data, 200);
        }
    }

    public function getQuoteVersionDetails()
    {
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
            $rules = [
                'qt_v_id' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $qt_v_master = new QuoteVersionMasterModel();
            $res = $qt_v_master->where('qvm_delete_flag', 0)->where('qvm_id', $this->request->getVar("qt_v_id"))->first();
            if ($res > 0) {
                $qt_v_items = new QuoteVersionItemsModel();
                $data["qt_versions_items"] =
                    $qt_v_items->where('qtvi_delete_flag', 0)
                    ->where('qtvi_qtv_id', $this->request->getVar("qt_v_id"))
                    ->join('quotes_items qi', 'qi.item_id=qtvi_qtv_item_id', 'left')
                    ->join('quote_item_types qit', 'qit.qit_id=qtvi_qtv_item_price_type', 'left')
                    ->join('brand_list as bl', 'bl.brand_id=qit.qit_brand', 'left')
                    ->findAll();
                $data["qt_versions"] = $res;
            } else {
                $data["qt_versions"] = [];
            }
            $data['ret_data'] = "success";
            return $this->respond($data, 200);
        } else {
            $data['ret_data'] = "fail";
            return $this->respond($data, 200);
        }
    }

    public function updateQuoteVersion()
    {
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            $sa = 0;
            $pa = 0;
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
                'qt_id' => 'required',
            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $qt_v_master = new QuoteVersionMasterModel();
            $qt_v_items_model = new QuoteVersionItemsModel();
            if ($this->request->getVar("qt_id") > 0) {
                // Define the data you want to update
                $update_data = [
                    'qvm_spare_total' => $this->request->getVar("qvm_spare_total"),
                    'qvm_labour_total' => $this->request->getVar("qvm_labour_total"),
                    'qvm_sub_total' => $this->request->getVar("qvm_sub_total"),
                    'qvm_vat_total' => $this->request->getVar("qvm_vat_total"),
                    'qvm_discount' => $this->request->getVar("qvm_discount"),
                    'qvm_updated_by' => $tokendata['uid'],
                    'qvm_note' => $this->request->getVar("qvm_note"),
                    'qvm_quote_label' => $this->request->getVar("qvm_quote_label"),
                ];

                $res = $qt_v_master->update($this->request->getVar('qt_id'), $update_data);
                $qt_items = $this->request->getVar('qt_items');
                $masterUpdate = [];
                foreach ($this->request->getVar('qt_items') as $qts) {
                    foreach ($qts as $qt) {
                        $itemsData = [];
                        $itemsData = [
                            'qtvi_qtv_item_id' => $qt->qtvi_qtv_item_id,
                            'qtvi_qtv_item_price' => $qt->qtvi_qtv_item_price

                        ];
                        array_push($masterUpdate, $itemsData);
                    }
                }
                $upres = $qt_v_items_model->updateBatch($masterUpdate, 'qtvi_qtv_item_id');
                if ($res) {
                    // Rows were updated successfully
                    $data['ret_data'] = "success";
                    return $this->respond($data, 200);
                } else {
                    // No rows were updated (or the query failed)
                    $data['ret_data'] = "fail";
                    return $this->fail($data, 200);
                }
            } else {
                $data['ret_data'] = "fail";
                return $this->fail($data, 200);
            }
        }
    }

    public function commonQuoteDetails()
    {
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
            $modelQT = new QuotesItemModel();
            $modelLabourCondition = new QuotesLabourCondition();
            $modelLabourpriority = new QuotesLabourPriority();

            $data['spare_items'] = $modelQT->select('item_name')
                ->where('item_delete_flag', 0)
                ->where('item_type', 1)
                ->distinct()
                ->limit(50)
                ->find();
            $data['spare_code'] = $modelQT->select('DISTINCT(item_code) as item_name')->where('item_delete_flag', 0)->where('item_type', 1)->where('item_code!=""')->limit(50)
                ->find();
            $data['service_items'] = $modelQT->select('DISTINCT(item_name)')->where('item_delete_flag', 0)->where('item_type', 2)->limit(50)
                ->find();
            $data['generic_items'] = $modelQT->select('DISTINCT(item_code) as item_name')->where('item_delete_flag', 0)->where('item_type', 3)->limit(50)
                ->find();
            $data['labour_condition'] = $modelLabourCondition->select('*')->where('qlc_delete_flag', 0)->limit(50)
                ->find();
            $data['labour_priority'] = $modelLabourpriority->select('*')->where('qlp_delete_flag', 0)->limit(50)
                ->find();
            $data['ret_data'] = "success";
            return $this->respond($data, 200);
        }
    }

    public function fetchAllQuote()
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
            } else if ($user['us_role_id'] == 16) {
                $pa = $user['us_id'];
            }
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
                $res = $modelQ->select('qt_id,qt_code,qt_cus_name,qt_cus_contact,qt_vin,qt_reg_no,qt_created_on,qt_chasis,qt_make,qt_odometer,qt_service_adv,qt_parts_adv,qt_jc_no,qt_cus_id,qt_lead_id,qt_type,qt_amount,qt_tax,qt_total,sau.us_firstname as sa_name,sau.us_email as sa_email,pau.us_firstname as pa_name,pau.us_email as pa_email')
                    ->where('qt_delete_flag', 0)
                    ->where('qt_type', 1)
                    ->where('DATE_FORMAT(qt_created_on, "%Y-%m-%d") >=',  $start_date)
                    ->where('DATE_FORMAT(qt_created_on, "%Y-%m-%d") <=',  $end_date)
                    ->where('qt_service_adv',  $sa_id)
                    ->join('users sau', 'sau.us_id=qt_service_adv', 'left')
                    ->join('users pau', 'pau.us_id=qt_parts_adv', 'left')
                    ->orderBy('qt_id', 'desc')
                    ->findAll();
            } else {
                $res = $modelQ->select('qt_id,qt_code,qt_cus_name,qt_cus_contact,qt_vin,qt_reg_no,qt_created_on,qt_chasis,qt_make,qt_odometer,qt_service_adv,qt_parts_adv,qt_jc_no,qt_cus_id,qt_lead_id,qt_type,qt_amount,qt_tax,qt_total,sau.us_firstname as sa_name,sau.us_email as sa_email,pau.us_firstname as pa_name,pau.us_email as pa_email')
                    ->where('qt_delete_flag', 0)
                    ->where('qt_type', 1)
                    ->where('DATE_FORMAT(qt_created_on, "%Y-%m-%d") >=',  $start_date)
                    ->where('DATE_FORMAT(qt_created_on, "%Y-%m-%d") <=',  $end_date)
                    ->join('users sau', 'sau.us_id=qt_service_adv', 'left')
                    ->join('users pau', 'pau.us_id=qt_parts_adv', 'left')
                    ->orderBy('qt_id', 'desc')
                    ->findAll();
            }



            if ($res) {
                $this->insertUserLog('View Normal Quotation List', $tokendata['uid']);
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

    public function setQuotesTermsFlag()
    {
        // $modelQ = new QuotesMasterModel();
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
            if ($user['us_role_id'] == 2) {
                $sa = $user['us_id'];
            } else if ($user['us_role_id'] == 16) {
                $pa = $user['us_id'];
            }
            if (!$user) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {
            $qt_v_master = new QuoteVersionMasterModel();


            $flag =  $this->request->getVar("flag");
            $qvm_id  =  $this->request->getVar("qvm_id ");
            $update_data = [
                'qvm_terms_flag' => $this->request->getVar("flag"),
            ];

            $res = $qt_v_master->where('qvm_id', $qvm_id)->set($update_data)->update();

            if ($res) {
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

    public function compareItems($newItems, $oldItems, $quote_code, $old_cust_detail, $new_cus_data)
    {
        $logs = [];
        foreach ($newItems as $newItem) {
            $oldItem = null;
            foreach ($oldItems as $item) {
                if ($item->item_id == $newItem->item_id) {
                    $oldItem = $item;
                    break;
                }
            }
            // new item adding and changes log entry
            if (!$oldItem) {
                if ($newItem->item_type == 3) {
                    $logs[] = ['message' => "{$newItem->item_code} added for {$quote_code}"];
                } else if ($newItem->item_type == 2) {
                    $logs[] = ['message' => "{$newItem->item_qty} {$newItem->item_name} with a price of {$newItem->unit_price} added for {$quote_code}"];
                } else {
                    $logs[] = ['message' => "{$newItem->item_qty} {$newItem->item_name} with a price of {$newItem->item_p_types[0]->qit_unit_price} added for {$quote_code}"];
                }
            } else {
                if ($newItem->item_name != $oldItem->item_name && $newItem->item_type == 3) {
                    $logs[] = ['message' => "Generic item description changed from {$oldItem->item_name} to {$newItem->item_name} in {$quote_code}"];
                } else if ($newItem->item_name != $oldItem->item_name) {
                    $logs[] = ['message' => "Spare changed from {$oldItem->item_name} to {$newItem->item_name} for {$quote_code}"];
                }

                if ($newItem->item_qty != $oldItem->item_qty) {
                    if ($newItem->item_type == 3) {
                        $logs[] = ['message' => "Generic item quantity changed from {$oldItem->item_qty} to {$newItem->item_qty} for {$newItem->item_code} in  {$quote_code}"];
                    } else {
                        $logs[] = ['message' => "Item quantity changed from {$oldItem->item_qty} to {$newItem->item_qty} for {$newItem->item_name} in  {$quote_code}"];
                    }
                }

                if ($newItem->unit_price != $oldItem->unit_price) {
                    if ($newItem->item_type == 3) {
                        $logs[] = ['message' => "Generic item price changed from {$oldItem->unit_price} to {$newItem->unit_price} for {$newItem->item_code} in  {$quote_code}"];
                    } else if ($newItem->item_type == 2) {
                        $logs[] = ['message' => "Labour price changed from {$oldItem->unit_price} to {$newItem->unit_price} for {$newItem->item_code} in  {$quote_code}"];
                    } else {
                        $logs[] = ['message' => "Item unit price changed from {$oldItem->unit_price} to {$newItem->unit_price} for {$newItem->item_name} in  {$quote_code}"];
                    }
                }

                if ($newItem->item_code != $oldItem->item_code && $newItem->item_type != 3) {
                    if ($oldItem->item_code == NULL || $oldItem->item_code == "") {
                        $logs[] = ['message' => "Part code ({$newItem->item_code}) added for {$newItem->item_name} in  {$quote_code}"];
                    } else {
                        $logs[] = ['message' => "Part code changed from {$oldItem->item_code} to {$newItem->item_code} for {$newItem->item_name} in  {$quote_code}"];
                    }
                }
                if ($newItem->item_code != $oldItem->item_code && $newItem->item_type == 3) {
                    if ($oldItem->item_code == NULL || $oldItem->item_code == "") {
                        $logs[] = ['message' => "{$newItem->item_code} added for {$newItem->item_name} in  {$quote_code}"];
                    } else {
                        $logs[] = ['message' => "Generic item  changed from {$oldItem->item_code} to {$newItem->item_code} in  {$quote_code}"];
                    }
                }

                if ($newItem->item_note !== $oldItem->item_note) {
                    if ($oldItem->item_note == NULL || $oldItem->item_note == "") {
                        $logs[] = ['message' => "Item note ({$newItem->item_note}) added  for {$newItem->item_name} in  {$quote_code}"];
                    } else {
                        $logs[] = ['message' => "Item note changed from {$oldItem->item_note} to {$newItem->item_note} for {$newItem->item_name} in  {$quote_code}"];
                    }
                }



                if ($newItem->item_type == 2) {
                    $oldPriceType = [
                        'qit_unit_price' => $oldItem->qit_unit_price,
                        'item_priority' => $oldItem->item_priority,
                        'item_id' => $oldItem->item_id,
                        'item_condition' => $oldItem->item_condition,
                    ];

                    if ($newItem->item_priority !== $oldPriceType['item_priority'] && $newItem->item_id == $oldPriceType['item_id']) {
                        $logs[] = ['message' => "Labour priority changed from {$oldPriceType['item_priority']} to {$newItem->item_priority} for {$newItem->item_name} in {$quote_code}"];
                    }
                    if ($newItem->qit_unit_price !== $oldPriceType['qit_unit_price'] && $newItem->item_id == $oldPriceType['item_id']) {
                        $logs[] = ['message' => "Labour Price changed from {$oldPriceType['qit_unit_price']} to {$newItem->qit_unit_price} for {$newItem->item_name} in {$quote_code}"];
                    }
                    if ($newItem->item_condition !== $oldPriceType['item_condition'] && $newItem->item_id == $oldPriceType['item_id']) {
                        $logs[] = ['message' => "Labour condition changed from {$oldPriceType['item_condition']} to {$newItem->item_condition} for {$newItem->item_name} in {$quote_code}"];
                    }
                    if ($newItem->item_id == '0') {
                        $logs[] = ['message' => "Labour added for {$newItem->item_name} in {$quote_code}"];
                    }
                }
                if ($newItem->item_type == 3) {
                    $oldPriceType = [
                        'qit_unit_price' => $oldItem->qit_unit_price,
                        'item_priority' => $oldItem->item_priority,
                        'item_id' => $oldItem->item_id,
                    ];

                    if ($newItem->item_priority !== $oldPriceType['item_priority'] && $newItem->item_id == $oldPriceType['item_id']) {
                        $logs[] = ['message' => "Generic item priority changed from {$oldPriceType['item_priority']} to {$newItem->item_priority} in {$quote_code}"];
                    }

                    if ($newItem->qit_unit_price !== $oldPriceType['qit_unit_price'] && $newItem->item_id == $oldPriceType['item_id']) {
                        $logs[] = ['message' => "Generic Item Price changed from {$oldPriceType['qit_unit_price']} to {$newItem->qit_unit_price} in {$quote_code}"];
                    }


                    if ($newItem->item_id == '0') {
                        $logs[] = ['message' => "Generic item added for {$newItem->item_name} in {$quote_code}"];
                    }
                }
            }
        }
        //items check for delete log entry
        foreach ($newItems as $newItem) {
            if ($newItem->item_delete_flag == 1) {
                if ($newItem->item_type == 2) {
                    $logs[] = ['message' => "Labour ({$newItem->item_name}) is deleted for {$quote_code}"];
                } else if ($newItem->item_type == 3) {
                    $logs[] = ['message' => "Generic item ({$newItem->item_code}) is deleted for {$quote_code}"];
                } else {
                    $logs[] = ['message' => "Part {$newItem->item_name} is deleted for {$quote_code}"];
                }
            }
            //item_p_types is only present in item_type 1 log entry
            if ($newItem->item_type == 1) {
                foreach ($newItem->item_p_types as $brnd) {
                    if ($brnd->qit_delete_flag == 1) {
                        $logs[] = ['message' => "{$newItem->item_name} Brand ({$brnd->qit_brand_name}) is deleted for {$quote_code}"];
                    }
                }
            }
        }

        //Parts Brand changes checking log entry
        foreach ($newItems as $newitem) {
            if (isset($newitem->item_p_types) && is_array($newitem->item_p_types) && count($newitem->item_p_types) > 0) {
                foreach ($newitem->item_p_types as $newPriceType) {
                    foreach ($oldItems as $old) {
                        $oldPriceType = [];
                        //   $logs[]= ['message' => "Brand name changed 145789654230 {$old}"];
                        if ($old->item_type == 1) {
                            $oldPriceType = [
                                'qit_unit_price' => $old->qit_unit_price,
                                'qit_availability' => $old->qit_availability,
                                'qit_brand_name' => $old->brand_name,
                                'qit_type' => $old->qit_type,
                                'qit_id' => $old->qit_id,
                                'item_qty' => $old->item_qty,
                                'brand_id' => $old->brand_id,
                            ];
                            if ($newPriceType->qit_brand !== $oldPriceType['brand_id'] && $newPriceType->qit_id == $oldPriceType['qit_id']) {

                                $builder = $this->db->table('brand_list');
                                $builder->select('brand_name');
                                $builder->where("brand_id", $newPriceType->qit_brand);
                                $newBrandQuery = $builder->get();
                                $n_b_name = $newBrandQuery->getRow()->brand_name ?? 'Unknown';

                                $builder = $this->db->table('brand_list');
                                $builder->select('brand_name');
                                $builder->where("brand_id", $oldPriceType['brand_id']);
                                $newBrandQuery = $builder->get();
                                $o_b_name = $newBrandQuery->getRow()->brand_name ?? 'Unknown';
                                $logs[] = ['message' => "Brand name changed from {$o_b_name} to {$n_b_name} for {$newitem->item_name} in {$quote_code}"];
                            }

                            // if ($newPriceType->qit_id != 0 && $newPriceType->qit_brand_name != $oldPriceType['qit_brand_name'] && $newPriceType->qit_id == $oldPriceType['qit_id']) {
                            //     $logs[] = ['message' => "Brand name changed from {$oldPriceType['qit_brand_name']} to {$newPriceType->qit_brand_name}"];
                            // }
                            if ($newPriceType->qit_type !== $oldPriceType['qit_type'] && $newPriceType->qit_id == $oldPriceType['qit_id']) {
                                $logs[] = ['message' => "{$newPriceType->qit_brand_name} type changed from {$oldPriceType['qit_type']} to {$newPriceType->qit_type} for {$newitem->item_name} in {$quote_code}"];
                            }

                            if ($newPriceType->qit_availability !== $oldPriceType['qit_availability'] && $newPriceType->qit_id == $oldPriceType['qit_id']) {
                                $logs[] = ['message' => "Availability changed from {$oldPriceType['qit_availability']} to {$newPriceType->qit_availability} for {$newitem->item_name} in {$quote_code}"];
                            }

                            if ($newPriceType->qit_unit_price != $oldPriceType['qit_unit_price'] && $newPriceType->qit_id == $oldPriceType['qit_id']) {
                                $logs[] = ['message' => "Price changed from {$oldPriceType['qit_unit_price']} to {$newPriceType->qit_unit_price} for {$newitem->item_name} in {$quote_code}"];
                            }
                            //duplicate values....
                            // if ($newPriceType->qit_unit_price !== $oldPriceType['qit_unit_price'] && $newPriceType->qit_id == $oldPriceType['qit_id']) {
                            //     $logs[]= ['message' => "Price changed from {$oldPriceType['qit_unit_price']} to {$newPriceType->qit_unit_price}"];
                            // }

                            if ($newPriceType->qit_id == '0' && $newitem->item_id != '0') {
                                $builder = $this->db->table('brand_list');
                                $builder->select('brand_name');
                                $builder->where("brand_id", $newPriceType->qit_brand);
                                $newBrandQuery = $builder->get();
                                $n_b_name = $newBrandQuery->getRow()->brand_name ?? 'Unknown';
                                $logs[] = ['message' => "Brand ({$n_b_name}) added for {$newitem->item_name} in {$quote_code}"];
                                break;
                            }
                        }
                    }
                }
            }
        }

        //customer fields checking.. log entry
        $aa = $new_cus_data['serv_name'];
        if ($aa != $old_cust_detail->sa_name) {
            $logs[] = ['message' => "Service Advisor {$aa} created  for {$quote_code}"];
        }

        if (($new_cus_data['cust_name'] == "" || $new_cus_data['cust_name'] == null) && $old_cust_detail->qt_cus_name != null) {
            $logs[] = ['message' => "Customer name removed for {$quote_code}"];
        } else if ($new_cus_data['cust_name'] !== $old_cust_detail->qt_cus_name && $old_cust_detail->qt_cus_name != null &&  $old_cust_detail->qt_cus_name != '') {
            $logs[] = ['message' => "Customer name changed from {$old_cust_detail->qt_cus_name} to  {$new_cus_data['cust_name']} for {$quote_code}"];
        } else if (($old_cust_detail->qt_cus_name == null ||  $old_cust_detail->qt_cus_name == '') && ($new_cus_data['cust_name'] != null && $new_cus_data['cust_name'] != '')) {
            $logs[] = ['message' => "Customer name ({$new_cus_data['cust_name']}) added for {$quote_code}"];
        }

        if (($new_cus_data['make'] == "" || $new_cus_data['make'] == null) && $old_cust_detail->qt_make != null) {
            $logs[] = ['message' => "Make/Model removed for {$quote_code}"];
        } else if ($new_cus_data['make'] !== $old_cust_detail->qt_make && $old_cust_detail->qt_make != null && $old_cust_detail->qt_make != '') {
            $logs[] = ['message' => "Make/Model changed from {$old_cust_detail->qt_make} to  {$new_cus_data['make']} for {$quote_code}"];
        } else if (($old_cust_detail->qt_make == null || $old_cust_detail->qt_make == '') && $new_cus_data['make'] != "" && $new_cus_data['make'] != null) {
            $logs[] = ['message' => "Make/Model ({$new_cus_data['make']}) added for {$quote_code}"];
        }

        if (($new_cus_data['jc_no'] == "" || $new_cus_data['jc_no'] == null) && $old_cust_detail->qt_jc_no != null) {
            $logs[] = ['message' => "Jobcard number Removed for {$quote_code}"];
        } else if ($new_cus_data['jc_no'] !== $old_cust_detail->qt_jc_no && $old_cust_detail->qt_jc_no != null && $old_cust_detail->qt_jc_no != '') {
            $logs[] = ['message' => "Job Card Number changed from {$old_cust_detail->qt_jc_no} to  {$new_cus_data['jc_no']} for {$quote_code}"];
        } else if (($old_cust_detail->qt_jc_no == null || $old_cust_detail->qt_jc_no == '') && $new_cus_data['jc_no'] != "" && $new_cus_data['jc_no'] != null) {
            $logs[] = ['message' => "Job Card Number ({$new_cus_data['jc_no']}) added for {$quote_code}"];
        }

        if (($new_cus_data['odometer'] == "" || $new_cus_data['odometer'] == null) && $old_cust_detail->qt_odometer != null) {
            $logs[] = ['message' => "Odometer value Removed for {$quote_code}"];
        } else if ($new_cus_data['odometer'] !== $old_cust_detail->qt_odometer && $old_cust_detail->qt_odometer != null && $old_cust_detail->qt_odometer != '') {
            $logs[] = ['message' => "Odometer value changed from {$old_cust_detail->qt_odometer} to  {$new_cus_data['odometer']} for {$quote_code}"];
        } else if (($old_cust_detail->qt_odometer == null || $old_cust_detail->qt_odometer == '')  && $new_cus_data['odometer'] != "" && $new_cus_data['odometer'] != null) {
            $logs[] = ['message' => "Odometer ({$new_cus_data['odometer']}) added for {$quote_code}"];
        }
        if (($new_cus_data['contact'] == "" || $new_cus_data['contact'] == null) && $old_cust_detail->qt_cus_contact != null) {
            $logs[] = ['message' => "Contact number Removed for {$quote_code}"];
        } else if ($new_cus_data['contact'] !== $old_cust_detail->qt_cus_contact && $old_cust_detail->qt_cus_contact != null && $old_cust_detail->qt_cus_contact != '') {
            $logs[] = ['message' => "Contact Number changed from {$old_cust_detail->qt_cus_contact} to  {$new_cus_data['contact']} for {$quote_code}"];
        } else if (($old_cust_detail->qt_cus_contact == null || $old_cust_detail->qt_cus_contact == '') && $new_cus_data['contact'] != "" && $new_cus_data['contact'] != null) {
            $logs[] = ['message' => "Contact Number ({$new_cus_data['contact']}) added for {$quote_code}"];
        }

        if ($new_cus_data['chasis_no'] !== $old_cust_detail->qt_chasis) {
            $logs[] = ['message' => "Chasis No changed from {$old_cust_detail->qt_chasis} to  {$new_cus_data['chasis_no']} for {$quote_code}"];
        }

        if (($new_cus_data['reg_no'] == "" || $new_cus_data['reg_no'] == null) && $old_cust_detail->qt_reg_no != null) {
            $logs[] = ['message' => "Register Number Removed for {$quote_code}"];
        } else if ($new_cus_data['reg_no'] !== $old_cust_detail->qt_reg_no && $old_cust_detail->qt_reg_no != null && $old_cust_detail->qt_reg_no != '') {
            $logs[] = ['message' => "Register Number changed from {$old_cust_detail->qt_reg_no} to  {$new_cus_data['reg_no']} for {$quote_code}"];
        } else if (($old_cust_detail->qt_reg_no == null || $old_cust_detail->qt_reg_no == '') && $new_cus_data['reg_no'] != "" && $new_cus_data['reg_no'] != null) {
            $logs[] = ['message' => "Register Number  ({$new_cus_data['reg_no']}) added for {$quote_code}"];
        }

        return $logs;
    }

    public function getQuoteById($quoteid)
    {
        $modelQT = new QuotesItemModel();
        $modelQ = new QuotesMasterModel();

        $res = $modelQ->select('qt_id,qt_code,qt_type,qt_cus_name,qt_cus_contact,qt_vin,qt_reg_no,qt_chasis,qt_make,qt_odometer,qt_service_adv,qt_parts_adv,qt_jc_no,qt_cus_id,qt_lead_id,qt_type,qt_amount,qt_tax,qt_total,part_code_print,avail_print,part_type_print,brand_print,qt_camp_id,sau.us_firstname as sa_name,sau.us_email as sa_email,pau.us_firstname as pa_name,pau.us_email as pa_email,qt_vehicle_value')
            ->where('qt_id', $quoteid)
            ->where('qt_delete_flag', 0)
            ->join('users sau', 'sau.us_id=qt_service_adv', 'left')
            ->join('users pau', 'pau.us_id=qt_parts_adv', 'left')
            ->join('campaign', 'campaign.camp_id=qt_camp_id', 'left')
            ->orderBy('qt_id', 'desc')
            ->first();
        if ($res) {
            $qt_items = $modelQT->where('qt_id', $quoteid)
                ->where('item_delete_flag', 0)
                // ->where('its.qit_delete_flag', 0)
                ->join('quote_item_types its', 'its.qit_item_id=item_id', 'left')
                ->join('brand_list as bl', 'bl.brand_id=its.qit_brand', 'left')
                ->findAll();
            // $this->insertUserLog('View Normal Quotation Data for Update', $tokendata['uid']);
            $response = [
                'ret_data' => 'success',
                'quotation' => $res,
                'qt_items' => $qt_items
            ];
            return $response;
        }
    }

    public function getQuoteLogs()
    {

        $quoteLogModel = new QuotesLog();
        $quoteId = $this->request->getVar('quoteId');


        $logs = $quoteLogModel->where('ql_delete_flag', 0)
            ->where('ql_qt_id', $quoteId)
            ->join('users', 'users.us_id=ql_created_by', 'left')
            ->select('ql_id,ql_qt_id,ql_notes,ql_created_on,ql_created_by,ql_delete_flag,us_firstname')
            ->findAll();


        if ($logs) {
            $response = [
                'ret_data' => 'success',
                'quotes_log' => $logs
            ];
            return $this->respond($response, 200);
        } else {
            $response = [
                'ret_data' => 'success',
                'quotes_log' => []
            ];
            return $this->respond($response, 200);
        }
    }
}
