<?php

require_once('eapi/StreamOneMemorySessionStore.php');

require_once('StreamOneSessionStoreInterfaceTest.php');

/**
 * Test the StreamOneMemorySessionStore
 */
class StreamOneMemorySessionStoreTest extends StreamOneSessionStoreInterfaceTest
{
	protected function constructSessionStore()
	{
		return new StreamOneMemorySessionStore;
	}
}
