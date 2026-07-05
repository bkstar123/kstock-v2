@extends('cms.layouts.master')
@if(!empty($financial_statement->quarter))
    @section('title', "$financial_statement->symbol Báo cáo tài chính Q$financial_statement->quarter-$financial_statement->year")
@else
    @section('title', "$financial_statement->symbol Báo cáo tài chính $financial_statement->year (Năm)")
@endif

@section('content')
@php
    $ar = $financial_statement->analysis_report;
    $institutionType = institutionType($ar);
    // Nhận diện DN sản xuất / phi sản xuất -> chọn đúng mô hình Altman (Z vs Z2).
    $sectorClass = $sectorClass ?? null;
    $recommendedZ = businessSectorZAlias($sectorClass); // 'Z-Score' | 'Z2-Score' | null
    $sectorLabel = businessSectorLabel($sectorClass);
    $tiles = [];
    if ($institutionType) {
        // Định chế tài chính: dùng thẻ tóm tắt + nhận định theo bộ chỉ số chuẩn ngành.
        $verdict = institutionVerdict($ar);
        $tiles = institutionTiles($ar);
    } else {
    $verdict = analysisOverallVerdict($ar, $recommendedZ ?? 'Z-Score');
    if (!empty($ar)) {
        $toneFromClass = ['success' => 'good', 'warning' => 'warn', 'danger' => 'bad'];
        $classFromTone = ['good' => 'success', 'warn' => 'warning', 'bad' => 'danger'];

        // Model-score tiles (Altman Z / Beneish M). Nếu biết ngành -> chỉ hiện Z phù hợp.
        $zTiles = $recommendedZ ? [$recommendedZ] : ['Z-Score', 'Z2-Score'];
        foreach (array_merge($zTiles, ['M8-Score', 'M5-Score']) as $al) {
            $it = $ar->getItem($al);
            if (!$it || empty($it->values)) { continue; }
            $vals = \Arr::pluck($it->values, 'value');
            $v = $vals[0] ?? null;
            $zone = analysisScoreZone($al, $v);
            $caption = analysisScoreCaption($al);
            if ($al === $recommendedZ && $sectorLabel) {
                $caption = '✓ Phù hợp: ' . $sectorLabel . '. ' . $caption;
            }
            $tiles[] = [
                'label'   => $it->name,
                'value'   => formatFinancialValue($v, 'scalar'),
                'unit'    => '',
                'values'  => $vals,
                'delta'   => financialDelta($vals),
                'tone'    => $zone ? ($toneFromClass[$zone['class']] ?? null) : null,
                'badge'   => $zone ? ['text' => $zone['label'], 'icon' => $zone['icon'], 'class' => $zone['class']] : null,
                'caption' => $caption,
            ];
        }

        // Key-ratio tiles: [alias => [label, caption]]
        $keyMap = [
            'ROE'                            => ['ROE (LN / VCSH)', null],
            'ROS'                            => ['Biên LN ròng (ROS)', null],
            'Gross profit margin'            => ['Biên LN gộp', null],
            'Revenue Growth YoY'             => ['Tăng trưởng DT (YoY)', 'Dương là tăng trưởng'],
            'Current Ratio'                  => ['Thanh toán hiện hành', '≥ 1 là đủ'],
            'Debts/Equities'                 => ['Nợ vay / VCSH (D/E)', '> 1 = đòn bẩy cao'],
            'Total Liabilities/Total Assets' => ['Nợ phải trả / Tổng TS', '≥ 70% = đòn bẩy cao'],
            'CFO/Revenue'                    => ['Chất lượng dòng tiền (CFO/DT)', 'Âm = dòng tiền HĐKD âm'],
        ];
        foreach ($keyMap as $al => $meta) {
            $it = $ar->getItem($al);
            if (!$it || empty($it->values)) { continue; }
            $vals = \Arr::pluck($it->values, 'value');
            $v = $vals[0] ?? null;
            $sig = analysisMetricSignal($al, $vals, $it->unit);
            $tiles[] = [
                'label'   => $meta[0],
                'value'   => formatFinancialValue($v, $it->unit),
                'unit'    => $it->unit === '%' ? '' : financialUnitLabel($it->unit),
                'values'  => $vals,
                'delta'   => financialDelta($vals),
                'tone'    => $sig['tone'] ?? null,
                'badge'   => $sig ? ['text' => $sig['label'], 'icon' => $sig['icon'], 'class' => $classFromTone[$sig['tone']]] : null,
                'caption' => $meta[1],
                'note'    => $it->values[0]['valueNote'] ?? null,
                'ttm'     => $it->values[0]['ttm'] ?? false,
            ];
        }
    }
    }
