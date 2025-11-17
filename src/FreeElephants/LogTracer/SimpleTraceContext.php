<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer;

use Psr\Http\Message\MessageInterface;

class SimpleTraceContext extends AbstractTraceContext
{
    private string $traceId;
    private string $parentId;
    private bool $isSampled = false;

    public function populateFromMessage(MessageInterface $request)
    {
        $this->isInitialized = true;

        if ($incomeValue = $request->getHeaderLine('traceparent')) {
            if (preg_match(self::W3C_TRACEPARENT_HEADER_REGEX, $incomeValue, $parts) === 1) {
                $this->traceId = $parts['trace_id'];
                $this->parentId = $parts['parent_id'];
                $this->isSampled = $parts['sampled'] === '01';

                return;
            }
        }

        $this->populateWithDefaults();
    }

    public function traceMessage(MessageInterface $message, bool $updateParent = true): MessageInterface
    {
        return $message->withHeader('traceparent', sprintf('00-%s-%s-%s', $this->traceId, $this->getParentId($updateParent), $this->isSampled ? '01' : '00'));
    }

    public function getTraceId(): string
    {
        if (!$this->isInitialized()) {
            $this->populateWithDefaults();
        }

        return $this->traceId;
    }

    public function populateWithDefaults()
    {
        $this->traceId = bin2hex(random_bytes(16));
        $this->parentId = bin2hex(random_bytes(8));

        $this->isInitialized = true;
    }

    public function getParentId(bool $update = false): string
    {
        if ($update) {
            $this->parentId = bin2hex(random_bytes(8));
            $this->isSampled = !$this->isSampled;
        }

        return $this->parentId;
    }
}
