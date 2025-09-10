<?php

namespace App\Interfaces;

use Illuminate\Http\UploadedFile;

interface HomeownerParserInterface
{
    // Method to parse through the CSV file and return an array of home owners
    public function importFromCsv(UploadedFile $file): array;
}
