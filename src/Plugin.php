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

	public static function getHooks() {
		return [
			'function.requirements' => [__CLASS__, 'getRequirements'],
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.reactivate' => [__CLASS__, 'getActivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
			self::$module.'.deactivate_ip' => [__CLASS__, 'getDeactivate'],
		];
	}

	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('KSPLICE')) {
			myadmin_log(self::$module, 'info', 'Ksplice Activation', __LINE__, __FILE__);
			function_requirements('activate_ksplice');
			activate_ksplice($serviceClass->getIp(), $event['field1']);
			$ksplice = new \Detain\MyAdminKsplice\Ksplice(KSPLICE_API_USERNAME, KSPLICE_API_KEY);
			$uuid = $ksplice->ipToUuid($serviceClass->getIp());
			myadmin_log(self::$module, 'info', "Got UUID $uuid from IP ".$serviceClass->getIp(), __LINE__, __FILE__);
			$ksplice->authorizeMachine($uuid, TRUE);
			myadmin_log(self::$module, 'info', 'Response: '.$ksplice->responseRaw, __LINE__, __FILE__);
			myadmin_log(self::$module, 'info', 'Response: '.json_encode($ksplice->response), __LINE__, __FILE__);
			$event->stopPropagation();
		}
	}

	public static function getDeactivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('KSPLICE')) {
			myadmin_log(self::$module, 'info', 'Ksplice Deactivation', __LINE__, __FILE__);
			function_requirements('deactivate_ksplice');
			deactivate_ksplice($serviceClass->getIp());
			$event->stopPropagation();
		}
	}

	public static function getChangeIp(GenericEvent $event) {
		if ($event['category'] == get_service_define('KSPLICE')) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$ksplice = new Ksplice(KSPLICE_USERNAME, KSPLICE_PASSWORD);
			myadmin_log(self::$module, 'info', "IP Change - (OLD:".$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $ksplice->editIp($serviceClass->getIp(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'Ksplice editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getIp());
				$serviceClass->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_ksplice', 'icons/database_warning_48.png', 'ReUsable Ksplice Licenses');
			$menu->add_link(self::$module, 'choice=none.ksplice_list', 'icons/database_warning_48.png', 'Ksplice Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.ksplice_licenses_list', 'whm/createacct.gif', 'List all Ksplice Licenses');
		}
	}

	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('class.Ksplice', '/../vendor/detain/myadmin-ksplice-licensing/src/Ksplice.php');
		$loader->add_requirement('deactivate_ksplice', '/../vendor/detain/myadmin-ksplice-licensing/src/ksplice.inc.php');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'KSplice', 'ksplice_api_username', 'Ksplice API Username:', 'Ksplice API Username', $settings->get_setting('KSPLICE_API_USERNAME'));
		$settings->add_text_setting(self::$module, 'KSplice', 'ksplice_api_key', 'Ksplice API Key:', 'Ksplice API Key', $settings->get_setting('KSPLICE_API_KEY'));
		$settings->add_dropdown_setting(self::$module, 'KSplice', 'outofstock_licenses_ksplice', 'Out Of Stock Ksplice Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_KSPLICE'), ['0', '1'], ['No', 'Yes']);
	}

}
