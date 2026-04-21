<?php

namespace App\Service;

class ProfanityFilterService
{
    /**
     * An internal customizable dictionary of bad words.
     * In a bigger app, this could be loaded from an entity or YAML config.
     */
    private array $badWords = [
        'fuck', 'shit', 'bitch', 'asshole', 'cunt', 'dick', 'bastard', 'whore',
        'slut', 'faggot', 'nigger', 'nigga', 'pussy', 'motherfucker', 'cock', 'twat'
    ];

    /**
     * A map for l33t-speak character substitution (making it smarter).
     */
    private array $leetMap = [
        '@' => 'a', '4' => 'a',
        '8' => 'b',
        '(' => 'c', '<' => 'c',
        '3' => 'e',
        '1' => 'i', '!' => 'i', '|' => 'i',
        '0' => 'o', '*' => 'o',
        '$' => 's', '5' => 's',
        '7' => 't', '+' => 't',
        'v' => 'u', '\\/' => 'u',
        '\\\\' => 'w',
        'x' => 'cks',
        'z' => 's'
    ];

    /**
     * Evaluates a string and returns true if clean, false if bad words found.
     */
    public function isClean(string $text): bool
    {
        return count($this->getDetectedBadWords($text)) === 0;
    }

    /**
     * Detects all bad words mathematically and logically.
     * Retruns an array of detected offenses.
     */
    public function getDetectedBadWords(string $text): array
    {
        if (empty(trim($text))) {
            return [];
        }

        $detected = [];
        $normalizedText = $this->normalizeText($text);
        
        // Strategy 1: Check the raw normalized continuous string for substrings 
        // Example: someone writes "s  h  i  t" -> normalized is "shit".
        // Warning: This could cause Scunthorpe problem ("bass" finding "ass"),
        // so we filter strictly or use spaces if isolated words are important.
        $continuousText = str_replace(' ', '', $normalizedText);

        foreach ($this->badWords as $badWord) {
            // First check isolated words to avoid Scunthorpe problems with small bad words (like "ass").
            if (preg_match('/\b' . preg_quote($badWord, '/') . '\b/i', $normalizedText)) {
                $detected[] = $badWord;
                continue;
            }

            // For larger, harder to accidentally-type bad words (length > 3), 
            // check the continuous squished string to catch spacing bypass like "f u c k".
            if (strlen($badWord) > 3 && str_contains($continuousText, $badWord)) {
                $detected[] = $badWord;
                continue;
            }
        }
        
        // Strategy 2: Fuzzy matching for slight typos (e.g., "fukc")
        $words = explode(' ', $normalizedText);
        foreach ($words as $word) {
            if (strlen($word) < 4) {
                continue; // Skip small words for fuzzy distance to avoid false positives
            }
            
            foreach ($this->badWords as $badWord) {
                if (in_array($badWord, $detected)) {
                    continue; // Already found
                }
                
                // If words are roughly the same length, check similarity
                if (abs(strlen($word) - strlen($badWord)) <= 1) {
                    $distance = levenshtein($word, $badWord);
                    // 1 typo allowed for severe offenses
                    if ($distance === 1) {
                        $detected[] = $badWord;
                    }
                }
            }
        }

        return array_unique($detected);
    }

    /**
     * Translates l33t speak to normal alphabet, converts to lower case, and cleans punctuation.
     */
    private function normalizeText(string $text): string
    {
        $text = strtolower($text);

        // Replace l33t chars map
        foreach ($this->leetMap as $leet => $char) {
            $text = str_replace($leet, $char, $text);
        }

        // Remove all non-alphanumeric chars except spaces, to merge symbols out
        // e.g., "f_u-c.k" becomes "f u c k"
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        
        return trim($text);
    }
}
