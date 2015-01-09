<?php
/**
 * @addtogroup StreamOneSDK
 * @{
 */

namespace StreamOne\API\v3;

/**
 * This class contains the configuration options for the StreamOne related
 * classes. To change the configuration options, adjust the values of the
 * class member variables below.
 */
class Config
{
    /**
     * The URL at which the StreamOne API is available. There is usually no
     * need to change this. The given URL should not end in a slash ('/').
     * Do not include /api in this URL.
	 * 
	 * @TODO: figure out what to do with the protocol
     */
    public static $api_url = "api.streamone.nl";
    
    /**
     * The URL at which the the content server is available. There is usually no
     * need to change this. The given URL should not end in a slash ('/').
     */
    public static $content_url = "content.streamone.nl";
    
    /**
	 * Whether to behave as an application, or to use user/PSK-authentication
	 * 
	 * When set to false, requests will be made signed with the user specified in $user,
	 * using the preshared key specified in $user_key.
	 * 
	 * When set to true, requests will be made signed with the application specified in
	 * $application, using the preshared key specified in $application_key. To allow users to
	 * create sessions (using the Session class), it is required to use application
	 * authentication.
     */
    public static $use_application_auth = false;
    
	// The three settings below are only applicable when NOT using application authentication
	
    /**
	 * The user ID to sign requests with when not using application authentication.
     */
    public static $user = "User";
    
    /**
	 * The API key for the specified user, when not using application authentication.
	 * 
	 * This key is a shared secret between you and StreamOne. To find your key or generate a
	 * new one, log in into the StreamOne Control Panel. Make sure that this key remains secret.
     */
    public static $user_key = "UserPSK";
	
	/**
	 * The default account to use for all requests.
	 * 
	 * If specified, all requests will by default be executed for this account. To not specify a
	 * default account, set the value to null. If the default account is not specified here, it
	 * must be specified explicitly with all requests. Specifying it explicitly is always
	 * possible, regardless of a default account being set.
	 */
	public static $default_account = "Account";
	
	// The two settings below are only applicable when using application authentication
    
	/**
	 * The application ID to sign requests with when using application authentication
	 */
	public static $application = "Application";
	
	/**
	 * The API key for the specified user, when not using application authentication.
	 * 
	 * This key is a shared secret between you and StreamOne. To find your key or generate a
	 * new one, log in into the StreamOne Control Panel and view the details of the specified
	 * application. Make sure that this key remains secret.
	 */
	public static $application_key = "ApplicationPSK";
	
	// The remainder of the settings is again applicable to all situations
    
    /**
     * Show clearly visible warnings for certain errors returned by the API.
     * 
     * This array contains all status codes which should cause visible warnings. To disable
     * any visible warnings, empty this array. For details on the meaning of these status
     * codes, check the StreamOne API Documentation. The default value (errors 2, 3, 4, 5 and 7)
	 * shows errors which are usually caused by a wrong configuration or incorrect API usage.
     */
    public static $visible_errors = array(2,3,4,5,7);
    
    /**
     * The caching class to use, to be instantiated below.
     */
    public static $cache;

	/**
	 * The class to use for storing session information, to be instantiated below.
	 */
	public static $session_store;
}

// There are various caching classes available; uncomment the desired class and its related
// include file below. Exactly one cache needs to be present; use the Noop cache when no
// caching is desired.

/**
 * Noop caching system
 * 
 * The noop caching system (where 'noop' stands for 'no operation') is a caching class which
 * does not cache any requests. Use this caching class to avoid using any caching at all.
 */
// Config::$cache = new NoopCache();

/**
 * File caching system
 * 
 * The file caching system is a simple caching class which stores the cached objects as files
 * on your file system. The constructor has 2 arguments:
 * - The directory to write cache files to, e.g. '/tmp' (no trailing slash is required)
 *     This directory will be created if it does not exist yet
 * - The number of seconds that cache items are available before they expire
 */
Config::$cache = new FileCache("/tmp/s1_cache", 300);

/**
 * MemCache caching system
 *
 * The MemCache caching system can connect to a MemCache daemon (memcached) to use as a cache.
 * The constructor has 3 arguments:
 * - The hostname or IP address of the memcached server
 * - The port of the memcached server on the given host/ip
 * - The number of seconds that cache items are available before they expire
 */
// Config::$cache = new MemCache("localhost", 12111, 300);

// Which class to use for session data storage.

/**
 * PHP session storage
 *
 * This session store uses PHP's sessions to store session information. This will use session_start() and $_SESSION
 */
Config::$session_store = new PhpSessionStore();

/**
 * @}
 */
