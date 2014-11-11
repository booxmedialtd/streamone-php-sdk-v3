<?php
/**
 * @addtogroup StreamOneSDK
 * @{
 */

require_once('StreamOneSessionStoreInterface.php');

/**
 * PHP session storage class
 */
class StreamOnePhpSessionStore implements StreamOneSessionStoreInterface
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
	 * @see StreamOneSessionStoreInterface::hasSession()
	 */
	public function hasSession()
	{
		$all_set = (isset($_SESSION[self::S1_SESSION_PREFIX . 'id']) &&
		            isset($_SESSION[self::S1_SESSION_PREFIX . 'key']) &&
		            isset($_SESSION[self::S1_SESSION_PREFIX . 'renew']) &&
		            isset($_SESSION[self::S1_SESSION_PREFIX . 'timeout']) &&
		            isset($_SESSION[self::S1_SESSION_PREFIX . 'user']) &&
		            is_string($_SESSION[self::S1_SESSION_PREFIX . 'id']) &&
		            is_string($_SESSION[self::S1_SESSION_PREFIX . 'key']) &&
		            is_numeric($_SESSION[self::S1_SESSION_PREFIX . 'renew']) &&
		            is_numeric($_SESSION[self::S1_SESSION_PREFIX . 'timeout']) &&
		            is_string($_SESSION[self::S1_SESSION_PREFIX . 'user']));

		if (!$all_set)
		{
			return false;
		}

		if ($_SESSION[self::S1_SESSION_PREFIX . 'timeout'] >= time())
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
	 * @see StreamOneSessionStoreInterface::getSession()
	 */
	public function getSession()
	{
		if (!$this->hasSession())
		{
			throw new Exception("No active session");
		}
		return array(
			'id' => $_SESSION[self::S1_SESSION_PREFIX . 'id'],
			'key' => $_SESSION[self::S1_SESSION_PREFIX . 'key'],
			'user' => $_SESSION[self::S1_SESSION_PREFIX . 'user']
		);
	}

	/**
	 * @see StreamOneSessionStoreInterface::getSession()
	 */
	public function clearSession()
	{
		unset($_SESSION[self::S1_SESSION_PREFIX . 'id']);
		unset($_SESSION[self::S1_SESSION_PREFIX . 'key']);
		unset($_SESSION[self::S1_SESSION_PREFIX . 'renew']);
		unset($_SESSION[self::S1_SESSION_PREFIX . 'timeout']);
		unset($_SESSION[self::S1_SESSION_PREFIX . 'user']);
	}

	/**
	 * @see StreamOneSessionStoreInterface::setSession()
	 */
	public function setSession($id, $key, $user, $renew, $timeout)
	{
		$_SESSION[self::S1_SESSION_PREFIX . 'id'] = $id;
		$_SESSION[self::S1_SESSION_PREFIX . 'key'] = $key;
		$_SESSION[self::S1_SESSION_PREFIX . 'user'] = $user;
		$_SESSION[self::S1_SESSION_PREFIX . 'renew'] = time() + $renew;
		$_SESSION[self::S1_SESSION_PREFIX . 'timeout'] = time() + $timeout;
	}

	/**
	 * @see StreamOneSessionStoreInterface::updateRenew()
	 */
	public function updateRenew($renew)
	{
		$_SESSION[self::S1_SESSION_PREFIX . 'renew'] = time() + $renew;
	}

	/**
	 * @see StreamOneSessionStoreInterface::updateTimeout()
	 */
	public function updateTimeout($timeout)
	{
		$_SESSION[self::S1_SESSION_PREFIX . 'timeout'] = time() + $timeout;
	}
}

/**
 * @}
 */
