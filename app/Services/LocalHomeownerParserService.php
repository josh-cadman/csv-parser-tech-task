<?php

namespace App\Services;

use App\Interfaces\HomeownerParserInterface;
use App\Models\Homeowner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocalHomeownerParserService implements HomeownerParserInterface
{
    // Array of titles to search for
    private array $titles = [
        'Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Prof', 'Professor',
        'Mister', 'Mistress', 'Master',
    ];

    // Array of different separator between names
    private array $conjunctions = ['and', '&', 'And'];

    /**
     * Import homeowners from CSV file
     */
    public function importFromCsv(UploadedFile $file): array
    {
        DB::beginTransaction();

        try {
            $parseResults = $this->parseCsvFile($file);
            $homeowners = $this->createHomeowners($parseResults);

            DB::commit();

            return [
                'success' => true,
                'homeowners' => $homeowners,
                'count' => count($homeowners),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create homeowners from parsed data (skip duplicates)
     */
    private function createHomeowners(array $parseResults): array
    {
        $createdHomeowners = [];
        $skippedCount = 0;

        foreach ($parseResults as $parsedData) {
            if (! $this->isDuplicateHomeowner($parsedData)) {
                $createdHomeowners[] = Homeowner::createFromParsedData($parsedData);
            } else {
                $skippedCount++;
            }
        }

        // Log skipped duplicates for debugging
        if ($skippedCount > 0) {
            Log::info("Skipped {$skippedCount} duplicate homeowners during import");
        }

        return $createdHomeowners;
    }

    /**
     * Check if homeowner already exists to prevent duplicates
     */
    private function isDuplicateHomeowner(array $parsedData): bool
    {
        return Homeowner::where('title', $parsedData['title'])
            ->where('first_name', $parsedData['first_name'])
            ->where('initial', $parsedData['initial'])
            ->where('last_name', $parsedData['last_name'])
            ->exists();
    }

    /**
     * Parse CSV file and return array of people
     */
    private function parseCsvFile(UploadedFile $file): array
    {
        $people = [];
        $handle = fopen($file, 'r');

        $this->skipHeaderRow($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (! empty($row[0])) {
                $parsedPeople = $this->parseHomeownerString(trim($row[0]));
                $people = array_merge($people, $parsedPeople);
            }
        }

        fclose($handle);

        return $people;
    }

    /**
     * Skip the header row in CSV file
     */
    private function skipHeaderRow($handle): void
    {
        fgetcsv($handle);
    }

    /**
     * Parse a homeowner string and return array of people
     */
    public function parseHomeownerString(string $homeownerString): array
    {
        $cleanedString = $this->cleanupInputString($homeownerString);

        if ($this->detectsMultiplePeople($cleanedString)) {
            return $this->parseMultiplePeople($cleanedString);
        }

        $singlePerson = $this->parseSinglePerson($cleanedString);

        return $singlePerson ? [$singlePerson] : [];
    }

    /**
     * Clean up and normalise input string
     */
    private function cleanupInputString(string $input): string
    {
        $cleaned = trim($input);

        return preg_replace('/\s+/', ' ', $cleaned); // Remove extra spaces
    }

    /**
     * Detect if string contains multiple people using regex pattern
     */
    private function detectsMultiplePeople(string $homeownerString): bool
    {
        $pattern = $this->getMultiplePeopleRegexPattern();

        return (bool) preg_match($pattern, $homeownerString);
    }

    /**
     * Build regex pattern to detect multiple people
     */
    private function getMultiplePeopleRegexPattern(): string
    {
        $titlePattern = $this->getTitleRegexPattern();
        $conjunctionPattern = $this->getConjunctionRegexPattern();

        return "/{$titlePattern}.*?{$conjunctionPattern}.*?({$titlePattern}|(?:{$conjunctionPattern}\s+(?!.*{$titlePattern})))/i";
    }

    /**
     * Get regex pattern for matching titles
     */
    private function getTitleRegexPattern(): string
    {
        $quotedTitles = array_map('preg_quote', $this->titles);

        return '('.implode('|', $quotedTitles).')';
    }

    /**
     * Get regex pattern for matching conjunctions
     */
    private function getConjunctionRegexPattern(): string
    {
        $quotedConjunctions = array_map('preg_quote', $this->conjunctions);

        return '('.implode('|', $quotedConjunctions).')';
    }

    /**
     * Parse string containing multiple people
     */
    private function parseMultiplePeople(string $homeownerString): array
    {
        $conjunction = $this->findConjunctionInString($homeownerString);

        if (! $conjunction) {
            return [];
        }

        $parts = $this->splitStringByConjunction($homeownerString, $conjunction);

        if (count($parts) === 2) {
            return $this->parseTwoPeopleParts(trim($parts[0]), trim($parts[1]));
        }

        return $this->parseMultipleCompletePeople($parts);
    }

    /**
     * Find which conjunction is used in the string
     */
    private function findConjunctionInString(string $text): ?string
    {
        foreach ($this->conjunctions as $conjunction) {
            $pattern = "/\b".preg_quote($conjunction)."\b/i";
            if (preg_match($pattern, $text)) {
                return $conjunction;
            }
        }

        return null;
    }

    /**
     * Split string by conjunction
     */
    private function splitStringByConjunction(string $text, string $conjunction): array
    {
        $pattern = "/\b".preg_quote($conjunction)."\b/i";

        return preg_split($pattern, $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Parse two people parts (handles cases like "Mr & Mrs Smith")
     */
    private function parseTwoPeopleParts(string $firstPart, string $secondPart): array
    {
        $people = [];

        $firstPerson = $this->parseSinglePerson($firstPart);
        if ($firstPerson) {
            $people[] = $firstPerson;
        }

        $secondPerson = $this->parseSecondPersonWithInheritance($secondPart, $firstPerson);
        if ($secondPerson) {
            $people[] = $secondPerson;
        }

        return array_filter($people);
    }

    /**
     * Parse multiple complete people (each with full names)
     */
    private function parseMultipleCompletePeople(array $parts): array
    {
        $people = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (! empty($part)) {
                $person = $this->parseSinglePerson($part);
                if ($person) {
                    $people[] = $person;
                }
            }
        }

        return array_filter($people);
    }

    /**
     * Parse second person, inheriting surname if only title provided
     */
    private function parseSecondPersonWithInheritance(string $secondPart, ?array $firstPerson): ?array
    {
        if ($this->containsOnlyTitle($secondPart)) {
            return $this->createPersonWithInheritedSurname($secondPart, $firstPerson);
        }

        return $this->parseSinglePerson($secondPart);
    }

    /**
     * Check if string contains only a title (like "Mrs" in "Mr & Mrs Smith")
     */
    private function containsOnlyTitle(string $part): bool
    {
        $words = explode(' ', $part);

        return count($words) === 1 && $this->validateTitle($words[0]);
    }

    /**
     * Create person with inherited surname from first person
     */
    private function createPersonWithInheritedSurname(string $titleOnly, ?array $firstPerson): ?array
    {
        if (! $firstPerson || empty($firstPerson['last_name'])) {
            return null;
        }

        return [
            'title' => $this->normaliseTitle($titleOnly),
            'first_name' => null,
            'initial' => null,
            'last_name' => $firstPerson['last_name'],
        ];
    }

    /**
     * Parse a single person string into components
     */
    private function parseSinglePerson(string $personString): ?array
    {
        $words = $this->extractCleanWordsFromString($personString);

        if (empty($words)) {
            return null;
        }

        $person = $this->initialiseEmptyPersonArray();
        $wordIndex = $this->extractTitleFromWords($words, $person);

        if (! $this->hasRemainingWords($words, $wordIndex)) {
            return null; // Need at least one more word for last name
        }

        if ($this->isOnlyLastNameRemaining($words, $wordIndex)) {
            $person['last_name'] = $words[$wordIndex];

            return $person;
        }

        $wordIndex = $this->extractFirstNameOrInitialFromWords($words, $wordIndex, $person);
        $this->extractSurnameFromRemainingWords($words, $wordIndex, $person);

        return $this->validatePersonHasRequiredFields($person) ? $person : null;
    }

    /**
     * Extract clean words array from person string
     */
    private function extractCleanWordsFromString(string $personString): array
    {
        $words = explode(' ', trim($personString));
        $words = array_filter($words); // Remove empty elements

        return array_values($words); // Reindex
    }

    /**
     * Initialise empty person array with all fields
     */
    private function initialiseEmptyPersonArray(): array
    {
        return [
            'title' => null,
            'first_name' => null,
            'initial' => null,
            'last_name' => null,
        ];
    }

    /**
     * Extract title from words array and return next word index
     */
    private function extractTitleFromWords(array $words, array &$person): int
    {
        if (isset($words[0]) && $this->validateTitle($words[0])) {
            $person['title'] = $this->normaliseTitle($words[0]);

            return 1;
        }

        return 0;
    }

    /**
     * Check if there are remaining words to process
     */
    private function hasRemainingWords(array $words, int $currentIndex): bool
    {
        return isset($words[$currentIndex]);
    }

    /**
     * Check if only one word remains (must be surname)
     */
    private function isOnlyLastNameRemaining(array $words, int $currentIndex): bool
    {
        return count($words) === $currentIndex + 1;
    }

    /**
     * Extract first name or initial from words and return next index
     */
    private function extractFirstNameOrInitialFromWords(array $words, int $wordIndex, array &$person): int
    {
        if (! isset($words[$wordIndex])) {
            return $wordIndex;
        }

        if ($this->validateInitial($words[$wordIndex])) {
            $person['initial'] = $this->normaliseInitial($words[$wordIndex]);

            return $wordIndex + 1;
        }

        $person['first_name'] = $words[$wordIndex];

        return $wordIndex + 1;
    }

    /**
     * Extract surname from remaining words in array
     */
    private function extractSurnameFromRemainingWords(array $words, int $wordIndex, array &$person): void
    {
        if (isset($words[$wordIndex])) {
            $lastNameParts = array_slice($words, $wordIndex);
            $person['last_name'] = implode(' ', $lastNameParts);
        }
    }

    /**
     * Validate that person has required fields (at minimum a surname)
     */
    private function validatePersonHasRequiredFields(array $person): bool
    {
        return ! empty($person['last_name']);
    }

    /**
     * Validate if a word is a recognised title
     */
    private function validateTitle(string $word): bool
    {
        $cleanWord = ucfirst(strtolower(rtrim($word, '.')));

        return in_array($cleanWord, $this->titles);
    }

    /**
     * Validate if a word is a single letter initial
     */
    private function validateInitial(string $word): bool
    {
        $cleanWord = rtrim($word, '.');

        return strlen($cleanWord) === 1 && ctype_alpha($cleanWord);
    }

    /**
     * Normalise title to standard format
     */
    private function normaliseTitle(string $title): string
    {
        $title = rtrim($title, '.');
        $normalised = ucfirst(strtolower($title));

        // Handle special case mappings
        $specialCases = [
            'Mister' => 'Mr',
            'Mistress' => 'Mrs',
            'Professor' => 'Prof',
        ];

        return $specialCases[$normalised] ?? $normalised;
    }

    /**
     * Normalise initial to standard format (uppercase, no period)
     */
    private function normaliseInitial(string $initial): string
    {
        return strtoupper(rtrim($initial, '.'));
    }
}
