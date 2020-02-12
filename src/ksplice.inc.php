<?php
/**
 * Ksplice Functionality
 *
 * API Documentation at http://www.ksplice.com/uptrack/api
 *
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category Licenses
 */

/**
 * deactivate a ksplice license
 *
 * @param string $ipAddressUuid can be either an ip or the uuid from the $serviceExtra['ksplice_uuid']
 */
function deactivate_ksplice($ipAddressUuid)
{
	// Deactivate Ksplice
	//
	$ksplice = new \Detain\MyAdminKsplice\Ksplice(KSPLICE_API_USERNAME, KSPLICE_API_KEY);
	if (validIp($ipAddressUuid, false)) {
		$uuid = $ksplice->ipToUuid($ipAddressUuid);
		myadmin_log('licenses', 'info', "Ksplice IP to UUID ({$ipAddressUuid}) Response {$uuid}", __LINE__, __FILE__);
	} else {
		$uuid = $ipAddressUuid;
	}
	$response = $ksplice->deauthorizeMachine($uuid);
	myadmin_log('licenses', 'info', "Deactivate Ksplice ({$ipAddressUuid}) Response ".json_encode($response), __LINE__, __FILE__);
	return true;
}

/**
 * @param $ipAddressUuid
 */
function activate_ksplice($ipAddressUuid)
{
	// Deactivate Ksplice
	//
	$ksplice = new \Detain\MyAdminKsplice\Ksplice(KSPLICE_API_USERNAME, KSPLICE_API_KEY);
	if (validIp($ipAddressUuid, false)) {
		$uuid = $ksplice->ipToUuid($ipAddressUuid);
		myadmin_log('licenses', 'info', "Ksplice IP to UUID ({$ipAddressUuid}) Response {$uuid}", __LINE__, __FILE__);
	} else {
		$uuid = $ipAddressUuid;
	}
	$ksplice->authorize_machine($uuid, true);
	myadmin_log('licenses', 'info', 'Response: ' . $ksplice->response_raw, __LINE__, __FILE__);
	myadmin_log('licenses', 'info', 'Response: ' . json_encode($ksplice->response), __LINE__, __FILE__);
	return true;
}
