function init_map_markers(wordpress_maps) {
    var geoJson = [];
    for (var i = 0; i < placesLatLng.length; i++) {
        geoJson[i] = {
            type: 'Feature',
            geometry: {
                type: 'Point',
                coordinates: [placesLatLng[i][1], placesLatLng[i][0]]
            },
            properties: {
                'marker-color': placesLatLng[i][2],
                description: placesContent[i],
            }
        };
    }

    wordpress_maps.whenReady(function() {
        var mapZoom = wordpress_maps.getZoom();
        var mapCenter = wordpress_maps._initialCenter;
        wordpress_maps.on('popupclose', function(e) {
            wordpress_maps.setView(mapCenter, mapZoom);
        });

        var smpMap = L.mapbox.featureLayer().addTo(wordpress_maps);

        smpMap.setGeoJSON(geoJson);

        smpMap.eachLayer(function(layer) {
            layer.bindPopup(layer.feature.properties.description, {
                'closeButton': false,
                'keepInView' : true,
                'maxHeight'  : wordpress_maps._size.y/1.5
            });
        });

    });
}