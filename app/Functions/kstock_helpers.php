<?php
/**
 * kstock helper functions
 *
 * @author: tuanha
 * @date: 04-Aug-2022
 */
if (! function_exists('readVietnameseDongForHuman')) {
    /**
     * Read a Vietnamese Dong value for human
     * For instance, readVietnameseDongForHuman(1000000000) => 1 (Ty VND)
     *
     * @param float
     * @return float | null
     */
    function readVietnameseDongForHuman($value)
    {
        $value = (float) $value;
        return round($value/1000000000, 2) != 0 ? round($value/1000000000, 2) : null;
    }
}

if (! function_exists('statementItemValues')) {
    /**
     * Lấy getValues() của một item BCTC/AnalysisReport một cách an toàn — trả [] nếu bản
     * thân statement (balance/income/cash_flow/analysis_report) hoặc item không tồn tại.
     * Cần thiết vì một số công ty có LCTT theo phương pháp trực tiếp (thiếu hẳn các item ID
     * của phương pháp gián tiếp mà các biểu đồ mặc định giả định), hoặc một tỷ số không
     * tính được nên writer bỏ qua không ghi vào AnalysisReport.
     *
     * @param  mixed   $statement  đối tượng có method getItem($id), hoặc null
     * @param  string  $itemId
     * @return array
     */
    function statementItemValues($statement, $itemId)
    {
        if (empty($statement)) {
            return [];
        }
        $item = $statement->getItem($itemId);
        return $item ? $item->getValues() : [];
    }
}

if (! function_exists('cashFlowMethodOf')) {
    /**
     * Nhận diện LCTT được lập theo phương pháp TRỰC TIẾP hay GIÁN TIẾP — dựa vào chính dữ
     * liệu (không phụ thuộc tham số truyền vào lúc pull, vốn không được lưu lại nên không
     * tin cậy khi chạy lại phân tích). Item id '1' là dòng đầu tiên của mục "I. Lưu chuyển
     * tiền từ hoạt động kinh doanh" trong mọi trường hợp: ở PP gián tiếp đó luôn là
     * "Lợi nhuận trước thuế"; ở PP trực tiếp đó là "Tiền thu từ bán hàng...". Đây là dòng
     * bắt buộc theo mẫu B03-DN (TT200) nên ổn định giữa các công ty.
     *
     * @param  mixed  $cashFlowStatement  CashFlowStatement hoặc null
     * @return string|null  'indirect' | 'direct' | null (không có dữ liệu)
     */
    function cashFlowMethodOf($cashFlowStatement)
    {
        if (empty($cashFlowStatement)) {
            return null;
        }
        $firstLine = $cashFlowStatement->getItem('101');
        if (!$firstLine) {
            return null;
        }
        return mb_stripos($firstLine->name, 'Lợi nhuận trước thuế') !== false ? 'indirect' : 'direct';
    }
}

if (! function_exists('getPreviousPeriod')) {
    /**
     * Get the previous period to the current period given by year and quarter
     * For instance, concern year 2022, concern quarter 1, then previous period is 2021 quarter 4
     *
     * @param integer $concernYear
     * @param integer $concernQuarter
     * @return array
     */
    function getPreviousPeriod($concernYear, $concernQuarter)
    {
        $concernYear = (int) $concernYear;
        $concernQuarter = (int) $concernQuarter;
        if ($concernQuarter == 1) {
            return [
                'year' => $concernYear - 1,
                'quarter' => 4
            ];
        } elseif ($concernQuarter == 0) {
            return [
                'year' => $concernYear - 1,
                'quarter' => 0
            ];
        } else {
            return [
                'year' => $concernYear,
                'quarter' => $concernQuarter - 1
            ];
        }
    }
}

if (! function_exists('getLastYearSamePeriod')) {
    /**
     * Get the last year same period to the current period given by year and quarter
     * For instance, concern year 2022, concern quarter 1, then previous period is 2021 quarter 1
     *
     * @param integer $concernYear
     * @param integer $concernQuarter
     * @return array
     */
    function getLastYearSamePeriod($concernYear, $concernQuarter)
    {
        $concernYear = (int) $concernYear;
        $concernQuarter = (int) $concernQuarter;
        return [
            'year' => $concernYear - 1,
            'quarter' => $concernQuarter
        ];
    }
}

if (! function_exists('financialUnitLabel')) {
    /**
     * Human label for an analysis-report metric unit.
     */
    function financialUnitLabel($unit)
    {
        switch ($unit) {
            case 'scalar': return 'Lần';
            case 'cycles': return 'Vòng';
            case 'days':   return 'Ngày';
            case '%':      return '%';
            default:       return (string) $unit;
        }
    }
}

if (! function_exists('formatFinancialValue')) {
    /**
     * Format an analysis-report value for display, by unit. Percent values are
     * already stored as percentages (e.g. 3.48 => "3.48%").
     */
    function formatFinancialValue($value, $unit = 'scalar')
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return '—';
        }
        $value = (float) $value;
        switch ($unit) {
            case '%':      return number_format($value, 2) . '%';
            case 'days':   return number_format($value, 1);
            default:       return number_format($value, 2); // scalar / cycles / other
        }
    }
}

if (! function_exists('quarterOnlyNote')) {
    /**
     * Ghi chú tooltip cho giá trị riêng-quý của một chỉ số đã được quy đổi năm (TTM) —
     * hiển thị cạnh giá trị chính để người xem đối chiếu. Trả null cho báo cáo năm
     * (quarter=0, giá trị chính vốn đã là riêng-kỳ nên không cần ghi chú) hoặc khi không
     * có số liệu riêng-quý.
     */
    function quarterOnlyNote($quarterOnlyValue, $unit, $quarter, $label = 'Riêng quý này (chưa quy đổi năm)')
    {
        if ($quarter == 0 || $quarterOnlyValue === null) {
            return null;
        }
        return $label . ': ' . formatFinancialValue($quarterOnlyValue, $unit);
    }
}

if (! function_exists('negativeEquityAlert')) {
    /**
     * Banner cảnh báo dùng chung cho mọi chỉ số có VCSH (hoặc VCSH bình quân) làm mẫu số —
     * khi VCSH âm (lỗ lũy kế vượt vốn góp), tỷ số vẫn tính ra số nhưng không còn phản ánh
     * đúng ý nghĩa gốc (vd lỗ/VCSH âm = ROE dương trông như sinh lời tốt).
     */
    function negativeEquityAlert($equity)
    {
        if ($equity === null || $equity >= 0) {
            return null;
        }
        return 'VCSH đang <strong>âm</strong> (lỗ lũy kế vượt vốn góp) — chỉ số này không còn '
             . 'phản ánh đúng ý nghĩa gốc, cần xem trực tiếp giá trị VCSH và lợi nhuận thay vì tỷ lệ.';
    }
}

if (! function_exists('negativeCfoAlert')) {
    /**
     * Banner cảnh báo dùng chung cho các chỉ số dựa trên FCF/CFO — khi CFO kỳ này âm, FCF và
     * mọi chỉ số phái sinh (FCF/CFO, hệ số trang trải nợ bằng FCF, hệ số phụ thuộc tài chính
     * bên ngoài...) không còn phản ánh đúng khả năng tạo tiền bền vững từ HĐKD.
     */
    function negativeCfoAlert($cfo)
    {
        if ($cfo === null || $cfo >= 0) {
            return null;
        }
        return 'Dòng tiền hoạt động kinh doanh (CFO) kỳ này đang <strong>âm</strong> — các chỉ số '
             . 'dựa trên FCF/CFO không còn phản ánh đúng khả năng tạo tiền bền vững, cần xem trực '
             . 'tiếp báo cáo lưu chuyển tiền tệ thay vì tỷ lệ.';
    }
}

