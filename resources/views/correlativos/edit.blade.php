@extends('layouts.admin')

@section('content')


<form action="{{ route('correlativos.update', $store->id) }}" method="POST">
    @csrf
    @method('PUT')

    @include('correlativos._form')
</form>

@if ($errors->any())
    <div class="alert alert-danger mt-2 mb-0">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@endsection