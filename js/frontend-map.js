Element.prototype.hasClass = function (className) {
    return new RegExp(' ' + className + ' ').test(' ' + this.className + ' ');
};

Element.prototype.addClass = function (className) {
    if (!this.hasClass(className)) {
        this.className += ' ' + className;
    }
    return this;
};

Element.prototype.removeClass = function (className) {
    var newClass = ' ' + this.className.replace(/[\t\r\n]/g, ' ') + ' ';
    if (this.hasClass(className)) {
        while (newClass.indexOf( ' ' + className + ' ') >= 0) {
            newClass = newClass.replace(' ' + className + ' ', ' ');
        }
        this.className = newClass.replace(/^\s+|\s+$/g, ' ');
    }
    return this;
};

function myclick(index) {

   google.maps.event.trigger(map_markers[index],"click");
}
var map_markers = [];
var params = params || {};
var maptour = (function(window, document, google, markers, mapStyle, params){
    
    console.log(params);
    var 
       _params = params || {},
        bounds,
        map,
        google = google,
        _mapStyle = mapStyle,
        _markers = markers,
        infowindow,
        _element = {
            mapContainer : document.getElementById( 'map' )
        };





        initMap = function(m){
            return new google.maps.Map(m, {
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		styles: _mapStyle
	    })
        },
        _position = function(lat, lng ) {
            return new google.maps.LatLng( lat, lng )
        },
        _pinSymbol = function(color) {
            return {
                path: 'M25 0c-8.284 0-15 6.656-15 14.866 0 8.211 15 35.135 15 35.135s15-26.924 15-35.135c0-8.21-6.716-14.866-15-14.866zm-.049 19.312c-2.557 0-4.629-2.055-4.629-4.588 0-2.535 2.072-4.589 4.629-4.589 2.559 0 4.631 2.054 4.631 4.589 0 2.533-2.072 4.588-4.631 4.588z',
                fillColor: color,
                fillOpacity: 1,
                strokeColor: '#fff',
                strokeWeight: 2,
                scale: .8,
            };
        },
        placeMarker = function(i){
            return new google.maps.Marker({
		    position: _position( _markers[i].lat, _markers[i].lng ),
		    map: map,
		    title: markers[i].title,
		    info: create_infowindow(i),
		    icon: _pinSymbol(markerGroups[markers[i].type].color),
		    type: markers[i].type
		});
        },
	openInfowindow = function(marker){
	    google.maps.event.addListener(marker, 'click', function(e) {
		if (infowindow) infowindow.close();
		    infowindow = createInfowindow(this);
		    infowindow.open(map, marker);
		});
	},
	createInfowindow = function(m){
	    return new google.maps.InfoWindow({
		content: m.info
	    });
	};


	create_infowindow = function(i){
	    var html = '<div class="infowindow">'+
            '<h2>'+markers[i].title+'</h2>'+infowindowContent(i)+infowindowNavigation(i)+
            '</div>';
	    return html;
	};

infowindowContent = function(i){
    var html = '<div class="content">';
    if(markers[i].image){
	    html += '<img src="'+markers[i].image+'" class="post-thumbnail" >';
	}
	    html += markers[i].description+'</div>';


return html;
};

    infowindowNavigation = function(i){
    var next = '<a class="btn-next" href="javascript:myclick('+(i+1)+');"><i class="fa fa-long-arrow-right"></i></a>';
    var previous = '<a class="btn-previous" href="javascript:myclick('+(i-1)+');"><i class="fa fa-long-arrow-left"></i></a>';
     if( i > 0 && i < markers.length ){
	links = previous + next;
    }
    if( i == markers.length-1 ){
	links = previous;
    }

    if( i == 0  ){
	links = next;
    }
var html = '<div class="infowindow-navigation"><span class="infowindow-navigation-header">Places navigation </span>'+links+'</div>';
    return html;


};


	var toggle = (function toggle_markers(b){
	    var toggle_buttons = document.querySelectorAll(b);
	    for ( var i=0; i < toggle_buttons.length; i++ ) {
		toggle_buttons[i].addEventListener( 'click', function(e){
		    e.preventDefault();
		    var type = this.getAttribute('data-type');
		    for ( var i=0; i < toggle_buttons.length; i++ ) {
			toggle_buttons[i].setAttribute('class', 'hidden');
			if(toggle_buttons[i].getAttribute('data-type') == type){
			    toggle_buttons[i].setAttribute('class', '');
			}
		    }
		    for (var i = 0; i < map_markers.length; i++) {
			var marker = map_markers[i];
			if(marker.type == type) {
			    marker.setVisible(true);
			    marker.setOptions({'opacity': 1, 'strokeWeight':1});
			}else{
			    marker.setOptions({'opacity': 0.3,'strokeWeight':.1});
			}
		    }
		}, false);
	    };
	})('#map-legend a');
	var resizeBig = (function resizeBig(btn_class){
	    var buttons = document.getElementsByClassName(btn_class);
	    
	    for ( var i = 0, length = buttons.length; i < length; i++ ) {
		buttons[i].addEventListener( 'click', function(e){
		    e.preventDefault();
		    document.getElementById('sidebar').style.display='none';
		    document.getElementById('header').style.display='none';
		    //var container = this.getAttribute('data-map-container');
		    var c = document.getElementById(this.getAttribute('data-map-container'));
		    c.addClass('full');
		    google.maps.event.trigger( map, "resize" );
		    map.fitBounds(bounds);
		    this.style.display = 'none';
		    //c.firstElementChild.style.display = 'block';
		    c.children[0].style.display = 'block';
		}, false);
	    }
	})('resize-big');
	var resizeSmall = (function resizeSmall(btn_class){
	    var buttons = document.getElementsByClassName(btn_class);
	    for ( var i = 0, length = buttons.length; i < length; i++ ) {
		buttons[i].addEventListener( 'click', function(e){
		    e.preventDefault();
		    document.getElementById('sidebar').style.display='block';
		    document.getElementById('header').style.display='block';
		    var c = document.getElementById(this.getAttribute('data-map-container'));
		    c.removeClass('full');
		    google.maps.event.trigger( map, "resize" );
		    map.fitBounds(bounds);
		    this.style.display = 'none';
		    c.children[1].style.display = 'block';
		}, false);
	    }
	})('resize-small');

        window.onload = function() {
            bounds = new google.maps.LatLngBounds();
            map = initMap( _element.mapContainer );
            
            for (var i = 0; i < _markers.length; i++) {
                marker = placeMarker(i);
		if(markerGroups[markers[i].type].default != 1){
		    marker.setOptions({'opacity': 0.3,'strokeWeight':.1});
		}
		bounds.extend(marker.position);
		map_markers.push(marker);
		markerGroups[markers[i].type].markers.push(marker);
		openInfowindow(marker);
	    }
	    map.fitBounds(bounds);
	};
        
        return map;
    });
