@php
    $level = $dteUsage['warning_level'] ?? 'ok';
    $used = $dteUsage['used'] ?? 0;
    $limit = $dteUsage['limit'] ?? null;
    $message = $dteUsage['message'] ?? null;
@endphp

<div id="dte-usage-banner"
    class="mb-3 dte-usage-{{ $level }}"
    data-warning-level="{{ $level }}">
    @if ($level === 'critical')
        <div class="alert alert-danger d-flex flex-wrap align-items-center justify-content-between gap-2 mb-0" role="alert">
            <div>
                <span class="badge text-bg-danger">Crítico</span>
                <strong class="ms-2">DTE en nivel crítico.</strong>
                @if ($message)
                    <span class="d-block mt-1">{{ $message }}</span>
                @endif
            </div>
            <a class="btn btn-danger" href="{{ route('landing') }}#contacto">Contactar soporte</a>
        </div>
    @elseif ($level === 'warn')
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-0" role="alert">
            <div>
                <span class="badge text-bg-warning">Aviso</span>
                <strong class="ms-2">DTE cerca del límite.</strong>
                @if ($message)
                    <span class="d-block mt-1">{{ $message }}</span>
                @endif
            </div>
            <a class="btn btn-warning" href="{{ route('landing') }}#contacto">Contactar soporte</a>
        </div>
    @else
        <div class="small text-muted" role="status">
            DTE del mes:
            @if ($limit === null)
                {{ $used }} / ilimitados
            @else
                {{ $used }} / {{ $limit }}
            @endif
        </div>
    @endif
</div>
