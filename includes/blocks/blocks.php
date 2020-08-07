<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the badgeOS block category
 *
 * @param $categories
 * @param $post
 *
 * @return none
 */
function badgeos_register_block_category( $categories, $post ) {

    $new_cats = array_merge(
        $categories,
        array(
            array(
                'slug' => 'badgeos-blocks',
                'title' => __( 'BadgeOS Blocks', 'badgeos' ),
                'icon'	=> ''
            ),
        )
    );

    return $new_cats;
}
add_filter( 'block_categories', 'badgeos_register_block_category', 10000, 2);

/**
 * Renders the user earned points block
 *
 * @param $attributes
 *
 * @return output html
 */
function badgeos_render_user_earned_points_block( $attributes ) {
    
    $user_param = '';
    if( !empty( $attributes['user_id'] ) ) {
        $user_id_obj = json_decode( sanitize_text_field( $attributes['user_id'] ) );
        if( $user_id_obj ) {
            $user_param = ' user_id="'.$user_id_obj->value.'"';
        }
    }

    if( isset( $attributes['point_type'] ) && !empty( $attributes['point_type'] ) ){
        $data = json_decode( $attributes['point_type'] );
        if( ! empty( $data->value  ) ) {
            $show_title = $attributes['show_title'];
            return do_shortcode( '[badgeos_user_earned_points point_type="'.$data->value.'" '.$user_param.' show_title="'.$show_title.'"]' );
        } else {
            return '<div class="inner-content">'.__( 'No point type found.', 'badgeos' ).'</div>';
        }
    } else {
        return '<div class="inner-content">'.__( 'Please, select a point type to display.', 'badgeos' ).'</div>';
    }
}

/**
 * Renders the rank block
 *
 * @param $attributes
 *
 * @return output html
 */
function badgeos_render_single_rank_block( $attributes ) {

    if( isset( $attributes['rank'] ) && !empty( $attributes['rank'] ) ){
        $data = json_decode( $attributes['rank'] );
        if( ! empty( $data->value  ) ) {
            
            $param = '';
            if( !empty( $attributes['show_description'] ) ) {
                $param .= ' show_description="'.sanitize_text_field( $attributes['show_description'] ).'"';
            } else {
                $param .= ' show_description="true"';
            }
        
            if( !empty( $attributes['show_thumb'] ) ) {
                $param .= ' show_thumb="'.sanitize_text_field( $attributes['show_thumb'] ).'"';
            } else {
                $param .= ' show_thumb="true"';
            }
        
            if( !empty( $attributes['show_title'] ) ) {
                $param .= ' show_title="'.sanitize_text_field( $attributes['show_title'] ).'"';
            } else {
                $param .= ' show_title="true"';
            }
        
            if( !empty( $attributes['image_width'] ) ) {
                $param .= ' image_width="'.sanitize_text_field( $attributes['image_width'] ).'"';
            }
            
            if( !empty( $attributes['image_height'] ) ) {
                $param .= ' image_height="'.sanitize_text_field( $attributes['image_height'] ).'"';
            }

            return do_shortcode( '[badgeos_rank id="'.$data->value.'" '.$param.' ]' );
        } else {
            return '<div class="inner-content">'.__( 'No rank found.', 'badgeos' ).'</div>';
        }
    } else {
        return '<div class="inner-content">'.__( 'Please, select a rank to display.', 'badgeos' ).'</div>';
    }
}

/**
 * Renders the achievement block
 *
 * @param $attributes
 *
 * @return output html
 */
function badgeos_render_evidence_blocks( $attributes ) {

    if( isset( $attributes['award_id'] ) && !empty( $attributes['award_id'] ) ){

        $data = json_decode( $attributes['award_id'] );
        if( ! empty( $data->value  ) ) {
            
            $param = '';
            if( !empty( $attributes['achievement'] ) ) {
                $achi_data = json_decode( $attributes['achievement'] );
                $param .= ' achievement="'.sanitize_text_field( $achi_data->value ).'"';
            } else {
                $param .= ' achievement="0"';
            }
        
            $param = '';
            if( !empty( $attributes['user_id'] ) ) {
                $user_data = json_decode( $attributes['user_id'] );
                $param .= ' user_id="'.sanitize_text_field( $user_data->value ).'"';
            } else {
                $param .= ' user_id="0"';
            }

            $body = do_shortcode( '[badgeos_evidence award_id="'.$data->value.'" '.$param.' ]' );
            if( empty($body  ) ) {
                return '<div class="inner-content">'.__( 'No achievement found.', 'badgeos' ).'</div>';
            } else {
                return do_shortcode( '[badgeos_evidence award_id="'.$data->value.'" '.$param.' ]' );
            }
            
        } else {
            return '<div class="inner-content">'.__( 'No achievement found.', 'badgeos' ).'</div>';
        }
    } else {
        return '<div class="inner-content">'.__( 'Please, select an award id to display.', 'badgeos' ).'</div>';
    }
}

