<div class="mx-auto max-w-5xl px-4 py-8">
    {{-- Header con leve gradiente --}}
    <div
        class="rounded-2xl p-6 bg-gradient-to-r from-indigo-600/10 via-indigo-500/5 to-transparent dark:from-indigo-500/15 dark:via-indigo-400/10">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Cotizar USD → ARS</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
            Elegí el tipo de cambio, ingresá el monto en dólares y presioná <strong>Cotizar</strong>.
        </p>
    </div>

    {{-- Formulario --}}
    <form wire:submit.prevent="cotizarViaApi" class="mt-8 space-y-8">
        {{-- Tipo: chips (alto contraste + accesibles) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Tipo de cambio</label>

            <div class="flex flex-wrap gap-2" role="group" aria-label="Tipo de cambio">
                @php
                    $chipBase = 'inline-flex items-center rounded-full px-4 py-1.5 text-sm font-medium transition focus:outline-none';
                @endphp

                @foreach($tiposPermitidos as $t)
                    <button type="button" wire:click="$set('tipo','{{ $t }}')"
                        aria-pressed="{{ $tipo === $t ? 'true' : 'false' }}" @class([
                            $chipBase,
                            // Activo: contraste alto + ring con offset (accesible)
                            'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-500 ring-offset-2 dark:ring-offset-gray-950 hover:bg-indigo-600/95' => $tipo === $t,
                            // Inactivo: borde neutro + hover sutil
                            'border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800' => $tipo !== $t,
                        ])>
                        {{ $labels[$t] ?? ucfirst($t) }}
                    </button>
                @endforeach
            </div>

            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Usamos <em>venta</em> si existe; si no, <em>compra</em>.
            </p>
        </div>

        {{-- Monto + botón --}}
        <div class="grid gap-6 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="monto" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monto
                    (USD)</label>
                <div class="mt-1 relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-600 dark:text-gray-400">USD</span>
                    <input id="monto" type="text" inputmode="decimal" autocomplete="off" autocapitalize="off"
                        placeholder="Ej: 150.50" wire:model.defer="monto" class="block w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900
                               pl-12 pr-3 py-3 text-gray-900 dark:text-gray-100 tabular-nums
                               placeholder:text-gray-600 dark:placeholder:text-gray-400
                               shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/40" />
                </div>
            </div>

            <div class="sm:col-span-1 flex items-end">
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold
               bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-sm
               hover:from-indigo-600 hover:to-indigo-600 active:from-indigo-700 active:to-indigo-600
               focus:outline-none focus:ring-2 focus:ring-indigo-500/50
               disabled:opacity-50 disabled:cursor-not-allowed" wire:loading.attr="disabled">
                    <svg wire:loading class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span wire:loading.remove>💱 Cotizar</span>
                    <span class="sr-only" wire:loading>Procesando…</span>
                </button>

            </div>
        </div>

        {{-- Error (si existe) --}}
        @if($error)
            <div class="rounded-xl border border-red-300/70 bg-red-50 px-4 py-3 text-sm text-red-800
                            dark:border-red-800/60 dark:bg-red-900/25 dark:text-red-200">
                {{ $error }}
            </div>
        @endif
    </form>

    {{-- Tarjetas --}}
    <div class="mt-10 grid gap-6 sm:grid-cols-3">
        <div
            class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/70 p-5 shadow-sm backdrop-blur">
            <div class="text-sm text-gray-600 dark:text-gray-300">Compra</div>
            <div class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100 tabular-nums">
                @if(!is_null($compra)) $ {{ number_format($compra, 2, ',', '.') }} @else — @endif
            </div>
        </div>
        <div
            class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/70 p-5 shadow-sm backdrop-blur">
            <div class="text-sm text-gray-600 dark:text-gray-300">Venta</div>
            <div class="mt-1 text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100 tabular-nums">
                @if(!is_null($venta)) $ {{ number_format($venta, 2, ',', '.') }} @else — @endif
            </div>
        </div>
        <div
            class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/70 p-5 shadow-sm backdrop-blur">
            <div class="text-sm text-gray-600 dark:text-gray-300">Usada</div>
            <div class="mt-1 text-xl font-medium text-gray-900 dark:text-gray-100">
                {{ $usada ? ucfirst($usada) : '—' }}
            </div>
        </div>
    </div>

    {{-- Resultado --}}
    <div
        class="mt-10 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-950/70 p-6 shadow-sm backdrop-blur">
        <div class="text-sm text-gray-600 dark:text-gray-300">Resultado en pesos (ARS)</div>
        <div
            class="mt-2 text-4xl sm:text-5xl font-extrabold tracking-tight text-gray-900 dark:text-gray-100 tabular-nums">
            @if(!is_null($resultado))
                $ {{ number_format($resultado, 2, ',', '.') }}
            @else
                —
            @endif
        </div>

        <div
            class="mt-4 border-t border-gray-200 dark:border-gray-800 pt-3 text-xs text-gray-500 dark:text-gray-400 flex flex-wrap gap-x-4 gap-y-1">
            <span>Tipo: <strong
                    class="text-gray-700 dark:text-gray-200">{{ $labels[$tipo] ?? ucfirst($tipo) }}</strong></span>
            <span>Fecha: <strong class="text-gray-700 dark:text-gray-200">{{ $fecha ?? '—' }}</strong></span>
            @if(!is_null($compra) && !is_null($venta))
                @php $spread = $venta - $compra;
                $pct = $compra > 0 ? ($venta / $compra - 1) * 100 : null; @endphp
                <span>Spread: <strong class="text-gray-700 dark:text-gray-200">$
                        {{ number_format($spread, 2, ',', '.') }}</strong>
                    @if($pct !== null) ({{ number_format($pct, 2, ',', '.') }}%) @endif
                </span>
            @endif
        </div>
    </div>

@php
    // Solo construir URLs si hay resultado
    $showCalendarBtns = !is_null($resultado);

    if ($showCalendarBtns) {
        $tz = config('app.timezone', 'UTC');

        // Arranque: dentro de 10 minutos, duración 15m
        $start = now($tz)->addMinutes(10);
        $end   = (clone $start)->addMinutes(15);

        // Google Calendar requiere fechas UTC formato YYYYMMDDTHHMMSSZ
        $gStart = $start->copy()->utc()->format('Ymd\THis\Z');
        $gEnd   = $end->copy()->utc()->format('Ymd\THis\Z');

        $titulo = 'Revisar cotización '.$labels[$tipo] ?? ucfirst($tipo);
        $detalle = 'Abrir la app y verificar valores de compra/venta.';

        $gcalUrl = 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action'  => 'TEMPLATE',
            'text'    => $titulo,
            'details' => $detalle,
            'dates'   => "{$gStart}/{$gEnd}",
        ]);

        // Enlace a ICS (nuestro endpoint)
        $icsUrl = route('calendar.ics', [
            'title'       => $titulo,
            'description' => $detalle,
            'dtstart'     => $start->toIso8601String(), // respeta TZ local
            'duration'    => 15,
            'tz'          => $tz,
            // 'rrule'    => 'FREQ=DAILY;BYHOUR=10;BYMINUTE=0;BYSECOND=0' // si querés recurrente, descomentar
        ], false);
    }
@endphp

@if($showCalendarBtns)
    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ $gcalUrl }}" target="_blank" rel="noopener"
           class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium
                  bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none
                  focus:ring-2 focus:ring-emerald-500/60 focus:ring-offset-2 dark:focus:ring-offset-gray-950">
            Añadir a Google Calendar
        </a>

        <a href="{{ $icsUrl }}"
           class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium
                  border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900
                  text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800
                  focus:outline-none focus:ring-2 focus:ring-indigo-500/60">
            Descargar .ics
        </a>
    </div>
@endif


    {{-- Overlay de carga sutil --}}
    <div wire:loading.delay.short class="fixed inset-0 z-40 bg-black/20 backdrop-blur-sm"></div>
</div>