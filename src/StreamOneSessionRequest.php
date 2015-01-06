<?php
/**
 * @addtogroup StreamOneSDK The StreamOne SDK
 *
 * @{
 */

/**
 * Execute a request to the StreamOne API with an active session
 * 
 * Note that it is only possible to use sessions when application authentication is enabled
 * in StreamOneConfig. Trying to use sessions with user authentication will always result in
 * an authentication error. Refer to the StreamOne Platform Documentation on Sessions for more
 * information on using sessions.
 */
class StreamOneSessionRequest extends StreamOneRequest
{
	/// The session store containing the required session information
	private $session_store;
	
	/**
	 * Initializes a request and sets the session
	 *
	 * @param string $command
	 *   The command to execute
	 * @param string $action
	 *   The action to execute
	 * @param StreamOneSessionStoreInterface $session_store
	 *   The session store containing the required session information
	 * 
	 * @throw InvalidArgumentException
	 *   Application authentication is not in use; it is required to use application
	 *   authentication for sessions to function
	 * 
	 * @see StreamOneRequestBase::__construct
	 */
	public function __construct($command, $action, StreamOneSessionStoreInterface $session_store)
	{
		if (!StreamOneConfig::$use_application_auth)
		{
			throw new InvalidArgumentException("Sessions are only supported when application authentication is used");
		}
		
		parent::__construct($command, $action);
		$this->session_store = $session_store;
	}
	
	/**
	 * Retrieve the key to use for signing the request
	 * 
	 * This method retrieves the signing key of the parent class and appends the session key.
	 * 
	 * @retval string
	 *   The key to use for signing requests
	 */
	protected function signingKey()
	{
		$key = parent::signingKey();
		
		return $key . $this->session_store->getKey();
	}
	
	/**
	 * Retrieve the parameters to include for signing this request
	 * 
	 * This method retrieves the signing parameters of its parent class, and adds the session
	 * parameter to the returned list of parameters.
	 *
	 * @retval array
	 *   An array containing the parameters needed for signing
	 */
	protected function parametersForSigning()
	{
		$parameters = parent::parametersForSigning();
		
		$parameters['session'] = $this->session_store->getId();
		
		return $parameters;
	}
	
	/**
	 * Execute the prepared request
	 * 
	 * After executing the request in the regular way, the retrieved headers are inspected
	 * and the session timeout is updated according to the new retrieved timeout.
	 *
	 * @see StreamOneRequestBase::execute
	 */
	public function execute()
	{
		parent::execute();

		$header = $this->header();
		
		if (isset($header['sessiontimeout']))
		{
			$this->session_store->setTimeout($header['sessiontimeout']);
		}
	}
}

/**
 * @}
 */
