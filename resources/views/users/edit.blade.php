@extends('layouts.admin')

@section('content')

    <!--     formulario para la creacion de un usuario -->
    <form action="{{ route('stores.users.store', $store->id) }}" method="POST">
    @csrf
        @include('users._form')
    </form>
    
    @if ($errors->any())
        <div class="alert alert-danger">    
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>       
        </div>
    @endif
    
    
@endsection