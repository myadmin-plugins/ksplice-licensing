# MyAdmin Ksplice Licensing Plugin

Composer plugin package that manages Oracle Ksplice rebootless kernel update licenses via the Uptrack API. Integrates with the MyAdmin event-driven plugin system.

## Commands

```bash
composer install              # install deps including phpunit/phpunit ^9.6
vendor/bin/phpunit            # run all tests (uses phpunit.xml.dist)
```

## Architecture

- **`src/Ksplice.php`** — API client for `https://uptrack.api.ksplice.com`; wraps `RESTClient`, sets `X-Uptrack-User` / `X-Uptrack-Key` headers
- **`src/Plugin.php`** — MyAdmin plugin; static class, registers hooks via `getHooks()`, handles `licenses.activate` / `licenses.deactivate` / `licenses.settings` events
- **`src/RESTClient.php`** — thin wrapper over PEAR `HTTP_Request`; lives in global namespace (no namespace declaration)
- **`src/ksplice.inc.php`** — procedural helpers `activate_ksplice($ip)` / `deactivate_ksplice($ip)` loaded via `function_requirements()`
- **`tests/`** — PHPUnit 9 reflection-based tests; bootstrap at `tests/bootstrap.php`
- **`.github/`** — CI/CD workflow configurations for automated testing and deployment pipelines
- **`.idea/`** — IDE project settings including `inspectionProfiles`, `deployment.xml`, and `encodings.xml`

## Conventions

- Namespace: `Detain\MyAdminKsplice\` → `src/`; tests: `Detain\MyAdminKsplice\Tests\` → `tests/`
- `RESTClient` is global namespace — reference as `\RESTClient` or `\RestClient` inside namespaced code
- All logging via `myadmin_log('licenses', $level, $message, __LINE__, __FILE__)`
- Plugin hooks return `void`; stop propagation with `$event->stopPropagation()` after handling
- Settings registered in `getSettings()` using `add_text_setting()` / `add_password_setting()` / `add_dropdown_setting()`
- Constants used: `KSPLICE_API_USERNAME`, `KSPLICE_API_KEY`, `KSPLICE`, `INCLUDE_ROOT`
- Procedural functions in `src/ksplice.inc.php` registered via `$loader->add_requirement('func_name', 'path/to/ksplice.inc.php')` in `getRequirements()`
- Tabs for indentation (see `.scrutinizer.yml` coding style)
- Commit messages: lowercase, descriptive

## Plugin Hook Pattern

```php
public static function getHooks(): array {
    return [
        'function.requirements'   => [__CLASS__, 'getRequirements'],
        self::$module.'.settings' => [__CLASS__, 'getSettings'],
        self::$module.'.activate' => [__CLASS__, 'getActivate'],
    ];
}

public static function getActivate(GenericEvent $event): void {
    $serviceClass = $event->getSubject();
    if ($event['category'] == get_service_define('KSPLICE')) {
        myadmin_log(self::$module, 'info', 'Ksplice Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        // ... do work ...
        $event->stopPropagation();
    }
}
```

## Ksplice API Pattern

```php
$ksplice = new \Detain\MyAdminKsplice\Ksplice(KSPLICE_API_USERNAME, KSPLICE_API_KEY);
$uuid    = $ksplice->ipToUuid($ipAddress);   // calls listMachines() if not loaded
$ksplice->authorizeMachine($uuid, true);      // POST /api/1/machine/{uuid}/authorize
$ksplice->deauthorizeMachine($uuid);          // POST with authorized=false
$ksplice->changeGroup($uuid, 'group-name');   // POST /api/1/machine/{uuid}/group
```

## Test Pattern

Tests use `ReflectionClass` — do not instantiate classes that require external deps:

```php
$reflection = new ReflectionClass(Ksplice::class);
$instance   = $reflection->newInstanceWithoutConstructor();
$instance->machinesLoaded = true;
$instance->ips = ['10.0.0.1' => ['uuid' => 'abc-123']];
$this->assertSame('abc-123', $instance->ipToUuid('10.0.0.1'));
```

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
