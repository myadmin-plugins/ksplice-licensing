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
class Ksplice
{
	private $apiKey;
	private $apiUsername;
	private $urlBase = 'https://uptrack.api.ksplice.com';
	public $url = '';
	public $method = 'GET';
	public $headers = [];
	public $inputs = '';
	public $response_raw = '';
	public $response = [];
	private $rest_client;
	public $machines_loaded = FALSE;
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
		if (file_exists(__DIR__ . '/../../../../include/rendering/RESTClient.php'))
			include_once(__DIR__ . '/../../../../include/rendering/RESTClient.php');
		if (class_exists('\\RestClient'))
			$this->rest_client = new \RESTClient();
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
		$this->rest_client->createRequest($this->urlBase . $this->url, $this->method, $this->inputs, $this->headers);
		$this->rest_client->sendRequest();
		$this->response_raw = $this->rest_client->getResponse();
		$this->response = json_decode($this->response_raw);
		return $this->response;
	}

	/**
	 * Ksplice::list_machines()
	 *
	 * @return void
	 */
	public function list_machines() {
		$this->url = '/api/1/machines';
		$this->method = 'GET';
		$machines = obj2array($this->request());
		foreach ($machines as $idx => $data) {
			$this->ips[$data['ip']] = $data;
			$this->hosts[$data['hostname']] = $data;
			$this->uuids[$data['uuid']] = $data;
		}
		$this->machines_loaded = TRUE;
		return $this->response;
	}

	/**
	 * Ksplice::describe_machine()
	 *
	 * @param mixed $uuid
	 * @return void
	 */
	public function describe_machine($uuid) {
		$this->url = '/api/1/machine/' . $uuid . '/describe';
		$this->method = 'GET';
		return $this->request();
	}

	/**
	 * Ksplice::ip_to_uuid()
	 *
	 * @param mixed $ipAddress
	 * @return string|bool
	 */
	public function ip_to_uuid($ipAddress) {
		if (!$this->machines_loaded) {
			$this->list_machines();
		}
		if (isset($this->ips[$ipAddress])) {
			return $this->ips[$ipAddress]['uuid'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Ksplice::authorize_machine()
	 *
	 * @param mixed $uuid
	 * @param bool $authorize
	 * @return void
	 */
	public function authorize_machine($uuid, $authorize = TRUE) {
		$this->url = '/api/1/machine/' . $uuid . '/authorize';
		$this->method = 'POST';
		$this->inputs = json_encode(array('authorized' => $authorize));
		$this->request();
		if ($authorize == TRUE)
			myadmin_log('licenses', 'info', "Authorize Ksplice ({$uuid}, {$authorize}) Response: " . json_encode($this->response), __LINE__, __FILE__);
		else
			myadmin_log('licenses', 'info', "Deauthorize Ksplice ({$uuid}, {$authorize}) Response: " . json_encode($this->response), __LINE__, __FILE__);
		return $this->response;
	}

	/**
	 * @param $uuid
	 */
	public function deauthorize_machine($uuid) {
		return $this->authorize_machine($uuid, FALSE);
	}

	/**
	 * Ksplice::change_group()
	 *
	 * @param mixed $uuid
	 * @param string $groupName
	 * @return void
	 */
	public function change_group($uuid, $groupName = '') {
		$this->url = '/api/1/machine/' . $uuid . '/group';
		$this->method = 'POST';
		$this->inputs = json_encode(array('group_name' => $groupName));
		return $this->request();
	}

}
