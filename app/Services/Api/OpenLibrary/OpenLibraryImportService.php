<?php

namespace App\Services\Api\OpenLibrary;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Imports book metadata from the official Open Library Search API.
 *
 * APIs used:
 *   Search  → https://openlibrary.org/search.json
 *   Covers  → https://covers.openlibrary.org/b/id/{id}-L.jpg
 *             https://covers.openlibrary.org/b/isbn/{isbn}-L.jpg  (fallback)
 *
 * Official docs: https://openlibrary.org/developers/api
 */
class OpenLibraryImportService
{
    private const SEARCH_URL = 'https://openlibrary.org/search.json';
    private const COVER_URL  = 'https://covers.openlibrary.org/b';
    private const PAGE_SIZE  = 50;
    private const USER_AGENT = 'LuminaLibrary/1.0 (library-management-system)';
    private const MAX_RETRIES = 3;

    /**
     * Fields requested from the Search API.
     * Keeping this explicit reduces payload and avoids undocumented fields.
     */
    private const FIELDS = 'key,title,subtitle,author_name,isbn,publisher,'
        . 'first_publish_year,language,number_of_pages_median,subject,cover_i,first_sentence';

    public function __construct(
        private readonly string $adminId    = 'c1b2c3d4-0003-4000-8000-000000000006',
        private readonly string $categoryId = 'e1b2c3d4-0005-4000-8000-000000000003',
    ) {}

    // ── Public API ─────────────────────────────────────────────────────────

    /**
     * Import books for a given subject from the Open Library Search API.
     *
     * @param  string        $subject    Open Library subject tag (e.g. 'programming')
     * @param  int           $limit      Maximum books to import across all pages
     * @param  string        $language   ISO 639-2 language filter ('eng', 'ind', …) — empty = all
     * @param  int           $startPage  Page number to start from (1-based, for resuming)
     * @param  callable|null $onProgress Called after each page with the current stats array
     *
     * @return array{imported:int, skipped:int, errors:list<string>, total_fetched:int}
     */
    public function importBySubject(
        string $subject,
        int $limit = 50,
        string $language = '',
        int $startPage = 1,
        ?callable $onProgress = null,
    ): array {
        $stats = ['imported' => 0, 'skipped' => 0, 'errors' => [], 'total_fetched' => 0];
        $page  = max(1, $startPage);

        while (($stats['imported'] + $stats['skipped']) < $limit) {
            $offset   = ($page - 1) * self::PAGE_SIZE;
            $pageSize = min(self::PAGE_SIZE, $limit - $stats['imported'] - $stats['skipped']);

            [$docs, $fetchError] = $this->fetchPage($subject, $pageSize, $offset, $language, $page);

            if ($fetchError !== null) {
                $stats['errors'][] = $fetchError;
                Log::error("[OpenLibrary] Aborting subject '{$subject}': {$fetchError}");
                break;
            }

            if (empty($docs)) {
                break; // No more results
            }

            foreach ($docs as $doc) {
                if (($stats['imported'] + $stats['skipped']) >= $limit) {
                    break 2;
                }

                [$result, $detail] = $this->importDoc($doc);

                match ($result) {
                    'imported' => $stats['imported']++,
                    'skipped'  => $stats['skipped']++,
                    default    => $stats['errors'][] = $detail,
                };
            }

            $stats['total_fetched'] += count($docs);
            $page++;

            if ($onProgress !== null) {
                ($onProgress)($stats);
            }

            if (count($docs) < self::PAGE_SIZE) {
                break; // Last page
            }

            usleep(1_000_000); // 1 s between pages — polite API consumer
        }

        return $stats;
    }

    // ── Private: HTTP ──────────────────────────────────────────────────────

