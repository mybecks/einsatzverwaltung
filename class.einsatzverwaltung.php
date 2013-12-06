<?php
// next to come: https://github.com/jkudish/WordPress-GitHub-Plugin-Updater
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

class Einsatzverwaltung {
    protected $pluginPath;

    public function __construct() {
        $this->pluginPath = dirname(__FILE__);

        add_action( 'wp_enqueue_scripts', array($this, 'add_styles' ));
        add_action( 'plugins_loaded', array($this, 'plugin_textdomain'));
        // add_action( 'publish_mission', array($this, 'einsatzverwaltung_save_data' ));
        add_action( 'wp_footer', array($this, 'postinfo_head' ));
        add_shortcode( 'einsatzverwaltung', array($this, 'my_einsatzverwaltung_handler' ));
    }

    public function add_styles() {
    // Respects SSL, style.css is relative to the current file
        wp_register_style( 'einsatzverwaltung-style', plugins_url( '/css/styles.css', __FILE__ ) );
        wp_enqueue_style( 'einsatzverwaltung-style' );
        wp_register_style( 'bootstrap-style', plugins_url( '/css/bootstrap.css', __FILE__ ) );
        wp_enqueue_style( 'bootstrap-style' );
    }

    public function add_scripts(){
    }

    public function my_einsatzverwaltung_handler( $atts, $content=null, $code="" ) {

        ob_start();

        //code 4 displaying
        $this->display_missions();

        $output_string = ob_get_contents();
        ob_end_clean();

        return $output_string;
    }

