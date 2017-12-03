@extends('layouts.app')

@section('content')
<table id="players">
    <tr>
        <th>#</th>
        <th>Игрок</th>
        <th>Поз.</th>
        <th>Скор.</th>
        <th>Ускор.</th>
        <th>Коор.</th>
        <th>Сила.</th>
        <th>Точн.</th>
        <th>Вид.</th>
        <th>Реак.</th>
        <th>В ств.</th>
        <th>На вых.</th>
    </tr>
    @foreach ($players as $item)
    <tr>
        <td>{{ $item->id }}</td>
        <td>{{ $item->name }}</td>
        <td>{{ implode(', ', $item->roles) }}</td>
        <td>{{ $item->speed }}</td>
        <td>{{ $item->acceleration }}</td>
        <td>{{ $item->coordination }}</td>
        <td>{{ $item->power }}</td>
        <td>{{ $item->accuracy }}</td>
        <td>{{ $item->vision }}</td>
        <td>{{ $item->reaction }}</td>
        <td>{{ $item->in_gate }}</td>
        <td>{{ $item->on_out }}</td>
    </tr>
    @endforeach
</table>
<div id="settings_block">
    <div id="controls">
        <select id="settings">
        @foreach ($settings as $item)
            <option value="{{ $item->id }}">{{ $item->name }}</option>
        @endforeach
        </select>
        <a id="save_settings" href="#">Сохранить</a>
        <a id="save_as_settings" href="#">Сохранить как</a>
    </div>
    Тактика: 
    <select id="tactic">
    @foreach ($options['tactic'] as $item)
        <option value="{{ $item }}"{!! $settings[0]->settings->tactic == $item ? ' selected' : '' !!}>{{ $item }}</option>
    @endforeach
    </select>
    <div id="field">
        @foreach ($players as $item)
            @if ($item->settings->position)
        <span data-pos_x="{{ $item->settings->position->x }}" data-pos_y="{{ $item->settings->position->y }}" class="player">{{ $item->id }}</span>
            @endif
        @endforeach
    </div>
</div>
<div class="popup" id="save_as_settings_block">
    <div class="popup_content">
        Название:
        <input type="text">
        <a id="save_as_settings_save" href="#">Сохранить</a>
    </div>
</div>
@endsection

@section('css')
<link href="{{ url('css/team.css') }}" rel="stylesheet">
@endsection

@section('js')
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="{{ url('js/team.js') }}"></script>
@endsection
