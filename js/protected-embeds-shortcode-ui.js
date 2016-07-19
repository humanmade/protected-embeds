jQuery(document).ready( function($) {

	/**
	 * Attach JS hooks for shortcodes that need them.
	 *
	 */
	if ( typeof wp !== 'undefined' &&
		typeof wp.shortcake !== 'undefined' &&
		typeof wp.shortcake.hooks !== 'undefined' ) {

		wp.shortcake.hooks.addAction( 'protected-iframe.id', protectedEmbedsUI.getHtml );
	}

});

var protectedEmbedsUI = {

	embedCode: '',

	/**
	 * Triggered when shortcode UI for protected iframe is first rendered.
	 *
	 * Sets the "id" field as "readonly", and loads the embed HTML to populate
	 * the textarea, for potential editing.
	 */
	getHtml: function( field, collection ) {
		var id = field.value,
			embedCodeInput = collection[1];

		if ( 'undefined' === typeof id || ! id ) {
			wp.media.frame.views.view.setState('shortcake-bakery-embed')
		}

		jQuery.get( ajaxurl, { action: 'protected-embeds-get', id: field.value },
			function( response ) {
				protectedEmbedsUI.embedCode = response.html;
				var textArea = embedCodeInput.$el.find( 'textarea' );
				textArea.val( response.html );
				textArea.off('blur').on('blur', protectedEmbedsUI.updateHtml.bind( this, field.value, textArea, collection ) );
			}
		);
	},

	/**
	 * Triggered on blur to the embed HTML textarea.
	 *
	 * Pops a confirmation box, asking whether the user intended to update the
	 * embed code. If so, posts to the ajax endpoint to make that update.
	 */
	updateHtml: function( embedId, textArea, collection ) {
		newEmbedCode = textArea.val();

		if ( newEmbedCode !== protectedEmbedsUI.embedCode && confirm( 'Do you want to update the embed code?' ) ) {
			jQuery.post( ajaxurl, { action: 'protected-embeds-update', id: embedId, html: textArea.val() },
				function( response ) {
					if ( ! embedId ) {
						sui.views.editAttributeField.getField( collection, 'id' ).setValue( response.id );
					}
					protectedEmbedsUI.embedCode = response.html;
				}
			);
		} else {
			textArea.val( protectedEmbedsUI.embedCode );
		}
	}
}
