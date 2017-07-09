<?php
/**
 * Ksplice Functionality
 *
 * API Documentation at http://www.ksplice.com/uptrack/api
 *
 * Last Changed: $LastChangedDate: 2017-05-31 17:13:05 -0400 (Wed, 31 May 2017) $
 * @author detain
 * @copyright 2017
 * @package MyAdmin
 * @category Licenses
 */

/**
 * deactivate a ksplice license
 *
 * @param string $ipAddressUuid can be either an ip or the uuid from the $serviceExtra['ksplice_uuid']
 */
function deactivate_ksplice($ipAddressUuid) {
	// Deactivate Ksplice
	//
	$ksplice = new \Detain\MyAdminKsplice\Ksplice(KSPLICE_API_USERNAME, KSPLICE_API_KEY);
	if (validIp($ipAddressUuid, FALSE)) {
		$uuid = $ksplice->ipToUuid($ipAddressUuid);
		myadmin_log('licenses', 'info', "Ksplice IP to UUID ({$ipAddressUuid}) Response {$uuid}", __LINE__, __FILE__);
	} else
		$uuid = $ipAddressUuid;
	$response = $ksplice->deauthorizeMachine($uuid);
	myadmin_log('licenses', 'info', "Deactivate Ksplice ({$ipAddressUuid}) Response ".json_encode($response), __LINE__, __FILE__);
}
