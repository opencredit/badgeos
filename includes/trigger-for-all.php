<?php

function badgeos_activity_triggers_for_all_callback( $triggers ) {

    $fields = [ 
            [ 'label'=> 'field21', "id"=>"badgeos_my_first_field21", 'type' => 'text', 'fields'=>[] ],
            [ 'label'=> 'field22', "id"=>"badgeos_my_first_field22", 'type' => 'select', 'options'=> [ 
                '1' => '1', 
                '2' => '2', 
                '3' => '3', 
                '4' => '4' 
                ], 'fields' => [] ],
        ];
    $fields2 = [ 
        ['label'=> 'field23', "id"=>"badgeos_my_first_field23", 'type' => 'text', 'fields'=>[] ],
        ['label'=> 'field24', "id"=>"badgeos_my_first_field24", 'type' => 'select', 'options'=> [ 
            '1' => '1', 
            '2' => '2', 
            '3' => '3', 
            '4' => '4' 
            ], 'fields' => [] ],
    ];
    $triggers[ 'new_trigger_event' ]	= array( 
            'label'=> __( 'Login to new Website', 'badgeos' ),
            'sub_triggers'=> [
                [ 'trigger' => 'trigger21', 'label' => __( 'Trigger 1', 'badgeos' ), 'fields' => $fields ],
                [ 'trigger' => 'trigger22', 'label' => __( 'Trigger 2', 'badgeos' ), 'fields' => $fields2 ],
            ]
        );
    
    return $triggers;
}
add_filter( 'badgeos_activity_triggers_for_all', 'badgeos_activity_triggers_for_all_callback' );

function badgeos_activity_triggers_for_all_callback2( $triggers ) {

    $fields = [ 
            ['label'=> 'field1', "id"=>"badgeos_my_first_field1", 'type' => 'text'   , 'fields'=>[] ],
            ['label'=> 'field2', "id"=>"badgeos_my_second_field2", 'type' => 'select' , 'options'=> [ 
                '1' => '1', 
                '2' => '2', 
                '3' => '3', 
                '4' => '4' 
                ], 'fields' => [] ],
        ];
        
    $fields2 = [ 
        ['label'=> 'field3', "id"=>"badgeos_my_first_field3", 'type' => 'text'   , 'fields'=>[] ],
        ['label'=> 'field4', "id"=>"badgeos_my_second_field4", 'type' => 'select' , 'options'=> [ 
            '1' => '1', 
            '2' => '2', 
            '3' => '3', 
            '4' => '4' 
            ], 'fields' => [] ],
    ];

    $triggers[ 'new_trigger_event2' ]	= array( 
            'label'=> __( 'new Website', 'badgeos' ),
            'sub_triggers'=> [
                [ 'trigger' => 'trigger3', 'label' => __( 'Trigger 3', 'badgeos' ), 'fields' => $fields ],
                [ 'trigger' => 'trigger4', 'label' => __( 'Trigger 4', 'badgeos' ), 'fields' => $fields2 ],
            ]
        );
    
    return $triggers;
}
add_filter( 'badgeos_activity_triggers_for_all', 'badgeos_activity_triggers_for_all_callback2' );
 
function call_back_trigger() {
    $user_id = get_current_user_id(); 
    if( intval( $user_id ) > 0 )
        badgeos_perform_event( 'trigger22', $user_id, ['badgeos_my_first_field23'=>'1','badgeos_my_first_field24'=>'1']);
}
add_action( 'save_post', 'call_back_trigger' );

function badgeos_check_dynamic_filter_callback( $is_valid=true, $type, $trigger, $step_params, $args ) {
    
    if( $step_params['badgeos_my_first_field23'] == $args['badgeos_my_first_field23'] ) {

        if( $step_params['badgeos_my_first_field24'] == $args['badgeos_my_first_field24'] ) {
            
            return true;
        }    
    }

    return false;
}
add_filter( 'badgeos_check_dynamic_trigger_filter', 'badgeos_check_dynamic_filter_callback', 10, 5 );