/**
 * Renders the achievement block
 *
 * @param $attributes
 *
 * @return output html
 */
function badgeos_render_achievement_block( $attributes ) {

    if( isset( $attributes['achievement'] ) && !empty( $attributes['achievement'] ) ){
        $data = json_decode( $attributes['achievement'] );
        if( ! empty( $data->value  ) ) {
            
            $param = '';
            if( !empty( $attributes['show_description'] ) ) {
                $param .= ' show_description="'.sanitize_text_field( $attributes['show_description'] ).'"';
            } else {
                $param .= ' show_description="true"';
            }
        
            if( !empty( $attributes['show_thumb'] ) ) {
                $param .= ' show_thumb="'.sanitize_text_field( $attributes['show_thumb'] ).'"';
            } else {
                $param .= ' show_thumb="true"';
            }
        
            if( !empty( $attributes['show_title'] ) ) {
                $param .= ' show_title="'.sanitize_text_field( $attributes['show_title'] ).'"';
            } else {
                $param .= ' show_title="true"';
            }
        
            if( !empty( $attributes['show_steps'] ) ) {
                $param .= ' show_steps="'.sanitize_text_field( $attributes['show_steps'] ).'"';
            } else {
                $param .= ' show_steps="true"';
            }
            
            if( !empty( $attributes['image_width'] ) ) {
                $param .= ' image_width="'.sanitize_text_field( $attributes['image_width'] ).'"';
            }
            
            if( !empty( $attributes['image_height'] ) ) {
                $param .= ' image_height="'.sanitize_text_field( $attributes['image_height'] ).'"';
            }

            return do_shortcode( '[badgeos_achievement id="'.$data->value.'" '.$param.' ]' );
        } else {
            return '<div class="inner-content">'.__( 'No achievement found.', 'badgeos' ).'</div>';
        }
    } else {
        return '<div class="inner-content">'.__( 'Please, select an achievement to display.', 'badgeos' ).'</div>';
    }
}

/**
 * Renders the user earned achievement block
 *
 * @param $attributes
 *
 * @return output html
 */
function badgeos_render_user_earned_achievement_block( $attributes ) {

    $param = '';
    if( !empty( $attributes['achievement_types'] ) ) {
        $data = json_decode( sanitize_text_field( $attributes['achievement_types'] ) );
        $types = '';
        if( count( $data ) > 0 ) {
            foreach( $data as $dt ) {
                $types .= empty( $types )?"":",";
                $types .= $dt->value;
            }
        }
        if( ! empty( $types ) )
            $param .= ' type="'.$types.'"';
    }
    if( !empty( $attributes['include'] ) ) {
        $data = json_decode( sanitize_text_field( $attributes['include'] ) );
        $include = '';
        if( count( $data ) > 0 ) {
            foreach( $data as $dt ) {
                $include .= empty( $include )?"":",";
                $include .= $dt->value;
            }
        }
        if( ! empty( $include ) )
            $param .= ' include="'.$include.'"';
    }
    if( !empty( $attributes['exclude'] ) ) {
        $data = json_decode( sanitize_text_field( $attributes['exclude'] ) );
        $exclude = '';
        if( count( $data ) > 0 ) {
            foreach( $data as $dt ) {
                $exclude .= empty( $exclude )?"":",";
                $exclude .= $dt->value;
            }
        }
        if( ! empty( $exclude ) )
            $param .= ' exclude="'.$exclude.'"';
    }
    if( !empty( $attributes['limit'] ) && intval( $attributes['limit'] ) > 0 ) {
        $param .= ' limit="'.sanitize_text_field( $attributes['limit'] ).'"';
    }
    if( !empty( $attributes['orderby'] ) ) {
        $param .= ' orderby="'.sanitize_text_field( $attributes['orderby'] ).'"';
    }
    if( !empty( $attributes['order'] ) ) {
        $param .= ' order="'.sanitize_text_field( $attributes['order'] ).'"';
    }

    if( !empty( $attributes['user_id'] ) ) {
        $user_id_obj = json_decode( sanitize_text_field( $attributes['user_id'] ) );
        if( $user_id_obj ) {
            $param .= ' user_id="'.$user_id_obj->value.'"';
        }
    }

    if( !empty( $attributes['show_search'] ) ) {
        $param .= ' show_search="'.sanitize_text_field( $attributes['show_search'] ).'"';
    } else {
        $param .= ' show_search="true"';
    }
    if( !empty( $attributes['wpms'] ) ) {
        $param .= ' wpms="'.sanitize_text_field( $attributes['wpms'] ).'"';
    }

    if( !empty( $attributes['show_description'] ) ) {
        $param .= ' show_description="'.sanitize_text_field( $attributes['show_description'] ).'"';
    }

    if( !empty( $attributes['show_thumb'] ) ) {
        $param .= ' show_thumb="'.sanitize_text_field( $attributes['show_thumb'] ).'"';
    }

    if( !empty( $attributes['show_title'] ) ) {
        $param .= ' show_title="'.sanitize_text_field( $attributes['show_title'] ).'"';
    }

    if( ! empty( $attributes['default_view'] ) ) {
        $param .= ' default_view="' . sanitize_text_field( $attributes['default_view'] ) . '"';
    }
    
    if( ! empty( $attributes['image_width'] ) ) {
        $param .= ' image_width="'.sanitize_text_field( $attributes['image_width'] ).'"';
    }
    
    if( ! empty( $attributes['image_height'] ) ) {
        $param .= ' image_height="'.sanitize_text_field( $attributes['image_height'] ).'"';
    }

    return do_shortcode( '[badgeos_user_earned_achievements '.$param.']' );
}

