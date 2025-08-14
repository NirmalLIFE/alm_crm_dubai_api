<?php

namespace App\Models\ServicePackage;

use CodeIgniter\Model;

class ServicePackageKmPriceModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'sp_km_price_map';
    protected $primaryKey       = 'spkmp_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'spkmp_spmc_id',
        'spkmp_spkm_id',
        'spkmp_markup_price',
        'spkmp_display_price',
        'spkmp_created_on',
        'spkmp_created_by',
        'spkmp_updated_on',
        'spkmp_updated_by',
        'spkmp_delete_flag'
    ];

    /**
     * Update spkmp_display_price for given model & KM IDs
     * Logic:
     * 1. Add price_diff to current display price
     * 2. Round to nearest threshold only if result is not already aligned
     * 3. Update all matching rows in a single query
     *
     * Example Payload:
     * multiple: 10
     * pm_brand: "1235"
     * pm_code: "0001803009"
     * pm_id: "59"
     * pm_new_price: "100.00"
     * pm_price: "80.00"
     * pm_sp_pm_id: "1"
     * rounding: "nearest_threshold"
     * threshold: 5
     *
     * @param array $spmcIds Numeric model IDs
     * @param array $kmIds   Numeric KM IDs
     * @param string|null $rounding 'nearest_threshold' supported
     * @param float|null $threshold Threshold for rounding
     * @param float $price_diff Per-unit price difference (new - old)
     * @return array Summary of operation
     */
    public function processUniqueIds($spmcIds, $kmIds, $rounding = null, $threshold = null, $price_diff = 0)
    {

        $summary = [
            'matched_model_codes' => [],
            'matched_rows' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        // --- Sanitize & validate IDs ---
        $spmcIds = array_values(array_unique(array_filter((array)$spmcIds, 'is_numeric')));
        $kmIds   = array_values(array_unique(array_filter((array)$kmIds, 'is_numeric')));
        $price_diff = (float)$price_diff;
        $threshold = is_numeric($threshold) ? (float)$threshold : 0.0;
        $now = date('Y-m-d H:i:s');
        $updatedBy = (int)(session()->get('user_id') ?? 0);

        if (empty($spmcIds) || empty($kmIds)) {
            $summary['errors'][] = 'Empty SPMC or KM ID list.';
            return $summary;
        }

        if (abs($price_diff) < 0.000001) {
            $summary['errors'][] = 'Price difference is zero — no update required.';
            return $summary;
        }

        // --- Fetch distinct model codes for the given SPMC & KM IDs ---
        try {
            $modelRows = $this->db->table($this->table)
                ->distinct()
                ->select('spkmp_spmc_id')
                ->whereIn('spkmp_spmc_id', $spmcIds)
                ->whereIn('spkmp_spkm_id', $kmIds)
                ->where('spkmp_delete_flag', 0)
                ->get()
                ->getResultArray();

            $summary['matched_model_codes'] = array_map('intval', array_column($modelRows, 'spkmp_spmc_id'));

            $cntRow = $this->db->table($this->table)
                ->select('COUNT(*) as cnt', false)
                ->whereIn('spkmp_spmc_id', $spmcIds)
                ->whereIn('spkmp_spkm_id', $kmIds)
                ->where('spkmp_delete_flag', 0)
                ->get()
                ->getRowArray();

            $summary['matched_rows'] = isset($cntRow['cnt']) ? (int)$cntRow['cnt'] : 0;
        } catch (\Throwable $e) {
            $summary['errors'][] = 'Error fetching matched model codes/count: ' . $e->getMessage();
            return $summary;
        }

        if ($summary['matched_rows'] === 0) return $summary;

        // --- Build SQL expression for updating display price ---
        try {
            if ($rounding === 'nearest_threshold' && $threshold > 0) {
                /*
                 * Logic:
                 * 1. Add price_diff to current display price
                 * 2. If new value MOD threshold = 0 -> keep it
                 * 3. Else round UP to nearest threshold using CEIL()
                 *
                 * Example:
                 * Current display: 1025
                 * Price diff: 20
                 * Threshold: 5
                 * 1025 + 20 = 1045
                 * 1045 % 5 = 0 -> no rounding, final = 1045
                 *
                 * Current display: 1025
                 * Price diff: 17
                 * Threshold: 5
                 * 1025 + 17 = 1042
                 * 1042 % 5 = 2 -> round up to 1045
                 */
                $expr = "
                    IF(
                        MOD(IFNULL(spkmp_display_price,0) + {$price_diff}, {$threshold}) = 0,
                        IFNULL(spkmp_display_price,0) + {$price_diff},
                        CEIL((IFNULL(spkmp_display_price,0) + {$price_diff}) / {$threshold}) * {$threshold}
                    )
                ";
            } else {
                // Simple addition without rounding
                $expr = "IFNULL(spkmp_display_price,0) + {$price_diff}";
            }

            // --- Execute single query update ---
            $this->db->transStart();
            $this->db->table($this->table)
                ->set('spkmp_display_price', $expr, false) // false = literal SQL
                ->set('spkmp_updated_on', $now)
                ->set('spkmp_updated_by', $updatedBy)
                ->whereIn('spkmp_spmc_id', array_map('intval', $spmcIds))
                ->whereIn('spkmp_spkm_id', array_map('intval', $kmIds))
                ->where('spkmp_delete_flag', 0)
                ->update();
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                $summary['errors'][] = 'DB transaction failed during update.';
                return $summary;
            }

            $summary['updated'] = $this->db->affectedRows();
        } catch (\Throwable $e) {
            $summary['errors'][] = 'Exception during update: ' . $e->getMessage();
        }

        return $summary;
    }
}
