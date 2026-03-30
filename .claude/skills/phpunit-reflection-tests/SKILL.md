---
name: phpunit-reflection-tests
description: Writes PHPUnit 9 tests in `tests/` using ReflectionClass to inspect class structure without invoking constructors. Use when user says 'add test', 'write test for', 'test this class', or adds new methods to `src/`. Tests go in `Detain\MyAdminKsplice\Tests\` namespace. Use `newInstanceWithoutConstructor()` for classes with external deps (e.g. Ksplice, RESTClient). Do NOT instantiate Ksplice directly — it requires live API credentials.
---
# PHPUnit Reflection Tests

## Critical

- **Never** call `new Ksplice(...)` or `new RESTClient(...)` in tests — they require external deps (PEAR HTTP_Request, live API creds). Use `$this->reflection->newInstanceWithoutConstructor()` instead.
- `RESTClient` is global namespace — use `new ReflectionClass('RESTClient')` (string, not `::class`) and guard with `if (!class_exists('RESTClient', false)) { $this->markTestSkipped(...); }`.
- All test files must begin with `declare(strict_types=1);`.
- Run `composer test` to verify — config is `phpunit.xml.dist`, bootstrap is `tests/bootstrap.php`.

## Instructions

1. **Create the test file** (e.g., `tests/KspliceTest.php`). Verify the corresponding source file (e.g., `src/Ksplice.php`) exists before proceeding.

2. **Add the file header** — exact boilerplate:
   ```php
   <?php
   
   declare(strict_types=1);
   
   namespace Detain\MyAdminKsplice\Tests;
   
   use PHPUnit\Framework\TestCase;
   use Detain\MyAdminKsplice\ClassName;
   use ReflectionClass;
   ```

3. **Declare the class and `$reflection` property**:
   ```php
   class ClassNameTest extends TestCase
   {
       /** @var ReflectionClass<ClassName> */
       private ReflectionClass $reflection;
   
       protected function setUp(): void
       {
           $this->reflection = new ReflectionClass(ClassName::class);
       }
   ```

4. **Add structural tests first** (no constructor invocation needed):
   - `testClassExists` — `assertTrue(class_exists(ClassName::class))`
   - `testClassNamespace` — `assertSame('Detain\\MyAdminKsplice', $this->reflection->getNamespaceName())`
   - `testClassIsNotAbstract` — `assertFalse($this->reflection->isAbstract())`
   - Constructor param count/names via `$this->reflection->getConstructor()->getParameters()`
   - Property visibility/defaults via `$this->reflection->getProperty('propName')->isPrivate()` / `->getDefaultValue()`
   - Method existence + visibility via `$this->reflection->hasMethod('name')` and `->getMethod('name')->isPublic()`
   - Method param names/defaults via `->getMethod('name')->getParameters()`

5. **Add behavioral tests using `newInstanceWithoutConstructor()`** when you need to call methods that branch on pre-set state:
   ```php
   $instance = $this->reflection->newInstanceWithoutConstructor();
   $instance->machinesLoaded = true;  // set public props directly
   $instance->ips = ['10.0.0.1' => ['uuid' => 'abc-123']];
   $result = $instance->ipToUuid('10.0.0.1');
   $this->assertSame('abc-123', $result);
   ```
   Verify only public properties are set this way — access private props via `ReflectionProperty::setAccessible(true)` if needed.

6. **For static-only classes** (like `Plugin`), call static methods directly — no instantiation needed:
   ```php
   $hooks = Plugin::getHooks();
   $this->assertIsArray($hooks);
   $this->assertArrayHasKey('licenses.activate', $hooks);
   ```

7. **Run tests** and confirm all pass:
   ```bash
   composer test
   ```

## Examples

**User says:** "Add tests for the `Ksplice` class `ipToUuid` method"

**Actions:**
1. Read `src/Ksplice.php` to confirm `ipToUuid(string $ipAddress)` signature and that `$ips`, `$machinesLoaded` are public.
2. Add to `tests/KspliceTest.php`:
```php
public function testIpToUuidReturnsFalseForUnknownIp(): void
{
    $instance = $this->reflection->newInstanceWithoutConstructor();
    $instance->machinesLoaded = true;
    $instance->ips = [];
    $this->assertFalse($instance->ipToUuid('192.168.1.1'));
}

public function testIpToUuidReturnsUuidWhenFound(): void
{
    $instance = $this->reflection->newInstanceWithoutConstructor();
    $instance->machinesLoaded = true;
    $instance->ips = ['10.0.0.1' => ['uuid' => 'abc-123']];
    $this->assertSame('abc-123', $instance->ipToUuid('10.0.0.1'));
}
```
3. Run `composer test` — both tests pass.

## Common Issues

- **`Cannot instantiate class Ksplice`**: You called `new Ksplice(...)` — switch to `$this->reflection->newInstanceWithoutConstructor()`.
- **`Class RESTClient not found`**: Missing `markTestSkipped` guard. Add `if (!class_exists('RESTClient', false)) { $this->markTestSkipped('RESTClient not loaded'); }` in `setUp()`.
- **`ReflectionProperty::getDefaultValue() throws ReflectionException`**: Property has no declared default. Use `->isDefaultValueAvailable()` to check first.
- **Autoloader not found**: Run `composer install` — bootstrap falls back to manual PSR-4 loader only when vendor is absent.
- **`Call to undefined method`** on `newInstanceWithoutConstructor()` result: The method requires live deps (e.g., `$this->restClient`). Test it structurally via `$this->reflection->getMethod('name')->getParameters()` instead.
