# MyAdmin Ksplice Licensing

[![Build Status](https://github.com/detain/myadmin-ksplice-licensing/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-ksplice-licensing/actions)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-ksplice-licensing/version)](https://packagist.org/packages/detain/myadmin-ksplice-licensing)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-ksplice-licensing/downloads)](https://packagist.org/packages/detain/myadmin-ksplice-licensing)
[![License](https://poser.pugx.org/detain/myadmin-ksplice-licensing/license)](https://packagist.org/packages/detain/myadmin-ksplice-licensing)

A MyAdmin plugin for managing Oracle Ksplice rebootless kernel update licenses. Provides an API client for the Ksplice Uptrack service and integrates with the MyAdmin event-driven plugin system for license activation, deactivation, and IP management.

## Features

- Ksplice Uptrack API client for machine listing, authorization, and group management
- Event-driven plugin architecture with Symfony EventDispatcher integration
- License activation and deactivation handlers
- IP-based and UUID-based machine lookups
- Admin menu integration for license management

## Installation

Install with Composer:

```sh
composer require detain/myadmin-ksplice-licensing
```

## Usage

The plugin registers event hooks automatically through the MyAdmin plugin system. For direct API access:

```php
use Detain\MyAdminKsplice\Ksplice;

$ksplice = new Ksplice($apiUsername, $apiKey);
$machines = $ksplice->listMachines();
$uuid = $ksplice->ipToUuid('10.0.0.1');
$ksplice->authorizeMachine($uuid);
```

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

Licensed under the LGPL-2.1. See [LICENSE](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html) for details.
