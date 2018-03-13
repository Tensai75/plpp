<?php


// Defining constants
define('PLPP_PATH', '');
define('PLPP_INCLUDE_PATH', PLPP_PATH.'php/');
define('PLPP_CONFIGURATION_PATH', PLPP_PATH.'config/');
define('PLPP_LANGUAGES_PATH', PLPP_PATH.'languages/');
define('PLPP_CSS_PATH', PLPP_PATH.'css/');
define('PLPP_JS_PATH', PLPP_PATH.'js/');
define('PLPP_TEMPLATES_PATH', PLPP_PATH.'templates/');
define('PLPP_FONTS_PATH', PLPP_PATH.'fonts/');
define('PLPP_IMGCACHE_PATH', PLPP_PATH.'cache/');
define('PLPP_BASE_PATH', $_SERVER['SCRIPT_NAME']);


// Redirect to settings page if general.json does not exist (usually in case of first run after installation)
if (!file_exists(PLPP_CONFIGURATION_PATH.'general.json')) {
	header('Location: settings.php');
	die;
}


// Defining runtime variables and setting standard details
$plppConfiguration = array(
	'general' => array(),
	'plexserver' => array(),
	'libraries' => array(),
	'usersettings' => array(),
	'mediatypes' => array()
);
$plppErrors = array();
$plppItems = array();
$plppOutput = array(
	'Title' => '',
	'Errors' => '',
	'Menu' => '',
	'Content' => '',
	'Include' => '',
	'ScriptCode' => '',
	'IncludeJS' => '',
	'IncludeCSS' => '',
);
$plppViewmode = 'thumbs';
$plppImageWidth = 160;
$plppIsModal = false;
$plppLibraryTypes = array();


// Include functions and classes
include(PLPP_INCLUDE_PATH.'plpp.functions.php');
include(PLPP_INCLUDE_PATH.'plpp.classes.php');
include(PLPP_INCLUDE_PATH.'class.plexAPI.php');


// Load configuration files
$plppConfiguration = json_load($plppConfiguration, PLPP_CONFIGURATION_PATH, '');
foreach ($plppConfiguration as $key => $value) {
	if (empty($plppConfiguration[$key])) {
		$plppErrors[] = 'Unable to load configuration file "'.PLPP_CONFIGURATION_PATH.$key.'.json"!';
	}
	else {
		// Something to do here?
	}
}


// Generate warning message if plex server is not configured
if (
	empty($plppConfiguration['plexserver']['domain']) ||
	empty($plppConfiguration['plexserver']['username']) ||
	empty($plppConfiguration['plexserver']['password'])
	) {
	$plppErrors[] = 'Plex Server not configured. Please configure <a href="settings.php"><u>settings</u></a> first!';
}


// Setting Error level
if ($plppConfiguration['usersettings']['debug']) {
	error_reporting(-1);
	ini_set("display_errors", 1);
	// Register error handler
	set_error_handler("error_handler");
	set_exception_handler("error_handler");
	register_shutdown_function("error_handler");
}
else {
	error_reporting(0);
	ini_set('display_errors','Off'); 
}


// Set the default viewmode from the configuration file
$plppViewmode = $plppConfiguration['libraries']['default_viewmode'];


// Start Session
session_start();


// Initiate the plexAPI class and request the token if not already set in session variable (speeds up image delivery)
$plppConfiguration['plexserver']['token'] = $_SESSION['token'];
$plex = new plexAPI($plppConfiguration['plexserver'], $plppConfiguration['general']);
if (empty($plex->getToken())) {
	$plppErrors[] = 'No token received! Plex.tv not reachable or wrong credentials!';
}
else {
	$_SESSION['token'] = $plex->getToken();
	$plppConfiguration['plexserver']['token'] = $plex->getToken();
}


// Setting the excluded libraries
$plex->setExcludedLibraries($plppConfiguration['libraries']['excluded_libraries']);


// Setting the Item variable from GET
if (isset($_GET['item'])) {
	$plppItem = (int)$_GET['item'];
	$plppItemsQueryString['item'] = $plppItem;
}


// Setting the Viewmode variable either from GET or SESSION
if (isset($_GET['viewmode'])) {
	$plppViewmode = $_GET['viewmode'];
}
else { 
	if (isset($_SESSION['viewmode'])) {
		$plppViewmode = $_SESSION['viewmode'];
	}
}
// If viewmode is thumbs or list we save it in the session
if ($plppViewmode == 'thumbs' || $plppViewmode == 'list') {
		$_SESSION['viewmode'] = $plppViewmode;
}
// If no item specified, we show the slider view
if (empty($plppItem)) {
	$plppViewmode = 'slider';
}


// Setting the Type variable from GET
if (isset($_GET['type'])) {
	$plppItemType = $_GET['type'];
	$plppItemsQueryString['type'] = $plppItemType;
}


// Setting the Filter variable from GET
if (isset($_GET['filter'])) {
	$plppItemsFilter = $_GET['filter'];
	$plppItemsQueryString['filter'] = $plppItemsFilter;
}


// Setting the Search variable from GET
if (isset($_GET['search'])) {
	$plppIsSearch = true;
	$plppItemsQueryString['search'] = $plppIsSearch;
	$plppItemsQueryString['viewgroup'] = $_GET['viewgroup'];
}


// Setting the Query variable from GET
if (isset($_GET['query'])) {
	$plppItemsQuery = $_GET['query'];
	$plppItemsQueryString['query'] = urldecode($plppItemsQuery);
}


// Setting the ImageFileName variable from GET
if (isset($_GET['filename'])) {
	$plppImageFileName = $_GET['filename'];
}


// Setting the Thumb variable from GET
if (isset($_GET['thumb'])) {
	$plppThumbID = (int)$_GET['thumb'];
}


// Setting the IsModal variable from GET
if (isset($_GET['modal'])) {
	$plppIsModal = true;
}


// Close session because it is no longer needed
session_write_close();


