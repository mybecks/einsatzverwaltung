<?php

/*
 * Begin Admin Menu
 */

/**
 * Initializing Admin Menu
 *
 * @author Andre Becker
 * */
function einsatzverwaltung_admin_init() {


}

/**
 * Setting Admin Menu Style
 *
 * @author Andre Becker
 * */
function einsatzverwaltung_admin_styles() {
	/*
     * It will be called only on your plugin admin page, enqueue our stylesheet here
     */
	/* Register our stylesheet. */
	wp_register_style( 'adminStylesheet', plugins_url( 'css/admin.css', __FILE__ ) );
	wp_enqueue_style( 'adminStylesheet' );
}
add_action( 'admin_print_styles', 'einsatzverwaltung_admin_styles' );

//http://codex.wordpress.org/Adding_Administration_Menus
// http://wp.tutsplus.com/tutorials/theme-development/create-a-settings-page-for-your-wordpress-theme/
/**
 * Creating Admin Menu
 *
 * @author Andre Becker
 * */
function einsatzverwaltung_admin_menu() {

	add_menu_page( 'Einsatzverwaltung', 'Mission Control', 'read', 'einsatzverwaltung-admin', 'einsatzverwaltung_admin_howto', plugin_dir_url( __FILE__ ).'img/blaulicht_state_hover.png' );

	add_submenu_page( 'einsatzverwaltung-admin', 'How-To', 'How-To', 'read', 'einsatzverwaltung-admin', 'einsatzverwaltung_admin_howto' );

	if ( current_user_can( 'edit_pages' ) ) {
		add_submenu_page( 'einsatzverwaltung-admin', 'Vehicles', 'Fahrzeuge', 'edit_pages', 'einsatzverwaltung-admin-vehicles', 'einsatzverwaltung_admin_handle_vehicles' );
	}

	if ( current_user_can( 'manage_options' ) ) {
		//wp_die('You do not have sufficient permissions to access this page.');
		add_submenu_page( 'einsatzverwaltung-admin', 'Mission Importer', 'Einsatz Import', 'manage_options', 'einsatzverwaltung-admin-import-missions', 'einsatzverwaltung_admin_import_missions' );
	}

	if ( current_user_can( 'manage_options' ) ) {
		//wp_die('You do not have sufficient permissions to access this page.');
		add_submenu_page( 'einsatzverwaltung-admin', 'Settings', 'Einstellungen', 'manage_options', 'einsatzverwaltung-admin-handle-options', 'einsatzverwaltung_admin_handle_options' );
	}

	// add_action( 'admin_print_styles-' . $page, 'einsatzverwaltung_admin_styles' );
}

// should be a seperate file!

/**
 * Handle Category Selection
 *
 * @author Andre Becker
 * */
