<?php

declare(strict_types=1);

namespace WpAiAgent\Tools\Contracts;

use WpAiAgent\Tools\ToolDefinition;
use WpAiAgent\Tools\ToolResult;

interface ToolInterface
{
    /**
     * Unique snake_case tool name used by Claude to invoke it.
     * Examples: "create_page", "set_acf_field", "upload_media"
     */
    public function getName(): string;

    /**
     * Full description + JSON Schema sent to Claude API.
     * Better description = more accurate tool invocations.
     */
    public function getDefinition(): ToolDefinition;

    /**
     * Execute the tool with params provided by Claude.
     *
     * @param array<string, mixed> $params Validated against JSON Schema
     * @return ToolResult Always returns result — never throws
     */
    public function execute(array $params): ToolResult;
}
