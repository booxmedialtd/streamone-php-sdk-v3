<?php
/**
 * @addtogroup StreamOneSDK
 * @{
 */

namespace StreamOne\API\v3;

/**
 * The configuration for the StreamOne SDK.
 * 
 * This class is used internally by the SDK to get the correct configuration values to the
 * correct places. It is not instantiated correctly, but the constructor is called with an
 * array of options when a Platform is constructed.
 */
class Config
{
	/// Unknown authentication type
	const AUTH_UNKNOWN = '-';
	/// User authentication type
	const AUTH_USER = 'user';
	/// Application authentication type
	const AUTH_APPLICATION = 'application';
	
	/// API URL
	private $api_url = "http://api.streamoneonecloud.net";
	
	/// Authentication type
	private $authentication_type = self::AUTH_UNKNOWN;
	
	/// Actor ID for authentication
	private $auth_actor_id = '';
	
	/// Actor pre-shared key for authentication
	private $auth_actor_psk = '';
	
	/// Default account id
	private $default_account_id = null;
	
	/// Array of status codes to show a very visible error for
	private $visible_errors = array(2,3,4,5,7);
	
	/// Caching object to use; must implement CacheInterface
	private $cache = null;
	
	/// Default session store to use; must implement SessionStoreInterface
	private $session_store = null;
	
	
	/**
	 * Construct an instance of the Config class
	 * 
	 * The $options argument can be used to set the following options:
	 * 
	 * - api_url: URL of the API
	 *            - see setApiUrl()
	 * - authentication_type: authentication type to use either 'user' or 'application'
	 *                        - see setUserAuthentication() and setApplicationAuthentication()
	 * - application_id: application ID to use for application authentication; only used if
	 *                   authentication_type is 'application'
	 *                   - see setApplicationAuthentication()
	 * - application_psk: application pre-shared key to use for application authentication; only
	 *                    used if authentication_type is 'application'
	 *                    - see setApplicationAuthentication()
	 * - user_id: user ID to use for user authentication; only used if authentication_type
	 *            is 'user'
	 *            - see setUserAuthentication()
	 * - user_psk: user pre-shared key to use for user authentication; only used if
	 *             authentication_type is 'user'
	 *             - see setUserAuthentication()
	 * - default_account_id: account ID of the default account to use for all requests; leave
	 *                       empty to use no account by default
	 *                       - see setDefaultAccountId()
	 * - visible_errors: an array of status codes that result in a very visible error bar
	 *                   - see setVisibleErrors()
	 * - cache: caching object to use; must implement CacheInterface
	 *          - see setCache()
	 * - session_store: session store to use; must implement SessionStoreInterface
	 *                  - see setSessionStore()
	 * 
	 * @param array $options
	 *   A key=>value array of options to use
	 * 
	 * @throws \InvalidArgumentException
	 *   An authentication type is provided, but the necessary fields for that authentication type
	 *   are not provided. For user authentication, user_id and user_psk must be provided. For
	 *   application authentication, application_id and application_psk must be provided.
	 */
	public function __construct(array $options)
	{
		// Instantiate default cache and session store
		$this->cache = new NoopCache;
		$this->session_store = new PhpSessionStore;
		
		// Resolve options from options array
		$allowed_options = array(
			'api_url' => 'setApiUrl',
			'default_account_id' => 'setDefaultAccountId',
			'visible_errors' => 'setVisibleErrors',
			'cache' => 'setCache',
			'session_store' => 'setSessionStore'
		);
		
		foreach ($allowed_options as $key => $method)
		{
			if (array_key_exists($key, $options))
			{
				call_user_func(array($this, $method), $options[$key]);
			}
		}
		
		// Check authentication options
		if (array_key_exists('authentication_type', $options))
		{
			switch ($options['authentication_type'])
			{
				case 'user':
					if (!array_key_exists('user_id', $options) ||
						!array_key_exists('user_psk', $options))
					{
						throw new \InvalidArgumentException("Missing user_id or user_psk");
					}
					$this->setUserAuthentication($options['user_id'], $options['user_psk']);
					break;
				
				case 'application':
					if (!array_key_exists('application_id', $options) ||
						!array_key_exists('application_psk', $options))
					{
						throw new \InvalidArgumentException("Missing application_id or application_psk");
					}
					$this->setApplicationAuthentication($options['application_id'],
					                                    $options['application_psk']);
					break;
				
				default:
					throw new \InvalidArgumentException("Unknown authentication type '" .
					                                   $options['authentication_type'] . "'");
			}
		}
	}
	
	
	/**
	 * Set the API URL to use for API requests
	 * 
	 * The API URL must be a fully-qualified URL to the API. By default, the API URL for the
	 * StreamOne Cloud Platform (http://api.streamonecloud.net) is used. There is usually no
	 * need to change this unless a private deployment of the platform is used.
	 * 
	 * @param string $url
	 *   The API URL to use
	 */
	public function setApiUrl($url)
	{
		$this->api_url = $url;
	}
	
