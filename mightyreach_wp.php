<?php
/*
Plugin Name: MightyReach for WordPress
Plugin URI: http://mightybrand.com/blog/mightyreach-wordpress-plugin/
Description: MightyReach for WordPress displays your Feedburner and Twitter stats on your dashboard
Author: MightyBrand
Version: 0.1
Author URI: http://mightybrand.com/
*/

/*
    Copyright (c) 2009 MightyBrand, Inc.

    This file is part of MightyReach for WordPress.

    MightyReach for WordPress is free software: you can redistribute 
    it and/or modify it under the terms of the GNU General Public License as 
    published by the Free Software Foundation, either version 3 of the License, 
    or (at your option) any later version.

    Feed Stats for WordPress is distributed in the hope that it will 
    be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Feed Stats for WordPress.  If not, see 
    <http://www.gnu.org/licenses/>.
*/


/**
*  Function for admin dashboard widgets
*/
function mightyreach_add_dashboard_widgets() {
	wp_add_dashboard_widget('mightyreach_dashboard_widget', 'MightyReach for WordPress', 'mightyreach_dashboard_widget_output');	
} 

/**
*  Functions for Options page links
*/
function mightyreach_admin_menu_link() {
	add_options_page('MightyReach Options', 'MightyReach Options', 10, basename(__FILE__), 'mightyreach_admin_options_page');
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'mightyreach_filter_plugin_actions', 10, 2 );
}

// hooks for the functions above
add_action('wp_dashboard_setup', 'mightyreach_add_dashboard_widgets' );
add_action('admin_menu', 'mightyreach_admin_menu_link');


// when the plugin is activated, this hook calls the activation function
register_activation_hook(__FILE__, 'mightyreach_activation');

// when the plugin is deactivated, this hook calls the deactivation function
register_deactivation_hook(__FILE__, 'mightyreach_deactivation');

// we need a hook for the function that's called once per day, so here it is
add_action('mightyreach_hourly_event', 'mightyreach_refresh_data');


/**
*  Plugin activation
*/
function mightyreach_activation() {
	// schedule a new event to be run at this time, every day, and use the freshpress_daily_event action
	wp_schedule_event(time(), 'hourly', 'mightyreach_hourly_event');
	mightyreach_refresh_data();
}


/**
*  Plugin deactivation
*/
function mightyreach_deactivation() {
	// since plugin is being deactivated, need to clear all future occurances of the scheduled action
	wp_clear_scheduled_hook('mightyreach_hourly_event');
}


/**
*  Output the contents of the dashboard widget
*/
function mightyreach_dashboard_widget_output() {
	
	$settings_link = 'options-general.php?page=' . basename(__FILE__);
	
	$options = mightyreach_get_options();
	if (empty($options)) {	
		echo "Please <a href='$settings_link'>click here</a> to setup MightyReach for WordPress";
		return;
	}
	$data = mightyreach_get_data();
	
	if (!empty($options['mightyreach_feedburner_uri'])) {
		$no_feedburner = false;
		$today_feedburner = (!empty($data['feedburner']['subs_today'])) ? $data['feedburner']['subs_today'] : '';
		$yesterday_feedburner = (!empty($data['feedburner']['subs_yesterday'])) ? $data['feedburner']['subs_yesterday'] : '';
		$feedburner_change = (!empty($today_feedburner) && !empty($yesterday_feedburner)) ? round((($today_feedburner - $yesterday_feedburner) / $yesterday_feedburner)*100, 1) . '%' : '';
	} else {
		$no_feedburner = true;
	}

	if (!empty($options['mightyreach_twitter_username'])) {	
		$no_twitter = false;
		$today_twitter = (!empty($data['twitter']['followers_today'])) ? $data['twitter']['followers_today'] : '';
		$yesterday_twitter = (!empty($data['twitter']['followers_yesterday'])) ? $data['twitter']['followers_yesterday'] : '';
		$twitter_change = (!empty($today_twitter) && !empty($yesterday_twitter)) ? round((($today_twitter - $yesterday_twitter) / $yesterday_twitter)*100, 1) . '%' : '';	
	} else {
		$no_twitter = true;
	}
	
	// Display whatever it is you want to show

	echo "<table width='100%' cellspacing='7'>";

	echo "<tr style='text-align: left;'><th>Stats</th><th>Today</th><th>Yesterday</th><th>Change</th></tr>";

	//echo "<tr><td>Visits:</td><td><strong>$today_visits</strong></td><td>$yesterday_visits</td><td>".round((($today_visits - $yesterday_visits) / $yesterday_visits)*100)."%</td></tr>";
	//echo "<tr><td>Pageviews:</td><td><strong>$today_pageviews</strong></td><td>$yesterday_pageviews</td><td>".round((($today_pageviews - $yesterday_pageviews) / $yesterday_pageviews)*100)."%</td></tr>";
	
	if ($no_feedburner) {
		echo "<tr><td>RSS Subscribers:</td><td colspan=3><a href='$settings_link'>Click here to setup Feedburner</a></td></tr>";
	} else {
		echo "<tr><td>RSS Subscribers:</td><td><strong>$today_feedburner</strong></td><td>$yesterday_feedburner</td><td>".$feedburner_change."</td></tr>";
	}
	
	if ($no_twitter) {
		echo "<tr><td>Twitter followers:</td><td colspan=3><a href='$settings_link'>Click here to setup Twitter</a></td></tr>";
	} else {
		echo "<tr><td>Twitter followers:</td><td><strong>$today_twitter</strong></td><td>$yesterday_twitter</td><td>".$twitter_change."</td></tr>";
	}
	
	echo "</table>";
	
	if (!empty($data['last_updated'])) {
		
		echo "<p><em><small>Last updated: " . $data['last_updated'] . "</small></em>";
		echo "<span style='float:right;'><em><small><a href='http://mightybrand.com/blog'>Plugin by MightyBrand</a></small></em></span></p>";
	}
} 

