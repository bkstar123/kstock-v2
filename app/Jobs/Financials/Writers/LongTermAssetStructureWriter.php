<?php
/**
 * LongTermAssetStructureWriter trait
 *
 * @author: tuanha
 * @date: 14-Aug-2022
 */
namespace App\Jobs\Financials\Writers;

use App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator;

trait LongTermAssetStructureWriter
{
    /**
     * Write Long Term Asset / Total Asset Ratio
     *
     * @param \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeLongTermAssetToTotalAssetRatio(LongTermAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateLongTermAssetToTotalAssetRatio($year, $quarter)->longTermAssetToTotalAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tài sản dài hạn/Tổng tài sản',
            'alias' => 'Long Term Assets/Total Assets',
            'group' => 'Chỉ số Cơ cấu tài sản dài hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng tài sản dài hạn trên tổng tài sản của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Long Term Receivable / Long Term Asset Ratio
     *
     * @param \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeLongTermReceivableToLongTermAssetRatio(LongTermAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateLongTermReceivableToLongTermAssetRatio($year, $quarter)->longTermReceivableToLongTermAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Các khoản phải thu dài hạn/Tài sản dài hạn',
            'alias' => 'Long Term Receivables/Long Term Assets',
            'group' => 'Chỉ số Cơ cấu tài sản dài hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng các khoản phải thu dài hạn trên tổng tài sản dài hạn của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Fixed Asset / Long Term Asset Ratio
     *
     * @param \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeFixedAssetToLongTermAssetRatio(LongTermAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateFixedAssetToLongTermAssetRatio($year, $quarter)->fixedAssetToLongTermAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tài sản cố định/Tài sản dài hạn',
            'alias' => 'Fixed Assets/Long Term Assets',
            'group' => 'Chỉ số Cơ cấu tài sản dài hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng tài sản cố định trên tổng tài sản dài hạn của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Tangible Fixed Asset / Fixed Asset Ratio
     *
     * @param \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeTangibleFixedAssetToFixedAssetRatio(LongTermAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateTangibleFixedAssetToFixedAssetRatio($year, $quarter)->tangibleFixedAssetToFixedAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tài sản cố định hữu hình/Tài sản cố định',
            'alias' => 'Tangible Fixed Assets/Fixed Assets',
            'group' => 'Chỉ số Cơ cấu tài sản dài hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng tài sản cố định hữu hình trên tổng tài sản của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Financial Lending Asset / Fixed Asset Ratio
     *
     * @param \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeFinancialLendingAssetToFixedAssetRatio(LongTermAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateFinancialLendingAssetToFixedAssetRatio($year, $quarter)->financialLendingAssetToFixedAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tài sản cố định cho thuê tài chính/Tài sản cố định',
            'alias' => 'Financial Lending Fixed Assets/Fixed Assets',
            'group' => 'Chỉ số Cơ cấu tài sản dài hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng tài sản cố định cho thuê tài chính trên tổng tài sản cố định của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Intangible Asset / Fixed Asset Ratio
     *
     * @param \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeIntangibleAssetToFixedAssetRatio(LongTermAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateIntangibleAssetToFixedAssetRatio($year, $quarter)->intangibleAssetToFixedAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tài sản cố định vô hình/Tài sản cố định',
            'alias' => 'Intangible Fixed Assets/Fixed Assets',
            'group' => 'Chỉ số Cơ cấu tài sản dài hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng tài sản cố định vô hình trên tổng tài sản cố định của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Construction In Progress / Fixed Asset Ratio
     *
     * @param \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeInvestingRealEstateToLongTermAssetRatio(LongTermAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateInvestingRealEstateToLongTermAssetRatio($year, $quarter)->investingRealEstateToLongTermAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Bất động sản đầu tư/Tài sản dài hạn',
            'alias' => 'Investing Real Estates/Long Term Assets',
            'group' => 'Chỉ số Cơ cấu tài sản dài hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng bất động sản đầu tư trên tổng tài sản dài hạns của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Construction In Progress / Fixed Asset Ratio
     *
     * @param \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeConstructionInProgressToLongTermAssetRatio(LongTermAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateConstructionInProgressToLongTermRatio($year, $quarter)->constructionInProgressToLongTermAssetRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Tài sản dở dang dài hạn/Tài sản dài hạn',
            'alias' => 'Long Term Assets in Progress/Long Term Assets',
            'group' => 'Chỉ số Cơ cấu tài sản dài hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng chi phí xây dựng cơ bản dở dang trên tổng tài sản dài hạn của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Construction In Progress / Fixed Asset Ratio
     *
     * @param \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeLongTermFinancialInvestingToLongTermRatio(LongTermAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateLongTermFinancialInvestingToLongTermRatio($year, $quarter)->longTermFinancialInvestingToLongTermRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Các khoản đầu tư tài chính dài hạn/Tài sản dài hạn',
            'alias' => 'Long Term Financial Investing/Long Term Assets',
            'group' => 'Chỉ số Cơ cấu tài sản dài hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng các khoản đầu tư tài chính dài hạn trên tổng tài sản dài hạn của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }

    /**
     * Write Construction In Progress / Fixed Asset Ratio
     *
     * @param \App\Jobs\Financials\Calculators\LongTermAssetStructureCalculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    public function writeOtherLongTermAssetToLongTermRatio(LongTermAssetStructureCalculator $calculator, $year, $quarter)
    {
        $values = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            array_push($values, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => $calculator->calculateOtherLongTermAssetToLongTermRatio($year, $quarter)->otherLongTermAssetToLongTermRatio
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Các tài sản dài hạn khác/Tài sản dài hạn',
            'alias' => 'Other Long Term Assets/Long Term Assets',
            'group' => 'Chỉ số Cơ cấu tài sản dài hạn',
            'unit' => '%',
            'description' => 'Phản ánh tỉ trọng các tài sản dài hạn khác trên tổng tài sản dài hạn của doanh nghiệp',
            'values' => $values
        ]);
        return $this;
    }
}
