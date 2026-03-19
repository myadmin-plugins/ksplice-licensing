<?php

declare(strict_types=1);

namespace Detain\MyAdminKsplice\Tests;

use PHPUnit\Framework\TestCase;
use Detain\MyAdminKsplice\Plugin;
use ReflectionClass;

/**
 * Tests for the Plugin event handler and configuration class.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Test that the Plugin class exists and is loadable.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Test that the class resides in the correct namespace.
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('Detain\\MyAdminKsplice', $this->reflection->getNamespaceName());
    }

    /**
     * Test that the class is not abstract.
     */
    public function testClassIsInstantiable(): void
    {
        $this->assertTrue($this->reflection->isInstantiable());
    }

    /**
     * Test that $name is set to the expected value.
     */
    public function testNameStaticProperty(): void
    {
        $this->assertSame('Ksplice Licensing', Plugin::$name);
    }

    /**
     * Test that $description contains expected content.
     */
    public function testDescriptionStaticProperty(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertStringContainsString('Ksplice', Plugin::$description);
    }

    /**
     * Test that $help is a non-empty string.
     */
    public function testHelpStaticProperty(): void
    {
        $this->assertIsString(Plugin::$help);
        $this->assertNotEmpty(Plugin::$help);
    }

    /**
     * Test that $module is set to 'licenses'.
     */
    public function testModuleStaticProperty(): void
    {
        $this->assertSame('licenses', Plugin::$module);
    }

    /**
     * Test that $type is set to 'service'.
     */
    public function testTypeStaticProperty(): void
    {
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Test that all static properties are public.
     */
    public function testStaticPropertiesArePublic(): void
    {
        $staticProps = ['name', 'description', 'help', 'module', 'type'];
        foreach ($staticProps as $propName) {
            $prop = $this->reflection->getProperty($propName);
            $this->assertTrue($prop->isPublic(), "Property \${$propName} should be public");
            $this->assertTrue($prop->isStatic(), "Property \${$propName} should be static");
        }
    }

    /**
     * Test that getHooks returns an array.
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Test that getHooks contains the expected event keys.
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('function.requirements', $hooks);
        $this->assertArrayHasKey('licenses.settings', $hooks);
        $this->assertArrayHasKey('licenses.activate', $hooks);
        $this->assertArrayHasKey('licenses.reactivate', $hooks);
        $this->assertArrayHasKey('licenses.deactivate', $hooks);
        $this->assertArrayHasKey('licenses.deactivate_ip', $hooks);
    }

    /**
     * Test that each hook value is a callable-style array with class and method.
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $handler) {
            $this->assertIsArray($handler, "Handler for {$eventName} should be an array");
            $this->assertCount(2, $handler, "Handler for {$eventName} should have 2 elements");
            $this->assertSame(Plugin::class, $handler[0], "Handler for {$eventName} should reference Plugin class");
            $this->assertIsString($handler[1], "Handler for {$eventName} method name should be a string");
        }
    }

    /**
     * Test that activate and reactivate hooks point to the same handler method.
     */
    public function testActivateAndReactivateUseSameHandler(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame($hooks['licenses.activate'], $hooks['licenses.reactivate']);
    }

    /**
     * Test that deactivate and deactivate_ip hooks point to the same handler method.
     */
    public function testDeactivateAndDeactivateIpUseSameHandler(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame($hooks['licenses.deactivate'], $hooks['licenses.deactivate_ip']);
    }

    /**
     * Test that all hook handler methods exist on the Plugin class.
     */
    public function testAllHookMethodsExist(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $handler) {
            $methodName = $handler[1];
            $this->assertTrue(
                $this->reflection->hasMethod($methodName),
                "Method {$methodName} referenced by hook {$eventName} should exist"
            );
        }
    }

    /**
     * Test that all hook handler methods are static.
     */
    public function testAllHookMethodsAreStatic(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $handler) {
            $methodName = $handler[1];
            $method = $this->reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isStatic(),
                "Method {$methodName} should be static"
            );
        }
    }

    /**
     * Test that getActivate accepts exactly one parameter of type GenericEvent.
     */
    public function testGetActivateSignature(): void
    {
        $method = $this->reflection->getMethod('getActivate');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame('Symfony\\Component\\EventDispatcher\\GenericEvent', $type->getName());
    }

    /**
     * Test that getDeactivate accepts exactly one GenericEvent parameter.
     */
    public function testGetDeactivateSignature(): void
    {
        $method = $this->reflection->getMethod('getDeactivate');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame('Symfony\\Component\\EventDispatcher\\GenericEvent', $type->getName());
    }

    /**
     * Test that getChangeIp accepts exactly one GenericEvent parameter.
     */
    public function testGetChangeIpSignature(): void
    {
        $method = $this->reflection->getMethod('getChangeIp');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame('Symfony\\Component\\EventDispatcher\\GenericEvent', $type->getName());
    }

    /**
     * Test that getMenu accepts exactly one GenericEvent parameter.
     */
    public function testGetMenuSignature(): void
    {
        $method = $this->reflection->getMethod('getMenu');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame('Symfony\\Component\\EventDispatcher\\GenericEvent', $type->getName());
    }

    /**
     * Test that getRequirements accepts exactly one GenericEvent parameter.
     */
    public function testGetRequirementsSignature(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame('Symfony\\Component\\EventDispatcher\\GenericEvent', $type->getName());
    }

    /**
     * Test that getSettings accepts exactly one GenericEvent parameter.
     */
    public function testGetSettingsSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame('Symfony\\Component\\EventDispatcher\\GenericEvent', $type->getName());
    }

    /**
     * Test that getHooks is a static method.
     */
    public function testGetHooksIsStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that getHooks takes no parameters.
     */
    public function testGetHooksHasNoParameters(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertCount(0, $method->getParameters());
    }

    /**
     * Test that the constructor takes no parameters.
     */
    public function testConstructorHasNoParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Test that Plugin can be instantiated without errors.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test the number of public static methods on the class.
     */
    public function testExpectedPublicMethodCount(): void
    {
        $methods = $this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $ownMethods = array_filter($methods, function ($m) {
            return $m->getDeclaringClass()->getName() === Plugin::class;
        });
        // __construct, getHooks, getActivate, getDeactivate, getChangeIp, getMenu, getRequirements, getSettings
        $this->assertCount(8, $ownMethods);
    }

    /**
     * Test that hook keys use the module property value as prefix.
     */
    public function testHookKeysUseModulePrefix(): void
    {
        $hooks = Plugin::getHooks();
        $modulePrefix = Plugin::$module . '.';
        $modulePrefixedKeys = array_filter(
            array_keys($hooks),
            fn($key) => str_starts_with($key, $modulePrefix)
        );
        // licenses.settings, licenses.activate, licenses.reactivate, licenses.deactivate, licenses.deactivate_ip
        $this->assertCount(5, $modulePrefixedKeys);
    }
}
