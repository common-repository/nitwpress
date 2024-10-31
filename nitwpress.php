<?php
/*
Plugin Name: NiTwPress
Plugin URI: http://sakuratan.biz/contents/NiTwPress
Description: NiTwPress is a Twitter client for WordPress sidebar widget. It displays your twit on the WordPress sidebar with comment scrolling like Niconico-doga. (NiTwPress is an abbreviation of `NIconico-doga like TWitter client for wordPRESS'.)
Author: sakuratan
Author URI: http://sakuratan.biz/
Version: 0.9.3
*/

/*
Copyright (c) 2009-2020 sakuratan.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
 * Returns options.
 */
function nitwpress_get_options() {
	$defaults = array(
		'username'                     => '',
		'bearertoken'                  => '',
		'widgettitle'                  => '',
		'widgetstyles'                 => 'text-align:center',
		'fontcolor'                    => 'auto',
		'linkcolor'                    => 'auto',
		'interval'                     => 30,
		'logo'                         => true,
		'icon'                         => true,
		'iconframe'                    => false,
		'iconframecolor'               => '#c0c0c0',
		'profile_background_image_url' => '',
		'profile_background_tile'      => false,
	);

	return array_merge( $defaults, get_option( 'nitwpress_options', array() ) );
}

/*
 * Update options.
 */
function nitwpress_update_options( &$newvars, $prefix = '' ) {
	$options = nitwpress_get_options();

	$options['logo']                    = false;
	$options['icon']                    = false;
	$options['iconframe']               = false;
	$options['profile_background_tile'] = false;

	foreach ( $options as $key => $value ) {
		$nkey = "{$prefix}{$key}";
		if ( array_key_exists( $nkey, $newvars ) ) {
			$newvalue = $newvars[ $nkey ];
			if ( ( $key == 'logo' || $key == 'icon' || $key == 'iconframe' ||
						$key == 'profile_background_tile' ) && $newvalue ) {
				$options[ $key ] = true;
			} elseif ( $key == 'interval' ) {
				$options[ $key ] = (int) $newvalue;
			} else {
				$options[ $key ] = $newvalue;
			}
		}
	}

	update_option( 'nitwpress_options', $options );
}

/*
 * Update cache files.
 */
function nitwpress_update_caches() {
	require_once dirname( __file__ ) . '/twitter.php';

	$options = nitwpress_get_options();
	if ( $options['bearertoken'] && $options['username'] ) {
		nitwpress_twitter_update_caches( WP_PLUGIN_DIR . '/nitwpress/caches', $options );
	}
}

/*
 * The widget.
 */
function nitwpress_sidebar_widget( $args ) {
	extract( $args );

	echo $before_widget;

	$options = nitwpress_get_options();
	if ( $options['username'] ) :
		$username = htmlspecialchars( $options['username'] );
		$siteurl  = get_option( 'siteurl' );

		$_flashvars                = array();
		$_flashvars['timelineurl'] = plugins_url( '/caches/user_timeline.json', __FILE__ );
		$_flashvars['wrappernode'] = '#nitwpress_wrapper';

		if ( $options['fontcolor'] && strcasecmp( $options['fontcolor'], 'auto' ) != 0 ) {
			$_flashvars['fontcolor'] = preg_replace( '/^#*/', '', $options['fontcolor'] );
		}

		if ( $options['linkcolor'] && strcasecmp( $options['linkcolor'], 'auto' ) != 0 ) {
			$_flashvars['linkcolor'] = preg_replace( '/^#*/', '', $options['linkcolor'] );
		}

		if ( ! $options['logo'] ) {
			$_flashvars['disablelogo'] = '1';
		}

		if ( ! $options['icon'] ) {
			$_flashvars['disableicon'] = '1';
		}

		if ( $options['iconframe'] ) {
			$_flashvars['iconframe']      = '1';
			$_flashvars['iconframecolor'] = preg_replace( '/^#*/', '', $options['iconframecolor'] );
		}

		if ( $options['widgetstyles'] ) {
			$style = ' style="' . htmlspecialchars( $options['widgetstyles'] ) . '"';
		} else {
			$style = '';
		}

		if ( $options['widgettitle'] ) :
			?>
<h2 class="widgettitle"><?php echo htmlspecialchars( $options['widgettitle'] ); ?></h2>
			<?php
	endif;
		?>
<div class="nitwpress_widget_content"<?php echo $style; ?>>
<span id="nitwpress_wrapper"></span>
<script type="text/javascript">
	//<![CDATA[
		nitwpress(<?php echo json_encode( $_flashvars ); ?>);
	//]]>
</script>
<div><a href="https://twitter.com/<?php echo $username; ?>">follow <?php echo $username; ?> at twitter.com</a></div>
</div>
		<?php

	endif;

	echo $after_widget;
}

