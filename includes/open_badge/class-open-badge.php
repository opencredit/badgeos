<?php
/**
 * BadgeOS Open Badge
 *
 * @package BadgeOS
 * @subpackage Open Badge
 * @author Learning Times
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BadgeOS_Open_Badge {

	public $badgeos_assertion_page_id 	= 0; 		
	public $badgeos_json_page_id 		= 0; 
	public $badgeos_issuer_page_id 		= 0;
	public $badgeos_evidence_page_id 	= 0;
	
	public $badgeos_salt 				= 'BADGEOSOBI';
    /**
	 * Instantiate the Opne Badge.
	 */
	public function __construct() {

		$this->badgeos_assertion_page_id	= badgeos_utilities::get_option( 'badgeos_assertion_url' );
		$this->badgeos_json_page_id 		= badgeos_utilities::get_option( 'badgeos_json_url' );
		$this->badgeos_evidence_page_id		= badgeos_utilities::get_option( 'badgeos_evidence_url' );
		$this->badgeos_issuer_page_id       = badgeos_utilities::get_option( 'badgeos_issuer_url' );
	
		add_filter( 'template_include', 	array( $this,'badgeos_template_pages' ) );
        add_action( 'admin_post_convert_badges_to_open_standards', 	array( $this,'convert_badges_to_open_standards' ) );
        add_action( 'wp_ajax_convert_badges_to_open_standards', 	array( $this,'convert_badges_to_open_standards' ) );
        add_action( 'cron_convert_badges_to_open_standards', array( $this,'cron_convert_badges_to_open_standards_cb' ), 10, 2 ); //Scheduler

        //$this->add_bulk_option();
    }

    function cron_convert_badges_to_open_standards_cb($non_os_badgeos_achievements, $is_last) {

	    foreach ($non_os_badgeos_achievements as $non_os_badgeos_achievement) {

            $entry_id = $non_os_badgeos_achievement->entry_id;
            $achievement_id = $non_os_badgeos_achievement->ID;
            $user_id = $non_os_badgeos_achievement->user_id;

            $this->bake_user_badge($entry_id, $user_id, $achievement_id);
        }

        if ( $is_last  ) {
            $this->notify_conversion_admin();
            delete_transient('non_ob_conversion_progress');
        }
    }

    /**
     * Convert non open standard badges and redirect
     */
    function convert_badges_to_open_standards() {


        if ( ! wp_next_scheduled ( 'cron_convert_badges_to_open_standards' ) ) {

            $non_os_badgeos_achievements_all = badgeos_ob_get_all_non_achievements();

            if (!empty($non_os_badgeos_achievements_all)) {

                set_transient('non_ob_conversion_progress', 1, 86400); //expiration 1 Day = 60*60*24 = 86400

                $time = time();
                $time_duration  = 15;   //Define after how many seconds cron should run
                $num_records    = 50;   //Define how many records should be process for each run

                $non_os_badgeos_achievements_chunks = array_chunk($non_os_badgeos_achievements_all, $num_records)  ;

                $i = 0;
                $total_chunks = count($non_os_badgeos_achievements_chunks);
                $is_last_chunk = false;

                foreach ($non_os_badgeos_achievements_chunks as $non_os_badgeos_achievements) {

                    if( $i == $total_chunks-1 ) {
                        $is_last_chunk = true;
                    }

                    $time = $time + $time_duration;
                    wp_schedule_single_event($time, 'cron_convert_badges_to_open_standards', array( $non_os_badgeos_achievements, $is_last_chunk ));

                    $i++;
                }

                if ( ! wp_doing_ajax() ) {
                    $link = wp_get_referer();
                    wp_safe_redirect($link);
                    exit;
                } else {
                    wp_send_json( array('success' => true ) );
                }
            }

        }

        if ( ! wp_doing_ajax() ) {
            $link = wp_get_referer();
            wp_safe_redirect($link);
            exit;
        } else {
            wp_send_json( array('success' => false ) );
        }
    }

    private function notify_conversion_admin() {
        $from_title = get_bloginfo( 'name' );
        $from_email = get_bloginfo( 'admin_email' );
        $headers[] = 'From: '.$from_title.' <'.$from_email.'>';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        $body = '<table border="0" cellspacing="0" cellpadding="0" align="center">';
        $body .= '<tr valign="top">';
        $body .= '<td>';
        $body .= '<p>'.__( 'Hi', 'badgeos' ).' '.$from_title.'</p>';
        $body .= '<p>'.__( "All your achievements, scheduled for conversion to open standards are converted successfully.", 'badgeos' ).'</p>';
        $body .= '</td></tr>';
        $body .= '<tr valign="top"><td></td></tr>';
        $body .= '<tr valign="top"><td>'.__( 'Thanks', 'badgeos' ).'</td></tr>';
        $body .= '</table>';

        wp_mail( $from_email, 'BadgeOS Opne Badge Conversion', $body, $headers );
    }

	/**
	 * Override the current displayed template.
	 * 
	 * @param $page_template
	 * 
	 * @return $page_template
	 */ 
	function badgeos_template_pages( $page_template ) {
		
		global $post;
		
		$achievement_id = 0;
        if( ! empty( $_REQUEST['bg'] ) ) {
            $achievement_id 	= sanitize_text_field( $_REQUEST['bg'] );
        }
        
        $entry_id = 0;
        if( ! empty( $_REQUEST['eid'] ) ) {
            $entry_id  	        = sanitize_text_field( $_REQUEST['eid'] );
        }
        
        $user_id = 0;
        if( ! empty( $_REQUEST['uid'] ) ) {
            $user_id  	        = sanitize_text_field( $_REQUEST['uid'] );
        }
		if( $post ) {
			if( $post->ID == $this->badgeos_assertion_page_id ) {
				$this->badgeos_generate_assertion( $user_id, $entry_id, $achievement_id );
				exit;
			} else if( $post->ID == $this->badgeos_json_page_id ) {
				$this->badgeos_generate_badge( $user_id, $entry_id, $achievement_id );
				exit;
			} else if( $post->ID == $this->badgeos_issuer_page_id ) {
				$this->badgeos_generate_issuer( $user_id, $entry_id, $achievement_id );
				exit;
			}
		}
		
		return $page_template;
	}
	
	
	/**
	 * Generates assertion json.
	 * 
	 * @param $user_id
	 * @param $entry_id
	 * @param $achievement_id
	 * 
	 * @return voidexit;
	 */ 
	public function badgeos_generate_assertion( $user_id, $entry_id, $achievement_id ) {
		
		$badge = badgeos_utilities::badgeos_get_post( $achievement_id );
		if( $badge ) {

			$post_content = $badge->post_content;
			$post_title = $badge->post_title;
			
			$date = new DateTime();

			$thumbnail_url = get_the_post_thumbnail_url( $achievement_id, 'full' );
			
			if( empty( $thumbnail_url ) ) {
				$directory_url = badgeos_get_directory_url();
				$thumbnail_url = $directory_url. 'images/default_badge.png';
			}

			$badgeos_assertion_url 	= get_permalink( $this->badgeos_assertion_page_id );
			$badgeos_assertion_url  = add_query_arg( 'bg', $achievement_id, $badgeos_assertion_url );
			$badgeos_assertion_url  = add_query_arg( 'eid', $entry_id, $badgeos_assertion_url );
			$badgeos_assertion_url  = add_query_arg( 'uid', $user_id, $badgeos_assertion_url );

			$badgeos_json_url 		= get_permalink( $this->badgeos_json_page_id );
			$badgeos_json_url  		= add_query_arg( 'bg', $achievement_id, $badgeos_json_url );
			$badgeos_json_url  		= add_query_arg( 'eid', $entry_id, $badgeos_json_url );
			$badgeos_json_url  		= add_query_arg( 'uid', $user_id, $badgeos_json_url );

			$badgeos_evidence_url 	= get_permalink( $this->badgeos_evidence_page_id );
			$badgeos_evidence_url   = add_query_arg( 'bg', $achievement_id, $badgeos_evidence_url );
			$badgeos_evidence_url   = add_query_arg( 'eid', $entry_id, $badgeos_evidence_url );
			$badgeos_evidence_url   = add_query_arg( 'uid', $user_id, $badgeos_evidence_url );

			$identity_id = $this->get_identity_id( $user_id, $entry_id, $achievement_id );

			$open_badge_include_evidence = ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_include_evidence', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_include_evidence', true ) : 'false' );
			
			$result = array(
				'@context'	=> 'https://w3id.org/openbadges/v2',
				'type'	=> 'Assertion',
				'id'	=> $badgeos_assertion_url,
				'recipient'	=> array(
					'type'	=> 'email',
					'hashed'	=> true,
					'salt'	=> $this->badgeos_salt,
					'identity'	=> $identity_id
				),
				'badge'	=> $badgeos_json_url,
				'issuedOn'	=> $date->format('Y-m-d\TH:i:sP'),
				'image'	=> $thumbnail_url,
				'verification'	=> array(
					'type'	=> 'HostedBadge',
					'verificationProperty'	=> 'id',
				),
			);
			if( $open_badge_include_evidence == 'true' ) {
				$result[ 'evidence' ] = $badgeos_evidence_url;
			}

			$expires = $this->expired_date( $user_id, $entry_id, $achievement_id );
			if( ! empty( $expires ) ) {
				$result[ 'expires' ] = $expires;
			}
			wp_send_json( $result );
		}
	}

	/**
	 * Generates badge json.
	 * 
	 * @param $user_id
	 * @param $entry_id
	 * @param $achievement_id
	 * 
	 * @return void
	 */ 
	public function badgeos_generate_badge( $user_id, $entry_id, $achievement_id ) {
				
		$badge = badgeos_utilities::badgeos_get_post( $achievement_id );
		if( $badge ) {

			$post_content = $badge->post_content;
			$post_title = $badge->post_title;
			
			$thumbnail_url = get_the_post_thumbnail_url( $achievement_id, 'full' );
			if( empty( $thumbnail_url ) ) {
				$directory_url = badgeos_get_directory_url();
				$thumbnail_url = $directory_url. 'images/default_badge.png';
			}

			$badgeos_json_url  = get_permalink( $this->badgeos_json_page_id );
			$badgeos_json_url  = add_query_arg( 'bg', $achievement_id, $badgeos_json_url );
			$badgeos_json_url  = add_query_arg( 'eid', $entry_id, $badgeos_json_url );
			$badgeos_json_url  = add_query_arg( 'uid', $user_id, $badgeos_json_url );

			$badgeos_issuer_url = get_permalink( $this->badgeos_issuer_page_id );

			$badge_link = get_permalink( $achievement_id );

			$result =  array(
				'@context'		=> 'https://w3id.org/openbadges/v2',
				'type'			=> 'BadgeClass',
				'id'			=> $badgeos_json_url,
				'name'			=> $post_title,
				'image'			=> $thumbnail_url,
				'description'	=> $post_content,
				'criteria'		=> $badge_link,
				'issuer'		=> $badgeos_issuer_url,
				'tags'			=> array()
			);
			
			wp_send_json( $result );
		}
	}

	/**
	 * Generates issuer json.
	 * 
	 * @param $user_id
	 * @param $entry_id
	 * @param $achievement_id
	 * 
	 * @return none
	 */ 
	public function badgeos_generate_issuer( $user_id, $entry_id, $achievement_id ) {
		
		$blog_title = get_bloginfo( 'name' );
		$admin_email = get_bloginfo( 'admin_email' );
		$blog_url = get_site_url();

		$result =  array(
			'@context'	=> 'https://w3id.org/openbadges/v2',
			'type'	=> 'Issuer',
			'id'	=> get_permalink(),
			'name'	=> $blog_title,
			'url'	=> $blog_url,
			'email'	=> $admin_email
		);
		
		wp_send_json( $result );
	}
	
	/**
	 * Return the expiry date
	 * 
	 * @param $entry_id
	 * @param $user_id
	 * @param $achievement_id
	 * 
	 * @return none
	 */ 
	function expired_date( $user_id, $entry_id, $achievement_id ) {
		
		global $wpdb;
		
		$open_badge_expiration       = ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration', true ) : '0' );
		$open_badge_expiration_type  = ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration_type', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration_type', true ) : '0' );
		
		$badge_expiry = '';
		
		$recs = $wpdb->get_results( "select * from ".$wpdb->prefix."badgeos_achievements where entry_id='".$entry_id."'" );
		if( count( $recs ) > 0 && intval( $open_badge_expiration ) > 0 ) {
			
			$badge_date = $recs[ 0 ]->date_earned;
			$badge_expiry = date( 'Y-m-d H:i:s', strtotime( '+'.$open_badge_expiration.' '.$open_badge_expiration_type, strtotime( $badge_date ) ));
			$date = new DateTime( $badge_expiry );
			return $date->format('Y-m-d\TH:i:sP');
		}

		return $badge_expiry;
	}

	/**
	 * Bake a badge if the Badge Baking is enabled
	 * 
	 * @param $entry_id
	 * @param $user_id
	 * @param $achievement_id
	 * 
	 * @return none
	 */ 
	public function bake_user_badge( $entry_id, $user_id, $achievement_id ) {
		
		global $wpdb;

		$badge = badgeos_utilities::badgeos_get_post( $achievement_id );
		if( $badge ) {
			
			//$open_badge_enable_baking       	= ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_enable_baking', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_enable_baking', true ) : 'false' );
			$open_badge_enable_baking       	= badgeos_get_option_open_badge_enable_baking($achievement_id);
			$open_badge_criteria            	= ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_criteria', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_criteria', true ): '' );
			$open_badge_include_evidence    	= ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_include_evidence', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_include_evidence', true ) : 'false' );
			
			$open_badge_expiration  			= ( badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration', true ) ? badgeos_utilities::get_post_meta( $achievement_id, '_open_badge_expiration', true ) : '0' );
			if( $open_badge_enable_baking ) {
				
				$date = new DateTime();
				
				$dirs = wp_upload_dir();
				$basedir = trailingslashit( $dirs[ 'basedir' ] );
				$baseurl = trailingslashit( $dirs[ 'baseurl' ] );

				$user_badge_directory = $basedir.'user_badges';
				if ( ! file_exists( $user_badge_directory ) && ! is_dir( $user_badge_directory ) ) {
					mkdir( $user_badge_directory, 0777 );         
				}
				
				$user_badge_directory = trailingslashit( $user_badge_directory ).$user_id;
				if ( ! file_exists( $user_badge_directory ) && ! is_dir( $user_badge_directory ) ) {
					mkdir( $user_badge_directory, 0777 );         
				}
				$user_badge_directory = trailingslashit( $user_badge_directory );

				$post_content = $badge->post_content;
				$post_title = $badge->post_title;
				
				$thumbnail_url 	= get_the_post_thumbnail_url( $achievement_id, 'full' );
				if( empty( $thumbnail_url ) ) {
					$directory_url = badgeos_get_directory_url();
					$thumbnail_url = $directory_url. 'images/default_badge.png';
				}

				$userdirectory 	= '/images/users/';
				$directory 		= badgeos_get_directory_path();
				$directory 		.= $userdirectory;

				$directory_url 	= badgeos_get_directory_url();
				$directory_url .= $userdirectory;
				$badge_link 	= get_permalink( $achievement_id );

				$badgeos_assertion_url 	= get_permalink( $this->badgeos_assertion_page_id );
				$badgeos_assertion_url  = add_query_arg( 'bg', $achievement_id, $badgeos_assertion_url );
				$badgeos_assertion_url  = add_query_arg( 'eid', $entry_id, $badgeos_assertion_url );
				$badgeos_assertion_url  = add_query_arg( 'uid', $user_id, $badgeos_assertion_url );
				
				$badgeos_json_url 		= get_permalink( $this->badgeos_json_page_id );
				$badgeos_json_url 		= add_query_arg( 'bg', $achievement_id, $badgeos_json_url );
				$badgeos_json_url  		= add_query_arg( 'eid', $entry_id, $badgeos_json_url );
				$badgeos_json_url  		= add_query_arg( 'uid', $user_id, $badgeos_json_url );

				$badgeos_issuer_url 	= get_permalink( $this->badgeos_issuer_page_id );
				$badgeos_issuer_url 	= add_query_arg( 'bg', $achievement_id, $badgeos_issuer_url );
				$badgeos_issuer_url  	= add_query_arg( 'eid', $entry_id, $badgeos_issuer_url );
				$badgeos_issuer_url  	= add_query_arg( 'uid', $user_id, $badgeos_issuer_url );

				$badgeos_evidence_url 	= get_permalink( $this->badgeos_evidence_page_id );
				$badgeos_evidence_url 	= add_query_arg( 'bg', $achievement_id, $badgeos_evidence_url );
				$badgeos_evidence_url  	= add_query_arg( 'eid', $entry_id, $badgeos_evidence_url );
				$badgeos_evidence_url  	= add_query_arg( 'uid', $user_id, $badgeos_evidence_url );

				$identity_id = $this->get_identity_id( $user_id, $entry_id, $achievement_id );

				$json = array(
					'@context'	=> 'https://w3id.org/openbadges/v2',
					'type'	=> 'Assertion',
					'id'	=> $badgeos_assertion_url,
					'recipient'	=> array(
						'type'	=> 'email',
						'hashed'	=> true,
						'salt'	=> $this->badgeos_salt,
						'identity'	=> $identity_id
					),
					'badge'	=> array(
						'@context'	=> 'https://w3id.org/openbadges/v2',
						'type'	=> 'BadgeClass',
						'id'	=> "a-".$achievement_id,
						'name'	=> $post_title,
						'image'	=> $thumbnail_url,
						'description' => $post_content,
						'criteria'	=> $badge_link,
						'issuer'	=> $badgeos_issuer_url,
						'tags'	=> array(),
					),
					'issuedOn'	=> $date->format('Y-m-d\TH:i:sP'),
					'image'	=> $thumbnail_url,
					'verification'	=> array(
						'type'	=> 'HostedBadge',
						'verificationProperty'	=> 'id',
					),
				);
				
				$expires = $this->expired_date( $user_id, $entry_id, $achievement_id );
				if( !empty( $expires ) ) {
					$json[ 'expires' ] = $expires;
				}

				if( $open_badge_include_evidence == 'true' ) {
					$json[ 'evidence' ] = $badgeos_evidence_url;
				}

				$result = $this->bake_image( $thumbnail_url, $json );

				$filename = ( 'Badge-'.$entry_id . '-' . $achievement_id ).'.png';
				file_put_contents( $user_badge_directory.$filename ,$result);
				
				$table_name = $wpdb->prefix . 'badgeos_achievements';
				$rec_type = 'open_badge';
				$data_array = array( 'image' => $filename, 'rec_type' => $rec_type );
                $where = array( 'entry_id' => absint(  $entry_id ) );
				$wpdb->update( $table_name , $data_array, $where );
			}
		}
	}

	/**
	 * Return the Identity
	 * 
	 * @param $user_id
	 * @param $entry_id
	 * @param $achievement_id
	 * 
	 * @return none
	 */ 
	function get_identity_id( $user_id, $entry_id, $achievement_id ) {
		$user = get_user_by( 'ID', $user_id );
		$user_email = '';
		if( $user ) {
			$user_email = $user->user_email;
		}
		return 'sha256$' . hash('sha256',$user_email.$this->badgeos_salt);
	}

	/**
	 * Bake a badge if the Badge Baking is enabled
	 *
	 * @param int $image        The ID of the user earning the achievement
	 * @param int $assertion The ID of the achievement being earned
	 */
	function bake_image($image, array $assertion) {
		$png = file_get_contents($image);

		// You may wish to perform additional checks to ensure the file
		// is a png file here.
		$embed = [
			'openbadges',
			'',
			'',
			'',
			'',
			(string) json_encode($assertion),
		];

		// Glue with null-bytes.
		$data = implode("\0", $embed);

		// Make the CRC.
		$crc = pack("N", crc32('iTXt' . $data));

		// Put it all together.
		$final = pack("N", strlen($data)) . 'iTXt' . $data . $crc;

		// What's the length?
		$length = strlen($png);

		// Put this all at the end, before IEND.
		// We _should_ be removing all other iTXt blobs with keyword openbadges
		// before writing this out.
		$png = substr($png, 0, $length - 12) . $final . substr($png, $length - 12,12);

		return $png;
	}
}

$GLOBALS['badgeos_open_badge'] = new BadgeOS_Open_Badge();