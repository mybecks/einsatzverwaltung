<?php
/*
Plugin Name: Einsatzverwaltung 2.0
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Einsatzverwaltung der FF Langenbruecken
Version: 0.49
Author: Andre Becker
Author URI: la.ffbs.de
License: GPL2
*/

//global $db_version;
//$db_version = "1.0";
// next to come: https://github.com/jkudish/WordPress-GitHub-Plugin-Updater

include 'einsatzverwaltung_admin.php';
include 'einsatzverwaltung_widget.php';
// include_once 'updater.php';

// Settings for Automatic Github Updates
// if (is_admin()) { // note the use of is_admin() to double check that this is happening in the admin
//     $config = array(
//         'slug' => plugin_basename(__FILE__), // this is the slug of your plugin
//         'proper_folder_name' => 'einsatzverwaltung', // this is the name of the folder your plugin lives in
//         'api_url' => 'https://api.github.com/repos/mybecks/einsatzverwaltung', // the github API url of your github repo
//         'raw_url' => 'https://raw.github.com/mybecks/einsatzverwaltung/master', // the github raw url of your github repo
//         'github_url' => 'https://github.com/mybecks/einsatzverwaltung', // the github url of your github repo
//         'zip_url' => 'https://github.com/mybecks/einsatzverwaltung/zipball/master', // the zip url of the github repo
//         'sslverify' => true, // wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
//         'requires' => '3.0', // which version of WordPress does your plugin require?
//         'tested' => '3.3', // which version of WordPress is your plugin tested up to?
//         'readme' => 'README.md', // which file to use as the readme for the version number
//         'access_token' => '' // Access private repositories by authorizing under Appearance > Github Updates when this example plugin is installed
//     );
//     new WP_GitHub_Updater($config);
// }



// Aktuelles Jahr
define ("CURRENT_YEAR" , date("Y"));
define ('CATEGORY', get_option("einsatzverwaltung_settings_option_category_id")); 
define ('MISSION_ID', 'mission_id');

wp_enqueue_script( 'jquery-ui-autocomplete', '', array('jquery-ui-widget', 'jquery-ui-position'), '1.8.6' );
wp_enqueue_script( 'einsatzverwaltung-responsive-table-script', plugins_url( '/js/responsive-tables.js', __FILE__ ));

register_activation_hook(__FILE__,'einsatzverwaltung_install');

/**
 * Add Custom Stylesheet for Plugin
 * 
 * @author Andre Becker
 **/
function einsatzverwaltung_add_stylesheet() {
        // Respects SSL, Style.css is relative to the current file
	// $url = plugins_url( '/css/styles.css', __FILE__ );
	// wp_die("url: ".$url);
        wp_register_style( 'einsatzverwaltung-style', plugins_url( '/css/styles.css', __FILE__ ) );
        wp_enqueue_style( 'einsatzverwaltung-style' );

        wp_register_style( 'einsatzverwaltung-responsive-table-style', plugins_url( '/css/responsive-tables.css', __FILE__ ) );
        wp_enqueue_style( 'einsatzverwaltung-responsive-table-style' );

}
add_action( 'wp_enqueue_scripts', 'einsatzverwaltung_add_stylesheet' );


// function einsatzverwaltung_add_script(){
// 	wp_register_script( 'einsatzverwaltung-responsive-table-script', plugins_url( '/js/responsive-tables.js', __FILE__ ) );
// 	wp_enqueue_scripts( 'einsatzverwaltung-responsive-table-script' );
// }
// add_action( 'wp_enqueue_scripts', 'einsatzverwaltung_add_script' );
/**
 * Display Missions using [einsatzverwaltung] shortcode
 * 
 * @author Andre Becker
 **/
function my_einsatzverwaltung_handler( $atts, $content=null, $code="" ) {
	
	//code 4 displaying 
   display_missions();
}

add_shortcode( 'einsatzverwaltung', 'my_einsatzverwaltung_handler' );

add_action( 'add_meta_boxes', 'einsatzverwaltung_add_custom_box' );
add_action( 'publish_post', 'einsatzverwaltung_save_postdata' );
add_action(	'trash_post', 'einsatzverwaltung_trash_mission' );
add_action( 'admin_init', 'einsatzverwaltung_admin_init' );
add_action( 'admin_menu', 'einsatzverwaltung_admin_menu' );
add_action( 'widgets_init', 'einsatzverwaltung_widget_init' );
   