if (! function_exists('oppositeSignAlert')) {
    /**
     * Banner cảnh báo khi tử số và mẫu số của 1 tỷ lệ trái dấu nhau (vd HĐKD lãi nhưng LNTT
     * lỗ do 1 khoản bất thường ngoài HĐKD, hoặc ngược lại) — tỷ lệ ra số âm dễ bị đọc nhầm
     * thành "HĐKD đang lỗ" trong khi thực chất ngược lại.
     */
    function oppositeSignAlert($numerator, $denominator, $message)
    {
        if ($numerator === null || $denominator === null) {
            return null;
        }
        if (($numerator > 0 && $denominator < 0) || ($numerator < 0 && $denominator > 0)) {
            return $message;
        }
        return null;
    }
}

if (! function_exists('analysisScoreZone')) {
    /**
     * Interpret a headline model score (Altman Z / Beneish M) into a status zone.
     * Returns ['label','class','icon'] (class = bootstrap contextual) or null.
     *
     * @param  string  $alias
     * @param  float|null  $value
     * @return array|null
     */
    function analysisScoreZone($alias, $value)
    {
        if ($value === null || !is_numeric($value)) {
            return null;
        }
        $value = (float) $value;
        $safe     = ['label' => 'An toàn',        'class' => 'success', 'icon' => 'fa-shield-alt'];
        $warning  = ['label' => 'Cảnh báo',       'class' => 'warning', 'icon' => 'fa-exclamation-triangle'];
        $distress = ['label' => 'Nguy hiểm',      'class' => 'danger',  'icon' => 'fa-radiation'];
        $clean    = ['label' => 'Ít rủi ro',      'class' => 'success', 'icon' => 'fa-check-circle'];
        $fraud    = ['label' => 'Rủi ro gian lận','class' => 'danger',  'icon' => 'fa-user-secret'];

        switch ($alias) {
            case 'Z-Score':  // Altman (manufacturing)
                return $value >= 2.99 ? $safe : ($value >= 1.81 ? $warning : $distress);
            case 'Z2-Score': // Altman (private/other)
                return $value >= 2.6 ? $safe : ($value >= 1.1 ? $warning : $distress);
            case 'M8-Score': // Beneish 8-variable — higher = more likely manipulation
                return $value <= -1.78 ? $clean : $fraud;
            case 'M5-Score': // Beneish 5-variable
                return $value <= -2.22 ? $clean : $fraud;
            default:
                return null;
        }
    }
}

if (! function_exists('financialDelta')) {
    /**
     * Latest-vs-prior change for a most-recent-first value list.
     * Returns ['dir' => up|down|flat, 'pct' => float|null] or null.
     * Direction only (neutral) — no good/bad semantics.
     *
     * @param  array  $vals  values, index 0 = latest
     * @return array|null
     */
    function financialDelta($vals)
    {
        if (!is_array($vals) || count($vals) < 2) {
            return null;
        }
        $latest = $vals[0];
        $prior  = $vals[1];
        if (!is_numeric($latest) || !is_numeric($prior)) {
            return null;
        }
        $latest = (float) $latest;
        $prior  = (float) $prior;
        $diff   = $latest - $prior;
        $dir    = abs($diff) < 1e-9 ? 'flat' : ($diff > 0 ? 'up' : 'down');
        $pct    = ($prior != 0.0) ? ($diff / abs($prior)) * 100 : null;

        return ['dir' => $dir, 'pct' => $pct];
    }
}

if (! function_exists('financialBarPercent')) {
    /**
     * Position of a value inside a [min,max] range, as 0..100 (for in-cell
     * magnitude bars). Non-numeric or empty range => 0.
     */
    function financialBarPercent($value, $min, $max)
    {
        if (!is_numeric($value) || !is_numeric($min) || !is_numeric($max)) {
            return 0;
        }
        $range = (float) $max - (float) $min;
        if ($range <= 0) {
            return 0;
        }
        $p = ((float) $value - (float) $min) / $range * 100;

        return (int) round(max(0, min(100, $p)));
    }
}

