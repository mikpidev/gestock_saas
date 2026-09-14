<form method="POST" action="{{ route('landing.contact') }}" class="lp-form">
    @csrf
    <div class="lp-honeypot" aria-hidden="true">
        <label>Sitio web
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </label>
    </div>
    <label>
        Nombre
        <input type="text" name="name" value="{{ old('name') }}" required maxlength="120">
    </label>
    <label>
        Negocio
        <input type="text" name="business" value="{{ old('business') }}" required maxlength="160">
    </label>
    <label>
        Correo electrónico
        <input type="email" name="email" value="{{ old('email') }}" required maxlength="160">
    </label>
    <label>
        Teléfono
        <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="40">
    </label>
    <label>
        Mensaje
        <textarea name="message" rows="4" maxlength="2000">{{ old('message') }}</textarea>
    </label>
    @if ($errors->any())
        <div class="lp-alert lp-alert--error">
            {{ $errors->first() }}
        </div>
    @endif
    <button type="submit" class="lp-btn lp-btn--primary">Solicitar información</button>
</form>
