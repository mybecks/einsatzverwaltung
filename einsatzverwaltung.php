<?php
/*
Plugin Name: Einsatzverwaltung 2.0
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Einsatzverwaltung der FF Langenbruecken
Version: 0.4 beta
Author: Andre Becker
Author URI: la.ffbs.de
License: GPL2
*/

//global $db_version;
//$db_version = "1.0";

// Aktuelles Jahr
define ("CURRENT_YEAR" , date("Y"));
define ('CATEGORY', 3);
define ('MISSION_ID', 'mission_id');

wp_enqueue_script('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js', array('jquery'), '1.8.6');
// wp_enqueue_script('data_transfer');



register_activation_hook(__FILE__,'einsatzverwaltung_install');

function my_einsatzverwaltung_handler( $atts, $content=null, $code="" ) {
	
	//code 4 displaying 
	
   //print("Hello World");
   getMissions();
}
add_shortcode( 'einsatzverwaltung', 'my_einsatzverwaltung_handler' );

add_action( 'admin_menu', 'einsatzverwaltung_menu');
add_action( 'add_meta_boxes', 'einsatzverwaltung_add_custom_box' );
add_action( 'publish_post', 'einsatzverwaltung_save_postdata' );

function show_einsatzverwaltung_box() {
    if ( is_admin() ) {
        $script = <<< EOF
<script type='text/javascript'>
    jQuery(document).ready(function($) {
        $('#einsatzverwaltung_sectionid').hide();
        $('#in-category-3').is(':checked') ? $("#einsatzverwaltung_sectionid").show() : $("#einsatzverwaltung_sectionid").hide();
        $('#in-category-3').click(function() {
            $("#einsatzverwaltung_sectionid").toggle(this.checked);
        });
    });
</script>
EOF;
        echo $script;
    }
}
add_action( 'admin_footer', 'show_einsatzverwaltung_box');


function einsatzverwaltung_add_custom_box() {
    add_meta_box( 
        'einsatzverwaltung_sectionid',
        __( 'Einsatzverwaltung', 'einsatzverwaltung_textdomain' ),
        'einsatzverwaltung_inner_custom_box',
        'post' 
    );
}

