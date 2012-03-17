/**
* Google Map API
*
*/
var map = null;

var geocoder = null;

var marker = null;

var xml;

var address;

var pointInterval = 30;

var overlays  = new Array();

function initialize(address, width, height) {
    if (GBrowserIsCompatible()) {
        map = new GMap2(document.getElementById("map_canvas"),{ 
            size: new GSize(width, height)
        } );
        var mapTypeControl = new GMapTypeControl();
        var topRight = new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(10,10));
        map.addControl(mapTypeControl, topRight);
        // Add Map Controls
        map.addControl(new GSmallMapControl());    
        geocoder = new GClientGeocoder();
        showAddress(address, geocoder)
    }
}

function initialize2(lon, lat, width, height, address, markerIcon, showControls, showInfo, mapId, zoom){
    if (GBrowserIsCompatible()) {
        if(typeof mapId == 'undefined'){
            mapId = 'map_canvas';
        }
        var map = new GMap2(document.getElementById(mapId),{
            size: new GSize(width, height)
        } );
        if(typeof showControls == 'undefined' || showControls){
            var mapTypeControl = new GMapTypeControl();
            var topRight = new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(10,10));
            map.addControl(mapTypeControl, topRight);
        }
        // Add Map Controls
        map.addControl(new GSmallMapControl());
        var center = new GLatLng(lat, lon);
        if(typeof zoom == 'undefined')
            zoom = 10;
        map.setCenter(center, zoom);

        if(typeof markerIcon != 'undefined'){
            marker = new GMarker(center, {
                icon: markerIcon
            });
        }else{
            marker = new GMarker(center);
        }
        map.addOverlay(marker);
        if(typeof showInfo == 'undefined' || showInfo){
            marker.openInfoWindowHtml(address);
        }
    }
}

function showAddress(address, geocoder) {
    if (geocoder) {
        geocoder.getLatLng(
            address,
            function(point){
                if (point) {
                    map.setCenter(point, 16);
                    marker = new GMarker(point);
                    map.addOverlay(marker);
                    marker.openInfoWindowHtml(address);
                }
            }
       );
    }
}

function showDraggableMarkerLongLat(lon, lat, width, height, showInitially, msg, markerIcon){
    if (GBrowserIsCompatible()) {
        var marker = null;
        if(map == null){
            map = new GMap2(document.getElementById("map_canvas"),{
                size: new GSize(width, height)
            });
            var mapTypeControl = new GMapTypeControl();
            var topRight = new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(10,10));
            map.addControl(mapTypeControl, topRight);
            // Add Map Controls
            map.addControl(new GSmallMapControl());
        }
        var center = new GLatLng(lat, lon);      
        map.setCenter(center, 10);
        if(typeof markerIcon != 'undefined'){
            marker = new GMarker(center, {
                draggable: true,
                icon: markerIcon
            });
        }else{
            marker = new GMarker(center, {
                draggable: true
            });
        }
        overlays[0] = marker;
        GEvent.addListener(marker, "dragstart", function(center) {
            map.closeInfoWindow();
        });
        GEvent.addListener(marker, "dragend", function(center) {            
            var geocoder = new GClientGeocoder();
            geocoder.getLocations(center, function(response){
                place = response.Placemark[0];
                currentLocation = 'Your current location is : <br/>' + '<strong>' + place.address + '</strong>';
                if($('#hazard-location').length > 0){
                    $('#hazard-location').val(place.address);
                }
                getLongLat(center, marker, currentLocation);
            });
        });
        if(showInitially == true){ 
               getLongLat(center, marker, msg);
        }
        map.addOverlay(marker);
    }
}

