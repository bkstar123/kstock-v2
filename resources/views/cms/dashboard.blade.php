@extends('cms.layouts.master')
@section('title', 'Dashboard')

@section('content')
@php
    $adminId = auth()->guard('admins')->user()->id;
    $watch = \App\Models\Watchlist::with('symbol')
        ->where('admin_id', $adminId)->orderBy('symbol_code')->get();
    // Các mã đã có phân tích -> so sánh được (kỳ gần nhất mỗi mã).
    $comparable = \App\Models\FinancialStatement::with('analysis_report')->get()
        ->filter(fn ($fs) => !empty($fs->analysis_report))
        ->sortByDesc(fn ($fs) => sprintf('%04d%d', $fs->year, $fs->quarter))
        ->groupBy('symbol')
        ->map(function ($s) {
            $fs = $s->first();
            return [
                'code'   => $fs->symbol,
                'type'   => institutionType($fs->analysis_report),
                'period' => $fs->quarter ? "Q{$fs->quarter} {$fs->year}" : (string) $fs->year,
            ];
        })
        ->sortKeys()->values();
    $typeLabels = ['bank' => 'Ngân hàng', 'securities' => 'Chứng khoán', 'insurance' => 'Bảo hiểm'];
    $typeLabel = fn ($t) => $typeLabels[$t] ?? 'Phi tài chính';
    $typeClass = fn ($t) => 'ks-type-' . ($t ?: 'normal');
@endphp
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-star text-warning"></i> My Watchlist</h3>
                <div class="card-tools">
                    <a href="{{ route('cms.watchlist.index') }}" class="btn btn-sm btn-primary">Open</a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($watch->count())
                <table class="table table-hover mb-0">
                    <tbody>
                        @foreach($watch as $w)
                        <tr>
                            <td style="width:90px">
                                <a href="{{ route('cms.companies.show', ['code' => $w->symbol_code]) }}">
                                    <strong>{{ $w->symbol_code }}</strong>
                                </a>
                            </td>
                            <td>{{ optional($w->symbol)->name }}</td>
                            <td class="text-right">@if(optional($w->symbol)->exchange)<span class="badge badge-secondary">{{ $w->symbol->exchange }}</span>@endif</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <p class="text-muted p-3 mb-0">
                        No symbols followed yet. <a href="{{ route('cms.companies.index') }}">Browse the directory</a>.
                    </p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Quick actions</h3></div>
            <div class="card-body">
                <a href="{{ route('cms.companies.index') }}" class="btn btn-primary mb-2"><i class="fas fa-list"></i> Symbol Directory</a>
                <a href="{{ route('cms.watchlist.index') }}" class="btn btn-warning mb-2"><i class="far fa-star"></i> Watchlist</a>
                <a href="{{ route('cms.compare.index') }}" class="btn btn-info mb-2"><i class="fas fa-scale-balanced"></i> So sánh cổ phiếu</a>
                <a href="{{ route('cms.financial.statements.pull') }}" class="btn btn-secondary mb-2"><i class="fas fa-download"></i> Pull Statement</a>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-scale-balanced text-info"></i> So sánh cổ phiếu</h3>
            </div>
            <div class="card-body">
                @if($comparable->count())
                    <form method="GET" action="{{ route('cms.compare.index') }}">
                        <p class="text-muted mb-2">Tích chọn các mã cần so sánh chỉ số tài chính:</p>
                        <div class="ks-chip-group">
                            @foreach($comparable as $row)
                                <label class="ks-chip">
                                    <input type="checkbox" name="symbols[]" value="{{ $row['code'] }}">
                                    <span class="ks-chip__check"></span>
                                    <span class="ks-chip__code">{{ $row['code'] }}</span>
                                    <span class="ks-type {{ $typeClass($row['type']) }}">{{ $typeLabel($row['type']) }}</span>
                                    <span class="ks-chip__period">{{ $row['period'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-info mt-3"><i class="fas fa-scale-balanced"></i> So sánh</button>
                    </form>
                @else
                    <p class="text-muted mb-0">Chưa có mã nào được phân tích. Hãy
                        <a href="{{ route('cms.financial.statements.pull') }}">pull báo cáo tài chính</a> trước.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scriptBottom')
<script>
document.querySelectorAll('.ks-chip input[type="checkbox"]').forEach(function (cb) {
    cb.addEventListener('change', function () {
        this.closest('.ks-chip').classList.toggle('is-checked', this.checked);
    });
});
</script>
@endpush
