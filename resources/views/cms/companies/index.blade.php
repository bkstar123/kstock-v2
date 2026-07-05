@extends('cms.layouts.master')
@section('title', 'Symbol Directory')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Add a symbol</h3>
            </div>
            <form action="{{ route('cms.companies.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    @error('symbol')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    <div class="input-group">
                        <input type="text" name="symbol" class="form-control"
                               placeholder="Ticker, e.g. FPT" value="{{ old('symbol') }}" required>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <small class="text-muted">Resolves the ticker against the data provider and adds it to the directory.</small>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Symbol Directory</h3>
                <div class="card-tools">
                    <form action="{{ route('cms.companies.index') }}" method="GET" class="form-inline">
                        @if($exchanges->count())
                        <select name="exchange" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="">All exchanges</option>
                            @foreach($exchanges as $ex)
                                <option value="{{ $ex }}" @selected($exchange === $ex)>{{ $ex }}</option>
                            @endforeach
                        </select>
                        @endif
                        <div class="input-group input-group-sm" style="width: 220px;">
                            <input type="text" name="search" class="form-control" placeholder="Search code or name" value="{{ $search }}">
                            <div class="input-group-append">
                                <button class="btn btn-default" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Exchange</th>
                            <th>Type</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                        <tr>
                            <td>
                                <a href="{{ route('cms.companies.show', ['code' => $company->code]) }}">
                                    <strong>{{ $company->code }}</strong>
                                </a>
                            </td>
                            <td>{{ $company->name }}</td>
                            <td>@if($company->exchange)<span class="badge badge-secondary">{{ $company->exchange }}</span>@endif</td>
                            <td>{{ $company->company_type }}</td>
                            <td class="text-right">
                                <a href="{{ route('cms.companies.show', ['code' => $company->code]) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-chart-line"></i> View
                                </a>
                                <form action="{{ route('cms.watchlist.store') }}" method="POST" style="display:inline">
                                    @csrf
                                    <input type="hidden" name="symbol" value="{{ $company->code }}">
                                    <button class="btn btn-sm btn-warning" type="submit" title="Add to watchlist">
                                        <i class="far fa-star"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted p-4">
                                No symbols yet. Add one on the left, or run <code>php artisan symbols:sync FPT VNM HPG</code>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $companies->links() }}
    </div>
</div>
@endsection
