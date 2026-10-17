const test = require( 'node:test' );
const assert = require( 'node:assert/strict' );
const { create } = require( '../../assets/js/finish-detector.js' );

function setup() {
	const calls = { count: 0 };
	const detector = create( { onFinish: () => calls.count++ } );
	const at = ( seconds, duration = 10 ) => detector.timeupdate( { seconds, duration } );
	return { calls, detector, at };
}

test( 'fires once inside the end window', () => {
	const { calls, at } = setup();
	at( 9.75 );
	assert.equal( calls.count, 1 );
	at( 9.9 );
	assert.equal( calls.count, 1 );
} );

test( 'does not fire mid-video', () => {
	const { calls, at } = setup();
	[ 0, 2.5, 5, 9.6 ].forEach( ( s ) => at( s ) );
	assert.equal( calls.count, 0 );
} );

test( 'falls back to backward wrap', () => {
	const { calls, at } = setup();
	at( 9.5 );
	at( 0.1 );
	assert.equal( calls.count, 1 );
} );

test( 'ignores backward seek before halfway', () => {
	const { calls, at } = setup();
	at( 5 );
	at( 1 );
	assert.equal( calls.count, 0 );
} );

test( 'ended fires once', () => {
	const a = setup();
	a.detector.ended();
	assert.equal( a.calls.count, 1 );
	a.detector.ended();
	assert.equal( a.calls.count, 1 );

	const b = setup();
	b.at( 9.8 );
	b.detector.ended();
	assert.equal( b.calls.count, 1 );
} );

test( 'reset re-arms', () => {
	const { calls, detector, at } = setup();
	at( 9.8 );
	detector.reset();
	at( 9.8 );
	assert.equal( calls.count, 2 );
} );

test( 'loop wrap re-arms for next play-through', () => {
	const { calls, at } = setup();
	at( 9.8 );
	at( 0.1 );
	at( 9.8 );
	assert.equal( calls.count, 2 );
} );

test( 'unknown duration never fires', () => {
	const { calls, at } = setup();
	at( 0, 0 );
	assert.equal( calls.count, 0 );
} );
