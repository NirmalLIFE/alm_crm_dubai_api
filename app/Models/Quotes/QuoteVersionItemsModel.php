<?php

namespace App\Models\Quotes;

use CodeIgniter\Model;

class QuoteVersionItemsModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'quote_version_items';
    protected $primaryKey       = 'qtvi_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['qtvi_id', 'qtvi_qtv_id', 'qtvi_qtv_item_id', 'qtvi_qtv_item_price_type', 'qtvi_qtv_item_qty', 'qtvi_qtv_item_price', 'qtvi_item_group', 'qtvi_created_by', 'qtvi_created_on', 'qtvi_updated_by', 'qtvi_updated_on', 'qtvi_delete_flag'];
}
