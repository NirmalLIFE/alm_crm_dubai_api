<?php

namespace App\Controllers;

use App\Models\Quotes\QuotesItemModel;
use App\Models\Quotes\QuotesMasterModel;
use App\Models\Quotes\QuoteItemTypesModel;
use App\Models\Quotes\QuoteVersionMasterModel;
use App\Models\Quotes\QuoteVersionItemsModel;
use App\Models\Customer\CustomerMasterModel;
use App\Models\Commonutils\PartsInvoiceMaster;
use App\Models\Commonutils\PartsInvoiceItems;
use \Mpdf\Mpdf;
use App\Models\UserActivityLog;
use Config\Common;
use Config\Validation;

use App\Controllers\BaseController;

class HTMLPdfController extends BaseController
{
  public function __construct()
  {
    $db = \Config\Database::connect();
  }
  public function index()
  {
    $db = \Config\Database::connect();
    $id = $_GET['id'];
    $query   = $db->query("SELECT * FROM quotes_master where qt_id=" . $id);
    $customer = $query->getRow();
    $query1  = $db->query("SELECT * FROM quotes_items where qt_id=" . $id);
    $item = $query1->getResult();
    $data['cust'] =  $customer;
    $data['item'] =  $item;
    // echo '<pre>';
    // print_r($item);
    // echo '</pre>';
    // die;
    return view('index', $data);
  }

