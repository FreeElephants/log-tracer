<?php
declare(strict_types=1);

namespace FreeElephants\LogTracer;

use Psr\Http\Message\MessageInterface;

class SimpleTraceContext implements TraceContextInterface
{

    private string $traceId;
    private string $parentId;
    private bool $isSampled = false;

    public function isInitialized(): bool
    {
        // TODO: Implement isInitialized() method.
    }

    public function populateFromMessage(MessageInterface $request): string
    {
        $incomeValue = $request->getHeaderLine('traceparent');
        if (preg_match(self::W3C_TRACEPARENT_HEADER_REGEX, $incomeValue, $parts)) {
            $this->traceId = $parts['trace_id'];
            $this->parentId = $parts['parent_id'];
            $this->isSampled = $parts['sampled'] === '01';

            return $this->traceId;
        }

        return $this->populateWithDefaults();
    }

    public function traceMessage(MessageInterface $message): MessageInterface
    {
        return $message->withHeader('traceparent', sprintf('00-%s-%s-%s', $this->traceId, $this->parentId, $this->isSampled ? '01' : '00'));
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function populateWithDefaults(): string
    {
        $this->traceId = bin2hex(random_bytes(16));
        $this->parentId = bin2hex(random_bytes(8));

        return $this->traceId;
    }

    public function getParentId(): string
    {
        return $this->parentId;
    }
}