function showDraggableMarkerAddress(address, width, height) {
    if (GBrowserIsCompatible()) {
        map = new GMap2(document.getElementById("map_canvas"),{
            size: new GSize(width, height)
        } );
        var mapTypeControl = new GMapTypeControl();
        var topRight = new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(10,10));
        map.addControl(mapTypeControl, topRight);
        // Add Map Controls
        map.addControl(new GSmallMapControl());
        geocoder = new GClientGeocoder();
        if (geocoder) {
            geocoder.getLatLng(
                address,
                function(point){
                    if (point) {
                        map.setCenter(point, 16);
                        var marker = new GMarker(point, {
                            draggable: true
                        });
                        GEvent.addListener(marker, "dragstart", function(point) {
                            map.closeInfoWindow();
                        });
                        GEvent.addListener(marker, "dragend", function(point) {
                            getLongLat(point, marker);
                        });
                        getLongLat(point, marker);
                        marker.openInfoWindowHtml(address);
                        map.addOverlay(marker);
                    }
                }
           );
        }
    }
}

function showMultipleMarkerLongLat(lon, lat, width, height, gLatLng, showInitially){
    if (GBrowserIsCompatible()) {
        var map = new GMap2(document.getElementById("map_canvas"),{
            size: new GSize(width, height)
        } );
        var mapTypeControl = new GMapTypeControl();
        var topRight = new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(10,10));
        map.addControl(mapTypeControl, topRight);
        // Add Map Controls
        map.addControl(new GSmallMapControl());
        var center = new GLatLng(lat, lon);
        for(var i = 0; i < gLatLng.length; i++ )
        {
            var marker = new GMarker( gLatLng[ i ] );
            map.addOverlay( marker );
        }
        // #2a -- calculate center
        var latlngbounds = new GLatLngBounds();
        for (i = 0; i < gLatLng.length; i++ )
        {
            latlngbounds.extend( gLatLng[ i ] );
        }
      // #2b -- set center using the calculated values
      map.setCenter( latlngbounds.getCenter(), map.getBoundsZoomLevel( latlngbounds ) );
      if(showInitially == true)
            getLongLat(center, marker);
        map.addOverlay(marker);
    }
}

function removeOldOverlays(){
    for(index=0; index<overlays.length; index++){
        if(typeof overlays[index] != 'undefined')
            map.removeOverlay(overlays[index]);
    }
}

function showMultipleMarkerAddress(latsLangs, width, height, zoom, markerIcon) {
    var marker;
    if (GBrowserIsCompatible()) {
        if(latsLangs.length){
            overlays = new Array();
            for(i = 0; i < latsLangs.length; i++ ){
                if(latsLangs[i].coords.length){
                    var center = new GLatLng(latsLangs[i].coords[0], latsLangs[i].coords[1]);
                    if(typeof markerIcon != 'undefined'){
                        marker = new GMarker(center, {
                                    icon: markerIcon,
                                    title: 'Click to see details'
                                });
                    }else{
                        marker = new GMarker(center);
                    }
                    overlays[i] = marker;
                    map.addOverlay(marker);
                    marker.bindInfoWindowHtml(latsLangs[i].address);
                }
            }
        }
    }
}

function showCenterLocation(lats, langs, width, height, zoom, radius) {
    if (GBrowserIsCompatible()) {
        map = new GMap2(document.getElementById("map_canvas"),{
            size: new GSize(width, height)
        } );
        var mapTypeControl = new GMapTypeControl();
        var topRight = new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(10,10));
        map.addControl(mapTypeControl, topRight);
        // Add Map Controls
        map.addControl(new GSmallMapControl());
        geocoder = new GClientGeocoder();
        if (geocoder) {
            var center = new GLatLng(lats, langs);
            var marker = new GMarker( center);
            map.setCenter(center, zoom);
            map.addOverlay(marker);
            searchPoints = getCirclePoints(center, radius);
        }
    }
}

