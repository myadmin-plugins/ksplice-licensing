<?php

namespace Detain\MyAdminKsplice;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Ksplice Licensing';
	public static $description = 'Allows selling of Ksplice Server and VPS License Types.  More info at https://www.netenberg.com/ksplice.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a ksplice license. Allow 10 minutes for activation.';
	public static $module = 'licenses';
	public static $type = 'service';


	public function __construct() {
	}

	public static function Hooks() {
		return [
			'function.requirements' => ['Detain\MyAdminKsplice\Plugin', 'Requirements'],
			'licenses.settings' => ['Detain\MyAdminKsplice\Plugin', 'Settings'],
			'licenses.activate' => ['Detain\MyAdminKsplice\Plugin', 'Activate'],
			'licenses.deactivate' => ['Detain\MyAdminKsplice\Plugin', 'Deactivate'],
		];
	}

	public static function Activate(GenericEvent $event) {
		// will be executed when the licenses.license event is dispatched
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_KSPLICE) {
			myadmin_log('licenses', 'info', 'Ksplice Activation', __LINE__, __FILE__);
			function_requirements('activate_ksplice');
			activate_ksplice($license->get_ip(), $event['field1']);
			$ksplice = new \Detain\MyAdminKsplice\Ksplice(KSPLICE_API_USERNAME, KSPLICE_API_KEY);
			$uuid = $ksplice->ip_to_uuid($license->get_ip());
			myadmin_log('licenses', 'info', "Got UUID $uuid from IP " . $license->get_ip(), __LINE__, __FILE__);
			$ksplice->authorize_machine($uuid, true);
			myadmin_log('licenses', 'info', 'Response: ' . $ksplice->response_raw, __LINE__, __FILE__);
			myadmin_log('licenses', 'info', 'Response: ' . json_encode($ksplice->response), __LINE__, __FILE__);
			$event->stopPropagation();
		}
	}

	public static function Deactivate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_KSPLICE) {
			myadmin_log('licenses', 'info', 'Ksplice Deactivation', __LINE__, __FILE__);
			function_requirements('deactivate_ksplice');
			deactivate_ksplice($license->get_ip());
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_KSPLICE) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$ksplice = new Ksplice(KSPLICE_USERNAME, KSPLICE_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $ksplice->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Ksplice editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function Menu(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_ksplice', 'icons/database_warning_48.png', 'ReUsable Ksplice Licenses');
			$menu->add_link($module, 'choice=none.ksplice_list', 'icons/database_warning_48.png', 'Ksplice Licenses Breakdown');
			$menu->add_link($module.'api', 'choice=none.ksplice_licenses_list', 'whm/createacct.gif', 'List all Ksplice Licenses');
		}
	}

	public static function Requirements(GenericEvent $event) {
		// will be executed when the licenses.loader event is dispatched
		$loader = $event->getSubject();
		$loader->add_requirement('class.Ksplice', '/../vendor/detain/myadmin-ksplice-licensing/src/Ksplice.php');
		$loader->add_requirement('deactivate_ksplice', '/../vendor/detain/myadmin-ksplice-licensing/src/ksplice.inc.php');
	}

	public static function Settings(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$settings = $event->getSubject();
		$settings->add_text_setting('licenses', 'KSplice', 'ksplice_api_username', 'Ksplice API Username:', 'Ksplice API Username', $settings->get_setting('KSPLICE_API_USERNAME'));
		$settings->add_text_setting('licenses', 'KSplice', 'ksplice_api_key', 'Ksplice API Key:', 'Ksplice API Key', $settings->get_setting('KSPLICE_API_KEY'));
		$settings->add_dropdown_setting('licenses', 'KSplice', 'outofstock_licenses_ksplice', 'Out Of Stock Ksplice Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_KSPLICE'), array('0', '1'), array('No', 'Yes', ));
	}

}
