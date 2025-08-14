<?php

namespace App\Models\SpareParts;

use CodeIgniter\Model;

class SparePartsMaster extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'parts_master';
    protected $primaryKey       = 'pm_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['pm_id', 'pm_code', 'pm_sp_pm_id', 'pm_name', 'pm_category', 'pm_unit_type', 'pm_brand', 'pm_price', 'pm_new_price', 'pm_price_requested_by', 'pm_created_on', 'pm_created_by', 'pm_updated_on', 'pm_updated_by', 'pm_delete_flag'];



    public function updatePricesByIds($id, $rounding, $threshold, $price_diff)
    {
        // Build query and fetch distinct pairs
        $results = $this->db->table('parts_master AS pm')
            ->distinct()
            ->select('spares.sp_spare_spmc_id, kmItemMap.spkm_km_id')
            ->join('sp_spares AS spares', 'spares.sp_spare_pm_id = pm.pm_id')
            ->join('sp_km_item_map AS kmItemMap', 'kmItemMap.spkm_item_id = spares.sp_spare_id')
            ->where('pm.pm_id', $id)
            ->where('pm.pm_delete_flag', 0)
            ->where('spares.sp_spare_delete_flag', 0)
            ->where('kmItemMap.spkm_delete_flag', 0)
            ->where('kmItemMap.spkm_item_type', 0)
            ->get()
            ->getResultArray();

        if (empty($results)) {
            // Nothing to process
            return [
                'spmcIds' => [],
                'kmIds' => [],
                'summary' => null
            ];
        }

        // Collect unique values
        $spmcIds = array_unique(array_column($results, 'sp_spare_spmc_id'));
        $kmIds   = array_unique(array_column($results, 'spkm_km_id'));

        if (empty($spmcIds) || empty($kmIds)) {
            return [
                'spmcIds' => [],
                'kmIds' => [],
            ];
        }


        // Return everything to the controller
        return [
            'spmcIds' => $spmcIds,
            'kmIds' => $kmIds,
            'rounding'  => $rounding,
            'threshold' => $threshold,
            'price_diff' => $price_diff,
        ];
    }
}
