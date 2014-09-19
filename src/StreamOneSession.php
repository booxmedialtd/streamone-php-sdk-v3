<?php
/**
 * @addtogroup StreamOneSDK The StreamOne SDK
 *
 * @{
 */

require_once('StreamOneRequest.php');
require_once('StreamOneSessionRequest.php');
require_once('StreamOnePassword.php');

class StreamOneSession
{
	private $success = false;
	private $code = null;
	private $message = null;

	/**
	 * Checks whether there is a session active. If not, you should let the user login somehow (e.g. send to login page)
	 *
	 * @retval bool
	 *   True if and only if there is an active session
	 */
	public function hasActiveSession()
	{
		return StreamOneConfig::$session_store->hasSession();
	}

	public function sessionUserId()
	{
		if (!$this->hasActiveSession())
		{
			return null;
		}
		$session = StreamOneConfig::$session_store->getSession();
		return $session['user'];
	}

	/**
	 * Create a new session with the StreamOne API.
	 *
	 * @param string $username
	 *   The username to use for this session
	 * @param string $password
	 *   The password to use for this session
	 * @param string $account
	 *   The account to use when creating the session. Set to null to not use an account
	 * @param string $customer
	 *   The customer to use when creating the session. Set to null to not use a customer
	 *
	 * @retval bool
	 *   Whether the session has been started succesfully. Use code() and message() to see the return code and message
	 */
	public function start($username, $password, $ip, $account = null, $customer = null)
	{
		$request = new StreamOneRequest('session', 'initialize');
		if ($account !== null)
		{
			$request->setAccount($account);
		}
		if ($customer !== null)
		{
			$request->setCustomer($customer);
		}
		$request->setArgument('user', $username);
		$request->setArgument('userip', $ip);
		$request->execute();

		if (!$request->success())
		{
			$this->success = false;
			$this->code = $request->status();
			$this->message = $request->statusMessage();
			return false;
		}

		$request_body = $request->body();
		$needs_v2_hash = $request_body['needsv2hash'];
		$salt = $request_body['salt'];
		$challenge = $request_body['challenge'];

		// Initializing session was OK, try to start it
		$request = new StreamOneRequest('session', 'create');
		if ($account !== null)
		{
			$request->setAccount($account);
		}
		if ($customer !== null)
		{
			$request->setCustomer($customer);
		}
		$request->setArgument('challenge', $challenge);
		$request->setArgument('response', StreamOnePassword::generatePasswordResponse($password, $salt, $challenge));
		if ($needs_v2_hash)
		{
			$request->setArgument('v2hash', StreamOnePassword::generateV2PasswordHash($password));
		}
		$request->execute();

		if (!$request->success())
		{
			$this->success = false;
			$this->code = $request->status();
			$this->message = $request->statusMessage();
			return false;
		}

		$request_body = $request->body();

		StreamOneConfig::$session_store->setSession($request_body['id'], $request_body['key'], $request_body['user'], $request_body['timeout']);


		$this->success = true;
		$this->code = $request->status();
		$this->message = $request->statusMessage();
		return true;
	}

	/**
	 * This will end the current session, i.e. log out the user
	 *
	 * @throws Exception
	 *   When no session is active
	 */
	public function end()
	{
		if (!$this->hasActiveSession())
		{
			throw new Exception('No active session');
		}

		$request = $this->newRequest('session', 'delete');
		$request->execute();

		StreamOneConfig::$session_store->clearSession();
	}

	/**
	 * Creata a new request that uses this session
	 *
	 * @param string $command
	 *   The command for the new request
	 * @param string $action
	 *   The action for the new request
	 * @retval StreamOneRequest
	 *   The request with the session enabled
	 * @throws Exception
	 *   When no session is active
	 */
	public function newRequest($command, $action)
	{
		if (!$this->hasActiveSession())
		{
			throw new Exception('No active session');
		}

		$session_data = StreamOneConfig::$session_store->getSession();
		$request = new StreamOneSessionRequest($command, $action, $session_data);

		return $request;
	}

	/**
	 * Returns whether the session was started successfully. Only useful after calling start()
	 *
	 * @retval bool
	 *   Whether the session was started successfully
	 */
	public function success()
	{
		return $this->success;
	}

	/**
	 * Returns the session status code. Only useful after calling start()
	 *
	 * @retval bool
	 *   The session status code
	 */
	public function code()
	{
		return $this->code;
	}

	/**
	 * Returns the session status message. Only useful after calling start()
	 *
	 * @retval bool
	 *   The session status message
	 */
	public function message()
	{
		return $this->message;
	}
}

/**
 * @}
 */
