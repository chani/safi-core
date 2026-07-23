<?php

declare(strict_types=1);

namespace Safi\Core\Tests;

use PHPUnit\Framework\TestCase;
use Safi\Core\Util\ClassFinder;

final class ClassFinderTest extends TestCase
{
    public function testExtractsFullyQualifiedClassNameFromTokens(): void
    {
        $code = <<<'PHP'
<?php
// Comment containing fake class Dummy
namespace App\Services\Foo;

use Psr\Log\LoggerInterface;

final class BarService {
    public function doSomething(): void {}
}
PHP;

        $fqcn = ClassFinder::extractClassName($code);
        $this->assertSame('App\Services\Foo\BarService', $fqcn);
    }
}
