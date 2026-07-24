@props(['id' => null, 'type' => 'line', 'labels' => '[]', 'datasets' => '[]', 'height' => '300', 'options' => '{}'])

@php
    $chartId = $id ?? 'chart-' . bin2hex(random_bytes(4));
@endphp

<div style="height: {{ $height }}px;">
    <canvas
        id="{{ $chartId }}"
        x-data="{
            chart: null,
            init() {
                const labels = {{ $labels }};
                const datasets = {{ $datasets }};
                const extraOptions = {{ $options }};

                const config = {
                    type: '{{ $type }}',
                    data: { labels, datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        ...extraOptions
                    }
                };

                this.chart = createChart(this.$refs.canvas, config);
            }
        }"
        x-init="init()"
        x-ref="canvas"
    ></canvas>
</div>