    /**
     * Fetch one page from the Search API with exponential-backoff retry.
     *
     * @return array{0: list<array>, 1: string|null}  [docs, errorMessage|null]
     */
    private function fetchPage(
        string $subject,
        int $limit,
        int $offset,
        string $language,
        int $page,
    ): array {
        $params = [
            'subject' => $subject,
            'limit'   => $limit,
            'offset'  => $offset,
            'fields'  => self::FIELDS,
        ];

        if ($language !== '') {
            $params['language'] = $language;
        }

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                    ->acceptJson()
                    ->timeout(30)
                    ->get(self::SEARCH_URL, $params);

                if ($response->successful()) {
                    $docs = $response->json('docs', []);
                    return [is_array($docs) ? $docs : [], null];
                }

                $status = $response->status();
                $reason = $response->reason() ?: 'Unknown';
                $errMsg = "Subject: {$subject} | Page: {$page} | Status: {$status} | Reason: {$reason}";

                Log::warning("[OpenLibrary] {$errMsg} (attempt {$attempt}/{" . self::MAX_RETRIES . "})");

                // Retry on rate-limit and server errors
                if (($status === 429 || $status >= 500) && $attempt < self::MAX_RETRIES) {
                    usleep((int) (pow(2, $attempt) * 1_000_000));
                    continue;
                }

                return [[], $errMsg];

            } catch (\Throwable $e) {
                $errMsg = "Subject: {$subject} | Page: {$page} | Exception: {$e->getMessage()}";
                Log::warning("[OpenLibrary] {$errMsg} (attempt {$attempt}/{" . self::MAX_RETRIES . "})");

                if ($attempt < self::MAX_RETRIES) {
                    usleep((int) (pow(2, $attempt) * 1_000_000));
                }
            }
        }

        return [[], "Subject: {$subject} | Page: {$page} | Failed after " . self::MAX_RETRIES . " attempts"];
    }

    // ── Private: Import ────────────────────────────────────────────────────

    /**
     * Map one Search API doc to a books row and insert it.
     *
     * Duplicate detection priority:
     *   1. ISBN-13 match
     *   2. ISBN-10 match
     *   3. Title + Author match (no ISBN)
     *
     * @param  array<string,mixed>  $doc
     * @return array{0: 'imported'|'skipped'|'error', 1: string}  [result, detail]
     */
    private function importDoc(array $doc): array
    {
        try {
            $title = trim((string) ($doc['title'] ?? ''));
            if ($title === '') {
                return ['skipped', 'Empty title'];
            }

            // ── ISBN extraction ──────────────────────────────────────────
            $rawIsbns = (array) ($doc['isbn'] ?? []);
            $isbn13   = $this->pickIsbn($rawIsbns, 13);
            $isbn10   = $this->pickIsbn($rawIsbns, 10);
            $isbn     = $isbn13 ?? $isbn10;

            // ── Duplicate detection (priority: ISBN-13 → ISBN-10 → title+author) ──
            if ($isbn13 && DB::table('books')->where('isbn', $isbn13)->exists()) {
                return ['skipped', "Duplicate ISBN-13: {$isbn13}"];
            }
            if ($isbn10 && DB::table('books')->where('isbn', $isbn10)->exists()) {
                return ['skipped', "Duplicate ISBN-10: {$isbn10}"];
            }

            $authorRaw = array_slice((array) ($doc['author_name'] ?? []), 0, 3);
            $author    = implode(', ', $authorRaw) ?: 'Unknown';

            if (
                !$isbn &&
                DB::table('books')
                    ->where('title', mb_substr($title, 0, 500))
                    ->where('author', mb_substr($author, 0, 300))
                    ->exists()
            ) {
                return ['skipped', "Duplicate title+author: {$title}"];
            }

            // ── Cover URL ────────────────────────────────────────────────
            $coverId   = $doc['cover_i'] ?? null;
            $coverPath = $coverId
                ? self::COVER_URL . "/id/{$coverId}-L.jpg"
                : ($isbn ? self::COVER_URL . "/isbn/{$isbn}-L.jpg" : null);

            // ── Description ──────────────────────────────────────────────
            $firstSentenceRaw = $doc['first_sentence'] ?? null;
            $firstSentence    = is_array($firstSentenceRaw)
                ? (string) ($firstSentenceRaw['value'] ?? '')
                : (string) ($firstSentenceRaw ?? '');
            $subtitle    = trim((string) ($doc['subtitle'] ?? ''));
            $subjectTags = array_slice((array) ($doc['subject'] ?? []), 0, 5);

            $description = $firstSentence ?: ($subjectTags ? implode('; ', $subjectTags) : null);
            if ($subtitle && $description) {
                $description = "{$subtitle} — {$description}";
            } elseif ($subtitle) {
                $description = $subtitle;
            }

            // ── Publisher ────────────────────────────────────────────────
            $publishers = is_array($doc['publisher'] ?? null)
                ? $doc['publisher']
                : [(string) ($doc['publisher'] ?? '')];
            $publisher = trim($publishers[0] ?? '');

            // ── Language ─────────────────────────────────────────────────
            $languages = (array) ($doc['language'] ?? []);
            $langLabel = $this->languageLabel($languages[0] ?? '');

            // ── Insert ───────────────────────────────────────────────────
            DB::table('books')->insert([
                'id'                => (string) Str::uuid(),
                'title'             => mb_substr($title, 0, 500),
                'author'            => mb_substr($author, 0, 300),
                'isbn'              => $isbn,
                'publisher'         => $publisher ? mb_substr($publisher, 0, 200) : null,
                'publish_year'      => ($year = (int) ($doc['first_publish_year'] ?? 0)) > 0 ? $year : null,
                'description'       => $description ? mb_substr($description, 0, 1000) : null,
                'cover_path'        => $coverPath,
                'cover_is_external' => $coverPath !== null,
                'category_id'       => $this->categoryId,
                'language'          => $langLabel,
                'page_count'        => ($pages = (int) ($doc['number_of_pages_median'] ?? 0)) > 0 ? $pages : null,
                'edition'           => null,
                'rating'            => 0,   // NOT NULL DEFAULT 0 — must not be null
                'rating_count'      => 0,
                'is_active'         => true,
                'created_at'        => now(),
                'updated_at'        => now(),
                'deleted_at'        => null,
                'created_by'        => $this->adminId,
                'updated_by'        => $this->adminId,
            ]);

            return ['imported', ''];

        } catch (\Throwable $e) {
            $titleSnip = mb_substr((string) ($doc['title'] ?? '?'), 0, 60);
            $msg       = "Insert failed for '{$titleSnip}': " . $e->getMessage();
            Log::error("[OpenLibrary] {$msg}", [
                'key'   => $doc['key'] ?? null,
                'isbn'  => $doc['isbn'][0] ?? null,
            ]);
            return ['error', $msg];
        }
    }

    // ── Private: helpers ───────────────────────────────────────────────────

    /**
     * Pick the first ISBN of exactly $length digits from a mixed list.
     */
    private function pickIsbn(array $isbns, int $length): ?string
    {
        foreach ($isbns as $raw) {
            $clean = preg_replace('/\D/', '', (string) $raw);
            if (strlen($clean) === $length) {
                return $clean;
            }
        }
        return null;
    }

    private function languageLabel(string $code): string
    {
        return match (strtolower($code)) {
            'eng'        => 'English',
            'ind', 'may' => 'Indonesia',
            'jpn'        => 'Japanese',
            'deu'        => 'German',
            'fra'        => 'French',
            'ara'        => 'Arabic',
            'zho', 'chi' => 'Chinese',
            'kor'        => 'Korean',
            'spa'        => 'Spanish',
            ''           => 'English',
            default      => ucfirst($code),
        };
    }

    // ── Static helpers ─────────────────────────────────────────────────────

    /** @return array<string,string>  subject → category_id */
    public static function subjectCategoryMap(): array
    {
        return [
            'fiction'                 => 'e1b2c3d4-0005-4000-8000-000000000001',
            'science fiction'         => 'e1b2c3d4-0005-4000-8000-000000000001',
            'fantasy'                 => 'e1b2c3d4-0005-4000-8000-000000000001',
            'mystery'                 => 'e1b2c3d4-0005-4000-8000-000000000001',
            'novel'                   => 'e1b2c3d4-0005-4000-8000-000000000001',
            'mathematics'             => 'e1b2c3d4-0005-4000-8000-000000000002',
            'physics'                 => 'e1b2c3d4-0005-4000-8000-000000000002',
            'chemistry'               => 'e1b2c3d4-0005-4000-8000-000000000002',
            'biology'                 => 'e1b2c3d4-0005-4000-8000-000000000002',
            'science'                 => 'e1b2c3d4-0005-4000-8000-000000000002',
            'computer science'        => 'e1b2c3d4-0005-4000-8000-000000000003',
            'networking'              => 'e1b2c3d4-0005-4000-8000-000000000003',
            'database'                => 'e1b2c3d4-0005-4000-8000-000000000003',
            'history'                 => 'e1b2c3d4-0005-4000-8000-000000000004',
            'world history'           => 'e1b2c3d4-0005-4000-8000-000000000004',
            'business'                => 'e1b2c3d4-0005-4000-8000-000000000005',
            'economics'               => 'e1b2c3d4-0005-4000-8000-000000000005',
            'psychology'              => 'e1b2c3d4-0005-4000-8000-000000000006',
            'self-help'               => 'e1b2c3d4-0005-4000-8000-000000000006',
            'religion'                => 'e1b2c3d4-0005-4000-8000-000000000007',
            'philosophy'              => 'e1b2c3d4-0005-4000-8000-000000000007',
            'reference'               => 'e1b2c3d4-0005-4000-8000-000000000008',
            'programming'             => 'e1b2c3d4-0005-4000-8000-000000000009',
            'software engineering'    => 'e1b2c3d4-0005-4000-8000-000000000009',
            'algorithms'              => 'e1b2c3d4-0005-4000-8000-000000000009',
            'artificial intelligence' => 'e1b2c3d4-0005-4000-8000-000000000010',
            'machine learning'        => 'e1b2c3d4-0005-4000-8000-000000000010',
            'biography'               => 'e1b2c3d4-0005-4000-8000-000000000011',
            'autobiography'           => 'e1b2c3d4-0005-4000-8000-000000000011',
            'management'              => 'e1b2c3d4-0005-4000-8000-000000000012',
            'leadership'              => 'e1b2c3d4-0005-4000-8000-000000000012',
            'education'               => 'e1b2c3d4-0005-4000-8000-000000000013',
            'medicine'                => 'e1b2c3d4-0005-4000-8000-000000000014',
            'health'                  => 'e1b2c3d4-0005-4000-8000-000000000014',
            'law'                     => 'e1b2c3d4-0005-4000-8000-000000000015',
            'politics'                => 'e1b2c3d4-0005-4000-8000-000000000015',
            'art'                     => 'e1b2c3d4-0005-4000-8000-000000000020',
            'design'                  => 'e1b2c3d4-0005-4000-8000-000000000020',
            'linguistics'             => 'e1b2c3d4-0005-4000-8000-000000000021',
            'architecture'            => 'e1b2c3d4-0005-4000-8000-000000000025',
            'entrepreneurship'        => 'e1b2c3d4-0005-4000-8000-000000000028',
            'environment'             => 'e1b2c3d4-0005-4000-8000-000000000029',
        ];
    }
}
