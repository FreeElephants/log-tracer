<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer;

abstract class AbstractTraceContext implements TraceContextInterface
{
    protected bool $isInitialized = false;

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }
}
