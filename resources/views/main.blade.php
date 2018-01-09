@extends('layouts.app')

@section('content')
    @foreach ($users as $item)
    <div>
        {{ $item->name }}
        <span>{{ $item->online ? 'online' : 'offline' }}</span>
        @if($item->id != auth()->user()->id)
        <a href="{{ url('match/challenge?user_id=' . $item->id) }}">Предложить матч</a>
        @endif
    </div>
    @endforeach
@endsection
