/**
 * Vimeo Video Slider front-end handler.
 *
 * Plays only the visible slide's video, moves to the next slide when that
 * video finishes (see finish-detector.js), and pauses everything when the
 * Elementor popup containing the slider is closed.
 */
( function ( $ ) {
	'use strict';

	const SPEED = 500;
	let instanceCount = 0;

	function noop() {}

	function quietly( promise ) {
		// Vimeo SDK calls reject when e.g. unmuted autoplay is blocked; the
		// slider should carry on regardless.
		if ( promise && promise.catch ) {
			promise.catch( noop );
		}
		return promise;
	}

	// Seek and play are sent together: Vimeo's setCurrentTime(0) never
	// resolves when the video is already at 0s, so chaining play() after it
	// would leave the slide paused. `muted` is only a boolean when the slider
	// has a mute button; otherwise the slide's own query parameters decide.
	// `onBlocked` is called when the browser refuses to autoplay with sound.
	function restartPlayer( player, muted, onBlocked ) {
		if ( typeof muted === 'boolean' ) {
			quietly( player.setMuted( muted ) );
		}
		quietly( player.setCurrentTime( 0 ) );

		const playing = player.play();
		if ( playing && playing.catch ) {
			playing.catch( ( error ) => {
				if ( onBlocked && error && error.name === 'NotAllowedError' ) onBlocked();
			} );
		}
	}

	async function init( rootEl ) {
		if ( ! rootEl || rootEl.dataset.lumeniqReady ) return;
		rootEl.dataset.lumeniqReady = '1';

		const swiperEl = rootEl.querySelector( '.lumeniq-vimeo-slider__swiper' );
		const dotsEl   = rootEl.querySelector( '.lumeniq-vimeo-slider__dots' );
		const muteBtn  = rootEl.querySelector( '[data-lumeniq-mute]' );
		const iframes  = Array.prototype.slice.call( swiperEl.querySelectorAll( '.swiper-slide iframe' ) );

		if ( ! iframes.length || typeof Vimeo === 'undefined' || typeof LumenEVWFinish === 'undefined' ) return;

		const multiple  = iframes.length > 1;
		let active      = 0;
		let swiper      = null;

		const players = iframes.map( ( iframe ) => new Vimeo.Player( iframe ) );

		// With a mute button the visitor's choice carries across slides. Videos
		// start muted unless "Start muted" is off; if the browser then blocks
		// autoplay with sound, fall back to muted so the video still plays.
		let muted = muteBtn ? muteBtn.dataset.startMuted !== '0' : null;

		function playMutedInstead() {
			muted = true;
			updateMuteButton();
			quietly( players[ active ].setMuted( true ) );
			quietly( players[ active ].play() );
		}

		if ( muteBtn ) {
			updateMuteButton();
			players.forEach( ( player ) => quietly( player.setMuted( muted ) ) );

			muteBtn.addEventListener( 'click', () => {
				muted = ! muted;
				updateMuteButton();
				quietly( players[ active ].setMuted( muted ) );
				quietly( players[ active ].play() );
			} );
		}

		function updateMuteButton() {
			muteBtn.classList.toggle( 'is-muted', muted );
			muteBtn.setAttribute( 'aria-label', muted ? 'Unmute video' : 'Mute video' );
		}

		const detectors = players.map( () => LumenEVWFinish.create( {
			onFinish() {
				if ( multiple && swiper ) {
					swiper.slideNext( SPEED );
				}
			},
		} ) );

		// Only the active slide may advance the slider: events from a video
		// that was just left (or is still loading) are ignored.
		players.forEach( ( player, index ) => {
			player.on( 'timeupdate', ( data ) => {
				if ( index === active ) detectors[ index ].timeupdate( data );
			} );
			player.on( 'ended', () => {
				if ( index === active ) detectors[ index ].ended();
			} );
		} );

		function pauseAll() {
			players.forEach( ( player ) => quietly( player.pause() ) );
		}

		function activate( index ) {
			active = index;

			players.forEach( ( player, i ) => {
				if ( i !== index ) quietly( player.pause() );
			} );

			detectors[ index ].reset();

			restartPlayer( players[ index ], muted, muteBtn ? playMutedInstead : null );
		}

		if ( multiple ) {
			const SwiperClass = elementorFrontend.utils.swiper;
			const options     = {
				slidesPerView: 1,
				speed: SPEED,
				rewind: true,
				autoplay: false,
				allowTouchMove: true,
			};

			if ( dotsEl ) {
				options.pagination = { el: dotsEl, type: 'bullets', clickable: true };
			}

			swiper = await new SwiperClass( swiperEl, options );
			swiper.on( 'slideChange', () => activate( swiper.realIndex ) );
		}

		activate( 0 );

		// Elementor Pro popups: restart from the first slide when shown, and
		// stop every video when hidden so nothing plays out of sight.
		const popupEl = rootEl.closest( '[data-elementor-type="popup"]' );
		if ( ! popupEl ) return;

		const popupId = String( popupEl.dataset.elementorId );
		const ns      = '.lumeniqVimeoSlider' + ( ++instanceCount );

		function isMine( id ) {
			if ( ! rootEl.isConnected ) {
				// Re-rendered (e.g. in the editor): drop this instance's listeners.
				$( window ).off( ns );
				return false;
			}
			return String( id ) === popupId;
		}

		$( window ).on( 'elementor/popup/show' + ns, ( event, id ) => {
			if ( ! isMine( id ) ) return;
			if ( swiper ) {
				swiper.update();
				swiper.slideTo( 0, 0, false );
			}
			activate( 0 );
		} );

		$( window ).on( 'elementor/popup/hide' + ns, ( event, id ) => {
			if ( ! isMine( id ) ) return;
			pauseAll();
		} );
	}

	if ( typeof module === 'object' && module.exports ) {
		module.exports = { restartPlayer };
	}

	if ( ! $ ) return;

	$( window ).on( 'elementor/frontend/init', () => {
		elementorFrontend.hooks.addAction( 'frontend/element_ready/lumeniq_vimeo_slider.default', ( $scope ) => {
			init( $scope[ 0 ].querySelector( '[data-lumeniq-vimeo-slider]' ) );
		} );
	} );
}( typeof jQuery !== 'undefined' ? jQuery : null ) );