/*
 * Display errors.
 */
function nitwpress_display_error( $mesg ) {
	echo "<p style=\"color:red\">ERROR: {$mesg}</p>";
}

/*
 * Widget manager.
 */
function nitwpress_widget_control() {
	if ( array_key_exists( 'nitwpress_username', $_POST ) ) {
		nitwpress_update_options( $_POST, 'nitwpress_' );
		nitwpress_update_caches();
	}

	$options = nitwpress_get_options();
	?>
<h3><?php _e( 'Twitter account', 'nitwpress' ); ?></h3>

<table>
	<tr>
		<td><?php _e( 'Screen name:', 'nitwpress' ); ?></td>
		<td><input type="text" name="nitwpress_username" value="<?php echo htmlspecialchars( $options['username'] ); ?>" size="20" /></td>
	</tr>

	<tr>
		<td><?php _e( 'Bearer token:', 'nitwpress' ); ?></td>
		<td><input type="password" name="nitwpress_bearertoken" value="<?php echo htmlspecialchars( $options['bearertoken'] ); ?>" size="20" /></td>
	</tr>
</table>

<h3><?php _e( 'Widget title', 'nitwpress' ); ?></h3>

<div>
	<input type="text" name="nitwpress_widgettitle" value="<?php echo htmlspecialchars( $options['widgettitle'] ); ?>" style="width:100%" />
</div>

<p>
	<?php _e( '(The widget suppress the widget title when this field is empty.)', 'nitwpress' ); ?>
</p>

<h3><?php _e( 'CSS for widget content', 'nitwpress' ); ?></h3>

<div>
	<input type="text" name="nitwpress_widgetstyles" value="<?php echo htmlspecialchars( $options['widgetstyles'] ); ?>" style="width:100%" />
</div>
<p>
	<?php _e( '(The widget content area have &quot;nitwpress_widget_content&quot; class. You can use the CSS class for designing the widget with out this field.)', 'nitwpress' ); ?>
</p>

<h3><?php _e( 'Font colors', 'nitwpress' ); ?></h3>

<table>
	<tr>
		<td><?php _e( 'Color of comments:', 'nitwpress' ); ?></td>
		<td><input type="text" name="nitwpress_fontcolor" value="<?php echo htmlspecialchars( $options['fontcolor'] ); ?>" size="7" /></td>
	</tr>
	<tr>
		<td><?php _e( 'Color of links:', 'nitwpress' ); ?></td>
		<td><input type="text" name="nitwpress_linkcolor" value="<?php echo htmlspecialchars( $options['linkcolor'] ); ?>" size="7" /></td>
	</tr>
</table>

<p>
	<?php _e( '(Use hash color code (e.g. #ffffff) or &quot;auto&quot; for these fields. HTML color name (e.g. white) is not acceptable. The widget will read default font and link colors from Twitter API if you choose &quot;auto&quot;.)', 'nitwpress', 'nitwpress' ); ?>
	</p>

<h3><?php _e( 'Icon', 'nitwpress' ); ?></h3>

<p>
	<input type="checkbox" id="nitwpress_icon" name="nitwpress_icon" value="1" 
	<?php
	if ( $options['icon'] ) {
		echo 'checked="checked"'; }
	?>
	 />
	<label for="nitwpress_icon"><?php _e( 'Display your Twitter icon.', 'nitwpress' ); ?></label>
</p>

<p>
	<input type="checkbox" id="nitwpress_iconframe_checkbox" name="nitwpress_iconframe" value="1" 
	<?php
	if ( $options['iconframe'] ) {
		echo 'checked="checked"'; }
	?>
	 />
	<label for="nitwpress_iconframe_checkbox"><?php _e( 'Enable icon image frame.', 'nitwpress' ); ?></label>
</p>

<p>
	<?php _e( 'Color of icon frame:', 'nitwpress' ); ?> <input type="text" name="nitwpress_iconframecolor" value="<?php echo htmlspecialchars( $options['iconframecolor'] ); ?>" size="7" /><br />
	<?php _e( '(Use hash color code (e.g. #ffffff) for this field. HTML color name (e.g. white) is not acceptable.)', 'nitwpress' ); ?>
</p>

<h3><?php _e( 'Background Image', 'nitwpress' ); ?></h3>

<p>
	<?php _e( 'Enter image URL if you want to use different background image from Twitter', 'nitwpress' ); ?><br />
	<input type="text" name="nitwpress_profile_background_image_url" value="<?php echo htmlspecialchars( $options['profile_background_image_url'] ); ?>" style="width:99%" />
</p>

<p>
	<input type="checkbox" id="nitwpress_profile_background_tile" name="nitwpress_profile_background_tile" value="1" 
	<?php
	if ( $options['profile_background_tile'] ) {
		echo 'checked="checked"'; }
	?>
	 />
	<label for="nitwpress_profile_background_tile"><?php _e( 'Tile background image', 'nitwpress' ); ?></label>
</p>

<h3><?php _e( 'Miscellaneous options', 'nitwpress' ); ?></h3>

<p>
	<?php _e( 'Cache updating interval:', 'nitwpress' ); ?> <input type="text" name="nitwpress_interval" value="<?php echo htmlspecialchars( $options['interval'] ); ?>" size="3" /> <?php _e( '(minutes)', 'nitwpress' ); ?>
</p>

<p>
	<input type="checkbox" id="nitwpress_logo_checkbox" name="nitwpress_logo" value="1" 
	<?php
	if ( $options['logo'] ) {
		echo 'checked="checked"'; }
	?>
	 />
	<label for="nitwpress_logo_checkbox"><?php _e( 'Display NiTwPress logo on Flash.', 'nitwpress' ); ?></label>
</p>
	<?php

	$dir = WP_PLUGIN_DIR . '/nitwpress/caches';
	if ( ! is_dir( $dir ) ) {
		nitwpress_display_error( sprintf( __( 'Missing permissions for writing on %s. Fix the error before enter your Twitter account.', 'nitwpress' ), $dir ) );
	}

	if ( ! function_exists( 'curl_init' ) ) {
		nitwpress_display_error( __( 'Missing cURL module.', 'nitwpress' ) );
	}
}

