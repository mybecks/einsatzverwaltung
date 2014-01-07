<?php

/**
 * Custom Post Type for Einsatzverwaltung
 * 
 * @author Andre Becker
 **/
class EinsatzverwaltungCustomPost {
    
    public function __construct() {
        add_action( 'init', array($this, 'custom_post_mission' ) );
        add_action( 'publish_mission', array($this, 'save_data' ));
        // add_action( 'trash_mission', array($this, 'trash_mission') );
        add_action( 'admin_enqueue_scripts', array($this,'add_scripts') );
        add_action( 'manage_mission_posts_custom_column', array($this, 'manage_mission_columns'), 10, 2 );
        add_filter( 'post_updated_messages', array($this, 'mission_updated_messages' ) );
        add_filter( 'manage_edit-mission_columns', array($this, 'edit_mission_column' ) );
        add_filter( 'manage_edit-mission_sortable_columns', array($this,'mission_sortable_columns') );
        add_filter( 'post_type_link', array($this, 'mission_permalink'), 10, 3 ); 
        $this->dbHandler = DatabaseHandler::get_instance();
    }

    public function custom_post_mission() {
        global $wp_rewrite;

        $labels = array(
            'name'               => __( 'Missions', TEXT_DOMAIN ),
            'singular_name'      => __( 'Mission', TEXT_DOMAIN ),
            'add_new'            => __( 'Add New', TEXT_DOMAIN ),
            'add_new_item'       => __( 'Add New Mission', TEXT_DOMAIN ),
            'edit_item'          => __( 'Edit Mission', TEXT_DOMAIN ),
            'new_item'           => __( 'New Mission', TEXT_DOMAIN ),
            'all_items'          => __( 'All Missions', TEXT_DOMAIN ),
            'view_item'          => __( 'View Mission', TEXT_DOMAIN ),
            'search_items'       => __( 'Search Missions', TEXT_DOMAIN ),
            'not_found'          => __( 'No Missions found', TEXT_DOMAIN ),
            'not_found_in_trash' => __( 'No Missions found in the Trash', TEXT_DOMAIN ), 
            'parent_item_colon'  => '',
            'menu_name'          => __( 'Missions', TEXT_DOMAIN )
        );
        
        $args = array(
            'labels'        => $labels,
            'description'   => __( 'Holds our missions and specific data', TEXT_DOMAIN ),
            'public'        => true,
            'menu_position' => 5,
            'supports'      => array( 'title', 'author', 'editor' ),
            'has_archive'   => true,
            'menu_icon'     => plugin_dir_url( __FILE__ ).'img/blaulicht_state_hover.png',
            'register_meta_box_cb' => array( $this, 'add_custom_box' )
        );
        register_post_type( 'mission', $args );

        $wp_rewrite->add_permastruct('mission', 'mission/%year%/%monthnum%/%mission%/', true, 1);
        add_rewrite_rule('mission/([0-9]{4})/([0-9]{2})/(.+)/?$', 'index.php?mission=$matches[3]', 'top');
        // $wp_rewrite->flush_rules(); // !!!
    }

