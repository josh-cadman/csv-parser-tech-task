<?php

use App\Models\Homeowner;
use App\Services\LocalHomeownerParserService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = new LocalHomeownerParserService;
    Storage::fake('local');
});

describe('parseHomeownerString', function () {
    it('parses a single person with title, first name and last name', function () {
        $result = $this->service->parseHomeownerString('Mr John Smith');

        expect($result)->toHaveCount(1);
        expect($result[0])->toEqual([
            'title' => 'Mr',
            'first_name' => 'John',
            'initial' => null,
            'last_name' => 'Smith',
        ]);
    });

    it('parses a single person with title, initial and last name', function () {
        $result = $this->service->parseHomeownerString('Dr P Gunn');

        expect($result)->toHaveCount(1);
        expect($result[0])->toEqual([
            'title' => 'Dr',
            'first_name' => null,
            'initial' => 'P',
            'last_name' => 'Gunn',
        ]);
    });

    it('parses a single person with hyphenated last name', function () {
        $result = $this->service->parseHomeownerString('Mrs Faye Hughes-Eastwood');

        expect($result)->toHaveCount(1);
        expect($result[0])->toEqual([
            'title' => 'Mrs',
            'first_name' => 'Faye',
            'initial' => null,
            'last_name' => 'Hughes-Eastwood',
        ]);
    });

    it('parses a single person with initial containing period', function () {
        $result = $this->service->parseHomeownerString('Mr F. Fredrickson');

        expect($result)->toHaveCount(1);
        expect($result[0])->toEqual([
            'title' => 'Mr',
            'first_name' => null,
            'initial' => 'F',
            'last_name' => 'Fredrickson',
        ]);
    });

    it('normalises title variations correctly', function () {
        $variations = [
            'Mister John Doe' => 'Mr',
            'Professor Alex Brogan' => 'Prof',
            'Mistress Jane Smith' => 'Mrs',
        ];

        foreach ($variations as $input => $expectedTitle) {
            $result = $this->service->parseHomeownerString($input);
            expect($result[0]['title'])->toBe($expectedTitle);
        }
    });

    it('parses two complete people with different surnames', function () {
        $result = $this->service->parseHomeownerString('Mr Tom Staff and Mr John Doe');

        expect($result)->toHaveCount(2);
        expect($result[0])->toEqual([
            'title' => 'Mr',
            'first_name' => 'Tom',
            'initial' => null,
            'last_name' => 'Staff',
        ]);
        expect($result[1])->toEqual([
            'title' => 'Mr',
            'first_name' => 'John',
            'initial' => null,
            'last_name' => 'Doe',
        ]);
    });

    it('handles extra whitespace correctly', function () {
        $result = $this->service->parseHomeownerString('  Mr   John    Smith  ');

        expect($result)->toHaveCount(1);
        expect($result[0]['first_name'])->toBe('John');
        expect($result[0]['last_name'])->toBe('Smith');
    });

    it('handles person with only title and surname', function () {
        $result = $this->service->parseHomeownerString('Mr Smith');

        expect($result)->toHaveCount(1);
        expect($result[0])->toEqual([
            'title' => 'Mr',
            'first_name' => null,
            'initial' => null,
            'last_name' => 'Smith',
        ]);
    });
});

describe('CSV file parsing', function () {
    it('parses a CSV file with multiple homeowner strings', function () {
        $csvContent = "Homeowner\n";
        $csvContent .= "Mr John Smith\n";
        $csvContent .= "Dr & Mrs Joe Bloggs\n";
        $csvContent .= "Ms Claire Robbo\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $result = $this->service->importFromCsv($file);

        expect($result['success'])->toBeTrue();
        expect($result['count'])->toBe(4); // Mr John Smith + Dr Joe Bloggs + Mrs Joe Bloggs + Ms Claire Robbo
        expect($result['homeowners'])->toHaveCount(4);
    });

    it('skips empty rows in CSV file', function () {
        $csvContent = "Homeowner\n";
        $csvContent .= "Mr John Smith\n";
        $csvContent .= "\n"; // Empty row
        $csvContent .= "Mrs Jane Doe\n";

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $result = $this->service->importFromCsv($file);

        expect($result['success'])->toBeTrue();
        expect($result['count'])->toBe(2);
    });

    it('handles CSV parsing errors gracefully', function () {
        // Mock a file that doesn't exist
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getPathname')->andReturn('/nonexistent/path.csv');

        $result = $this->service->importFromCsv($file);

        expect($result['success'])->toBeFalse();
        expect($result)->toHaveKey('error');
    });
});

