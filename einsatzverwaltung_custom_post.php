<?php

/**
 * Custom Post Type for Einsatzverwaltung
 * 
 * @author Andre Becker
 **/

function custom_post_mission() {

	$labels = array(
		'name'               => __( 'Missions', 'einsatzverwaltung_textdomain' ),
		'singular_name'      => __( 'Mission', 'einsatzverwaltung_textdomain' ),
		'add_new'            => __( 'Add New', 'einsatzverwaltung_textdomain' ),
		'add_new_item'       => __( 'Add New Mission', 'einsatzverwaltung_textdomain' ),
		'edit_item'          => __( 'Edit Mission', 'einsatzverwaltung_textdomain' ),
		'new_item'           => __( 'New Mission', 'einsatzverwaltung_textdomain' ),
		'all_items'          => __( 'All Missions', 'einsatzverwaltung_textdomain' ),
		'view_item'          => __( 'View Mission', 'einsatzverwaltung_textdomain' ),
		'search_items'       => __( 'Search Missions', 'einsatzverwaltung_textdomain' ),
		'not_found'          => __( 'No Missions found', 'einsatzverwaltung_textdomain' ),
		'not_found_in_trash' => __( 'No Missions found in the Trash', 'einsatzverwaltung_textdomain' ), 
		'parent_item_colon'  => '',
		'menu_name'          => 'Eins&auml;tze'
	);
	$args = array(
		'labels'        => $labels,
		'description'   => 'Holds our missions and specific data',
		'public'        => true,
		'menu_position' => 5,
		'supports'      => array( 'title', 'author', 'editor' ),
		'has_archive'   => true,
		'menu_icon' 	=> plugin_dir_url( __FILE__ ).'img/blaulicht_state_hover.png',
		'register_meta_box_cb' => 'einsatzverwaltung_add_custom_box'
	);
	register_post_type( 'mission', $args );	
}

// function my_updated_messages( $messages ) {
// 	global $post, $post_ID;
// 	$messages['einsatz'] = array(
// 		0 => '', 
// 		1 => sprintf( __('Product updated. <a href="%s">View product</a>'), esc_url( get_permalink($post_ID) ) ),
// 		2 => __('Custom field updated.'),
// 		3 => __('Custom field deleted.'),
// 		4 => __('Product updated.'),
// 		5 => isset($_GET['revision']) ? sprintf( __('Product restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
// 		6 => sprintf( __('Product published. <a href="%s">View product</a>'), esc_url( get_permalink($post_ID) ) ),
// 		7 => __('Product saved.'),
// 		8 => sprintf( __('Product submitted. <a target="_blank" href="%s">Preview product</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
// 		9 => sprintf( __('Product scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview product</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
// 		10 => sprintf( __('Product draft updated. <a target="_blank" href="%s">Preview product</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
// 	);
// 	return $messages;
// }
// add_filter( 'post_updated_messages', 'my_updated_messages' );


/**
 * Add Custom Box to Category
 *
 * @author Andre Becker
 * */
function einsatzverwaltung_add_custom_box() {
	add_meta_box(
		'einsatzverwaltung_sectionid',
		__( 'Einsatzverwaltung', 'einsatzverwaltung_textdomain' ),
		'einsatzverwaltung_inner_custom_box',
		'mission'
	);
}

