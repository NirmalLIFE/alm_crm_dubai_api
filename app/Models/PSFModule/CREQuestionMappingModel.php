<?php

namespace App\Models\PSFModule;

use CodeIgniter\Model;

class CREQuestionMappingModel extends Model
{
    protected $table            = 'cre_question_mapping';
    protected $primaryKey       = 'cq_id';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['cq_id','cq_psfid','cq_qid','cq_answer','cq_user_id','cq_created_by'];

}
