<?php

/**
 * Abstract class providing tests for a class implementing StreamOneSessionStoreInterface
 */
abstract class SessionStoreInterfaceTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Construct an (empty) session store to test
	 * 
	 * @retval StreamOneSessionStoreInterface
	 *   An instantiated session store to run the tests on
	 */
	abstract protected function constructSessionStore();
	
	/**
	 * Test whether a session store is empty by default
	 */
	public function testDefault()
	{
		$store = $this->constructSessionStore();
		$this->assertFalse($store->hasSession());
	}
	
	/**
	 * Test whether clearing a session really removes it
	 */
	public function testClear()
	{
		// Construct store and set a session
		$store = $this->constructSessionStore();
		$store->setSession('id', 'key', 'user', 10);
		
		// There must now be an active session
		$this->assertTrue($store->hasSession());
		
		// Clear the store, and the active session must be gone
		$store->clearSession();
		$this->assertFalse($store->hasSession());
	}
	
	/**
	 * Test whether the basic property (id, key, user_id) values are stored and retrieved correctly
	 * 
	 * @param string $id
	 *   Session ID
	 * @param string $key
	 *   Session key
	 * @param string $user_id
	 *   Session user ID
	 * 
	 * @dataProvider provideProperties
	 */
	public function testProperties($id, $key, $user_id)
	{
		// Hardcoded timeout of 10; not checked here
		$timeout = 10;
		
		// Obtain an empty session store and store session information in it
		$store = $this->constructSessionStore();
		$store->setSession($id, $key, $user_id, $timeout);
		
		// The store must now have a session
		$this->assertTrue($store->hasSession());
		
		// Check whether all properties are saved correctly
		$this->assertSame($id, $store->getId());
		$this->assertSame($key, $store->getKey());
		$this->assertSame($user_id, $store->getUserId());
	}
	
	public function provideProperties()
	{
		return array(
			array('id', 'key', 'user_id'),
			array('7JhNCK-SWtEi', 'fAoMLYOCEpEi', '_i5EDeMSEwIm')
		);
	}
	
	/**
	 * Test whether timeouts are stored correctly
	 */
	public function testInitialTimeout()
	{
		// Use a fixed timeout
		$timeout = 10;
		
		// Store current time to obtain a bound on maximum timeout change
		$start_time = time();
		
		// Construct store and set a session
		$store = $this->constructSessionStore();
		$store->setSession('id', 'key', 'user', 10);
		
		// Retrieve the stored timeout
		$new_timeout = $store->getTimeout();
		
		// Calculate maximum time passed (rounded up for some safety)
		$time_passed = (time() - $start_time) + 1;
		
		// Check whether timeout decay is within margins
		$timeout_diff = $timeout - $new_timeout;
		$this->assertLessThanOrEqual($time_passed, $timeout_diff);
	}
	
	/**
	 * Test whether timeouts are updated correctly
	 */
	public function testSetTimeout()
	{
		// Construct store and set a session with a low timeout
		$store = $this->constructSessionStore();
		$store->setSession('id', 'key', 'user', 5);
		
		// Use a fixed timeout
		$timeout = 10;
		
		// Store current time to obtain a bound on maximum timeout change
		$start_time = time();
		
		// Update timeout
		$store->setTimeout($timeout);
		
		// Retrieve the stored timeout
		$new_timeout = $store->getTimeout();
		
		// Calculate maximum time passed (rounded up for some safety)
		$time_passed = (time() - $start_time) + 1;
		
		// Check whether timeout decay is within margins
		$timeout_diff = $timeout - $new_timeout;
		$this->assertLessThanOrEqual($time_passed, $timeout_diff);
	}
	
	/**
	 * Test whether timeouts actually happen
	 * 
	 * This test sets a session with a timeout of 2 seconds, then waits 5 seconds for that
	 * session to timeout, and checks whether it actually timed out.
	 * 
	 * @medium
	 */
	public function testTimeout()
	{
		// Construct store and set a session with 2 seconds timeout
		$store = $this->constructSessionStore();
		$store->setSession('id', 'key', 'user', 2);
		
		// There should be an active session
		$this->assertTrue($store->hasSession());
		
		// Wait 5 seconds for timeout to occur
		sleep(5);
		
		// Check whether the session is still valid; it should not be since the timeout occurred
		$this->assertFalse($store->hasSession());
	}
}