if (! function_exists('analysisMetricSignal')) {
    /**
     * Interpret a metric whose good/bad direction is known, into a coloured
     * signal for the latest value / its trend. Returns:
     *   ['tone' => good|warn|bad, 'label' => vi text, 'icon' => fa-*]
     * or null for metrics without a defined polarity (kept neutral).
     *
     * @param  string  $alias
     * @param  array   $vals   values, index 0 = latest (most-recent-first)
     * @param  string  $unit
     * @return array|null
     */
    function analysisMetricSignal($alias, $vals, $unit = null)
    {
        if (!is_array($vals) || !isset($vals[0]) || !is_numeric($vals[0])) {
            return null;
        }
        $latest = (float) $vals[0];
        $delta  = financialDelta($vals); // ['dir'=>up|down|flat,...] | null

        $good = ['tone' => 'good', 'icon' => 'fa-check-circle'];
        $warn = ['tone' => 'warn', 'icon' => 'fa-exclamation-triangle'];
        $bad  = ['tone' => 'bad',  'icon' => 'fa-times-circle'];

        // ================= LEVEL-BASED (ngưỡng lấy theo mô tả/tooltip) =================

        // ---- Thanh khoản ----
        if ($alias === 'Overall Solvency Ratio') { // tooltip: <1 nguy cơ phá sản, ≥1 đáp ứng nợ
            return $latest < 1 ? $bad + ['label' => 'Nguy cơ mất khả năng thanh toán (< 1)']
                               : $good + ['label' => 'Thanh toán tổng quát tốt (≥ 1)'];
        }
        if ($alias === 'Current Ratio') { // tooltip: <1 khả năng trả nợ yếu
            return $latest < 1 ? $warn + ['label' => 'Thanh khoản ngắn hạn yếu (< 1)']
                               : $good + ['label' => 'Thanh toán hiện hành đảm bảo (≥ 1)'];
        }
        if ($alias === 'Quick Ratio 1') { // tooltip: <0.5 khó khăn trả nợ ngắn hạn
            return $latest < 0.5 ? $warn + ['label' => 'Thanh toán nhanh yếu (< 0.5)']
                                 : $good + ['label' => 'Thanh toán nhanh đảm bảo (≥ 0.5)'];
        }
        if ($alias === 'Cash Ratio') { // tooltip: <0.5 thường được xem là rủi ro
            return $latest < 0.5 ? $warn + ['label' => 'Thanh toán tức thời yếu (< 0.5)']
                                 : $good + ['label' => 'Thanh toán tức thời đảm bảo (≥ 0.5)'];
        }
        if ($alias === 'Quick Ratio 2') { // tooltip: phép thử khắt khe hơn Quick Ratio 1
            return $latest < 0.3 ? $warn + ['label' => 'Thanh toán nhanh (khắt khe) yếu (< 0.3)']
                                 : $good + ['label' => 'Thanh toán nhanh (khắt khe) đảm bảo (≥ 0.3)'];
        }
        if ($alias === 'Interest Coverage Ratio') { // tooltip: <1 không đủ trả lãi vay
            if ($latest < 1) { return $bad + ['label' => 'Không đủ trả lãi vay (< 1)']; }
            if ($latest < 2) { return $warn + ['label' => 'Khả năng trả lãi vay thấp']; }
            return $good + ['label' => 'Thừa khả năng trả lãi vay (≥ 2)'];
        }

        // ---- Dòng tiền: hệ số bao phủ nợ (tooltip: ≥ 1 là đủ) ----
        $cfoCoverage = ['Liability Coverage Ratio By CFO', 'Current Liability Coverage Ratio By CFO', 'Long-term Liability Coverage Ratio By CFO'];
        $fcfCoverage = ['Liability Coverage Ratio By FCF', 'Current Liability Coverage Ratio By FCF', 'Long-term Liability Coverage Ratio By FCF'];
        if (in_array($alias, $cfoCoverage, true) || in_array($alias, $fcfCoverage, true)) {
            $isFcf = in_array($alias, $fcfCoverage, true);
            if ($latest < 0) { return $bad + ['label' => $isFcf ? 'Dòng tiền tự do âm' : 'Dòng tiền HĐKD âm']; }
            if ($latest < 1) { return $warn + ['label' => 'Chưa đủ trang trải nợ (< 1)']; }
            return $good + ['label' => 'Đủ khả năng thanh toán (≥ 1)'];
        }
        if ($alias === 'Interest Coverage Ratio By FCF') { // tooltip: <1 nhiều khả năng vỡ nợ
            return $latest < 1 ? $bad + ['label' => 'FCF không đủ trả lãi vay (< 1)']
                               : $good + ['label' => 'FCF đủ trả lãi vay (≥ 1)'];
        }

        // ---- Dòng tiền: chất lượng ----
        if ($alias === 'CFO/Revenue') {
            return $latest < 0 ? $bad + ['label' => 'Dòng tiền HĐKD âm']
                               : $good + ['label' => 'Dòng tiền HĐKD dương'];
        }
        if ($alias === 'FCF/Revenue') { // tooltip: > 10% nhiều năm = cỗ máy tạo tiền
            if ($latest < 0) { return $warn + ['label' => 'Dòng tiền tự do âm']; }
            if ($latest >= 10) { return $good + ['label' => 'FCF/DT ≥ 10% — cỗ máy tạo tiền']; }
        }
        if ($alias === 'FCF/CFO' && $latest < 0) {
            return $warn + ['label' => 'Dòng tiền tự do âm'];
        }
        if ($alias === 'Cash Generating Power Ratio') { // tooltip: > 15% ổn định = cỗ máy tạo tiền
            if ($latest < 0) { return $bad + ['label' => 'Dòng tiền HĐKD âm']; }
            if ($latest >= 15) { return $good + ['label' => 'Khả năng tạo tiền tốt (≥ 15%)']; }
        }
        if ($alias === 'Asset Efficency For FCF Ratio' && $latest < 0) {
            return $warn + ['label' => 'Dòng tiền tự do âm'];
        }
        if ($alias === 'External Financing Ratio') { // tooltip: âm (<0) & CFO>0 = tài chính ổn định
            return $latest <= 0 ? $good + ['label' => 'Tự chủ tài chính (không phụ thuộc vốn ngoài)']
                                : $warn + ['label' => 'Phụ thuộc tài chính bên ngoài'];
        }

        // ---- CAPEX (tooltip: CFO/CAPEX ≥1 tốt; CAPEX/LNST <25% bền vững, <50% có lợi thế) ----
        if ($alias === 'CFO/CAPEX') {
            return $latest < 1 ? $warn + ['label' => 'CFO chưa đủ tài trợ CAPEX (< 1)']
                               : $good + ['label' => 'CFO đủ tài trợ CAPEX (≥ 1)'];
        }
        if ($alias === 'CAPEX/NetProfit' && $latest > 0) {
            if ($latest <= 25) { return $good + ['label' => 'CAPEX ≤ 25% LNST — lợi thế bền vững']; }
            if ($latest <= 50) { return $good + ['label' => 'CAPEX ≤ 50% LNST — có lợi thế']; }
            return $warn + ['label' => 'CAPEX cao (> 50% LNST)'];
        }

        // ---- Cơ cấu chi phí (ngưỡng Buffett trong tooltip) ----
        if ($alias === 'Gross profit margin') { // >40% lợi thế bền vững; <20% cạnh tranh gay gắt
            if ($latest < 0)  { return $bad + ['label' => 'Biên LN gộp âm']; }
            if ($latest >= 40) { return $good + ['label' => 'Biên LN gộp ≥ 40% — lợi thế bền vững']; }
            if ($latest < 20)  { return $warn + ['label' => 'Biên LN gộp < 20% — cạnh tranh gay gắt']; }
        }
        if ($alias === 'Selling and Enterprise Management Expenses/Gross Profit') {
            // tooltip: <30% tuyệt vời; 30–80% vẫn có thể có lợi thế; ~100%+ cạnh tranh gay gắt
            if ($latest <= 30)  { return $good + ['label' => 'CP BH&QL ≤ 30% LN gộp — tuyệt vời']; }
            if ($latest >= 100) { return $bad  + ['label' => 'CP BH&QL ≥ 100% LN gộp — cạnh tranh gay gắt']; }
            if ($latest > 80)   { return $warn + ['label' => 'CP BH&QL cao (> 80% LN gộp)']; }
            return $good + ['label' => 'CP BH&QL 30–80% LN gộp — chấp nhận được'];
        }
        if ($alias === 'Cogs/Revenue') { // = 100% − biên LN gộp -> theo ngưỡng Buffett của biên gộp
            if ($latest <= 60) { return $good + ['label' => 'Giá vốn ≤ 60% DT — biên gộp cao (≥ 40%)']; }
            if ($latest >= 80) { return $warn + ['label' => 'Giá vốn ≥ 80% DT — biên gộp mỏng (< 20%)']; }
        }

        // ---- Đòn bẩy tài chính ----
        if ($alias === 'Debts/Equities') { // tooltip: > 1 đòn bẩy cao, > 2 rất cao
            if ($latest < 0) { return $bad + ['label' => 'VCSH âm (đòn bẩy bất thường)']; }
            if ($latest > 2) { return $bad + ['label' => 'Đòn bẩy rất cao (> 2)']; }
            if ($latest > 1) { return $warn + ['label' => 'Đòn bẩy cao (> 1)']; }
            return $good + ['label' => 'Đòn bẩy an toàn (≤ 1)'];
        }
        if ($alias === 'Total Liabilities/Total Assets') { // stored as %
            if ($latest >= 85) { return $bad + ['label' => 'Đòn bẩy rất cao (≥ 85%)']; }
            if ($latest >= 70) { return $warn + ['label' => 'Đòn bẩy cao (≥ 70%)']; }
            return $good + ['label' => 'Đòn bẩy an toàn (< 70%)'];
        }
        if ($alias === 'Total Debts/Total Assets') { // stored as %; nợ vay (chịu lãi) trên tổng tài sản
            if ($latest > 50) { return $bad + ['label' => 'Đòn bẩy nợ vay cao (> 50%)']; }
            if ($latest > 30) { return $warn + ['label' => 'Đòn bẩy nợ vay trung bình (> 30%)']; }
            return $good + ['label' => 'Đòn bẩy nợ vay an toàn (≤ 30%)'];
        }
        if ($alias === 'Net Debts/Equities') { // nợ vay ròng / VCSH
            if ($latest < 0) { return $good + ['label' => 'Tiền ròng dương (net cash)']; }
            if ($latest > 2) { return $bad + ['label' => 'Nợ ròng rất cao (> 2)']; }
            if ($latest > 1) { return $warn + ['label' => 'Nợ ròng cao (> 1)']; }
        }
        if ($alias === 'Long Term Debts/Equities') { // nợ vay dài hạn / VCSH
            if ($latest > 1) { return $bad + ['label' => 'Nợ vay dài hạn cao (> 1)']; }
            if ($latest > 0.5) { return $warn + ['label' => 'Nợ vay dài hạn trung bình (> 0.5)']; }
            return $good + ['label' => 'Nợ vay dài hạn thận trọng (≤ 0.5)'];
        }
        if (in_array($alias, ['Total Assets/Equities', 'Average Total Assets/Average Equities'], true)) { // hệ số đòn bẩy tài chính (equity multiplier)
            if ($latest > 3) { return $bad + ['label' => 'Đòn bẩy cao (> 3 lần)']; }
            if ($latest > 2) { return $warn + ['label' => 'Đòn bẩy trung bình (> 2 lần)']; }
            return $good + ['label' => 'Đòn bẩy vừa phải (≤ 2 lần)'];
        }
        if ($alias === 'Interest cost/Revenue') { // gánh nặng lãi vay trên doanh thu
            if ($latest <= 3) { return $good + ['label' => 'Gánh nặng lãi vay thấp (≤ 3% DT)']; }
            if ($latest > 10) { return $warn + ['label' => 'Gánh nặng lãi vay cao (> 10% DT)']; }
        }
        if ($alias === 'Operating Profit/EBT') { // chất lượng lợi nhuận: HĐKD cốt lõi so với LNTT
            if ($latest < 0) { return $bad + ['label' => 'HĐKD lỗ, LNTT dương nhờ khoản bất thường']; }
            if ($latest < 50) { return $warn + ['label' => 'Phần lớn LNTT đến từ nguồn không cốt lõi (< 50%)']; }
            return $good + ['label' => 'LNTT chủ yếu từ HĐKD cốt lõi (≥ 50%)'];
        }

        // ---- Sinh lời: ngưỡng mức tuyệt đối (benchmark phổ biến; vùng giữa để phần xu hướng) ----
        $profitStrong = [
            'ROE' => 15.0, 'ROEA' => 15.0, 'ROCE' => 15.0,
            'ROA' => 7.5,  'ROAA' => 7.5,  'ROTA' => 7.5,
            'ROS' => 10.0, 'ROS2' => 10.0,
        ];
        if (isset($profitStrong[$alias])) {
            if ($latest < 0) { return $bad + ['label' => 'Thua lỗ (âm)']; }
            if ($latest >= $profitStrong[$alias]) {
                $th = rtrim(rtrim(number_format($profitStrong[$alias], 1), '0'), '.');
                return $good + ['label' => "Sinh lời cao (≥ {$th}%)"];
            }
        }
        // ---- Chu kỳ tiền mặt âm = được khách trả trước khi trả NCC (chiếm dụng vốn tốt) ----
        if ($alias === 'Cash Conversion Cycle' && $latest < 0) {
            return $good + ['label' => 'Chu kỳ tiền mặt âm — chiếm dụng vốn tốt'];
        }

        // ---- Level-based: profitability / growth turning negative ----
        $profitability = ['ROE', 'ROEA', 'ROAA', 'ROA', 'ROS', 'ROS2', 'Gross profit margin', 'ROTA', 'ROCE'];
        if (in_array($alias, $profitability, true) && $latest < 0) {
            return $bad + ['label' => 'Thua lỗ (âm)'];
        }
        $growthNeg = ['Revenue Growth YoY', 'Revenue Growth QoQ', 'Gross Profit Growth YoY',
                      'Net Profit Of Parent ShareHolder Growth YoY', 'Earnings Before Tax Growth YoY'];
        if (in_array($alias, $growthNeg, true) && $latest < 0) {
            return $warn + ['label' => 'Tăng trưởng âm'];
        }

        // ---- Beneish M-Score components (ngưỡng theo tooltip: đa số > 1 = tín hiệu xấu) ----
        // "Dấu hiệu" (bằng chứng) -> đỏ khi vượt ngưỡng; "Động cơ" -> vàng.
        // [ngưỡng, tone khi vượt, nhãn xấu (lý do ngắn theo tooltip), nhãn tốt]
        $mComponents = [
            'DSRI' => [1, 'bad',  'Phải thu tăng bất thường → nghi ngờ ghi nhận doanh thu', 'Phải thu/DT ổn định'],
            'AQI'  => [1, 'bad',  'Tài sản kém chất lượng tăng → có thể vốn hóa chi phí', 'Chất lượng tài sản ổn định'],
            'DEPI' => [1, 'bad',  'Khấu hao chậm lại → giảm chi phí bất thường', 'Khấu hao ổn định'],
            'GMI'  => [1, 'warn', 'Biên lãi gộp suy giảm → động cơ thao túng', 'Biên lãi gộp không giảm'],
            'SGI'  => [1, 'warn', 'Tăng trưởng nóng → áp lực duy trì KQKD', 'Tăng trưởng ổn định'],
            'SGAI' => [1, 'warn', 'Chi phí BH&QL/DT tăng → động cơ gian lận', 'Chi phí BH&QL ổn định'],
            'LVGI' => [1, 'warn', 'Đòn bẩy tăng → động cơ làm đẹp chỉ số', 'Đòn bẩy ổn định'],
        ];
        if (isset($mComponents[$alias])) {
            [$th, $badTone, $labBad, $labGood] = $mComponents[$alias];
            if ($latest > $th) {
                return ($badTone === 'bad' ? $bad : $warn) + ['label' => $labBad];
            }
            return $good + ['label' => $labGood];
        }
        if ($alias === 'TATA') { // dấu hiệu: dồn tích cao khi LN kế toán > dòng tiền HĐKD
            return $latest > 0
                ? $bad + ['label' => 'Dồn tích cao (LN kế toán > dòng tiền HĐKD)']
                : $good + ['label' => 'Dồn tích thấp'];
        }

        // ---- Trend-based: operating effectiveness + profitability (needs a direction) ----
        if ($delta && $delta['dir'] !== 'flat') {
            $up = $delta['dir'] === 'up';

            // lower is better (shorter cycles)
            $lowerBetter = [
                'Average Age of Inventory'  => ['Bán hàng nhanh hơn', 'Tồn kho lâu hơn'],
                'Average Collection Period' => ['Thu hồi công nợ tốt hơn', 'Thu hồi công nợ chậm hơn'],
                'Cash Conversion Cycle'     => ['Chu kỳ tiền mặt ngắn lại', 'Chu kỳ tiền mặt dài ra'],
                'Accounts payable turnover' => ['Chiếm dụng vốn NCC lâu hơn', 'Trả nợ NCC nhanh hơn'],
            ];
            // higher is better
            $higherBetter = [
                'Average Account Payable Duration' => ['Chiếm dụng vốn NCC lâu hơn', 'Trả nợ NCC nhanh hơn'],
                'Total Asset Turnover Ratio'       => ['Hiệu quả dùng tài sản tăng', 'Hiệu quả dùng tài sản giảm'],
                'Fixed Asset Turnover Ratio'       => ['Hiệu quả dùng TSCĐ tăng', 'Hiệu quả dùng TSCĐ giảm'],
                'Equity Turnover Ratio'            => ['Hiệu quả dùng VCSH tăng', 'Hiệu quả dùng VCSH giảm'],
                'Inventory turnover ratio'         => ['Bán hàng nhanh hơn', 'Bán hàng chậm hơn'],
                'Receivable turnover ratio'        => ['Thu hồi công nợ tốt hơn', 'Thu hồi công nợ chậm hơn'],
                'ROE'                              => ['Sinh lời trên VCSH tăng', 'Sinh lời trên VCSH giảm'],
                'ROEA'                             => ['Sinh lời trên VCSH tăng', 'Sinh lời trên VCSH giảm'],
                'ROAA'                             => ['Sinh lời trên tài sản tăng', 'Sinh lời trên tài sản giảm'],
                'ROA'                              => ['Sinh lời trên tài sản tăng', 'Sinh lời trên tài sản giảm'],
                'ROS'                              => ['Biên lợi nhuận tăng', 'Biên lợi nhuận giảm'],
                'Gross profit margin'              => ['Biên LN gộp tăng', 'Biên LN gộp giảm'],
            ];

            if (isset($lowerBetter[$alias])) {
                $isGood = !$up; // decreasing = good
                return ($isGood ? $good : $warn) + ['label' => $lowerBetter[$alias][$isGood ? 0 : 1]];
            }
            if (isset($higherBetter[$alias])) {
                $isGood = $up; // increasing = good
                return ($isGood ? $good : $warn) + ['label' => $higherBetter[$alias][$isGood ? 0 : 1]];
            }
        }

        return null;
    }
}

