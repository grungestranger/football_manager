@extends('layouts.app')

@section('content')
@if ($isMatch)
    @if ($action)
<script>
    var action = {!! $action !!};
</script>
    @endif
<div id="matchField">
    @if ($time < 0)
    <div id="matchLoader"><img src="/img/loader.gif"><span>{{ abs($time) }}</span></div>
    @endif
</div>
@endif

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
        @if (!$settings->id)
        <option value="NULL">Load</option>
        @endif
        @foreach ($allSettings as $item)
            <option value="{{ $item->id }}"{!! $settings->id == $item->id ? ' selected' : '' !!}>{{ $item->name }}</option>
        @endforeach
        </select>
        @if (!$isMatch)
        <a id="remove_settings"{!! $allSettings->count() < 2 ? ' class="displayNone"' : '' !!} href="#">Удалить</a>
        <a id="save_settings" class="displayNone" href="#">Сохранить</a>
        <a id="save_as_settings_open" href="#">Сохранить как</a>
        @else
        <a id="confirm_settings" class="displayNone" href="#">Принять</a>
        @endif

    </div>
    @foreach ($options as $k => $v)
        {{ $k }}: 
        <select name="settings[{{ $k }}]">
        @foreach ($v as $item)
            <option value="{{ $item }}"{!! $settings->settings->$k == $item ? ' selected' : '' !!}>{{ $item }}</option>
        @endforeach
        </select>
    @endforeach
    <div id="field">
        @foreach ($rolesAreas as $k => $v)
        <div data-coords="{{ json_encode($v) }}" class="rolesArea"><span>{{ $k }}</span></div>
        @endforeach
        @foreach ($players as $item)
        <span data-id="{{ $item->id }}" class="player{{ !$item->settings->position ? ' displayNone' : '' }}">{{ $item->id }}</span>
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
@if ($isMatch)
<link href="{{ url('css/match.css') }}" rel="stylesheet">
@endif
@endsection

@section('js')
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="{{ url('js/team.js') }}"></script>
@if ($isMatch)
<script src="{{ url('js/match.js') }}"></script>
@endif
@endsection
