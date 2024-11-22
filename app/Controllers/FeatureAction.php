<?php

namespace App\Controllers;


use CodeIgniter\RESTful\ResourceController;
use App\Models\FeatureActionModel;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use Config\Common;
use Config\Validation;


class FeatureAction extends ResourceController
{
    /**
     * @api {get} FeatureAction  Action list
     * @apiName Action list
     * @apiGroup Super Admin
     * @apiPermission  Super Admin
     *
     * @apiSuccess {String}   ret_data success or fail
     * @apiSuccess {Object}   action  Object containing action list 
     * @apiError Unauthorized user with this Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     *
     *
     */

    public function index()
    {
        $model = new FeatureActionModel();
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
            $res = $model->findAll();
            if ($res) {
                $response = [
                    'ret_data' => 'success',
                    'action' => $res
                ];
                return $this->respond($response, 200);
            }
        }
    }


    /**
     * @api {get} FeatureAction/:id  Action details by id
     * @apiName Action details by  id
     * @apiGroup Super Admin
     * @apiPermission  Super Admin
     *
     *
     * @apiSuccess {String}   ret_data Success or fail
     * @apiSuccess {Object}   action object with action details
     * 
     * @apiError Unauthorized User with this <code>username</code> is not authorized.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */

    public function show($id = null)
    {
        $model = new FeatureActionModel();
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
            $res = $model->where('fa_id', $id)->first();
            $response = [
                'ret_data' => 'success',
                'action' => $res
            ];
            return $this->respond($response, 200);
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

    /**
     * @api {post} FeatureAction/create Action create
     * @apiName Action create
     * @apiGroup super admin
     * @apiPermission super admin
     *
     *@apiBody {String} action action name     
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error*/

    public function create()
    {
        $model = new FeatureActionModel();
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
                'action' => 'required',

            ];
            if (!$this->validate($rules)) return $this->fail($this->validator->getErrors());
            $data = [
                'fa_name' => $this->request->getVar('action'),
                'fa_created_by' => $tokendata['uid']
            ];

            if ($model->insert($data) === false) {

                $response = [
                    'errors' => $model->errors(),
                    'ret_data' => 'fail'
                ];

                return $this->fail($response, 409);
            }
            return $this->respond(['ret_data' => 'success'], 201);
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

    /**
     * @api {post} FeatureAction/update Action Update
     * @apiName Action Update
     * @apiGroup super admin
     * @apiPermission super admin
     *
     * @apiBody {String} action action name
     * @apiBody {String} id action id
     * 
     * @apiSuccess {String}   ret_data success or fail.
     * 
     * 
     * @apiError Unauthorized User with Unauthorized token.
     * @apiError (500 Internal Server Error) InternalServerError The server encountered an internal error
     */

    public function update($id = null)
    {
        echo "hiuf12--34hdvjkhgkjhfd";
        die;
        $model = new FeatureActionModel();
        $common = new Common();
        $valid = new Validation();

        $heddata = $this->request->headers();
        $tokendata = $common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));

        if ($tokendata['aud'] == 'superadmin') {
            $SuperModel = new SuperAdminModel();
            $super = $SuperModel->where("s_adm_id", $tokendata['uid'])->first();
            if (!$super) return $this->fail("invalid user", 400);
        } else {
            $data['ret_data'] = "Invalid user";
            return $this->fail($data, 400);
        }
        if ($tokendata) {
            $data = [
                'fa_name' => $this->request->getVar('action'),
                'fa_updated_by' => $tokendata['uid']
            ];
            $id = $this->request->getVar('id');
            $model->where('fa_id', $id)->set($data)->update();
            return $this->respond(['ret_data' => 'Updated Successfully'], 201);
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


    public function sendTestWBMessage()
    {
        $common = new Common();
        $data = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => '971509766075',
            "type" => "template",
            "template" => [
                "name" => "service_reminder",
                "language" => [
                    "code" => "en"
                ],
                "components" => array(
                    array(
                        "type" => "header",
                        "parameters" => array(
                            "type" => "image",
                            "image" => [
                                "link" => "https://tmpfiles.org/dl/2748477/snipped.jpg"
                            ]
                        )
                    ),
                    array(
                        "type" => "body",
                        "parameters" => array(
                            "type" => "text",
                            "text" => "Mr. Akhil"
                        ),
                        array(
                            "type" => "text",
                            "text" => "R3-6766"
                        ),
                        array(
                            "type" => "text",
                            "text" => "OCT_2023"
                        )
                    )
                )
            ],
        ];

        $messageData = array(
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => '971509766075',
            "type" => "template",
            'template' => array("name" => "service_reminder", 'language' => array("code" => "en"), 'components' =>
            array(
                array(
                    "type" => "header",
                    "parameters" => array(
                        array("type" => "image", "image" => array("link" => "https://tmpfiles.org/dl/2782326/snipped.jpg"))
                    )
                ),
                array(
                    "type" => "body",
                    "parameters" => array(array("type" => "text", "text" => "Mr. Akhil"), array("type" => "text", "text" => "R3-6766"), array("type" => "text", "text" => "OCT_2023"))
                )
            ))
        );
        $response['ret_data'] = $common->sendWhatsappMessage($messageData, '971509766075');
        return $this->respond($response, 200);
    }
}
