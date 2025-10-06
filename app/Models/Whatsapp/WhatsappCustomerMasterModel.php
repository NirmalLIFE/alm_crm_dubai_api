<?php

namespace App\Models\Whatsapp;

use CodeIgniter\Model;

class WhatsappCustomerMasterModel extends Model
{

    protected $DBGroup          = 'default';
    protected $table            = 'alm_whatsapp_customers';
    protected $primaryKey       = 'wb_cus_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['wb_cus_id', 'wb_cus_name', 'wb_cus_mobile', 'wb_cus_profile_pic', 'wb_cus_category','wb_cus_lead_category', 'wb_cus_follow_up', 'wb_cus_follow_up_time', 'wb_cus_reminder', 'wb_cus_remind_date', 'wb_cus_remind_flag', 'wb_cus_pick_drop', 'wb_cus_pickup_mode', 'wb_cus_assigned', 'wb_cus_created_on', 'wb_cus_updated_on', 'wb_cus_block', 'wb_cus_delete_flag'];


    //     public function getCustomerWithLastMessageAndUnreadCount($offset, $limit)
    //     {
    //         $sql = "SELECT 
    //     c.wb_cus_id,
    //     c.wb_cus_name,
    //     c.wb_cus_mobile,
    //     c.wb_cus_category,
    //     c.wb_cus_created_on,
    //     c.wb_cus_updated_on,
    //     lm.*,
    //     IFNULL(message_status_2_count, 0) AS message_status_2_count
    // FROM 
    //     alm_whatsapp_customers c
    // LEFT JOIN (
    //     SELECT 
    //         alm_wb_msg_customer,
    //         MAX(alm_wb_msg_created_on) AS max_created_on,
    //         COUNT(CASE WHEN alm_wb_msg_status = 2 AND alm_wb_msg_source = 1 THEN 1 END) AS message_status_2_count
    //     FROM 
    //         alm_whatsapp_cus_messages
    //     GROUP BY 
    //         alm_wb_msg_customer
    // ) AS msg_summary
    // ON c.wb_cus_id = msg_summary.alm_wb_msg_customer
    // LEFT JOIN 
    //     alm_whatsapp_cus_messages lm
    // ON 
    //     c.wb_cus_id = lm.alm_wb_msg_customer
    //     AND lm.alm_wb_msg_created_on = msg_summary.max_created_on
    // WHERE 
    //     c.wb_cus_block = false
    // ORDER BY 
    //     msg_summary.max_created_on DESC
    // LIMIT ?, ?;
    // ";

    //         // Execute the query and return the results
    //         $query = $this->db->query($sql, [$offset, $limit]);
    //         return $query->getResultArray();
    //     }


    public function getCustomerWithLastMessageAndUnreadCount($offset, $limit)
    {
        // Step 1: Get customer data with unread message count and max_created_on timestamp
        $customers = $this->getCustomerWithLastMessageSummary($offset, $limit);

        // Step 2: Extract customer IDs from the results
        $customerIds = array_map(function ($customer) {
            return $customer['wb_cus_id'];
        }, $customers);

        // Step 3: Get the latest message for each customer
        $messages = $this->getLastMessagesForCustomers($customerIds);

        // Step 4: Merge the results, adding the message fields directly into the customer object
        foreach ($customers as &$customer) {
            foreach ($messages as $message) {
                if ($customer['wb_cus_id'] === $message['alm_wb_msg_customer']) {
                    // Flatten the message into the customer object
                    foreach ($message as $key => $value) {
                        $customer[$key] = $value;  // Add each message field to the customer
                    }
                    break;
                }
            }
        }

        // Step 5: Return the merged result
        return $customers;
    }






    public function getCustomerWithLastMessageSummary($offset, $limit)
    {
        $sql = "SELECT 
            c.wb_cus_id,
            c.wb_cus_name,
            c.wb_cus_mobile,
            c.wb_cus_category,
            c.wb_cus_created_on,
            c.wb_cus_updated_on,
            c.wb_cus_profile_pic,
            c.wb_cus_lead_category,
            msg_summary.max_created_on,
            IFNULL(msg_summary.message_status_2_count, 0) AS message_status_2_count
        FROM 
            alm_whatsapp_customers c
        LEFT JOIN (
            SELECT 
                alm_wb_msg_customer,
                MAX(alm_wb_msg_created_on) AS max_created_on,
                COUNT(CASE WHEN alm_wb_msg_status = 2 AND alm_wb_msg_source = 1 THEN 1 END) AS message_status_2_count
            FROM 
                alm_whatsapp_cus_messages
            GROUP BY 
                alm_wb_msg_customer
        ) AS msg_summary
        ON c.wb_cus_id = msg_summary.alm_wb_msg_customer
        WHERE 
            c.wb_cus_block = false
        ORDER BY 
            msg_summary.max_created_on DESC
        LIMIT ?, ?;
       ";

        $query = $this->db->query($sql, [$offset, $limit]);
        return $query->getResultArray();
    }



