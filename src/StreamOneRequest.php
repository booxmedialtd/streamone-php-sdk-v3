<?php
/**
 * @addtogroup StreamOneSDK The StreamOne SDK
 * 
 * The StreamOne SDK contains various classes for communication with the StreamOne platform. All
 * classes work with configuration parameters defined in StreamOneConfig.php. To start working
 * with the SDK, copy StreamOneConfig.dist.php to StreamOneConfig.php and adjust the settings
 * where needed.
 * 
 * The central class in the SDK is the StreamOneRequest class, which is used to perform requests
 * to the external API.
 * 
 * @{
 */

// Include the base class
require_once('StreamOneRequestBase.php');

// Include configuration file, if no configuration has been defined yet
if (!class_exists('StreamOneConfig'))
{
	require_once('StreamOneConfig.php');
}

/**
 * Execute a request to the StreamOne API
 * 
 * This class represents a request to the StreamOne API. To execute a new request, first construct
 * an instance of this class by specifying the command and action to the constructor. The various
 * arguments and options of the request can then be specified and then the request can be actually
 * sent to the StreamOne API server by executing the request. There are various functions to
 * inspect the retrieved response.
 * 
 * \code
 * $request = new StreamOneRequest('item', 'view');
 * $request->setAccount('Mn9mdVb-02mA')
 *         ->setArgument('item', 'vMD_9k1SmkS5')
 *         ->execute();
 * if ($request->success())
 * {
 *     var_dump($request->body());
 * }
 * \endcode
 *
 * This class only supports version 3 of the StreamOne API. All configuration is done using the
 * StreamOneConfig class.
 * 
 * This class inherits from StreamOneRequestBase, which is a very basic request-class implementing
 * only the basics of setting arguments and parameters, and generic signing of requests. This
 * class adds specific signing for users, applications and sessions, as well as a basic caching
 * mechanism.
 */
class StreamOneRequest extends StreamOneRequestBase
{
	/**
	 * When the request must be signed with an active session, the session token to use
	 */
	private $session_token = null;
	
	/**
	 * When the request must be signed with an active session, the session ID to use
	 */
	private $session_id = null;
	
	/**
	 * Whether the response was retrieved from the cache
	 */
	private $from_cache = false;

	/**
	 * If the response was retrieved from the cache, how old it is in seconds; otherwise null
	 */
	private $cache_age = null;

	/**
	 * @see StreamOneRequestBase::__construct
	 */
	public function __construct($command, $action)
	{
		parent::__construct($command, $action);
		
		// Check whether to use application authentication
		if (StreamOneConfig::$use_application_auth)
		{
			$this->parameters['authentication_type'] = 'application';
		}
		else // user authentication
		{
			$this->parameters['authentication_type'] = 'user';
		}
	}
	
	/**
	 * Set the session information to use for this request
	 * 
	 * By providing the session information, sessions are enabled for this request. To disable
	 * sessions again, call this method with null for both values.
	 * 
	 * Using sessions is only supported when application authentication is used.
	 * 
	 * @param string $id
	 *   The session token to use for this request
	 * @param string $key
	 *   The key to use with the specified session token
	 *
	 * @throws InvalidArgumentException
	 *   When application authentication is not used
	 */
	public function setSession($id, $key)
	{
		if (StreamOneConfig::$use_application_auth)
		{
			$this->session_token = $id;
			$this->session_id = $key;
		}
		else
		{
			throw new InvalidArgumentException("Sessions are only supported when application authentication is used");
		}
	}

	/**
	 * Execute the prepared request
	 *
	 * This method will first check if there is a cached response for this request. If there is,
	 * the cached response is used. Otherwise, the request is signed and sent to the API server.
	 * The response will be stored in this class for inspection, and in the cache if applicable
	 * for this request.
	 * 
	 * To check whether the request was successful, use the success() method. The header and body
	 * of the response can be obtained using the header() and body() methods of this class. A
	 * request can be unsuccessful because either the response was invalid (check using the valid()
	 * method), or because the status in the header was not OK / 0 (check using the status() and
	 * statusMessage() methods.)
	 *
	 * @see StreamOneRequestBase::execute
	 *
	 * @retval StreamOneRequest
	 *   A reference to this object, to allow chaining
	 */
	public function execute()
	{
		// Check cache
		$response = $this->retrieveCache();
		if ($response === false)
		{
			parent::execute();
		}
		else
		{
			$this->handleResponse($response);
		}

		$this->saveCache();
		
		return $this;
	}

	/**
	 * Retrieve whether this response was retrieved from cache
	 *
	 * @retval bool
	 *   True if and only if the response was retrieved from cache
	 */
	public function fromCache()
	{
		return $this->from_cache;
	}