// Check if images can be cached
if (!is_writable(PLPP_IMGCACHE_PATH) && $plppConfiguration['usersettings']['cache_images']) {
	$plppErrors[] = PLPP_IMGCACHE_PATH.' must be writable in order to cache the images!';
}

// If Viewmode is img serve image and end script (Images can be cached to speed up image delivery)
if ($plppViewmode == 'img') {
	
	//Get image aspect ration for thumb from mediatypes setting and calculate height of the thumb
	$plppImageHeight = $plppImageWidth / eval('return '.$plppConfiguration['mediatypes'][$plppItemType]['imageAspectRatio'].';');
	
	// Construct the path and filename for the thumb
	$plppImageFile = realpath(dirname(__FILE__)).'/'.PLPP_IMGCACHE_PATH.md5($plppItem.'_'.$plppImageWidth).'.jpg';
	
	// If the file exists, image refresh is disabled and caching is enabled, load the file from the cache and serve it to the browser
	if (file_exists($plppImageFile) && !$plppConfiguration['usersettings']['refresh_images'] && $plppConfiguration['usersettings']['cache_images'] && $plppItemsFilter != 'full') {
		$image = imagecreatefromjpeg($plppImageFile);
		header("Content-type: image/jpeg");
		header('Content-Disposition: inline; filename="thumb_'.$plppImageFileName.'.jpg"');
		imagejpeg($image);
	}
	// In all other cases, we load the image from the plex server and process it
	else {
		// If full image is requested (for photo detail) we load the biggest image possible, otherwise we request the image in the thumbs width
		$plppImageLoadWidth = ($plppItemsFilter == 'full') ? '9999' : $plppImageWidth ;;
		
		// Construct the image URL
		$plppImageURL = $plppConfiguration['plexserver']['scheme'].'://'.$plppConfiguration['plexserver']['domain'].':'.$plppConfiguration['plexserver']['port'].'/photo/:/transcode?url=/library/metadata/'.$plppItem.'/thumb/'.$plppThumbID.'&width='.$plppImageLoadWidth.'&height=9999&X-Plex-Token='.$plex->getToken();


		// For debug only: If GET parameter url is set, we show the URL and filename and -path instead of the image
		if (isset($_GET['url'])) {
			print '$plppImageURL = '.$plppImageURL.'<br />';
			print '$plppImageFile = '.$plppImageFile;
			die;
		}

		// Call the image load function
		$plppImage = createImageFromURL($plppImageURL);
		
		// We only process the image if it is a thumb and not a full image
		if ($plppItemsFilter != 'full') {

			// Get original width and height
			$plppImageOriginalWidth = imagesx($plppImage);
			$plppImageOriginalHeight = imagesy($plppImage);

			// Calculate original aspect and thumb aspect
			$plppImageOriginalAspect = $plppImageOriginalWidth / $plppImageOriginalHeight;
			$plppThumbAspect = $plppImageWidth / $plppImageHeight;

			// If it is photo, we fit the photo into the thumb
			if ($plppItemType == 'photo') {
				// If thumbnail is wider than image (in aspect ratio sense)
				if ( $plppImageOriginalAspect <= $plppThumbAspect )
				{
					// Calculate new height and width
					$plppThumbNewlHeight = $plppImageHeight;
					$plppThumbNewlWidth = $plppThumbNewlHeight * $plppImageOriginalAspect;
				}
				// If the image is wider than the thumbnail
				else
				{
					// Calculate new height and width
					$plppThumbNewlWidth = $plppImageWidth;
					$plppThumbNewlHeight = 	$plppThumbNewlWidth / $plppImageOriginalAspect;
				}
			}
			// If it is not a photo, we resize and crop the image in order to fill up the whole thumb
			else {
				// If image is wider than thumbnail (in aspect ratio sense)
				if ( $plppImageOriginalAspect >= $plppThumbAspect )
				{
					// Calculate new height and width
					$plppThumbNewlHeight = $plppImageHeight;
					$plppThumbNewlWidth = $plppImageOriginalWidth / ($plppImageOriginalHeight / $plppImageHeight);
				}
				// If the thumbnail is wider than the image
				else
				{
				   // Calculate new height and width
				   $plppThumbNewlWidth = $plppImageWidth;
				   $plppThumbNewlHeight = $plppImageOriginalHeight / ($plppImageOriginalWidth / $plppImageWidth);
				}			
			}
			// Create blank thumb
			$plppThumb = imagecreatetruecolor( $plppImageWidth, $plppImageHeight );
			$plppThumbBackgroundColor = imagecolorallocate($plppThumb, 34, 34, 34);
			imagefill($plppThumb, 0, 0, $plppThumbBackgroundColor);
		
			// Resize and crop the image into the thumb
			imagecopyresampled($plppThumb,
				$plppImage,
				0 - ($plppThumbNewlWidth - $plppImageWidth) / 2, // Center the image horizontally
				0 - ($plppThumbNewlHeight - $plppImageHeight) / 2, // Center the image vertically
				0, 0,
				$plppThumbNewlWidth, $plppThumbNewlHeight,
				$plppImageOriginalWidth, $plppImageOriginalHeight);
			
			// Serve the thumb to the browser			
			header('Content-Type: image/jpeg');
			header('Content-Disposition: inline; filename="thumb_'.$plppImageFileName.'.jpg"');
			imagejpeg($plppThumb);
			
			// If caching is enabled, save the thumb to the cache folder
			if (is_writable(PLPP_IMGCACHE_PATH) && $plppConfiguration['usersettings']['cache_images']) {
				imagejpeg($plppThumb, $plppImageFile);
			}
			// Destroy the thumb
			imagedestroy($plppThumb);
		}
		// Serve the full image to the browser
		else {
			header('Content-Type: image/jpeg');
			header('Content-Disposition: inline; filename="'.$plppImageFileName.'.jpg"');
			imagejpeg($plppImage);
		}
	}
	// Destroy the image and end
	imagedestroy($plppImage);
	die;
}


