export default function empodatModal() {
    return {
        showModal: false,
        record: null,
        recordId: null,
        mapInstance: null,
        mapMarker: null,
        stationArray: [],
        analyticalMethodArray: [],
        dataSourceArray: [],
        metaDataArray: [],

        init() {
            // Initialize Alpine component
            console.log('Empodat modal component initialized');
            
            // Clean up map on page unload
            window.addEventListener('beforeunload', () => {
                if (this.mapInstance) {
                    this.mapInstance.remove();
                    this.mapInstance = null;
                }
            });
        },

        async openModal(recordId) {
            try {
                console.log('Opening Empodat modal for record ID:', recordId);
                
                this.recordId = recordId;

                // Fetch record data
                const response = await fetch(
                    window.empodatRoutes.show.replace(':id', recordId)
                );

                if (!response.ok) {
                    console.error('Failed to fetch record data:', response.status, response.statusText);
                    throw new Error('Failed to fetch record data');
                }

                this.record = await response.json();
                console.log('Record data loaded:', this.record);

                // Build arrays for display
                this.buildStationArray();
                this.buildAnalyticalMethodArray();
                this.buildDataSourceArray();
                this.buildMetaDataArray();

                // Show the modal
                this.showModal = true;

                // Initialize map after modal is shown and DOM is updated
                // Use nextTick to ensure DOM is ready
                await this.$nextTick();
                
                // Additional delay to ensure modal transition is complete
                setTimeout(() => {
                    if (this.hasValidCoordinates()) {
                        this.initializeMap();
                    }
                }, 300);

            } catch (error) {
                console.error('Error opening Empodat modal:', error);
                alert('Failed to load record data. Please try again.');
            }
        },

        initializeMap() {
            console.log('Initializing map...');
            
            const mapContainer = document.getElementById('map');
            
            if (!mapContainer) {
                console.error('Map container not found');
                return;
            }

            // Clean up existing map instance if it exists
            if (this.mapInstance) {
                console.log('Removing existing map instance');
                this.mapInstance.remove();
                this.mapInstance = null;
                this.mapMarker = null;
            }

            try {
                const lat = parseFloat(this.record.station.latitude);
                const lng = parseFloat(this.record.station.longitude);
                
                console.log('Creating map with coordinates:', lat, lng);

                // Create new map instance
                this.mapInstance = L.map('map', {
                    center: [lat, lng],
                    zoom: 10,
                    scrollWheelZoom: false // Disable scroll zoom in modal
                });

                // Add tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(this.mapInstance);

                // Add marker
                this.mapMarker = L.marker([lat, lng])
                    .addTo(this.mapInstance)
                    .bindPopup(`
                        <div class="text-sm">
                            <strong>Station:</strong> ${this.record.station.name || 'Unknown'}<br>
                            <strong>Record ID:</strong> ${this.recordId}<br>
                            <strong>Coordinates:</strong> ${lat.toFixed(4)}, ${lng.toFixed(4)}
                        </div>
                    `);

                // Force map to recalculate its size
                setTimeout(() => {
                    this.mapInstance.invalidateSize();
                    
                    // Optional: Open popup automatically
                    this.mapMarker.openPopup();
                }, 100);

                console.log('Map initialized successfully');

            } catch (error) {
                console.error('Error creating map:', error);
            }
        },

        buildStationArray() {
            if (this.record?.station) {
                const excludedKeys = ['id', 'created_at', 'updated_at', 'latitude', 'longitude'];
                this.stationArray = Object.entries(this.record.station)
                    .filter(([key, val]) =>
                        !excludedKeys.includes(key) &&
                        val !== null &&
                        val !== '' &&
                        val !== 0
                    )
                    .map(([key, val]) => [
                        this.formatFieldName(key),
                        val
                    ]);
            } else {
                this.stationArray = [];
            }
        },

        buildAnalyticalMethodArray() {
            if (this.record?.analytical_method) {
                const excludedKeys = ['id', 'created_at', 'updated_at'];
                this.analyticalMethodArray = Object.entries(this.record.analytical_method)
                    .filter(([key, val]) =>
                        !excludedKeys.includes(key) &&
                        val !== null &&
                        val !== ''
                    )
                    .map(([key, val]) => [
                        this.formatFieldName(key),
                        val
                    ]);
            } else {
                this.analyticalMethodArray = [];
            }
        },

        buildDataSourceArray() {
            if (this.record?.data_source) {
                const excludedKeys = ['id', 'created_at', 'updated_at'];
                this.dataSourceArray = Object.entries(this.record.data_source)
                    .filter(([key, val]) =>
                        !excludedKeys.includes(key) &&
                        val !== null &&
                        val !== ''
                    )
                    .map(([key, val]) => [
                        this.formatFieldName(key),
                        val
                    ]);
            } else {
                this.dataSourceArray = [];
            }
        },

        buildMetaDataArray() {
            if (this.record?.matrix_data?.meta_data) {
                const excludedKeys = ['id', 'created_at', 'updated_at'];
                
                this.metaDataArray = Object.entries(this.record.matrix_data.meta_data)
                    .filter(([key, val]) =>
                        !excludedKeys.includes(key) &&
                        val !== null &&
                        val !== ''
                    )
                    .map(([key, val]) => [
                        this.formatFieldName(key),
                        val
                    ]);
            } else {
                this.metaDataArray = [];
            }
        },

        formatFieldName(fieldName) {
            // Convert snake_case to Title Case
            return fieldName
                .split('_')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        },

        hasValidCoordinates() {
            if (!this.record?.station) {
                return false;
            }
            
            const lat = parseFloat(this.record.station.latitude);
            const lng = parseFloat(this.record.station.longitude);
            
            // Check if coordinates exist, are valid numbers, and not both zero
            return !isNaN(lat) && !isNaN(lng) && 
                   lat >= -90 && lat <= 90 && 
                   lng >= -180 && lng <= 180 &&
                   (lat !== 0 || lng !== 0);
        },

        closeModal() {
            console.log('Closing modal');
            
            // Clean up map instance
            if (this.mapInstance) {
                console.log('Removing map instance');
                try {
                    this.mapInstance.remove();
                } catch (error) {
                    console.error('Error removing map:', error);
                }
                this.mapInstance = null;
                this.mapMarker = null;
            }

            // Reset all data
            this.showModal = false;
            this.record = null;
            this.recordId = null;
            this.stationArray = [];
            this.analyticalMethodArray = [];
            this.dataSourceArray = [];
            this.metaDataArray = [];
        },

        // Helper method to format coordinates for display
        formatCoordinate(value, type) {
            const val = parseFloat(value);
            if (isNaN(val)) return 'N/A';
            
            const absVal = Math.abs(val);
            const degrees = Math.floor(absVal);
            const minutes = Math.floor((absVal - degrees) * 60);
            const seconds = ((absVal - degrees) * 60 - minutes) * 60;
            
            let direction = '';
            if (type === 'latitude') {
                direction = val >= 0 ? 'N' : 'S';
            } else {
                direction = val >= 0 ? 'E' : 'W';
            }
            
            return `${degrees}°${minutes}'${seconds.toFixed(2)}"${direction} (${val.toFixed(6)})`;
        }
    };
}

// Make it available globally for Alpine
window.empodatModal = empodatModal;