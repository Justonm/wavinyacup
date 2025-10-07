/**
 * Location Dropdown Handler
 * Handles dynamic loading and filtering of sub counties and wards
 */

class LocationDropdown {
    constructor() {
        this.subCounties = [];
        this.wards = [];
        this.init();
    }

    async init() {
        await this.loadLocationData();
        this.setupEventListeners();
    }

    async loadLocationData() {
        try {
            const response = await fetch('get_locations.php');
            const data = await response.json();
            
            if (data.success) {
                this.subCounties = data.sub_counties;
                this.wards = data.wards;
                this.populateSubCounties();
            } else {
                console.error('Failed to load location data:', data.error);
            }
        } catch (error) {
            console.error('Error fetching location data:', error);
        }
    }

    populateSubCounties() {
        const subCountySelect = document.getElementById('sub_county_id');
        if (!subCountySelect) return;

        // Clear existing options except the first one
        subCountySelect.innerHTML = '<option value="">Select Sub County</option>';

        // Add sub counties
        this.subCounties.forEach(subCounty => {
            const option = document.createElement('option');
            option.value = subCounty.id;
            option.textContent = subCounty.name;
            subCountySelect.appendChild(option);
        });
    }

    filterWards(subCountyId) {
        const wardSelect = document.getElementById('ward_id');
        if (!wardSelect) return;

        // Clear ward options
        wardSelect.innerHTML = '<option value="">Select Ward</option>';

        if (!subCountyId) return;

        // Filter and add wards for selected sub county
        const filteredWards = this.wards.filter(ward => 
            ward.sub_county_id == subCountyId
        );

        filteredWards.forEach(ward => {
            const option = document.createElement('option');
            option.value = ward.id;
            option.textContent = ward.name;
            option.setAttribute('data-sub-county', ward.sub_county_id);
            wardSelect.appendChild(option);
        });
    }

    setupEventListeners() {
        const subCountySelect = document.getElementById('sub_county_id');
        if (subCountySelect) {
            subCountySelect.addEventListener('change', (e) => {
                this.filterWards(e.target.value);
            });
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new LocationDropdown();
});

// Global function for backward compatibility
function filterWards() {
    const subCountySelect = document.getElementById('sub_county_id');
    const wardSelect = document.getElementById('ward_id');
    const selectedSubCounty = subCountySelect.value;
    
    // Clear ward selection
    wardSelect.innerHTML = '<option value="">Select Ward</option>';
    
    if (selectedSubCounty && window.locationDropdown) {
        window.locationDropdown.filterWards(selectedSubCounty);
    }
}
