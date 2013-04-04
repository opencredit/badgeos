<?php
/**
 * Credly Integration
 *
 * @package BadgeOS
 * @subpackage Credly
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

//Check if we've defined our url elsewhere, if not, set it here.
if ( !defined( 'BADGEOS_CREDLY_API_URL' ) )
    define( 'BADGEOS_CREDLY_API_URL', 'https://api.credly.com/v0.2/' );

/**
 * Build our Credly object
 *
 * @since 1.0.0
 */
class BadgeOS_Credly {

    function __construct() {

        // Credly API
        $this->api_url                 = BADGEOS_CREDLY_API_URL;

        // Set our options based on our Credly settings
        $this->credly_settings         = get_option( 'credly_settings' );
        $this->api_key                 = ( isset( $this->credly_settings['api_key'] ) ) ? $this->credly_settings['api_key'] : '';
        $this->field_title             = ( isset( $this->credly_settings['credly_badge_title'] ) ? $this->credly_settings['credly_badge_title'] : 'post_title' ) ;
        $this->field_short_description = ( isset( $this->credly_settings['credly_badge_short_description'] ) ? $this->credly_settings['credly_badge_short_description'] : 'post_excerpt' );
        $this->field_description       = ( isset( $this->credly_settings['credly_badge_description'] ) ? $this->credly_settings['credly_badge_description'] : 'post_body' );
        $this->field_criteria          = ( isset( $this->credly_settings['credly_badge_criteria'] ) ? $this->credly_settings['credly_badge_criteria'] : '' );
        $this->field_category          = ( isset( $this->credly_settings['credly_badge_category'] ) ? $this->credly_settings['credly_badge_category'] : '' );
        $this->field_image             = ( isset( $this->credly_settings['credly_badge_image'] ) ? $this->credly_settings['credly_badge_image'] : 'featured_image' );
        $this->field_testimonial       = ( isset( $this->credly_settings['credly_badge_testimonial'] ) ? $this->credly_settings['credly_badge_testimonial'] : '' );
        $this->field_evidence          = ( isset( $this->credly_settings['credly_badge_evidence'] ) ? $this->credly_settings['credly_badge_evidence'] : '' );
        $this->send_email              = ( isset( $this->credly_settings['credly_badge_sendemail'] ) ? $this->credly_settings['credly_badge_sendemail'] : true );

        // Set our user settings
        $this->user_id                 = get_current_user_id();
        $this->user_enabled            = ( 'false' === get_user_meta( $this->user_id, 'credly_user_enable', true ) ? 'false' : 'true' );

        // Hook in to WordPress
        $this->hooks();

    }

    /**
     * Add any hooks into WordPress here
     *
     * @since 1.0.0
     */
    public function hooks() {

        //admin notice
        add_action( 'admin_notices', array( $this, 'credly_admin_notice' ) );

        // Badge Metabox
        add_action( 'add_meta_boxes', array( $this, 'badge_metabox_add' ) );
        add_action( 'save_post', array( $this, 'badge_metabox_save' ) );

        // Category search AJAX
        add_action( 'wp_ajax_search_credly_categories', array( $this, 'credly_category_search' ) );

        // Credly enable user meta setting
        add_action( 'personal_options', array( $this, 'credly_profile_setting' ), 99 );
        add_action( 'personal_options_update', array( $this, 'credly_profile_setting_save' ) );
        add_action( 'init', array( $this, 'credly_profile_setting_force_enable' ), 999 );

        // Update Credly ID on profile save
        add_action( 'personal_options_update', array( $this, 'credly_get_user_id' ) );

    }

    /**
     * Display an admin notice if there is no Credly API key saved and Credly is enabled
     *
     * @since  1.0.0
     * @return string      Admin notice if API key is empty
     */
    public function credly_admin_notice() {

        $credly_settings = get_option( 'credly_settings' );

        //check if Credly is enabled and if an API key exists
        if ( empty( $credly_settings ) || 'false' === $credly_settings['credly_enable'] || ! empty( $credly_settings['api_key'] ) )
            return;

        //display the admin notice
        printf( __( '<div class="updated"><p>Note: Credly Integration is turned on, but you must first <a href="%s">enter your Credly credentials</a> to allow earned badges to be shared and by recipients (or Disable Credly Integration to hide this notice).</p></div>', 'badgeos' ), admin_url( 'admin.php?page=badgeos_sub_credly_integration' ) );

    }