/*
 * Module initializer.
 */
function nitwpress_init() {
	require_once ABSPATH . 'wp-includes/widgets.php';
	wp_register_sidebar_widget(
		'NiTwPress',
		'Nitwpress',
		'nitwpress_sidebar_widget',
		array(
			'description' => 'Nitwpress sidebar widget',
		)
	);
	wp_register_widget_control( 'NiTwPress', 'nitwpress', 'nitwpress_widget_control' );
}

function nitwpress_load_plugin_textdomain() {
	load_plugin_textdomain( 'nitwpress', false, basename( dirname( __FILE__ ) ) . '/po' );
}

function nitwpress_script_method() {
	wp_enqueue_script( 'openfl', '//cdnjs.cloudflare.com/ajax/libs/openfl/8.9.6/openfl.min.js', array(), '8.9.6' );
	wp_enqueue_script( 'nitwpress', plugins_url( '/nitwpress.min.js', __FILE__ ) );
}

add_action( 'init', 'nitwpress_init' );
add_action( 'plugins_loaded', 'nitwpress_load_plugin_textdomain' );
add_action( 'wp_enqueue_scripts', 'nitwpress_script_method' );

/*
 * Schedule cron task.
 */
function hitwpress_activation() {
	wp_schedule_event( time(), 'nitwpress', 'nitwpress_hourly_event' );
}

/*
 * Unschedule cron task.
 */
function nitwpress_deactivation() {
	wp_clear_scheduled_hook( 'nitwpress_hourly_event' );
}

/*
 * Filter for wp_cron that appends nitwpress schedule event.
 */
function nitwpress_add_cron( $sched ) {
	if ( ! array_key_exists( 'nitwpress', $sched ) ) {
		$options = get_option( 'nitwpress_options' );
		if ( array_key_exists( 'interval', $options ) ) {
			$sched['nitwpress'] = array(
				'interval' => (int) $options['interval'] * 60,
				'display'  => __( 'Schedule for NiTwPress plugins', 'nitwpress' ),
			);
		}
	}
	return $sched;
}

register_activation_hook( __FILE__, 'hitwpress_activation' );
register_deactivation_hook( __FILE__, 'nitwpress_deactivation' );

add_action( 'nitwpress_hourly_event', 'nitwpress_update_caches' );
add_filter( 'cron_schedules', 'nitwpress_add_cron' );

?>
