<?php
declare(strict_types=1);

/**
 * TF-IDF and Cosine Similarity Engine for ScholarLocal
 * Supports stop-word removal, suffix normalization/stemming, and unigrams + bigrams.
 */

const STOP_WORDS = [
    'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
    'has', 'have', 'in', 'into', 'is', 'it', 'its', 'of', 'on', 'or',
    'that', 'the', 'their', 'this', 'to', 'using', 'was', 'were', 'with',
    'study', 'research', 'system', 'based', 'pup', 'itech', 'philippines',
];

function tfidf_normalize_token(string $token): string
{
    $len = strlen($token);
    if ($len > 5 && str_ends_with($token, 'ing')) {
        return substr($token, 0, -3);
    }
    if ($len > 4 && str_ends_with($token, 'ed')) {
        return substr($token, 0, -2);
    }
    if ($len > 4 && str_ends_with($token, 'es')) {
        return substr($token, 0, -2);
    }
    if ($len > 3 && str_ends_with($token, 's')) {
        return substr($token, 0, -1);
    }
    return $token;
}

/**
 * Tokenize text into normalized unigrams and bigrams.
 * @return string[]
 */
function tfidf_tokenize(string $text, bool $includeBigrams = true): array
{
    static $stopMap = null;
    if ($stopMap === null) {
        $stopMap = array_flip(STOP_WORDS);
    }

    $lower = mb_strtolower($text, 'UTF-8');
    $parts = preg_split('/[^a-z0-9]+/', $lower, -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) {
        return [];
    }

    $unigrams = [];
    foreach ($parts as $part) {
        if (strlen($part) <= 1 || isset($stopMap[$part])) {
            continue;
        }
        $unigrams[] = tfidf_normalize_token($part);
    }

    if (!$includeBigrams || count($unigrams) < 2) {
        return $unigrams;
    }

    $tokens = $unigrams;
    $count = count($unigrams);
    for ($i = 0; $i < $count - 1; $i++) {
        $tokens[] = $unigrams[$i] . '_' . $unigrams[$i + 1];
    }
    return $tokens;
}

/**
 * Build weighted token list for a study record.
 * @return string[]
 */
function tfidf_document_tokens(array $study): array
{
    $keywords = is_array($study['keywords'] ?? null)
        ? implode(' ', $study['keywords'])
        : (string) ($study['keywords'] ?? '');

    $weightedText = implode(' ', [
        $study['title'] ?? '',
        $study['title'] ?? '',
        $study['title'] ?? '',
        $keywords,
        $keywords,
        $study['abstract'] ?? '',
        $study['authors'] ?? '',
        $study['program'] ?? '',
        $study['extracted_text'] ?? '',
    ]);

    return tfidf_tokenize($weightedText, true);
}

/**
 * Compute smoothed IDF map across all token sets.
 * @param array<int, string[]> $tokenSets
 * @return array<string, float>
 */
function tfidf_build_idf(array $tokenSets): array
{
    $docCount = count($tokenSets);
    $df = [];

    foreach ($tokenSets as $tokens) {
        $unique = array_unique($tokens);
        foreach ($unique as $token) {
            $df[$token] = ($df[$token] ?? 0) + 1;
        }
    }

    $idf = [];
    foreach ($df as $token => $freq) {
        $idf[$token] = log(($docCount + 1) / ($freq + 1)) + 1.0;
    }
    return $idf;
}

/**
 * Vectorize a token list into TF-IDF weights.
 * @param string[] $tokens
 * @param array<string, float> $idf
 * @return array<string, float>
 */
function tfidf_vectorize(array $tokens, array $idf): array
{
    $length = max(count($tokens), 1);
    $counts = array_count_values($tokens);
    $vector = [];

    foreach ($counts as $token => $count) {
        if (isset($idf[$token])) {
            $vector[$token] = ($count / $length) * $idf[$token];
        }
    }
    return $vector;
}

/**
 * Cosine similarity between two sparse TF-IDF vectors in [0.0, 1.0].
 * @param array<string, float> $left
 * @param array<string, float> $right
 */
function tfidf_cosine_similarity(array $left, array $right): float
{
    if (empty($left) || empty($right)) {
        return 0.0;
    }

    $dot = 0.0;
    $leftMag = 0.0;
    $rightMag = 0.0;

    foreach ($left as $token => $val) {
        $leftMag += $val * $val;
        if (isset($right[$token])) {
            $dot += $val * $right[$token];
        }
    }

    foreach ($right as $val) {
        $rightMag += $val * $val;
    }

    if ($leftMag <= 0.0 || $rightMag <= 0.0) {
        return 0.0;
    }

    return $dot / (sqrt($leftMag) * sqrt($rightMag));
}

/**
 * Rank studies against a search query using TF-IDF and Cosine Similarity.
 * @param array<int, array<string, mixed>> $studies
 * @return array<int, array{id: int|string, score: float}>
 */
function tfidf_rank_studies(array $studies, string $query): array
{
    $query = trim($query);
    if ($query === '' || empty($studies)) {
        return [];
    }

    $tokenSets = [];
    foreach ($studies as $idx => $study) {
        $tokenSets[$idx] = tfidf_document_tokens($study);
    }

    $idf = tfidf_build_idf($tokenSets);
    $queryTokens = tfidf_tokenize($query, true);
    $queryVector = tfidf_vectorize($queryTokens, $idf);

    $results = [];
    foreach ($studies as $idx => $study) {
        $docVector = tfidf_vectorize($tokenSets[$idx], $idf);
        $score = tfidf_cosine_similarity($queryVector, $docVector);
        if ($score > 0.0) {
            $results[] = [
                'id'    => $study['id'],
                'score' => round($score, 4),
            ];
        }
    }

    usort($results, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
    return $results;
}

/**
 * Find top N related studies for a target study using Cosine Similarity.
 * @param array<int, array<string, mixed>> $studies
 * @return array<int, array{study: array<string, mixed>, score: float}>
 */
function tfidf_related_studies(array $studies, int|string $targetId, int $limit = 3): array
{
    if (count($studies) < 2) {
        return [];
    }

    $tokenSets = [];
    $targetIdx = null;
    foreach ($studies as $idx => $study) {
        $tokenSets[$idx] = tfidf_document_tokens($study);
        if ((string) $study['id'] === (string) $targetId) {
            $targetIdx = $idx;
        }
    }

    if ($targetIdx === null) {
        return [];
    }

    $idf = tfidf_build_idf($tokenSets);
    $targetVector = tfidf_vectorize($tokenSets[$targetIdx], $idf);

    $matches = [];
    foreach ($studies as $idx => $study) {
        if ($idx === $targetIdx) {
            continue;
        }
        $candidateVector = tfidf_vectorize($tokenSets[$idx], $idf);
        $score = tfidf_cosine_similarity($targetVector, $candidateVector);
        if ($score > 0.0) {
            $matches[] = [
                'study' => $study,
                'score' => round($score, 4),
            ];
        }
    }

    usort($matches, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
    return array_slice($matches, 0, $limit);
}
