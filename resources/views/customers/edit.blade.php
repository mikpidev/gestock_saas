@extends('layouts.admin')

@section('content')

    <!--     formulario para la creacion de un usuario -->
    <form action="{{ route('stores.customers.update', ['store' => $store->id, 'customer' => $customer->id]) }}" method="POST">
        @csrf
        @method('PUT')
        @include('customers._form')

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