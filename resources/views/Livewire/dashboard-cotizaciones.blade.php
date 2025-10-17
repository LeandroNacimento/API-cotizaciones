<div class="mx-auto max-w-7xl px-4 py-6">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            Cotizaciones – Dashboard
        </h1>

    </div>

    {{-- Tarjetas: cotización actual por tipo --}}
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($cards as $t => $data)
            <button type="button" wire:click="$set('tipo', '{{ $t }}')" class="text-left rounded-2xl border p-4 shadow-sm bg-white dark:bg-gray-950
                                       border-gray-200 dark:border-gray-800
                                       @if($tipo === $t) ring-2 ring-indigo-500 @endif">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-medium">
                        {{ $labels[$t] ?? ucfirst($t) }}
                    </h2>
                    <span class="text-xs text-gray-500">{{ $data['fecha'] ?? '—' }}</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <div>
                        <div class="text-xs text-gray-500">Compra</div>
                        <div class="text-lg font-semibold">
                            @if(!is_null($data['compra']))
                                $ {{ number_format((float) $data['compra'], 2, ',', '.') }}
                            @else — @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Venta</div>
                        <div class="text-lg font-semibold">
                            @if(!is_null($data['venta']))
                                $ {{ number_format((float) $data['venta'], 2, ',', '.') }}
                            @else — @endif
                        </div>
                    </div>
                </div>
            </button>
        @endforeach

    </div>

    {{-- Gráfico 30 días del tipo seleccionado --}}
    <div class="mt-8 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 shadow-sm bg-white dark:bg-gray-950">
        <div class="mb-2 text-sm text-gray-600 dark:text-gray-300">
            Evolución 30 días – {{ $labels[$tipo] ?? ucfirst($tipo) }} (Compra vs Venta)
        </div>

        {{-- contenedor del chart --}}
        <div id="chart" wire:ignore x-data
            x-init="window.initApexChart($el, @js($chart['labels']), @js($chart['series']))">
        </div>
    </div>

    @push('scripts')
        <script>
            window.renderCotChart = function (el, labels, series) {
                const isDark = document.documentElement.classList.contains('dark');

                if (el._apexchart) { try { el._apexchart.destroy(); } catch (e) { } el.innerHTML = ''; }

                const chart = new ApexCharts(el, {
                    chart: { type: 'line', height: 320, toolbar: { show: false } },
                    stroke: { width: 2, curve: 'smooth' },
                    markers: { size: 3 },
                    xaxis: { categories: labels },
                    series: series,
                    noData: { text: 'Sin datos' },
                    legend: { position: 'top' },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        custom: ({ series, dataPointIndex, w }) => {
                            const fecha = w.globals.labels?.[dataPointIndex] ?? '';
                            const compra = series?.[0]?.[dataPointIndex] ?? null;
                            const venta = series?.[1]?.[dataPointIndex] ?? null;

                            const fmt = (v) => (v == null || isNaN(v))
                                ? '—'
                                : Number(v).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                            const bg = isDark ? '#111827' : '#ffffff';
                            const fg = isDark ? '#e5e7eb' : '#111827';
                            const sub = isDark ? '#9ca3af' : '#6b7280';

                            return `<div style="background:${bg};color:${fg};padding:8px 10px;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,.20);font-size:12px;line-height:1.3;">
              <div>Compra: $ ${fmt(compra)}</div>
              <div>Venta: $ ${fmt(venta)}</div>
            </div>`;
                        }
                    }
                });

                chart.render();
                el._apexchart = chart;
            };

            window.initApexChart = function (el, labels, series) {
                const start = () => window.renderCotChart(el, labels, series);
                if (!window.ApexCharts) {
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                    s.onload = start;
                    document.head.appendChild(s);
                } else {
                    start();
                }
            };
        </script>
    @endpush