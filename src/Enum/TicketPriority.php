<?php

declare(strict_types=1);

namespace Daktela\McpServer\Enum;

enum TicketPriority: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
}
