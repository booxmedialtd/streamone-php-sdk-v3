<?php
/**
 * @addtogroup StreamOneSDK The StreamOne SDK
 *
 * @{
 */

class StreamOneSessionrequest extends StreamOneRequest
{
	/**
	 * Initializes a request and sets the session
	 *
	 * @see StreamOneRequestBase::__construct
	 */
	public function __construct($command, $action, $session_data)
	{
		parent::__construct($command, $action);
		$this->setSession($session_data['id'], $session_data['key']);
	}

	/**
	 * After doing the request, also updates the session expiry time
	 *
	 * @see StreamOneRequestBase::execute
	 */
	public function execute()
	{
		parent::execute();

		$header = $this->header();
		if (isset($header['sessionrenew']))
		{
			StreamOneConfig::$session_store->updateRenew($header['sessionrenew']);
		}
		if (isset($header['sessiontimeout']))
		{
			StreamOneConfig::$session_store->updateTimeout($header['sessiontimeout']);
		}
	}
}

/**
 * @}
 */
