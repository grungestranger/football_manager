@extends('layouts.start')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Email Confirm</div>
                <div class="panel-body">
					@if ($success)
						<div class="confirm_email_success">{{ $message }}</div>
					@else
						<div class="confirm_email_not_success">{{ $message }}</div>
					@endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