if (! function_exists('analysisScoreCaption')) {
    /**
     * Threshold caption for a headline model score, or null.
     */
    function analysisScoreCaption($alias)
    {
        switch ($alias) {
            case 'Z-Score':  return 'An toàn ≥ 2.99 · Cảnh báo 1.81–2.99 · Nguy hiểm ≤ 1.81';
            case 'Z2-Score': return 'An toàn ≥ 2.6 · Cảnh báo 1.1–2.6 · Nguy hiểm ≤ 1.1';
            case 'M8-Score': return 'Nghi ngờ gian lận nếu > -1.78';
            case 'M5-Score': return 'Nghi ngờ gian lận nếu > -2.22';
            default:         return null;
        }
    }
}

if (! function_exists('analysisOverallVerdict')) {
    /**
     * Aggregate a company's headline metrics into one overall health verdict.
     * Returns ['tone','label','icon','summary','drivers'=>[['label','tone'],...]] or null.
     *
     * @param  mixed  $ar  AnalysisReport (has getItem($alias))
     * @return array|null
     */
    function analysisOverallVerdict($ar, $zAlias = 'Z-Score')
    {
        if (empty($ar)) {
            return null;
        }
        $zAlias = in_array($zAlias, ['Z-Score', 'Z2-Score'], true) ? $zAlias : 'Z-Score';
        $latestOf = function ($alias) use ($ar) {
            $it = $ar->getItem($alias);
            $v = ($it && !empty($it->values)) ? ($it->values[0]['value'] ?? null) : null;
            return is_numeric($v) ? (float) $v : null;
        };

        $rank = ['good' => 0, 'warn' => 1, 'bad' => 2];
        $worst = 'good';
        $drivers = [];
        $add = function ($label, $tone) use (&$drivers, &$worst, $rank) {
            $drivers[] = ['label' => $label, 'tone' => $tone];
            if ($rank[$tone] > $rank[$worst]) { $worst = $tone; }
        };

        // Bankruptcy risk (Altman Z — chọn Z hay Z2 theo ngành sản xuất/phi sản xuất)
        $z = $latestOf($zAlias);
        if ($z !== null) {
            $zone = analysisScoreZone($zAlias, $z);
            $t = ['success' => 'good', 'warning' => 'warn', 'danger' => 'bad'][$zone['class'] ?? 'warning'] ?? 'warn';
            $add($t === 'good' ? 'Nguy cơ phá sản thấp' : ($t === 'warn' ? 'Nguy cơ phá sản: cảnh báo' : 'Nguy cơ phá sản cao'), $t);
        }
        // Fraud risk (Beneish M8)
        $m8 = $latestOf('M8-Score');
        if ($m8 !== null) {
            $t = $m8 > -1.78 ? 'bad' : 'good';
            $add($t === 'bad' ? 'Rủi ro gian lận BCTC' : 'Ít rủi ro gian lận', $t);
        }
        // Liquidity
        $cr = $latestOf('Current Ratio');
        if ($cr !== null) {
            $t = $cr < 1 ? 'warn' : 'good';
            $add($t === 'warn' ? 'Thanh khoản yếu' : 'Thanh khoản ổn', $t);
        }
        // Leverage
        $de = $latestOf('Debts/Equities');
        if ($de !== null) {
            $t = $de > 2 ? 'bad' : ($de > 1 ? 'warn' : 'good');
            $add($t === 'good' ? 'Đòn bẩy hợp lý' : 'Đòn bẩy cao', $t);
        }
        // Profitability
        $roe = $latestOf('ROE');
        if ($roe !== null) {
            $t = $roe < 0 ? 'bad' : 'good';
            $add($t === 'bad' ? 'Đang thua lỗ' : 'Có lãi', $t);
        }
        // Operating cash flow
        $cfo = $latestOf('CFO/Revenue');
        if ($cfo !== null) {
            $t = $cfo < 0 ? 'bad' : 'good';
            $add($t === 'bad' ? 'Dòng tiền HĐKD âm' : 'Dòng tiền HĐKD dương', $t);
        }

        if (empty($drivers)) {
            return null;
        }

        $labels = ['good' => 'An toàn', 'warn' => 'Cảnh báo', 'bad' => 'Rủi ro cao'];
        $icons  = ['good' => 'fa-shield-alt', 'warn' => 'fa-exclamation-triangle', 'bad' => 'fa-radiation'];
        $issues = array_values(array_filter($drivers, function ($d) { return $d['tone'] !== 'good'; }));
        $summary = count($issues)
            ? 'Điểm cần lưu ý: ' . implode(', ', array_map(function ($d) { return mb_strtolower($d['label'], 'UTF-8'); }, $issues)) . '.'
            : 'Các chỉ số tài chính chính đều ở mức an toàn.';

        return [
            'tone' => $worst, 'label' => $labels[$worst], 'icon' => $icons[$worst],
            'summary' => $summary, 'drivers' => $drivers,
        ];
    }
}

