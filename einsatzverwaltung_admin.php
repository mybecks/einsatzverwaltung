<?php
/**
 * Initializing Admin Menu
 *
 * @author Andre Becker
 * */

// http://wp.tutsplus.com/articles/tips-articles/quick-tip-conditionally-including-js-and-css-with-get_current_screen/?search_index=6
class EinsatzverwaltungAdmin {
	protected $pluginPath;
    private $dbHandler;  
    // protected $pluginUrl;

	public function __construct()  {
		$this->pluginPath = dirname(__FILE__);  

        add_action( 'admin_print_styles', array($this, 'einsatzverwaltung_admin_styles') );
        add_action( 'admin_enqueue_scripts', array($this,'einsatzverwaltung_admin_scripts') );
        add_action( 'admin_menu', array($this, 'einsatzverwaltung_admin_menu' ));
        $this->dbHandler = DatabaseHandler::get_instance();

        add_action('wp_ajax_nopriv_add_vehicle', array($this,'add_vehicle'));  
        add_action('wp_ajax_add_vehicle', array($this,'add_vehicle'));  
	}

	public function einsatzverwaltung_admin_styles(){
		wp_register_style( 'admin_styles', plugins_url( 'css/admin.css', __FILE__ ) );
        wp_register_style( 'admin_bootstrap', plugins_url( 'css/bootstrap.css', __FILE__ ) );

        wp_enqueue_style( 'admin_styles' );
        wp_enqueue_style( 'admin_bootstrap' );
	}

    public function einsatzverwaltung_admin_scripts( $hook ){

        wp_enqueue_script( 'einsatzverwaltung_admin_scripts', plugins_url( 'js/functions.admin.js', __FILE__ ), array('jquery') );
        wp_localize_script('einsatzverwaltung_admin_scripts', 'ajax_var', array(  
            'url' => admin_url('admin-ajax.php')
            ,'nonce' => wp_create_nonce('ajax-nonce')  
        ));
    }

