<?php
namespace LumenIQ\Elementor;

if ( ! defined( 'ABSPATH' ) ) exit;

class Vimeo_Embed_Widget extends \Elementor\Widget_Base {

	use Vimeo_Src;

	public function get_name() {
		return 'lumeniq_vimeo_embed';
	}

	public function get_title() {
		return 'Vimeo Video Embed';
	}

	public function get_icon() {
		return 'eicon-youtube';
	}

	public function get_categories() {
		return [ 'basic' ];
	}

	public function get_keywords() {
		return [ 'vimeo', 'video', 'raw', 'iframe', 'background' ];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => 'Video',
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'video_id',
			[
				'label'       => 'Vimeo Video ID',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => '1234567890',
				'label_block' => true,
			]
		);

		$this->add_control(
			'privacy_hash',
			[
				'label'       => 'Privacy Hash (optional, for Unlisted)',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'e.g. a1b2c3d4',
				'description' => 'If your embed URL includes ?h=..., paste the hash here.',
				'label_block' => true,
			]
		);

		$this->add_control(
			'raw_params',
			[
				'label'       => 'Query Parameters',
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => 'background=1',
				'description' => 'Enter exactly what you want after the ?. Example: background=1 OR autoplay=1&muted=1&loop=1&controls=0',
			]
		);

		$this->add_control(
			'allow',
			[
				'label'       => 'Iframe allow attribute',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'autoplay; fullscreen; picture-in-picture',
				'label_block' => true,
			]
		);

		$this->add_control(
			'disable_pointer_events',
			[
				'label'        => 'Disable clicks (prevents pause)',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => 'Yes',
				'label_off'    => 'No',
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => 'Stops users clicking the Vimeo overlay (play/pause).',
			]
		);

		$this->add_control(
			'aspect_ratio',
			[
				'label'   => 'Aspect Ratio',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '56.25', // 16:9
				'options' => [
					'56.25' => '16:9',
					'75'    => '4:3',
					'62.5'  => '16:10',
					'100'   => '1:1',
				],
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
					'{{WRAPPER}} .lumeniq-vimeo-wrap' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$src = $this->build_src(
			$settings['video_id'] ?? '',
			$settings['privacy_hash'] ?? '',
			$settings['raw_params'] ?? ''
		);

		if ( empty( $src ) ) {
			echo '<div>Please provide a Vimeo Video ID.</div>';
			return;
		}

		$allow = ! empty( $settings['allow'] ) ? $settings['allow'] : 'autoplay; fullscreen; picture-in-picture';

		$disable_pointer = ( $settings['disable_pointer_events'] ?? '' ) === 'yes';
		$aspect          = (float) ( $settings['aspect_ratio'] ?? 56.25 );

		// The iframe is sized with the CSS `aspect-ratio` property on the inner
		// box. For that height to be respected, the widget element itself needs
		// a definite width: it is an Elementor flex item, and a flex item with
		// no definite main size doesn't resolve the aspect-ratio box's height,
		// so `.elementor-widget-container` collapses to its ~300px fallback and
		// the taller iframe overflows downward into the next element. Forcing
		// `width:100%` on {{WRAPPER}} (see register_controls) gives the definite
		// width, so the height resolves and the container grows to fit.
		//
		// The aspect ratio control stores the height/width ratio as a
		// percentage (16:9 = 56.25), so width/height = 100 / percentage.
		$inner_style = 'position:relative;width:100%;overflow:hidden;aspect-ratio:100 / ' . esc_attr( $aspect ) . ';';

		$iframe_style = 'display:block;width:100%;height:100%;border:0;';
		if ( $disable_pointer ) {
			$iframe_style .= 'pointer-events:none;';
		}

		echo '<div class="lumeniq-vimeo-wrap" style="width:100%;">';
		echo '<div class="lumeniq-vimeo-inner" style="' . esc_attr( $inner_style ) . '">';
		echo '<iframe'
			. ' src="' . esc_url( $src ) . '"'
			. ' style="' . esc_attr( $iframe_style ) . '"'
			. ' frameborder="0"'
			. ' allow="' . esc_attr( $allow ) . '"'
			. ' allowfullscreen'
			. ' loading="lazy"'
			. '></iframe>';
		echo '</div>';
		echo '</div>';
	}
}