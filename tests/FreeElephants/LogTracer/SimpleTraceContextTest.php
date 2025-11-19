<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer;

use Nyholm\Psr7\Request;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class SimpleTraceContextTest extends TestCase
{
    public function testPopulateFromMessage(): void
    {
        $context = new SimpleTraceContext();

        $context->populateFromMessage((new ServerRequest('GET', '/foo'))->withHeader('traceparent', '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01'));

        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $context->getTraceId());
        $this->assertSame('00f067aa0ba902b7', $context->getParentId());
    }

    public function testPopulateFromMessageWithBrokenHeaderValue(): void
    {
        $context = new SimpleTraceContext();

        $context->populateFromMessage((new ServerRequest('GET', '/foo'))->withHeader('traceparent', '00-4bf92f3577b34da6a3ce929d0e0e4736-ZZZZ-01'));

        $this->assertNotSame('4bf92f3577b34da6a3ce929d0e0e4736', $context->getTraceId());
        $this->assertNotSame('00f067aa0ba902b7', $context->getParentId());
    }

    public function testPopulateFromMessageWithoutW3CHeader(): void
    {
        $context = new SimpleTraceContext();

        $context->populateFromMessage(new ServerRequest('GET', '/foo'));

        $this->assertNotSame('4bf92f3577b34da6a3ce929d0e0e4736', $context->getTraceId());
        $this->assertNotSame('00f067aa0ba902b7', $context->getParentId());
    }

    public function testPopulateWithDefaults(): void
    {
        $context = new SimpleTraceContext();

        $context->populateWithDefaults();

        $this->assertNotEmpty($context->getTraceId());
        $this->assertNotEmpty($context->getParentId());
    }

    public function testTraceMessage(): void
    {
        $context = new SimpleTraceContext();

        $context->populateWithDefaults();

        $tracedMessage = $context->traceMessage(new Request('POST', '/bar'), false);

        $headerLine = $tracedMessage->getHeaderLine('traceparent');
        $this->assertStringStartsWith('00-' . $context->getTraceId(), $headerLine);
        $this->assertStringEndsWith($context->getParentId() . '-00', $headerLine);
    }

    public function testTraceMessageWithUpdateParent(): void
    {
        $context = new SimpleTraceContext();

        $context->populateWithDefaults();

        $tracedMessage = $context->traceMessage(new Request('POST', '/bar'));

        $headerLine = $tracedMessage->getHeaderLine('traceparent');
        $this->assertStringStartsWith('00-' . $context->getTraceId(), $headerLine);
        $this->assertStringEndsNotWith($context->getParentId(), $headerLine);
        $this->assertStringEndsWith('-01', $headerLine);
    }

    public function testLazyInitializeWithDefault(): void
    {
        $context = new SimpleTraceContext();

        $this->assertFalse($context->isInitialized());

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/i', $context->getTraceId());

        $this->assertTrue($context->isInitialized());
    }

    public function testPopulateWithValues(): void
    {
        $context = new SimpleTraceContext();

        $this->assertFalse($context->isInitialized());

        $context->populateWithValues('4bf92f3577b34da6a3ce929d0e0e4736', '00f067aa0ba902b7');

        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $context->getTraceId());
        $this->assertTrue($context->isInitialized());
    }
}
