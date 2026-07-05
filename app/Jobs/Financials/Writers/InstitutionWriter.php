<?php
/**
 * InstitutionWriter trait - ghi các chỉ số định chế tài chính vào $this->content.
 * Duyệt cửa sổ kỳ (config settings.limits) lùi dần, tính từng chỉ số theo closure
 * trong InstitutionCalculator::definitions(). Chỉ số không tính được ở mọi kỳ (thiếu
 * dữ liệu / mẫu số 0) sẽ bị bỏ qua.
 */
namespace App\Jobs\Financials\Writers;

use App\Jobs\Financials\Calculators\InstitutionCalculator;

trait InstitutionWriter
{
    /**
     * @param  int  $companyType  1=bank, 2=securities, 4=insurance
     */
    protected function writeInstitution(InstitutionCalculator $calculator, int $companyType, $year, $quarter)
    {
        $definitions = $calculator->definitions($companyType);
        $limit = (int) config('settings.limits', 5);

        foreach ($definitions as $def) {
            $values = [];
            $y = $year;
            $q = $quarter;
            $hasData = false;
            $usesTtm = !empty($def['usesTtm']);
            for ($i = 0; $i < $limit; $i++) {
                $raw = ($def['fn'])($y, $q, true);
                $value = is_null($raw) ? '' : round($raw, 4);
                if ($value !== '') {
                    $hasData = true;
                }
                $entry = [
                    'period' => $q != 0 ? "Q$q $y" : "$y",
                    'year' => $y,
                    'quarter' => $q,
                    'value' => $value,
                ];
                if ($usesTtm && $q != 0) {
                    $quarterOnlyRaw = ($def['fn'])($y, $q, false);
                    $quarterOnly = is_null($quarterOnlyRaw) ? null : round($quarterOnlyRaw, 4);
                    $entry['ttm'] = true;
                    $entry['valueNote'] = quarterOnlyNote($quarterOnly, $def['unit'], $q);
                }
                array_push($values, $entry);
                $previous = getPreviousPeriod($y, $q);
                $y = $previous['year'];
                $q = $previous['quarter'];
            }

            if (!$hasData) {
                continue; // Bỏ qua chỉ số không tính được ở bất kỳ kỳ nào.
            }

            array_push($this->content, [
                'name' => $def['name'],
                'alias' => $def['alias'],
                'group' => $def['group'],
                'unit' => $def['unit'],
                'description' => $def['desc'] ?? '',
                'values' => $values,
            ]);
        }

        return $this;
    }
}
