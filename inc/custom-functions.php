<?php
/*  WPBizPlugins Custom Admin Help Boxes
    Copyright 2014  Gabriel Nordeborn  (email : gabriel@wpbizplugins.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  021101301  USA
*/

/**
 *
 * This file contains custom functions.
 *
 */

/**
 * Simple function for checking whether or not we're on a certain post type.
 *
 * @return Returns the current post type.
 * @since 1.0
 *
 */

function wpbizplugins_cahb_return_post_type() {

    global $post, $typenow, $current_screen;

    //we have a post so we can just get the post type from that
    if ( $post && $post->post_type )
        return $post->post_type;

    //check the global $typenow - set in admin.php
    elseif( $typenow )
        return $typenow;

    //check the global $current_screen object - set in sceen.php
    elseif( $current_screen && $current_screen->post_type )
        return $current_screen->post_type;

    //lastly check the post_type querystring
    elseif( isset( $_REQUEST['post_type'] ) )
        return sanitize_key( $_REQUEST['post_type'] );

    elseif (get_post_type($_REQUEST['post']))
            return get_post_type($_REQUEST['post']);
    //we do not know the post type!
    return null;

}

/**
 * Clean a string for use as HTML ID or similar element.
 *
 * @param string $string The string to be cleaned.
 * @return string Returns the cleaned string.
 * @since 1.0
 *
 */

function wpbizplugins_cahb_clean_string_for_html_id( $string ) {

    //Lower case everything
    $string = strtolower($string);
    //Make alphanumeric (removes all other characters)
    $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
    //Clean up multiple dashes or whitespaces
    $string = preg_replace("/[\s-]+/", " ", $string);
    //Convert whitespaces and underscore to dash
    $string = preg_replace("/[\s_]/", "-", $string);

    return $string;

}


/**
 * Get an array of all metaboxes that should be added. Mostly to get around weird issues with pulling posts inside a post.
 *
 * @return array Returns an array of all the metaboxes + their settings.
 * @since 1.0
 *
 */

function wpbizplugins_cahb_get_metaboxes_array() {

    $metaboxes_array = array();

    $args = array(
    
        'post_type' => 'wpbizplugins-cahb'
        
    );

    $help_boxes = new WP_Query( $args );

    if( $help_boxes->have_posts() ) {

        while( $help_boxes->have_posts() ) {
            $help_boxes->the_post();
            
            $help_box_id = get_the_ID();

            // Get the various needed fields
            $title = get_the_title();
            $html_id = wpbizplugins_cahb_clean_string_for_html_id( $title );

            // Get the data of where to display the boxes
            $where_to_display = get_post_meta( $help_box_id, 'where_to_display', true );
            // Get the additional text field, and get rid of all whitespace
            $where_to_display_additional = preg_replace('/\s+/', '', get_post_meta( $help_box_id, 'where_to_display_additional', true ) );
            $where_to_display_additional = sanitize_text_field( $where_to_display_additional );
            $where_to_display_additional_array = explode(',', $where_to_display_additional );

            $where_to_display = array_merge( $where_to_display, $where_to_display_additional_array );

            $content = get_post_meta( $help_box_id, 'content', true );
            $context = get_post_meta( $help_box_id, 'context', true );
            $priority = get_post_meta( $help_box_id, 'priority', true );
            $show_extras = get_post_meta( $help_box_id, 'show_extras', true );
            $use_popup = get_post_meta( $help_box_id, 'use_popup', true );
            $popup_button_text = get_post_meta( $help_box_id, 'popup_button_text', true );
            $popup_button_text_before = get_post_meta( $help_box_id, 'popup_button_text_before', true );

            $metaboxes_array[] = array( 

                'html_id'                   => $html_id,
                'title'                     => $title,
                'content'                   => $content,
                'callback'                  => 'wpbizplugins_cahb_print_help_box_content',
                'where_to_display'          => $where_to_display,
                'context'                   => $context,
                'priority'                  => $priority,
                'show_extras'               => $show_extras,
                'use_popup'                 => $use_popup,
                'popup_button_text'         => $popup_button_text,
                'popup_button_text_before'  => $popup_button_text_before

            );
        }

    }

    return $metaboxes_array;
}

