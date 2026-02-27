<?php

declare(strict_types=1);

namespace Daktela\McpServer\Enum;

enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';
}
