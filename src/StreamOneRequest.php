<?php
/**
 * @package StreamOne
 */

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
 *     echo "Success!";
 * }
 * \endcode
 * 
 * This class only supports the in-development version of API v3.
 */
class StreamOneRequest
{
	/**
	 * The API command to call
	 */
	private $command;
	
	/**
	 * The action to perform on the API command called
	 */
	private $action;
	
	/**
	 * When the request must be signed with an active session, the session token to use
	 */
	private $session_token = null;
	
	/**
	 * When the request must be signed with an active session, the session key to use
	 */
	private $session_key = null;
	
	/**
	 * The parameters to use for the API request
	 * 
	 * The parameters are the GET-parameters sent, and include meta-data for the request such
	 * as API-version, output type, and authentication parameters. They cannot directly be set.
	 */
	private $parameters;
	
	/**
	 * The arguments to use for the API request
	 * 
	 * The arguments are the POST-data sent, and represent the arguments for the specific API
	 * command and action called.
	 */
	private $arguments;
	
	/**
	 * The plain-text response received from the API server
	 * 
	 * This is the plain-text response as received from the server, or null if no plain-text
	 * response has been received.
	 */
	private $plain_response;
	
	/**
	 * The parsed response received from the API
	 * 
	 * This is the parsed response as received from the server, or null if no parseable response
	 * has been received.
	 */
	private $response;
	
	/**
	 * The protocol to use for requests
	 */
	private $protocol = "http";
	
	/**
	 * Construct a new request
	 * 
	 * @param string $command
	 *   The API command to call
	 * @param string $action
	 *   The action to perform on the API command
	 * @param string $session_token
	 *   When the request must be signed with an active session, the session token to use
	 * @param string $session_key
	 *   When the request must be signed with an active session, the session key to use
	 */
	public function __construct($command, $action, $session_token = null, $session_key = null)
	{
		$this->command = $command;
		$this->action = $action;
		
		// Default parameters
		$this->parameters = array(
			'api' => 3,
			'format' => 'json',
			'authentication_type' => 'user'
		);
		
		// Check whether to use application authentication
		if (StreamOneConfig::$use_application_auth)
		{
			$this->parameters['authentication_type'] = 'application';
			
			// Store session token/key
			$this->session_token = $session_token;
			$this->session_key = $session_key;
		}
		else // user authentication
		{
			// Check if a default account is specified
			if (isset(StreamOneConfig::$default_account))
			{
				$this->parameters['account'] = StreamOneConfig::$default_account;
			}
		}
		
		// Initially, there are no arguments
		$this->arguments = array();
	}
	
	/**
	 * Set the account to use for this request
	 * 
	 * Most actions require an account to be set, but not all. Refer to the documentation of the
	 * action you are executing to read whether providing an account is required or not.
	 * 
	 * It is possible to specify a default account in StreamOneConfig. This method can be used
	 * to override that default account.
	 * 
	 * @param string $account
	 *   Hash of the account to use for the request
	 * @retval StreamOneRequest
	 *   A reference to this object, to allow chaining
	 */
	public function setAccount($account)
	{
		$this->parameters['account'] = $account;
	}
	
	/**
	 * Set the value of a single argument
	 * 
	 * @param string $argument
	 *   The name of the argument
	 * @param string $value
	 *   The new value for the argument
	 * @retval StreamOneRequest
	 *   A reference to this object, to allow chaining
	 */
	public function setArgument($argument, $value)
	{
		$this->arguments[$argument] = $value;
		
		return $this;
	}
	
	/**
	 * Retrieve the currently defined arguments
	 * 
	 * @retval array
	 *   An array containing the currently defined arguments as key=>value pairs
	 */
	public function arguments()
	{
		return $this->arguments;
	}
	
	/**
	 * Sets the protocol to use for requests
	 * 
	 * @param $protocol string
	 *   The protocol to use
	 * @retval StreamOneRequest
	 *   A reference to this object, to allow chaining
	 */
	public function setProtocol($protocol)
	{
		$this->protocol = $protocol;
		
		return $this;
	}
	
	/**
	 * Retrieves the protocol to use for requests, with trailing ://
	 * 
	 * @retval string
	 *   The protocol to use
	 */
	public function protocol()
	{
		return $this->protocol . "://";
	}
	
	/**
	 * Execute the prepared request
	 * 
	 * This will sign the request, send it to the Internal API server, and analyze the response. To
	 * check whether the request was successful and returned no error, use the method success().
	 * 
	 * @retval StreamOneRequest
	 *   A reference to this object, to allow chaining
	 */
	public function execute()
	{
		// Gather path, signed parameters and arguments
		$server = $this->protocol() . StreamOneConfig::$api_url;
		$path = $this->path();
		$parameters = $this->signedParameters();
		$arguments = $this->arguments();
		
		// Actually execute the request
		$response = $this->sendRequest($server, $path, $parameters, $arguments);
		
		// Handle the response
		$this->handleResponse($response);
		
		return $this;
	}
	
