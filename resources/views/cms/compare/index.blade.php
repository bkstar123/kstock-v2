@extends('cms.layouts.master')
@section('title', 'So sánh cổ phiếu')

@section('content')
@php
    $typeLabels = ['bank' => 'Ngân hàng', 'securities' => 'Chứng khoán', 'insurance' => 'Bảo hiểm'];
    $typeLabel = fn ($t) => $typeLabels[$t] ?? 'Phi tài chính';
    $typeClass = fn ($t) => 'ks-type-' . ($t ?: 'normal');
    $selectedCodes = $selected->pluck('code')->all();
@endphp

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-scale-balanced text-primary"></i> So sánh cổ phiếu</h3>
            </div>
            <div class="card-body">
                @if($available->count())
                    <form method="GET" action="{{ route('cms.compare.index') }}">
                        <p class="text-muted mb-2">Tích chọn các mã cần so sánh (lấy kỳ báo cáo gần nhất của mỗi mã):</p>
                        <div class="ks-chip-group">
                            @foreach($available as $row)
                                <label class="ks-chip {{ in_array($row['code'], $selectedCodes, true) ? 'is-checked' : '' }}">
                                    <input type="checkbox" name="symbols[]" value="{{ $row['code'] }}"
                                        {{ in_array($row['code'], $selectedCodes, true) ? 'checked' : '' }}>
                                    <span class="ks-chip__check"></span>
                                    <span class="ks-chip__code">{{ $row['code'] }}</span>
                                    <span class="ks-type {{ $typeClass($row['type']) }}">{{ $typeLabel($row['type']) }}</span>
                                    <span class="ks-chip__period">{{ $row['period'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-scale-balanced"></i> So sánh</button>
                    </form>
                @else
                    <p class="text-muted mb-0">Chưa có mã nào được phân tích. Hãy
                        <a href="{{ route('cms.financial.statements.pull') }}">pull báo cáo tài chính</a> trước.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@if($selected->count())
    @if($mixedTypes)
        <div class="alert alert-warning d-flex align-items-start" role="alert">
            <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
            <div>Bạn đang so sánh các mã <strong>không cùng loại</strong> (ví dụ cổ phiếu phi tài chính với cổ phiếu
                định chế tài chính như ngân hàng/chứng khoán/bảo hiểm). Nhiều chỉ số đặc thù không tương đương giữa các
                loại nên sẽ hiển thị <strong>"—"</strong>; hãy tập trung vào các chỉ số dùng chung (sinh lời, tăng
                trưởng, đòn bẩy).</div>
        </div>
    @endif

    @if(count($rows))
        {{-- Biểu đồ cột cho các chỉ số headline --}}
        <div class="row">
            @foreach($rows as $groupRows)
                @foreach($groupRows as $row)
                    @if($row['chartable'])
                        <div class="col-md-6">
                            <div class="ks-chart" data-compare-chart
                                 data-label="{{ $row['label'] }}"
                                 data-unit="{{ $row['unitCode'] }}"
                                 data-categories="{{ $selected->pluck('code')->implode('|') }}"
                                 data-values="{{ collect($row['cells'])->pluck('raw')->map(fn ($v) => $v === null ? '' : $v)->implode('|') }}"></div>
                        </div>
                    @endif
                @endforeach
            @endforeach
        </div>

        {{-- Bảng so sánh chi tiết --}}
        <div class="card">
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-hover mb-0 ks-compare-table">
                    <thead>
                        <tr>
                            <th style="min-width:220px">Chỉ số tài chính</th>
                            @foreach($selected as $s)
                                <th class="text-right">
                                    {{ $s['code'] }}
                                    <span class="ks-type {{ $typeClass($s['type']) }} d-inline-block mt-1">{{ $typeLabel($s['type']) }}</span>
                                    <small class="text-muted">{{ $s['period'] }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $group => $groupRows)
                            <tr class="bg-light">
                                <td colspan="{{ $selected->count() + 1 }}" class="font-weight-bold text-uppercase" style="font-size:.78rem;letter-spacing:.03em">{{ $group }}</td>
                            </tr>
                            @foreach($groupRows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}@if($row['unit'])<small class="text-muted"> ({{ $row['unit'] }})</small>@endif</td>
                                    @foreach($row['cells'] as $cell)
                                        <td class="text-right font-weight-bold {{ $cell['tone'] === 'good' ? 'table-success' : ($cell['tone'] === 'bad' ? 'table-danger' : '') }}">
                                            {{ $cell['display'] }}
                                            @if($cell['zone'])
                                                <span class="badge badge-{{ $cell['zone']['class'] }} d-block font-weight-normal mt-1">
                                                    <i class="fas {{ $cell['zone']['icon'] }}"></i> {{ $cell['zone']['label'] }}
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-info">Các mã đã chọn không có chỉ số chung để so sánh.</div>
    @endif
@endif
@endsection

@push('scriptBottom')
<script src="{{ url('/js/vendor/highcharts/highcharts.js') }}"></script>
<script src="{{ asset('cms-assets/js/highcharts-theme.js') }}?v=1"></script>
<script src="{{ asset('cms-assets/js/comparison-charts.js') }}?v=1"></script>
<script>
// Toggle trạng thái đã chọn cho chip (đảm bảo chạy cả khi trình duyệt không hỗ trợ :has())
document.querySelectorAll('.ks-chip input[type="checkbox"]').forEach(function (cb) {
    cb.addEventListener('change', function () {
        this.closest('.ks-chip').classList.toggle('is-checked', this.checked);
    });
});
</script>
@endpush
