@extends('layouts.app')

@section('content')
<div id="users">
    @foreach ($users as $item)
    <div data-id="{{ $item->id }}" class="user {{
    	$item->online ? 'online' : 'offline'
    }}">
        {{ $item->name }}
        <span class="status"></span>
        <a class="challenge{{ $item->challenge ? '' : ' displayNone' }}" href="#">Предложить матч</a>
    </div>
    @endforeach
</div>
@endsection

@section('css')
<link href="{{ url('css/main.css') }}" rel="stylesheet">
@endsection

@section('js')
<script src="{{ url('js/main.js') }}"></script>
@endsection
