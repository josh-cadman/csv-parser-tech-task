<?php

namespace App\Services;

use App\Interfaces\HomeownerParserInterface;
use Illuminate\Http\UploadedFile;

class LocalHomeownerParserService implements HomeownerParserInterface
{
    // Array of titles to search for
    private array $titles = [
        'Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Prof', 'Professor',
        'Mister', 'Mistress', 'Master'
    ];

    // Array of different separator between names
    private array $conjunctions = ['and', '&', 'And'];

    /**
     * Parse CSV file and return array of people
     */
    public function parseCsvFile(UploadedFile $file): array
    {
        $people = [];

        // Open the file
        $handle = fopen($file, 'r');

        // Skip header row if exists
        fgetcsv($handle);

        // Loop through the CSV and pass each row into a method to parse the string
        while (($row = fgetcsv($handle)) !== false) {
            if (!empty($row[0])) {
                $parsedPeople = $this->parseHomeownerString(trim($row[0]));

                // Add the parsed home owner to the people array
                $people = array_merge($people, $parsedPeople);
            }
        }

        // Close the file
        fclose($handle);

        // Return an array of the people
        return $people;
    }

    /**
     * Parse a homeowner string and return array of people
     */
    public function parseHomeownerString(string $homeownerString): array
    {
        $people = [];

        // Clean up the string
        $homeownerString = trim($homeownerString);

        // Remove extra spaces
        $homeownerString = preg_replace('/\s+/', ' ', $homeownerString);

        // Check for multiple people using conjunctions
        $multiplePeoplePattern = $this->buildMultiplePeoplePattern();

        if (preg_match($multiplePeoplePattern, $homeownerString, $matches)) {
            $people = $this->parseMultiplePeople($homeownerString);
        } else {
            $people[] = $this->parseSinglePerson($homeownerString);
        }

        // Remove any null results
        return array_filter($people);
    }

    /**
     * Build regex pattern to detect multiple people
     */
    private function buildMultiplePeoplePattern(): string
    {
        $titlePattern = '(' . implode('|', array_map('preg_quote', $this->titles)) . ')';
        $conjunctionPattern = '(' . implode('|', array_map('preg_quote', $this->conjunctions)) . ')';

        // Pattern: Title ... conjunction ... Title OR conjunction ... Title
        return "/{$titlePattern}.*?{$conjunctionPattern}.*?({$titlePattern}|(?:{$conjunctionPattern}\s+(?!.*{$titlePattern})))/i";
    }

    /**
     * Parse string containing multiple people
     */
    private function parseMultiplePeople(string $homeownerString): array
    {
        $people = [];

        // Handle cases like "Mr & Mrs Smith" or "Mr and Mrs Smith"
        foreach ($this->conjunctions as $conjunction) {
            $pattern = "/\b{$conjunction}\b/i";
            if (preg_match($pattern, $homeownerString)) {
                $parts = preg_split($pattern, $homeownerString, -1, PREG_SPLIT_NO_EMPTY);

                if (count($parts) === 2) {
                    $firstPart = trim($parts[0]);
                    $secondPart = trim($parts[1]);

                    // Parse first person
                    $firstPerson = $this->parseSinglePerson($firstPart);
                    if ($firstPerson) {
                        $people[] = $firstPerson;
                    }

                    // Handle second part - might just be a title without last name
                    $secondPerson = $this->parseSecondPerson($secondPart, $firstPerson);
                    if ($secondPerson) {
                        $people[] = $secondPerson;
                    }
                }
                break; // Stop after finding the first conjunction
            }
        }

        // Handle complex cases like "Mr Tom Staff and Mr John Doe"
        if (empty($people)) {
            $people = $this->parseComplexMultiplePeople($homeownerString);
        }

        return $people;
    }

    /**
     * Parse complex cases with multiple full names
     */
    private function parseComplexMultiplePeople(string $homeownerString): array
    {
        $people = [];

        // Split by conjunctions and try to parse each part as a complete person
        foreach ($this->conjunctions as $conjunction) {
            $pattern = "/\b{$conjunction}\b/i";
            if (preg_match($pattern, $homeownerString)) {
                $parts = preg_split($pattern, $homeownerString, -1, PREG_SPLIT_NO_EMPTY);

                foreach ($parts as $part) {
                    $part = trim($part);
                    if (!empty($part)) {
                        $person = $this->parseSinglePerson($part);
                        if ($person) {
                            $people[] = $person;
                        }
                    }
                }
                break;
            }
        }

        return $people;
    }