    public static function plugin_textdomain(){
        load_plugin_textdomain( 'einsatzverwaltung_textdomain', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
    }

    public function plugin_activation() {
        global $wpdb;
        // $wpdb->show_errors(); 
        $table_name_vehicles =     $wpdb->prefix . "fahrzeuge";
        $table_name_missions =     $wpdb->prefix . "einsaetze";
        $table_name_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";
        $table_name_wp_posts =    $wpdb->prefix . "posts";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        /*
        * SQL Create Tables
        *
        * No Foreign Keys: http://wordpress.stackexchange.com/questions/52783/dbdelta-support-for-foreign-key
        */

        $sql_vehicles = "CREATE TABLE IF NOT EXISTS ".$table_name_vehicles."  
        (
            id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
            description         VARCHAR(25) NOT NULL,
            PRIMARY KEY  (id)
        )
        CHARACTER SET utf8
        COLLATE utf8_general_ci;
        ";
        dbDelta( $sql_vehicles );
        
        $sql_missions = "CREATE TABLE IF NOT EXISTS $table_name_missions
        (
            id                  INT UNSIGNED NOT NULL AUTO_INCREMENT ,
            art_alarmierung     VARCHAR(25) NOT NULL ,
            alarmstichwort      VARCHAR(125) NOT NULL ,
            freitext            VARCHAR(125) NULL ,
            alarm_art           VARCHAR(45) NOT NULL ,
            einsatzort          VARCHAR(45) NOT NULL ,
            alarmierung_date    DATE NOT NULL ,
            alarmierung_time    TIME NOT NULL ,
            rueckkehr_date      DATE NULL ,
            rueckkehr_time      VARCHAR(45) NULL ,
            link_to_media       VARCHAR(255) NULL ,
            wp_posts_ID         INT UNSIGNED NOT NULL ,
            PRIMARY KEY  (id)
        )
        CHARACTER SET utf8
        COLLATE utf8_general_ci;
        ";
        // KEY fk_einsaetze_wp_posts1 (wp_posts_ID ASC),
        // CONSTRAINT fk_einsaetze_wp_posts1
        //     FOREIGN KEY (wp_posts_ID)
        //     REFERENCES  $table_name_wp_posts (ID)
        //     ON DELETE NO ACTION
        //     ON UPDATE NO ACTION

        dbDelta( $sql_missions );

        $sql_missions_has_vehicles = "CREATE TABLE IF NOT EXISTS $table_name_missions_has_vehicles
        (
            einsaetze_id        INT NOT NULL ,
            fahrzeuge_id        INT NOT NULL ,
            PRIMARY KEY  (einsaetze_id, fahrzeuge_id)
        )
        CHARACTER SET utf8
        COLLATE utf8_general_ci;
        ";
        // KEY fk_einsaetze_has_fahrzeuge_fahrzeuge1 (fahrzeuge_id ASC) ,
        // KEY fk_einsaetze_has_fahrzeuge_einsaetze (einsaetze_id ASC) ,
        //    CONSTRAINT fk_einsaetze_has_fahrzeuge_einsaetze
        //      FOREIGN KEY  (einsaetze_id)
        //      REFERENCES $table_name_missions (id)
        //      ON DELETE NO ACTION
        //      ON UPDATE NO ACTION,
        //    CONSTRAINT fk_einsaetze_has_fahrzeuge_fahrzeuge1
        //      FOREIGN KEY  (fahrzeuge_id)
        //      REFERENCES $table_name_vehicles (id)
        //      ON DELETE NO ACTION
        //      ON UPDATE NO ACTION
        dbDelta( $sql_missions_has_vehicles );

        // $wpdb->print_error();
}

public function display_missions() {

    // $selected_year = $_POST['einsatzjahr'];
    $permalink = get_permalink();
    $years = $this->get_mission_years();

    echo "<div>";
    echo    "<form action=\"$permalink\" method=\"post\">";
    echo        "<table>";
    echo            "<tr>Gewähltes Einsatzjahr:&nbsp;</tr>";
    echo            "<tr><select name=\"einsatzjahr\">";

    foreach ( $years as $year ) {
        echo "  <option value=\"".$year."\">".$year."</option>";
    }

    echo                "</select>";
    echo                "<input type=\"submit\" value=\"Anzeigen\" />";
    echo            "</tr>";
    echo        "</table>";
    echo    "</form>";
    echo "</div>";

    if ( !isset( $_POST['einsatzjahr'] ) ) {
        $missions = $this->get_missions_by_year( CURRENT_YEAR );
    }
    else {
        $missions = $this->get_missions_by_year( $_POST['einsatzjahr'] );
    }

    $this->print_missions_month_overview( $missions );
    $this->print_missions_by_year( $missions );
}


/**
 * Returns missions grouped by month for current year.
 *
 * @return array()
 * @author Andre Becker
 * */
public function print_missions_by_year( $arr_months ) {
    // Pfade
    $arrow_up_path = plugin_dir_url( __FILE__ )."img/mini-nav-top.gif";

    // Ausgabe der Einsätze im aktuellen Jahr
    foreach ( $arr_months as $key => $value ) {

        $german_month = $this->get_german_month( $key );
        $count = count( $arr_months[$key] );

        //redesign with bootstrap
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

        foreach ( $arr_months[$key] as $key => $value ) {
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
 * */
public function get_mission_years() {
    global $wpdb;
    $array = array();
    $table_name_missions = $wpdb->prefix . "einsaetze";

    $query = "SELECT YEAR(alarmierung_date) AS Year FROM ".$table_name_missions." GROUP BY Year DESC";

    $years = $wpdb->get_results( $query );

    foreach ( $years as $year ) {
        if ( $year->Year != 1970 )
            $array[] = $year->Year;
    }

    if(empty($array)){
        array_push($array, CURRENT_YEAR);
    }

    return $array;
}

/**
 * Transfers the english months to german
 *
 * @return array()
 * @author Florian Wallburg
 * */
public function get_german_month( $english_month_2number ) {


    // $dateFormat = new LocaleDateFormat('MMMM'); # Long Month Names
    // $date = new DateTime(); # Now
 //    $month = $dateFormat->localeFormat(LOCALE, $date);
 //    wp_die($month. ' # '. $english_month_2number);

    $german_months = array( 1=>"Januar",
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
        12=>"Dezember" );
    $english_month_2number = ltrim( $english_month_2number, "0" );

    return $german_months[$english_month_2number];
}

/**
 * Einsätze nach Jahr sammeln
 *
 * @return array()
 * @author Andre Becker
 * */
public function get_missions_by_year( $year ) {
    global $wpdb;
    $table_name_missions = $wpdb->prefix . "einsaetze";

    $arr_months = array();


    $query = "SELECT id, art_alarmierung, alarmstichwort, alarm_art, einsatzort, alarmierung_date, alarmierung_time, rueckkehr_date, rueckkehr_time, link_to_media, wp_posts_ID, MONTH(alarmierung_date) AS Month, freitext ".
        "FROM ".$table_name_missions.
        " WHERE YEAR(alarmierung_date) = ".$year.
        " ORDER BY alarmierung_date DESC, alarmierung_time DESC";

    $missions = $wpdb->get_results( $query );
    
    foreach ( $missions as $mission ) {

        //http://stackoverflow.com/questions/1195549/php-arrays-and-solution-to-undefined-index-errors

        if( !array_key_exists($mission->Month, $arr_months)){
            $arr_months[$mission->Month] = array();
        }

        foreach ( $arr_months as $key => $value ) {

            if ( $key == $mission->Month ) {

                $tmp_arr = $arr_months[$key];

                $arr_content = array();

                $post = get_post( $mission->wp_posts_ID );


                if ( strlen( $post->post_content )!=0 ) {
                    $description = "Bericht";
                }
                else {
                    $description = "Kurzinfo";
                }


                if ( 'Freitext' == $mission->alarmstichwort || 'Sonstiger Brand' == $mission->alarmstichwort ) {
                    $alarmstichwort = $mission->freitext;
                }else {
                    $alarmstichwort = $mission->alarmstichwort;
                }

                // if(strlen($alarmstichwort) > 22) {
                //  // Shortening the string to 22 characters
                //  $alarmstichwort = substr($alarmstichwort,0,22)."…";
                // }


                if ( strpos( $mission->art_alarmierung, 'Brandeinsatz' ) !== false ) {
                    $alarm_short = 'BE';
                }else if ( strpos( $mission->art_alarmierung, 'Technischer Einsatz' ) !== false ) {
                        $alarm_short = 'TE';
                    }else {
                    $alarm_short = 'SE';
                }

                $arr_content[0] = $alarm_short;
                $arr_content[1] = $alarmstichwort;
                $arr_content[2] = $mission->alarm_art;
                $arr_content[3] = $mission->einsatzort;
                $arr_content[4] = strftime( "%d.%m.%Y", strtotime( $mission->alarmierung_date ) );
                $arr_content[5] = strftime( "%H:%M", strtotime( $mission->alarmierung_time ) );
                $arr_content[6] = $mission->rueckkehr_date;
                $arr_content[7] = $mission->rueckkehr_time;
                $arr_content[8] = $mission->link_to_media;
                $arr_content[9] = $description;
                $arr_content[10] = get_permalink( $mission->wp_posts_ID );

                array_push( $tmp_arr, $arr_content );

                $arr_months[$key] = $tmp_arr;
            }
        }
    }

    // Umgekehrte Sortierung der Monate (12,11,10,...,1)
    krsort( $arr_months );

    return $arr_months;
}

/**
 * Print overview of missions grouped by month
 *
 * @author Florian Wallburg, Andre Becker
 * */
public function print_missions_month_overview( $arr_months ) {
    // START Attributes
    $mission_year = CURRENT_YEAR;

    if ( isset($_POST['einsatzjahr']) )
        $mission_year = $_POST['einsatzjahr'];

    $mission_year_count = 0;

    foreach ( $arr_months as $key => $value ) {
        foreach ( $arr_months[$key] as $key => $value ) {
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

    foreach ( $arr_months as $key => $value ) {
        // START Amount of missions in the month
        $count_missions_in_month = count( $arr_months[$key] );
        // END

        // START Ratio of false alarms and real missions
        $count_brandeinsatz = 0;
        $count_technischereinsatz = 0;
        $count_sonstiges = 0;

        foreach ( $value as $mission_key => $mission_value ) {


            if ( strpos( $mission_value[0], 'BE' ) !== false ) {
                $count_brandeinsatz++;
            }
            elseif ( strpos( $mission_value[0], 'TE' ) !== false ) {
                $count_technischereinsatz++;
            }
            else {
                $count_sonstiges++;
            }
        }

        // OUTPUT
        $german_month = $this->get_german_month( $key );
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
 * */
public function postinfo_head() {
    global $post;

    // $cat_id = get_the_category( $post->ID );

    //Check if mission category
    if ( 'mission' !== $post->post_type )
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
    $this->postinfo();
}

/**
 * Ausgabe der Detailinformationen zu einem Einsatz
 *
 * @author Florian Wallburg
 * */
public function postinfo() {
    global $post;

    $mission = $this->einsatzverwaltung_load_mission_by_post_id( $post->ID );
    $vehicles = $this->einsatzverwaltung_load_vehicles_by_mission_id( $mission->id );

    $used_vehicles = "";

    for ( $i=0; $i<count( $vehicles ); $i++ ) {
        if($i === count( $vehicles ) -1 ){
            $used_vehicles .= $vehicles[$i]->description;
        }else{
            $used_vehicles .= $vehicles[$i]->description." &#x95 ";
        }       
    }

    if ( ( $mission->alarmstichwort == "Freitext" ) || ( $mission->alarmstichwort == "Sonstiger Brand" ) ) {
        $alarmstichwort = $mission->freitext;
    }
    else {
        $alarmstichwort = $mission->alarmstichwort;
    }

    echo '<p class="open-post-info" id="'. $post->post_name .'">Details</p>';
    echo '<div class="post-info post-info-'. $post->post_name .'">';
    echo '<ul>';
    echo    '<li class="alarmstichwort">';
    echo        "<b>Alarmstichwort:</b> ".$alarmstichwort;
    echo    '</li>';
    echo    '<li class="art_der_alarmierung">';
    echo        "<b>Art der Alarmierung:</b> ".$mission->art_alarmierung;
    echo    '</li>';
    echo    '<li class="alarmierung">';
    echo        "<b>Alarmierung:</b> ".strftime( "%d.%m.%Y", strtotime( $mission->alarmierung_date ) )." ".strftime( "%H:%M", strtotime( $mission->alarmierung_time ) );
    echo    '</li>';
    echo    '<li class="rueckkehr">';
    echo        "<b>R&uuml;ckkehr:</b> ".strftime( "%d.%m.%Y", strtotime( $mission->rueckkehr_date ) )." ".strftime( "%H:%M", strtotime( $mission->rueckkehr_time ) );
    echo    '</li>';
    echo    '<li class="einsatzort">';
    echo        "<b>Einsatzort:</b> ".$mission->einsatzort;
    echo    '</li>';
    echo    '<li class="eingesetzte_fahrzeuge">';
    echo        "<b>Eingesetzte Fahrzeuge:</b> ".$used_vehicles;
    echo    '</li>';
    echo    '<li class="link">';
    if ( empty( $mission->link_to_media ) ) {
        echo "<b>Quelle:</b> "."Nicht verf&uuml;gbar";
    }
    else {
        echo "<b>Quelle:</b> <a href='$link' target='_blank'>".$mission->link_to_media."</a>";
    }
    echo    '</li>';
    echo '</ul>';
    echo '</div>';
}














}

// $einsatzverwaltung = new Einsatzverwaltung();

// register_activation_hook( __FILE__, array( $einsatzverwaltung, 'einsatzverwaltung_install') );
// register_deactivation_hook( __FILE__, array( 'Einsatzverwaltung', 'einsatzverwaltung_deinstall' ) );





?>