    public function add_vehicle()  
    {  
        $nonce = $_POST['nonce'];  
   
        if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) )  
            die ( 'Busted!');

        $title = $_POST['id'];
        wp_die("TEST" . $title);
        exit;  
    }

	public function einsatzverwaltung_admin_menu() {

		add_menu_page( 'Einsatzverwaltung', 'Mission Control', 'read', 'einsatzverwaltung-admin', array($this, 'einsatzverwaltung_admin_howto'), plugin_dir_url( __FILE__ ).'img/blaulicht_state_hover.png' );

		add_submenu_page( 'einsatzverwaltung-admin', 'How-To', 'How-To', 'read', 'einsatzverwaltung-admin', array($this, 'einsatzverwaltung_admin_howto') );

		if ( current_user_can( 'edit_pages' ) ) {
			add_submenu_page( 'einsatzverwaltung-admin', 'Vehicles', 'Fahrzeuge', 'edit_pages', 'einsatzverwaltung-admin-vehicles', array($this, 'einsatzverwaltung_admin_handle_vehicles') );
		}

		if ( current_user_can( 'manage_options' ) ) {
			//wp_die('You do not have sufficient permissions to access this page.');
			add_submenu_page( 'einsatzverwaltung-admin', 'Mission Importer', 'Einsatz Import', 'manage_options', 'einsatzverwaltung-admin-import-missions', array($this, 'einsatzverwaltung_admin_import_missions') );
		}

		if ( current_user_can( 'manage_options' ) ) {
			//wp_die('You do not have sufficient permissions to access this page.');
			add_submenu_page( 'einsatzverwaltung-admin', 'Settings', 'Einstellungen', 'manage_options', 'einsatzverwaltung-admin-handle-options', array($this, 'einsatzverwaltung_admin_handle_options') );
		}
	} 

    // private function is_my_plugin_screen() {  
    //     $screen = get_current_screen();  
    //     if (is_object($screen) && ($screen->id == 'einsatzverwaltung-admin' || $screen->id == 'einsatzverwaltung-admin-vehicles' || $screen->id == 'einsatzverwaltung-admin-import-missions' || $screen->id == 'einsatzverwaltung-admin-handle-options')) {  
    //         return true;  
    //     } else {  
    //         return false;  
    //     }  
    // }  

	public function einsatzverwaltung_admin_howto() {
	?>
		<div class="wrap">
            <?php screen_icon( 'edit-pages' ); ?><h2>HowTo</h2>
            <div class="row">
            </div>
		</div>
	<?php
	}

	public function einsatzverwaltung_admin_handle_options() {
		$category_id = get_option( "einsatzverwaltung_settings_option_category_id" );
		$bitly_user = get_option( "einsatzverwaltung_settings_option_bitly_user" );
		$bitly_api_key = get_option( "einsatzverwaltung_settings_option_bitly_api_key" );
		?>
		<div class="wrap">
		    <?php screen_icon( 'options-general' ); ?> <h2>Einstellungen</h2>
		    <form method="POST" action="">
		    	<table class="form-table">
		    		<!-- <tbody>
		    			<tr>
		    				<th scope="rowgroup">Mapping</th>
		    			</tr>
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
		    		</tbody> -->
		    		<tbody>
		    			<tr>
		    				<th scope="rowgroup">bit.ly Einstellungen</th>
		    			</tr>
		    			</tr>
		             <tr valign="top">
		                <th scope="row">
		                    <label for="bitly_user">
		                        bit.ly User:
		                    </label>
		                </th>
		                <td>
		                    <input type="text" name="bitly_user" size="25" value="<?php echo $bitly_user;?>" />
		                </td>
		            </tr>
		            <tr valign="top">
		                <th scope="row">
		                    <label for="bitly_api_key">
		                        bit.ly API Key:
		                    </label>
		                </th>
		                <td>
		                    <input type="text" name="bitly_api_key" size="25" value="<?php echo $bitly_api_key;?>" />
		                </td>
		            </tr>
		            <tr valign="top">
		                <td colspan="2">
		                    <div>Get your bit.ly API here: <a href="http://bitly.com/a/your_api_key" target="_blank">http://bitly.com/a/your_api_key</a></div>
		                </td>
		            </tr>
		    		</tbody>


		        </table>
		        <input type="hidden" name="update_settings" value="Y" />
		        <p>
		    		<input type="submit" value="Save settings" class="button-primary"/>
				</p>
			</form>
		</div>

		<?php

		if ( isset( $_POST["update_settings"] ) ) {
			// Do the saving
			// $category_id = esc_attr( $_POST["category_id"] );
			// update_option( "einsatzverwaltung_settings_option_category_id", $category_id );

			$bitly_user = esc_attr( $_POST["bitly_user"] );
			update_option( "einsatzverwaltung_settings_option_bitly_user", $bitly_user );

			$bitly_api_key = esc_attr( $_POST["bitly_api_key"] );
			update_option( "einsatzverwaltung_settings_option_bitly_api_key", $bitly_api_key );
		?>
		    <div id="message" class="updated">Settings saved</div>
		<?php
			// update_category_id_value( $category_id );
			update_values( 'bitly_user', $bitly_user );
			update_values( 'bitly_api_key', $bitly_api_key );
		}
	}

	public function einsatzverwaltung_admin_handle_vehicles() {
		// AJAX loading: http://return-true.com/2010/01/using-ajax-in-your-wordpress-theme-admin/
		// http://codex.wordpress.org/AJAX_in_Plugins	
        global $wpdb;   
        $table_name_vehicles = $wpdb->prefix . "fahrzeuge";
     	?>

		<div class="wrap">
			<?php screen_icon( 'edit-pages' ); ?> <h2>Fahrzeugverwaltung</h2>

            <?php

			$query = "SELECT id, description FROM ".$table_name_vehicles;

			$vehicles = $wpdb->get_results( $query );

            ?>
			<table class="tab-vehicle" border="1">
				<tr>
					<th>ID</th>
					<th>Beschreibung</th>
					<th>Edit</th>
					<th>Delete</th>
				</tr>

		<?php
            // http://codex.wordpress.org/AJAX_in_Plugins
			foreach ( $vehicles as $vehicle ) {
				echo '	<tr>';
				echo '		<td>';
				echo    $vehicle->id;
				echo '		</td>';
				echo '		<td>';
				echo  $vehicle->description;
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
				<?php _e( "Neues Fahrzeug hinzuf&uuml;gen", 'einsatzverwaltung_textdomain' ); ?>
				<label>
				<input id="new_vehicle" name="add_new_vehicle" />
				<input type="hidden" name="insert_vehicle" value="Y" />
				<input type="submit" value="add" class="add-vehicle button-primary">
			</form>
		</div>
		<?php

        if( isset( $_POST["insert_vehicle"] ) )
        {
            $this->dbHandler->admin_insert_vehicle( $_POST['add_new_vehicle'] );

            // $wpdb->insert(
            //     $table_name_vehicles,
            //     array(
            //         'description' => $_POST['add_new_vehicle']
            //     ),
            //     array(
            //         '%s'
            //     )
            // );		
		?>
		<div id="message" class="updated">Added new vehicle</div>
		<?php
        }
	}

	public function einsatzverwaltung_admin_import_missions()
	{
	   //http://html5demos.com/dnd-upload#view-source
    	?>
    	<div class="wrap">
    	    <?php screen_icon( 'edit-pages' ); ?> <h2>Mass Importer</h2>
    	    <style>
    			
    		</style>
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <div class="holder"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-md-offset-3">
                    <p>Upload progress: <progress id="uploadprogress" min="0" max="100" value="0">0</progress></p>
                </div>
            </div>

            <p id="upload" class="hidden"><label>Drag & drop not supported, but you can still upload via this input field:<br><input type="file"></label></p>
            <p id="filereader">File API & FileReader API not supported</p>
            <p id="formdata">XHR2's FormData is not supported</p>
            <p id="progress">XHR2's upload progress isn't supported</p>
    		<p>Drag an image from your desktop on to the drop zone above to see the browser both render the preview, but also upload automatically to this server.</p>
    		<!-- </article> -->
    
    	</div>
    	<?php
    }
}

$wpEinsatzverwaltungAdmin = new EinsatzverwaltungAdmin();


/*
 * End Admin Menu
 */

?>