/**
 * Renders the credly assertion page block
 *
 * @param $attributes
 *
 * @return output html
 */
function badgeos_render_credly_assertion_page_blocks( $attributes ) {

    $param = '';
    if( !empty( $attributes['width'] ) && intval( $attributes['width'] ) > 0 ) {
        $param .= ' width="'.sanitize_text_field( $attributes['width'] ).'"';
    }
    if( !empty( $attributes['height'] ) ) {
        $param .= ' height="'.sanitize_text_field( $attributes['height'] ).'"';
    }
    if( !empty( $attributes['CID'] ) ) {
        $param .= ' CID="'.sanitize_text_field( $attributes['CID'] ).'"';
    }

    return do_shortcode( '[credly_assertion_page '.$param.']' );
}
/**
 * Renders the ranks list block
 *
 * @param $attributes
 *
 * @return output html
 */
function badgeos_render_ranks_list_blocks( $attributes ) {

    $param = '';
    if( !empty( $attributes['limit'] ) && intval( $attributes['limit'] ) > 0 ) {
        $param .= ' limit="'.sanitize_text_field( $attributes['limit'] ).'"';
    }

    if( !empty( $attributes['show_search'] ) ) {
        $param .= ' show_search="'.sanitize_text_field( $attributes['show_search'] ).'"';
    } else {
        $param .= ' show_search="true"';
    }
    if( !empty( $attributes['orderby'] ) ) {
        $param .= ' orderby="'.sanitize_text_field( $attributes['orderby'] ).'"';
    }

    if( !empty( $attributes['show_description'] ) ) {
        $param .= ' show_description="'.sanitize_text_field( $attributes['show_description'] ).'"';
    }

    if( !empty( $attributes['show_thumb'] ) ) {
        $param .= ' show_thumb="'.sanitize_text_field( $attributes['show_thumb'] ).'"';
    }

    if( !empty( $attributes['show_title'] ) ) {
        $param .= ' show_title="'.sanitize_text_field( $attributes['show_title'] ).'"';
    }

    if( !empty( $attributes['order'] ) ) {
        $param .= ' order="'.sanitize_text_field( $attributes['order'] ).'"';
    }

    if( !empty( $attributes['user_id'] ) ) {
        $user_id_obj = json_decode( sanitize_text_field( $attributes['user_id'] ) );
        if( $user_id_obj ) {
            $param .= ' user_id="'.$user_id_obj->value.'"';
        }
    }

    if( !empty( $attributes['types'] ) ) {
        $data = json_decode( sanitize_text_field( $attributes['types'] ) );
        $types = '';
        if( count( $data ) > 0 ) {
            foreach( $data as $dt ) {
                $types .= empty( $types )?"":",";
                $types .= $dt->value;
            }
        }
        if( ! empty( $types ) )
            $param .= ' types="'.$types.'"';
    }

    if( ! empty( $attributes['default_view'] ) ) {
        $param .= ' default_view="' . sanitize_text_field( $attributes['default_view'] ) . '"';
    }

    if( ! empty( $attributes['image_width'] ) ) {
        $param .= ' image_width="'.sanitize_text_field( $attributes['image_width'] ).'"';
    }
    
    if( ! empty( $attributes['image_height'] ) ) {
        $param .= ' image_height="'.sanitize_text_field( $attributes['image_height'] ).'"';
    }

    return do_shortcode( '[badgeos_ranks_list '.$param.']' );
}