if (! function_exists('institutionType')) {
    /**
     * Loại định chế của một AnalysisReport dựa trên alias chỉ số: 'bank' | 'securities'
     * | 'insurance' | null (không phải định chế / không có report).
     */
    function institutionType($ar)
    {
        if (empty($ar)) {
            return null;
        }
        if ($ar->getItem('BANK_ROAA')) { return 'bank'; }
        if ($ar->getItem('SEC_ROAA'))  { return 'securities'; }
        if ($ar->getItem('INS_ROAA'))  { return 'insurance'; }
        return null;
    }
}

if (! function_exists('institutionMetricSignal')) {
    /**
     * Đánh tín hiệu tốt/trung bình/yếu cho một chỉ số định chế theo ngưỡng chuẩn ngành.
     * Trả ['tone','icon','label'] hoặc null (chỉ số mang tính thông tin, không chấm điểm).
     *
     * @param  string  $alias
     * @param  array   $vals  giá trị mới nhất ở [0]
     */
    function institutionMetricSignal($alias, $vals)
    {
        if (!is_array($vals) || !isset($vals[0]) || !is_numeric($vals[0])) {
            return null;
        }
        $latest = (float) $vals[0];

        // dir 'high' = càng cao càng tốt; 'low' = càng thấp càng tốt.
        // [dir, good, warn] => good nếu vượt/không quá ngưỡng good, warn nếu tới ngưỡng warn.
        $table = [
            // Ngân hàng
            'BANK_ROAA' => ['high', 1.5, 0.8],  'BANK_ROEA' => ['high', 15, 10],
            'BANK_NIM'  => ['high', 3.5, 2.5],  'BANK_CIR'  => ['low', 35, 45],
            'BANK_NONII' => ['high', 20, 10],   'BANK_PROV' => ['low', 20, 40],
            'BANK_LDR'  => ['low', 100, 115],   'BANK_ETA'  => ['high', 10, 7],
            'BANK_LEV'  => ['low', 10, 12],     'BANK_CREDIT_COST' => ['low', 1.0, 2.0],
            'BANK_CREDIT_GROWTH' => ['growth', 10, 0], 'BANK_DEPOSIT_GROWTH' => ['growth', 10, 0],
            'BANK_TOI_GROWTH' => ['growth', 10, 0], 'BANK_NP_GROWTH' => ['growth', 10, 0],
            // Chứng khoán
            'SEC_ROAA' => ['high', 3, 1.5],   'SEC_ROEA' => ['high', 15, 8],
            'SEC_NPM'  => ['high', 25, 10],   'SEC_CIR'  => ['low', 50, 70],
            'SEC_CURRENT' => ['high', 1.5, 1], 'SEC_ETA' => ['high', 40, 25],
            'SEC_LEV'  => ['low', 3, 5],
            // Giới hạn UBCKNN: dư nợ margin không vượt quá 200% VCSH — 150% là vùng an toàn.
            'SEC_MARGIN_LEVERAGE' => ['low', 150, 200],
            'SEC_REV_GROWTH' => ['growth', 10, 0], 'SEC_NP_GROWTH' => ['growth', 10, 0],
            // Bảo hiểm
            'INS_ROAA' => ['high', 1.5, 0.7], 'INS_ROEA' => ['high', 12, 7],
            'INS_LOSS' => ['low', 60, 75],    'INS_EXPENSE' => ['low', 30, 40],
            'INS_COMBINED' => ['low', 95, 100], 'INS_ETA' => ['high', 10, 6],
            'INS_LEV'  => ['low', 10, 15],
            'INS_REV_GROWTH' => ['growth', 10, 0], 'INS_NP_GROWTH' => ['growth', 10, 0],
        ];
        if (!isset($table[$alias])) {
            return null; // Chỉ số thông tin (vd cơ cấu doanh thu, đóng góp LN tài chính).
        }
        [$dir, $good, $warn] = $table[$alias];

        $icons = ['good' => 'fa-check-circle', 'warn' => 'fa-exclamation-triangle', 'bad' => 'fa-times-circle'];
        if ($dir === 'high') {
            $tone = $latest >= $good ? 'good' : ($latest >= $warn ? 'warn' : 'bad');
            $labels = ['good' => 'Tốt', 'warn' => 'Trung bình', 'bad' => 'Yếu'];
        } elseif ($dir === 'low') {
            $tone = $latest <= $good ? 'good' : ($latest <= $warn ? 'warn' : 'bad');
            $labels = ['good' => 'Tốt', 'warn' => 'Trung bình', 'bad' => 'Cao'];
        } else { // growth
            $tone = $latest >= $good ? 'good' : ($latest >= $warn ? 'warn' : 'bad');
            $labels = ['good' => 'Tăng tốt', 'warn' => 'Đi ngang', 'bad' => 'Suy giảm'];
        }
        return ['tone' => $tone, 'icon' => $icons[$tone], 'label' => $labels[$tone]];
    }
}