	/**
	 * Retrieve the age of the response retrieved from cache
	 *
	 * @retval int
	 *   The age of the response retrieved from cache in seconds. If the response was not
	 *   retrieved from cache, this will return null instead.
	 */
	public function cacheAge()
	{
		return $this->cache_age;
	}

	/**
	 * Retrieve the URL of the StreamOne API server to use.
	 * 
	 * @see StreamOneRequestBase::apiUrl
	 */
	protected function apiUrl()
	{
		return StreamOneConfig::$api_url;
	}

	/**
	 * Retrieve the key to use for signing this request.
	 *
	 * @see StreamOneRequestBase::signingKey
	 */
	protected function signingKey()
	{
		if (StreamOneConfig::$use_application_auth)
		{
			// Application authentication: return the application pre-shared key, with the session
			// key appended if a session is currently active.
			$key = StreamOneConfig::$application_key;

			if (isset($this->session_token) && isset($this->session_id))
			{
				$key .= $this->session_id;
			}

			return $key;
		}
		else
		{
			// User authentication: return the user pre-shared key.
			return StreamOneConfig::$user_key;
		}
	}

	/**
	 * Retrieve the parameters to include for signing this request.
	 *
	 * @see StreamOneRequestBase::parametersForSigning
	 */
	protected function parametersForSigning()
	{
		$parameters = parent::parametersForSigning();
		if (StreamOneConfig::$use_application_auth)
		{
			$parameters['application'] = StreamOneConfig::$application;

			// Possibly add session key
			if (isset($this->session_token) && isset($this->session_id))
			{
				$parameters['session'] = $this->session_token;
			}
		}
		else
		{
			$parameters['user'] = StreamOneConfig::$user;

			// Check if a default account is specified
			if (!isset($parameters['account']) && !isset($parameters['customer']) &&
			    isset(StreamOneConfig::$default_account))
			{
				$parameters['account'] = StreamOneConfig::$default_account;
			}
		}

		return $parameters;
	}
	
	/**
	 * Handle a plain-text response as received from the API
	 * 
	 * If the request was valid and contains one of the status codes set in
	 * StreamOneConfig::$visible_errors, a very noticable error message will be shown on the
	 * screen. It is advisable that these errors are handled and logged in a less visible manner,
	 * and that the visible_errors configuration variable is then set to an empty array. This is
	 * not done by default to aid in catching these errors during development.
	 *
	 * @see StreamOneRequestBase::handleResponse
	 * 
	 * @param mixed $response
	 *   The plain-text response as received from the API
	 */
	protected function handleResponse($response)
	{
		parent::handleResponse($response);

		// Check if the response was valid and the status code is one of the visible errors
		if ($this->valid() && in_array($this->status(), StreamOneConfig::$visible_errors))
		{
			echo '<div style="position:absolute;top:0;left:0;right:0;background-color:black;color:red;font-weight:bold;padding:5px 10px;border:3px outset #d00;z-index:2147483647;font-size:12pt;font-family:sans-serif;">StreamOne API error ' . $this->status() . ': <em>' . $this->statusMessage() . '</em></div>';
		}
	}
	
	/**
	 * Check whether the response is cacheable
	 * 
	 * @retval bool
	 *   True if and only if a successful response was given, which is cacheable
	 */
	protected function cacheable()
	{
		if ($this->success())
		{
			$header = $this->header();
			if (array_key_exists('cacheable', $header) && $header['cacheable'])
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Determine the key to use for caching
	 * 
	 * @retval string
	 *   A cache-key representing this request
	 */
	protected function cacheKey()
	{
		return $this->path() . '?' . http_build_query($this->parameters()) . '#' .
			http_build_query($this->arguments());
	}
	
	/**
	 * Attempt to retrieve the result for the current request from the cache
	 * 
	 * @retval string
	 *   The cached plain text response if it was found in the cache; false otherwise
	 */
	protected function retrieveCache()
	{
		$response = StreamOneConfig::$cache->get($this->cacheKey());
		
		if ($response !== false)
		{
			$this->from_cache = true;
			$this->cache_age = StreamOneConfig::$cache->age($this->cacheKey());
			return $response;
		}
		
		// No cache hit
		return false;
	}
	
	/**
	 * Save the result of the current request to the cache
	 * 
	 * This method only saves to cache if the request is cacheable, and if the request was not
	 * retrieved from the cache.
	 */
	protected function saveCache()
	{
		if ($this->cacheable() && !$this->from_cache)
		{
			StreamOneConfig::$cache->set($this->cacheKey(), $this->plainResponse());
		}
	}
}

/**
 * @}
 */
