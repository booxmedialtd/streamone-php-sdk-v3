<?php

use StreamOne\API\v3\Actor;
use StreamOne\API\v3\Config;
use StreamOne\API\v3\MemorySessionStore;
use StreamOne\API\v3\Session;
use StreamOne\API\v3\Request;
use StreamOne\API\v3\SessionRequest;

/**
 * Class that can reuse everything from a Request and overwrite methods for tests
 */
class TestActorRequest extends Request
{
	private $request;
	private $config;
	
	public function __construct(Request $request)
	{
		$this->request = $request;
		$this->config = $request->getConfig();
		$this->command = $request->command;
		$this->action = $request->action;
	}
	
	public function getAccount()
	{
		return $this->request->getAccount();
	}

	public function getAccounts()
	{
		return $this->request->getAccounts();
	}

	public function getCustomer()
	{
		return $this->request->getCustomer();
	}
	
	public function arguments()
	{
		return $this->request->arguments();
	}
	
	protected function parameters()
	{
		return $this->request->parameters();
	}
	
	public function getAuthenticationType()
	{
		$parameters = $this->parameters();
		return $parameters['authentication_type'];
	}
	
	public function parametersForSigning()
	{
		return $this->request->parametersForSigning();
	}

	public function signingKey()
	{
		return $this->request->signingKey();
	}
}

/**
 * Class that can reuse everything from a SessionRequest and overwrite methods for tests
 */
class TestActorSessionRequest extends SessionRequest
{
	private $request;
	private $config;

	public function __construct(SessionRequest $request)
	{
		$this->request = $request;
		$this->config = $request->getConfig();
		$this->command = $request->command;
		$this->action = $request->action;
	}

	public function getAccount()
	{
		return $this->request->getAccount();
	}

	public function getAccounts()
	{
		return $this->request->getAccounts();
	}

	public function getCustomer()
	{
		return $this->request->getCustomer();
	}

	public function arguments()
	{
		return $this->request->arguments();
	}

	protected function parameters()
	{
		return $this->request->parameters();
	}

	public function getAuthenticationType()
	{
		$parameters = $this->parameters();
		return $parameters['authentication_type'];
	}

	public function parametersForSigning()
	{
		return $this->request->parametersForSigning();
	}

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
			$request = new TestActorSessionRequest($request);
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
		}
		
		$this->assertEquals($config_to_use->getAuthenticationType(), 
		                    $request->getAuthenticationType());
		$this->assertArrayKeySameValue($config_to_use->getAuthenticationType(), 
		                               $config_to_use->getAuthenticationActorId(), 
		                               $request->parametersForSigning());
		if ($use_session)
		{
			$this->assertArrayHasKey('session', $request->parametersForSigning());
		}
		else
		{
			$this->assertEquals($config_to_use->getAuthenticationActorKey(), $request->signingKey());
		}

		$this->assertNull($request->getCustomer());
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
			array('user', true, true, 'account123'),
			array('user_default_account', true, true, 'account123'),
			array('application', true, true, 'account123'),
			array('application_default_account', true, true, 'account123'),
		);
	}

	/**
	 * Test if setting multiple accounts has the intended behaviour
	 *
	 * @param bool $set_accounts
	 *   True if and only if the accounts should be set
	 * @param array $accounts
	 *   The accounts to set / test
	 * @param bool $should_be_default_account
	 *   True if and only if the default account should be returned instead of the set one
	 *
	 * @dataProvider provideSetAccounts
	 */
	public function testSetAccounts($set_accounts, $accounts, $should_be_default_account = false)
	{
		/** @var Config $config */
		foreach (self::$configs as $config)
		{
			$actor = new Actor($config);

			if ($set_accounts)
			{
				$actor->setAccounts($accounts);
			}

			if ($should_be_default_account)
			{
				if ($config->getDefaultAccountId() !== null)
				{
					$this->assertEquals(array($config->getDefaultAccountId()), $actor->getAccounts());
				}
				else
				{
					$this->assertEmpty($actor->getAccounts());
				}
			}
			else
			{
				$this->assertEquals($accounts, $actor->getAccounts());
			}
			$this->assertNull($actor->getCustomer());

			$request = $actor->newRequest('command', 'action');
			$this->assertInstanceOf('\StreamOne\API\v3\Request', $request);

			if ($should_be_default_account)
			{
				if ($config->getDefaultAccountId() !== null)
				{
					$this->assertEquals(array($config->getDefaultAccountId()), $request->getAccounts());
				}
				else
				{
					$this->assertEmpty($request->getAccounts());
				}
			}
			else
			{
				$this->assertEquals($accounts, $request->getAccounts());
			}

			$this->assertNull($request->getCustomer());
		}
	}

	public function provideSetAccounts()
	{
		return array(
			array(true, array()),
			array(true, array('account')),
			array(true, array('A', 'bcdef')),
			array(false, array(), true),
		);
	}

	/**
	 * Test if setting a customer has the intended behaviour
	 *
	 * Note that we do not have to test for the default account here when not setting a customer,
	 * as that is already tested when not setting an account.
	 *
	 * @param string|null $customer
	 *   The customer to set / test
	 *
	 * @dataProvider provideSetCustomer
	 */
	public function testSetCustomer($customer)
	{
		foreach (self::$configs as $config)
		{
			$actor = new Actor($config);

			$actor->setCustomer($customer);

			$this->assertEquals($customer, $actor->getCustomer());
			$this->assertNull($actor->getAccount());
			$this->assertEmpty($actor->getAccounts());

			$request = $actor->newRequest('command', 'action');
			$this->assertEquals($customer, $request->getCustomer());

			$this->assertNull($request->getAccount());
			$this->assertEmpty($request->getAccounts());
		}
	}

	public function provideSetCustomer()
	{
		return array(
			array('cust456'),
			array(null),
			array('CD')
		);
	}
}
