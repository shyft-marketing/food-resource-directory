<div id="frd-container" class="frd-directory">
    
    <!-- View Toggle -->
    <div class="frd-view-toggle">
        <button class="frd-toggle-btn active" data-view="map">
            Map View
            <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/icons/Map Icon.svg'; ?>" alt="Map" />
        </button>
        <button class="frd-toggle-btn" data-view="list">
            List View
            <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/icons/List Icon.svg'; ?>" alt="List" />
        </button>
    </div>

    <!-- Location Search Bar -->
    <div class="frd-search-container">
        <div class="frd-search-wrapper">
            <div class="frd-search-input-group">
                <input type="text" id="frd-location-search" placeholder="Enter your address or ZIP code to find nearby locations" />
                <button class="frd-search-btn" id="frd-search-btn" aria-label="Search">
                    <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/icons/Search Icon.svg'; ?>" alt="Search" />
                </button>
            </div>
        </div>
        <button class="frd-filters-toggle-btn" id="frd-filters-toggle-btn">
            Filters
            <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/icons/Filter Icon.svg'; ?>" alt="Filters" />
        </button>
    </div>

    <!-- Filters -->
    <div class="frd-filters">
        <div class="frd-filters-content">

            <!-- Distance Filter (shown after location search) -->
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

                <!-- Languages Filter -->
                <div class="frd-filter-group">
                    <label for="frd-languages">Languages</label>
                    <select id="frd-languages" name="languages[]" multiple>
                        <!-- Options will be dynamically populated by JavaScript -->
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
                <option value="distance" disabled>Distance</option>
                <option value="name" selected>Name (A-Z)</option>
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
