<?php

namespace FreeElephants\LogTracer\Sentry;

use Sentry\Tracing\PropagationContext;
use Sentry\Tracing\Span;

class SentryContext
{
    private string $traceId;
    private string $parentId;
    private bool $sampled;

    private function __construct(
        string $traceId,
        string $parentId,
        bool $sampled = false
    ) {
        $this->traceId = $traceId;
        $this->parentId = $parentId;
        $this->sampled = $sampled;
    }

    public static function fromSpan(Span $span): self
    {
        return new self(
            (string) $span->getTraceId(),
            (string) $span->getSpanId(),
            $span->getSampled(),
        );
    }

    public static function fromPropagationContext(PropagationContext $propagationContext): self
    {
        return new self(
            (string) $propagationContext->getTraceId(),
            (string) $propagationContext->getSpanId(),
        );
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function getParentId(): string
    {
        return $this->parentId;
    }

    public function isSampled(): bool
    {
        return $this->sampled;
    }
}
