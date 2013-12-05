<?php
require('./wp-load.php');
?>
<pre>
<?php
  $wpdb->show_errors(); 
  // require_once(ABSPATH . 'wp-admin/includes/upgrade.php');


// global $user_ID;
global $wpdb;

$table_name_missions =     $wpdb->prefix . "einsaetze";
$table_name_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";

for($i=0; $i<70; $i++){


$new_post = array(
'post_title' => 'Wasserschaden',
'post_content' => '',
'post_status' => 'publish',
'post_date' => date('2013-08-06 22:00'),
'post_date_gmt' => date('2013-08-06 22:00'),
'post_author' => 1,
'post_type' => 'mission',
'comment_status' => 'closed',
'ping_status' => 'closed'
);
$post_id = wp_insert_post($new_post);
print('Inserted post id: '.$post_id.' # ');
$wpdb->insert(
			$table_name_missions,
			array(
				'art_alarmierung' => "Einsatzalarm",
				'alarmstichwort' => "Wasserschaden",
				'alarm_art' => "Technischer Einsatz",
				'einsatzort' => "Langenbrücken",
				'alarmierung_date' => "2013-08-06",
				'alarmierung_time' => "15:31",
				'rueckkehr_date' => "2013-08-06",
				'rueckkehr_time' => "21:56",
				'link_to_media' => "",
				'wp_posts_ID' => $post_id
			), array() );

$id = $wpdb->insert_id;

print('Mission ID '.$id.'<br />');

// $wpdb->insert(
// 				$table_name_missions_has_vehicles,
// 				array(
// 					'einsaetze_id' => $id,
// 					'fahrzeuge_id' => 0
// 				), array() );

// $wpdb->insert(
// 				$table_name_missions_has_vehicles,
// 				array(
// 					'einsaetze_id' => $id,
// 					'fahrzeuge_id' => 1
// 				), array() );

$wpdb->insert(
				$table_name_missions_has_vehicles,
				array(
					'einsaetze_id' => $id,
					'fahrzeuge_id' => 2
				), array() );

$wpdb->insert(
				$table_name_missions_has_vehicles,
				array(
					'einsaetze_id' => $id,
					'fahrzeuge_id' => 3
				), array() );

// $wpdb->insert(
// 				$table_name_missions_has_vehicles,
// 				array(
// 					'einsaetze_id' => $id,
// 					'fahrzeuge_id' => 4
// 				), array() );

}
// add_post_meta( $post_id, MISSION_ID, $id );
$wpdb->print_error();

?>


</pre>