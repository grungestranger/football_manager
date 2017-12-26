@extends('layouts.app')

@section('content')
<table id="players">
    <thead>
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
    </thead>
    <tbody>
        @foreach ($players as $item)
        <tr data-id="{{ $item->id }}">
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
    </tbody>
</table>
<form id="settingsForm">
    <div id="controls">
        <select name="settings_id">
        @foreach ($settings as $item)
            <option value="{{ $item->id }}">{{ $item->name }}</option>
        @endforeach
        </select>
        <a id="save_settings" href="#">Сохранить</a>
        <a id="save_as_settings_open" href="#">Сохранить как</a>
    </div>
    @foreach ($options as $k => $v)
        {{ $k }}: 
        <select name="settings[{{ $k }}]">
        @foreach ($v as $item)
            <option value="{{ $item }}"{!! $settings[0]->settings->$k == $item ? ' selected' : '' !!}>{{ $item }}</option>
        @endforeach
        </select>
    @endforeach
    <div id="field">
        @foreach ($players as $item)
            @if ($item->settings->position)
        <span data-id="{{ $item->id }}" class="player">{{ $item->id }}</span>
            @endif
        @endforeach
    </div>
    @foreach ($players as $item)
        <input type="hidden" name="players[{{ $item->id }}][position]" value="{{ $item->settings->position ? json_encode($item->settings->position) : 'NULL' }}">
        <input type="hidden" name="players[{{ $item->id }}][reserveIndex]" value="{{ $item->settings->reserveIndex === NULL ? 'NULL' : $item->settings->reserveIndex }}">
    @endforeach
</form>
<div class="popup" id="save_as_settings_block">
    <div class="popup_content">
        Название:
        <input type="text" name="settings_name" form="settingsForm">
        <a id="save_as_settings" href="#">Сохранить</a>
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
