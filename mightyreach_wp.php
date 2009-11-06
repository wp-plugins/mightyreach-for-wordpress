<?php
/*
Plugin Name: MightyReach for WordPress
Plugin URI: http://mightybrand.com/blog/our-wordpress-plugins/
Description: MightyReach for WordPress displays your Google Analytics, Feedburner and Twitter stats on your dashboard
Author: MightyBrand
Version: 0.2
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
	
	if (!empty($options['mightyreach_googleanalytics_session_token']) && !empty($options['mightyreach_googleanalytics_id'])) {
		
	} else {
		$no_googleanalytics = true;
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
	
	if ($no_googleanalytics) {
		echo "<tr><td>Google Analytics:</td><td colspan=3><a href='$settings_link'>Click here to setup Google Analytics</a></td></tr>";
	} else {
		
		$ga_data = $data['googleanalytics'];
		
		echo '<tr><td colspan=4><hr/></td></tr>';
		echo '<tr style="text-align:left;">
			<th>Date</th>
			<th>Pageviews</th>
			<th>Visits</th>
			<th>New Visits</th>
		</tr>';
	
		$total_visits = $total_pageviews = 0;
		foreach($ga_data['days'] as $date => $values){
			echo '<tr>';
			echo '<td>'.$date.'</td>';
			echo '<td>'.$values['pageviews'].'</td>';
			echo '<td>'.$values['visits'].'</td>';
			echo '<td>'.$values['new_visits'].'</td>';
			echo '</tr>';
		}
		
		echo '<tr style="font-weight:bold;">';
		echo '<td>Totals:</td>';
		echo '<td>'.$ga_data['total_pageviews'].'</td>';
		echo '<td>'.$ga_data['total_visits'].'</td>';
		echo '<td>'.$ga_data['total_new_visits'].'</td>';
		echo '</tr>';
		
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
		
		/*
		*  NOTE: Feedburner's midnight is CDT (-5 GMT) so we get the GMT times here and adjust back
		*  by five hours so these switch to the next day anywhere in the world.
		*/ 
		$yesterday_date = gmdate('Y-m-d', time()-(60*60*24)-(60*60*5));
		$day_before_yesterday_date = gmdate('Y-m-d', time()-(60*60*24*2)-(60*60*5));
	 	$host = 'feedburner.google.com';
		$path = '/api/awareness/1.0/GetFeedData?uri=' . $options['mightyreach_feedburner_uri'] . '&dates=' . $day_before_yesterday_date . ',' . $yesterday_date;		
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
	
	
	// Get Google Analytics data
	
	$ga_data = array();
	if (!empty($options['mightyreach_googleanalytics_session_token'])) {
		// Get list of accounts
		$accountxml = mightyreach_make_api_call("https://www.google.com/analytics/feeds/accounts/default", $options['mightyreach_googleanalytics_session_token']);

		// Get an array with the available accounts
		$profiles = mightyreach_parse_account_list($accountxml);
		
		$ga_data['profiles'] = $profiles;
	}
	
	if (!empty($options['mightyreach_googleanalytics_id'])) {
		
		function check_for_accountid($profiles) {
			$options = mightyreach_get_options();
			return ($profiles['webPropertyId'] == $options['mightyreach_googleanalytics_id']);
		}

		$profile = array_pop(array_filter( $profiles, 'check_for_accountid' ));
		
		$ga_data['account_name'] = $profile['accountName'];
		$ga_data['site_title'] = $profile['title'];
		
		// For each profile, get number of pageviews
		$requrl = "https://www.google.com/analytics/feeds/data?ids=";
		$requrl .= $profile['tableId']."&dimensions=ga:date&metrics=ga:pageviews,ga:visits,ga:newVisits&sort=ga:date";
		$requrl .= "&start-date=".date("Y-m-d", strtotime("-1 week"))."&end-date=".date("Y-m-d")."&start-index=1&max-results=30&prettyprint=false";
		
		$pagecountxml = mightyreach_make_api_call($requrl, $options['mightyreach_googleanalytics_session_token']);

		$ga_data['total_pageviews'] = 0;
		$ga_data['total_visits'] = 0;
		
		foreach (mightyreach_reportObjectMapper($pagecountxml) as $result) {
			$ga_data['days'][date("D M j, y", strtotime($result['date']))] = array( 
				"pageviews" => $result['pageviews'],
				"visits" => $result['visits'],
				"new_visits" => $result['newVisits']);
				
			$ga_data['total_pageviews'] += $result['pageviews'];
			$ga_data['total_visits'] += $result['visits'];
			$ga_data['total_new_visits'] += $result['newVisits'];
		}
		
	}
	
	$data['googleanalytics'] = $ga_data;
	
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
* Google Analytics Functions
* Special thanks to the following for their direction and sample
* code for this project:
* 
* gapi-google-analytics-php-interface
* GAPI - Google Analytics API PHP Interface
* http://code.google.com/p/gapi-google-analytics-php-interface/
* 
* Alex Curelea’s Dev Log - Using the Google Analytics API - getting total number of page views
* http://www.alexc.me/using-the-google-analytics-api-getting-total-number-of-page-views/74/
*/