	/**
	 * Retrieve the API URL used for API requests
	 * 
	 * @see setApiUrl()
	 * 
	 * @retval string
	 *   The API URL to use
	 */
	public function getApiUrl()
	{
		return $this->api_url;
	}
	
	
	/**
	 * Enable user authentication with the given user ID and pre-shared key
	 * 
	 * @param string $user_id
	 *   User ID of the user to use for authentication
	 * @param string $user_psk
	 *   User pre-shared key of the user to use for authentication
	 */
	public function setUserAuthentication($user_id, $user_psk)
	{
		$this->authentication_type = self::AUTH_USER;
		$this->auth_actor_id = $user_id;
		$this->auth_actor_psk = $user_psk;
	}
	
	/**
	 * Enable application authentication with the given application ID and pre-shared key
	 * 
	 * @param string $application_id
	 *   Application ID of the application to use for authentication
	 * @param string $application_psk
	 *   Application pre-shared key of the application to use for authentication
	 */
	public function setApplicationAuthentication($application_id, $application_psk)
	{
		$this->authentication_type = self::AUTH_APPLICATION;
		$this->auth_actor_id = $application_id;
		$this->auth_actor_psk = $application_psk;
	}
	
	/**
	 * Get the currently enabled authentication type
	 * 
	 * @retval mixed
	 *   One of the following values:
	 *   - Config::AUTH_UNKNOWN if no authentication type is configured
	 *   - Config::AUTH_USER if user authentication is enabled
	 *   - Config::AUTH_APPLICATION if application authentication is enabled
	 */
	public function getAuthenticationType()
	{
		return $this->authentication_type;
	}
	
	/**
	 * Get the current actor ID used for authentication
	 * 
	 * When user authentication is enabled, this returns the user ID to use.
	 * 
	 * When application authentication is enabled, this returns the application ID to use.
	 * 
	 * @retval string
	 *   The actor ID to use for authentication
	 */
	public function getAuthenticationActorId()
	{
		return $this->auth_actor_id;
	}
	
	/**
	 * Get the current actor pre-shared key used for authentication
	 * 
	 * When user authentication is enabled, this returns the user PSK to use.
	 * 
	 * When application authentication is enabled, this returns the application PSK to use.
	 * 
	 * @retval string
	 *   The actor pre-shared key to use for authentication
	 */
	public function getAuthenticationActorKey()
	{
		return $this->auth_actor_psk;
	}
	
	
	/**
	 * Set the default account ID to use for API requests
	 * 
	 * If a default account is set, new requests obtained from Platform::newRequest will by
	 * default use that account. It is still possible to override this by using
	 * Request::setAccount() on the obtained request.
	 * 
	 * @param string $account_id
	 *   Default account ID for API requests; null to disable
	 */
	public function setDefaultAccountId($account_id)
	{
		$this->default_account_id = $account_id;
	}
	
	/**
	 * Get the default account ID to use for API requests
	 * 
	 * @retval string
	 *   Default account ID for API requests; null if not enabled
	 */
	public function getDefaultAccountId()
	{
		return $this->default_account_id;
	}
	
