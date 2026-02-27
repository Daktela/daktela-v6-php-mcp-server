<?php

declare(strict_types=1);

namespace Daktela\McpServer\Enum;

enum ActivityType: string
{
    case CALL = 'CALL';
    case EMAIL = 'EMAIL';
    case CHAT = 'CHAT';
    case SMS = 'SMS';
    case FBM = 'FBM';
    case IGDM = 'IGDM';
    case WAP = 'WAP';
    case VBR = 'VBR';
    case CUSTOM = 'CUSTOM';
}