function getCirclePoints(center,radius){ 
    var circlePoints = Array();
    var searchPoints = Array();
    with (Math) {
            var rLat = ((radius*0.621371192)/(3963.189)) * (180/PI); // miles
            var rLng = rLat/cos(center.lat() * (PI/180));
            for (var a = 0 ; a < 361 ; a++ ) {
                    var aRad = a*(PI/180);
                    var x = center.lng() + (rLng * cos(aRad));
                    var y = center.lat() + (rLat * sin(aRad));
                    var point = new GLatLng(parseFloat(y),parseFloat(x),true);                    
                    circlePoints.push(point);
                    if (a % pointInterval == 0) {
                            searchPoints.push(point);
                    }
            }
    }
    searchPolygon = new GPolygon(circlePoints, '#5caa0a', 2, 1, '#5caa0a', 0.2);
    map.addOverlay(searchPolygon);
    map.setCenter(searchPolygon.getBounds().getCenter(),map.getBoundsZoomLevel(searchPolygon.getBounds()));
    return searchPoints;
}

function getLongLat(point, marker, msg){    
    var matchll = /\(([-.\d]*), ([-.\d]*)/.exec( point );
    var message = '';
    if ( matchll ) {
        var lat = parseFloat( matchll[1] );
        var lon = parseFloat( matchll[2] );
        lat = lat.toFixed(6);
        lon = lon.toFixed(6);
        if(typeof msg != 'undefined')
              message = msg;
        else
              message = "Your current location is : <br/><br/><strong>Latitude</strong> : " + lat + "<br/> <strong>Longitude</strong> : " + lon;
    } else {
        message = "<b>Error extracting info from</b>:" + point + "";
    }    
    marker.openInfoWindowHtml(message);
    if(document.getElementById("frmLat"))
        document.getElementById("frmLat").value = lat;
    if(document.getElementById("frmLon"))
        document.getElementById("frmLon").value = lon;
}

function getLongLat1(point, map, msg){
    var matchll = /\(([-.\d]*), ([-.\d]*)/.exec( point );
    var lat = parseFloat( matchll[1] );
    var lon = parseFloat( matchll[2] );
    if(document.getElementById("frmLat"))
        document.getElementById("frmLat").value = lat;
    if(document.getElementById("frmLon"))
        document.getElementById("frmLon").value = lon;
}

// On page load, call this function
function load(xmlFile)
{
    // Create new map object
    map = new GMap2(document.getElementById("map_canvas"),{
        size: new GSize(210,250)
    } );
    // Add Map Controls
    map.addControl(new GSmallMapControl());
    // Create new geocoding object
    geocoder = new GClientGeocoder();
    // Download the data in data.xml and load it on the map.
    data = window.location.protocol + '//' + window.location.host + '/js/' + xmlFile;
    GDownloadUrl(data, function(data) {
        xml = GXml.parse(data);
        markers = xml.documentElement.getElementsByTagName("marker");
        for (var i = 0; i < markers.length; i++)
        {
            address = markers[i].getAttribute("address");
            link = markers[i].getAttribute("link");
            showMarkers(address, link, i, geocoder);
        }
    });
}

function showMarkers(center) {
    var marker = null;
    var geocoder = new GClientGeocoder();
    map.setCenter(center, 12);
    
    marker = new GMarker(center, {
        draggable: true
    });
    overlays[0] = marker;
    
    GEvent.addListener(marker, "dragstart", function(center) {
        map.closeInfoWindow();
    });
    GEvent.addListener(marker, "dragend", function(center) {
        
        geocoder.getLocations(center, function(response){
            place = response.Placemark[0];
            currentLocation = 'Worksite Location : <br/>' + '<strong>' + place.address + '</strong>';
            getLongLat(center, marker, currentLocation);
        });

    });

    geocoder.getLocations(center, function(response){
        place = response.Placemark[0];
        currentLocation = 'Worksite Location : <br/>' + '<strong>' + place.address + '</strong>';
        getLongLat(center, marker, currentLocation);
    });
    
    map.addOverlay(marker);
}





function argItems (theArgName) {

    sArgs = location.search.slice(1).split('&');

    r = '';

    for (var i = 0; i < sArgs.length; i++) {

        if (sArgs[i].slice(0,sArgs[i].indexOf('=')) == theArgName) {

            r = sArgs[i].slice(sArgs[i].indexOf('=')+1);

            break;

        }

    }

    return (r.length > 0 ? unescape(r).split(',') : '')

}



function getCoordForAddress(response){

    if (!response || response.Status.code != 200) {

        alert("Sorry, we were unable to geocode that address\n\n Sorry, dat adres bestaat blijkbaar niet!");

    } else {

        place = response.Placemark[0];

        setLat = place.Point.coordinates[1];

        setLon = place.Point.coordinates[0];

        setLat = setLat.toFixed(6);

        setLon = setLon.toFixed(6);

        document.getElementById("frmLat").value = setLat;

        document.getElementById("frmLon").value = setLon;

    }

    placeMarker(setLat, setLon)

}

function autoRotate() {

    if (map.isRotatable()) {

        setTimeout('map.changeHeading(90)', 3000);

        setTimeout('map.changeHeading(180)',6000);

        setTimeout('map.changeHeading(270)',9000);

        setTimeout('map.changeHeading(0)',12000);

    }

}

function placeMarker(setLat, setLon, zm){

    if(!zm){

        zm=14;

    }else

        zm = 4;

    if(document.getElementById("frmLat"))

        //document.getElementById("frmLat").value = setLat;

        if(document.getElementById("frmLon"))

            //document.getElementById("frmLon").value = setLon;

            var map = new GMap(document.getElementById("map"));

    map.centerAndZoom(new GPoint(setLon, setLat), zm);

    map.setUIToDefault();

    map.enableRotation();

    var point = new GPoint(setLon, setLat);

    var marker = new GMarker(point);

    map.addOverlay(marker);

    GEvent.addListener(map, 'click', function(overlay, point) {

        if (overlay) {

            map.removeOverlay(overlay);

        } else if (point) {

            map.recenterOrPanToLatLng(point);

            var marker = new GMarker(point);

            map.addOverlay(marker);

            var matchll = /\(([-.\d]*), ([-.\d]*)/.exec( point );

            if ( matchll ) {

                var lat = parseFloat( matchll[1] );

                var lon = parseFloat( matchll[2] );

                lat = lat.toFixed(6);

                lon = lon.toFixed(6);

                var message = "geotagged geo:lat=" + lat + " geo:lon=" + lon + " ";

                var messageRoboGEO = lat + ";" + lon + "";

            } else {

                var message = "<b>Error extracting info from</b>:" + point + "";

                var messagRoboGEO = message;

            }

            marker.openInfoWindowHtml(message);

            if(document.getElementById("frmLat"))

                document.getElementById("frmLat").value = lat;

            if(document.getElementById("frmLon"))

                document.getElementById("frmLon").value = lon;



        }

    });

}



function findAddress() {

    myAddress = document.getElementById("address").value;

    geocoder.getLocations(myAddress, getCoordForAddress);



}







// To get the latitude longitude on multiple clicks

function placeMarker_custom(setLat, setLon, zm){

    if(!zm){

        zm=14;

    }else

        zm = 4;

    if(document.getElementById("frmLat"))

        document.getElementById("frmLat").value = setLat;

    if(document.getElementById("frmLon"))

        document.getElementById("frmLon").value = setLon;

    var map = new GMap(document.getElementById("map"));

    map.centerAndZoom(new GPoint(setLon, setLat), zm);

    map.setUIToDefault();

    map.enableRotation();

    var point = new GPoint(setLon, setLat);

    var marker = new GMarker(point);

    map.addOverlay(marker);

    GEvent.addListener(map, 'click', function(overlay, point) {

        if (overlay) {

            map.removeOverlay(overlay);

        } else if (point) {

            map.recenterOrPanToLatLng(point);

            var marker = new GMarker(point);

            map.addOverlay(marker);

            var matchll = /\(([-.\d]*), ([-.\d]*)/.exec( point );

            if ( matchll ) {

                var lat = parseFloat( matchll[1] );

                var lon = parseFloat( matchll[2] );

                lat = lat.toFixed(6);

                lon = lon.toFixed(6);

                var message = "geotagged geo:lat=" + lat + " geo:lon=" + lon + " ";

                var messageRoboGEO = lat + ";" + lon + "";

            } else {

                var message = "<b>Error extracting info from</b>:" + point + "";

                var messagRoboGEO = message;

            }





            marker.openInfoWindowHtml(message);

            if(document.getElementById("frmLat")){

                if(document.getElementById("frmLat").value == ""){

                    document.getElementById("frmLat").value = lat;

                }else{

                    document.getElementById("frmLat").value = document.getElementById("frmLat").value+","+lat;

                }

            }

            if(document.getElementById("frmLon")){

                if(document.getElementById("frmLon").value == ""){

                    document.getElementById("frmLon").value = lon;

                }else{

                    document.getElementById("frmLon").value = document.getElementById("frmLon").value+","+lon;

                }

            }

        }

    });
}


function route(){
    var n = new GLatLng(30.744786,76.753664);
    var s = new GLatLng(30.738959,76.758127);
    var g1 = new GLatLngBounds(s, n);
 //    n = new GLatLng(30.740139,76.757441);
 //   s = new GLatLng(30.740139,76.757441);
   // var g2 = new GLatLngBounds(n, s);
 //   alert(g1);
  //  g1.extend(new GLatLng(30.737483,76.759157));
  //  alert(g1.containsLatLng(new GLatLng(30.741909,76.755981)));

  //  var bounds = new GLatLngBounds(new GLatLng(10, 50),new GLatLng(20, 70));
//   alert(bounds.containsLatLng(new GLatLng(15, 51)));  // outputs true
//   alert(bounds.containsLatLng(new GLatLng(29, 51)));  // outputs false

}


// Change Google map yellow man location
function changeMapLocation(address, defaultVal, www_root){
    if(address && address != defaultVal){
        var geoLocation = 'Your current location is : <br/>' + '<strong>'+address+'</strong>';
        removeOldOverlays();
        geocoder = new GClientGeocoder();
        if(geocoder){
            geocoder.getLatLng(address, function(center){
                var personIcon = getIcon(www_root);
                map.setCenter(center, 14);
                if(typeof personIcon != 'undefined'){
                    marker = new GMarker(center, {
                        draggable: true,
                        icon: personIcon
                    });
                }else{
                    marker = new GMarker(center, {
                        draggable: true
                    });
                }
                overlays[0] = marker;
                GEvent.addListener(marker, "dragstart", function(center) {
                    map.closeInfoWindow();
                });
                GEvent.addListener(marker, "dragend", function(center) {
                   var geocoder = new GClientGeocoder();
                    geocoder.getLocations(center, function(response){
                        place = response.Placemark[0];
                        currentLocation = 'Your current location is : <br/>' + '<strong>' + place.address + '</strong>';
                        getLongLat(center, marker, currentLocation, place.address);
                    });
                });

                //map.openInfoWindowHtml(center, geoLocation);
                getLongLat1(center, map, geoLocation);
                map.addOverlay(marker);
            });
        }
    }

    return false;
}
function getIcon(www_root){
    var baseIcon = new GIcon();
    baseIcon.iconSize=new GSize(32,32);
    baseIcon.iconAnchor=new GPoint(32,32);
    baseIcon.infoWindowAnchor=new GPoint(1,0);

    var basePath = www_root + '/resources';
    var personIcon = (new GIcon(baseIcon, basePath+"/images/marker.png", null, ""));
    return personIcon;
}