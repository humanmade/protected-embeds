<?php

namespace Protected_Embeds\Shortcode_UI;

add_action( 'register_shortcode_ui', __NAMESPACE__ . '\\register_shortcode_ui' );

/**
 * Register the UI via Shortcake.
 *
 * @return [type] [description]
 */
function register_shortcode_ui() {
	shortcode_ui_register_for_shortcode(
		'protected-iframe',
		array(
			'label'         => esc_html__( 'Protected Embed', 'protected-embeds' ),
			'listItemImage' => 'dashicons-media-code',
			'attrs'         => array(
				array(
					'label' => esc_html__( 'ID', 'protected-embeds' ),
					'type'  => 'hidden',
					'attr'  => 'id',
					'meta'  => array(
						'readonly' => 'readonly',
					),
				),
				array(
					'label' => esc_html__( 'Embed Code', 'protected-embeds' ),
					'type'  => 'textarea',
				),
				array(
					'label'       => esc_html__( 'Height', 'protected-embeds' ),
					'type'        => 'number',
					'attr'        => 'height',
					'description' => esc_html__( 'Suggested height to render the embed at. (Where possible, embeds will resize to fit their contents. But setting an initial height helps.)', 'protected-embeds' ),
				),
				array(
					'label'       => esc_html__( 'Width', 'protected-embeds' ),
					'type'        => 'text',
					'attr'        => 'width',
					'description' => esc_html__( 'Width, if other than 100%', 'protected-embeds' ),
				),
				array(
					'label'       => esc_html__( 'Class names', 'protected-embeds' ),
					'type'        => 'text',
					'attr'        => 'class',
					'description' => esc_html__( 'Class names to apply to the iframe containing the embed.', 'protected-embeds' ),
				),
				array(
					'label' => esc_html__( 'Scrolling?', 'protected-embeds' ),
					'type'  => 'checkbox',
					'attr'  => 'scrolling',
				),
			),
		)
	);
}

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\admin_enqueue_scripts' );

function admin_enqueue_scripts() {
	if ( 'post' !== get_current_screen()->base ) {
		return;
	}
	wp_enqueue_script( 'protected-embeds-shortcode-ui',
		plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'js/protected-embeds-shortcode-ui.js',
		array( 'media-views', 'shortcode-ui' )
	);
}

add_action( 'wp_ajax_protected-embeds-get', __NAMESPACE__ . '\\protected_embeds_get' );

function protected_embeds_get() {
	if ( empty( $_GET['id'] ) ) {
		die();
	}

	wp_send_json( \Protected_Embeds\Embed::get( sanitize_text_field( $_GET['id'] ) ) );
}

add_action( 'wp_ajax_protected-embeds-update', __NAMESPACE__ . '\\protected_embeds_update' );

function protected_embeds_update() {

	$html = stripslashes( $_POST['html'] );

	// Create and return a new embed if no ID yet
	if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) ) {
		wp_send_json( \Protected_Embeds\Embed::create( '', get_current_blog_id(), $html ) );
	}

	$id = sanitize_text_field( $_POST['id'] );

	wp_send_json( \Protected_Embeds\Embed::update( $id, $html ) );
}
