<?php

namespace App\Models\Quotes;

use CodeIgniter\Model;

class QuotesItemModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'quotes_items';
    protected $primaryKey       = 'item_id ';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'item_id',
        'item_name',
        'item_code',
        'item_note',
        'item_type',
        'item_qty',
        'item_condition',
        'item_priority',
        'item_special_avail',
        'unit_price',
        'disc_amount',
        'qt_id',
        'item_seq',
        'item_group',
        'item_created_on',
        'item_created_by',
        'item_updated_on',
        'item_updated_by',
        'item_delete_flag'
    ];
}
