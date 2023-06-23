<?php

namespace Detain\MyAdminKsplice;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminKsplice
 */
class Plugin
{
    public static $name = 'Ksplice Licensing';
    public static $description = 'Allows selling of Ksplice Server and VPS License Types.  More info at https://www.netenberg.com/ksplice.php';
    public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.      Must have a pre-existing cPanel license with cPanelDirect to purchase a ksplice license. Allow 10 minutes for activation.';
    public static $module = 'licenses';
    public static $type = 'service';

    /**
     * Plugin constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return array
     */
    public static function getHooks()
    {
        return [
            'function.requirements' => [__CLASS__, 'getRequirements'],
            self::$module.'.settings' => [__CLASS__, 'getSettings'],
            self::$module.'.activate' => [__CLASS__, 'getActivate'],
            self::$module.'.reactivate' => [__CLASS__, 'getActivate'],
            self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
            self::$module.'.deactivate_ip' => [__CLASS__, 'getDeactivate']
        ];
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getActivate(GenericEvent $event)
    {
        $serviceClass = $event->getSubject();
        if ($event['category'] == get_service_define('KSPLICE')) {
            myadmin_log(self::$module, 'info', 'Ksplice Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            \function_requirements('activate_ksplice');
            activate_ksplice($serviceClass->getIp());
            $ksplice = new \Detain\MyAdminKsplice\Ksplice(KSPLICE_API_USERNAME, KSPLICE_API_KEY);
            $uuid = $ksplice->ipToUuid($serviceClass->getIp());
            myadmin_log(self::$module, 'info', "Got UUID $uuid from IP ".$serviceClass->getIp(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $ksplice->authorizeMachine($uuid, true);
            myadmin_log(self::$module, 'info', 'Response: '.$ksplice->responseRaw, __LINE__, __FILE__, self::$module, $serviceClass->getId());
            myadmin_log(self::$module, 'info', 'Response: '.json_encode($ksplice->response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getDeactivate(GenericEvent $event)
    {
        $serviceClass = $event->getSubject();
        if ($event['category'] == get_service_define('KSPLICE')) {
            myadmin_log(self::$module, 'info', 'Ksplice Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            \function_requirements('deactivate_ksplice');
            $event['success'] = deactivate_ksplice($serviceClass->getIp());
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getChangeIp(GenericEvent $event)
    {
        if ($event['category'] == get_service_define('KSPLICE')) {
            $serviceClass = $event->getSubject();
            $settings = get_module_settings(self::$module);
            $ksplice = new Ksplice(KSPLICE_USERNAME, KSPLICE_PASSWORD);
            myadmin_log(self::$module, 'info', 'IP Change - (OLD:' .$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $result = $ksplice->editIp($serviceClass->getIp(), $event['newip']);
            if (isset($result['faultcode'])) {
                myadmin_log(self::$module, 'error', 'Ksplice editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['status'] = 'error';
                $event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
            } else {
                $GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getId(), $serviceClass->getCustid());
                $serviceClass->set_ip($event['newip'])->save();
                $event['status'] = 'ok';
                $event['status_text'] = 'The IP Address has been changed.';
            }
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getMenu(GenericEvent $event)
    {
        $menu = $event->getSubject();
        if ($GLOBALS['tf']->ima == 'admin') {
            $menu->add_link(self::$module, 'choice=none.reusable_ksplice', '/images/myadmin/to-do.png', _('ReUsable Ksplice Licenses'));
            $menu->add_link(self::$module, 'choice=none.ksplice_list', '/images/myadmin/to-do.png', _('Ksplice Licenses Breakdown'));
            $menu->add_link(self::$module.'api', 'choice=none.ksplice_licenses_list', '/images/whm/createacct.gif', _('List all Ksplice Licenses'));
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getRequirements(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Plugins\Loader $this->loader
         */
        $loader = $event->getSubject();
//        $loader->add_requirement('class.RESTClient', '/../vendor/detain/myadmin-ksplice-licensing/src/RESTClient.php');
//        $loader->add_requirement('class.Ksplice', '/../vendor/detain/myadmin-ksplice-licensing/src/Ksplice.php', '\\Detain\\MyAdminKsplice\\');
        $loader->add_requirement('deactivate_ksplice', '/../vendor/detain/myadmin-ksplice-licensing/src/ksplice.inc.php');
        $loader->add_requirement('activate_ksplice', '/../vendor/detain/myadmin-ksplice-licensing/src/ksplice.inc.php');
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
        $settings->add_text_setting(self::$module, _('KSplice'), 'ksplice_api_username', _('Ksplice API Username'), _('Ksplice API Username'), $settings->get_setting('KSPLICE_API_USERNAME'));
        $settings->add_password_setting(self::$module, _('KSplice'), 'ksplice_api_key', _('Ksplice API Key'), _('Ksplice API Key'), $settings->get_setting('KSPLICE_API_KEY'));
        $settings->add_dropdown_setting(self::$module, _('KSplice'), 'outofstock_licenses_ksplice', _('Out Of Stock Ksplice Licenses'), _('Enable/Disable Sales Of This Type'), $settings->get_setting('OUTOFSTOCK_LICENSES_KSPLICE'), ['0', '1'], ['No', 'Yes']);
    }
}