    public function getLastMessagesForCustomers($customerIds)
    {
        $sql = "SELECT 
            lm.alm_wb_msg_id,
            lm.alm_wb_msg_master_id,
            lm.alm_wb_msg_source,
            lm.alm_wb_msg_staff_id,
            lm.alm_wb_msg_type,
            lm.alm_wb_msg_content,
            lm.alm_wb_msg_caption,
            lm.alm_wb_msg_status,
            lm.alm_wb_msg_customer,
            lm.alm_wb_msg_reply_id,
            lm.alm_wb_msg_created_on,
            lm.alm_wb_msg_updated_on,
            lm.alm_wb_msg_delete_flag
        FROM 
            alm_whatsapp_cus_messages lm
        WHERE 
            lm.alm_wb_msg_customer IN ?
        AND 
            lm.alm_wb_msg_created_on IN (
                SELECT MAX(alm_wb_msg_created_on)
                FROM alm_whatsapp_cus_messages
                WHERE alm_wb_msg_customer = lm.alm_wb_msg_customer
                GROUP BY alm_wb_msg_customer
            );";

        $query = $this->db->query($sql, [$customerIds]);
        return $query->getResultArray();
    }








    // ------------->>>>>>DUBAI API >>>>>>>>>>--------------------------------

    // public function getCustomerWithLastMessageAndUnreadCount($offset, $limit)
    // {
    //     $sql = "SELECT
    //             c.*,
    //             lm.*,
    //             (
    //                 SELECT COUNT(*) 
    //                 FROM alm_whatsapp_cus_messages m 
    //                 WHERE m.alm_wb_msg_customer = c.wb_cus_id 
    //                 AND m.alm_wb_msg_status = 2 AND alm_wb_msg_source = 1
    //             ) AS message_status_2_count,
    //             leads.status_id
    //         FROM 
    //             alm_whatsapp_customers c
    //         LEFT JOIN (
    //             SELECT 
    //                 alm_wb_msg_customer, 
    //                 MAX(alm_wb_msg_created_on) AS max_created_on
    //             FROM 
    //                 alm_whatsapp_cus_messages 
    //             GROUP BY 
    //                 alm_wb_msg_customer
    //         ) AS last_msg_summary
    //         ON c.wb_cus_id = last_msg_summary.alm_wb_msg_customer
    //         LEFT JOIN leads ON RIGHT(c.wb_cus_mobile, 9) = RIGHT(leads.phone, 9) AND leads.status_id = 8
    //         LEFT JOIN alm_whatsapp_cus_messages lm
    //         ON c.wb_cus_id = lm.alm_wb_msg_customer
    //         AND lm.alm_wb_msg_created_on = last_msg_summary.max_created_on
    //         ORDER BY last_msg_summary.max_created_on DESC
    //        LIMIT ?, ?";

    //     // Ensure indexes are created on the relevant columns in your database

    //     // Execute the query and return the results
    //     $query = $this->db->query($sql, [$offset, $limit]);
    //     return $query->getResultArray();
    // }


    // <---------------------------------------------------------------------------------------------------------------->

    // public function getCustomerWithLastMessageAndUnreadCount($offset, $limit)
    // {
    //     $sql = "
    //         WITH latest_messages AS (
    //         SELECT 
    //             alm_wb_msg_customer,
    //             alm_wb_msg_content,
    //             alm_wb_msg_updated_on,
    //             alm_wb_msg_created_on,
    //             alm_wb_msg_source,
    //             alm_wb_msg_type,
    //             alm_wb_msg_status,
    //             alm_wb_msg_reply_id,
    //             alm_wb_msg_id,
    //             alm_wb_msg_staff_id,
    //             ROW_NUMBER() OVER (PARTITION BY alm_wb_msg_customer ORDER BY alm_wb_msg_created_on DESC) AS rn,
    //             COUNT(CASE WHEN alm_wb_msg_source = 1 AND alm_wb_msg_status = 2 THEN 1 END) OVER (PARTITION BY alm_wb_msg_customer) AS message_status_2_count
    //         FROM 
    //             alm_whatsapp_cus_messages
    //     ),
    //     filtered_messages AS (
    //         SELECT 
    //             alm_wb_msg_customer,
    //             alm_wb_msg_content,
    //             alm_wb_msg_updated_on,
    //             alm_wb_msg_created_on,
    //             alm_wb_msg_source,
    //             alm_wb_msg_type,
    //             alm_wb_msg_status,
    //             alm_wb_msg_reply_id,
    //             alm_wb_msg_id,
    //             alm_wb_msg_staff_id,
    //             message_status_2_count
    //         FROM 
    //             latest_messages
    //         WHERE 
    //             rn = 1  -- Only keep the latest message per customer
    //     )
    //     SELECT
    //         c.wb_cus_id,
    //         c.wb_cus_name,
    //         c.wb_cus_mobile,
    //         c.wb_cus_category,
    //         c.wb_cus_follow_up,
    //         c.wb_cus_follow_up_time,
    //         fm.alm_wb_msg_updated_on,
    //         fm.alm_wb_msg_content,
    //         fm.alm_wb_msg_created_on,
    //         fm.alm_wb_msg_source,
    //         fm.alm_wb_msg_type,
    //         fm.alm_wb_msg_status,
    //         fm.alm_wb_msg_reply_id,
    //         fm.alm_wb_msg_id,
    //         fm.alm_wb_msg_staff_id,
    //         COALESCE(fm.message_status_2_count, 0) AS message_status_2_count,
    //         leads.*
    //     FROM 
    //         alm_whatsapp_customers c
    //     LEFT JOIN filtered_messages fm ON c.wb_cus_id = fm.alm_wb_msg_customer
    //     LEFT JOIN leads ON RIGHT(c.wb_cus_mobile, 9) = RIGHT(leads.phone, 9) AND leads.status_id = 8
    //     WHERE 
    //         c.wb_cus_block = false
    //     ORDER BY 
    //         fm.alm_wb_msg_created_on DESC";
    //     // LIMIT ?, ?";

