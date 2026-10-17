/**
 * Decides when a Vimeo video has finished playing.
 *
 * With `background=1` Vimeo loops the video and never fires `ended`, so the
 * end is detected from `timeupdate` instead: first when playback gets within
 * `endWindow` seconds of the duration, and as a fallback when the position
 * jumps back to the start from past halfway (the loop restarting).
 */
( function ( root, factory ) {
	if ( typeof module === 'object' && module.exports ) {
		module.exports = factory();
	} else {
		root.LumenEVWFinish = factory();
	}
}( typeof self !== 'undefined' ? self : this, function () {
	'use strict';

	function create( options ) {
		const endWindow = options && options.endWindow !== undefined ? options.endWindow : 0.3;
		const onFinish = options && options.onFinish ? options.onFinish : function () {};

		let fired = false;
		let lastSeconds = null;

		function fire() {
			fired = true;
			onFinish();
		}

		return {
			timeupdate( data ) {
				const seconds = data.seconds;
				const duration = data.duration;

				if ( ! ( duration > 0 ) ) {
					lastSeconds = seconds;
					return;
				}

				// A backwards jump from past halfway is the loop restarting: it
				// ends this play-through (if the end window was missed) and
				// starts the next one.
				if ( lastSeconds !== null && seconds < lastSeconds && lastSeconds > duration / 2 ) {
					if ( ! fired ) {
						fire();
					}
					fired = false;
				} else if ( ! fired && duration - seconds <= endWindow ) {
					fire();
				}

				lastSeconds = seconds;
			},

			ended() {
				if ( ! fired ) {
					fire();
				}
			},

			reset() {
				fired = false;
				lastSeconds = null;
			},
		};
	}

	return { create };
} ) );
