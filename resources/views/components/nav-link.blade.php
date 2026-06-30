@props(['href', 'title' => '', 'icon' => '', 'active' => false])

<a href="{{ $href }}"
   title="{{ $title }}"
   class="nav-link institutional-nav-link {{ $active ? 'institutional-nav-link-active' : '' }}">
    <span class="text-lg shrink-0 w-6 text-center">{{ $icon }}</span>
    <span class="nav-label truncate">{{ $slot }}</span>
</a>