/**
 *
 * Function to register regular metaboxes in the correct hooks.
 *
 * @param array All the metaboxes, in an array.
 * @return null Returns nothing.
 * @since 1.0
 *
 */

function wpbizplugins_cahb_register_metaboxes( $metaboxes_array ) {

    foreach( $metaboxes_array as $metabox ) {

        foreach( $metabox['where_to_display'] as $where_to_display ) {

            if( ( $where_to_display != 'dashboard' ) && ( $where_to_display != '' ) ) {

                add_meta_box( 

                    $metabox['html_id'],
                    $metabox['title'],
                    $metabox['callback'],
                    $where_to_display,
                    $metabox['context'],
                    $metabox['priority'],
                    array(
                        'content'                   => $metabox['content'],
                        'show_extras'               => $metabox['show_extras'],
                        'use_popup'                 => $metabox['use_popup'],
                        'popup_button_text'         => $metabox['popup_button_text'],
                        'popup_button_text_before'  => $metabox['popup_button_text_before']

                    )

                );

            }

        }

    }

}

/**
 *
 * Function to register dashboard widgets in the correct hooks.
 *
 * @param array All the dashboard widgets, in an array.
 * @return null Returns nothing.
 * @since 1.0
 *
 */

function wpbizplugins_cahb_register_dashboard_widgets( $metaboxes_array ) {

    foreach( $metaboxes_array as $metabox ) {

        foreach( $metabox['where_to_display'] as $where_to_display ) {

            if( ( $where_to_display == 'dashboard' ) && ( $where_to_display != '' ) ) {

                add_meta_box( 

                    $metabox['html_id'],
                    $metabox['title'],
                    $metabox['callback'],
                    $where_to_display,
                    $metabox['context'],
                    $metabox['priority'],
                    array(
                        'content'                   => $metabox['content'],
                        'show_extras'               => $metabox['show_extras'],
                        'use_popup'                 => $metabox['use_popup'],
                        'popup_button_text'         => $metabox['popup_button_text'],
                        'popup_button_text_before'  => $metabox['popup_button_text_before']
                    )

                );

            }

        }

    }

}

/**
 * Print the CSS style for the buttons.
 *
 */

