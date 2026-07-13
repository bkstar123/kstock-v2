@extends('cms.layouts.master')
@section('title', $symbol->code . ' — Company')

@section('content')
{{-- Header --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <h2 class="mb-0" style="font-weight:800;letter-spacing:-.02em">
                        {{ $symbol->code }}
                        @if($symbol->exchange)<span class="badge badge-secondary align-middle">{{ $symbol->exchange }}</span>@endif
                    </h2>
                    <div class="text-muted">{{ $symbol->name }}</div>
                </div>
                <div class="text-right">
                    @if($latestQuote && isset($latestQuote['priceClose']))
                        <div style="font-size:1.9rem;font-weight:800">{{ number_format($latestQuote['priceClose'], 1) }}</div>
                        <small class="text-muted">latest close · vol {{ number_format($latestQuote['totalVolume'] ?? 0) }}</small>
                    @else
                        <small class="text-muted">No recent quote</small>
                    @endif
                </div>
                <div>
                    @if($inWatchlist)
                        <form action="{{ route('cms.watchlist.destroy', ['code' => $symbol->code]) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-secondary"><i class="fas fa-star"></i> Watching</button>
                        </form>
                    @else
                        <form action="{{ route('cms.watchlist.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="symbol" value="{{ $symbol->code }}">
                            <button class="btn btn-warning"><i class="far fa-star"></i> Watch</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Fundamentals --}}
@php $f = $fundamentals ?? []; @endphp
<div class="row">
    <div class="col-6 col-lg mb-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3 style="font-size:1.6rem">{{ isset($f['marketCap']) ? number_format($f['marketCap']/1e9) : '—' }}</h3>
                <p>Market cap (tỷ VND)</p>
            </div>
            <div class="icon"><i class="fas fa-coins"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg mb-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 style="font-size:1.6rem">{{ isset($f['pe']) ? number_format($f['pe'], 2) : '—' }}</h3>
                <p>P/E</p>
            </div>
            <div class="icon"><i class="fas fa-balance-scale"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg mb-3">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3 style="font-size:1.6rem">{{ $priceToBook !== null ? number_format($priceToBook['value'], 2) : '—' }}</h3>
                <p>
                    P/B
                    @if($priceToBook !== null)
                        <small style="opacity:.85">· book {{ $priceToBook['period'] }}</small>
                        @if($priceToBook['stale'])
                            <i class="fas fa-exclamation-triangle"
                               title="Book value từ kỳ {{ $priceToBook['period'] }} có thể đã cũ so với vốn hóa hiện tại nên P/B có thể bị lệch (thường bị thổi cao). Pull báo cáo tài chính mới hơn để chính xác."></i>
                        @endif
                    @endif
                </p>
            </div>
            <div class="icon"><i class="fas fa-book"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg mb-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 style="font-size:1.6rem">{{ isset($f['eps']) ? number_format($f['eps']) : '—' }}</h3>
                <p>EPS</p>
            </div>
            <div class="icon"><i class="fas fa-chart-line"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg mb-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 style="font-size:1.6rem">
                    @php $dy = $f['dividendYield'] ?? null; @endphp
                    {{ $dy !== null ? number_format(($dy <= 1 ? $dy*100 : $dy), 2) . '%' : '—' }}
                </h3>
                <p>Dividend yield</p>
            </div>
            <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
        </div>
    </div>
</div>

