<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        $coordinates = $getState() ?? [];
        $height = $getHeight();
        $zoom = $getZoom();
        $mapId = 'yandex-map-' . $entry->getName() . '-' . uniqid();

        // Calculate center from coordinates
        $centerLat = 41.311081;
        $centerLng = 69.240562;

        if (!empty($coordinates)) {
            $lats = array_column($coordinates, 0);
            $lngs = array_column($coordinates, 1);
            $centerLat = array_sum($lats) / count($lats);
            $centerLng = array_sum($lngs) / count($lngs);
        }
    @endphp

    <div
        id="{{ $mapId }}"
        style="width: 100%; height: {{ $height }}px; border-radius: 0.5rem; overflow: hidden;"
        class="border border-gray-300 dark:border-gray-700"
    ></div>

    @if(empty($coordinates))
        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            No coordinates available for this area.
        </div>
    @endif

    <script>
        (function() {
            function initMap{{ str_replace('-', '', $mapId) }}() {
                if (typeof ymaps === 'undefined') {
                    setTimeout(initMap{{ str_replace('-', '', $mapId) }}, 100);
                    return;
                }

                ymaps.ready(function() {
                    var map = new ymaps.Map('{{ $mapId }}', {
                        center: [{{ $centerLat }}, {{ $centerLng }}],
                        zoom: {{ $zoom }},
                        controls: ['zoomControl', 'typeSelector', 'fullscreenControl']
                    });

                    @if(!empty($coordinates))
                        var coordinates = @json($coordinates);

                        // Create polygon
                        var polygon = new ymaps.Polygon([coordinates], {
                            hintContent: "{{ $entry->getRecord()->name ?? 'Area' }}"
                        }, {
                            fillColor: '#22c55e40',
                            strokeColor: '#16a34a',
                            strokeWidth: 3,
                            fillOpacity: 0.4
                        });

                        map.geoObjects.add(polygon);

                        // Fit map to polygon bounds
                        map.setBounds(polygon.geometry.getBounds(), {
                            checkZoomRange: true,
                            zoomMargin: 30
                        });
                    @endif
                });
            }

            // Load Yandex Maps API if not already loaded
            if (typeof ymaps === 'undefined') {
                var apiKey = '{{ config('services.yandex.maps_api_key', '') }}';
                var script = document.createElement('script');
                script.src = 'https://api-maps.yandex.ru/2.1/?apikey=' + apiKey + '&lang=ru_RU';
                script.onload = initMap{{ str_replace('-', '', $mapId) }};
                document.head.appendChild(script);
            } else {
                initMap{{ str_replace('-', '', $mapId) }}();
            }
        })();
    </script>
</x-dynamic-component>