// Getting the xml for the plex library index
$plppIndex = $plex->getIndex();
if (empty($plppIndex)) {
	$plppErrors[] = 'Could not get list of libraries! Is the Plex Server online and are the plex server settings configured properly?';
}
// Sort the library index
usort($plppIndex['items'], make_comparer([$plppConfiguration['libraries']['sort_by'], constant($plppConfiguration['libraries']['sort_order'])], ['title', SORT_ASC]));

// Creating the plex library menu
foreach($plppIndex['items'] AS $child) {
	$plppOutput['Menu'] .= '<li class="plpp_menu';
	if ($child['key'] == $plppLibrarySectionID) {
		$plppOutput['Menu'] .= ' plpp_menu_selected selected';
	}
	$plppOutput['Menu'] .= '"><a href="'.PLPP_BASE_PATH.'?item='.$child['key'].'&type=library">';
	$plppOutput['Menu'] .= '<span class="plpp_menu plpp_menu_'.$child['type'].'">'.$child['title'].'</span></a></li>'.PHP_EOL;
	if (!in_array($child['type'],$plppLibraryTypes)) {
		$plppLibraryTypes[] = $child['type'];
	}
}
// Add link for Search
if ($plppConfiguration['usersettings']['search_link']) {$plppOutput['Menu'] .= '<li class="plpp_menu"><a href="#" data-target="#myPlexModalSearch"><span class="plpp_menu plpp_menu_search">Search...</span></a></li>'.PHP_EOL;}
// Add link for Settings
if ($plppConfiguration['usersettings']['settings_link']) {$plppOutput['Menu'] .= '<li class="plpp_menu"><a href="settings.php"><span class="plpp_menu plpp_menu_settings">Settings</span></a></li>'.PHP_EOL;}
// Add link for debug data
if ($plppConfiguration['usersettings']['debug']) {$plppOutput['Menu'] .= '<li class="plpp_menu"><a href="#" data-target="#myPlexModalDebug"><span class="plpp_menu plpp_menu_debug">Show Debug Data</span></a></li>'.PHP_EOL;}


// Generating the ItemTypes variable
if (in_array('movie',$plppLibraryTypes)) {
		$plppItemTypes[] = 'movie';
}
if (in_array('show',$plppLibraryTypes)) {
		$plppItemTypes[] = 'show';
		$plppItemTypes[] = 'season';
		$plppItemTypes[] = 'episode';
}
if (in_array('artist',$plppLibraryTypes)) {
		$plppItemTypes[] = 'artist';
		$plppItemTypes[] = 'album';
		$plppItemTypes[] = 'track';
}
if (in_array('photo',$plppLibraryTypes)) {
		$plppItemTypes[] = 'photoalbum';
		$plppItemTypes[] = 'photo';
}

// Loading the plex xml
// If the type is search, we have a search request
if ($plppIsSearch) {
	if ($plppItemType == 'library') {
		$plppItems[$plppItemType] = $plex->search($plppItem, $_GET['viewgroup'], $plppItemsQuery);
	}
	else if (!empty($plppItemType)) {
		$plppItem = '';
		$plppItems[$plppItemType] = $plex->search($plppItem, $plppItemType, $plppItemsQuery);
	}
	else {
		$plppItem = '';
		$types = array('movie', 'show', 'episode', 'artist', 'album', 'track', 'photoalbum', 'photo');
		foreach ($types as $type) {
			if (in_array($type, $plppItemTypes)) {
				$plppItems[$type] = $plex->search($plppItem, $type, $plppItemsQuery);
			}
		}
	}
	$plppViewmode = $_SESSION['viewmode'];
}
else {
	// If there is no item defined, we are on the start page and load the recently added items
	if (empty($plppItem)) {
		$plppItems['movie'] = $plex->getRecentlyAdded('movie');
		$plppItems['season'] = $plex->getRecentlyAdded('season');
		$plppItems['photo'] = $plex->getRecentlyAdded('photo');
		$plppItems['artist'] = $plex->getRecentlyAdded('artist');	
	}
	// Else we load the requested item
	else {
		$plppItems[$plppItemType] = $plex->getItems($plppItem, $plppItemType, $plppItemsFilter, $plppItemsQuery);
	}
}

// Check if we got some data and if not throw an error message
// If there are no items or no data at all, we unset the variable so nothing will be displayed
Foreach ($plppItems as $key => $value) {
	if (empty($value)) {
		$plppErrors[] = 'Could not get list of '.$key.'s! Is the Plex Server online and are the plex server setting correct??';
		unset($plppItems[$key]);
	}
	if (empty($value['items'])) {
		unset($plppItems[$key]);
	}
}


// Setting the librarySectionID variable
$plppLibrarySectionID = $plppItems[$plppItemType]['librarySectionID'];


// Checking whether requested item is in the excluded libraries array
if (in_array($plppLibrarySectionID,$plppConfiguration['libraries']['excluded_libraries'])) {
	// If yes we throw an error message and load the start page
	$plppErrors[] = 'Requested '.$plppItemType.' is not available!';
	unset($plppItems[$plppItemType]);
	$plppItem = '';
	$plppItemType = 'library';
	$plppViewmode = 'slider';
	$plppItems['movie'] = $plex->getRecentlyAdded('movie');
	$plppItems['season'] = $plex->getRecentlyAdded('season');
	$plppItems['photo'] = $plex->getRecentlyAdded('photo');
	$plppItems['artist'] = $plex->getRecentlyAdded('artist');	
	Foreach ($plppItems as $key => $value) {
		if (empty($value)) {
			$plppErrors[] = 'Could not get list of '.$key.'s! Is the Plex Server online and are the plex server setting correct??';
			unset($plppItems[$key]);
		}
		if (empty($value['items'])) {
			unset($plppItems[$key]);
		}
	}
}


// Add the header sections

// By default we include the trunc8 jQuery javascript (even if it is not used on all pages)
$plppOutput['IncludeJS'] .= '	<script src="'.PLPP_JS_PATH.'trunk8.min.js"></script>'.PHP_EOL;