if (! function_exists('institutionTiles')) {
    /**
     * Bộ thẻ tóm tắt headline cho định chế tài chính (thay cho Z/M-Score của DN thường).
     * Trả mảng tile theo đúng shape view đang dùng, hoặc [] nếu không phải định chế.
     */
    function institutionTiles($ar)
    {
        $type = institutionType($ar);
        if (!$type) {
            return [];
        }
        $classFromTone = ['good' => 'success', 'warn' => 'warning', 'bad' => 'danger'];
        // Bộ chỉ số headline + gợi ý ngưỡng theo từng loại.
        $headline = [
            'bank' => [
                'BANK_ROAA' => 'Tốt ≥ 1.5%', 'BANK_ROEA' => 'Tốt ≥ 15%',
                'BANK_NIM'  => 'Biên lãi ròng, tốt ≥ 3.5%', 'BANK_CIR' => 'Càng thấp càng hiệu quả (≤ 35%)',
            ],
            'securities' => [
                'SEC_ROAA' => 'Tốt ≥ 3%', 'SEC_ROEA' => 'Tốt ≥ 15%',
                'SEC_NPM'  => 'Biên LN ròng, tốt ≥ 25%', 'SEC_CIR' => 'Chi phí/DT, ≤ 50% là tốt',
            ],
            'insurance' => [
                'INS_ROEA' => 'Tốt ≥ 12%', 'INS_COMBINED' => '> 100% = HĐ bảo hiểm lỗ',
                'INS_LOSS' => 'Tỷ lệ bồi thường, ≤ 60% tốt', 'INS_ROAA' => 'Tốt ≥ 1.5%',
            ],
        ][$type];

        $tiles = [];
        foreach ($headline as $alias => $caption) {
            $it = $ar->getItem($alias);
            if (!$it || empty($it->values)) { continue; }
            $vals = \Arr::pluck($it->values, 'value');
            $v = $vals[0] ?? null;
            $sig = institutionMetricSignal($alias, $vals);
            $tiles[] = [
                'label'   => $it->name,
                'value'   => formatFinancialValue($v, $it->unit),
                'unit'    => $it->unit === '%' ? '' : financialUnitLabel($it->unit),
                'values'  => $vals,
                'delta'   => financialDelta($vals),
                'tone'    => $sig['tone'] ?? null,
                'badge'   => $sig ? ['text' => $sig['label'], 'icon' => $sig['icon'], 'class' => $classFromTone[$sig['tone']]] : null,
                'caption' => $caption,
            ];
        }
        return $tiles;
    }
}

if (! function_exists('institutionVerdict')) {
    /**
     * Nhận định tổng quan cho định chế tài chính — gom tín hiệu các chỉ số cốt lõi.
     * Cùng shape với analysisOverallVerdict().
     */
    function institutionVerdict($ar)
    {
        $type = institutionType($ar);
        if (!$type) {
            return null;
        }
        // Chỉ số cốt lõi + nhãn ngắn cho từng loại định chế.
        $core = [
            'bank' => [
                'BANK_ROEA' => 'Sinh lời VCSH', 'BANK_CIR' => 'Hiệu quả chi phí',
                'BANK_CREDIT_COST' => 'Chi phí tín dụng', 'BANK_PROV' => 'Gánh nặng dự phòng',
                'BANK_ETA' => 'An toàn vốn', 'BANK_NP_GROWTH' => 'Tăng trưởng LN',
            ],
            'securities' => [
                'SEC_ROEA' => 'Sinh lời VCSH', 'SEC_NPM' => 'Biên lợi nhuận',
                'SEC_CIR' => 'Hiệu quả chi phí', 'SEC_LEV' => 'Đòn bẩy', 'SEC_NP_GROWTH' => 'Tăng trưởng LN',
            ],
            'insurance' => [
                'INS_ROEA' => 'Sinh lời VCSH', 'INS_COMBINED' => 'Hiệu quả bảo hiểm',
                'INS_ETA' => 'An toàn vốn', 'INS_NP_GROWTH' => 'Tăng trưởng LN',
            ],
        ][$type];

        $rank = ['good' => 0, 'warn' => 1, 'bad' => 2];
        $worst = 'good';
        $drivers = [];
        foreach ($core as $alias => $label) {
            $it = $ar->getItem($alias);
            if (!$it || empty($it->values)) { continue; }
            $vals = \Arr::pluck($it->values, 'value');
            $sig = institutionMetricSignal($alias, $vals);
            if (!$sig) { continue; }
            $drivers[] = ['label' => $label . ': ' . mb_strtolower($sig['label'], 'UTF-8'), 'tone' => $sig['tone']];
            if ($rank[$sig['tone']] > $rank[$worst]) { $worst = $sig['tone']; }
        }
        if (empty($drivers)) {
            return null;
        }

        $typeLabel = ['bank' => 'ngân hàng', 'securities' => 'công ty chứng khoán', 'insurance' => 'doanh nghiệp bảo hiểm'][$type];
        $labels = ['good' => 'Ổn định', 'warn' => 'Cần theo dõi', 'bad' => 'Rủi ro cao'];
        $icons  = ['good' => 'fa-shield-alt', 'warn' => 'fa-exclamation-triangle', 'bad' => 'fa-radiation'];
        $issues = array_values(array_filter($drivers, fn ($d) => $d['tone'] !== 'good'));
        $summary = count($issues)
            ? 'Đánh giá theo bộ chỉ số ' . $typeLabel . '. Điểm cần lưu ý: ' . implode(', ', array_map(fn ($d) => $d['label'], $issues)) . '.'
            : 'Các chỉ số ' . $typeLabel . ' cốt lõi đều ở mức tốt.';

        return [
            'tone' => $worst, 'label' => $labels[$worst], 'icon' => $icons[$worst],
            'summary' => $summary, 'drivers' => $drivers,
        ];
    }
}

if (! function_exists('institutionDataNote')) {
    /**
     * Ghi chú về các chỉ số chuẩn ngành KHÔNG tính được từ dữ liệu external API (chỉ có trong
     * thuyết minh BCTC), theo loại định chế. Trả chuỗi (có thể chứa HTML) hoặc null.
     *
     * @param  string|null  $type  'bank' | 'securities' | 'insurance'
     */
    function institutionDataNote($type)
    {
        $notes = [
            'bank' => 'Một số chỉ số chuẩn ngành ngân hàng không tính được do dữ liệu chỉ gồm '
                . 'bảng cân đối & kết quả kinh doanh (không có thuyết minh): <strong>tỷ lệ nợ xấu (NPL)</strong>, '
                . '<strong>tỷ lệ bao phủ nợ xấu (LLR/NPL)</strong>, <strong>hệ số an toàn vốn (CAR)</strong>, '
                . '<strong>tỷ lệ CASA</strong>. Thay vào đó, chất lượng tài sản được ước lượng qua '
                . 'Dự phòng/Dư nợ và Chi phí tín dụng.',
            'securities' => 'Cơ cấu tự doanh chi tiết (FVTPL/HTM/AFS) và một số chỉ tiêu an toàn tài chính '
                . 'theo quy định chỉ có trong thuyết minh nên không được tính ở đây.',
            'insurance' => 'Các chỉ tiêu dự phòng nghiệp vụ chi tiết và biên khả năng thanh toán chỉ có trong '
                . 'thuyết minh BCTC nên không được tính ở đây.',
        ];
        return $notes[$type] ?? null;
    }
}

