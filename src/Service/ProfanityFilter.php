<?php

namespace App\Service;

class ProfanityFilter
{
    private array $profanityList;

    public function __construct()
    {
        // Basic list of words to filter - in production, this should be loaded from a configuration file
        $this->profanityList = [
            'badword',
            'profanity',
            // Add more words as needed
        ];
    }

    public function hasProfanity(string $text): bool
    {
        $text = strtolower($text);
        foreach ($this->profanityList as $word) {
            if (str_contains($text, strtolower($word))) {
                return true;
            }
        }
        return false;
    }

    public function filter(string $text): string
    {
        $text = strtolower($text);
        foreach ($this->profanityList as $word) {
            $replacement = str_repeat('*', strlen($word));
            $text = str_ireplace($word, $replacement, $text);
        }
        return $text;
    }
}