# KStock — Vietnamese Stock Financial Analysis Platform

KStock is a Laravel application that pulls financial statements (balance sheet,
income statement, cash flow) for Vietnamese stock‑market symbols from an
**external market‑data API** and computes a large catalogue of financial‑analysis
ratios from them. On top of that statement/analysis core it provides a company
directory with per‑company profile hubs (profile, fundamentals, OHLCV price
charts), a side‑by‑side stock comparison tool, and a per‑admin watchlist.

A key strength is **coverage across company types**: the engine automatically
detects whether a symbol is a **normal (non‑financial) enterprise**, a **bank**,
a **securities/brokerage firm**, or an **insurer**, and applies the ratio set and
formulas appropriate to that type (financial institutions use industry‑standard
metrics whose statement line‑items differ entirely from ordinary companies).

---

## Features

- **Financial statements** — pull, store, list, view and bulk‑delete quarterly &
  annual statements per symbol; asynchronous processing via queued jobs with
  real‑time completion notifications.
- **Automated ratio analysis** — 100+ ratios computed per statement, grouped and
  presented with formulas, reference thresholds, and a rolling multi‑period
  window (TTM‑adjusted for quarterly reports).
- **Company hub** — profile, fundamentals and interactive OHLCV price charts.
- **Comparison tool** — compare ratios side‑by‑side across multiple tickers.
- **Watchlist** — per‑admin followed tickers with live price / P/E / market cap.
- **Role‑based admin panel** — authentication, authorization, settings.

---

## Technical stack

| Layer      | Technology                                            |
|------------|-------------------------------------------------------|
| Backend    | PHP 8.2+, Laravel 12                                   |
| Database   | SQLite (dev) / MySQL (production)                      |
| Frontend   | AdminLTE 3, Bootstrap 4, jQuery, Highcharts           |
| Realtime   | Pusher (broadcast events)                             |
| Queue      | Laravel queues (statement pull + analysis run)        |

Market‑data credentials (API base URL and access token) are **not** stored in the
repository — they are supplied at runtime through the application's DB‑backed
settings, so no endpoint or secret is committed to source control.

---

## Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Configure your database in .env (SQLite or MySQL), then migrate + seed
php artisan migrate
php artisan bkscms:initAuth --scope=all      # seeds roles/permissions/admin users

# 4. (Optional) build front-end assets — pre-built assets ship under public/
npm install && npm run dev

