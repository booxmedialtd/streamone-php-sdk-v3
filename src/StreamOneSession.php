<?php
/**
 * @addtogroup StreamOneSDK The StreamOne SDK
 *
 * @{
 */

require_once('StreamOneRequest.php');
require_once('StreamOneSessionRequest.php');
require_once('StreamOnePassword.php');

/**
 * Manage a session for use with the StreamOne platform
 */
class StreamOneSession
{
	/// The session store to use for this session
	private $session_store;
	
	/// The last request executed by the start() method
	private $start_request = null;
	
	/**
	 * Construct a new session object
	 * 
	 * The session object may or may not have an active session, depending on what is stored
	 * in the passed session store object.
	 * 
	 * @param StreamOneSessionStoreInterface $session_store
	 *   The session store to use for this session; if not given, use the one defined in
	 *   StreamOneConfig::$session_store;
	 */
	public function __construct(StreamOneSessionStoreInterface $session_store = null)
	{
		if ($session_store === null)
		{
			$this->session_store = StreamOneConfig::$session_store;
		}
		else
		{
			$this->session_store = $session_store;
		}
	}
	
	/**
	 * Check whether there is an active session
	 * 
	 * If there is no active session, it is only possible to start a new session.
	 * 
	 * @retval bool
	 *   True if and only if there is an active session
	 */
	public function isActive()
	{
		return $this->session_store->hasSession();
	}

	/**
	 * Create a new session with the StreamOne API.
	 * 
	 * To start a new session provide the username, password, and IP address of the user
	 * requesting the new session. The IP address is required for rate limiting purposes.
	 * 
	 * @param string $username
	 *   The username to use for this session
	 * @param string $password
	 *   The password to use for this session
	 * @param string $ip
	 *   The IP address of the user creating the session
	 *
	 * @retval bool
	 *   Whether the session has been started succesfully; if the session was not created
	 *   successfully, 
	 */
	public function start($username, $password, $ip)
	{
		// Initialize session to obtain challenge from API
		$request = new StreamOneRequest('session', 'initialize');
		$request->setArgument('user', $username);
		$request->setArgument('userip', $ip);
		$request->execute();
		
		$this->saveStartRequest($request);

		if (!$request->success())
		{
			return false;
		}

		$request_body = $request->body();
		$needs_v2_hash = $request_body['needsv2hash'];
		$salt = $request_body['salt'];
		$challenge = $request_body['challenge'];
		
		$response = StreamOnePassword::generatePasswordResponse($password, $salt, $challenge);

		// Initializing session was OK, try to start it
		$request = new StreamOneRequest('session', 'create');
		$request->setArgument('challenge', $challenge);
		$request->setArgument('response', $response);
		if ($needs_v2_hash)
		{
			$vs_hash = StreamOnePassword::generateV2PasswordHash($password);
			$request->setArgument('v2hash', $v2_hash);
		}
		$request->execute();

		$this->saveStartRequest($request);
		
		if (!$request->success())
		{
			return false;
		}

		$request_body = $request->body();

		$this->session_store->setSession($request_body['id'], $request_body['key'],
		                                 $request_body['user'], $request_body['timeout']);

		return true;
	}
	
	/**
	 * Save the request used in start() for later inspection by startStatus/startStatusMessage
	 * 
	 * @param StreamOneRequest $request
	 *   The request to save for later inspection
	 */
	protected function saveStartRequest(StreamOneRequest $request)
	{
		$this->start_request = $request;
	}
	
	/**
	 * The status of the last request of the last call to start()
	 * 
	 * Depending on what exactly went wrong, this can be either the session/initialize or the
	 * session/create API-call.
	 * 
	 * This method should not be called before start() is called.
	 * 
	 * @retval int
	 *   The status of the last request of the last call to start(), or null if the
	 *   reponse was invalid
	 * 
	 * @throw LogicException
	 *   The start() method has not been called on this instance
	 */
	public function startStatus()
	{
		if ($this->start_request === null)
		{
			throw new LogicException('The start() method has not been called on this instance');
		}
		
		if (!$this->start_request->valid())
		{
			return null;
		}
		
		return $this->start_request->status();
	}
	
	
	/**
	 * The status message of the last request of the last call to start()
	 * 
	 * Depending on what exactly went wrong, this can be either the session/initialize or the
	 * session/create API-call.
	 * 
	 * This method should not be called before start() is called.
	 * 
	 * @retval string
	 *   The status message of the last request of the last call to start(), or null if the
	 *   reponse was invalid
	 * 
	 * @throw LogicException
	 *   The start() method has not been called on this instance
	 */
	public function startStatusMessage()
	{
		if ($this->start_request === null)
		{
			throw new LogicException('The start() method has not been called on this instance');
		}
		
		if (!$this->start_request->valid())
		{
			return null;
		}
		
		return $this->start_request->statusMessage();
	}
	
	/**
	 * End the currently active session; i.e. log out the user
	 * 
	 * This method should only be called with an active session.
	 * 
	 * @throw LogicException
	 *   There is currently no active session
	 * 
	 * @retval bool
	 *   True if and only if the session was successfully deleted in the API. If not, the session
	 *   is still cleared from the session store and the session is therefore always inactive
	 *   after this method returns.
	 */
	public function end()
	{
		if (!$this->isActive())
		{
			throw new LogicException("No active session");
		}

		$request = $this->newRequest('session', 'delete');
		$request->execute();
		
		$this->session_store->clearSession();
		
		return $request->success();
	}

	/**
	 * Create a new request that uses the currently active session
	 * 
	 * This method should only be called with an active session.
	 * 
	 * @param string $command
	 *   The command for the new request
	 * @param string $action
	 *   The action for the new request
	 * @retval StreamOneSessionRequest
	 *   The new request using the currently active session for authentication
	 * 
	 * @throws LogicException
	 *   When no session is active
	 */
	public function newRequest($command, $action)
	{
		if (!$this->isActive())
		{
			throw new LogicException("No active session");
		}
		
		return new StreamOneSessionRequest($command, $action, $this->session_store);
	}

	/**
	 * Retrieve the ID of the user currently logged in with this session
	 * 
	 * This method can only be called if there is an active session.
	 * 
	 * @retval string
	 *   The ID of the user currently logged in with this session
	 * 
	 * @throw LogicException
	 *   There is no currently active session
	 */

	public function getUserId()
	{
		if (!$this->isActive())
		{
			throw new LogicException("No active session");
		}
		
		return $this->session_store->getUserId();
	}
}

/**
 * @}
 */
