# Vimeo Video Slider Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an Elementor "Vimeo Video Slider" widget: a dots-only carousel of Vimeo embeds (built with the plugin's existing raw-parameter embed code) that plays only the visible video and advances when that video finishes.

**Architecture:** A PHP widget renders Swiper markup with one Vimeo iframe per slide. The `src` is built by the same code as the existing embed widget, which moves into a shared trait. A front-end handler, attached through Elementor's `frontend/element_ready` hook, starts Elementor's bundled Swiper 8 and attaches the Vimeo Player SDK to each iframe. A small, pure "finish detector" module, unit-tested under Node, decides when a looping (`background=1`) video has finished.

**Tech Stack:** PHP (WordPress Coding Standards), Elementor 4.3.5 / Elementor Pro 4.3.2 (on dowodev), Swiper 8.4.5 (Elementor script handle `swiper`, style handle `e-swiper`), `@vimeo/player` 2.30.4 (vendored), plain browser JS + jQuery (no build step), `node --test` (Node 26) for the JS unit tests, and plain `php` CLI scripts for the PHP unit tests.

**Spec:** `docs/superpowers/specs/2026-10-17-vimeo-video-slider-design.md`

## Global Constraints

- Follow WordPress Coding Standards and use WordPress APIs. Sanitise input and escape all output (`CLAUDE.md`).
- The widget requires Elementor only, not Elementor Pro. Popup support is detected at runtime.
- Never add to or rewrite the user's query parameters in the iframe `src`. The Vimeo SDK attaches via postMessage, so no extra parameters are needed.
- No duplicate iframes. Swiper 8's `loop` clones slides, so use `rewind: true` for wrapping from the last slide to the first. The spec allows this fallback.
- Asset versions use `LUMEN_EVW_PLUGIN_VERSION`. The vendored SDK uses its own version, `2.30.4`.
- The existing Vimeo Embed widget's rendered HTML must stay byte-identical.
- Work happens on branch `feature/vimeo-video-slider`. **Never push a `Version:` change to `main`.** The 1.1.0 bump is Task 6 and happens only when the user says ship.
- Commit only the paths each task names (`git commit -- <paths>`). The index already has the user's staged `CLAUDE.md` / `AGENTS.md` / `.github/copilot-instructions.md` move, and other uncommitted changes (`lumen-elementor-vimeo.php` Plugin URI, `release.yml`, `GitHubUpdater.php`) belong to the user. Don't sweep them into feature commits.
- Copy (UK English, en dashes): widget title `Vimeo Video Slider`. Empty-state message `Please provide a Vimeo Video ID.`, the same as the existing widget.
- Test videos: Vimeo `1139303378` and `1236789302`, no privacy hash.
- Test site: SSH alias `dowodev`, WordPress root `public_html/showtime/`, plugin directory `wp-content/plugins/lumen-elementor-vimeo/`.

## Review Focus

1. **Popup closed mid-video:** every video in it pauses and nothing keeps playing hidden. Reopening starts at slide 1, 0s. → Task 5, check 3.
2. **Visitor clicks a dot mid-video:** the old video pauses, the new one starts from 0, and the old one's `timeupdate`s can't trigger an advance. → Task 4, `ignores events from inactive slides` test plus Task 5, check 4.
3. **A slide with an empty or non-numeric video ID:** that slide is skipped. If no slides are valid, the empty-state message shows instead of an empty slider. → Task 3, `prepare_slides` tests.
4. **One valid slide:** no dots and no advance attempts. The video simply loops. → Task 3 test (`show_dots` false) plus Task 5, check 6.
5. **Elementor editor re-renders the widget repeatedly:** each render initialises once, with no duplicate players or Swiper instances piling up. → Task 4, init guard plus Task 5, check 7.

---

### Task 0: Branch and commit the spec

- [ ] **Step 1:** `git switch -c feature/vimeo-video-slider`
- [ ] **Step 2:** `git commit -m "docs: Vimeo video slider design and plan" -- docs/superpowers/specs/2026-10-17-vimeo-video-slider-design.md docs/superpowers/plans/2026-10-17-vimeo-video-slider.md`. Expected: a commit containing exactly those 2 files (check with `git show --stat HEAD`).

---

### Task 1: Finish detector, plus a go/no-go spike on real videos

