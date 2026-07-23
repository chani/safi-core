<?php

/**
 * Safi Microframework - safi-core
 * @author Jean Bruenn
 * @copyright 2026 All Rights Reserved
 * @see https://github.com/chani/safi-core
 */

declare(strict_types=1);

namespace Safi\Core\Util;

final class ClassFinder
{
    /**
     * Extracts the Fully Qualified Class Name (FQCN) using native PHP tokens.
     */
    public static function extractClassName(string $content): ?string
    {
        $tokens = token_get_all($content);
        $namespace = '';
        $className = '';
        $gettingNamespace = false;
        $gettingClass = false;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[0] === T_NAMESPACE) {
                    $gettingNamespace = true;
                } elseif ($token[0] === T_CLASS) {
                    $gettingClass = true;
                } elseif ($gettingNamespace) {
                    if ($token[0] === T_NAME_QUALIFIED || $token[0] === T_STRING) {
                        $namespace .= $token[1];
                    }
                } elseif ($gettingClass && $token[0] === T_STRING) {
                    $className = $token[1];
                    break;
                }
            } elseif ($token === ';') {
                $gettingNamespace = false;
            }
        }

        if ($className === '') {
            return null;
        }

        return $namespace !== '' ? $namespace . '\\' . $className : $className;
    }
}
