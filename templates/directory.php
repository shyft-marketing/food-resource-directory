<div id="frd-container" class="frd-directory">
    
    <!-- View Toggle -->
    <div class="frd-view-toggle">
        <button class="frd-toggle-btn active" data-view="map">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z"/>
                <path d="M8 2v16M16 6v16"/>
            </svg>
            Map View
        </button>
        <button class="frd-toggle-btn" data-view="list">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="8" y1="6" x2="21" y2="6"/>
                <line x1="8" y1="12" x2="21" y2="12"/>
                <line x1="8" y1="18" x2="21" y2="18"/>
                <line x1="3" y1="6" x2="3.01" y2="6"/>
                <line x1="3" y1="12" x2="3.01" y2="12"/>
                <line x1="3" y1="18" x2="3.01" y2="18"/>
            </svg>
            List View
        </button>
    </div>

    <!-- Filters -->
    <div class="frd-filters">
        <div class="frd-filters-header">
            <span class="frd-filters-title">Filter Resources</span>
            <button class="frd-filters-toggle">
                <span class="frd-filters-toggle-text">Show Filters</span>
                <svg class="frd-filters-toggle-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>
        </div>
        
        <div class="frd-filters-content">
            <!-- Location Search -->
            <div class="frd-filter-group">
                <label for="frd-location-search">Your Location</label>
                <input type="text" id="frd-location-search" placeholder="Enter address, city, or ZIP code" />
                <small>Enter a location to see distances and sort by proximity</small>
            </div>

            <!-- Distance Filter -->
            <div class="frd-filter-group" id="frd-distance-filter" style="display: none;">
                <label for="frd-distance">Maximum Distance</label>
                <div class="frd-distance-slider">
                    <input type="range" id="frd-distance" min="1" max="50" value="25" step="1" />
                    <span class="frd-distance-value">25 miles</span>
                </div>
            </div>

            <!-- Main Filters Row -->
            <div class="frd-filters-row">
                <!-- County Filter -->
                <div class="frd-filter-group">
                    <label for="frd-county">County</label>
                    <select id="frd-county" name="county">
                        <option value="">All Counties</option>
                        <option value="Macomb County">Macomb County</option>
                        <option value="Oakland County">Oakland County</option>
                        <option value="Wayne County">Wayne County</option>
                    </select>
                </div>

                <!-- Services Filter -->
                <div class="frd-filter-group">
                    <label for="frd-services">Services</label>
                    <select id="frd-services" name="services[]" multiple>
                        <option value="Food Pantry">Food Pantry</option>
                        <option value="Soup Kitchen">Soup Kitchen</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <!-- Days Filter -->
                <div class="frd-filter-group">
                    <label for="frd-days">Open On</label>
                    <select id="frd-days" name="days[]" multiple>
                        <option value="monday">Monday</option>
                        <option value="tuesday">Tuesday</option>
                        <option value="wednesday">Wednesday</option>
                        <option value="thursday">Thursday</option>
                        <option value="friday">Friday</option>
                        <option value="saturday">Saturday</option>
                        <option value="sunday">Sunday</option>
                    </select>
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="frd-filter-actions">
                <button class="frd-btn frd-btn-primary" id="frd-apply-filters">Apply Filters</button>
                <button class="frd-btn frd-btn-secondary" id="frd-reset-filters">Reset</button>
            </div>
        </div>
    </div>

    <!-- Results Count -->
    <div class="frd-results-info">
        <p class="frd-results-count">
            <span id="frd-count">0</span> locations found
        </p>
    </div>

    <!-- Map View -->
    <div class="frd-view frd-map-view active">
        <div id="frd-map"></div>
    </div>

    <!-- List View -->
    <div class="frd-view frd-list-view">
        <div class="frd-list-controls">
            <label for="frd-sort">Sort by:</label>
            <select id="frd-sort">
                <option value="distance">Distance</option>
                <option value="name">Name (A-Z)</option>
                <option value="county">County</option>
            </select>
        </div>
        
        <div class="frd-list-container">
            <div id="frd-list-loading" class="frd-loading">Loading locations...</div>
            <div id="frd-list-items"></div>
        </div>
    </div>

    <!-- Location Detail Modal -->
    <div id="frd-modal" class="frd-modal">
        <div class="frd-modal-overlay"></div>
        <div class="frd-modal-content">
            <button class="frd-modal-close">&times;</button>
            <div id="frd-modal-body"></div>
        </div>
    </div>

</div>