    /**
     * Parse the second person in a conjunction, inheriting last name if needed
     */
    private function parseSecondPerson(string $secondPart, ?array $firstPerson): ?array
    {
        // If second part is just a title (like "Mrs" in "Mr & Mrs Smith")
        $words = explode(' ', $secondPart);

        if (count($words) === 1 && $this->isTitle($words[0])) {
            // Just a title, inherit last name from first person
            return [
                'title' => $this->normaliseTitle($words[0]),
                'first_name' => null,
                'initial' => null,
                'last_name' => $firstPerson['last_name'] ?? null
            ];
        }

        // Otherwise, parse as a complete person
        return $this->parseSinglePerson($secondPart);
    }

    /**
     * Parse a single person string
     */
    private function parseSinglePerson(string $personString): ?array
    {
        $words = explode(' ', trim($personString));
        $words = array_filter($words); // Remove empty elements
        $words = array_values($words); // Reindex

        if (empty($words)) {
            return null;
        }

        $person = [
            'title' => null,
            'first_name' => null,
            'initial' => null,
            'last_name' => null
        ];

        $wordIndex = 0;

        // Extract title
        if (isset($words[$wordIndex]) && $this->isTitle($words[$wordIndex])) {
            $person['title'] = $this->normaliseTitle($words[$wordIndex]);
            $wordIndex++;
        }

        // Need at least one more word for last name
        if (!isset($words[$wordIndex])) {
            return null;
        }

        // If only one word left, it's the last name
        if (count($words) === $wordIndex + 1) {
            $person['last_name'] = $words[$wordIndex];
            return $person;
        }

        // Check for initial (single letter with or without dot)
        if (isset($words[$wordIndex]) && $this->isInitial($words[$wordIndex])) {
            $person['initial'] = rtrim($words[$wordIndex], '.');
            $wordIndex++;
        } elseif (isset($words[$wordIndex])) {
            // It's a first name
            $person['first_name'] = $words[$wordIndex];
            $wordIndex++;
        }

        // Remaining words form the last name
        if (isset($words[$wordIndex])) {
            $lastNameParts = array_slice($words, $wordIndex);
            $person['last_name'] = implode(' ', $lastNameParts);
        }

        // Must have a last name
        if (empty($person['last_name'])) {
            return null;
        }

        return $person;
    }

    /**
     * Check if a word is a title
     */
    private function isTitle(string $word): bool
    {
        return in_array(ucfirst(strtolower(rtrim($word, '.'))), $this->titles);
    }

    /**
     * Check if a word is an initial
     */
    private function isInitial(string $word): bool
    {
        $word = rtrim($word, '.');
        return strlen($word) === 1 && ctype_alpha($word);
    }

    /**
     * Normalise title format
     */
    private function normaliseTitle(string $title): string
    {
        $title = rtrim($title, '.');
        $normalised = ucfirst(strtolower($title));

        // Handle special cases
        $specialCases = [
            'Mister' => 'Mr',
            'Mistress' => 'Mrs',
            'Professor' => 'Prof'
        ];

        return $specialCases[$normalised] ?? $normalised;
    }
}

// Usage example and test cases
class HomeownerParserTest
{
    public static function runTests(): void
    {
        $parser = new HomeownerParser();

        $testCases = [
            'Mr John Smith',
            'Mrs Jane Smith',
            'Mister John Doe',
            'Mr Bob Lawblaw',
            'Mr and Mrs Smith',
            'Mr Craig Charles',
            'Mr M Mackie',
            'Mrs Jane McMaster',
            'Mr Tom Staff and Mr John Doe',
            'Dr P Gunn',
            'Dr & Mrs Joe Bloggs',
            'Ms Claire Robbo',
            'Prof Alex Brogan',
            'Mrs Faye Hughes-Eastwood',
            'Mr F. Fredrickson'
        ];

        foreach ($testCases as $testCase) {
            echo "Input: '{$testCase}'\n";
            $results = $parser->parseHomeownerString($testCase);

            foreach ($results as $i => $person) {
                echo "Person " . ($i + 1) . ":\n";
                foreach ($person as $key => $value) {
                    echo "  {$key} => " . ($value ?? 'null') . "\n";
                }
            }
            echo "\n";
        }
    }
}