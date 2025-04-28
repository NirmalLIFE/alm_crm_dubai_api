<?php

namespace App\Models\Whatsapp;

use CodeIgniter\Model;

class WhatsappCampaignMessageModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'alm_whatsapp_camp_cus_messages';
    protected $primaryKey       = 'alm_wb_camp_msg_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['alm_wb_camp_msg_id', 'alm_wb_camp_msg_wb_camp_id', 'alm_wb_camp_msg_wb_cus_id', 'alm_wb_camp_msg_cus_phone', 'alm_wb_camp_msg_wb_msg_id', 'alm_wb_camp_msg_cus_reg_no', 'alm_wb_camp_msg_created_on', 'alm_wb_msg_camp_delete_flag'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];


    public function getCampaignMessages($campaignId, $startDate, $endDate)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('alm_whatsapp_camp_cus_messages AS cc')
            ->select('
                camp.alm_wb_camp_id, 
                camp.alm_wb_camp_name, 
                cc.alm_wb_camp_msg_wb_msg_id, 
                cc.alm_wb_camp_msg_created_on,
                msg.alm_wb_msg_status AS message_status, 
                msg.alm_wb_msg_content,
                cust.wb_cus_id, 
                cust.wb_cus_name, 
                cust.wb_cus_mobile, 
                DATE(msg.alm_wb_msg_created_on) AS message_date,
                resp.alm_wb_msg_content AS response_message_content
            ')
            ->select("(CASE WHEN first_resp.min_msg_id IS NOT NULL THEN 'Responded' ELSE 'Not Responded' END) AS response_status", false) // Raw SQL Case
            ->join('alm_whatsapp_campaign AS camp', 'cc.alm_wb_camp_msg_wb_camp_id = camp.alm_wb_camp_id')
            ->join('alm_whatsapp_cus_messages AS msg', 'msg.alm_wb_msg_id = cc.alm_wb_camp_msg_wb_msg_id')
            ->join('alm_whatsapp_customers AS cust', 'cust.wb_cus_id = cc.alm_wb_camp_msg_wb_cus_id')
            ->join("( 
                SELECT 
                    resp.alm_wb_msg_customer, 
                    MIN(resp.alm_wb_msg_id) AS min_msg_id 
                FROM alm_whatsapp_cus_messages AS resp 
                WHERE resp.alm_wb_msg_source = 1  
                GROUP BY resp.alm_wb_msg_customer 
            ) AS first_resp", "first_resp.alm_wb_msg_customer = cust.wb_cus_id 
                          AND first_resp.min_msg_id > msg.alm_wb_msg_id", 'left')
            ->join('alm_whatsapp_cus_messages AS resp', 'resp.alm_wb_msg_id = first_resp.min_msg_id', 'left')
            ->where('DATE(cc.alm_wb_camp_msg_created_on) >=', $startDate)
            ->where('DATE(cc.alm_wb_camp_msg_created_on) <=', $endDate);

        // Apply campaign ID condition
        if ($campaignId != 0) {
            $builder->where('camp.alm_wb_camp_id', $campaignId);
        }

        // Apply filtering for specific campaign types
        $builder->groupStart()
            ->whereNotIn('camp.alm_wb_camp_type', [3, 4, 5])
            ->groupEnd();


        return $builder->get()->getResultArray();
    }

    public function getCSR($campaign_id, $dateFrom, $dateTo, $type)
    {
        $db = \Config\Database::connect();

        // Build the base SQL with subqueries
        $sql = "SELECT 
            cc.alm_wb_camp_msg_created_on,
            DATE_FORMAT(STR_TO_DATE(cc.alm_wb_camp_msg_created_on, '%Y-%m-%d'), '%d-%m-%Y') AS message_date,
            camp.alm_wb_camp_id,
            camp.alm_wb_camp_type,
            cust.wb_cus_mobile,
            cust.wb_cus_name,
            msg1.alm_wb_msg_status,
            cust_laabs.customer_code,
            lj.job_open_date,
            messages2.alm_wb_msg_id AS customer_replied_message_id,
            IF(messages2.alm_wb_msg_id IS NOT NULL, 'true', 'false') AS customer_responded,
            CASE 
                WHEN camp.alm_wb_camp_type = 3 THEN 'First Service Reminder' 
                WHEN camp.alm_wb_camp_type = 4 THEN 'Second Service Reminder'
                WHEN camp.alm_wb_camp_type = 5 THEN 'Third Service Reminder'
                ELSE 'Other' 
            END AS campaign_type,
            CASE 
                WHEN appt.apptm_type = 7 THEN 'First Service Reminder' 
                WHEN appt.apptm_type = 8 THEN 'Second Service Reminder'
                WHEN appt.apptm_type = 9 THEN 'Third Service Reminder'
                ELSE 'Other' 
            END AS appointment_source,
            appt.apptm_type AS appointment_type
        FROM alm_whatsapp_camp_cus_messages cc
        INNER JOIN alm_whatsapp_campaign camp 
            ON camp.alm_wb_camp_id = cc.alm_wb_camp_msg_wb_camp_id
        INNER JOIN alm_whatsapp_customers cust 
            ON cust.wb_cus_id = cc.alm_wb_camp_msg_wb_cus_id
        LEFT JOIN (
            SELECT 
                alm_wb_msg_customer, 
                MAX(alm_wb_msg_id) AS latest_msg_id 
            FROM alm_whatsapp_cus_messages 
            GROUP BY alm_wb_msg_customer
        ) lm ON lm.alm_wb_msg_customer = cc.alm_wb_camp_msg_wb_cus_id
        LEFT JOIN alm_whatsapp_cus_messages msg1 
            ON msg1.alm_wb_msg_id = lm.latest_msg_id
        LEFT JOIN alm_whatsapp_cus_messages messages2 
            ON messages2.alm_wb_msg_customer = cc.alm_wb_camp_msg_wb_cus_id 
            AND messages2.alm_wb_msg_created_on > cc.alm_wb_camp_msg_created_on
            AND messages2.alm_wb_msg_source = 1
        LEFT JOIN cust_data_laabs cust_laabs 
            ON RIGHT(cust_laabs.phone, 9) = RIGHT(cust.wb_cus_mobile, 9)
        LEFT JOIN (
            SELECT 
                j1.customer_no, 
                j1.job_no AS latest_job_no, 
                j1.job_open_date
            FROM cust_job_data_laabs j1
            INNER JOIN (
                SELECT 
                    customer_no, 
                    MAX(job_no) AS latest_job_no 
                FROM cust_job_data_laabs 
                WHERE STR_TO_DATE(job_open_date, '%d-%b-%Y') >= ?
                GROUP BY customer_no
            ) j2 ON j1.customer_no = j2.customer_no AND j1.job_no = j2.latest_job_no
        ) lj ON lj.customer_no = cust_laabs.customer_code
        LEFT JOIN appointment_master appt 
            ON appt.apptm_alternate_no = cust.wb_cus_mobile
        WHERE cc.alm_wb_msg_camp_delete_flag = 0
           AND STR_TO_DATE(LEFT(cc.alm_wb_camp_msg_created_on, 10), '%Y-%m-%d') >= ?
    AND STR_TO_DATE(LEFT(cc.alm_wb_camp_msg_created_on, 10), '%Y-%m-%d') <= ?";

        // Add dynamic campaign filters to WHERE clause
        $params = [$dateFrom, $dateFrom, $dateTo];
        if ($campaign_id != '0') {
            $sql .= " AND camp.alm_wb_camp_id = ?";
            $params[] = $campaign_id;
        } else if (!empty($type)) {
            $placeholders = implode(',', array_fill(0, count($type), '?'));
            $sql .= " AND camp.alm_wb_camp_type IN ($placeholders)";
            $params = array_merge($params, $type);
        }

        // Add GROUP BY and ORDER BY
        $sql .= " GROUP BY cc.alm_wb_camp_msg_wb_cus_id, camp.alm_wb_camp_id 
                  ORDER BY camp.alm_wb_camp_id DESC";

        // Execute
        $query = $db->query($sql, $params);
        return $query->getResultArray();
    }
}