switch ($plppViewmode) {
	
	// For the slider viewmode we include the bxslider jQuery javascript
	case 'slider': {
		$plppOutput['IncludeJS'] .= '	<script src="'.PLPP_JS_PATH.'jquery.bxslider.min.js"></script>'.PHP_EOL;
		$plppOutput['ScriptCode'] .= '	<script>'.PHP_EOL;
		$plppOutput['ScriptCode'] .= '		$(document).ready(function() {'.PHP_EOL;
		break;
	}
	// For the list viewmode we include the DataTables jQuery javascript
	case 'list': {
		$plppOutput['IncludeCSS'] .= '	<link rel="stylesheet" type="text/css" href="'.PLPP_CSS_PATH.'datatables.min.css"/>'.PHP_EOL;
		$plppOutput['IncludeJS'] .= '	<script src="'.PLPP_JS_PATH.'datatables.min.js"></script>'.PHP_EOL;
		$plppOutput['ScriptCode'] .= '	<script>'.PHP_EOL;
		$plppOutput['ScriptCode'] .= '		$(document).ready(function() {'.PHP_EOL;
		break;
	}
	// In any other case we include just the script header for the modal and trunk8 javascript
	default: {
		$plppOutput['ScriptCode'] .= '	<script>'.PHP_EOL;
	}
}

// If we show photos, we include the lightbox javascript and css
if ($plppItems[$plppItemType]['viewGroup'] == 'photo') {
	$plppOutput['IncludeJS'] .= '	<script src="'.PLPP_JS_PATH.'lightbox.min.js"></script>'.PHP_EOL;
	$plppOutput['IncludeCSS'] .= '	<link rel="stylesheet" type="text/css" href="'.PLPP_CSS_PATH.'lightbox.min.css" />'.PHP_EOL;
}

// Include font awesome and plpp css
$plppOutput['IncludeCSS'] .= '	<link rel="stylesheet" type="text/css" href="'.PLPP_CSS_PATH.'font-awesome.min.css" />'.PHP_EOL;
$plppOutput['IncludeCSS'] .= '	<link rel="stylesheet" type="text/css" href="'.PLPP_CSS_PATH.'plpp.css" />'.PHP_EOL;


