<?php

declare(strict_types=1);

namespace Detain\MyAdminKsplice\Tests;

use PHPUnit\Framework\TestCase;
use Detain\MyAdminKsplice\Ksplice;
use ReflectionClass;

/**
 * Tests for the Ksplice API client class.
 */
class KspliceTest extends TestCase
{
    /**
     * @var ReflectionClass<Ksplice>
     */
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Ksplice::class);
    }

    /**
     * Test that the Ksplice class can be instantiated via reflection.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Ksplice::class));
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
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Test that the class is not an interface.
     */
    public function testClassIsNotInterface(): void
    {
        $this->assertFalse($this->reflection->isInterface());
    }

    /**
     * Test that the constructor requires exactly two parameters.
     */
    public function testConstructorParameterCount(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(2, $constructor->getParameters());
    }

    /**
     * Test that the constructor parameter names are apiUsername and apiKey.
     */
    public function testConstructorParameterNames(): void
    {
        $constructor = $this->reflection->getConstructor();
        $params = $constructor->getParameters();
        $this->assertSame('apiUsername', $params[0]->getName());
        $this->assertSame('apiKey', $params[1]->getName());
    }

    /**
     * Test that apiKey is a private property.
     */
    public function testApiKeyPropertyIsPrivate(): void
    {
        $prop = $this->reflection->getProperty('apiKey');
        $this->assertTrue($prop->isPrivate());
    }

    /**
     * Test that apiUsername is a private property.
     */
    public function testApiUsernamePropertyIsPrivate(): void
    {
        $prop = $this->reflection->getProperty('apiUsername');
        $this->assertTrue($prop->isPrivate());
    }

    /**
     * Test that urlBase is a private property with the correct default.
     */
    public function testUrlBaseDefaultValue(): void
    {
        $prop = $this->reflection->getProperty('urlBase');
        $this->assertTrue($prop->isPrivate());
        $this->assertSame('https://uptrack.api.ksplice.com', $prop->getDefaultValue());
    }

    /**
     * Test that the url property is public with an empty default.
     */
    public function testUrlPropertyIsPublic(): void
    {
        $prop = $this->reflection->getProperty('url');
        $this->assertTrue($prop->isPublic());
        $this->assertSame('', $prop->getDefaultValue());
    }

    /**
     * Test that the method property defaults to GET.
     */
    public function testMethodPropertyDefault(): void
    {
        $prop = $this->reflection->getProperty('method');
        $this->assertTrue($prop->isPublic());
        $this->assertSame('GET', $prop->getDefaultValue());
    }

    /**
     * Test that the headers property defaults to an empty array.
     */
    public function testHeadersPropertyDefault(): void
    {
        $prop = $this->reflection->getProperty('headers');
        $this->assertTrue($prop->isPublic());
        $this->assertSame([], $prop->getDefaultValue());
    }

    /**
     * Test that the inputs property defaults to an empty string.
     */
    public function testInputsPropertyDefault(): void
    {
        $prop = $this->reflection->getProperty('inputs');
        $this->assertTrue($prop->isPublic());
        $this->assertSame('', $prop->getDefaultValue());
    }

    /**
     * Test that the responseRaw property defaults to an empty string.
     */
    public function testResponseRawPropertyDefault(): void
    {
        $prop = $this->reflection->getProperty('responseRaw');
        $this->assertTrue($prop->isPublic());
        $this->assertSame('', $prop->getDefaultValue());
    }

    /**
     * Test that the response property defaults to an empty array.
     */
    public function testResponsePropertyDefault(): void
    {
        $prop = $this->reflection->getProperty('response');
        $this->assertTrue($prop->isPublic());
        $this->assertSame([], $prop->getDefaultValue());
    }

    /**
     * Test that the restClient property is private.
     */
    public function testRestClientPropertyIsPrivate(): void
    {
        $prop = $this->reflection->getProperty('restClient');
        $this->assertTrue($prop->isPrivate());
    }

    /**
     * Test that machinesLoaded defaults to false.
     */
    public function testMachinesLoadedDefault(): void
    {
        $prop = $this->reflection->getProperty('machinesLoaded');
        $this->assertTrue($prop->isPublic());
        $this->assertFalse($prop->getDefaultValue());
    }

    /**
     * Test that the ips property defaults to an empty array.
     */
    public function testIpsPropertyDefault(): void
    {
        $prop = $this->reflection->getProperty('ips');
        $this->assertTrue($prop->isPublic());
        $this->assertSame([], $prop->getDefaultValue());
    }

    /**
     * Test that the hosts property defaults to an empty array.
     */
    public function testHostsPropertyDefault(): void
    {
        $prop = $this->reflection->getProperty('hosts');
        $this->assertTrue($prop->isPublic());
        $this->assertSame([], $prop->getDefaultValue());
    }

    /**
     * Test that the uuids property defaults to an empty array.
     */
    public function testUuidsPropertyDefault(): void
    {
        $prop = $this->reflection->getProperty('uuids');
        $this->assertTrue($prop->isPublic());
        $this->assertSame([], $prop->getDefaultValue());
    }

    /**
     * Test that all expected public methods exist.
     */
    public function testPublicMethodsExist(): void
    {
        $expectedMethods = [
            'request',
            'listMachines',
            'describeMachine',
            'ipToUuid',
            'authorizeMachine',
            'deauthorizeMachine',
            'changeGroup',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $this->reflection->hasMethod($method),
                "Method {$method} should exist"
            );
            $this->assertTrue(
                $this->reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test that request() takes no parameters.
     */
    public function testRequestMethodHasNoParameters(): void
    {
        $method = $this->reflection->getMethod('request');
        $this->assertCount(0, $method->getParameters());
    }

    /**
     * Test that listMachines() takes no parameters.
     */
    public function testListMachinesMethodHasNoParameters(): void
    {
        $method = $this->reflection->getMethod('listMachines');
        $this->assertCount(0, $method->getParameters());
    }

    /**
     * Test that describeMachine() requires exactly one parameter (uuid).
     */
    public function testDescribeMachineParameterCount(): void
    {
        $method = $this->reflection->getMethod('describeMachine');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('uuid', $params[0]->getName());
    }

    /**
     * Test that ipToUuid() requires exactly one parameter (ipAddress).
     */
    public function testIpToUuidParameterCount(): void
    {
        $method = $this->reflection->getMethod('ipToUuid');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('ipAddress', $params[0]->getName());
    }

    /**
     * Test that authorizeMachine() has two parameters with the second defaulting to true.
     */
    public function testAuthorizeMachineParameters(): void
    {
        $method = $this->reflection->getMethod('authorizeMachine');
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('uuid', $params[0]->getName());
        $this->assertSame('authorize', $params[1]->getName());
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertTrue($params[1]->getDefaultValue());
    }

    /**
     * Test that deauthorizeMachine() requires exactly one parameter.
     */
    public function testDeauthorizeMachineParameterCount(): void
    {
        $method = $this->reflection->getMethod('deauthorizeMachine');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('uuid', $params[0]->getName());
    }

    /**
     * Test that changeGroup() has two parameters with groupName defaulting to empty string.
     */
    public function testChangeGroupParameters(): void
    {
        $method = $this->reflection->getMethod('changeGroup');
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('uuid', $params[0]->getName());
        $this->assertSame('groupName', $params[1]->getName());
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertSame('', $params[1]->getDefaultValue());
    }

    /**
     * Test that ipToUuid returns false when IP is not found and machines are already loaded.
     */
    public function testIpToUuidReturnsFalseForUnknownIp(): void
    {
        $instance = $this->reflection->newInstanceWithoutConstructor();
        // Mark machines as loaded so it won't call listMachines()
        $instance->machinesLoaded = true;
        $instance->ips = [];

        $result = $instance->ipToUuid('192.168.1.1');
        $this->assertFalse($result);
    }

    /**
     * Test that ipToUuid returns the correct UUID when IP is found.
     */
    public function testIpToUuidReturnsUuidWhenFound(): void
    {
        $instance = $this->reflection->newInstanceWithoutConstructor();
        $instance->machinesLoaded = true;
        $instance->ips = [
            '10.0.0.1' => ['uuid' => 'abc-123', 'ip' => '10.0.0.1', 'hostname' => 'host1'],
            '10.0.0.2' => ['uuid' => 'def-456', 'ip' => '10.0.0.2', 'hostname' => 'host2'],
        ];

        $this->assertSame('abc-123', $instance->ipToUuid('10.0.0.1'));
        $this->assertSame('def-456', $instance->ipToUuid('10.0.0.2'));
    }

    /**
     * Test that the class has exactly the expected number of properties.
     */
    public function testPropertyCount(): void
    {
        $properties = $this->reflection->getProperties();
        $this->assertCount(12, $properties);
    }

    /**
     * Test that describeMachine is a public non-static method.
     */
    public function testDescribeMachineIsPublicNonStatic(): void
    {
        $method = $this->reflection->getMethod('describeMachine');
        $this->assertFalse($method->isStatic());
        $this->assertTrue($method->isPublic());
    }

    /**
     * Test that authorizeMachine is a public non-static method.
     */
    public function testAuthorizeMachineMethodSignature(): void
    {
        $method = $this->reflection->getMethod('authorizeMachine');
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());
    }

    /**
     * Test that deauthorizeMachine is a non-static method.
     */
    public function testDeauthorizeMachineIsNotStatic(): void
    {
        $method = $this->reflection->getMethod('deauthorizeMachine');
        $this->assertFalse($method->isStatic());
    }
}
