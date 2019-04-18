<?php
/**
 * Module Name: WordPress.com Block Editor Iframe
 * Module Description: Allow new block editor posts to be composed on WordPress.com.
 * Jumpstart Description: Allow new block editor posts to be composed on WordPress.com.
 * Sort Order: 15
 * First Introduced: 7.3
 * Requires Connection: Yes
 * Auto Activate: Yes
 * Module Tags: Writing
 * Feature: Writing
 * Additional Search Queries: iframes, allow, compose, WordPress.com, block, editor, post
 */

function jetpack_disable_send_frame_options_header() {
	if ( jetpack_framing_allowed() ) {
		remove_action( 'admin_init', 'send_frame_options_header' );
	}
}
add_action( 'admin_init', 'jetpack_disable_send_frame_options_header', 1 ); // High priority to get ahead of send_frame_options_header

function jetpack_framing_allowed() {
	if ( empty( $_GET['frame-nonce'] ) || false === strpos( $_GET['frame-nonce'], '.' ) ) {
		return false;
	}

	list( $token, $signature ) = explode( '.', rawurldecode( $_GET['frame-nonce'] ) );

	$verified = Jetpack::init()->verify_xml_rpc_signature( $token, $signature );

	if ( $verified && ! defined( 'IFRAME_REQUEST' ) ) {
		define( 'IFRAME_REQUEST', true );
	}

	return (bool) $verified;
}

function jetpack_add_iframed_body_class( $classes ) {
	if ( jetpack_framing_allowed() ) {
		$classes .= ' is-iframed ';
	}
	return $classes;
}
add_filter( 'admin_body_class', 'jetpack_add_iframed_body_class' );

/**
 * Enqueue the scripts for the WordPress.com block editor integration.
 */
function jetpack_enqueue_wpcom_block_editor_scripts() {
	$suffix        = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	$version       = gmdate( 'YW' );
	$is_calypsoify = 1 === (int) get_user_meta( get_current_user_id(), 'calypsoify', true );

	wp_enqueue_script(
		'wpcom-block-editor-common',
		'//widgets.wp.com/wpcom-block-editor/common' . $suffix . '.js',
		array( 'lodash', 'wp-compose', 'wp-data', 'wp-editor', 'wp-rich-text' ),
		$version,
		true
	);

	wp_localize_script(
		'wpcom-block-editor-common',
		'wpcomGutenberg',
		array(
			'switchToClassic' => array(
				'isVisible' => false,
			),
			'isCalypsoify'    => $is_calypsoify,
			'richTextToolbar' => array(
				'justify'   => __( 'Justify' ),
				'underline' => __( 'Underline' ),
			),
		)
	);

	if ( $is_calypsoify ) {
		wp_enqueue_script(
			'wpcom-block-editor-calypso-iframe-bridge',
			'//widgets.wp.com/wpcom-block-editor/calypso-iframe-bridge-server' . $suffix . '.js',
			array( 'calypsoify_wpadminmods_js', 'jquery', 'lodash', 'react', 'wp-blocks', 'wp-data', 'wp-hooks', 'wp-tinymce', 'wp-url' ),
			$version,
			true
		);
	}
}
add_action( 'admin_enqueue_scripts', 'jetpack_enqueue_wpcom_block_editor_scripts' );

/**
 * Register the Tiny MCE plugins for the WordPress.com block editor integration.
 *
 * @param array $plugin_array An array of external Tiny MCE plugins.
 *
 * @return array External TinyMCE plugins.
 */
function jetpack_add_wpcom_block_editor_tinymce_plugins( $plugin_array ) {
	$is_calypsoify = 1 === (int) get_user_meta( get_current_user_id(), 'calypsoify', true );

	if ( $is_calypsoify ) {
		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$version = gmdate( 'YW' );
		$plugin_array['gutenberg-wpcom-iframe-media-modal'] = add_query_arg(
			'v',
			$version,
			'//widgets.wp.com/wpcom-block-editor/calypso-tinymce' . $suffix . '.js'
		);
	}
	return $plugin_array;
}

/**
 * Add the filters to customize the Tiny MCE editor for the WordPress.com block editor integration.
 */
function jetpack_add_wpcom_block_editor_tinyme_filters() {
	add_filter( 'mce_external_plugins', 'jetpack_add_wpcom_block_editor_tinymce_plugins' );
}
add_action( 'admin_init', 'jetpack_add_wpcom_block_editor_tinyme_filters' );
