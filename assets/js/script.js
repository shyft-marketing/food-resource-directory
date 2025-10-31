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
        const services = [];
        $('input[name="services[]"]:checked').each(function() {
            services.push($(this).val());
        });

        const days = [];
        $('input[name="days[]"]:checked').each(function() {
            days.push($(this).val());
        });

        const county = $('#frd-county').val();

        return {
            services: services,
            days: days,
            county: county
        };
    }

    function resetFilters() {
        // Clear checkboxes
        $('input[name="services[]"]').prop('checked', false);
        $('input[name="days[]"]').prop('checked', false);
        
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

            // Add click handler to show full details
            marker.getElement().addEventListener('click', function() {
                showLocationDetails(location);
            });

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
        html += '<h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 600;">' + location.title + '</h3>';
        
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
        
        html += '<p style="margin: 8px 0 0 0; font-size: 13px;"><a href="#" onclick="return false;" style="color: #2563eb; font-weight: 500;">Click marker for full details →</a></p>';
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
        const $title = $('<h3 class="frd-location-title">' + location.title + '</h3>');
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
            $footer.append('<a href="tel:' + location.phone + '" class="frd-location-link">📞 Call</a>');
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
        let html = '<h2 class="frd-modal-title">' + location.title + '</h2>';

        // Distance
        if (location.distance !== null) {
            html += '<div class="frd-modal-section">';
            html += '<p style="font-size: 18px; font-weight: 600; color: #2563eb; margin-bottom: 12px;">' + location.distance + ' miles away</p>';
            html += '</div>';
        }

        // Address
        html += '<div class="frd-modal-section">';
        html += '<h4>Address</h4>';
        html += '<p>' + location.full_address + '</p>';
        html += '</div>';

        // Contact Info
        if (location.phone || location.website) {
            html += '<div class="frd-modal-section">';
            html += '<h4>Contact</h4>';
            if (location.phone) {
                html += '<p><strong>Phone:</strong> <a href="tel:' + location.phone + '">' + location.phone + '</a></p>';
            }
            if (location.website) {
                html += '<p><strong>Website:</strong> <a href="' + location.website + '" target="_blank">' + location.website + '</a></p>';
            }
            html += '</div>';
        }

        // Services
        if (location.services && location.services.length > 0) {
            html += '<div class="frd-modal-section">';
            html += '<h4>Services</h4>';
            html += '<p>' + location.services.join(', ') + '</p>';
            html += '</div>';
        }

        // Hours
        if (location.hours) {
            html += '<div class="frd-modal-section">';
            html += '<h4>Hours</h4>';
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
            html += '</div>';
        }

        // Languages
        if (location.languages && location.languages.length > 0) {
            html += '<div class="frd-modal-section">';
            html += '<h4>Languages Spoken</h4>';
            html += '<p>' + location.languages.join(', ') + '</p>';
            html += '</div>';
        }

        // Eligibility
        if (location.eligibility) {
            html += '<div class="frd-modal-section">';
            html += '<h4>Eligibility Requirements</h4>';
            html += '<p>' + nl2br(location.eligibility) + '</p>';
            html += '</div>';
        }

        // Notes
        if (location.notes) {
            html += '<div class="frd-modal-section">';
            html += '<h4>Additional Notes</h4>';
            html += '<p>' + nl2br(location.notes) + '</p>';
            html += '</div>';
        }

        // Action buttons
        html += '<div class="frd-modal-actions">';
        
        if (location.phone) {
            html += '<a href="tel:' + location.phone + '" class="frd-modal-btn">📞 Call</a>';
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
