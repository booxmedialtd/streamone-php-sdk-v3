<?php

use StreamOne\API\v3\Actor;
use StreamOne\API\v3\Config;
use StreamOne\API\v3\MemorySessionStore;
use StreamOne\API\v3\Session;
use StreamOne\API\v3\Request;

/**
 * Class that can reuse everything from a Request and overwrite methods for tests
 */
class TestActorRequest extends Request
{
	private $request;
	
	public function __construct(Request $request)
	{
		$this->request = $request;
		$this->command = $request->command;
		$this->action = $request->action;
	}
	
	/**
	 * Just pass it to the wrapped request
	 */
	public function getAccount()
	{
		return $this->request->getAccount();
	}

	/**
	 * Just pass it to the wrapped request
	 */
	public function getAccounts()
	{
		return $this->request->getAccounts();
	}

	/**
	 * Just pass it to the wrapped request
	 */
	public function getCustomer()
	{
		return $this->request->getCustomer();
	}

	/**
	 * Just pass it to the wrapped request
	 */
	public function arguments()
	{
		return $this->request->arguments();
	}

	/**
	 * Just pass it to the wrapped request
	 */
	protected function parameters()
	{
		return $this->request->parameters();
	}

	/**
	 * We need the authentication type in tests, so make it publicly available
	 * 
	 * @retval string
	 *   The authentication type used
	 */
	public function getAuthenticationType()
	{
		$parameters = $this->parameters();
		return $parameters['authentication_type'];
	}

	/**
	 * We need the parameters for signing in tests, so make it publicly available
	 */
	public function parametersForSigning()
	{
		return $this->request->parametersForSigning();
	}

	/**
	 * We need the signing key in tests, so make it publicly available
	 */
	public function signingKey()
	{
		return $this->request->signingKey();
	}
}
/**
 * Test for the Actor class
 */
class ActorTest extends PHPUnit_TestCase
{
	private static $configs;
	private static $sessions;

	public static function setUpBeforeClass()
	{
		self::$configs['user'] = new Config(
			array(
				'api_url' => 'api',
				'authentication_type' => 'user',
				'user_id' => 'user',
				'user_psk' => 'psk'
			)
		);
		self::$configs['user_default_account'] = new Config(
			array(
				'api_url' => 'api',
				'authentication_type' => 'user',
				'user_id' => 'application',
				'user_psk' => 'apppsk',
				'default_account_id' => 'account'
			)
		);
		self::$configs['application'] = new Config(
			array(
				'api_url' => 'api',
				'authentication_type' => 'application',
				'application_id' => 'user',
				'application_psk' => 'psk'
			)
		);
		self::$configs['application_default_account'] = new Config(
			array(
				'api_url' => 'api',
				'authentication_type' => 'application',
				'application_id' => 'application',
				'application_psk' => 'apppsk',
				'default_account_id' => 'account'
			)
		);

		foreach (self::$configs as $key => $dummy)
		{
			$session_store = new MemorySessionStore();
			$session_store->setSession('session', 'key', 'user', 100);
			self::$sessions[$key] = new Session(self::$configs[$key], $session_store);
		}
	}