/* Prints the box content */
function einsatzverwaltung_inner_custom_box( $post ) {
	global $post;
  	// Use nonce for verification
  	wp_nonce_field( plugin_basename( __FILE__ ), 'einsatzverwaltung_noncename' );
  
  	// print("Post ID: ".$post->ID. "<br />");
  	$meta_values = get_post_meta($post->ID, MISSION_ID, '');  
	$mission = einsatzverwaltung_load_missions_by_id($meta_values[0]);

//Ugly Workaround
if(strlen($mission->art_alarmierung) != 0)
{
	// wp_localize_script('data_transfer', 'js_alarm_art', $mission->art_alarmierung);
	// http://wpquicktips.wordpress.com/2012/04/25/using-php-variables-in-javascript-with-wp_localize_script/
	// http://www.ronakg.com/2011/05/passing-php-array-to-javascript-using-wp_localize_script/
	// $("#alarm_art").val($mission->art_alarmierung);
	print("Alarm Art: ".$mission->art_alarmierung."<br />");

	if($mission->art_alarmierung == "Technischer Einsatz")
	{
		$selected = 'selected="selected"';
	}
	else
	{
		$selected = '';
	}

		if($mission->art_alarmierung == "Brandeinsatz")
	{
		$selectedA = 'selected="selected"';
	}
	else
	{
		$selectedA = '';
	}

		if($mission->art_alarmierung == "Sonstiger Einsatz")
	{
		$selectedB = 'selected="selected"';
	}
	else
	{
		$selectedB = '';
	}

}

if(strlen($mission->alarmstichwort) != 0)
{
	print("Alarm Stichwort: ". $mission->alarmstichwort."<br />");
}

if(strlen($mission->alarm_art) != 0)
{
	print("Alarm: ".$mission->alarm_art);
}


  $script = <<< EOF
<script type='text/javascript'>
    jQuery(document).ready(function($) {
        $('#row_freitext_alarmstichwort').hide();
      
            
            
           $('select').change(function() {
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
			"Kirrlach"];
	        
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
	echo '			<select name="alarm_art">';
	echo '  			<option '.$selectedA.'>Brandeinsatz</option>';
	echo '   			<option '.$selected.'>Technischer Einsatz</option>';
	echo '   			<option '.$selectedB.'>Sonstiger Einsatz</option>';
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
	echo '			<select name="alarm_stichwort">';
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
	echo '			<select name="alarm">';
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
						_e("Rückkehr (Datum)", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input id="alarm_end_date" name="rueckkehr_datum" type="date" value="'.$mission->rueckkehr_date.'"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="rueckkehr_zeit">';
						_e("Rückkehr (Uhrzeit)", 'einsatzverwaltung_textdomain' );
	echo '			<label>';
	echo '		</td>';
	echo '		<td>';
	echo '			<input name="rueckkehr_zeit" type="time" value="'.$mission->rueckkehr_time.'"/>';
	echo '		</td>';
	echo '	</tr>';
	echo '	<tr>';
	echo '		<td>';
	echo '			<label for="link_zu_medien">';
						_e("Link zu weiterführenden Medien", 'einsatzverwaltung_textdomain' );
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
	echo '			<label for="fahrzeuge_elw"> <input name="fahrzeuge_elw" type="checkbox"/> ELW 1 </label>';
	echo '			<label for="fahrzeuge_dlk"> <input name="fahrzeuge_dlk" type="checkbox"/> DLK 23/12 </label>';
	echo '			<label for="fahrzeuge_lf16"> <input name="fahrzeuge_lf16" type="checkbox"/> LF 16 </label>';
	echo '			<label for="fahrzeuge_lf10"> <input name="fahrzeuge_lf10" type="checkbox"/> LF 10 </label>';
	echo '			<label for="fahrzeuge_sap11"> <input name="fahrzeuge_sap11" type="checkbox"/> SAP 11 </label>';
	echo '		</td>';
	echo '	</tr>';
	echo '</table>';
  
}

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

  	// OK, we're authenticated: we need to find and save the data

	$mission_id = $_POST['mission_id'];
	// print("mission id: ".$mission_id);
	// die();

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
	$fahrzeuge_elw = $_POST['fahrzeuge_elw'];
	$fahrzeuge_dlk = $_POST['fahrzeuge_dlk'];
	$fahrzeuge_lf16 = $_POST['fahrzeuge_lf16'];
	$fahrzeuge_lf10 = $_POST['fahrzeuge_lf10'];
	$fahrzeuge_sap11 = $_POST['fahrzeuge_sap11'];

	$vehicles = array();

	if(isset($fahrzeuge_elw)){
		$vehicles[] = 1;
	}

	if(isset($fahrzeuge_dlk)){
		$vehicles[] = 2;
	}

	if(isset($fahrzeuge_lf16)){
		$vehicles[] = 3;
	}

	if(isset($fahrzeuge_lf10)){
		$vehicles[] = 4;
	}

	if(isset($fahrzeuge_sap11)){
		$vehicles[] = 5;
	}


	if(!empty($mission_id))
	{
		//Update
		$wpdb->update( 
			$table_name_missions, 
			array( 
				'art_alarmierung' => $alarm_art,
				'alarmstichwort' => $alarm_stichwort,	// integer (number) 
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
		//remove all vehicles bound to current mission! Problem old: 4 vehicles new: 3 vehicles - alogrythom below won't work
		for($i=0; $i<count($vehicles); $i++)
		{
			$wpdb->update( 
				$table_name_missions_has_vehicles, 
				array( 
					'fahrzeuge_id' => $vehicles[$i]				
				), 
				array( 'einsaetze_id' => $mission_id )
			);
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

/*
 * DB Setup
 */
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
	// $wpdb->show_errors;

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

function einsatzverwaltung_load_missions_by_id($id)
{
	global $wpdb;
	$table_name_missions = $wpdb->prefix . "einsaetze";

	$query = "SELECT * FROM ". $table_name_missions ." WHERE id = ".$id;
	$mission_details = $wpdb->get_row($query);
	

	return $mission_details;

	// if ($mission_details != null) {
	// 	// do something with the link 
	// 	return $mission_details;
	// } else {
	// 	// no link found
	// 	return "nothing found";
	// }
}

/*
 * Begin DB Access
 */

/*
 * Begin Admin Menu
 */

//http://codex.wordpress.org/Adding_Administration_Menus

function einsatzverwaltung_menu() {
	// http://codex.wordpress.org/Function_Reference/add_menu_page

	// add_options_page('Einsatzverwaltungs Options', 'Einsatzverwaltung', 'manage_options', 'einsatzverwaltung', 'einsatzverwaltung_admin_options');
	add_menu_page( 'Einsatzverwaltung', 'Einsatzverwaltung', 'administrator', __FILE__, 'einsatzverwaltung_admin_options', '', '76' );
	// add_submenu_page( __FILE__, '', 'Manage Categories','administrator', __FILE__.'_categories_settings', 'my_custom_submenu_test_callback');

}

// should be a seperate file!
function einsatzverwaltung_admin_options() {

	global $wpdb;

	$table_name_vehicles = 				$wpdb->prefix . "fahrzeuge";
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	echo '<div class="wrap">';
	echo '<p>List with vehicles and add-edit-buttons</p>';
	echo '<p>Show table with all vehicles with edit/delete/update mechanism</p>';
	echo '</div>';

	$query = "SELECT id, description FROM ".$table_name_vehicles;

	$vehicles = $wpdb->get_results($query);

	echo '<table border="1">';
	echo '	<tr>';
	echo '		<th>';
	echo '			ID';
	echo '		</th>';
	echo '		<th>';
	echo '			Beschreibung';
	echo '		</th>';
	echo '	</tr>';

	foreach ( $vehicles as $vehicle ) 
	{
		echo '	<tr>';
		echo '		<td>';
		echo 			$vehicle->id;
		echo '		</td>';
		echo '		<td>';
		echo 			$vehicle->description;
		echo '		</td>';
		echo '	</tr>';
	}

	echo '</table>';
	echo '<br />';
	//Form
	echo '<label for="new_vehicle">';
			_e("Neues Fahrzeug hinzufügen", 'einsatzverwaltung_textdomain' );
	echo '<label>';
	echo '<input id="new_vehicle" name="add_new_vehicle"/>';
	echo '<input type="submit" value="add">';

	//logic for adding new vehicle
	//refresh admin
}

// function my_custom_submenu_test_callback(){
// 	echo '
// 		Hello Sub Menu Page
// 		';
// }
/*
 * End Admin Menu
 */

/*
 * Display Missions
 */

/*
* Einsätze nach Jahr anzeigen
*/
function getMissions() {
	//echo get_permalink();
	$selected_year = $_POST['einsatzjahr'];
	$permalink = get_permalink();
	$years = getMissionYears();
	
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
		$missions = getMissionsByYear(CURRENT_YEAR);
	}	
	else {
		$missions = getMissionsByYear($selected_year);
	}
	printMissionsMonthOverview($missions);
	printMissionsByYear($missions);
}


/**
 * Returns missions grouped by month for current year.
 *
 * @return array() 
 * @author Andre Becker
 **/
function printMissionsByYear($arr_months){
	
	// Pfade
$arrow_up_path = get_bloginfo("template_url")."/images/mini-nav-top.gif";
// Ausgabe der Einsätze im aktuellen Jahr
foreach($arr_months as $key => $value) {
	$german_month = getGermanMonth($key);
	echo "<br /> <div>
	<a name='$german_month'></a>
	<table class='mission_month' summary='Einsatzliste im Monat $german_month' border='0'>
	<caption>$german_month&nbsp;<a href='#Übersicht'><img src='$arrow_up_path' class='overview'/></a></caption>
<thead>
<tr>
	<th>Datum</th>
	<th>Uhrzeit</th>
	<th>Art der Alarmierung</th>
	<th>Alarmstichwort</th>
	<th>Einsatzort</th>
	<th>Bericht</th>
</tr>
</thead>";
	$count = count($arr_months[$key]);

	// Sortieren nach dem Datum, umgekehrt, und der Uhrzeit, umgekehrt
//	uasort($arr_months[$key], 'compare_datetime');
	
	foreach($arr_months[$key] as $key => $value) {
		echo "
	<tbody>	
	<tr>
		<td width='5%'>$value[4]</td>
		<td width='5%'>$value[5]</td>
		<td width='30%'>$value[0]</td>
		<td width='40%'>$value[1]</td>
		<td width='15%'>$value[3]</td>
		<td width='5%'><a href=\"".$value[10]."\">$value[9]</a></td>
	</tr>
	</tbody>";
	}
	echo "
<tfoot>
	<tr>
		<td colspan='6'>Anzahl der Eins&auml;tze im Monat: <b>$count</b></td>
	</tr>
</foot>
</table>
</div>";
	}
}


/**
 * Returns array with all mission years
 *
 * @return array() 
 * @author Florian Wallburg
 **/
function getMissionYears() {
//	global $post;
	global $wpdb;
	$array = array();
	$table_name_missions = $wpdb->prefix . "einsaetze";
	
	$years = $wpdb->get_results( 
	"
	SELECT YEAR(alarmierung_date) AS Year 
	FROM $table_name_missions
	GROUP BY Year
	"
	);
		
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
function getGermanMonth($english_month_2number) {
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

/*
* Einsätze nach Jahr sammeln
*/
function getMissionsByYear($year) {
	global $wpdb;
	$table_name_missions = $wpdb->prefix . "einsaetze";
	
	$arr_months = array();	
	$missions = $wpdb->get_results( 
	"
	SELECT id, art_alarmierung, alarmstichwort, alarm_art, einsatzort, alarmierung_date, alarmierung_time, rueckkehr_date, rueckkehr_time, link_to_media, wp_posts_ID, MONTH(alarmierung_date) as Month 
	FROM $table_name_missions
	ORDER BY alarmierung_date DESC, alarmierung_time
	"
	);
	
//	print_r($missions);
	
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
	
	
			if(strlen($mission->alarmstichwort) > 22) {
				// Shortening the string to 22 characters
				$alarmstichwort = substr($mission->alarmstichwort,0,22)."…";
			}
			else
				$alarmstichwort = $mission->alarmstichwort;



//			echo "<br />";
//			echo "<br />";
//			print_r($postle);
//			echo "<br />";
//			echo "############<br />";
//			echo"$postle->post_content";
//			echo "<br />";
//			echo "<br />";
	
			$arr_content[0] = $mission->art_alarmierung;
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
			
//			
//			
//			$arr_content[5] = $description;
			
//			$arr_content[7] = $custom_fields['Alarm'][0];
			
			array_push($tmp_arr,$arr_content);
			
			$arr_months[$key] = $tmp_arr;
			}
		}
		
		
	}
		
//	$monthsUnique = array_unique($tmpArr);
//	
//	foreach($monthsUnique as $var){
//		echo $var;
//	}
//	
//	$arr_months[$monthUnique] = array();
	
	
//	print_r($arr_months);	
	
	
	
	// Umgekehrte Sortierung der Monate (12,11,10,...,1)
	krsort($arr_months);
//	getArtDerAlarmierung($arr_months);
//	wp_reset_query();
	
	return $arr_months;
}


function printMissionsMonthOverview($arr_months){
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
	// END
	
	
	echo '<a name="Übersicht"></a>';
	echo '<div><table class="mission_month_overview" summary="Übersicht über die Anzahl der Einsätze im Jahr '.$mission_year.'"><caption>Monatsübersicht für '.$mission_year.'</caption><tbody>';
	echo '<thead><tr><th>Monat</th><th>Einsätze</th><th>BE/TE/S</th><th>% Keine T&auml;tigkeit</th><th>Übersicht</th></tr></thead>';
	
	foreach($arr_months as $key => $value) {
		// START Amount of missions in the month
		$count_missions_in_month = count($arr_months[$key]);
		// END
		
		// START Ratio of false alarms and real missions
		$count_real_missions = 0;
		$count_false_alarms = 0;
		$count_mistakes =0;
		$count_brandeinsatz =0;
		$count_technischereinsatz =0;
		$count_sonstiges = 0;
		
		foreach($value as $mission_key => $mission_value) {
			
//			print_r($mission_value);
			
			
			//
			if($mission_value[2] == "Einsatzalarm")
				$count_real_missions++;
			elseif($mission_value[2] == "Keine Tätigkeit")
				$count_false_alarms++;
			else
				$count_mistakes++;
			
			//
			if($mission_value[0] == "Brandeinsatz") {
				$count_brandeinsatz++;
			}
			elseif($mission_value[0] == "Technischer Einsatz") {
				$count_technischereinsatz++;
			}
			elseif($mission_value[0] == "Sonstiger Einsatz") {
				$count_sonstiges++;
			}
		}
		// ceil() runded auf, floor() rundet ab
		$ratio = ceil(($count_false_alarms/$count_missions_in_month)*100);
		if($ratio>20)
			$ratio = '<font color=red>'.$ratio.'%</font>';
		else
			$ratio = '<font color=green>'.$ratio.'%</font>';
		// END
		
		
		// OUTPUT
		$german_month = getGermanMonth($key);
		echo '<tr><td>'.$german_month.'</td><td>'.$count_missions_in_month.'</td><td>'.$count_brandeinsatz.'/'.$count_technischereinsatz.'/'.$count_sonstiges.'</td><td>'.$ratio.'</td><td><a href="#'.$german_month.'">Link</a></td></tr>';
	}
	
	echo '</tbody><tfoot><tr><td colspan="5">Anzahl der Einsätze im Jahr: <b>'.$mission_year_count.'</b></td></tr></tfoot></table></div>';
}



/*
 * Begin Postinfo
 */

//function list_mission_details() {	
//	postinfo();	
//}

function postinfo_head() {
	
	global $post;
	
	$script = <<< EOF
<script type='text/javascript'>
    jQuery(document).ready(function($){
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
}
add_action('wp_head', 'postinfo_head');

/*
* Ausgabe der Detailinformationen zu einem Einsatz
*/
function postinfo() {
	global $post;
	global $wpdb;
//$category = get_the_category( $post->ID ); 
//echo $category[0]->cat_ID;


	//filter, dass nur für kategorie einsätze angezeigt wird!
//	if($category[0]->cat_ID == CATEGORY){
		
	echo '<p class="open-post-info" id="'. $post->post_name .'">Details</p>';
	echo '<div class="post-info post-info-'. $post->post_name .'">';
	echo '<ul>';
	echo '<li class="alarm">';
	echo "<b>Alarm:</b> ".$alarm;
	echo '</li>';
	echo '<li class="alarmstichwort">';
	echo "<b>Alarmstichwort:</b> ".$alarmstichwort;
	echo '</li>';
	echo '<li class="art_der_alarmierung">';
	echo "<b>Art der Alarmierung:</b> ".$art_der_alarmierung;
	echo '</li>';
	echo '<li class="alarmierung">';
	echo "<b>Alarmierung:</b> ".$alarmierung_datum." ".$alarmierung_uhrzeit;
	echo '</li>';
	echo '<li class="rueckkehr">';
	echo "<b>R&uuml;ckkehr:</b> ".$rueckkehr_datum." ".$rueckkehr_uhrzeit;
	echo '</li>';
	echo '<li class="einsatzort">';
	echo "<b>Einsatzort:</b> ".$einsatzort;
	echo '</li>';
	echo '<li class="eingesetzte_fahrzeuge">';
	echo "<b>Eingesetzte Fahrzeuge:</b> ".$eingesetzte_fahrzeuge;
	echo '</li>';
	echo '<li class="link">';
	if($link == "Nicht verf&uuml;gbar"){
		echo "<b>Quelle:</b> ".$link;
	}
	else {
		echo "<b>Quelle:</b> <a href='$link' target='_blank'>".$link."</a>";
	}
	echo '</li>';
	echo '</ul>';
	echo '</div>';
	
		echo '<br />';
	echo $post->post_content;
//	}else{
//		echo $post->post_content;
//	}
	

}
//add_filter('the_content', 'postinfo');

?>