//})(window, document, google || {}, markers || {}, mapStyle || {}, params );


var Maptour = (function(document, window, google){
    
    var settings = {
        mapTypeId   : google.maps.MapTypeId.ROADMAP,
	mapStyles   : mapStyle || {},
	markers	    : markers || {},
	markerGroups: markerGroups || {}
    };
    
    var element = {
        mapContainer : document.getElementById( 'map' )
    };
    
    var map,
	bounds,
	iwindow
	;
	
    var Maptour = {
	map : {},
	init : function(){
	    Maptour.map = new google.maps.Map(element.mapContainer, {
		mapTypeId   : settings.mapTypeId,
		styles	    : settings.mapStyles
	    });
        },
	marker: {
	    position : function(lat, lng ) {
		return new google.maps.LatLng( lat, lng )
	    },
	    icon : function(color) {
		return {
		    path: 'M25 0c-8.284 0-15 6.656-15 14.866 0 8.211 15 35.135 15 35.135s15-26.924 15-35.135c0-8.21-6.716-14.866-15-14.866zm-.049 19.312c-2.557 0-4.629-2.055-4.629-4.588 0-2.535 2.072-4.589 4.629-4.589 2.559 0 4.631 2.054 4.631 4.589 0 2.533-2.072 4.588-4.631 4.588z',
		    fillColor: color,
		    fillOpacity: 1,
		    strokeColor: '#fff',
		    strokeWeight: 2,
		    scale: .8,
		};
	    },
	    infowindow: {
		content: function(point, index){
		    var html = '<div class="infowindow" id="infowindow-' + point.id + '">'+
			    this.title(point.title) +
			    this.image(point.image) +
			    this.description(point.description) +
			'</div>';
		    return html;
		},
		title : function(title){
		   return '<h2>' + title + '</h2>';
		},
		description : function(description){
		   return description;
		},
		image: function(image){
		   return '<img src="' + image + '" class="post-thumbnail" >';
		},
		listener : function(marker,point){
		    google.maps.event.addListener(marker, 'click', function(e) {
		    if (iwindow) iwindow.close();
			iwindow = Maptour.marker.infowindow.create(marker, point);
			iwindow.open(Maptour.map, marker);
		    });
		},
		create: function(marker, point){
		    return new google.maps.InfoWindow({
			content: Maptour.marker.infowindow.content(point)
		    });
		}
	    },
	    place : function(point, index){
		return new google.maps.Marker({
		    position: Maptour.marker.position( point.lat, point.lng ),
		    map: Maptour.map,
		    title: point.title,
		    info: Maptour.marker.infowindow.create(point, index),
		    icon: Maptour.marker.icon(markerGroups[point.type].color),
		    type: point.type
		});
	    },

	},
	
	
	placeMarkers : function(markers){
	    for (var i = 0; i < markers.length; i++) {
		currentMarker = Maptour.marker.place( markers[i], i );
		bounds.extend(currentMarker.position);
		if(markerGroups[markers[i].type].default != 1){
		    currentMarker.setOptions({'opacity': 0.3,'strokeWeight':.1});
		}
		Maptour.marker.infowindow.listener(currentMarker, markers[i]);
	    }
	    //console.log(markerGroups);
	}
        
            
    };
    
    window.onload = function() {
        bounds = new google.maps.LatLngBounds();
        Maptour.init();
	Maptour.placeMarkers(settings.markers);
        Maptour.map.fitBounds(bounds);
    };
    
}(document,window, google, markers));