	/**
	 * Test if setting an account has the intended behaviour
	 *
	 * @param string $config
	 *   The configuration to use
	 * @param bool $use_session
	 *   True if and only if a session should be used
	 * @param bool $set_account
	 *   True if and only if the account should be set
	 * @param string|null $account
	 *   The account to set / test
	 * @param bool $should_be_default_account
	 *   True if and only if the default account should be returned instead of the set one
	 *
	 * @dataProvider provideSetAccount
	 */
	public function testSetAccount($config, $use_session, $set_account, $account,
	                               $should_be_default_account = false)
	{
		/** @var Session $session_to_use */
		$session_to_use = null;
		if ($use_session)
		{
			$session_to_use = self::$sessions[$config];
		}
		$actor = new Actor(self::$configs[$config], $session_to_use);

		/** @var Config $config_to_use */
		$config_to_use = self::$configs[$config];

		if ($set_account)
		{
			$actor->setAccount($account);
		}

		if ($should_be_default_account)
		{
			$this->assertEquals($config_to_use->getDefaultAccountId(), $actor->getAccount());
		}
		else
		{
			$this->assertEquals($account, $actor->getAccount());
		}
		$this->assertNull($actor->getCustomer());
		
		$request = $actor->newRequest('command', 'action');
		if ($use_session)
		{
			$this->assertInstanceOf('\StreamOne\API\v3\SessionRequest', $request);
			$request = new TestActorRequest($request);
		}
		else
		{
			$this->assertInstanceOf('\StreamOne\API\v3\Request', $request);
			$request = new TestActorRequest($request);
		}

		if ($should_be_default_account)
		{
			$this->assertEquals($config_to_use->getDefaultAccountId(), $request->getAccount());
		}
		else
		{
			$this->assertEquals($account, $request->getAccount());
			if ($account === null)
			{
				$this->assertArrayNotHasKey('account', $request->parametersForSigning());
			}
		}
		
		$this->assertEquals($config_to_use->getAuthenticationType(), 
		                    $request->getAuthenticationType());
		$this->assertArrayKeySameValue($config_to_use->getAuthenticationType(), 
		                               $config_to_use->getAuthenticationActorId(), 
		                               $request->parametersForSigning());
		if ($use_session)
		{
			$session_id = $session_to_use->getSessionStore()->getId();
			$this->assertArrayKeySameValue('session', $session_id, $request->parametersForSigning());
			$session_key = $session_to_use->getSessionStore()->getKey();
			$this->assertEquals($config_to_use->getAuthenticationActorKey() . $session_key, $request->signingKey());
		}
		else
		{
			$this->assertEquals($config_to_use->getAuthenticationActorKey(), $request->signingKey());
		}

		$this->assertNull($request->getCustomer());
		$this->assertArrayNotHasKey('customer', $request->parametersForSigning());
	}

	public function provideSetAccount()
	{
		return array(
			array('user', false, true, 'account123'),
			array('user_default_account', false, true, 'account123'),
			array('application', false, true, 'account123'),
			array('application_default_account', false, true, 'account123'),
			array('user', false, true, null),
			array('user_default_account', false, true, null),
			array('application', false, true, null),
			array('application_default_account', false, true, null),
			array('user', false, true, 'A'),
			array('user', false, false, null, true),
			array('user_default_account', false, false, null, true),
			array('application', false, false, null, true),
			array('application_default_account', false, false, null, true),
			array('application', true, true, 'account123'),
			array('application_default_account', true, true, 'account123'),
		);
	}

	/**
	 * Test if setting multiple accounts has the intended behaviour
	 *
	 * @param string $config
	 *   The configuration to use
	 * @param bool $use_session
	 *   True if and only if a session should be used
	 * @param bool $set_accounts
	 *   True if and only if the accounts should be set
	 * @param array $accounts
	 *   The accounts to set / test
	 * @param bool $should_be_default_account
	 *   True if and only if the default account should be returned instead of the set ones
	 *
	 * @dataProvider provideSetAccounts
	 */
	public function testSetAccounts($config, $use_session, $set_accounts, $accounts,
	                                $should_be_default_account = false)
	{
		/** @var Session $session_to_use */
		$session_to_use = null;
		if ($use_session)
		{
			$session_to_use = self::$sessions[$config];
		}
		$actor = new Actor(self::$configs[$config], $session_to_use);

		/** @var Config $config_to_use */
		$config_to_use = self::$configs[$config];

		if ($set_accounts)
		{
			$actor->setAccounts($accounts);
		}

		if ($should_be_default_account)
		{
			if ($config_to_use->getDefaultAccountId() === null)
			{
				$this->assertEmpty($actor->getAccounts());
			}
			else
			{
				$this->assertEquals(array($config_to_use->getDefaultAccountId()), $actor->getAccounts());
			}
		}
		else
		{
			$this->assertEquals($accounts, $actor->getAccounts());
		}
		$this->assertNull($actor->getCustomer());

		$request = $actor->newRequest('command', 'action');
		if ($use_session)
		{
			$this->assertInstanceOf('\StreamOne\API\v3\SessionRequest', $request);
			$request = new TestActorRequest($request);
		}
		else
		{
			$this->assertInstanceOf('\StreamOne\API\v3\Request', $request);
			$request = new TestActorRequest($request);
		}

		if ($should_be_default_account)
		{
			if ($config_to_use->getDefaultAccountId() === null)
			{
				$this->assertEmpty($request->getAccounts());
			}
			else
			{
				$this->assertEquals(array($config_to_use->getDefaultAccountId()), $request->getAccounts());
			}
		}
		else
		{
			$this->assertEquals($accounts, $request->getAccounts());
		}

		$this->assertEquals($config_to_use->getAuthenticationType(),
		                    $request->getAuthenticationType());
		$this->assertArrayKeySameValue($config_to_use->getAuthenticationType(),
		                               $config_to_use->getAuthenticationActorId(),
		                               $request->parametersForSigning());
		if ($use_session)
		{
			$session_id = $session_to_use->getSessionStore()->getId();
			$this->assertArrayKeySameValue('session', $session_id, $request->parametersForSigning());
			$session_key = $session_to_use->getSessionStore()->getKey();
			$this->assertEquals($config_to_use->getAuthenticationActorKey() . $session_key, $request->signingKey());
		}
		else
		{
			$this->assertEquals($config_to_use->getAuthenticationActorKey(), $request->signingKey());
		}

		$this->assertNull($request->getCustomer());
		$this->assertArrayNotHasKey('customer', $request->parametersForSigning());
	}

