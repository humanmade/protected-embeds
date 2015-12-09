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
			'label' => 'Protected Embed'
		)
	);
}