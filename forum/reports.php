<?php
/**
 * Created by PhpStorm.
 * User: San Nguyen
 * Date: 18.05.2019
 * Time: 18:40
 */


if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class Report_List extends WP_List_Table {

    /** Class constructor */
    public function __construct() {

        parent::__construct( [
            'singular' => __( 'Report', 'sp' ), //singular name of the listed records
            'plural'   => __( 'Report', 'sp' ), //plural name of the listed records
            'ajax'     => false //does this table support ajax?
        ] );

    }


    /**
     * Retrieve categories data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_reports( $per_page = 5, $page_number = 1 ) {

        global $wpdb;

        $sql = "SELECT report_id,
                       COALESCE(user_id, user_from_id) as user_id, 
                       COALESCE(post_message, message_body) AS text_original, 
                       {$wpdb->prefix}reports.post_id, 
                       {$wpdb->prefix}reports.message_id,
                       {$wpdb->prefix}reports.create_timestamp
                FROM ({$wpdb->prefix}reports LEFT OUTER JOIN {$wpdb->prefix}f_posts 
                              ON {$wpdb->prefix}f_posts.post_id = {$wpdb->prefix}reports.post_id)
                      LEFT OUTER JOIN {$wpdb->prefix}c_messages ON {$wpdb->prefix}reports.message_id = {$wpdb->prefix}c_messages.message_id";

        if ( ! empty( $_REQUEST['orderby'] ) ) {
            $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
            $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


        $result = $wpdb->get_results( $sql, 'ARRAY_A' );

        return $result;
    }


    /**
     * Delete a category record.
     *
     * @param int $id category ID
     */
    public static function delete_report( $id ) {
        global $wpdb;

        $wpdb->query("DELETE FROM {$wpdb->prefix}reports
                      WHERE report_id = '$id'");
    }


    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count() {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}reports";

        return $wpdb->get_var( $sql );
    }


    /** Text displayed when no category data is available */
    public function no_items() {
        _e( 'No reports avaliable.', 'sp' );
    }


    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'name':
                $user_info = new WP_User( $item['user_id'] );
                return $user_info->user_login;
            case 'text_original':
            case 'create_timestamp':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['report_id']
        );
    }


    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    function column_name( $item ) {
        $user_info = new WP_User( $item['user_id'] );

        $delete_nonce = wp_create_nonce( 'sp_delete_report' );
        $ignore_nonce = wp_create_nonce( 'sp_ignore_report' );
        $ban_nonce = wp_create_nonce( 'sp_ban_report' );

        $title = '<strong>' . $user_info->user_login . '</strong>';

        $actions = [
            'ignore' => sprintf(
                '<a href="?page=%s&action=%s&report=%s&_wpnonce=%s">Ignore</a>',
                esc_attr( $_REQUEST['page'] ),
                'ignore',
                $item['report_id'],
                $ignore_nonce ),
            'delete' => sprintf(
                '<a href="?page=%s&action=%s&report=%s&post_id=%s&message_id=%s&_wpnonce=%s">Delete</a>',
                esc_attr( $_REQUEST['page'] ),
                'delete',
                $item['report_id'],
                $item['post_id'],
                $item['message_id'],
                $delete_nonce ),
            'ban' => sprintf(
                "<a style='color: rgb(195, 3, 255);' href='?page=%s&action=%s&report=%s&post_id=%s&message_id=%s&user_id=%s&_wpnonce=%s'>Ban</a>",
                esc_attr( $_REQUEST['page'] ),
                'ban',
                $item['report_id'],
                $item['post_id'],
                $item['message_id'],
                $item['user_id'],
                $ban_nonce ),
        ];

        if (((array)( $user_info->roles )[0])[0] == 'administrator')  unset($actions['ban']);

        return $title . $this->row_actions( $actions );
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns() {
        $columns = [
            'cb'      => '<input type="checkbox" />',
            'name'    => __( 'Username', 'sp' ),
            'text_original'    => __( 'Text', 'sp' ),
            'create_timestamp'    => __( 'Time', 'sp' )
        ];

        return $columns;
    }


    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'create_timestamp' => array( 'create_timestamp', true )
        );

        return $sortable_columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = [
            'bulk-ignore' => 'Ignore',
            'bulk-delete' => 'Delete',
            'bulk-ban' => 'Ban'
        ];

        return $actions;
    }


    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

        $this->_column_headers = $this->get_column_info();

        /** Process bulk action */
        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'reports_per_page', 5 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args( [
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ] );

        $this->items = self::get_reports( $per_page, $current_page );
    }

    public function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if ( 'ignore' === $this->current_action() ) {
            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'sp_ignore_report' ) ) {
                die( 'Go get a life script kiddies' );
            }
            else {
                self::delete_report($_GET['report']);
                wp_redirect( $_SERVER["HTTP_REFERER"] );
                exit;
            }
        }

        if ( 'delete' === $this->current_action() ) {
            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'sp_delete_report' ) ) {
                die( 'Go get a life script kiddies' );
            }
            else {
                global $wpdb;

                $wpdb->query("DELETE FROM {$wpdb->prefix}reports
                      WHERE report_id = $_GET[report]");

                $wpdb->query("DELETE FROM {$wpdb->prefix}f_posts
                      WHERE post_id = $_GET[post_id]");

                $wpdb->query("DELETE FROM {$wpdb->prefix}c_messages
                      WHERE message_id = $_GET[message_id]");

                // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
                // add_query_arg() return the current url
                wp_redirect( $_SERVER["HTTP_REFERER"] );
                exit;
            }
        }

        if ( 'ban' === $this->current_action() ) {
            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'sp_ban_report' ) ) {
                die( 'Go get a life script kiddies' );
            }
            else {
                global $wpdb;

                $wpdb->query("DELETE FROM {$wpdb->prefix}reports
                      WHERE report_id = $_GET[report]");

                $wpdb->query("DELETE FROM {$wpdb->prefix}f_posts
                      WHERE post_id = $_GET[post_id]");

                $wpdb->query("DELETE FROM {$wpdb->prefix}c_messages
                      WHERE message_id = $_GET[message_id]");

                update_user_option( $_GET['user_id'], 'sn_banned', true, false );

                wp_redirect( $_SERVER["HTTP_REFERER"] );
                exit;
            }
        }

        // If the delete bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-ignore' )
            || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-ignore' )
        ) {
            $delete_ids = esc_sql( $_POST['bulk-delete'] );

            // loop over the array of record IDs and delete them
            foreach ( $delete_ids as $id ) {
                self::delete_report( $id );
            }

            // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
            // add_query_arg() return the current url
            wp_redirect( esc_url_raw(add_query_arg()) );
            exit;
        }

        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
            || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
        ) {
            $delete_ids = esc_sql( $_POST['bulk-delete'] );

            // loop over the array of record IDs and delete them
            foreach ( $delete_ids as $id ) {

                global $wpdb;
                $item = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}reports WHERE report_id=$id");

                $wpdb->query("DELETE FROM {$wpdb->prefix}reports
                      WHERE report_id = $id");

                $wpdb->query("DELETE FROM {$wpdb->prefix}f_posts
                      WHERE post_id = {$item->post_id}");

                $wpdb->query("DELETE FROM {$wpdb->prefix}c_messages
                      WHERE message_id = {item->message_id}");
            }

            // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
            // add_query_arg() return the current url
            wp_redirect( esc_url_raw(add_query_arg()) );
            exit;
        }

        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-ban' )
            || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-ban' )
        ) {
            $delete_ids = esc_sql( $_POST['bulk-delete'] );

            // loop over the array of record IDs and delete them
            foreach ( $delete_ids as $id ) {

                global $wpdb;
                $item = $wpdb->get_row("SELECT report_id,
                       COALESCE(user_id, user_from_id) as user_id, 
                       {$wpdb->prefix}reports.post_id, 
                       {$wpdb->prefix}reports.message_id
                      FROM ({$wpdb->prefix}reports LEFT OUTER JOIN {$wpdb->prefix}f_posts 
                              ON {$wpdb->prefix}f_posts.post_id = {$wpdb->prefix}reports.post_id)
                      LEFT OUTER JOIN {$wpdb->prefix}c_messages 
                              ON {$wpdb->prefix}reports.message_id = {$wpdb->prefix}c_messages.message_id
                      WHERE report_id = $id;");



                $wpdb->query("DELETE FROM {$wpdb->prefix}reports
                  WHERE report_id = $id");

                $wpdb->query("DELETE FROM {$wpdb->prefix}f_posts
                  WHERE post_id = {$item->post_id}");

                $wpdb->query("DELETE FROM {$wpdb->prefix}c_messages
                  WHERE message_id = {item->message_id}");

                update_user_option($item->user_id, 'sn_banned', true, false);

            }

            // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
            // add_query_arg() return the current url
            wp_redirect( esc_url_raw(add_query_arg()) );
            exit;
        }

    }

}


class SP_Plugin_Report {

    // class instance
    static $instance;

    // category WP_List_Table object
    public $reports_obj;

    // class constructor
    public function __construct() {
        add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
        add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
    }


    public static function set_screen( $status, $option, $value ) {
        return $value;
    }

    public function plugin_menu() {

        $hook = add_submenu_page(
            'sn_categories',
            'Report',
            'Report',
            'manage_categories',
            'sn_report',
            [ $this, 'reports_page' ]
        );
        add_action( "load-$hook", [ $this, 'screen_option' ] );

    }


    /**
     * Plugin settings page
     */
    public function reports_page() {
        ?>
        <div class="wrap">
            <h2>Report
                <a href="<?php echo get_site_url() . "/wp-admin/admin.php?page=sn_report_add" ?>" class="page-title-action">Add new</a>
            </h2>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post">
                                <?php
                                $this->reports_obj->prepare_items();
                                $this->reports_obj->display(); ?>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>
        <?php
    }

    /**
     * Screen options
     */
    public function screen_option() {

        $option = 'per_page';
        $args   = [
            'label'   => 'Reports',
            'default' => 5,
            'option'  => 'reports_per_page'
        ];

        add_screen_option( $option, $args );

        $this->reports_obj = new Report_List();
    }


    /** Singleton instance */
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

}


add_action( 'plugins_loaded', function () {
    SP_Plugin_Report::get_instance();
} );