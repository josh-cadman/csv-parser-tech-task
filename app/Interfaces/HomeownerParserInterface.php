<?php

namespace App\Interfaces;

use Illuminate\Http\UploadedFile;

interface HomeownerParserInterface
{
    // Method to parse through the CSV file and return an array of home owners
    public function parseCsvFile(UploadedFile $file): array;
}
