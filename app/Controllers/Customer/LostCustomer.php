<?php

namespace App\Controllers\Customer;

use CodeIgniter\API\ResponseTrait;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;
use App\Models\Customer\LostCustomerModel;
use App\Models\Customer\UploadFileListModel;
use CodeIgniter\RESTful\ResourceController;
use App\Models\User\UserLogTableModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Customer\MaraghiJobcardModel;
use App\Models\Leads\AppointmentMasterModel;
use App\Models\Leads\AppointmentModel;
use App\Models\Leads\AppointmentLogModel;
use App\Models\Leads\LeadModel;
use App\Models\Customer\CustomerMasterModel;



class LostCustomer extends ResourceController
{
    use ResponseTrait;
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function uploadExcel()
    {
        helper(['form', 'url']);
        $model = new LostCustomerModel();
        $upmodel = new UploadFileListModel();
        $UserModel = new UserModel();
        $validModel = new Validation();
        $commonutils = new Common();
        $heddata = $this->request->headers();
        $tokendata = $commonutils->decode_jwt_token($validModel->getbearertoken($heddata['Authorization']));
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
            $in_data = array();
            $file = $this->request->getFile('attachment');
            $file_name =  $this->request->getVar('file_name');
            $file_type = $this->request->getVar('file_type');
            $checkrecord = $upmodel->where('uf_filename', $file_name)->first();
            if ($checkrecord) {
                $data['ret_data'] = "fail";
                $data['count'] = 0;
                return $this->respond($data, 200);
            } else {

                //   //  $csv = new csvimport;
                $newName = mt_rand(1000, 9999) . "-" . $file->getName();
                // $newName = $file->getRandomName();
                $file->move('../public/uploads/LcExcel', $newName);

                $file = fopen("../public/uploads/LcExcel/" . $newName, "r");
                $i = 0;
                $numberOfFields = 1;
                $csvArr = array();

                while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {

                    $num = count($filedata);

                    if ($num == $numberOfFields && !empty($filedata[0])) {
                        $csv = explode('|', $filedata[0]);
                        $csvArr = array();

                        if (count($csv) == 16 && $csv[0] != 'CUSTOMER CODE') {

                            // $csvArr[$i]['customer_code'] = $csv[0];
                            // $csvArr[$i]['customer_name'] = $csv[1];
                            // $csvArr[$i]['phone'] = $csv[2];
                            // $csvArr[$i]['sms_mobile'] = $csv[3];
                            // $csvArr[$i]['sms_option'] = $csv[4];
                            // $csvArr[$i]['email'] = $csv[5];
                            // $csvArr[$i]['reg_no'] = $csv[6];
                            // $csvArr[$i]['chasis'] = $csv[7];
                            // $csvArr[$i]['brand'] = $csv[8];
                            // $csvArr[$i]['model_code'] = $csv[9];
                            // $csvArr[$i]['model_name'] = $csv[10];
                            // $csvArr[$i]['model_year'] = $csv[11];
                            // $csvArr[$i]['miles_done'] = $csv[12];
                            // $csvArr[$i]['visits'] = $csv[13];
                            // $csvArr[$i]['invoice_date'] = $csv[14];

                            $csvArr['customer_code'] = $csv[0];
                            $csvArr['customer_name'] = $csv[1];
                            $csvArr['phone'] = $csv[2];
                            $csvArr['sms_mobile'] = $csv[3];
                            $csvArr['sms_option'] = $csv[4];
                            $csvArr['email'] = $csv[5];
                            $csvArr['reg_no'] = $csv[6];
                            $csvArr['chasis'] = $csv[7];
                            $csvArr['brand'] = $csv[8];
                            $csvArr['model_code'] = $csv[9];
                            $csvArr['model_name'] = $csv[10];
                            $csvArr['model_year'] = $csv[11];
                            $csvArr['miles_done'] = $csv[12];
                            $csvArr['visits'] = $csv[13];
                            $csvArr['invoice_date'] = $csv[14];

                            array_push($in_data, $csvArr);
                        }
                        $i++;
                    }
                }
                fclose($file);
                // $ret= $model->insertBatch($in_data);
                $insdata = [
                    'uf_file_name' =>  $newName,
                    'uf_file_note' => 'Lost Customer List',
                    'uf_created_by' => $tokendata['uid'],
                    'uf_filename'  => $file_name,
                    'uf_rec_count' => count($in_data),
                    'uf_filetype' => $file_type
                ];
                $file_id = $upmodel->insert($insdata);
                $count = 0;

                foreach ($in_data as $userdata) {

                    // Check record
                    //    $checkrecord = $model->where('customer_code',$userdata['customer_code'])->where('invoice_date',$userdata['invoice_date'])->countAllResults();

                    $checkrecord = 0;

                    if ($checkrecord == 0) {
                        $userdata['lcst_file_id'] = $file_id;
                        $userdata['lcst_file_type'] = $file_type;
                        ## Insert Record
                        if ($model->insert($userdata)) {
                            $count++;
                        }
                    }
                }
                $this->insertUserLog('Upload Lost Customer File ' . $newName, $tokendata['uid']);
                $data['ret_data'] = "success";
                $data['count'] = $count;

                return $this->respond($data, 200);
            }

            // print_r($csvArr);
            // print_r($_FILES);
            // echo($file->getClientMimeType());

            //$file_data = $this->csvimport->get_array($_FILES["attachment"]["tmp_name"]);  
            // print_r($file_data);    
            // $profile_image = $imageFile->getName();
            // $imageFile->move(ROOTPATH . 'public/uploads/CustomerDocument');
            // $data = [
            //     'img_name' => $imageFile->getName(),
            //     'file'  => $imageFile->getClientMimeType(),
            //     'path' => ROOTPATH,
            //     'docpath' => 'uploads\\CustomerDocument\\',
            // ];
            // $data['ret_data'] = "success";
            // return $this->respond($data, 200);
        }
    }
    public function assignLostCustomer()
    {
        $model = new LostCustomerModel();
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


            // $sdate = $this->request->getVar("SDate");
            // $edate = $this->request->getVar("EDate");
            $cust_id = $this->db->escapeString($this->request->getVar("cust_id"));
            $user = $this->request->getVar("assignUser");
            $filter = $this->request->getVar("filter_by");




            $builder = $this->db->table('lost_customer_seq');
            $builder->selectMax('lc_current_seq');
            $query = $builder->get();
            $row = $query->getRow();
            $code = $row->lc_current_seq;
            $seqvalfinal = $row->lc_current_seq;
            if (strlen($row->lc_current_seq) == 1) {
                $code = "ALMLC-000" . $row->lc_current_seq;
            } else if (strlen($row->lc_current_seq) == 2) {
                $code = "ALMLC-00" . $row->lc_current_seq;
            } else if (strlen($row->lc_current_seq) == 3) {
                $code = "ALMLC-0" . $row->lc_current_seq;
            } else {
                $code = "ALMLC-" . $row->lc_current_seq;
            }

            $data = ['lcst_code' => $code, 'lcst_assign' => $user, 'lcst_assigned_on' => date('d/m/Y'), 'lcst_due_date' => $this->request->getVar("Due"), 'lcst_due_date_to' => $this->request->getVar("DueTo"), 'lcst_filter_by' => $filter];

            $res = $model->whereIn("lcst_id",  $cust_id)->set($data)->update();
            $this->insertUserLog('Assign Lost Customer', $tokendata['uid']);


            $builder = $this->db->table('lost_customer_seq');
            $builder->set('lc_current_seq', ++$seqvalfinal);
            $builder->update();
            $response = [
                'ret_data' => 'success',
            ];
            // $cl_res= $model->where("invoice_date >=",  $sdate)->where("invoice_date <=",  $edate)->select('lcst_id ')->first();
            // if($cl_res)
            // {
            //     $data=['lcst_assign'=>$user,'lcst_assigned_on'=>date('d/m/Y')];

            //     $res = $model->where("invoice_date >=",  $sdate)->where("invoice_date <=",  $edate)->set($data)->update();
            //     $response = [
            //         'ret_data'=>'success',
            //     ];
            // }
            // else{
            //     $response = [
            //         'ret_data'=>'fail',
            //     ];

            //     }


            return $this->respond($response, 200);
        }
    }
    public function assigned_date_list()
    {
        $model = new LostCustomerModel();
        $UserModel = new UserModel();
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

            $userdept = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->select('us_dept_id,us_dept_head')->first();
            if ($userdept['us_dept_head'] == true) {
                $builder = $this->db->table('lost_customer_list');
                $builder->select("us_dept_id,lcst_due_date as start,lcst_due_date_to as due,count(lcst_id) as count,lcst_filter_by,us_firstname,lcst_assign,str_to_date(lcst_assigned_on, '%d/%m/%Y') as ass_date,str_to_date(lcst_due_date, '%d/%m/%Y')  as startdate,str_to_date(lcst_due_date_to, '%d/%m/%Y')  as duedate,lcst_code,lcst_file_type");
                $builder->join('users as us', 'us.us_id=lcst_assign');
                $builder->groupby('lcst_due_date');
                $builder->groupby('lcst_due_date_to');
                $builder->groupby('lcst_filter_by');
                $builder->groupby('lcst_code');
                $builder->where('us_dept_id', $userdept['us_dept_id']);
                $builder->orderby('ass_date', 'DESC');
                $query = $builder->get();
                $res = $query->getResultArray();
            } else {

                $builder = $this->db->table('lost_customer_list');
                $builder->select("us_dept_id,lcst_due_date as start,lcst_due_date_to as due,count(lcst_id) as count,lcst_filter_by,us_firstname,lcst_assign,str_to_date(lcst_assigned_on, '%d/%m/%Y') as ass_date,str_to_date(lcst_due_date, '%d/%m/%Y')  as startdate,str_to_date(lcst_due_date_to, '%d/%m/%Y')  as duedate,lcst_code,lcst_file_type");
                $builder->join('users as us', 'us.us_id=lcst_assign');
                $builder->groupby('lcst_due_date');
                $builder->groupby('lcst_due_date_to');
                $builder->groupby('lcst_filter_by');
                $builder->groupby('lcst_code');
                $builder->where('lcst_assign', $tokendata['uid']);
                $builder->orderby('ass_date', 'DESC');
                $query = $builder->get();
                $res = $query->getResultArray();


                // $builder = $this->db->table('lost_customer_list');
                // $builder->select('lcst_due_date as start,lcst_due_date_to as due,count(lcst_id) as count,lcst_filter_by,us_firstname');
                // $builder->join('users as us','us.us_id=lcst_assign');
                // $builder->where('lcst_assign',$tokendata['uid']);
                // $builder->groupby('lcst_due_date');
                // $builder->groupby('lcst_due_date_to');
                // $builder->groupby('lcst_filter_by');
                // $query = $builder->get();
                // $res = $query->getResultArray();   


                // $res= $model->where('lcst_assign',$tokendata['uid'])->groupby('lcst_due_date')->groupby('lcst_due_date_to')->groupby('lcst_filter_by')->select('lcst_due_date as start,lcst_due_date_to as due,count(lcst_id) as count,lcst_filter_by')->findAll();            
            }

            foreach ($res as $key => $value) {

                $note_count = $model
                    ->where('lcst_assign', $value['lcst_assign'])
                    ->where('lcst_due_date', $value['start'])
                    ->where('lcst_due_date_to', $value['due'])
                    ->where('lcst_filter_by', $value['lcst_filter_by'])
                    ->where('lcst_ring_status', 'Answered')
                    ->where('lcst_code', $value['lcst_code'])
                    ->countAllResults(false);


                $resN = $model->where('lcst_note!=', NULL)
                    ->where('lcst_ring_status!=', "")
                    ->where('lcst_assign', $value['lcst_assign'])
                    ->where('lcst_due_date', $value['start'])
                    ->where('lcst_due_date_to', $value['due'])
                    ->where('lcst_filter_by', $value['lcst_filter_by'])
                    //  ->where('lcst_code', $value['lcst_code'])

                    ->join('users', 'users.us_id =lost_customer_list.lcst_assign', 'left')
                    ->select('count(lcst_id) as note_count,lcst_filter_by')
                    ->findAll();

                $resNn = $model
                    ->where('lcst_note =', NULL)
                    //    ->where('lcst_ring_status!=', "")
                    ->where('lcst_assign', $value['lcst_assign'])
                    ->where('lcst_due_date', $value['start'])
                    ->where('lcst_due_date_to', $value['due'])
                    ->where('lcst_filter_by', $value['lcst_filter_by'])
                    //  ->where('lcst_code', $value['lcst_code'])
                    ->join('users', 'users.us_id =lost_customer_list.lcst_assign', 'left')
                    ->join('cust_data_laabs', 'cust_data_laabs.customer_code=lost_customer_list.customer_code')
                    ->whereIn('customer_cat_type', [2, 3, 5, 6])
                    ->select('count(lcst_id) as note_count,lcst_filter_by,customer_cat_type')
                    ->findAll();


                $res[$key]['note_count']  = $note_count;
                $res[$key]['percentage'] = round(($note_count / $res[$key]['count']) * 100);

                // $res[$key]['note_count'] = $resN[0]['note_count'] + $resNn[0]['note_count'];
                // $res[$key]['percentage'] = round((($resN[0]['note_count'] + $resNn[0]['note_count']) / $res[$key]['count']) * 100);
            }

            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'datelist' => $res,
                    'result' => $resN,
                    'new' => $resNn
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'datelist' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function assigned_lc_list()
    {
        $model = new LostCustomerModel();
        $common = new Common();
        $valid = new Validation();
        $UserModel = new UserModel();

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
            // $ad = $this->request->getVar('assigndate');
            $start = $this->request->getVar('start_date');
            $due = $this->request->getVar('due_date');
            $filter =  $this->request->getVar('filter_by');
            $code =  $this->request->getVar('code');


            $builder = $this->db->table('lost_customer_list');
            $builder->select([
                'lcst_id',
                'lost_customer_list.customer_code',
                'lost_customer_list.customer_name',
                'lost_customer_list.phone',
                'lost_customer_list.sms_mobile',
                'lost_customer_list.sms_option',
                'lost_customer_list.email',
                'lost_customer_list.reg_no',
                'lost_customer_list.chasis',
                'lost_customer_list.brand',
                'model_code',
                'model_name',
                'model_year',
                'miles_done',
                'visits',
                'cust_job_data_laabs.invoice_date',
                'lost_customer_list.created_on',
                'lcst_status',
                'lcst_note',
                'lcst_assign',
                'appointment_date',
                'users.us_firstname AS assigned',
                'us.us_firstname AS updated',
                'lcst_ring_status',
                'lcst_due_date',
                'lcst_due_date_to',
                'RIGHT(lost_customer_list.phone, 7) AS phon_uniq',
                'RIGHT(lost_customer_list.sms_mobile, 7) AS mob_uniq',
                'IF(IFNULL(lcst_code, "") = "", " ", lcst_code) AS lcst_code',
                'customer_cat_type',
                'COUNT(cust_job_data_laabs.job_no) AS jobcount'
            ]);
            $builder->join('users', 'users.us_id = lost_customer_list.lcst_assign', 'left');
            $builder->join('users AS us', 'us.us_id = lost_customer_list.lcst_updated_by', 'left');
            $builder->join('cust_data_laabs', 'cust_data_laabs.customer_code = lost_customer_list.customer_code', 'left');
            $builder->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no = lost_customer_list.customer_code AND cust_job_data_laabs.job_status = "INV" AND STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d-%b-%Y") >= STR_TO_DATE(lost_customer_list.lcst_due_date, "%d/%m/%Y") OR STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d/%m/%Y") >= STR_TO_DATE(lost_customer_list.lcst_due_date, "%d/%m/%Y")', 'left');
            $builder->where('users.us_id', $tokendata['uid']);
            $builder->where('lcst_due_date', $start);
            $builder->where('lcst_due_date_to', $due);
            $builder->where('lcst_filter_by', $filter);
            $builder->where('lcst_code', $code);
            $builder->groupBy('lcst_id');

            $query = $builder->get();

            // Get the results
            $results = $query->getResultArray();
            // $userdept = $UserModel->where("us_id", $this->db->escapeString($tokendata['uid']))->select('us_dept_id')->first();

            // $builder = $this->db->table('lost_customer_list');
            // $builder->select("lcst_id,lost_customer_list.customer_code,lost_customer_list.customer_name,lost_customer_list.phone,lost_customer_list.sms_mobile,lost_customer_list.sms_option,lost_customer_list.email,lost_customer_list.reg_no,lost_customer_list.chasis,lost_customer_list.brand,model_code,model_name,model_year,miles_done,visits,invoice_date,lost_customer_list.created_on,lcst_status,lcst_note,lcst_assign,appointment_date,users.us_firstname assigned,us.us_firstname updated,lcst_ring_status,lcst_due_date,RIGHT(lost_customer_list.phone,7) as phon_uniq,RIGHT(lost_customer_list.sms_mobile,7) as mob_uniq,IF(IFNULL(lcst_code, '') = '', ' ', lcst_code) as lcst_code,customer_cat_type");
            // $builder->join('users', 'users.us_id =lost_customer_list.lcst_assign', 'left');
            // $builder->join('users as us', 'us.us_id =lost_customer_list.lcst_updated_by', 'left');
            // $builder->join('cust_data_laabs', 'cust_data_laabs.customer_code =lost_customer_list.customer_code');
            // $builder->where('users.us_dept_id', $userdept);
            // $builder->where('lcst_due_date', $start);
            // $builder->where('lcst_due_date_to', $due);
            // $builder->where('lcst_filter_by', $filter);
            // $builder->where('lcst_code', $code);
            // $builder->orderBy('lcst_assigned_on', 'DESC');
            // $query = $builder->get();
            // $res = $query->getResultArray();
            // if (sizeof($res)) {
            //     $ret_list = [];
            //     foreach ($res as $eachdata) {
            //         $c_date = date('Y-m-d', strtotime(str_replace('/', '-', $eachdata['lcst_due_date'])));
            //         $builder = $this->db->table('cust_job_data_laabs');
            //         $builder->select("job_no,job_open_date,str_to_date(job_open_date, '%d-%M-%y') as jc_open_date,job_status,invoice_date");
            //         $builder->join('cust_veh_data_laabs', 'cust_veh_data_laabs.vehicle_id = cust_job_data_laabs.vehicle_id');
            //         $builder->where('cust_veh_data_laabs.chassis_no', $eachdata['chasis']);
            //         $builder->where('job_status', 'INV');
            //         $builder->where('customer_no', $eachdata['customer_code']);
            //         $builder->where("str_to_date(job_open_date, '%d-%M-%y')  >=",  $c_date);
            //         $builder->where("str_to_date(job_open_date, '%d-%M-%y')  >=",  $c_date);
            //         $query = $builder->get();
            //         $ress = $query->getResultArray();


            //         $builder = $this->db->table('cust_job_data_laabs');
            //         $builder->select("job_no,str_to_date(job_open_date, '%d/%m/%Y')as jc_open_date,job_status,invoice_date");
            //         $builder->join('cust_veh_data_laabs', 'cust_veh_data_laabs.vehicle_id = cust_job_data_laabs.vehicle_id');
            //         $builder->where('cust_veh_data_laabs.chassis_no', $eachdata['chasis']);
            //         $builder->where('job_status', 'INV');
            //         $builder->where('customer_no', $eachdata['customer_code']);
            //         $builder->where("str_to_date(job_open_date, '%d/%m/%Y')  >=",  $c_date);
            //         $query = $builder->get();
            //         $res = $query->getResultArray();
            //         $results = array_merge($ress, $res);
            //         if (sizeof($results) > 0) {
            //             $eachdata['status'] = 'Converted';
            //             $eachdata['Jobcards'] =  $results;
            //             $results = [];
            //         } else {
            //             $eachdata['status'] = 'Pending';
            //         }
            //         array_push($ret_list, $eachdata);
            //     }
            // }


            // $res= $model->where('lcst_assign',$tokendata['uid'])->where('lcst_due_date',$start)->where('lcst_due_date_to',$due)->where('lcst_filter_by',$filter)->join('users as us','us.us_id=lcst_assign')->select('lcst_id,customer_code,customer_name,phone,sms_mobile,sms_option,email,reg_no,chasis,brand,model_code,model_name,model_year,miles_done,visits,invoice_date,created_on,lcst_status,lcst_note,lcst_assign,CONCAT("*****",RIGHT(phone,6)) as trim_phone,appointment_date,us_firstname')->findAll();

            // $this->insertUserLog('View Assigned Lost Customer List', $tokendata['uid']);

            if ($results) {
                $response = [
                    'ret_data' => 'success',
                    'customer' => $results,




                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customer' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function AddLcNote()
    {
        $model = new LostCustomerModel();
        $log = new UserLogTableModel();
        $common = new Common();
        $valid = new Validation();
        $customermodel = new MaragiCustomerModel();
        $ApptMaster = new AppointmentMasterModel();
        $Appoint = new AppointmentModel();
        $Appointmentlog = new AppointmentLogModel();
        $modelL = new LeadModel();
        $custmastermodel = new CustomerMasterModel();

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

            $note = $this->request->getVar("call_remark");
            $status = $this->request->getVar("status");
            $id = $this->request->getVar("lc_id");
            $cust_code = $this->request->getVar("customer_code");
            $builder = $this->db->table('upload_file_list');
            $builder->select('uf_filename');
            $builder->join('lost_customer_list', 'lost_customer_list.lcst_file_id = uf_id');
            $builder->where('lcst_id', $id);
            $query = $builder->get();
            $row1 = $query->getRow();
            $this->db->transStart();

            $cus_id_response = $custmastermodel->where('cust_alm_code', $cust_code)
                ->select('cus_id')
                ->first();
            if ($cus_id_response) {
                $cus_id = $cus_id_response['cus_id'];
            } else {
                $cus_id = '0';
            }


            // if($this->request->getVar("callTime")!=''){

            //     $cll_date = explode(' ',$this->request->getVar("callTime"));
            //     $c_date = date('Y-m-d', strtotime(str_replace('/', '-', $cll_date[0])));


            //     $builder = $this->db->table('cust_job_data_laabs');
            //     $builder->select("job_no,job_open_date,str_to_date(job_open_date, '%d-%M-%y') as jc_open_date");
            //     $builder->where('customer_no', $this->request->getVar("cust_code"));
            //     $builder->where("str_to_date(job_open_date, '%d-%M-%y')  >=",  $c_date );
            //     $query = $builder->get();
            //     $ress = $query->getResultArray();


            //     $builder = $this->db->table('cust_job_data_laabs');
            //     $builder->select("job_no,str_to_date(job_open_date, '%d/%m/%Y')as jc_open_date");
            //     $builder->where('customer_no', $this->request->getVar("cust_code"));
            //     $builder->where("str_to_date(job_open_date, '%d/%m/%Y')  >=",  $c_date );
            //     $query = $builder->get();
            //     $res = $query->getResultArray();
            //     $results = array_merge($ress,$res);

            //     if($results)
            //     {
            //         $status = 'Converted';
            //     }
            // }
            if ($status == "Not Answered") {
                $typeupdate = ['customer_cat_type' => 4];
            } elseif ($status == "Car Sold") {
                $typeupdate = ['customer_cat_type' => 6];
            } elseif ($status == "Unhappy Customer") {
                $typeupdate = ['customer_cat_type' => 3];
            } elseif ($status == "Positive Response" || $status == "Appointment") {
                $typeupdate = ['customer_cat_type' => 2];
                if ($status == "Appointment") {
                    $typeupdate = ['customer_cat_type' => 2];
                    $builder = $this->db->table('sequence_data');
                    $builder->selectMax('current_seq');
                    $query = $builder->get();
                    $row_lead = $query->getRow();
                    $code = $row_lead->current_seq;
                    $seqvalfinal = $row_lead->current_seq;
                    if (strlen($row_lead->current_seq) == 1) {
                        $code = "ALMLD-000" . $row_lead->current_seq;
                    } else if (strlen($row_lead->current_seq) == 2) {
                        $code = "ALMLD-00" . $row_lead->current_seq;
                    } else if (strlen($row_lead->current_seq) == 3) {
                        $code = "ALMLD-0" . $row_lead->current_seq;
                    } else {
                        $code = "ALMLD-" . $row_lead->current_seq;
                    }

                    $Lead_data = [
                        'lead_code' => $code,
                        'name' => $this->request->getVar('customer_name'),
                        'phone' => $this->request->getVar('phone'),
                        'status_id' => 1,
                        'source_id' => 6,  // Lead From Lost Customer
                        'purpose_id' => 1,
                        'cus_id' => $cus_id,
                        'lead_note' => $this->request->getVar('call_remark'),
                        'assigned' => $this->request->getVar('assigned'),
                        'ld_appoint_time' => $this->request->getVar('appTime'),
                        'ld_appoint_date' => $this->request->getVar('dateField'),
                        'lead_createdby' => $tokendata['uid'],
                        'lead_creted_date' => date("Y-m-d H:i:s"),
                        'lead_createdon' => date("Y-m-d H:i:s"),
                        'lead_updatedon' => date("Y-m-d H:i:s"),
                        'register_number' => $this->request->getVar('reg_no'),
                    ];
                    $lead_id = $modelL->insert($Lead_data);

                    if ($lead_id) {
                        $builder = $this->db->table('sequence_data');
                        $builder->set('current_seq', ++$seqvalfinal);
                        $builder->update();
                    }
                    $assigned = $this->request->getVar('assigned');
                    $builder = $this->db->table('sequence_data');
                    $builder->selectMax('appt_seq');
                    $query = $builder->get();
                    $row = $query->getRow();
                    $code = $row->appt_seq;
                    $seqvalfinal = $row->appt_seq;
                    if (strlen($row->appt_seq) == 1) {
                        $code = "ALMAP-000" . $row->appt_seq;
                    } else if (strlen($row->appt_seq) == 2) {
                        $code = "ALMAP-00" . $row->appt_seq;
                    } else if (strlen($row->appt_seq) == 3) {
                        $code = "ALMAP-0" . $row->appt_seq;
                    } else {
                        $code = "ALMAP-" . $row->appt_seq;
                    }
                    $apptMdata = [
                        'apptm_customer_code' => $this->request->getVar('customer_code'),
                        'apptm_code' => $code,
                        'apptm_lead_id' => $lead_id,
                        'apptm_status' => '1', //Appointment Scheduled
                        'apptm_transport_service' =>  $this->request->getVar('transportation_service'),
                        'apptm_created_by' =>  $tokendata['uid'],
                        'apptm_updated_by' =>  $tokendata['uid'],
                        'apptm_type' => 2,
                        'apptm_group' => $this->request->getVar('apptm_group'),
                        'apptm_created_on' => date("Y-m-d H:i:s"),
                    ];
                    $result = $ApptMaster->insert($apptMdata);
                    if ($result) {
                        $builder = $this->db->table('sequence_data');
                        $builder->set('appt_seq', ++$seqvalfinal);
                        $builder->update();
                        $Apptdata = [
                            'appt_apptm_id' => $result,
                            'appt_date' => $this->request->getVar('dateField'),
                            'appt_time' => $this->request->getVar('appTime'),
                            'appt_assign_to' =>  $assigned,
                            'appt_note' => $this->request->getVar('call_remark'),
                            'appt_created_by' => $tokendata['uid'],
                            'appt_created_on' => date("Y-m-d H:i:s"),
                        ];
                        $result1 = $Appoint->insert($Apptdata);
                        $Logdata = [
                            'applg_apptm_id' => $result,
                            'applg_note' => "Appointment Scheduled",
                            'applg_created_by' => $tokendata['uid'],
                            'applg_created_on' => date("Y-m-d H:i:s"),
                            'applg_time' => date("Y-m-d H:i:s"),

                        ];

                        $logentry = $Appointmentlog->insert($Logdata);
                    }
                }
            } elseif ($status == "Do Not Disturb") {
                $typeupdate =
                    [
                        'customer_cat_type' => 5
                    ];
            } elseif ($status == "Visited Customer") {
                $typeupdate = ['customer_cat_type' => 1];
            }
            $customermodel->where("customer_code", $cust_code)->set($typeupdate)->update();
            $data = [
                'lcst_note' => $note,
                'lcst_status' => $status,
                'lcst_note_date' => date('d/m/Y'),
                'appointment_date' => $this->request->getVar("dateField"),
                'lcst_updated_by' => $tokendata['uid'],
                // 'lcst_call_time' => $this->request->getVar("callTime"),
                // 'lcst_ring_status' => $this->request->getVar("ringStatus")
            ];
            $model->where("lcst_id",  $id)->set($data)->update();
            $ip = $this->request->getIPAddress();
            $dataL = [
                'ulg_user' => $tokendata['uid'],
                'ulg_activity' => 'Add Lost Customer Note ' . $note,
                'ulg_file' => $row1->uf_filename, 'ulg_ip' => $ip
            ];
            $res =  $log->insert($dataL);
            $this->insertUserLog('Add Note for Lost Customer', $tokendata['uid']);
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->db->transCommit();
                $data['ret_data'] = "success";
                return $this->respond($data, 200);
            }
            // $response = [
            //     'ret_data' => 'success',
            // ];
            // return $this->respond($response, 200);
        }
    }
    public function uploadFileList()
    {
        $model = new UploadFileListModel();
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

            $res = $model->orderby('uf_id', 'desc')->join('users', 'users.us_id =upload_file_list.uf_created_by', 'left')->select('us_firstname,uf_id,uf_file_name,uf_created_by,uf_file_note,uf_rec_count,uf_filename,DATE(uf_created_on) as uf_created_on,uf_delete_flag')
                ->where('uf_delete_flag', 0)->findAll();

            $this->insertUserLog('View Lost Customer File List', $tokendata['uid']);

            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'filelist' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'filelist' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function LcListByFile()
    {
        $model = new LostCustomerModel();
        $modelf = new UploadFileListModel();
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

            $file_id =  $this->request->getVar('file_id');
            $file = $modelf->where('uf_id', $file_id)->select('uf_filename')->first();

            $res = $model->orderby('lcst_id', 'desc')->where('lcst_file_id', $file_id)->join('users', 'users.us_id =lost_customer_list.lcst_assign', 'left')->select('users.us_firstname,lcst_id,customer_code,customer_name,phone,sms_mobile,sms_option,email,reg_no,chasis,brand,model_code,model_name,model_year,miles_done,visits,invoice_date,created_on,lcst_status,lcst_note,lcst_assign,lcst_note_date,lcst_due_date,lcst_assigned_on,lcst_due_date_to')->findAll();

            $this->insertUserLog('View Lost Customer List', $tokendata['uid']);

            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'customer' => $res,
                    'file' => $file
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customer' => [],
                    'file' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }


    public function getLostCustomerTypewise()
    {
        $model = new LostCustomerModel();
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

            // $assigned =  $this->request->getVar('assigned_to');
            $assigned =  $this->request->getVar('us_id');
            $datefrom = date('d/m/Y', strtotime($this->request->getVar('start_date')));
            $dateto = date('d/m/Y', strtotime($this->request->getVAr('date_to')));
            $fetchtype = $this->request->getVar('fetchtype');
            $fetchtypee =  ($fetchtype === '0') ? [1, 2, 3, 4, 5] : [$fetchtype];

            if ($assigned == 0) {
                $builder = $this->db->table('lost_customer_list');
                $builder->select([
                    'lcst_id',
                    'lost_customer_list.customer_code',
                    'lost_customer_list.customer_name',
                    'lost_customer_list.phone',
                    'lost_customer_list.sms_mobile',
                    'lost_customer_list.sms_option',
                    'lost_customer_list.email',
                    'lost_customer_list.reg_no',
                    'lost_customer_list.chasis',
                    'lost_customer_list.brand',
                    'model_code',
                    'model_name',
                    'model_year',
                    'miles_done',
                    'visits',
                    'cust_job_data_laabs.invoice_date',
                    'lost_customer_list.created_on',
                    'lcst_status',
                    'lcst_note',
                    'lcst_assign',
                    'lcst_file_type',
                    'appointment_date',
                    'users.us_firstname AS assigned',
                    'us.us_firstname AS updated',
                    'lcst_ring_status',
                    'lcst_due_date',
                    'lcst_due_date_to',
                    'RIGHT(lost_customer_list.phone, 7) AS phon_uniq',
                    'RIGHT(lost_customer_list.sms_mobile, 7) AS mob_uniq',
                    'IF(IFNULL(lcst_code, "") = "", " ", lcst_code) AS lcst_code',
                    'customer_cat_type',
                    'COUNT(cust_job_data_laabs.job_no) AS jobcount'
                ]);
                $builder->join('users', 'users.us_id = lost_customer_list.lcst_assign', 'left');
                $builder->join('users AS us', 'us.us_id = lost_customer_list.lcst_updated_by', 'left');
                $builder->join('cust_data_laabs', 'cust_data_laabs.customer_code = lost_customer_list.customer_code', 'left');
                $builder->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no = lost_customer_list.customer_code AND cust_job_data_laabs.job_status = "INV" AND STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d-%b-%Y") >= STR_TO_DATE(lost_customer_list.lcst_due_date, "%d/%m/%Y") OR STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d/%m/%Y") >= STR_TO_DATE(lost_customer_list.lcst_due_date, "%d/%m/%Y")', 'left');
                $builder->where("STR_TO_DATE(lcst_assigned_on, '%d/%m/%Y') >= ", "STR_TO_DATE('$datefrom', '%d/%m/%Y')", false);
                $builder->where("STR_TO_DATE(lcst_assigned_on, '%d/%m/%Y') <= ", "STR_TO_DATE('$dateto', '%d/%m/%Y')", false);
                $builder->whereIn('lcst_file_type', $fetchtypee);
                $builder->groupBy('lcst_id');
                // $builder->groupBy('lcst_file_type');
                $query = $builder->get();

                // Get the results
                $res = $query->getResultArray();
            } else {
                // $res = $model->where('lcst_assign =', $assigned)->join('users', 'users.us_id =lost_customer_list.lcst_assign')
                // ->join('upload_file_list', 'uf_id=lcst_file_id', 'left')
                // ->where("STR_TO_DATE(lcst_assigned_on, '%d/%m/%Y') >= ", "STR_TO_DATE('$datefrom', '%d/%m/%Y')", false)
                // ->where("STR_TO_DATE(lcst_assigned_on, '%d/%m/%Y') <= ", "STR_TO_DATE('$dateto', '%d/%m/%Y')", false)
                // ->groupby('lcst_file_type')
                // ->select("users.us_firstname,lcst_due_date as start,lcst_due_date_to as due,count(lcst_id) as count,lcst_filter_by,lcst_file_type,lcst_assign,lcst_assigned_on, str_to_date(lcst_assigned_on, '%d/%m/%Y') as ass_date,str_to_date(lcst_due_date, '%d/%m/%Y')  as startdate,str_to_date(lcst_due_date_to, '%d/%m/%Y')  as duedate,IF(IFNULL(lcst_code, '') = '', ' ', lcst_code) as lcst_code,upload_file_list.uf_filename")
                // ->findAll();


                $builder = $this->db->table('lost_customer_list');
                $builder->select([
                    'lcst_id',
                    'lost_customer_list.customer_code',
                    'lost_customer_list.customer_name',
                    'lost_customer_list.phone',
                    'lost_customer_list.sms_mobile',
                    'lost_customer_list.sms_option',
                    'lost_customer_list.email',
                    'lost_customer_list.reg_no',
                    'lost_customer_list.chasis',
                    'lost_customer_list.brand',
                    'model_code',
                    'model_name',
                    'model_year',
                    'miles_done',
                    'visits',
                    'cust_job_data_laabs.invoice_date',
                    'lost_customer_list.created_on',
                    'lcst_status',
                    'lcst_note',
                    'lcst_assign',
                    'lcst_file_type',
                    'appointment_date',
                    'users.us_firstname AS assigned',
                    'us.us_firstname AS updated',
                    'lcst_ring_status',
                    'lcst_due_date',
                    'lcst_due_date_to',
                    'RIGHT(lost_customer_list.phone, 7) AS phon_uniq',
                    'RIGHT(lost_customer_list.sms_mobile, 7) AS mob_uniq',
                    'IF(IFNULL(lcst_code, "") = "", " ", lcst_code) AS lcst_code',
                    'customer_cat_type',
                    'COUNT(cust_job_data_laabs.job_no) AS jobcount'
                ]);
                $builder->join('users', 'users.us_id = lost_customer_list.lcst_assign', 'left');
                $builder->join('users AS us', 'us.us_id = lost_customer_list.lcst_updated_by', 'left');
                $builder->join('cust_data_laabs', 'cust_data_laabs.customer_code = lost_customer_list.customer_code', 'left');
                $builder->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no = lost_customer_list.customer_code AND cust_job_data_laabs.job_status = "INV" AND STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d-%b-%Y") >= STR_TO_DATE(lost_customer_list.lcst_due_date, "%d/%m/%Y") OR STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d/%m/%Y") >= STR_TO_DATE(lost_customer_list.lcst_due_date, "%d/%m/%Y")', 'left');
                $builder->where("STR_TO_DATE(lcst_assigned_on, '%d/%m/%Y') >= ", "STR_TO_DATE('$datefrom', '%d/%m/%Y')", false);
                $builder->where("STR_TO_DATE(lcst_assigned_on, '%d/%m/%Y') <= ", "STR_TO_DATE('$dateto', '%d/%m/%Y')", false);
                $builder->where('lcst_assign=', $assigned);
                $builder->whereIn('lcst_file_type', $fetchtypee);
                $builder->groupBy('lcst_id');

                $query = $builder->get();

                // Get the results
                $res = $query->getResultArray();
            }
        }
        if ($res) {
            $response = [
                'ret_data' => 'success',
                'data' => $res,
            ];
            return $this->respond($response, 200);
        } else {
            $response = [
                'ret_data' => 'success',
                'data' => []
            ];
            return $this->respond($response, 200);
        }
    }

    public function assigned_list()
    {
        $model = new LostCustomerModel();
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

            $res = $model->where('lcst_assign !=', 0)
                ->join('users', 'users.us_id =lost_customer_list.lcst_assign')
                ->join('upload_file_list', 'uf_id=lcst_file_id', 'left')
                ->groupby('lcst_assign')
                ->groupby('lcst_due_date')
                ->groupby('lcst_due_date_to')
                ->groupby('lcst_filter_by')
                ->groupby('lcst_code')
                ->select("users.us_firstname,lcst_due_date as start,lcst_due_date_to as due,count(lcst_id) as count,lcst_filter_by,lcst_file_type,lcst_assign,lcst_assigned_on, str_to_date(lcst_assigned_on, '%d/%m/%Y') as ass_date,str_to_date(lcst_due_date, '%d/%m/%Y')  as startdate,str_to_date(lcst_due_date_to, '%d/%m/%Y')  as duedate,IF(IFNULL(lcst_code, '') = '', ' ', lcst_code) as lcst_code,upload_file_list.uf_filename")
                ->orderby('lcst_code', 'desc')
                ->findAll();

            //    $resC= $model->where('lcst_assign !=',0)->where('lcst_note!=',NULL)->join('users','users.us_id =lost_customer_list.lcst_assign','left')->groupby('lcst_assign')->groupby('lcst_assigned_on')->groupby('lcst_due_date')->select('count(lcst_id) as note_count,lcst_filter_by')->findAll();    




            foreach ($res as $key => $value) {

                $note_count = $model
                    ->where('lcst_assign', $value['lcst_assign'])
                    ->where('lcst_due_date', $value['start'])
                    ->where('lcst_due_date_to', $value['due'])
                    ->where('lcst_filter_by', $value['lcst_filter_by'])
                    ->where('lcst_ring_status', 'Answered')
                    ->where('lcst_code', $value['lcst_code'])
                    ->countAllResults(false);

                $resN = $model->where('lcst_note!=', NULL)
                    ->where('lcst_ring_status!=', '')->where('lcst_assign', $value['lcst_assign'])
                    ->where('lcst_due_date', $value['start'])->where('lcst_due_date_to', $value['due'])
                    ->where('lcst_filter_by', $value['lcst_filter_by'])
                    ->where('lcst_code', $value['lcst_code'])
                    ->join('users', 'users.us_id =lost_customer_list.lcst_assign', 'left')
                    ->select('count(lcst_id) as note_count,lcst_filter_by,lcst_code')
                    ->findAll();

                $resNn = $model
                    ->where('lcst_note =', NULL)->where('lcst_assign', $value['lcst_assign'])
                    ->where('lcst_due_date', $value['start'])->where('lcst_due_date_to', $value['due'])
                    ->where('lcst_filter_by', $value['lcst_filter_by'])
                    ->where('lcst_code', $value['lcst_code'])
                    ->join('users', 'users.us_id =lost_customer_list.lcst_assign', 'left')
                    ->join('cust_data_laabs', 'cust_data_laabs.customer_code=lost_customer_list.customer_code')
                    ->whereIn('customer_cat_type', [2, 3, 5, 6])
                    ->select('count(lcst_id) as note_count,lcst_filter_by,customer_cat_type')
                    ->findAll();

                $res[$key]['note_count']  = $note_count;
                $res[$key]['percentage'] = round(($note_count / $res[$key]['count']) * 100);

                // $res[$key]['note_count'] = $resN[0]['note_count'];
                // $res[$key]['percentage'] = round((($resN[0]['note_count']) / $res[$key]['count']) * 100);

                $res[$key]['akhil'] = $resNn;
            }


            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'datelist' => $res,
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'datelist' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    // public function LcAdminReport()
    // {
    //     $model = new LostCustomerModel();
    //     $modelf = new UploadFileListModel();
    //     $common = new Common();
    //     $valid = new Validation();

    //     $heddata = $this->request->headers();
    //     $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
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

    //         $start =  $this->request->getVar('start');
    //         $due =  $this->request->getVar('due');
    //         $filter =  $this->request->getVar('filter');
    //         $uid =  $this->request->getVar('uid');

    //         $builder = $this->db->table('lost_customer_list');
    //         $builder->select("users.us_firstname,us.us_firstname as update_user,lcst_id,customer_code,customer_name,
    //         phone,sms_mobile,sms_option,email,reg_no,chasis,brand,model_code,model_name,model_year,miles_done,
    //         visits,invoice_date,created_on,lcst_status,lcst_note,lcst_assign,lcst_note_date,lcst_due_date,
    //         lcst_assigned_on,appointment_date,lcst_ring_status,lcst_code,
    //         str_to_date(invoice_date, '%d-%M-%y') as inv_date,RIGHT(phone,7) as phon_uniq,
    //         RIGHT(sms_mobile,7) as mob_uniq,lcst_updated_by");
    //         $builder->join('users', 'users.us_id =lost_customer_list.lcst_assign', 'left');
    //         $builder->join('users as us', 'us.us_id =lost_customer_list.lcst_updated_by', 'left');
    //         $builder->where('lcst_due_date', $start);
    //         $builder->where('lcst_due_date_to', $due);
    //         $builder->where('lcst_filter_by', $filter);
    //         $builder->where('lcst_assign', $uid);
    //         $query = $builder->get();
    //         $res = $query->getResultArray();
    //         // return $this->respond($res, 200);

    //         if (sizeof($res)) {
    //             $ret_list = [];
    //             foreach ($res as $eachdata) {
    //                 $c_date = date('Y-m-d', strtotime(str_replace('/', '-', $eachdata['lcst_due_date'])));
    //                 $builder = $this->db->table('cust_job_data_laabs');
    //                 $builder->select("job_no,job_open_date,str_to_date(job_open_date, '%d-%M-%y') as jc_open_date,job_status,invoice_date");
    //                 $builder->join('cust_veh_data_laabs', 'cust_veh_data_laabs.vehicle_id = cust_job_data_laabs.vehicle_id');
    //                 $builder->where('cust_veh_data_laabs.chassis_no', $eachdata['chasis']);
    //                 $builder->where('job_status', 'INV');
    //                 $builder->where('customer_no', $eachdata['customer_code']);
    //                 $builder->where("str_to_date(job_open_date, '%d-%M-%y')  >=",  $c_date);
    //                 $query = $builder->get();
    //                 $ress = $query->getResultArray();


    //                 $builder = $this->db->table('cust_job_data_laabs');
    //                 $builder->select("job_no,str_to_date(job_open_date, '%d/%m/%Y')as jc_open_date,job_status,invoice_date");
    //                 $builder->join('cust_veh_data_laabs', 'cust_veh_data_laabs.vehicle_id = cust_job_data_laabs.vehicle_id');
    //                 $builder->where('cust_veh_data_laabs.chassis_no', $eachdata['chasis']);
    //                 $builder->where('job_status', 'INV');
    //                 $builder->where('customer_no', $eachdata['customer_code']);
    //                 $builder->where("str_to_date(job_open_date, '%d/%m/%Y')  >=",  $c_date);
    //                 $query = $builder->get();
    //                 $res = $query->getResultArray();
    //                 $results = array_merge($ress, $res);
    //                 if (sizeof($results) > 0) {
    //                     $eachdata['status'] = 'Converted';
    //                     $eachdata['Jobcards'] = $results;
    //                     $results = [];
    //                 } else {
    //                     $eachdata['status'] = 'Pending';
    //                     $eachdata['Jobcards'] = 'No jobcards';
    //                 }
    //                 array_push($ret_list, $eachdata);
    //             }
    //         }

    //         //   $res= $model->orderby('lcst_id', 'desc')->where('lcst_due_date',$start)->where('lcst_due_date_to',$due)->where('lcst_filter_by',$filter)->where('lcst_assign',$uid)->join('users','users.us_id =lost_customer_list.lcst_assign','left')->join('users as us','us.us_id =lost_customer_list.lcst_updated_by','left')->select('users.us_firstname,us.us_first_name as update_user,lcst_id,customer_code,customer_name,phone,sms_mobile,sms_option,email,reg_no,chasis,brand,model_code,model_name,model_year,miles_done,visits,invoice_date,created_on,lcst_status,lcst_note,lcst_assign,lcst_note_date,lcst_due_date,lcst_assigned_on,appointment_date')->findAll();

    //         $this->insertUserLog('View Lost Customer Report', $tokendata['uid']);

    //         if ($ret_list) {
    //             $response = [
    //                 'ret_data' => 'success',
    //                 'customer' => $ret_list,

    //             ];
    //             return $this->respond($response, 200);
    //         } else {
    //             $response = [
    //                 'ret_data' => 'success',
    //                 'customer' => [],

    //             ];
    //             return $this->respond($response, 200);
    //         }
    //     }
    // }
    public function LcAdminReport()
    {
        $model = new LostCustomerModel();
        $modelf = new UploadFileListModel();
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

            $start =  $this->request->getVar('start');
            $due =  $this->request->getVar('due');
            $filter =  $this->request->getVar('filter');
            $code = $this->request->getVar('code');
            $uid =  $this->request->getVar('uid');
            // $builder = $this->db->table('lost_customer_list');
            // $builder->select([
            //     'lcst_id',
            //     'lost_customer_list.customer_code',
            //     'lost_customer_list.customer_name',
            //     'lost_customer_list.phone',
            //     'lost_customer_list.sms_mobile',
            //     'lost_customer_list.sms_option',
            //     'lost_customer_list.email',
            //     'lost_customer_list.reg_no',
            //     'lost_customer_list.chasis',
            //     'lost_customer_list.brand',
            //     'model_code',
            //     'model_name',
            //     'model_year',
            //     'miles_done',
            //     'visits',
            //     'cust_job_data_laabs.invoice_date',
            //     'lost_customer_list.created_on',
            //     'lcst_status',
            //     'lcst_note',
            //     'lcst_assign',
            //     'appointment_date',
            //     'users.us_firstname AS assigned',
            //     'us.us_firstname AS updated',
            //     'lcst_ring_status',
            //     'lcst_due_date',
            //     'RIGHT(lost_customer_list.phone, 7) AS phon_uniq',
            //     'RIGHT(lost_customer_list.sms_mobile, 7) AS mob_uniq',
            //     'IF(IFNULL(lcst_code, "") = "", " ", lcst_code) AS lcst_code',
            //     'customer_cat_type',
            //     'COUNT(cust_job_data_laabs.job_no) AS jobcount'
            // ]);
            // $builder->join('users', 'users.us_id = lost_customer_list.lcst_assign', 'left');
            // $builder->join('users AS us', 'us.us_id = lost_customer_list.lcst_updated_by', 'left');
            // $builder->join('cust_data_laabs', 'cust_data_laabs.customer_code = lost_customer_list.customer_code', 'left');
            // $builder->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no = lost_customer_list.customer_code AND cust_job_data_laabs.job_status = "INV" AND STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d-%b-%Y") >= STR_TO_DATE(lost_customer_list.lcst_due_date, "%d/%m/%Y") OR STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d/%m/%Y") >= STR_TO_DATE(lost_customer_list.lcst_due_date, "%d/%m/%Y")', 'left');
            // $builder->where('lcst_due_date', $start);
            // $builder->where('lcst_due_date_to', $due);
            // $builder->where('lcst_filter_by', $filter);
            // $builder->where('lcst_assign', $uid);
            // $builder->groupBy('lcst_id');

            // $query = $builder->get();


            $builder = $this->db->table('lost_customer_list');
            $builder->select([
                'lcst_id',
                'lost_customer_list.customer_code',
                'lost_customer_list.customer_name',
                'lost_customer_list.phone',
                'lost_customer_list.sms_mobile',
                'lost_customer_list.sms_option',
                'lost_customer_list.email',
                'lost_customer_list.reg_no',
                'lost_customer_list.chasis',
                'lost_customer_list.brand',
                'model_code',
                'model_name',
                'model_year',
                'miles_done',
                'visits',
                'cust_job_data_laabs.invoice_date',
                'lost_customer_list.created_on',
                'lcst_status',
                'lcst_note',
                'lcst_assign',
                'appointment_date',
                'users.us_firstname AS assigned',
                'us.us_firstname AS updated',
                'lcst_ring_status',
                'lcst_due_date',
                'lcst_due_date_to',
                'RIGHT(lost_customer_list.phone, 7) AS phon_uniq',
                'RIGHT(lost_customer_list.sms_mobile, 7) AS mob_uniq',
                'IF(IFNULL(lcst_code, "") = "", " ", lcst_code) AS lcst_code',
                'customer_cat_type',
                'COUNT(cust_job_data_laabs.job_no) AS jobcount'
            ]);
            $builder->join('users', 'users.us_id = lost_customer_list.lcst_assign', 'left');
            $builder->join('users AS us', 'us.us_id = lost_customer_list.lcst_updated_by', 'left');
            $builder->join('cust_data_laabs', 'cust_data_laabs.customer_code = lost_customer_list.customer_code', 'left');
            $builder->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no = lost_customer_list.customer_code AND cust_job_data_laabs.job_status = "INV" AND STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d-%b-%Y") >= STR_TO_DATE(lost_customer_list.lcst_due_date, "%d/%m/%Y") OR STR_TO_DATE(cust_job_data_laabs.job_open_date, "%d/%m/%Y") >= STR_TO_DATE(lost_customer_list.lcst_due_date, "%d/%m/%Y")', 'left');
            $builder->where('users.us_id', $uid);
            $builder->where('lcst_due_date', $start);
            $builder->where('lcst_due_date_to', $due);
            $builder->where('lcst_filter_by', $filter);
            $builder->where('lcst_code', $code);
            $builder->groupBy('lcst_id');

            $query = $builder->get();

            // Get the results
            $results = $query->getResultArray();



            //   $res= $model->orderby('lcst_id', 'desc')->where('lcst_due_date',$start)->where('lcst_due_date_to',$due)->where('lcst_filter_by',$filter)->where('lcst_assign',$uid)->join('users','users.us_id =lost_customer_list.lcst_assign','left')->join('users as us','us.us_id =lost_customer_list.lcst_updated_by','left')->select('users.us_firstname,us.us_first_name as update_user,lcst_id,customer_code,customer_name,phone,sms_mobile,sms_option,email,reg_no,chasis,brand,model_code,model_name,model_year,miles_done,visits,invoice_date,created_on,lcst_status,lcst_note,lcst_assign,lcst_note_date,lcst_due_date,lcst_assigned_on,appointment_date')->findAll();

            // $this->insertUserLog('View Lost Customer Report', $tokendata['uid']);

            if ($results) {
                $response = [
                    'ret_data' => 'success',
                    'customer' => $results,

                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customer' => [],

                ];
                return $this->respond($response, 200);
            }
        }
    }
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        $model = new LostCustomerModel();
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

            $res = $model->orderby('lcst_id', 'desc')->join('users', 'users.us_id =lost_customer_list.lcst_assign', 'left')->join('users as us', 'us.us_id =lost_customer_list.lcst_updated_by', 'left')->select('users.us_firstname,us.us_firstname as update_user,lcst_id,customer_code,customer_name,phone,sms_mobile,sms_option,email,reg_no,chasis,brand,model_code,model_name,model_year,miles_done,visits,invoice_date,created_on,lcst_status,lcst_note,lcst_assign,lcst_note_date')->findAll();

            $this->insertUserLog('View Lost Customer List', $tokendata['uid']);

            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'customer' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customer' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    public function disableLcFile()
    {
        $model = new UploadFileListModel();
        $log = new UserLogTableModel();
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

            $id = $this->request->getVar("file_id");
            $status = $this->request->getVar("status");



            $data = ['uf_delete_flag' => $status];
            $model->where("uf_id",  $id)->set($data)->update();

            $this->insertUserLog('Disabel Lost Customer File', $tokendata['uid']);
            $response = [
                'ret_data' => 'success',
            ];
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
    public function updateLCReport()
    {
        $model = new LostCustomerModel();
        $log = new UserLogTableModel();
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

            $arr = $this->request->getVar("data");

            foreach ($arr as $ar) {
                // $data = ['lcst_ring_status' => $ar->lcst_ring_status];
                $res = $model->where("lcst_id",   $ar->lcst_id)
                    // ->where("lcst_ring_status", '')->orWhere("lcst_ring_status", NULL)
                    ->set('lcst_ring_status', $ar->lcst_ring_status)->update();
            }


            $response = [
                'ret_data' => 'success',
            ];
            return $this->respond($response, 200);
        }
    }


    public function getUserAssignedLostCustomers()
    {
        $model = new LostCustomerModel();
        $maraghijobmodel = new MaraghiJobcardModel();
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

            $start = $this->request->getVar('start');
            $due = $this->request->getVar('due');
            $uid = $this->request->getVar('uid');

            // $builder = $this->db->table('lost_customer_list');
            // $builder->select("*");
            // $builder->where("STR_TO_DATE(lcst_due_date, '%d/%m/%Y') >=", $start);
            // $builder->where("STR_TO_DATE(lcst_due_date_to, '%d/%m/%Y') <=", $due);
            // $builder->where('lcst_assign', $uid);
            // $query = $builder->get();
            // $res = $query->getResultArray();

            $lostlist = $model->where("str_to_date(lcst_due_date, '%d/%m/%Y')  >=", $start)
                ->where("str_to_date(lcst_due_date_to, '%d/%m/%Y')  <=", $due)
                ->where('lcst_assign', $uid)
                ->groupby('customer_code')
                ->orderby('customer_code', 'DESC')
                ->limit(1)
                ->findAll();

            $lostlistconverted = $model->where("str_to_date(lcst_due_date, '%d/%m/%Y')  >=", $start)
                ->where("str_to_date(lcst_due_date_to, '%d/%m/%Y')  <=", $due)
                ->where('lcst_assign', $uid)
                ->where('job_status', 'INV')
                ->where("str_to_date(job_open_date, '%d-%M-%y')  >=", $start)
                ->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no=customer_code', 'left')
                ->groupby('customer_code')
                ->orderby('customer_code', 'DESC')
                ->limit(1)
                ->findAll();


            // if (sizeof($lostlist)) {
            //     $ret_list = [];
            //     foreach ($lostlist as $eachdata) {
            //         $c_date = date('Y-m-d', strtotime(str_replace('/', '-', $eachdata['lcst_due_date'])));
            //         $ress=$maraghijobmodel->where('job_status', 'INV')
            //         ->where('customer_no', $eachdata['customer_code'])
            //         ->where("str_to_date(job_open_date, '%d-%M-%y')  >=",  $c_date)
            //         ->groupby('customer_no')
            //         ->select("job_no,str_to_date(job_open_date, '%d-%M-%y') as jc_open_date,job_status,invoice_date")
            //         ->limit(1)
            //         ->findAll();

            //         if (sizeof($ress) > 0) {
            //             $eachdata['status'] = 'Converted';
            //             $eachdata['Jobcards'] = $ress;
            //             $results = [];
            //         } else {
            //             $eachdata['status'] = 'Pending';
            //             $eachdata['Jobcards'] = 'No jobcards';
            //         }
            //         array_push($ret_list, $eachdata);
            //     }
            // }

            if ($lostlist) {
                $response = [
                    'ret_data' => 'success',
                    'lostlist' => $lostlist,
                    'lostlistconverted' =>  $lostlistconverted,

                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'success1',
                    'lostlist' => [],

                ];
                return $this->respond($response, 200);
            }
        }
    }
}
