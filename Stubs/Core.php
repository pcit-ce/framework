<?php

declare(strict_types=1);

class Attribute
{
    const TARGET_CLASS = 1;

    const TARGET_FUNCTION = 2;

    const TARGET_METHOD = 4;

    const TARGET_PROPERTY = 8;

    const TARGET_CLASS_CONSTANT = 16;

    const TARGET_PARAMETER = 32;

    const TARGET_ALL = 63;

    const IS_REPEATABLE = 64;
}