  function prinPDFVersion()
  {
    $mpdf = new \Mpdf\Mpdf();
    $id = base64_decode(base64_decode($_GET['id']));
    $v_id = base64_decode(base64_decode($_GET['v_id']));
    $type = base64_decode(base64_decode($_GET['type']));
    $qt_v_master = new QuoteVersionMasterModel();
    $modelQ = new QuotesMasterModel();
    $res_master = $modelQ->select('qt_id,qt_code,qt_type,qt_cus_name,qt_cus_contact,qt_vin,qt_reg_no,qt_chasis,qt_make,qt_odometer,qt_service_adv,qt_parts_adv,qt_jc_no,qt_cus_id,qt_lead_id,qt_type,qt_amount,qt_tax,qt_total,part_code_print,avail_print,part_type_print,brand_print,qt_camp_id,sau.us_firstname as sa_name,sau.us_email as sa_email,pau.us_firstname as pa_name,pau.us_email as pa_email')
      ->where('qt_id', $id)
      ->where('qt_delete_flag', 0)
      ->join('users sau', 'sau.us_id=qt_service_adv', 'left')
      ->join('users pau', 'pau.us_id=qt_parts_adv', 'left')
      ->join('campaign', 'campaign.camp_id=qt_camp_id', 'left')
      ->orderBy('qt_id', 'desc')
      ->first();
    $res = $qt_v_master->where('qvm_delete_flag', 0)->where('qvm_id', $v_id)->first();
    if ($res > 0) {
      $qt_v_items = new QuoteVersionItemsModel();
      $data["qt_versions_items"] =
        $qt_v_items->where('qtvi_delete_flag', 0)
        ->where('qtvi_qtv_id',  $v_id)
        ->join('quotes_items qi', 'qi.item_id=qtvi_qtv_item_id', 'left')
        ->join('quote_item_types qit', 'qit.qit_id=qtvi_qtv_item_price_type', 'left')
        ->join('brand_list as bl', 'bl.brand_id=qit.qit_brand', 'left')
        ->findAll();

      $temp_array = array();
      $i = 0;
      $key_array = array();
      $grouped_items = [];
      foreach ($data["qt_versions_items"] as $val) {
        if (!in_array($val['qtvi_item_group'], $key_array)) {
          $key_array[$i] = $val['qtvi_item_group'];
          $temp_array[$i] = $val;
        }
        $i++;
      }
      foreach ($temp_array as $group_ar) {
        $temp_grp = [];
        foreach ($data["qt_versions_items"] as $val) {
          if ($val['qtvi_item_group'] == $group_ar['qtvi_item_group']) {
            if ($val['qtvi_item_group'] != 0) {
              array_push($temp_grp, $val);
            }
          }
        }
        array_push($grouped_items, $temp_grp);
      }

      if ($type != 1) {
        foreach ($grouped_items as &$group_items) {
          $has_item_type_2 = false;
          $item_type_3_index = null;

          // Combine checks for efficiency
          foreach ($group_items as $index => $item) {
            if ($item['item_type'] == 2) {
              $has_item_type_2 = true;
              break;
            } elseif ($item['item_type'] == 3) {
              $item_type_3_index = $index;
            }
          }

          // Move item type 3 to top if necessary
          if (!$has_item_type_2 && $item_type_3_index !== null) {
            $item_type_3_item = $group_items[$item_type_3_index];
            $item_type_3_item['item_priority'] = 'High';
            unset($group_items[$item_type_3_index]);
            array_unshift($group_items, $item_type_3_item);
          }

          // Sort items within the group
          usort($group_items, function ($a, $b) {

            // Prioritize labor items and sort by priority
            if ($a['item_type'] == 2 && $b['item_type'] == 2) {
              return $this->getPriorityOrder($a['item_priority']) - $this->getPriorityOrder($b['item_priority']);
            } elseif ($a['item_type'] == 2) {
              return -1;
            } elseif ($b['item_type'] == 2) {
              return 1;
            }

            // Prioritize item type 3
            if ($a['item_type'] == 3 && $b['item_type'] != 3) {
              return -1;
            } elseif ($b['item_type'] == 3 && $a['item_type'] != 3) {
              return 1;
            }

            // Maintain original order for other item types
            return 0;
          });
        }
        unset($group_items);

        //Sort groups based on labor item priority
        usort($grouped_items, function ($a, $b) {
          $a_priority = PHP_INT_MAX;
          $b_priority = PHP_INT_MAX;

          foreach ($a as $item) {
            if ($item['item_type'] == 2 || $item['item_type'] == 3) {
              $a_priority = min($a_priority, $this->getPriorityOrder($item['item_priority']));
            }
          }

          foreach ($b as $item) {
            if ($item['item_type'] == 2 || $item['item_type'] == 3) {
              $b_priority = min($b_priority, $this->getPriorityOrder($item['item_priority']));
            }
          }

          return $a_priority - $b_priority;
        });


        // $group_priorities = [];
        // foreach ($grouped_items as $index => $group) {
        //   $priority = 0;
        //   foreach ($group as $item) {
        //     if ($item['item_type'] == 2) {
        //       $priority = $this->getPriorityOrder($item['item_priority']);
        //       break;
        //     } elseif ($item['item_type'] == 3) {
        //       $priority = PHP_INT_MAX; // A very large value to place these groups at the end
        //       break;
        //     }
        //   }
        //   $group_priorities[$index] = $priority;
        // }

        // // Sort groups based on assigned priorities in descending order
        // array_multisort($group_priorities, SORT_ASC, $grouped_items);


      }





      $data["qt_group"] = $grouped_items;
      $data["qt_versions"] = $res;
      $data["qt_master"] = $res_master;
      $data["type"] = $type;
      // echo '<pre>';
      // print_r($grouped_items);
      // echo '</pre>';
      // exit;
      $common = new Common();
      $mpdf->SetHTMLHeader('<img src=' . $common->getPrintHeaderImage() . ' />');
      $mpdf->SetHTMLFooter('<img src=' . $common->getPrintFooterImage() . ' />');
      $mpdf->shrink_tables_to_fit = 1;
      $html = view('quoteView', $data);
      $mpdf->AddPage(
        '', // L - landscape, P - portrait 
        '',
        '',
        '',
        '',
        0, // margin_left
        0, // margin right
        35, // margin top
        30, // margin bottom
        5, // margin header
        0
      ); // margin footer
      $mpdf->WriteHTML($html);
      $mpdf->Output($res_master['qt_code'] . "_" . $v_id . ".pdf", 'D');
    } else {
      $data["qt_versions"] = [];
    }
  }