/**
* Retrieve full URL for return path after Google Authorization
*/
function mightyreach_full_url() {
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	$protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
	return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
}

/**
* Retrieve session token for further Google Authorization
*/
function mightyreach_get_session_token($onetimetoken) {
	$output = mightyreach_make_api_call("https://www.google.com/accounts/AuthSubSessionToken", $onetimetoken);
			
	if (preg_match("/Token=(.*)/", $output, $matches))
	{
		$sessiontoken = $matches[1];
	}
	
	return $sessiontoken;
}

/**
* The API curl call to retrieve data
*/
function mightyreach_make_api_call($url, $token) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$curlheader[0] = sprintf("Authorization: AuthSub token=\"%s\"/n", $token);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $curlheader);
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

/**
* Parse the account list in search for the correct account ID
*/
function mightyreach_parse_account_list($xml) {
	$doc = new DOMDocument();
	$doc->loadXML($xml);
	$entries = $doc->getElementsByTagName('entry');
	$i = 0;
	$profiles = array();
	foreach($entries as $entry)
	{
		$profiles[$i] = array();
		
		$title = $entry->getElementsByTagName('title');
		$profiles[$i]["title"] = $title->item(0)->nodeValue;
		
		$entryid = $entry->getElementsByTagName('id');
		$profiles[$i]["entryid"] = $entryid->item(0)->nodeValue;
		
		$properties = $entry->getElementsByTagName('property');
		foreach($properties as $property)
		{
			if (strcmp($property->getAttribute('name'), 'ga:accountId') == 0)
				$profiles[$i]["accountId"] = $property->getAttribute('value');
			
			if (strcmp($property->getAttribute('name'), 'ga:accountName') == 0)
				$profiles[$i]["accountName"] = $property->getAttribute('value');
			
			if (strcmp($property->getAttribute('name'), 'ga:profileId') == 0)
				$profiles[$i]["profileId"] = $property->getAttribute('value');
			
			if (strcmp($property->getAttribute('name'), 'ga:webPropertyId') == 0)
				$profiles[$i]["webPropertyId"] = $property->getAttribute('value');
		}
		
		$tableId = $entry->getElementsByTagName('tableId');
		$profiles[$i]["tableId"] = $tableId->item(0)->nodeValue;
		
		$i++;
	}
	return $profiles;
}

