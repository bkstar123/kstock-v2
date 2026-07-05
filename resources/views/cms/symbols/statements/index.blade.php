@extends('cms.layouts.master')
@section('title', 'List of financial statements')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Financial Statements 
                </h3>
                @can('financial.statements.massiveDestroy')
                    {{ CrudView::removeAllBtn(route('cms.financial.statements.massiveDestroy')) }}
                @else
                    <button class="btn btn-danger" disabled>
                        Remove all
                    </button>
                @endcan
                <div class="card-tools">
                    {{ CrudView::searchInput(route('cms.financial.statements.index')) }}
                </div>
            </div><!-- /.card-header -->
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>
                                {{ CrudView::checkAllBox('danger') }}
                            </th>
                            <th>Symbol</th>
                            <th>Year</th>
                            <th>Quarter</th>
                            <th>Pulled by</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($financial_statements as $financial_statement)
                        <tr>
                            <td>
                                {{ CrudView::checkBox($financial_statement, 'danger') }}
                            </td>
                            <td>
                                <a href="{{ route('cms.financial.statements.show', [
                                    'financial_statement' => $financial_statement->id
                                    ]) }}">{{ $financial_statement->symbol }}</a>
                            </td>
                            <td>
                                {{ $financial_statement->year }}
                            </td>
                            <td>
                                {{ !empty($financial_statement->quarter) ? $financial_statement->quarter : 'Yearly' }}
                            </td>
                            <td>
                                {{ $financial_statement->admin->email }}
                            </td>
                            <td>
                                @can('financial.statements.destroy', $financial_statement)
                                {{ CrudView::removeBtn($financial_statement, route('cms.financial.statements.destroy', [
                                    'financial_statement' => $financial_statement->id
                                    ])) }}
                                @else
                                <button class="btn btn-danger" disabled>
                                    Remove
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div><!-- /.card-body -->
        </div><!-- /.card -->
        Shows {{ $financial_statements->count() }} result(s)
        {{ $financial_statements->links() }}
    </div>
</div>
@endsection