<?php

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}
if (!defined('WP_AI_AGENT_VERSION')) {
    define('WP_AI_AGENT_VERSION', '1.0.0');
}
if (!defined('WP_AI_AGENT_DIR')) {
    define('WP_AI_AGENT_DIR', dirname(__DIR__, 2) . '/');
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        private array $params = [];
        private array $url_params = [];

        public function get_params(): array
        {
            return $this->params;
        }

        public function get_param(string $key): mixed
        {
            return $this->url_params[$key] ?? $this->params[$key] ?? null;
        }

        public function set_param(string $key, mixed $value): void
        {
            $this->params[$key] = $value;
        }

        public function set_url_params(array $params): void
        {
            $this->url_params = $params;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        private mixed $data;
        private int $status;

        public function __construct(mixed $data = null, int $status = 200)
        {
            $this->data = $data;
            $this->status = $status;
        }

        public function get_data(): mixed
        {
            return $this->data;
        }

        public function get_status(): int
        {
            return $this->status;
        }
    }
}
