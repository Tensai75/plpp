<?php


// Start Session
session_start();


// Log out if requested
if (isset($_GET['logout']) and isset($_SESSION['loged-in'])) {
	unset($_SESSION['loged-in']);
	header('Location: index.php');
	die;
}

// Reset token if requested
if (isset($_GET['cleartoken']) and isset($_SESSION['token'])) {
	unset($_SESSION['token']);
}

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


// Defining runtime variables and setting standard details
$plppConfiguration = array(
	'general' => array(),
	'plexserver' => array(),
	'libraries' => array(),
	'usersettings' => array(),
//	'mediatypes' => array(),
);

if (isset($_GET['section'])) {
	$plppConfigurationSection = $_GET['section'];
}
else {
	$plppConfigurationSection = 'plexserver';
}

$plppSomethingToSave = false;

$plppConfigurationSettings = array(
	'plexserver' => array(
		'name' => 'Plex Server',
		'description' => 'Plex Server Settings and Credentials',
		'settings' => array(
			'scheme' => array(
				'name' => 'Secure connection',
				'help' => 'Use http or https for the connection to the Plex Server',
				'type' => 'single_option',
				'options' => array(
					'http://' => 'http',
					'http<b>s</b>://' => 'https'
				),
				'default' => 'http'
			),
			'domain' => array(
				'name' => 'Plex Server',
				'help' => 'IP number or domain name of the Plex Server (do not use http:// or https:// nor trailing backslash)',
				'type' => 'string',
				'default' => ''				
			),
			'port' => array(
				'name' => 'Plex Port',
				'help' => 'Port number of the Plex Server (usually 32400)',
				'type' => 'string',
				'default' => '32400'				
			),
			'username' => array(
				'name' => 'Username',
				'help' => 'Your Plex Account username',
				'type' => 'string',
				'default' => ''				
			),
			'password' => array(
				'name' => 'Password',
				'help' => 'Your Plex Account password',
				'type' => 'string',
				'default' => ''				
			)				
		)
	),
	'libraries' => array(
		'name' => 'Libraries',
		'description' => 'Plex Server Library Settings',
		'settings' => array(
			'excluded_libraries' => array(
				'name' => 'Excluded Libraries',
				'help' => 'Selected Libraries will not be shown',
				'type' => 'library_select',
				'default' => array()
			),
			'default_viewmode' => array(
				'name' => 'Default view',
				'help' => 'Defines the default view for the libraries (front page will always show the slider view)',
				'type' => 'single_option',
				'options' => array(
					'Thumbnails' => 'thumbs',
					'Data table list' => 'list'
				),
				'default' => 'thumbs'
			),
			'sort_by' => array(
				'name' => 'Sort by',
				'help' => 'Defines how the libraries are sorted',
				'type' => 'single_option',
				'options' => array(
					'Library type' => 'type',
					'Library name' => 'title'
				),
				'default' => 'type'
			),
			'sort_order' => array(
				'name' => 'Sort order',
				'help' => 'Defines how the libraries are sorted',
				'type' => 'single_option',
				'options' => array(
					'ascending' => 'SORT_ASC',
					'descending' => 'SORT_DESC'
				),
				'default' => 'SORT_ASC'
			)
		)
	),
	'usersettings' => array(
		'name' => 'User Settings',
		'description' => 'PLPP User Settings',
		'settings' => array(
			'title' => array(
				'name' => 'Program Title',
				'help' => 'Title to be shown in the upper left and the Window bar',
				'type' => 'string',
				'default' => 'PHP Library Presenter for PLEX'
			),
			'admin_password' => array(
				'name' => 'Administrator Password',
				'help' => 'Administrator Password for the Settings section',
				'type' => 'string',
				'default' => ''
			),
			'language' => array(
				'name' => 'Language',
				'help' => 'Select the language of the program (not yet implemented)',
				'type' => 'disabled',
				'default' => 'en'
			),
			'search_link' => array(
				'name' => 'Enable Search',
				'help' => 'Show link to search the libraries in Main Menu',
				'type' => 'bolean',
				'default' => 1
			),
			'settings_link' => array(
				'name' => 'Settings Link',
				'help' => 'Show link to Settings section in Main Menu',
				'type' => 'bolean',
				'default' => 1
			),
			'cache_images' => array(
				'name' => 'Cache Images',
				'help' => 'If set to true images will be cached to speed up image loading',
				'type' => 'bolean',
				'default' => 1
			),
			'refresh_images' => array(
				'name' => 'Refresh Images',
				'help' => 'If set to true cached images will be be refreshed when loaded again.<br />Can be used to reload wrongly cached images but should be turned of afterwards to speed up image loading.',
				'type' => 'bolean',
				'default' => 0
			),
			'debug' => array(
				'name' => 'Debug',
				'help' => 'Show link with debug information (CAUTION: this will expose your token!)<br />Will also turn on/off PHP error reporting',
				'type' => 'bolean',
				'default' => 0
			)					
		)
	),
	'general' => array(
		'name' => 'Info',
		'description' => 'Script Info',
		'settings' => array(
			'script_name' => array(
				'name' => 'Script Name',
				'help' => '',
				'type' => 'info',
				'default' => 'PHP Library Presenter for PLEX'
			),
			'script_description' => array(
				'name' => 'Description',
				'help' => '',
				'type' => 'info',
				'default' => 'PHP front end to present PLEX libraries over the web'
			),
			'script_version' => array(
				'name' => 'Version',
				'help' => '',
				'type' => 'info',
				'default' => 'v1.0'
			),
			'script_guid' => array(
				'name' => 'GUID',
				'help' => 'Globally Unique IDentifier of the script',
				'type' => 'info',
				'default' => ''
			)				
		)
	)
);


