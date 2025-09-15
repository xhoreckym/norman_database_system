export default function empodatModal() {
    return {
        showModal: false,
        record: null,
        recordId: null,
        mapInstance: null,
        stationArray: [],
        analyticalMethodArray: [],
        dataSourceArray: [],
        metaDataArray: [],

        init() {
            // Initialize Alpine component
            console.log('Empodat modal component initialized');
        },

        initLeaflet() {
            // Initialize Leaflet once when component loads
            // We'll wait to set the view until after we have coordinates
            // or we can set some default. For now, let's do a blank init.
            this.mapInstance = L.map('map', {
                center: [0, 0],
                zoom: 2
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.mapInstance);
        },

        async openModal(recordId) {
            try {
                console.log('Opening Empodat modal for record ID:', recordId);
                
                this.recordId = recordId; // Store the record ID

                // Fetch record data from our /records/:id/json route
                const response = await fetch(
                    window.empodatRoutes.show.replace(':id', recordId)
                );

                if (!response.ok) {
                    console.error('Failed to fetch record data:', response.status, response.statusText);
                    throw new Error('Failed to fetch record data');
                }

                this.record = await response.json();
                console.log('Record data loaded:', this.record);

                // Build arrays for display, filtering out unwanted keys and empty/null values
                this.buildStationArray();
                this.buildAnalyticalMethodArray();
                this.buildDataSourceArray();
                this.buildMetaDataArray();

                // Show the modal
                this.showModal = true;

                // Update map with record coordinates
                this.updateMapLocation();

            } catch (error) {
                console.error('Error opening Empodat modal:', error);
                alert('Failed to load record data. Please try again.');
            }
        },

        buildStationArray() {
            if (this.record.station) {
                const excludedKeys = ['id', 'created_at', 'updated_at'];
                this.stationArray = Object.entries(this.record.station)
                    .filter(([key, val]) =>
                        !excludedKeys.includes(key) &&
                        val !== null &&
                        val !== ''
                    );
            } else {
                this.stationArray = [];
            }
        },

        buildAnalyticalMethodArray() {
            if (this.record.analytical_method) {
                const excludedKeys = ['id', 'created_at', 'updated_at'];
                this.analyticalMethodArray = Object.entries(this.record.analytical_method)
                    .filter(([key, val]) =>
                        !excludedKeys.includes(key) &&
                        val !== null &&
                        val !== ''
                    );
            } else {
                this.analyticalMethodArray = [];
            }
        },

        buildDataSourceArray() {
            if (this.record.data_source) {
                const excludedKeys = ['id', 'created_at', 'updated_at'];
                this.dataSourceArray = Object.entries(this.record.data_source)
                    .filter(([key, val]) =>
                        !excludedKeys.includes(key) &&
                        val !== null &&
                        val !== ''
                    );
            } else {
                this.dataSourceArray = [];
            }
        },

        buildMetaDataArray() {
            if (this.record.matrix_data && this.record.matrix_data.meta_data) {
                const excludedKeys = ['id', 'created_at', 'updated_at'];
                
                this.metaDataArray = Object.entries(this.record.matrix_data.meta_data)
                    .filter(([key, val]) =>
                        !excludedKeys.includes(key) &&
                        val !== null &&
                        val !== ''
                    );
            } else {
                this.metaDataArray = [];
            }
        },

        updateMapLocation() {
            // Now that we have record coordinates, update the map
            if (this.record.station && this.record.station.latitude && this.record.station.longitude) {
                // Fly or setView to the record's location
                this.mapInstance.setView([this.record.station.latitude, this.record.station.longitude], 7);

                // Clear existing markers (if any).
                this.mapInstance.eachLayer((layer) => {
                    if (layer instanceof L.Marker) {
                        this.mapInstance.removeLayer(layer);
                    }
                });

                // Add a marker
                L.marker([this.record.station.latitude, this.record.station.longitude])
                    .addTo(this.mapInstance)
                    .bindPopup(`Record ID: ${this.recordId}`);
            }
        },

        closeModal() {
            this.showModal = false;
            this.record = null;
            this.recordId = null;
            this.stationArray = [];
            this.analyticalMethodArray = [];
            this.dataSourceArray = [];
            this.metaDataArray = [];
            // Optionally reset map or let it persist
        }
    };
}

// Make it available globally for Alpine
window.empodatModal = empodatModal;
