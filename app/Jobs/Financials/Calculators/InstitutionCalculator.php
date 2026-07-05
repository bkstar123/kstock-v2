<?php
/**
 * InstitutionCalculator - chỉ số cho định chế tài chính (ngân hàng / chứng khoán /
 * bảo hiểm). Item ID của các loại này khác hẳn doanh nghiệp thường nên phải map riêng.
 * Data-driven: definitions($type) trả về danh sách chỉ số kèm closure tính giá trị
 * theo (year, quarter). Thiếu item / mẫu số = 0 => trả null (writer sẽ bỏ qua).
 */
namespace App\Jobs\Financials\Calculators;

use App\Jobs\Financials\Calculators\BaseCalculator;

class InstitutionCalculator extends BaseCalculator
{
    /* ---------------- Safe accessors ---------------- */

    protected function bs(string $id, $y, $q): float
    {
        $it = optional($this->financialStatement->balance_statement)->getItem($id);
        return $it ? (float) $it->getValue($y, $q) : 0.0;
    }

    protected function is(string $id, $y, $q): float
    {
        $it = optional($this->financialStatement->income_statement)->getItem($id);
        return $it ? (float) $it->getValue($y, $q) : 0.0;
    }

    /**
     * Trailing value: annual => giá trị năm; quý => luỹ kế 4 quý (nếu $ttm=true) hoặc
     * riêng quý đang xét (nếu $ttm=false, dùng để dựng bản đồng hành cho tooltip/badge TTM).
     */
    protected function isTTM(string $id, $y, $q, bool $ttm = true): float
    {
        $it = optional($this->financialStatement->income_statement)->getItem($id);
        if (!$it) {
            return 0.0;
        }
        if ((int) $q === 0 || !$ttm) {
            return (float) $it->getValue($y, $q);
        }
        return (float) $it->getAccumulatedValueFromPastPeriod($y, $q, 3);
    }

    /** Bình quân số dư đầu–cuối kỳ. */
    protected function bsAvg(string $id, $y, $q): float
    {
        $p = getPreviousPeriod($y, $q);
        return ($this->bs($id, $y, $q) + $this->bs($id, $p['year'], $p['quarter'])) / 2;
    }

    protected function sumBs(array $ids, $y, $q): float
    {
        return array_sum(array_map(fn ($id) => $this->bs($id, $y, $q), $ids));
    }

    /** Bình quân đầu–cuối kỳ của tổng số dư nhiều khoản mục. */
    protected function avgSumBs(array $ids, $y, $q): float
    {
        $p = getPreviousPeriod($y, $q);
        return ($this->sumBs($ids, $y, $q) + $this->sumBs($ids, $p['year'], $p['quarter'])) / 2;
    }

    protected function sumTTM(array $ids, $y, $q, bool $ttm = true): float
    {
        return array_sum(array_map(fn ($id) => $this->isTTM($id, $y, $q, $ttm), $ids));
    }

    /* ---------------- Ratio helpers (null-safe) ---------------- */

    protected function pct($num, $den): ?float
    {
        return ($den == 0.0) ? null : $num / $den * 100;
    }

    protected function times($num, $den): ?float
    {
        return ($den == 0.0) ? null : $num / $den;
    }

    protected function yoyBs(string $id, $y, $q): ?float
    {
        $prev = $this->bs($id, $y - 1, $q);
        return $prev == 0.0 ? null : ($this->bs($id, $y, $q) - $prev) / abs($prev) * 100;
    }

    protected function yoyTTM(string $id, $y, $q): ?float
    {
        $prev = $this->isTTM($id, $y - 1, $q);
        return $prev == 0.0 ? null : ($this->isTTM($id, $y, $q) - $prev) / abs($prev) * 100;
    }

    /* ---------------- Metric definitions per company type ---------------- */

    /**
     * @param  int  $type  1=bank, 2=securities, 4=insurance
     * @return array<int,array{alias:string,name:string,group:string,unit:string,desc:string,fn:callable}>
     */
    public function definitions(int $type): array
    {
        switch ($type) {
            case 1: return $this->bankDefinitions();
            case 2: return $this->securitiesDefinitions();
            case 4: return $this->insuranceDefinitions();
            default: return [];
        }
    }

