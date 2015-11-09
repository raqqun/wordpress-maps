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

map.whenReady(function() {
    var mapZoom = map.getZoom();
    var mapCenter = map._initialCenter;
    map.on('popupclose', function(e) {
        map.setView(mapCenter, mapZoom);
    });

    var smpMap = L.mapbox.featureLayer().addTo(map);

    smpMap.setGeoJSON(geoJson);

    smpMap.eachLayer(function(layer) {
        layer.bindPopup(layer.feature.properties.description, {
            'closeButton': false,
            'keepInView' : true,
            'maxHeight'  : 400
        });
    });

});