@endphp
@if($verdict)
<div class="row">
    <div class="col-12">
        <div class="card ks-verdict ks-zone-{{ $verdict['tone'] }}">
            <div class="card-body d-flex flex-wrap align-items-center">
                <span class="badge badge-{{ ['good'=>'success','warn'=>'warning','bad'=>'danger'][$verdict['tone']] }} ks-verdict-badge mr-3">
                    <i class="fas {{ $verdict['icon'] }}"></i> {{ $verdict['label'] }}
                </span>
                @if($sectorLabel)
                    <span class="badge badge-light border ks-verdict-badge mr-3" title="Nhận diện tự động theo mã ngành ICB">
                        <i class="fas {{ $sectorClass === 'manufacturing' ? 'fa-industry' : ($sectorClass === 'financial' ? 'fa-landmark' : 'fa-building') }}"></i> {{ $sectorLabel }}
                    </span>
                @endif
                <div class="flex-grow-1">
                    <div class="ks-verdict-summary">{{ $verdict['summary'] }}</div>
                    <div class="ks-verdict-drivers mt-1">
                        @foreach($verdict['drivers'] as $d)
                            <span class="badge ks-sig ks-sig-{{ $d['tone'] }}">{{ $d['label'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@if(count($tiles))
<div class="row">
    @foreach($tiles as $t)
    <div class="col-lg-3 col-md-6">
        <div class="card ks-score-tile ks-zone-{{ $t['tone'] ?: 'secondary' }}">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="text-muted text-truncate" title="{{ $t['label'] }}" style="font-size:.8rem">
                        {{ $t['label'] }}@if(!empty($t['ttm']))<sup class="ks-ttm-badge" title="Đã quy đổi năm: lũy kế 4 quý gần nhất (TTM)">TTM</sup>@endif
                    </div>
                    <span class="ks-spark ks-tile-spark" data-values="{{ implode(',', array_reverse($t['values'])) }}"></span>
                </div>
                <div class="d-flex align-items-baseline justify-content-between mt-1">
                    <span class="ks-score-value {{ $t['tone'] ? 'ks-val-'.$t['tone'] : '' }} {{ !empty($t['note']) ? 'ks-val-note' : '' }}"
                        @if(!empty($t['note']))
                            tabindex="0" role="button" data-toggle="popover" data-trigger="focus" data-html="true"
                            data-title="Chi tiết" data-content="{{ $t['note'] }}"
                        @endif
                    >{{ $t['value'] }}@if($t['unit'])<small class="text-muted"> {{ $t['unit'] }}</small>@endif</span>
                    @if($t['delta'])
                        <span class="ks-delta {{ $t['tone'] ? 'ks-delta-sig-'.$t['tone'] : 'ks-delta-'.$t['delta']['dir'] }}">
                            <i class="fas fa-arrow-{{ $t['delta']['dir'] === 'up' ? 'up' : ($t['delta']['dir'] === 'down' ? 'down' : 'right') }}"></i>@if($t['delta']['pct'] !== null){{ number_format(abs($t['delta']['pct']), 1) }}%@endif
                        </span>
                    @endif
                </div>
                @if($t['badge'])
                    <span class="badge badge-{{ $t['badge']['class'] }} mt-1"><i class="fas {{ $t['badge']['icon'] }}"></i> {{ $t['badge']['text'] }}</span>
                @endif
                @if($t['caption'])
                    <div class="ks-tile-caption">{{ $t['caption'] }}</div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link active" 
                           href="#balance-statement" 
                           data-toggle="tab">
                            Bảng cân đối kế toán
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" 
                           href="#income-statement" 
                           data-toggle="tab">
                            Báo cáo kết quả kinh doanh
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" 
                           href="#cash-flow-statement" 
                           data-toggle="tab">
                            Báo cáo lưu chuyển tiền tệ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" 
                           href="#analysis-report" 
                           data-toggle="tab">
                            Phân tích các chỉ số tài chính
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" 
                           href="#graphs" 
                           data-toggle="tab">
                            Biểu đồ
                        </a>
                    </li>
                </ul>
            </div><!-- /.card-header -->
            <div class="card-body">
                <div class="tab-content">
                    <div class="active tab-pane" id="balance-statement">
                        @if(!empty($financial_statement->balance_statement))
                            @include('cms.symbols.statements._tree_table', [
                                'items' => $financial_statement->balance_statement->getItems(),
                                'year' => $financial_statement->year,
                                'quarter' => $financial_statement->quarter,
                                'treeId' => 'balance',
                            ])
                        @else
                        No balance statement found
                        @endif
                    </div>
                    <div class="tab-pane" id="income-statement">
                        @if(!empty($financial_statement->income_statement))
                            @include('cms.symbols.statements._tree_table', [
                                'items' => $financial_statement->income_statement->getItems(),
                                'year' => $financial_statement->year,
                                'quarter' => $financial_statement->quarter,
                                'treeId' => 'income',
                            ])
                        @else
                        No Income statement found
                        @endif
                    </div>
                    <div class="tab-pane" id="cash-flow-statement">
                        @if(!empty($financial_statement->cash_flow_statement))
                            @include('cms.symbols.statements._tree_table', [
                                'items' => $financial_statement->cash_flow_statement->getItems(),
                                'year' => $financial_statement->year,
                                'quarter' => $financial_statement->quarter,
                                'treeId' => 'cashflow',
                            ])
                        @else
                        No Cash Flow statement found
                        @endif
                    </div>
                    <div class="tab-pane" id="analysis-report">
                        @if(!empty($financial_statement->analysis_report))
                            @php
                                $arItems = $financial_statement->analysis_report->getItems();
                                $groups = array_values(array_unique($arItems->pluck('group')->toArray()));
                                $dataNote = $institutionType ? institutionDataNote($institutionType) : null;
                            @endphp
                            @if($dataNote)
                                <div class="alert alert-info d-flex align-items-start" role="alert">
                                    <i class="fas fa-circle-info mr-2 mt-1"></i>
                                    <div>{!! $dataNote !!}</div>
                                </div>
                            @endif
                            {{-- quick jump nav --}}
                            <div class="ks-jumpnav mb-3">
                                @foreach($groups as $gi => $g)
                                    <a href="#arg-{{ $gi }}" class="ks-jump" data-body="#arg-body-{{ $gi }}">{{ $g }}</a>
                                @endforeach
                            </div>
                            @foreach($groups as $gi => $group)
                                @php
                                    $groupItems = $arItems->where('group', $group)->values();
                                    $periods = (isset($groupItems[0]) && !empty($groupItems[0]->values))
                                        ? \Arr::pluck($groupItems[0]->values, 'period') : [];
                                    $open = $gi === 0;
                                @endphp
                                <div class="card ks-agroup" id="arg-{{ $gi }}">
                                    <div class="card-header ks-agroup-header {{ $open ? '' : 'collapsed' }}" role="button"
                                         data-toggle="collapse" data-target="#arg-body-{{ $gi }}"
                                         aria-expanded="{{ $open ? 'true' : 'false' }}">
                                        <h3 class="card-title">{{ $group }} <small class="text-muted">({{ $groupItems->count() }})</small></h3>
                                        <i class="fas fa-chevron-down ks-chevron"></i>
                                    </div>
                                    <div class="collapse {{ $open ? 'show' : '' }}" id="arg-body-{{ $gi }}">
                                        <div class="card-body p-0 ks-matrix-wrap">
                                            <table class="table table-hover ks-matrix">
                                                <thead>
                                                    <tr>
                                                        <th class="ks-sticky-col">Chỉ số tài chính</th>
                                                        <th class="text-center">Xu hướng</th>
                                                        @foreach($periods as $period)
                                                            <th class="text-right ks-period {{ $loop->first ? 'ks-latest' : '' }}">{{ $period }}</th>
                                                        @endforeach
                                                        <th class="text-center">Đơn vị</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($groupItems as $item)
                                                        @php
                                                            $vals = \Arr::pluck($item->values, 'value');
                                                            $latest = $vals[0] ?? null;
                                                            $zone = analysisScoreZone($item->alias, $latest);
                                                            $spark = implode(',', array_reverse($vals));
                                                            $numeric = array_values(array_filter($vals, 'is_numeric'));
                                                            $rmin = count($numeric) ? min($numeric) : null;
                                                            $rmax = count($numeric) ? max($numeric) : null;
                                                            $delta = financialDelta($vals);
                                                            $signal = $institutionType
                                                                ? institutionMetricSignal($item->alias, $vals)
                                                                : analysisMetricSignal($item->alias, $vals, $item->unit);
                                                            // Giá trị kỳ mới nhất đã bị gắn banner "không còn ý nghĩa" (vd CFO âm làm
                                                            // FCF/CFO mất ý nghĩa) -> không hiển thị nhận định tốt/xấu dựa trên chính
                                                            // giá trị đó nữa, tránh mâu thuẫn (badge xanh "tốt" cạnh banner cảnh báo).
                                                            if (!empty($item->alert)) {
                                                                $signal = null;
                                                            }
                                                            $rowIsTtm = $item->values[0]['ttm'] ?? false;
                                                            // Chỉ kèm thuật ngữ tiếng Anh cạnh tên khi nó là 1 chữ viết tắt thật sự
                                                            // (vd ROS, ROAA) - không kèm các cụm dịch dài (vd "Liability Coverage Ratio By FCF").
                                                            $aliasIsAcronym = $item->alias
                                                                && preg_match('/^[A-Z][A-Z0-9]{1,5}$/', $item->alias)
                                                                && !str_contains($item->name, $item->alias);
                                                        @endphp
                                                        <tr class="{{ $zone ? 'ks-headline' : '' }}">
                                                            <td class="ks-sticky-col">
                                                                <span class="ks-metric-name">{{ $item->name }}@if($aliasIsAcronym) ({{ $item->alias }})@endif</span>
                                                                @if($rowIsTtm)
                                                                    <sup class="ks-ttm-badge" title="Đã quy đổi năm: lũy kế 4 quý gần nhất (TTM)">TTM</sup>
                                                                @endif
                                                                <a class="ks-info" tabindex="0" role="button"
                                                                   data-toggle="popover" data-trigger="focus" data-html="true"
                                                                   data-title="{{ $item->name }}"
                                                                   data-content="{{ $item->description }}"><i class="fas fa-info-circle"></i></a>
                                                                @if($recommendedZ && $item->alias === $recommendedZ)
                                                                    <span class="badge badge-primary" title="Mô hình phù hợp với {{ mb_strtolower($sectorLabel) }}">
                                                                        <i class="fas fa-star"></i> Phù hợp ngành
                                                                    </span>
                                                                @endif
                                                                @if($delta)
                                                                    <span class="ks-delta {{ $signal ? 'ks-delta-sig-'.$signal['tone'] : 'ks-delta-'.$delta['dir'] }}">
                                                                        <i class="fas fa-arrow-{{ $delta['dir'] === 'up' ? 'up' : ($delta['dir'] === 'down' ? 'down' : 'right') }}"></i>@if($delta['pct'] !== null){{ number_format(abs($delta['pct']), 1) }}%@endif
                                                                    </span>
                                                                @endif
                                                                @if($zone)
                                                                    <span class="badge badge-{{ $zone['class'] }} ks-zone">
                                                                        <i class="fas {{ $zone['icon'] }}"></i> {{ $zone['label'] }}
                                                                    </span>
                                                                @endif
                                                                @if($signal)
                                                                    <span class="badge ks-sig ks-sig-{{ $signal['tone'] }}" title="{{ $signal['label'] }}">
                                                                        <i class="fas {{ $signal['icon'] }}"></i> {{ $signal['label'] }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center ks-sparkcell">
                                                                <span class="ks-spark" data-values="{{ $spark }}"></span>
                                                            </td>
                                                            @foreach($vals as $i => $value)
                                                                @php
                                                                    $valueNote = $item->values[$i]['valueNote'] ?? null;
                                                                @endphp
                                                                <td class="text-right ks-num {{ $i === 0 ? 'ks-latest' : '' }} {{ ($i === 0 && $signal) ? 'ks-sig-cell-'.$signal['tone'] : '' }}" style="--ks-bar:{{ financialBarPercent($value, $rmin, $rmax) }}%">
                                                                    @if($i === 0 && !empty($item->alert))
                                                                        <i class="fas fa-exclamation-triangle ks-invalid-icon" tabindex="0" role="button"
                                                                           data-toggle="popover" data-trigger="focus" data-html="true"
                                                                           data-title="Không còn ý nghĩa" data-content="{{ $item->alert }}"></i>
                                                                    @endif
                                                                    @if($valueNote)
                                                                        <span class="ks-val-note" tabindex="0" role="button"
                                                                           data-toggle="popover" data-trigger="focus" data-html="true"
                                                                           data-title="Chi tiết" data-content="{{ $valueNote }}">{{ formatFinancialValue($value, $item->unit) }}</span>
                                                                    @else
                                                                        {{ formatFinancialValue($value, $item->unit) }}
                                                                    @endif
                                                                </td>
                                                            @endforeach
                                                            <td class="text-center text-muted">{{ financialUnitLabel($item->unit) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                        No analysis report found
                        @endif
                    </div>
                    <div class="tab-pane" id="graphs">
                        @if(!empty($financial_statement->analysis_report))
                            @if($institutionType)
                                {{-- Biểu đồ cho định chế tài chính: dựng động từ các nhóm chỉ số --}}
                                @php
                                    $instItems = $financial_statement->analysis_report->getItems();
                                    $instGroups = array_values(array_unique($instItems->pluck('group')->toArray()));
                                @endphp
                                <ul class="nav nav-pills ks-graph-nav flex-wrap mb-3">
                                    @foreach($instGroups as $gi => $g)
                                        <li class="nav-item"><a class="nav-link {{ $gi === 0 ? 'active' : '' }}" href="#ig-{{ $gi }}" data-toggle="tab">{{ $g }}</a></li>
                                    @endforeach
                                </ul>
                                <div class="tab-content ks-graph-content">
                                    @foreach($instGroups as $gi => $group)
                                        <div class="tab-pane {{ $gi === 0 ? 'active' : '' }}" id="ig-{{ $gi }}">
                                            <div class="row">
                                                @foreach($instItems->where('group', $group)->values() as $item)
                                                    @php
                                                        $periods = \Arr::pluck($item->values, 'period');
                                                        $vals = \Arr::pluck($item->values, 'value');
                                                    @endphp
                                                    <div class="col-md-6">
                                                        <div class="ks-chart" data-inst-chart
                                                             data-name="{{ $item->name }}"
                                                             data-unit="{{ $item->unit }}"
                                                             data-periods="{{ implode('|', array_reverse($periods)) }}"
                                                             data-values="{{ implode('|', array_reverse($vals)) }}"></div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                            <ul class="nav nav-pills ks-graph-nav flex-wrap mb-3">
                                <li class="nav-item"><a class="nav-link active" href="#g-0" data-toggle="tab">Chỉ số sinh lời</a></li>
                                <li class="nav-item"><a class="nav-link" href="#g-1" data-toggle="tab">Chỉ số thanh toán</a></li>
                                <li class="nav-item"><a class="nav-link" href="#g-2" data-toggle="tab">Chỉ số dòng tiền</a></li>
                                <li class="nav-item"><a class="nav-link" href="#g-3" data-toggle="tab">Chỉ số CAPEX</a></li>
                                <li class="nav-item"><a class="nav-link" href="#g-4" data-toggle="tab">Hiệu quả hoạt động</a></li>
                                <li class="nav-item"><a class="nav-link" href="#g-5" data-toggle="tab">Tăng trưởng</a></li>
                                <li class="nav-item"><a class="nav-link" href="#g-6" data-toggle="tab">Dupont cấp 5</a></li>
                                <li class="nav-item"><a class="nav-link" href="#g-7" data-toggle="tab">Kết quả kinh doanh</a></li>
                                <li class="nav-item"><a class="nav-link" href="#g-8" data-toggle="tab">Lưu chuyển tiền tệ</a></li>
                                <li class="nav-item"><a class="nav-link" href="#g-9" data-toggle="tab">Cân đối kế toán</a></li>
                            </ul>
                            <div class="tab-content ks-graph-content">
                                <div class="tab-pane active" id="g-0">
                                    <div class="row">
                                        <div class="col-md-6"><div class="ks-chart" id="roaa-container"></div></div>
                                        <div class="col-md-6"><div class="ks-chart" id="roea-container"></div></div>
                                        <div class="col-md-6"><div class="ks-chart" id="ros-container"></div></div>
                                        <div class="col-md-6"><div class="ks-chart" id="gpm-container"></div></div>
                                        <div class="col-md-6"><div class="ks-chart" id="rota-container"></div></div>
                                        <div class="col-md-6"><div class="ks-chart" id="ebit-margin-container"></div></div>
                                        <div class="col-md-6"><div class="ks-chart" id="roce-container"></div></div>
                                        <div class="col-md-6"><div class="ks-chart" id="ebitda-margin-container"></div></div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="g-1">
                                    <div class="row">
                                        <div class="col-md-6"><div class="ks-chart" id="liquidity-container"></div></div>
                                        <div class="col-md-6"><div class="ks-chart" id="interest-coverage-ratio-container"></div></div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="g-2">
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="cash-flow-ratio-container"></div></div></div>
                                </div>
                                <div class="tab-pane" id="g-3">
                                    <p class="text-muted mb-2"><i>Chỉ được tính toán nếu tiền chi cho CAPEX &gt; tiền thu từ thanh lý CAPEX</i></p>
                                    <div class="row">
                                        <div class="col-md-6"><div class="ks-chart" id="cfo-to-capex-container"></div></div>
                                        <div class="col-md-6"><div class="ks-chart" id="capex-to-net-profit-container"></div></div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="g-4">
                                    <div class="row">
                                        <div class="col-md-6"><div class="ks-chart" id="effectiveness-ratio-container"></div></div>
                                        <div class="col-md-6"><div class="ks-chart" id="other-effectiveness-ratio-container"></div></div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="g-5">
                                    <div class="row">
                                        <div class="col-md-6"><div class="ks-chart" id="growthQoQ-container"></div></div>
                                        <div class="col-md-6"><div class="ks-chart" id="growthYoY-container"></div></div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="g-6">
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="dupont-level5-container"></div></div></div>
                                </div>
                                <div class="tab-pane" id="g-7">
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="income-statement-container"></div></div></div>
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="cost-structure-container"></div></div></div>
                                </div>
                                <div class="tab-pane" id="g-8">
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="cash-flows-statement-container"></div></div></div>
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="cfo-cash-flows-container"></div></div></div>
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="cfi-cash-flows-container"></div></div></div>
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="cff-cash-flows-container"></div></div></div>
                                </div>
                                <div class="tab-pane" id="g-9">
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="financial-leverage-container"></div></div></div>
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="assets-structure-container"></div></div></div>
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="current-assets-structure-container"></div></div></div>
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="long-term-assets-structure-container"></div></div></div>
                                    <div class="row"><div class="col-12"><div class="ks-chart" id="fixed-assets-structure-container"></div></div></div>
                                </div>
                            </div>
                            @endif
                        @else
                        No graphs to be shown
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scriptBottom')
<script src="{{ asset('cms-assets/js/statement-tree.js') }}?v=1"></script>
<script src="{{ url('/js/vendor/highcharts/highcharts.js') }}"></script>
<script src="{{ asset('cms-assets/js/highcharts-theme.js') }}?v=1"></script>
<script type="text/javascript">
// Cac chi so tai chinh (chi ap dung cho doanh nghiep thuong; dinh che dung bo rieng)
@if(!empty($financial_statement->analysis_report) && !$institutionType)
    // Chi so sinh loi
    var roaaData = @json(statementItemValues($financial_statement->analysis_report, 'ROAA'));
    var roaData = @json(statementItemValues($financial_statement->analysis_report, 'ROA'));
    var roeaData = @json(statementItemValues($financial_statement->analysis_report, 'ROEA'));
    var roeData = @json(statementItemValues($financial_statement->analysis_report, 'ROE'));
    var rosData = @json(statementItemValues($financial_statement->analysis_report, 'ROS'));
    var ros2Data = @json(statementItemValues($financial_statement->analysis_report, 'ROS2'));
    var gpmData = @json(statementItemValues($financial_statement->analysis_report, 'Gross profit margin'));
    var rotaData = @json(statementItemValues($financial_statement->analysis_report, 'ROTA'));
    var ebitMarginData = @json(statementItemValues($financial_statement->analysis_report, 'EBIT margin'));
    var roceData = @json(statementItemValues($financial_statement->analysis_report, 'ROCE'));
    var ebitda1Data = @json(statementItemValues($financial_statement->analysis_report, 'EBITDA margin 1'));
    var ebitda2Data = @json(statementItemValues($financial_statement->analysis_report, 'EBITDA margin 2'));

    // Chi so thanh toan
    var overallSolvencyRatioData = @json(statementItemValues($financial_statement->analysis_report, 'Overall Solvency Ratio'));
    var currentRatioData = @json(statementItemValues($financial_statement->analysis_report, 'Current Ratio'));
    var quickRatioData = @json(statementItemValues($financial_statement->analysis_report, 'Quick Ratio 1'));
    var quickRatio2Data = @json(statementItemValues($financial_statement->analysis_report, 'Quick Ratio 2'));
    var cashRatioData = @json(statementItemValues($financial_statement->analysis_report, 'Cash Ratio'));
    var interestCoverageRatioData = @json(statementItemValues($financial_statement->analysis_report, 'Interest Coverage Ratio'));

    // Chi so dong tien
    var cfoToRevenueData = @json(statementItemValues($financial_statement->analysis_report, 'CFO/Revenue'));
    var fcfToRevenueData = @json(statementItemValues($financial_statement->analysis_report, 'FCF/Revenue'));
    var fcfToCfoData = @json(statementItemValues($financial_statement->analysis_report, 'FCF/CFO'));

    // Chi so CAPEX
    var cfoToCapex = @json(statementItemValues($financial_statement->analysis_report, 'CFO/CAPEX'));
    var capexToNetProfitData = @json(statementItemValues($financial_statement->analysis_report, 'CAPEX/NetProfit'));

    // Chi so hieu qua hoat dong
    var averageCollectionPeriodData = @json(statementItemValues($financial_statement->analysis_report, 'Average Collection Period'));
    var averageAgeOfInventoryData = @json(statementItemValues($financial_statement->analysis_report, 'Average Age of Inventory'));
    var averageAccountPayableDurationData = @json(statementItemValues($financial_statement->analysis_report, 'Average Account Payable Duration'));
    var cashConversionCycleData = @json(statementItemValues($financial_statement->analysis_report, 'Cash Conversion Cycle'));
    var totalAssetTurnoverData = @json(statementItemValues($financial_statement->analysis_report, 'Total Asset Turnover Ratio'));
    var fixedAssetTurnoverData = @json(statementItemValues($financial_statement->analysis_report, 'Fixed Asset Turnover Ratio'));
    var equityTurnoverData = @json(statementItemValues($financial_statement->analysis_report, 'Equity Turnover Ratio'));

    // Chi so don bay tai chinh
    var debtToEquitiesData = @json(statementItemValues($financial_statement->analysis_report, 'Debts/Equities'));
    var netDebtToEquitiesData = @json(statementItemValues($financial_statement->analysis_report, 'Net Debts/Equities'));
    var longTermDebtToEquityData = @json(statementItemValues($financial_statement->analysis_report, 'Long Term Debts/Equities'));
    var financialLeverageData = @json(statementItemValues($financial_statement->analysis_report, 'Total Assets/Equities'));
    var averageFinancialLeverageData = @json(statementItemValues($financial_statement->analysis_report, 'Average Total Assets/Average Equities'));
    var currrentDebtsToTotalDebtsData = @json(statementItemValues($financial_statement->analysis_report, 'Currrent Debts/Total Debts'));
    var currentDebtsToCurrentLiabilitiesData = @json(statementItemValues($financial_statement->analysis_report, 'Current Debts/Current Liabilities'));
    var longTermDebtsToLongTermLiabilitiesData = @json(statementItemValues($financial_statement->analysis_report, 'Long Term Debts/Long Term Liabilities'));
    var debtsToLiabilitiesData = @json(statementItemValues($financial_statement->analysis_report, 'Total Debts/Total Liabilities'));
    var liabilitiesToAssetsData = @json(statementItemValues($financial_statement->analysis_report, 'Total Liabilities/Total Assets'));
    var currentLiabilitiesToTotalLiabilitiesData = @json(statementItemValues($financial_statement->analysis_report, 'Short-term liabilities/Total liabilities'));

    // Cac chi so tang truong QoQ
    var revenueGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Revenue Growth QoQ'));
    var inventoryGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Inventory Growth QoQ'));
    var cogsGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'COGS Growth QoQ'));
    var grossProfitGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Gross Profit Growth QoQ'));
    var operatingExpenseGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Operation Expense Growth QoQ'));
    var interestExpenseGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Interest Expense Growth QoQ'));
    var eBTGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Earnings Before Tax Growth QoQ'));
    var netProfitOfParentShareHolderGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Net Profit Of Parent ShareHolder Growth QoQ'));
    var totalAssetsGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Total Asset Growth QoQ'));
    var longTermLiabilityGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Long Term Liability Growth QoQ'));
    var liabilityGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Liability Growth QoQ'));
    var debtGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Debt Growth QoQ'));
    var charterCapitalGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Charter Capital Growth QoQ'));
    var equityGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'Equity Growth QoQ'));
    var fcfGrowthQoQData = @json(statementItemValues($financial_statement->analysis_report, 'FCF Growth QoQ'));

    // Cac chi so tang truong YoY
    var revenueGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Revenue Growth YoY'));
    var inventoryGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Inventory Growth YoY'));
    var cogsGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'COGS Growth QoQ'));
    var grossProfitGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Gross Profit Growth YoY'));
    var operatingExpenseGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Operation Expense Growth YoY'));
    var interestExpenseGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Interest Expense Growth YoY'));
    var eBTGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Earnings Before Tax Growth YoY'));
    var netProfitOfParentShareHolderGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Net Profit Of Parent ShareHolder Growth YoY'));
    var totalAssetsGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Total Asset Growth YoY'));
    var longTermLiabilityGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Long Term Liability Growth YoY'));
    var liabilityGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Liability Growth YoY'));
    var debtGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Debt Growth YoY'));
    var charterCapitalGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Charter Capital Growth YoY'));
    var equityGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'Equity Growth YoY'));
    var fcfGrowthYoYData = @json(statementItemValues($financial_statement->analysis_report, 'FCF Growth YoY'));

    // Dupont analysis level 5
    var earningAfterTaxParentCompanyToEBTData = @json(statementItemValues($financial_statement->analysis_report, 'Dupont5-Earning After Tax of Parent Company To Earning Before Tax'));
    var earningAfterTaxToEBTData = @json(statementItemValues($financial_statement->analysis_report, 'Dupont5-Earning After Tax To Earning Before Tax'));
    var earningBeforeTaxToEBITData = @json(statementItemValues($financial_statement->analysis_report, 'Dupont5-Earning Before Tax To EBIT'));
    var eBITMarginData = @json(statementItemValues($financial_statement->analysis_report, 'Dupont5-EBIT Margin'));
    var averageTotalAssetTurnoverData = @json(statementItemValues($financial_statement->analysis_report, 'Dupont5-Average Total Asset Turnover'));

    // Cau truc tai san ngan han
    var cashAndEquivalentData = @json(statementItemValues($financial_statement->analysis_report, 'Cash/Current Assets'));
    var currentFinancialInvestingData = @json(statementItemValues($financial_statement->analysis_report, 'Current Financial Investing/Current Assets'));
    var currentReceivableAccountData = @json(statementItemValues($financial_statement->analysis_report, 'Current Receivable Accounts/Current Assets'));
    var inventoriesData = @json(statementItemValues($financial_statement->analysis_report, 'Inventories/Current Assets'));
    var otherCurrentAssetsData = @json(statementItemValues($financial_statement->analysis_report, 'Other Current Assets/Current Assets'));

    // Cau truc tai san dai han
    var longTermReceivablesData = @json(statementItemValues($financial_statement->analysis_report, 'Long Term Receivables/Long Term Assets'));
    var fixedAssetsData = @json(statementItemValues($financial_statement->analysis_report, 'Fixed Assets/Long Term Assets'));
    var investingRealEstatesData = @json(statementItemValues($financial_statement->analysis_report, 'Investing Real Estates/Long Term Assets'));
    var longTermAssetsinProgressData = @json(statementItemValues($financial_statement->analysis_report, 'Long Term Assets in Progress/Long Term Assets'));
    var longTermFinancialInvestingData = @json(statementItemValues($financial_statement->analysis_report, 'Long Term Financial Investing/Long Term Assets'));
    var otherLongTermAssetsData = @json(statementItemValues($financial_statement->analysis_report, 'Other Long Term Assets/Long Term Assets')); 
    var tangibleFixedAssetsData = @json(statementItemValues($financial_statement->analysis_report, 'Tangible Fixed Assets/Fixed Assets'));
    var financialLendingFixedAssetsData = @json(statementItemValues($financial_statement->analysis_report, 'Financial Lending Fixed Assets/Fixed Assets'));
    var intangibleFixedAssetsData = @json(statementItemValues($financial_statement->analysis_report, 'Intangible Fixed Assets/Fixed Assets'));  

    // Cau truc tai san  
    var currentAssetsData = @json(statementItemValues($financial_statement->analysis_report, 'Current Assets/Total Assets'));
    var longTermAssetsData = @json(statementItemValues($financial_statement->analysis_report, 'Long Term Assets/Total Assets')); 
    @if(!empty($financial_statement->income_statement))
        var totalAssetData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->balance_statement, '2')));
    @endif
    // Bao cao ket qua HDKD
    @if(!empty($financial_statement->income_statement))
        var revenueData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '3')));
        var earningsAfterTaxParentCompanyData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '21')));
        var grossProfitData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '5')));
        var cogsData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '4')));
        var financialExpenseData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '7')));
        var sellingExpenseData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '9')));
        var generalAdminExpenseData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '10')));
        var eBTData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '15')));
        var financialRevenueData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '6')));
        var interestExpenseData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '701')));
        var otherProfitData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '14')));
        var otherExpenseData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '13')));
        var taxData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->income_statement, '18')));
    @endif
    var operatingProfitToEBTData = @json(statementItemValues($financial_statement->analysis_report, 'Operating Profit/EBT'));
    var cogsToRevenueData = @json(statementItemValues($financial_statement->analysis_report, 'Cogs/Revenue'));
    var sellingExpenseToRevenueData = @json(statementItemValues($financial_statement->analysis_report, 'Selling Expense/Revenue'));
    var adminExpenseToRevenueData = @json(statementItemValues($financial_statement->analysis_report, 'Administration Expense/Revenue'));
    var interestCostToRevenueData = @json(statementItemValues($financial_statement->analysis_report, 'Interest cost/Revenue'));
    var selllingEnterpriseManagementExpenseToGrossProfitData = @json(statementItemValues($financial_statement->analysis_report, 'Selling and Enterprise Management Expenses/Gross Profit'));

    //Bao cao luu chuyen tien te
    @if(!empty($financial_statement->cash_flow_statement))
        var cfoData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '104')));
        var cfiData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '212')));
        var cffData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '311')));
        var cashMovingData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '4')));
        var cashEndData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '7')));

        var deprecationData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '10201')));
        var receivableAccountChangenData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '10301')));
        var inventoryAccountChangenData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '10302')));
        var payableAccountChangenData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '10303')));
        var payForCapexData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '201')));
        var receiveFromCapexData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '202')));
        var payForDebtPrincipalData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '304')));
        var loanData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '303')));
        var payForLoanToolData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '203')));
        var receiveForLoanToolData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '204')));
        var paidInterestData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '10306')));
        var paidTaxData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '10307')));
        var changeFromCurrencyConversionRateData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '10205')));
        var changeFromInvestingActivityData = @json(array_map(function($value) {
            $value[1] = readVietnameseDongForHuman($value[1]);
            return $value;
        }, statementItemValues($financial_statement->cash_flow_statement, '10207')));
    @endif
@endif
</script>
@if($institutionType)
<script src="{{ asset('cms-assets/js/institution-charts.js') }}?v=1"></script>
@else
<script src="/js/stock-symbols/graph_report.min.js"></script>
@endif
<script src="{{ asset('cms-assets/js/analysis-report.js') }}?v=2"></script>
@endpush