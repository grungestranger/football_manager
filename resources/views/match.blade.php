@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading">Dashboard</div>

                <div class="panel-body">
<div id="field">
    <span id="unit_0" class="player">0</span>
    @foreach ($players as $item)
    <span id="unit_{{ $item['player_id'] }}" class="player team_{{ $item['user_id'] }}">{{ $item['player_id'] }}</span>
    @endforeach
</div>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="?">
    @foreach ($players as $item)
    Игрок {{ $item['player_id'] }} <input type="text" name="players[{{ $item['player_id'] }}][x]" value="{{ json_decode($item['position'])[0]->x }}"> - x, <input type="text" name="players[{{ $item['player_id'] }}][y]" value="{{ json_decode($item['position'])[0]->y }}"> - y<br>
    @endforeach
    <input type="submit">
</form>
@endsection

@section('css')
<link href="{{ url('css/match.css') }}" rel="stylesheet">
@endsection

@section('js')
<script src="{{ url('js/match.js') }}"></script>
@endsection
