<?php
/**
 * Plain PHP test for the shared Vimeo src builder.
 *
 * Run: php tests/php/build-src-test.php
 */

namespace LumenIQ\Elementor\Tests;

define( 'ABSPATH', __DIR__ );

require_once dirname( __DIR__, 2 ) . '/includes/trait-vimeo-src.php';

class Src_Builder {
	use \LumenIQ\Elementor\Vimeo_Src;

	public static function src( $video_id, $hash, $params ) {
		return self::build_src( $video_id, $hash, $params );
	}
}

$cases = [
	[ [ '1139303378', '', 'background=1' ], 'https://player.vimeo.com/video/1139303378?background=1' ],
	[ [ 'abc123', 'a1b2', ' ?autoplay=1&muted=1 ' ], 'https://player.vimeo.com/video/123?h=a1b2&autoplay=1&muted=1' ],
	[ [ '42', '', '' ], 'https://player.vimeo.com/video/42' ],
	[ [ '', 'x', 'y' ], '' ],
];

$failed = 0;
foreach ( $cases as [ $args, $expected ] ) {
	$actual = Src_Builder::src( ...$args );
	if ( $actual !== $expected ) {
		fwrite( STDERR, 'FAIL build_src(' . implode( ', ', $args ) . "): expected '$expected', got '$actual'\n" );
		$failed++;
	}
}

if ( $failed ) {
	exit( 1 );
}
echo "OK\n";
