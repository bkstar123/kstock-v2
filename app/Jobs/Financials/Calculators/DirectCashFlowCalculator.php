<?php
/**
 * DirectCashFlowCalculator
 *
 * Bản LCTT theo phương pháp TRỰC TIẾP dùng bộ item ID hoàn toàn khác phương pháp gián
 * tiếp (vd id '104' ở gián tiếp là dòng tiền thuần HĐKD, còn ở trực tiếp lại là "Tiền chi
 * trả lãi vay"). Class này kế thừa CashFlowCalculator để giữ nguyên tên thuộc tính công
 * khai + chữ ký mà CashFlowWriter đang dùng, chỉ ghi đè cách LẤY DỮ LIỆU: thay vì literal
 * id (chỉ đúng với phương pháp gián tiếp), tra theo TÊN dòng trong đúng mục cha (I/II/III —
 * luôn cố định id '1'/'2'/'3' theo mẫu B03-DN dù trực tiếp hay gián tiếp), để không phụ
 * thuộc vào số lượng dòng chi tiết (khác nhau giữa các công ty).
 *
 * @author: kstock
 */
namespace App\Jobs\Financials\Calculators;

class DirectCashFlowCalculator extends CashFlowCalculator
{
    /** Các item con của một mục gốc (I/II/III), theo parent_id. */
    private function childrenOf($statement, $parentId)
    {
        return $statement->getItems()->filter(function ($it) use ($parentId) {
            return (string) $it->parent_id === (string) $parentId;
        })->values();
    }

    /** Tìm item con đầu tiên có tên chứa $needle, trong mục cha $parentId. */
    private function findChildByName($statement, $parentId, $needle)
    {
        return $this->childrenOf($statement, $parentId)->first(function ($it) use ($needle) {
            return mb_stripos($it->name, $needle) !== false;
        });
    }

    private function cfoItem($statement)
    {
        return $this->findChildByName($statement, '1', 'Lưu chuyển tiền thuần');
    }

    private function capexOutItem($statement)
    {
        return $this->findChildByName($statement, '2', 'mua sắm, xây dựng TSCĐ');
    }

    private function capexInItem($statement)
    {
        return $this->findChildByName($statement, '2', 'thanh lý, nhượng bán TSCĐ');
    }

    private function cffItem($statement)
    {
        return $this->findChildByName($statement, '3', 'Lưu chuyển tiền thuần');
    }

    private function paidInterestItem($statement)
    {
        return $this->findChildByName($statement, '1', 'chi trả lãi vay');
    }

    private function paidTaxItem($statement)
    {
        return $this->findChildByName($statement, '1', 'nộp thuế thu nhập doanh nghiệp');
    }

    /** CFO tại kỳ, hoặc null nếu không xác định được dòng "Lưu chuyển tiền thuần HĐKD". */
    private function cfoValue($statement, $year, $quarter)
    {
        $item = $this->cfoItem($statement);
        return $item ? $item->getValue($year, $quarter) : null;
    }

    /** FCF = CFO + chi mua sắm TSCĐ (đã âm) + thu thanh lý TSCĐ. */
    private function fcfValue($statement, $year, $quarter)
    {
        $cfo = $this->cfoValue($statement, $year, $quarter);
        if ($cfo === null) {
            return null;
        }
        $capexOut = $this->capexOutItem($statement);
        $capexIn = $this->capexInItem($statement);
        $out = $capexOut ? $capexOut->getValue($year, $quarter) : 0;
        $in = $capexIn ? $capexIn->getValue($year, $quarter) : 0;
        return $cfo - abs($out) + $in;
    }

    /** CFO quy đổi năm (TTM nếu là báo cáo quý) — dùng cho các tỷ số flow/stock. */
    private function cfoValueTTM($statement, $year, $quarter)
    {
        $item = $this->cfoItem($statement);
        return $item ? $this->ttmOrAnnual($item, $year, $quarter) : null;
    }

    /** FCF quy đổi năm (TTM nếu là báo cáo quý). */
    private function fcfValueTTM($statement, $year, $quarter)
    {
        $cfo = $this->cfoValueTTM($statement, $year, $quarter);
        if ($cfo === null) {
            return null;
        }
        $capexOut = $this->capexOutItem($statement);
        $capexIn = $this->capexInItem($statement);
        $out = $capexOut ? $this->ttmOrAnnual($capexOut, $year, $quarter) : 0;
        $in = $capexIn ? $this->ttmOrAnnual($capexIn, $year, $quarter) : 0;
        return $cfo - abs($out) + $in;
    }

