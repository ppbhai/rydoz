<div class="app-header">
    <div class="app-shell">
        <div class="app-header-inner">
            <div>
                @if (!empty($backUrl))
                    <a href="{{ $backUrl }}" class="header-action" aria-label="Back">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                @elseif (!empty($leftIcon) && !empty($leftUrl))
                    <a href="{{ $leftUrl }}" class="header-action" aria-label="{{ $leftLabel ?? 'Action' }}">
                        <i class="{{ $leftIcon }}"></i>
                    </a>
                @else
                    <span></span>
                @endif
            </div>
            <div class="header-brand">
                <span class="header-kicker">{{ $kicker ?? 'RYDOZ' }}</span>
                <p class="header-title">{{ $title ?? 'Dashboard' }}</p>
            </div>
            <div>
                @if (!empty($rightUrl) && !empty($rightIcon))
                    <a href="{{ $rightUrl }}" class="header-action right" aria-label="{{ $rightLabel ?? 'Action' }}">
                        <i class="{{ $rightIcon }}"></i>
                    </a>
                @elseif (!empty($rightUrl) && !empty($rightText))
                    <a href="{{ $rightUrl }}" class="header-action right" aria-label="{{ $rightText }}">
                        {{ $rightText }}
                    </a>
                @elseif (!request()->routeIs('index'))
                    <a href="{{ route('index') }}" class="header-action right" aria-label="Home">
                        <i class="fas fa-house"></i>
                    </a>
                @else
                    <span></span>
                @endif
            </div>
        </div>
    </div>
</div>
