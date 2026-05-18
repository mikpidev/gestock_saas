<!--     formulario para la creacion de un usuario -->
<form action="{{ isset($productType) 
        ? route('stores.product_types.update', [$store->id, $productType->id]) 
        : route('stores.product_types.store', $store->id) }}" method="POST">
    @csrf
    @if(isset($productType))
    @method('PUT')
    @endif

    @include('productType._form')
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