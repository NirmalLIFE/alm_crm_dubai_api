<?php

namespace App\Models\PSFModule;

use CodeIgniter\Model;

class CREQuestionMasterModel extends Model
{
    protected $table            = 'cre_question_master';
    protected $primaryKey       = 'cqm_id';
    protected $allowedFields    = ['cqm_id','cqm_name','cqm_responseFlag','cqm_created_by','cqm_updated_by','cqm_delete_flag'];

}