    /**
     * Append our url with our access token
     *
     * @since  1.0.0
     * @param  string $url Our base url path
     * @return string      Our base url path with API token parameter
     */
    private function api_url_with_token( $url ) {

        $url .= '?access_token=' . $this->api_key;

        return $url;

    }


    /**
     * Generate our base url for badge create/update
     *
     * @since  1.0.0
     * @param  string $badge_id The ID of our existing Credly badge if it exists
     * @return string           Our url including base and badge slug
     */
    private function api_url_badge( $badge_id ) {

        if ( ! empty( $badge_id ) ) {

            $url = $this->api_url . 'badges/' . $badge_id;

        } else {

            $url = $this->api_url . 'badges';

        }

        return $url;

    }

    /**
     * Generate our base url for category queries
     *
     * @since  1.0.0
     * @return string  Our URL including base and category slug
     */
    private function api_url_category() {

        $url = $this->api_url . 'badges/categories';

        return $url;

    }

    /**
     * Generate our base url for email search queries
     *
     * @since  1.0.0
     * @return string  Our URL including base and members search slug
     */
    private function api_url_check_email() {

        $url = $this->api_url . 'members';

        return $url;

    }

    /**
     * Generate our base url for posting user badges
     *
     * @since  1.0.0
     * @return string  Our URL including base and member badges slug
     */
    private function api_url_user_badge() {

        $url = $this->api_url . 'member_badges';

        return $url;

    }

    /**
     * Wrapper for Credly API POST requests
     *
     * @since  1.0.0
     * @param  string $url  The URL we're posting to
     * @param  array  $body An array of data we're passing in the post body
     * @return array        Array of results
     */
    private function credly_api_post( $url, $body ) {

        $response = wp_remote_post( $url, array(
            'method'      => 'POST',
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(),
            'body'        => $body,
            'cookies'     => array()
            )
        );

        return $response;

    }

    /**
     * Wrapper for Credly API GET requests
     *
     * @since  1.0.0
     * @param  string $url The URL we're getting from
     * @return array       Array of results
     */
    private function credly_api_get( $url ) {

        $response = wp_remote_get( $url );

        return $response;

    }

    /**
     * Create or update a badge on Credly
     *
     * @since  1.0.0
     * @param  string  $badge_id The post ID of our badge
     * @param  array   $fields   An array of meta fields from our badge post
     * @return mixed             False on error. The Credly ID for our badge on success
     */
    function post_credly_badge( $badge_id = '', $fields ){

        // Set array of parameters for API call
        $body = $this->post_credly_badge_args( $badge_id, $fields );

        // Generate our API URL endpoint
        $url = $this->api_url_with_token( $this->api_url_badge( $fields['credly_badge_id'] ) );

        // POST our data to the CredlyAPI
        $response = $this->credly_api_post( $url, $body );

        // Process our response
        $results = $this->process_api_response_badge( $response );

        return $results;

    }

    /**
     * Generate the array for the Credly badge API call
     *
     * @since  1.0
     *
     * @param  string  $badge_id The post of our badge
     * @param  array   $fields   Our array of fields
     *
     * @return array             An array of args for our API call
     */
    function post_credly_badge_args( $badge_id = '', $fields ) {

        $attachment        = $this->encoded_image( credly_fieldmap_get_field_value( $badge_id, $this->field_image ) );

        $title             = credly_fieldmap_get_field_value( $badge_id, $this->field_title );
        $title             = ( strlen( $title ) > 128 ? ( substr( $title, 0, 124 ) . '...' ) : $title );

        $short_description = credly_fieldmap_get_field_value( $badge_id, $this->field_short_description );
        $short_description = ( strlen( $short_description ) > 128 ? ( substr( $short_description, 0, 124 ) . '...' ) : $short_description );

        $description       = credly_fieldmap_get_field_value( $badge_id, $this->field_description );
        $description       = ( strlen( $description ) > 500 ? ( substr( $description, 0, 496 ) . '...' ) : $description );

        $criteria          = credly_fieldmap_get_field_value( $badge_id, $this->field_criteria );
        $criteria          = ( strlen( $criteria ) > 500 ? ( substr( $criteria, 0, 496 ) . '...' ) : $criteria );

        $is_giveable       = ( 'true' == $fields['credly_is_giveable'] ? true : false );

        $expires_in        = ( is_numeric( $fields['credly_expiration'] ) ? (int) $fields['credly_expiration'] * 3600 : 0 );

        $categories        = ( $fields['credly_categories'] ? implode( ',',  $fields['credly_categories'] ) : '' );

        $args = array(
            'attachment'        => $attachment, // base64 encoded string
            'title'             => $title, // string; limit 128
            'short_description' => $short_description, // string; limit 128
            'description'       => $description, // string; limit 500
            'criteria'          => $criteria, // string; limit 500
            'is_giveable'       => $is_giveable, // boolean
            'expires_in'        => $expires_in, // int; in seconds
            'categories'        => $categories, // comma separated string of ids
            );

        // Remove array keys with an empty value
        $args = array_diff( $args, array( '' ) );

        return $args;

    }

