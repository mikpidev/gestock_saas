@switch($name)
    @case('sales')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 6h15l-1.5 9h-12z" />
            <path d="M6 6L5 3H2" />
            <circle cx="9" cy="20" r="1.2" />
            <circle cx="18" cy="20" r="1.2" />
        </svg>
        @break
    @case('products')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 8l9-4 9 4-9 4-9-4z" />
            <path d="M3 8v8l9 4 9-4V8" />
            <path d="M12 12v8" />
        </svg>
        @break
    @case('customers')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="9" cy="8" r="3" />
            <path d="M3.5 19a5.5 5.5 0 0 1 11 0" />
            <circle cx="17" cy="9" r="2.2" />
            <path d="M16.2 14.2a4.5 4.5 0 0 1 4.3 4.8" />
        </svg>
        @break
    @case('reports')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 19V5" />
            <path d="M4 19h16" />
            <path d="M8 15v-4" />
            <path d="M12 15V8" />
            <path d="M16 15v-6" />
        </svg>
        @break
    @case('users')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="8" r="3.2" />
            <path d="M5 19a7 7 0 0 1 14 0" />
        </svg>
        @break
    @case('platform')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="12" rx="2" />
            <path d="M8 20h8" />
            <path d="M12 16v4" />
        </svg>
        @break
    @case('faq')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="8" />
            <path d="M9.5 9.2a2.5 2.5 0 1 1 3.4 2.3c-.8.4-1.4 1-1.4 1.9" />
            <path d="M12 17h.01" />
        </svg>
        @break
    @default
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M8 4h9a2 2 0 0 1 2 2v14l-4-2-4 2-4-2-4 2V8" />
            <path d="M8 4a2 2 0 0 0-2 2v12" />
            <path d="M10 9h6" />
            <path d="M10 13h6" />
        </svg>
@endswitch
