# Vimeo Video Slider – Design

## Intent

The Showtime site (e.g. `https://www.dowodev.uk/showtime/retro/`) opens small Elementor Pro popups from inline text triggers ("Trading cards", "prints", "filters" …). Each popup currently holds an Elementor Pro **Slides** widget showing a handful of images with dot navigation.

We want the same experience with **videos instead of images**: a carousel of Vimeo videos people watch, built on this plugin's existing embed code (`widgets/class-vimeo-embed.php`), which outputs the Vimeo iframe exactly as configured, without Elementor rewriting the parameters.

## Reference: the current popup slider

Inspected on the live page (popup ID 12884, opened by selector `.popup-trading-cards`). Widget `slides.default` with:

| Setting | Value |
|---|---|
| Slides | 4 |
| Slide size | 400 × 500px (portrait, 4:5), image `cover`, centred |
| Navigation | dots only |
| Dots | 14px circles, 6px horizontal margin; inactive `#000` at 20% opacity; active `#FF0F02` at 100% |
| Dot placement | below the slides (slider area 540px tall, slides 500px, dots ~5px from the bottom) |
| Transition | `slide`, 500ms |
| Infinite | yes |
| Autoplay | yes, 3500ms, pause on hover and on interaction – **this is what we are replacing** |
| Popup box | white, 6px radius, fade-in 0.3s |

## Goals

1. A new Elementor widget, **Vimeo Video Slider**, in this plugin, usable inside Elementor popups (primary use) and on normal pages.
2. Slides are managed through a repeater, as in the Slides widget. Each slide has the same options as the existing embed widget:
   - Vimeo Video ID
   - Privacy hash (optional)
   - Query parameters, raw, appended exactly as entered. Default `background=1`.
   - Iframe `allow` attribute. Default `autoplay; fullscreen; picture-in-picture`.
   - Disable clicks (pointer-events). Default on.
3. Aspect ratio is set once for the whole slider: the existing choices (16:9, 4:3, 16:10, 1:1), **plus 4:5 and 9:16**. Every slide uses the same box so the slider never changes height.
4. **Navigation: dots only.** By default they match the reference above. Style controls cover dot size, spacing, inactive colour and active colour, plus the existing Max Width control.
5. **Playback:**
   - Only the visible slide plays. When the slide changes, the one being left pauses, and the arriving video restarts from 0 and plays.
   - Muting, looping and controls come purely from each slide's query parameters. Most slides use `background=1`, so they are muted by default. The widget never adds or rewrites parameters in the `src`. The one exception is the Vimeo-documented API parameters needed for the player script to talk to the iframe, if the builder finds any are required. If so, document why in code.
6. **Advancing:** the slider moves to the next slide **when the current video finishes**, with a 500ms slide transition. After the last slide it wraps to the first. **There is no timer-based autoplay.** If a visitor picks a slide with the dots, advancing carries on from that slide when its video finishes (my call).
7. **Popup lifecycle:** when the popup opens, the first visible slide starts playing. When the popup closes, every video in it pauses. Reopening starts again from slide 1 at 0s (my call).
8. Swiping between slides on touch devices works where the iframe isn't capturing touches (i.e. when "Disable clicks" is on, which is the default).

## Detecting "video finished" (the key technical risk)

With `background=1` Vimeo loops the video, so the player's `ended` event does not fire. Approach, using the Vimeo Player SDK (`@vimeo/player`):

- **Plan A:** on `timeupdate`, when `duration - seconds <= ~0.3s`, advance. This happens just before the loop, which looks cleanest.
- **Plan B (fallback, also kept as a safety net):** if `seconds` jumps backwards to near 0 after having been past the halfway point, treat that as finished and advance.
- When the video does not loop (no `background=1` / `loop=1`), the normal `ended` event also triggers advance.
- Guard against advancing twice for the same play-through.
- **Unconfirmed:** whether `background=1&loop=0` makes Vimeo stop and fire `ended`. The builder may check, but the design must not depend on the editor typing `loop=0`.

**First build step:** before building the full widget, verify Plan A/B against a real `background=1` Vimeo video in a browser, and report what was observed (event cadence, how smooth the change is). If neither works acceptably, stop and bring it back to the user.

