<?php

require_once('StreamOneSessionStoreInterfaceTest.php');

use StreamOne\API\v3\MemorySessionStore;

/**
 * Test the StreamOneMemorySessionStore
 */
class MemorySessionStoreTest extends SessionStoreInterfaceTest
{
	protected function constructSessionStore()
	{
		return new MemorySessionStore;
	}
}
