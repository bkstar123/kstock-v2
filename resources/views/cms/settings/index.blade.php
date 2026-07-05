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
        </div>
	</div>
</div>
@endsection