@push('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <style>
        table tr th:first-child {
            width: 10%;
        }

        table tr th:nth-child(2) {
            width: 4%;
            color: rgb(0, 66, 128);
        }

        #map {
            /* height: 100%; */
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            /* width: 100%; */
        }
    </style>
@endpush

<div>

    <div class="container mx-auto ">
        <div class="bg-white p-6 rounded-lg mt-3 shadow-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h2 class="text-2xl font-bold my-1">Informasi Pegawai</h2>
                    <div class="bg-gray-100 p-4 rounded-lg w-100">
                        <table class="table-auto border-collapse w-full text-left">
                            <tr>
                                <th>Name</th>
                                <th>:</th>
                                <td>{{ Auth::user()->name }}</td>
                            </tr>
                            <tr>
                                <th>Office</th>
                                <th>:</th>
                                <td>{{ $schedule->office->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Shift</th>
                                <th>:</th>
                                <td>{{ $schedule->shift->name ?? '-' }}
                                    {{ $schedule->shift->start_time ? '(' . $schedule->shift->start_time . ')' : '' }} -
                                    {{ $schedule->shift->end_time ? '(' . $schedule->shift->end_time . ')' : '' }}
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <th>:</th>
                                <td>
                                    @if ($schedule->is_wfa)
                                        <p class="text-blue-400">
                                            <span class="font-bold">WFA</span> <span class="font-italic text-sm">(Work
                                                From
                                                Anywhere)</span>
                                        </p>
                                    @else
                                        <p class="text-red-400">
                                            <span class="font-bold">WFO</span> <span class="font-italic text-sm">(Work
                                                From
                                                Office)</span>
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <h4 class="text-l font-bold mb-2">Clock in</h4>
                            <p class="text-gray-500"><strong>{{ $attendance->start_time ?? '00:00' }}</strong></p>
                        </div>
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <h4 class="text-l font-bold mb-2">Clock Out</h4>
                            <p class="text-gray-500"><strong>{{ $attendance->end_time ?? '00:00' }}</strong></p>

                        </div>
                    </div>
                </div>
                <div>
                    <h2 class="text-2xl font-bold my-1">Attendance</h2>

                    <div class="relative h-[400px] mb-4" wire:ignore>

                        <div id="map"></div>

                    </div>

                    @if (session()->has('error'))
                        <div class="text-red-400 p-2 border border-red-400 rounded shadow-sm mb-4">{{ session('error') }}
                        </div>
                    @endif

                    <form class="flex gap-4" wire:submit="store" enctype="multipart/form-data">
                        <button type="button" class="px-4 py-2 bg-blue-500 text-white rounded"
                            onclick="tagLocation()">Absen</button>
                        @if ($isInsideRadius)
                            <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded">Submit
                                Absen</button>
                        @endif

                    </form>
                </div>
            </div>
        </div>
    </div>


    <script>
        const office = [{{ $schedule->office->latitude }}, {{ $schedule->office->longitude }}];

        // SATUAN METER
        const radius = [{{ $schedule->office->radius }}];

        let marker;
        let component;
        document.addEventListener("livewire:initialized", function() {
            component = @this;
            map = L.map('map').setView([{{ $schedule->office->latitude }}, {{ $schedule->office->longitude }}],
                8);
            // Rustle Tile Lyers
            L.tileLayer('https://api.maptiler.com/maps/streets/{z}/{x}/{y}.png?key=enmtbZUSgAknpbn4KuUP', {
                crossOrigin: true,
            }).addTo(map);


            const circle = L.circle(office, {
                color: 'red',
                fillColor: '#f03',
                fillOpacity: 0.5,
                radius: radius,
            }).addTo(map).bindPopup("<b>{{ $schedule->office->name }}</b>");
            // .openPopup();
        });


        const getLocation = () => {
            return new Promise((resolve, reject) => {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const {
                                latitude,
                                longitude
                            } = position.coords;
                            resolve({
                                latitude,
                                longitude
                            });
                        },
                        (error) => {
                            reject(error);
                        }
                    );
                } else {
                    reject(new Error('Geolocation is not supported by this browser.'));
                }
            });
        }

        function addMarker(latitude, longitude) {
            if (marker) {
                map.removeLayer(marker);
            }

            marker = L.marker([latitude, longitude]).addTo(map);
            marker.bindPopup(`<div>
                            <p>{{ Auth::user()->name }}</p>
                        </div>`).openPopup();
            map.setView([latitude, longitude], 16);
        }


        async function tagLocation() {

            const location = await getLocation();

            if (location) {
                addMarker(location.latitude, location.longitude);

                if (isWithInRadius(location.latitude, location.longitude, office, radius)) {
                    component.set('isInsideRadius', true);
                    component.set('latitude', location.latitude);
                    component.set('longitude', location.longitude);
                } else {
                    alert('Anda Diluar Radius');
                }
            }
        }

        async function initLocationUser() {
            const location = await getLocation();

            if (location) {
                addMarker(location.latitude, location.longitude);
            }
        }

        function isWithInRadius(lat, long, center, radius) {
            let is_wfa = '{{ $schedule->is_wfa }}';
            if (is_wfa) {
                return true;
            } else {
                let distance = map.distance([lat, long], center);
                return distance <= radius;
            }
        }


        // Ketika document berhasil di load
        document.addEventListener("DOMContentLoaded", () => {
            // initLocationUser();
        });
    </script>
</div>