    /**
     * Process the results of our badge create/update API call
     *
     * @since  1.0.0
     * @param  array $response An array of fields returned from the API
     * @return mixed           False on wp_error. Credly badge ID on success
     */
    private function process_api_response_badge( $response = array() ) {

        if( is_wp_error( $response ) || empty( $response ) ) {

            return false;

        } else {

           $response_body = json_decode( $response['body'] );

           $credly_badge_id = $response_body->data;

           return $credly_badge_id;

        }

    }

    /**
     * Process our category search ajax call
     *
     * @since  1.0.0
     * @return void
     */
    public function credly_category_search() {

        // Sanitize our input
        $search_string = sanitize_text_field( $_REQUEST['search_terms'] );

        // Generate a url for our request
        $url  = $this->api_url_with_token( $this->api_url_category() );
        $url .= $this->category_search_args( $search_string );

        // API call
        $response = $this->credly_api_get( $url );

        // Process the results
        $results = $this->process_api_response_category( $response );

        // Send back our response
        echo json_encode( $results );
        die();

    }

    /**
     * Generate a url encoded string for our category api call
     *
     * @since  1.0.0
     * @param  string $search Our search term
     * @return string A url encoded string
     */
    private function category_search_args( $search = '' ) {

        $args = array(
            'query'           => $search,
            'page'            => 1,
            'per_page'        => 10,
            'order_direction' => 'ASC'
            );

        $url = '&' . http_build_query( $args );

        return $url;

    }

    /**
     * Process our category API response and generate required markup
     *
     * @since  1.0.0
     * @param  mixed $response An array of results or WP_Error object
     * @return mixed           A string of html markup or false on WP_Error
     */
    private function process_api_response_category( $response = array() ) {

        if( is_wp_error( $response ) || empty( $response ) ) {

            return false;

        } else {

            $response_body = json_decode( $response['body'] );

            $categories = $response_body->data;

            $markup = '';

            foreach ( $categories as $category ) {

                $markup .= '<label for="' . $category->name . '"><input type="checkbox" name="_badgeos_credly_categories[' . $category->name . ']" id="'. $category->name . '" value="' . $category->id . '" /> ' . ucwords( $category->name ) . '</label><br />';

            }

            return $markup;

        }

    }


    /**
     * Output existing saved Credly categories for our metabox
     *
     * @since  1.0.0
     * @param  array  $categories An array of category names and ids
     * @return string             A concatenated string of html markup
     */
    private function credly_existing_category_output( $categories ) {

        // Return if we don't have any categories saved in post meta
        if ( ! is_array( $categories ) )
            return;

        $markup = '';

        foreach ( $categories as $name => $value ) {

            $markup .= '<label for="' . $name . '"><input type="checkbox" name="_badgeos_credly_categories[' . $name . ']" id="'. $name . '" value="' . $value . '" checked="checked" /> ' . ucwords( $name ) . '</label><br />';
        }

        return $markup;

    }


    /**
     * Add a credly_user_enable checkbox to our user profile
     *
     * @since  1.0.0
     * @param  object  $user The WP_User object of the user being edited.
     * @return void
     */
    public function credly_profile_setting( $user ) {
    ?>

        <tr>
            <th scope="row"><?php _e( 'Badge Sharing', 'badgeos' ); ?></th>
            <td><label for="credly_user_enable"><input type="checkbox" name="credly_user_enable" value="true" <?php checked( $user->credly_user_enable, 'true' ); ?>/> <?php _e( 'Send eligible earned badges to Credly', 'badgeos' ); ?></td>
        </tr>

    <?php
    }


    /**
     * Process our credly_user_enable user meta setting
     *
     * @since  1.0.0
     * @param  int  $user_id The user ID of the user being edited
     * @return void
     */
    public function credly_profile_setting_save( $user_id ) {

        $credly_enable = ( ! empty( $_POST['credly_user_enable'] ) && $_POST['credly_user_enable'] == 'true' ? 'true' : 'false' );

        $this->credly_profile_setting_update_meta( $user_id, $credly_enable );

    }


