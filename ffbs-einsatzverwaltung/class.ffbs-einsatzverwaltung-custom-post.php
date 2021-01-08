<?php
/**
 * Custom Post Type for Einsatzverwaltung
 *
 * @author Andre Becker
 **/
class EinsatzverwaltungCustomPost {

    public function __construct() {
        add_action( 'init', array( $this, 'custom_post_mission' ) );
        add_action( 'publish_mission', array( $this, 'save_data' ) );
        // add_action( 'trash_mission', array($this, 'trash_mission') ); //to set a flag that the mission is not shown any more
        add_action( 'before_delete_post', array( $this, 'delete_mission' ) ); //deltes the mission data
        add_action( 'admin_enqueue_scripts', array( $this,'add_scripts') );
        add_action( 'manage_mission_posts_custom_column', array( $this, 'manage_mission_columns'), 10, 2 );
        add_filter( 'post_updated_messages', array( $this, 'mission_updated_messages' ) );
        add_filter( 'manage_edit-mission_columns', array( $this, 'edit_mission_column' ) );
        add_filter( 'manage_edit-mission_sortable_columns', array( $this, 'mission_sortable_columns' ) );
        add_filter( 'post_type_link', array( $this, 'mission_permalink' ), 10, 3 );
        $this->db_handler = DatabaseHandler::get_instance();
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
            'supports'      => array('author', 'editor' ),
            'has_archive'   => true,
            'rewrite'       => array('slug' => 'mission', 'with_front' => false),
            'menu_icon'     => plugin_dir_url( __FILE__ ).'img/blaulicht_state_hover.png',
            'register_meta_box_cb' => array( $this, 'add_custom_box' )
        );
        register_post_type( 'mission', $args );

