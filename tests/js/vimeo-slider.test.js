const test = require( 'node:test' );
const assert = require( 'node:assert/strict' );
const { restartPlayer } = require( '../../assets/js/vimeo-slider.js' );

// Vimeo's setCurrentTime(0) never settles when the video is already at 0s.
function fakePlayer() {
	const calls = [];
	return {
		calls,
		setCurrentTime( seconds ) {
			calls.push( 'seek ' + seconds );
			return new Promise( () => {} );
		},
		play() {
			calls.push( 'play' );
			return Promise.resolve();
		},
		setMuted( muted ) {
			calls.push( 'muted ' + muted );
			return Promise.resolve( muted );
		},
	};
}

test( 'restartPlayer plays without waiting for the seek to settle', () => {
	const player = fakePlayer();
	restartPlayer( player );
	assert.deepEqual( player.calls, [ 'seek 0', 'play' ] );
} );

test( 'restartPlayer swallows rejected play (blocked autoplay)', async () => {
	const player = fakePlayer();
	player.play = () => Promise.reject( new Error( 'NotAllowedError' ) );
	restartPlayer( player );
	await new Promise( ( resolve ) => setImmediate( resolve ) );
} );

test( 'restartPlayer applies the mute state before playing', () => {
	const player = fakePlayer();
	restartPlayer( player, true );
	assert.deepEqual( player.calls, [ 'muted true', 'seek 0', 'play' ] );
} );

test( 'restartPlayer leaves sound alone without a mute button', () => {
	const player = fakePlayer();
	restartPlayer( player, null );
	assert.deepEqual( player.calls, [ 'seek 0', 'play' ] );
} );

test( 'restartPlayer reports blocked unmuted autoplay', async () => {
	const player = fakePlayer();
	player.play = () => Promise.reject( Object.assign( new Error( 'blocked' ), { name: 'NotAllowedError' } ) );
	let blocked = 0;
	restartPlayer( player, false, () => blocked++ );
	await new Promise( ( resolve ) => setImmediate( resolve ) );
	assert.equal( blocked, 1 );
} );

test( 'restartPlayer does not report other play failures', async () => {
	const player = fakePlayer();
	player.play = () => Promise.reject( Object.assign( new Error( 'gone' ), { name: 'PasswordError' } ) );
	let blocked = 0;
	restartPlayer( player, false, () => blocked++ );
	await new Promise( ( resolve ) => setImmediate( resolve ) );
	assert.equal( blocked, 0 );
} );
