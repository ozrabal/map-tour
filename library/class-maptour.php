<?php

class Maptour {

    private
	    $plugin_name = 'maptour',
	    $secret = 'RAJmIEgoEQATUufRPDov',
	    $post_type = 'map_place';

    public function __construct() {
	add_action( 'init', array( $this, 'register_post_place' ) );
	add_action( 'init', array( $this, 'register_taxonomy_place' ) );
	add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	if(is_admin()){
	    $this->admin_setup();
	}
    }

    public function admin_setup() {
	
	
	add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	add_action( 'add_meta_boxes', array( $this, 'add_meta_box') );
	add_action( 'save_post', array( $this, 'save_meta_box' ) );
    }

    public function enqueue_frontend_scripts() {
	wp_enqueue_style( 'maptour',  MAPTOUR_PLUGIN_URL . 'css/maptour.css');
	
    }

    public function admin_enqueue_scripts() {
	wp_enqueue_script( 'maps', 'https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&sensor=false' );
	wp_enqueue_script( 'field-map', plugins_url( '../js/backend-map.js', __FILE__ ), array( 'jquery' ), MAPTOUR_VERSION );
	wp_localize_script( 'field-map', 'geocode_notfound', __( 'No results were found for the search criteria', 'mt' ) );
    }

    public function register_post_place() {
	$labels = array(
	    'name' => __( 'Places', 'mt' ),
	    'singular_name' => __( 'Place', 'mt' ),
	    'add_new' => __( 'Add New', 'mt' ),
	    'add_new_item' => __( 'Add New place', 'mt' ),
	    'edit_item' => __( 'Edit place', 'mt' ),
	    'new_item' => __( 'New place', 'mt' ),
	    'view_item' => __( 'View place', 'mt' ),
	    'search_items' => __( 'Search places', 'mt' ),
	    'not_found' => __( 'No placess found', 'mt' ),
	    'not_found_in_trash' => __( 'No places found in Trash', 'mt' ),
	    'parent_item_colon' => __( 'Parent place:', 'mt' ),
	    'menu_name' => __( 'Map places', 'mt' ),
	);
	$args = array(
	    'labels' => $labels,
	    'hierarchical' => false,
	    'description' => __( 'places', 'mt' ),
	    'supports' => array( 'title', 'editor','custom-fields', 'thumbnail' ),
	    'public' => true,
	    'show_ui' => true,
	    'show_in_menu' => true,
	    'menu_position' => 6,
	    'show_in_nav_menus' => true,
	    'publicly_queryable' => true,
	    'exclude_from_search' => true,
	    'has_archive' => false,
	    'query_var' => true,
	    'can_export' => true,
	    'rewrite' => true,
	    'capability_type' => 'post'
	);
	register_post_type( $this->post_type, $args );
    }

    public function register_taxonomy_place() {
	$labels = array(
		'name'                       => _x( 'Writers', 'taxonomy general name' ),
		'singular_name'              => _x( 'Writer', 'taxonomy singular name' ),
		'search_items'               => __( 'Search Writers' ),
		'popular_items'              => __( 'Popular Writers' ),
		'all_items'                  => __( 'All Writers' ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit Writer' ),
		'update_item'                => __( 'Update Writer' ),
		'add_new_item'               => __( 'Add New Writer' ),
		'new_item_name'              => __( 'New Writer Name' ),
		'separate_items_with_commas' => __( 'Separate writers with commas' ),
		'add_or_remove_items'        => __( 'Add or remove writers' ),
		'choose_from_most_used'      => __( 'Choose from the most used writers' ),
		'not_found'                  => __( 'No writers found.' ),
		'menu_name'                  => __( 'Writers' ),
	);

	$args = array(
		'hierarchical'          => true,
		'labels'                => $labels,
		'show_ui'               => true,
		'show_admin_column'     => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'place_type' ),
	);

	register_taxonomy( 'place_type', $this->post_type, $args );
	
    }


    public function add_meta_box() {
	add_meta_box( 'metabox_' . $this->plugin_name, __( 'Post place', 'mt' ), array( $this, 'render_metabox' ), $this->post_type );
    }

    public function render_metabox( $post ) {
	wp_nonce_field( $this->secret . '_save', $this->plugin_name . '_nonce' );
	$value = get_post_meta( $post->ID, 'place_data', true );
	$type = 'hidden';
	if ( MAPTOUR_DEBUG ) {
	    $type = 'text';
	}
	$html = '<style>.map-box {height: 250px;}</style>'
		. '<div id="field_map" class="map-field box">'
		. '<input onkeydown="if (event.keyCode == 13){ codeAddress(); return false;}" id="geocode_field_map" class="controls" type="text" placeholder="' . __( 'Type location', 'mt' ) . '">'
		. '<input type="button" class="code-address button button-small" value="' . __( 'Show on map', 'mt' ) . '" >'
		. '<div id="map_field_map" class="map-box" ></div>'
		. '<input id="map" class="geodata"  type="' . $type . '"  name="place_data" value="' . esc_attr( $value ) . '" />'
		. '</div>';
	echo $html;
    }

    public function save_meta_box( $post_id ) {
	if ( !filter_input( INPUT_POST, $this->plugin_name . '_nonce' ) ) {
	    return;
	}
	if ( !wp_verify_nonce( filter_input( INPUT_POST, $this->plugin_name . '_nonce' ), $this->secret . '_save' ) ) {
	    return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
	    return;
	}
	if ( filter_input( INPUT_POST, 'post_type' ) && 'page' == filter_input( INPUT_POST, 'post_type' ) ) {
	    if ( ! current_user_can( 'edit_page', $post_id ) ) {
		    return;
	    }
	} else {
	    if ( ! current_user_can( 'edit_post', $post_id ) ) {
		    return;
	    }
	}
	if ( !filter_input( INPUT_POST, 'place_data' ) ) {
	    return;
	}
	$my_data = sanitize_text_field( filter_input( INPUT_POST, 'place_data' ) );
	update_post_meta( $post_id, 'place_data', $my_data );
    }


 

}