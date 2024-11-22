<?php

namespace App\Models;

use CodeIgniter\Model;

class AccessFeaturesModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'features_list';
    protected $primaryKey       = 'feat_id';
    protected $useAutoIncrement = true;   
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ft_id','ft_name','ft_description','ft_created_on','ft_created_by','ft_updated_on','	ft_updated_by','ft_deleteflag'];    
   
}
