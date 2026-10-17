<?php
/**
 * Plain PHP test for Vimeo_Slider_Widget::prepare_slides().
 *
 * Run: php tests/php/prepare-slides-test.php
 */

namespace Elementor {
	abstract class Widget_Base {}
	class Controls_Manager {}
	class Repeater {}
}

namespace LumenIQ\Elementor\Tests {

	use LumenIQ\Elementor\Vimeo_Slider_Widget;

	define( 'ABSPATH', __DIR__ );

	require_once dirname( __DIR__, 2 ) . '/includes/trait-vimeo-src.php';
	require_once dirname( __DIR__, 2 ) . '/widgets/class-vimeo-slider.php';

	$failed = 0;
	$check  = function ( $name, $actual, $expected ) use ( &$failed ) {
		if ( $actual !== $expected ) {
			fwrite( STDERR, "FAIL $name: expected " . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . "\n" );
			$failed++;
		}
	};

	$default_allow = 'autoplay; fullscreen; picture-in-picture';

	$items = Vimeo_Slider_Widget::prepare_slides( [
		[ 'video_id' => '1139303378', 'raw_params' => 'background=1' ],
		[ 'video_id' => '1236789302', 'disable_pointer_events' => 'yes', 'allow' => 'autoplay' ],
	] );
	$check( 'valid slides kept', count( $items ), 2 );
	$check( 'src built', $items[0]['src'], 'https://player.vimeo.com/video/1139303378?background=1' );
	$check( 'default allow', $items[0]['allow'], $default_allow );
	$check( 'pointer default off', $items[0]['disable_pointer'], false );
	$check( 'pointer yes', $items[1]['disable_pointer'], true );
	$check( 'custom allow', $items[1]['allow'], 'autoplay' );

	$items = Vimeo_Slider_Widget::prepare_slides( [
		[ 'video_id' => '' ],
		[ 'video_id' => 'abc' ],
		[ 'video_id' => '1236789302' ],
	] );
	$check( 'invalid ids skipped', count( $items ), 1 );
	$check( 'remaining slide', $items[0]['src'] ?? null, 'https://player.vimeo.com/video/1236789302' );

	$check( 'no slides', Vimeo_Slider_Widget::prepare_slides( [] ), [] );

	if ( $failed ) {
		exit( 1 );
	}
	echo "OK\n";
}
