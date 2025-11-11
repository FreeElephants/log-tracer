<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Monolog;

use FreeElephants\LogTracer\TraceContextInterface;
use Monolog\Processor\ProcessorInterface;

class TraceProcessorV2 implements ProcessorInterface
{
    private TraceContextInterface $traceContext;

    public function __construct(
        TraceContextInterface $traceContext
    ) {
        $this->traceContext = $traceContext;
    }

    public function __invoke(array $record)
    {
        if (!$this->traceContext->isInitialized()) {
            $this->traceContext->populateWithDefaults();
        }
        $record['extra']['trace']['id'] = $this->traceContext->getTraceId();
        $record['extra']['trace']['parent'] = $this->traceContext->getParentId();

        return $record;
    }
}