function wpbizplugins_cahb_print_plugin_styles() {

    global $wpbizplugins_cahb_options;

    echo '<style type="text/css">

    .wpbizplugins-cahb-content {

        margin-top: 20px;
        margin-bottom: 20px;

    }

    .wpbizplugins-cahb-metabox {
        padding: 10px 10px 10px 10px;
        margin: 10px 10px 10px 10px;
    }

    .wpbizplugins-cahb-support-section {

        background-color: #f2f2f2;
        padding-left: 10px;
        padding-right: 10px;
        padding-top: 5px;
        padding-bottom: 10px;
        -webkit-border-top-left-radius: 5px;
        -moz-border-radius-topleft:5px;
        border-top-left-radius:5px;
        -webkit-border-top-right-radius:5px;
        -moz-border-radius-topright:5px;
        border-top-right-radius:5px;
        -webkit-border-bottom-right-radius:5px;
        -moz-border-radius-bottomright:5px;
        border-bottom-right-radius:5px;
        -webkit-border-bottom-left-radius:5px;
        -moz-border-radius-bottomleft:5px;
        border-bottom-left-radius:5px;
        text-indent:0px;
        border:2px solid #ececec;


    }


    .wpbizplugins-cahb-button {

        padding: 5px 5px 5px 5px;
        margin-top: 5px;
        
        background-color:#4ea5fc;
        -webkit-border-top-left-radius: 5px;
        -moz-border-radius-topleft:5px;
        border-top-left-radius:5px;
        -webkit-border-top-right-radius:5px;
        -moz-border-radius-topright:5px;
        border-top-right-radius:5px;
        -webkit-border-bottom-right-radius:5px;
        -moz-border-radius-bottomright:5px;
        border-bottom-right-radius:5px;
        -webkit-border-bottom-left-radius:5px;
        -moz-border-radius-bottomleft:5px;
        border-bottom-left-radius:5px;
        text-indent:0px;
        border:1px solid #469df5;
        display:inline-block;
        color:#FFF;        
        
        max-width: 100%;
        width:96%;
        text-decoration:none;
        //text-align:center;
        text-shadow:1px 1px 1px #528ecc;
    }

    .wpbizplugins-cahb-button:hover { 
        background-color:#6aaaeb; 
        color: #F6F6F6 !important;
    }

    .wpbizplugins-cahb-button:active { 
        color: #F6F6F6 !important;
    }

    .wpbizplugins-cahb-button:focus { 
        color: #F6F6F6 !important;
    }

    .btn-blue { background-color:#2EA0CC !important; border:1px solid #408099 !important; text-shadow:0px 0px 3px #0F6485 !important; }
    .btn-blue:hover { background-color:#408099 !important; }.btn-green { background-color:#26B637 !important; border:1px solid #42A84F !important; text-shadow:0px 0px 3px #0E921E !important; }
    .btn-green:hover { background-color:#42A84F !important; }.btn-red { background-color:#FF4531 !important; border:1px solid #BF564B !important; text-shadow:0px 0px 3px #A61E10 !important; }
    .btn-red:hover { background-color:#BF564B !important; }.btn-orange { background-color:#FF9C31 !important; border:1px solid #BF884B !important; text-shadow:0px 0px 3px #A65E10 !important; }
    .btn-orange:hover { background-color:#BF884B !important; }

    ' . $wpbizplugins_cahb_options['custom_css'] . '

    </style>
    ';

    unset( $wpbizplugins_cahb_options );

}

add_action( 'admin_head', 'wpbizplugins_cahb_print_plugin_styles' );

/**
 * Returns a number cleaned from everything but digits.
 *
 * @param string $number The number to clean.
 * @return string The cleaned number.
 * @since 1.0
 *
 */

function wpbizplugins_cahb_clean_number( $number ) {

    $number_clean = preg_replace("/[^0-9]/","", $number);
    
    return $number_clean;

}

/**
 * Return array of capabilities for use with restricting access to editing the plugin contents.
 *
 * @return array Returns an array of all available capabilities.
 * @since 1.1
 *
 */

function wpbizplugins_cahb_return_capabilities_array() {

    $capabilities_array = array(
        'activate_plugins',
        'add_users',
        'create_users',
        'delete_others_pages',
        'delete_others_posts',
        'delete_pages',
        'delete_plugins',
        'delete_posts',
        'delete_private_pages',
        'delete_private_posts',
        'delete_published_pages',
        'delete_published_posts',
        'delete_themes',
        'delete_users',
        'edit_dashboard',
        'edit_others_pages',
        'edit_others_posts',
        'edit_pages',
        'edit_plugins',
        'edit_posts',
        'edit_private_pages',
        'edit_private_posts',
        'edit_published_pages',
        'edit_published_posts',
        'edit_theme_options',
        'edit_themes',
        'edit_users',
        'export',
        'import',
        'install_plugins',
        'install_themes',
        'list_users',
        'manage_categories',
        'manage_links',
        'manage_options',
        'moderate_comments',
        'promote_users',
        'publish_pages',
        'publish_posts',
        'read',
        'read_private_pages',
        'read_private_posts',
        'remove_users',
        'switch_themes',
        'unfiltered_html',
        'unfiltered_upload',
        'update_core',
        'update_plugins',
        'update_themes',
        'upload_files'
    );

    $capabilities_array_keypair = array();

    foreach( $capabilities_array as $capability ) {

        $capabilities_array_keypair[$capability] = $capability;

    }

    return $capabilities_array_keypair;
}