        $wp_rewrite->add_rewrite_tag( '%mission%', '([^\/]+)', 'index.php?mission=' );
        // $wp_rewrite->add_rewrite_tag( '%mission%', '(\d{4}\_\d{2}\_\D+[a-zA-Z])', 'index.php?mission=' );
        $wp_rewrite->add_permastruct( 'mission', 'mission/%year%/%monthnum%/%mission%/' );
    }

    public function mission_updated_messages( $messages ) {
        global $post, $post_ID;
        $messages['einsatz'] = array(
            0 => '',
            1 => sprintf( __( 'Mission updated. <a href="%s">View mission</a>', TEXT_DOMAIN ), esc_url( get_permalink($post_ID) ) ),
            2 => __( 'Custom field updated.', TEXT_DOMAIN ),
            3 => __( 'Custom field deleted.', TEXT_DOMAIN ),
            4 => __( 'Mission updated.', TEXT_DOMAIN ),
            5 => isset( $_GET['revision']) ? sprintf( __( 'Mission restored to revision from %s', TEXT_DOMAIN ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => sprintf( __( 'Mission published. <a href="%s">View mission</a>', TEXT_DOMAIN), esc_url( get_permalink($post_ID) ) ),
            7 => __( 'Mission saved.', TEXT_DOMAIN ),
            8 => sprintf( __( 'Mission submitted. <a target="_blank" href="%s">Preview mission</a>', TEXT_DOMAIN ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
            9 => sprintf( __( 'Mission scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview mission</a>', TEXT_DOMAIN ), date_i18n( __( 'M j, Y @ G:i', TEXT_DOMAIN ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
            10 => sprintf( __( 'Mission draft updated. <a target="_blank" href="%s">Preview mission</a>', TEXT_DOMAIN ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
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
            'author' => __( 'Author' ),
            'id' => __( 'Mission Id' )
        );

        return $columns;
    }

    // http://justintadlock.com/archives/2011/06/27/custom-columns-for-custom-post-types
    public function manage_mission_columns( $column, $post_id ){
        global $post;

        $mission = $this->db_handler->get_mission_details_by_post_id( $post->ID );

        switch( $column ) {
            case 'id' :
                echo $mission[0]->id;
                break;
            case 'type' :
                echo $mission[0]->art_alarmierung;
                break;
            case 'alarmdate' :
                echo $mission[0]->alarmierung_date;
                break;
            case 'alarmtime' :
                $date = new DateTime( $mission[0]->alarmierung_time );
                echo $date->format( 'H:i' );
                break;
            case 'lastedit' :
                the_modified_author();
                break;
            default :
                break;
        }
    }

    public function mission_permalink( $permalink, $post_id, $leavename ) {
        $post = get_post( $post_id );

        $rewritecode = array(
            '%year%',
            '%monthnum%',
            '%postname%'
        );
        // echo $permalink;
        // $regex = '/(\d{4}\_\d{2}\_\D+[a-zA-Z])/';
        // $rewritecode = array(
        //     '/%year%/',
        //     '/%monthnum%/',
        //     $regex
        // );

        if ( '' != $permalink && ! in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) ) ) {
            $unixtime = strtotime($post->post_date);

            $date = explode( " ", date( 'Y m d H i s', $unixtime ) );

            list ($year, $month, $postname) = explode( '_' , $post->post_name );
            $rewritereplace = array(
                $date[0],
                $date[1],
                $postname,
            );

            $permalink = str_replace( $rewritecode, $rewritereplace, $permalink );
            // $permalink = preg_replace($rewritecode, $rewritereplace, $permalink);

            // wp_die($permalink);
        } else { // if they're not using the fancy permalink option
        }
        return $permalink;
    }

    private function prepare_permalink ( $permalink ){
        // detect position of basis string
        $pos = strrpos($permalink, '/', -2);
        $basis = substr($permalink, 0, $pos) . '/';

        // detect & clear post name
        $postname = substr($permalink, $pos+1, strlen($permalink));
        list($year, $monath, $postname) = explode('_', $postname);

        // put basis & new post name together
        return $basis . $postname;
    }

    public function mission_sortable_columns( $columns ) {
        $columns['alarmdate'] = 'alarmdate';

        return $columns;
    }

    public function add_scripts() {
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

        if ( ! empty( $meta_values ) ) {
            $mission = $this->db_handler->load_mission_by_id( $meta_values[0] );
            $vehicles_by_mission = $this->db_handler->load_vehicles_by_mission_id( $mission->id );
            $vehicles = $this->db_handler->load_vehicles();
        } else {
            $vehicles = $this->db_handler->load_vehicles();

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

        if ( 0 !== strlen( $mission->type ) ) {
            // http://wpquicktips.wordpress.com/2012/04/25/using-php-variables-in-javascript-with-wp_localize_script/
            // http://www.ronakg.com/2011/05/passing-php-array-to-javascript-using-wp_localize_script/
            $this->set_selector_for_dropdown_value( "#mission_type", $mission->type );
        }

        if ( 0 !== strlen( $mission->alarmstichwort ) ) {
            $this->set_selector_for_dropdown_value( "#alarm_stichwort", $mission->alarmstichwort );
        }

        if ( 0 !== strlen( $mission->alarm_art ) ) {
            $this->set_selector_for_dropdown_value( "#alarm", $mission->alarm_art );
        }

        if ( 0 !== count( $vehicles_by_mission ) ) {
            for ( $i = 0; $i < count( $vehicles_by_mission ); $i++ ) {
                $name = $this->rename_db_vehicle_name( $vehicles_by_mission[$i]->description );
                $this->set_selector_for_checkbox_value( $name );
            }
        }

        $script = <<< EOF
        <script type='text/javascript'>
            jQuery(document).ready(function($) {
                // Needs more testing - title will not be written to db
                // $('#title').prop('disabled', true);


                if($('#alarm_stichwort').val() === 'Sonstiger Brand' || $('#alarm_stichwort').val() === 'Freitext'){
                    $('#row_freitext_alarmstichwort').show();
                    // $('#title').attr('value', $('#freitext').val());
                }else{
                    $('#row_freitext_alarmstichwort').hide();
                    // $('#title').attr('value', $("#alarm_stichwort option:first").text());
                }

                var label = $('#alarm_stichwort :selected').parent().attr('label');

                if(label) {
                    switch(label){
                        case 'Brand':
                            $('#mission_type').val('Brandeinsatz');
                            break;
                        case 'Technische Hilfe':
                            $('#mission_type').val('Technischer Einsatz');
                            break;
                        case 'Sonstiges':
                            $('#mission_type').val('Sonstiger Einsatz');
                            break;
                        }
                }

                $('#alarm_stichwort').change(function() {
                    // $('#title').attr('value', $("#alarm_stichwort option:selected").text());
                    if($('#sel_so_brand').is(':selected') || $('#sel_freitext').is(':selected')) {
                        $('#row_freitext_alarmstichwort').show();

                        // $('#freitext').keyup(function(){
                        //     $('#title').attr('value', $('#freitext').val());
                        // });
                    }else{
                        $('#row_freitext_alarmstichwort').hide();
                    }


                    var label = $('#alarm_stichwort :selected').parent().attr('label');

                    switch(label){
                        case 'Brand':
                            $('#mission_type').val('Brandeinsatz');
                            break;
                        case 'Technische Hilfe':
                            $('#mission_type').val('Technischer Einsatz');
                            break;
                        case 'Sonstiges':
                            $('#mission_type').val('Sonstiger Einsatz');
                            break;
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
                    "Oberöwisheim",
                    "Weiher",
                    "Ubstadt",
                    "Stettfeld",
                    "Zeitern"];

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
        echo '          <input id="mission_id" name="mission_id" value="' . $mission->id . '" readonly="true" size="4"/>';
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
        echo '                  <option>Verdächtiger Rauch</option>';
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
        if ( ( "Freitext" == $mission->alarmstichwort ) || ( "Sonstiger Brand" == $mission->alarmstichwort ) ) {
            echo '          <input name="alarmstichwort_freitext" id="freitext" value="' . $mission->freitext . '"/>';
        }
        else {
            echo '          <input name="alarmstichwort_freitext" id="freitext"/>';
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
        echo '          <input id="einsatzort" name="einsatzort" value="' . $mission->einsatzort . '"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="alarmierung_datum">';
        _e( "Alarmierung (Datum)", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <input id="alarm_date" name="alarmierung_datum" type="date" value="' . $mission->alarmierung_date . '"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="alarmierung_zeit">';
        _e( "Alarmierung (Uhrzeit)", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <input name="alarmierung_zeit" type="time" value="' . $mission->alarmierung_time . '"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="rueckkehr_datum">';
        _e( "R&uuml;ckkehr (Datum)", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <input id="alarm_end_date" name="rueckkehr_datum" type="date" value="' . $mission->rueckkehr_date . '"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="rueckkehr_zeit">';
        _e( "R&uuml;ckkehr (Uhrzeit)", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <input name="rueckkehr_zeit" type="time" value="' . $mission->rueckkehr_time . '"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="link_zu_medien">';
        _e( "Link zu weiterf&uuml;hrenden Medien", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        echo '          <input name="link_zu_medien" type="url" value="' . $mission->link_to_media . '" size="50"/>';
        echo '      </td>';
        echo '  </tr>';
        echo '  <tr>';
        echo '      <td>';
        echo '          <label for="fahrzeuge">';
        _e( "Eingesetzte Fahrzeuge", TEXT_DOMAIN );
        echo '          <label>';
        echo '      </td>';
        echo '      <td>';
        if ( 0 < count( $vehicles ) ) {
            for ( $i = 0; $i < count( $vehicles ); $i++ ) {
                $name = $this->rename_db_vehicle_name( $vehicles[$i]->description );
                echo '          <label for="' . $name . '"> <input name="' . $name . '" type="checkbox"/> ' . $vehicles[$i]->description . ' </label>';
            }
        } else {
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

        $table_missions = $wpdb->prefix . "einsaetze";


        // verify if this is an auto save routine.
        // If it is our form has not been submitted, so we dont want to do anything
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;

        // verify this came from the our screen and with proper authorization,
        // because save_post can be triggered at other times
        if ( ! wp_verify_nonce( $_POST['einsatzverwaltung_noncename'], plugin_basename( __FILE__ ) ) )
            return;

        // Check permissions
        if ( 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) )
                return;
        }
        else {
            if ( ! current_user_can( 'edit_post', $post_id ) )
                return;
        }

        // $cat_id = get_the_category( $post_id );

        // //Check if mission category
        // if ( CATEGORY != $cat_id[0]->cat_ID )
        //  return;

        // OK, we're authenticated: we need to find and save the data


        $mission_id = $_POST['mission_id'];
        $mission_type = $_POST['mission_type'];

        if ( ( "Freitext" == $_POST['alarm_stichwort'] ) || ( "Sonstiger Brand" == $_POST['alarm_stichwort'] ) ) {
            $freitext = $_POST['alarmstichwort_freitext'];
            $pn = $freitext;
        } else {
            $freitext = "";
            $pn = $_POST['alarm_stichwort'];
        }

        $alarm = $_POST['alarm'];
        $einsatzort = $_POST['einsatzort'];
        $alarm_stichwort = $_POST['alarm_stichwort'];
        $alarmierung_datum = $_POST['alarmierung_datum'];
        $alarmierung_zeit = $_POST['alarmierung_zeit'];
        $rueckkehr_datum = $_POST['rueckkehr_datum'];
        $rueckkehr_zeit = $_POST['rueckkehr_zeit'];
        $link_zu_medien = $this->media_link_shortener( $_POST['link_zu_medien'] );

        $db_vehicles = $this->db_handler->load_vehicles();
        $vehicles = array();

        for ( $i = 0; $i < count( $db_vehicles ); $i++ ) {
            $name = $this->rename_db_vehicle_name( $db_vehicles[$i]->description );

            if ( isset( $_POST[$name] ) ) {
                $vehicles[] = $db_vehicles[$i]->id;
            }
        }

        if ( ! empty( $mission_id ) ) {
            //Update
            $wpdb->update(
                $table_missions,
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

            if ( function_exists("SimpleLogger") ) {
                SimpleLogger()->info( "Mission updated ".$alarm_stichwort );
            }

            //remove all vehicles bound to current mission!
            $this->db_handler->remove_vehicles_from_mission( $mission_id );

            //insert new values:
            foreach ( $vehicles as $vehicle ) {
               $this->db_handler->insert_new_vehicle_to_mission( $mission_id, $vehicle );
            }
        }else {
            //new mission entry
            $wpdb->insert(
                $table_missions,
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

            if ( function_exists("SimpleLogger") ) {
                SimpleLogger()->info( "Mission ".$alarm_stichwort." created" );
            }

            foreach ( $vehicles as $vehicle ) {
               $this->db_handler->insert_new_vehicle_to_mission( $id, $vehicle );
            }

            add_post_meta( $post_id, MISSION_ID, $id );
        }

        $current_post = array(
            'ID' => $post_id,
            'post_title' => $pn,
            'post_name' => date("Y_m", strtotime($alarmierung_datum)).'_'.$pn,
            'post_date' => date( $alarmierung_datum . ' ' . $alarmierung_zeit ),
            'post_date_gmt' => date( $alarmierung_datum . ' ' . $alarmierung_zeit )
        );

        remove_action( 'publish_mission', array( $this, 'save_data' ) );

        // Update the post into the database
        wp_update_post( $current_post );

        add_action( 'publish_mission', array( $this, 'save_data' ) );
    }

    /**
     * Delete mission data & associated post
     *
     * @return boolean
     * @author Andre Becker
     * */
    public function delete_mission( $post_id ) {

        if ( current_user_can( 'delete_posts' ) ) {
            $this->db_handler->delete_mission_by_post_id( $post_id );
        }
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

        if ( "DLK 23/12" == $name )
            $name = "dlk";

        $cleaned_name = str_replace( ' ', '', $name );
        $name = strtolower( $cleaned_name );

        return "fahrzeuge_" . $name;
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
            $('" . $id . "').val('" . $value . "');
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
            $('input[name=" . $value . "]').attr('checked', true);
        });
        </script>";
        echo $script;
    }
}

$wpEinsatzverwaltungCustomPost = new EinsatzverwaltungCustomPost();
?>