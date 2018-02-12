@extends('layouts.app')

@section('content')
    @foreach ($users as $item)
    <div data-id="{{ $item->id }}" class="user {{
    	$item->online ? 'online' : 'offline'
    }}">
        {{ $item->name }}
        <span class="status"></span>
        @if($item->challenge)
        <a class="challenge" href="#">Предложить матч</a>
        @endif
    </div>
    @endforeach
@endsection

@section('css')
<link href="{{ url('css/main.css') }}" rel="stylesheet">
@endsection

@section('js')
<script src="{{ url('js/main.js') }}"></script>
@endsection
