<?php
/**
 * Created by PhpStorm.
 * User: San Nguyen
 * Date: 14.04.2019
 * Time: 23:46
 */


if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class Categories_List extends WP_List_Table {

    /** Class constructor */
    public function __construct() {

        parent::__construct( [
            'singular' => __( 'Category', 'sp' ), //singular name of the listed records
            'plural'   => __( 'Categories', 'sp' ), //plural name of the listed records
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
    public static function get_categories( $per_page = 5, $page_number = 1 ) {

        global $wpdb;

        $sql = "SELECT * FROM {$wpdb->prefix}f_categories";

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
    public static function delete_category( $id ) {
        global $wpdb;

        $wpdb->query("DELETE FROM {$wpdb->prefix}f_categories
                      WHERE cat_name = '$id'");
    }


    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count() {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}f_categories";

        return $wpdb->get_var( $sql );
    }


    /** Text displayed when no category data is available */
    public function no_items() {
        _e( 'No categories avaliable.', 'sp' );
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
            case 'cat_name':
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
            '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['cat_name']
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

        $delete_nonce = wp_create_nonce( 'sp_delete_category' );

        $title = '<strong>' . $item['cat_name'] . '</strong>';

        $actions = [
            'visit' => "<a href='". get_site_url() . '/topics/?cat_name=' . $item['cat_name'] ."'>Visit</a>",
            'delete' => sprintf(
                '<a href="?page=%s&action=%s&category=%s&_wpnonce=%s">Delete</a>',
                esc_attr( $_REQUEST['page'] ),
                'delete',
                $item['cat_name'],
                $delete_nonce )
        ];

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
            'name'    => __( 'Naam', 'sp' ),
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
            'cat_name' => array( 'cat_name', true )
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
            'bulk-delete' => 'Delete'
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

        $per_page     = $this->get_items_per_page( 'categories_per_page', 5 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args( [
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ] );

        $this->items = self::get_categories( $per_page, $current_page );
    }

    public function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if ( 'delete' === $this->current_action() ) {

            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'sp_delete_category' ) ) {
                die( 'Go get a life script kiddies' );
            }
            else {
                self::delete_category($_GET['category']);

                // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
                // add_query_arg() return the current url
                wp_redirect( $_SERVER["HTTP_REFERER"] );
                exit;
            }

        }

        // If the delete bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
            || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
        ) {

            $delete_ids = esc_sql( $_POST['bulk-delete'] );

            // loop over the array of record IDs and delete them
            foreach ( $delete_ids as $id ) {
                self::delete_category( $id );

            }

            // esc_url_raw() is used to prevent converting ampersand in url to "#038;"
            // add_query_arg() return the current url
            wp_redirect( esc_url_raw(add_query_arg()) );
            exit;
        }
    }

}


class SP_Plugin {

    // class instance
    static $instance;

    // category WP_List_Table object
    public $categories_obj;

    // class constructor
    public function __construct() {
        add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
        add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
    }


    public static function set_screen( $status, $option, $value ) {
        return $value;
    }

    public function plugin_menu() {

        $hook = add_menu_page(
            'Forum+Chat',
            'Forum+Chat',
            'manage_categories',
            'sn_categories',
            [ $this, 'plugin_settings_page' ],
            'dashicons-excerpt-view',
            5
        );

        add_action( "load-$hook", [ $this, 'screen_option' ] );

        add_submenu_page(
            'sn_categories',
            'Categorieën',
            'Categorieën',
            'manage_categories',
            'sn_categories',
            [ $this, 'plugin_settings_page' ]
        );

        add_submenu_page(
                'sn_categories',
                'Voeg nieuw categorie toe',
                'Voeg nieuw categorie toe',
                'manage_categories',
                'sn_categories_add',
                [ $this, 'categories_add' ]
            );


    }


    /**
     * Plugin settings page
     */
    public function plugin_settings_page() {
        ?>
        <div class="wrap">
            <h2>Categorieën
                <a href="<?php echo get_site_url() . "/wp-admin/admin.php?page=sn_categories_add" ?>" class="page-title-action">Voeg nieuw toe</a>
            </h2>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post">
                                <?php
                                $this->categories_obj->prepare_items();
                                $this->categories_obj->display(); ?>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>
        <?php
    }


    public function categories_add()
    {
        include __DIR__ . '/category-add.php';
    }



    /**
     * Screen options
     */
    public function screen_option() {

        $option = 'per_page';
        $args   = [
            'label'   => 'Categories',
            'default' => 5,
            'option'  => 'categories_per_page'
        ];

        add_screen_option( $option, $args );

        $this->categories_obj = new Categories_List();
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
    SP_Plugin::get_instance();
} );