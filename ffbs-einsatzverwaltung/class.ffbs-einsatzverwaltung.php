<?php
/**
 * Main Class
 *
 * @author Andre Becker
 */
class Einsatzverwaltung {
    protected $pluginPath;

    public function __construct() {
        $this->plugin_path = dirname(__FILE__);
        $this->db_handler = DatabaseHandler::get_instance();
        add_action( 'wp_enqueue_scripts', array( $this, 'add_styles' ) );
        add_action( 'plugins_loaded', array( $this, 'plugin_textdomain') );
        add_action( 'wp_footer', array( $this, 'postinfo_head' ) );
        add_shortcode( 'einsatzverwaltung', array( $this, 'my_einsatzverwaltung_handler' ) );
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

        // https://codex.wordpress.org/Creating_Tables_with_Plugins#Adding_an_Upgrade_Function

        flush_rewrite_rules();
        // $wpdb->show_errors();
        $table_vehicles =     $wpdb->prefix . "fahrzeuge";
        $table_missions =     $wpdb->prefix . "einsaetze";
        $table_missions_has_vehicles = $wpdb->prefix . "einsaetze_has_fahrzeuge";
        $table_wp_posts =    $wpdb->prefix . "posts";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        /*
        * SQL Create Tables
        *
        * No Foreign Keys: http://wordpress.stackexchange.com/questions/52783/dbdelta-support-for-foreign-key
        */

        $sql_vehicles = "CREATE TABLE IF NOT EXISTS ".$table_vehicles."
        (
            id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
            description         VARCHAR(25) NOT NULL,
            radio_call_name     VARCHAR(3) NOT NULL,
            location            VARCHAR(14) NOT NULL,
            PRIMARY KEY  (id)
        )
        CHARACTER SET utf8
        COLLATE utf8_general_ci;
        ";
        dbDelta( $sql_vehicles );

        $sql_missions = "CREATE TABLE IF NOT EXISTS $table_missions
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
        //     REFERENCES  $table_wp_posts (ID)
        //     ON DELETE NO ACTION
        //     ON UPDATE NO ACTION

        dbDelta( $sql_missions );

        $sql_missions_has_vehicles = "CREATE TABLE IF NOT EXISTS $table_missions_has_vehicles
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
        //      REFERENCES $table_missions (id)
        //      ON DELETE NO ACTION
        //      ON UPDATE NO ACTION,
        //    CONSTRAINT fk_einsaetze_has_fahrzeuge_fahrzeuge1
        //      FOREIGN KEY  (fahrzeuge_id)
        //      REFERENCES $table_vehicles (id)
        //      ON DELETE NO ACTION
        //      ON UPDATE NO ACTION
        dbDelta( $sql_missions_has_vehicles );

        // $wpdb->print_error();
    }

    public function plugin_deactivation() {
        flush_rewrite_rules();
    }