    public function calculateLiabilityCoverageRatioByCFO($year = null, $quarter = null)
    {
        $this->liabilityCoverageRatioByCFO = null;
        $this->liabilityCoverageRatioByCFOQuarterOnly = null;
        if (!empty($this->financialStatement->cash_flow_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $statement = $this->financialStatement->cash_flow_statement;
            $cfo = $this->cfoValueTTM($statement, $selectedYear, $selectedQuarter);
            $cfoQuarter = $this->cfoValue($statement, $selectedYear, $selectedQuarter);
            $average_liabilities = $this->financialStatement->balance_statement->getItem('301')->getAverageValue($selectedYear, $selectedQuarter);
            if ($cfo !== null && $average_liabilities != 0) {
                $this->liabilityCoverageRatioByCFO = round($cfo / $average_liabilities, 4);
            }
            if ($cfoQuarter !== null && $average_liabilities != 0) {
                $this->liabilityCoverageRatioByCFOQuarterOnly = round($cfoQuarter / $average_liabilities, 4);
            }
        }
        return $this;
    }

    public function calculateCurrentLiabilityCoverageRatioByCFO($year = null, $quarter = null)
    {
        $this->currentLiabilityCoverageRatioByCFO = null;
        $this->currentLiabilityCoverageRatioByCFOQuarterOnly = null;
        if (!empty($this->financialStatement->cash_flow_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $statement = $this->financialStatement->cash_flow_statement;
            $cfo = $this->cfoValueTTM($statement, $selectedYear, $selectedQuarter);
            $cfoQuarter = $this->cfoValue($statement, $selectedYear, $selectedQuarter);
            $average_current_liabilities = $this->financialStatement->balance_statement->getItem('30101')->getAverageValue($selectedYear, $selectedQuarter);
            if ($cfo !== null && $average_current_liabilities != 0) {
                $this->currentLiabilityCoverageRatioByCFO = round($cfo / $average_current_liabilities, 4);
            }
            if ($cfoQuarter !== null && $average_current_liabilities != 0) {
                $this->currentLiabilityCoverageRatioByCFOQuarterOnly = round($cfoQuarter / $average_current_liabilities, 4);
            }
        }
        return $this;
    }

    public function calculateLongTermLiabilityCoverageRatioByCFO($year = null, $quarter = null)
    {
        $this->longTermLiabilityCoverageRatioByCFO = null;
        $this->longTermLiabilityCoverageRatioByCFOQuarterOnly = null;
        if (!empty($this->financialStatement->cash_flow_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $statement = $this->financialStatement->cash_flow_statement;
            $cfo = $this->cfoValueTTM($statement, $selectedYear, $selectedQuarter);
            $cfoQuarter = $this->cfoValue($statement, $selectedYear, $selectedQuarter);
            $average_long_term_liabilitiess = $this->financialStatement->balance_statement->getItem('30102')->getAverageValue($selectedYear, $selectedQuarter);
            if ($cfo !== null && $average_long_term_liabilitiess != 0) {
                $this->longTermLiabilityCoverageRatioByCFO = round($cfo / $average_long_term_liabilitiess, 4);
            }
            if ($cfoQuarter !== null && $average_long_term_liabilitiess != 0) {
                $this->longTermLiabilityCoverageRatioByCFOQuarterOnly = round($cfoQuarter / $average_long_term_liabilitiess, 4);
            }
        }
        return $this;
    }

    public function calculateCFOToRevenue($year = null, $quarter = null)
    {
        $this->cFOToRevenue = null;
        if (!empty($this->financialStatement->cash_flow_statement) && !empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $cfo = $this->cfoValue($this->financialStatement->cash_flow_statement, $selectedYear, $selectedQuarter);
            $net_revenue = $this->financialStatement->income_statement->getItem('3')->getValue($selectedYear, $selectedQuarter);
            if ($cfo !== null && $net_revenue != 0) {
                $this->cFOToRevenue = round(100 * $cfo / $net_revenue, 2);
            }
        }
        return $this;
    }

    public function calculateFCFToRevenue($year = null, $quarter = null)
    {
        $this->fCFToRevenue = null;
        if (!empty($this->financialStatement->cash_flow_statement) && !empty($this->financialStatement->income_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $fcf = $this->fcfValue($this->financialStatement->cash_flow_statement, $selectedYear, $selectedQuarter);
            $net_revenue = $this->financialStatement->income_statement->getItem('3')->getValue($selectedYear, $selectedQuarter);
            if ($fcf !== null && $net_revenue != 0) {
                $this->fCFToRevenue = round(100 * $fcf / $net_revenue, 2);
            }
        }
        return $this;
    }

    public function calculateFCFToCFO($year = null, $quarter = null)
    {
        $this->fCFToCFO = null;
        $this->cfoUsedForFCFToCFO = null;
        if (!empty($this->financialStatement->cash_flow_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $fcf = $this->fcfValue($this->financialStatement->cash_flow_statement, $selectedYear, $selectedQuarter);
            $cfo = $this->cfoValue($this->financialStatement->cash_flow_statement, $selectedYear, $selectedQuarter);
            $this->cfoUsedForFCFToCFO = $cfo;
            if ($fcf !== null && $cfo != 0) {
                $this->fCFToCFO = round(100 * $fcf / $cfo, 2);
            }
        }
        return $this;
    }

    public function calculateLiabilityCoverageRatioByFCF($year = null, $quarter = null)
    {
        $this->liabilityCoverageRatioByFCF = null;
        $this->liabilityCoverageRatioByFCFQuarterOnly = null;
        $this->cfoUsedForFCF = null;
        if (!empty($this->financialStatement->cash_flow_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $statement = $this->financialStatement->cash_flow_statement;
            $this->cfoUsedForFCF = $this->cfoValueTTM($statement, $selectedYear, $selectedQuarter);
            $fcf = $this->fcfValueTTM($statement, $selectedYear, $selectedQuarter);
            $fcfQuarter = $this->fcfValue($statement, $selectedYear, $selectedQuarter);
            $average_liabilities = $this->financialStatement->balance_statement->getItem('301')->getAverageValue($selectedYear, $selectedQuarter);
            if ($fcf !== null && $average_liabilities != 0) {
                $this->liabilityCoverageRatioByFCF = round($fcf / $average_liabilities, 4);
            }
            if ($fcfQuarter !== null && $average_liabilities != 0) {
                $this->liabilityCoverageRatioByFCFQuarterOnly = round($fcfQuarter / $average_liabilities, 4);
            }
        }
        return $this;
    }

    public function calculateCurrentLiabilityCoverageRatioByFCF($year = null, $quarter = null)
    {
        $this->currentLiabilityCoverageRatioByFCF = null;
        $this->currentLiabilityCoverageRatioByFCFQuarterOnly = null;
        $this->cfoUsedForFCF = null;
        if (!empty($this->financialStatement->cash_flow_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $statement = $this->financialStatement->cash_flow_statement;
            $this->cfoUsedForFCF = $this->cfoValueTTM($statement, $selectedYear, $selectedQuarter);
            $fcf = $this->fcfValueTTM($statement, $selectedYear, $selectedQuarter);
            $fcfQuarter = $this->fcfValue($statement, $selectedYear, $selectedQuarter);
            $average_current_liabilities = $this->financialStatement->balance_statement->getItem('30101')->getAverageValue($selectedYear, $selectedQuarter);
            if ($fcf !== null && $average_current_liabilities != 0) {
                $this->currentLiabilityCoverageRatioByFCF = round($fcf / $average_current_liabilities, 4);
            }
            if ($fcfQuarter !== null && $average_current_liabilities != 0) {
                $this->currentLiabilityCoverageRatioByFCFQuarterOnly = round($fcfQuarter / $average_current_liabilities, 4);
            }
        }
        return $this;
    }

    public function calculateLongTermLiabilityCoverageRatioByFCF($year = null, $quarter = null)
    {
        $this->longTermLiabilityCoverageRatioByFCF = null;
        $this->longTermLiabilityCoverageRatioByFCFQuarterOnly = null;
        $this->cfoUsedForFCF = null;
        if (!empty($this->financialStatement->cash_flow_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $statement = $this->financialStatement->cash_flow_statement;
            $this->cfoUsedForFCF = $this->cfoValueTTM($statement, $selectedYear, $selectedQuarter);
            $fcf = $this->fcfValueTTM($statement, $selectedYear, $selectedQuarter);
            $fcfQuarter = $this->fcfValue($statement, $selectedYear, $selectedQuarter);
            $average_long_term_liabilities = $this->financialStatement->balance_statement->getItem('30102')->getAverageValue($selectedYear, $selectedQuarter);
            if ($fcf !== null && $average_long_term_liabilities != 0) {
                $this->longTermLiabilityCoverageRatioByFCF = round($fcf / $average_long_term_liabilities, 4);
            }
            if ($fcfQuarter !== null && $average_long_term_liabilities != 0) {
                $this->longTermLiabilityCoverageRatioByFCFQuarterOnly = round($fcfQuarter / $average_long_term_liabilities, 4);
            }
        }
        return $this;
    }

    public function calculateInterestCoverageRatioByFCF($year = null, $quarter = null)
    {
        $this->interestCoverageRatioByFCF = null;
        if (!empty($this->financialStatement->cash_flow_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $statement = $this->financialStatement->cash_flow_statement;
            $fcf = $this->fcfValue($statement, $selectedYear, $selectedQuarter);
            $interestItem = $this->paidInterestItem($statement);
            $taxItem = $this->paidTaxItem($statement);
            $paidInterests = $interestItem ? abs($interestItem->getValue($selectedYear, $selectedQuarter)) : 0;
            $paidTaxes = $taxItem ? abs($taxItem->getValue($selectedYear, $selectedQuarter)) : 0;
            if ($fcf !== null && $paidInterests != 0) {
                $this->interestCoverageRatioByFCF = round(($fcf + $paidInterests + $paidTaxes) / $paidInterests, 4);
            }
        }
        return $this;
    }

    public function calculateAssetEfficencyForFCFRatio($year = null, $quarter = null)
    {
        $this->assetEfficencyForFCFRatio = null;
        $this->assetEfficencyForFCFRatioQuarterOnly = null;
        if (!empty($this->financialStatement->cash_flow_statement) && !empty($this->financialStatement->balance_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $statement = $this->financialStatement->cash_flow_statement;
            $fcf = $this->fcfValueTTM($statement, $selectedYear, $selectedQuarter);
            $fcfQuarter = $this->fcfValue($statement, $selectedYear, $selectedQuarter);
            $average_assets = $this->financialStatement->balance_statement->getItem('2')->getAverageValue($selectedYear, $selectedQuarter);
            if ($fcf !== null && $average_assets != 0) {
                $this->assetEfficencyForFCFRatio = round(100 * $fcf / $average_assets, 2);
            }
            if ($fcfQuarter !== null && $average_assets != 0) {
                $this->assetEfficencyForFCFRatioQuarterOnly = round(100 * $fcfQuarter / $average_assets, 2);
            }
        }
        return $this;
    }

    public function calculateCashGeneratingPowerRatio($year = null, $quarter = null)
    {
        $this->cashGeneratingPowerRatio = null;
        if (!empty($this->financialStatement->cash_flow_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $statement = $this->financialStatement->cash_flow_statement;
            $cfo = $this->cfoValue($statement, $selectedYear, $selectedQuarter);
            $investingNet = $this->findChildByName($statement, '2', 'Lưu chuyển tiền thuần');
            $financingNet = $this->findChildByName($statement, '3', 'Lưu chuyển tiền thuần');
            $investingInflows = 0;
            foreach ($this->childrenOf($statement, '2') as $it) {
                if ($investingNet && $it->id === $investingNet->id) {
                    continue; // bỏ dòng tổng, chỉ cộng các dòng chi tiết dương
                }
                $v = $it->getValue($selectedYear, $selectedQuarter);
                if ($v > 0) {
                    $investingInflows += $v;
                }
            }
            $financingInflows = 0;
            foreach ($this->childrenOf($statement, '3') as $it) {
                if ($financingNet && $it->id === $financingNet->id) {
                    continue;
                }
                $v = $it->getValue($selectedYear, $selectedQuarter);
                if ($v > 0) {
                    $financingInflows += $v;
                }
            }
            if ($cfo !== null && $cfo > 0 && ($cfo + $investingInflows + $financingInflows) != 0) {
                $this->cashGeneratingPowerRatio = round(100 * $cfo / ($cfo + $investingInflows + $financingInflows), 2);
            }
        }
        return $this;
    }

    public function calculateExternalFinancingRatio($year = null, $quarter = null)
    {
        $this->externalFinancingRatio = null;
        $this->cfoUsedForExternalFinancing = null;
        if (!empty($this->financialStatement->cash_flow_statement)) {
            $selectedYear = $year ?? $this->financialStatement->year;
            $selectedQuarter = $quarter ?? $this->financialStatement->quarter;
            $statement = $this->financialStatement->cash_flow_statement;
            $cfo = $this->cfoValue($statement, $selectedYear, $selectedQuarter);
            $cffItem = $this->cffItem($statement);
            $cff = $cffItem ? $cffItem->getValue($selectedYear, $selectedQuarter) : null;
            $this->cfoUsedForExternalFinancing = $cfo;
            if ($cfo != 0 && $cff !== null) {
                $this->externalFinancingRatio = round($cff / $cfo, 2);
            }
        }
        return $this;
    }
}
