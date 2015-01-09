<?php
/**
 * @addtogroup StreamOneSDK
 * @{
 */

namespace StreamOne\API\v3;

/**
 * PHP session storage class
 */
class PhpSessionStore implements SessionStoreInterface
{
	const S1_SESSION_PREFIX = 'streamone_session_';

	/**
	 * Start the PHP session if not already done so
	 */
	public function __construct()
	{
		if (session_id() == "")
		{
			session_start();
		}
	}

	/**
	 * @copydoc SessionStoreInterface::hasSession()
	 */
	public function hasSession()
	{
		$all_set = (isset($_SESSION[self::S1_SESSION_PREFIX . 'id']) &&
		            isset($_SESSION[self::S1_SESSION_PREFIX . 'key']) &&
		            isset($_SESSION[self::S1_SESSION_PREFIX . 'timeout']) &&
		            isset($_SESSION[self::S1_SESSION_PREFIX . 'user']) &&
		            is_string($_SESSION[self::S1_SESSION_PREFIX . 'id']) &&
		            is_string($_SESSION[self::S1_SESSION_PREFIX . 'key']) &&
		            is_numeric($_SESSION[self::S1_SESSION_PREFIX . 'timeout']) &&
		            is_string($_SESSION[self::S1_SESSION_PREFIX . 'user']));

		if (!$all_set)
		{
			return false;
		}

		if ($_SESSION[self::S1_SESSION_PREFIX . 'timeout'] > time())
		{
			return true;
		}
		else
		{
			$this->clearSession();
		}
		
		return false;
	}

	/**
	 * @copydoc SessionStoreInterface::clearSession()
	 */
	public function clearSession()
	{
		unset($_SESSION[self::S1_SESSION_PREFIX . 'id']);
		unset($_SESSION[self::S1_SESSION_PREFIX . 'key']);
		unset($_SESSION[self::S1_SESSION_PREFIX . 'timeout']);
		unset($_SESSION[self::S1_SESSION_PREFIX . 'user']);
	}

	/**
	 * @copydoc SessionStoreInterface::setSession()
	 */
	public function setSession($id, $key, $user_id, $timeout)
	{
		$_SESSION[self::S1_SESSION_PREFIX . 'id'] = $id;
		$_SESSION[self::S1_SESSION_PREFIX . 'key'] = $key;
		$_SESSION[self::S1_SESSION_PREFIX . 'user'] = $user_id;
		$_SESSION[self::S1_SESSION_PREFIX . 'timeout'] = time() + $timeout;
	}

	/**
	 * @copydoc SessionStoreInterface::setTimeout()
	 */
	public function setTimeout($timeout)
	{
		$_SESSION[self::S1_SESSION_PREFIX . 'timeout'] = time() + $timeout;
	}
	
	/**
	 * @copydoc SessionStoreInterface::getId()
	 */
	public function getId()
	{
		return $_SESSION[self::S1_SESSION_PREFIX . 'id'];
	}
	
	/**
	 * @copydoc SessionStoreInterface::getKey()
	 */
	public function getKey()
	{
		return $_SESSION[self::S1_SESSION_PREFIX . 'key'];
	}
	
	/**
	 * @copydoc SessionStoreInterface::getUserId()
	 */
	public function getUserId()
	{
		return $_SESSION[self::S1_SESSION_PREFIX . 'user'];
	}
	
	/**
	 * @copydoc SessionStoreInterface::getTimeout()
	 */
	public function getTimeout()
	{
		// Return difference between timeout moment and now
		return ($_SESSION[self::S1_SESSION_PREFIX . 'timeout'] - time());
	}
}

/**
 * @}
 */
