<?php
/**
 * REST Client
 *
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2018
 * @package MyAdmin
 * @category REST
 */

require_once INCLUDE_ROOT.'/../vendor/pear/HTTP_Request/Request.php';

/**
 * RESTClient
 *
 * @access public
 */
class RESTClient
{

	private $root_url = '';
	private $curr_url = '';
	private $user_name = '';
	private $password = '';
	private $response = '';
	private $responseBody = '';
	private $req;

	/**
	 * RESTClient::__construct()
	 * @param string $root_url
	 * @param string $user_name
	 * @param string $password
	 * @return \RESTClient
	 */
	public function __construct($root_url = '', $user_name = '', $password = '') {
		$this->root_url = $this->curr_url = $root_url;
		$this->user_name = $user_name;
		$this->password = $password;
		if ($root_url != '') {
			$this->createRequest($root_url, 'GET');
			$this->sendRequest();
		}
	}

	/**
	 * RESTClient::createRequest()
	 *
	 * @param mixed $url
	 * @param mixed $method
	 * @param mixed $arr
	 * @param mixed $headers
	 * @return void
	 */
	public function createRequest($url, $method, $arr = null, $headers = null) {
		$this->curr_url = $url;
		$this->req = new HTTP_Request($url);
		if (is_array($headers)) {
			foreach ($headers as $key => $value)
				$this->req->addHeader($key, $value);
		}
		if ($this->user_name != '' && $this->password != '')
			$this->req->setBasicAuth($this->user_name, $this->password);

		switch ($method) {
			case 'GET':
				$this->req->setMethod(HTTP_REQUEST_METHOD_GET);
				break;
			case 'POST':
				$this->req->setMethod(HTTP_REQUEST_METHOD_POST);
				$this->req->setBody($arr);
				//					$this->addPostData($arr);
				break;
			case 'PUT':
				$this->req->setMethod(HTTP_REQUEST_METHOD_PUT);
				// to-do
				break;
			case 'DELETE':
				$this->req->setMethod(HTTP_REQUEST_METHOD_DELETE);
				// to-do
				break;
		}
	}

	/**
	 * RESTClient::sendRequest()
	 *
	 * @return void
	 */
	public function sendRequest() {
		$this->response = $this->req->sendRequest();

		if (PEAR::isError($this->response)) {
			echo $this->response->getMessage();
			die();
		} else {
			$this->responseBody = $this->req->getResponseBody();
		}
	}

	/**
	 * RESTClient::getResponse()
	 *
	 * @return string
	 */
	public function getResponse() {
		return $this->responseBody;
	}

}
