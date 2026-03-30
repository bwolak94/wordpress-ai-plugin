<?php

declare(strict_types=1);

namespace WpAiAgent\Agent;

use WpAiAgent\AI\Contracts\AIProviderInterface;
use WpAiAgent\Agent\ConversationHistory;
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
        $history = $this->buildInitialHistory($brief);
        $log     = [];
        $pages   = [];
        $rounds  = 0;

        while ($rounds < self::MAX_ROUNDS) {
            $rounds++;
            $response   = $this->ai->sendMessage($history->toArray(), $this->registry->getDefinitions());
            $stopReason = $response['stop_reason'];
            $content    = $response['content'];

            $history = $history->addAssistantMessage($content);

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

            $history = $history->addUserMessage($toolResults);
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

    private function buildInitialHistory(Brief $brief): ConversationHistory
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

        return ConversationHistory::create()
            ->addUserMessage(self::SYSTEM_PROMPT . "\n\n" . $userContent);
    }
}
