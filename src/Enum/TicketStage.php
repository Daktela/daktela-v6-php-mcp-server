<?php

declare(strict_types=1);

namespace Daktela\McpServer\Enum;

enum TicketStage: string
{
    case OPEN = 'OPEN';
    case WAIT = 'WAIT';
    case CLOSE = 'CLOSE';
    case ARCHIVE = 'ARCHIVE';
}
