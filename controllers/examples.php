<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Examples extends CI_Controller
{	
	function __construct()
	{
		parent::__construct();
	}
	
	// Create Profile
	function create()
	{
		// Load the ARB lib
		$this->load->library('authorize_arb');
		
		echo '<h1>Creating Profile</h1>';
		
		// Start with a create object
		$this->authorize_arb->startData('create');
		
		// Locally-defined reference ID (can't be longer than 20 chars)
		$refId = substr(md5( microtime() . 'ref' ), 0, 20);
		$this->authorize_arb->addData('refId', $refId);
		
		// Data must be in this specific order
		// For full list of possible data, refer to the documentation:
		// http://www.authorize.net/support/ARB_guide.pdf
		$subscription_data = array(
			'name' => 'My Test Subscription',
			'paymentSchedule' => array(
				'interval' => array(
					'length' => 1,
					'unit' => 'months',
					),
				'startDate' => date('Y-m-d'),
				'totalOccurrences' => 9999,
				'trialOccurrences' => 0,
				),
			'amount' => 10.50,
			'trialAmount' => 0.00,
			'payment' => array(
				'creditCard' => array(
					'cardNumber' => '4111111111111111',
					'expirationDate' => '2014-08',
					'cardCode' => '123',
					),
				),
			'order' => array(
				'invoiceNumber' => '123',
				'description' => 'Campaign name',
				),
			'customer' => array(
				'id' => '777',
				'email' => 'test@test.com',
				'phoneNumber' => '859-222-1111',
				),
			'billTo' => array(
				'firstName' => 'James',
				'lastName' => 'Dobson',
				'address' => '123 Green St',
				'city' => 'Lexington',
				'state' => 'KY',
				'zip' => '40502',
				'country' => 'US',
				),
			);
		
		$this->authorize_arb->addData('subscription', $subscription_data);
		
		// Send request
		if( $this->authorize_arb->send() )
		{
			echo '<h1>Success! ID: ' . $this->authorize_arb->getId() . '</h1>';
		}
		else
		{
			echo '<h1>Epic Fail!</h1>';
			echo '<p>' . $this->authorize_arb->getError() . '</p>';
		}
		
		// Show debug data
		$this->authorize_arb->debug();
	}
	
	// Update Profile
	function update( $subscription_id )
	{
		// Load the ARB lib
		$this->load->library('authorize_arb');
		
		echo '<h1>Updating Profile</h1>';
		
		// Start with an update object
		$this->authorize_arb->startData('update');
		
		// Locally-defined reference ID (can't be longer than 20 chars)
		$refId = substr(md5( microtime() . 'ref' ), 0, 20);
		$this->authorize_arb->addData('refId', $refId);
		
		// The subscription ID that we're editing
		$this->authorize_arb->addData('subscriptionId', $subscription_id);
		
		// Data must be in this specific order
		// For full list of possible data, refer to the documentation:
		// http://www.authorize.net/support/ARB_guide.pdf
		$subscription_data = array(
			'name' => 'My Updated Subscription',
			'paymentSchedule' => array(
				'totalOccurrences' => 17,
				'trialOccurrences' => 1,
				),
			'amount' => 14.99,
			'trialAmount' => 9.99,
			'payment' => array(
				'creditCard' => array(
					'cardNumber' => '5105105105105100',
					'expirationDate' => '2013-07',
					'cardCode' => '777',
					),
				),
			'order' => array(
				'invoiceNumber' => '774',
				'description' => 'Updated Campaign name',
				),
			'customer' => array(
				'id' => '774',
				'email' => 'update@edit.com',
				'phoneNumber' => '859-777-7777',
				),
			'billTo' => array(
				'firstName' => 'Dan',
				'lastName' => 'Bryson',
				'address' => '123 Blue St',
				'city' => 'London',
				'state' => 'CA',
				'zip' => '90210',
				'country' => 'US',
				),
			);
		
		$this->authorize_arb->addData('subscription', $subscription_data);
		
		// Send request
		if( $this->authorize_arb->send() )
		{
			echo '<h1>Success! Ref ID: ' . $this->authorize_arb->getRefId() . '</h1>';
		}
		else
		{
			echo '<h1>Epic Fail!</h1>';
			echo '<p>' . $this->authorize_arb->getError() . '</p>';
		}
		
		// Show debug data
		$this->authorize_arb->debug();
	}
	
	// Cancel Profile
	function cancel( $subscription_id )
	{
		// Load the ARB lib
		$this->load->library('authorize_arb');
		
		echo '<h1>Canceling Profile</h1>';
		
		// Start with a cancel object
		$this->authorize_arb->startData('cancel');
		
		// Locally-defined reference ID (can't be longer than 20 chars)
		$refId = substr(md5( microtime() . 'ref' ), 0, 20);
		$this->authorize_arb->addData('refId', $refId);
		
		// The subscription ID that we're canceling
		$this->authorize_arb->addData('subscriptionId', $subscription_id);
		
		// Send request
		if( $this->authorize_arb->send() )
		{
			echo '<h1>Success! Ref ID: ' . $this->authorize_arb->getRefId() . '</h1>';
		}
		else
		{
			echo '<h1>Epic Fail!</h1>';
			echo '<p>' . $this->authorize_arb->getError() . '</p>';
		}
		
		// Show debug data
		$this->authorize_arb->debug();
	}
	
}

/* EOF */