<?php

declare(strict_types=1);

namespace WpAiAgent\Agent;

use WpAiAgent\AI\Contracts\AIProviderInterface;
use WpAiAgent\Tools\ToolRegistry;
use WpAiAgent\DTO\Brief;
use WpAiAgent\DTO\AgentResult;
use WpAiAgent\Events\EventBus;
use WpAiAgent\Events\ToolExecuted;
use WpAiAgent\Events\AgentFinished;

final class AgentOrchestrator
{
    private const MAX_ROUNDS = 20;

    private const SYSTEM_PROMPT = <<<PROMPT
You are an agent that builds WordPress pages.
You have access to tools: create_page, set_acf_field, upload_media, set_menu_item, get_acf_schema.

Rules:
- Always create the page first, then set its ACF fields.
- Use get_acf_schema before set_acf_field if you don't know the field keys.
- Set pages to 'draft' status unless the brief explicitly requests 'publish'.
- When all tasks are done, stop calling tools and summarize what was created.
PROMPT;

    public function __construct(
        private readonly AIProviderInterface $ai,
        private readonly ToolRegistry $registry,
        private readonly EventBus $events,
    ) {}

    public function run(Brief $brief): AgentResult
    {
        $messages = $this->buildInitialMessages($brief);
        $log      = [];
        $pages    = [];
        $rounds   = 0;

        while ($rounds < self::MAX_ROUNDS) {
            $rounds++;
            $response   = $this->ai->sendMessage($messages, $this->registry->getDefinitions());
            $stopReason = $response['stop_reason'];
            $content    = $response['content'];

            $messages[] = ['role' => 'assistant', 'content' => $content];

            if ($stopReason === 'end_turn') {
                break;
            }

            $toolUseBlocks = array_filter($content, fn(array $b): bool => $b['type'] === 'tool_use');

            if (empty($toolUseBlocks)) {
                break;
            }

            $toolResults = [];

            foreach ($toolUseBlocks as $block) {
                $result = $this->registry->dispatch($block['name'], $block['input']);

                $this->events->dispatch(new ToolExecuted($block['name'], $block['input'], $result));

                $log[] = "[{$block['name']}] " . $result->message;

                if ($block['name'] === 'create_page' && $result->success) {
                    $pages[] = $result->data;
                }

                $toolResults[] = [
                    'type'        => 'tool_result',
                    'tool_use_id' => $block['id'],
                    'content'     => $result->toJson(),
                ];
            }

            $messages[] = ['role' => 'user', 'content' => $toolResults];
        }

        $agentResult = new AgentResult(
            log: $log,
            rounds: $rounds,
            pages: $pages,
            success: true,
        );

        $this->events->dispatch(new AgentFinished($agentResult));

        return $agentResult;
    }

    private function buildInitialMessages(Brief $brief): array
    {
        $userContent = implode("\n\n", array_filter([
            "## Product Documentation",
            $brief->documentation,
            "## Page Goals",
            $brief->goals,
            $brief->acfGroupKey
                ? "## ACF Schema (group key: {$brief->acfGroupKey})\nUse get_acf_schema tool to inspect this group's fields."
                : null,
            $brief->targetUrl
                ? "## Target URL Context\n{$brief->targetUrl}"
                : null,
        ]));

        return [
            ['role' => 'user', 'content' => self::SYSTEM_PROMPT . "\n\n" . $userContent],
        ];
    }
}
