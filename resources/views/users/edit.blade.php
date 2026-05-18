<!--     formulario para la creacion de un usuario -->
<form action="{{ route('stores.users.update', [$store->id, $user->id]) }}" method="POST">
    @csrf
    @method('PUT')
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