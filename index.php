<?php

/**
 * Typecho Redis Cache script
 *
 * (c) Lance Liao www.shuyz.com
 *
 * For the full manual and license information, please view the README.md
 * file that was distributed with this source code.
 */

require 'Credis/Client.php';
date_default_timezone_set('Asia/Shanghai');

/** redis connection parameters */
$redis_host = '127.0.0.1';
$redis_port = '6379';
/** the key used to purge cache */
$userkey = 'abc123';

$start_time = microtime(true);

/** server environment variables */
$protocol = (!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] !== 'off')) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
/** remove query parameters from the uri to calc hash, thus only one copy of cache is generated for different query parameters */
$uris = explode('?',$uri,2);
$url = $protocol . "://" . $host . $uris[0];
$dkey = md5($host);
$ukey = md5($url);
/** show debug msg in html page */
$rd_debug = (isset($_GET['debug']) && ($_GET['debug'] == 'true')) ? true: false;
/** redis instance */
$redis;

/**
 *	show debug and error message in html page
 *	@param 	$msg
 *	@return none
 */
function debug_trace($msg) {
	global $rd_debug;
	global $uri;
	/** do not append anything to sitemal.xml */
	if( (!strpos($uri, 'sitemap.xml')) && $rd_debug){
		echo '<!--debug: ' . $msg . '-->';
	}
}

/**
 *	check if the pages is cached
 *	@param 	none
 * 	@return true:	cache valid
 *			false:	no such page in cache
 */

function is_cache_valid(){
	global $redis;
	global $dkey;
	global $ukey;

	if ($redis->hexists($dkey, $ukey)){
		return true;
	}
	else{
		return false;
	}
}

/**
 *	load page from cache
 *	@param 	none
 *	@return html content of the page
 *	@note 	call is_cache_valid() before this function
 */
function load_cached_page(){
	global $redis;
	global $dkey;
	global $ukey;

	return $redis->hget($dkey, $ukey);
}

/**
 *	call original index.php to generate new page
 *	@param 	none
 *	@return html content
 */
function generate_new_page(){
	/** turn on output buffering */
	ob_start();

	require 'index_origin.php';

	/** get contents of output buffer */
    $html = ob_get_contents();

    /* clean output buffer */
    ob_end_clean();

    return $html;
}

/**
 *	check if the page should be cached
 *	if the uri starts with '/admin/' and '/search/' or the page is 404 pages, do not cache
 *	@param 	$html 	html content of the page, we use it to check 404 pages
 *	@return true:	the page should be cached
 *			false:
 */
function check_if_cache_needed($html){
	global $uri;

	debug_trace('the request uri is ' . $uri);

	/** currently there is a bug to cache sitemap, we'll make it a mess if cache it */
	if(preg_match('/^\/sitemap\.xml/i', $uri)){
		debug_trace('no cache for sitemap.');
		return false;
	}
	
	/** exclude search results */
	if(isset($_POST['s']) 
		|| (preg_match('/^\/search\//i', $uri)) ){
		debug_trace('no cache for search results.');
		return false;
	}

	/** exclude admin pages */
	if(preg_match('/^\/admin\//i', $uri)){
		debug_trace('no cache for admin pages.');
		return false;
	}

	/** exclude 404 pages 
		try to match the pattern '<title>页面没找到[\s\S]*<\/title>' int the 'head' section, 
		you may change the pattern according to your site */
	/** in fact Typecho will break out as soon as 404 page is generate, there is no change to reach here */
	if( preg_match('/<head>[\s\S]*<\/head>/i', $html, $matches) ){
		if(preg_match('/<title>页面没找到[\s\S]*<\/title>/i', $matches[0]) ){
			debug_trace('no cache for 404 pages.');
			return false;
		}
	}

	return true;
}

/**
 *	check and catch the page content
 *	@param 	$html html content of the page
 *	@return none	
 */
function cache_new_page($html){
	global $redis;
	global $dkey;
	global $ukey;

	/** don't uncomment the line below! */
	//$html += '<!--page cached at ' . date("Y-m-d H:i:s") . '-->';
	
	$redis->hset($dkey, $ukey, $html);  
	debug_trace('the page is cached.'); 
}

/**
 *	check uri get get parameters to excute user actions
 *	@param 	none
 * 	@return none
 * 	@note 	a normal page with the uri will still be outputed after this action
 */
function check_user_actions(){
	global $userkey;
	global $redis;
	global $dkey;
	global $ukey;

	if( isset($_GET['userkey']) && isset($_GET['action']) && ($_GET['userkey'] == $userkey)) {
		if($_GET['action'] == 'purgeall'){
		  	if($redis->exists($dkey)) {
	        	$redis->del($dkey);
	        	debug_trace('domain cache purged.');
		    } 
		}
		else if($_GET['action'] == 'purgepage'){
			if($redis->hexists($dkey, $ukey)){
				$redis->hdel($dkey, $ukey);
				debug_trace('page cache purged.');
			}
		}
		else{
			debug_trace('nothing to do.');
		}
	}
	else{
		//debug_trace('invalid user key or no action is specified!');
	}
}

/**
 * 	implement of all functions
 *	@param 	none
 * 	@return none
 */
function do_action(){
	global $redis_host;
	global $redis_port;
	global $redis;

	debug_trace('setup redis connection...');

	/** make sure the page is still servered even if redis-server is down */
	try{
   		$redis = new Credis_Client($redis_host . ':' . $redis_port);

   		debug_trace('check user actions...');
   		check_user_actions();

   		/** check and cache */
		$result = is_cache_valid();
		if( isset($_POST['s']) ){
			$result = false;
			debug_trace('this is a search page, no valid cache');
		}
		
		if($result){
			echo load_cached_page();
			debug_trace('this is a redis cached page.');
		}
		else
		{
			$html = generate_new_page();
			echo $html;
			debug_trace('this is a fresh page.');
			if(check_if_cache_needed($html)){
				cache_new_page($html);
			}
		}
	} 
	catch (Exception $e) {
		echo generate_new_page();
		debug_trace('redis connection exception: ' . $e->getMessage());

		/** do something to wakeup redis */
		$dir = getenv('OPENSHIFT_DATA_DIR');
		shell_exec($dir . '/redis/check-and-start.sh');
	}
}

do_action();
$stop_time = microtime(true);
debug_trace('page processed in '. round(($stop_time-$start_time),5) . ' seconds');

?>