**Files:**
- Create: `assets/js/finish-detector.js`
- Test: `tests/js/finish-detector.test.js`
- Modify: `.githubupdater.conf` (set `EXCLUDE_EXTRA="tests"` so tests don't ship)
- Throwaway (scratchpad, not committed): `spike.html`

**Interfaces:**
- Produces: a global `window.LumenEVWFinish` (and `module.exports` under Node), with `create( { endWindow = 0.3, onFinish } ) -> { timeupdate( { seconds, duration } ), ended(), reset() }`.
  - Calls `onFinish()` **at most once per play-through**.
  - **Plan A:** fires when `duration > 0 && duration - seconds <= endWindow`.
  - **Plan B:** fires when `seconds < lastSeconds && lastSeconds > duration / 2`, i.e. a backwards jump from past halfway.
  - A backwards jump from past halfway also re-arms after a fire. That is the loop restarting, which keeps the single-slide case and repeated loops correct.
  - `ended()` fires unless already fired. `reset()` clears both the fired flag and `lastSeconds`.
  - Wrap in a UMD-style IIFE so the same file runs in the browser and under `require`.

- [ ] **Step 1: Write failing tests** in `tests/js/finish-detector.test.js` using `node:test` + `node:assert/strict`, with `duration: 10` unless stated:
  - `fires once inside the end window`: `timeupdate({seconds:9.75})` → 1 call. A further `9.9` → still 1.
  - `does not fire mid-video`: `0, 2.5, 5, 9.6` → 0 calls.
  - `falls back to backward wrap`: `9.5` then `0.1` → 1 call.
  - `ignores backward seek before halfway`: `5` then `1` → 0 calls.
  - `ended fires once`: `ended()` → 1. `ended()` again → 1. And `timeupdate 9.8` then `ended()` → 1.
  - `reset re-arms`: fire via `9.8`, `reset()`, `9.8` → 2 calls.
  - `loop wrap re-arms for next play-through`: `9.8`, `0.1`, `9.8` → 2 calls.
  - `unknown duration never fires`: `{seconds: 0, duration: 0}` → 0.
- [ ] **Step 2:** `node --test tests/js/` → FAIL (module not found).
- [ ] **Step 3:** Implement `assets/js/finish-detector.js` to the interface above.
- [ ] **Step 4:** `node --test tests/js/` → all 8 pass.
- [ ] **Step 5: Spike (go/no-go).**
  - Write a scratchpad `spike.html` that embeds both test videos with `background=1` and the SDK from `https://unpkg.com/@vimeo/player@2.30.4/dist/player.min.js`.
  - Feed `timeupdate`/`ended` into `LumenEVWFinish.create`, and `console.log` every event plus each `onFinish`.
  - Serve it with `python3 -m http.server` from the scratchpad, open it in the user's Chrome (claude-in-chrome), let each video run through 2 loops, and read the console.
  - **Record:** the `timeupdate` cadence, whether `ended` ever fires with `background=1`, and whether Plan A fires before the visible loop.
  - Optional: try `background=1&loop=0` once and note whether `ended` fires.
  - **If neither plan fires reliably, STOP and report to the user before Task 2.** If Vimeo refuses to embed on localhost (domain privacy), report that and ask the user how to proceed.
- [ ] **Step 6: Commit** `git commit -m "feat: finish detector for looping Vimeo videos" -- assets/js/finish-detector.js tests/js/finish-detector.test.js .githubupdater.conf`, and report the spike observations in the hand-back.

---

### Task 2: Shared embed `src` trait (no behaviour change)

**Files:**
- Create: `includes/trait-vimeo-src.php`
- Modify: `widgets/class-vimeo-embed.php` (remove `build_src`, `use` the trait)
- Modify: `includes/class-main.php` (`register_widgets`: `require_once` the trait before the widgets)
- Test: `tests/php/build-src-test.php`

**Interfaces:**
- Produces: `trait LumenIQ\Elementor\Vimeo_Src { protected function build_src( $video_id, $hash, $params ) }`, with its body moved verbatim from `class-vimeo-embed.php:156-181`.

- [ ] **Step 1: Write the test.**
  - It's a plain PHP script: `define( 'ABSPATH', __DIR__ )`, require the trait, and define a tiny class that `use`s it and exposes `build_src` publicly.
  - On mismatch, `fwrite( STDERR, … )` and `exit( 1 )`. Print `OK` when all pass.
  - Cases:
    - `('1139303378', '', 'background=1')` → `https://player.vimeo.com/video/1139303378?background=1`
    - `('abc123', 'a1b2', ' ?autoplay=1&muted=1 ')` → `https://player.vimeo.com/video/123?h=a1b2&autoplay=1&muted=1`
    - `('42', '', '')` → `https://player.vimeo.com/video/42`
    - `('', 'x', 'y')` → `''`
- [ ] **Step 2:** `php tests/php/build-src-test.php` → fails (trait file missing).
- [ ] **Step 3:** Create the trait, switch the embed widget to `use Vimeo_Src;`, and require the trait in `register_widgets`.
- [ ] **Step 4:** `php tests/php/build-src-test.php` → `OK`. Also run `php -l` on all 3 PHP files → no errors.
- [ ] **Step 5: Commit** `git commit -m "refactor: share Vimeo src builder via trait" -- includes/trait-vimeo-src.php includes/class-main.php widgets/class-vimeo-embed.php tests/php/build-src-test.php`

---

### Task 3: Slider widget – controls, markup, styles, asset registration

**Files:**
- Create: `widgets/class-vimeo-slider.php`, `assets/css/vimeo-slider.css`
- Create: `assets/js/vendor/vimeo-player.min.js` (downloaded from `https://unpkg.com/@vimeo/player@2.30.4/dist/player.min.js`, with the licence header kept)
- Create: `assets/js/vimeo-slider.js` (a stub with a header comment only; Task 4 fills it in)
- Modify: `includes/class-main.php`
- Test: `tests/php/prepare-slides-test.php`

**Interfaces:**
- Consumes: the `Vimeo_Src` trait (Task 2).
- Produces:
  - **Widget class:** `LumenIQ\Elementor\Vimeo_Slider_Widget`
    - name `lumeniq_vimeo_slider`, title `Vimeo Video Slider`, icon `eicon-slider-video`, category `basic`
    - keywords `vimeo, video, slider, carousel, slides`
  - **`public static function prepare_slides( array $slides ): array`**
    - Returns a list of `[ 'src' => string, 'allow' => string, 'disable_pointer' => bool ]`, built with `build_src`.
    - Slides whose `src` is `''` are skipped. An empty or missing `allow` falls back to `autoplay; fullscreen; picture-in-picture`.
    - Make it static by instantiating a private helper that uses the trait, or make `build_src` static in the trait, whichever keeps Task 2's test intact.
  - **Rendered markup.** Handler-facing hooks are the `data-` attributes and `.lumeniq-vimeo-slider__dots`:
    ```html
    <div class="lumeniq-vimeo-slider" data-lumeniq-vimeo-slider>
      <div class="swiper lumeniq-vimeo-slider__swiper"><div class="swiper-wrapper">
        <div class="swiper-slide"><div class="lumeniq-vimeo-inner" style="position:relative;width:100%;overflow:hidden;aspect-ratio:100 / {aspect};">
          <iframe src="…" style="display:block;width:100%;height:100%;border:0;[pointer-events:none;]" frameborder="0" allow="…" allowfullscreen></iframe>
        </div></div> …
      </div></div>
      <div class="swiper-pagination lumeniq-vimeo-slider__dots"></div>   <!-- omitted when only 1 slide -->
    </div>
    ```
    - No `loading="lazy"` on the iframes.
    - With 0 valid slides, output `<div>Please provide a Vimeo Video ID.</div>` instead.
  - **Script handles**, registered on `elementor/frontend/after_register_scripts`:
    - `lumen-evw-vimeo-player`: vendor file, version `2.30.4`
    - `lumen-evw-finish-detector`
    - `lumen-evw-vimeo-slider`: deps `jquery`, `elementor-frontend`, `swiper`, `lumen-evw-vimeo-player`, `lumen-evw-finish-detector`
    - All in the footer.
  - **Style handle** `lumen-evw-vimeo-slider`, registered on `elementor/frontend/after_register_styles`.
  - **Widget dependencies:**
    - `get_script_depends()` → `[ 'swiper', 'lumen-evw-vimeo-player', 'lumen-evw-finish-detector', 'lumen-evw-vimeo-slider' ]`
    - `get_style_depends()` → `[ 'e-swiper', 'lumen-evw-vimeo-slider' ]`

**Controls:**
- **Content › Slides:** a `REPEATER` named `slides`, with `title_field` `{{{ video_id }}}`. Its fields are identical to the embed widget's `video_id`, `privacy_hash`, `raw_params` (default `background=1`), `allow` and `disable_pointer_events` (default `yes`). Default: 2 empty items.
- **Content › Slider:**
  - `aspect_ratio` uses the embed widget's options plus `'125' => '4:5'` and `'177.78' => '9:16'`. Default `125` (my call: it matches the trading cards box).
  - The hidden `force_full_width` control, copied from the embed widget along with its comment.
- **Style:**
  - Copy the embed widget's `max_width` responsive control, targeting `.lumeniq-vimeo-slider`.
  - `dot_size`: slider, default 14px, applied to bullet width and height.
  - `dot_spacing`: default 6px, as the bullet's horizontal margin.
  - `dot_color`: default `rgba(0,0,0,0.2)`, with bullet `opacity:1` so the colour alone controls it.
  - `dot_active_color`: default `#FF0F02`.
  - All via `selectors` on `{{WRAPPER}} .lumeniq-vimeo-slider__dots .swiper-pagination-bullet[-active]`.

**CSS (`vimeo-slider.css`):**
- The dots sit **below** the slides, centred: `.lumeniq-vimeo-slider__dots { position: static; padding: 13px 0; line-height: 0; }`. This gives a 14px dot in a 40px band, matching the reference (540px slider area vs 500px slides).
- Slides fill the width.

- [ ] **Step 1: Write `tests/php/prepare-slides-test.php`.**
  - Stub `namespace Elementor { abstract class Widget_Base {} class Controls_Manager {} }`, define `ABSPATH`, then require the trait and the widget.
  - Cases:
    - `[['video_id'=>'1139303378','raw_params'=>'background=1']]` → 1 item; `src` `https://player.vimeo.com/video/1139303378?background=1`; default `allow`; `disable_pointer` false when the key is missing, true when it's `'yes'`.
    - `[['video_id'=>''], ['video_id'=>'abc'], ['video_id'=>'1236789302']]` → 1 item, the `1236789302` one.
    - `[]` → `[]`.
  - Print `OK` on success.
- [ ] **Step 2:** `php tests/php/prepare-slides-test.php` → fails.
- [ ] **Step 3:** Implement the widget, CSS, vendored SDK, stub JS, and registration in `class-main.php` (`register_widgets` also registers `Vimeo_Slider_Widget`, plus the two register-asset hooks in `on_plugins_loaded`).
- [ ] **Step 4:** Both PHP test scripts print `OK`, `php -l` is clean on every PHP file, and `node --test tests/js/` passes.
- [ ] **Step 5: Commit** `git commit -m "feat: Vimeo Video Slider widget markup and controls" -- widgets/class-vimeo-slider.php assets/css/vimeo-slider.css assets/js/vendor/vimeo-player.min.js assets/js/vimeo-slider.js includes/class-main.php tests/php/prepare-slides-test.php`

---

### Task 4: Front-end handler – playback, advance, popups

**Files:**
- Modify: `assets/js/vimeo-slider.js`

**Interfaces:**
- Consumes:
  - the markup and handles from Task 3
  - `LumenEVWFinish.create` from Task 1
  - `Vimeo.Player`
  - `elementorFrontend.utils.swiper`: `const swiper = await new elementorFrontend.utils.swiper( el, options )`
  - Elementor Pro's jQuery window events `elementor/popup/show` / `elementor/popup/hide`, called with `( event, id, instance )`

**Behaviour** (one `init( rootEl )` per widget, registered on `elementorFrontend.hooks.addAction( 'frontend/element_ready/lumeniq_vimeo_slider.default', … )` inside `jQuery( window ).on( 'elementor/frontend/init', … )`):
- **Init guard:** if `rootEl.dataset.lumeniqReady` is set, return; otherwise set it. Editor re-renders replace the DOM node, so a fresh node inits once.
- **Swiper options:** `{ slidesPerView: 1, speed: 500, rewind: true, autoplay: false, allowTouchMove: true, pagination: { el: dotsEl, clickable: true } }`. Omit `pagination` when there's no dots element.
- **Players:** one `Vimeo.Player( iframe )` per slide, plus one detector per slide whose `onFinish` calls `swiper.slideNext()`.
  - Each player's `timeupdate`/`ended` is forwarded to its detector **only if its index is the active one**. This is the guard for Review Focus #2.
  - With one slide, never call `slideNext`.
- **`activate( index )`:**
  - pause every other player
  - `reset()` that slide's detector
  - `setCurrentTime( 0 )` then `play()` on the active player
  - Catch every SDK promise rejection silently, since unmuted autoplay may be blocked.
  - Called on Swiper `slideChange` with `swiper.realIndex`, and once after init with `0`.
- **Popup:**
  - If `rootEl.closest( '.elementor-popup-modal, [data-elementor-type="popup"]' )` exists, look for the popup ID on the closest `[data-elementor-id]` with `data-elementor-type="popup"`.
  - On `elementor/popup/show` with a matching ID: `swiper.slideTo( 0, 0 )` then `activate( 0 )`.
  - On `elementor/popup/hide` with a matching ID: pause all players.

- [ ] **Step 1:** Implement the above.
- [ ] **Step 2:** `node --check assets/js/vimeo-slider.js` → no syntax errors, and `node --test tests/js/` still passes.
- [ ] **Step 3: Commit** `git commit -m "feat: slider playback, advance on finish and popup handling" -- assets/js/vimeo-slider.js`

---

### Task 5: Install on dowodev and verify in the browser

**Files:** none in the repo (deploy only).

- [ ] **Step 1: Back up.** `ssh dowodev 'cd public_html/showtime/wp-content/plugins && cp -a lumen-elementor-vimeo lumen-elementor-vimeo.bak-1.0.3'` → the backup directory exists (`ls`).
- [ ] **Step 2: Deploy.** `rsync -av --delete --exclude .git --exclude .github --exclude docs --exclude tests --exclude CLAUDE.md --exclude AGENTS.md --exclude .githubupdater.conf ./ dowodev:public_html/showtime/wp-content/plugins/lumen-elementor-vimeo/`. Then `ssh dowodev 'cd public_html/showtime && wp plugin list | grep lumen'` → still `active`.
- [ ] **Step 3: Hand-off point.** Ask the user to add the **Vimeo Video Slider** to a duplicate of the Trading Cards popup (or a test page) with 4 slides alternating `1139303378` / `1236789302`, `background=1`, 4:5. Then wait.
  - Don't create content on the site yourself.
  - The page URL and popup trigger come from the user.
- [ ] **Step 4: Browser checks** in the user's Chrome (claude-in-chrome, new tab).
  - Before each round, run `await fetch( <each plugin asset URL>, { cache: 'reload' } )` in the page so stale assets aren't served, because the version stays 1.0.3 during testing.
  - Checks:
    1. The popup box and dots match the reference: 400 × 500 slides, 14px dots 6px apart, 20% black inactive, `#FF0F02` active, sitting below the video.
    2. Each video advances to the next when it finishes, with no timer. After the last slide it rewinds to the first.
    3. Close the popup mid-video: all players are paused (check `getPaused()` via the console). Reopen: slide 1 starts at 0s.
    4. Click a dot mid-video: the old video pauses, the new one starts from 0, and the advance follows the new video's end only.
    5. A page with the existing Vimeo Embed widget renders the same as before.
    6. A one-slide slider shows no dots, loops and doesn't error.
    7. In the Elementor editor, change a slider control 3 times. The console shows no errors and only one Swiper per widget.
  - Any failure: fix it in the owning task's file, commit, redeploy (Step 2) and re-check.
- [ ] **Step 5:** Report results to the user, including anything that differed from the spec. **Stop here until the user says ship.**

---

### Task 6: Release (only after the user says ship)

**Files:**
- Modify: `lumen-elementor-vimeo.php` (only the `Version:` line → `1.1.0`)
- Modify: `CHANGELOG.md`

- [ ] **Step 1:** Add a `## v1.1.0 – Vimeo Video Slider - <date>` entry at the top of `CHANGELOG.md`, in the existing style: one bullet for the new widget and one for the shared `src` builder refactor.
- [ ] **Step 2:** Bump `Version:` to `1.1.0`. Use `git add -p` so the user's Plugin URI line is only included if they want it; ask them.
- [ ] **Step 3:** Commit, then confirm with the user how to land it (merge to `main` or a PR) before pushing anything. Pushing to `main` triggers the release to every site running the plugin.
- [ ] **Step 4:** Ask the user whether to remove the `lumen-elementor-vimeo.bak-1.0.3` backup on dowodev. Don't delete it unasked.
