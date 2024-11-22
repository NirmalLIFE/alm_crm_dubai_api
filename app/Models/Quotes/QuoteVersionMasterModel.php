<?php

namespace App\Models\Quotes;

use CodeIgniter\Model;

class QuoteVersionMasterModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'quotes_version_master';
    protected $primaryKey       = 'qvm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['qvm_id','qvm_qt_id','qvm_version_no','qvm_spare_total','qvm_labour_total','qvm_sub_total','qvm_vat_total','qvm_discount','qvm_quote_label','qvm_recommended_flag','qvm_note','qvm_reference_version','qvm_created_by','qvm_created_on','qvm_updated_by','qvm_updated_on','qvm_delete_flag','qvm_terms_flag'];

    
}
