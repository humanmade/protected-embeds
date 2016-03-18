<?php

namespace Protected_Embeds;

class Embed {

	/**
	 * Get an Embed from the database based off id.
	 *
	 * @param  string $id
	 * @return null|Embed
	 */
	public static function get( $id ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT embed_id, src, embed_group_id, html FROM protected_embeds WHERE embed_id = %s", $id )
		);
		if ( ! $row ) {
			return null;
		}

		return new static( $row->embed_id, $row->src, $row->embed_group_id, $row->html );
	}

	/**
	 * Create a new Embed from a html embed fragment.
	 *
	 * @param  string $html
	 * @return Embed
	 */
	public static function create( $src = '', $embed_group_id = '', $html = '' ) {
		global $wpdb;
		$id = md5( $html . rand( 0, 10000 ) . time() );
		$insert = $wpdb->insert( 'protected_embeds', array( 'embed_id' => $id, 'src' => $src, 'embed_group_id' => $embed_group_id, 'html' => $html ) );
		return static::get( $id );
	}

	/**
	 * Update the embed code on an Embed
	 *
	 * @param  int $id
	 * @param  string $html
	 * @return Embed
	 */
	public static function update( $id, $html = '' ) {
		global $wpdb;
		$update = $wpdb->update( 'protected_embeds', array( 'html' => $html ), array( 'embed_id' => $id ) );
		return static::get( $id );
	}

	public function __construct( $id, $src, $embed_group_id, $html ) {
		$this->id = $id;
		$this->src = $src;
		$this->embed_group_id = $embed_group_id;
		$this->html = $html;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_src() {
		return $this->src;
	}

	public function get_html() {
		return $this->html;
	}
}