	/**
	 * Check if the returned response is valid
	 * 
	 * A valid response contains a header and a body, and the header contains at least the fields
	 * status and statusmessage with correct types.
	 * 
	 * @retval bool
	 *   Whether the retrieved response is valid
	 */
	public function valid()
	{
		// The response must be a valid array
		if (($this->response === null) || (!is_array($this->response)))
		{
			return false;
		}
		
		// The response must have a header and a body
		if (!array_key_exists('header', $this->response) ||
		    !array_key_exists('body', $this->response))
		{
			return false;
		}
		
		// The header must be an array and contain a status and statusmessage
		if (!is_array($this->response['header']) ||
		    !array_key_exists('status', $this->response['header']) ||
		    !array_key_exists('statusmessage', $this->response['header']))
		{
			return false;
		}
		
		// The status must be an integer and the statusmessage must be a string
		if (!is_int($this->response['header']['status']) ||
		    !is_string($this->response['header']['statusmessage']))
		{
			return false;
		}
		
		// All is valid
		return true;
	}
	
	/**
	 * Check if the request was successful
	 * 
	 * The request was successful if the response is valid, and the status is 0 (OK).
	 * 
	 * @retval bool
	 *   Whether the request was successful
	 */
	public function success()
	{
		return ($this->valid() && ($this->response['header']['status'] === 0));
	}
	
	/**
	 * Retrieve the header as received from the server
	 * 
	 * This method returns the response header as received from the server. If the response was
	 * not valid (check with valid()), this method will return null.
	 * 
	 * @retval array
	 *   The header of the received response; null if the response was not valid
	 */
	public function header()
	{
		if (!$this->valid())
		{
			return null;
		}
		
		return $this->response['header'];
	}
	
	/**
	 * Retrieve the body as received from the server
	 * 
	 * This method returns the response body as received from the server. If the response was
	 * not valid (check with valid()), this method will return null.
	 * 
	 * @retval array
	 *   The body of the received response; null if the response was not valid
	 */
	public function body()
	{
		if (!$this->valid())
		{
			return null;
		}
		
		return $this->response['body'];
	}
	
	/**
	 * Retrieve the plain-text response as received from the server
	 * 
	 * This method returns the entire plain-text response as received from the server. If there was
	 * no valid plain-text response, this method will return null.
	 * 
	 * @retval string
	 *   The plain-text response; null if no response was received
	 */
	public function plainResponse()
	{
		return $this->plain_response;
	}
	
	
	/**
	 * Retrieve the path to use for the API request
	 * 
	 * @retval string
	 *   The path for the API request
	 */
	protected function path()
	{
		return '/api/' . $this->command . '/' . $this->action;
	}
	
	/**
	 * Retrieve the currently defined parameters
	 * 
	 * @retval array
	 *   An array containing the currently defined parameters as key=>value pairs
	 */
	protected function parameters()
	{
		return $this->parameters;
	}
	
	/**
	 * Retrieve the signed parameters for the current request
	 * 
	 * This method will lookup the current path, parameters and arguments, calculates the
	 * authentication parameters, and returns the new set of parameters.
	 * 
	 * @retval array
	 *   An array containing the defined parameters, as well as authentication parameters, both as
	 *   key=>value pairs
	 */
	protected function signedParameters()
	{
		$path = $this->path();
		$parameters = $this->parameters();
		$arguments = $this->arguments();
		
		// Store a single timestamp to use for signing
		$ts = time();
		
		// Add basic authentication parameters
		$parameters['timestamp'] = $ts;
		if (StreamOneConfig::$use_application_auth)
		{
			$parameters['application'] = StreamOneConfig::$application;
			$key = StreamOneConfig::$application_key;
			
			// Possibly add session key
			if (isset($this->session_token) && isset($this->session_key))
			{
				$parameters['session'] = $this->session_token;
				$key .= $this->session_key;
			}
		}
		else
		{
			$parameters['user'] = StreamOneConfig::$user;
			$key = StreamOneConfig::$user_key;
		}
		
		// Calculate signature
		$url = $path . '?' . http_build_query($parameters) . '&' . http_build_query($arguments);
		$parameters['signature'] = md5($key . $url);
		
		var_dump($key . $url);
		
		return $parameters;
	}
	
	/**
	 * Actually send a signed request to the server
	 * 
	 * @param string $server
	 *   The API server to use
	 * @param string $path
	 *   The request path
	 * @param array $parameters
	 *   The request parameters as key=>value pairs
	 * @param array $arguments
	 *   The request arguments as key=>value pairs
	 * @retval string
	 *   The plain-text response from the server; false if the request failed
	 *
	 * @codeCoverageIgnore
	 *   This function is deliberately not included in unit tests
	 */
	protected function sendRequest($server, $path, $parameters, $arguments)
	{
		// Build the URL (including GET-params)
		$url = $server . $path . '?' . http_build_query($parameters);
		
		// Create the required stream context for POSTing
		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'content' => http_build_query($arguments),
				'header' => "Content-Type: application/x-www-form-urlencoded"
			)
		));
		
		// Actually do the request and return the response
		return file_get_contents($url, false, $context);
	}
	
	/**
	 * Handle a plain-text response as received from the API
	 * 
	 * @param mixed $response
	 *   The plain-text response as received from the API; parsing will not be succesful if this is
	 *   not a string.
	 */
	protected function handleResponse($response)
	{
		// Only attempt handling the response if it is a string
		if (is_string($response))
		{
			$this->plain_response = $response;
			
			// Attempt to decode the (JSON) response; returns null if failed
			$this->response = json_decode($response, true);
		}
	}
}
