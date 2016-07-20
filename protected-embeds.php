<?php

/**
 * Plugin Name: Protected Embeds
 * Description: A drop-in replacement for WordPress.com protected embeds
 * Version: 1.0
 * Author: Joe Hoyle, Human Made, Fusion
 */

namespace Protected_Embeds;

require_once __DIR__ . '/inc/class-embed.php';

add_action( 'admin_init', __NAMESPACE__ . '\\create_database_table' );
add_action( 'init', __NAMESPACE__ . '\\add_rewrite_rules' );
add_action( 'query_vars', __NAMESPACE__ . '\\add_public_query_vars' );
add_action( 'parse_request', __NAMESPACE__ . '\\display_protected_iframe' );
add_action( 'admin_init', __NAMESPACE__ . '\\load_shortcode_ui' );
add_filter( 'shortcode_ui_fields', __NAMESPACE__ . '\\shortcode_ui_fields' );
add_action( 'print_shortcode_ui_templates', __NAMESPACE__ . '\\shortcode_ui_hidden_field_template' );
add_shortcode( 'protected-iframe', __NAMESPACE__ . '\\protected_iframe_shortcode' );

function create_database_table() {
	global $wpdb;
	$wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}protected_embeds` (
			`embed_id` varchar(64) NOT NULL,
			`src` varchar(255) NOT NULL,
			`embed_group_id` varchar(64) NOT NULL,
			`html` mediumtext,
			UNIQUE KEY `embed_id` (`embed_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8"
	);
}

/**
 * Load the Shortcode UI based off Shortcake, if shortcake is available.
 */
function load_shortcode_ui() {

	if ( ! function_exists( 'shortcode_ui_register_for_shortcode' ) ) {
		return;
	}

	require_once __DIR__ . '/inc/shortcode-ui/namespace.php';
}

/**
 * The shortcode callback for the `protected-iframe` shortcode.
 *
 * @param  array $attrs The shortcode attributes
 * @return string
 */
function protected_iframe_shortcode( $attrs ) {

	if ( ! defined( 'PROTECTED_EMBEDS_DOMAIN' ) ) {
		return;
	}

	$attrs = wp_parse_args( $attrs, array(
		'id'     => null,
		'width'  => '',
		'height' => '',
		'scrolling' => '',
		'class' => 'wpcom-protected-iframe',
	) );

	$embed = Embed::get( $attrs['id'] );

	if ( ! $embed ) {
		return '<!-- Embed not found -->';
	}

	$target_origin = ( is_ssl() ? 'https://' : 'http://' ) . PROTECTED_EMBEDS_DOMAIN;

	ob_start();
	?>
	<iframe
		id="wpcom-iframe-<?php echo esc_attr( $attrs['id'] ) ?>"
		width="<?php echo esc_attr( $attrs['width'] ) ?>"
		height="<?php echo esc_attr( $attrs['height'] ) ?>"
		src="<?php echo esc_url( $target_origin . '/protected-iframe/' . $embed->get_id() ) ?>"
		scrolling="<?php echo esc_attr( $attrs['scrolling'] ) ?>"
		frameborder="0"
		class="<?php echo esc_attr( $attrs['class'] ) ?>"
		>
	</iframe>
	<script type="text/javascript">
		( function() {
			var func = function() {
				var iframe = document.getElementById('wpcom-iframe-<?php echo esc_attr( $attrs['id'] ) ?>'),
				    iframeResize = ( '' === iframe.height )
				if ( iframe ) {
					iframe.onload = function() {
						// Allow iframe's scrollHeight to be as small as possible
						if ( iframeResize ) {
							iframe.height = 1;
						}
						iframe.contentWindow.postMessage( {
							'msg_type': 'poll_size',
							'frame_id': 'wpcom-iframe-<?php echo esc_attr( $attrs['id'] ) ?>'
						}, <?php echo json_encode( $target_origin ); ?> );
					}
				}

				// debouncing function from John Hann
				// http://unscriptable.com/index.php/2009/03/20/debouncing-javascript-methods/
				var debounce = function (func, threshold, execAsap) {
					var timeout;

					return function debounced () {
						var obj = this, args = arguments;
						function delayed () {
							if (!execAsap)
								func.apply(obj, args);
							timeout = null;
						};

						if (timeout)
							clearTimeout(timeout);
						else if (execAsap)
							func.apply(obj, args);

						timeout = setTimeout(delayed, threshold || 100);
					};
				}

				// Autosize iframe
				var funcSizeResponse = function( e ) {

					var origin = document.createElement( 'a' );
					origin.href = e.origin;

					// Verify message origin
					if ( '<?php echo esc_js( PROTECTED_EMBEDS_DOMAIN ) ?>' !== origin.host )
						return;

					// Verify message is in a format we expect
					if ( 'object' !== typeof e.data || undefined === e.data.msg_type )
						return;

					switch ( e.data.msg_type ) {
						case 'poll_size:response':
							var iframe = document.getElementById( e.data._request.frame_id );

							if ( iframe && '' === iframe.width )
								iframe.width = '100%';
							if ( iframe && iframeResize )
								iframe.height = parseInt( e.data.height );

							return;
						default:
							return;
					}
				}

				var funcOnResize = debounce( iframe.onload, 300 );

				if ( 'function' === typeof window.addEventListener ) {
					window.addEventListener( 'message', funcSizeResponse, false );
					window.addEventListener( 'resize', funcOnResize, false );
				} else if ( 'function' === typeof window.attachEvent ) {
					window.attachEvent( 'onmessage', funcSizeResponse );
					window.attachEvent( 'resize', funcOnResize );
				}
			}
			if (document.readyState === 'complete') { func.apply(); /* compat for infinite scroll */ }
			else if ( document.addEventListener ) { document.addEventListener( 'DOMContentLoaded', func, false ); }
			else if ( document.attachEvent ) { document.attachEvent( 'onreadystatechange', func ); }
		} )();
		</script>
	<?php
	return ob_get_clean();
}

