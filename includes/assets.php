<?php
/**
 * Assets Functions
 *
 * @package BadgeOS
 * @subpackage Assets
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

 /**
 * Lists all the available assets packs.
 *
 * @return void
 */
function badgeos_assets_page( ) {
    
    $assets = badgeos_assets_data();
    echo '<div class="badgeos_assets">';
    echo '<h1>'.__( 'Assets', 'badgeos' ).'</h1>';
    $root_url = badgeos_get_directory_url();
    if( isset( $assets ) && is_object( $assets ) && count( $assets ) > 0 ) { 
        foreach( $assets as $key => $asset ) {
            if( $asset->active == 'Yes' ) {
                ?>
                    <div class="badgeos_item badgeos_item_<?php echo $key;?>">
                        <div class="badgeos-assets-image">
                            <img src="<?php echo $asset->image;?>" alt="<?php echo $asset->title;?>" style="width:100%">
                        </div>
                        <h2><?php echo $asset->title;?></h2>
                        <div class="badgeos-item-description"><?php echo $asset->description;?></div>
                        <div class="badgeos-assets-message-divs">
                            <div class="badgeos_download_asset_success_message" style="display:none"><?php _e( 'Thank you for downloading the pack! The icons are in your media files and you can use them for your badges, points, or ranks', 'badgeos' ); ?></div>
                            <div class="badgeos_download_asset_failed_message" style="display:none"></div>
                        </div>
                        <p class="badgeos-item-button">
                            <input type="hidden" name="badgeos_assets_id" class="badgeos_assets_id" id="badgeos_assets_id" value="<?php echo $key;?>" />
                            <button class="btn_badgeos_download_assets" data-ajax_url="<?php echo admin_url( 'admin-ajax.php' ); ?>">
                                <?php _e( 'Download', 'badgeos' ); ?>
                                <img id="btn_badgeos_download_assets_loader" style="background-color:#000000; visibility:hidden;" src="<?php echo $root_url.'/images/ajax-loader.gif';?>" />
                            </button>
                        </p>
                    </div>
                <?php
            }
        }
    }
    echo '</div>';
}

/**
 * Ajax download handler script.
 *
 * @return void
 */
function badgeos_download_and_configure_asset() {
    set_time_limit(0);
    $assets_id  = sanitize_text_field( $_POST['assets_id'] );
    $assets     = badgeos_assets_data();
    
    $assets = (array)$assets;
    if( ! empty( $assets_id ) ) {
        if( $assets[$assets_id] ) {

            $downloaded_assets_id = badgeos_utilities::get_option( 'badgeos_restapi_'.$assets_id  );
            if( trim( $downloaded_assets_id ) != 'downloaded' ) {
                if( !empty( $assets[$assets_id]->asset_url ) ) { 
                    // If the function it's not available, require it.
                    if ( ! function_exists( 'download_url' ) ) {
                        require_once ABSPATH . 'wp-admin/includes/file.php';
                    }
    
                    // Now you can use it!
                    $tmp_file = download_url( $assets[$assets_id]->asset_url );
                    $filename = basename( $assets[$assets_id]->asset_url );
    
                    // Sets file final destination.
                    $filepath = ABSPATH . 'wp-content/uploads/'.$filename;
    
                    // Copies the file to the final destination and deletes temporary file.
                    copy( $tmp_file, $filepath );
                    @unlink( $tmp_file );
    
    
                    WP_Filesystem();
                    $destination = wp_upload_dir();
                    $destination_path = $destination['path'];
                    
                    if( $unzipfile = unzip_file( $filepath, $destination_path) ) {
                        $image_folder = trailingslashit($destination_path).$assets[ $assets_id ]->asset_folder;
                        $files = list_files( $image_folder );
                        foreach( $files as $file ) {
                            $image_name = basename( $file );   
                            
                            $wp_filetype = wp_check_filetype( $image_name, null );
    
                            $attachment = array(
                                'post_mime_type' => $wp_filetype['type'],
                                'post_title' => sanitize_file_name( $image_name ),
                                'post_content' => '',
                                'post_status' => 'inherit'
                            );
    
                            $attach_id = wp_insert_attachment( $attachment, $file );
                            require_once( ABSPATH . 'wp-admin/includes/image.php' );
                            $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
                            wp_update_attachment_metadata( $attach_id, $attach_data );
                        }
                        badgeos_utilities::update_option( 'badgeos_restapi_'.$assets_id, 'downloaded'  );
                        echo 'done';
                    } else {
                        echo __( 'Invalid zip archive provided.', 'badgeos' ) ;
                    }
                } else {
                    echo __( 'Please, provide an asset url.', 'badgeos' );
                }
            } else {
                echo __( 'Asset is already downloaded on your media gallery.', 'badgeos' );
            }
        } else {
            echo __( "Sorry, we're unable to reach the server right now please try later.", 'badgeos' );
        }
    } else {
        echo __( "Sorry, we're unable to reach the server right now please try later.", 'badgeos' );
    }
    exit;
}
add_action( 'wp_ajax_badgeos_download_asset', 'badgeos_download_and_configure_asset' );

/**
 * Ajax download handler script.
 *
 * @return void
 */
function badgeos_assets_data() {
    
    $request = wp_remote_get( 'https://badgeos.org/badgeos-assets-api.php' );

    if( is_wp_error( $request ) ) {
        return false;
    }

    $body = wp_remote_retrieve_body( $request );

    return json_decode( $body );
}
