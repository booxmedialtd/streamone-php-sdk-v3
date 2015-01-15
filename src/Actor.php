<?php
/**
 * @addtogroup StreamOneSDK
 * @{
 */

namespace StreamOne\API\v3;

/**
 * An actor corresponding to a user (with or without session) or application.
 *
 * Besides information about whether this actor is a user or application, one can also set an
 * account, multiple accounts or a customer for the actor
 */
class Actor
{
	/// The configuration object to use for this Actor
	private $config;

	/// The session object to use for this Actor
	private $session;

	/// The customer to use for this Actor
	private $customer = null;

	/// The account(s) to use for this Actor
	private $accounts = array();

	/**
	 * Construct a new actor object
	 *
	 * @param Config $config
	 *   The configuration object to use for this actor
	 * @param Session|null $session
	 *   The session object to use for this actor; if null, it will use authentication information
	 *   from the configuration
	 */
	public function __construct(Config $config, $session = null)
	{
		$this->config = $config;
		$this->session = $session;

		if ($config->getDefaultAccountId() !== null)
		{
			$this->accounts = array($config->getDefaultAccountId());
		}
	}

	/**
	 * Set the account to use for this actor
	 *
	 * @param string|null $account
	 *   Hash of the account to use for this actor; if null, clear account. Note that calling this
	 *   function will clear the customer, as it is not possible to have both at the same time
	 */
	public function setAccount($account)
	{
		if ($account === null)
		{
			$this->accounts = array();
		}
		else
		{
			$this->accounts = array($account);
		}
		$this->customer = null;
	}

	/**
	 * Get the account used for this actor
	 *
	 * @retval string|null
	 *   Hash of the account used for this actor; null if none
	 */
	public function getAccount()
	{
		if (empty($this->accounts))
		{
			return null;
		}
		return $this->accounts[0];
	}

	/**
	 * Set the accounts to use for this actor
	 *
	 * @param array $accounts
	 *   Array with hashes of the accounts to use for this actor. Note that calling this
	 *   function will clear the customer, as it is not possible to have both at the same time
	 */
	public function setAccounts(array $accounts)
	{
		$this->accounts = $accounts;
		$this->customer = null;
	}

	/**
	 * Get the accounts used for this actor
	 *
	 * @retval array
	 *   The hashes of the accounts used for this actor; empty array if none
	 */
	public function getAccounts()
	{
		return $this->accounts;
	}

	/**
	 * Set the customer to use for this actor
	 *
	 * @param string|null $customer
	 *   Hash of the customer to use for this actor; if null, clear customer. Note that calling this
	 *   function will clear the account(s), as it is not possible to have both at the same time
	 */
	public function setCustomer($customer)
	{
		$this->customer = $customer;
		$this->accounts = array();
	}

	/**
	 * Get the customer used for this actor
	 *
	 * @retval string|null
	 *   The hash of the customer used for this actor; null if none
	 */
	public function getCustomer()
	{
		return $this->customer;
	}

	/**
	 * Create a new request to the API for this actor
	 *
	 * @param string $command
	 *   The API command to call
	 * @param string $action
	 *   The action to perform on the API command
	 *
	 * @retval Request
	 *   A request to the given command and action for the given actor
	 */
	public function newRequest($command, $action)
	{
		if ($this->session !== null && $this->session->isActive())
		{
			$request = $this->session->newRequest($command, $action);
		}
		else
		{
			$request = new Request($command, $action, $this->config);
		}

		if ($this->customer !== null)
		{
			$request->setCustomer($this->customer);
		}
		elseif (!empty($this->accounts))
		{
			$request->setAccounts($this->accounts);
		}
		else
		{
			$request->setAccount(null);
		}

		return $request;
	}
}

/**
 * @}
 */
