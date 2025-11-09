<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer\Sentry;

use FreeElephants\LogTracer\Exception\NotImplementedException;

class SentryTraceProviderVersionFrom4_12 extends AbstractSentryTraceProvider
{
    public function getTranceparentHeader(): string
    {
        // \Sentry\getW3CTraceparent() is deprecated in senty/sentry 4.12
        // see https://github.com/getsentry/sentry-php/pull/1833
        // TODO: build this value here from other parts, handled by sdk?
        throw new NotImplementedException();
    }
}