if (! function_exists('businessSectorClass')) {
    /**
     * Nhận diện doanh nghiệp sản xuất / phi sản xuất từ mã ngành ICB (4 chữ số) — dùng để
     * chọn đúng mô hình Altman (Z cho sản xuất, Z2/Z'' cho phi sản xuất).
     *
     * Chữ số đầu = nhóm ngành ICB: 0 Dầu khí, 1 Vật liệu cơ bản, 2 Công nghiệp,
     * 3 Hàng tiêu dùng, 4 Y tế, 5 Dịch vụ tiêu dùng, 6 Viễn thông, 7 Tiện ích,
     * 8 Tài chính, 9 Công nghệ. Một số ngành hỗn hợp được override theo mã 4 số.
     *
     * @param  string|null  $industryCode
     * @return string|null  'manufacturing' | 'non_manufacturing' | 'financial' | null
     */
    function businessSectorClass($industryCode)
    {
        $code = preg_replace('/\D/', '', (string) $industryCode);
        if ($code === '') {
            return null;
        }
        $sector = substr($code, 0, 4);
        // Ngành mặc định phi SX nhưng bản chất là sản xuất.
        if (in_array($sector, ['4570', '9570'], true)) { // Dược phẩm & CNSH; Phần cứng CNTT
            return 'manufacturing';
        }
        // Ngành mặc định SX nhưng bản chất là dịch vụ/phi SX.
        if (in_array($sector, ['2350', '2770', '2790'], true)) { // Xây dựng; Vận tải CN; Dịch vụ hỗ trợ
            return 'non_manufacturing';
        }
        // Bất động sản (ICB 86xx) nằm trong nhóm 8 nhưng là DN vận hành phi sản xuất,
        // không phải định chế tài chính -> dùng Z2 như DN dịch vụ.
        if (substr($code, 0, 2) === '86') {
            return 'non_manufacturing';
        }
        $industry = (int) substr($code, 0, 1);
        if ($industry === 8) {
            return 'financial';
        }
        // 0 Dầu khí, 1 Vật liệu, 2 Công nghiệp (chế tạo), 3 Hàng tiêu dùng -> sản xuất.
        if (in_array($industry, [0, 1, 2, 3], true)) {
            return 'manufacturing';
        }
        // 4 Y tế, 5 Dịch vụ, 6 Viễn thông, 7 Tiện ích, 9 Công nghệ (phần mềm) -> phi SX.
        return 'non_manufacturing';
    }
}

if (! function_exists('businessSectorLabel')) {
    /** Nhãn hiển thị cho phân loại ngành, hoặc null. */
    function businessSectorLabel($class)
    {
        return [
            'manufacturing'     => 'Doanh nghiệp sản xuất',
            'non_manufacturing' => 'Doanh nghiệp phi sản xuất',
            'financial'         => 'Định chế tài chính',
        ][(string) $class] ?? null;
    }
}

if (! function_exists('businessSectorZAlias')) {
    /** Mô hình Altman phù hợp theo ngành: Z-Score (sản xuất) hoặc Z2-Score (phi sản xuất). */
    function businessSectorZAlias($class)
    {
        if ($class === 'manufacturing') { return 'Z-Score'; }
        if ($class === 'non_manufacturing') { return 'Z2-Score'; }
        return null;
    }
}

if (! function_exists('marketDataTtl')) {
    /**
     * Cache TTL (seconds) for live market data (price, fundamentals), aware of the
     * Vietnamese trading session (Mon–Fri 09:00–15:00 ICT).
     *   - In session  -> $inSession (kept fresh).
     *   - Off session -> seconds until the next session open (daily bars and
     *     price-derived ratios are immutable until then, so serving the cached
     *     value avoids pointless API calls).
     *
     * Timezone is explicit because config('app.timezone') defaults to UTC. Keeps
     * no config() dependency so it stays unit-testable without the container.
     *
     * @param  int    $inSession  in-session TTL in seconds
     * @param  array  $holidays   exchange-closed dates as 'Y-m-d' (Asia/Ho_Chi_Minh)
     * @return int
     */
    function marketDataTtl($inSession = 900, array $holidays = [])
    {
        $now   = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $open  = $now->copy()->setTime(9, 0);
        $close = $now->copy()->setTime(15, 0);

        $isTradingDay = function (\Carbon\Carbon $d) use ($holidays) {
            return $d->isWeekday() && ! in_array($d->toDateString(), $holidays, true);
        };

        if ($isTradingDay($now) && $now->gte($open) && $now->lte($close)) {
            return (int) $inSession;
        }

        $next = ($isTradingDay($now) && $now->lt($open))
            ? $open
            : $now->copy()->addDay()->setTime(9, 0);
        while (! $isTradingDay($next)) {
            $next->addDay();
        }
        return max(60, (int) abs($now->diffInSeconds($next)));
    }
}

if (! function_exists('marketHolidays')) {
    /**
     * Exchange-closed holiday dates ('Y-m-d', Asia/Ho_Chi_Minh) that must be
     * excluded from data refresh, on top of weekends + trading hours. Managed
     * from Settings → Refresh calendar and stored as a JSON array under the
     * `market_holidays` setting key (see SettingController::updateMarketCalendar).
     *
     * @return array<int, string>
     */
    function marketHolidays()
    {
        $raw = config('settings.market_holidays');
        if (is_array($raw)) {
            return array_values(array_filter($raw, 'is_string'));
        }
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, 'is_string'));
            }
        }
        return [];
    }
}

