<?php

declare(strict_types=1);

namespace Daktela\McpServer\Enum;

enum ActivityAction: string
{
    case OPEN = 'OPEN';
    case WAIT = 'WAIT';
    case POSTPONE = 'POSTPONE';
    case CLOSE = 'CLOSE';
}
