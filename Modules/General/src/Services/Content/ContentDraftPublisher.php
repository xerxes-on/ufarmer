<?php

declare(strict_types=1);

namespace Modules\General\Services\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\General\Events\ContentPublishedEvent;
use Modules\General\Models\Article;
use Modules\General\Models\ContentDraft;
use Modules\General\Models\Story;
use Modules\General\Rules\NoContentCorruption;

final class ContentDraftPublisher
{
    public function __construct(
        private readonly ContentMediaDownloader $mediaDownloader,
    ) {}

    public function publish(ContentDraft $draft): Model
    {
        if ($draft->status !== ContentDraft::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'status' => 'Only approved drafts can be published.',
            ]);
        }

        if ($draft->isPublished()) {
            throw ValidationException::withMessages([
                'status' => 'Draft is already published.',
            ]);
        }

        return DB::transaction(function () use ($draft): Model {
            $published = match ($draft->content_type) {
                ContentDraft::TYPE_ARTICLE, ContentDraft::TYPE_NEWS => $this->publishArticle($draft),
                ContentDraft::TYPE_STORY, ContentDraft::TYPE_VIDEO => $this->publishStory($draft),
                default => throw ValidationException::withMessages([
                    'content_type' => 'Unsupported content type.',
                ]),
            };

            $draft->forceFill([
                'status' => ContentDraft::STATUS_PUBLISHED,
                'published_type' => $published::class,
                'published_id' => $published->getKey(),
                'published_at' => now(),
            ])->save();

            event(ContentPublishedEvent::fromDraft($draft->refresh(), $published));

            $draft->forceFill([
                'mq_published_at' => now(),
            ])->save();

            return $published;
        });
    }

    private function publishArticle(ContentDraft $draft): Article
    {
        $title = $this->localized($draft->title);
        $preview = $this->localized($draft->preview);
        $body = $this->localized($draft->body);
        $previewImage = (string) ($draft->thumbnail_url ?: $draft->media_url ?: '');

        $this->assertCleanContent(['title' => $title, 'preview' => $preview, 'body' => $body]);

        $article = Article::create([
            'title_uz' => $title['uz'],
            'title_ru' => $title['ru'],
            'title_en' => $title['en'],
            'preview_uz' => $preview['uz'] ?: $this->shorten($body['uz']),
            'preview_ru' => $preview['ru'] ?: $this->shorten($body['ru']),
            'preview_en' => $preview['en'] ?: $this->shorten($body['en']),
            'body_uz' => $body['uz'],
            'body_ru' => $body['ru'],
            'body_en' => $body['en'],
            'attachment' => $draft->media_url,
            'icon' => $previewImage,
            'preview_image' => $previewImage,
            'crop_ids' => $draft->crop_ids,
        ]);

        $tagIds = collect($draft->tag_ids)->filter()->map(fn (mixed $id): int => (int) $id)->all();

        if ($tagIds !== []) {
            $article->tags()->sync($tagIds);
        }

        return $article;
    }

    /**
     * ponytail: publish-time gate for UFARM-2770 (mojibake/token-quality QA before a draft goes live).
     *
     * @param  array<string, array{uz: string, ru: string, en: string}>  $fieldGroups
     */
    private function assertCleanContent(array $fieldGroups): void
    {
        $errors = [];
        $rule = new NoContentCorruption;

        foreach ($fieldGroups as $field => $locales) {
            foreach ($locales as $locale => $value) {
                $key = "{$field}_{$locale}";
                $rule->validate($key, $value, function (string $message) use (&$errors, $key): void {
                    $errors[$key] = $message;
                });
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function publishStory(ContentDraft $draft): Story
    {
        if (! filled($draft->media_url)) {
            throw ValidationException::withMessages([
                'media_url' => 'Story and video drafts require a media URL before publishing.',
            ]);
        }

        $draft = $this->mediaDownloader->download($draft);
        $title = $this->localized($draft->title);

        $this->assertCleanContent(['title' => $title]);

        return Story::create([
            'color' => (string) data_get($draft->source_payload, 'story.color', '#2f855a'),
            'attachment' => $draft->media_url,
            'is_active' => true,
            'pre_attachment' => $draft->thumbnail_url ?: $draft->media_url,
            'button' => data_get($draft->source_payload, 'story.button'),
            'button_text' => data_get($draft->source_payload, 'story.button_text', $title['uz']),
            'button_action' => data_get($draft->source_payload, 'story.button_action', $draft->source_url),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $value
     * @return array{uz: string, ru: string, en: string}
     */
    private function localized(?array $value): array
    {
        $uz = trim((string) ($value['uz'] ?? ''));
        $ru = trim((string) ($value['ru'] ?? ''));
        $en = trim((string) ($value['en'] ?? ''));
        $fallback = $uz ?: $ru ?: $en;

        return [
            'uz' => $uz ?: $fallback,
            'ru' => $ru ?: $fallback,
            'en' => $en ?: $fallback,
        ];
    }

    private function shorten(string $value, int $limit = 200): string
    {
        $plain = trim((string) preg_replace('/\s+/', ' ', strip_tags($value)));

        if (mb_strlen($plain) <= $limit) {
            return $plain;
        }

        return mb_substr($plain, 0, $limit - 3).'...';
    }
}
