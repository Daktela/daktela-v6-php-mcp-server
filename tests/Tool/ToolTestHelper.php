<?php

declare(strict_types=1);

namespace Daktela\McpServer\Tests\Tool;

use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;

trait ToolTestHelper
{
    private static function resultText(CallToolResult $result): string
    {
        $parts = [];
        foreach ($result->content as $content) {
            if ($content instanceof TextContent) {
                $parts[] = $content->text;
            }
        }

        return implode("\n", $parts);
    }
}
