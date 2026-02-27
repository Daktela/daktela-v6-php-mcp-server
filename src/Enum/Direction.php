<?php

declare(strict_types=1);

namespace Daktela\McpServer\Enum;

enum Direction: string
{
    case IN = 'in';
    case OUT = 'out';
    case INTERNAL = 'internal';
}
