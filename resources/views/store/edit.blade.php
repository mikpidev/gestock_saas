
<!-- formulario para la edición de una tienda -->
<form action="{{ route('stores.update', $store->id) }}" method="POST">
    @csrf
    @method('PUT')
    @include('store._form')
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