function einsatzverwaltung_admin_handle_options() {
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

/**
 * Updates the category input field with the new value
 *
 * @author Andre Becker
 * */
// function update_category_id_value( $value ) {
//  $script = "
//  <script type='text/javascript'>
//   jQuery(document).ready(function($) {
//   $('input[name=category_id]').val('".$value."');
//  });
//  </script>";
//  echo $script;
// }

/**
 * Updates the input field with the new value
 *
 * @author Andre Becker
 * */
function update_value( $field, $value ) {
	$script = "
	<script type='text/javascript'>
	 jQuery(document).ready(function($) {
		$('input[name='".$field."']').val('".$value."');
	});
	</script>";
	echo $script;
}

/**
 * HowTo for adding new missions
 *
 * @author Andre Becker
 * */
function einsatzverwaltung_admin_howto() {
?>
	<div class="wrap">
	<?php screen_icon( 'edit-pages' ); ?><h2>HowTo</h2>
	</div>
<?php
}

/**
 * Dispalying Admin Menu for Managing Vehicles
 *
 * @author Andre Becker
 * */
function einsatzverwaltung_admin_handle_vehicles() {
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
	$script = "
		<script type='text/javascript'>
    		jQuery(document).ready(function($) {
    			$('.tab-images').click(function() {
  					alert('Handler for .click() called.');
				});
    		});
		</script>";

	echo $script;
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
		<input type="submit" value="add" class="button-primary">
	</form>
	</div>
	<?php


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


function einsatzverwaltung_admin_import_missions ()
{
	//http://html5demos.com/dnd-upload#view-source
	?>
	<div class="wrap">
	    <?php screen_icon( 'edit-pages' ); ?> <h2>Mass Importer</h2>
	    <style>
			#holder { border: 10px dashed #ccc; width: 300px; min-height: 300px; margin: 20px auto;}
			#holder.hover { border: 10px dashed #0c0; }
			#holder img { display: block; margin: 10px auto; }
			#holder p { margin: 10px; font-size: 14px; }
			progress { width: 100%; }
			progress:after { content: '%'; }
			.fail { background: #c00; padding: 2px; color: #fff; }
			.hidden { display: none !important;}
		</style>

		<article>
			<div id="holder">
			</div> 
			<p id="upload" class="hidden"><label>Drag & drop not supported, but you can still upload via this input field:<br><input type="file"></label></p>
			<p id="filereader">File API & FileReader API not supported</p>
			<p id="formdata">XHR2's FormData is not supported</p>
			<p id="progress">XHR2's upload progress isn't supported</p>
			<p>Upload progress: <progress id="uploadprogress" min="0" max="100" value="0">0</progress></p>
			<p>Drag an image from your desktop on to the drop zone above to see the browser both render the preview, but also upload automatically to this server.</p>
		</article>

		<script>
			var holder = document.getElementById('holder'),
			    tests = {
					filereader: typeof FileReader != 'undefined',
					dnd: 'draggable' in document.createElement('span'),
					formdata: !!window.FormData,
					progress: "upload" in new XMLHttpRequest
			    }, 
			    support = {
					filereader: document.getElementById('filereader'),
					formdata: document.getElementById('formdata'),
					progress: document.getElementById('progress')
			    },
			    acceptedTypes = {
					'text/csv': true,
					'text/plain': true
			    },
			    progress = document.getElementById('uploadprogress'),
			    fileupload = document.getElementById('upload');

			"filereader formdata progress".split(' ').forEach(function (api) {
			  if (tests[api] === false) {
			    support[api].className = 'fail';
			  } else {
			    // FFS. I could have done el.hidden = true, but IE doesn't support
			    // hidden, so I tried to create a polyfill that would extend the
			    // Element.prototype, but then IE10 doesn't even give me access
			    // to the Element object. Brilliant.
			    support[api].className = 'hidden';
			  }
			});

			function previewfile(file) {
			  if (tests.filereader === true && acceptedTypes[file.type] === true) {
			    var reader = new FileReader();
			    reader.onload = function (event) {
			      var image = new Image();
			      image.src = event.target.result;
			      image.width = 250; // a fake resize
			      holder.appendChild(image);
			    };

			    reader.readAsDataURL(file);
			  }  else {
			    holder.innerHTML += '<p>Uploaded ' + file.name + ' ' + (file.size ? (file.size/1024|0) + 'K' : '');
			    console.log(file);
			  }
			}

			function readfiles(files) {
			    debugger;
			    var formData = tests.formdata ? new FormData() : null;
			    for (var i = 0; i < files.length; i++) {
			      if (tests.formdata) formData.append('file', files[i]);
			      previewfile(files[i]);
			    }

			    // now post a new XHR request
			    if (tests.formdata) {
			      var xhr = new XMLHttpRequest();
			      xhr.open('POST', '/devnull.php');
			      xhr.onload = function() {
			        progress.value = progress.innerHTML = 100;
			      };

			      if (tests.progress) {
			        xhr.upload.onprogress = function (event) {
			          if (event.lengthComputable) {
			            var complete = (event.loaded / event.total * 100 | 0);
			            progress.value = progress.innerHTML = complete;
			          }
			        }
			      }

			      xhr.send(formData);
			    }
			}

			if (tests.dnd) { 
				holder.ondragover = function () { this.className = 'hover'; return false; };
				holder.ondragend = function () { this.className = ''; return false; };
				holder.ondrop = function (e) {
					this.className = '';
					e.preventDefault();
					readfiles(e.dataTransfer.files);
				}
			} else {
				fileupload.className = 'hidden';
				fileupload.querySelector('input').onchange = function () {
					readfiles(this.files);
				};
			}
		</script>
	</div>
	<?php
}

/*
 * End Admin Menu
 */

?>
