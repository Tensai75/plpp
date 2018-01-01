<?php

class plexAPI
{
	private $plexServer = array(
		'scheme' => 'http',
		'domain' => '127.0.0.1',
		'port' => '32400',
		'token' => '',
		'username' => '',
		'password' => ''
		);
	
	private $plexScriptInfo = array(
		'script_name' => 'Plex PHP API class',
		'script_version' => 'v0.1 (beta)',
		'script_description' => 'PHP class for the plex web API',
		'script_guid' => ''
		);
	
	private $plexExcludedLibraries = array();
	
	private $plexItemTypes = array(
		'library' => array(
			'id' => 0,
			'typeString' => 'library',
			'title' => 'Library',
			'element' => array('Directory', 'Video', 'Photo', 'Artist'),
			'path' => '/library/sections',
			'endpoint' => '/all',
			'grandparentType' => ''
		),
		'movie' => array(
			'id' => 1,
			'typeString' => 'movie',
			'title' => 'Movie',
			'element' => array('Video'),
			'path' => '/library/metadata',
			'endpoint' => '',
			'grandparentType' => ''
		),
		'show' => array(
			'id' => 2,
			'typeString' => 'show',
			'title' => 'Show',
			'element' => array('Directory'),
			'path' => '/library/metadata',
			'endpoint' => '/children',
			'grandparentType' => ''
		),
		'season' => array(
			'id' => 3,
			'typeString' => 'season',
			'title' => 'Season',
			'element' => array('Video'),
			'path' => '/library/metadata',
			'endpoint' => '/children',
			'grandparentType' => 'show'
		),
		'episode' => array(
			'id' => 4,
			'typeString' => 'episode',
			'title' => 'Episode',
			'element' => array('Video'),
			'path' => '/library/metadata',
			'endpoint' => '',
			'grandparentType' => 'season'
		),
		'artist' => array (
			'id' => 8,
			'typeString' => 'artist',
			'title' => 'Artist',
			'element' => array('Directory'),
			'path' => '/library/metadata',
			'endpoint' => '/children',
		),
		'album' => array (
			'id' => 9,
			'typeString' => 'album',
			'title' => 'Album',
			'element' => array('Directory', 'Track'),
			'path' => '/library/metadata',
			'endpoint' => '/children',
			'grandparentType' => 'artist'
		),
		'track' => array (
			'id' => 10,
			'typeString' => 'track',
			'title' => 'Track',
			'element' => array('Track'),
			'path' => '/library/metadata',
			'endpoint' => '',
			'grandparentType' => 'album'
		),
		'photoalbum' => array (
			'id' => 11,
			'typeString' => 'photoalbum',
			'title' => 'Photo Album',
			'element' => array('Photo', 'Directory'),
			'path' => '/library/metadata',
			'endpoint' => '/children',
			'grandparentType' => 'photoalbum'
		),
		'picture' => array (
			'id' => 13,
			'typeString' => 'picture',
			'title' => 'Picture',
			'element' => array('Photo'),
			'path' => '/library/metadata',
			'endpoint' => '',
			'grandparentType' => 'photoalbum'
		),
		'photo' => array (
			'id' => 13,
			'typeString' => 'photo',
			'title' => 'Photo',
			'element' => array('Photo'),
			'path' => '/library/metadata',
			'endpoint' => '',
			'grandparentType' => 'photoalbum'
		),
		'secondary' => array(
			'element' => array('Directory'),
		)
	);
	/* Remaining media types to be added later	
	id: 5,
	typeString: "trailer",
	title: "Trailer",
	element: "video"       
	 
	id: 6,
	typeString: "comic",
	title: "Comic",
	element: "photo" 

	id: 7,
	typeString: "person",
	title: "Person",
	element: "directory"

	id: 14,
	typeString: "clip",
	title: "Clip",
	element: "video"

	id: 15,
	typeString: "playlistItem",
	title: "Clip",
	element: "video"
	*/	
	