    public function mission_updated_messages( $messages ) {
        global $post, $post_ID;
        $messages['einsatz'] = array(
            0 => '', 
            1 => sprintf( __('Mission updated. <a href="%s">View mission</a>', TEXT_DOMAIN), esc_url( get_permalink($post_ID) ) ),
            2 => __('Custom field updated.', TEXT_DOMAIN),
            3 => __('Custom field deleted.', TEXT_DOMAIN),
            4 => __('Mission updated.', TEXT_DOMAIN),
            5 => isset($_GET['revision']) ? sprintf( __('Mission restored to revision from %s', TEXT_DOMAIN), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => sprintf( __('Mission published. <a href="%s">View mission</a>', TEXT_DOMAIN), esc_url( get_permalink($post_ID) ) ),
            7 => __('Mission saved.', TEXT_DOMAIN),
            8 => sprintf( __('Mission submitted. <a target="_blank" href="%s">Preview mission</a>', TEXT_DOMAIN), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
            9 => sprintf( __('Mission scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview mission</a>', TEXT_DOMAIN), date_i18n( __( 'M j, Y @ G:i', TEXT_DOMAIN ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
            10 => sprintf( __('Mission draft updated. <a target="_blank" href="%s">Preview mission</a>', TEXT_DOMAIN), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
        );
        return $messages;
    }

    public function edit_mission_column( $columns ) {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __( 'Title' ),
            'type' => __( 'Mission Type' ),
            'alarmdate' => __( 'Alarm Date' ),
            'alarmtime' => __( 'Alarm Time' ),
            'date' => __( 'Entry Date' ),
            'lastedit' => __( 'Last Edited by User' ),
            'author' => __( 'Author' )
        );

        return $columns;
    }

    // http://justintadlock.com/archives/2011/06/27/custom-columns-for-custom-post-types
    public function manage_mission_columns( $column, $post_id ){
        global $post;

        $mission = $this->dbHandler->get_mission_details_by_post_id( $post->ID );

        // var_dump($mission);
        // die();
        switch( $column ) {
            case 'type' :
                echo $mission[0]->art_alarmierung;
                break;
            case 'alarmdate' :
                echo $mission[0]->alarmierung_date;
                break;
            case 'alarmtime' :
                $date = new DateTime($mission[0]->alarmierung_time);
                echo $date->format('H:i');
                break;
            case 'lastedit' :
                the_modified_author();
                break;
            default :
                break;
        }
    }
    
function mission_permalink( $permalink, $post_id, $leavename ) {
    $post = get_post($post_id);
        $rewritecode = array(
            '%year%',
            '%monthnum%',
            '%day%',
            '%hour%',
            '%minute%',
            '%second%',
            $leavename? '' : '%postname%',
            '%post_id%',
            '%category%',
            '%author%',
            $leavename? '' : '%pagename%',
        );
     
        if ( '' != $permalink && !in_array($post->post_status, array('draft', 'pending', 'auto-draft')) ) {
            $unixtime = strtotime($post->post_date);
         
            $category = '';
            if ( strpos($permalink, '%category%') !== false ) {
                $cats = get_the_category($post->ID);
                if ( $cats ) {
                    usort($cats, '_usort_terms_by_ID'); // order by ID
                    $category = $cats[0]->slug;
                    if ( $parent = $cats[0]->parent )
                        $category = get_category_parents($parent, false, '/', true) . $category;
                }
                // show default category in permalinks, without
                // having to assign it explicitly
                if ( empty($category) ) {
                    $default_category = get_category( get_option( 'default_category' ) );
                    $category = is_wp_error( $default_category ) ? '' : $default_category->slug;
                }
            }
         
            $author = '';
            if ( strpos($permalink, '%author%') !== false ) {
                $authordata = get_userdata($post->post_author);
                $author = $authordata->user_nicename;
            }
         
            $date = explode(" ",date('Y m d H i s', $unixtime));
            $rewritereplace =
            array(
                $date[0],
                $date[1],
                $date[2],
                $date[3],
                $date[4],
                $date[5],
                $post->post_name,
                $post->ID,
                $category,
                $author,
                $post->post_name,
            );
            $permalink = str_replace($rewritecode, $rewritereplace, $permalink);

            // wp_die($permalink );
        } else { // if they're not using the fancy permalink option
        }
        return $permalink;
    }

    public function mission_sortable_columns( $columns ){
        $columns['alarmdate'] = 'alarmdate';

        return $columns;
    }

    public function add_scripts(){
        wp_enqueue_script( 'jquery-ui-autocomplete' );
    }

    /**
     * Add Custom Box to Category
     *
     * @author Andre Becker
     * */
    public function add_custom_box() {
        add_meta_box(
            'einsatzverwaltung_sectionid',
            __( 'Einsatzverwaltung', TEXT_DOMAIN ),
            array( $this, 'custom_box_content' ),
            'mission'
        );
    }

    public function custom_box_content( $post ) {

        // Use nonce for verification
        wp_nonce_field( plugin_basename( __FILE__ ), 'einsatzverwaltung_noncename' );

        $meta_values = get_post_meta( $post->ID, MISSION_ID, '' );
        $meta_values = array_filter( $meta_values );

        if ( !empty( $meta_values ) ) {
            $mission = $this->dbHandler->load_mission_by_id( $meta_values[0] );
            $vehicles_by_mission = $this->dbHandler->load_vehicles_by_mission_id( $mission->id );
            $vehicles = $this->dbHandler->load_vehicles();
        }else {
            $vehicles = $this->dbHandler->load_vehicles();

            $mission = new stdClass();
            $mission->id = "";
            $mission->type = "";
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

        if ( strlen( $mission->type ) != 0 ) {
            // http://wpquicktips.wordpress.com/2012/04/25/using-php-variables-in-javascript-with-wp_localize_script/
            // http://www.ronakg.com/2011/05/passing-php-array-to-javascript-using-wp_localize_script/
            $this->set_selector_for_dropdown_value( "#mission_type", $mission->type );
        }
        if ( strlen( $mission->alarmstichwort ) != 0 ) {
            $this->set_selector_for_dropdown_value( "#alarm_stichwort", $mission->alarmstichwort );
        }

        if ( strlen( $mission->alarm_art ) != 0 ) {
            $this->set_selector_for_dropdown_value( "#alarm", $mission->alarm_art );
        }

        if ( count( $vehicles_by_mission ) != 0 ) {
            for ( $i=0; $i < count( $vehicles_by_mission ); $i++ ) {
                $name = $this->rename_db_vehicle_name( $vehicles_by_mission[$i]->description );
                $this->set_selector_for_checkbox_value( $name );
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
                    "Kronau",
                    "Unteröwisheim",
                    "Oberöwisheim"];

                $( "#einsatzort" ).autocomplete({
                    source: availableTags
                });

            });

        </script>
EOF;

        echo $script;

        echo '<table border="1">';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="mission_id">';
        _e( "Einsatz Nr.", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <input id="mission_id" name="mission_id" value="'.$mission->id.'" readonly="true" size="4"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="mission_type">';
        _e( "Einsatz Art", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <select id="mission_type" name="mission_type">';
        echo '              <option>Brandeinsatz</option>';
        echo '              <option>Technischer Einsatz</option>';
        echo '              <option>Sonstiger Einsatz</option>';
        echo '          </select>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="alarm_stichwort">';
        _e( "Alarmstichwort", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <select id="alarm_stichwort" name="alarm_stichwort">';
        echo '              <optgroup label="Brand">';
        echo '                  <option>Brandmeldealarm</option>';
        echo '                  <option>Lagerhallenbrand</option>';
        echo '                  <option>Dachstuhlbrand</option>';
        echo '                  <option>Wohnungsbrand</option>';
        echo '                  <option>Zimmerbrand</option>';
        echo '                  <option>Kellerbrand</option>';
        echo '                  <option>Kleinbrand</option>';
        echo '                  <option>PKW-Brand</option>';
        echo '                  <option>Feuerschein</option>';
        echo '                  <option id="sel_so_brand">Sonstiger Brand</option>';
        echo '              <optgroup label="Technische Hilfe">';
        echo '                  <option>Verkehrsunfall</option>';
        echo '                  <option>Person in Not</option>';
        echo '                  <option>Unterstützung Rettungsdienst</option>';
        echo '                  <option>Wasserschaden</option>';
        echo '              <optgroup label="Sonstiges">';
        echo '                  <option>Absperrmaßnahme</option>';
        echo '                  <option>Ölspur</option>';
        echo '                  <option>Drehleitereinsatz</option>';
        echo '                  <option>Sicherheitsdienst</option>';
        echo '                  <option id="sel_freitext">Freitext</option>';
        echo '          </select>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr id="row_freitext_alarmstichwort">';
        echo '      <td>';
        echo '          <label for="alarmstichwort_freitext">';
        _e( "Alarmstichwort (Freitext)", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        if ( ( $mission->alarmstichwort == "Freitext" ) || ( $mission->alarmstichwort == "Sonstiger Brand" ) ) {
            echo '          <input name="alarmstichwort_freitext" value="'.$mission->freitext.'"/>';
        }
        else {
            echo '          <input name="alarmstichwort_freitext" />';
        }
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="alarm">';
        _e( "Alarm", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <select id="alarm" name="alarm">';
        echo '              <option>Einsatzalarm</option>';
        echo '              <option>Keine Tätigkeit</option>';
        echo '          </select>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="einsatzort">';
        _e( "Einsatzort", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <input id="einsatzort" name="einsatzort" value="'.$mission->einsatzort.'"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="alarmierung_datum">';
        _e( "Alarmierung (Datum)", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <input id="alarm_date" name="alarmierung_datum" type="date" value="'.$mission->alarmierung_date.'"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="alarmierung_zeit">';
        _e( "Alarmierung (Uhrzeit)", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <input name="alarmierung_zeit" type="time" value="'.$mission->alarmierung_time.'"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="rueckkehr_datum">';
        _e( "R&uuml;ckkehr (Datum)", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <input id="alarm_end_date" name="rueckkehr_datum" type="date" value="'.$mission->rueckkehr_date.'"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="rueckkehr_zeit">';
        _e( "R&uuml;ckkehr (Uhrzeit)", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <input name="rueckkehr_zeit" type="time" value="'.$mission->rueckkehr_time.'"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="link_zu_medien">';
        _e( "Link zu weiterf&uuml;hrenden Medien", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <input name="link_zu_medien" type="url" value="'.$mission->link_to_media.'" size="50"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="fahrzeuge">';
        _e( "Eingesetzte Fahrzeuge", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        if ( count( $vehicles ) > 0 ) {
            for ( $i=0; $i<count( $vehicles ); $i++ ) {
                $name = $this->rename_db_vehicle_name( $vehicles[$i]->description );
                echo '          <label for="'.$name.'"> <input name="'.$name.'" type="checkbox"/> '.$vehicles[$i]->description.' </label>';
            }
        }else {
            echo '<p>';
            _e( "Keine Fahrzeuge in der Datenbank gefunden!", TEXT_DOMAIN );
            echo '</p>';
        }
        echo '      </td>';
        echo '  </tr>';
        echo '</table>';
    }

    /**
     * Save and Edit Mission Details
     *
     * @author Andre Becker
     * */
    /* When the post is saved, saves our custom data */
    public function save_data( $post_id ) {
        global $wpdb;

        $table_name_missions =     $wpdb->prefix . "einsaetze";
        
        
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
        //  return;

        // OK, we're authenticated: we need to find and save the data


        $mission_id = $_POST['mission_id'];
        $mission_type = $_POST['mission_type'];

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
        $link_zu_medien = $this->media_link_shortener( $_POST['link_zu_medien'] );

        $db_vehicles = $this->dbHandler->load_vehicles();
        $vehicles = array();

        for ( $i=0; $i<count( $db_vehicles ); $i++ ) {
            $name = $this->rename_db_vehicle_name( $db_vehicles[$i]->description );

            if ( isset( $_POST[$name] ) )
                $vehicles[] = $db_vehicles[$i]->id;
        }

        if ( !empty( $mission_id ) ) {
            //Update
            $wpdb->update(
                $table_name_missions,
                array(
                    'art_alarmierung' => $mission_type,
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

            //remove all vehicles bound to current mission!
            $this->dbHandler->remove_vehicles_from_mission( $mission_id );

            //insert new values:
            foreach ( $vehicles as $vehicle ) {
               $this->dbHandler->insert_new_vehicle_to_mission( $mission_id, $vehicle );
            }

        }else {
            //new mission entry
            $wpdb->insert(
                $table_name_missions,
                array(
                    'art_alarmierung' => $mission_type,
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
            
            $current_post = array(
                'ID'           => $post_id,
                'post_date' => date($alarmierung_datum .' '. $alarmierung_zeit),
                'post_date_gmt' => date($alarmierung_datum .' '. $alarmierung_zeit)
            );
            remove_action('publish_mission', array($this, 'save_data'));
            // Update the post into the database
            wp_update_post( $current_post );

            add_action('publish_mission', array($this, 'save_data'));
        }
    }

    /**
     * Delete mission data & associated post
     *
     * @return boolean
     * @author Andre Becker
     * */
    public function trash_mission( $post_id ) {
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
    public function media_link_shortener( $link ) {

        $short_link;
        // $url = 'http://api.bit.ly/v3/shorten?format=txt&login='.BITLY_USER.'&apiKey='.BITLY_API_KEY.'&longUrl='.$link;
        // $response = wp_remote_get( $url );

        // if ( !is_wp_error( $response ) ) {
        //  // wp_die($response['body']);
        //  $short_link = "";
        // }else {
        //  $short_link = $link;
        // }
        // $shortend_url = file_get_contents($url);
        $short_link = $link;
        return $short_link;
    }

    /**
     *
     *
     * @author Andre Becker
     * */
    public function rename_db_vehicle_name( $name ) {

        if ( $name == "DLK 23/12" )
            $name = "dlk";

        $cleaned_name = str_replace( ' ', '', $name );
        $name = strtolower( $cleaned_name );

        return "fahrzeuge_".$name;
    }

    /**
     *
     *
     * @author Andre Becker
     * */
    public function set_selector_for_dropdown_value( $id, $value ) {
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
    public function set_selector_for_checkbox_value( $value ) {
        $script = "
        <script type='text/javascript'>
         jQuery(document).ready(function($) {
            $('input[name=".$value."]').attr('checked', true);
        });
        </script>";
        echo $script;
    }
}

$wpEinsatzverwaltungCustomPost = new EinsatzverwaltungCustomPost();
?>
