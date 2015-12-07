<?php

namespace Protected_Embeds;

class Embed {

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

	public static function create( $src, $embed_group_id, $html ) {

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