// Generate the content
foreach ($plppItems as $parentKey => $parent) {
	
	// Set the type of content
	$plppViewgroupType = (!empty($parent['viewGroup'])) ? $parent['viewGroup'] : $parentKey ;;
	if ($plppIsSearch) {
		$plexKey = $parentKey;
	}
	else {
		$plexKey = (empty($plppItem)) ? $plppViewgroupType : $plppItem ;;
	}
	
	// Start of the content output container
	$plppOutput['Content'] .= '<div class="panel panel-default plpp_panel">'.PHP_EOL;
	
	// Start of the content header container
	$plppOutput['Content'] .= '	<div class="panel-heading plpp_panel-heading">'.PHP_EOL;
	
	//Start of the viewmode and slider naviagtion container
	$plppOutput['Content'] .= '		<div class="plpp_navigation_container">'.PHP_EOL;	
	
	// Add slider navigation if in slider viewmode
	if ($plppViewmode == 'slider') {
		$plppOutput['Content'] .= '			<div class="plpp_bxslide_navigation">'.PHP_EOL;
		$plppOutput['Content'] .= '				<span class="plpp_bxslide_prev_'.$plppViewgroupType.'"></span>&nbsp;&nbsp;'.PHP_EOL;
		$plppOutput['Content'] .= '				<span class="plpp_bxslide_next_'.$plppViewgroupType.'"></span>'.PHP_EOL;
		$plppOutput['Content'] .= '			</div>'.PHP_EOL;
	}		
	
	// Add links to change the viewmode
	else if ($plppViewmode != 'details') {
		$plppOutput['Content'] .= '			<div class="plpp_viewmode_navigation">'.PHP_EOL;
		$plppOutput['Content'] .= '				<a href="'.PLPP_BASE_PATH.'?'.http_build_query($plppItemsQueryString).'&viewmode=list"><i class="fa fa-bars fa-lg"></i></a>&nbsp;&nbsp;'.PHP_EOL;
		$plppOutput['Content'] .= '				<a href="'.PLPP_BASE_PATH.'?'.http_build_query($plppItemsQueryString).'&viewmode=thumbs"><i class="fa fa-th fa-lg"></i></a>&nbsp;&nbsp;'.PHP_EOL;
		$plppOutput['Content'] .= '			</div>'.PHP_EOL;
	}
	
	// End of navigation container
	$plppOutput['Content'] .= '		</div>'.PHP_EOL;
	
	// Start of the breadcrumb container
	$plppOutput['Content'] .= '		<div class="plpp_breadcrumb_container">'.PHP_EOL;	
	$plppOutput['Content'] .= '			<ul class="breadcrumb plpp_breadcrumb">'.PHP_EOL;
	
	// Add the home icon
	$plppOutput['Content'] .= '				<li><a href="'.PLPP_BASE_PATH.'"><i class="fa fa-home fa-lg"></i></a></li>'.PHP_EOL;
	
	// Is there a librarySectionTitle? If yes it is the first part of the breadcrumb. It is always of type "library"
	if (!empty($parent['librarySectionTitle'])) {
		$plppOutput['Content'] .= '				<li><a href="'.PLPP_BASE_PATH.'?item='.$parent['librarySectionID'].'&type=library">'.$parent['librarySectionTitle'].'</a></li>'.PHP_EOL;
	}
	
	// Is there a title1?
	if (!empty($parent['title1'])) {
		// If librarySectionTitle is empty title1 it is the link to the current item
		if (empty($parent['librarySectionTitle'])) {
			$plppOutput['Content'] .= '				<li><a href="'.PLPP_BASE_PATH.'?item='.$plppItem.'&type='.$plppItemType.'">'.$parent['title1'].'</a></li>'.PHP_EOL;
		}
		// If title1 is not the same as librarySectionTitle it is the linke to the grandparent
		else if ($parent['librarySectionTitle'] != $parent['title1']) {
			$plppOutput['Content'] .= '				<li><a href="'.PLPP_BASE_PATH.'?item='.$parent['grandparentRatingKey'].'&type='.$parent['grandparentType'].'">'.$parent['title1'].'</a></li>'.PHP_EOL;				
		}
	}
	
	// If there is a title2 it is always the title of the active view.
	if (!empty($parent['title2'])) {
		// But if it is a library, we generate the filter dropdwon menu
		if ($plppItemType == 'library' && $plppItem != '') {
			$filters = $plex->getFilters($plppItem);
			asort($filters);
			$plppOutput['Content'] .= '				<li class="plpp_dropdown dropdown active">'.PHP_EOL;
			// First we have to find the active filter view and set it as title of the dropdwon menu
			foreach ($filters as $key => $value) {
				if ($plppItemsFilter == $key || ($plppItemsFilter == '' && $key == 'all')) {
					$plppOutput['Content'] .= '					'.$parent['title2'].' ('.count($parent['items']).')&nbsp;&nbsp;<a href="" class="plpp_dropdown-toggle dropdown-toggle" data-toggle="dropdown"><i class="caret"></i></a>'.PHP_EOL;
				}
			}
			$plppOutput['Content'] .= '					<ul class="plpp_dropdown-menu dropdown-menu">'.PHP_EOL;
			// Then we add the other filters except the active filter as dropdown items
			foreach ($filters as $key => $value) {
				if ($plppItemsFilter != $key && (!($plppItemsFilter == '' && $key == 'all') || $plppIsSearch)) {
					$plppOutput['Content'] .= '						<li><a href="'.PLPP_BASE_PATH.'?item='.$plppItem.'&type=library&filter='.$key.'">'.$value.'</a></li>'.PHP_EOL;
				}
			}
			$plppOutput['Content'] .= '					</ul>'.PHP_EOL;
			$plppOutput['Content'] .= '				</li>'.PHP_EOL;
		}
		else {
			$plppOutput['Content'] .= '				<li class="active">'.$parent['title2'].' ('.count($parent['items']).')</li>'.PHP_EOL;
		}
	}

	// End of breadcrumb
	$plppOutput['Content'] .= '			</ul>'.PHP_EOL;
	$plppOutput['Content'] .= '		</div>'.PHP_EOL;

	// End of header
	$plppOutput['Content'] .= '	</div>'.PHP_EOL;
	
	// Creating the content body
	$plppOutput['Content'] .= '	<div class="panel-body plpp_panel-body">'.PHP_EOL;	

	// Generate season, episode & artist details output if we are not in slider viewmode and if it is not a search
	if (($plppConfiguration['mediatypes'][$plppViewgroupType]['showDetails']) && $plppViewmode != 'slider' && ($plppItemsFilter != 'albums' && $plppItemsFilter != 'recentlyAdded') && !$plppIsSearch) {
		foreach ($plppConfiguration['mediatypes'][$plppViewgroupType]['itemList'] as $item) {
			if (in_array('details',$item['visibility'])) {
					$plppItems['details'][$item['name']] = $plex->getFormatedItemsContent(0, $item['type'], $item['content'], $item['content_type'], $plexKey);
			}
		}
		$plppOutput['Content'] .= plpp_templates($plppItems, $plppViewgroupType, 'details');
	}

	
	// If we are in list viewmode we need to generate the table header
	if ($plppViewmode == 'list') {
		
		// Start of the table
		$plppOutput['Content'] .= '		<table class="table table-condensed table-responsive plpp_table" id="plpp_table_'.$plppViewgroupType.'">'.PHP_EOL;
		
		// Start of the table header
		$plppOutput['Content'] .= '			<thead class="plpp_table">'.PHP_EOL;
		$plppOutput['Content'] .= '				<tr class="plpp_table">'.PHP_EOL;
		foreach ($plppConfiguration['mediatypes'][$plppViewgroupType]['itemList'] as $item) {
			if (in_array('list',$item['visibility'])) {
				if ($plex->isSetContent(0, $item['type'], $item['content'], $plexKey)) {
					$plppOutput['Content'] .= '					<th class="plpp_table plpp_table_'.str_replace(' ', '_', $item['name']).'">'.$item['name'].'</th>'.PHP_EOL;
				}
			}
		}
		$plppOutput['Content'] .= '				</tr>'.PHP_EOL;
		$plppOutput['Content'] .= '			</thead>'.PHP_EOL;
		$plppOutput['Content'] .= '			<tbody class="plpp_table">'.PHP_EOL;				
	}
	// If we are in thumbs viewmode we need to generate the thumbs container
	else if ($plppViewmode == 'thumbs') {
		
		// Start of thumbs container
		$plppOutput['Content'] .= '		<div class="row plpp_thumbs_container">'.PHP_EOL;
	}
	// If we are in slider viewmode we need to generate the slider container
	else if ($plppViewmode == 'slider') {
		
		// Start of slider container
		$plppOutput['Content'] .= '		<div class="plpp_slider_container" id="plpp_bxslider_'.$plppViewgroupType.'">'.PHP_EOL;			
	}
	
	// Only generate the items output if we are not in details viewmode
	if ($plppViewmode != 'details') {

		// rotate through the items
		foreach ($parent['items'] as $childKey => $child) {

			switch ($plppViewmode) {
			
				// Start thumb for thumbs view
				case 'thumbs': {
					$plppOutput['Content'] .= '			<div class="col-xs-1 plpp_'.$plppViewmode.'">'.PHP_EOL;
					$plppOutput['Content'] .= '				<a class="plpp_'.$plppViewmode.'" href="'.PLPP_BASE_PATH.'?item='.$child['ratingKey'].'&type='.$child['type'];
					if ($plppConfiguration['mediatypes'][$child['type']]['isItem']) {
						if ($child['type'] != 'photo') {
							$plppOutput['Content'] .= '&viewmode=details" data-target="#plpp_Modal">'.PHP_EOL;
							$plppIsModalLink = true;
						}
						else if ($child['type'] == 'photo') {
							$plppOutput['Content'] .= '&viewmode=img&filter=full" data-lightbox="plpp_photo" data-title="'.$child['title'].'">'.PHP_EOL;
						}
					}
					else {
						$plppOutput['Content'] .= '">'.PHP_EOL;
					}
					break;
				}
				
				// Start thumb for slider view
				case 'slider': {
					$plppOutput['Content'] .= '			<div class="plpp_slider">'.PHP_EOL;
					$plppOutput['Content'] .= '				<a class="plpp_'.$plppViewmode.'" href="'.PLPP_BASE_PATH.'?item='.$child['ratingKey'].'&type='.$child['type'];
					if ($plppConfiguration['mediatypes'][$child['type']]['isItem']) {
						if ($child['type'] != 'photo') {
							$plppOutput['Content'] .= '&viewmode=details" data-target="#plpp_Modal">'.PHP_EOL;
							$plppIsModalLink = true;
						}
						else if ($child['type'] == 'photo') {
							$plppOutput['Content'] .= '&viewmode=img&filter=full" data-lightbox="plpp_photo" data-title="'.$child['title'].'">'.PHP_EOL;
						}
					}
					else {
						$plppOutput['Content'] .= '">'.PHP_EOL;
					}
					break;
				}
				
				// Start row for list view
				case 'list': {
					$plppOutput['Content'] .= '				<tr class="plpp_table">'.PHP_EOL;
					break;
				}
			}

			// Generate the actual item content
			foreach ($plppConfiguration['mediatypes'][$child['type']]['itemList'] as $item) {
				
				if ($plex->isSetContent(0, $item['type'], $item['content'], $plexKey)) {
					if (in_array($plppViewmode,$item['visibility'])) {
						// For the list view we have to add the td and link tag
						if ($plppViewmode == 'list') { 
							$plppOutput['Content'] .= '					<td class="plpp_table plpp_table_'.str_replace(' ', '_', $item['name']).'">'.PHP_EOL;
							$plppOutput['Content'] .= '						<a class="plpp_'.$plppViewmode.'" href="'.PLPP_BASE_PATH.'?item='.$child['ratingKey'].'&type='.$child['type'];
							if ($plppConfiguration['mediatypes'][$child['type']]['isItem']) {
								if ($child['type'] != 'photo') {
									$plppOutput['Content'] .= '&viewmode=details" data-target="#plpp_Modal">'.PHP_EOL;
									$plppIsModalLink = true;
								}
								else if ($child['type'] == 'photo') {
									$plppOutput['Content'] .= '&viewmode=img&filter=full" data-lightbox="plpp_photo_'.$item['name'].'" data-title="'.$child['title'].'">'.PHP_EOL;
								}
							}
							else {
								$plppOutput['Content'] .= '">'.PHP_EOL;
							}
						}
						$plppOutput['Content'] .= '				<span class="plpp_'.$plppViewmode.' plpp_'.$plppViewmode.'_'.str_replace(' ', '_', $item['name']).'">';
						$plppOutput['Content'] .=  $plex->getFormatedItemsContent($childKey, $item['type'], $item['content'], $item['content_type'], $plexKey);
						$plppOutput['Content'] .= '</span>'.PHP_EOL;
						// For the list view we have to close the td and link tag
						if ($plppViewmode == 'list') { 
							$plppOutput['Content'] .= '						</a>'.PHP_EOL;
							$plppOutput['Content'] .= '					</td>'.PHP_EOL;
						}
					}
				}
			}					

			switch ($plppViewmode) {
			
				// Close thumb for thumbs view
				case 'thumbs': {
					$plppOutput['Content'] .= '				</a>'.PHP_EOL;
					$plppOutput['Content'] .= '			</div>'.PHP_EOL;
					break;
				}
				// Close thumb for slider view
				case 'slider': {
					$plppOutput['Content'] .= '				</a>'.PHP_EOL;
					$plppOutput['Content'] .= '			</div>'.PHP_EOL;
					break;
				}
				// Close row for list view
				case 'list': {
					$plppOutput['Content'] .= '				</tr>'.PHP_EOL;
					break;
				}
			}
		}
	}
	else {
		// Generate item details output if we are in details viewmode
		foreach ($plppConfiguration['mediatypes'][$plppViewgroupType]['itemList'] as $item) {
			if (in_array('itemdetails',$item['visibility'])) {
					$plppItems['itemdetails'][$item['name']] = $plex->getFormatedItemsContent(0, $item['type'], $item['content'], $item['content_type'], $plexKey);
			}
		}
		$plppDetails = plpp_templates($plppItems, $plppViewgroupType, 'itemdetails');
		
		// If it is an ajax request serve it to the browser and end
		if ($plppIsModal) {
			echo $plppDetails;
			die;
		}
		else {
			$plppOutput['Content'] .= $plppDetails;
		}
	}


	// If we are in thumbs viewmode we need to close the thumbs container
	if ($plppViewmode == 'thumbs') {
		
		// End of thumbs container
		$plppOutput['Content'] .= '		</div>'.PHP_EOL;
	}
	// If we are in slider viewmode we need to close the slider container and add the javascript
	else if ($plppViewmode == 'slider') {
		
		// End of slider container
		$plppOutput['Content'] .= '		</div>'.PHP_EOL;

		// Add the javascript code
		$plppOutput['ScriptCode']  .= '			$("#plpp_bxslider_'.$plppViewgroupType.'").bxSlider( {'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				pager: false,'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				nextSelector: $(".plpp_bxslide_next_'.$plppViewgroupType.'"),'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				nextText: "<i class=\"fa fa-chevron-right fa-lg\"></i>",'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				prevSelector: $(".plpp_bxslide_prev_'.$plppViewgroupType.'"),'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				prevText: "<i class=\"fa fa-chevron-left fa-lg\"></i>",'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				slideWidth: '.$plppImageWidth.','.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				minSlides: 3,'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				maxSlides: 99999,'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				slideMargin: 15,'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				infiniteLoop: false,'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				easing: "ease"'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '			});'.PHP_EOL;
		
	}
	// If we are in list viewmode we need to close the table and add the javascript
	else if ($plppViewmode == 'list') {
		
		// End of the table
		$plppOutput['Content'] .= '			</tbody>'.PHP_EOL;	
		$plppOutput['Content'] .= '		</table>'.PHP_EOL;

		// Add the javascript code
		$plppOutput['ScriptCode']  .= '			$("#plpp_table_'.$plppViewgroupType.'").dataTable( {'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				"order": [],'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '				"responsive": true'.PHP_EOL;
		$plppOutput['ScriptCode']  .= '			});'.PHP_EOL;
	}
	// Close the content container
	$plppOutput['Content'] .= '	</div>'.PHP_EOL;	
	$plppOutput['Content'] .= '</div>'.PHP_EOL;	
}
		

