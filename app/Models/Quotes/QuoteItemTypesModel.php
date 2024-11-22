<?php

namespace App\Models\Quotes;

use CodeIgniter\Model;

class QuoteItemTypesModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'quote_item_types';
    protected $primaryKey       = 'qit_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'qit_id', 'qit_item_id', 'qit_qt_id', 'qit_brand', 'qit_type', 'qit_availability', 'qit_unit_price', 'qit_discount', 'qit_created_on', 'qit_created_by', 'qit_updated_on', 'qit_delete_flag', 'qit_old_margin_price',
        'qit_margin_price',
    ];
}
