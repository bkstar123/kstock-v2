@extends('cms.layouts.master')
@section('title', 'Settings')

@section('content')
<div class="row">
	<div class="col-md-3">
		<div class="card card-primary">
            <ul class="nav flex-column nav-tabs">
                <li class="nav-item">
                	<a class="nav-link active" href="#thirdParty" data-toggle="tab">
                		Third party authentication
                	</a>
                </li>
                <li class="nav-item">
                	<a class="nav-link" href="#displayStatementItemCode" data-toggle="tab">
                		Display Codes of Statement Items
                	</a>
                </li>
                <li class="nav-item">
                	<a class="nav-link" href="#refreshCalendar" data-toggle="tab">
                		Refresh Calendar
                	</a>
                </li>
            </ul>
        </div>
	</div>
	<div class="col-md-9">
		<div class="tab-content">
            <div class="tab-pane active" id="thirdParty">
                <div class="card">
				    <div class="card-header">
					    <h3>Third party authentication</h3>
					</div>
					<div class="card-body">
					    <form action="{{ route('cms.settings.update') }}" method="POST">
					        @csrf
					        <div class="form-group">
					        	<label for="api_endpoint">API Endpoint</label>
					        	<input class="form-control" 
					        	       type="text" 
					        	       placeholder="Enter the url of the API service"
					        	       id="api_endpoint"
					        	       name="api_endpoint"
					        	       value="{{ config('settings.api_endpoint') }}" />
					        </div>
					        <div class="form-group">
					        	<label for="api_token">API Token</label>
					        	<textarea class="form-control" 
					        	          placeholder="Enter the API token"
					        	          rows="10"
					        	          id="api_token"
					        	          name="api_token">{{ config('settings.api_token') }}</textarea>
					        </div>
					        <div class="form-group">
					        	<label for="limits">Limits</label>
					        	<input class="form-control" 
					        	       type="text" 
					        	       placeholder="Số lượng tối đa kỳ báo cáo tài chính tải về (tối đa là 10, tối thiểu là 2)"
					        	       id="limits"
					        	       name="limits"
					        	       value="{{ config('settings.limits') }}" />
					        	@error('limits')
					        	    <div class="alert alert-danger">{{ $message }}</div>
					        	@enderror
					        </div>
					        <div class="col-12 text-right">
                                <button class="btn btn-success" type="submit">
                                    <i class="fa fa-fw fa-lg fa-check-circle"></i>Save
                                </button>
                            </div>
					    </form>
					</div>
				</div>
            </div>
            <div class="tab-pane" id="displayStatementItemCode">
                <div class="card">
				    <div class="card-header">
					    <h3>Display Options</h3>
					</div>
					<div class="card-body">
					    <form action="{{ route('cms.settings.update') }}" method="POST">
					        @csrf
					        <div class="form-group">
								<div class="row">
									<div class="col-md-12">
										<div class="icheck-warning">
											<input class="form-control"
											       type="checkbox"
											       id="display_statement_item_code"
											       {{ config('settings.display_statement_item_code') != 'on' ?: "checked"}}
											       name="display_statement_item_code">
											       <label for="display_statement_item_code"> Display codes of statement items</label>
										</div>
									</div>
								</div>
							</div>
							<div class="form-group">
								<div class="row">
									<div class="col-md-12">
										<label for="graph_theme"> 
										    Select a theme for graphs
										</label>
										<select class="form-control" name="graph_theme" id="graph_theme">
											<option value="" disabled selected>Please select a theme</option>
											@foreach($graphThemes as $theme)
											    <option value="{{ $theme }}" {{ config('settings.graph_theme') != $theme ?: "selected"}}>{{ $theme }}</option>
											@endforeach
										</select>
									</div>
								</div>
							</div>
					        <div class="col-12 text-right">
                                <button class="btn btn-success" type="submit">
                                    <i class="fa fa-fw fa-lg fa-check-circle"></i>Save
                                </button>
                            </div>
					    </form>
					</div>
				</div>
            </div>
            <div class="tab-pane" id="refreshCalendar">
                <div class="card">
                    <div class="card-header">
                        <h3>Refresh Calendar</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Chọn những ngày thị trường chứng khoán Việt Nam đóng cửa (ngày lễ) để
                            <strong>loại khỏi lịch refresh dữ liệu</strong>. Cuối tuần đã được tự động
                            loại trừ (hiển thị mờ), bạn chỉ cần thêm các ngày lễ.
                        </p>
                        <form action="{{ route('cms.settings.marketCalendar.update') }}" method="POST"
                              id="ks-calendar-form">
                            @csrf
                            <div id="ks-calendar" class="ks-cal"></div>

                            <div class="ks-cal-selected mt-3">
                                <label class="d-block mb-1">
                                    Ngày đã loại trừ (<span id="ks-cal-count">0</span>)
                                </label>
                                <div id="ks-cal-chips" class="ks-cal-chips"></div>
                            </div>

                            <input type="hidden" name="holidays" id="ks-cal-input" value="[]">
                            <div class="col-12 text-right mt-3 px-0">
                                <button class="btn btn-success" type="submit">
                                    <i class="fa fa-fw fa-lg fa-check-circle"></i>Exclude from refresh
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>
@endsection

