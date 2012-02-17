<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Authorize.Net ARB API Integration
 *
 * For Authorize.Net ARB API integration
 *
 * @package        	CodeIgniter
 * @subpackage    	Libraries
 * @category    	Libraries
 * @author		SammyK (http://sammyk.me/)
 * @link		https://github.com/SammyK/codeigniter-authorize.net-arb-api
 */

class Authorize_arb
{
    private $CI;						// CodeIgniter instance

    private $api_login_id = '';			// API Login ID
    private $api_transaction_key = '';	// API Transation Key
    private $arb_api_url = '';			// Where we postin' to?
	
	private $type_list = array(	// The type of action we want to perform
		'create' => 'ARBCreateSubscriptionRequest',
		'update' => 'ARBUpdateSubscriptionRequest',
		'cancel' => 'ARBCancelSubscriptionRequest',
		);
	
    private $type;						// The current action type we are working with
    private $send_data;					// The data that we will be sending (SimpleXMLElement object)
	
	/*
	 * If your installation of cURL works without the "CURLOPT_SSL_VERIFYHOST"
	 * and "CURLOPT_SSL_VERIFYPEER" options disabled, then remove them
	 * from the array below for better security.
	 */
    private $curl_options = array(		// Additional cURL Options
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_TIMEOUT => 60,
		);
    private $curl_headers = array(
		'Connection: close',
		);
    private $curl_send_data_headers = array(	// Additional headers for POST/PUT
		'Content-type: application/xml',
		);
	
    private $response;					// Response from Authorize.Net
	
    private $error;						// Error to show to the user
	
	public function __construct( $config = array() )
	{
		$this->CI =& get_instance();
		
		// Load config file
		$this->CI->config->load('authorize_net', TRUE);
		
		// Pull the config into scope
		foreach( $this->CI->config->item('authorize_net') as $key => $value )
		{
			if( isset($this->$key) )
			{
				$this->$key = $value;
			}
		}

		// Inline config
		$this->initialize($config);
		
		// Load cURL lib
		$this->CI->load->library('curl');
	}

	// Initialize the lib config
	public function initialize( $config )
	{
		foreach( $config as $key => $value )
		{
			if( isset($this->$key) )
			{
				$this->$key = $value;
			}
		}
	}
	
	// Initialize the send data
	public function startData( $type )
	{
		if( !isset($this->type_list[$type]) )
		{
			$this->error = 'Invalid type "' . $type . '"';
			return FALSE;
		}
		
		$this->type = $this->type_list[$type];
		
		$this->send_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $this->type . '></' . $this->type . '>');
		$this->send_data->addAttribute('xmlns', 'https://killme.die/AnetApi/xml/v1/schema/AnetApiSchema.xsd'); // Workaround for relative name space warnings
		
		// Authentication
		$this->addData('merchantAuthentication',
				array(
					'name' => $this->api_login_id,
					'transactionKey' => $this->api_transaction_key,
					)
				);
	}
	
	// Add general data
	public function addData( $key, $value, $key_alias = '' )
	{
		$this->_addData( $key, $value, $this->send_data, $key_alias );
	}
	
	// Add data to a node
	private function _addData( $key, $value, &$node, $key_alias = '' )
	{
		if( is_array($value) )
		{
			$new_node = $this->_getNode($key);
			$key_alias = !empty($key_alias) ? $key_alias : $key;
			$this->_addArray($value, $new_node, $key_alias);
		}
		elseif( is_bool($value) )
		{
			$node->addChild($key, $value ? 'true' : 'false');
		}
		elseif( is_null($value) )
		{
			$node->addChild($key);
		}
		elseif( is_int($value) )
		{
			$node->addChild($key, $value);
		}
		else
		{
			$node->addChild($key, $this->_escape($value));
		}
	}
	
	// Escape a value
	private function _escape( $value )
    {
		return htmlspecialchars($value, NULL, 'UTF-8');
    }
	
	// Try to find a node, if not exists, create it
	private function _getNode( $name, $attr_data = NULL )
	{
		foreach( $this->send_data->children() as $child )
		{
			if( $name == $child->getName() )
			{
				return $child;
			}
		}
		
		// Not found, create the node
		$node = $this->send_data->addChild($name);
		
		if( is_array($attr_data) )
		{
			$node->addAttribute($attr_data['key'],$attr_data['value']);
		}
		
		return $node;
	}
	
	// Recursively add an array to the send data
	private function _addArray( $data, &$node, $key_alias = '' )
	{
		foreach( $data as $key => $value )
		{
			$key = is_numeric($key) ? $key_alias : $key;
			
			if( is_array($value) )
			{
				$subnode = $node->addChild($key);
				$this->_addArray($value, $subnode, $key);
			}
			else
			{
				$this->_addData($key, $value, $node);
			}
		}
	}
	
	// POST a request
	public function send()
	{
		$this->CI->curl->create($this->arb_api_url);
		
		foreach( $this->getHeaders('POST') as $header )
		{
			$this->CI->curl->http_header($header);
		}
		
		// Get the XML to send
		$send_xml = $this->getSendXml();
		
		$len = strlen($send_xml);
		$this->CI->curl->http_header('Content-Length: ' . $len);
		
		/*
		 * Very helpful debugging info if you need it
		 */
		//$f = fopen('request.txt', 'w');
		//$this->curl_options[CURLOPT_VERBOSE] = 1;
		//$this->curl_options[CURLOPT_STDERR] = $f;
		
		// POST data (as XML)
		$this->CI->curl->post($send_xml, $this->curl_options);
		
		$response = $this->CI->curl->execute();
		
		//fclose($f);

		return $this->parseResponse($response);
	}
	
	// Parse the response back from Authorize.Net
	public function parseResponse( $response )
	{
		if( $response === FALSE )
		{
			$this->error = 'There was a problem while contacting Authorize.Net. Please try again.';
			return FALSE;
		}
		elseif( is_string($response) )
		{
			$response = str_replace(' xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"', '', $response); // Workaround to kill warnings about relative name spaces
			$res = simplexml_load_string($response);
			if( $res !== FALSE )
			{
				if( isset($res->messages->resultCode) )
				{
					switch( $res->messages->resultCode )
					{
						case 'Error':
						$this->error = isset($res->messages->message->text) ? (string)$res->messages->message->text : 'Unknown error';
						return FALSE;
						break;
					
						case 'Ok':
						$this->response = $res;
						return TRUE;
						break;
					}
				}
			}
		}
		
		$this->error = 'Received an unknown response from the Authorize.Net. Please try again.';
		return FALSE;
	}
	
	// Get the headers we'll need for the request
	public function getHeaders( $method )
	{
		switch( $method )
		{
			case 'POST':
			return array_merge($this->curl_headers, $this->curl_send_data_headers);
			break;
		}
		
		return $this->curl_headers;
	}
	
	// Get the raw response
	public function getResponse()
	{
		return $this->response;
	}
	
	// Get the raw send data
	public function getSendData()
	{
		return $this->send_data;
	}
	
	// Get the XML we want to send
	private function getSendXml()
	{
		return $this->_stripWorkaround($this->send_data->asXML());
	}
	
	// Get the error text
	public function getError()
	{
		return $this->error;
	}
	
	// Get the id of the last created entry
	public function getId()
	{
		return isset($this->response->subscriptionId) ? (int)$this->response->subscriptionId : 0;
	}
	
	// Get the reference id of the last request
	public function getRefId()
	{
		return isset($this->response->refId) ? $this->response->refId : NULL;
	}
	
	// Show debug info
	public function debug( $show_curl_debug = FALSE )
	{
		echo '<h1>Authorize.Net ARB API Debug Info</h1>';
		$url = $this->CI->curl->debug_request();
		echo '<h3>URL: ' . $url['url'] . '</h3>';
		
		if( !empty($this->error) )
		{
			echo '<p>' . $this->error . '"</p>';
		}
		
		if( isset($this->send_data) )
		{
			echo '<h1>Send Data For "' . $this->type . '"</h1>';
			echo '<pre>';
			echo htmlspecialchars($this->formatXml($this->send_data), ENT_QUOTES, 'UTF-8');
			echo '</pre>';
		}
		
		if( isset($this->response) )
		{
			echo '<h1>Response For "' . $this->type . '"</h1>';
			echo '<pre>';
			echo htmlspecialchars($this->formatXml($this->response), ENT_QUOTES, 'UTF-8');
			echo '</pre>';
		}
		
		if( $show_curl_debug )
		{
			echo '<h1>cURL Debug Data</h1>';
			$this->CI->curl->debug();
		}
	}
	
	// Format XML nicely
	public function formatXml( $xml )
	{
		$dom = new DOMDocument('1.0');
		$dom->preserveWhiteSpace = FALSE;
		$dom->formatOutput = TRUE;
		$dom->loadXML($xml->asXML());
		return $this->_stripWorkaround($dom->saveXML());
	}
	
	// Strip workaround to kill warnings about relative name spaces
	private function _stripWorkaround( $xml )
	{
		return str_replace('https://killme.die/', '', $xml);
	}
	
	// Reset everything so we can go again
	public function clear()
	{
		$this->type = NULL;
		$this->send_data = NULL;
		
		$this->response = NULL;
		$this->error = NULL;
	}

}

/* EOF */