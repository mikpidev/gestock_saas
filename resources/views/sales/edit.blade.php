@extends('layouts.admin')

@section('content')
    <style>
        .gestok-form-card {
            background: #fff;
            color: #000;
            width: 100%;
            max-width: 450px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            overflow: hidden;
            margin: 2rem auto;
        }
        .gestok-form {
            background: #000;
            color: #fff;
            padding: 1.5rem;
            text-align: center;
        }
        .gestok-form-header h1 {
            font-size: 1.6rem;
            font-weight: bold;
            margin: 0;
        }
        .gestok-form-header p {
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .gestok-form-body {
            padding: 2rem;
        }
        .gestok-form-body label {
            font-size: 0.9rem;
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 500;
        }
        .gestok-form-body input[type="email"],
        .gestok-form-body input[type="password"],
        .gestok-form-body input[type="text"],
        .gestok-form-body input[type="numeric"],

        .gestok-form-body select {
            width: 100%;
            padding: 0.6rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 0.95rem;
            box-sizing: border-box;
        }
        .gestok-form-body select {
            background: #fff;
            cursor: pointer;
        }
        .gestok-form-body .btn {
            background: #000;
            color: #fff;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: bold;
            width: 100%;
            margin-bottom: 1rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .gestok-form-body .btn:hover {
            background: #333;
        }
        .gestok-form-body .btn-secondary {
            background: #666;
            color: #fff;
        }
        .gestok-form-body .btn-secondary:hover {
            background: #555;
        }
        .gestok-form-body .text-danger {
            color: #dc3545;
            font-size: 0.8rem;
            margin-top: -0.8rem;
            margin-bottom: 0.8rem;
        }
        .gestok-form-body .form-text {
            font-size: 0.8rem;
            color: #666;
            margin-top: -0.8rem;
            margin-bottom: 0.8rem;
        }
        .gestok-form-actions {
            display: flex;
            gap: 0.5rem;
            flex-direction: column;
        }
        @media (min-width: 400px) {
            .gestok-form-actions {
                flex-direction: row;
            }
            .gestok-form-actions .btn {
                width: auto;
                flex: 1;
                margin-bottom: 0;
            }
        }
    </style>
    <!--     formulario para la creacion de un usuario -->
    <form action="{{ isset($productType) 
        ? route('stores.product_types.update', [$store->id, $productType->id]) 
        : route('stores.product_types.store', $store->id) }}" method="POST">
    @csrf
    @if(isset($productType))
    @method('PUT')
    @endif

    @include('sales._form')
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