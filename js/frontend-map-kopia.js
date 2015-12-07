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



var map_markers = [];

var bounds;
var map;




function create_infowindow(i){
    var html = '<div class="infowindow">'+
            '<h2>'+markers[i].title+'</h2>'+infowindowContent(i)+infowindowNavigation(i)+
            '</div>';
    return html;
}

function infowindowContent(i){
    var html = '<div class="content">';
    if(markers[i].image){
	    html += '<img src="'+markers[i].image+'" class="post-thumbnail" >';
	}
	    html += markers[i].description+'</div>';


return html;
}


function infowindowNavigation(i){
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


}


function myclick(index) {

   google.maps.event.trigger(map_markers[index],"click");
}


function pinSymbol(color) {

    return {
        path: 'M25 0c-8.284 0-15 6.656-15 14.866 0 8.211 15 35.135 15 35.135s15-26.924 15-35.135c0-8.21-6.716-14.866-15-14.866zm-.049 19.312c-2.557 0-4.629-2.055-4.629-4.588 0-2.535 2.072-4.589 4.629-4.589 2.559 0 4.631 2.054 4.631 4.589 0 2.533-2.072 4.588-4.631 4.588z',
        fillColor: color,
        fillOpacity: 1,
        strokeColor: '#fff',
        strokeWeight: 2,
        scale: .9,
   };
}







//900000344400000991998808

var maptour = (function(window, document, map){

    var 
        bounds,
        map,
        _element = {
            mapContainer : document.getElementById( 'map' )
        },
        _initMap = function(m){
            return new google.maps.Map(m, {
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		styles: map_style
	    })
        };

        window.onload = function() {
            bounds = new google.maps.LatLngBounds();
            map = _initMap( _element.mapContainer );
//     map = new google.maps.Map(document.getElementById('map'), {
//
//		mapTypeId: google.maps.MapTypeId.roadmap,
//		styles: map_style
//	    });





	    var infowindow;
	    for (var i = 0; i < markers.length; i++) {
		var marker = new google.maps.Marker({
		    position: new google.maps.LatLng(markers[i].lat,markers[i].lng ),
		    map: map,
		    title: markers[i].title,
		    info: create_infowindow(i),
		    icon: pinSymbol(markerGroups[markers[i].type].color),
		    type: markers[i].type
		});

		console.log(markers[i].type);

if(markerGroups[markers[i].type].default != 1){
    marker.setOptions({'opacity': 0.3,'strokeWeight':.1});
}

 bounds.extend(marker.position);

map_markers.push(marker);

markerGroups[markers[i].type].markers.push(marker);

		(function(marker, i) {




		    google.maps.event.addListener(marker, 'click', function(e) {

			if (infowindow) infowindow.close();
			infowindow = new google.maps.InfoWindow({
			    content: this.info
			});
			infowindow.open(map, marker);
		    });
		})(marker, i);
	    }

	    map.fitBounds(bounds);



	};
        
        return map;
})(window, document, maptour || {} );






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
        }else{

    }

}









    for (var i = 0; i < map_markers.length; i++) {

        var marker = map_markers[i];

if(marker.type == type) {
     marker.setVisible(true);
     marker.setOptions({'opacity': 1, 'strokeWeight':1});

}else{
     //marker.setVisible(false);
    marker.setOptions({'opacity': 0.3,'strokeWeight':.1});
}


//	    if (!marker.getVisible()) {
//            marker.setVisible(true);
//        } else {
//            marker.setVisible(false);
//        }

	}

}, false);
};
})('#map-legend a');



var resizeBig = (function resizeBig(btn_class){
    var buttons = document.getElementsByClassName(btn_class);
    for ( var i = 0, length = buttons.length; i < length; i++ ) {
	buttons[i].addEventListener( 'click', function(e){
	    e.preventDefault();
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
	    var c = document.getElementById(this.getAttribute('data-map-container'));
	    c.removeClass('full');
	    google.maps.event.trigger( map, "resize" );
	    map.fitBounds(bounds);
	    this.style.display = 'none';
	    c.children[1].style.display = 'block';
	}, false);
    }
})('resize-small');



var showhide = (function showhide_markers(b){
var toggle_buttons = document.querySelectorAll(b);
for ( var i=0; i < toggle_buttons.length; i++ ) {

	    toggle_buttons[i].addEventListener( 'click', function(e){

    var type = this.getAttribute('data-type');
if(this.classList.contains('hidden')){
    this.classList.remove('hidden');
}else{
    this.classList.add('hidden');
    }


    for (var i = 0; i < markerGroups[type].markers.length; i++) {

        var marker = markerGroups[type].markers[i];

	    if (!marker.getVisible()) {
            marker.setVisible(true);
        } else {
            marker.setVisible(false);
        }

	}

}, false);
};
})('s#map-legend a');