	public function provideSetAccounts()
	{
		return array(
			array('user', false, true, array('account123')),
			array('user_default_account', false, true, array('account123')),
			array('application', false, true, array('account123')),
			array('application_default_account', false, true, array('account123')),
			array('user', false, true, array()),
			array('user_default_account', false, true, array()),
			array('application', false, true, array()),
			array('application_default_account', false, true, array()),
			array('user', false, true, array('A', 'B', 'C', 'D')),
			array('user', false, false, null, true),
			array('user_default_account', false, false, null, true),
			array('application', false, false, null, true),
			array('application_default_account', false, false, null, true),
			array('application', true, true, array('account123', 'abc')),
			array('application_default_account', true, true, array('account123', 'abc')),
		);
	}

	/**
	 * Test if setting a customer has the intended behaviour
	 *
	 * @param string $config
	 *   The configuration to use
	 * @param bool $use_session
	 *   True if and only if a session should be used
	 * @param string|null $customer
	 *   The customer to set / test
	 *
	 * @dataProvider provideSetCustomer
	 */
	public function testSetCustomer($config, $use_session, $customer)
	{
		/** @var Session $session_to_use */
		$session_to_use = null;
		if ($use_session)
		{
			$session_to_use = self::$sessions[$config];
		}
		$actor = new Actor(self::$configs[$config], $session_to_use);

		/** @var Config $config_to_use */
		$config_to_use = self::$configs[$config];

		$actor->setCustomer($customer);

		$this->assertEquals($customer, $actor->getCustomer());
		$this->assertNull($actor->getAccount());

		$request = $actor->newRequest('command', 'action');
		if ($use_session)
		{
			$this->assertInstanceOf('\StreamOne\API\v3\SessionRequest', $request);
			$request = new TestActorRequest($request);
		}
		else
		{
			$this->assertInstanceOf('\StreamOne\API\v3\Request', $request);
			$request = new TestActorRequest($request);
		}

		$this->assertEquals($customer, $request->getCustomer());

		$this->assertEquals($config_to_use->getAuthenticationType(),
		                    $request->getAuthenticationType());
		$this->assertArrayKeySameValue($config_to_use->getAuthenticationType(),
		                               $config_to_use->getAuthenticationActorId(),
		                               $request->parametersForSigning());
		if ($use_session)
		{
			$session_id = $session_to_use->getSessionStore()->getId();
			$this->assertArrayKeySameValue('session', $session_id, $request->parametersForSigning());
			$session_key = $session_to_use->getSessionStore()->getKey();
			$this->assertEquals($config_to_use->getAuthenticationActorKey() . $session_key, $request->signingKey());
		}
		else
		{
			$this->assertEquals($config_to_use->getAuthenticationActorKey(), $request->signingKey());
		}

		$this->assertNull($request->getAccount());
		$this->assertArrayNotHasKey('account', $request->parametersForSigning());
	}

	public function provideSetCustomer()
	{
		return array(
			array('user', false, 'customerABC'),
			array('user_default_account', false, 'customerABC'),
			array('application', false, 'customerABC'),
			array('application_default_account', false, 'customerABC'),
			array('user', false, null),
			array('user_default_account', false, null),
			array('application', false, null),
			array('application_default_account', false, null),
			array('user', false, 'C'),
			array('application', true, 'customerABC'),
			array('application_default_account', true, 'customerABC'),
		);
	}

	/**
	 * Test that sessions do not work when doing them as a user
	 *
	 * @param string $name
	 *   The configuration and session to use
	 *
	 * @dataProvider provideInvalidSession
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidSession($name)
	{
		$config = self::$configs[$name];
		$session = self::$sessions[$name];
		$actor = new Actor($config, $session);
		$actor->newRequest('command', 'action');
	}

	public function provideInvalidSession()
	{
		return array(
			array('user'),
			array('user_default_account'),
		);
	}
}
