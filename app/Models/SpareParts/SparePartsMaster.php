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


    public function getSpareModels($pm_id)
    {
        return $this->db->query("
        SELECT 
            s.sp_spare_id,
            s.sp_spare_spmc_id,
            m.spmc_value,
            m.spmc_vin_no,
            m.spmc_model_year,
            m.spmc_variant,
            m.spmc_type,
            s.sp_spare_qty
        FROM sp_spares s
        INNER JOIN sp_model_code m 
            ON m.spmc_id = s.sp_spare_spmc_id
            AND m.spmc_delete_flag = 0
        WHERE s.sp_spare_pm_id = ?
        AND s.sp_spare_delete_flag = 0
    ", [$pm_id])->getResultArray();
    }

    public function getKmPricesByModels($modelIds)
    {
        if (empty($modelIds)) return [];

        $ids = implode(',', array_map('intval', $modelIds));

        return $this->db->query("
        SELECT 
            p.spkmp_id,
            p.spkmp_spmc_id,
            k.km_id,
            k.km_value,
            p.spkmp_markup_price,
            p.spkmp_display_price
        FROM sp_km_price_map p
        INNER JOIN kilometer_master k 
            ON k.km_id = p.spkmp_spkm_id
            AND k.km_delete_flag = 0
        WHERE p.spkmp_spmc_id IN ($ids)
        AND p.spkmp_delete_flag = 0
    ")->getResultArray();
    }


    public function getKmPricesBySpares($pm_id)
    {
        // parameterized query to avoid injection; pm_id applied as binding
        return $this->db->query("
        SELECT 
            s.sp_spare_id,
            s.sp_spare_spmc_id,
            p.spkmp_id,
            p.spkmp_spmc_id,
            k.km_id,
            k.km_value,
            p.spkmp_markup_price,
            p.spkmp_display_price
        FROM sp_spares s
        INNER JOIN sp_km_item_map im 
            ON im.spkm_item_id = s.sp_spare_id 
            AND im.spkm_item_type = 0
            AND im.spkm_delete_flag = 0
        INNER JOIN sp_km_price_map p
            ON p.spkmp_spkm_id = im.spkm_km_id
            AND p.spkmp_spmc_id = s.sp_spare_spmc_id
            AND p.spkmp_delete_flag = 0
        INNER JOIN kilometer_master k
            ON k.km_id = im.spkm_km_id
            AND k.km_delete_flag = 0
        WHERE s.sp_spare_pm_id = ?
        AND s.sp_spare_delete_flag = 0
    ", [$pm_id])->getResultArray();
    }
}