// Close the javascript code for slider and list view
switch ($plppViewmode) {
	case 'slider': {
		$plppOutput['ScriptCode']  .= '		});'.PHP_EOL;
		break;
	}
	case 'list': {
		$plppOutput['ScriptCode']  .= '		});'.PHP_EOL;
		break;
	}
	default: {

	}
}


// If there is a modal link, we need to add the modal javascript and the html container
if ($plppIsModalLink) {
	$plppOutput['ScriptCode']  .= <<<END
		$(function() {
			$("a[data-target=#plpp_Modal]").click(function (ev) {
				console.log("clicked");
				ev.preventDefault();
				$("#plpp_Modal .modal-body").empty();
				$("#plpp_Modal .modal-body").append("<div class=\"plpp_spinner\"><i class=\"fa fa-spinner fa-pulse fa-3x fa-fw \"></i></div>");
				$("#plpp_Modal .modal-body").modal('handleUpdate')
				$("#plpp_Modal").modal("show");
				var target = $(this).attr("href");
				target = target + "&modal=1";
				// load the url and show modal on success
				$("#plpp_Modal .modal-body").load(target, function() { 
					$('#plpp_Modal').modal('handleUpdate');
				});
			});
		});
END;
	$plppOutput['ScriptCode']  .= PHP_EOL;
	$plppOutput['Content']  .= <<<END
<div class="modal fade plpp_modal" id="plpp_Modal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-md">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title">Details</h4>
			</div>
			<div class="modal-body">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>	
END;
$plppOutput['Content']  .= PHP_EOL;
	
}


