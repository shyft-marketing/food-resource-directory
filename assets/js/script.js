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

                // Get the actual rendered height using getBoundingClientRect
                const selectionRect = $selection[0].getBoundingClientRect();
                const selectionHeight = selectionRect.height;

                console.log('FRD Dropdown: Selection height (getBoundingClientRect):', selectionHeight);
                console.log('FRD Dropdown: Selection outerHeight:', $selection.outerHeight());

                // Get current dropdown position
                const currentTop = parseFloat($dropdown.css('top')) || 0;
                console.log('FRD Dropdown: Current top CSS:', currentTop);

                // Calculate new position - add the full selection height plus spacing
                const newTop = selectionRect.bottom - selectionRect.top + 8;

                console.log('FRD Dropdown: Setting top to:', newTop);

                $dropdown.css({
                    'top': newTop + 'px',
                    'position': 'absolute'
                });
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

        // Open filters by default on desktop
        if ($(window).width() > 768) {
            $('.frd-filters').addClass('expanded');
            $('.frd-filters-toggle-text').text('Hide Filters');
        }

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

        // Filters toggle
        $('.frd-filters-toggle').on('click', function() {
            $('.frd-filters').toggleClass('expanded');
            const isExpanded = $('.frd-filters').hasClass('expanded');
            $('.frd-filters-toggle-text').text(isExpanded ? 'Hide Filters' : 'Show Filters');
        });

        // Location search
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
                    
                    // Reload locations with distance calculation
                    loadLocations();
                    
                    // Enable sort by distance
                    $('#frd-sort option[value="distance"]').prop('disabled', false);
                    
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
        el.style.backgroundColor = '#3b82f6';
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

                    // Update list
                    updateList();
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
                color: '#dc2626' // Red color for food resource markers
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
            html += '<p style="margin: 0 0 8px 0; font-weight: 600; color: #2563eb;">' + location.distance + ' miles away</p>';
        }

        html += '<p style="margin: 0 0 8px 0; font-size: 14px; color: #64748b;">' + location.full_address + '</p>';

        if (location.services && location.services.length > 0) {
            html += '<div style="margin-bottom: 8px;">';
            location.services.forEach(function(service) {
                html += '<span style="display: inline-block; padding: 2px 8px; background: #f1f5f9; border-radius: 4px; font-size: 12px; margin-right: 4px; margin-bottom: 4px;">' + service + '</span>';
            });
            html += '</div>';
        }

        // Add "More Info" button
        html += '<button class="frd-popup-more-info" data-location-id="' + location.id + '" style="width: 100%; margin-top: 8px; padding: 8px 12px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s;">More Info</button>';
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

        // Hours (abbreviated)
        if (location.hours_text) {
            const hoursLines = location.hours_text.split('<br>');
            const previewHours = hoursLines.slice(0, 2).join('<br>');
            const $hours = $('<div class="frd-location-hours">' + previewHours);
            if (hoursLines.length > 2) {
                $hours.append('<br><em>+ more...</em>');
            }
            $hours.append('</div>');
            $card.append($hours);
        }

        // Footer with links
        const $footer = $('<div class="frd-location-footer"></div>');

        if (location.phone) {
            $footer.append('<a href="tel:' + location.phone_link + '" class="frd-location-link">📞 Call</a>');
        }
        
        if (location.website) {
            $footer.append('<a href="' + location.website + '" target="_blank" class="frd-location-link">🌐 Website</a>');
        }
        
        const directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(location.full_address);
        $footer.append('<a href="' + directionsUrl + '" target="_blank" class="frd-location-link">🗺️ Directions</a>');
        
        $card.append($footer);

        // Click to show details
        $card.on('click', function(e) {
            // Don't trigger if clicking a link
            if (!$(e.target).is('a')) {
                showLocationDetails(location);
            }
        });

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
        let html = '<span class="frd-modal-title">' + location.title + '</span>';

        // Distance
        if (location.distance !== null) {
            html += '<div class="frd-modal-section">';
            html += '<p style="font-size: 18px; font-weight: 600; color: #2563eb; margin-bottom: 12px;">' + location.distance + ' miles away</p>';
            html += '</div>';
        }

        // Address
        html += '<div class="frd-modal-section">';
        html += '<span class="frd-modal-section-title">Address</span>';
        html += '<p>' + location.full_address + '</p>';
        html += '</div>';

        // Contact Info
        if (location.phone || location.website) {
            html += '<div class="frd-modal-section">';
            html += '<span class="frd-modal-section-title">Contact</span>';
            if (location.phone) {
                html += '<p><strong>Phone:</strong> <a href="tel:' + location.phone_link + '">' + location.phone + '</a></p>';
            }
            if (location.website) {
                html += '<p><strong>Website:</strong> <a href="' + location.website + '" target="_blank">' + location.website + '</a></p>';
            }
            html += '</div>';
        }

        // Services
        if (location.services && location.services.length > 0) {
            html += '<div class="frd-modal-section">';
            html += '<span class="frd-modal-section-title">Services</span>';
            html += '<p>' + location.services.join(', ') + '</p>';
            html += '</div>';
        }

        // Hours
        html += '<div class="frd-modal-section">';
        html += '<span class="frd-modal-section-title">Hours</span>';

        // Check if there's a special hours note (Appointment only, Hours unknown, etc.)
        // Skip if it's "Regular hours" (the default value)
        if (location.hours_other_hours && location.hours_other_hours !== 'Regular hours') {
            html += '<p>' + location.hours_other_hours + '</p>';
        } else if (location.hours) {
            // Display normal hours table
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
                html += '<div class="frd-modal-hour-row">';
                html += '<span class="frd-modal-hour-day">' + dayLabels[day] + '</span>';
                if (dayData && dayData.open) {
                    html += '<span class="frd-modal-hour-time">' + dayData.open_time + ' - ' + dayData.close_time + '</span>';
                } else {
                    html += '<span class="frd-modal-hour-time">Closed</span>';
                }
                html += '</div>';
            });

            html += '</div>';
        } else {
            html += '<p>Hours not available</p>';
        }

        html += '</div>';

        // Languages
        if (location.languages && location.languages.length > 0) {
            html += '<div class="frd-modal-section">';
            html += '<span class="frd-modal-section-title">Languages Spoken</span>';
            html += '<p>' + location.languages.join(', ') + '</p>';
            html += '</div>';
        }

        // Eligibility
        if (location.eligibility) {
            html += '<div class="frd-modal-section">';
            html += '<span class="frd-modal-section-title">Eligibility Requirements</span>';
            html += '<p>' + nl2br(location.eligibility) + '</p>';
            html += '</div>';
        }

        // Notes
        if (location.notes) {
            html += '<div class="frd-modal-section">';
            html += '<span class="frd-modal-section-title">Additional Notes</span>';
            html += '<p>' + nl2br(location.notes) + '</p>';
            html += '</div>';
        }

        // Action buttons
        html += '<div class="frd-modal-actions">';

        if (location.phone) {
            html += '<a href="tel:' + location.phone_link + '" class="frd-modal-btn">📞 Call</a>';
        }
        
        if (location.website) {
            html += '<a href="' + location.website + '" target="_blank" class="frd-modal-btn">🌐 Website</a>';
        }
        
        const directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(location.full_address);
        html += '<a href="' + directionsUrl + '" target="_blank" class="frd-modal-btn">🗺️ Directions</a>';
        
        html += '</div>';

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