/**
* Refresh data from 3rd party services
*/
function mightyreach_refresh_data() {

	$options = mightyreach_get_options();
	$data = mightyreach_get_data();
	$change = false;

	if (!empty($options['mightyreach_feedburner_uri'])) {
		// get feedburner data
	 	$host = 'feedburner.google.com';
		$path = '/api/awareness/1.0/GetFeedData?uri=' . $options['mightyreach_feedburner_uri'] . '&dates=2009-10-24,2009-10-25';		
		$feedburner = mightyreach_get_http_data($host, $path);
		if (!empty($feedburner)) {
			$feedburner = mightyreach_parse_feedburner($feedburner);
			$data['feedburner']['subs_yesterday'] = $feedburner['yesterday']['subs'];
			$data['feedburner']['subs_today'] = $feedburner['today']['subs'];
			$data['feedburner']['refresh'] = date('Ymd');
			$change = true;
		}
	}

 	// get twitter data
	if (!empty($options['mightyreach_twitter_username'])) {
		$host = 'twitter.com';
		$path = '/users/show/' . $options['mightyreach_twitter_username'] . '.json';
		$twitter = mightyreach_get_http_data($host, $path);
		if (!empty($twitter)) {
			$twitter = json_decode($twitter);
			// check to see if need to shift old 'today' update into 'yesterday' slot
			if ($data['twitter']['refresh'] < date('Ymd')) {
				$data['twitter']['followers_yesterday'] = $data['twitter']['followers_today'];
				$data['twitter']['followers_today'] = $twitter->followers_count;
				$data['twitter']['refresh'] = date('Ymd');
			} else {
				$data['twitter']['followers_today'] = $twitter->followers_count;
				$data['twitter']['refresh'] = date('Ymd');
			}
			$change = true;
		}
	}

	if ($change) {
		$data['last_updated'] = date('m/d/Y g:i A');
	}

	update_option('mightyreach_data', serialize($data));
}

/**
* Load options from the database
*/
function mightyreach_get_options() {
	if (!$options = get_option('mightyreach_options')) {
		$default_data = null;
        update_option('mightyreach_options', $default_data);
    }
	return $options;
}
   
/**
* Load data from the database
*/
function mightyreach_get_data() {
	if (!$theData = get_option('mightyreach_data')) {
		
		$default_data = array(
				'twitter' => array(
					'followers_today' => '',
					'followers_yesterday' => '',
					'refresh' => '',
					),
				'feedburner' => array(
					'subs_today' => '',
					'subs_yesterday' => '',
					'refresh' => '',
					),
			);
        $theData = serialize($default_data);
        update_option('mightyreach_data', $theData);
    }

	return unserialize($theData);
}

/**
* Get HTTP data from url
*/
function mightyreach_get_http_data($host, $path) {
	global $wp_version;
	
	$request = null;
	$port = 80;
	$ip=null;

	$http_request  = "GET $path HTTP/1.0\r\n";
	$http_request .= "Host: $host\r\n";
	$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
	$http_request .= "Content-Length: " . strlen($request) . "\r\n";
	$http_request .= "User-Agent: WordPress/$wp_version\r\n";
	$http_request .= "\r\n";
	$http_request .= $request;

	$http_host = $host;
	
	$response = '';
	if( false != ( $fs = @fsockopen($http_host, $port, $errno, $errstr, 10) ) ) {
		fwrite($fs, $http_request);

		while ( !feof($fs) )
			$response .= fgets($fs, 1160); // One TCP-IP packet
		fclose($fs);
		$response = explode("\r\n\r\n", $response, 2);
	}
	return $response[1];
}       


