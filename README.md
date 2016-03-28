# [INACTIVE] Codeigniter Authorize.Net ARB Integration

> **Warning:** This repo is no longer being mantianed. Use at your own risk.

A quick way to add Authorize.Net Automated Recurring Billing (ARB) integration to a Codeigniter site. ARB is simpler than using CIM if all you need to do is recurring/subscription based billing.

Requires [Philip Sturgeon's](http://philsturgeon.co.uk/) [cURL lib](http://getsparks.org/packages/curl/show) (included).

Installation
------------

1. Copy the /config/authorize_net.php file into your application's config/ folder and make sure to change the values!
2. Copy /libraries/Authorize_ARB.php and /libraries/Curl.php into your application's libraries/ folder.

Usage
-----

This library will allow you to create, update or cancel an ARB subscription.

### Initialization

First and foremost, you must load the library of course:

	$this->load->library('authorize_arb');

And as with most libraries, you can send in an array of config data.

### Create subscription

To create a subscription, you need to initialize a "create" object:

	$this->authorize_arb->startData('create');

You can optionally send a reference ID:

	$this->authorize_arb->addData('refId', 'my_reference_id');

Then you need to create an associative array of the subscription data. The naming schema and order of the array must match the XML send data detailed in the [ARB documentation](http://www.authorize.net/support/ARB_guide.pdf). For example:

	$subscription_data = array(
			'name' => 'My Test Subscription',
			'paymentSchedule' => array(
				'interval' => array(
					'length' => 1,
					'unit' => 'months',
					),
				'startDate' => date('Y-m-d'),
				'totalOccurrences' => 9999, // Unlimited
				),
			'amount' => 19.99,
			'payment' => array(
				'creditCard' => array(
					'cardNumber' => '4111111111111111',
					'expirationDate' => '2016-08',
					'cardCode' => '123',
					),
				),
			'billTo' => array(
				'firstName' => 'Bill',
				'lastName' => 'Gates',
				'address' => '123 Green St',
				'city' => 'Lexington',
				'state' => 'KY',
				'zip' => '40502',
				'country' => 'US',
				),
			);
	
	$this->authorize_arb->addData('subscription', $subscription_data);

Then all you do is send the request

	$this->authorize_arb->send();

The send() method will return TRUE on success or FALSE on failure. On success, the subscription ID can be accessed like so:

	$subscription_id = $this->authorize_arb->getId();

On failure, any error messages can be accessed like so:

	$error = $this->authorize_arb->getError();

### Update subscription

Updating a subscription is the same as creating one except you need to initialize an "update" object instead of a "create" object:

	$this->authorize_arb->startData('update');

And you also need to send in the subscription ID after the reference ID:

	$this->authorize_arb->addData('subscriptionId', 1234);

### Cancel subscription

To cancel a subscription you just need to initialize a "cancel" object and send in the subscription ID:

	$this->authorize_arb->startData('cancel');
	$this->authorize_arb->addData('subscriptionId', 1234);

### See it in action

To see a full example of create, update and cancel, check out /controllers/examples.php

Enjoy!
[SammyK](http://sammyk.me/)