if (! function_exists('comparisonMetricCatalog')) {
    /**
     * Danh mục chỉ số dùng để so sánh nhiều mã cổ phiếu (bảng hợp nhất, ánh xạ khái niệm
     * → alias theo loại định chế). Mỗi phần tử:
     *   group   : tên nhóm hiển thị
     *   label   : tên chỉ số
     *   unit    : '%' | 'scalar' | 'cycles' | 'days' | ''
     *   better  : 'high' | 'low' | null (null = không tô best/worst)
     *   source  : 'ratio' (từ AnalysisReport) | 'fundamental' (từ getFundamentalsData)
     *   alias   : [type => alias|null]  (khi source=ratio), type ∈ normal|bank|securities|insurance
     *   key     : khóa trong mảng fundamental (khi source=fundamental)
     *   zone    : true nếu tô màu theo analysisScoreZone (Z/M-Score)
     *
     * Loại 'normal' = DN phi tài chính (institutionType() trả null).
     */
    function comparisonMetricCatalog()
    {
        $r = fn ($normal, $bank, $sec, $ins) => ['normal' => $normal, 'bank' => $bank, 'securities' => $sec, 'insurance' => $ins];
        return [
            // ----- Định giá (live) -----
            ['group' => 'Định giá', 'label' => 'P/E', 'unit' => 'scalar', 'better' => 'low', 'source' => 'fundamental', 'key' => 'pe'],
            ['group' => 'Định giá', 'label' => 'EPS', 'unit' => '', 'better' => null, 'source' => 'fundamental', 'key' => 'eps'],
            ['group' => 'Định giá', 'label' => 'Vốn hóa thị trường', 'unit' => '', 'better' => null, 'source' => 'fundamental', 'key' => 'marketCap'],
            ['group' => 'Định giá', 'label' => 'Tỷ suất cổ tức', 'unit' => '%', 'better' => 'high', 'source' => 'fundamental', 'key' => 'dividendYield'],
            // ----- Khả năng sinh lời -----
            ['group' => 'Khả năng sinh lời', 'label' => 'ROE', 'unit' => '%', 'better' => 'high', 'chart' => true, 'source' => 'ratio', 'alias' => $r('ROE', 'BANK_ROEA', 'SEC_ROEA', 'INS_ROEA')],
            ['group' => 'Khả năng sinh lời', 'label' => 'ROA', 'unit' => '%', 'better' => 'high', 'chart' => true, 'source' => 'ratio', 'alias' => $r('ROA', 'BANK_ROAA', 'SEC_ROAA', 'INS_ROAA')],
            ['group' => 'Khả năng sinh lời', 'label' => 'Biên LN ròng / NIM', 'unit' => '%', 'better' => 'high', 'source' => 'ratio', 'alias' => $r('ROS', 'BANK_NIM', 'SEC_NPM', null)],
            ['group' => 'Khả năng sinh lời', 'label' => 'Biên lợi nhuận gộp', 'unit' => '%', 'better' => 'high', 'source' => 'ratio', 'alias' => $r('Gross profit margin', null, null, null)],
            // ----- Tăng trưởng -----
            ['group' => 'Tăng trưởng', 'label' => 'Tăng trưởng doanh thu (YoY)', 'unit' => '%', 'better' => 'high', 'chart' => true, 'source' => 'ratio', 'alias' => $r('Revenue Growth YoY', 'BANK_TOI_GROWTH', 'SEC_REV_GROWTH', 'INS_REV_GROWTH')],
            ['group' => 'Tăng trưởng', 'label' => 'Tăng trưởng LNST (YoY)', 'unit' => '%', 'better' => 'high', 'chart' => true, 'source' => 'ratio', 'alias' => $r('Net Profit Of Parent ShareHolder Growth YoY', 'BANK_NP_GROWTH', 'SEC_NP_GROWTH', 'INS_NP_GROWTH')],
            // ----- Hiệu quả hoạt động -----
            ['group' => 'Hiệu quả hoạt động', 'label' => 'Vòng quay tổng tài sản', 'unit' => 'cycles', 'better' => 'high', 'source' => 'ratio', 'alias' => $r('Total Asset Turnover Ratio', null, null, null)],
            ['group' => 'Hiệu quả hoạt động', 'label' => 'Kỳ thu tiền bình quân', 'unit' => 'days', 'better' => 'low', 'source' => 'ratio', 'alias' => $r('Average Collection Period', null, null, null)],
            ['group' => 'Hiệu quả hoạt động', 'label' => 'Số ngày tồn kho', 'unit' => 'days', 'better' => 'low', 'source' => 'ratio', 'alias' => $r('Average Age of Inventory', null, null, null)],
            ['group' => 'Hiệu quả hoạt động', 'label' => 'Chu kỳ tiền mặt', 'unit' => 'days', 'better' => 'low', 'source' => 'ratio', 'alias' => $r('Cash Conversion Cycle', null, null, null)],
            ['group' => 'Hiệu quả hoạt động', 'label' => 'Chi phí / Thu nhập (CIR)', 'unit' => '%', 'better' => 'low', 'source' => 'ratio', 'alias' => $r(null, 'BANK_CIR', 'SEC_CIR', 'INS_EXPENSE')],
            // ----- Đòn bẩy tài chính -----
            ['group' => 'Đòn bẩy tài chính', 'label' => 'Tổng tài sản / VCSH', 'unit' => 'scalar', 'better' => 'low', 'source' => 'ratio', 'alias' => $r('Total Assets/Equities', 'BANK_LEV', 'SEC_LEV', 'INS_LEV')],
            ['group' => 'Đòn bẩy tài chính', 'label' => 'Nợ phải trả / Tổng tài sản', 'unit' => '%', 'better' => 'low', 'source' => 'ratio', 'alias' => $r('Total Liabilities/Total Assets', null, null, null)],
            ['group' => 'Đòn bẩy tài chính', 'label' => 'VCSH / Tổng tài sản (an toàn vốn)', 'unit' => '%', 'better' => 'high', 'source' => 'ratio', 'alias' => $r(null, 'BANK_ETA', 'SEC_ETA', 'INS_ETA')],
            ['group' => 'Đòn bẩy tài chính', 'label' => 'Nợ vay / VCSH (D/E)', 'unit' => 'scalar', 'better' => 'low', 'source' => 'ratio', 'alias' => $r('Debts/Equities', null, null, null)],
            // ----- Chất lượng tài sản -----
            ['group' => 'Chất lượng tài sản', 'label' => 'Chỉ số chất lượng TS (AQI)', 'unit' => 'scalar', 'better' => 'low', 'source' => 'ratio', 'alias' => $r('AQI', null, null, null)],
            ['group' => 'Chất lượng tài sản', 'label' => 'Dự phòng / Dư nợ gộp', 'unit' => '%', 'better' => null, 'source' => 'ratio', 'alias' => $r(null, 'BANK_LLR', null, null)],
            ['group' => 'Chất lượng tài sản', 'label' => 'Chi phí tín dụng', 'unit' => '%', 'better' => 'low', 'source' => 'ratio', 'alias' => $r(null, 'BANK_CREDIT_COST', null, null)],
            ['group' => 'Chất lượng tài sản', 'label' => 'Chi phí dự phòng / PPOP', 'unit' => '%', 'better' => 'low', 'source' => 'ratio', 'alias' => $r(null, 'BANK_PROV', null, null)],
            // ----- Thanh khoản & dòng tiền -----
            ['group' => 'Thanh khoản & dòng tiền', 'label' => 'Thanh toán hiện hành', 'unit' => 'scalar', 'better' => 'high', 'source' => 'ratio', 'alias' => $r('Current Ratio', null, 'SEC_CURRENT', null)],
            ['group' => 'Thanh khoản & dòng tiền', 'label' => 'Khả năng trả lãi vay', 'unit' => 'scalar', 'better' => 'high', 'source' => 'ratio', 'alias' => $r('Interest Coverage Ratio', null, null, null)],
            ['group' => 'Thanh khoản & dòng tiền', 'label' => 'CFO / Doanh thu', 'unit' => '%', 'better' => 'high', 'source' => 'ratio', 'alias' => $r('CFO/Revenue', null, null, null)],
            // ----- Rủi ro mô hình (DN thường) -----
            // 'zscore' => true: controller chọn Z-Score (sản xuất) / Z2-Score (phi sản xuất) theo ngành từng mã.
            ['group' => 'Rủi ro mô hình', 'label' => 'Nguy cơ phá sản (Altman Z)', 'unit' => 'scalar', 'better' => null, 'zone' => true, 'zscore' => true, 'source' => 'ratio', 'alias' => $r('Z-Score', null, null, null)],
            ['group' => 'Rủi ro mô hình', 'label' => 'M-Score (rủi ro gian lận)', 'unit' => 'scalar', 'better' => null, 'zone' => true, 'source' => 'ratio', 'alias' => $r('M8-Score', null, null, null)],
        ];
    }
}

if (! function_exists('comparisonRowHighlight')) {
    /**
     * Xác định cột "tốt nhất" / "kém nhất" trong một hàng so sánh để tô màu.
     * $values: mảng theo thứ tự cột, phần tử là số (float) hoặc null (không có/—).
     * Trả ['best'=>index|null, 'worst'=>index|null]; rỗng nếu $better=null hoặc <2 số.
     */
    function comparisonRowHighlight(array $values, $better)
    {
        if (!in_array($better, ['high', 'low'], true)) {
            return ['best' => null, 'worst' => null];
        }
        $numeric = array_filter($values, 'is_numeric');
        if (count($numeric) < 2) {
            return ['best' => null, 'worst' => null];
        }
        $maxKey = array_keys($numeric, max($numeric))[0];
        $minKey = array_keys($numeric, min($numeric))[0];
        // Nếu mọi giá trị bằng nhau thì không tô.
        if (max($numeric) == min($numeric)) {
            return ['best' => null, 'worst' => null];
        }
        return $better === 'high'
            ? ['best' => $maxKey, 'worst' => $minKey]
            : ['best' => $minKey, 'worst' => $maxKey];
    }
}
