<?php

namespace App\Models\ServicePackage;

use CodeIgniter\Model;

class ServicePackageModelCodeLabourModel extends Model
{
    protected $DBGroup          = 'commonDB';
    protected $table            = 'sp_model_code_labour';
    protected $primaryKey       = 'spmcl_id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'spmcl_id',
        'brand_code',
        'model_code',
        'model_name',
        'family_code',
        'labour_rate',
        'spmcl_type',
        'spmcl_inc_pct',
        'spmcl_created_on',
        'spmcl_created_by',
        'spmcl_updated_on',
        'spmcl_updated_by',
        'spmcl_delete_flag'
    ];

    /**
     * Sync API data into DB (insert if new, update if exists by model_code)
     */
    public function syncApiData(array $apiData, int $userId): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);

        // 1. Fetch existing model_codes and their spmcl_inc_pct values
        $existingRows = $builder->select('model_code, spmcl_inc_pct')->get()->getResultArray();
        $existingCodes = array_column($existingRows, 'model_code');
        $existingPct = array_column($existingRows, 'spmcl_inc_pct', 'model_code'); // preserves current pct

        $insertData = [];
        $updateData = [];

        // 2. Split into insert / update
        foreach ($apiData as $row) {
            if (in_array($row['MODEL_CODE'], $existingCodes)) {
                // Update only necessary fields, keep spmcl_inc_pct unchanged
                $updateData[] = [
                    'brand_code'       => $row['BRAND_CODE'],
                    'model_name'       => $row['MODEL_NAME'],
                    'family_code'      => $row['FAMILY_CODE'],
                    'labour_rate'      => $row['LABOUR_RATE'],
                    'spmcl_updated_by' => $userId,
                    'spmcl_updated_on' => date("Y-m-d H:i:s"),
                    'model_code'       => $row['MODEL_CODE'], // required for updateBatch
                ];
            } else {
                // Insert new row, spmcl_inc_pct = 0.00
                $insertData[] = [
                    'brand_code'       => $row['BRAND_CODE'],
                    'model_code'       => $row['MODEL_CODE'],
                    'model_name'       => $row['MODEL_NAME'],
                    'family_code'      => $row['FAMILY_CODE'],
                    'labour_rate'      => $row['LABOUR_RATE'],
                    'spmcl_inc_pct'    => 0.00,
                    'spmcl_created_by' => $userId,
                    'spmcl_created_on' => date("Y-m-d H:i:s"),
                    'spmcl_updated_by' => $userId,
                    'spmcl_updated_on' => date("Y-m-d H:i:s"),
                ];
            }
        }

        // 3. Batch Insert
        if (!empty($insertData)) {
            $builder->insertBatch($insertData);
        }

        // 4. Batch Update
        if (!empty($updateData)) {
            $builder->updateBatch($updateData, 'model_code');
        }

        return [
            'inserted' => count($insertData),
            'updated'  => count($updateData),
        ];
    }
}
