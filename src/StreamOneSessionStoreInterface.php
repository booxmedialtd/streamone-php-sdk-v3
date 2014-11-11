<?php
/**
 * @addtogroup StreamOneSDK
 * @{
 */

/**
 * Interface for session storage
 */
interface StreamOneSessionStoreInterface
{
	/**
	 * Determines if there is an active session
	 *
	 * @return bool True if and only if there is an active session
	 */
	public function hasSession();

	/**
	 * Gets the current active session
	 *
	 * @return array An array containing the session information, having an ID, key and uservar
	 */
	public function getSession();

	/**
	 * Clears the current active session
	 */
	public function clearSession();

	/**
	 * Save a session to this store
	 *
	 * @param string $id
	 *   The ID for this session
	 * @param string $key
	 *   The key for this session
	 * @param string $user
	 *   The user ID for this session
	 * @param int $renew
	 *   The number of seconds after when this session needs to be renwed
	 * @param int $timeout
	 *   The number of seconds before this session becomes invalid when not doing any requests
	 */
	public function setSession($id, $key, $user, $renew, $timeout);

	/**
	 * Update the renew moment of a session
	 *
	 * @param int $renew
	 *   The new renew moment for the active session
	 */
	public function updateRenew($renew);

	/**
	 * Update the timeout of a session
	 *
	 * @param int $timeout
	 *   The new timeout for the active session
	 */
	public function updateTimeout($timeout);
}

/**
 * @}
 */
