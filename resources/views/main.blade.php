@extends('layouts.app')

@section('content')
    @foreach ($users as $item)
    <div data-id="{{ $item->id }}" class="user {{
    	$item->online || $item->id == auth()->user()->id? 'online' : 'offline'
    }}">
        {{ $item->name }}
        <span class="status"></span>
        @if(
            $item->id != auth()->user()->id
            && !count(auth()->user()->challengesFrom->filter(
                function ($value, $key) use ($item) {
                    return $value->user_to == $item->id;
                }
            ))
        )
        <a class="challenge" data-id="{{ $item->id }}" href="#">Предложить матч</a>
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