  function getPriorityOrder($priority)
  {
    switch ($priority) {
      case 'High':
        return 1;
      case 'Medium':
        return 2;
      case 'Low':
        return 3;
      default:
        return 4;
    }
  }








  function printComboPDFVersion()
  {
    $mpdf = new \Mpdf\Mpdf();
    $id = base64_decode(base64_decode($_GET['id']));
    $v1_id = base64_decode(base64_decode($_GET['v1_id']));
    $v2_id = base64_decode(base64_decode($_GET['v2_id']));
    $v1_flag = base64_decode(base64_decode($_GET['v1_flag']));
    $v2_flag = base64_decode(base64_decode($_GET['v2_flag']));
    echo "v1_recommended_flag: " . $v1_flag . "<br>";
    echo "v2_recommended_flag: " . $v2_flag . "<br>";
    $qt_v_master = new QuoteVersionMasterModel();
    $modelQ = new QuotesMasterModel();
    $res_master = $modelQ->select('qt_id,qt_code,qt_type,qt_cus_name,qt_cus_contact,qt_vin,qt_reg_no,qt_chasis,qt_make,qt_odometer,qt_service_adv,qt_parts_adv,qt_jc_no,qt_cus_id,qt_lead_id,qt_type,qt_amount,qt_tax,qt_total,part_code_print,avail_print,part_type_print,brand_print,qt_camp_id,sau.us_firstname as sa_name,sau.us_email as sa_email,pau.us_firstname as pa_name,pau.us_email as pa_email')
      ->where('qt_id', $id)
      ->where('qt_delete_flag', 0)
      ->join('users sau', 'sau.us_id=qt_service_adv', 'left')
      ->join('users pau', 'pau.us_id=qt_parts_adv', 'left')
      ->join('campaign', 'campaign.camp_id=qt_camp_id', 'left')
      ->orderBy('qt_id', 'desc')
      ->first();
    $res1 = $qt_v_master->where('qvm_delete_flag', 0)->where('qvm_id', $v1_id)->first();
    $res2 = $qt_v_master->where('qvm_delete_flag', 0)->where('qvm_id', $v2_id)->first();
    if ($res1 > 0) {
      $qt_v_items = new QuoteVersionItemsModel();
      $data["qt_versions_1_items"] =
        $qt_v_items->where('qtvi_delete_flag', 0)
        ->where('qtvi_qtv_id',  $v1_id)
        ->join('quotes_items qi', 'qi.item_id=qtvi_qtv_item_id', 'left')
        ->join('quote_item_types qit', 'qit.qit_id=qtvi_qtv_item_price_type', 'left')
        ->join('brand_list as bl', 'bl.brand_id=qit.qit_brand', 'left')
        ->findAll();

      $temp_array = array();
      $i = 0;
      $key_array = array();
      $grouped_items_1 = [];
      foreach ($data["qt_versions_1_items"] as $val) {
        if (!in_array($val['qtvi_item_group'], $key_array)) {
          $key_array[$i] = $val['qtvi_item_group'];
          $temp_array[$i] = $val;
        }
        $i++;
      }
      foreach ($temp_array as $group_ar) {
        $temp_grp = [];
        foreach ($data["qt_versions_1_items"] as $val) {
          if ($val['qtvi_item_group'] == $group_ar['qtvi_item_group']) {
            if ($val['qtvi_item_group'] != 0) {
              array_push($temp_grp, $val);
            }
          }
        }
        array_push($grouped_items_1, $temp_grp);
      }
      $data["qt_group1"] = $grouped_items_1;
      $data["qt_version1"] = $res1;
      $data["qt_version1_flag"] = $v1_flag;
    } else {
      $data["qt_group1"] = [];
      $data["qt_version1"] = [];
    }
    if ($res2 > 0) {
      $qt_v_items = new QuoteVersionItemsModel();
      $data["qt_versions_2_items"] =
        $qt_v_items->where('qtvi_delete_flag', 0)
        ->where('qtvi_qtv_id',  $v2_id)
        ->join('quotes_items qi', 'qi.item_id=qtvi_qtv_item_id', 'left')
        ->join('quote_item_types qit', 'qit.qit_id=qtvi_qtv_item_price_type', 'left')
        ->join('brand_list as bl', 'bl.brand_id=qit.qit_brand', 'left')
        ->findAll();

      $temp_array = array();
      $i = 0;
      $key_array = array();
      $grouped_items_2 = [];
      foreach ($data["qt_versions_2_items"] as $val) {
        if (!in_array($val['qtvi_item_group'], $key_array)) {
          $key_array[$i] = $val['qtvi_item_group'];
          $temp_array[$i] = $val;
        }
        $i++;
      }
      foreach ($temp_array as $group_ar) {
        $temp_grp = [];
        foreach ($data["qt_versions_2_items"] as $val) {
          if ($val['qtvi_item_group'] == $group_ar['qtvi_item_group']) {
            if ($val['qtvi_item_group'] != 0) {
              array_push($temp_grp, $val);
            }
          }
        }
        array_push($grouped_items_2, $temp_grp);
      }
      $data["qt_group2"] = $grouped_items_2;
      $data["qt_version2"] = $res2;
      $data["qt_version2_flag"] = $v2_flag;
    } else {
      $data["qt_group2"] = [];
      $data["qt_version2"] = [];
    }
    $data["qt_master"] = $res_master;
    $common = new Common();
    $mpdf->SetHTMLHeader('<img src=' . $common->getPrintHeaderImage() . ' />');
    $mpdf->SetHTMLFooter('<img src=' . $common->getPrintFooterImage() . ' />');
    $mpdf->shrink_tables_to_fit = 2;
    $html = view('multiQuoteView', $data);
    $mpdf->AddPage(
      '', // L - landscape, P - portrait 
      'A4',
      '',
      '',
      '',
      0, // margin_left
      0, // margin right
      35, // margin top
      30, // margin bottom
      5, // margin header
      5
    ); // margin footer
    $mpdf->WriteHTML($html);
    $mpdf->Output($res_master['qt_code'] . "_" . $v1_id . "_" . $v2_id . ".pdf", 'D');
  }