	private $plexIndex = array();
	
	private $plexItems = array();
	
	// Mainly for debug purpose
	public $plexURL = array();
	
	private $plexFilters = array(
		'all' => array(
			'endpoint' => '/all',
			'query' => FALSE
		),
		'recentlyAdded' => array(
			'endpoint' => '/recentlyAdded',
			'query' => FALSE
		),
		'newest' => array(
			'endpoint' => '/newest',
			'query' => FALSE
		),
		'albums' => array(
			'endpoint' => '/albums',
			'query' => FALSE
		)
	);

	public function __construct($server='', $script='')
	{
		if (!empty($script)) { $this->setScriptInfo($script); };
		if (!empty($server)) { $this->setServer($server); };
	}

	public function setServer($server)
	{
		$this->plexServer['scheme'] = isset($server['scheme']) ? $server['scheme'] : $this->plexServer['scheme'];;
		$this->plexServer['domain'] = isset($server['domain']) ? $server['domain'] : $this->plexServer['domain'];;
		$this->plexServer['port'] = isset($server['port']) ? $server['port'] : $this->plexServer['port'];;
		$this->plexServer['username'] = isset($server['username']) ? $server['username'] : $this->plexServer['username'];;
		$this->plexServer['password'] = isset($server['password']) ? $server['password'] : $this->plexServer['password'];;
		$this->plexServer['token'] = isset($server['token']) ? $server['token'] : $this->getToken();;
	}

	public function getServer($index='')
	{
		if (!empty($index))
		{
			return $this->plexServer[$index];
		}
		else
		{
			return $this->plexServer;
		}
	}

	public function setScriptInfo($scriptInfo)
	{
		$this->plexScriptInfo['script_name'] = isset($scriptInfo['script_name']) ? $scriptInfo['script_name'] : $this->plexScriptInfo['script_name'];;
		$this->plexScriptInfo['script_version'] = isset($scriptInfo['script_version']) ? $scriptInfo['script_version'] : $this->plexScriptInfo['script_version'];;
		$this->plexScriptInfo['script_description'] = isset($scriptInfo['script_description']) ? $scriptInfo['script_description'] : $this->plexScriptInfo['script_description'];;
		$this->plexScriptInfo['script_guid'] = isset($scriptInfo['script_guid']) ? $scriptInfo['script_guid'] : $this->generateGUID();;
	}

	public function getScriptInfo($index='')
	{
		if (!empty($index))
		{
			return $this->plexScriptInfo[$index];
		}
		else
		{
			return $this->plexScriptInfo;
		}
	}

	public function setExcludedLibraries($excludedLibraries)
	{
		if (is_array($excludedLibraries)) { $this->plexExcludedLibraries = $excludedLibraries; }
	}

	public function getExcludedLibraries()
	{
		return $this->plexExcludedLibraries;
	}

	public function getIndex($item = 0, $type='library')
	{
		if (empty($this->plexIndex[$item]))
		{
			$path = $this->plexItemTypes[$type]['path'];
			$item = ($item == 0) ? '' : '/'.$item;;
			$index = $this->getXML($path, '', $item);
			$element = $this->plexItemTypes[$type]['element'];
			foreach ($element as $parent)
			{
				foreach ($index[$parent] as $child)
				{
					if (!in_array($child['key'], $this->plexExcludedLibraries))
					{
						$items[] = $child;
					}
				}
				unset($index[$parent]);
			}
			if (!empty($items))
			{
				$index['items'] = $items;
			}
			$this->plexIndex = $index;
		}
		return $this->plexIndex;
	}

