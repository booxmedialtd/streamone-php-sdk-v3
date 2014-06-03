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
 * $request = new StreamOneRequest('items', 'view');
 * $request->setArgument('account', 'Mn9mdf')
 *         ->setArgument('item', 'vMD9k')
 *         ->execute();
 * if ($request->success())
 * {
 *     var_dump($request->body());
 * }
 * \endcode
 *
 * This class only supports the in-development version of API v3.
 */
class StreamOneRequest extends StreamOneRequestBase
{
	/**
	 * When the request must be signed with an active session, the session token to use
	 */
	private $session_token = null;
	
	/**
	 * When the request must be signed with an active session, the session key to use
	 */
	private $session_key = null;
	
	/**
	 * Whether the response was retrieved from the cache
	 */
	private $from_cache = false;

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
	 * sessions, call this method with null for both values.
	 * 
	 * Using sessions is only supported when application authentication is used. If this is not
	 * enabled, this method will do nothing.
	 * 
	 * @param string $token
	 *   The session token to use for this request
	 * @param string $key
	 *   The key to use with the specified session token
	 *
	 * @throws InvalidArgumentException
	 *   When application authentication is not used
	 */
	public function setSession($token, $key)
	{
		if (StreamOneConfig::$use_application_auth)
		{
			$this->session_token = $token;
			$this->session_key = $key;
		}
		else
		{
			throw new InvalidArgumentException("Sessions are only supported when application authentication is used");
		}
	}

	/**
	 * Execute the prepared request
	 *
	 * This will sign the request, send it to the Internal API server, and analyze the response. To
	 * check whether the request was successful and returned no error, use the method success().
	 *
	 * It will first check if the data is still available in the cache
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
	}

	/**
	 * @see StreamOneRequestBase::apiUrl
	 */
	protected function apiUrl()
	{
		return StreamOneConfig::$api_url;
	}

	/**
	 * @see StreamOneRequestBase::signingKey
	 */
	protected function signingKey()
	{
		if (StreamOneConfig::$use_application_auth)
		{
			$key = StreamOneConfig::$application_key;

			// Possibly add session key
			if (isset($this->session_token) && isset($this->session_key))
			{
				$key .= $this->session_key;
			}

			return $key;
		}
		else
		{
			return StreamOneConfig::$user_key;
		}
	}

	/**
	 * @see StreamOneRequestBase::parametersForSigning
	 */
	protected function parametersForSigning()
	{
		$parameters = parent::parametersForSigning();
		if (StreamOneConfig::$use_application_auth)
		{
			$parameters['application'] = StreamOneConfig::$application;

			// Possibly add session key
			if (isset($this->session_token) && isset($this->session_key))
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
	 * If the header contains an error status code set in StreamOneConfig::$visible_errors, a clear error will be
	 * shown on the screen
	 *
	 * @see StreamOneRequestBase::handleResponse
	 * 
	 * @param mixed $response
	 *   The plain-text response as received from the API; parsing will not be succesful if this is
	 *   not a string.
	 */
	protected function handleResponse($response)
	{
		parent::handleResponse($response);

		// Only attempt handling the response if it is a string
		if (is_string($response))
		{
			// Check the resulting header to see if we have a general error; report them
			$header = $this->header();
			if (in_array($header['status'], StreamOneConfig::$visible_errors))
			{
				echo '<div style="position:absolute;top:0;left:0;right:0;background-color:black;color:red;font-weight:bold;padding:5px 10px;border:3px outset #d00;z-index:2147483647;font-size:12pt;font-family:sans-serif;">StreamOne API error ' . $header['status'] . ': <em>' . htmlspecialchars($header['statusmessage']) . '</em></div>';
			}
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