function einsatzverwaltung_inner_custom_box( $post ) {

  	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'einsatzverwaltung_noncename' );

	$meta_values = get_post_meta( $post->ID, MISSION_ID, '' );
	$meta_values = array_filter( $meta_values );

	if ( !empty( $meta_values ) ) {
		$mission = einsatzverwaltung_load_mission_by_id( $meta_values[0] );
		$vehicles_by_mission = einsatzverwaltung_load_vehicles_by_mission_id( $mission->id );
		$vehicles = einsatzverwaltung_load_vehicles();
	}else {
		$vehicles = einsatzverwaltung_load_vehicles();

		$mission->id = "";
		$mission->art_alarmierung = "";
		$mission->alarmstichwort = "";
		$mission->alarm_art = "";
		$mission->einsatzort = "";
		$mission->alarmierung_date = "";
		$mission->alarmierung_time = "";
		$mission->rueckkehr_date = "";
		$mission->rueckkehr_time = "";
		$mission->link_to_media = "";
		$mission->freitext = "";
		$vehicles_by_mission = array();
	}

	if ( strlen( $mission->art_alarmierung ) != 0 ) {
		// http://wpquicktips.wordpress.com/2012/04/25/using-php-variables-in-javascript-with-wp_localize_script/
		// http://www.ronakg.com/2011/05/passing-php-array-to-javascript-using-wp_localize_script/
		set_selector_for_dropdown_value( "#alarm_art", $mission->art_alarmierung );
	}
	if ( strlen( $mission->alarmstichwort ) != 0 ) {
		set_selector_for_dropdown_value( "#alarm_stichwort", $mission->alarmstichwort );
	}

	if ( strlen( $mission->alarm_art ) != 0 ) {
		set_selector_for_dropdown_value( "#alarm", $mission->alarm_art );
	}

	if ( count( $vehicles_by_mission ) != 0 ) {
		for ( $i=0; $i < count( $vehicles_by_mission ); $i++ ) {
			$name = rename_db_vehicle_name( $vehicles_by_mission[$i]->description );
			set_selector_for_checkbox_value( $name );
		}
	}

	$script = <<< EOF
<script type='text/javascript'>
    jQuery(document).ready(function($) {


    	if($('#alarm_stichwort').val() === 'Sonstiger Brand' || $('#alarm_stichwort').val() === 'Freitext'){
    		$('#row_freitext_alarmstichwort').show();
    	}else{
    		$('#row_freitext_alarmstichwort').hide();
    	}

        $('#alarm_stichwort').change(function() {
        	if($('#sel_so_brand').is(':selected') || $('#sel_freitext').is(':selected')) {
            	$('#row_freitext_alarmstichwort').show();
         }else{
            $('#row_freitext_alarmstichwort').hide();
         }
        });

        $('#alarm_date').change(function(){
        	$('#alarm_end_date').val($(this).val());
        });

		var availableTags = [
			"Langenbrücken",
			"Mingolsheim",
			"Bad Schönborn",
			"Östringen",
			"Kraichtal",
			"Bruchsal",
			"Wiesental",
			"Waghäusel",
			"Kirrlach",
			"Odenheim",
			"Kronau"];

	    $( "#einsatzort" ).autocomplete({
			source: availableTags
		});

    });

