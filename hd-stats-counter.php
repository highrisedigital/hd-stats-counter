<?php
/*
Plugin Name: HD Stats Counter
Description: UPDATED UPDATED Add simple stats to pages which count up when the page loads.
Requires at least: 6.7
Requires PHP: 8.0
Version: 1.1
Author: Highrise Digital
Author URI: https://highrise.digital/
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: hd-stats-counter
*/

// define variable for path to this plugin file.
define( 'HD_STATS_COUNTER_LOCATION', dirname( __FILE__ ) );
define( 'HD_STATS_COUNTER_LOCATION_URL', plugins_url( '', __FILE__ ) );

/**
 * Function to run on plugins load.
 */
function hd_stats_counter_plugins_loaded() {

	$locale = apply_filters( 'plugin_locale', get_locale(), 'hd-stats-counter' );
	load_textdomain( 'hd-stats-counter', WP_LANG_DIR . '/hd-stats-counter/hd-stats-counter-' . $locale . '.mo' );
	load_plugin_textdomain( 'hd-stats-counter', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );

}

add_action( 'plugins_loaded', 'hd_stats_counter_plugins_loaded' );

/**
 * Updater class for the plugin.
 */
function hd_stats_counter_plugin_check_for_updates( $transient ) {
    
	// Check if transient is available
    if ( empty( $transient->checked ) ) {
        return $transient;
    }

    // Plugin information
    $plugin_slug = 'hd-stats-counter';
    $plugin_file = 'hd-stats-counter/hd-stats-counter.php'; // Adjust the path to your plugin's main file
    $remote_url  = 'https://wp.test/hd-stats-counter-updates.json'; // URL to your JSON file

    // Fetch update information
    $response = wp_remote_get( $remote_url );

	// if the response errors.
    if (
        is_wp_error( $response ) ||
        wp_remote_retrieve_response_code( $response ) !== 200
    ) {
		hd_write_log('Update check error: ' . $response->get_error_message());
        return $transient;
    }

	// get the data about an updates.
    $update_data = json_decode( wp_remote_retrieve_body( $response ) );

    // Ensure the response contains valid data
    if (
        ! empty( $update_data->new_version ) &&
        version_compare( $update_data->new_version, $transient->checked[$plugin_file], '>' )
    ) {
        $transient->response[ $plugin_file ] = (object) [
            'slug'        => $plugin_slug,
            'plugin'      => $plugin_file,
            'new_version' => $update_data->new_version,
            'package'     => $update_data->package, // URL to the new plugin zip file
            'url'         => $update_data->homepage // Optional: Link to plugin details page
        ];
		hd_write_log('Update detected: ' . $update_data->new_version);
    } else {

		hd_write_log('No update available.');
	}

	// return the transient.
    return $transient;

}

add_filter( 'pre_set_site_transient_update_plugins', 'hd_stats_counter_plugin_check_for_updates' );

/**
 * Enqueue Editor assets.
 */
function hd_stats_counter_enqueue_editor_assets() {
	
	$asset_file = include plugin_dir_path( __FILE__ ) . 'build/index.asset.php';

	wp_enqueue_script(
		'hd-stats-counter-editor-scripts',
		plugin_dir_url( __FILE__ ) . 'build/index.js',
		$asset_file['dependencies'],
		$asset_file['version']
	);

	wp_set_script_translations(
		'hd-stats-counter-editor-scripts',
		'hd-stats-counter'
	);
}

add_action( 'enqueue_block_editor_assets', 'hd_stats_counter_enqueue_editor_assets' );

/**
 * Adds a custom 'makeSlider' attribute to all Gallery blocks.
 *
 * @param array  $args       The block arguments for the registered block type.
 * @param string $block_type The block type name, including namespace.
 * @return array             The modified block arguments.
 */
function hd_stats_counter_filter_paragraph_block_attrs( $args, $block_type ) {

    // Only add the attribute to Gallery blocks.
    if ( $block_type === 'core/paragraph' ) {
        
		// if no attrs are set, set an empty array.
		if ( ! isset( $args['attributes'] ) ) {
            $args['attributes'] = [];
        }

		$args['attributes']['makeCounter'] = [
			'type'    => 'boolean',
			'default' => false,
		];

		$args['attributes']['counterDuration'] = [
			'type'    => 'text',
			'default' => 2000,
		];
    }

	// returm the block args.
    return $args;
}

add_filter( 'register_block_type_args', 'hd_stats_counter_filter_paragraph_block_attrs', 10, 2 );

/**
 * Register the stats counter JS with WordPress.
 */
function hd_stats_counter_register_js() {

	// register the script.
	wp_register_script(
		'hd-stats-counter',
		HD_STATS_COUNTER_LOCATION_URL . '/assets/js/hd-stats-counter.js',
		[],
		filemtime( HD_STATS_COUNTER_LOCATION . '/assets/js/hd-stats-counter.js' ),
		true
	);

}

add_action( 'init', 'hd_stats_counter_register_js' );

/**
 * Filter the block metadata to add the stats counter script to the paragraph block.
 *
 * @param array $metadata The block metadata.
 * @return array
 */
function hd_stat_counter_filter_paragraph_block_metadata( $metadata ) {

	// if the block is a core/paragraph block add the custom assets.
	if ( 'core/paragraph' === $metadata['name'] ) {

		// add our js script handle to load on the front end.
		$metadata['viewScript'] = array_merge(
			(array) ( $metadata['viewScript'] ?? [] ),
			[ 'hd-stats-counter' ]
		);
	}

	// return the modified metadata.
	return $metadata;

}

add_filter( 'block_type_metadata', 'hd_stat_counter_filter_paragraph_block_metadata' );

/**
 * Add the counter duration attribute to the paragraph block.
 *
 * @param string $block_content The block content.
 * @param array  $block The block data.
 * @param array  $instance The block instance.
 * @return string
 */
function hd_stats_counter_add_block_attrs( $block_content, $block, $instance ) {

	// if this block is not a stats counter block, return the block content.
	if ( empty( $block['attrs']['makeCounter'] ) ) {
		return $block_content;
	}

	// set a default counter duration.
	$counter_duration = '2000';

	// if no counter duration is set.
	if ( ! empty( $block['attrs']['counterDuration'] ) ) {

		// set a default counter duration.
		$counter_duration = $block['attrs']['counterDuration'];

	}

	// create a new instance of the WP_HTML_Tag_Processor class.
	$tags = new WP_HTML_Tag_Processor( $block_content );

	if ( $tags->next_tag( 'p' ) ) {
		$tags->set_attribute( 'data-counter-duration', $counter_duration );
		$tags->add_class( 'hd-stats-counter' );
	}

	// save the manipulated HTML back to the block content.
	$block_content = $tags->get_updated_html();
	
	// return the block content.
	return $block_content;

}

add_filter( 'render_block_core/paragraph', 'hd_stats_counter_add_block_attrs', 10, 3 );
