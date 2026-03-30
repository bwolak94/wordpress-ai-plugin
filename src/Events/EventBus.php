<?php

declare(strict_types=1);

namespace WpAiAgent\Events;

final class EventBus
{
    /** @var array<string, callable[]> */
    private array $listeners = [];

    public function listen(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function dispatch(object $event): void
    {
        $class = get_class($event);

        foreach ($this->listeners[$class] ?? [] as $listener) {
            $listener($event);
        }

        $basename = strtolower(substr(strrchr($class, '\\') ?: $class, 1));

        // Also hook into WordPress actions for external plugin integration
        do_action('wp_ai_agent_event', $event);
        do_action('wp_ai_agent_' . $basename, $event);
    }
}
