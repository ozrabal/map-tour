<?php
/*
 * Plugin Name: Map Tour
 * Plugin URI: http://
 * Description:
 * Version: 1.0.0
 * Author:
 * Author URI: http://webkowski.com
 * Text Domain: mt
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'MAPTOUR_VERSION', '1.0.0');
define( 'MAPTOUR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAPTOUR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MAPTOUR_DEBUG', true );

require_once 'library/class-maptour.php';

add_action( 'plugins_loaded', function() {
    new Maptour();
});





add_action( 'the_content', 'get_pages_map' );
function get_pages_map( $content ) {
    if ( !is_single() && !is_page() ) {
	return;
    }
    $place_types = get_terms( 'place_type' );

dump($place_types);

    $map_args  = array(
	'post_type'	=> 'map_place',
	'post_status'	=> 'publish',
	'meta_query' => array(
	    array(
		'key'	    => 'place_data',
		'compare'   => 'EXIST',
	    )
	)
    );
    $map_query = new WP_Query( $map_args );
    if ( $map_query->have_posts() ) {
	$i = 0;
	while ( $map_query->have_posts() ) {
	    $map_query->the_post();

	    $page_location = explode( ',', get_post_meta( get_the_ID(), 'place_data', true ) );

	    $map_markers[$i]['lat'] = $page_location[0];
	    $map_markers[$i]['lng'] = $page_location[1];
	    $map_markers[$i]['title'] = get_the_title();
	    $map_markers[$i]['description'] = get_the_content();
	    $image = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'thumbnail' );
	    $map_markers[$i]['image'] = $image[0];
	    //$map_markers[$i]['image'] = wp_get_attachment_url( get_post_thumbnail_id( get_the_ID() ) );
	    $map_markers[$i]['url'] = get_permalink();
	    $type = wp_get_post_terms( get_the_ID(), 'place_type', array( 'fields' => 'slugs') );
	    $map_markers[$i]['type'] = $type[0];

	    $i++;
	    //$page_location[] = '<div id="content"><h3><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h3>' . get_the_content() . '</div>';
	    //$markers[] = $page_location;
	}
    }
    dump($map_markers);
    //dump($markers);
    //dump($place_types);
    ?>
    <style>
	.entry-content img, .comment-content img, .widget img {
	    max-width: inherit;
	}
	#map {
	    height: 600px;
	    border: 0px solid #000;
	}
	#map-legend .hidden{
	    opacity: .2;
	}
    </style>
    <script>
	
	
	
    </script>
    <div id="map"></div>

    <div class="siderbarmap">
        <ul id="map-legend">
            <?php
	    $html = '';
    foreach( $place_types as $type ) {
		$types[$type->slug]['markers'] = array();
		$type_description = explode(',', $type->description);
		$types[$type->slug]['color'] = $type_description[0];
		$types[$type->slug]['default'] = isset($type_description[1])?1:0;
		$hidden = 'hidden';
		if($type_description[1] == 'default'){
		    $hidden = '';
		}
		$html .= '<li><a class="' . $hidden . '" href="#' . $type->slug . '" data-type="' . $type->slug . '">' . $type->name . '</a></li>';
	    }
	    echo $html;
	?>
            
        </ul>
    </div>
    <script>

//	var markerGroups = {
//
//        "a": [],
//        "b": [],
//        "c": [],
//	"d": []
//};
//console.log(markerGroups);


var markerGroups = <?php

echo json_encode( $types, JSON_HEX_QUOT | JSON_HEX_TAG  );


?>;

var map_markers = [];

var markers = <?php echo json_encode( $map_markers, JSON_HEX_QUOT | JSON_HEX_TAG ); ?>;
//var markerGroups = [];

	window.onload = function() {

	    var bounds = new google.maps.LatLngBounds();
	    
    var map = new google.maps.Map(document.getElementById('map'), {

		mapTypeId: google.maps.MapTypeId.roadmap,
		styles:[
    {
        "featureType": "landscape",
        "elementType": "all",
        "stylers": [
            {
                "weight": "1.71"
            },
            {
                "gamma": "1.48"
            },
            {
                "lightness": "-17"
            },
            {
                "saturation": "40"
            },
            {
                "hue": "#00ffdb"
            }
        ]
    },
    {
        "featureType": "landscape.man_made",
        "elementType": "all",
        "stylers": [
            {
                "saturation": "-50"
            },
            {
                "lightness": "-10"
            },
            {
                "gamma": "3.26"
            },
            {
                "weight": "3.00"
            }
        ]
    },
    {
        "featureType": "landscape.natural",
        "elementType": "geometry.fill",
        "stylers": [
            {
                "visibility": "on"
            },
            {
                "color": "#e0efef"
            }
        ]
    },
    {
        "featureType": "poi",
        "elementType": "geometry.fill",
        "stylers": [
            {
                "visibility": "on"
            },
            {
                "hue": "#1900ff"
            },
            {
                "color": "#c0e8e8"
            }
        ]
    },
    {
        "featureType": "road",
        "elementType": "all",
        "stylers": [
            {
                "saturation": "27"
            }
        ]
    },
    {
        "featureType": "road",
        "elementType": "geometry",
        "stylers": [
            {
                "lightness": 200
            },
            {
                "visibility": "simplified"
            }
        ]
    },
    {
        "featureType": "road",
        "elementType": "labels",
        "stylers": [
            {
                "visibility": "on"
            }
        ]
    },
    {
        "featureType": "transit.line",
        "elementType": "geometry",
        "stylers": [
            {
                "visibility": "on"
            },
            {
                "lightness": 700
            }
        ]
    },
    {
        "featureType": "water",
        "elementType": "all",
        "stylers": [
            {
                "color": "#77c1bc"
            },
            {
                "visibility": "on"
            },
            {
                "lightness": "13"
            }
        ]
    },
    {
        "featureType": "water",
        "elementType": "geometry",
        "stylers": [
            {
                "saturation": "19"
            },
            {
                "hue": "#00b2ff"
            },
            {
                "lightness": "-4"
            }
        ]
    },
    {
        "featureType": "water",
        "elementType": "geometry.fill",
        "stylers": [
            {
                "lightness": "31"
            }
        ]
    },
    {
        "featureType": "water",
        "elementType": "labels.text",
        "stylers": [
            {
                "color": "#3d3b38"
            },
            {
                "visibility": "simplified"
            },
            {
                "lightness": "20"
            }
        ]
    }
]
	    });

	    var infowindow;
	    for (var i = 0; i < markers.length; i++) {
		var marker = new google.maps.Marker({
		    position: new google.maps.LatLng(markers[i].lat,markers[i].lng ),
		    map: map,
		    title: markers[i].title,
		    info: '<div id="content">'+
      '<div id="siteNotice">'+
      '</div>'+
      '<h1 id="firstHeading" class="firstHeading">'+markers[i].title+'</h1>'+
      '<div id="bodyContent">'+
      '<img src="'+markers[i].image+'" >'+markers[i].description+
      '</div>'+
      '</div>',
		  
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

		    

		    
		    google.maps.event.addListener(marker, 'click', function() {
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

console.log(map_markers);

var toggle = (function toggle_markers(b){
var toggle_buttons = document.querySelectorAll(b);

for ( var i=0; i < toggle_buttons.length; i++ ) {

	    toggle_buttons[i].addEventListener( 'click', function(e){


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





function pinSymbol(color) {
    
    return {
        path: 'M0-48c-9.8 0-17.7 7.8-17.7 17.4 0 15.5 17.7 30.6 17.7 30.6s17.7-15.4 17.7-30.6c0-9.6-7.9-17.4-17.7-17.4z',
        fillColor: color,
        fillOpacity: 1,
        strokeColor: '#fff',
        strokeWeight: 2,
        scale: .7,
   };
}


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


//	function toggleGroup(type) {
//
//
//    for (var i = 0; i < markerGroups[type].length; i++) {
//        var marker = markerGroups[type][i];
//        if (!marker.getVisible()) {
//            marker.setVisible(true);
//        } else {
//            marker.setVisible(false);
//        }
//    }
//}

    </script>
    <?php
    wp_enqueue_script( 'maps', 'http://maps.google.com/maps/api/js', array(), true );

    return $content;
}
