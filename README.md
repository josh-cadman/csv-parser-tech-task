## Homeowner Parser

This is an application as part of the Street Group Tech Task, for parsing through a CSV file of home owners and returning the results.

This application has been built with:
- [Laravel 12](https://laravel.com/docs/12.x).
- [Inertia JS](https://inertiajs.com/).
- [Vue 3](https://vuejs.org/).
- [Tailwind CSS](https://tailwindcss.com/).
- [Pest PHP](https://pestphp.com/).
- [Vite](https://vite.dev/).

## To test this application

### Prerequisites
- PHP 8.2+ installed
- Composer installed
- Node.js and npm installed

### Setup
1. Clone the repository and navigate to the project directory
2. Install PHP dependencies:
    ```bash
    composer install
    ```
3. Install JavaScript dependencies:
    ```bash
    npm install
    ```
4. Copy the environment file:
    ```bash
    cp .env.example .env
    ```
5. Generate an application key:
    ```bash
    php artisan key:generate
    ```
6. Build the frontend assets:
    ```bash
    npm run build
    ```
### Running Tests
To run the tests:

    ./vendor/bin/pest

### Manual Testing

1. Visit http://localhost in your browser (maybe different depending on how you view laravel applications locally)
2. Upload the provided example.csv file (located in the project root) using the upload form
Verify that the parsed homeowner data is displayed correctly below the upload form
3. The example.csv file contains various test cases including:
    - Single homeowners
    - Multiple people on the same row
    - Different title formats

## How the application works
This application works by allowing the user to upload a CSV file on the frontend. When the user uploads a CSV file, we send a POST request to Laravel with the file. Laravel does validation to make sure that a CSV file has been uploaded, and if so, it will pass the file into a service (LocalHomeownerParserService). Inside the service, the file is read and starts to loop through each row within the file.

For each row, it passes the content into the `parseHomeownerString` method which removes any empty spaces and builds a regex pattern to check for multiple people in the row. It then uses the regex pattern to check if the row contains multiple people.

If it detects multi people on the same row, it will pass the row content to the `parseMultiplePeople` method which parses the first and second person from the string and returns an array of people found.

If it does not detect multiple people, it will pass the content into the `parseSinglePerson` method where it will extract the data from the string and return an array of a homeowner.

After going through each line, it will then run `array_filter` to remove any empty arrays. It will then return the array of homeowners to the UploadController, where it is passed into the Inertia component using a prop called `output`.

## What I would implement if I had longer on this task

If I had longer on this task, I would make a few adjustments. These contain:
- Error handling for malformed CSV files
- Export functionality to download the parsed results
- Unit tests for the regex patterns and edge cases
- Adding a table in the database to create OR update home owners
- Adding a screen after upload to review all home owners (with the ability to make manual adjustments to names or removing home owners)
- Adding PHPStan for static code analysis to catch potential bugs and improve code quality