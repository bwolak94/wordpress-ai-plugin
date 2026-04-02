<?php

declare(strict_types=1);

namespace WpAiAgent\Acf;

final class AcfVersionDetector
{
    public const FREE_FIELDS = [
        'text', 'textarea', 'number', 'range', 'email', 'url', 'password',
        'image', 'file', 'wysiwyg', 'oembed',
        'select', 'checkbox', 'radio', 'button_group', 'true_false', 'link',
        'post_object', 'page_link', 'taxonomy', 'user',
        'google_map', 'date_picker', 'date_time_picker', 'time_picker', 'color_picker',
        'message', 'accordion', 'tab', 'group',
    ];

    public const PRO_ONLY_FIELDS = [
        'repeater', 'flexible_content', 'gallery', 'clone', 'relationship',
    ];

    public function isActive(): bool
    {
        return function_exists('acf') || class_exists('ACF');
    }

    public function isPro(): bool
    {
        if (defined('ACF_PRO')) {
            return (bool) ACF_PRO;
        }

        if (class_exists('acf_pro')) {
            return true;
        }

        if (function_exists('acf') && method_exists(acf(), 'get_pro')) {
            return true;
        }

        return false;
    }

    public function getVersion(): string
    {
        return defined('ACF_VERSION') ? (string) ACF_VERSION : '0.0.0';
    }

    public function supportsField(string $type): bool
    {
        if (in_array($type, self::FREE_FIELDS, true)) {
            return true;
        }

        if (in_array($type, self::PRO_ONLY_FIELDS, true)) {
            return $this->isPro();
        }

        return false;
    }

    /** @return string[] */
    public function getAvailableFieldTypes(): array
    {
        return $this->isPro()
            ? array_merge(self::FREE_FIELDS, self::PRO_ONLY_FIELDS)
            : self::FREE_FIELDS;
    }

    /**
     * @param array<array{type: string}> $fields
     * @return array<array{type: string}>
     */
    public function filterProOnlyFields(array $fields, ProFieldUpgrader $upgrader): array
    {
        if ($this->isPro()) {
            return $fields;
        }

        return array_map(
            fn(array $field): array => in_array($field['type'] ?? '', self::PRO_ONLY_FIELDS, true)
                ? $upgrader->upgrade($field)
                : $field,
            $fields,
        );
    }

    /**
     * @param string[] $suggestedProFields
     */
    public function getUpgradeNotice(array $suggestedProFields): ?string
    {
        if ($this->isPro() || empty($suggestedProFields)) {
            return null;
        }

        $list = implode(', ', array_unique($suggestedProFields));

        return "ACF Free detected — the following PRO field types were downgraded to Free equivalents: {$list}. "
             . 'Upgrade to ACF PRO for full support of repeaters, flexible content, galleries, and more.';
    }
}
