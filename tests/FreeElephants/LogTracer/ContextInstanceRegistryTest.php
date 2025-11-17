<?php

declare(strict_types=1);

namespace FreeElephants\LogTracer;

use PHPUnit\Framework\TestCase;

class ContextInstanceRegistryTest extends TestCase
{
    public function testGetSentryInstance(): void
    {
        $a = ContextInstanceRegistry::getSentryInstance();

        $this->assertSame($a, ContextInstanceRegistry::getSentryInstance(), 'Registry of Singletons must return same instances every time');
    }

    public function testGetSimpleInstance(): void
    {
        $a = ContextInstanceRegistry::getSimpleInstance();

        $this->assertSame($a, ContextInstanceRegistry::getSimpleInstance(), 'Registry of Singletons must return same instances every time');
    }
}
