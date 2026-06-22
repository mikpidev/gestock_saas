<!--     formulario para la creacion de un productos -->
<form action="{{ route('stores.product_types.update', ['store' => $store->id, $productType->id]) }}" method="POST">
    @csrf
    @method('PUT')
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