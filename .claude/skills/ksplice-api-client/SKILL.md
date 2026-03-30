---
name: ksplice-api-client
description: Adds or modifies methods on src/Ksplice.php following the existing REST request pattern. Use when user says 'add API method', 'call Ksplice endpoint', 'new Uptrack API call', or modifies src/Ksplice.php. Sets $this->url, $this->method, $this->inputs then calls $this->request(). Do NOT use for plugin hook changes (use plugin hook pattern instead) or for RESTClient internals.
---
# ksplice-api-client

## Critical

- **Never** add new properties to the class — reuse `$this->url`, `$this->method`, `$this->inputs`, `$this->response`, `$this->responseRaw`.
- **Always** set `$this->url`, `$this->method`, and (for POST) `$this->inputs` before calling `$this->request()`; these are not reset between calls.
- UUID-based endpoints require the UUID as a path segment, never a query param.
- POST body must be `json_encode([...])` — `RESTClient` passes it as raw body via `setBody()`.
- Use tabs for indentation (project-wide convention per `.scrutinizer.yml`).
- Log with `myadmin_log('licenses', 'info', ..., __LINE__, __FILE__)` whenever the API response represents a state change (authorize/deauthorize). Read-only GET methods do not require logging.

## Instructions

1. **Read `src/Ksplice.php`** to confirm the method name doesn't already exist and to find the insertion point (before the closing `}`).

2. **Determine HTTP method and endpoint path** from the Uptrack API docs (`http://www.ksplice.com/uptrack/api`).

3. **Write the doc block** using the existing format:
   ```php
   /**
    * Ksplice::methodName()
    *
    * @param mixed $uuid
    * @param string $optionalParam
    * @return array
    */
   ```

4. **Implement GET methods** (no request body):
   ```php
   public function getSomething($uuid)
   {
   	$this->url = '/api/1/machine/'.$uuid.'/action';
   	$this->method = 'GET';
   	return $this->request();
   }
   ```
   Verify: `$this->inputs` is **not** set — leave it as-is from its last use.

5. **Implement POST methods** (with request body — always `json_encode`):
   ```php
   public function doSomething($uuid, $param)
   {
   	$this->url = '/api/1/machine/'.$uuid.'/action';
   	$this->method = 'POST';
   	$this->inputs = json_encode(['param_key' => $param]);
   	return $this->request();
   }
   ```

6. **Add `myadmin_log()` for state-changing methods** (after `$this->request()`, before return):
   ```php
   $this->request();
   myadmin_log('licenses', 'info', "ActionName Ksplice ({$uuid}) Response: ".json_encode($this->response), __LINE__, __FILE__);
   return $this->response;
   ```

7. **If the new method is the inverse of an existing one** (e.g., deauthorize → authorize), delegate:
   ```php
   public function undoSomething($uuid)
   {
   	return $this->doSomething($uuid, false);
   }
   ```

8. **Add a reflection-based test** in `tests/KspliceTest.php` verifying the method exists, is public, is non-static, and has the correct parameter names and count (follow existing `testDescribeMachineParameterCount` / `testAuthorizeMachineParameters` patterns).

9. **Run tests**: `composer test` — all tests must pass.

## Examples

**User says:** "Add a method to list available updates for a machine"

**Actions taken:**
- Endpoint is GET `/api/1/machine/{uuid}/updates` (read-only, no body, no logging needed)
- Insert before closing `}` in `src/Ksplice.php`:
  ```php
  /**
   * Ksplice::listUpdates()
   *
   * @param mixed $uuid
   * @return array
   */
  public function listUpdates($uuid)
  {
  	$this->url = '/api/1/machine/'.$uuid.'/updates';
  	$this->method = 'GET';
  	return $this->request();
  }
  ```
- Add to `tests/KspliceTest.php`:
  ```php
  public function testListUpdatesParameterCount(): void
  {
  	$method = $this->reflection->getMethod('listUpdates');
  	$params = $method->getParameters();
  	$this->assertCount(1, $params);
  	$this->assertSame('uuid', $params[0]->getName());
  	$this->assertFalse($method->isStatic());
  	$this->assertTrue($method->isPublic());
  }
  ```
- Also update `testPublicMethodsExist()` to add `'listUpdates'` to `$expectedMethods`.
- Also update `testPropertyCount()` — only needed if a new property was added (it wasn't here).
- Run `composer test` to confirm green.

## Common Issues

- **`Call to undefined function myadmin_log()`** during tests: tests use `ReflectionClass::newInstanceWithoutConstructor()` and stub globals — check `tests/bootstrap.php` defines `myadmin_log` as a stub function.
- **`Class 'RESTClient' not found`**: `RESTClient` is global namespace; reference as `new \RESTClient()` inside namespaced code. The constructor guards this with `class_exists('\\RestClient')`.
- **POST body ignored / empty response**: `RESTClient::createRequest()` calls `$this->req->setBody($arr)` — `$arr` must be a JSON string, not an array. Always use `json_encode([...])` before assigning to `$this->inputs`.
- **`testPropertyCount()` fails after adding a method**: that test counts class properties (12), not methods. Only fails if you added a new property — remove the new property and use the existing public ones instead.
- **Tests fail with wrong parameter name**: PHPUnit reflection checks exact parameter names (`$uuid`, `$groupName`, `$authorize`). Match the parameter name exactly as declared in the method signature.