    /**
     * Wrapper to update our user meta value
     *
     * @since  1.0.0
     * @param  string  $user_id The user ID we're updating
     * @param  string  $value   true if enabling, false if disabling
     * @return mixed            User meta id on success, false on failure
     */
    private function credly_profile_setting_update_meta( $user_id = '', $value = 'true' ) {

        // Check if we have a numeric user id, if not get current user
        $user_id = ( is_numeric( $user_id ) ? $user_id : $this->user_id );

        $updated = update_user_meta( $user_id, 'credly_user_enable', $value );

        return $updated;

    }


    /**
     * Enable Credly for the current if they don't have a true/false value set in their user meta
     *
     * @since  1.0.0
     * @return void
     */
    public function credly_profile_setting_force_enable() {

        $enabled = get_user_meta( $this->user_id, 'credly_user_enable', true );

        if ( empty( $enabled ) )
            $this->credly_profile_setting_update_meta();

    }


    /**
     * Gets our Credly user ID or defaults to user email
     *
     * @since  1.0.0
     * @param  int     User ID we're checking
     * @return string  A numeric user ID from credly or the user email
     */
    public function credly_get_user_id( $user_id = 0 ) {

        $user_id = ( ! empty( $user_id ) ? $user_id : $this->user_id );

        $user = get_userdata( $user_id );

        // If we're saving our profile use that value, otherwise the current user email
        $user_email = ( ! empty( $_POST['email'] ) ? $_POST['email'] : $user->user_email );

        // Run our search against the Credly API
        $credly_id = $this->credly_user_email_search( $user_email );

        // If we didn't return a numeric id, set it to the user email
        if ( ! is_numeric( $credly_id ) )
            $credly_id = $user_email;

        // Set our local meta
        $this->credly_user_set_id( $user_id, $credly_id );

        return $credly_id;

    }


    /**
     * Searches the credly API for a user email
     *
     * @since  1.0.0
     * @param  string  $email Email address for the current user
     * @return mixed          Numeric user ID on success, false on failure
     */
    private function credly_user_email_search( $email ) {

        // Generate a url for our request
        $url  = $this->api_url_with_token( $this->api_url_check_email() );
        $url .= $this->user_email_search_args( $email );

        // API call
        $response = $this->credly_api_get( $url );

        // Process the results
        $results = $this->process_api_response_email_search( $response );

        return $results;

    }


    /**
     * Generate a url encoded string for our email search api call
     *
     * @since  1.0.0
     * @param  string  $search The email address we're searching for
     * @return string          A url encoded string
     */
    private function user_email_search_args( $email = '' ) {

        $args = array(
            'email'           => $email,
            'page'            => 1,
            'per_page'        => 1,
            'order_direction' => 'ASC'
            );

        $url = '&' . http_build_query( $args );

        return $url;

    }


    /**
     * Process our email search API call_user_func(function)
     *
     * @since  1.0.0
     * @param  mixed  $response An array of results or WP_Error object
     * @return mixed            A numeric user id. False on WP_Error or no results returned
     */
    private function process_api_response_email_search( $response = array() ) {

        if( is_wp_error( $response ) || empty( $response ) ) {

            return false;

        } else {

            $response_body = json_decode( $response['body'] );

            $email = ( ! empty( $response_body->data[0]->id ) ? $response_body->data[0]->id : false );

            return $email;

        }

    }


    /**
     * Helper function to set Credly user ID
     *
     * @since  1.0.0
     * @param  string  $id The user ID value we're setting
     * @return void
     */
    private function credly_user_set_id( $id = '' ) {

        if ( ! empty( $id ) )
            update_user_meta( $this->user_id, 'credly_user_id', $id );

    }


    /**
     * Post a users earned badge to Credly
     *
     * @since  1.0.0
     * @param  int  $badge_id The badge ID the user is earning
     * @return string         Results of the API call
     */
    public function post_credly_user_badge( $user_id = 0, $badge_id ) {

        // Bail if the badge isn't in Credly
        if ( ! credly_is_achievement_giveable( $badge_id ) )
            return false;

        if ( empty( $user_id ) )
            $user_id = $this->user_id;

        // Generate our API URL endpoint
        $url = $this->api_url_with_token( $this->api_url_user_badge() );

        // Generate our args
        $body = $this->post_user_badge_args( $user_id, $badge_id );

        // POST our data to the Credly API
        $response = $this->credly_api_post( $url, $body );

        // Process our response
        $results = $this->process_api_response_user_badge( $response );


        if ( $results ) {

            $user = get_userdata( $user_id );
            $username = $user->user_login;
            $badge_name = get_the_title( $badge_id );

            badgeos_post_log_entry( $badge_id, $user_id, null, sprintf( "%s sent %s badge to Credly", $username, $badge_name ) );

        }

        return $results;

    }


