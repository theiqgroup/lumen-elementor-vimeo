<?php
namespace LumenIQ\Elementor;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Builds the Vimeo player URL shared by the embed and slider widgets.
 */
trait Vimeo_Src {

	protected static function build_src( $video_id, $hash, $params ) {
		$video_id = preg_replace( '/\D+/', '', (string) $video_id );

		if ( empty( $video_id ) ) return '';

		$base = 'https://player.vimeo.com/video/' . $video_id;

		$query = [];

		// Include privacy hash if provided
		if ( ! empty( $hash ) ) {
			$query[] = 'h=' . rawurlencode( trim( $hash ) );
		}

		// Append raw params exactly as provided (no rewriting)
		$params = trim( (string) $params );
		if ( $params !== '' ) {
			// Strip leading ? if user pasted it
			$params = ltrim( $params, '?' );
			$query[] = $params;
		}

		if ( empty( $query ) ) return $base;

		return $base . '?' . implode( '&', $query );
	}
}