// If search is enabled, we need to add the modal javascript and the html container for the search
if ($plppConfiguration['usersettings']['search_link']) {
	$plppOutput['ScriptCode']  .= <<<END
		$(function() {
			$("a[data-target=#myPlexModalSearch]").click(function (ev) {
				console.log("clicked");
				ev.preventDefault();
				$("#myPlexModalSearch").modal("show");
				$('#myPlexModalSearch').modal('handleUpdate');
			});
		});
END;
	$plppOutput['ScriptCode']  .= PHP_EOL;
	$plppOutput['Content']  .= <<<END
<div class="modal fade" id="myPlexModalSearch" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-md">
		<div class="modal-content">
			<!-- div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title">Search</h4>
			</div -->
			<div class="modal-body">
END;
$plppOutput['Content']  .= PHP_EOL;
$plppOutput['Content'] .= '<form class="form-horizontal" action="'.PLPP_BASE_PATH.'" method="get">'.PHP_EOL;
$plppOutput['Content'] .= '<fieldset>'.PHP_EOL;
$plppOutput['Content'] .= '<legend>Search</legend>'.PHP_EOL;
$plppOutput['Content'] .= '	<fieldset class="form-group">'.PHP_EOL;
$plppOutput['Content'] .= '		<label class="col-md-3 control-label" for="query">Search term</label>'.PHP_EOL;
$plppOutput['Content'] .= '		<input type="hidden" name="search" id="search" value="1">'.PHP_EOL;
$plppOutput['Content'] .= '		<div class="col-md-9">'.PHP_EOL;
$plppOutput['Content'] .= '			<input id="query" name="query" type="text" placeholder="" value="" class="form-control input-md" required="">'.PHP_EOL;
$plppOutput['Content'] .= '		</div>'.PHP_EOL;
$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;	
$plppOutput['Content'] .= '	<fieldset class="form-group">'.PHP_EOL;
$plppOutput['Content'] .= '		<label class="col-md-3 control-label" for="searchtype">Search for</label>'.PHP_EOL;
$plppOutput['Content'] .= '		<div class="col-md-9">'.PHP_EOL;
$plppOutput['Content'] .= '			<select id="searchtype" name="searchtype" class="form-control">'.PHP_EOL;
$plppOutput['Content'] .= '				<option value="">everything</option>'.PHP_EOL;
if ($plppItemType == 'library') {
	$plppOutput['Content'] .= '				<option value="library">'.$plppViewgroupType.'s in current library</option>'.PHP_EOL;	
}
if (in_array('movie',$plppLibraryTypes)) {
	$plppOutput['Content'] .= '				<option value="movie">movies in all libraries</option>'.PHP_EOL;
}
if (in_array('show',$plppLibraryTypes)) {
	$plppOutput['Content'] .= '				<option value="show">shows in all libraries</option>'.PHP_EOL;
	$plppOutput['Content'] .= '				<option value="episode">episodes in all libraries</option>'.PHP_EOL;
}
if (in_array('artist',$plppLibraryTypes)) {
	$plppOutput['Content'] .= '				<option value="artist">artists in all libraries</option>'.PHP_EOL;
	$plppOutput['Content'] .= '				<option value="album">albums in all libraries</option>'.PHP_EOL;
	$plppOutput['Content'] .= '				<option value="track">tracks in all libraries</option>'.PHP_EOL;
}
if (in_array('photo',$plppLibraryTypes)) {
	$plppOutput['Content'] .= '				<option value="photo album">photo albums  in all libraries</option>'.PHP_EOL;
	$plppOutput['Content'] .= '				<option value="photo">photos  in all libraries</option>'.PHP_EOL;
}
$plppOutput['Content'] .= '			</select>'.PHP_EOL;
if ($plppItemType == 'library') {
	$plppOutput['Content'] .= '			<input type="hidden" name="item" id="item" value="'.$plppItem.'">'.PHP_EOL;
	$plppOutput['Content'] .= '			<input type="hidden" name="viewgroup" id="viewgroup" value="'.$plppViewgroupType.'">'.PHP_EOL;
}
$plppOutput['Content'] .= '		</div>'.PHP_EOL;
$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;
$plppOutput['Content'] .= '	<fieldset class="row form-group">'.PHP_EOL;
$plppOutput['Content'] .= '		<div class="col-md-3">'.PHP_EOL;
$plppOutput['Content'] .= '		</div>'.PHP_EOL;
$plppOutput['Content'] .= '		<div class="col-md-9">'.PHP_EOL;
$plppOutput['Content'] .= '			<input class="btn btn-primary" type="submit" value="Search">'.PHP_EOL;
$plppOutput['Content'] .= '			<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>'.PHP_EOL;
$plppOutput['Content'] .= '		</div>'.PHP_EOL;
$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;	
$plppOutput['Content'] .= '</fieldset>'.PHP_EOL;
$plppOutput['Content'] .= '</form>'.PHP_EOL;
$plppOutput['Content']  .= <<<END
			</div>
			<!-- div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
			</div -->
		</div>
	</div>
</div>
END;
$plppOutput['Content']  .= PHP_EOL;
}


