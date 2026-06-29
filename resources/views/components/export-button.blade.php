@props([
    'route' => '',
    'params' => [],
])
<button type="button"
    x-data="{}"
    @click.prevent="
        const p = {};
        @foreach($params as $queryParam => $wireProp)
            const raw{{ $loop->index }} = $wire?.{{ $wireProp }};
            if (raw{{ $loop->index }} === true || raw{{ $loop->index }} === '1') {
                p['{{ $queryParam }}'] = '1';
            } else if (raw{{ $loop->index }} !== null && raw{{ $loop->index }} !== '' && raw{{ $loop->index }} !== undefined && raw{{ $loop->index }} !== false) {
                p['{{ $queryParam }}'] = raw{{ $loop->index }};
            }
        @endforeach
        const qs = Object.keys(p).length > 0 ? '?' + new URLSearchParams(p).toString() : '';
        window.open('{{ route($route) }}' + qs, '_blank');
    "
    {{ $attributes->merge(['class' => 'btn-export']) }}>
    Exportar CSV
</button>