/**
 * Display Mission Details Box
 * 
 * @author Andre Becker
 **/
function show_einsatzverwaltung_box() {
    if ( current_user_can( 'publish_posts' ) ) {
        $script = "
			<script type='text/javascript'>
			    jQuery(document).ready(function($) {
			        $('#einsatzverwaltung_sectionid').hide();
			        $('#in-category-".CATEGORY."').is(':checked') ? $('#einsatzverwaltung_sectionid').show() : $('#einsatzverwaltung_sectionid').hide();
			        $('#in-category-".CATEGORY."').click(function() {
			            $('#einsatzverwaltung_sectionid').toggle(this.checked);
			        });
			    });
			</script>";
        echo $script;
    }
}
add_action( 'admin_footer', 'show_einsatzverwaltung_box');

/**
 * Add Custom Box to Category
 * 
 * @author Andre Becker
 **/
function einsatzverwaltung_add_custom_box() {
    add_meta_box( 
        'einsatzverwaltung_sectionid',
        __( 'Einsatzverwaltung', 'einsatzverwaltung_textdomain' ),
        'einsatzverwaltung_inner_custom_box',
        'post' 
    );
}

/**
 * Display Mission Details Input Form 
 * 
 * @author Andre Becker
 **/
function einsatzverwaltung_inner_custom_box( $post ) {
	global $post;
  	// Use nonce for verification
  	wp_nonce_field( plugin_basename( __FILE__ ), 'einsatzverwaltung_noncename' );
  
  	$meta_values = get_post_meta($post->ID, MISSION_ID, '');  
  	$meta_values = array_filter($meta_values);
  	
  	if(!empty($meta_values)){
  		$mission = einsatzverwaltung_load_mission_by_id($meta_values[0]);
  		$vehicles_by_mission = einsatzverwaltung_load_vehicles_by_mission_id($mission->id);
		$vehicles = einsatzverwaltung_load_vehicles();
  	}else{
  		$vehicles = einsatzverwaltung_load_vehicles();
  	}

	if(strlen($mission->art_alarmierung) != 0)
	{
		// http://wpquicktips.wordpress.com/2012/04/25/using-php-variables-in-javascript-with-wp_localize_script/
		// http://www.ronakg.com/2011/05/passing-php-array-to-javascript-using-wp_localize_script/
		set_selector_for_dropdown_value("#alarm_art", $mission->art_alarmierung);
	}
	if(strlen($mission->alarmstichwort) != 0)
	{
		set_selector_for_dropdown_value("#alarm_stichwort", $mission->alarmstichwort);
	}

	if(strlen($mission->alarm_art) != 0)
	{
		set_selector_for_dropdown_value("#alarm", $mission->alarm_art);
	}

	if(count($vehicles_by_mission) != 0)
	{
		for ($i=0; $i < count($vehicles_by_mission); $i++) 
		{
		 	$name = rename_db_vehicle_name($vehicles_by_mission[$i]->description);
		 	set_selector_for_checkbox_value($name);
		}
	}


  	$script = <<< EOF
<script type='text/javascript'>
    jQuery(document).ready(function($) {
        $('#row_freitext_alarmstichwort').hide();
            
           // $('select').change(function() {
        $('#alarm_stichwort').change(function() {
         if($('#sel_so_brand').is(':selected') || $('#sel_freitext').is(':selected')){
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
			"Odenheim"];
	        
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
						_e("Einsatz Nr.", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input id="mission_id" name="mission_id" value="'.$mission->id.'" readonly="true" size="4"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="alarm_art">';
						_e("Art der Alarmierung", 'einsatzverwaltung_textdomain' );
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
						_e("Alarmstichwort", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<select id="alarm_stichwort" name="alarm_stichwort">';
	echo '   			<option>Brandmeldealarm</option>';
	echo '   			<option>Verkehrsunfall</option>';
	echo '   			<option>Ölspur</option>';
	echo '   			<option>Absperrmaßnahme</option>';
	echo '   			<option>Dachstuhlbrand</option>';
	echo '   			<option>Wohnungsbrand</option>';
	echo '   			<option>Zimmerbrand</option>';
	echo '   			<option>Kellerbrand</option>';
	echo '   			<option id="sel_so_brand">Sonstiger Brand</option>';
	echo '   			<option>PKW-Brand</option>';
	echo '   			<option>Person in Not</option>';
	echo '   			<option>Wasserschaden</option>';
	echo '   			<option>Drehleitereinsatz</option>';
	echo '   			<option>Sicherheitsdienst</option>';
	echo '   			<option>Feuerschein</option>';
	echo '   			<option>Unterstützung Rettungsdienst</option>';
	echo '  			<option id="sel_freitext">Freitext</option>';
	echo '  		</select>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr id="row_freitext_alarmstichwort">';
	echo '		<td>';
	echo '			<label for="alarmstichwort_freitext">';
						_e("Alarmstichwort (Freitext)", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	if(($mission->alarmstichwort == "Freitext") || ($mission->alarmstichwort == "Sonstiger Brand"))
	{
			echo '			<input name="alarmstichwort_freitext" value="'.$mission->freitext.'"/>';
	}
	else
	{
			echo '			<input name="alarmstichwort_freitext" />';
	}
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="alarm">';
						_e("Alarm", 'einsatzverwaltung_textdomain' );
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
						_e("Einsatzort", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input id="einsatzort" name="einsatzort" value="'.$mission->einsatzort.'"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="alarmierung_datum">';
						_e("Alarmierung (Datum)", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input id="alarm_date" name="alarmierung_datum" type="date" value="'.$mission->alarmierung_date.'"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="alarmierung_zeit">';
						_e("Alarmierung (Uhrzeit)", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input name="alarmierung_zeit" type="time" value="'.$mission->alarmierung_time.'"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="rueckkehr_datum">';
						_e("R&uuml;ckkehr (Datum)", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input id="alarm_end_date" name="rueckkehr_datum" type="date" value="'.$mission->rueckkehr_date.'"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="rueckkehr_zeit">';
						_e("R&uuml;ckkehr (Uhrzeit)", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input name="rueckkehr_zeit" type="time" value="'.$mission->rueckkehr_time.'"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="link_zu_medien">';
						_e("Link zu weiterf&uuml;hrenden Medien", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input name="link_zu_medien" type="url" value="'.$mission->link_to_media.'" size="50"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="fahrzeuge">';
						_e("Eingesetzte Fahrzeuge", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	if(count($vehicles) > 0)
	{
		for($i=0; $i<count($vehicles); $i++)
		{
			$name = rename_db_vehicle_name($vehicles[$i]->description);
			echo '			<label for="'.$name.'"> <input name="'.$name.'" type="checkbox"/> '.$vehicles[$i]->description.' </label>';
		}	
	}else {
		echo '<p>';
			_e("Keine Fahrzeuge in der Datenbank gefunden!", 'einsatzverwaltung_textdomain' );
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
 **/
/* When the post is saved, saves our custom data */
function einsatzverwaltung_save_postdata( $post_id ) {
	global $wpdb;

	$table_name_missions = 				$wpdb->prefix . "einsaetze";
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
  	if ( 'page' == $_POST['post_type'] ) 
  	{
    	if ( !current_user_can( 'edit_page', $post_id ) )
        	return;
  	}
  	else
  	{
    	if ( !current_user_can( 'edit_post', $post_id ) )
        	return;
  	}

  	$cat_id = get_the_category($post_id);

  	//Check if mission category
	if( CATEGORY != $cat_id[0]->cat_ID )
		return;

  	// OK, we're authenticated: we need to find and save the data

	$mission_id = $_POST['mission_id'];

	$alarm_art = $_POST['alarm_art'];

	if(($_POST['alarm_stichwort'] == "Freitext") || ($_POST['alarm_stichwort'] == "Sonstiger Brand"))
		$freitext = $_POST['alarmstichwort_freitext'];
	else
		$freitext = "";

	$alarm = $_POST['alarm'];
	$einsatzort = $_POST['einsatzort'];
	$alarm_stichwort = $_POST['alarm_stichwort'];
	$alarmierung_datum = $_POST['alarmierung_datum'];
	$alarmierung_zeit = $_POST['alarmierung_zeit'];
	$rueckkehr_datum = $_POST['rueckkehr_datum'];
	$rueckkehr_zeit = $_POST['rueckkehr_zeit'];
	$link_zu_medien = $_POST['link_zu_medien'];

	$db_vehicles = einsatzverwaltung_load_vehicles();
	$vehicles = array();

	for($i=0; $i<count($db_vehicles); $i++){
		$name = rename_db_vehicle_name($db_vehicles[$i]->description);

		if(isset($_POST[$name]))
			$vehicles[] = $db_vehicles[$i]->id;
	}

	if(!empty($mission_id))
	{
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

		//loop for all vehicles
		//remove all vehicles bound to current mission!
		$query = "DELETE FROM ". $table_name_missions_has_vehicles ." WHERE einsaetze_id = ".$mission_id;

		//fire delete query!
		$delete = $wpdb->query($query);

		//insert new values:
		foreach($vehicles as $vehicle){
			$wpdb->insert( 
				$table_name_missions_has_vehicles, 
				array( 
					'einsaetze_id' => $mission_id, 
					'fahrzeuge_id' => $vehicle
					), array());
		}

	}else{
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
				), array());

		$id = $wpdb->insert_id;

		foreach($vehicles as $vehicle){
			$wpdb->insert( 
				$table_name_missions_has_vehicles, 
				array( 
					'einsaetze_id' => $id, 
					'fahrzeuge_id' => $vehicle
					), array());
		}

		add_post_meta($post_id, MISSION_ID, $id);
	}
}

/**
 * 
 * 
 * @author Andre Becker
 **/
function set_selector_for_dropdown_value($id, $value){
	$script = "
	<script type='text/javascript'>
	 jQuery(document).ready(function($) {
		$('".$id."').val('".$value."');
	});
	</script>";
	echo $script;
}

/**
 * 
 * 
 * @author Andre Becker
 **/
function set_selector_for_checkbox_value($value){
	$script = "
	<script type='text/javascript'>
	 jQuery(document).ready(function($) {
		$('input[name=".$value."]').attr('checked', true);
	});
	</script>";
	echo $script;
}

/**
 * 
 * 
 * @author Andre Becker
 **/
function rename_db_vehicle_name($name){

	if($name == "DLK 23/12")
		$name = "dlk";

	$cleaned_name = str_replace(' ','',$name);
	$name = strtolower($cleaned_name);

	return "fahrzeuge_".$name;
}

/**
 * Delete mission data of current post
 * 
 * @return boolean
 * @author Andre Becker
 **/
function einsatzverwaltung_trash_mission($post_id) {
  // global $wpdb;

	if( current_user_can( 'delete_posts' ) )
		$i = 0;
  // wp_die("Post ID: ".$pid);

  // if ($wpdb->get_var($wpdb->prepare('SELECT post_id FROM codex_postmeta WHERE post_id = %d', $pid))) {
  //   return $wpdb->query($wpdb->prepare('DELETE FROM codex_postmeta WHERE post_id = %d', $pid));
  // }
  // return true;
}


/*
 * DB Setup
 */


/**
 * Create Tables on Plugin Installation
 * 
 * @author Andre Becker
 **/
function einsatzverwaltung_install(){
	global $wpdb;
	
   	$table_name_vehicles = 				$wpdb->prefix . "fahrzeuge"; 
   	$table_name_missions = 				$wpdb->prefix . "einsaetze";
   	$table_name_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";
   	$table_name_wp_posts =				$wpdb->prefix . "posts";
	
   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   
   /*
   	* SQL Create Tables
   	* 
   	* No Foreign Keys: http://wordpress.stackexchange.com/questions/52783/dbdelta-support-for-foreign-key
   	*/

	$sql_vehicles = "CREATE TABLE IF NOT EXISTS $table_name_vehicles 
	(
		id 					INT UNSIGNED NOT NULL AUTO_INCREMENT,
	  	description 		VARCHAR(25) NOT NULL,
	  	PRIMARY KEY  (id)
	)
	CHARACTER SET utf8 
	COLLATE utf8_general_ci;	
	";
	dbDelta($sql_vehicles);

	$sql_missions = "CREATE TABLE IF NOT EXISTS $table_name_missions 
	(
  		id 					INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  		art_alarmierung 	VARCHAR(25) NOT NULL ,
  		alarmstichwort 		VARCHAR(125) NOT NULL ,
  		freitext 			VARCHAR(125) NULL ,
  		alarm_art 			VARCHAR(45) NOT NULL ,
  		einsatzort 			VARCHAR(45) NOT NULL ,
  		alarmierung_date 	DATE NOT NULL ,
  		alarmierung_time 	TIME NOT NULL ,
  		rueckkehr_date 		DATE NULL ,
  		rueckkehr_time 		VARCHAR(45) NULL ,
  		link_to_media 		VARCHAR(255) NULL ,
  		wp_posts_ID 		INT UNSIGNED NOT NULL ,
  		PRIMARY KEY  (id)
  	)
  	CHARACTER SET utf8 
  	COLLATE utf8_general_ci;
	";
	// KEY fk_einsaetze_wp_posts1 (wp_posts_ID ASC),
	// CONSTRAINT fk_einsaetze_wp_posts1
	//    	FOREIGN KEY (wp_posts_ID)
	//    	REFERENCES 	$table_name_wp_posts (ID)
	//    	ON DELETE NO ACTION
	//    	ON UPDATE NO ACTION 

	dbDelta($sql_missions);

	$sql_missions_has_vehicles = "CREATE TABLE IF NOT EXISTS $table_name_missions_has_vehicles
	(
		einsaetze_id 		INT NOT NULL ,
  		fahrzeuge_id 		INT NOT NULL ,
  		PRIMARY KEY  (einsaetze_id, fahrzeuge_id)
	)
	CHARACTER SET utf8 
	COLLATE utf8_general_ci;
	";
	// KEY fk_einsaetze_has_fahrzeuge_fahrzeuge1 (fahrzeuge_id ASC) ,
	// KEY fk_einsaetze_has_fahrzeuge_einsaetze (einsaetze_id ASC) ,
	//   	CONSTRAINT fk_einsaetze_has_fahrzeuge_einsaetze
	//     	FOREIGN KEY  (einsaetze_id)
	//     	REFERENCES $table_name_missions (id)
	//     	ON DELETE NO ACTION
	//     	ON UPDATE NO ACTION,
	//   	CONSTRAINT fk_einsaetze_has_fahrzeuge_fahrzeuge1
	//     	FOREIGN KEY  (fahrzeuge_id)
	//     	REFERENCES $table_name_vehicles (id)
	//     	ON DELETE NO ACTION
	//     	ON UPDATE NO ACTION
	dbDelta($sql_missions_has_vehicles);
}

/*
 *End DB Setup
 */






/*
 * Begin DB Access
 */

/**
 * Load all vehicles
 *
 * @return array()
 * @author Andre Becker
 **/
function einsatzverwaltung_load_vehicles()
{
	global $wpdb;
	$table_name_vehicles =	$wpdb->prefix . "fahrzeuge";
	
	$query = "SELECT id, description FROM ". $table_name_vehicles;
	$vehicles = $wpdb->get_results($query);


	return $vehicles;
}

/**
 * Load missions by mission_id
 *
 * @param $id
 * @return array()
 * @author Andre Becker
 **/
function einsatzverwaltung_load_mission_by_id( $id )
{
	global $wpdb;
	$table_name_missions = $wpdb->prefix . "einsaetze";

	$query = "SELECT * FROM ". $table_name_missions ." WHERE id = ".$id;
	$mission_details = $wpdb->get_row($query);
	

	return $mission_details;
}

/**
 * Load vehicles bound to mission
 *
 * @param $mission_id
 * @return array()
 * @author Andre Becker
 **/
function einsatzverwaltung_load_vehicles_by_mission_id( $mission_id )
{
	global $wpdb;
	$table_name_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";
	$table_name_vehicles = 				$wpdb->prefix . "fahrzeuge";
	
	$query = "SELECT f.description FROM ". $table_name_vehicles ." as f, ". $table_name_missions_has_vehicles ." as h WHERE f.id = h.fahrzeuge_id AND h.einsaetze_id = ".$mission_id;

	$vehicles = $wpdb->get_results($query);

	return $vehicles;
}

/**
 * Load missions bound to post id
 *
 * @param post id
 * @return single mission
 * @author Andre Becker
 **/
function einsatzverwaltung_load_mission_by_post_id( $id )
{
	global $wpdb;
	$table_name_missions = $wpdb->prefix . "einsaetze";

	$query = "SELECT * FROM ". $table_name_missions ." WHERE wp_posts_ID = ".$id;
	$mission_details = $wpdb->get_row($query);
	

	return $mission_details;
}

/*
 * End DB Access
 */

/*
 * Display Missions on Page
 */

/**
 * Einsätze nach Jahr anzeigen
 *
 * @author Andre Becker
 **/
function display_missions() {

	$selected_year = $_POST['einsatzjahr'];
	$permalink = get_permalink();
	$years = get_mission_years();
	
	echo "<div>";
	echo "<form action=\"$permalink\" method=\"post\">";
	echo "<table>";
	echo "<tr>Gewähltes Einsatzjahr:&nbsp;</tr>";
	echo "<tr><select name=\"einsatzjahr\">";
	
	foreach($years AS $year) {
		echo "	<option value=\"".$year."\">".$year."</option>";
	}
	
	echo "</select>";
	echo "<input type=\"submit\" value=\"Anzeigen\" />";
	echo "</form></tr></table></div>";

	if(!isset($selected_year)) {
		$missions = get_missions_by_year(CURRENT_YEAR);
	}	
	else {
		$missions = get_missions_by_year($selected_year);
	}
	print_missions_month_overview($missions);
	print_missions_by_year($missions);
}


/**
 * Returns missions grouped by month for current year.
 *
 * @return array() 
 * @author Andre Becker
 **/
function print_missions_by_year($arr_months){
	// Pfade
	$arrow_up_path = plugin_dir_url( __FILE__ )."img/mini-nav-top.gif";

	// Ausgabe der Einsätze im aktuellen Jahr
	foreach($arr_months as $key => $value) {
		
		$german_month = get_german_month($key);
		$count = count($arr_months[$key]);
		
		echo "<br /> <div>
		<a name='$german_month'></a>

		<table class='mission-month' summary='Einsatzliste im Monat $german_month' border='0'>
			<caption class='mission-month-header'>".$german_month."&nbsp;<a href='#Übersicht'><img src='$arrow_up_path' class='overview'/></a></caption>
			<thead>
				<tr>
					<th scope='col' class='th-mission'>Datum</th>
					<th scope='col' class='th-mission'>Zeit</th>
					<th scope='col' class='th-mission'>Art</th>
					<th scope='col' class='th-mission'>Alarmstichwort</th>
					<th scope='col' class='th-mission'>Einsatzort</th>
					<th scope='col' class='th-mission'>Bericht</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan='6'>Anzahl der Eins&auml;tze im Monat: <b>".$count."</b></td>
				</tr>
			</tfoot>";
		
	
		foreach($arr_months[$key] as $key => $value) {
			echo "
				<tbody>	
				<tr class='row'>
					<td>$value[4]</td>
					<td>$value[5]</td>
					<td class='td-text-center'>$value[0]</td>
					<td>$value[1]</td>
					<td>$value[3]</td>
					<td><a href=\"".$value[10]."\">$value[9]</a></td>
				</tr>
				</tbody>";
		}
		echo "
			</table>
			<div class='footer-legend'>
				BE - Brandeinsatz &#x95
				TE - Technischer Einsatz &#x95
				SE - Sonstiger Einsatz
			</div>
			</div>";
		}
}


/**
 * Returns array with all mission years
 *
 * @return array() 
 * @author Andre Becker
 **/
function get_mission_years() {
	global $wpdb;
	$array = array();
	$table_name_missions = $wpdb->prefix . "einsaetze";
	
	$query = "SELECT YEAR(alarmierung_date) AS Year FROM ".$table_name_missions." GROUP BY Year DESC";

	$years = $wpdb->get_results($query);
		
	foreach($years as $year){
		if($year->Year != 1970)
			$array[] = $year->Year;
	}
	
	return $array;
}

/**
 * Transfers the english months to german
 *
 * @return array() 
 * @author Florian Wallburg
 **/
function get_german_month($english_month_2number) {
	$german_months = array(1=>"Januar",
		2=>"Februar",
		3=>"M&auml;rz",
		4=>"April",
		5=>"Mai",
		6=>"Juni",
		7=>"Juli",
		8=>"August",
		9=>"September",
		10=>"Oktober",
		11=>"November",
		12=>"Dezember");
	$english_month_2number = ltrim($english_month_2number, "0");
	
	return $german_months[$english_month_2number];
}

/**
 * Einsätze nach Jahr sammeln
 * 
 * @return array() 
 * @author Andre Becker
 **/
function get_missions_by_year($year) {
	global $wpdb;
	$table_name_missions = $wpdb->prefix . "einsaetze";
	
	$arr_months = array();

	$query = "SELECT id, art_alarmierung, alarmstichwort, alarm_art, einsatzort, alarmierung_date, alarmierung_time, rueckkehr_date, rueckkehr_time, link_to_media, wp_posts_ID, MONTH(alarmierung_date) AS Month, freitext ".
			 "FROM ".$table_name_missions.
			 " WHERE YEAR(alarmierung_date) = ".$year.
			 " ORDER BY alarmierung_date DESC, alarmierung_time DESC";

	// $missions = $wpdb->get_results( 
	// "
	// SELECT id, art_alarmierung, alarmstichwort, alarm_art, einsatzort, alarmierung_date, alarmierung_time, rueckkehr_date, rueckkehr_time, link_to_media, wp_posts_ID, MONTH(alarmierung_date) as Month, freitext 
	// FROM $table_name_missions
	// WHERE YEAR(alarmierung_date) = $year
	// ORDER BY alarmierung_date DESC, alarmierung_time DESC
	// "
	// );
	$missions = $wpdb->get_results($query);
		
	foreach($missions as $mission){

		if(!is_array($arr_months[$mission->Month])) {
			$arr_months[$mission->Month] = array();
		}
		
		foreach($arr_months as $key => $value) {
					
			
			if($key == $mission->Month) {
				$tmp_arr = $arr_months[$key];
				
				$arr_content = array();
				
				$post = wp_get_single_post( $mission->wp_posts_ID);
				
				
				if(strlen($post->post_content)!=0) {
					$description = "Bericht";
				}
				else{
					$description = "Kurzinfo";
				}
			

				if('Freitext' == $mission->alarmstichwort || 'Sonstiger Brand' == $mission->alarmstichwort){
					$alarmstichwort = $mission->freitext; 
				}else{
					$alarmstichwort = $mission->alarmstichwort;
				}
		
				// if(strlen($alarmstichwort) > 22) {
				// 	// Shortening the string to 22 characters
				// 	$alarmstichwort = substr($alarmstichwort,0,22)."…";
				// }


				if(strpos($mission->art_alarmierung,'Brandeinsatz') !== false){
					$alarm_short = 'BE';
				}else if(strpos($mission->art_alarmierung,'Technischer Einsatz') !== false){
					$alarm_short = 'TE';
				}else{
					$alarm_short = 'SE';
				}

				$arr_content[0] = $alarm_short;
				$arr_content[1] = $alarmstichwort;
				$arr_content[2] = $mission->alarm_art;
				$arr_content[3] = $mission->einsatzort;
				$arr_content[4] = strftime("%d.%m.%Y", strtotime($mission->alarmierung_date));
				$arr_content[5] = strftime("%H:%M", strtotime($mission->alarmierung_time));
				$arr_content[6] = $mission->rueckkehr_date;
				$arr_content[7] = $mission->rueckkehr_time;
				$arr_content[8] = $mission->link_to_media;
				$arr_content[9] = $description;
				$arr_content[10] = get_permalink($mission->wp_posts_ID);
						
				array_push($tmp_arr,$arr_content);
				
				$arr_months[$key] = $tmp_arr;
			}
		}
	}

	// Umgekehrte Sortierung der Monate (12,11,10,...,1)
	krsort($arr_months);
	
	return $arr_months;
}

/**
 * Print overview of missions grouped by month
 * 
 * @author Florian Wallburg, Andre Becker
 **/
function print_missions_month_overview($arr_months){
	// START Attributes
	$mission_year = $_POST['einsatzjahr'];
	if($mission_year == '')
		$mission_year = CURRENT_YEAR;
	
	$mission_year_count = 0;
	
	foreach($arr_months as $key => $value) {
		foreach($arr_months[$key] as $key => $value) {
			$mission_year_count++;
		}
	}
	
	
	echo '<a name="Übersicht"></a>
		<div>
			<table class="mission-month-overview" summary="Übersicht über die Anzahl der Einsätze im Jahr '.$mission_year.'">
			<caption>Monatsübersicht für '.$mission_year.'</caption>
			<thead>
				<tr>
					<th class="th-mission">Monat</th>
					<th class="th-mission-center">Einsätze</th>
					<th class="th-mission-center">BE/TE/SE</th>
					<th class="th-mission-center">Übersicht</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="5">Anzahl der Einsätze im Jahr: <b>'.$mission_year_count.'</b></td>
				</tr>
			</tfoot>
			<tbody>';
	
	foreach($arr_months as $key => $value) {
		// START Amount of missions in the month
		$count_missions_in_month = count($arr_months[$key]);
		// END
		
		// START Ratio of false alarms and real missions
		$count_brandeinsatz = 0;
		$count_technischereinsatz = 0;
		$count_sonstiges = 0;
		
		foreach($value as $mission_key => $mission_value) {
			

			if(strpos($mission_value[0],'BE') !== false) {
				$count_brandeinsatz++;
			}
			elseif(strpos($mission_value[0],'TE') !== false) {
				$count_technischereinsatz++;
			}
			else {
				$count_sonstiges++;
			}
		}	
		
		// OUTPUT
		$german_month = get_german_month($key);
		echo '
			<tr>
				<td>'.$german_month.'</td>
				<td class="td-text-center">'.$count_missions_in_month.'</td>
				<td class="td-text-center">'.$count_brandeinsatz.'/'.$count_technischereinsatz.'/'.$count_sonstiges.'</td>
				<td class="td-text-center"><a href="#'.$german_month.'">Link</a></td>
			</tr>';
	}
	
	echo '</tbody></table></div>';
}







/*
 * Begin Postinfo
 */

/**
 * Add JavaScript for postinfo to the footer
 * 
 * @author Florian Wallburg
 **/
function postinfo_head() {
	global $post;

	$cat_id = get_the_category($post->ID);

  	//Check if mission category
	if( CATEGORY != $cat_id[0]->cat_ID )
		return;

	$script = <<< EOF
<script type='text/javascript'>
    jQuery(document).ready(function($){
    	$('.post-info').prependTo('.entry-content');
		$('.open-post-info').prependTo('.entry-content');

		$('.post-info').hide();
		$('.open-post-info').click(function() {
			var id = $(this).attr('id');
        	
        	$('.post-info-' + id).slideToggle("medium", function() {
            	$(this).prev().toggleClass("toggled");
        	}); 
			
			return false;
		});	
	});       
</script>
EOF;
    echo $script;
    postinfo();
}

add_action('wp_footer', 'postinfo_head');


/**
 * Ausgabe der Detailinformationen zu einem Einsatz
 * 
 * @author Florian Wallburg
 **/
function postinfo() {
	global $post;

	$mission = einsatzverwaltung_load_mission_by_post_id($post->ID);
	$vehicles = einsatzverwaltung_load_vehicles_by_mission_id($mission->id);

	$used_vehicles = "";

	for($i=0; $i<count($vehicles); $i++)
	{
		$used_vehicles .= $vehicles[$i]->description." ";
	}

	if(($mission->alarmstichwort == "Freitext") || ($mission->alarmstichwort == "Sonstiger Brand"))
	{
		$alarmstichwort = $mission->freitext;
	}
	else
	{
		$alarmstichwort = $mission->alarmstichwort;
	}


	echo '<p class="open-post-info" id="'. $post->post_name .'">Details</p>';
	echo '<div class="post-info post-info-'. $post->post_name .'">';
	echo '<ul>';
	echo 	'<li class="alarmstichwort">';
	echo 		"<b>Alarmstichwort:</b> ".$alarmstichwort;
	echo 	'</li>';
	echo 	'<li class="art_der_alarmierung">';
	echo 		"<b>Art der Alarmierung:</b> ".$mission->art_alarmierung;
	echo 	'</li>';
	echo 	'<li class="alarmierung">';
	echo 		"<b>Alarmierung:</b> ".strftime("%d.%m.%Y", strtotime($mission->alarmierung_date))." ".strftime("%H:%M", strtotime($mission->alarmierung_time));
	echo 	'</li>';
	echo 	'<li class="rueckkehr">';
	echo 		"<b>R&uuml;ckkehr:</b> ".strftime("%d.%m.%Y", strtotime($mission->rueckkehr_date))." ".strftime("%H:%M", strtotime($mission->rueckkehr_time));
	echo 	'</li>';
	echo 	'<li class="einsatzort">';
	echo 		"<b>Einsatzort:</b> ".$mission->einsatzort;
	echo 	'</li>';
	echo 	'<li class="eingesetzte_fahrzeuge">';
	echo 		"<b>Eingesetzte Fahrzeuge:</b> ".$used_vehicles;
	echo 	'</li>';
	echo 	'<li class="link">';
	if(empty($mission->link_to_media)){
		echo "<b>Quelle:</b> "."Nicht verf&uuml;gbar";
	}
	else {
		echo "<b>Quelle:</b> <a href='$link' target='_blank'>".$mission->link_to_media."</a>";
	}
	echo 	'</li>';
	echo '</ul>';
	echo '</div>';
}
/*
 * End Postinfo
 */

?>