    public function display_missions() {

        $permalink = get_permalink();
        $years = $this->db_handler->get_mission_years();

        echo "<div>";
        echo    "<form action=\"$permalink\" method=\"post\">";
        echo        "<table>";
        echo            "<tr>Gewähltes Einsatzjahr:&nbsp;</tr>";
        echo            "<tr><select name=\"einsatzjahr\">";

        foreach ( $years as $year ) {
            echo "  <option value=\"" . $year . "\">" . $year . "</option>";
        }

        echo                "</select>";
        echo                "<input type=\"submit\" value=\"Anzeigen\" />";
        echo            "</tr>";
        echo        "</table>";
        echo    "</form>";
        echo "</div>";

        if ( !isset( $_POST['einsatzjahr'] ) ) {
            $missions = $this->db_handler->get_missions_by_year( CURRENT_YEAR );
        }
        else {
            $missions = $this->db_handler->get_missions_by_year( $_POST['einsatzjahr'] );
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
        // Paths
        $arrow_up_path = plugin_dir_url( __FILE__ ) . 'img/mini-nav-top.gif';

        // Ausgabe der Einsätze im aktuellen Jahr
        foreach ( $arr_months as $key => $value ) {

            $german_month = $this->get_german_month( $key );
            $count = count( $arr_months[$key] );

            //redesign with bootstrap
            echo "<br /> <div>
            <a name='$german_month'></a>
            <div class='table-responsive'>
            <table class='table mission-month' summary='Einsatzliste im Monat $german_month' border='0'>
                <caption class='mission-month-header'>$german_month
                    <a href='#Übersicht'>
                        <img src='" . $arrow_up_path . "' class='overview'/>
                    </a>
                </caption>
                <thead>
                    <tr>
                        <th scope='col' class='th-mission td-space-left'>Datum</th>
                        <th scope='col' class='th-mission'>Zeit</th>
                        <th scope='col' class='th-mission'>Art</th>
                        <th scope='col' class='th-mission'>Alarmstichwort</th>
                        <th scope='col' class='th-mission'>Einsatzort</th>
                        <th scope='col' class='th-mission'>Bericht</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td colspan='6'>Anzahl der Eins&auml;tze im Monat: <b>" . $count . "</b></td>
                    </tr>
                </tfoot>
                <tbody>";

            foreach ( $arr_months[$key] as $key => $value ) {
                echo "
                    <tr class='row-mission'>
                        <td class='td-space-left'>$value[4]</td>
                        <td>$value[5]</td>
                        <td class='td-text-center'>$value[0]</td>
                        <td class='td-mission-keyword'>$value[1]</td>
                        <td>$value[3]</td>
                        <td><a href=\"" . $value[10] . "\">$value[9]</a></td>
                    </tr>";
            }
            echo "
                </tbody>
                </table>
                </div>
                    <div class='footer-legend'>
                        BE - Brandeinsatz &#x95
                        TE - Technischer Einsatz &#x95
                        SE - Sonstiger Einsatz
                    </div>
                </div>";
        }
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

        $german_months = array(
            1=>"Januar",
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
     * Print overview of missions grouped by month
     *
     * @author Florian Wallburg, Andre Becker
     * */
    public function print_missions_month_overview( $arr_months ) {
        // START Attributes
        $mission_year = CURRENT_YEAR;

        if ( isset( $_POST['einsatzjahr'] ) )
            $mission_year = $_POST['einsatzjahr'];

        $mission_year_count = 0;

        foreach ( $arr_months as $key => $value ) {
            foreach ( $arr_months[$key] as $key => $value ) {
                $mission_year_count++;
            }
        }


        echo '<a name="Übersicht"></a>
            <div>
                <table class="mission-month-overview" summary="Übersicht über die Anzahl der Einsätze im Jahr ' . $mission_year . '">
                <caption>Monatsübersicht für '. $mission_year .'</caption>
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
                        <td colspan="5">Anzahl der Einsätze im Jahr: <b>' . $mission_year_count . '</b></td>
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


                if ( false !== strpos( $mission_value[0], 'BE' ) ) {
                    $count_brandeinsatz++;
                }
                elseif ( false !== strpos( $mission_value[0], 'TE' ) ) {
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
                    <td>' . $german_month . '</td>
                    <td class="td-text-center">' . $count_missions_in_month . '</td>
                    <td class="td-text-center">' . $count_brandeinsatz . '/' . $count_technischereinsatz . '/' . $count_sonstiges . '</td>
                    <td class="td-text-center"><a href="#' . $german_month . '">Link</a></td>
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

        //Check if mission category
        if ( 'mission' !== $post->post_type )
            return;

        $script = <<< EOF
<script type='text/javascript'>
    jQuery(document).ready(function($){
        $('.post-info').prependTo('.entry-content');
        $('.open-post-info').prependTo('.entry-content');

        $('.post-info').show();
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

        $mission = $this->db_handler->load_mission_by_post_id( $post->ID );
        $vehicles = $this->db_handler->load_vehicles_by_mission_id( $mission->id );

        $used_vehicles = "";

        for ( $i = 0; $i < count( $vehicles ); $i++ ) {
            if ( count( $vehicles ) -1 === $i ) {
                $used_vehicles .= $vehicles[$i]->description;
            } else {
                $used_vehicles .= $vehicles[$i]->description . " &#x95 ";
            }
        }

        if ( ( "Freitext" === $mission->alarmstichwort ) || ( "Sonstiger Brand" === $mission->alarmstichwort ) ) {
            $alarmstichwort = $mission->freitext;
        }
        else {
            $alarmstichwort = $mission->alarmstichwort;
        }

        echo '<p class="open-post-info" id="' . $post->post_name . '">Details</p>';
        echo '<div class="post-info post-info-' . $post->post_name . '">';
        echo '<ul>';
        echo    '<li class="alarmstichwort">';
        echo        "<b>Alarmstichwort:</b> " . $alarmstichwort;
        echo    '</li>';
        echo    '<li class="art_der_alarmierung">';
        echo        "<b>Art der Alarmierung:</b> " . $mission->art_alarmierung;
        echo    '</li>';
        echo    '<li class="alarmierung">';
        echo        "<b>Alarmierung:</b> " . strftime( "%d.%m.%Y", strtotime( $mission->alarmierung_date ) ) . " " . strftime( "%H:%M", strtotime( $mission->alarmierung_time ) );
        echo    '</li>';
        echo    '<li class="rueckkehr">';
        echo        "<b>R&uuml;ckkehr:</b> " . strftime( "%d.%m.%Y", strtotime( $mission->rueckkehr_date ) ) . " " . strftime( "%H:%M", strtotime( $mission->rueckkehr_time ) );
        echo    '</li>';
        echo    '<li class="einsatzort">';
        echo        "<b>Einsatzort:</b> " . $mission->einsatzort;
        echo    '</li>';
        echo    '<li class="eingesetzte_fahrzeuge">';
        echo        "<b>Eingesetzte Fahrzeuge:</b> " . $used_vehicles;
        echo    '</li>';
        echo    '<li class="link">';
        if ( empty( $mission->link_to_media ) ) {
            echo "<b>Quelle:</b> Nicht verf&uuml;gbar";
        }
        else {
            echo "<b>Quelle:</b> <a href='$link' target='_blank'>" . $mission->link_to_media . "</a>";
        }
        echo    '</li>';
        echo '</ul>';
        echo '</div>';
    }
}
?>