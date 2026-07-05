<?php
/**
 * MScoreWriter trait
 *
 * @author: tuanha
 * @date: 29-Sept-2022
 */
namespace App\Jobs\Financials\Writers;

use App\Jobs\Financials\Calculators\MScoreCalculator;

trait MScoreWriter
{
    /**
     * Write M-Score
     *
     * @param \App\Jobs\Financials\Calculators\MScoreCalculator $calculator
     * @param  int $year
     * @param  int $quarter
     * @return $this
     */
    protected function writeMScore(MScoreCalculator $calculator, $year, $quarter)
    {
        $values1 = [];
        $values2 = [];
        $values3 = [];
        $values4 = [];
        $values5 = [];
        $values6 = [];
        $values7 = [];
        $values8 = [];
        $values9 = [];
        $values10 = [];
        for ($i = 1; $i <= config('settings.limits'); $i++) {
            $calculator->calculateMScores($year, $quarter);
            array_push($values1, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => !is_null($calculator->dsri) ? round($calculator->dsri, 4) : ''
            ]);
            array_push($values2, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => !is_null($calculator->gmi) ? round($calculator->gmi, 4) : ''
            ]);
            array_push($values3, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => !is_null($calculator->aqi) ? round($calculator->aqi, 4) : ''
            ]);
            array_push($values4, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => !is_null($calculator->sgi) ? round($calculator->sgi, 4) : ''
            ]);
            array_push($values5, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => !is_null($calculator->depi) ? round($calculator->depi, 4) : ''
            ]);
            array_push($values6, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => !is_null($calculator->sgai) ? round($calculator->sgai, 4) : ''
            ]);
            array_push($values7, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => !is_null($calculator->tata) ? round($calculator->tata, 4) : ''
            ]);
            array_push($values8, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => !is_null($calculator->lvgi) ? round($calculator->lvgi, 4) : ''
            ]);
            array_push($values9, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => !is_null($calculator->m8Score) ? round($calculator->m8Score, 4) : ''
            ]);
            array_push($values10, [
                'period' => $quarter != 0 ? "Q$quarter $year" : "$year",
                'year' => $year,
                'quarter' => $quarter,
                'value' => !is_null($calculator->m5Score) ? round($calculator->m5Score, 4) : ''
            ]);
            $previous = getPreviousPeriod($year, $quarter);
            $year = $previous['year'];
            $quarter = $previous['quarter'];
        }
        array_push($this->content, [
            'name' => 'Chỉ số thay đổi kỳ thu tiền bình quân (DSRI)',
            'alias' => 'DSRI',
            'group' => "Phân tích mô hình Beneish M-Score",
            'unit' => 'scalar',
            'description' => '<strong>Dấu hiệu:</strong> Chỉ số này so sánh tỷ lệ các khoản phải thu khách hàng trên tổng doanh thu của một năm so với năm trước đó, DSRI > 1 phản ánh phải thu trên tổng doanh thu của một năm lớn hơn năm trước đó, việc gia tăng bất thường các khoản phải thu sẽ phản ánh có thể có những sai lệch trong cách ghi nhận doanh thu doanh nghiệp. Ghi tăng khoản phải thu sẽ giúp cải thiện doanh thu doanh nghiệp, nhờ vậy giúp các nhà điều hành của công ty có thể đạt được mục tiêu doanh thu đề ra trong năm',
            'values' => $values1
        ]);
        array_push($this->content, [
            'name' => 'Chỉ số tỷ lệ lãi gộp (GMI)',
            'alias' => 'GMI',
            'group' => "Phân tích mô hình Beneish M-Score",
            'unit' => 'scalar',
            'description' => '<strong>Động cơ:</strong> Đây là chỉ số đo lường tỷ lệ lãi gộp của năm trước so với năm nay. GMI > 1 phản ánh sự suy giảm lãi gộp, khi một công ty đang chứng kiến xu hướng giảm biên lợi nhuận gộp, các nhà điều hành sẽ có động lực hơn cho việc thực hiện một số mánh khóe nhằm cải thiện kết quả kinh doanh trên báo cáo',
            'values' => $values2
        ]);
        array_push($this->content, [
            'name' => 'Chỉ số chất lượng tài sản (AQI)',
            'alias' => 'AQI',
            'group' => "Phân tích mô hình Beneish M-Score",
            'unit' => 'scalar',
            'description' => '<strong>Dấu hiệu:</strong>  Chỉ số chất lượng tài sản đánh giá mức tăng của tài sản dài hạn ngoài tài sản cố định (PPE), PPE là loại tài sản dài hạn mang lại lợi ích kinh tế trong tương lai một cách chắc chắn. Các thành phần khác của tài sản dài hạn như phải thu dài hạn của khách hàng, đầu tư tàu chính, chi phí trả trước đều không mang lại lợi ích cốt lõi cho hoạt động kinh doanh của doanh nghiệp. AQI > 1 phản ánh nền tảng cốt lõi của doanh nghiệp giảm sút hoặc công ty có thể đánh giá việc vốn hóa quá mức các chi phí của công ty. Có thể thấy rằng, nếu công ty không tính các chi phí ấy vào kì kinh doanh hiện tại mà vốn hóa thành tài sản dài hạn rồi khấu hao cho nhiều năm sau sẽ làm giảm đáng kể chi phí của công ty trong năm ấy. Và lợi nhuận của công ty này sẽ được cải thiện dù rằng nó không xuất phát từ hiệu quả hoạt động kinh doanh cốt lõi của mình. <strong>PPE = Tài sản cố định hữu hình+ Tài sản cố định thuê tài chính + Tài sản dở dang dài hạn + Bất động sản đầu tư</strong>',
            'values' => $values3
        ]);
        array_push($this->content, [
            'name' => 'Chỉ số tăng trưởng doanh thu bán hàng (SGI)',
            'alias' => 'SGI',
            'group' => "Phân tích mô hình Beneish M-Score",
            'unit' => 'scalar',
            'description' => '<strong>Động cơ:</strong> Chỉ số thể hiện sự so sánh giữa doanh thu năm nay và doanh thu năm trước. SGI > 1 thể hiện sự tăng trưởng dương trong doanh thu của doanh nghiệp. Thực chất tăng trưởng doanh thu không phải là chỉ số đo lường sự thao túng lợi nhuận, tuy nhiên với vị thế tăng trưởng nhanh chóng và nhu cầu tăng vốn rất cao của những công ty dạng này sẽ đặt một áp lực không hề nhỏ lên nhà điều hành. Do đó, người người đứng đầu sẽ chịu áp lực phải duy trì mức tăng trưởng đủ hấp dẫn và điều này trong thực tế không hề dễ dàng gì, do vậy những công ty này sẽ có nhiều động cơ để thao túng lợi nhuận hơn',
            'values' => $values4
        ]);
        array_push($this->content, [
            'name' => 'Chỉ số tỷ lệ khấu hao (DEPI)',
            'alias' => 'DEPI',
            'group' => "Phân tích mô hình Beneish M-Score",
            'unit' => 'scalar',
            'description' => '<strong>Dấu hiệu:</strong> Đo lường tỷ lệ khấu hao năm trước so với năm sau. DEPI > 1 có nghĩa rằng tài sản đang bị khấu hao ở mức độ chậm hơn. Điều này chỉ ra rằng công ty có thể tìm cách kéo dài thời gian khấu hao hay một số thủ thuật tài chính khác nhằm ghi nhận giảm chi phí khấu hao trong kỳ kinh doanh. Khi chi phí khấu hao giảm, lợi nhuận của công ty sẽ được cải thiện',
            'values' => $values5
        ]);
        array_push($this->content, [
            'name' => 'Chỉ số chi phí bán hàng và quản lý doanh nghiệp (SGAI)',
            'alias' => 'SGAI',
            'group' => "Phân tích mô hình Beneish M-Score",
            'unit' => 'scalar',
            'description' => '<strong>Động cơ:</strong> Chỉ số này đánh giá sự thay đổi tỉ trọng chi phí bán hàng và quản lý doanh nghiệp trên mức doanh thu thuần của năm sau so với năm trước. SGAI > 1 phản ánh sự gia tăng của chi phí bán hàng và quản lý doanh nghiệp trên tổng doanh thu, tương tự như tác động của lợi nhuận gộp biên giảm, điều này có thể tạo ra cảm giác tiêu cực cho nhà đầu tư. Do đó, nhà quản lý sẽ có động lực để gian lận tài chính',
            'values' => $values6
        ]);
        array_push($this->content, [
            'name' => 'Chỉ số dồn tích trên tổng tài sản (TATA)',
            'alias' => 'TATA',
            'group' => "Phân tích mô hình Beneish M-Score",
            'unit' => 'scalar',
            'description' => '<strong>Dấu hiệu:</strong> Với tính chất dồn tích của kế toán hiện hành, thời điểm ghi nhận doanh thu và chi phí sẽ khác với khi thực chất dòng tiền về với doanh nghiệp, sự khác biệt lớn giữa lợi nhuận kế toán sau thuế và dòng tiền thuần từ hoạt động kinh doanh sẽ đem lại nhiều nghi ngờ cho nhà đầu tư về khả năng công ty sử dụng một số chính sách kế toán không hợp lý, việc chia cho tổng tài sản là để so sánh giữa các doanh nghiệp có quy mô khác nhau. TATA &gt; 0 nghĩa là lợi nhuận kế toán lớn hơn dòng tiền HĐKD — doanh nghiệp nào chủ yếu phát sinh lợi nhuận kế toán mà không phát sinh dòng tiền từ HĐKD sẽ có rủi ro thao túng lợi nhuận cao hơn',
            'values' => $values7
        ]);
        array_push($this->content, [
            'name' => 'Chỉ số đòn bẩy tài chính (LVGI)',
            'alias' => 'LVGI',
            'group' => "Phân tích mô hình Beneish M-Score",
            'unit' => 'scalar',
            'description' => '<strong>Động cơ:</strong> LVGI đo lường đòn bẩy tài chính trong năm so với năm trước. LVGI > 1 thể hiện sự tăng lên trong đòn bẩy tài chính và sẽ làm xấu đi một số chỉ số tài chính của doanh nghiệp, đây là 1 động lực để thao túng lợi nhuận nhằm đảm bảo các chỉ số đo lường tín dụng doanh nghiệp ở mức tốt',
            'values' => $values8
        ]);
        array_push($this->content, [
            'name' => 'M-Score (Mô hình 8 biến)',
            'alias' => 'M8-Score',
            'group' => "Phân tích mô hình Beneish M-Score",
            'unit' => 'scalar',
            'description' => 'Chỉ số đánh giá khả năng doanh nghiệp gian lận báo cáo tài chính bằng các thủ thuật kế toán, được đề xuất bới <strong>Beneish </strong>. Nếu <span style="color:rgb(230,76,76);"><strong>M-Score &gt; -1.78</strong></span> thì doanh nghiệp có khả năng gian lận báo cáo tài chính, nếu <span style="color:hsl(150,75%,60%);"><strong>M-Score &lt;= -1.78</strong></span> thì doanh nghiệp nhiều khả năng không gian lận báo cáo taif chính. Chỉ số này được tính toán dựa trên số liệu của báo cáo tài chính 4 quý gần nhất bao gồm quý đang xét hoặc dựa trên báo cáo tài chính của cả năm. <strong style="color:#FF00FF;">M8-Score = –4.84 + 0.920 * DSR + 0.528 * GMI + 0.404 * AQI + 0.892 * SGI + 0.115 * DEPI – 0.172 * SGAI + 4.679 * TATA – 0.327 * LEVI</strong>',
            'values' => $values9
        ]);
        array_push($this->content, [
            'name' => 'M-Score (Mô hình 5 biến)',
            'alias' => 'M5-Score',
            'group' => "Phân tích mô hình Beneish M-Score",
            'unit' => 'scalar',
            'description' => 'Chỉ số đánh giá khả năng doanh nghiệp gian lận báo cáo tài chính bằng các thủ thuật kế toán, được đề xuất bới <strong>Beneish </strong>. Nếu <span style="color:rgb(230,76,76);"><strong>M-Score &gt; -2.22</strong></span> thì doanh nghiệp có khả năng gian lận báo cáo tài chính, nếu <span style="color:hsl(150,75%,60%);"><strong>M-Score &lt;= -2.22</strong></span> thì doanh nghiệp nhiều khả năng không gian lận báo cáo taif chính. Chỉ số này được tính toán dựa trên số liệu của báo cáo tài chính 4 quý gần nhất bao gồm quý đang xét hoặc dựa trên báo cáo tài chính của cả năm. <strong style="color:#FF00FF;">M5-Score = -6.065 + 0.823 * DSRI + 0.906 * GMI + 0.593 * AQI + 0.717 * SGI + 0.107 * DEP</strong>',
            'values' => $values10
        ]);
        return $this;
    }
}