# 5. Serve + process the queue
php artisan serve
php artisan queue:work
```

> Configure the market‑data API endpoint and token via the in‑app **Settings**
> screen after logging in. Default seeded admin credentials are configurable
> through `SUPERADMIN_*` / `ADMIN_*` environment variables — **change them and
> remove the default users before any non‑local deployment.**

### Useful commands

```bash
php artisan symbols:sync FPT VNM     # refresh the local symbols master table
php artisan analysis:recompute        # re-run analysis for saved statements
vendor/bin/phpunit                    # run the test suite
```

---

## Financial ratios covered

The analysis engine routes each symbol by company type:

| Company type            | Ratio set applied                     |
|-------------------------|---------------------------------------|
| Normal enterprise       | Full catalogue below (§1)             |
| Bank                    | Banking metric set (§2)               |
| Securities / brokerage  | Securities metric set (§3)            |
| Insurance               | Insurance metric set (§4)             |
| Fund / investment fund  | *Not supported* (analysis skipped)    |

**Conventions used in the formulas below**
- **LNST** = profit after tax; **LNTT** = profit before tax; **VCSH** = owners'
  equity; **TSCĐ** = fixed assets; **HTK** = inventory; **TSNH** = current assets;
  **CFO/CFF** = net cash flow from operating / financing activities.
- **EBIT** = LNTT + interest expense. **EBITDA** = EBIT + depreciation & amortization.
- **FCF** (free cash flow) = CFO − CAPEX; **CAPEX** = cash spent on fixed‑asset
  purchases + construction in progress.
- **TTM** (trailing twelve months): for a quarterly report, "flow" items (income
  statement / cash flow) are accumulated over the latest 4 quarters so they are
  comparable with balance‑sheet stocks; annual reports use the year value as‑is.
- **bình quân** ("average") = average of opening and closing balances of the period.
- Every ratio is computed over a rolling window of the last *N* periods
  (`settings.limits`) walking backwards.

---

### §1. Normal (non‑financial) enterprises

#### 1.1 Profitability — *Chỉ số sinh lời*

| Ratio | Formula | Unit | Reference threshold |
|-------|---------|------|---------------------|
| ROAA — Return on Average Assets | 100% × LNST cổ đông công ty mẹ (TTM) / Tổng tài sản bình quân | % | Âm = thua lỗ; ≥ 7.5% = sinh lời cao |
| ROTA — Return on Total Assets (EBIT) | 100% × EBIT (TTM) / Tổng tài sản bình quân | % | Âm = thua lỗ; ≥ 7.5% = sinh lời cao |
| ROA — Return on Assets | 100% × LNST cổ đông công ty mẹ (TTM) / Tổng tài sản | % | Âm = thua lỗ; ≥ 7.5% = sinh lời cao |
| ROCE — Return on Capital Employed | 100% × EBIT (TTM) / (Tổng tài sản bình quân − Nợ ngắn hạn bình quân) | % | Âm = thua lỗ; ≥ 15% = sinh lời cao |
| ROEA — Return on Average Equity | 100% × LNST cổ đông công ty mẹ (TTM) / VCSH bình quân | % | Âm = thua lỗ; ≥ 15% = sinh lời cao |
| ROE — Return on Equity | 100% × LNST cổ đông công ty mẹ (TTM) / VCSH | % | Âm = thua lỗ; ≥ 15% = sinh lời cao |
| ROS — Return on Sales | 100% × LNST / Doanh thu thuần | % | Âm = thua lỗ; ≥ 10% = sinh lời cao (Buffett: > 20% bền vững, < 10% cạnh tranh gay gắt) |
| ROS2 — Return on Sales (parent) | 100% × LNST cổ đông công ty mẹ / Doanh thu thuần | % | Âm = thua lỗ; ≥ 10% = sinh lời cao |
| EBITDA Margin (balance + income) | 100% × EBITDA / Doanh thu thuần | % | — |
| EBITDA Margin (cash flow) | 100% × EBITDA / Doanh thu thuần | % | — |
| EBIT Margin | 100% × EBIT / Doanh thu thuần | % | — |
| Gross Profit Margin | 100% × Lợi nhuận gộp / Doanh thu thuần | % | Buffett: > 40% lợi thế bền vững, < 20% cạnh tranh gay gắt |

#### 1.2 Liquidity & solvency — *Chỉ số thanh toán*

| Ratio | Formula | Unit | Reference threshold |
|-------|---------|------|---------------------|
| Overall Solvency | Tổng tài sản / Nợ phải trả | × | — |
| Current Ratio | Tài sản ngắn hạn / Nợ ngắn hạn | × | Buffett: chủ yếu quan trọng với DN nhỏ; DN có lợi thế bền vững có thể < 1 |
| Quick Ratio 1 | (Tài sản ngắn hạn − HTK) / Nợ ngắn hạn | × | — |
| Quick Ratio 2 | (Tài sản ngắn hạn − HTK − Phải thu ngắn hạn) / Nợ ngắn hạn | × | < 0.3 yếu; ≥ 0.3 an toàn |
| Cash Ratio | Tiền & tương đương tiền / Nợ ngắn hạn | × | < 0.5 rủi ro; ≥ 0.5 an toàn |
| Interest Coverage | EBIT / Chi phí lãi vay | × | < 1 xấu; 1–2 thấp; ≥ 2 tốt (Buffett > 6.67) |

#### 1.3 Cash‑flow — *Chỉ số dòng tiền*

| Ratio | Formula | Unit | Reference threshold |
|-------|---------|------|---------------------|
| Liability Coverage by CFO | CFO (TTM) / Tổng nợ bình quân | × | CFO < 0 xấu; 0–<1 chưa đủ; ≥ 1 đủ trang trải |
| Current Liability Coverage by CFO | CFO (TTM) / Nợ ngắn hạn bình quân | × | CFO < 0 xấu; 0–<1 chưa đủ; ≥ 1 đủ trang trải |
| Long‑term Liability Coverage by CFO | CFO (TTM) / Nợ dài hạn bình quân | × | CFO < 0 xấu; 0–<1 chưa đủ; ≥ 1 đủ trang trải |
| CFO / Revenue | 100% × CFO / Doanh thu thuần | % | < 0 xấu (dù có lãi kế toán); dương = lành mạnh |
| FCF / Revenue | 100% × FCF / Doanh thu thuần | % | < 0 cảnh báo; ≥ 10% ổn định nhiều năm = cỗ máy tạo tiền |
| FCF / CFO | 100% × FCF / CFO | % | < 0 cảnh báo |
| Liability Coverage by FCF | FCF (TTM) / Tổng nợ bình quân | × | FCF < 0 xấu; 0–<1 chưa đủ; ≥ 1 đủ trang trải |
| Current Liability Coverage by FCF | FCF (TTM) / Nợ ngắn hạn bình quân | × | FCF < 0 xấu; 0–<1 chưa đủ; ≥ 1 đủ trang trải |
| Long‑term Liability Coverage by FCF | FCF (TTM) / Nợ dài hạn bình quân | × | FCF < 0 xấu; 0–<1 chưa đủ; ≥ 1 đủ trang trải |
| Interest Coverage by FCF | (FCF + Lãi vay đã trả + Thuế TNDN đã trả) / Lãi vay đã trả | × | — |
| Asset Efficiency for FCF | 100% × FCF (TTM) / Tổng tài sản bình quân | % | < 0 cảnh báo |
| Cash Generating Power | 100% × CFO / (CFO + Dòng tiền vào từ đầu tư + Dòng tiền vào từ tài chính) | % | CFO < 0 xấu; ≥ 15% ổn định nhiều năm = cỗ máy tạo tiền |
| External Financing | CFF / CFO | × | — |

#### 1.4 CAPEX — *Chỉ số CAPEX*

| Ratio | Formula | Unit | Reference threshold |
|-------|---------|------|---------------------|
| CFO / CAPEX | CFO / \|CAPEX\| | × | — |
| CAPEX / Net Profit | 100% × \|CAPEX\| / LNST | % | < 50% ổn định nhiều năm ⇒ có lợi thế cạnh tranh; < 25% ⇒ lợi thế bền vững |

#### 1.5 Operating effectiveness — *Chỉ số hiệu quả hoạt động*

| Ratio | Formula | Unit | Reference threshold |
|-------|---------|------|---------------------|
| Receivable turnover | Doanh thu thuần (TTM) / Phải thu KH ngắn hạn bình quân | vòng | — |
| Average collection period | 365 / Vòng quay phải thu khách hàng | ngày | — |
| Inventory turnover | Giá vốn hàng bán (TTM) / HTK bình quân | vòng | — |
| Average age of inventory | 365 / Vòng quay hàng tồn kho | ngày | — |
| Accounts‑payable turnover | Giá vốn hàng bán (TTM) / Phải trả người bán ngắn hạn bình quân | vòng | — |
| Average payable duration | 365 / Vòng quay phải trả nhà cung cấp | ngày | — |
| Cash conversion cycle | Thời gian tồn kho + Thời gian phải thu − Thời gian trả tiền NCC | ngày | Âm = tốt (chiếm dụng vốn tốt); xu hướng giảm dần = tích cực |
| Fixed‑asset turnover | Doanh thu thuần (TTM) / TSCĐ bình quân | vòng | — |
| Total‑asset turnover | Doanh thu thuần (TTM) / Tổng tài sản bình quân | vòng | — |
| Equity turnover | Doanh thu thuần (TTM) / VCSH bình quân | vòng | — |

#### 1.6 Financial leverage — *Chỉ số đòn bẩy tài chính*

| Ratio | Formula | Unit | Reference threshold |
|-------|---------|------|---------------------|
| Total liabilities / Total assets | 100% × Nợ phải trả / Tổng tài sản | % | < 70% an toàn; 70–85% cao; ≥ 85% rất cao |
| Total debts / Total assets | 100% × Tổng nợ vay / Tổng tài sản | % | ≤ 30% an toàn; 30–50% trung bình; > 50% cao |
| Short‑term liabilities / Total liabilities | 100% × Nợ ngắn hạn / Nợ phải trả | % | — |
| Total debts / Total liabilities | 100% × Tổng nợ vay / Nợ phải trả | % | — |
| Current debts / Total debts | 100% × Nợ vay ngắn hạn / Tổng nợ vay | % | — |
| Long‑term debts / Long‑term liabilities | 100% × Vay dài hạn / Nợ dài hạn | % | — |
| Current debts / Current liabilities | 100% × Vay ngắn hạn / Nợ ngắn hạn | % | — |
| Interest expense / Average debts | 100% × Chi phí lãi vay (TTM) / (Vay NH bình quân + Vay DH bình quân) | % | — |
| Debts / Equity | (Vay ngắn hạn + Vay dài hạn) / VCSH | × | ≤ 1 an toàn; 1–2 cao; > 2 rất cao (VCSH âm = mất vốn) |
| Net debts / Equity | (Vay NH + Vay DH − ĐTTC ngắn hạn − ĐTTC dài hạn) / VCSH | × | Âm = tiền ròng dương (net cash, tốt); 1–2 cao; > 2 rất cao |
| Long‑term debts / Equity | Vay dài hạn / VCSH | × | ≤ 0.5 thận trọng; 0.5–1 trung bình; > 1 cao |
| Total assets / Equity (financial leverage) | Tổng tài sản / VCSH | × | ≤ 2 vừa phải; 2–3 trung bình; > 3 cao |
| Average total assets / Average equity | Tổng tài sản bình quân / VCSH bình quân | × | ≤ 2 vừa phải; 2–3 trung bình; > 3 cao |

#### 1.7 Cost structure — *Chỉ số cơ cấu chi phí*

| Ratio | Formula | Unit | Reference threshold |
|-------|---------|------|---------------------|
| COGS / Revenue | 100% × Giá vốn bán hàng / Doanh thu thuần | % | ≤ 60% (biên gộp ≥ 40%) tốt; ≥ 80% (biên gộp < 20%) cạnh tranh gay gắt |
| Selling expense / Revenue | 100% × Chi phí bán hàng / Doanh thu thuần | % | — |
| Administration expense / Revenue | 100% × Chi phí QLDN / Doanh thu thuần | % | — |
| Interest cost / Revenue | 100% × Chi phí lãi vay / Doanh thu thuần | % | ≤ 3% gánh nặng lãi vay thấp; > 10% cao |
| (Selling + Admin) / Gross profit | 100% × (Chi phí bán hàng + Chi phí QLDN) / Lợi nhuận gộp | % | Buffett: < 30% tuyệt vời; 30–80% vẫn có thể bền vững |

#### 1.8 Current‑asset structure — *Chỉ số cơ cấu tài sản ngắn hạn*

| Ratio | Formula | Unit |
|-------|---------|------|
| Current assets / Total assets | 100% × TSNH / Tổng tài sản | % |
| Cash / Current assets | 100% × Tiền & tương đương tiền / TSNH | % |
| Current financial investing / Current assets | 100% × ĐTTC ngắn hạn / TSNH | % |
| Current receivables / Current assets | 100% × Phải thu ngắn hạn / TSNH | % |
| Inventories / Current assets | 100% × HTK / TSNH | % |
| Other current assets / Current assets | 100% × TSNH khác / TSNH | % |

#### 1.9 Long‑term‑asset structure — *Chỉ số cơ cấu tài sản dài hạn*

| Ratio | Formula | Unit |
|-------|---------|------|
| Long‑term assets / Total assets | 100% × Tài sản dài hạn / Tổng tài sản | % |
| Long‑term receivables / Long‑term assets | 100% × Phải thu dài hạn / Tài sản dài hạn | % |
| Fixed assets / Long‑term assets | 100% × TSCĐ / Tài sản dài hạn | % |
| Tangible fixed assets / Fixed assets | 100% × TSCĐ hữu hình / TSCĐ | % |
| Financial‑lease fixed assets / Fixed assets | 100% × TSCĐ thuê tài chính / TSCĐ | % |
| Intangible fixed assets / Fixed assets | 100% × TSCĐ vô hình / TSCĐ | % |
| Investment property / Long‑term assets | 100% × Bất động sản đầu tư / Tài sản dài hạn | % |
| Long‑term assets in progress / Long‑term assets | 100% × Tài sản dở dang dài hạn / Tài sản dài hạn | % |
| Long‑term financial investing / Long‑term assets | 100% × ĐTTC dài hạn / Tài sản dài hạn | % |
| Other long‑term assets / Long‑term assets | 100% × Tài sản dài hạn khác / Tài sản dài hạn | % |

#### 1.10 Profit structure — *Chỉ số cơ cấu lợi nhuận*

| Ratio | Formula | Unit | Reference threshold |
|-------|---------|------|---------------------|
| Operating profit / EBT | 100% × Lợi nhuận thuần từ HĐKD / LNTT<br>trong đó *LN thuần HĐKD = Lợi nhuận gộp + Lợi nhuận tài chính − Chi phí hoạt động*; *Lợi nhuận tài chính = Doanh thu tài chính − Chi phí tài chính*; *Chi phí hoạt động = Chi phí bán hàng + Chi phí QLDN* | % | Âm = HĐKD lỗ (LNTT dương chỉ nhờ khoản bất thường); < 50% phần lớn từ nguồn không cốt lõi; ≥ 50% chủ yếu từ HĐKD cốt lõi |

#### 1.11 Growth — *Chỉ số tăng trưởng*

Computed for **15 metrics**, each in two variants — **QoQ** (vs previous quarter)
and **YoY** (vs same period last year):

> Formula: **(Kỳ này − Kỳ gốc) / |Kỳ gốc| × 100%**

Metrics: Doanh thu thuần · Lợi nhuận gộp · Lợi nhuận trước thuế · LNST cổ đông
công ty mẹ · Tổng tài sản · Nợ dài hạn · Nợ phải trả · Nợ vay · VCSH · Vốn điều lệ
· Hàng tồn kho · FCF · Giá vốn bán hàng · Chi phí hoạt động · Chi phí lãi vay.
*(= 30 growth series in total.)*

#### 1.12 DuPont decomposition — *Phân tích Dupont*

| Model | Decomposition |
|-------|---------------|
| 2‑factor | ROEA = ROAA × (Tổng tài sản bình quân / VCSH bình quân) |
| 3‑factor | ROEA = ROS2 × Vòng quay tổng tài sản bình quân × Đòn bẩy tài chính trung bình |
| 5‑factor | ROEA = (LNST mẹ / LNTT) × (LNTT / EBIT) × (EBIT / Doanh thu thuần) × Vòng quay tổng tài sản bình quân × Đòn bẩy tài chính trung bình |

#### 1.13 Altman Z‑Score — *Nguy cơ phá sản*

Components: **X1** = Vốn lưu động / Tổng tài sản · **X2** = Lợi nhuận giữ lại lũy
kế / Tổng tài sản · **X3** = EBIT (TTM) / Tổng tài sản · **X4** = VCSH / Tổng nợ ·
**X5** = Doanh thu (TTM) / Tổng tài sản.

| Model | Formula | Interpretation |
|-------|---------|----------------|
| Z‑Score (manufacturing) | 1.2·X1 + 1.4·X2 + 3.3·X3 + 0.6·X4 + 0.999·X5 | ≥ 2.99 safe · 1.81–2.99 grey · ≤ 1.81 distress |
| Z2‑Score (non‑manufacturing) | 6.56·X1 + 3.26·X2 + 6.72·X3 + 1.05·X4 | ≥ 2.6 safe · 1.1–2.6 grey · ≤ 1.1 distress |

#### 1.14 Beneish M‑Score — *Nguy cơ thao túng lợi nhuận*

Components: **DSRI** (days sales in receivables) · **GMI** (gross‑margin) · **AQI**
(asset quality) · **SGI** (sales growth) · **DEPI** (depreciation) · **SGAI**
(SG&A) · **TATA** (total accruals to total assets) · **LVGI** (leverage).

| Model | Formula | Interpretation |
|-------|---------|----------------|
| M8‑Score (8 variables) | −4.84 + 0.920·DSRI + 0.528·GMI + 0.404·AQI + 0.892·SGI + 0.115·DEPI − 0.172·SGAI + 4.679·TATA − 0.327·LVGI | > −1.78 ⇒ likely manipulator |
| M5‑Score (5 variables) | −6.065 + 0.823·DSRI + 0.906·GMI + 0.593·AQI + 0.717·SGI + 0.107·DEPI | > −2.22 ⇒ likely manipulator |

---

### §2. Banks — *Định chế tín dụng*

| Ratio | Formula | Unit | Reference threshold |
|-------|---------|------|---------------------|
| ROAA | LNST (TTM) / Tổng tài sản bình quân | % | Good ≥ 1.5, Avg 0.8–1.5, Weak < 0.8 |
| ROEA | LNST cổ đông mẹ (TTM) / VCSH bình quân | % | Good ≥ 15, Avg 10–15, Weak < 10 |
| NIM — Net interest margin | Thu nhập lãi thuần (TTM) / Tài sản sinh lời bình quân | % | Good ≥ 3.5, Avg 2.5–3.5, Weak < 2.5 |
| Earning‑asset yield | Thu nhập lãi gộp (TTM) / Tài sản sinh lời bình quân | % | Trend / peers |
| Cost of funds (COF) | \|Chi phí lãi\| (TTM) / Nguồn vốn chịu lãi bình quân | % | Trend / peers |
| CIR — Cost‑to‑income | \|Chi phí hoạt động\| (TTM) / Tổng thu nhập hoạt động | % | Good ≤ 35, Avg 35–45, Weak > 45 |
| Non‑interest income share | (Tổng TN hoạt động − Thu nhập lãi thuần) / Tổng TN hoạt động | % | Good ≥ 20, Avg 10–20, Weak < 10 |
| Loan‑loss reserve / Gross loans | \|Dự phòng cho vay KH\| / Dư nợ cho vay gộp | % | Read with real NPL |
| Credit cost | \|Chi phí dự phòng\| (TTM) / Dư nợ cho vay bình quân | % | Good ≤ 1, Avg 1–2, Weak > 2 |
| Provision / Pre‑provision profit | \|Chi phí dự phòng\| (TTM) / LN trước dự phòng (TTM) | % | Good ≤ 20, Avg 20–40, Weak > 40 |
| LDR — Loan‑to‑deposit | Cho vay khách hàng / Tiền gửi khách hàng | % | Good ≤ 100, Avg 100–115, Weak > 115 |
| Equity / Total assets | VCSH / Tổng tài sản | % | Good ≥ 10, Avg 7–10, Weak < 7 |
| Leverage | Tổng tài sản / VCSH | × | Good ≤ 10, Avg 10–12, Weak > 12 |
| Loans / Total assets | Cho vay khách hàng / Tổng tài sản | % | Model / peers |
| Investment securities / Total assets | Chứng khoán đầu tư / Tổng tài sản | % | Model / peers |
| Credit growth (YoY) | Δ Dư nợ cho vay khách hàng so với cùng kỳ | % | Good ≥ 10, flat 0–10, decline < 0 |
| Deposit growth (YoY) | Δ Tiền gửi khách hàng so với cùng kỳ | % | Good ≥ 10, flat 0–10, decline < 0 |
| Charter‑capital growth (YoY) | Δ Vốn điều lệ so với cùng kỳ | % | Context |
| Operating‑income growth (YoY) | Δ Tổng thu nhập hoạt động (TTM) so với cùng kỳ | % | Good ≥ 10, flat 0–10, decline < 0 |
| Net‑profit growth (YoY) | Δ LNST (TTM) so với cùng kỳ | % | Good ≥ 10, flat 0–10, decline < 0 |

---

### §3. Securities / brokerage firms — *Công ty chứng khoán*

| Ratio | Formula | Unit | Reference threshold |
|-------|---------|------|---------------------|
| ROAA | LNST (TTM) / Tổng tài sản bình quân | % | Good ≥ 3, Avg 1.5–3, Weak < 1.5 |
| ROEA | LNST chủ sở hữu (TTM) / VCSH bình quân | % | Good ≥ 15, Avg 8–15, Weak < 8 |
| Net‑profit margin | LNST (TTM) / Doanh thu hoạt động (TTM) | % | Good ≥ 25, Avg 10–25, Weak < 10 |
| Brokerage‑revenue share | Doanh thu môi giới (TTM) / Doanh thu hoạt động (TTM) | % | Business model |
| Margin‑lending share | Lãi cho vay & phải thu (TTM) / Doanh thu hoạt động (TTM) | % | Business model |
| Proprietary‑trading share | (Lãi FVTPL + HTM + AFS + phái sinh phòng ngừa) (TTM) / Doanh thu hoạt động (TTM) | % | Business model |
| CIR — Cost‑to‑income | \|Chi phí hoạt động\| (TTM) / Doanh thu hoạt động (TTM) | % | Good ≤ 50, Avg 50–70, Weak > 70 |
| Current ratio | Tài sản ngắn hạn / Nợ ngắn hạn | × | Good ≥ 1.5, Avg 1–1.5, Weak < 1 |
| Equity / Total assets | VCSH / Tổng tài sản | % | Good ≥ 40, Avg 25–40, Weak < 25 |
| Leverage | Tổng tài sản / VCSH | × | Good ≤ 3, Avg 3–5, Weak > 5 |
| Margin loans / Equity | Dư nợ cho vay ký quỹ / VCSH | % | Good ≤ 150, Avg 150–200, > 200 breaches limit |
| Operating‑revenue growth (YoY) | Δ Doanh thu hoạt động (TTM) so với cùng kỳ | % | Good ≥ 10, flat 0–10, decline < 0 |
| Net‑profit growth (YoY) | Δ LNST (TTM) so với cùng kỳ | % | Good ≥ 10, flat 0–10, decline < 0 |

---

### §4. Insurers — *Doanh nghiệp bảo hiểm*

| Ratio | Formula | Unit | Reference threshold |
|-------|---------|------|---------------------|
| ROAA | LNST (TTM) / Tổng tài sản bình quân | % | Good ≥ 1.5, Avg 0.7–1.5, Weak < 0.7 |
| ROEA | LNST cổ đông mẹ (TTM) / VCSH bình quân | % | Good ≥ 12, Avg 7–12, Weak < 7 |
| Loss ratio | Bồi thường thuộc phần giữ lại (TTM) / Doanh thu thuần HĐ bảo hiểm (TTM) | % | Good ≤ 60, Avg 60–75, Weak > 75 |
| Expense ratio | (Chi phí bán hàng + quản lý) (TTM) / Doanh thu thuần HĐ bảo hiểm (TTM) | % | Good ≤ 30, Avg 30–40, Weak > 40 |
| Combined ratio | (Bồi thường + chi phí) (TTM) / Doanh thu thuần HĐ bảo hiểm (TTM) = Loss + Expense | % | Good ≤ 95, Avg 95–100, > 100 underwriting loss |
| Investment‑profit contribution | Lợi nhuận HĐ tài chính (TTM) / Tổng lợi nhuận kế toán (TTM) | % | Read with combined ratio |
| Retention ratio | Doanh thu thuần HĐ bảo hiểm (TTM) / (Thu phí gốc + Thu phí nhận tái BH) (TTM) | % | Risk appetite |
| Investment yield | Doanh thu HĐ tài chính (TTM) / Tài sản đầu tư tài chính (NH+DH) bình quân | % | Trend / benchmark |
| Equity / Total assets | VCSH / Tổng tài sản | % | Good ≥ 10, Avg 6–10, Weak < 6 |
| Leverage | Tổng tài sản / VCSH | × | Good ≤ 10, Avg 10–15, Weak > 15 |
| Net‑premium‑revenue growth (YoY) | Δ Doanh thu thuần HĐ bảo hiểm (TTM) so với cùng kỳ | % | Good ≥ 10, flat 0–10, decline < 0 |
| Net‑profit growth (YoY) | Δ LNST (TTM) so với cùng kỳ | % | Good ≥ 10, flat 0–10, decline < 0 |
| Technical‑reserve growth (YoY) | Δ Tổng dự phòng nghiệp vụ so với cùng kỳ | % | Read with premium growth |

> Reference thresholds are indicative benchmarks for Vietnamese institutions and
> are meant as reading aids, not investment advice. Some proxies (e.g. bank NPL)
> require disclosure‑note detail not present in the machine‑readable statements
> and are approximated accordingly.

---

## Security

- All CMS routes require authentication; sensitive actions are permission‑gated.
- Request throttling protects statement‑pull endpoints.
- Market‑data endpoint and token, and admin credentials, are configured outside
  the repository (settings DB / environment variables) — no secrets are committed.

## Author

**Hoang Anh Tuan**

## License

Released under the MIT License — see the `LICENSE` file.
