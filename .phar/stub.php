#!/usr/bin/env php
<?php

Phar::mapPhar('phpstan.phar');

require 'phar://phpstan.phar/bin/phpstan';

__HALT_COMPILER(); ?>
