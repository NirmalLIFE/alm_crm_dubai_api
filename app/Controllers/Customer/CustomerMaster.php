<?php

namespace App\Controllers\Customer;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Customer\CustomerNoteModel;
use App\Models\Customer\CustomerDocumentModel;
use App\Models\Customer\MaraghiVehicleModel;
use App\Models\Customer\MaragiCustomerModel;
use App\Models\Customer\MaraghiJobcardModel;
use App\Models\Leads\LeadModel;
use App\Models\Calllogs\CustomerCallsModel;
use App\Models\Leads\LeadCallLogModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;
use App\Models\UserActivityLog;
use App\Models\Commonutils\CommonNumberModel;

class CustomerMaster extends ResourceController
{
    use ResponseTrait;
    private $db;
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    /**
     * @api {get} Customer/Customer  Customer list
     * @apiName Customer list
     * @apiGroup Customer
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   customer  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */
    public function index()
    {
        $model = new CustomerMasterModel();
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
            $res = $model->where('cust_delete_flag', 0)->join('country_master', 'country_master.id =cust_country', 'left')->join('customer_type', 'customer_type.cst_id =cust_type', 'left')->orderby('cus_id', 'desc')->select('cus_id,cust_name,cust_phone,cust_email,cust_address,cust_emirates,cust_city,cst_name,cst_code,country_name')->findAll();

            $this->insertUserLog('View Customer List', $tokendata['uid']);

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

    /**
     * @api {get} customer/customermaster/:id  Customer list by  id
     * @apiName Customer list by  id
     * @apiGroup Customer
     * @apiPermission  Super Admin, User
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}   customer object with lead source details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function show($id = null)
    {
        $model = new CustomerMasterModel();
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
            $res = $model->where('cus_id', $this->db->escapeString($id))->join('country_master', 'country_master.id =cust_country', 'left')->join('customer_type', 'customer_type.cst_id =cust_type', 'left')->first();
            if ($res) {
                $this->insertUserLog('View Customer data For Update', $tokendata['uid']);
                $response = [
                    'ret_data' => 'success',
                    'customer' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            }
        }
    }

    /**
     * @api {post} customer/customermaster/update Customer Update
     * @apiName Customer Update
     * @apiGroup Customer
     * @apiPermission super admin, User
     *
     *
     *@apiBody {String} cust_type Customer Type
     *@apiBody {String} cust_name Name
     *@apiBody {String} cust_salutation Salutation
     *@apiBody {String} cust_address Address
     *@apiBody {String} cust_emirates Emirates
     *@apiBody {String} cust_city City
     *@apiBody {String} cust_country Country
     *@apiBody {String} cust_phone Phone
     *@apiBody {String} cust_alternate_no Alternate phone Number
     *@apiBody {String} cust_email Email
     *@apiBody {String} cust_lang Lanaguage 
     *@apiBody {String} cus_id Customer ID
     * 
     * @apiSuccess {String}   ret_data success or fail.    * 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function update($id = null)
    {
        $model = new CustomerMasterModel();
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
            $data = [
                'cust_type' => $this->request->getVar('cust_type'),
                'cust_name' => $this->request->getVar('cust_name'),
                'cust_salutation' => $this->request->getVar('cust_salutation'),
                'cust_address' => $this->request->getVar('cust_address'),
                'cust_emirates' => $this->request->getVar('cust_emirates'),
                'cust_city' => $this->request->getVar('cust_city'),
                'cust_country' => $this->request->getVar('cust_country'),
                'cust_phone' => $this->request->getVar('cust_phone'),
                'cust_alternate_no' => $this->request->getVar('cust_alt_no'),
                'cust_email' => $this->request->getVar('cust_email'),
                'cust_lang' => $this->request->getVar('cust_lang'),
                'cust_updated_by' => $tokendata['uid']
            ];
            $id = $this->db->escapeString($this->request->getVar('cus_id'));
            if ($model->where('cus_id', $id)->set($data)->update() === false) {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->insertUserLog('Update Customer Data ', $tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 201);
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
        //
    }

    /**
     * @api {post} customer/customermaster/createNote Create Note
     * @apiName Create Note
     * @apiGroup Customer
     * @apiPermission super admin, User
     *
     *
     *@apiBody {String} note Note     
     *@apiBody {String} cus_id Customer ID
     * 
     * @apiSuccess {String}   ret_data success or fail.    * 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function createNote()
    {
        $model = new CustomerNoteModel();
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
            $id = $this->db->escapeString($this->request->getVar('cus_id'));
            $data = [
                'cus_note' => $this->request->getVar('note'),
                'cus_id' => $id,
                'cus_note_createdby' => $tokendata['uid']
            ];
            if ($model->insert($data) === false) {
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->insertUserLog('New Customer Note Created', $tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 200);
            }
        }
    }

    /**
     * @api {post} customer/customermaster/noteList Note List
     * @apiName  Note List
     * @apiGroup Customer
     * @apiPermission super admin, User
     *
     *       
     *@apiBody {String} cus_id Customer ID
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * @apiSuccess {String}   note  object with note details * 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function noteList()
    {
        $model = new CustomerNoteModel();
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
            $id = $this->db->escapeString($this->request->getVar('cus_id'));
            $res = $model->where('cus_note_delete', 0)->where('cus_id', $id)->orderby('cus_note_id', 'desc')->select('cus_note_id,cus_note,DATE(cus_note_cretedon) as cus_note_cretedon')->findAll();
            $this->insertUserLog('View Customer Note List', $tokendata['uid']);
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'note' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'note' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    /**
     * @api {post} customer/customermaster/deleteNote Delete Note
     * @apiName Note Delete
     * @apiGroup Customer
     * @apiPermission Super Admin , User
     *
     * 
     * @apiParam {String} note_id   id of the note to be deleted
     * 
     * @apiSuccess {String}   ret_data success or fail
     * 
     * @apiError Unauthorized Customer with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function deleteNote()
    {
        $model = new CustomerNoteModel();
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
            $id = $this->db->escapeString($this->request->getVar('note_id'));
            $data = [
                'cus_note_delete' => 1,
            ];
            if ($model->where('cus_note_id', $id)->set($data)->update() === false) {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->respond($response, 200);
            } else {
                $this->insertUserLog('Customer Note Deleted', $tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 200);
            }
        }
    }
    public function docupload()
    {
        helper(['form', 'url']);
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
            $imageFile = $this->request->getFile('attachment');
            $profile_image = $imageFile->getName();
            $imageFile->move(ROOTPATH . 'public/uploads/CustomerDocument');
            $data = [
                'img_name' => $imageFile->getName(),
                'file'  => $imageFile->getClientMimeType(),
                'path' => ROOTPATH,
                'docpath' => 'uploads\\CustomerDocument\\',
            ];
            $data['ret_data'] = "success";
            return $this->respond($data, 200);
        }
    }
    public function saveCustDoc()
    {
        helper(['form', 'url']);
        $model = new CustomerDocumentModel();
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
            $file = $this->request->getFile('attachment');
            $profile_image = $file->getName();
            $file_ext = pathinfo($profile_image, PATHINFO_EXTENSION);
            if ($file_ext == 'docx' || $file_ext == 'pdf') {
                $prev = "uploads\\CustomerDocument\\doc-preview.png";
            } else {
                $prev = "uploads\\CustomerDocument\\" . $profile_image;
            }
            $data = [
                'cust_doc_path' => "uploads\\CustomerDocument\\" . $profile_image,
                'cust_doc_desc' => $this->request->getVar('desc'),
                'cus_id' => $this->request->getVar('cus_id'),
                'cust_doc_name' => $profile_image,
                'cust_doc_thumbnail' => $prev,
                'cust_doc_created_by' => $tokendata['uid']
            ];
            if ($model->insert($data) === false) {
                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];
                return $this->respond($response, 200);
            } else {
                $this->insertUserLog('Attached Customer Doc', $tokendata['uid']);
                return $this->respond(['ret_data' => 'success'], 201);
            }
        }
    }


    /**
     * @api {post} customer/customermaster/getCustDoc Document List
     * @apiName  Document List
     * @apiGroup Customer
     * @apiPermission super admin, User
     *
     *       
     *@apiBody {String} id Customer ID
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * @apiSuccess {String}   doc  object with Document details 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function getCustDoc()
    {
        $model = new CustomerDocumentModel();
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
            $id = $this->db->escapeString($this->request->getVar('id'));
            $res = $model->where('cus_id', $id)->where('cust_doc_delete_flag', 0)->orderBy("cust_doc_id", "desc")->findAll();
            $this->insertUserLog('View Customer Docs', $tokendata['uid']);
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'doc' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'doc' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    /**
     * @api {post} customer/customermaster/getCustVehicle Vehicle List
     * @apiName  Vehicle List
     * @apiGroup Customer
     * @apiPermission super admin, User
     *
     *       
     *@apiBody {String} id Customer ID
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * @apiSuccess {String}   veh  object with Document details 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function getCustVehicle()
    {
        $model = new CustomerMasterModel();
        $vehmodel = new MaraghiVehicleModel();
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
            $id = $this->db->escapeString($this->request->getVar('id'));
            $res = $model->where('cust_delete_flag', 0)->where('cus_id', $id)->join('cust_veh_data_laabs', 'cust_veh_data_laabs.customer_code =cust_alm_code', 'left')->select('cus_id,cust_alm_code,vehicle_id ,brand_code,model_name,family_name,model_year,reg_no,chassis_no')->findAll();

            $this->insertUserLog('View Customer Vehicle List', $tokendata['uid']);

            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'veh' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'veh' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    /**
     * @api {post} customer/customermaster/getCustJobCard Job Card List
     * @apiName  Job Card List
     * @apiGroup Customer
     * @apiPermission super admin, User
     *
     *       
     *@apiBody {String} id Customer ID
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * @apiSuccess {String}   jc  object with Document details 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function getCustJobCard()
    {
        $model = new CustomerMasterModel();
        $vehmodel = new MaraghiVehicleModel();
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
            $id = $this->db->escapeString($this->request->getVar('id'));
            $res = $model->where('cust_delete_flag', 0)->where('cus_id', $id)->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no =cust_alm_code')->select('cus_id,cust_alm_code,job_no ,car_reg_no,job_open_date,job_close_date')->findAll();
            $this->insertUserLog('View Customer Job card List', $tokendata['uid']);
            if ($res) {

                $response = [
                    'ret_data' => 'success',
                    'jc' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'jc' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }
    /**
     * @api {post} customer/customermaster/getCustLead Lead List
     * @apiName  Lead List
     * @apiGroup Customer
     * @apiPermission super admin, User
     *
     *       
     *@apiBody {String} id Customer ID
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * @apiSuccess {String}   lead  object with Document details 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */
    public function getCustLead()
    {
        $model = new CustomerMasterModel();
        $vehmodel = new MaraghiVehicleModel();
        $modelC = new MaragiCustomerModel();
        $common = new Common();
        $valid = new Validation();
        $modelL = new LeadModel();
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
            $id = $this->db->escapeString($this->request->getVar('id'));
            $res =  $modelL->where('lead_delete_flag', 0)->where('cus_id', $id)->where('status_id !=', 7)->join('users', 'users.us_id =leads.assigned', 'left')->join('lead_status', 'lead_status.ld_sts_id =leads.status_id', 'left')->join('call_purposes', 'call_purposes.cp_id =leads.purpose_id', 'left')->select('lead_id,lead_code,lead_note,DATE(lead_createdon) as lead_createdon,lead_status.ld_sts,users.us_firstname,call_purpose')->findAll();

            $this->insertUserLog('View Customer Lead List', $tokendata['uid']);

            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'lead' => $res
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'lead' => []
                ];
                return $this->respond($response, 200);
            }
        }
    }

    public function getCustLog()
    {
        $model = new CustomerMasterModel();
        $vehmodel = new MaraghiVehicleModel();
        $modelC = new MaragiCustomerModel();
        $logmodel = new CustomerCallsModel();
        $common = new Common();
        $valid = new Validation();
        $modelL = new LeadModel();
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

            $id = $this->db->escapeString($this->request->getVar('id'));
            $res = $model->where('cus_id', $this->db->escapeString($id))->select('cust_phone')->first();
            $phone = $res['cust_phone'];

            $builder = $this->db->table('cust_call_logs');
            $builder->select('us.us_firstname as to,usr.us_firstname as from,DATE(cust_call_logs.created_on) as created,cust_call_logs.*');
            $builder->like('call_from', $phone);
            $builder->orLike('call_to', $phone);
            $builder->join('users as us', 'us.ext_number=call_to', 'left');
            $builder->join('users as usr', 'usr.ext_number=call_from', 'left');
            $builder->orderBy('cust_call_logs.call_id', 'desc');
            $query = $builder->get();
            $log = $query->getResultArray();

            $this->insertUserLog('View Customer Call Logs', $tokendata['uid']);

            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'log' => $log
                ];
                return $this->respond($response, 200);
            } else {
                $response = [
                    'ret_data' => 'fail',
                    'log' => []
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

    public function customerExist()
    {
        $marcustmodel = new MaragiCustomerModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();
        $leadlogmodel = new LeadCallLogModel();
        $cnmodel = new CommonNumberModel();

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

            $num_list = $this->request->getVar('num_list');
            $customer = [];
            $i = 0;
            if (sizeof($num_list) > 0) {
                foreach ($num_list as $num) {
                    $cust_master_res = $custmastermodel->where('RIGHT(cust_phone,9)', substr($num, -9))
                        ->orWhere('RIGHT(cust_alternate_contact,9)', substr($num, -9))
                        ->select('cus_id,cust_alm_code,cust_name as customer_name')->first();
                    if (!$cust_master_res) {
                        $marag_cus_res = $marcustmodel->where('RIGHT(phone,9)', substr($num, -9))
                            ->select('customer_code,customer_name,city,mobile,')->first();
                        if (!$marag_cus_res) {
                            $ret_data['cust_name'] = "";
                            $ret_data['cust_id'] = 0;
                            $ret_data['cust_number'] = $num;
                            $customer[$i] = $ret_data;
                            $i++;
                        } else {
                            $ret_data['cust_name'] = strtoupper($marag_cus_res['customer_name']);
                            $ret_data['cust_id'] = 0;
                            $ret_data['cust_number'] = $num;
                            $customer[$i] = $ret_data;
                            $i++;
                        }
                    } else {
                        $ret_data['cust_name'] = strtoupper($cust_master_res['customer_name']);
                        $ret_data['cust_id'] = $cust_master_res['cus_id'];
                        $ret_data['cust_number'] = $num;
                        $customer[$i] = $ret_data;
                        $i++;
                    }
                }
                $response = [
                    'ret_data' => 'success',
                    'customers' => $customer,
                ];
            }else{
                $response = [
                    'ret_data' => 'success',
                    'customers' => [],
                ];
            }
            return $this->respond($response, 200);

        }
    }
    public function leadExistReport()
    {
        $marcustmodel = new MaragiCustomerModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();
        $leadlogmodel = new LeadCallLogModel();
        $cnmodel = new CommonNumberModel();
        $marjobmodel = new MaraghiJobcardModel();

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

            $statusArr = ['1', '6'];
            $today = date("d/m/Y");
            $todayy = date("Y-m-d");
            $startdate = $this->request->getVar('date');

            $date = date("Y-m-d", strtotime($startdate));
            $call_to = $this->request->getVar('call_to');
            $num_list = $this->request->getVar('num_list');
            $call_id = $this->request->getVar('call_id');





            $builder = $this->db->table('leads');
            $builder->select('count(lead_id) as ld');
            $builder->like('lead_creted_date', $today);
            $builder->whereIn('status_id', $statusArr);
            $builder->where('outbound_lead', 0);
            $query = $builder->get();
            $result = $query->getRow();
            $created_lead =  $result->ld;

            $builder = $this->db->table('leads');
            $builder->select('count(lead_id) as cnld');
            $builder->where('DATE(lead_updatedon)', $todayy);
            $builder->where('status_id', 5);
            $query = $builder->get();
            $result = $query->getRow();
            $conv_lead =  $result->cnld;
            if (sizeof($num_list) > 0) {
                $num_unique = array_unique($num_list);

                $lead = $leadmodel->whereIn('RIGHT(phone,7)', $num_list)->whereIn('status_id', $statusArr)->join('call_purposes', 'call_purposes.cp_id=purpose_id')->select('lead_id ,call_purposes.cp_id,call_purposes.call_purpose,name as customer_name,phone as mobile,lead_code,RIGHT(phone,7) as phon_uniq,lead_creted_date,close_time,lead_updatedon')->find();

                $leadlog = $leadlogmodel->whereIn('ystar_call_id', $call_id)->where('lcl_pupose_id !=' . 0)->join('call_purposes', 'call_purposes.cp_id =lead_call_log.lcl_pupose_id', 'left')->select("lcl_id,lcl_time,lcl_lead_id,RIGHT(lcl_phone,7) as phon_uniq,lcl_purpose_note,ystar_call_id,lcl_call_to,call_purpose")->find();

                $common = $cnmodel->whereIn('RIGHT(cn_number,7)', $num_list)->select('cn_id,RIGHT(cn_number,7) as phon_uniq')->find();

                $response = [
                    'ret_data' => 'success',
                    'lead' => $lead,
                    'leadlog' => $leadlog,
                    'common' => $common,
                    'cr_lead' => $created_lead,
                    'conv_lead' => $conv_lead,

                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'lead' => [],
                    'leadlog' => [],
                    'common' => [],
                    'cr_lead' => $created_lead,
                    'conv_lead' => $conv_lead,

                ];
            }


            return $this->respond($response, 200);
        }
    }
    public function customerExistReport()
    {
        $marcustmodel = new MaragiCustomerModel();
        $leadmodel = new LeadModel();
        $custmastermodel = new CustomerMasterModel();
        $leadlogmodel = new LeadCallLogModel();
        $cnmodel = new CommonNumberModel();
        $marjobmodel = new MaraghiJobcardModel();

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

            // $phone = $this->request->getVar('phone');
            // $ph = substr($phone, -7);
            // $patern = $ph; 
            $statusArr = ['1', '6'];
            $today = date("d/m/Y");
            $todayy = date("Y-m-d");

            $startdate = $this->request->getVar('date');

            $date = date("Y-m-d", strtotime($startdate));
            $call_to = $this->request->getVar('call_to');
            $num_list = $this->request->getVar('num_list');
            $call_id = $this->request->getVar('call_id');
            $i = 0;
            $j = 0;
            $customer = [];
            $jobcard_res = [];

            //    $hours =  date('Y-m-d H:i:s', strtotime('-1 day', time()));
            $hours =  date('Y-m-d', strtotime('-1 day'));

            if (sizeof($num_list) > 0) {
                $num_unique = array_unique($num_list);
                $marag_cus_res = $marcustmodel->whereIn('RIGHT(phone,7)', $num_list)->select("customer_code,UPPER(customer_name) as customer_name,city,mobile,RIGHT(phone,7) as phon_uniq,'M' as type")->find();
                $cust_master_res = $custmastermodel->whereIn('RIGHT(cust_phone,7)', $num_list)->select("cus_id,cust_alm_code,UPPER(cust_name) as customer_name,cust_city as city,cust_phone as mobile,RIGHT(cust_phone,7) as phon_uniq,'C' as type,RIGHT(cust_alternate_contact,7) as alt_num_uniq")->find();
                $lead_res = $leadmodel->whereIn('RIGHT(phone,7)', $num_list)->where('DATE(lead_updatedon) >=', $hours)->select("IF(IFNULL(name, '') = '', 'EXISTS', name) as customer_name,phone as mobile,RIGHT(phone,7) as phon_uniq,'L' as type")->find();
                $customer = array_merge($marag_cus_res, $cust_master_res, $lead_res);

                $jobcard_res = $marjobmodel->where('job_status', 'WIP')->whereIn('RIGHT(cust_data_laabs.phone,7)', $num_list)->join('cust_data_laabs', 'cust_data_laabs.customer_code=customer_no')->select('job_no,customer_no,RIGHT(cust_data_laabs.phone,7) as phon_uniq')->find();





                // foreach ($num_unique as $num) {

                //     $marag_cus_res = $marcustmodel->where('RIGHT(phone,7)', $num)->select('customer_code,customer_name,city,mobile,RIGHT(phone,7) as phon_uniq')->first();
                //     if ($marag_cus_res) {
                //         $marag_cus_res['type'] = 'M';
                //         $marag_cus_res['customer_name'] = strtoupper($marag_cus_res['customer_name']);
                //         $customer[$i] = $marag_cus_res;
                //         $i++;
                //     } else {
                //         $cust_master_res = $custmastermodel->where('RIGHT(cust_phone,7)', $num)->select('cus_id,cust_alm_code,cust_name as customer_name,cust_city as city,cust_phone as mobile,RIGHT(cust_phone,7) as phon_uniq')->first();
                //         if ($cust_master_res) {
                //             $cust_master_res['customer_name'] = strtoupper($cust_master_res['customer_name']);
                //             $cust_master_res['type'] = 'C';
                //             $customer[$i] = $cust_master_res;
                //             $i++;
                //         }
                //         else{
                //             $lead_res= $leadmodel->where('RIGHT(phone,7)',$num)->where('status_id !=', 7)->select('name as customer_name,phone as mobile,RIGHT(phone,7) as phon_uniq')->first();
                //             if($lead_res)
                //             {
                //                 $lead_res['customer_code'] = '0000';
                //                 $lead_res['customer_name']='EXISTS';
                //                 $lead_res['type']='L';                               
                //                 $customer[$i] = $lead_res;
                //                 $i++;
                //             }


                //         }

                //     }
                //     $job_res = $marjobmodel->where('RIGHT(cust_data_laabs.phone,7)', $num)->where('job_status', 'WIP')->join('cust_data_laabs', 'cust_data_laabs.customer_code=customer_no')->select('job_no,customer_no,RIGHT(phone,7) as phon_uniq')->first();


                //     if ($job_res) {
                //         $jobcard_res[$j] = $job_res;
                //         $j++;

                //     }
                // }



                $response = [
                    'ret_data' => 'success',
                    'customers' => $customer,
                    'jobcard' => $jobcard_res,
                    ' $hours' => $hours

                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'customers' => [],
                    'jobcard' => [],
                    ' $hours' => $hours
                ];
            }


            return $this->respond($response, 200);
        }
    }

    public function customerStatusReport()
    {
        $marcustmodel = new MaragiCustomerModel();
        $custmastermodel = new CustomerMasterModel();
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
            $num_list = $this->request->getVar('num_list');
            $i = 0;
            $customer = null;
            if (sizeof($num_list) > 0) {
                foreach ($num_list as $num) {
                    $marag_cus_res = $marcustmodel->where('RIGHT(phone,7)', $num)->select('customer_code,customer_name,city,mobile,RIGHT(phone,7) as phon_uniq')->first();
                    if ($marag_cus_res) {
                        $marag_cus_res['type'] = 'M';
                        $marag_cus_res['customer_name'] = strtoupper($marag_cus_res['customer_name']);
                        $customer[$i] = $marag_cus_res;
                        $i++;
                    } else {
                        $cust_master_res = $custmastermodel->where('RIGHT(cust_phone,7)', $num)->select('cus_id,cust_alm_code,cust_name as customer_name,cust_city as city,cust_phone as mobile,RIGHT(cust_phone,7) as phon_uniq')->first();
                        if ($cust_master_res) {
                            $cust_master_res['customer_name'] = strtoupper($cust_master_res['customer_name']);
                            $cust_master_res['type'] = 'C';
                            $customer[$i] = $cust_master_res;
                            $i++;
                        }
                    }
                }
                $response = [
                    'ret_data' => 'success',
                    'msg' => 'Found in Maraghi Customer',
                    'customers' => $customer,
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'lead' => [],
                    'customers' => [],
                ];
            }


            return $this->respond($response, 200);
        }
    }





    // public function customerExist()
    // {
    //     $marcustmodel = new MaragiCustomerModel();        
    //     $leadmodel = new LeadModel();
    //     $custmastermodel = new CustomerMasterModel();  
    //     $leadlogmodel = new LeadCallLogModel();    

    //     $common =new Common();
    //     $valid=new Validation();   
    //     $heddata=$this->request->headers();
    //     $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));   
    //     if($tokendata['aud']=='superadmin'){
    //         $SuperModel = new SuperAdminModel();
    //         $super = $SuperModel->where("s_adm_id", $this->db->escapeString($tokendata['uid']))->first();
    //         if(!$super) return $this->fail("invalid user",400);        
    //     }else if($tokendata['aud']=='user'){
    //         $usmodel = new UserModel();
    //         $user = $usmodel->where("us_id", $this->db->escapeString($tokendata['uid']))->first();
    //         if(!$user) return $this->fail("invalid user",400); 
    //     }
    //     else{          
    //         $data['ret_data']="Invalid user";
    //         return $this->fail($data,400);
    //     }
    //     if($tokendata['aud']=='superadmin' || $tokendata['aud']=='user'){

    //         $phone = $this->request->getVar('phone');
    //         $startdate = $this->request->getVar('date');
    //         $ph = substr($phone, -7);
    //         $patern = $ph; 
    //         $date = date("Y-m-d", strtotime($startdate));
    //         $call_time = $this->request->getVar('call_time');
    //         $call_to = $this->request->getVar('call_to');

    //         $cust_master_res= $custmastermodel->like('cust_phone', $patern)->select('cus_id,cust_alm_code,cust_name as customer_name,cust_city as city,cust_phone as mobile')->first();
    //         $marag_cus_res= $marcustmodel->like('phone', $patern)->select('customer_code,customer_name,city,mobile')->first();
    //         $lead_res= $leadmodel->like('phone', $patern)->where('status_id !=', 7)->select('lead_id ,name as customer_name,phone as mobile')->first();
    //         $lead = $leadmodel->like('phone', $patern)->where('status_id !=', 7)->where('DATE(lead_createdon) >=',$startdate)->select('lead_id ,name as customer_name,phone as mobile,lead_code')->first();
    //         $leadlog = $leadlogmodel->where('lcl_call_time', $call_time)->like('lcl_phone',$patern)->where('lcl_call_to',$call_to)->select('lcl_id,lcl_time,lcl_lead_id,')->first();

    //         if($lead )
    //         {
    //             $avail = $lead['lead_code'];
    //         }
    //         else{
    //             $avail = 'fail';
    //         }
    //         if($leadlog)
    //         {
    //             $availg = $leadlog['lcl_call_time'];
    //         }
    //         else{
    //             $availg = 'fail';
    //         }
    //         if($cust_master_res) // Phone number found in CRM customer master table
    //         {
    //             $response = [
    //                 'ret_data'=>'success',
    //                 'lead'=>$avail,
    //                 'leadlog'=>$availg,
    //                 'msg'=>'Found in CRM Customer',
    //                 'customer'=>$cust_master_res,                        
    //             ];
    //             return $this->respond($response,200);
    //         }
    //         else if($marag_cus_res)
    //         {
    //              $response = [
    //             'ret_data'=>'success',
    //             'lead'=>$avail,
    //             'leadlog'=>$availg,
    //             'msg'=>'Found in Maraghi Customer',
    //             'customer'=>$marag_cus_res,                        
    //             ];
    //             return $this->respond($response,200);

    //         }
    //         else if($lead_res)
    //         {
    //              $response = [
    //             'ret_data'=>'success',
    //             'lead'=>$avail,
    //             'leadlog'=>$availg,
    //             'msg'=>'Found in CRM Lead',
    //             'customer'=>$lead_res,                        
    //             ];
    //             return $this->respond($response,200);
    //         }
    //         else{
    //             $response = [
    //                 'ret_data'=>'fail',
    //                 'lead'=>$avail,
    //                 'leadlog'=>$avail,
    //                 'msg'=>'Not Found',
    //                 'customer'=>$lead_res,                        
    //                 ];
    //                 return $this->respond($response,200);
    //         }
    //     }
    // }


    public function customerJobStatusReport()
    {
        $marcustmodel = new MaragiCustomerModel();
        $custmastermodel = new CustomerMasterModel();
        $marjobmodel = new MaraghiJobcardModel();
        $common = new Common();
        $valid = new Validation();
        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
        $customer = [];
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
            $num_list = $this->request->getVar('num_list');

            $startdate = date("Y-m-d", strtotime($this->request->getVar('start_date')));
            $enddate = date("Y-m-d", strtotime($this->request->getVar('end_date')));
            $i = 0;
            if (sizeof($num_list) > 0) {
                foreach ($num_list as $num) {
                    $marag_cus_res = $marcustmodel->where('RIGHT(phone,7)', $num)->select('customer_code,customer_name,created_on,city,mobile,RIGHT(phone,7) as phon_uniq')->first();
                    if ($marag_cus_res) {
                        $marag_cus_res['type'] = 'M';
                        $marag_cus_res['customer_name'] = strtoupper($marag_cus_res['customer_name']);
                        $mm = $marjobmodel->where('customer_no', $marag_cus_res['customer_code'])
                            ->orderBy('created_on', 'desc')->limit(1)->first();

                        //     $conv = $marjobmodel->where('customer_no',$marag_cus_res['customer_code'])->where("str_to_date(job_open_date, '%d-%M-%y')  >=",  $startdate)->where("str_to_date(job_open_date, '%d-%M-%y')  <=",  $enddate)
                        //     ->where('job_status', 'INV')->select("str_to_date(job_open_date, '%d-%M-%y')")->first();
                        //     if($conv)
                        //     {
                        //         $marag_cus_res['convert'] =   'true'; 
                        //     }
                        //    else{
                        //     $marag_cus_res['convert'] =   'fail';
                        //    }


                        if ($mm) {
                            $marag_cus_res['last_jobcard'] = $mm;
                        } else {
                            $marag_cus_res['last_jobcard'] = 'fail';
                        }
                        $customer[$i] = $marag_cus_res;
                        $i++;
                    } else {
                        $cust_master_res = $custmastermodel->where('RIGHT(cust_phone,7)', $num)->select('cus_id,cust_alm_code,cust_name as customer_name,cust_city as city,cust_phone as mobile,RIGHT(cust_phone,7) as phon_uniq')->first();
                        if ($cust_master_res) {
                            $cust_master_res['customer_name'] = strtoupper($cust_master_res['customer_name']);
                            $cust_master_res['type'] = 'C';
                            $cust_master_res['last_jobcard'] = 'fail';
                            $customer[$i] = $cust_master_res;
                            $i++;
                        }
                    }
                }
                $response = [
                    'ret_data' => 'success',
                    'msg' => 'Found in Maraghi Customer',
                    'customers' => $customer,
                ];
            } else {
                $response = [
                    'ret_data' => 'success',
                    'lead' => [],
                    'customers' => [],
                ];
            }


            return $this->respond($response, 200);
        }
    }
    function customerConvertReport()
    {
        $marcustmodel = new MaragiCustomerModel();
        $custmastermodel = new CustomerMasterModel();
        $marjobmodel = new MaraghiJobcardModel();
        $leadmodel = new LeadModel();
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
            $num_list = $this->request->getVar('num_list');

            $startdate = date('Y-m-d', strtotime(str_replace('/', '-', $this->request->getVar('start_date'))));

            $enddate = date('Y-m-d', strtotime(str_replace('/', '-', $this->request->getVar('end_date'))));
            $statusArr = ['1', '6'];
            $i = 0;
            if (sizeof($num_list) > 0) {

                $builder = $this->db->table('cust_job_data_laabs');
                $builder->select("job_no,job_open_date,str_to_date(job_open_date, '%d-%M-%y') as jc_open_date,job_status,RIGHT(phone,7) as phon_uniq,customer_no");
                $builder->join('cust_data_laabs', 'customer_code = customer_no');
                $builder->where("DATE(cust_data_laabs.created_on)  >=",  $startdate);
                $builder->where("DATE(cust_data_laabs.created_on)  <=",  $enddate);
                $builder->where("str_to_date(job_open_date, '%d-%M-%y')  >=",  $startdate);
                $builder->where("str_to_date(job_open_date, '%d-%M-%y')  <=",  $enddate);
                $builder->where('job_status', 'INV');
                $builder->whereIn('RIGHT(phone,7)', $num_list);
                $query = $builder->get();
                $resjc = $query->getResultArray();


                $builder = $this->db->table('cust_job_data_laabs');
                $builder->select("job_no,str_to_date(job_open_date, '%d/%m/%Y')as jc_open_date,RIGHT(phone,7) as phon_uniq,customer_no");
                $builder->join('cust_data_laabs', 'customer_code = customer_no');
                $builder->where("DATE(cust_data_laabs.created_on)  >=",  $startdate);
                $builder->where("DATE(cust_data_laabs.created_on)  <=",  $enddate);
                $builder->where("str_to_date(job_open_date, '%d/%m/%Y')  >=",  $startdate);
                $builder->where("str_to_date(job_open_date, '%d/%m/%Y')  <=",  $enddate);
                $builder->where('job_status', 'INV');
                //   $builder->whereIn('RIGHT(phone,7)', $num_list);
                $query = $builder->get();
                $resjcc = $query->getResultArray();
                $resultsjcc = array_merge($resjc, $resjcc);


                $lead = $leadmodel->whereIn('RIGHT(phone,7)', $num_list)->whereIn('status_id', $statusArr)->join('call_purposes', 'call_purposes.cp_id=purpose_id')->select('lead_id ,call_purposes.cp_id,call_purposes.call_purpose,name as customer_name,phone as mobile,lead_code,RIGHT(phone,7) as phon_uniq,lead_creted_date,close_time')->find();

                $response = [
                    'ret_data' => 'success',
                    'msg' => 'Found in Maraghi Customer',
                    'customersConv' => $resjc,
                    'lead' =>    $lead,
                ];


                return $this->respond($response, 200);
            }
        }
    }

    function searchCustomer()
    {
        $marcustmodel = new MaragiCustomerModel();
        $custmastermodel = new CustomerMasterModel();
        $marvehmodel = new MaraghiVehicleModel();
        $marjcmodel = new MaraghiJobcardModel();
        $resV = [];
        $resJ = [];
        $resJC = [];
        $resJY = [];

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
            $searchItem = $this->request->getVar('searchItem');
            $item = substr($this->request->getVar('searchItem'), -7);

            $res = $marvehmodel->where("cust_veh_data_laabs.reg_no",  $searchItem)->join('cust_data_laabs', 'cust_data_laabs.customer_code = cust_veh_data_laabs.customer_code')
                ->select("cust_data_laabs.customer_code,customer_name,phone,reg_no,city,mobile")->first();






            if ($res) {
                $cust_code = $res['customer_code'];
                $resV = $marvehmodel->where("customer_code", $cust_code)->where('reg_no IS NOT NULL', null, false)->select("reg_no,family_name,brand_code,model_name,model_year,miles_done")->findAll();
                $resJ = $marjcmodel->where("customer_no", $cust_code)->orderBy('job_no', "desc")->select("DATE(created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no")->findAll();
                $resJC = $marjcmodel->where('customer_no', $cust_code)->orderBy('job_no', "desc")->select('DATE(created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no')->first();
                $resJY = $marjcmodel->where('customer_no', $cust_code)->where('invoice_date !=', '')->groupBy('substring(invoice_date, 7, 10)')->select('substring(invoice_date, 7, 10) as year,count(job_no) as jy')->limit(4)->findAll();
            } else {
                $res = $marvehmodel->where("RIGHT(cust_data_laabs.phone,7)", $item)->join('cust_data_laabs', 'cust_data_laabs.customer_code = cust_veh_data_laabs.customer_code')
                    ->select("cust_data_laabs.customer_code,customer_name,phone,reg_no,city,mobile")->first();


                if ($res) {
                    $cust_code = $res['customer_code'];
                    $resV = $marvehmodel->where("customer_code", $cust_code)->where('reg_no IS NOT NULL', null, false)->select("reg_no,family_name,brand_code,model_name,model_year,miles_done")->findAll();
                    $resJ = $marjcmodel->where("customer_no", $cust_code)->orderBy('job_no', "desc")->select("DATE(created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no")->findAll();
                    $resJC = $marjcmodel->where('customer_no', $cust_code)->orderBy('job_no', "desc")->select('DATE(created_on) as created_on,job_no,car_reg_no,job_open_date,job_close_date,job_status,user_name,invoice_date,invoice_no')->first();
                    $resJY = $marjcmodel->where('customer_no', $cust_code)->where('invoice_date !=', '')->groupBy('substring(invoice_date, 7, 10)')->select('substring(invoice_date, 7, 10) as year,count(job_no) as jy')->limit(4)->findAll();
                }
            }
            $response = [
                'ret_data' => 'success',
                'res' => $res,
                'vehicle' => $resV,
                'jobcard' => $resJ,
                'LJC' => $resJC,
                'JCY' => $resJY
            ];

            return $this->respond($response, 200);
        }
    }

    public function customercatlist()
    {
        $marcustmodel = new MaragiCustomerModel();
        $marjobmodel = new MaraghiJobcardModel();

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
            $custlist = [];
            // $perPage = 100; // Number of records per page
            // $page = 1; // Current page number
            // $offset = ($page - 1) * $perPage;
            // ->limit($perPage, $offset)

            $columns = ["customer_code", "customer_cat_type", "customer_name", "phone", "mobile", "customer_cat_type", "job_no", "vehicle_id", "car_reg_no", "invoice_date"];
            // $custlist = $marcustmodel->select("customer_code, customer_cat_type, customer_name, phone, mobile, customer_cat_type, job_no, vehicle_id, car_reg_no,invoice_date")
            //     ->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no = customer_code')
            //     ->orderBy('job_no', 'desc')
            //     //->groupBy('customer_no')
            //     ->where('customer_cat_type !=', '1')
            //     ->find();

            $custlist = $marcustmodel->select("customer_code, customer_cat_type, customer_name, phone, mobile, job_no, vehicle_id, car_reg_no, invoice_date,job_open_date,job_close_date,job_status")
                ->join('cust_job_data_laabs', 'cust_job_data_laabs.customer_no = customer_code', 'left')
                ->orderBy('job_no', 'desc')
                // ->groupBy('customer_no') 
                ->where('customer_cat_type !=', '1')
                //->where('job_status','INV')
                ->find();


            $Total_cust = $marcustmodel->countAllResults();

            $active_cust = $marcustmodel->where('customer_cat_type', 1)
                ->countAllResults();
            $interested_Cust = $marcustmodel->where('customer_cat_type', 2)
                ->countAllResults();
            $unhappy_cust = $marcustmodel->where('customer_cat_type', 3)
                ->countAllResults();
            $non_cont_cust = $marcustmodel->where('customer_cat_type', 4)
                ->countAllResults();
            $DnD_cust = $marcustmodel->where('customer_cat_type', 5)
                ->countAllResults();
            $Lost_cust = $marcustmodel->where('customer_cat_type', 6)
                ->countAllResults();



            $response = [
                'ret_data' => 'success',
                'customers' => $custlist,
                'Total_cust' => $Total_cust,
                'active_cust' => $active_cust,
                'interested_Cust' => $interested_Cust,
                'unhappy_cust' => $unhappy_cust,
                'non_cont_cust' => $non_cont_cust,
                'DnD_cust' => $DnD_cust,
                'Lost_cust' => $Lost_cust,
            ];
        } else {
            $response = [
                'ret_data' => 'success',
                'customers' => [],
                'active_cust' => 0,
                'interested_Cust' => 0,
                'unhappy_cust' => 0,
                'non_cont_cust' => 0,
                'DnD_cust' => 0,
                'Lost_cust' => 0,
            ];
        }


        return $this->respond($response, 200);
    }

    public function customercatdata()
    {

        $marjobmodel = new MaraghiJobcardModel();

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

            $cust_code = $this->request->getVar('cust_code');
            // $perPage = 100; // Number of records per page
            // $page = $this->request->getVar('page') ?: 1;
            // $offset = ($page - 1) * $perPage;

            // $columns = ["customer_no", "vehicle_id", "car_reg_no", "invoice_date", "job_no"];

            // $custdata = $marjobmodel->select("customer_no,vehicle_id,car_reg_no")
            //     ->orderBy('job_no', 'desc')
            //     ->whereIn('customer_no', $cust_code)
            //     ->limit($perPage, $offset)
            //     ->findAll();
            $allCustomerData = [];

            foreach ($cust_code as $code) {
                $customerData = $marjobmodel->select("customer_no", "vehicle_id", "car_reg_no", "invoice_date", "job_no")
                    ->orderBy('job_no', 'desc')
                    ->where('customer_no', $code)
                    ->findAll();

                // Add the current customer's data to the array
                $allCustomerData[] = $customerData;
            }





            $response = [
                'ret_data' => 'success',
                'customers' => $allCustomerData,
            ];
        } else {
            $response = [
                'ret_data' => 'success',
                'customers' => [],
            ];
        }


        return $this->respond($response, 200);
    }
}
