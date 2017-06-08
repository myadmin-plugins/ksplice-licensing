<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_ksplice define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Ksplice Licensing',
	'description' => 'Allows selling of Ksplice Server and VPS License Types.  More info at https://www.netenberg.com/ksplice.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a ksplice license. Allow 10 minutes for activation.',
	'module' => 'licenses',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-ksplice-licensing',
	'repo' => 'https://github.com/detain/myadmin-ksplice-licensing',
	'version' => '1.0.0',
	'type' => 'service',
	'hooks' => [
		'function.requirements' => ['Detain\MyAdminKsplice\Plugin', 'Requirements'],
		'licenses.settings' => ['Detain\MyAdminKsplice\Plugin', 'Settings'],
		'licenses.activate' => ['Detain\MyAdminKsplice\Plugin', 'Activate'],
		'licenses.deactivate' => ['Detain\MyAdminKsplice\Plugin', 'Deactivate'],
		/* 'licenses.change_ip' => ['Detain\MyAdminKsplice\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminKsplice\Plugin', 'Menu'] */
	],
];
