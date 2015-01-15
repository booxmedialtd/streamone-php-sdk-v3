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
	 * Test the config-option of the constructor
	 */
	public function testConstructorConfig()
	{
		$actor = new Actor(self::$configs['user']);
		
		$this->assertSame(self::$configs['user'], $actor->getConfig());
	}
	
	/**
	 * Test the session-option of the constructor
	 *
	 * @param string|null $session
	 *   Name of the session (from self::$sessions) to use
	 *
	 * @dataProvider provideConstructorSession
	 */
	public function testConstructorSession($session)
	{
		$session_to_use = null;
		if ($session !== null)
		{
			$session_to_use = self::$sessions[$session];
		}
		$actor = new Actor(self::$configs['user'], $session_to_use);
		
		$this->assertSame($session_to_use, $actor->getSession());
	}
	
	public function provideConstructorSession()
	{
		return array(
			array(null),
			array('application'),
		);
	}
	
	/**
	 * Test that the constructor sets the default account
	 *
	 * @param string|null $config
	 *   Name of the config (from self::$config) to use
	 *
	 * @dataProvider provideConstructorDefaultAccount
	 */
	public function testConstructorDefaultAccount($config)
	{
		/** @var Config $my_config */
		$my_config = self::$configs[$config];
		$actor = new Actor($my_config);
		
		$this->assertSame($my_config->getDefaultAccountId(), $actor->getAccount());
	}
	
	public function provideConstructorDefaultAccount()
	{
		return array(
			array('user'),
			array('user_default_account'),
		);
	}
	
	/**
	 * Test that setting the account of an actor works as expected
	 *
	 * @param string|null $account
	 *   The ID of the account to set; null to clear the account
	 *
	 * @dataProvider provideSetAccount
	 */
	public function testSetAccount($account)
	{
		$actor = new Actor(self::$configs['user']);
		$actor->setAccount($account);
		
		$this->assertSame($account, $actor->getAccount());
		if ($account == null)
		{
			$this->assertEmpty($actor->getAccounts());
		}
		else
		{
			$this->assertSame(array($account), $actor->getAccounts());
		}
		$this->assertNull($actor->getCustomer());
	}
	
	public function provideSetAccount()
	{
		return array(
			array('account123'),
			array(null)
		);
	}
	
	/**
	 * Test that setting the accounts of an actor works as expected
	 *
	 * @param array $accounts
	 *   An arraw with the IDs of the accounts to set
	 *
	 * @dataProvider provideSetAccounts
	 */
	public function testSetAccounts($accounts)
	{
		$actor = new Actor(self::$configs['user']);
		$actor->setAccounts($accounts);
		
		$this->assertSame($accounts, $actor->getAccounts());
		if (empty($accounts))
		{
			$this->assertNull($actor->getAccount());
		}
		else
		{
			$this->assertSame($accounts[0], $actor->getAccount());
		}
		$this->assertNull($actor->getCustomer());
	}
	
	public function provideSetAccounts()
	{
		return array(
			array(array('account123')),
			array(array('account123', 'anotheraccount')),
			array(array())
		);
	}
	
	/**
	 * Test that setting the customer of an actor works as expected
	 *
	 * @param string|null $customer
	 *   The ID of the customer to set; null to clear the account
	 *
	 * @dataProvider provideSetCustomer
	 */
	public function testSetCustomer($customer)
	{
		$actor = new Actor(self::$configs['user']);
		$actor->setCustomer($customer);
		
		$this->assertSame($customer, $actor->getCustomer());
		$this->assertNull($actor->getAccount());
		$this->assertEmpty($actor->getAccounts());
	}
	
	public function provideSetCustomer()
	{
		return array(
			array('customer123'),
			array(null)
		);
	}
	
	/**
	 * Test if creating a new request with an account has the intended behaaviour
	 *
	 * @param string $config
	 *   The configuration to use
	 * @param string|null $account
	 *   The account to set / test
	 *
	 * @dataProvider provideRequestWithAccount
	 */
	public function testRequestWithAccount($config, $account)
	{
		/** @var Config $config_to_use */
		$config_to_use = self::$configs[$config];
		
		$actor = new Actor($config_to_use);
		
		$actor->setAccount($account);
		
		$request = $actor->newRequest('command', 'action');
		
		$this->assertInstanceOf('\StreamOne\API\v3\Request', $request);
		$request = new TestActorRequest($request);
		
		$this->assertSame($account, $request->getAccount());
		if ($account === null)
		{
			// If the account to set is null, it should not be used as a parameter for signing
			$this->assertArrayNotHasKey('account', $request->parametersForSigning());
			$this->assertEmpty($request->getAccounts());
		}
		else
		{
			$this->assertSame(array($account), $request->getAccounts());
		}
		
		$this->assertSame($config_to_use->getAuthenticationType(),
		                    $request->getAuthenticationType());
		$this->assertArrayKeySameValue($config_to_use->getAuthenticationType(),
		                               $config_to_use->getAuthenticationActorId(),
		                               $request->parametersForSigning());
		
		$this->assertSame($config_to_use->getAuthenticationActorKey(), $request->signingKey());
		
		// We have set an account, so there should not be a customer now
		$this->assertNull($request->getCustomer());
		$this->assertArrayNotHasKey('customer', $request->parametersForSigning());
	}
	
	public function provideRequestWithAccount()
	{
		return array(
			array('user', 'account123'),
			array('user_default_account', 'account123'),
			array('application', 'account123'),
			array('user', null),
			array('application', null),
			array('application_default_account', null),
		);
	}
	
	/**
	 * Test if creating a new request with the default account has the intended behaaviour
	 *
	 * @param string $config
	 *   The configuration to use
	 *
	 * @dataProvider provideRequestWithDefaultAccount
	 */
	public function testRequestWithDefaultAccount($config)
	{
		/** @var Config $config_to_use */
		$config_to_use = self::$configs[$config];
		
		$actor = new Actor($config_to_use);
		
		$request = $actor->newRequest('command', 'action');
		
		$this->assertInstanceOf('\StreamOne\API\v3\Request', $request);
		$request = new TestActorRequest($request);
		
		$this->assertSame($config_to_use->getDefaultAccountId(), $request->getAccount());
		if ($config_to_use->getDefaultAccountId() === null)
		{
			$this->assertEmpty($request->getAccounts());
		}
		else
		{
			$this->assertEquals(array($config_to_use->getDefaultAccountId()), $request->getAccounts());
		}
		$this->assertSame($config_to_use->getAuthenticationType(),
		                    $request->getAuthenticationType());
		$this->assertArrayKeySameValue($config_to_use->getAuthenticationType(),
		                               $config_to_use->getAuthenticationActorId(),
		                               $request->parametersForSigning());
		
		$this->assertSame($config_to_use->getAuthenticationActorKey(), $request->signingKey());
		
		// We have set an account, so there should not be a customer now
		$this->assertNull($request->getCustomer());
		$this->assertArrayNotHasKey('customer', $request->parametersForSigning());
	}
	
	public function provideRequestWithDefaultAccount()
	{
		return array(
			array('user'),
			array('user_default_account'),
			array('application'),
			array('application_default_account'),
		);
	}
	
	/**
	 * Test if creating a new request with an account in a session has the intended behaaviour
	 *
	 * @param string $config
	 *   The configuration to use
	 * @param string|null $account
	 *   The account to set / test
	 *
	 * @dataProvider provideRequestWithAccountInSession
	 */
	public function testRequestWithAccountInSession($config, $account)
	{
		/** @var Session $session */
		$session = self::$sessions[$config];
		/** @var Config $config_to_use */
		$config_to_use = self::$configs[$config];
		
		$actor = new Actor($config_to_use, $session);
		
		$actor->setAccount($account);
		
		$request = $actor->newRequest('command', 'action');
		
		$this->assertInstanceOf('\StreamOne\API\v3\SessionRequest', $request);
		$request = new TestActorRequest($request);
		
		$this->assertSame($account, $request->getAccount());
		if ($account === null)
		{
			// If the account to set is null, it should not be used as a parameter for signing
			$this->assertArrayNotHasKey('account', $request->parametersForSigning());
			$this->assertEmpty($request->getAccounts());
		}
		else
		{
			$this->assertSame(array($account), $request->getAccounts());
		}
		
		$this->assertSame($config_to_use->getAuthenticationType(),
		                    $request->getAuthenticationType());
		$this->assertArrayKeySameValue($config_to_use->getAuthenticationType(),
		                               $config_to_use->getAuthenticationActorId(),
		                               $request->parametersForSigning());
		
		$session_id = $session->getSessionStore()->getId();
		$this->assertArrayKeySameValue('session', $session_id, $request->parametersForSigning());
		$session_key = $session->getSessionStore()->getKey();
		$this->assertSame($config_to_use->getAuthenticationActorKey() . $session_key,
		                    $request->signingKey());
		
		// We have set an account, so there should not be a customer now
		$this->assertNull($request->getCustomer());
		$this->assertArrayNotHasKey('customer', $request->parametersForSigning());
	}
	
	public function provideRequestWithAccountInSession()
	{
		return array(
			array('application', 'account123'),
			array('application_default_account', 'account123'),
			array('application', null),
			array('application_default_account', null),
		);
	}
	
	/**
	 * Test if creating a new request with a default account in a session has the intended behaaviour
	 *
	 * @param string $config
	 *   The configuration to use
	 *
	 * @dataProvider provideRequestWithDefaultAccountInSession
	 */
	public function testRequestWithDefaultAccountInSession($config)
	{
		/** @var Session $session */
		$session = self::$sessions[$config];
		/** @var Config $config_to_use */
		$config_to_use = self::$configs[$config];
		
		$actor = new Actor($config_to_use, $session);
		
		$request = $actor->newRequest('command', 'action');
		
		$this->assertInstanceOf('\StreamOne\API\v3\SessionRequest', $request);
		$request = new TestActorRequest($request);
		
		if ($config_to_use->getDefaultAccountId() === null)
		{
			$this->assertEmpty($request->getAccounts());
		}
		else
		{
			$this->assertEquals(array($config_to_use->getDefaultAccountId()), $request->getAccounts());
		}
		$this->assertSame($config_to_use->getAuthenticationType(),
		                    $request->getAuthenticationType());
		$this->assertArrayKeySameValue($config_to_use->getAuthenticationType(),
		                               $config_to_use->getAuthenticationActorId(),
		                               $request->parametersForSigning());
		
		$session_id = $session->getSessionStore()->getId();
		$this->assertArrayKeySameValue('session', $session_id, $request->parametersForSigning());
		$session_key = $session->getSessionStore()->getKey();
		$this->assertSame($config_to_use->getAuthenticationActorKey() . $session_key,
		                    $request->signingKey());
		
		// We have set an account, so there should not be a customer now
		$this->assertNull($request->getCustomer());
		$this->assertArrayNotHasKey('customer', $request->parametersForSigning());
	}
	
	public function provideRequestWithDefaultAccountInSession()
	{
		return array(
			array('application'),
			array('application_default_account'),
		);
	}
	
	/**
	 * Test if creating a new request with accounts has the intended behaaviour
	 *
	 * @param string $config
	 *   The configuration to use
	 * @param array $accounts
	 *   The accounts to set / test
	 *
	 * @dataProvider provideRequestWithAccounts
	 */
	public function testRequestWithAccounts($config, $accounts)
	{
		/** @var Config $config_to_use */
		$config_to_use = self::$configs[$config];
		
		$actor = new Actor($config_to_use);
		
		$actor->setAccounts($accounts);
		
		$request = $actor->newRequest('command', 'action');
		
		$this->assertInstanceOf('\StreamOne\API\v3\Request', $request);
		$request = new TestActorRequest($request);
		
		$this->assertEquals($accounts, $request->getAccounts());
		if (empty($accounts))
		{
			// If the account to set is null, it should not be used as a parameter for signing
			$this->assertArrayNotHasKey('account', $request->parametersForSigning());
			$this->assertNull($request->getAccount());
		}
		else
		{
			$this->assertSame($accounts[0], $request->getAccount());
		}
		
		$this->assertEquals($config_to_use->getAuthenticationType(),
		                    $request->getAuthenticationType());
		$this->assertArrayKeySameValue($config_to_use->getAuthenticationType(),
		                               $config_to_use->getAuthenticationActorId(),
		                               $request->parametersForSigning());
		
		$this->assertEquals($config_to_use->getAuthenticationActorKey(), $request->signingKey());
		
		// We have set accounts, so there should not be a customer now
		$this->assertNull($request->getCustomer());
		$this->assertArrayNotHasKey('customer', $request->parametersForSigning());
	}
	
	public function provideRequestWithAccounts()
	{
		return array(
			array('user', array('account123')),
			array('user_default_account', array('account123')),
			array('application', array('account123')),
			array('user', array()),
			array('application', array()),
			array('application_default_account', array()),
			array('user', array('account123', 'anotheraccount')),
		);
	}
	
	/**
	 * Test if creating a new request with accounts in a session has the intended behaaviour
	 *
	 * @param string $config
	 *   The configuration to use
	 * @param array $accounts
	 *   The accounts to set / test
	 *
	 * @dataProvider provideRequestWithAccountsInSession
	 */
	public function testRequestWithAccountsInSession($config, $accounts)
	{
		/** @var Session $session */
		$session = self::$sessions[$config];
		/** @var Config $config_to_use */
		$config_to_use = self::$configs[$config];
		
		$actor = new Actor($config_to_use, $session);
		
		$actor->setAccounts($accounts);
		
		$request = $actor->newRequest('command', 'action');
		
		$this->assertInstanceOf('\StreamOne\API\v3\SessionRequest', $request);
		$request = new TestActorRequest($request);
		
		$this->assertEquals($accounts, $request->getAccounts());
		if (empty($accounts))
		{
			// If the account to set is null, it should not be used as a parameter for signing
			$this->assertArrayNotHasKey('account', $request->parametersForSigning());
			$this->assertNull($request->getAccount());
		}
		else
		{
			$this->assertSame($accounts[0], $request->getAccount());
		}
		
		$this->assertEquals($config_to_use->getAuthenticationType(),
		                    $request->getAuthenticationType());
		$this->assertArrayKeySameValue($config_to_use->getAuthenticationType(),
		                               $config_to_use->getAuthenticationActorId(),
		                               $request->parametersForSigning());
		
		$session_id = $session->getSessionStore()->getId();
		$this->assertArrayKeySameValue('session', $session_id, $request->parametersForSigning());
		$session_key = $session->getSessionStore()->getKey();
		$this->assertEquals($config_to_use->getAuthenticationActorKey() . $session_key,
		                    $request->signingKey());
		
		// We have set accounts, so there should not be a customer now
		$this->assertNull($request->getCustomer());
		$this->assertArrayNotHasKey('customer', $request->parametersForSigning());
	}
	
	public function provideRequestWithAccountsInSession()
	{
		return array(
			array('application', array('account123')),
			array('application', array()),
			array('application', array('account1', 'anotheraccount')),
		);
	}
	
	/**
	 * Test if creating a new request with a customer has the intended behaaviour
	 *
	 * @param string $config
	 *   The configuration to use
	 * @param string|null $customer
	 *   The customer to set / test
	 *
	 * @dataProvider provideRequestWithCustomer
	 */
	public function testRequestWithCustomer($config, $customer)
	{
		/** @var Config $config_to_use */
		$config_to_use = self::$configs[$config];
		
		$actor = new Actor($config_to_use);
		
		$actor->setCustomer($customer);
		
		$request = $actor->newRequest('command', 'action');
		
		$this->assertInstanceOf('\StreamOne\API\v3\Request', $request);
		$request = new TestActorRequest($request);
		
		$this->assertEquals($customer, $request->getCustomer());
		if ($customer === null)
		{
			// If the account to set is null, it should not be used as a parameter for signing
			$this->assertArrayNotHasKey('customer', $request->parametersForSigning());
		}
		
		$this->assertEquals($config_to_use->getAuthenticationType(),
		                    $request->getAuthenticationType());
		$this->assertArrayKeySameValue($config_to_use->getAuthenticationType(),
		                               $config_to_use->getAuthenticationActorId(),
		                               $request->parametersForSigning());
		
		$this->assertEquals($config_to_use->getAuthenticationActorKey(), $request->signingKey());
		
		// We have set a customer, so there should not be an account now
		$this->assertNull($request->getAccount());
		$this->assertEmpty($request->getAccounts());
		$this->assertArrayNotHasKey('account', $request->parametersForSigning());
	}
	
	public function provideRequestWithCustomer()
	{
		return array(
			array('user', 'customer1'),
			array('user_default_account', 'customer1'),
			array('user', null),
			array('user_default_account', null),
		);
	}
	
	/**
	 * Test if creating a new request with a customer in a session has the intended behaaviour
	 *
	 * @param string $config
	 *   The configuration to use
	 * @param string|null $customer
	 *   The customer to set / test
	 *
	 * @dataProvider provideRequestWithCustomerInSession
	 */
	public function testRequestWithCustomerInSession($config, $customer)
	{
		/** @var Session $session */
		$session = self::$sessions[$config];
		/** @var Config $config_to_use */
		$config_to_use = self::$configs[$config];
		
		$actor = new Actor($config_to_use, $session);
		
		$actor->setCustomer($customer);
		
		$request = $actor->newRequest('command', 'action');
		
		$this->assertInstanceOf('\StreamOne\API\v3\SessionRequest', $request);
		$request = new TestActorRequest($request);
		
		$this->assertEquals($customer, $request->getCustomer());
		if ($customer === null)
		{
			// If the account to set is null, it should not be used as a parameter for signing
			$this->assertArrayNotHasKey('customer', $request->parametersForSigning());
		}
		
		$this->assertEquals($config_to_use->getAuthenticationType(),
		                    $request->getAuthenticationType());
		$this->assertArrayKeySameValue($config_to_use->getAuthenticationType(),
		                               $config_to_use->getAuthenticationActorId(),
		                               $request->parametersForSigning());
		
		$session_id = $session->getSessionStore()->getId();
		$this->assertArrayKeySameValue('session', $session_id, $request->parametersForSigning());
		$session_key = $session->getSessionStore()->getKey();
		$this->assertEquals($config_to_use->getAuthenticationActorKey() . $session_key,
		                    $request->signingKey());
		
		// We have set a customer, so there should not be an account now
		$this->assertNull($request->getAccount());
		$this->assertEmpty($request->getAccounts());
		$this->assertArrayNotHasKey('account', $request->parametersForSigning());
	}
	
	public function provideRequestWithCustomerInSession()
	{
		return array(
			array('application', 'customer1'),
			array('application', null),
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
