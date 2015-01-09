<?php
/**
 * @addtogroup StreamOneSDK
 * @{
 */

namespace StreamOne\API\v3;

/**
 * Interface for session storage
 */
interface SessionStoreInterface
{
	/**
	 * Determines if there is an active session
	 *
	 * @return bool True if and only if there is an active session
	 */
	public function hasSession();

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
	 * @param string $user_id
	 *   The user ID for this session
	 * @param int $timeout
	 *   The number of seconds before this session becomes invalid when not doing any requests
	 */
	public function setSession($id, $key, $user_id, $timeout);
	
	/**
	 * Update the timeout of a session
	 *
	 * @param int $timeout
	 *   The new timeout for the active session, in seconds from now
	 */
	public function setTimeout($timeout);
	
	/**
	 * Retrieve the current session ID
	 * 
	 * The behavior of this function is undefined if there is no active session.
	 * 
	 * @retval string
	 *   The current session ID
	 */
	public function getId();
	
	/**
	 * Retrieve the current session key
	 * 
	 * The behavior of this function is undefined if there is no active session.
	 * 
	 * @retval string
	 *   The current session key
	 */
	public function getKey();
	
	/**
	 * Retrieve the ID of the user logged in with the current session
	 * 
	 * The behavior of this function is undefined if there is no active session.
	 * 
	 * @retval string
	 *   Retrieve the ID of the user logged in with the current session
	 */
	public function getUserId();
	
	/**
	 * Retrieve the current session timeout
	 * 
	 * The behavior of this function is undefined if there is no active session.
	 * 
	 * @retval int
	 *   The number of seconds before this session expires; negative if the session has expired
	 */
	public function getTimeout();
}

/**
 * @}
 */
