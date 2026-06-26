# Elementor Vimeo Widget

A WordPress plugin that allows embedding of Vimeo videos without the embed settings being over-ridden by Elementor.

## Requirements

- WordPress 6.9+
- Elementor Plugin
- PHP 8.4+

## Installation

### 1. Extract Plugin Files

Extract the plugin ZIP file to your WordPress plugins directory:

```bash
wp-content/plugins/lumen-elementor-vimeo/
```

### 2. Create Elementor Widget

Once the plugin is activated, a new Elementor widget called **"Raw Vimeo (No Params Rewrite)"** will be available in the Elementor editor.

#### Adding the Widget to Your Page

1. **Open Elementor Editor** on your page/post
2. **Search for "Raw Vimeo"** in the widgets panel
3. **Drag the widget** to your desired location

#### Widget Settings

The widget provides the following controls:

##### Content Tab

- **Vimeo Video ID** (required)
  - Enter the numeric Vimeo video ID
  - Example: `1139305288`
  - Found in your Vimeo URL: `vimeo.com/{VIDEO_ID}`

- **Privacy Hash** (optional)
  - For unlisted videos with privacy hash
  - Example: `a1b2c3d4`
  - If your embed URL includes `?h=...`, paste the hash here

- **Query Parameters (raw)**
  - Enter exactly what you want after the `?` in the iframe URL
  - Examples:
    - `background=1`
    - `autoplay=1&muted=1&loop=1&controls=0`
  - The plugin will **not** rewrite these parameters (unlike default Elementor video widget)

- **Iframe allow attribute**
  - Default: `autoplay; fullscreen; picture-in-picture`
  - Customize browser permissions for the iframe

- **Disable clicks (prevents pause)**
  - Switcher: Yes/No (Default: Yes)
  - Stops users from clicking the Vimeo overlay (play/pause)
  - Useful for background videos

- **Aspect Ratio**
  - Options: 16:9 (default), 4:3, 16:10, 1:1
  - Maintains responsive video proportions

##### Style Tab

- **Max Width**
  - Responsive control with units: px, %, vw
  - Default: 100%
  - Control the maximum width of the video container

#### Example Use Cases

**Background Video**
```
Vimeo Video ID: 1139305288
Query Parameters: background=1&autoplay=1&muted=1&loop=1&controls=0
Disable clicks: Yes
```

**Unlisted Video with Custom Controls**
```
Vimeo Video ID: 987654321
Privacy Hash: a1b2c3d4
Query Parameters: color=ffffff&title=0&byline=0
Disable clicks: No
```

## Support

For issues, questions, or feature requests, contact LumenIQ: team@lumeniq.uk