	public function getItems($item = 0, $type, $filter = '', $query = '')
	{
		if (empty($this->plexItems[$item]))
		{
			$path = $this->plexItemTypes[$type]['path'];
			if (array_key_exists($filter, $this->plexFilters))
			{
				$endpoint = $this->plexFilters[$filter]['endpoint'];
				$filter = (!empty($query) && $this->plexFilters[$filter]['query']) ? $endpoint.'/'.$query : $endpoint;;
			}
			else {
				$filter = $this->plexItemTypes[$type]['endpoint'];
			}
			$urlItem = ($item == 0) ? '' : '/'.$item;;
			$index = $this->getXML($path, $filter, $urlItem, $query);
			$element = $this->plexItemTypes[$type]['element'];
			foreach ($element as $parent)
			{
				foreach ($index[$parent] as $child)
				{
					if (!in_array($child['librarySectionID'], $this->plexExcludedLibraries))
					{
						// Only add items with a ratingKey
						if (isset($child['ratingKey']))
						{
							// Do some special handling depending on the item type
							switch ($child['type']) {
								case 'photo': {
									// If type photo is an actual photo (if it has media), generate the dimension info
									if (isset($child['Media'])) {
										$child['Media']['dimensions'] = $child['Media']['width'].' x '.$child['Media']['height'].' px';
									}
									// Otherwise it is of type photoalbum and has no dimension
									else {
										$child['type'] = 'photoalbum';
										$child['Media']['dimensions'] = 'Photo Album';
									}
								}
							}
							$items[] = $child;
						}
					}
				}
				unset($index[$parent]);
			}
			// Add the grandparentType for better handling
			if (!empty($this->plexItemTypes[$type]['grandparentType']))
			{
				$index['grandparentType'] = $this->plexItemTypes[$type]['grandparentType'];
			}
			if (!empty($items))
			{
				$index['items'] = $items;
			}
			// Correct the size
			$index['size'] = count($items);
			$this->plexItems[$item] = $index;
		}
		return $this->plexItems[$item];
	}

	public function getRecentlyAdded($type)
	{
		if (empty($this->plexItems[$type]))
		{
			if (empty($this->plexItems['recentlyAdded']))
			{
				$path = '/library/recentlyAdded';
				$this->plexItems['recentlyAdded'] = $this->getXML($path);
				
			}
			$index = $this->plexItems['recentlyAdded'];
			$element = $this->plexItemTypes['library']['element'];
			foreach ($element as $parent)
			{
				foreach ($index[$parent] as $child)
				{
					if (!in_array($child['librarySectionID'], $this->plexExcludedLibraries) && $child['type'] == $type)
					{
						$items[] = $child;
					}
				}
				unset($index[$parent]);
			}
			$index['viewGroup'] = $type;
			$index['title2'] = 'Recently Added '.$this->plexItemTypes[$type]['title'].'s';
			if (!empty($items))
			{
				$index['items'] = $items;
			}
			$index['size'] = count($items);
			$this->plexItems[$type] = $index;
		}
		return $this->plexItems[$type];
	}

	public function getFilters($item, $filter='')
	{
			$path = '/library/sections';
			if (array_key_exists($filter, $this->plexFilters))
			{
				$endpoint = $this->plexFilters[$filter]['endpoint'];
				
			}
			else {
				$endpoint = '';
			}
			$item = '/'.$item;
			$index = $this->getXML($path, $endpoint, $item);
			foreach ($index['Directory'] as $child)
			{
				if (array_key_exists($filter, $this->plexFilters) || array_key_exists($child['key'], $this->plexFilters))
				{
					$filters[$child['key']] = $child['title'];
				}
			}
			return $filters;
		}

