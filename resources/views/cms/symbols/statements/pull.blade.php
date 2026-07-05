@extends('cms.layouts.master')
@section('title', 'Pull financial statement')

@section('content')
<div class="row">
	<div class="col-md-6">
		<div class="card card-primary">
			<div class="card-header">
				<h3 class="card-title">Enter details of the financial statements</h3>
			</div>
			<form id="form" action="{{ route('cms.financial.statements.pull') }}" method="POST">
				@csrf
				<div class="card-body">
					<div class="form-group">
						<label for="symbol">Symbol <span style="color:red">&midast;</span></label>
						@error('symbol')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
						<input type="text" 
						       required
						       class="form-control" 
						       id="symbol" 
						       name="symbol"
						       placeholder="Enter symbol (e.g: FPT, HPG, MWG, etc...)"
						       value="{{ old('symbol', request('symbol')) }}" />
					</div>
					<div class="form-group">
						<label for="year">Year of the financial statement <span style="color:red">&midast;</span></label>
						@error('year')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
						<input type="text" 
						       required
						       class="form-control" 
						       id="year" 
						       name="year"
						       placeholder="Year of the financial statement"
						       value="{{ old('year') }}" />
					</div>
					<div class="form-group">
						<label for="year">Quarter of the concerned year <span style="color:red">&midast;</span></label>
						@error('quarter')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
						<input type="text" 
						       required
						       class="form-control" 
						       id="quarter" 
						       name="quarter"
						       placeholder="Quarter of the concerned year (e.g: 0 -> full year, 1 -> Q1, 2 -> Q2, etc...)"
						       value="{{ old('quarter') }}" />
					</div>
					<div class="card-footer">
						<button id="submitBtn" type="submit" class="btn btn-primary">Pull</button>
					</div>
				</div>
		    </form>
        </div>
	</div>
</div>
@endsection

@push('scriptBottom')
<script type="text/javascript">
	$(document).ready(function () {
		$("#form").submit(function () {
			$("#submitBtn").attr('disabled', true);
		});
	});
</script>
@endpush