/**
* Returns the parsed analytics data
*/
function mightyreach_reportObjectMapper($xml_string) {
	$xml = simplexml_load_string($xml_string);

	$results = array();

	$report_root_parameters = array();
	$report_aggregate_metrics = array();

	//Load root parameters

	$report_root_parameters['updated'] = strval($xml->updated);
	$report_root_parameters['generator'] = strval($xml->generator);
	$report_root_parameters['generatorVersion'] = strval($xml->generator->attributes());

	$open_search_results = $xml->children('http://a9.com/-/spec/opensearchrss/1.0/');

	foreach($open_search_results as $key => $open_search_result) {
  		$report_root_parameters[$key] = intval($open_search_result);
	}

	$google_results = $xml->children('http://schemas.google.com/analytics/2009');

	foreach($google_results->dataSource->property as $property_attributes) {
  		$report_root_parameters[str_replace('ga:','',$property_attributes->attributes()->name)] = strval($property_attributes->attributes()->value);
	}

	$report_root_parameters['startDate'] = strval($google_results->startDate);
	$report_root_parameters['endDate'] = strval($google_results->endDate);

	//Load result aggregate metrics

	foreach($google_results->aggregates->metric as $aggregate_metric) {
  		$metric_value = strval($aggregate_metric->attributes()->value);

  		//Check for float, or value with scientific notation
	  	if(preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/',$metric_value)) {
	    	$report_aggregate_metrics[str_replace('ga:','',$aggregate_metric->attributes()->name)] = floatval($metric_value);
	  	} else {
	    	$report_aggregate_metrics[str_replace('ga:','',$aggregate_metric->attributes()->name)] = intval($metric_value);
	  	}
	}

	//Load result entries

	foreach($xml->entry as $entry) {
  		$metrics = array();
  		foreach($entry->children('http://schemas.google.com/analytics/2009')->metric as $metric) {
    		$metric_value = strval($metric->attributes()->value);

		    //Check for float, or value with scientific notation
		    if(preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/',$metric_value)) {
      			$metrics[str_replace('ga:','',$metric->attributes()->name)] = floatval($metric_value);
    		} else {
      			$metrics[str_replace('ga:','',$metric->attributes()->name)] = intval($metric_value);
    		}
  		}

  		$dimensions = array();
  		foreach($entry->children('http://schemas.google.com/analytics/2009')->dimension as $dimension) {
    		$dimensions[str_replace('ga:','',$dimension->attributes()->name)] = strval($dimension->attributes()->value);
  		}

  		$results[] = array_merge($metrics,$dimensions);
	}

	return $results;
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
	
	// Saves the Google Session AuthSub token
	if (!empty($_REQUEST['token']) && !isset($options['mightyreach_googleanalytics_session_token'])) {
		
		$options['mightyreach_googleanalytics_session_token'] = mightyreach_get_session_token($_REQUEST['token']);
		$options['mightyreach_googleanalytics_authsub_token'] = $_REQUEST['token'];
		
		update_option('mightyreach_options', $options);
		
		mightyreach_refresh_data();
		
		echo '<div class="updated"><p>Success! You have successfully authenticated your Google Analytics Account!</p></div>';
	}
	
	$data = mightyreach_get_data();
	
	// check if they have revoked access to GA
	if (!empty($options['mightyreach_googleanalytics_session_token']) && !empty($options['mightyreach_googleanalytics_id'])) {
		$return = mightyreach_make_api_call("https://www.google.com/analytics/feeds/accounts/default", $options['mightyreach_googleanalytics_session_token']);
		if (strpos($return, 'Error 401')) {
			unset($options['mightyreach_googleanalytics_session_token']);
			unset($options['mightyreach_googleanalytics_authsub_token']);
			unset($options['mightyreach_googleanalytics_id']);
			unset($data['googleanalytics']['profiles']);
			update_option('mightyreach_options', $options);
			update_option('mightyreach_data', serialize($data));
		}
	}
	
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
			if (stristr($key, 'mightyreach_googleanalytics_id') && !empty($value)) {
				$options['mightyreach_googleanalytics_id'] = $value;
			}
			if (stristr($key, 'mightyreach_clear_data') && !empty($value) && $value == 'on') {
				update_option('mightyreach_data', null);
				$options = array();
			}
		}			

		update_option('mightyreach_options', $options);
		
		mightyreach_refresh_data();

		echo '<div class="updated"><p>Success! Your changes were successfully saved!</p></div>';
	} 
	
	?>
                               
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
					<?php if (empty($options['mightyreach_googleanalytics_session_token'])): ?>
						<th style="width: 5%; text-align: right"><label for="mightyreach_googleanalytics_id">Google Analytics</label></th>
						<td style="width: 30%;">
							<a href="https://www.google.com/accounts/AuthSubRequest?next=<?php echo mightyreach_full_url(); ?>&scope=https://www.google.com/analytics/feeds/&secure=0&session=1">Click here to authenticate your Google Analytics Account.</a>
						</td>
					<?php else: ?>
						<th style="width: 5%; text-align: right"><label for="mightyreach_googleanalytics_id">Google Analtics Account</label></th>
						<td style="width: 30%;">
							<select name="mightyreach_googleanalytics_id">
							<?php foreach ($data['googleanalytics']['profiles'] as $profile): ?>
								<?php $selected = ($profile['webPropertyId'] == $options['mightyreach_googleanalytics_id']) ? 'selected="selected"' : ''; ?>
								<option value="<?php echo $profile['webPropertyId'] ?>"<?php echo $selected ?>>
									<?php echo $profile['title'] ?>
								</option>
							<?php endforeach ?>
							</select>
							&nbsp;
							<a href="https://www.google.com/accounts/RevokeAuthSubAccess?authsub_tokens=<?php echo $options['mightyreach_googleanalytics_session_token'] ?>&amp;authsub_target_label=<?php echo $_SERVER['HTTP_HOST']; ?>&amp;authsub_scope_label=Google+Analytics">Revoke Google Analytics Access</a>
						</td>
					<?php endif ?>
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