/**
 * Renders the user earned ranks block
 *
 * @param $attributes
 *
 * @return output html
 */
function badgeos_render_earned_ranks_blocks( $attributes ) {

    $param = '';
    if( !empty( $attributes['limit'] ) && intval( $attributes['limit'] ) > 0 ) {
        $param .= ' limit="'.sanitize_text_field( $attributes['limit'] ).'"';
    }

    if( !empty( $attributes['show_search'] ) ) {
        $param .= ' show_search="'.sanitize_text_field( $attributes['show_search'] ).'"';
    } else {
        $param .= ' show_search="true"';
    }
    if( !empty( $attributes['orderby'] ) ) {
        $param .= ' orderby="'.sanitize_text_field( $attributes['orderby'] ).'"';
    }

    if( !empty( $attributes['show_description'] ) ) {
        $param .= ' show_description="'.sanitize_text_field( $attributes['show_description'] ).'"';
    }

    if( !empty( $attributes['show_thumb'] ) ) {
        $param .= ' show_thumb="'.sanitize_text_field( $attributes['show_thumb'] ).'"';
    }

    if( !empty( $attributes['show_title'] ) ) {
        $param .= ' show_title="'.sanitize_text_field( $attributes['show_title'] ).'"';
    }

    if( !empty( $attributes['order'] ) ) {
        $param .= ' order="'.sanitize_text_field( $attributes['order'] ).'"';
    }

    if( !empty( $attributes['user_id'] ) ) {
        $user_id_obj = json_decode( sanitize_text_field( $attributes['user_id'] ) );
        if( $user_id_obj ) {
            $param .= ' user_id="'.$user_id_obj->value.'"';
        }
    }

    if( !empty( $attributes['rank_types'] ) ) {
        $data = json_decode( sanitize_text_field( $attributes['rank_types'] ) );
        $types = '';
        if( count( $data ) > 0 ) {
            foreach( $data as $dt ) {
                $types .= empty( $types )?"":",";
                $types .= $dt->value;
            }
        }
        if( ! empty( $types ) )
            $param .= ' rank_type="'.$types.'"';
    }

    if( ! empty( $attributes['default_view'] ) ) {
        $param .= ' default_view="' . sanitize_text_field( $attributes['default_view'] ) . '"';
    }

    if( ! empty( $attributes['image_width'] ) ) {
        $param .= ' image_width="'.sanitize_text_field( $attributes['image_width'] ).'"';
    }
    
    if( ! empty( $attributes['image_height'] ) ) {
        $param .= ' image_height="'.sanitize_text_field( $attributes['image_height'] ).'"';
    }

    return do_shortcode( '[badgeos_user_earned_ranks '.$param.']' );
}

/**
 * Renders the achievements list block
 *
 * @param $attributes
 *
 * @return output html
 */