// If debug mode is active, we need to add the modal javascript and the html container for the debug info
if ($plppConfiguration['usersettings']['debug']) {
	$plppOutput['ScriptCode']  .= <<<END
		$(function() {
			$("a[data-target=#myPlexModalDebug]").click(function (ev) {
				console.log("clicked");
				ev.preventDefault();
				$("#myPlexModalDebug").modal("show");
				$('#myPlexModalDebug').modal('handleUpdate');
			});
		});
END;
	$plppOutput['ScriptCode']  .= PHP_EOL;
	$plppOutput['Content']  .= <<<END
<div class="modal fade" id="myPlexModalDebug" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title">Debug Information</h4>
			</div>
			<div class="modal-body">
				<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingOne">
							<h4 class="panel-title">
								<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
									Variables
								</a>
							</h4>
						</div>
						<div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
							<div class="panel-body">
END;
	$plppOutput['Content']  .= PHP_EOL;
	$plppOutput['Content']  .= '								<pre>'.PHP_EOL;
	$plppOutput['Content']  .= '$plexURL = ';
	$plppOutput['Content']  .= print_r($plex->plexURL, true).PHP_EOL;
	$plppOutput['Content']  .= '$plppItem = '.$plppItem;
	$plppOutput['Content']  .= PHP_EOL;
	$plppOutput['Content']  .= '$plppItemType = '.$plppItemType.PHP_EOL;
	$plppOutput['Content']  .= '$plppViewgroupType = '.$plppViewgroupType.PHP_EOL;	
	$plppOutput['Content']  .= '$plppViewmode = '.$plppViewmode.PHP_EOL;
	$plppOutput['Content']  .= '$plppItemsFilter = '.$plppItemsFilter.PHP_EOL;
	$plppOutput['Content']  .= '$plppItemsQuery = '.$plppItemsQuery.PHP_EOL;
	$plppOutput['Content']  .= 'Token = '.$plex->getToken().PHP_EOL;
	$plppOutput['Content']  .= '								</pre>'.PHP_EOL;
	$plppOutput['Content']  .= <<<END
							</div>
						</div>
					</div>
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingTwo">
							<h4 class="panel-title">
								<a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
									Library Index
								</a>
							</h4>
						</div>
						<div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
							<div class="panel-body">
END;
	$plppOutput['Content']  .= PHP_EOL;
	$plppOutput['Content']  .= '								<pre>'.PHP_EOL;
	$plppOutput['Content']  .= print_r($plppIndex, true);
	$plppOutput['Content']  .= '								</pre>'.PHP_EOL;
	$plppOutput['Content']  .= <<<END
							</div>
						</div>
					</div>
					<div class="panel panel-default">
						<div class="panel-heading" role="tab" id="headingThree">
							<h4 class="panel-title">
								<a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
									Item Index
								</a>
							</h4>
						</div>
						<div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
							<div class="panel-body">
END;
	$plppOutput['Content']  .= PHP_EOL;
	$plppOutput['Content']  .= '								<pre>'.PHP_EOL;
	$plppOutput['Content']  .= print_r($plppItems, true);
	$plppOutput['Content']  .= '								</pre>'.PHP_EOL;
	$plppOutput['Content']  .= <<<END
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
END;
$plppOutput['Content']  .= PHP_EOL;
}

// Add the trunk8 script for the summaries
$plppOutput['ScriptCode'] .= <<<END
		$(function() {
			$('.plpp_details_Summary').trunk8({
			  fill: '&hellip; <a id="read-more" href="#">&nbsp;<i>[more]</i></a>',
			  lines: 4
			});
			$(document).on('click', '#read-more', function (event) {
			  $(this).parent().trunk8('revert');
			  return false;
			});
		});
END;
$plppOutput['ScriptCode']  .= PHP_EOL;

// Close the script tag
$plppOutput['ScriptCode']  .= '	</script>'.PHP_EOL;


// Constructing the error messages
if (!empty($plppErrors)){
	foreach ($plppErrors as $details) {
		$plppOutput['Errors'] .= '<div class="plpp_errors alert alert-danger"><strong>Error:</strong> '.$details.'</div>'.PHP_EOL;
	}
}


// Constructing the Include html
$plppOutput['Include'] = $plppOutput['IncludeJS'];
$plppOutput['Include'] .= $plppOutput['IncludeCSS'];
$plppOutput['Include'] .= $plppOutput['ScriptCode'];


// Setting the Title
$plppOutput['Title'] = $plppConfiguration['usersettings']['title'];


// Fill the generated html into the template and output the template
$output = new Template(PLPP_TEMPLATES_PATH.'index.tpl');
foreach ($plppOutput as $key => $content){
	$output->set($key, $content);
}
echo $output->output();

?>
