---
name: plugin-event-hooks
description: Adds new event handler methods to `src/Plugin.php` and registers them in `getHooks()`. Use when user says 'add hook', 'handle event', 'new plugin event', 'add handler for licenses.*'. Handlers must be static, accept `GenericEvent $event`, check `$event['category']`, call `$event->stopPropagation()`. Do NOT use for API client changes in `src/Ksplice.php` or `src/RESTClient.php`.
---
# Plugin Event Hooks

## Critical

- Every handler MUST guard with `if ($event['category'] == get_service_define('KSPLICE'))` before doing any work.
- Every handler MUST call `$event->stopPropagation()` as its last statement inside the category guard.
- Handlers are `public static function`; they return `void` (no return value).
- Log every significant action with `myadmin_log(self::$module, 'info', '...', __LINE__, __FILE__, self::$module, $serviceClass->getId())`.
- Use tabs for indentation — never spaces (enforced by `.scrutinizer.yml`).

## Instructions

1. **Identify the event name** to handle (e.g., `licenses.change_ip`, `licenses.suspend`). The module prefix is always `self::$module` (`'licenses'`).

2. **Register the hook in `getHooks()`** in `src/Plugin.php`.
   - Open `src/Plugin.php` and locate the `getHooks()` array (lines 31–40).
   - Add one entry: `self::$module.'.event_name' => [__CLASS__, 'getHandlerName']`.
   - For aliases (e.g., `reactivate` → same handler as `activate`), add a second entry pointing to the same method.
   - Verify the array key does not already exist before adding.

   ```php
   public static function getHooks()
   {
       return [
           'function.requirements'        => [__CLASS__, 'getRequirements'],
           self::$module.'.settings'      => [__CLASS__, 'getSettings'],
           self::$module.'.activate'      => [__CLASS__, 'getActivate'],
           self::$module.'.reactivate'    => [__CLASS__, 'getActivate'],
           self::$module.'.deactivate'    => [__CLASS__, 'getDeactivate'],
           self::$module.'.deactivate_ip' => [__CLASS__, 'getDeactivate'],
           self::$module.'.change_ip'     => [__CLASS__, 'getChangeIp'],  // ← new
       ];
   }
   ```

3. **Write the handler method** in `src/Plugin.php`, after the last existing handler.
   - Signature: `public static function getHandlerName(GenericEvent $event)`
   - First line inside the guard: `$serviceClass = $event->getSubject();`
   - Use `$serviceClass->getIp()`, `$serviceClass->getId()`, `$serviceClass->getCustid()` to access service data.
   - For error states, set `$event['status'] = 'error'` and `$event['status_text'] = '...'`; for success set `'ok'`.
   - Call `$event->stopPropagation()` last.

   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function getChangeIp(GenericEvent $event)
   {
       if ($event['category'] == get_service_define('KSPLICE')) {
           $serviceClass = $event->getSubject();
           $settings = get_module_settings(self::$module);
           $ksplice = new Ksplice(KSPLICE_API_USERNAME, KSPLICE_API_KEY);
           myadmin_log(self::$module, 'info', 'IP Change - (OLD:'.$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
           $result = $ksplice->editIp($serviceClass->getIp(), $event['newip']);
           if (isset($result['faultcode'])) {
               myadmin_log(self::$module, 'error', 'Ksplice editIp returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
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
   ```

4. **If the handler needs a procedural helper** (e.g., a function in `src/ksplice.inc.php`), load it with `\function_requirements('func_name');` inside the guard before calling it. The function must already be registered in `getRequirements()` via `$loader->add_requirement('func_name', '/../vendor/detain/myadmin-ksplice-licensing/src/ksplice.inc.php')`.

5. **Run tests** to confirm nothing is broken:
   ```bash
   vendor/bin/phpunit tests/ -v
   ```
   Verify `PluginTest` passes. Fix any failures before committing.

## Examples

**User says:** "Add a hook to handle `licenses.suspend` events for Ksplice."

**Actions taken:**
1. Add `self::$module.'.suspend' => [__CLASS__, 'getSuspend']` to the `getHooks()` array in `src/Plugin.php`.
2. Add the method:
```php
public static function getSuspend(GenericEvent $event)
{
    if ($event['category'] == get_service_define('KSPLICE')) {
        $serviceClass = $event->getSubject();
        myadmin_log(self::$module, 'info', 'Ksplice Suspension', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $ksplice = new Ksplice(KSPLICE_API_USERNAME, KSPLICE_API_KEY);
        $uuid = $ksplice->ipToUuid($serviceClass->getIp());
        $ksplice->authorizeMachine($uuid, false);
        $event->stopPropagation();
    }
}
```
3. Run `vendor/bin/phpunit tests/ -v`.

**Result:** `licenses.suspend` events for Ksplice services are now handled and do not propagate to other plugins.

## Common Issues

- **Handler runs but does nothing / propagation continues:** Missing or wrong `get_service_define('KSPLICE')` value. Run `grep -r 'KSPLICE' src/` and confirm the constant name matches what `get_service_define()` returns in the host MyAdmin instance.
- **`Call to undefined function activate_ksplice()`:** `\function_requirements('activate_ksplice')` was not called before the function, or the requirement was not registered in `getRequirements()`. Add `$loader->add_requirement('activate_ksplice', '/../vendor/detain/myadmin-ksplice-licensing/src/ksplice.inc.php');` to `getRequirements()`.
- **PHPUnit error `Class 'Detain\MyAdminKsplice\Ksplice' not found` in tests:** Test bootstrap does not stub global functions. Add the missing stub to `tests/bootstrap.php` following the pattern of existing stubs there.
- **Hook never fires:** The event name string in `getHooks()` must exactly match what `run_event()` dispatches in the host. Confirm spelling with `grep -r "run_event.*licenses" /home/sites/mystage/include/`.