{{-- Tabs --}}
<div class="card">
    <div class="card-header p-2">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link active" href="#tab-overview" data-toggle="tab">Overview</a></li>
            <li class="nav-item"><a class="nav-link" href="#tab-price" data-toggle="tab">Price</a></li>
            <li class="nav-item"><a class="nav-link" href="#tab-financials" data-toggle="tab">Financials</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            {{-- Overview --}}
            <div class="active tab-pane" id="tab-overview">
                @php $p = $profile ?? []; @endphp
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th style="width:40%">Company</th><td>{{ $p['companyName'] ?? $symbol->name }}</td></tr>
                            <tr><th>Exchange</th><td>{{ $p['exchange'] ?? $symbol->exchange }}</td></tr>
                            <tr><th>Listing date</th><td>{{ !empty($p['dateOfListing']) ? \Illuminate\Support\Str::before($p['dateOfListing'], 'T') : '—' }}</td></tr>
                            <tr><th>Employees</th><td>{{ isset($p['employees']) ? number_format($p['employees']) : '—' }}</td></tr>
                            <tr><th>Website</th><td>@if(!empty($p['webAddress']))<a href="{{ $p['webAddress'] }}" target="_blank" rel="noopener">{{ $p['webAddress'] }}</a>@else — @endif</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th style="width:40%">Charter capital</th><td>{{ isset($p['charterCapital']) ? number_format($p['charterCapital']/1e9) . ' tỷ' : '—' }}</td></tr>
                            <tr><th>Headquarters</th><td>{{ $p['headQuarters'] ?? '—' }}</td></tr>
                            <tr><th>Industry code</th><td>{{ $symbol->industry_code ?? '—' }}</td></tr>
                            <tr><th>52w range</th><td>{{ (isset($f['low52Week']) && isset($f['high52Week'])) ? number_format($f['low52Week'],1).' – '.number_format($f['high52Week'],1) : '—' }}</td></tr>
                            <tr><th>Foreign own.</th><td>{{ isset($f['foreignOwnership']) ? number_format(($f['foreignOwnership'] <= 1 ? $f['foreignOwnership']*100 : $f['foreignOwnership']),2).'%' : '—' }}</td></tr>
                        </table>
                    </div>
                </div>
                @if(!empty($p['overview']))
                    <h5 class="mt-3">Overview</h5>
                    <p style="text-align:justify">{{ trim(html_entity_decode(strip_tags($p['overview']))) }}</p>
                @endif
            </div>

            {{-- Price --}}
            <div class="tab-pane" id="tab-price">
                <div class="btn-group btn-group-sm mb-3" role="group" id="price-range">
                    @foreach(['1m'=>'1M','3m'=>'3M','6m'=>'6M','1y'=>'1Y','3y'=>'3Y'] as $k=>$label)
                        <button type="button" class="btn btn-outline-primary {{ $k==='1y'?'active':'' }}" data-range="{{ $k }}">{{ $label }}</button>
                    @endforeach
                </div>
                <div id="price-chart" style="height:420px">
                    <div class="text-center text-muted p-5">Loading price history…</div>
                </div>
            </div>

            {{-- Financials --}}
            <div class="tab-pane" id="tab-financials">
                <a href="{{ route('cms.financial.statements.pull', ['symbol' => $symbol->code]) }}" class="btn btn-primary mb-3">
                    <i class="fas fa-download"></i> Pull financial statement
                </a>
                @if($statements->count())
                <table class="table table-hover">
                    <thead><tr><th>Year</th><th>Quarter</th><th>Pulled by</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                        @foreach($statements as $st)
                        <tr>
                            <td>{{ $st->year }}</td>
                            <td>{{ $st->quarter == 0 ? 'Annual' : 'Q'.$st->quarter }}</td>
                            <td>{{ optional($st->admin)->email }}</td>
                            <td class="text-right">
                                <a href="{{ route('cms.financial.statements.show', ['financial_statement' => $st->id]) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Open analysis
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <p class="text-muted">No financial statements pulled for {{ $symbol->code }} yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scriptBottom')
<script src="{{ url('/js/vendor/highcharts/highcharts.js') }}"></script>
<script>
    window.ksPriceHistoryUrl = "{{ route('cms.companies.priceHistory', ['code' => $symbol->code], false) }}";
    window.ksSymbolCode = "{{ $symbol->code }}";
</script>
<script src="{{ asset('cms-assets/js/companies/price-chart.js') }}?v=1"></script>
@endpush
