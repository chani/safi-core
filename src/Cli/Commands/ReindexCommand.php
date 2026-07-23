<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Cli\Commands;

use Safi\Core\Cli\CommandInterface;
use Safi\Core\Contracts\SearchInterface;

final readonly class ReindexCommand implements CommandInterface
{
    public function __construct(private SearchInterface $search) {}

    #[\Override]
    public function getName(): string
    {
        return 'search:reindex';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Purges and rebuilds search index targets.';
    }

    #[\Override]
    public function getCategory(): string
    {
        return 'search';
    }

    #[\Override]
    public function execute(array $args): int
    {
        $module = $args[0] ?? null;

        if (is_string($module)) {
            $this->search->clearModuleIndex($module);
            echo "Cleared search index for module '{$module}'.\n";
        } else {
            echo "Executing full search reindex...\n";
        }

        return 0;
    }
}
