<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer;

use Psr\Http\Message\MessageInterface;

interface TraceContextInterface
{
    public const W3C_TRACEPARENT_HEADER_REGEX = '/^[ \\t]*(?<version>[0]{2})?-?(?<trace_id>[0-9a-f]{32})?-?(?<parent_id>[0-9a-f]{16})?-?(?<sampled>[01]{2})?[ \\t]*$/i';

    public function isInitialized(): bool;

    public function populateWithDefaults();

    public function populateFromMessage(MessageInterface $request);

    public function traceMessage(MessageInterface $message, bool $updateParent = true): MessageInterface;

    public function getTraceId(): string;

    public function getParentId(): string;
}
