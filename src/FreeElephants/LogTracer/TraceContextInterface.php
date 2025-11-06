<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer;

use Psr\Http\Message\MessageInterface;

interface TraceContextInterface
{
    public function isInitialized(): bool;

    public function populateFromMessage(MessageInterface $request): string;

    public function traceMessage(MessageInterface $message): MessageInterface;

    public function getTraceId(): string;

    public function populateWithDefaults(): string;
}