	public function search($item = 0, $type = '', $query = '')
	{
		if (empty($this->plexItems[$type]))
		{
			if ($item == 0)
			{
				$path = '/search';
			}
			else {
				$path = '/library/sections';
			}
			$urlQuery['query'] = $query;
			if ($type != '')
			{
				$urlQuery['type'] = $this->plexItemTypes[$type]['id'];
			}
			$urlItem = ($item == 0) ? '' : '/'.$item.'/search';;
			$index = $this->getXML($path, '', $urlItem, $urlQuery);
			$element = ($type != '') ? $this->plexItemTypes[$type]['element'] : array('Directory', 'Video', 'Photo', 'Artist', 'Track');;
			foreach ($element as $parent)
			{
				foreach ($index[$parent] as $child)
				{
					if (!in_array($child['librarySectionID'], $this->plexExcludedLibraries))
					{
						// Only add items with a ratingKey
						if (isset($child['ratingKey']))
						{
							// Do some special handling depending on the item type
							switch ($child['type']) {
								case 'photo': {
									// If type photo is an actual photo (if it has media), generate the dimension info
									if (isset($child['Media'])) {
										$child['Media']['dimensions'] = $child['Media']['width'].' x '.$child['Media']['height'].' px';
									}
									// Otherwise it is of type photoalbum and has no dimension
									else {
										$child['type'] = 'photoalbum';
										$child['Media']['dimensions'] = 'Photo Album';
									}
								}
							}
							$items[] = $child;
						}
					}
				}
				unset($index[$parent]);
			}
			// Add the grandparentType for better handling
			if (!empty($this->plexItemTypes[$type]['grandparentType']))
			{
				$index['grandparentType'] = $this->plexItemTypes[$type]['grandparentType'];
			}
			if (!empty($items))
			{
				$index['items'] = $items;
			}
			// Correct the size
			$index['size'] = count($items);
			
			$index['title2'] = 'Search results for '.$type.'s';
			if ($item == 0)
			{
				$this->plexItems[$type] = $index;
				return $this->plexItems[$type];
			}
			else
			{
				$this->plexItems['library'] = $index;
				return $this->plexItems['library'];
			}
			
		}
		else {
			return $this->plexItems[$type];
		}	
	}
	
	
	public function getItemsContent($itemsKey, $contentType, $content, $item, $streamKey=0, $partKey=0)
	{
		if ($this->plexItems[$item]['items'][$itemsKey]['type'] == 'track' && $content == 'originalTitle' && !isset($this->plexItems[$item]['items'][$itemsKey][$content]))
		{
			$content = 'grandparentTitle';
		}
				
		switch ($contentType)
		{
			case 'attribute':
			{
				$content = $this->plexItems[$item][$content];
				break;
			}
			case 'iteminfo':
			{
				$content = $this->plexItems[$item]['items'][$itemsKey][$content];
				break;
			}
			case 'mediainfo':
			{
				$content = $this->plexItems[$item]['items'][$itemsKey]['Media'][$content];
				break;
			}
			case 'streaminfo':
			{
				$content = $this->plexItems[$item]['items'][$itemsKey]['Media']['Part'][$partKey]['Stream'][$streamKey][$content];
				break;
			}
			default:
			{
				// Something to do here???
			}
		}
		return $content;
	}


	function getFormatedItemsContent($itemsKey, $contentType, $content, $format, $item)
	{
		$content = $this->getItemsContent($itemsKey, $contentType, $content, $item);
		
		switch ($format)
		{
			case 'img': 
			{
				if (empty($this->plexItems[$item]['items'][$itemsKey]['thumb']) || ($this->plexItems[$item]['items'][$itemsKey]['thumb'] == $this->plexItems[$item]['items'][$itemsKey]['parentThumb'])) {
					$content = $this->plexItems[$item]['items'][$itemsKey]['parentRatingKey'];
					$thumb = substr( strrchr( $this->plexItems[$item]['thumb'], '/' ), 1 );
				}
				else
				{
					$thumb = substr( strrchr( $this->plexItems[$item]['items'][$itemsKey]['thumb'], '/' ), 1 );
				}
				$content = '<img alt="" class="img-rounded img-responsive" src="'.PLPP_BASE_PATH.'?item='.urlencode($content).'&thumb='.urlencode($thumb).'&viewmode=img&type='.urlencode($this->plexItems[$item]['items'][$itemsKey]['type']).'&filename='.urlencode($this->plexItems[$item]['items'][$itemsKey]['title']).'">';
				break;
			}
			case 'date':
			{
				$content = date("Y-m-d", intval($content));
				break;
			}
			case 'minutes':
			{
				$minutes = round(intval($content)/60000);
				$content = $minutes.' min';
				break;
			}
			case 'minutes:seconds':
			{
				$minutes = floor(intval($content)/60000);
				$seconds = sprintf("%'02d", round(((intval($content)/60000) - $minutes)*60));
				$content = $minutes.':'.$seconds.' min';
				break;
			}
			default: {
				// Something to do here???
			}
		}
					
		return $content;
	}	

