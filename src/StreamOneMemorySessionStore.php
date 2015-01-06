<?php
/**
 * @addtogroup StreamOneSDK
 * @{
 */

require_once('StreamOneSessionStoreInterface.php');

/**
 * In-memory session storage class
 * 
 * Values in instances of  session store are only known for the lifetime of the instance, and
 * will be discarded once the instance is destroyed.
 */
class StreamOneMemorySessionStore implements StreamOneSessionStoreInterface
{
	/// The current session ID; null if no active session
	private $id = null;
	/// The current session key; null if no active session
	private $key = null;
	/// The current session timeout as absolute timestamp; null if no active session
	private $timeout = null;
	/// The user ID of the user logged in with the current session; null if no active session
	private $user_id = null;
	
	/**
	 * @copydoc StreamOneSessionStoreInterface::hasSession()
	 */
	public function hasSession()
	{
		// All variables need to be set to a non-null value for an active session
		if (($this->id === null) || ($this->key === null) || ($this->timeout === null) ||
		    ($this->user_id === null))
		{
			return false;
		}
		
		// The timeout must not have passed yed
		if ($this->timeout < time())
		{
			$this->clearSession();
			return false;
		}
		
		// All checks passed; there is an active session
		return true;
	}

	/**
	 * @copydoc StreamOneSessionStoreInterface::clearSession()
	 */
	public function clearSession()
	{
		$this->id = null;
		$this->key = null;
		$this->timeout = null;
		$this->user_id = null;
	}

	/**
	 * @copydoc StreamOneSessionStoreInterface::setSession()
	 */
	public function setSession($id, $key, $user_id, $timeout)
	{
		$this->id = $id;
		$this->key = $key;
		$this->user_id = $user_id;
		$this->timeout = time() + $timeout;
	}

	/**
	 * @copydoc StreamOneSessionStoreInterface::setTimeout()
	 */
	public function setTimeout($timeout)
	{
		$this->timeout = time() + $timeout;
	}
	
	/**
	 * @copydoc StreamOneSessionStoreInterface::getId()
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * @copydoc StreamOneSessionStoreInterface::getKey()
	 */
	public function getKey()
	{
		return $this->key;
	}
	
	/**
	 * @copydoc StreamOneSessionStoreInterface::getUserId()
	 */
	public function getUserId()
	{
		return $this->user_id;
	}
	
	/**
	 * @copydoc StreamOneSessionStoreInterface::getTimeout()
	 */
	public function getTimeout()
	{
		// Return difference between timeout moment and now
		return $this->timeout - time();
	}
}

/**
 * @}
 */
