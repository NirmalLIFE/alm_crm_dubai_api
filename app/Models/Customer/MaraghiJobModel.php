<?php

namespace App\Models\Customer;

use CodeIgniter\Model;

class MaraghiJobModel extends Model
{
    protected $table            = 'cust_job_data_laabs';
    protected $primaryKey       = 'job_no';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['job_no',
                                    'customer_no',
                                    'vehicle_id',
                                    'car_reg_no',
                                    'job_open_date',
                                    'job_close_date',
                                    'received_date',
                                    'promised_date',
                                    'extended_promise_date',
                                    'speedometer_reading',
                                    'delivered_date',
                                    'invoice_no',
                                    'invoice_date',
                                    'sa_emp_id',
                                    'user_name',
                                    'job_status',
                                    'jb_sub_status'
                                ];

}
