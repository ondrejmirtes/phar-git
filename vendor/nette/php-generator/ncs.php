<?php

/**
 * Rules for Nette Coding Standard
 * https://github.com/nette/coding-standard
 */
declare (strict_types=1);
namespace _PHPStan_checksum;

return [
    // constant NULL, FALSE in src/PhpGenerator/Type.php
    'constant_case' => \false,
    'lowercase_static_reference' => \false,
];