</script>
EOF;

	echo $script;

	echo '<table border="1">';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="mission_id">';
	_e( "Einsatz Nr.", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input id="mission_id" name="mission_id" value="'.$mission->id.'" readonly="true" size="4"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="alarm_art">';
	_e( "Art der Alarmierung", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<select id="alarm_art" name="alarm_art">';
	echo '  			<option>Brandeinsatz</option>';
	echo '   			<option>Technischer Einsatz</option>';
	echo '   			<option>Sonstiger Einsatz</option>';
	echo '  		</select>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="alarm_stichwort">';
	_e( "Alarmstichwort", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<select id="alarm_stichwort" name="alarm_stichwort">';
	echo '				<optgroup label="Brand">';
	echo '   				<option>Brandmeldealarm</option>';
	echo '   				<option>Lagerhallenbrand</option>';
	echo '   				<option>Dachstuhlbrand</option>';
	echo '   				<option>Wohnungsbrand</option>';
	echo '   				<option>Zimmerbrand</option>';
	echo '   				<option>Kellerbrand</option>';
	echo '   				<option>Kleinbrand</option>';
	echo '   				<option>PKW-Brand</option>';
	echo '   				<option>Feuerschein</option>';
	echo '   				<option id="sel_so_brand">Sonstiger Brand</option>';
	echo '				<optgroup label="Technische Hilfe">';
	echo '   				<option>Verkehrsunfall</option>';
	echo '   				<option>Person in Not</option>';
	echo '   				<option>Unterstützung Rettungsdienst</option>';
	echo '   				<option>Wasserschaden</option>';
	echo '				<optgroup label="Sonstiges">';
	echo '   				<option>Absperrmaßnahme</option>';
	echo '   				<option>Ölspur</option>';
	echo '		   			<option>Drehleitereinsatz</option>';
	echo '   				<option>Sicherheitsdienst</option>';
	echo '		  			<option id="sel_freitext">Freitext</option>';
	echo '  		</select>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr id="row_freitext_alarmstichwort">';
	echo '		<td>';
	echo '			<label for="alarmstichwort_freitext">';
	_e( "Alarmstichwort (Freitext)", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	if ( ( $mission->alarmstichwort == "Freitext" ) || ( $mission->alarmstichwort == "Sonstiger Brand" ) ) {
		echo '			<input name="alarmstichwort_freitext" value="'.$mission->freitext.'"/>';
	}
	else {
		echo '			<input name="alarmstichwort_freitext" />';
	}
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="alarm">';
	_e( "Alarm", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<select id="alarm" name="alarm">';
	echo '  			<option>Einsatzalarm</option>';
	echo '   			<option>Keine Tätigkeit</option>';
	echo '  		</select>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="einsatzort">';
	_e( "Einsatzort", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input id="einsatzort" name="einsatzort" value="'.$mission->einsatzort.'"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="alarmierung_datum">';
	_e( "Alarmierung (Datum)", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input id="alarm_date" name="alarmierung_datum" type="date" value="'.$mission->alarmierung_date.'"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="alarmierung_zeit">';
	_e( "Alarmierung (Uhrzeit)", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input name="alarmierung_zeit" type="time" value="'.$mission->alarmierung_time.'"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="rueckkehr_datum">';
	_e( "R&uuml;ckkehr (Datum)", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input id="alarm_end_date" name="rueckkehr_datum" type="date" value="'.$mission->rueckkehr_date.'"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="rueckkehr_zeit">';
	_e( "R&uuml;ckkehr (Uhrzeit)", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input name="rueckkehr_zeit" type="time" value="'.$mission->rueckkehr_time.'"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="link_zu_medien">';
	_e( "Link zu weiterf&uuml;hrenden Medien", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input name="link_zu_medien" type="url" value="'.$mission->link_to_media.'" size="50"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="fahrzeuge">';
	_e( "Eingesetzte Fahrzeuge", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	if ( count( $vehicles ) > 0 ) {
		for ( $i=0; $i<count( $vehicles ); $i++ ) {
			$name = rename_db_vehicle_name( $vehicles[$i]->description );
			echo '			<label for="'.$name.'"> <input name="'.$name.'" type="checkbox"/> '.$vehicles[$i]->description.' </label>';
		}
	}else {
		echo '<p>';
		_e( "Keine Fahrzeuge in der Datenbank gefunden!", 'einsatzverwaltung_textdomain' );
		echo '</p>';
	}
	echo '		</td>';
	echo '	</tr>';
	echo '</table>';

}

/**
 * Save and Edit Mission Details
 *
 * @author Andre Becker
 * */
/* When the post is saved, saves our custom data */
function einsatzverwaltung_save_data( $post_id ) {
	global $wpdb;

	$table_name_missions =     $wpdb->prefix . "einsaetze";
	$table_name_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";
	
	// verify if this is an auto save routine.
	// If it is our form has not been submitted, so we dont want to do anything
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( $_POST['einsatzverwaltung_noncename'], plugin_basename( __FILE__ ) ) )
		return;

	// Check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
			return;
	}
	else {
		if ( !current_user_can( 'edit_post', $post_id ) )
			return;
	}

	// $cat_id = get_the_category( $post_id );

	// //Check if mission category
	// if ( CATEGORY != $cat_id[0]->cat_ID )
	// 	return;

	// OK, we're authenticated: we need to find and save the data


	$mission_id = $_POST['mission_id'];

	$alarm_art = $_POST['alarm_art'];

	if ( ( $_POST['alarm_stichwort'] == "Freitext" ) || ( $_POST['alarm_stichwort'] == "Sonstiger Brand" ) ){
		$freitext = $_POST['alarmstichwort_freitext'];
	} else{
		$freitext = "";
	}
	

	$alarm = $_POST['alarm'];
	$einsatzort = $_POST['einsatzort'];
	$alarm_stichwort = $_POST['alarm_stichwort'];
	$alarmierung_datum = $_POST['alarmierung_datum'];
	$alarmierung_zeit = $_POST['alarmierung_zeit'];
	$rueckkehr_datum = $_POST['rueckkehr_datum'];
	$rueckkehr_zeit = $_POST['rueckkehr_zeit'];
	$link_zu_medien = einsatzverwaltung_shorten_media_link( $_POST['link_zu_medien'] );

	$db_vehicles = einsatzverwaltung_load_vehicles();
	$vehicles = array();

	for ( $i=0; $i<count( $db_vehicles ); $i++ ) {
		$name = rename_db_vehicle_name( $db_vehicles[$i]->description );

		if ( isset( $_POST[$name] ) )
			$vehicles[] = $db_vehicles[$i]->id;
	}

	if ( !empty( $mission_id ) ) {
		//Update
		$wpdb->update(
			$table_name_missions,
			array(
				'art_alarmierung' => $alarm_art,
				'alarmstichwort' => $alarm_stichwort,
				'alarm_art' => $alarm,
				'einsatzort' => $einsatzort,
				'alarmierung_date' => $alarmierung_datum,
				'alarmierung_time' => $alarmierung_zeit,
				'rueckkehr_date' => $rueckkehr_datum,
				'rueckkehr_time' => $rueckkehr_zeit,
				'link_to_media' => $link_zu_medien,
				'freitext' => $freitext
			),
			array( 'id' => $mission_id )
		);

		if ( function_exists( "simple_history_add" ) ) {
			simple_history_add( "action=updated&object_type=Mission&object_name=".$alarm_stichwort."" );
		}
		//loop for all vehicles
		//remove all vehicles bound to current mission!
		$query = "DELETE FROM ". $table_name_missions_has_vehicles ." WHERE einsaetze_id = ".$mission_id;

		//fire delete query!
		$delete = $wpdb->query( $query );

		//insert new values:
		foreach ( $vehicles as $vehicle ) {
			$wpdb->insert(
				$table_name_missions_has_vehicles,
				array(
					'einsaetze_id' => $mission_id,
					'fahrzeuge_id' => $vehicle
				), array() );
		}

	}else {
		//new mission entry
		$wpdb->insert(
			$table_name_missions,
			array(
				'art_alarmierung' => $alarm_art,
				'alarmstichwort' => $alarm_stichwort,
				'alarm_art' => $alarm,
				'freitext' => $freitext,
				'einsatzort' => $einsatzort,
				'alarmierung_date' => $alarmierung_datum,
				'alarmierung_time' => $alarmierung_zeit,
				'rueckkehr_date' => $rueckkehr_datum,
				'rueckkehr_time' => $rueckkehr_zeit,
				'link_to_media' => $link_zu_medien,
				'wp_posts_ID' => $post_id
			), array() );

		$id = $wpdb->insert_id;

		if ( function_exists( "simple_history_add" ) ) {
			simple_history_add( "action=created&object_type=Mission&object_name=".$alarm_stichwort."" );
		}

		foreach ( $vehicles as $vehicle ) {
			$wpdb->insert(
				$table_name_missions_has_vehicles,
				array(
					'einsaetze_id' => $id,
					'fahrzeuge_id' => $vehicle
				), array() );
		}

		add_post_meta( $post_id, MISSION_ID, $id );
	}
}

/**
 * Delete mission data of current post
 *
 * @return boolean
 * @author Andre Becker
 * */
function einsatzverwaltung_trash_mission( $post_id ) {
	// global $wpdb;

	if ( current_user_can( 'delete_posts' ) )
		$i = 0;
	// wp_die("Post ID: ".$pid);

	// if ($wpdb->get_var($wpdb->prepare('SELECT post_id FROM codex_postmeta WHERE post_id = %d', $pid))) {
	//   return $wpdb->query($wpdb->prepare('DELETE FROM codex_postmeta WHERE post_id = %d', $pid));
	// }
	// return true;
}

/**
 * Shorten the additional media link via bit.ly v.3 REST API
 *
 * @return String
 * @author Andre Becker
 * */
function einsatzverwaltung_shorten_media_link( $link ) {

	$short_link;
	$url = 'http://api.bit.ly/v3/shorten?format=txt&login='.BITLY_USER.'&apiKey='.BITLY_API_KEY.'&longUrl='.$link;
	$response = wp_remote_get( $url );

	if ( !is_wp_error( $response ) ) {
		// wp_die($response['body']);
		$short_link = "";
	}else {
		$short_link = $link;
	}
	// $shortend_url = file_get_contents($url);
	$short_link = $link;
	return $short_link;
}

/**
 *
 *
 * @author Andre Becker
 * */
function rename_db_vehicle_name( $name ) {

	if ( $name == "DLK 23/12" )
		$name = "dlk";

	$cleaned_name = str_replace( ' ', '', $name );
	$name = strtolower( $cleaned_name );

	return "fahrzeuge_".$name;
}

/**
 * Load all vehicles
 *
 * @return array()
 * @author Andre Becker
 * */
function einsatzverwaltung_load_vehicles() {
	global $wpdb;
	$table_name_vehicles = $wpdb->prefix . "fahrzeuge";

	$query = "SELECT id, description FROM ". $table_name_vehicles;
	$vehicles = $wpdb->get_results( $query );


	return $vehicles;
}

/**
 * Load missions by mission_id
 *
 * @param unknown $id
 * @return array()
 * @author Andre Becker
 * */
function einsatzverwaltung_load_mission_by_id( $id ) {
	global $wpdb;
	$table_name_missions = $wpdb->prefix . "einsaetze";

	$query = "SELECT * FROM ". $table_name_missions ." WHERE id = ".$id;
	$mission_details = $wpdb->get_row( $query );


	return $mission_details;
}

/**
 * Load vehicles bound to mission
 *
 * @param unknown $mission_id
 * @return array()
 * @author Andre Becker
 * */
function einsatzverwaltung_load_vehicles_by_mission_id( $mission_id ) {
	global $wpdb;
	$table_name_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";
	$table_name_vehicles =     $wpdb->prefix . "fahrzeuge";

	$query = "SELECT f.description FROM ". $table_name_vehicles ." as f, ". $table_name_missions_has_vehicles ." as h WHERE f.id = h.fahrzeuge_id AND h.einsaetze_id = ".$mission_id;

	$vehicles = $wpdb->get_results( $query );

	return $vehicles;
}

/**
 * Load missions bound to post id
 *
 * @param post    id
 * @return single mission
 * @author Andre Becker
 * */
function einsatzverwaltung_load_mission_by_post_id( $id ) {
	global $wpdb;
	$table_name_missions = $wpdb->prefix . "einsaetze";

	$query = "SELECT * FROM ". $table_name_missions ." WHERE wp_posts_ID = ".$id;
	$mission_details = $wpdb->get_row( $query );


	return $mission_details;
}
/**
 *
 *
 * @author Andre Becker
 * */
function set_selector_for_dropdown_value( $id, $value ) {
	$script = "
	<script type='text/javascript'>
	 jQuery(document).ready(function($) {
		$('".$id."').val('".$value."');
	});
	</script>";
	echo $script;
}
/*
 *
 *
 * @author Andre Becker
 **/
function set_selector_for_checkbox_value( $value ) {
	$script = "
	<script type='text/javascript'>
	 jQuery(document).ready(function($) {
		$('input[name=".$value."]').attr('checked', true);
	});
	</script>";
	echo $script;
}

// // ONLY MOVIE CUSTOM TYPE POSTS
// add_filter('manage_einsatz_posts_columns', 'ST4_columns_head_only_movies', 10);

// // CREATE TWO FUNCTIONS TO HANDLE THE COLUMN
// function ST4_columns_head_only_movies($defaults) {
// 	$defaults['author'] = 'Author';
// 	return $defaults;
// }
?>


