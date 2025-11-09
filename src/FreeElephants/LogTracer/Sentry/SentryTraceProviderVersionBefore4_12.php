<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

class SentryTraceProviderVersionBefore4_12 extends AbstractSentryTraceProvider
{
    public function getTranceparentHeader(): string
    {
        return \Sentry\getW3CTraceparent();
    }
}