/**
 * Add a public query var for the protected-iframe.
 *
 * This is needed to be able to pass the id of the embed via the WP class with rewrite rules.
 *
 * @param array $vars public_query_vars
 * @return array
 */
function add_public_query_vars( $vars ) {
	$vars[] = 'protected-iframe';
	return $vars;
}

/**
 * Add the rewrite rules for the /protected-iframe endpoint.
 *
 * This is used to serve the protected embed html inside the iframde.
 */
function add_rewrite_rules() {
	add_rewrite_rule( '^protected-iframe/([^/]+)?', 'index.php?protected-iframe=$matches[1]', 'top' );
}

function display_protected_iframe( \WP $wp ) {
	$server = $_SERVER['HTTP_HOST'];

	// Prevent any output on the embeds domain other than protected iframes
	if ( PROTECTED_EMBEDS_DOMAIN === $server && empty( $wp->query_vars['protected-iframe'] ) ) {
		wp_die();
	}

	// Don't return protected iframes on any other domain than the specified embeds domain
	if ( PROTECTED_EMBEDS_DOMAIN !== $server || empty( $wp->query_vars['protected-iframe'] ) ) {
		return;
	}

	$embed = Embed::get( $wp->query_vars['protected-iframe'] );

	// Allow themes/plugins to add additional selectors to apply fitvids to
	$players = apply_filters( 'protected_iframe_players', array(
		'iframe',
		'embed',
		'object',
	) );

	?>
	<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<style type="text/css">
				span, body, embed { margin: 0; }
				iframe, object, embed { max-width: 100%; }
			</style>
		</head>
		<body>
			<?php echo $embed->get_html(); ?>
			<script type="text/javascript">
				<?php include 'js/fitvids.min.js'; ?>
				fitvids( { players: <?php echo wp_json_encode( $players ); ?> } );

				var funcSizeRequest = function( e ) {
					var ref = document.createElement( 'a' );
					ref.href = document.referrer;

					// Verify message origin
					if ( ref.protocol + '//' + ref.host !== e.origin )
						return;

					// Verify message is in a format we expect
					if ( 'object' !== typeof e.data || undefined === e.data.msg_type )
						return;

					switch ( e.data.msg_type ) {
						case 'poll_size':
							e.source.postMessage( {
								'_request' : e.data,
								'msg_type' : 'poll_size:response',
								'height'   : document.body.scrollHeight,
								'width'    : document.body.scrollWidth
							}, e.origin );
							return;
						default:
							return;
					}
				};

				if ( 'function' === typeof window.addEventListener ) {
					window.addEventListener( 'message', funcSizeRequest, false );
				} else if ( 'function' === typeof window.attachEvent ) {
					window.attachEvent( 'onmessage', funcSizeRequest );
				}
			</script>
		</body>
	</html>
	<?php
	exit;
}

function shortcode_ui_fields( $fields ) {
	$fields['hidden'] = array(
		'template' => 'protected-embed-shortcode-ui-field-hidden',
	);
	return $fields;
}

function shortcode_ui_hidden_field_template() {
	//@formatter:off
	?>
<script type="text/html" id="tmpl-protected-embed-shortcode-ui-field-hidden">
	<input type="hidden" class="regular-text" name="{{ data.attr }}" id="{{ data.id }}" value="{{ data.value }}" {{{ data.meta }}} />
</script>
	<?php
	//@formatter:on
}
