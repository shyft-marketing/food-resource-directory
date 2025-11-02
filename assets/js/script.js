(function($) {
    'use strict';

    let map;
    let markers = [];
    let allLocations = [];
    let filteredLocations = [];
    let userLocation = null;
    let currentView = 'map';

    // Initialize on document ready
    $(document).ready(function() {
        console.log('FRD: Document ready, initializing...');
        console.log('FRD: Mapbox token:', frdData.mapboxToken);
        console.log('FRD: AJAX URL:', frdData.ajaxUrl);
        initializeDirectory();
    });

    function initializeDirectory() {
        console.log('FRD: Starting initialization...');

        // Initialize Select2 on multi-select dropdowns
        $('#frd-services, #frd-days').select2({
            placeholder: function() {
                return $(this).attr('id') === 'frd-services' ? 'All Services' : 'Any Day';
            },
            allowClear: true,
            closeOnSelect: false,
            width: '100%'
        });

        // Force dropdown to have proper spacing when it opens
        $('#frd-services, #frd-days').on('select2:open', function() {
            const $select = $(this);

            // Use a longer delay to ensure layout has settled
            setTimeout(function() {
                const $container = $select.data('select2').$container;
                const $selection = $container.find('.select2-selection--multiple');
                const $dropdown = $('.select2-dropdown');

                // Get positions to calculate the gap
                const selectionRect = $selection[0].getBoundingClientRect();
                const dropdownRect = $dropdown[0].getBoundingClientRect();

                console.log('FRD Dropdown: Selection bottom:', selectionRect.bottom);
                console.log('FRD Dropdown: Dropdown top:', dropdownRect.top);

                // Calculate the gap between selection bottom and dropdown top
                const gap = dropdownRect.top - selectionRect.bottom;
                console.log('FRD Dropdown: Current gap:', gap);

                // If there's a gap or overlap, adjust the dropdown position
                if (gap !== 0) {
                    const currentTop = parseFloat($dropdown.css('top')) || 0;
                    const adjustment = -gap; // Negative gap means overlap, positive means too much space
                    const newTop = currentTop + adjustment;

                    console.log('FRD Dropdown: Adjusting top from', currentTop, 'to', newTop, '(adjustment:', adjustment, ')');

                    $dropdown.css('top', newTop + 'px');
                }
            }, 50);
        });

        // Fix clear button to properly clear all selections
        console.log('FRD Clear: Setting up clear button handler');

        $('#frd-services, #frd-days').on('select2:unselect', function(e) {
            const $select = $(this);

            setTimeout(function() {
                const currentValues = $select.val() || [];
                console.log('FRD Clear: After unselect, remaining:', currentValues.length);

                // If exactly 1 item remains, clear it too (this is the clear-all bug)
                if (currentValues.length === 1) {
                    console.log('FRD Clear: Clearing last remaining item');
                    $select.val([]).trigger('change');
                }
            }, 10);
        });

        // Initialize map
        try {
            initializeMap();
            console.log('FRD: Map initialized successfully');
        } catch (error) {
            console.error('FRD: Error initializing map:', error);
        }

        // Load initial locations
        loadLocations();

        // Bind event handlers
        bindEvents();

        // Filters are hidden by default now with the new layout

        console.log('FRD: Initialization complete');
    }

    function initializeMap() {
        mapboxgl.accessToken = frdData.mapboxToken;
        
        map = new mapboxgl.Map({
            container: 'frd-map',
            style: 'mapbox://styles/mapbox/streets-v12',
            center: frdData.defaultCenter,
            zoom: frdData.defaultZoom
        });

        // Add navigation controls
        map.addControl(new mapboxgl.NavigationControl(), 'top-right');

        // Add geocoder for location search (this will be integrated with our custom search)
        const geocoder = new MapboxGeocoder({
            accessToken: mapboxgl.accessToken,
            mapboxgl: mapboxgl,
            countries: 'us',
            proximity: {
                longitude: frdData.defaultCenter[0],
                latitude: frdData.defaultCenter[1]
            },
            placeholder: 'Search location...',
            marker: false
        });

        // Store geocoder reference but don't add to map (we'll use our custom search)
        window.frdGeocoder = geocoder;
    }

    function bindEvents() {
        // View toggle
        $('.frd-toggle-btn').on('click', function() {
            const view = $(this).data('view');
            switchView(view);
        });

        // Filters toggle (new button)
        $('#frd-filters-toggle-btn').on('click', function() {
            $('.frd-filters').toggleClass('expanded');
            $(this).toggleClass('active');
        });

        // Search button click
        $('#frd-search-btn').on('click', function() {
            searchLocation();
        });

        // Location search (Enter key)
        $('#frd-location-search').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                searchLocation();
            }
        });

        // Distance slider
        $('#frd-distance').on('input', function() {
            const value = $(this).val();
            $('.frd-distance-value').text(value + ' miles');
            if (userLocation) {
                applyFilters();
            }
        });

        // Apply filters button
        $('#frd-apply-filters').on('click', function() {
            const searchValue = $('#frd-location-search').val().trim();
            if (searchValue && !userLocation) {
                searchLocation();
            } else {
                applyFilters();
            }
        });

        // Reset filters button
        $('#frd-reset-filters').on('click', function() {
            resetFilters();
        });

        // Sort dropdown
        $('#frd-sort').on('change', function() {
            sortLocations($(this).val());
        });

        // Modal close
        $('.frd-modal-overlay, .frd-modal-close').on('click', function() {
            closeModal();
        });

        // Prevent modal content clicks from closing modal
        $('.frd-modal-content').on('click', function(e) {
            e.stopPropagation();
        });

        // Handle "More Info" button clicks in map popups
        $(document).on('click', '.frd-popup-more-info', function(e) {
            e.preventDefault();
            const locationId = parseInt($(this).data('location-id'));

            // Find the location in allLocations array
            const location = allLocations.find(function(loc) {
                return loc.id === locationId;
            });

            if (location) {
                showLocationDetails(location);
            }
        });
    }

    function switchView(view) {
        currentView = view;
        
        // Update toggle buttons
        $('.frd-toggle-btn').removeClass('active');
        $('.frd-toggle-btn[data-view="' + view + '"]').addClass('active');
        
        // Update views
        $('.frd-view').removeClass('active');
        $('.frd-' + view + '-view').addClass('active');
        
        // If switching to map, resize it
        if (view === 'map' && map) {
            setTimeout(function() {
                map.resize();
            }, 100);
        }
        
        // Update sort dropdown visibility
        if (view === 'list') {
            $('.frd-list-controls').show();
        }
    }

    function searchLocation() {
        const searchValue = $('#frd-location-search').val().trim();
        
        if (!searchValue) {
            return;
        }

        // Show loading state
        $('#frd-location-search').prop('disabled', true);

        $.ajax({
            url: frdData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'frd_geocode',
                nonce: frdData.nonce,
                address: searchValue
            },
            success: function(response) {
                if (response.success) {
                    userLocation = response.data;
                    
                    // Show distance filter
                    $('#frd-distance-filter').slideDown();
                    
                    // Add marker to map
                    addUserMarker();
                    
                    // Enable sort by distance and switch to it
                    $('#frd-sort option[value="distance"]').prop('disabled', false);
                    $('#frd-sort').val('distance');
                    
                    // Reload locations with distance calculation
                    loadLocations();
                    
                } else {
                    alert('Could not find that location. Please try a different address or ZIP code.');
                }
            },
            error: function() {
                alert('Error searching for location. Please try again.');
            },
            complete: function() {
                $('#frd-location-search').prop('disabled', false);
            }
        });
    }

    function addUserMarker() {
        // Remove existing user marker if any
        if (window.frdUserMarker) {
            window.frdUserMarker.remove();
        }

        // Create custom marker element
        const el = document.createElement('div');
        el.className = 'user-location-marker';
        el.style.width = '30px';
        el.style.height = '30px';
        el.style.borderRadius = '50%';
        el.style.backgroundColor = '#3A5780';
        el.style.border = '3px solid white';
        el.style.boxShadow = '0 2px 4px rgba(0,0,0,0.3)';

        // Add marker to map
        window.frdUserMarker = new mapboxgl.Marker({
            element: el,
            anchor: 'center'
        })
        .setLngLat([userLocation.lng, userLocation.lat])
        .addTo(map);

        // Fly to user location
        map.flyTo({
            center: [userLocation.lng, userLocation.lat],
            zoom: 11,
            duration: 1500
        });
    }

    function loadLocations() {
        console.log('FRD: Loading locations...');
        const filters = getFilters();
        console.log('FRD: Current filters:', filters);

        $.ajax({
            url: frdData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'frd_get_locations',
                nonce: frdData.nonce,
                filters: filters,
                user_location: userLocation
            },
            success: function(response) {
                console.log('FRD: AJAX response received:', response);
                if (response.success) {
                    allLocations = response.data;
                    filteredLocations = response.data;
                    console.log('FRD: Loaded ' + allLocations.length + ' locations');

                    // Debug first location's phone data
                    if (allLocations.length > 0) {
                        console.log('FRD: First location phone data:', {
                            phone: allLocations[0].phone,
                            phone_link: allLocations[0].phone_link
                        });

                        // Make accessible for debugging
                        window.frdDebugLocation = allLocations[0];
                        console.log('FRD: Access location data via: window.frdDebugLocation');
                    }

                    // Update results count
                    updateResultsCount();

                    // Update map markers
                    updateMapMarkers();

                    // Sort and update list (apply default sorting)
                    const currentSort = $('#frd-sort').val();
                    sortLocations(currentSort);
                } else {
                    console.error('FRD: Error loading locations - success=false', response);
                    alert('Error loading locations. Check console for details.');
                }
            },
            error: function(xhr, status, error) {
                console.error('FRD: AJAX error loading locations');
                console.error('FRD: Status:', status);
                console.error('FRD: Error:', error);
                console.error('FRD: Response:', xhr.responseText);
                alert('AJAX error loading locations. Check console for details.');
            }
        });
    }

    function applyFilters() {
        const filters = getFilters();
        const maxDistance = parseFloat($('#frd-distance').val());

        // Filter locations
        filteredLocations = allLocations.filter(function(location) {
            // Filter by distance if user location is set
            if (userLocation && location.distance !== null) {
                if (location.distance > maxDistance) {
                    return false;
                }
            }

            // Filter by services
            if (filters.services.length > 0) {
                const hasService = filters.services.some(function(service) {
                    return location.services && location.services.includes(service);
                });
                if (!hasService) {
                    return false;
                }
            }

            // Filter by county
            if (filters.county) {
                if (location.county !== filters.county) {
                    return false;
                }
            }

            // Filter by days
            if (filters.days.length > 0) {
                const openOnDay = filters.days.some(function(day) {
                    return location.hours && location.hours[day] && location.hours[day].open;
                });
                if (!openOnDay) {
                    return false;
                }
            }

            return true;
        });

        // Update display
        updateResultsCount();
        updateMapMarkers();
        updateList();
    }

    function getFilters() {
        // Get selected values from Select2 dropdowns
        const services = $('#frd-services').val() || [];
        const days = $('#frd-days').val() || [];
        const county = $('#frd-county').val();

        return {
            services: services,
            days: days,
            county: county
        };
    }

    function resetFilters() {
        // Clear Select2 dropdowns
        $('#frd-services').val(null).trigger('change');
        $('#frd-days').val(null).trigger('change');

        // Clear county select
        $('#frd-county').val('');

        // Clear location search
        $('#frd-location-search').val('');
        userLocation = null;
        
        // Remove user marker
        if (window.frdUserMarker) {
            window.frdUserMarker.remove();
        }
        
        // Hide distance filter
        $('#frd-distance-filter').slideUp();
        $('#frd-distance').val(25);
        $('.frd-distance-value').text('25 miles');
        
        // Disable distance sort
        $('#frd-sort option[value="distance"]').prop('disabled', true);
        if ($('#frd-sort').val() === 'distance') {
            $('#frd-sort').val('name');
        }
        
        // Reset to all locations
        filteredLocations = allLocations;
        
        // Update display
        updateResultsCount();
        updateMapMarkers();
        updateList();
        
        // Reset map view
        map.flyTo({
            center: frdData.defaultCenter,
            zoom: frdData.defaultZoom,
            duration: 1500
        });
    }

    function updateResultsCount() {
        $('#frd-count').text(filteredLocations.length);
    }

    function updateMapMarkers() {
        // Clear existing markers
        markers.forEach(function(marker) {
            marker.remove();
        });
        markers = [];

        // Add new markers
        filteredLocations.forEach(function(location) {
            if (!location.latitude || !location.longitude) {
                return;
            }

            // Create marker
            const marker = new mapboxgl.Marker({
                color: '#ff6f61' // Coral color for food resource markers
            })
            .setLngLat([location.longitude, location.latitude])
            .addTo(map);

            // Create popup content
            const popupContent = createPopupContent(location);

            // Add popup
            const popup = new mapboxgl.Popup({
                offset: 25,
                maxWidth: '350px'
            }).setHTML(popupContent);

            marker.setPopup(popup);

            markers.push(marker);
        });

        // Fit map to markers if there are any
        if (filteredLocations.length > 0 && !userLocation) {
            const bounds = new mapboxgl.LngLatBounds();
            
            filteredLocations.forEach(function(location) {
                if (location.latitude && location.longitude) {
                    bounds.extend([location.longitude, location.latitude]);
                }
            });

            map.fitBounds(bounds, {
                padding: 50,
                maxZoom: 13
            });
        }
    }

    function createPopupContent(location) {
        let html = '<div class="frd-popup">';
        html += '<span class="frd-popup-title" style="display: block; margin: 0 0 10px 0; font-size: 16px; font-weight: 600;">' + location.title + '</span>';

        if (location.distance !== null) {
            html += '<p style="margin: 0 0 8px 0; font-weight: 600; color: #3A5780;">' + location.distance + ' miles away</p>';
        }

        // Services above address
        if (location.services && location.services.length > 0) {
            html += '<div style="margin-bottom: 8px;">';
            location.services.forEach(function(service) {
                html += '<span style="display: inline-block; padding: 2px 8px; background: #F4F1E9; border: 1px solid #dcd9d2; border-radius: 4px; font-size: 12px; margin-right: 4px; margin-bottom: 4px;">' + service + '</span>';
            });
            html += '</div>';
        }

        // Address
        html += '<p style="margin: 0 0 12px 0; font-size: 14px; color: #7A7A7A;">' + location.full_address + '</p>';

        // Icon buttons
        html += '<div class="frd-popup-actions">';
        
        if (location.phone) {
            html += '<a href="tel:' + location.phone_link + '" class="frd-popup-icon-btn" title="Call">';
            html += '<img src="' + frdData.pluginUrl + '/assets/icons/Phone Icon.svg" alt="Call">';
            html += '</a>';
        }
        
        if (location.website) {
            html += '<a href="' + location.website + '" target="_blank" class="frd-popup-icon-btn" title="Website">';
            html += '<img src="' + frdData.pluginUrl + '/assets/icons/Link Icon.svg" alt="Website">';
            html += '</a>';
        }
        
        const directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(location.full_address);
        html += '<a href="' + directionsUrl + '" target="_blank" class="frd-popup-icon-btn" title="Directions">';
        html += '<img src="' + frdData.pluginUrl + '/assets/icons/Directions Icon.svg" alt="Directions">';
        html += '</a>';
        
        html += '</div>';

        // Add "More Info" button
        html += '<button class="frd-popup-more-info" data-location-id="' + location.id + '" style="width: 100%; margin-top: 12px; padding: 8px 12px; background: #3A5780; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;">';
        html += '<span>More Info</span>';
        html += '<img src="' + frdData.pluginUrl + '/assets/icons/Read More Icon.svg" alt="" style="width: 1em; height: 1em; filter: brightness(0) invert(1);">';
        html += '</button>';
        html += '</div>';

        return html;
    }

    function updateList() {
        const $container = $('#frd-list-items');
        $('#frd-list-loading').hide();
        
        if (filteredLocations.length === 0) {
            $container.html('<div class="frd-loading">No locations found matching your filters.</div>');
            return;
        }

        $container.empty();

        filteredLocations.forEach(function(location) {
            const $card = createLocationCard(location);
            $container.append($card);
        });
    }

    function createLocationCard(location) {
        const $card = $('<div class="frd-location-card"></div>');

        // Header
        const $header = $('<div class="frd-location-header"></div>');
        
        const $titleContainer = $('<div></div>');
        const $title = $('<span class="frd-location-title">' + location.title + '</span>');
        $titleContainer.append($title);
        $header.append($titleContainer);

        if (location.distance !== null) {
            const $distance = $('<div class="frd-location-distance">' + location.distance + ' miles</div>');
            $header.append($distance);
        }

        $card.append($header);

        // Address
        const $address = $('<div class="frd-location-address">' + location.full_address + '</div>');
        $card.append($address);

        // Services
        if (location.services && location.services.length > 0) {
            const $services = $('<div class="frd-location-services"></div>');
            location.services.forEach(function(service) {
                $services.append('<span class="frd-service-tag">' + service + '</span>');
            });
            $card.append($services);
        }

        // Footer with icon buttons
        const $footer = $('<div class="frd-location-footer"></div>');

        // Icon buttons container
        const $iconButtons = $('<div class="frd-list-icon-buttons"></div>');

        if (location.phone) {
            const $phoneBtn = $('<a href="tel:' + location.phone_link + '" class="frd-list-icon-btn" title="Call"></a>');
            $phoneBtn.append('<img src="' + frdData.pluginUrl + '/assets/icons/Phone Icon.svg" alt="Call">');
            $iconButtons.append($phoneBtn);
        }
        
        const directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(location.full_address);
        const $directionsBtn = $('<a href="' + directionsUrl + '" target="_blank" class="frd-list-icon-btn" title="Directions"></a>');
        $directionsBtn.append('<img src="' + frdData.pluginUrl + '/assets/icons/Directions Icon.svg" alt="Directions">');
        $iconButtons.append($directionsBtn);
        
        if (location.website) {
            const $websiteBtn = $('<a href="' + location.website + '" target="_blank" class="frd-list-icon-btn" title="Website"></a>');
            $websiteBtn.append('<img src="' + frdData.pluginUrl + '/assets/icons/Link Icon.svg" alt="Website">');
            $iconButtons.append($websiteBtn);
        }

        $footer.append($iconButtons);

        // More Info button
        const $moreInfoBtn = $('<button class="frd-list-more-info"></button>');
        $moreInfoBtn.append('<span>More Info</span>');
        $moreInfoBtn.append('<img src="' + frdData.pluginUrl + '/assets/icons/Read More Icon.svg" alt="">');
        $moreInfoBtn.on('click', function(e) {
            e.stopPropagation();
            showLocationDetails(location);
        });
        $footer.append($moreInfoBtn);
        
        $card.append($footer);

        return $card;
    }

    function sortLocations(sortBy) {
        switch(sortBy) {
            case 'distance':
                if (userLocation) {
                    filteredLocations.sort(function(a, b) {
                        return (a.distance || 999999) - (b.distance || 999999);
                    });
                }
                break;
            case 'name':
                filteredLocations.sort(function(a, b) {
                    return a.title.localeCompare(b.title);
                });
                break;
            case 'county':
                filteredLocations.sort(function(a, b) {
                    return (a.county || '').localeCompare(b.county || '');
                });
                break;
        }

        updateList();
    }

    function showLocationDetails(location) {
        let html = '';
        
        // Title
        html += '<div class="frd-modal-title">' + location.title + '</div>';
        
        // Service type below title
        if (location.services && location.services.length > 0) {
            html += '<div class="frd-modal-service">' + location.services[0] + '</div>';
        }

        // Contact info row with icons (horizontal)
        html += '<div class="frd-modal-contact">';
        
        // Address
        html += '<div class="frd-modal-contact-item">';
        html += '<img src="' + frdData.pluginUrl + '/assets/icons/Map Marker Icon.svg" alt="" class="frd-modal-icon">';
        html += '<span>' + location.full_address + '</span>';
        html += '</div>';
        
        // Phone
        if (location.phone) {
            html += '<div class="frd-modal-contact-item">';
            html += '<img src="' + frdData.pluginUrl + '/assets/icons/Phone Icon.svg" alt="" class="frd-modal-icon">';
            html += '<a href="tel:' + location.phone_link + '">' + location.phone + '</a>';
            html += '</div>';
        }
        
        // Website
        if (location.website) {
            html += '<div class="frd-modal-contact-item">';
            html += '<img src="' + frdData.pluginUrl + '/assets/icons/Link Icon.svg" alt="" class="frd-modal-icon">';
            html += '<a href="' + location.website + '" target="_blank">' + location.website.replace(/^https?:\/\/(www\.)?/, '') + '</a>';
            html += '</div>';
        }
        
        html += '</div>';

        // Languages Spoken
        if (location.languages && location.languages.length > 0) {
            html += '<div class="frd-modal-section">';
            html += '<strong style="display: inline;">Languages Spoken:</strong> ' + location.languages.join(', ');
            html += '</div>';
        }

        // Eligibility Requirements
        if (location.eligibility) {
            html += '<div class="frd-modal-section">';
            html += '<strong>Eligibility Requirements</strong>';
            html += '<p>' + nl2br(location.eligibility) + '</p>';
            html += '</div>';
        }

        // Additional Notes
        if (location.notes) {
            html += '<div class="frd-modal-section">';
            html += '<strong>Additional Notes</strong>';
            html += '<p>' + nl2br(location.notes) + '</p>';
            html += '</div>';
        }

        // Hours
        html += '<div class="frd-modal-section">';
        html += '<strong>Hours</strong>';
        
        // Check if there's a special hours note
        if (location.hours_other_hours && location.hours_other_hours !== 'Regular hours') {
            html += '<p>' + location.hours_other_hours + '</p>';
        } else if (location.hours) {
            html += '<div class="frd-modal-hours">';
            const dayLabels = {
                monday: 'Monday',
                tuesday: 'Tuesday',
                wednesday: 'Wednesday',
                thursday: 'Thursday',
                friday: 'Friday',
                saturday: 'Saturday',
                sunday: 'Sunday'
            };

            Object.keys(dayLabels).forEach(function(day) {
                const dayData = location.hours[day];
                html += '<div class="frd-modal-hours-row">';
                html += '<span class="frd-modal-hours-day">' + dayLabels[day] + '</span>';
                if (dayData && dayData.open) {
                    html += '<span class="frd-modal-hours-time">' + dayData.open_time + ' - ' + dayData.close_time + '</span>';
                } else {
                    html += '<span class="frd-modal-hours-time">Closed</span>';
                }
                html += '</div>';
            });
            html += '</div>';
        }
        html += '</div>';

        // Action buttons
        html += '<div class="frd-modal-buttons">';
        
        if (location.phone) {
            html += '<a href="tel:' + location.phone_link + '" class="frd-modal-btn frd-modal-btn-primary">';
            html += '<img src="' + frdData.pluginUrl + '/assets/icons/Phone Icon.svg" alt="">';
            html += 'CALL';
            html += '</a>';
        }
        
        if (location.website) {
            html += '<a href="' + location.website + '" target="_blank" class="frd-modal-btn frd-modal-btn-secondary">';
            html += '<img src="' + frdData.pluginUrl + '/assets/icons/Link Icon.svg" alt="">';
            html += 'VISIT WEBSITE';
            html += '</a>';
        }
        
        const directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(location.full_address);
        html += '<a href="' + directionsUrl + '" target="_blank" class="frd-modal-btn frd-modal-btn-secondary">';
        html += '<img src="' + frdData.pluginUrl + '/assets/icons/Directions Icon.svg" alt="">';
        html += 'DIRECTIONS';
        html += '</a>';
        
        html += '</div>';

        // Suggest edits link
        html += '<a href="https://macombdefenders.com/contact/" target="_blank" rel="noopener noreferrer" class="frd-modal-suggest-edits">';
        html += 'Suggest edits';
        html += '<img src="' + frdData.pluginUrl + '/assets/icons/Edit Icon.svg" alt="">';
        html += '</a>';

        $('#frd-modal-body').html(html);
        $('#frd-modal').addClass('active');
        $('body').css('overflow', 'hidden');
    }

    function closeModal() {
        $('#frd-modal').removeClass('active');
        $('body').css('overflow', '');
    }

    function nl2br(str) {
        return (str + '').replace(/(\r\n|\n\r|\r|\n)/g, '<br>');
    }

})(jQuery);