	public function isSetContent($itemsKey, $contentType, $content, $item)
	{
		$return = false;
		switch ($contentType)
		{
			case 'attribute':
			{
				if (isset($this->plexItems[$item][$content])) { $return = true; }
				break;
			}
			case 'iteminfo':
			{
				if (isset($this->plexItems[$item]['items'][$itemsKey][$content])) { $return = true; }
				break;
			}
			case 'mediainfo':
			{
				if (isset($this->plexItems[$item]['items'][$itemsKey]['Media'][$content])) { $return = true; }
				break;
			}
			default:
			{
				// Something to do here???
			}
		}
		return $return;
	}	
	
	
	
	private function getXML($path='',$filter='',$item='',$query=array())
	{
		$query['X-Plex-Token'] = $this->plexServer['token'];
		$url = $this->plexServer['scheme'].'://'.$this->plexServer['domain'].':'.$this->plexServer['port'].$path.$item.$filter.'?'.http_build_query($query);
		$this->plexURL[] = $url;
		$libraryIndex = $this->loadXMLFile($url);
		if (!empty($libraryIndex))
		{
			$libraryIndex = $this->xmlToArray($libraryIndex);
		}
		return $libraryIndex['MediaContainer'];
	}

	private function loadXMLFile($url)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
		$url_parse = parse_url($url);
		if ($url_parse['scheme'] == 'https') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		$content = curl_exec($ch);
		$curlError = curl_error($ch);

		// Debug code
		if ($curlError <> "") {
			echo $url;
			echo $curlError;
			die;
		}
		