function badgeos_render_achivements_list_blocks( $attributes ) {

    $param = '';
    if( !empty( $attributes['limit'] ) && intval( $attributes['limit'] ) > 0 ) {
        $param .= ' limit="'.sanitize_text_field( $attributes['limit'] ).'"';
    }
    if( !empty( $attributes['show_filter'] ) ) {
        $param .= ' show_filter="'.sanitize_text_field( $attributes['show_filter'] ).'"';
    } else {
        $param .= ' show_filter="true"';
    }
    if( !empty( $attributes['show_search'] ) ) {
        $param .= ' show_search="'.sanitize_text_field( $attributes['show_search'] ).'"';
    } else {
        $param .= ' show_search="true"';
    }

    if( !empty( $attributes['multisite'] ) ) {
        $param .= ' wpms="'.sanitize_text_field( $attributes['multisite'] ).'"';
    } else {
        $param .= ' wpms="true"';
    }

    if( !empty( $attributes['show_description'] ) ) {
        $param .= ' show_description="'.sanitize_text_field( $attributes['show_description'] ).'"';
    } else {
        $param .= ' show_description="true"';
    }

    if( !empty( $attributes['show_thumb'] ) ) {
        $param .= ' show_thumb="'.sanitize_text_field( $attributes['show_thumb'] ).'"';
    } else {
        $param .= ' show_thumb="true"';
    }

    if( !empty( $attributes['show_title'] ) ) {
        $param .= ' show_title="'.sanitize_text_field( $attributes['show_title'] ).'"';
    } else {
        $param .= ' show_title="true"';
    }

    if( !empty( $attributes['show_steps'] ) ) {
        $param .= ' show_steps="'.sanitize_text_field( $attributes['show_steps'] ).'"';
    } else {
        $param .= ' show_steps="true"';
    }

    if( !empty( $attributes['orderby'] ) ) {
        $param .= ' orderby="'.sanitize_text_field( $attributes['orderby'] ).'"';
    }

    if( !empty( $attributes['order'] ) ) {
        $param .= ' order="'.sanitize_text_field( $attributes['order'] ).'"';
    }
    if( !empty( $attributes['user_id'] ) ) {
        $param .= ' user_id="'.sanitize_text_field( $attributes['user_id'] ).'"';
    }
    if( !empty( $attributes['achievement_types'] ) ) {
        $data = json_decode( sanitize_text_field( $attributes['achievement_types'] ) );
        $types = '';
        if( count( $data ) > 0 ) {
            foreach( $data as $dt ) {
                $types .= empty( $types )?"":",";
                $types .= $dt->value;
            }
        }
        if( ! empty( $types ) )
            $param .= ' type="'.$types.'"';
    }

    if( !empty( $attributes['include'] ) ) {
        $data = json_decode( sanitize_text_field( $attributes['include'] ) );
        $include = '';
        if( count( $data ) > 0 ) {
            foreach( $data as $dt ) {
                $include .= empty( $types )?"":",";
                $include .= $dt->value;
            }
        }
        if( ! empty( $include ) )
            $param .= ' include="'.$include.'"';
    }

    if( !empty( $attributes['exclude'] ) ) {
        $data = json_decode( sanitize_text_field( $attributes['exclude'] ) );
        $exclude = '';
        if( count( $data ) > 0 ) {
            foreach( $data as $dt ) {
                $exclude .= empty( $types )?"":",";
                $exclude .= $dt->value;
            }
        }
        if( ! empty( $exclude ) )
            $param .= ' exclude="'.$exclude.'"';
    }

    if( ! empty( $attributes['default_view'] ) ) {
        $param .= ' default_view="' . sanitize_text_field( $attributes['default_view'] ) . '"';
    }

    if( ! empty( $attributes['image_width'] ) ) {
        $param .= ' image_width="'.sanitize_text_field( $attributes['image_width'] ).'"';
    }
    
    if( ! empty( $attributes['image_height'] ) ) {
        $param .= ' image_height="'.sanitize_text_field( $attributes['image_height'] ).'"';
    }
    
    return do_shortcode( '[badgeos_achievements_list '.$param.']' );
}

/**
 * Registers the block types
 *
 * @return none
 */