describe('duplicate prevention', function () {
    it('does not create duplicate homeowners', function () {
        // Create an existing homeowner
        Homeowner::factory()->create([
            'title' => 'Mr',
            'first_name' => 'John',
            'initial' => null,
            'last_name' => 'Smith',
        ]);

        $csvContent = "Homeowner\n";
        $csvContent .= "Mr John Smith\n"; // This should be skipped as duplicate
        $csvContent .= "Mrs Jane Doe\n";  // This should be created

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $result = $this->service->importFromCsv($file);

        expect($result['success'])->toBeTrue();
        expect($result['count'])->toBe(1); // Only Mrs Jane Doe should be created
        expect(Homeowner::count())->toBe(2); // Original + new one
    });

    it('considers all fields when checking for duplicates', function () {
        // Create an existing homeowner
        Homeowner::factory()->create([
            'title' => 'Mr',
            'first_name' => 'John',
            'initial' => null,
            'last_name' => 'Smith',
        ]);

        $csvContent = "Homeowner\n";
        $csvContent .= "Dr John Smith\n"; // Different title, should be created

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $result = $this->service->importFromCsv($file);

        expect($result['success'])->toBeTrue();
        expect($result['count'])->toBe(1);
        expect(Homeowner::count())->toBe(2);
    });
});

describe('title validation and normalisation', function () {
    it('recognises all valid titles', function () {
        $validTitles = ['Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Prof', 'Professor', 'Mister', 'Mistress', 'Master'];

        foreach ($validTitles as $title) {
            $result = $this->service->parseHomeownerString("{$title} Smith");
            expect($result)->toHaveCount(1);
            expect($result[0]['title'])->not->toBeNull();
        }
    });

    it('handles titles with periods correctly', function () {
        $result = $this->service->parseHomeownerString('Dr. Smith');

        expect($result)->toHaveCount(1);
        expect($result[0]['title'])->toBe('Dr');
    });

    it('handles case-insensitive titles', function () {
        $variations = ['mr', 'MR', 'Mr', 'mR'];

        foreach ($variations as $title) {
            $result = $this->service->parseHomeownerString("{$title} Smith");
            expect($result[0]['title'])->toBe('Mr');
        }
    });
});

describe('initial validation and normalisation', function () {
    it('recognises single letter initials', function () {
        $result = $this->service->parseHomeownerString('Mr A Smith');

        expect($result[0]['initial'])->toBe('A');
        expect($result[0]['first_name'])->toBeNull();
    });

    it('normalises initials to uppercase without periods', function () {
        $variations = ['a', 'A', 'a.', 'A.'];

        foreach ($variations as $initial) {
            $result = $this->service->parseHomeownerString("Mr {$initial} Smith");
            expect($result[0]['initial'])->toBe('A');
        }
    });

    it('treats multi-letter strings as first names not initials', function () {
        $result = $this->service->parseHomeownerString('Mr Ab Smith');

        expect($result[0]['first_name'])->toBe('Ab');
        expect($result[0]['initial'])->toBeNull();
    });
});

describe('edge cases', function () {
    it('handles multiple spaces between words', function () {
        $result = $this->service->parseHomeownerString('Mr     John     Smith');

        expect($result[0]['first_name'])->toBe('John');
        expect($result[0]['last_name'])->toBe('Smith');
    });

    it('handles compound last names correctly', function () {
        $result = $this->service->parseHomeownerString('Mr John van der Berg');

        expect($result[0]['first_name'])->toBe('John');
        expect($result[0]['last_name'])->toBe('van der Berg');
    });

    it('requires at least a last name to create a valid person', function () {
        $invalidInputs = ['Mr', 'Dr', ''];

        foreach ($invalidInputs as $input) {
            $result = $this->service->parseHomeownerString($input);
            expect($result)->toBeEmpty();
        }
    });

    it('handles conjunction variations correctly', function () {
        $conjunctions = ['and', '&', 'And'];

        foreach ($conjunctions as $conjunction) {
            $result = $this->service->parseHomeownerString("Mr {$conjunction} Mrs Smith");
            expect($result)->toHaveCount(2);
        }
    });
});

describe('integration tests', function () {
    it('successfully imports a complete CSV file and creates homeowner records', function () {
        $csvContent = "Homeowner\n";
        $csvContent .= "Mr John Smith\n";
        $csvContent .= "Dr & Mrs Joe Bloggs\n";
        $csvContent .= "Ms Claire Robbo\n";
        $csvContent .= "Prof Alex Brogan\n";

        $file = UploadedFile::fake()->createWithContent('homeowners.csv', $csvContent);

        expect(Homeowner::count())->toBe(0);

        $result = $this->service->importFromCsv($file);

        expect($result['success'])->toBeTrue();
        expect($result['count'])->toBe(5);
        expect(Homeowner::count())->toBe(5);

        // Check specific records
        expect(Homeowner::where('first_name', 'John')->where('last_name', 'Smith')->exists())->toBeTrue();
        expect(Homeowner::where('title', 'Dr')->where('last_name', 'Bloggs')->exists())->toBeTrue();
        expect(Homeowner::where('title', 'Mrs')->where('last_name', 'Bloggs')->exists())->toBeTrue();
    });

    it('rolls back transaction on database error', function () {
        $service = Mockery::mock(LocalHomeownerParserService::class)->makePartial();
        $service->shouldReceive('createHomeowner')
            ->once()
            ->andThrow(new Exception('Database error'));

        $csvContent = "Homeowner\nMr John Smith\n";
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $result = $service->importFromCsv($file);

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Database error');
        expect(Homeowner::count())->toBe(0);
    });
});
