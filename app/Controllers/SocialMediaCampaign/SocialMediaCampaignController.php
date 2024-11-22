<?php

namespace App\Controllers\SocialMediaCampaign;

use CodeIgniter\RESTful\ResourceController;
use Config\Common;
use Config\Validation;
use App\Models\SuperAdminModel;
use App\Models\UserModel;
use App\Models\SocialMediaCampaign\SocialMediaCampaignModel;
use App\Models\SocialMediaCampaign\SocialMediaCampaignSourceModel;
use App\Models\SocialMediaCampaign\SocialMediaCampaignAmountModel;
use App\Models\Leads\LeadModel;




class SocialMediaCampaignController extends ResourceController
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

            $socialMediaCampaign = new SocialMediaCampaignModel();
            $socialMediaCampaignSource = new SocialMediaCampaignSourceModel();
            $builder = $this->db->table('sequence_data');
            $builder->selectMax('social_media_campaign_seq');
            $query = $builder->get();
            $row = $query->getRow();
            $seqvalfinal = $row->social_media_campaign_seq;

            $source = $this->request->getVar('source');
            $end_date = $this->request->getVar('end_date');
            $month = date('m', strtotime($end_date));
            $year = date('y', strtotime($end_date));

            $scodeResults = [];
            if (is_array($source)) {
                foreach ($source as $src) {
                    $scodeResult = $socialMediaCampaignSource->select('smcs_code')
                        ->where('smcs_id', $src)
                        ->where('smcs_delete_flag', 0)
                        ->first();
                    if ($scodeResult) {
                        $scodeResults[] = [
                            'src' => $src,
                            'code' => $scodeResult['smcs_code']
                        ];
                    }
                }
            } else {
                $scodeResult = $socialMediaCampaignSource->select('smcs_code')
                    ->where('smcs_id', $source)
                    ->where('smcs_delete_flag', 0)
                    ->first();
                if ($scodeResult) {
                    $scodeResults[] = [
                        'src' => $source,
                        'code' => $scodeResult['smcs_code']
                    ];
                }
            }

            $this->db->transStart();

            foreach ($scodeResults as $scodeResult) {
                $seqvalfinal++;
                if (strlen($seqvalfinal) == 1) {
                    $code = "CM" . $scodeResult['code'] . $month . $year . "-000" . $seqvalfinal;
                } else if (strlen($seqvalfinal) == 2) {
                    $code = "CM" . $scodeResult['code'] . $month . $year . "-00" . $seqvalfinal;
                } else if (strlen($seqvalfinal) == 3) {
                    $code = "CM" . $scodeResult['code'] . $month . $year . "-0" . $seqvalfinal;
                } else {
                    $code = "CM" . $scodeResult['code'] . $month . $year . "-" . $seqvalfinal;
                }

                $data = [
                    'smc_code' => $code,
                    'smc_ad_id' => $this->request->getVar('id'),
                    'smc_name' => $this->request->getVar('name'),
                    'smc_message' => $this->request->getVar('message'),
                    'smc_status' => 0,
                    'smc_start_date' => $this->request->getVar('start_date'),
                    'smc_end_date' => $this->request->getVar('end_date'),
                    'smc_source' => $scodeResult['src'],
                    'smc_owner' => $this->request->getVar('owner'),
                    'smc_created_by' => $tokendata['uid'],
                    'smc_created_on' => date("Y-m-d H:i:s"),
                    'smc_updated_by' => $tokendata['uid'],
                    'smc_updated_on' => date("Y-m-d H:i:s"),
                ];

                $smc_id = $socialMediaCampaign->insert($data);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->db->transCommit();
                $builder = $this->db->table('sequence_data');
                $builder->set('social_media_campaign_seq', $seqvalfinal);
                $builder->update();
                $data = [
                    'ret_data' => 'success',
                    'SocialMediaCampaign' => $smc_id,
                ];
                return $this->respond($data, 200);
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

            $socialMediaCampaign = new SocialMediaCampaignModel();
            $socialMediaCampaignAmount = new SocialMediaCampaignAmountModel();

            $smc_id = $this->request->getVar('smc_id');
            $items = $this->request->getVar('datesWithAmount');


            $this->db->transStart();
            $data = [
                // 'smc_name' => $this->request->getVar('smc_name'),
                // 'smc_message' => $this->request->getVar('smc_message'),
                'smc_status' => $this->request->getVar('smc_status'),
                // 'smc_start_date' => $this->request->getVar('smc_start_date'),
                // 'smc_end_date' => $this->request->getVar('smc_end_date'),
                // 'smc_source' =>  $this->request->getVar('smc_source'),
                // 'smc_owner' => $this->request->getVar('smc_owner'),
                'smc_updated_by' => $tokendata['uid'],
                'smc_updated_on' => date("Y-m-d H:i:s"),
            ];

            $socialMediaCampaign->where('smc_id', $smc_id)->set($data)->update();


            if ($smc_id) {
                $insdata = array();
                $updata = array();

                foreach ($items as $item) {
                    if ($item->smca_id == 0) {
                        $insdata[] = array(
                            'smca_smc_id' => $smc_id,
                            'smca_date' => $item->smca_date,
                            'smca_amount' => $item->smca_amount,

                        );
                    } else {
                        $updata[] = array(
                            'smca_id'  => $item->smca_id,
                            'smca_date' => $item->smca_date,
                            'smca_amount' => $item->smca_amount,
                        );
                    }
                }

                if (!empty($updata)) {
                    $ret = $socialMediaCampaignAmount->updateBatch($updata, 'smca_id');
                }
                if (!empty($insdata)) {
                    $ret = $socialMediaCampaignAmount->insertBatch($insdata);
                }
            }
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->db->transCommit();
                $data = [
                    'ret_data' => 'success',
                    'SocialMediaCampaign' => $ret,
                ];
                return $this->respond($data, 200);
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

    public function socialMediaCampaignsource()
    {
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

            $socialMediaCampaignSource = new  SocialMediaCampaignSourceModel();

            $sourceList = $socialMediaCampaignSource->select('*')
                ->where('smcs_delete_flag', 0)
                ->findAll();


            if (sizeof($sourceList) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'sourceList' => $sourceList,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getSocialMediaCampaigns()
    {

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

            $socialMediaCampaign = new SocialMediaCampaignModel();

            $dateFrom = $this->request->getVar('dateFrom');
            $dateTo = $this->request->getVar('dateTo');

            $campaigns = $socialMediaCampaign->select('social_media_campaign.*,us_firstname,smcs_name,smcs_name')
                ->where('smc_delete_flag', 0)
                ->join('users', 'users.us_id = smc_owner', 'left')
                ->join('social_media_campaign_source', 'social_media_campaign_source.smcs_id =smc_source', 'left');

            // if (!empty($start_Date)) {
            //     $campaigns->where("DATE(smc_start_date) <=", $start_Date)
            //         ->where("DATE(smc_end_date) >=", $start_Date);
            // }
            // if (!empty($end_Date)) {
            //     $campaigns->where("DATE(smc_start_date) <=", $end_Date)
            //         ->where("DATE(smc_end_date) >=", $end_Date);
            // }
            if (!empty($dateFrom)) {
                $campaigns->where('DATE(smc_start_date) >=', $dateFrom);
            }
            if (!empty($dateTo)) {
                $campaigns->where('DATE(smc_end_date) <=', $dateTo);
            }
            $campaigns = $campaigns->findAll();


            if (sizeof($campaigns) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'campaigns' => $campaigns,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function getSocialMediaCampaignDetails()
    {
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

            $socialMediaCampaign = new SocialMediaCampaignModel();
            $socialMediaCampaignAmount = new SocialMediaCampaignAmountModel();

            $id = $this->request->getVar('id');
            $campaigns = $socialMediaCampaign->where('smc_id', $id)
                ->select('social_media_campaign.*,us_firstname,smcs_name')
                ->where('smc_delete_flag', 0)
                ->join('users', 'users.us_id =smc_owner', 'left')
                ->join('social_media_campaign_source', 'social_media_campaign_source.smcs_id =smc_source', 'left')
                ->first();

            $amounts = $socialMediaCampaignAmount->where('smca_smc_id', $id)
                ->where('smca_delete_flag', 0)
                ->findAll();

            $amounts = sizeof($amounts) > 0 ? $amounts : [];

            if (sizeof($campaigns) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'campaigns' => $campaigns,
                    'amounts' => $amounts,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function changeCampaignStatus()
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

            $socialMediaCampaign = new SocialMediaCampaignModel();
            $smc_id = $this->request->getVar('smc_id');
            $smc_status = $this->request->getVar('status');
            // if ($this->request->getVar('status') == true) {
            //     $smc_status = 0;
            // } else {
            //     $smc_status = 1;
            // }

            $this->db->transStart();
            $data = [
                'smc_status' =>  $smc_status,
                'smc_updated_by' => $tokendata['uid'],
                'smc_updated_on' => date("Y-m-d H:i:s"),
            ];

            $ret = $socialMediaCampaign->where('smc_id', $smc_id)->set($data)->update();

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->db->transCommit();
                $data = [
                    'ret_data' => 'success',
                    'SocialMediaCampaign' => $ret,
                ];
                return $this->respond($data, 200);
            }
        }
    }

    public function checkSocialMediaCampaign()
    {
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

            $socialMediaCampaign = new SocialMediaCampaignModel();

            $message = $this->request->getVar('message');
            $start_Date = $this->request->getVar('start_Date');
            $end_Date = $this->request->getVar('end_Date');
            $source = $this->request->getVar('source');
            $status = $this->request->getVar('status');

            $campaigns = $socialMediaCampaign->select('social_media_campaign.*')
                //->where('smc_status', 0)
                ->where('smc_delete_flag', 0)
                ->where('smc_message', $message)
                ->where('smc_ad_id',$this->request->getVar('id'));
            if (is_array($source)) {
                $campaigns->whereIn('smc_source', $source);
            } else {
                $campaigns->where('smc_source', $source);
            }

            if ($status != '1') {
                $campaigns->where('smc_status', 0);
            }

            if (!empty($start_Date) && !empty($end_Date)) {
                $campaigns->groupStart()
                    ->where("DATE(smc_start_date) <=", $end_Date)
                    ->where("DATE(smc_end_date) >=", $start_Date)
                    ->groupEnd();
            } elseif (!empty($start_Date)) {
                $campaigns->where("DATE(smc_start_date) <=", $start_Date)
                    ->where("DATE(smc_end_date) >=", $start_Date);
            } elseif (!empty($end_Date)) {
                $campaigns->where("DATE(smc_start_date) <=", $end_Date)
                    ->where("DATE(smc_end_date) >=", $end_Date);
            }

            $campaigns = $campaigns->findAll();



            // $campaigns = $socialMediaCampaign->select('social_media_campaign.*')
            //     //->where('smc_message',  $message)
            //     ->where('smc_status', 0)
            //     ->where('smc_delete_flag', 0);

            // if (!empty($message)) {
            //     $campaigns->where('smc_message',  $message);
            // }
            // if (!empty($start_Date)) {
            //     $campaigns->where('DATE(smc_start_date)', $start_Date);
            // }
            // if (!empty($end_Date)) {
            //     $campaigns->where('DATE(smc_end_date)', $end_Date);
            // }
            // $campaigns = $campaigns->findAll();




            if (sizeof($campaigns) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'campaigns' => $campaigns,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }

    public function socialMediaCampaignDelete()
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

            $socialMediaCampaign = new SocialMediaCampaignModel();
            $socialMediaCampaignAmount = new SocialMediaCampaignAmountModel();

            $smc_id =  $this->request->getVar('smc_id');
            $this->db->transStart();


            $data = [
                'smc_delete_flag' => 1,
                'smc_updated_by' => $tokendata['uid'],
                'smc_updated_on' => date("Y-m-d H:i:s"),
            ];
            $updata = [
                'smca_delete_flag' => 1,
            ];

            $socialMediaCampaignAmount->where('smca_smc_id', $smc_id)->set($updata)->update();
            $socialMediaCampaign->where('smc_id', $smc_id)->set($data)->update();

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->db->transCommit();
                $data = [
                    'ret_data' => 'success',
                    'SocialMediaCampaign' => $smc_id,
                ];
                return $this->respond($data, 200);
            }
        }
    }


    public function getActiveSocialMediaCampaigns()
    {

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

            $socialMediaCampaign = new SocialMediaCampaignModel();

            $today = $this->request->getVar('today');

            $campaigns = $socialMediaCampaign->select('social_media_campaign.*, us_firstname, smcs_name')
                ->where('smc_delete_flag', 0)
                ->where('smc_status', 0)
                ->join('users', 'users.us_id = smc_owner', 'left')
                ->join('social_media_campaign_source', 'social_media_campaign_source.smcs_id = smc_source', 'left')
                ->where('DATE(smc_start_date) <=', $today)
                ->where('DATE(smc_end_date) >=', $today)
                ->findAll();

            if (sizeof($campaigns) > 0) {
                $response = [
                    'ret_data' => 'success',
                    'activeCampaigns' => $campaigns,
                ];
            } else {
                $response = [
                    'ret_data' => 'fail',
                ];
            }
            return $this->respond($response, 200);
        }
    }


    public function socialMediaCampaignDetailsfetch()
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
            $startdate = $this->request->getVar('dateFrom');
            $enddate = $this->request->getVar('dateTo');
            $campId = $this->request->getVar('campaign');
            $leadmodel = new LeadModel();


            $customerDetails = $leadmodel->where('source_id', 2)
                ->where('lead_createdon >=', $startdate)
                ->where('lead_createdon <=', $enddate)
                ->join('appointment_master', 'lead_id = appointment_master.apptm_lead_id', 'left')
                ->join('social_media_campaign', 'social_media_campaign.smc_id = lead_social_media_mapping', 'left')
                ->join('social_media_campaign_source', 'social_media_campaign_source.smcs_id = lead_social_media_source', 'left')
                ->where('smc_delete_flag', 0);
            if (!empty($campId)) {
                $customerDetails->where('smc_id', $campId);
            }
            $customerDetails = $customerDetails->findAll();


            $data['ret_data'] = "succes";
            $data['customer_data'] = $customerDetails;
            return $this->respond($data, 200);
        }
    }

    public function AutoStatusOff()
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

            $socialMediaCampaign = new SocialMediaCampaignModel();

            $this->db->transStart();

            $campaigns = $socialMediaCampaign->where('DATE(smc_end_date) <', date('Y-m-d'))->get()->getResultArray();

            foreach ($campaigns as $campaign) {
                $endDate = strtotime($campaign['smc_end_date']);
                if ($endDate < strtotime(date('Y-m-d'))) {
                    $data = [
                        'smc_status' => 1,
                        'smc_updated_by' => $tokendata['uid'],
                        'smc_updated_on' => date("Y-m-d H:i:s"),
                    ];

                    $socialMediaCampaign->where('smc_id', $campaign['smc_id'])->update($data);
                }
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                $data['ret_data'] = "fail";
                return $this->respond($data, 200);
            } else {
                $this->db->transCommit();
                $data = [
                    'ret_data' => 'success',
                    'SocialMediaCampaign' => $campaigns,
                ];
                return $this->respond($data, 200);
            }
        }
    }
}