	/**
	 * Check if a default account ID is specified
	 * 
	 * @retval bool
	 *   True if and only if a default account ID is specified
	 */
	public function hasDefaultAccountId()
	{
		return ($this->default_account_id !== null);
	}
	
	
	/**
	 * Set the statuses which will give large visible errors when received
	 * 
	 * The Request class will insert HTML code to display a large visible error bar on top of
	 * the page when API requests return one of the status codes set for this option. To
	 * disable any visible warnings, set this option to an empty array.
	 * 
	 * The default value (status codes 2, 3, 4, 5 and 7) shows errors which are usually caused
	 * by a wrong configuration option or incorrect API usage. These are enabled by default to
	 * aid in development, and it is strongly recommended to disable visible errors in a
	 * production environment.
	 * 
	 * @param array $visible_errors
	 *   The status codes to display visible errors for; use an empty array to show no errors
	 */
	public function setVisibleErrors(array $visible_errors)
	{
		$this->visible_errors = $visible_errors;
	}
	
	/**
	 * Get the status codes which will result in large visible errors when received
	 * 
	 * @retval array
	 *   The status codes to display visible errors for
	 */
	public function getVisibleErrors()
	{
		return $this->visible_errors;
	}
	
	/**
	 * Check if a given status code should produce a visible error
	 * 
	 * @param int $status
	 *   The status code to check
	 * @retval bool
	 *   True if and only if the given status code should produce a visible error
	 */
	public function isVisibleError($status)
	{
		return in_array($status, $this->getVisibleErrors());
	}
	
	
	/**
	 * Set the caching object to use
	 * 
	 * The caching object will be used by the Request class to cache requests when appropiate.
	 * Any caching object used must implement the CacheInterface.
	 * 
	 * The SDK provides the following caching classes:
	 * - NoopCache, which will not cache anything (default)
	 * - FileCache, which will cache to files on disk
	 * - MemCache, which will cache on a memcached server
	 * 
	 * @param CacheInterface $cache
	 *   The caching object to use
	 */
	public function setCache(CacheInterface $cache)
	{
		$this->cache = $cache;
	}
	
	/**
	 * Get the caching object used
	 * 
	 * @retval CacheInterface
	 *   The caching object used
	 */
	public function getCache()
	{
		return $this->cache;
	}
	
	
	/**
	 * Set the session store to use
	 * 
	 * The session store will be used by default by Session to store information on the currently
	 * active session. Any session store used must implement the SessionStoreInterface.
	 * 
	 * The SDK provides the following session stores:
	 * - MemorySessionStore, which will only save the information in memory for the duration
	 *                       of the current script
	 * - PhpSessionStore, which will save the information in a PHP session (default)
	 * 
	 * @param SessionStoreInterface $session_store
	 *   The session store to use
	 */
	public function setSessionStore(SessionStoreInterface $session_store)
	{
		$this->session_store = $session_store;
	}
	
	/**
	 * Get the session store used
	 * 
	 * @retval SessionStoreInterface
	 *   The session store used
	 */
	public function getSessionStore()
	{
		return $this->session_store;
	}
	
	
	/**
	 * Check whether this Config object can be used for performing Requests
	 * 
	 * This method checks the configuration to ensure that it is suitable to use for performing
	 * Requests to the API. To be suitable, the following conditions must be met
	 * 
	 * - Either user authentication or application authentication must be configured with
	 *   non-empty actor ID and pre-shared key
	 * 
	 * @retval bool
	 *   True if and only if the Config object can be used for performing Requests
	 */
	public function validateForRequests()
	{
		return (
			in_array($this->getAuthenticationType(),
			         array(self::AUTH_USER, self::AUTH_APPLICATION)) &&
			(strlen($this->getAuthenticationActorId()) > 0) &&
			(strlen($this->getAuthenticationActorKey()) > 0)
		);
	}
}

/**
 * @}
 */