    /**
     * Generate the array for user badge API call
     *
     * @since  1.0.0
     * @param  int  $user_id  The ID of the user earning a badge
     * @param  int  $badge_id The badge ID the user is earning
     * @return array          An array of args
     */
    private function post_user_badge_args( $user_id, $badge_id ) {

        $args = '';

        $user_id = ( ! empty( $user_id ) ? $user_id : $this->user_id );

        $credly_user_id = $this->credly_get_user_id( $user_id );

        $credly_badge_id = get_post_meta( $badge_id, '_badgeos_credly_badge_id', true );

        $testimonial = credly_fieldmap_get_field_value( $badge_id, $this->field_testimonial );
        $testimonial = ( strlen( $testimonial ) > 1000 ? ( substr( $testimonial, 0, 996 ) . '...' ) : $testimonial );

        if ( is_numeric( $credly_user_id ) ) {

            $args = array(
                'member_id'     => $credly_user_id,
                'badge_id'      => $credly_badge_id,
                'evidence_file' => credly_fieldmap_get_field_value( $badge_id, $this->field_evidence ),
                'testimonial'   => $testimonial,
                'notify'        => (bool) $this->send_email,
            );

        } elseif ( is_email( $credly_user_id ) ) {

            // Get the userdata object for our current user
            $user_info = get_userdata( $user_id );

            $args = array(
                'email'         => $credly_user_id,
                'first_name'    => $user_info->user_firstname,
                'last_name'     => $user_info->user_lastname,
                'badge_id'      => $credly_badge_id,
                'evidence_file' => credly_fieldmap_get_field_value( $badge_id, $this->field_evidence ),
                'testimonial'   => $testimonial,
                'notify'        => (bool) $this->send_email,
            );

        }

        // Remove array keys with an empty value
        $args = array_diff( $args, array( '' ) );

        return $args;

    }


    /**
     * Process the results of our post user badge API call
     *
     * @since  1.0.0
     * @param  array  $response An array of fields returned from the API
     * @return mixed  False on API error. Hash string on success
     */
    private function process_api_response_user_badge( $response = array() ) {

        if( is_wp_error( $response ) || empty( $response ) ) {

            return false;

        } else {

           $response_body = json_decode( $response['body'] );

           $results = ( ! empty( $response_body->data->hash ) ? $response_body->data->hash : false );

           return $results;

        }

    }


    /**
     * Encode our image file so we can pass it to the Credly API
     *
     * @since  1.0.0
     * @param  string  $image_id The ID of our image attachment
     * @return string            base64 encoded image file
     */
    private function encoded_image( $image_id ) {

        // If we don't have a valid image ID, bail here
        if ( ! is_numeric( $image_id ) )
            return null;

        $image_file = get_attached_file( $image_id );

        // If we don't have a valid file, bail here
        if ( ! file_exists( $image_file ) )
            return null;

        // Open and encode our image file
        $handle        = fopen( $image_file, 'r' );
        $image_binary  = fread( $handle, filesize( $image_file ) );
        $encoded_image = base64_encode( $image_binary );

        // Return the encoded file
        return $encoded_image;

    }


    /**
     * Add a Credly Badge Settings metabox on the badge CPT
     *
     * @since  1.0.0
     * @return void
     */
    public function badge_metabox_add() {

        foreach ( badgeos_get_achievement_types_slugs() as $achievement_type ) {

            add_meta_box( 'badgeos_credly_details_meta_box', __( 'Badge Sharing Options' , 'badgeos' ), array( $this, 'badge_metabox_show' ), $achievement_type, 'advanced', 'default' );

        }
    }


