@extends('cms.layouts.master')
@section('title', 'My Watchlist')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Add to watchlist</h3></div>
            <form action="{{ route('cms.watchlist.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    @error('symbol')<div class="alert alert-danger">{{ $message }}</div>@enderror
                    <div class="input-group">
                        <input type="text" name="symbol" class="form-control" placeholder="Ticker, e.g. FPT" required>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="far fa-star"></i> Follow</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">My Watchlist</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Code</th><th>Name</th><th class="text-right">Price</th>
                            <th class="text-right">P/E</th><th class="text-right">Mkt cap (tỷ)</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            <td><a href="{{ route('cms.companies.show', ['code' => $row['code']]) }}"><strong>{{ $row['code'] }}</strong></a></td>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-right">{{ $row['price'] !== null ? number_format($row['price'], 1) : '—' }}</td>
                            <td class="text-right">{{ $row['pe'] !== null ? number_format($row['pe'], 2) : '—' }}</td>
                            <td class="text-right">{{ $row['marketCap'] !== null ? number_format($row['marketCap']/1e9) : '—' }}</td>
                            <td class="text-right">
                                <form action="{{ route('cms.watchlist.destroy', ['code' => $row['code']]) }}" method="POST" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-secondary" title="Unfollow"><i class="fas fa-times"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted p-4">Your watchlist is empty. Add a ticker on the left or from a company page.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
