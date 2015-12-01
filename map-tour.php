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
    //dump(__FILE__);
    $current_post_location = get_post_meta( get_the_ID(), 'place_data', true );
    if( !$current_post_location ){
	//return $content;
    }
    $current_post_location = '52.4064,16.9252, 15, roadmap';
    $current_post_location = explode( ',', $current_post_location );
    $map_args  = array(
	'post_type'	=> 'map_place',
	'post_status'	=> 'publish',
	'meta_query' => array(
	    array(
		'key'	    => 'place_data',
		'compare'   => 'EXIST',
	    ),
	),
    );
    $map_query = new WP_Query( $map_args );
    if( $map_query->have_posts() ) {
	while ( $map_query->have_posts() ) {
	    $map_query->the_post();
	    $page_location = explode( ',', get_post_meta( get_the_ID(), 'place_data', true ) );
	    $page_location[] = '<div id="content"><h3><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h3>' . get_the_content() . '</div>';
	    $markers[] = $page_location;
	}
    }
    dump($markers);
    ?>
    <style>
	.entry-content img, .comment-content img, .widget img {
	    max-width: inherit;
	}
	#map {
	    height: 600px;
	    border: 0px solid #000;
	}
    </style>
    <script>
	var markers = <?php echo json_encode( $markers, JSON_HEX_QUOT | JSON_HEX_TAG ); ?>;
    </script>
    <div id="map"></div>
    <div class="siderbarmap">
        <ul>
            <input id="a" type="checkbox" onclick="toggleGroup('a')" checked="checked"></input>
            <input id="b" type="checkbox" onclick="toggleGroup('b')" checked="checked"></input>
            <input id="c" type="checkbox" onclick="toggleGroup('c')" checked="checked"></input>
            
        </ul>
    </div>
    <script>

	var markerGroups = {
    
        "a": [],
        "b": [],
        "c": []
};
function toggleGroup(type) {
    for (var i = 0; i < markerGroups[type].length; i++) {
        var marker = markerGroups[type][i];
        if (!marker.getVisible()) {
            marker.setVisible(true);
        } else {
            marker.setVisible(false);
        }
    }
}
	window.onload = function() {

	    var bounds = new google.maps.LatLngBounds();
	    var latlng = new google.maps.LatLng(<?php echo trim($current_post_location[0]) ?>, <?php echo trim($current_post_location[1]) ?>);
	    var map = new google.maps.Map(document.getElementById('map'), {
		center: latlng,
		zoom: <?php echo trim($current_post_location[2]) ?>,
		mapTypeId: google.maps.MapTypeId.<?php echo strtoupper(trim($current_post_location[3])); ?>
	    });
	    var infowindow;
	    for (var i = 0; i < markers.length; i++) {
		var marker = new google.maps.Marker({
		    position: new google.maps.LatLng(markers[i][0],markers[i][1] ),
		    map: map,
		    title: 'click to description',
		    info: markers[i][5],
		    icon: 'http://google.com/mapfiles/ms/micons/green-dot.png',
		    type: markers[i][4]
		});
		console.log(markers[i][4]);

		
 bounds.extend(marker.position);


		(function(marker, i) {
		    markerGroups[markers[i][4]].push(marker);
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
    </script>
    <?php
    wp_enqueue_script( 'maps', 'http://maps.google.com/maps/api/js', array(), true );
    return $content;
}