    /**
     * Output a Credly Badge Settings metabox on the badge CPT
     *
     * @since  1.0.0
     * @return void
     */
    public function badge_metabox_show() {

        global $post;

        //Check existing post meta
        $send_to_credly             = ( get_post_meta( $post->ID, '_badgeos_send_to_credly', true ) ? get_post_meta( $post->ID, '_badgeos_send_to_credly', true ) : 'false' );
        $credly_include_evidence    = ( get_post_meta( $post->ID, '_badgeos_credly_include_evidence', true ) ? get_post_meta( $post->ID, '_badgeos_credly_include_evidence', true ): 'false' );
        $credly_include_testimonial = ( get_post_meta( $post->ID, '_badgeos_credly_include_testimonial', true ) ? get_post_meta( $post->ID, '_badgeos_credly_include_testimonial', true ) : 'false' );
        $credly_expiration          = ( get_post_meta( $post->ID, '_badgeos_credly_expiration', true ) ? get_post_meta( $post->ID, '_badgeos_credly_expiration', true ) : '0' );
        $credly_is_giveable         = ( get_post_meta( $post->ID, '_badgeos_credly_is_giveable', true ) ? get_post_meta( $post->ID, '_badgeos_credly_is_giveable', true ) : 'false' );
        $credly_categories          = maybe_unserialize( get_post_meta( $post->ID, '_badgeos_credly_categories', true ) );
        $credly_badge_id            = get_post_meta( $post->ID, '_badgeos_credly_badge_id', true );

    ?>
        <input type="hidden" name="credly_details_nonce" value="<?php echo wp_create_nonce( 'credly_details' ); ?>" />
        <table class="form-table">
            <tr valign="top">
                <td colspan="2"><?php _e( 'This setting makes the earned badge for this achievement sharable via Credly on social networks, such as Facebook, Twitter, LinkedIn, Mozilla Backpack, or the badge earner’s own blog or site.', 'badgeos' ); ?> (<?php printf( __( '<a href="%s">Configure global settings</a> for Credly integration.', 'badgeos' ), admin_url( 'admin.php?page=badgeos_sub_credly_integration' ) ); ?> )</td>
            </tr>
            <tr valign="top"><th scope="row"><label for="_badgeos_send_to_credly"><?php _e( 'Send to Credly when earned', 'badgeos' ); ?></label></th>
                <td>
                    <select id="_badgeos_send_to_credly" name="_badgeos_send_to_credly">
                        <option value="1" <?php selected( $send_to_credly, 'true' ); ?>><?php _e( 'Yes', 'badgeos' ) ?></option>
                        <option value="0" <?php selected( $send_to_credly, 'false' ); ?>><?php _e( 'No', 'badgeos' ) ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <div id="credly-badge-settings">
            <table class="form-table">
                <tr valign="top"><th scope="row"><label for="_badgeos_credly_include_evidence"><?php _e( 'Include Evidence', 'badgeos' ); ?></label></th>
                <td>
                    <select id="_badgeos_credly_include_evidence" name="_badgeos_credly_include_evidence">
                        <option value="1" <?php selected( $credly_include_evidence, 'true' ); ?>><?php _e( 'Yes', 'badgeos' ) ?></option>
                        <option value="0" <?php selected( $credly_include_evidence, 'false' ); ?>><?php _e( 'No', 'badgeos' ) ?></option>
                    </select>
                </td>
            </tr>
            <tr valign="top"><th scope="row"><label for="_badgeos_credly_include_testimonial"><?php _e( 'Include Testimonial', 'badgeos' ); ?></label></th>
                <td>
                    <select id="_badgeos_credly_include_testimonial" name="_badgeos_credly_include_testimonial">
                        <option value="1" <?php selected( $credly_include_testimonial, 'true' ); ?>><?php _e( 'Yes', 'badgeos' ) ?></option>
                        <option value="0" <?php selected( $credly_include_testimonial, 'false' ); ?>><?php _e( 'No', 'badgeos' ) ?></option>
                    </select>
                </td>
            </tr>
            <tr valign="top"><th scope="row"><label for="_badgeos_credly_expiration"><?php _e( 'Expiration ( In days; 0 = never )', 'badgeos' ); ?></label></th>
                <td>
                    <input type="text" id="_badgeos_credly_expiration" name="_badgeos_credly_expiration" value="<?php echo $credly_expiration; ?>" class="widefat" />
                </td>
            </tr>
            <tr valign="top"><th scope="row"><label for="_badgeos_credly_is_giveable"><?php _e( 'Allow Badge to be Given by Others', 'badgeos' ); ?></label></th>
                <td>
                    <select id="_badgeos_credly_is_giveable" name="_badgeos_credly_is_giveable">
                        <option value="1" <?php selected( $credly_is_giveable, 'true' ); ?>><?php _e( 'Yes', 'badgeos' ) ?></option>
                        <option value="0" <?php selected( $credly_is_giveable, 'false' ); ?>><?php _e( 'No', 'badgeos' ) ?></option>
                    </select>
                </td>
            </tr>
            <tr valign="top" class="credly_category_search"><th scope="row"><label for="credly_category_search"><?php _e( 'Credly Category Search', 'badgeos' ); ?></label></th>
                <td>
                    <input type="text" id="credly_category_search" name="credly_category_search" value="" size="50" />
                    <a id="credly_category_search_submit" class="button" />Search Categories</a>
                </td>
            </tr>
            <tr valign="top" id="credly_search_results" <?php if ( ! is_array( $credly_categories ) ) { ?>style="display:none"<?php } ?>><th scope="row"><label><?php _e( 'Credly Badge Category', 'badgeos' ); ?></label></th>
                <td>
                    <fieldset>
                        <?php echo $this->credly_existing_category_output( $credly_categories ); ?>
                    </fieldset>
                </td>
            </tr>
            <tr valign="top"><th scope="row"><label for="_badgeos_credly_badge_id"><?php _e( 'Credly Badge ID', 'badgeos' ); ?></label></th>
                <td>
                    <input type="text" id="_badgeos_credly_badge_id" name="_badgeos_credly_badge_id" value="<?php echo $credly_badge_id; ?>" class="widefat" readonly="readonly" />
                </td>
            </tr>
            </table>
        </div>

    <?php
    }


