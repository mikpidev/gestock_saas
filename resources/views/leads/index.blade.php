@extends('layouts.admin')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Solicitudes de información</h2>
</div>

<p class="text-muted mb-4">Leads enviados desde la landing de Gestock.</p>

<div class="table-responsive mt-4">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Nombre</th>
                <th>Negocio</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Mensaje</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leads as $lead)
            <tr>
                <td>{{ $lead->created_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $lead->name }}</td>
                <td>{{ $lead->business_name }}</td>
                <td><a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></td>
                <td>{{ $lead->phone ?: '—' }}</td>
                <td>{{ $lead->message ?: '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted py-4">Aún no hay solicitudes.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center">
    {{ $leads->links() }}
</div>

@endsection