## Non-goals

- Thumbnails/poster images. A brief black frame while a video loads is acceptable (user decision).
- Arrows, captions, headings, buttons or links on slides.
- Timer-based autoplay.
- Changing the existing Vimeo Embed widget's behaviour or output. Sharing code with it through a refactor is fine, provided its rendered HTML stays byte-for-byte the same.
- Building or editing the popups on the site itself. Swapping the widget into the popups is done by the site editor afterwards.

## Anti-goals

- Two videos audible or playing at once.
- Videos continuing to play (and use bandwidth or play sound) after the popup is closed.
- Duplicate iframes per slide (e.g. from Swiper's classic `loop` mode cloning slides). This doubles video loads and breaks player tracking.
- Rewriting the user's query parameters. That is the whole reason this plugin exists.

## Constraints

- WordPress Coding Standards; WordPress APIs. Sanitise input and escape all output (see `CLAUDE.md`).
- Requires Elementor. Must not require Elementor Pro (the popup does, but the widget itself shouldn't).
- Version: use the plugin version constant (`LUMEN_EVW_PLUGIN_VERSION`) for asset versions. Bump the header `Version:` to **1.1.0** (my call: new feature) and add a CHANGELOG entry in the existing style.
- **Release flow (user decision):** any `Version:` change pushed to `main` triggers `.github/workflows/release.yml`, and the GitHub updater then ships that release to every site running the plugin. So work happens on a feature branch and is tested on dowodev. The 1.1.0 version bump is the **last** step, done only when the user says to ship. `docs/superpowers` is already excluded from the release zip by `release.yml`.
- The working tree has an unrelated uncommitted change (Plugin URI header line). Leave it alone and don't include it in feature commits.

## Decided by me (please check)

- **Slider engine:** Elementor's bundled Swiper (via `elementorFrontend.utils.swiper`), not a separate library, so it behaves like the native Slides widget.
- **Vimeo Player SDK:** shipped inside the plugin (e.g. `assets/js/vendor/`), not from a CDN, pinned to a specific version. It is registered and only enqueued by this widget through `get_script_depends()`.
- **Wrap-around:** handled without cloning iframes. Preferred: the slider keeps sliding forward from last to first, as the reference does. Acceptable fallback if that isn't practical without clones: Swiper `rewind`, which slides back to the first.
- Dropped the "fixed height" idea mentioned in conversation. 4:5 matches the reference box, so aspect ratio alone is enough.
- 4:5 and 9:16 are added to the slider only, not the existing embed widget.
- Iframes in the slider should not use `loading="lazy"`, since slides start off-screen or inside a hidden popup. The builder can choose a different loading strategy if it is justified.

## Left to the builder

- File and class layout (e.g. a shared trait or helper for `build_src`), the front-end handler structure and the CSS approach.
- How the handler hooks popup show and hide (Elementor Pro's `elementor/popup/show` / `elementor/popup/hide` events or equivalent), and initialisation in the Elementor editor preview.
- Behaviour with a single slide (no dots; the video just loops).
- Unmuted slides: browsers may block autoplay with sound. Best effort, with no workaround UI needed.

## Verification

The repo has no automated test setup. Verification is manual on the dowodev test site (user decision):

- **Install target:** SSH host alias `dowodev`, WordPress at `public_html/showtime/`, plugin directory `wp-content/plugins/lumen-elementor-vimeo/` (WP-CLI available). Development builds are copied there for testing.
- **Site stack (checked 17 Oct 2026):** Elementor 4.3.5, Elementor Pro 4.3.2, this plugin 1.0.3 active. Auto-updates are off for this plugin, so a test copy won't be overwritten.
- **Browser checks:** done in the user's Chrome against `https://www.dowodev.uk/showtime/` pages.
- **Test videos (user supplied):** Vimeo IDs `1139303378` and `1236789302`, no privacy hash. Use them for the "video finished" detection check and in the test slider (repeat them to make 3–4 slides if needed).

Checks:

- The slider in a popup matches the reference size and dots.
- Each video advances to the next when it finishes, with no timer, and wraps after the last.
- Only one video plays at a time, and closing the popup stops all playback.
- The existing Vimeo Embed widget's output is unchanged.
