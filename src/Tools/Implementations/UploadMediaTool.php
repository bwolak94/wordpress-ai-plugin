<?php

declare(strict_types=1);

namespace WpAiAgent\Tools\Implementations;

use WpAiAgent\Tools\Contracts\ToolInterface;
use WpAiAgent\Tools\ToolDefinition;
use WpAiAgent\Tools\ToolResult;

final class UploadMediaTool implements ToolInterface
{
    public function getName(): string
    {
        return 'upload_media';
    }

    public function getDefinition(): ToolDefinition
    {
        return ToolDefinition::make(
            name: 'upload_media',
            description: 'Downloads an image from a URL and adds it to the WordPress Media Library. '
                . 'Returns attachment ID for use in set_acf_field or set as featured image.'
        )->withSchema([
            'type' => 'object',
            'properties' => [
                'url'     => ['type' => 'string', 'description' => 'Public URL of the image to download'],
                'title'   => ['type' => 'string', 'description' => 'Attachment title in Media Library'],
                'post_id' => ['type' => 'integer', 'description' => 'Optional: parent post ID to attach to'],
            ],
            'required' => ['url'],
        ]);
    }

    public function execute(array $params): ToolResult
    {
        $this->loadWordPressMediaDependencies();

        $url    = esc_url_raw($params['url']);
        $postId = (int) ($params['post_id'] ?? 0);

        $tmpFile = download_url($url);
        if (is_wp_error($tmpFile)) {
            return ToolResult::error("Download failed: " . $tmpFile->get_error_message());
        }

        $file = [
            'name'     => basename(parse_url($url, PHP_URL_PATH)),
            'tmp_name' => $tmpFile,
        ];

        $attachmentId = media_handle_sideload($file, $postId, $params['title'] ?? '');

        if (is_wp_error($attachmentId)) {
            @unlink($tmpFile);
            return ToolResult::error("Media upload failed: " . $attachmentId->get_error_message());
        }

        return ToolResult::success(
            message: "Media uploaded: attachment #{$attachmentId}",
            data: ['attachment_id' => $attachmentId, 'url' => wp_get_attachment_url($attachmentId)],
        );
    }

    private function loadWordPressMediaDependencies(): void
    {
        if (!defined('ABSPATH')) {
            return;
        }

        $base = ABSPATH . 'wp-admin/includes/';
        foreach (['file.php', 'image.php', 'media.php'] as $file) {
            if (file_exists($base . $file)) {
                require_once $base . $file;
            }
        }
    }
}
