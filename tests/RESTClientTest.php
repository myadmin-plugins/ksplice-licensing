<?php

declare(strict_types=1);

namespace Detain\MyAdminKsplice\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for the RESTClient class structure.
 *
 * RESTClient is a global (non-namespaced) class used for HTTP communication.
 * These tests verify its structural integrity via reflection since instantiation
 * requires external dependencies (PEAR HTTP_Request).
 */
class RESTClientTest extends TestCase
{
    /**
     * @var ReflectionClass<\RESTClient>
     */
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        // RESTClient requires INCLUDE_ROOT and PEAR which may not be available.
        // We test only what we can verify structurally.
        if (!class_exists('RESTClient', false)) {
            $this->markTestSkipped('RESTClient class is not loaded (requires PEAR HTTP_Request).');
        }
        $this->reflection = new ReflectionClass('RESTClient');
    }

    /**
     * Test that RESTClient is in the global namespace.
     */
    public function testClassIsInGlobalNamespace(): void
    {
        $this->assertSame('', $this->reflection->getNamespaceName());
    }

    /**
     * Test that RESTClient is not abstract.
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Test that the expected methods exist.
     */
    public function testExpectedMethodsExist(): void
    {
        $methods = ['__construct', 'createRequest', 'sendRequest', 'getResponse'];
        foreach ($methods as $method) {
            $this->assertTrue(
                $this->reflection->hasMethod($method),
                "Method {$method} should exist"
            );
        }
    }

    /**
     * Test that getResponse is public.
     */
    public function testGetResponseIsPublic(): void
    {
        $method = $this->reflection->getMethod('getResponse');
        $this->assertTrue($method->isPublic());
    }
}