		curl_close($ch);
		$content = simplexml_load_string($content);
		return $content;
	}

	public function getToken()
	{
		if (empty($this->plexServer['token']))
		{
			$header = array(
				'Content-Type: application/xml; charset=utf-8', 
				'Content-Length: 0', 
				'X-Plex-Device-Name: '.$this->plexScriptInfo['script_name'],
				'X-Plex-Product: '.$this->plexScriptInfo['script_description'],
				'X-Plex-Version: '.$this->plexScriptInfo['script_version'],
				'X-Plex-Client-Identifier: '.$this->plexScriptInfo['script_guid']
			);
			$process = curl_init('https://plex.tv/users/sign_in.json');
			curl_setopt($process, CURLOPT_HTTPHEADER, $header);
			curl_setopt($process, CURLOPT_HEADER, false);
			curl_setopt($process, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($process, CURLOPT_USERPWD, $this->plexServer['username'] . ":" . $this->plexServer['password']);
			curl_setopt($process, CURLOPT_TIMEOUT, 30);
			curl_setopt($process, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($process, CURLOPT_POST, true);
			curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
			$data = curl_exec($process);
//			$curlError = curl_error($process);
			curl_close($process);
			$json = json_decode($data, true);
			$this->plexServer['token'] = $json[user][authentication_token];
			
// Needs Error handling!!			
			
		}
		return $this->plexServer['token'];
	}
	

	/**
	* Returns a GUIDv4 string
	*
	* Uses the best cryptographically secure method 
	* for all supported pltforms with fallback to an older, 
	* less secure version.
	*
	* @param bool $trim
	* @return string
	*/
	public static function generateGUID ($trim = true)
	{
		// Windows
		if (function_exists('com_create_guid') === true) {
			if ($trim === true)
				return trim(com_create_guid(), '{}');
			else
				return com_create_guid();
		}

		// OSX/Linux
		if (function_exists('openssl_random_pseudo_bytes') === true) {
			$data = openssl_random_pseudo_bytes(16);
			$data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
			$data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
			return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
		}

		// Fallback (PHP 4.2+)
		mt_srand((double)microtime() * 10000);
		$charid = strtolower(md5(uniqid(rand(), true)));
		$hyphen = chr(45);                  // "-"
		$lbrace = $trim ? "" : chr(123);    // "{"
		$rbrace = $trim ? "" : chr(125);    // "}"
		$guidv4 = $lbrace.
				  substr($charid,  0,  8).$hyphen.
				  substr($charid,  8,  4).$hyphen.
				  substr($charid, 12,  4).$hyphen.
				  substr($charid, 16,  4).$hyphen.
				  substr($charid, 20, 12).
				  $rbrace;
		return $guidv4;
	}

	// Converting the xml array into normal array
	// Taken from: http://outlandish.com/blog/xml-to-json/
	private function xmlToArray($xml, $options = array())
	{
		$defaults = array(
			'namespaceSeparator' => ':',	//you may want this to be something other than a colon
			'attributePrefix' => '',		//to distinguish between attributes and nodes with the same name
			'alwaysArray' => array('Video', 'Directory', 'Part', 'Track', 'Photo'),	//array of xml tag names which should always become arrays
			'autoArray' => true,			//only create arrays for tags which appear more than once
			'textContent' => '$',			//key used for the text content of elements
			'autoText' => true,				//skip textContent key if node has no attributes or child nodes
			'keySearch' => false,			//optional search and replace on tag and attribute names
			'keyReplace' => false			//replace values for above search values (as passed to str_replace())
		);
		$options = array_merge($defaults, $options);
		$namespaces = $xml->getDocNamespaces();
		$namespaces[''] = null; //add base (empty) namespace
		//get attributes from all namespaces
		$attributesArray = array();
		foreach ($namespaces as $prefix => $namespace) {
			foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
				//replace characters in attribute name
				if ($options['keySearch']) $attributeName =
					str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
				$attributeKey = $options['attributePrefix']
					. ($prefix ? $prefix . $options['namespaceSeparator'] : '')
					. $attributeName;
				$attributesArray[$attributeKey] = (string)$attribute;
			}
		}
		//get child nodes from all namespaces
		$tagsArray = array();
		foreach ($namespaces as $prefix => $namespace) {
			foreach ($xml->children($namespace) as $childXml) {
				//recurse into child nodes
				$childArray = $this->xmlToArray($childXml, $options);
				list($childTagName, $childProperties) = each($childArray);
				//replace characters in tag name
				if ($options['keySearch']) $childTagName =
					str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
				//add namespace prefix, if any
				if ($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;
				if (!isset($tagsArray[$childTagName])) {
					//only entry with this key
					//test if tags of this type should always be arrays, no matter the element count
					$tagsArray[$childTagName] =
						in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
						? array($childProperties) : $childProperties;
				} elseif (
					is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
					=== range(0, count($tagsArray[$childTagName]) - 1)
				) {
					//key already exists and is integer indexed array
					$tagsArray[$childTagName][] = $childProperties;
				} else {
					//key exists so convert to integer indexed array with previous value in position 0
					$tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
				}
			}
		}
		//get text content of node
		$textContentArray = array();
		$plainText = trim((string)$xml);
		if ($plainText !== '') $textContentArray[$options['textContent']] = $plainText;
		//stick it all together
		$propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
			? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;
		//return node as array
		return array(
			$xml->getName() => $propertiesArray
		);
	}
}

?>
