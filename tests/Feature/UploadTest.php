<?php

use App\Interfaces\HomeownerParserInterface;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

it('returns the upload inertia component', function () {
    // Check that the index Inertia component is shown and no output prop is sent
    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->component('index')
            ->missing('output')
        );
});

it('uploads a CSV file and returns parsed results', function () {
    // Mock the HomeownerParserInterface so that the service is not called
    $mockParser = Mockery::mock(HomeownerParserInterface::class);

    // Expected results
    $expectedResults = [
        [
            'title' => 'Mr',
            'first_name' => 'John',
            'initial' => 'A',
            'last_name' => 'Doe'
        ],
        [
            'title' => 'Mrs',
            'first_name' => 'Jane',
            'initial' => 'B',
            'last_name' => 'Smith'
        ]
    ];

    // Expect the parseCsvFile method to be called once and return the expected results
    $mockParser->shouldReceive('parseCsvFile')
        ->once()
        ->andReturn($expectedResults);

    // Bind the mock to the container
    $this->app->instance(HomeownerParserInterface::class, $mockParser);

    // Create a fake CSV file
    $csvFile = UploadedFile::fake()->createWithContent(
        'test.csv',
        "title,first_name,initial,last_name\nMr,John,A,Doe\nMrs,Jane,B,Smith"
    );

    // Make the POST request and check that the output prop is passed to the frontend
    $this->post('/', ['file' => $csvFile])
        ->assertInertia(fn (Assert $page) => $page
            ->component('index')
            ->has('output', 2)
            ->where('output.0.title', 'Mr')
            ->where('output.0.first_name', 'John')
            ->where('output.0.initial', 'A')
            ->where('output.0.last_name', 'Doe')
            ->where('output.1.title', 'Mrs')
            ->where('output.1.first_name', 'Jane')
            ->where('output.1.initial', 'B')
            ->where('output.1.last_name', 'Smith')
        );
});

it('validates a CSV file when uploading a file', function () {
    // Create a fake txt file
    $txtFile = UploadedFile::fake()->create('test.txt', 100);

    // Send a post request with the txt file and check an error has been returned for the file
    $this->post('/', ['file' => $txtFile])
        ->assertRedirect('/')
        ->assertSessionHasErrors('file');
});