//$plpp_strings = array();
$plppErrors = array();
$plppOutput = array(
	'Title' => '',
	'Errors' => '',
	'Menu' => '',
	'Content' => '',
	'Script' => '',
	'Include' => '',
);
$plpp_token = '';


// Include functions and classes
include(PLPP_INCLUDE_PATH.'plpp.functions.php');
include(PLPP_INCLUDE_PATH.'plpp.classes.php');
include(PLPP_INCLUDE_PATH.'class.plexAPI.php');


if (!is_writable(PLPP_CONFIGURATION_PATH)) {
	$plppErrors[] = PLPP_CONFIGURATION_PATH.' must be writable!!!';
	$plppOutput['Title'] = 'PHP Library Presenter for PLEX - Settings';
}
else {

	// Generate default config files
	foreach ($plppConfigurationSettings as $key => $value) {
		if (!file_exists(PLPP_CONFIGURATION_PATH.$key.'.json')) {
			foreach ($value['settings'] as $setting => $setting_value) {
				$plppConfiguration[$key][$setting] = $setting_value['default'];
			}
			$success = json_write($plppConfiguration, PLPP_CONFIGURATION_PATH, $key);
			if ($success[$key] == 'true') {
				$plppNotifications[] = 'Configuration file "'.PLPP_CONFIGURATION_PATH.$key.'.json" successfully generated!';
//				chmod(PLPP_CONFIGURATION_PATH.$key.'.json', 0666);
			}
			else {
				foreach ($success[$key] as $error_key => $errorValue) {
					$plppErrors[] = $errorValue;
				}
			}
		}
	}
	
	
	// Load configuration files
	$plppConfiguration = json_load($plppConfiguration, PLPP_CONFIGURATION_PATH, '');
	foreach ($plppConfiguration as $key => $details) {
		if (empty($plppConfiguration[$key])) {
			$plppErrors[] = 'Unable to load configuration file "'.PLPP_CONFUGURATION_PATH.$key.'.json"!';
		}
		else {
			// Something to do here?
		}
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
	
	
	// Generate guid if not yet set
	if (empty($plppConfiguration['general']['script_guid'])) {
		$plppConfiguration['general']['script_guid'] = plexAPI::generateGUID();
		$success = json_write($plppConfiguration, PLPP_CONFIGURATION_PATH, 'general');
		if ($success['general'] == 'true') {
			$plppNotifications[] = 'Configuration file "'.PLPP_CONFIGURATION_PATH.'general.json" successfully updated with new GUID!';
//			chmod(PLPP_CONFIGURATION_PATH.$key.'.json', 0666);
		}
		else {
			foreach ($success['general'] as $error_key => $errorValue) {
				$plppErrors[] = $errorValue;
			}
		}
	}
	
	
	// Saving the settings
	if (isset($_POST['section'])) {
	
		foreach ($_POST as $key => $value) {
			if ($key <> 'section'){
				unset($plppConfiguration[$_POST['section']][$key]);
				$plppConfiguration[$_POST['section']][$key] = $value;
			}
		}
		
		$success = json_write($plppConfiguration, PLPP_CONFIGURATION_PATH, $_POST['section']);
		if ($success[$_POST['section']] == 'true') {
			$plppNotifications[] = 'Configuration file "'.PLPP_CONFIGURATION_PATH.$_POST['section'].'.json" successfully saved!';
		}
		else {
			foreach ($success[$_POST['section']] as $key => $value) {
				$plppErrors[] = $value;
			}
		}
	
		// Reload configuration files
		$plppConfiguration = json_load($plppConfiguration, PLPP_CONFIGURATION_PATH, '');
		foreach ($plppConfiguration as $key => $details) {
			if (empty($plppConfiguration[$key])) {
				$plppErrors[] = 'Unable to load configuration file "'.PLPP_CONFIGURATION_PATH.$key.'.json"!';
			}
			else {
				// Something to do here?
			}
		}
	}
	
	// Authenticate the administrator
	if (!isset($_SESSION['loged-in'])) {
		if (isset($_POST['settings_password'])) {
			if ($_POST['settings_password'] == $plppConfiguration['usersettings']['admin_password']) {
				$_SESSION['loged-in'] = true;
			}
			else {
				$plppErrors[] = 'Wrong Administrator Password!';
			}
		}
	}
	
	
	if (empty($plppConfiguration['usersettings']['admin_password'])) {
		$plppOutput['Content'] .= '<form class="form-horizontal" action="'.PLPP_BASE_PATH.'" method="post">'.PHP_EOL;
		$plppOutput['Content'] .= '<fieldset>'.PHP_EOL;
		$plppOutput['Content'] .= '<legend>Setting Administrator Password</legend>'.PHP_EOL;
		$plppOutput['Content'] .= '	<fieldset class="form-group">'.PHP_EOL;
		$plppOutput['Content'] .= '		<label class="col-md-1 control-label" for="admin_password">Administrator Password</label>'.PHP_EOL;
		$plppOutput['Content'] .= '		<div class="col-md-4">'.PHP_EOL;
		$plppOutput['Content'] .= '			<input id="admin_password" name="admin_password" type="text" placeholder="" value="" class="form-control input-md" required="">'.PHP_EOL;
		$plppOutput['Content'] .= '			<span class="help-block">Please set your Administartor Password to access the Settings section</span>'.PHP_EOL;
		$plppOutput['Content'] .= '		</div>'.PHP_EOL;
		$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;	
		$plppOutput['Content'] .= '	<fieldset class="row form-group">'.PHP_EOL;
		$plppOutput['Content'] .= '		<div class="col-md-1">'.PHP_EOL;
		$plppOutput['Content'] .= '			<input type="hidden" name="section" id="section" value="usersettings">'.PHP_EOL;
		$plppOutput['Content'] .= '		</div>'.PHP_EOL;
		$plppOutput['Content'] .= '		<div class="col-md-4">'.PHP_EOL;
		$plppOutput['Content'] .= '			<input class="btn btn-primary" type="submit" value="Save">'.PHP_EOL;
		$plppOutput['Content'] .= '		</div>'.PHP_EOL;
		$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;	
		$plppOutput['Content'] .= '</fieldset>'.PHP_EOL;
		$plppOutput['Content'] .= '</form>'.PHP_EOL;
	}
	else if (!isset($_SESSION['loged-in'])) {
		$plppOutput['Content'] .= '<form class="form-horizontal" action="'.PLPP_BASE_PATH.'" method="post">'.PHP_EOL;
		$plppOutput['Content'] .= '<fieldset>'.PHP_EOL;
		$plppOutput['Content'] .= '<legend>Login</legend>'.PHP_EOL;
		$plppOutput['Content'] .= '	<fieldset class="form-group">'.PHP_EOL;
		$plppOutput['Content'] .= '		<label class="col-md-1 control-label" for="settings_password">Administrator Password</label>'.PHP_EOL;
		$plppOutput['Content'] .= '		<div class="col-md-4">'.PHP_EOL;
		$plppOutput['Content'] .= '			<input id="password" name="settings_password" type="password" placeholder="" value="" class="form-control input-md" required="">'.PHP_EOL;
		$plppOutput['Content'] .= '			<span class="help-block">Please enter your Administartor Password to access the Settings section</span>'.PHP_EOL;
		$plppOutput['Content'] .= '		</div>'.PHP_EOL;
		$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;	
		$plppOutput['Content'] .= '	<fieldset class="form-group">'.PHP_EOL;
		$plppOutput['Content'] .= '		<div class="col-md-1"></div>'.PHP_EOL;
		$plppOutput['Content'] .= '		<div class="col-md-4">'.PHP_EOL;
		$plppOutput['Content'] .= '			<input class="btn btn-primary" type="submit" value="Submit">'.PHP_EOL;
		$plppOutput['Content'] .= '		</div>'.PHP_EOL;
		$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;	
		$plppOutput['Content'] .= '</fieldset>'.PHP_EOL;
		$plppOutput['Content'] .= '</form>'.PHP_EOL;	
	}
	else if (isset($_SESSION['loged-in'])) {
	
		$plppOutput['Content'] .= '<form class="form-horizontal" action="'.PLPP_BASE_PATH.'?section='.$plppConfigurationSection.'" method="post">'.PHP_EOL;
		$plppOutput['Content'] .= '<fieldset>'.PHP_EOL;
		$plppOutput['Content'] .= '<legend>'.$plppConfigurationSettings[$plppConfigurationSection]['description'].'</legend>'.PHP_EOL;
	
		// Creating the settings menu
		foreach($plppConfigurationSettings as $key => $value) {
			$plppOutput['Menu'] .= '<li class="plpp_menu';
			if ($key == $plppConfigurationSection) {
				$plppOutput['Menu'] .= ' plpp_menu_selected selected';
			}
			$plppOutput['Menu'] .= '"><a href="'.PLPP_BASE_PATH.'?section='.$key.'">';
			$plppOutput['Menu'] .= '<span class="fa fa-gear fa-lg"></span>&nbsp;&nbsp;';
			$plppOutput['Menu'] .= $value['name'].'</a></li>'.PHP_EOL;
		}
		$plppOutput['Menu'] .= '<li class="plpp_menu"><a href="'.PLPP_BASE_PATH.'?cleartoken=1"><i class="fa fa-ban fa-lg"></i>&nbsp;&nbsp;Reset Token</a></li>'.PHP_EOL;
		$plppOutput['Menu'] .= '<li class="plpp_menu"><a href="'.PLPP_BASE_PATH.'?logout=1"><i class="fa fa-sign-out fa-lg"></i>&nbsp;&nbsp;Log out</a></li>'.PHP_EOL;
		$plppOutput['Menu'] .= '<li class="plpp_menu"><a href="index.php"><i class="fa fa-home fa-lg"></i>&nbsp;&nbsp;Back to Frontend</a></li>'.PHP_EOL;
	
	
		// Creating the settings content
		foreach ($plppConfigurationSettings[$plppConfigurationSection]['settings'] as $key => $value) {
			
			if (isset($plppConfiguration[$plppConfigurationSection][$key])){
				$setting_value = $plppConfiguration[$plppConfigurationSection][$key];	
			}
			else {
				$setting_value = $value['default'];		
			}
	
			
			switch ($value['type']) {
				case 'string': {
					$plppOutput['Content'] .= '	<fieldset class="form-group">'.PHP_EOL;
					$plppOutput['Content'] .= '		<label class="col-md-1 control-label" for="'.$key.'">'.$value['name'].'</label>'.PHP_EOL;
					$plppOutput['Content'] .= '		<div class="col-md-4">'.PHP_EOL;
					$plppOutput['Content'] .= '			<input id="'.$key.'" name="'.$key.'" type="text" placeholder="" value="'.$setting_value.'" class="form-control input-md" required="">'.PHP_EOL;
					$plppOutput['Content'] .= '			<span class="help-block">'.$value['help'].'</span>'.PHP_EOL;
					$plppOutput['Content'] .= '		</div>'.PHP_EOL;
					$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;
					$plppSomethingToSave = true;
					break;
				}
				case 'disabled': {
					$plppOutput['Content'] .= '	<fieldset class="form-group">'.PHP_EOL;
					$plppOutput['Content'] .= '		<label class="col-md-1 control-label" for="'.$key.'">'.$value['name'].'</label>'.PHP_EOL;
					$plppOutput['Content'] .= '		<div class="col-md-4">'.PHP_EOL;
					$plppOutput['Content'] .= '			<input id="'.$key.'" name="'.$key.'" type="text" placeholder="" value="'.$setting_value.'" class="form-control input-md" disabled="">'.PHP_EOL;
					$plppOutput['Content'] .= '			<span class="help-block">'.$value['help'].'</span>'.PHP_EOL;
					$plppOutput['Content'] .= '		</div>'.PHP_EOL;
					$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;
	
					break;
				}
				case 'info': {
					$plppOutput['Content'] .= '	<fieldset class="form-group">'.PHP_EOL;
					$plppOutput['Content'] .= '		<label class="col-md-1 control-label" for="'.$key.'">'.$value['name'].'</label>'.PHP_EOL;
					$plppOutput['Content'] .= '		<div class="col-md-4">'.PHP_EOL;
					$plppOutput['Content'] .= '			<input type="text" placeholder="'.$setting_value.'" value="'.$setting_value.'" class="form-control input-md">'.PHP_EOL;
					$plppOutput['Content'] .= '			<span class="help-block">'.$value['help'].'</span>'.PHP_EOL;
					$plppOutput['Content'] .= '		</div>'.PHP_EOL;
					$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;
	
					break;
				}
				case 'bolean': {
					if ($setting_value){
						$checked_true = 'checked';
						$checked_false = '';
					}
					else {
						$checked_false = 'checked';
						$checked_true = '';
					}
					$plppOutput['Content'] .= '	<fieldset class="form-group">'.PHP_EOL;
					$plppOutput['Content'] .= '		<label class="col-md-1 control-label" for="'.$key.'">'.$value['name'].'</label>'.PHP_EOL;
					$plppOutput['Content'] .= '		<div class="col-md-4">'.PHP_EOL;
					$plppOutput['Content'] .= '			<div class="radio">'.PHP_EOL;
					$plppOutput['Content'] .= '				<label for="'.$key.'-0">'.PHP_EOL;
					$plppOutput['Content'] .= '				<input type="radio" name="'.$key.'" id="'.$key.'-0" value="1" '.$checked_true.'>True</label><br />'.PHP_EOL;
					$plppOutput['Content'] .= '				<label for="'.$key.'-1">'.PHP_EOL;
					$plppOutput['Content'] .= '				<input type="radio" name="'.$key.'" id="'.$key.'-1" value="0" '.$checked_false.'>False</label>'.PHP_EOL;
					$plppOutput['Content'] .= '			</div>'.PHP_EOL;
					$plppOutput['Content'] .= '			<span class="help-block">'.$value['help'].'</span>'.PHP_EOL;
					$plppOutput['Content'] .= '		</div>'.PHP_EOL;
					$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;			
					$plppSomethingToSave = true;
					break;
					
				}
				case 'library_select': {
	
					if (empty($plppConfiguration['plexserver']['domain'])) {
						$plppErrors[count($plppErrors)] = 'Please setup Plex Server first!';
					}
					else {
						
						// Requesting the token if not already set in session variable (speeds up image delivery)
						if (isset($_SESSION['token'])) {
							$plppConfiguration['plexserver']['token'] = $_SESSION['token'];
							$plex = new plexAPI($plppConfiguration['plexserver'], $plppConfiguration['general']);
						}
						else {
							$plex = new plexAPI($plppConfiguration['plexserver'], $plppConfiguration['general']);
							if (empty($plex->getToken())) {
								$plppErrors[count($plppErrors)] = 'No token received! Plex.tv not reachable or wrong credentials!';
							}
							else {
								$_SESSION['token'] = $plex->getToken();
							}
						}
	
								
						// Getting the xml for the plex library index
						$plpp_libraryindex = $plex->getIndex();
						if (empty($plpp_libraryindex)) {
							$plppErrors[count($plppErrors)] = 'Plex server not reachable!';
						}
						else {
							if ($plppConfiguration['libraries']['sort_order'] == 'SORT_ASC') {
								usort($plpp_libraryindex['items'], make_comparer([$plppConfiguration['libraries']['sort_by'], SORT_ASC], ['title', SORT_ASC]));
							}
							else {
								usort($plpp_libraryindex['items'], make_comparer([$plppConfiguration['libraries']['sort_by'], SORT_DESC], ['title', SORT_ASC]));
							}
							$plppOutput['Content'] .= '	<fieldset class="form-group">'.PHP_EOL;
							$plppOutput['Content'] .= '		<label class="col-md-1 control-label" for="'.$key.'">'.$value['name'].'</label>'.PHP_EOL;
							$plppOutput['Content'] .= '		<div class="col-md-4">'.PHP_EOL;
							$plppOutput['Content'] .= '			<div class="checkbox">'.PHP_EOL;
							$count = 0;
							foreach ($plpp_libraryindex['items'] as $child) {
	//							if (array_key_exists($child['type'],$plppConfiguration['mediatypes'])) {
									if (in_array($child['key'],$plppConfiguration[$plppConfigurationSection][$key])) {
										$checked = ' checked';
									}
									else {
										$checked = '';
									}
									$plppOutput['Content'] .= '				<label for="'.$key.'-'.$count.'">'.PHP_EOL;
									$plppOutput['Content'] .= '				<input type="checkbox" name="'.$key.'[]" id="'.$key.'-'.$count.'" value="'.$child['key'].'"'.$checked.'>';
									$plppOutput['Content'] .= '				<i class="fa '.$plppConfiguration['mediatypes'][$child['type']]['icon'].'"></i>&nbsp;&nbsp;';							
									$plppOutput['Content'] .= $child['title'].'</label><br />'.PHP_EOL;
									$count += 1;
	//							}
							}
							
							$plppOutput['Content'] .= '			</div>'.PHP_EOL;
							$plppOutput['Content'] .= '			<span class="help-block">'.$value['help'].'</span>'.PHP_EOL;
							$plppOutput['Content'] .= '		</div>'.PHP_EOL;
							$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;			
	
						}		
					}
					$plppSomethingToSave = true;
					break;
					
				}
				case 'single_option': {
					$plppOutput['Content'] .= '	<fieldset class="form-group">'.PHP_EOL;
					$plppOutput['Content'] .= '		<label class="col-md-1 control-label" for="'.$key.'">'.$value['name'].'</label>'.PHP_EOL;
					$plppOutput['Content'] .= '		<div class="col-md-4">'.PHP_EOL;
					$plppOutput['Content'] .= '			<select id="'.$key.'" name="'.$key.'" class="form-control">'.PHP_EOL;
					foreach ($value['options'] as $options_key => $options_value){
						if ($setting_value == $options_value) {
							$selected = ' selected=""';
						}
						else {
							$selected = '';
						}
						$plppOutput['Content'] .= '				<option value="'.$options_value.'"'.$selected.'>'.$options_key.'</option>'.PHP_EOL;
					}
					$plppOutput['Content'] .= '			</select>'.PHP_EOL;
					$plppOutput['Content'] .= '			<span class="help-block">'.$value['help'].'</span>'.PHP_EOL;
					$plppOutput['Content'] .= '		</div>'.PHP_EOL;
					$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;
					$plppSomethingToSave = true;
					break;
				}			
				default: {
					
				}
			}
		}
	
		$plppOutput['Content'] .= '	<fieldset class="form-group">'.PHP_EOL;
		$plppOutput['Content'] .= '		<div class="col-md-1 control-label" for="">'.PHP_EOL;
		$plppOutput['Content'] .= '			<input type="hidden" name="section" id="section" value="'.$plppConfigurationSection.'">'.PHP_EOL;
		$plppOutput['Content'] .= '		</div>'.PHP_EOL;
		$plppOutput['Content'] .= '		<div class="col-md-4">'.PHP_EOL;
		if ($plppSomethingToSave) {
			$plppOutput['Content'] .= '			<input class="btn btn-primary" type="submit" value="Save">'.PHP_EOL;		
		}
		$plppOutput['Content'] .= '		</div>'.PHP_EOL;
		$plppOutput['Content'] .= '	</fieldset>'.PHP_EOL;	
		$plppOutput['Content'] .= '</fieldset>'.PHP_EOL;
		$plppOutput['Content'] .= '</form>'.PHP_EOL;
	
	}
	
	
	$plppOutput['Include'] .= '	<link rel="stylesheet" type="text/css" href="css/font-awesome.min.css">'.PHP_EOL;
	$plppOutput['Include'] .= '	<link rel="stylesheet" type="text/css" href="css/plpp.css"/>'.PHP_EOL;
	
	$plppOutput['Title'] = $plppConfiguration['usersettings']['title'].' - Settings';
}


// Constructing the error messages
if (!empty($plppErrors)){
	foreach ($plppErrors as $details) {
		$plppOutput['Errors'] .= '<div class="plppErrors alert alert-danger"><strong>Error:</strong> '.$details.'</div>'.PHP_EOL;
	}
}

if (!empty($plppNotifications)){
	foreach ($plppNotifications as $details) {
		$plppOutput['Errors'] .= '<div class="plppErrors alert alert-success alert-dismissible"><strong>Success:</strong> '.$details.'</div>'.PHP_EOL;
	}
}

$output = new Template(PLPP_TEMPLATES_PATH.'index.tpl');
foreach ($plppOutput as $key => $content){
	$output->set($key, $content);
}
echo $output->output();

?>
