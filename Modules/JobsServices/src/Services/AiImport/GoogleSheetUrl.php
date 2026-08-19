<?php

declare(strict_types=1);

namespace Modules\JobsServices\Services\AiImport;

/**
 * Rewrites a Google Sheets share link into its CSV export URL (UFARM-2644).
 *
 * Admins paste whatever the browser address bar shows. That URL is an editor
 * link, not data — this turns it into a CSV endpoint, keeping the `gid` when
 * the admin selected a specific tab. A share link without `gid` must use the
 * first-visible-sheet endpoint: `gid=0` is not guaranteed to exist after the
 * spreadsheet's original tab has been deleted.
 */
final class GoogleSheetUrl
{
    private const PATH_PATTERN = '~^/spreadsheets/d/(e/)?([a-zA-Z0-9_-]+)(?:/|$)~';

    /**
     * The tab id, which may sit in the fragment (#gid=) or the query (?gid=).
     *
     * Delimited with `~` rather than `#`: a `#` delimiter would terminate the
     * pattern at the literal `#` in the character class, silently matching
     * nothing and defaulting every import to the first tab.
     */
    private const GID_PATTERN = '~[#?&]gid=([0-9]+)~';

    public static function looksLikeSheet(?string $url): bool
    {
        return self::parse($url) !== null;
    }

    /**
     * Convert a share/editor URL into a CSV export URL, or null when the input
     * is not a Google Sheets link at all.
     *
     * When no tab id is present, Google's visualization endpoint exports the
     * first visible sheet without assuming that the deleted-or-renamed
     * original tab still has id zero.
     */
    public static function toCsvExport(?string $url): ?string
    {
        $sheet = self::parse($url);
        if ($sheet === null) {
            return null;
        }

        $gid = is_string($url) && preg_match(self::GID_PATTERN, $url, $gidMatch) === 1
            ? $gidMatch[1]
            : null;

        if ($sheet['published']) {
            return $gid === null
                ? sprintf('https://docs.google.com/spreadsheets/d/e/%s/pub?output=csv', $sheet['id'])
                : sprintf(
                    'https://docs.google.com/spreadsheets/d/e/%s/pub?gid=%s&single=true&output=csv',
                    $sheet['id'],
                    $gid,
                );
        }

        if ($gid === null) {
            return sprintf(
                'https://docs.google.com/spreadsheets/d/%s/gviz/tq?tqx=out:csv',
                $sheet['id'],
            );
        }

        return sprintf(
            'https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%s',
            $sheet['id'],
            $gid,
        );
    }

    /**
     * @return array{id: string, published: bool}|null
     */
    private static function parse(?string $url): ?array
    {
        if (! is_string($url)) {
            return null;
        }

        $parts = parse_url(trim($url));
        if (! is_array($parts) || strtolower((string) ($parts['host'] ?? '')) !== 'docs.google.com') {
            return null;
        }

        if (preg_match(self::PATH_PATTERN, (string) ($parts['path'] ?? ''), $matches) !== 1) {
            return null;
        }

        return [
            'id' => $matches[2],
            'published' => ($matches[1] ?? '') === 'e/',
        ];
    }
}