function badgeos_render_my_php_block(  ) {

    register_block_type( 'bos/badgeos-achievements-list-block', array(
        'render_callback' => 'badgeos_render_achivements_list_blocks',
        'category' => 'badgeos-blocks',
        'attributes' => array(
            'achievement_types' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'limit' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'show_filter' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'show_search' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'order' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'orderby' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'user_id' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'include' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'exclude' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'multisite' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'show_description' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'show_thumb' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'show_title' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'show_steps' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'image_width' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'image_height' => array(
                'type' => 'string',
                'default'=> ''
            ),
        )
    ));

    register_block_type( 'bos/badgeos-user-earned-achievement-block', array(
        'render_callback' => 'badgeos_render_user_earned_achievement_block',
        'category' => 'badgeos-blocks',
        'attributes' => array(
            'achievement_types' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'limit' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'show_search' => array(
                'type' => 'string',
                'default'=> 'false'
            ),
            'include' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'exclude' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'order' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'orderby' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'user_id' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'wpms' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'show_description' => array(
                'type' => 'string',
                'default' => 'true'
            ),
            'show_thumb' => array(
                'type' => 'string',
                'default' => 'true'
            ),
            'show_title' => array(
                'type' => 'string',
                'default' => 'true'
            ),
            'image_width' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'image_height' => array(
                'type' => 'string',
                'default'=> ''
            ),
        )
    ));

    register_block_type( 'bos/badgeos-user-earned-points-block', array(
        'render_callback' => 'badgeos_render_user_earned_points_block',
        'category' => 'badgeos-blocks',
        'attributes' => array(
            'point_type' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'user_id' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'show_title' => array(
                'type' => 'string',
                'default'=> 'true'
            )
        )
    ));

    register_block_type( 'bos/badgeos-single-rank-block', array(
        'render_callback' => 'badgeos_render_single_rank_block',
        'category' => 'badgeos-blocks',
        'attributes' => array(
            'rank' => array(
                'type' => 'string', 
                'default'=> ''
            ),
            'show_description' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'show_thumb' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'show_title' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'image_width' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'image_height' => array(
                'type' => 'string',
                'default'=> ''
            ),
        )
    ));

    register_block_type( 'bos/badgeos-single-achievement-block', array(
        'render_callback' => 'badgeos_render_achievement_block',
        'category' => 'badgeos-blocks',
        'attributes' => array(
            'achievement' => array(
                'type' => 'string', 
                'default'=> ''
            ),
            'show_description' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'show_thumb' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'show_title' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'show_steps' => array(
                'type' => 'string',
                'default'=> 'true'
            ),
            'image_width' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'image_height' => array(
                'type' => 'string',
                'default'=> ''
            ),
        )
    ));

    register_block_type( 'bos/badgeos-ranks-list-block', array(
        'render_callback' => 'badgeos_render_ranks_list_blocks',
        'category' => 'badgeos-blocks',
        'attributes' => array(
            'types' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'limit' => array(
                'type' => 'string',
                'default'=> '10'
            ),
            'order' => array(
                'type' => 'string',
                'default'=> 'ASC'
            ),
            'orderby' => array(
                'type' => 'string',
                'default'=> 'rank_id'
            ),
            'user_id' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'show_search' => array(
                'type' => 'string',
                'default'=> 'false'
            ),
            'show_description' => array(
                'type' => 'string',
                'default' => 'true'
            ),
            'show_thumb' => array(
                'type' => 'string',
                'default' => 'true'
            ),
            'show_title' => array(
                'type' => 'string',
                'default' => 'true'
            ),
            'image_width' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'image_height' => array(
                'type' => 'string',
                'default'=> ''
            ),
        )
    ));

    register_block_type( 'bos/badgeos-user-earned-ranks-block', array(
        'render_callback' => 'badgeos_render_earned_ranks_blocks',
        'category' => 'badgeos-blocks',
        'attributes' => array(
            'rank_types' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'limit' => array(
                'type' => 'string',
                'default'=> '10'
            ),
            'order' => array(
                'type' => 'string',
                'default'=> 'ASC'
            ),
            'orderby' => array(
                'type' => 'string',
                'default'=> 'rank_id'
            ),
            'user_id' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'show_search' => array(
                'type' => 'string',
                'default'=> 'false'
            ),
            'show_description' => array(
                'type' => 'string',
                'default' => 'true'
            ),
            'show_thumb' => array(
                'type' => 'string',
                'default' => 'true'
            ),
            'show_title' => array(
                'type' => 'string',
                'default' => 'true'
            ),
            'image_width' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'image_height' => array(
                'type' => 'string',
                'default'=> ''
            ),
        )
    ));

    register_block_type( 'bos/block-credly-assertion-page-block', array(
        'render_callback' => 'badgeos_render_credly_assertion_page_blocks',
        'category' => 'badgeos-blocks',
        'attributes' => array(
            'width' => array(
                'type' => 'string',
                'default'=> '560'
            ),
            'height' => array(
                'type' => 'string',
                'default'=> '1000'
            ),
            'CID' => array(
                'type' => 'string',
                'default'=> ''
            ),
        )
    ));

    register_block_type( 'bos/badgeos-evidence-block', array(
        'render_callback' => 'badgeos_render_evidence_blocks',
        'category' => 'badgeos-blocks',
        'attributes' => array(
            'achievement' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'user_id' => array(
                'type' => 'string',
                'default'=> ''
            ),
            'award_id' => array(
                'type' => 'string',
                'default'=> ''
            ),
        )
    ));
}
add_action( 'init', 'badgeos_render_my_php_block');