@push('css')
<style>
    .ks-cal { max-width: 520px; }
    .ks-cal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem; }
    .ks-cal-title { font-weight:700; font-size:1.05rem; color:var(--ks-ink,#0f172a); }
    .ks-cal-nav { display:flex; gap:.35rem; }
    .ks-cal-nav button { border:1px solid var(--ks-border,#e6eaf1); background:var(--ks-card,#fff);
        border-radius:var(--ks-radius-sm,10px); width:34px; height:34px; line-height:1; cursor:pointer;
        color:var(--ks-ink,#0f172a); transition:.15s; }
    .ks-cal-nav button:hover { border-color:var(--ks-primary,#6366f1); color:var(--ks-primary,#6366f1); }
    .ks-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:6px; }
    .ks-cal-dow { text-align:center; font-size:.72rem; font-weight:700; text-transform:uppercase;
        letter-spacing:.03em; color:var(--ks-muted,#64748b); padding:.25rem 0; }
    .ks-cal-cell { aspect-ratio:1/1; display:flex; align-items:center; justify-content:center;
        border:1px solid var(--ks-border,#e6eaf1); border-radius:var(--ks-radius-sm,10px);
        font-size:.85rem; font-weight:600; cursor:pointer; user-select:none; background:var(--ks-card,#fff);
        color:var(--ks-ink,#0f172a); transition:.12s; }
    .ks-cal-cell:hover { border-color:var(--ks-primary,#6366f1); }
    .ks-cal-cell.is-empty { border:none; background:transparent; cursor:default; }
    .ks-cal-cell.is-weekend { background:#f1f5f9; color:#94a3b8; cursor:not-allowed; }
    .ks-cal-cell.is-today { box-shadow:0 0 0 2px var(--ks-primary,#6366f1) inset; }
    .ks-cal-cell.is-holiday { background:var(--ks-primary,#6366f1); border-color:var(--ks-primary,#6366f1);
        color:#fff; }
    .ks-cal-chips { display:flex; flex-wrap:wrap; gap:.4rem; }
    .ks-cal-chip { display:inline-flex; align-items:center; gap:.4rem; background:#eef2ff;
        color:#4f46e5; border:1px solid #e0e7ff; border-radius:999px; padding:.2rem .6rem;
        font-size:.78rem; font-weight:600; }
    .ks-cal-chip button { border:none; background:transparent; color:#4f46e5; cursor:pointer;
        font-size:1rem; line-height:1; padding:0; }
    .ks-cal-empty { color:var(--ks-muted,#64748b); font-size:.82rem; }
</style>
@endpush

@push('scriptBottom')
<script>
    window.ksMarketHolidays = @json(marketHolidays());
</script>
<script src="{{ asset('cms-assets/js/settings/refresh-calendar.js') }}?v=1"></script>
@endpush