    //     if ($limit !== null && $limit > 0) {
    //         $sql .= " LIMIT ?, ?";
    //     }

    //     // Prepare query parameters
    //     $queryParams = [];
    //     if ($limit !== null && $limit > 0) {
    //         // Add offset and limit to the query params
    //         $queryParams = [$offset, $limit];
    //     }
    //     // If limit is 0 or null, no LIMIT or OFFSET is applied, so queryParams remains empty or unchanged

    //     // Execute the query
    //     $query = $this->db->query($sql, $queryParams);
    //     return $query->getResultArray();

    //     // Ensure indexes are created on the relevant columns in your database

    //     // Execute the query and return the results
    //     // $query = $this->db->query($sql, [$offset, $limit]);
    //     // return $query->getResultArray();
    // }

    // public function getCustomerMessageCounts()
    // {
    //     $sql =  "SELECT
    //         (
    //             SELECT COUNT(*)
    //             FROM alm_whatsapp_cus_messages m
    //             LEFT JOIN alm_whatsapp_customers c ON m.alm_wb_msg_customer = c.wb_cus_id
    //             WHERE m.alm_wb_msg_source = 1
    //             AND m.alm_wb_msg_status = 2
    //             AND c.wb_cus_category = 1
    //         ) AS message_status_2_count,
    //         (
    //             SELECT COUNT(*)
    //             FROM alm_whatsapp_cus_messages m2
    //             LEFT JOIN alm_whatsapp_customers c2 ON m2.alm_wb_msg_customer = c2.wb_cus_id
    //             WHERE m2.alm_wb_msg_source = 1
    //             AND m2.alm_wb_msg_status = 2
    //             AND m2.alm_wb_msg_created_on > NOW() - INTERVAL 30 MINUTE
    //             AND c2.wb_cus_category = 1
    //         ) AS count_last_30_minutes,
    //         (
    //             SELECT COUNT(*)
    //             FROM alm_whatsapp_cus_messages m3
    //             LEFT JOIN alm_whatsapp_customers c3 ON m3.alm_wb_msg_customer = c3.wb_cus_id
    //             WHERE m3.alm_wb_msg_source = 1
    //             AND m3.alm_wb_msg_status = 2
    //             AND m3.alm_wb_msg_created_on > NOW() - INTERVAL 1 HOUR
    //             AND c3.wb_cus_category = 1
    //         ) AS count_last_1_hour,
    //         (
    //             SELECT COUNT(*)
    //             FROM alm_whatsapp_cus_messages m4
    //             LEFT JOIN alm_whatsapp_customers c4 ON m4.alm_wb_msg_customer = c4.wb_cus_id
    //             WHERE m4.alm_wb_msg_source = 1
    //             AND m4.alm_wb_msg_status = 2
    //             AND m4.alm_wb_msg_created_on > NOW() - INTERVAL 3 HOUR
    //             AND c4.wb_cus_category = 1
    //         ) AS count_last_3_hours,
    //         (
    //             SELECT COUNT(*)
    //             FROM alm_whatsapp_cus_messages m5
    //             LEFT JOIN alm_whatsapp_customers c5 ON m5.alm_wb_msg_customer = c5.wb_cus_id
    //             WHERE m5.alm_wb_msg_source = 1
    //             AND m5.alm_wb_msg_status = 2
    //             AND m5.alm_wb_msg_created_on > NOW() - INTERVAL 1 DAY
    //             AND c5.wb_cus_category = 1
    //         ) AS count_last_1_day,
    //         (
    //             SELECT COUNT(*)
    //             FROM alm_whatsapp_cus_messages m6
    //             LEFT JOIN alm_whatsapp_customers c6 ON m6.alm_wb_msg_customer = c6.wb_cus_id
    //             WHERE m6.alm_wb_msg_source = 1
    //             AND m6.alm_wb_msg_status = 2
    //             AND m6.alm_wb_msg_created_on > NOW() - INTERVAL 4 DAY
    //             AND c6.wb_cus_category = 1
    //         ) AS count_last_4_days";

    //     $query = $this->db->query($sql);
    //     return $query->getRow();
    // }

}
