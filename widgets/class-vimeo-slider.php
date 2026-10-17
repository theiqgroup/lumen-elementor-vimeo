<?php
namespace LumenIQ\Elementor;

if ( ! defined( 'ABSPATH' ) ) exit;

class Vimeo_Slider_Widget extends \Elementor\Widget_Base {

	use Vimeo_Src;

	const DEFAULT_ALLOW = 'autoplay; fullscreen; picture-in-picture';

	public function get_name() {
		return 'lumeniq_vimeo_slider';
	}

	public function get_title() {
		return 'Vimeo Video Slider';
	}

	public function get_icon() {
		return 'eicon-slider-video';
	}

	public function get_categories() {
		return [ 'basic' ];
	}

	public function get_keywords() {
		return [ 'vimeo', 'video', 'slider', 'carousel', 'slides' ];
	}

	public function get_script_depends() {
		return [ 'swiper', 'lumen-evw-vimeo-player', 'lumen-evw-finish-detector', 'lumen-evw-vimeo-slider' ];
	}

	public function get_style_depends() {
		return [ 'e-swiper', 'lumen-evw-vimeo-slider' ];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'slides_section',
			[
				'label' => 'Videos',
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'aspect_ratio',
			[
				'label'   => 'Aspect Ratio',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '125', // 4:5
				'options' => [
					'56.25'  => '16:9',
					'75'     => '4:3',
					'62.5'   => '16:10',
					'100'    => '1:1',
					'125'    => '4:5',
					'177.78' => '9:16',
				],
			]
		);

		$this->add_control(
			'show_mute_button',
			[
				'label'        => 'Show mute button',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => 'Yes',
				'label_off'    => 'No',
				'return_value' => 'yes',
				'default'      => '',
				'description'  => 'Videos start muted, so browsers allow them to autoplay. Visitors tap the button to turn the sound on, and their choice carries across slides.',
			]
		);

		$this->add_control(
			'start_muted',
			[
				'label'        => 'Start muted',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => 'Yes',
				'label_off'    => 'No',
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => 'Turn off to start with sound. If the browser blocks that (e.g. Safari), the video plays muted instead.',
				'condition'    => [ 'show_mute_button' => 'yes' ],
			]
		);

		$this->add_control(
			'mute_button_position',
			[
				'label'     => 'Mute button position',
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'bottom-right',
				'options'   => [
					'bottom-right' => 'Bottom right',
					'bottom-left'  => 'Bottom left',
					'top-right'    => 'Top right',
					'top-left'     => 'Top left',
				],
				'condition' => [ 'show_mute_button' => 'yes' ],
			]
		);

		// Force a definite width on the widget element. As an Elementor flex
		// item it otherwise has no resolved main size, which stops the inner
		// `aspect-ratio` box from reporting its height — the container then
		// collapses to ~300px and the iframe overflows into the next element.
		// A hidden control with a default value emits this rule for every
		// instance, scoped to {{WRAPPER}} (the .elementor-element-… div).
		$this->add_control(
			'force_full_width',
			[
				'type'      => \Elementor\Controls_Manager::HIDDEN,
				'default'   => 'yes',
				'selectors' => [
					'{{WRAPPER}}' => 'width: 100%;',
				],
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'video_id',
			[
				'label'       => 'Vimeo Video ID',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => '1234567890',
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'privacy_hash',
			[
				'label'       => 'Privacy Hash (optional, for Unlisted)',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'e.g. a1b2c3d4',
				'description' => 'If your embed URL includes ?h=..., paste the hash here.',
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'raw_params',
			[
				'label'       => 'Query Parameters',
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => 'background=1',
				'description' => 'Enter exactly what you want after the ?. Example: background=1 OR autoplay=1&muted=1&loop=1&controls=0',
			]
		);

		$repeater->add_control(
			'allow',
			[
				'label'       => 'Iframe allow attribute',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => self::DEFAULT_ALLOW,
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'disable_pointer_events',
			[
				'label'        => 'Disable clicks (prevents pause)',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => 'Yes',
				'label_off'    => 'No',
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => 'Stops users clicking the Vimeo overlay (play/pause). Also lets visitors swipe between slides on touch devices.',
			]
		);

		$this->add_control(
			'slides',
			[
				'label'       => 'Videos',
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => [ [], [] ],
				'title_field' => '{{{ video_id }}}',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_section',
			[
				'label' => 'Style',
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'max_width',
			[
				'label'      => 'Max Width',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 2000 ],
					'%'  => [ 'min' => 0, 'max' => 100 ],
					'vw' => [ 'min' => 0, 'max' => 100 ],
				],
				'default'    => [ 'unit' => '%', 'size' => 100 ],
				'selectors'  => [
					'{{WRAPPER}} .lumeniq-vimeo-slider' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		// Caps the video height (e.g. to fit a popup on screen). The width
		// shrinks to keep the aspect ratio: see .lumeniq-vimeo-slider__swiper
		// in vimeo-slider.css, which multiplies this by --lumeniq-ratio.
		$this->add_responsive_control(
			'max_height',
			[
				'label'       => 'Max Video Height',
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'size_units'  => [ 'vh', 'px' ],
				'range'       => [
					'vh' => [ 'min' => 10, 'max' => 100 ],
					'px' => [ 'min' => 100, 'max' => 2000 ],
				],
				'description' => 'Keeps the video on screen; it narrows to keep its shape. The dots add about 40px below.',
				'selectors'   => [
					'{{WRAPPER}} .lumeniq-vimeo-slider' => '--lumeniq-max-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'dot_size',
			[
				'label'      => 'Dot Size',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 4, 'max' => 40 ] ],
				'default'    => [ 'unit' => 'px', 'size' => 14 ],
				'selectors'  => [
					'{{WRAPPER}} .lumeniq-vimeo-slider__dots .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'dot_spacing',
			[
				'label'      => 'Dot Spacing',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
				'default'    => [ 'unit' => 'px', 'size' => 6 ],
				'selectors'  => [
					'{{WRAPPER}} .lumeniq-vimeo-slider__dots .swiper-pagination-bullet' => 'margin: 0 {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'dot_color',
			[
				'label'     => 'Dot Colour',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.2)',
				'selectors' => [
					'{{WRAPPER}} .lumeniq-vimeo-slider__dots .swiper-pagination-bullet' => 'background-color: {{VALUE}}; opacity: 1;',
				],
			]
		);

		$this->add_control(
			'dot_active_color',
			[
				'label'     => 'Active Dot Colour',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FF0F02',
				'selectors' => [
					'{{WRAPPER}} .lumeniq-vimeo-slider__dots .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'mute_style_section',
			[
				'label'     => 'Mute Button',
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_mute_button' => 'yes' ],
			]
		);

		$this->add_control(
			'mute_button_size',
			[
				'label'      => 'Size',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 24, 'max' => 80 ] ],
				'default'    => [ 'unit' => 'px', 'size' => 40 ],
				'selectors'  => [
					'{{WRAPPER}} .lumeniq-vimeo-slider__mute' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'mute_icon_color',
			[
				'label'     => 'Icon Colour',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => [
					'{{WRAPPER}} .lumeniq-vimeo-slider__mute, {{WRAPPER}} .lumeniq-vimeo-slider__mute:hover, {{WRAPPER}} .lumeniq-vimeo-slider__mute:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'mute_background_color',
			[
				'label'     => 'Background Colour',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.5)',
				'selectors' => [
					'{{WRAPPER}} .lumeniq-vimeo-slider__mute, {{WRAPPER}} .lumeniq-vimeo-slider__mute:hover, {{WRAPPER}} .lumeniq-vimeo-slider__mute:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Turns the repeater rows into iframe data, skipping rows without a
	 * usable video ID.
	 *
	 * @param array $slides Repeater rows.
	 * @return array List of [ 'src' => string, 'allow' => string, 'disable_pointer' => bool ].
	 */
	public static function prepare_slides( array $slides ) {
		$items = [];

		foreach ( $slides as $slide ) {
			$src = self::build_src(
				$slide['video_id'] ?? '',
				$slide['privacy_hash'] ?? '',
				$slide['raw_params'] ?? ''
			);

			if ( empty( $src ) ) continue;

			$items[] = [
				'src'             => $src,
				'allow'           => ! empty( $slide['allow'] ) ? $slide['allow'] : self::DEFAULT_ALLOW,
				'disable_pointer' => ( $slide['disable_pointer_events'] ?? '' ) === 'yes',
			];
		}

		return $items;
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$items = self::prepare_slides( $settings['slides'] ?? [] );

		if ( empty( $items ) ) {
			echo '<div>Please provide a Vimeo Video ID.</div>';
			return;
		}

		// Same sizing approach as the embed widget: see
		// Vimeo_Embed_Widget::render() for why `aspect-ratio` needs the
		// forced full width on {{WRAPPER}}.
		$aspect      = (float) ( $settings['aspect_ratio'] ?? 125 );
		$aspect      = $aspect > 0 ? $aspect : 125;
		$inner_style = 'position:relative;width:100%;overflow:hidden;aspect-ratio:100 / ' . esc_attr( $aspect ) . ';';

		echo '<div class="lumeniq-vimeo-slider" style="--lumeniq-ratio:' . esc_attr( round( 100 / $aspect, 4 ) ) . ';" data-lumeniq-vimeo-slider>';
		echo '<div class="swiper lumeniq-vimeo-slider__swiper"><div class="swiper-wrapper">';

		foreach ( $items as $item ) {
			$iframe_style = 'display:block;width:100%;height:100%;border:0;';
			if ( $item['disable_pointer'] ) {
				$iframe_style .= 'pointer-events:none;';
			}

			// No loading="lazy": slides start off-screen or inside a hidden
			// popup, and the player must be ready when its slide is shown.
			echo '<div class="swiper-slide">';
			echo '<div class="lumeniq-vimeo-inner" style="' . esc_attr( $inner_style ) . '">';
			echo '<iframe'
				. ' src="' . esc_url( $item['src'] ) . '"'
				. ' style="' . esc_attr( $iframe_style ) . '"'
				. ' frameborder="0"'
				. ' allow="' . esc_attr( $item['allow'] ) . '"'
				. ' allowfullscreen'
				. '></iframe>';
			echo '</div>';
			echo '</div>';
		}

		echo '</div>';

		if ( ( $settings['show_mute_button'] ?? '' ) === 'yes' ) {
			$positions = [ 'bottom-right', 'bottom-left', 'top-right', 'top-left' ];
			$position  = in_array( $settings['mute_button_position'] ?? '', $positions, true ) ? $settings['mute_button_position'] : 'bottom-right';

			$start_muted = ( $settings['start_muted'] ?? 'yes' ) === 'yes';

			// The handler applies this start state to every video and toggles
			// the button from there.
			echo '<button type="button"'
				. ' class="lumeniq-vimeo-slider__mute lumeniq-vimeo-slider__mute--' . esc_attr( $position ) . ( $start_muted ? ' is-muted' : '' ) . '"'
				. ' aria-label="' . esc_attr( $start_muted ? 'Unmute video' : 'Mute video' ) . '"'
				. ' data-lumeniq-mute'
				. ' data-start-muted="' . ( $start_muted ? '1' : '0' ) . '"'
				. '>';
			echo '<svg class="lumeniq-vimeo-slider__icon-muted" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3 9v6h4l5 5V4L7 9H3z"/><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M16 9l5 6M21 9l-5 6"/></svg>';
			echo '<svg class="lumeniq-vimeo-slider__icon-sound" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3 9v6h4l5 5V4L7 9H3z"/><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></svg>';
			echo '</button>';
		}

		echo '</div>';

		if ( count( $items ) > 1 ) {
			echo '<div class="swiper-pagination lumeniq-vimeo-slider__dots"></div>';
		}

		echo '</div>';
	}
}
