# Homeowner Parser

This application parses CSV files containing homeowner data and stores unique results in the database. It intelligently handles various name formats including single homeowners, couples, and different title variations.

## Features

- **Smart parsing**: Detects single and multiple people per CSV row
- **Title handling**: Supports various titles (Mr, Mrs, Ms, Dr, Prof, etc.) with normalisation
- **Duplicate prevention**: Avoids storing duplicate homeowner records
- **Conjunction detection**: Handles "and", "&" separators between multiple people
- **Name inheritance**: Automatically assigns surnames to titles-only entries (e.g., "Mr & Mrs Smith")
- **Validation**: Comprehensive input validation and error handling
- **Transaction safety**: Database rollback on errors

## Technology Stack

- [Laravel 12](https://laravel.com/docs/12.x)
- [Inertia.js](https://inertiajs.com/)
- [Vue 3](https://vuejs.org/)
- [Tailwind CSS](https://tailwindcss.com/)
- [Pest PHP](https://pestphp.com/) - Testing framework
- [Vite](https://vite.dev/) - Build tool

## Prerequisites

- PHP 8.2+ installed
- Composer installed
- Node.js and npm installed
- SQLite (for database)

## Installation & Setup

1. **Clone the repository**

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   ```

## Testing

### Run all tests
```bash
./vendor/bin/pest
```

## Manual Testing

1. Visit `http://localhost:8000` in your browser
2. Upload the provided `example.csv` file using the upload form
3. Verify that the parsed homeowner data displays correctly below the form

### Test Cases in example.csv

The example file includes various parsing scenarios:
- **Single homeowners**: `Mr John Smith`, `Dr P Gunn`
- **Couples**: `Mr and Mrs Smith`, `Dr & Mrs Joe Bloggs`
- **Multiple complete names**: `Mr Tom Staff and Mr John Doe`
- **Title variations**: `Mister John Doe` (normalises to `Mr`)
- **Hyphenated surnames**: `Mrs Faye Hughes-Eastwood`
- **Initials**: `Mr M Mackie`, `Mr F. Fredrickson`

## How It Works

### Upload Process
1. User uploads CSV file via the frontend form
2. Laravel validates the file type and processes the upload
3. File is passed to `LocalHomeownerParserService` for parsing

### Parsing Logic
1. **Row Processing**: Each CSV row is cleaned and analysed
2. **Multiple People Detection**: Uses regex patterns and title counting to identify multiple people
3. **Parsing Strategy**:
   - **Single person**: Direct parsing of title, name/initial, surname
   - **Multiple people**: Splits by conjunctions ("and", "&") and parses each part
   - **Surname inheritance**: Handles cases like "Mr & Mrs Smith" where surname is shared

### Data Storage
1. Parsed data is validated and checked for duplicates
2. New homeowner records are created using database transactions
3. Results are returned to the frontend via Inertia.js props

### Error Handling
- Database transactions ensure data consistency
- Comprehensive validation prevents invalid data storage
- Graceful error reporting for failed uploads

## Parser Examples

| Input | Output |
|-------|--------|
| `Mr John Smith` | 1 person: Mr John Smith |
| `Mr and Mrs Smith` | 2 people: Mr Smith, Mrs Smith |
| `Dr & Mrs Joe Bloggs` | 2 people: Dr Bloggs, Mrs Joe Bloggs |
| `Mr Tom Staff and Mr John Doe` | 2 people: Mr Tom Staff, Mr John Doe |
| `Mister John Doe` | 1 person: Mr John Doe (normalised) |

## Architecture

- **Controller**: `UploadController` handles file uploads and responses
- **Service**: `LocalHomeownerParserService` implements parsing logic
- **Model**: `Homeowner` represents homeowner data with factory support
- **Interface**: `HomeownerParserInterface` allows for alternative implementations
- **Frontend**: Vue.js component with drag-and-drop file upload

## Future Enhancements

If given additional development time, the following features would be implemented:

- **Export functionality**: Download parsed results as CSV/Excel
- **Review screen**: Post-upload interface for manual corrections and deletions
- **Batch operations**: Edit multiple homeowners simultaneously
- **Import history**: Track and manage previous uploads
- **Advanced validation**: Custom rules for name formats and titles
- **API endpoints**: RESTful API for external integrations
- **Static analysis**: PHPStan integration for improved code quality
- **Performance optimisation**: Chunked processing for large files
- **Audit logging**: Track all data changes and imports

## Testing Coverage

The application includes comprehensive test coverage:
- **Unit tests**: Individual parser methods and validation logic
- **Integration tests**: Full CSV import workflow
- **Edge cases**: Malformed data, duplicate detection, error handling
- **Factory support**: Realistic test data generation