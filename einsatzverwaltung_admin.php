<?php

/*
 * Begin Admin Menu
 */

/**
 * Initializing Admin Menu
 * 
 * @author Andre Becker
 **/
function einsatzverwaltung_admin_init() {
    
    
}
   
/**
 * Setting Admin Menu Style
 * 
 * @author Andre Becker
 **/   
function einsatzverwaltung_admin_styles() {
    /*
     * It will be called only on your plugin admin page, enqueue our stylesheet here
     */
    /* Register our stylesheet. */
    wp_register_style( 'adminStylesheet', plugins_url('css/admin.css', __FILE__) );
    wp_enqueue_style( 'adminStylesheet' );
}
add_action( 'admin_print_styles', 'einsatzverwaltung_admin_styles' );

//http://codex.wordpress.org/Adding_Administration_Menus
// http://wp.tutsplus.com/tutorials/theme-development/create-a-settings-page-for-your-wordpress-theme/
/**
 * Creating Admin Menu
 * 
 * @author Andre Becker
 **/
function einsatzverwaltung_admin_menu() {

	add_menu_page('Einsatzverwaltung', 'Mission Control', 'read', 'einsatzverwaltung-admin', 'einsatzverwaltung_admin_howto', plugin_dir_url( __FILE__ ).'img/blaulicht_state_hover.png');
	
	add_submenu_page('einsatzverwaltung-admin','How-To', 'How-To', 'read', 'einsatzverwaltung-admin', 'einsatzverwaltung_admin_howto');
	
	if (current_user_can('edit_pages')) {  
    	add_submenu_page('einsatzverwaltung-admin','Vehicles', 'Fahrzeuge', 'edit_pages', 'einsatzverwaltung-admin-vehicles', 'einsatzverwaltung_admin_handle_vehicles');    	
	}

	if (current_user_can('manage_options')) {  
    	//wp_die('You do not have sufficient permissions to access this page.');   
    	add_submenu_page('einsatzverwaltung-admin','Settings', 'Einstellungen', 'manage_options', 'einsatzverwaltung-admin-handle-options', 'einsatzverwaltung_admin_handle_options');  
	}

	// add_action( 'admin_print_styles-' . $page, 'einsatzverwaltung_admin_styles' );     
}

// should be a seperate file!

/**
 * Handle Category Selection
 * 
 * @author Andre Becker
 **/
function einsatzverwaltung_admin_handle_options() 
{
   	$category_id = get_option("einsatzverwaltung_settings_option_category_id");
	
	?>
	<div class="wrap">  
	    <?php screen_icon('options-general'); ?> <h2>Einstellungen</h2>  
	    <form method="POST" action="">  
	    	<table class="form-table">  
	            <tr valign="top">  
	                <th scope="row">  
	                    <label for="category_id">  
	                        Mapping der Kategorien:  
	                    </label>  
	                </th>  
	                <td> 
	                    <input type="text" name="category_id" size="25" value="<?php echo $category_id;?>" />  
	                </td>  
	            </tr>  
	            </table>
	        <input type="hidden" name="update_settings" value="Y" /> 
	        <p>  
	    		<input type="submit" value="Save settings" class="button-primary"/>  
			</p>    
	   	 </form>          
	</div>  

	<?php

	if (isset($_POST["update_settings"])) {  
	    // Do the saving  
	    $category_id = esc_attr($_POST["category_id"]);  
		update_option("einsatzverwaltung_settings_option_category_id", $category_id);
	?>  
	    <div id="message" class="updated">Settings saved</div>
	<?php  
		update_category_id_value($category_id);
	}  
}

/**
 * Updates the category input field with the new value
 * 
 * @author Andre Becker
 **/
function update_category_id_value($value){
	$script = "
	<script type='text/javascript'>
	 jQuery(document).ready(function($) {
		$('input[name=category_id]').val('".$value."');
	});
	</script>";
	echo $script;
}

/**
 * HowTo for adding new missions
 * 
 * @author Andre Becker
 **/
function einsatzverwaltung_admin_howto(){
	?>
	<div class="wrap"> 
	<?php screen_icon('edit-pages'); ?><h2>HowTo</h2> 
	</div>
	<?php
}

/**
 * Dispalying Admin Menu for Managing Vehicles
 * 
 * @author Andre Becker
 **/
function einsatzverwaltung_admin_handle_vehicles() {
	// AJAX loading: http://return-true.com/2010/01/using-ajax-in-your-wordpress-theme-admin/
	// http://codex.wordpress.org/AJAX_in_Plugins
	global $wpdb;

	$table_name_vehicles = $wpdb->prefix . "fahrzeuge";
	?>
	
	<div class="wrap">  
	    <?php screen_icon('edit-pages'); ?> <h2>Fahrzeugverwaltung</h2>  
	
	<?php

	$query = "SELECT id, description FROM ".$table_name_vehicles;

	$vehicles = $wpdb->get_results($query);
	?>
	<table class="tab-vehicle" border="1">
		<tr>
			<th>ID</th>
			<th>Beschreibung</th>
			<th>Edit</th>
			<th>Delete</th>
		</tr>
	<?php
	foreach ( $vehicles as $vehicle ) 
	{
		echo '	<tr>';
		echo '		<td>';
		echo 			$vehicle->id;
		echo '		</td>';
		echo '		<td>';
		echo 			$vehicle->description;
		echo '		</td>';
		echo '		<td>';
		echo '			<img class="tab-images" src='.plugin_dir_url( __FILE__ ).'img/admin_edit.png />';
		echo '		</td>';
		echo '		<td>';
		echo '			<img class="tab-images" src='.plugin_dir_url( __FILE__ ).'img/admin_delete.png />';
		echo '		</td>';
		echo '	</tr>';
	}

	?>
	</table>
	<br />
	<form method="POST" action="">
		<label for="new_vehicle">
		<?php _e("Neues Fahrzeug hinzuf&uuml;gen", 'einsatzverwaltung_textdomain' ); ?>
		<label>
		<input id="new_vehicle" name="add_new_vehicle" />
		<input type="hidden" name="insert_vehicle" value="Y" /> 
		<input type="submit" value="add" class="button-primary">
	</form>
	</div>
	<?php


	if(isset($_POST['insert_vehicle']))
	{
		$wpdb->insert( 
			$table_name_vehicles, 
			array( 
				'description' => $_POST['add_new_vehicle'] 
			), 
			array( 
				'%s'
			) 
		);

		?>
		<div id="message" class="updated">Added new vehicle</div>
		<?php
	}
	
	
}

/*
 * End Admin Menu
 */

?>