    /**
     * Save our Credly Badge Settings metabox
     *
     * @since  1.0.0
     * @return int     Return the post ID of the post we're running on
     */
    public function badge_metabox_save( $post_id ) {

        // Verify nonce
        if ( ! isset( $_POST['credly_details_nonce'] ) || ! wp_verify_nonce( $_POST['credly_details_nonce'], 'credly_details' ) )
            return $post_id;

        // Make sure we're not doing an autosave
        if ( defined('DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return $post_id;

        // Make sure this isn't a post revision
        if ( wp_is_post_revision( $post_id ) )
            return $post_id;

        // Check user permissions
        if ( !current_user_can( 'edit_post', $post_id ) )
            return $post_id;

        // Sanitize our fields
        $fields = $this->badge_metabox_sanitize_fields();

        // If enabled and we have a credly badge ID lets update
        if ( 'true' == $fields['send_to_credly'] )
            $credly_badge = $this->post_credly_badge( $post_id, $fields );

        // Save our meta
        $meta = $this->badge_metabox_save_meta( $post_id, $fields );

        // Update our meta value with our returned Credly badge ID
        if ( $credly_badge )
            update_post_meta( $post_id, '_badgeos_credly_badge_id', $credly_badge );

        return $post_id;

    }


    /**
     * Sanitize our metabox fields
     *
     * @since  1.0.0
     * @return array  An array of sanitized fields from our metabox
     */
    private function badge_metabox_sanitize_fields() {

        $fields = array();

        // Sanitize our input fields
        $fields['send_to_credly']             = ( $_POST['_badgeos_send_to_credly'] ? 'true' : 'false' );
        $fields['credly_include_evidence']    = ( $_POST['_badgeos_credly_include_evidence'] ? 'true' : 'false' );
        $fields['credly_include_testimonial'] = ( $_POST['_badgeos_credly_include_testimonial'] ? 'true' : 'false' );
        $fields['credly_expiration']          = ( $_POST['_badgeos_credly_expiration'] ? sanitize_text_field( $_POST['_badgeos_credly_expiration'] ) : '0' );
        $fields['credly_is_giveable']         = ( $_POST['_badgeos_credly_is_giveable'] ? 'true' : 'false' );
        $fields['credly_categories']          = ( ! empty ( $_POST['_badgeos_credly_categories'] ) ? array_map( 'sanitize_text_field', $_POST['_badgeos_credly_categories'] ) : '' );
        $fields['credly_badge_id']            = ( $_POST['_badgeos_credly_badge_id'] ? sanitize_text_field( $_POST['_badgeos_credly_badge_id'] ) : '' );

        return $fields;

    }


    /**
     * Save the meta fields from our metabox
     *
     * @since  1.0.0
     * @param  int  $post_id   Post ID
     * @param  array  $fields  An array of fields in the metabox
     * @return bool            Return true
     */
    private function badge_metabox_save_meta( $post_id, $fields ) {

        update_post_meta( $post_id, '_badgeos_send_to_credly', $fields['send_to_credly'] );
        update_post_meta( $post_id, '_badgeos_credly_include_evidence', $fields['credly_include_evidence'] );
        update_post_meta( $post_id, '_badgeos_credly_include_testimonial', $fields['credly_include_testimonial'] );
        update_post_meta( $post_id, '_badgeos_credly_expiration', $fields['credly_expiration'] );
        update_post_meta( $post_id, '_badgeos_credly_is_giveable', $fields['credly_is_giveable'] );
        update_post_meta( $post_id, '_badgeos_credly_categories', $fields['credly_categories'] );

        return true;

    }

} // End BadgeOS_Credly class

/**
 * Generate the available fields to map to
 *
 * @since  1.0.0
 * @param  string $value Our current saved value if it exists
 * @return string        A concatenated string of <option> values
 */
function credly_fieldmap_get_fields() {

    $default_fields = array();

    // Set our default fields
    $default_fields[] = 'post_title';
    $default_fields[] = 'post_body';
    $default_fields[] = 'post_excerpt';
    $default_fields[] = 'featured_image';
    $default_fields[] = 'permalink';

    // Get all unique meta keys from the postmeta table
    global $wpdb;
    $meta_keys = $wpdb->get_col(
        "
        SELECT DISTINCT meta_key
        FROM $wpdb->postmeta
        "
    );

    // Excluded keys
    $exclude = array(
        '_badgestack_badge_unlock_options',
        '_badgestack_credly_badge_id',
        '_badgestack_credly_categories',
        '_badgestack_credly_expiration',
        '_badgestack_credly_include_evidence',
        '_badgestack_credly_include_testimonial',
        '_badgestack_credly_is_giveable',
        '_badgestack_dummy_data',
        '_badgestack_log_achievement_id',
        '_badgestack_point_value',
        '_badgestack_send_to_credly',
        '_edit_last',
        '_edit_lock',
        '_thumbnail_id',
        '_wp_attached_file',
        '_wp_attachment_metadata',
        '_wp_old_slug',
        '_wp_page_template',
        '_wp_trash_meta_status',
        '_wp_trash_meta_time',
    );

    // Get rid of our excluded meta keys
    $meta_keys = array_diff( $meta_keys, $exclude );

    // Merge our default fields with unique meta keys
    $fields = array_merge( $default_fields, $meta_keys );

    return $fields;

}

/**
 * Generate a string of html option values based on our fields
 *
 * @since  1.0.0
 * @param  string $value The current value of the field to compare for selected()
 * @return string        A string of <options>
 */
function credly_fieldmap_list_options( $value = '' ) {

    $fields = credly_fieldmap_get_fields();

    // Start off our <option>s with a default blank
    $options = '<option value>' . __( '&mdash; Select Field &mdash;' ) . '</option>';

    // Create the correct <option> markup for our fields
    foreach ( $fields as $field ) {

        $options .= '<option value="' . $field . '" ' . selected( $field, $value ) . '>' . $field . '</option>';

    }

    return $options;

}

/**
 * Map our fieldmap values to actual content.
 *
 * In the case it's one of our defined defaults, get the relevant post content.
 * For everything else, assume it's meta and attempt to get a meta key with
 * matching field name.
 *
 * @since  1.0.0
 * @param  string $post_id The ID of the badge post we're mapping fields for
 * @param  string $field   The field name we're attempting to map
 * @return mixed           string for most values. int for featured_image
 */
function credly_fieldmap_get_field_value( $post_id = '', $field = '' ) {

    switch ( $field ) {
        case 'post_title':
            $value = get_the_title( $post_id );
            break;
        case 'post_body':
            $value = get_post_field( 'post_content', $post_id );
            break;
        case 'post_excerpt':
            $value = get_post_field( 'post_excerpt', $post_id );
            break;
        case 'featured_image':
            $value = get_post_thumbnail_id( $post_id );
            break;
        case 'permalink':
            $value = get_permalink( $post_id );
            break;
        case '':
            $value = '';
            break;
        default:
            $value = get_post_meta( $post_id, $field, true );
            break;
    }

    return $value;

}

/**
 * Check if an achievement is giveable in Credly
 *
 * @since  1.0.0
 *
 * @param  integer $achievement_id The achievement ID we're checking
 *
 * @return bool                    True if giveable, false if not
 */
function credly_is_achievement_giveable( $achievement_id = 0 ) {

    // Get our Credly badge ID
    $credly_badge_id = ( ( ! empty( $achievement_id ) ) ? get_post_meta( $achievement_id, '_badgeos_credly_badge_id', true ) : '' );

    return ( ( ! empty( $credly_badge_id ) ) ? true : false );

}
