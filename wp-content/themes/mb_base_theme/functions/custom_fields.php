<?php

function mb_setup_fieldGroups() {
// resources  acf group

register_field_group(array(
    'id' => 'acf_resource',
    'title' => 'Resource',
    'fields' => array(
        array(
            'key' => 'field_5b8fc483749218',
            'label' => 'Private URL',
            'name' => 'document_private__url',
            'type' => 'text',
            'default_value' => '',
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
            'formatting' => 'html',
            //    'sanitization_callback' => 'mb_sanitize_private_link',
            'maxlength' => '45',
        ),
        array(
            'key' => 'field_5b8fc2dd4bea4',
            'label' => 'Timeline Date(s)',
            'name' => 'textdate',
            'type' => 'text',
            'required' => 0,
            'default_value' => '',
            'placeholder' => 'text Dates',
            'prepend' => '',
            'append' => '',
            'formatting' => 'html',
            'maxlength' => '20',
        ),
        array(
            'key' => 'field_5b8fc36a4bea7',
            'label' => 'Include Document',
            'name' => 'include_document',
            'type' => 'true_false',
            'message' => '',
            'default_value' => 0,
        ),
        array(
            'key' => 'field_5b8fc423bfbb6',
            'label' => 'Document Contributor',
            'name' => 'document_contributor',
            'type' => 'text',
            'conditional_logic' => array(
                'status' => 1,
                'rules' => array(
                    array(
                        'field' => 'field_5b8fc36a4bea7',
                        'operator' => '==',
                        'value' => '1',
                    ),
                ),
                'allorany' => 'all',
            ),
            'default_value' => '',
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
            'formatting' => 'html',
            'maxlength' => '',
        ),
        array(
            'key' => 'field_5b8fc2ec4bea5',
            'label' => 'Document Synopsis',
            'name' => 'document_synopsis',
            'type' => 'wysiwyg',
            'conditional_logic' => array(
                'status' => 1,
                'rules' => array(
                    array(
                        'field' => 'field_5b8fc36a4bea7',
                        'operator' => '==',
                        'value' => '1',
                    ),
                ),
                'allorany' => 'all',
            ),
            'default_value' => '',
            'toolbar' => 'full',
            'media_upload' => 'yes',
        ),
        array(
            'key' => 'field_5b8fc3054bea6',
            'label' => 'Document ',
            'name' => 'document',
            'type' => 'file',
            'conditional_logic' => array(
                'status' => 1,
                'rules' => array(
                    array(
                        'field' => 'field_5b8fc36a4bea7',
                        'operator' => '==',
                        'value' => '1',
                    ),
                ),
                'allorany' => 'all',
            ),
            'save_format' => 'object',
            'library' => 'uploadedTo',
        ),
        array(
            'key' => 'field_5b8fc47fb3ee0',
            'label' => 'Include Video',
            'name' => 'include_video',
            'type' => 'true_false',
            'message' => '',
            'default_value' => 0,
        ),
        array(
            'key' => 'field_5b8af244eadab',
            'label' => 'Video Contributor',
            'name' => 'video_contributor',
            'type' => 'text',
            'default_value' => '',
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
            'formatting' => 'html',
            'maxlength' => '',
        ),
        array(
            'key' => 'field_5b8af2c73c9de',
            'label' => 'Video Synposis',
            'name' => 'video_synposis',
            'type' => 'wysiwyg',
            'conditional_logic' => array(
                'status' => 1,
                'rules' => array(
                    array(
                        'field' => 'field_5b8fc47fb3ee0',
                        'operator' => '==',
                        'value' => '1',
                    ),
                ),
                'allorany' => 'all',
            ),
            'default_value' => '',
            'toolbar' => 'full',
            'media_upload' => 'yes',
        ),
        array(
            'key' => 'field_5b8af27a3c9dc',
            'label' => 'YT or Vimeo?',
            'name' => 'yt_or_vimeo',
            'type' => 'radio',
            'required' => 1,
            'choices' => array(
                'Youtube' => 'Youtube',
                'Vimeo' => 'Vimeo',
            ),
            'other_choice' => 0,
            'save_other_choice' => 0,
            'default_value' => '',
            'layout' => 'vertical',
        ),
        array(
            'key' => 'field_5b8af2a73c9dd',
            'label' => 'Video URL',
            'name' => 'video_url',
            'type' => 'text',
            'required' => 1,
            'default_value' => '',
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
            'formatting' => 'html',
            'maxlength' => '',
        ),
    ),
    'location' => array(
        array(
            array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'resource',
                'order_no' => 0,
                'group_no' => 0,
            ),
        ),
//                array(
        //                    array(
        //                        'param'    => 'post_type',
        //                        'operator' => '==',
        //                        'value'    => 'post',
        //                        'order_no' => 0,
        //                        'group_no' => 1,
        //                    ),
        //                ),
    ),
    'options' => array(
        'position' => 'acf_after_title',
        'layout' => 'no_box',
        'hide_on_screen' => array(
            0 => 'the_content',
            1 => 'excerpt',
            2 => 'custom_fields',
            3 => 'discussion',
            4 => 'comments',
            5 => 'revisions',
            6 => 'slug',
            7 => 'format',
            8 => 'categories',
        ),
    ),
    'menu_order' => 0,
));

}
//add_action( 'acf/register_fields', 'mb_setup_fieldGroups' );
add_action( 'acf/init', 'mb_setup_fieldGroups' );

// admin post listing

add_filter('manage_resource_posts_columns', 'mb_resource_date_heading', 10, 2);
//add_filter('manage_resource_sortable_columns', 'mb_resource_date_heading', 10, 2);
function mb_resource_date_heading($c)
{
	$a = array_slice($c,0,1);
	$b = array_slice($c,1);
	$new['Dates'] = "Timeline Dating";
	return array_merge(array_merge($a,$new),$b);
	//return array_merge($a,$c;
}
add_action( 'manage_resource_posts_custom_column' , 'custom_columns', 10, 2 );

function custom_columns( $column, $post_id ) {
	//switch ( $column ) {
	//	case 'Dates':
		$field = get_post_meta( $post_id, 'textdate', true );
			//$terms = get_the_term_list( $post_id, 'textdate', '', ',', '' );
			if ( !empty( $field ) ) {
				echo $field;
			} else {
				echo '(not set)';
			}
	//		break;

		// case 'publisher':
		// 	echo get_post_meta( $post_id, 'publisher', true ); 
		// 	break;
	//}
}
add_action('admin_head', 'my_admin_custom_styles');
function my_admin_custom_styles() {
    $output_css = '<style type="text/css">
        .column-Dates { width:133px; }
        // .column-tags { display: none }
        // .column-author { width:30px !important; overflow:hidden }
        // .column-categories { width:30px !important; overflow:hidden }
        // .column-title a { font-size:30px !important }
    </style>';
    echo $output_css;
}

// make admin dates on resources sortable
function resource_sortable_columns( $columns ) {
	$columns['Dates'] = 'slice';
	return $columns;
}
add_filter( 'manage_edit-resource_sortable_columns', 'resource_sortable_columns' );

function resource_slice_orderby( $query ) {
    if( ! is_admin() )
        return;
 
    $orderby = $query->get( 'orderby');
 
    if( 'slice' == $orderby ) {
      //  $query->set('meta_key','slices');
        $query->set('orderby','menu_order'); // "meta_value_num" is used for numeric sorting
                                                 // "meta_value"     is used for Alphabetically sort.
        
        // We can user any query params which used in WP_Query.
    }
}
add_action( 'pre_get_posts', 'resource_slice_orderby' );