    private function bankDefinitions(): array
    {
        $toi = fn ($y, $q, $ttm = true) => $this->sumTTM(['1', '2', '3', '4', '5', '6', '7'], $y, $q, $ttm); // Tổng thu nhập HĐ
        $earnAssets = ['104', '105', '107', '108']; // tài sản sinh lời (TCTD, CK KD, cho vay KH, CK đầu tư)
        $funding = ['301', '302', '303', '306'];     // nợ chịu lãi (NHNN, TCTD, tiền gửi KH, giấy tờ có giá)
        return [
            ['alias' => 'BANK_ROAA', 'name' => 'ROAA (LNST/Tổng TS bình quân)', 'group' => 'Sinh lời (Ngân hàng)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Tỷ suất sinh lời trên tổng tài sản bình quân — đo hiệu quả sử dụng toàn bộ tài sản để tạo ra lợi nhuận. Ngưỡng tham khảo cho NHTM Việt Nam: Tốt ≥ 1.5%, trung bình 0.8–1.5%, yếu &lt; 0.8%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('13', $y, $q, $ttm), $this->bsAvg('2', $y, $q))],
            ['alias' => 'BANK_ROEA', 'name' => 'ROEA (LNST cổ đông mẹ/VCSH bình quân)', 'group' => 'Sinh lời (Ngân hàng)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Tỷ suất sinh lời trên vốn chủ sở hữu bình quân — thước đo lợi nhuận chính cổ đông ngân hàng quan tâm nhất. Ngưỡng tham khảo: Tốt ≥ 15%, trung bình 10–15%, yếu &lt; 10%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('15', $y, $q, $ttm), $this->bsAvg('308', $y, $q))],
            ['alias' => 'BANK_NIM', 'name' => 'NIM (Thu nhập lãi thuần/TS sinh lời BQ)', 'group' => 'Sinh lời (Ngân hàng)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Biên lãi ròng: thu nhập lãi thuần trên tài sản sinh lời bình quân (cho vay, tiền gửi TCTD, chứng khoán). Xấp xỉ Lợi suất TS sinh lời − Chi phí vốn — chỉ số cốt lõi phản ánh khả năng sinh lời từ hoạt động tín dụng cơ bản. Ngưỡng tham khảo: Tốt ≥ 3.5%, trung bình 2.5–3.5%, yếu &lt; 2.5%.',
             'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('1', $y, $q, $ttm), $this->avgSumBs($earnAssets, $y, $q))],
            ['alias' => 'BANK_YIELD', 'name' => 'Lợi suất tài sản sinh lời', 'group' => 'Sinh lời (Ngân hàng)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Thu nhập từ lãi gộp trên tài sản sinh lời bình quân — mức sinh lời gộp từ hoạt động tín dụng/đầu tư. Kết hợp với Chi phí vốn (COF) để tính biên lãi ròng (NIM ≈ Lợi suất − COF); tự thân không có ngưỡng tốt/xấu phổ quát vì phụ thuộc mặt bằng lãi suất thị trường từng giai đoạn, nên so sánh theo xu hướng hoặc với ngân hàng cùng quy mô.',
             'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('101', $y, $q, $ttm), $this->avgSumBs($earnAssets, $y, $q))],
            ['alias' => 'BANK_COF', 'name' => 'Chi phí vốn (Cost of funds)', 'group' => 'Sinh lời (Ngân hàng)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Chi phí lãi trên nguồn vốn chịu lãi bình quân (tiền gửi, vay TCTD, giấy tờ có giá) — giá vốn huy động. Càng thấp thường càng tốt (huy động rẻ hơn), nhưng không có ngưỡng cố định vì phụ thuộc mặt bằng lãi suất từng giai đoạn — nên đọc cùng NIM/Lợi suất để đánh giá đầy đủ.',
             'fn' => fn ($y, $q, $ttm = true) => $this->pct(abs($this->isTTM('102', $y, $q, $ttm)), $this->avgSumBs($funding, $y, $q))],
            ['alias' => 'BANK_CIR', 'name' => 'CIR (Chi phí HĐ/Tổng thu nhập HĐ)', 'group' => 'Hiệu quả (Ngân hàng)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Tỷ lệ chi phí trên thu nhập — càng thấp càng hiệu quả vận hành. Ngưỡng tham khảo: Tốt ≤ 35%, trung bình 35–45%, yếu &gt; 45%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct(abs($this->isTTM('8', $y, $q, $ttm)), $toi($y, $q, $ttm))],
            ['alias' => 'BANK_NONII', 'name' => 'Tỷ trọng thu nhập ngoài lãi', 'group' => 'Hiệu quả (Ngân hàng)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Phần thu nhập ngoài lãi trên tổng thu nhập hoạt động — đa dạng nguồn thu (phí dịch vụ, bancassurance...), giảm phụ thuộc vào tín dụng. Ngưỡng tham khảo: Tốt ≥ 20%, trung bình 10–20%, yếu &lt; 10%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($toi($y, $q, $ttm) - $this->isTTM('1', $y, $q, $ttm), $toi($y, $q, $ttm))],
            ['alias' => 'BANK_LLR', 'name' => 'Dự phòng / Dư nợ gộp', 'group' => 'Chất lượng tài sản (Ngân hàng)', 'unit' => '%',
             'desc' => 'Số dư dự phòng rủi ro cho vay khách hàng trên dư nợ gộp — mức đệm dự phòng. Mức đệm cao hơn thường thể hiện thận trọng hơn, nhưng cần đối chiếu với tỷ lệ nợ xấu (NPL) thực để đánh giá đủ — nợ xấu tăng nhanh hơn dự phòng là dấu hiệu xấu. <em>Lưu ý: đây là proxy; tỷ lệ nợ xấu (NPL) thực và tỷ lệ bao phủ nợ xấu cần bảng phân loại nợ trong thuyết minh (không có trong dữ liệu).</em>',
             'fn' => fn ($y, $q) => $this->pct(abs($this->bs('10702', $y, $q)), $this->bs('10701', $y, $q))],
            ['alias' => 'BANK_CREDIT_COST', 'name' => 'Chi phí tín dụng (dự phòng/dư nợ BQ)', 'group' => 'Chất lượng tài sản (Ngân hàng)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Chi phí trích lập dự phòng rủi ro tín dụng (TTM) trên dư nợ cho vay bình quân — càng thấp càng tốt (chất lượng tín dụng tốt hơn). Ngưỡng tham khảo: Tốt ≤ 1%, trung bình 1–2%, yếu &gt; 2%.',
             'fn' => fn ($y, $q, $ttm = true) => $this->pct(abs($this->isTTM('10', $y, $q, $ttm)), $this->bsAvg('10701', $y, $q))],
            ['alias' => 'BANK_PROV', 'name' => 'Chi phí dự phòng / LN trước dự phòng', 'group' => 'Chất lượng tài sản (Ngân hàng)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Gánh nặng trích lập dự phòng rủi ro tín dụng so với lợi nhuận trước dự phòng — phần lợi nhuận "hy sinh" để phòng ngừa rủi ro tín dụng. Ngưỡng tham khảo: Tốt ≤ 20%, trung bình 20–40%, yếu &gt; 40%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct(abs($this->isTTM('10', $y, $q, $ttm)), $this->isTTM('9', $y, $q, $ttm))],
            ['alias' => 'BANK_LDR', 'name' => 'LDR (Cho vay KH/Tiền gửi KH)', 'group' => 'Cân đối & đòn bẩy (Ngân hàng)', 'unit' => '%',
             'desc' => 'Tỷ lệ dư nợ cho vay trên tiền gửi khách hàng — thanh khoản & đòn bẩy tín dụng. Ngưỡng tham khảo (theo tinh thần quy định NHNN về LDR): Tốt ≤ 100%, trung bình 100–115%, yếu &gt; 115% (phụ thuộc nhiều vào nguồn vốn khác ngoài tiền gửi).', 'fn' => fn ($y, $q) => $this->pct($this->bs('10701', $y, $q), $this->bs('303', $y, $q))],
            ['alias' => 'BANK_ETA', 'name' => 'VCSH / Tổng tài sản', 'group' => 'Cân đối & đòn bẩy (Ngân hàng)', 'unit' => '%',
             'desc' => 'Tỷ lệ vốn chủ sở hữu trên tổng tài sản — đại diện mức an toàn vốn, khả năng hấp thụ tổn thất. Ngưỡng tham khảo: Tốt ≥ 10%, trung bình 7–10%, yếu &lt; 7%.', 'fn' => fn ($y, $q) => $this->pct($this->bs('308', $y, $q), $this->bs('2', $y, $q))],
            ['alias' => 'BANK_LEV', 'name' => 'Đòn bẩy (Tổng TS/VCSH)', 'group' => 'Cân đối & đòn bẩy (Ngân hàng)', 'unit' => 'scalar',
             'desc' => 'Hệ số đòn bẩy tài chính — số lần tài sản được tài trợ bởi mỗi đồng vốn chủ sở hữu, ngân hàng vốn dĩ có đòn bẩy cao hơn doanh nghiệp thường. Ngưỡng tham khảo: Tốt ≤ 10 lần, trung bình 10–12 lần, yếu &gt; 12 lần (đòn bẩy càng cao, vốn đệm rủi ro càng mỏng).', 'fn' => fn ($y, $q) => $this->times($this->bs('2', $y, $q), $this->bs('308', $y, $q))],
            ['alias' => 'BANK_LOAN_TO_ASSETS', 'name' => 'Cho vay khách hàng / Tổng tài sản', 'group' => 'Cân đối & đòn bẩy (Ngân hàng)', 'unit' => '%',
             'desc' => 'Tỷ trọng dư nợ cho vay khách hàng trong tổng tài sản — mức độ tập trung vào tín dụng lõi so với đầu tư/liên ngân hàng. Không có ngưỡng tốt/xấu phổ quát — tỷ trọng cao thể hiện mô hình kinh doanh tập trung tín dụng bán lẻ/doanh nghiệp truyền thống, tỷ trọng thấp hơn thể hiện đa dạng hoá sang đầu tư/liên ngân hàng; nên so sánh theo xu hướng hoặc với ngân hàng cùng nhóm.',
             'fn' => fn ($y, $q) => $this->pct($this->bs('10701', $y, $q), $this->bs('2', $y, $q))],
            ['alias' => 'BANK_INVESTMENT_TO_ASSETS', 'name' => 'Chứng khoán đầu tư / Tổng tài sản', 'group' => 'Cân đối & đòn bẩy (Ngân hàng)', 'unit' => '%',
             'desc' => 'Tỷ trọng danh mục chứng khoán đầu tư (AFS/HTM) trong tổng tài sản. Không có ngưỡng tốt/xấu phổ quát — tỷ trọng cao có thể tăng tính thanh khoản và bộ đệm dự phòng nhưng thường giảm biên lãi ròng so với cho vay khách hàng.',
             'fn' => fn ($y, $q) => $this->pct($this->bs('108', $y, $q), $this->bs('2', $y, $q))],
            ['alias' => 'BANK_CREDIT_GROWTH', 'name' => 'Tăng trưởng tín dụng (YoY)', 'group' => 'Tăng trưởng (Ngân hàng)', 'unit' => '%',
             'desc' => 'Tăng trưởng dư nợ cho vay khách hàng so với cùng kỳ năm trước. Ngưỡng tham khảo: Tăng tốt ≥ 10%/năm, đi ngang 0–10%, suy giảm &lt; 0%.', 'fn' => fn ($y, $q) => $this->yoyBs('10701', $y, $q)],
            ['alias' => 'BANK_DEPOSIT_GROWTH', 'name' => 'Tăng trưởng tiền gửi (YoY)', 'group' => 'Tăng trưởng (Ngân hàng)', 'unit' => '%',
             'desc' => 'Tăng trưởng tiền gửi khách hàng so với cùng kỳ năm trước. Ngưỡng tham khảo: Tăng tốt ≥ 10%/năm, đi ngang 0–10%, suy giảm &lt; 0%.', 'fn' => fn ($y, $q) => $this->yoyBs('303', $y, $q)],
            ['alias' => 'BANK_CHARTER_CAPITAL_GROWTH', 'name' => 'Tăng trưởng vốn điều lệ (YoY)', 'group' => 'Tăng trưởng (Ngân hàng)', 'unit' => '%',
             'desc' => 'Tăng trưởng vốn điều lệ so với cùng kỳ năm trước — theo dõi hoạt động tăng vốn. Không có ngưỡng tốt/xấu phổ quát — tăng vốn điều lệ (qua phát hành cổ phiếu/trả cổ tức bằng cổ phiếu) giúp củng cố hệ số an toàn vốn nhưng có thể pha loãng ROE ngắn hạn.', 'fn' => fn ($y, $q) => $this->yoyBs('3080101', $y, $q)],
            ['alias' => 'BANK_TOI_GROWTH', 'name' => 'Tăng trưởng tổng thu nhập HĐ (YoY)', 'group' => 'Tăng trưởng (Ngân hàng)', 'unit' => '%',
             'desc' => 'Tăng trưởng tổng thu nhập hoạt động (TTM) so với cùng kỳ. Ngưỡng tham khảo: Tăng tốt ≥ 10%/năm, đi ngang 0–10%, suy giảm &lt; 0%.', 'fn' => function ($y, $q) use ($toi) {
                 $prev = $toi($y - 1, $q); return $prev == 0.0 ? null : ($toi($y, $q) - $prev) / abs($prev) * 100;
             }],
            ['alias' => 'BANK_NP_GROWTH', 'name' => 'Tăng trưởng LNST (YoY)', 'group' => 'Tăng trưởng (Ngân hàng)', 'unit' => '%',
             'desc' => 'Tăng trưởng lợi nhuận sau thuế (TTM) so với cùng kỳ. Ngưỡng tham khảo: Tăng tốt ≥ 10%/năm, đi ngang 0–10%, suy giảm &lt; 0%.', 'fn' => fn ($y, $q) => $this->yoyTTM('13', $y, $q)],
        ];
    }

    private function securitiesDefinitions(): array
    {
        return [
            ['alias' => 'SEC_ROAA', 'name' => 'ROAA (LNST/Tổng TS bình quân)', 'group' => 'Sinh lời (Chứng khoán)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Tỷ suất sinh lời trên tổng tài sản bình quân. Ngưỡng tham khảo: Tốt ≥ 3%, trung bình 1.5–3%, yếu &lt; 1.5%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('11', $y, $q, $ttm), $this->bsAvg('2', $y, $q))],
            ['alias' => 'SEC_ROEA', 'name' => 'ROEA (LNST chủ sở hữu/VCSH bình quân)', 'group' => 'Sinh lời (Chứng khoán)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Tỷ suất sinh lời trên vốn chủ sở hữu bình quân. Ngưỡng tham khảo: Tốt ≥ 15%, trung bình 8–15%, yếu &lt; 8%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('1101', $y, $q, $ttm), $this->bsAvg('4', $y, $q))],
            ['alias' => 'SEC_NPM', 'name' => 'Biên LNST (LNST/DT hoạt động)', 'group' => 'Sinh lời (Chứng khoán)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Biên lợi nhuận ròng trên doanh thu hoạt động — phản ánh khả năng kiểm soát chi phí toàn ngành nghề (môi giới, margin, tự doanh, tư vấn...). Ngưỡng tham khảo: Tốt ≥ 25%, trung bình 10–25%, yếu &lt; 10%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('11', $y, $q, $ttm), $this->isTTM('112', $y, $q, $ttm))],
            ['alias' => 'SEC_BROKERAGE', 'name' => 'Tỷ trọng doanh thu môi giới', 'group' => 'Cơ cấu doanh thu (Chứng khoán)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Tỷ trọng doanh thu môi giới trên doanh thu hoạt động. Không có ngưỡng tốt/xấu phổ quát — đây là lựa chọn mô hình kinh doanh: tỷ trọng cao ổn định hơn nhưng nhạy với thanh khoản thị trường chung; tỷ trọng thấp thường đi kèm tỷ trọng margin/tự doanh cao hơn (rủi ro khác).', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('106', $y, $q, $ttm), $this->isTTM('112', $y, $q, $ttm))],
            ['alias' => 'SEC_MARGIN', 'name' => 'Tỷ trọng lãi cho vay & phải thu (margin)', 'group' => 'Cơ cấu doanh thu (Chứng khoán)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Tỷ trọng lãi từ cho vay ký quỹ & phải thu trên doanh thu hoạt động. Không có ngưỡng tốt/xấu phổ quát — nguồn thu ổn định khi thị trường tăng nhưng rủi ro tăng khi thị trường giảm mạnh (call margin, mất vốn khách hàng); nên xem cùng chỉ số Dư nợ margin/VCSH để đánh giá đòn bẩy rủi ro.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('103', $y, $q, $ttm), $this->isTTM('112', $y, $q, $ttm))],
            ['alias' => 'SEC_PROPRIETARY', 'name' => 'Tỷ trọng doanh thu tự doanh', 'group' => 'Cơ cấu doanh thu (Chứng khoán)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Tỷ trọng lãi từ tự doanh (FVTPL + HTM + AFS + phái sinh phòng ngừa rủi ro) trên doanh thu hoạt động — hoàn thiện bộ 3 cơ cấu doanh thu môi giới/margin/tự doanh. Không có ngưỡng tốt/xấu phổ quát — tỷ trọng cao mang lại tiềm năng lợi nhuận lớn hơn nhưng biến động mạnh theo thị trường (có thể lỗ khi thị trường giảm), khác hẳn tính ổn định của môi giới.',
             'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->sumTTM(['101', '102', '104', '105'], $y, $q, $ttm), $this->isTTM('112', $y, $q, $ttm))],
            ['alias' => 'SEC_CIR', 'name' => 'Chi phí HĐ / Doanh thu HĐ', 'group' => 'Hiệu quả (Chứng khoán)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Tỷ lệ chi phí hoạt động trên doanh thu hoạt động — càng thấp càng hiệu quả vận hành. Ngưỡng tham khảo: Tốt ≤ 50%, trung bình 50–70%, yếu &gt; 70%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct(abs($this->isTTM('214', $y, $q, $ttm)), $this->isTTM('112', $y, $q, $ttm))],
            ['alias' => 'SEC_CURRENT', 'name' => 'Thanh toán hiện hành', 'group' => 'Cân đối & đòn bẩy (Chứng khoán)', 'unit' => 'scalar',
             'desc' => 'Tài sản ngắn hạn / Nợ ngắn hạn — khả năng thanh toán nợ ngắn hạn. Ngưỡng tham khảo: Tốt ≥ 1.5 lần, trung bình 1–1.5 lần, yếu &lt; 1 lần.', 'fn' => fn ($y, $q) => $this->times($this->bs('101', $y, $q), $this->bs('301', $y, $q))],
            ['alias' => 'SEC_ETA', 'name' => 'VCSH / Tổng tài sản', 'group' => 'Cân đối & đòn bẩy (Chứng khoán)', 'unit' => '%',
             'desc' => 'Tỷ lệ vốn chủ sở hữu trên tổng tài sản. Ngưỡng tham khảo: Tốt ≥ 40%, trung bình 25–40%, yếu &lt; 25%.', 'fn' => fn ($y, $q) => $this->pct($this->bs('4', $y, $q), $this->bs('2', $y, $q))],
            ['alias' => 'SEC_LEV', 'name' => 'Đòn bẩy (Tổng TS/VCSH)', 'group' => 'Cân đối & đòn bẩy (Chứng khoán)', 'unit' => 'scalar',
             'desc' => 'Hệ số đòn bẩy tài chính. Ngưỡng tham khảo: Tốt ≤ 3 lần, trung bình 3–5 lần, yếu &gt; 5 lần.', 'fn' => fn ($y, $q) => $this->times($this->bs('2', $y, $q), $this->bs('4', $y, $q))],
            ['alias' => 'SEC_MARGIN_LEVERAGE', 'name' => 'Dư nợ cho vay margin / VCSH', 'group' => 'Cân đối & đòn bẩy (Chứng khoán)', 'unit' => '%',
             'desc' => 'Dư nợ cho vay ký quỹ (margin) trên vốn chủ sở hữu — chỉ số UBCKNN giám sát trực tiếp (giới hạn cho vay margin không vượt quá 200% VCSH). Ngưỡng tham khảo: Tốt ≤ 150% (còn dư địa an toàn để tăng trưởng margin), trung bình 150–200%, vượt 200% là vi phạm giới hạn quy định.',
             'fn' => fn ($y, $q) => $this->pct($this->bs('1010104', $y, $q), $this->bs('4', $y, $q))],
            ['alias' => 'SEC_REV_GROWTH', 'name' => 'Tăng trưởng DT hoạt động (YoY)', 'group' => 'Tăng trưởng (Chứng khoán)', 'unit' => '%',
             'desc' => 'Tăng trưởng doanh thu hoạt động (TTM) so với cùng kỳ. Ngưỡng tham khảo: Tăng tốt ≥ 10%/năm, đi ngang 0–10%, suy giảm &lt; 0%.', 'fn' => fn ($y, $q) => $this->yoyTTM('112', $y, $q)],
            ['alias' => 'SEC_NP_GROWTH', 'name' => 'Tăng trưởng LNST (YoY)', 'group' => 'Tăng trưởng (Chứng khoán)', 'unit' => '%',
             'desc' => 'Tăng trưởng lợi nhuận sau thuế (TTM) so với cùng kỳ. Ngưỡng tham khảo: Tăng tốt ≥ 10%/năm, đi ngang 0–10%, suy giảm &lt; 0%.', 'fn' => fn ($y, $q) => $this->yoyTTM('11', $y, $q)],
        ];
    }

    private function insuranceDefinitions(): array
    {
        return [
            ['alias' => 'INS_ROAA', 'name' => 'ROAA (LNST/Tổng TS bình quân)', 'group' => 'Sinh lời (Bảo hiểm)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Tỷ suất sinh lời trên tổng tài sản bình quân. Ngưỡng tham khảo: Tốt ≥ 1.5%, trung bình 0.7–1.5%, yếu &lt; 0.7%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('35', $y, $q, $ttm), $this->bsAvg('2', $y, $q))],
            ['alias' => 'INS_ROEA', 'name' => 'ROEA (LNST cổ đông mẹ/VCSH bình quân)', 'group' => 'Sinh lời (Bảo hiểm)', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Tỷ suất sinh lời trên vốn chủ sở hữu bình quân. Ngưỡng tham khảo: Tốt ≥ 12%, trung bình 7–12%, yếu &lt; 7%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('37', $y, $q, $ttm), $this->bsAvg('302', $y, $q))],
            ['alias' => 'INS_LOSS', 'name' => 'Tỷ lệ bồi thường (Loss ratio)', 'group' => 'Hiệu quả bảo hiểm', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Bồi thường thuộc phần giữ lại / Doanh thu thuần HĐ bảo hiểm — đo mức độ rủi ro bồi thường thực tế phát sinh so với phí thu được. Ngưỡng tham khảo: Tốt ≤ 60%, trung bình 60–75%, yếu &gt; 75%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('11', $y, $q, $ttm), $this->isTTM('7', $y, $q, $ttm))],
            ['alias' => 'INS_EXPENSE', 'name' => 'Tỷ lệ chi phí (Expense ratio)', 'group' => 'Hiệu quả bảo hiểm', 'unit' => '%', 'usesTtm' => true,
             'desc' => '(Chi phí bán hàng + quản lý) / Doanh thu thuần HĐ bảo hiểm — hiệu quả vận hành và khai thác. Ngưỡng tham khảo: Tốt ≤ 30%, trung bình 30–40%, yếu &gt; 40%.', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->sumTTM(['20', '21'], $y, $q, $ttm), $this->isTTM('7', $y, $q, $ttm))],
            ['alias' => 'INS_COMBINED', 'name' => 'Tỷ lệ kết hợp (Combined ratio)', 'group' => 'Hiệu quả bảo hiểm', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Bồi thường + chi phí / Doanh thu thuần HĐ bảo hiểm = Loss ratio + Expense ratio — thước đo tổng hợp quan trọng nhất của hoạt động bảo hiểm gốc, độc lập với kết quả đầu tư. Ngưỡng tham khảo: Tốt ≤ 95% (HĐ bảo hiểm gốc có lãi), trung bình 95–100%, &gt; 100% là hoạt động bảo hiểm thuần lỗ (phải bù đắp bằng lợi nhuận đầu tư).', 'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->sumTTM(['11', '20', '21'], $y, $q, $ttm), $this->isTTM('7', $y, $q, $ttm))],
            ['alias' => 'INS_FIN', 'name' => 'Đóng góp LN hoạt động tài chính', 'group' => 'Hiệu quả bảo hiểm', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Lợi nhuận HĐ tài chính / Tổng lợi nhuận kế toán. Không có ngưỡng tốt/xấu phổ quát — tỷ trọng cao (kể cả &gt; 100% khi HĐ bảo hiểm gốc lỗ) cho thấy công ty đang dựa vào đầu tư để bù đắp lợi nhuận, nên đọc cùng Combined ratio để biết lợi nhuận có bền vững hay không.',
             'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('25', $y, $q, $ttm), $this->isTTM('29', $y, $q, $ttm))],
            ['alias' => 'INS_RETENTION', 'name' => 'Tỷ lệ giữ lại phí (Retention ratio)', 'group' => 'Hiệu quả bảo hiểm', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Doanh thu thuần HĐ bảo hiểm / (Thu phí bảo hiểm gốc + Thu phí nhận tái bảo hiểm) — mức độ công ty giữ lại rủi ro thay vì nhượng tái bảo hiểm. Không có ngưỡng tốt/xấu phổ quát — giữ lại cao hơn tăng tiềm năng lợi nhuận nhưng cũng tăng rủi ro bồi thường tập trung; giữ lại thấp an toàn hơn nhưng chia sẻ bớt lợi nhuận cho nhà tái bảo hiểm.',
             'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('7', $y, $q, $ttm), $this->sumTTM(['1', '2'], $y, $q, $ttm))],
            ['alias' => 'INS_INVESTMENT_YIELD', 'name' => 'Hiệu suất đầu tư', 'group' => 'Hiệu quả bảo hiểm', 'unit' => '%', 'usesTtm' => true,
             'desc' => 'Doanh thu hoạt động tài chính / Bình quân tài sản đầu tư tài chính ngắn+dài hạn — tách bạch hiệu quả đầu tư khỏi hiệu quả bảo hiểm gốc. Không có ngưỡng cố định (phụ thuộc mặt bằng lãi suất/thị trường chứng khoán từng giai đoạn) — nên so sánh theo xu hướng hoặc với lãi suất tiền gửi/trái phiếu cùng kỳ làm chuẩn tối thiểu.',
             'fn' => fn ($y, $q, $ttm = true) => $this->pct($this->isTTM('23', $y, $q, $ttm), $this->avgSumBs(['10102', '10205'], $y, $q))],
            ['alias' => 'INS_ETA', 'name' => 'VCSH / Tổng tài sản', 'group' => 'Cân đối & đòn bẩy (Bảo hiểm)', 'unit' => '%',
             'desc' => 'Tỷ lệ vốn chủ sở hữu trên tổng tài sản. Ngưỡng tham khảo: Tốt ≥ 10%, trung bình 6–10%, yếu &lt; 6%.', 'fn' => fn ($y, $q) => $this->pct($this->bs('302', $y, $q), $this->bs('2', $y, $q))],
            ['alias' => 'INS_LEV', 'name' => 'Đòn bẩy (Tổng TS/VCSH)', 'group' => 'Cân đối & đòn bẩy (Bảo hiểm)', 'unit' => 'scalar',
             'desc' => 'Hệ số đòn bẩy tài chính. Ngưỡng tham khảo: Tốt ≤ 10 lần, trung bình 10–15 lần, yếu &gt; 15 lần.', 'fn' => fn ($y, $q) => $this->times($this->bs('2', $y, $q), $this->bs('302', $y, $q))],
            ['alias' => 'INS_REV_GROWTH', 'name' => 'Tăng trưởng DT thuần BH (YoY)', 'group' => 'Tăng trưởng (Bảo hiểm)', 'unit' => '%',
             'desc' => 'Tăng trưởng doanh thu thuần HĐ bảo hiểm (TTM) so với cùng kỳ. Ngưỡng tham khảo: Tăng tốt ≥ 10%/năm, đi ngang 0–10%, suy giảm &lt; 0%.', 'fn' => fn ($y, $q) => $this->yoyTTM('7', $y, $q)],
            ['alias' => 'INS_NP_GROWTH', 'name' => 'Tăng trưởng LNST (YoY)', 'group' => 'Tăng trưởng (Bảo hiểm)', 'unit' => '%',
             'desc' => 'Tăng trưởng lợi nhuận sau thuế (TTM) so với cùng kỳ. Ngưỡng tham khảo: Tăng tốt ≥ 10%/năm, đi ngang 0–10%, suy giảm &lt; 0%.', 'fn' => fn ($y, $q) => $this->yoyTTM('35', $y, $q)],
            ['alias' => 'INS_RESERVE_GROWTH', 'name' => 'Tăng trưởng dự phòng nghiệp vụ (YoY)', 'group' => 'Tăng trưởng (Bảo hiểm)', 'unit' => '%',
             'desc' => 'Tăng trưởng tổng dự phòng nghiệp vụ (phí, toán học, bồi thường, dao động lớn, chia lãi, bảo đảm cân đối) so với cùng kỳ — theo dõi quy mô trách nhiệm bảo hiểm. Không có ngưỡng tốt/xấu phổ quát — tăng nhanh hơn tăng trưởng phí thường phản ánh mở rộng quy mô hợp đồng dài hạn hoặc thận trọng hơn trong trích lập, nên đọc cùng Tăng trưởng DT thuần BH.',
             'fn' => fn ($y, $q) => $this->yoyBs('30103', $y, $q)],
        ];
    }
}
