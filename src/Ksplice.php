<?php
/**
 * Ksplice Functionality
 *
 * API Documentation at http://www.ksplice.com/uptrack/api
 *
 * Last Changed: $LastChangedDate: 2017-05-31 17:13:05 -0400 (Wed, 31 May 2017) $
 * @author detain
 * @version $Revision: 24968 $
 * @copyright 2017
 * @package MyAdmin
 * @category Licenses
 */

namespace Detain\MyAdminKsplice;

/**
 * Ksplice
 *
 * @access public
 */
class Ksplice {
	private $apiKey;
	private $apiUsername;
	private $urlBase = 'https://uptrack.api.ksplice.com';
	public $url = '';
	public $method = 'GET';
	public $headers = [];
	public $inputs = '';
	public $responseRaw = '';
	public $response = [];
	private $restClient;
	public $machinesLoaded = FALSE;
	public $ips = [];
	public $hosts = [];
	public $uuids = [];

	/**
	 * Ksplice::__construct()
	 * @return \Ksplice
	 */
	public function __construct($apiUsername, $apiKey) {
		$this->apiUsername = $apiUsername;
		$this->apiKey = $apiKey;
		if (file_exists(__DIR__.'/../../../../include/rendering/RESTClient.php'))
			include_once(__DIR__.'/../../../../include/rendering/RESTClient.php');
		if (class_exists('\\RestClient'))
			$this->restClient = new \RESTClient();
		$this->headers = array(
			'X-Uptrack-User' => $this->apiUsername,
			'X-Uptrack-Key' => $this->apiKey,
			'Accept' => 'application/json');
	}

	/**
	 * Ksplice::request()
	 *
	 * @return void
	 */
	public function request() {
		$this->restClient->createRequest($this->urlBase.$this->url, $this->method, $this->inputs, $this->headers);
		$this->restClient->sendRequest();
		$this->responseRaw = $this->restClient->getResponse();
		$this->response = json_decode($this->responseRaw);
		return $this->response;
	}

	/**
	 * Ksplice::listMachines()
	 *
	 * @return void
	 */
	public function listMachines() {
		$this->url = '/api/1/machines';
		$this->method = 'GET';
		$machines = obj2array($this->request());
		$machinesValues = array_values($machines);
		foreach ($machinesValues as $data) {
			$this->ips[$data['ip']] = $data;
			$this->hosts[$data['hostname']] = $data;
			$this->uuids[$data['uuid']] = $data;
		}
		$this->machinesLoaded = TRUE;
		return $this->response;
	}

	/**
	 * Ksplice::describeMachine()
	 *
	 * @param mixed $uuid
	 * @return void
	 */
	public function describeMachine($uuid) {
		$this->url = '/api/1/machine/'.$uuid.'/describe';
		$this->method = 'GET';
		return $this->request();
	}

	/**
	 * Ksplice::ipToUuid()
	 *
	 * @param mixed $ipAddress
	 * @return string|bool
	 */
	public function ipToUuid($ipAddress) {
		if (!$this->machinesLoaded) {
			$this->listMachines();
		}
		if (isset($this->ips[$ipAddress])) {
			return $this->ips[$ipAddress]['uuid'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Ksplice::authorizeMachine()
	 *
	 * @param mixed $uuid
	 * @param bool $authorize
	 * @return void
	 */
	public function authorizeMachine($uuid, $authorize = TRUE) {
		$this->url = '/api/1/machine/'.$uuid.'/authorize';
		$this->method = 'POST';
		$this->inputs = json_encode(array('authorized' => $authorize));
		$this->request();
		if ($authorize == TRUE)
			myadmin_log('licenses', 'info', "Authorize Ksplice ({$uuid}, {$authorize}) Response: ".json_encode($this->response), __LINE__, __FILE__);
		else
			myadmin_log('licenses', 'info', "Deauthorize Ksplice ({$uuid}, {$authorize}) Response: ".json_encode($this->response), __LINE__, __FILE__);
		return $this->response;
	}

	/**
	 * @param string|boolean $uuid
	 */
	public function deauthorizeMachine($uuid) {
		return $this->authorizeMachine($uuid, FALSE);
	}

	/**
	 * Ksplice::changeGroup()
	 *
	 * @param mixed $uuid
	 * @param string $groupName
	 * @return void
	 */
	public function changeGroup($uuid, $groupName = '') {
		$this->url = '/api/1/machine/'.$uuid.'/group';
		$this->method = 'POST';
		$this->inputs = json_encode(array('group_name' => $groupName));
		return $this->request();
	}

}