  function convertHTMLToPdf()
  {
    $mpdf = new \Mpdf\Mpdf();
    $db = \Config\Database::connect();
    $model = new QuotesMasterModel();
    $modelC = new CustomerMasterModel();
    $modelQ = new QuotesItemModel();
    $id = $_GET['id'];
    // $query   = $db->query("SELECT * FROM quot_customer where cus_id=".$id."LEFT JOIN");
    $customer = $model->select('qt_id,qt_code,qt_type,qt_cus_name,qt_cus_contact,qt_vin,qt_reg_no,qt_chasis,qt_make,qt_odometer,qt_service_adv,qt_parts_adv,qt_jc_no,qt_cus_id,qt_lead_id,qt_type,qt_amount,qt_tax,qt_created_on,qt_total,part_code_print,avail_print,part_type_print,brand_print,qt_camp_id,sau.us_firstname as sa_name,sau.us_email as sa_email,pau.us_firstname as pa_name,pau.us_email as pa_email')
      ->where('qt_id', $id)
      ->where('qt_delete_flag', 0)
      ->join('users sau', 'sau.us_id=qt_service_adv', 'left')
      ->join('users pau', 'pau.us_id=qt_parts_adv', 'left')
      ->orderBy('qt_id', 'desc')
      ->first();

    $item = $modelQ->select('*')
      ->where('qt_id', $id)
      ->where('item_delete_flag', 0)
      ->findAll();

    $data['cust'] =  $customer;
    $data['item'] =  $item;
    // $dompdf->loadHtml(view('index'));
    $common = new Common();
    $mpdf->SetHTMLHeader('<img src=' . $common->getPrintHeaderImage() . ' />');
    $mpdf->SetHTMLFooter('<img src=' . $common->getPrintFooterImage() . ' />');
    $mpdf->shrink_tables_to_fit = 1;
    $html = view('index', $data);
    $mpdf->AddPage(
      '', // L - landscape, P - portrait 
      'A4',
      '',
      '',
      '',
      0, // margin_left
      0, // margin right
      35, // margin top
      30, // margin bottom
      5, // margin header
      2
    );
    $mpdf->WriteHTML($html);
    $mpdf->Output($customer['qt_code'] . ".pdf", 'D');
  }
  function mPdftest()
  {
    $mpdf = new \Mpdf\Mpdf();
    $db = \Config\Database::connect();
    $model = new QuotesMasterModel();
    $id = $_GET['id'];
    // $query   = $db->query("SELECT * FROM quot_customer where cus_id=".$id."LEFT JOIN");
    $customer = $model->select('cus_id,cust_name,chasis_no,jc_no,reg_no,quot_total,tax_percent,tax_amount,grand_total,cus_created_on,cus_created_by,cus_updated_on,
        cus_updated_by,odometer,service_advisor,parts_advisor,contact,make,qt_sequence,sau.us_name as sa_name,sau.us_email as sa_email,pau.us_name as pa_name,pau.us_email as pa_email')
      ->join('quot_users sau', 'sau.us_id=service_advisor', 'left')
      ->join('quot_users pau', 'pau.us_id=parts_advisor', 'left')
      ->where('cus_id', '1')->first();
    $query1  = $db->query("SELECT * FROM quot where qt_cus_id='1' AND delete_flag='0'");
    $item = $query1->getResult();
    $data['cust'] =  $customer;
    $data['item'] =  $item;
    $html = view('index', $data);
    $mpdf->WriteHTML($html);
    $mpdf->Output($customer['qt_sequence'] . ".pdf", 'D');
  }
  public function insertUserLog($log)
  {
    $common = new Common();
    $valid = new Validation();
    $heddata = $this->request->headers();
    //  $tokendata=$common->decode_jwt_token($valid->getbearertoken($heddata['Authorization']));
    $logmodel = new UserActivityLog();
    $ip = $this->request->getIPAddress();
    $indata = [
      //  'log_user'    => $tokendata['uid'],
      'log_ip'   =>  $ip,
      'log_activity' => $log
    ];
    $results = $logmodel->insert($indata);
  }

  function printSpareInvoice()
  {
    $mpdf = new \Mpdf\Mpdf();
    $id = base64_decode($_GET['id']);
    $invMaster = new PartsInvoiceMaster();
    $invItems = new PartsInvoiceItems();
    $invoices = $invMaster->join("cust_data_laabs", 'cust_data_laabs.customer_code=inv_customer_id', 'left')
      ->join("cust_veh_data_laabs", "vehicle_id=inv_vehicle_id", 'left')
      ->join("users", "us_id=inv_created_by", 'left')
      ->where("inv_delete_flag", 0)->where("inv_id", $id)->first();
    $invoices['items'] = $invItems->where("inv_item_delete_flag", 0)
      ->where("inv_item_master",  $id)->findAll();
    if ($invoices) {

      $data["invoice"] = $invoices;
      $common = new Common();
      $mpdf->SetHTMLHeader('<img src=' . $common->getHeaderInvoiceImage() . ' />');
      $mpdf->SetHTMLFooter('<img src=' . $common->getPrintFooterImage() . ' />');
      $mpdf->shrink_tables_to_fit = 1;
      $html = view('spareInvoiceView', $data);
      $mpdf->AddPage(
        '', // L - landscape, P - portrait 
        '',
        '',
        '',
        '',
        0, // margin_left
        0, // margin right
        35, // margin top
        30, // margin bottom
        5, // margin header
        0
      ); // margin footer
      $mpdf->WriteHTML($html);
      $mpdf->Output($invoices['inv_nm_id'] . ".pdf", 'D');
    } else {
      $data["qt_versions"] = [];
    }
  }
}