/*
*  The function mightyreach_parse_feedburner is partially based on the 
*  Feed Stats Plugin by Jonathan Wilde
*  
*  Copyright © 2008-2009 Jonathan Wilde
*  Feed Stats uses the GNU General Public License, which can be found at:
*  
*  <http://www.gnu.org/licenses/>
*/

/**
* Parse feedburner xml for data
*/
function mightyreach_parse_feedburner ($xml) {
	preg_match_all('|reach="(.*?)"|', $xml, $entry_reach);
    preg_match_all('|date="(.*?)"|', $xml, $entry_date);
    preg_match_all('|circulation="(.*?)"|', $xml, $entry_subscribers);
    preg_match_all('|hits="(.*?)"|', $xml, $entry_hits);
    
    return array(
		'yesterday' =>  array(
			'date' => $entry_date[1][0], 
			'reach' => $entry_reach[1][0], 
			'subs' => $entry_subscribers[1][0], 
			'hits' => $entry_hits[1][0],
			),
		'today' =>  array(
			'date' => $entry_date[1][1], 
			'reach' => $entry_reach[1][1], 
			'subs' => $entry_subscribers[1][1], 
			'hits' => $entry_hits[1][1],
			),	
	);
}



/**
* Adds the Settings link to the plugin activate/deactivate page
*/
function mightyreach_filter_plugin_actions($links, $file) {
   $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
   array_unshift( $links, $settings_link ); // before other links
   return $links;
}


/**
* Adds settings/options page
*/
function mightyreach_admin_options_page() { 
	
	$options = mightyreach_get_options();
	
	if ($_POST['mightyreach_save']) {
		if (! wp_verify_nonce($_POST['_wpnonce'], 'mightyreach-update-options') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 
		$options['mightyreach_path'] = $_POST['mightyreach_path'];                   
		$options['mightyreach_allowed_groups'] = $_POST['mightyreach_allowed_groups'];

		foreach ($_POST as $key => $value) {
			if (stristr($key, 'mightyreach_twitter_username') && !empty($value)) {
				$options['mightyreach_twitter_username'] = $value;
			}
			if (stristr($key, 'mightyreach_feedburner_uri') && !empty($value)) {
				$options['mightyreach_feedburner_uri'] = $value;
			}
			if (stristr($key, 'mightyreach_clear_data') && !empty($value) && $value == 'on') {
				update_option('mightyreach_data', null);
			}
		}			

		update_option('mightyreach_options', $options);
		
		mightyreach_refresh_data();

		echo '<div class="updated"><p>Success! Your changes were successfully saved!</p></div>';
	} ?>
                               
	<div class="wrap">
		<h2>MightyReach Options</h2>
		<form method="post" id="mightyreach_options">
			<?php wp_nonce_field('mightyreach-update-options'); ?>
			<table width="60%" cellspacing="2" cellpadding="5" class="form-table"> 

				<tr>
					<th colspan=4>Enter your Twitter username and Feedburner URI:</th>
				</tr>

				<tr>
					<td colspan=4><em>Note: Your Feedburner URI is what follows feeds.feedburner.com in your RSS URL - http://feeds.feedburner.com/[uri]</em></td>
				</tr>

				<tr valign="bottom" style="border-bottom: 1px solid #ccc;">
					<th style="width: 5%; text-align: right"><label for="mightyreach_twitter_username">Twitter username:</label></th>
					<td style="width: 30%;"><input type="text" size="15" id="mightyreach_twitter_username" name="mightyreach_twitter_username" value="<? echo (!empty($options['mightyreach_twitter_username'])) ? $options['mightyreach_twitter_username'] : "";?>"></td>
             	</tr>
				<tr valign="bottom" style="border-bottom: 1px solid #ccc;">
					<th style="width: 5%; text-align: right"><label for="mightyreach_feedburner_uri">Feedburner URI:</label></th>
					<td style="width: 30%;"><input type="text" size="15" id="mightyreach_feedburner_uri" name="mightyreach_feedburner_uri" value="<? echo (!empty($options['mightyreach_feedburner_uri'])) ? $options['mightyreach_feedburner_uri'] : "";?>"></td>
				</tr>
				
				<tr valign="bottom" style="border-bottom: 1px solid #ccc;">
					<th style="width: 5%; text-align: right"><label for="mightyreach_clear_data">Clear data?</label></th>
					<td style="width: 30%;"><input type="checkbox" size="15" id="mightyreach_clear_data" name="mightyreach_clear_data"></td>
				</tr>

				<tr>
					<th colspan=4><input type="submit" name="mightyreach_save" value="Save" /></th>
				</tr>
			</table>
		</form>
<?php } 

?>