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
var mapStyle = mapStyle || {};
var markers = markers || {};

var Maptour = (function(document, window, google){
    
    var settings = {
        mapTypeId	: google.maps.MapTypeId.ROADMAP,
	mapStyles	: mapStyle || {},
	markers		: markers || {},
	markerGroups	: {},
	markerIconSvg	: 'M25 0c-8.284 0-15 6.656-15 14.866 0 8.211 15 35.135 15 35.135s15-26.924 15-35.135c0-8.21-6.716-14.866-15-14.866zm-.049 19.312c-2.557 0-4.629-2.055-4.629-4.588 0-2.535 2.072-4.589 4.629-4.589 2.559 0 4.631 2.054 4.631 4.589 0 2.533-2.072 4.588-4.631 4.588z'
    };
    
    var element = {
        mapContainer	    : document.getElementById( 'map' ),
	markerToggleHandler : document.querySelectorAll('#map-legend a'),
	popupOpenHandler    : document.getElementsByClassName('resize-big'),
	popupCloseHandler   : document.getElementsByClassName('resize-small')
    };
    
    var bounds,
	iwindow,
	mapMarkers = [],
	ctype;
	
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
		return new google.maps.LatLng( lat, lng );
	    },
	    icon : function(color) {
		return {
		    path	: settings.markerIconSvg,
		    fillColor	: color,
		    fillOpacity	: 1,
		    strokeColor	: '#fff',
		    strokeWeight: 2,
		    scale	: .8
		};
	    },
	    infowindow: {
		content: function(point, index){
		    var html = '<div class="infowindow" id="infowindow-' + point.id + '">'+
			    this.title(point.title) +
			    this.image(point.image) +
			    this.description(point.description) +
                            this.navigation(index)+
			    '</div>';
		    return html;
		},
		title : function(title){
		   return '<h2>' + title + '</h2>';
		},
		description : function(description){
		    if(description){
			return description;
		    }
		    return '';
		},
		image: function(image){
                    if ( image ) {
			return '<img src="' + image + '" class="post-thumbnail" >';
                    }
                    return '';
                },
		listener : function(marker,point){
		    google.maps.event.addListener(marker, 'click', function(e) {
			for (var i = 0; i < markers.length; i++) {
			    if(mapMarkers[i].type !== ctype){
				mapMarkers[i].setOptions({'opacity': .3, 'clicked': 0});
				mapMarkers[i].setZIndex(google.maps.Marker.MAX_ZINDEX -1);
			    }
			}
			marker.setOptions({'opacity' :1});
			marker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);
			if (iwindow){
			    iwindow.close();
			}
			iwindow = new google.maps.InfoWindow({
			    content: this.info
			});
			iwindow.open(Maptour.map, marker);
			marker.setOptions({'clicked': 1});
			
                    });
		},
		create: function(point, index){
                    return new google.maps.InfoWindow({
			content: Maptour.marker.infowindow.content(point, index)
		    });
		},
		navigation : function(index){
		    var next = '<a class="btn-next" href="javascript:Maptour.marker.infowindow.nav('+(parseInt(index)+1)+');"><i class="fa fa-long-arrow-right"></i></a>';
		    var previous = '<a class="btn-previous" href="javascript:Maptour.marker.infowindow.nav('+(parseInt(index)-1)+');"><i class="fa fa-long-arrow-left"></i></a>';
		    if( parseInt(index) > 0 && parseInt(index) < settings.markers.length ){
			links = previous + next;
                    }  
                    if( parseInt(index) === settings.markers.length-1 ){
		 	links = previous;    
		    }
                    if( parseInt(index) === 0  ){
			links = next;
                    }
		    var html = '<div class="infowindow-navigation"><span class="infowindow-navigation-header">Places navigation </span>'+links+'</div>';
		    return html;
		},
		nav : function(index){
		    google.maps.event.trigger(mapMarkers[index],"click");
		}
	    },
	    place : function(point, index){
            	return new google.maps.Marker({
		    position	: Maptour.marker.position( point.lat, point.lng ),
		    map		: Maptour.map,
		    title	: point.title,
		    info	: Maptour.marker.infowindow.content(point, index),
		    icon	: Maptour.marker.icon(markerGroups[point.type].color),
		    type	: point.type
		});
	    }
	},
	placeMarkers : function(markers){
	    for (var i = 0; i < markers.length; i++) {
		currentMarker = Maptour.marker.place( markers[i], i );
		bounds.extend(currentMarker.position);
		if(markerGroups[markers[i].type].default !== 1){
		    currentMarker.setOptions({'opacity': 0.3,'strokeWeight':.1});
		}
                mapMarkers.push(currentMarker);
		Maptour.marker.infowindow.listener(currentMarker, markers[i],i);
	    }
	},
	toggle_markers: function(handler) {
	    for ( var i=0, l = handler.length; i < l; i++ ) {
		var currentHandler = handler[i];
		currentHandler.addEventListener( 'click', function(e){
		    e.preventDefault();
		    var type = this.getAttribute('data-type');
		    ctype = type;
		    for ( var j=0; j < l; j++ ) {
			handler[j].setAttribute('class', 'hidden');
			if(handler[j].getAttribute('data-type') === type){
			    handler[j].setAttribute('class', '');
			};
		    }
		    for (var i = 0; i < mapMarkers.length; i++) {
			var marker = mapMarkers[i];
			if(marker.type === type) {
			    marker.setVisible(true);
			    marker.setOptions({'opacity': 1, 'strokeWeight':1});
			    marker.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);
			}else{
			    marker.setOptions({'opacity': 0.3,'strokeWeight':.1});
			    marker.setZIndex(google.maps.Marker.MAX_ZINDEX - 1);
			}
		    }
		}, false);
	    }
	},
	popup: {
	    open : function(buttons){
		for ( var i = 0, length = buttons.length; i < length; i++ ) {
		    buttons[i].addEventListener( 'click', function(e){
			e.preventDefault();
			var event = document.createEvent('event');
			var c = document.getElementById(this.getAttribute('data-map-container'));
			c.addClass('full');
			google.maps.event.trigger( Maptour.map, "resize" );
			Maptour.map.fitBounds(bounds);
			this.style.display = 'none';
			c.children[0].style.display = 'block';
		    }, false);
		}
	    },
	    close : function(buttons){
		for ( var i = 0, length = buttons.length; i < length; i++ ) {
		    buttons[i].addEventListener( 'click', function(e){
			e.preventDefault();
			var c = document.getElementById(this.getAttribute('data-map-container'));
			c.removeClass('full');
			google.maps.event.trigger( Maptour.map, "resize" );
			Maptour.map.fitBounds(bounds);
			this.style.display = 'none';
			c.children[1].style.display = 'block';
		    }, false);
		}
	    },
	    toggle : function(buttonsOpen, buttonsClose){
		this.open(buttonsOpen);
		this.close(buttonsClose);
	    }
	},
	fitView : function(zoom){
	    fitViewListener =
		google.maps.event.addListenerOnce(Maptour.map, 'bounds_changed', function(event) {
		if ( Maptour.map.getZoom()){
		    Maptour.map.setZoom(zoom);
		}
	    });
	    setTimeout(function(){google.maps.event.removeListener(fitViewListener)}, 2000);
	}
    };
    window.onload = function() {
        bounds = new google.maps.LatLngBounds();
        Maptour.init();
	Maptour.placeMarkers(settings.markers);
        Maptour.map.fitBounds(bounds);
	Maptour.fitView(13);
	Maptour.map.panToBounds(bounds);
	Maptour.toggle_markers(element.markerToggleHandler);
	Maptour.popup.toggle(element.popupOpenHandler,element.popupCloseHandler );
    };
    return Maptour;
}(document, window, google, markers));