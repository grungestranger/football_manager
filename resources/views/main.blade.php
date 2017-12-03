@extends('layouts.app')

@section('content')
    @foreach ($users as $item)
    <div>
        {{ $item->name }}
        <span>{{ $item->online ? 'online' : 'offline' }}</span>
        @if($item->id != auth()->user()->id)
        <span data-user_id="{{ $item->id }}" class="requestMatch">Предложить матч</span>
        @endif
    </div>
    @endforeach
@endsection
