<?php

namespace App\Http\Controllers;

use App\Interfaces\HomeownerParserInterface;
use App\Models\Homeowner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UploadController extends Controller
{
    public function __construct(protected HomeownerParserInterface $homeownerInterface) {}

    /**
     * Display the upload form and homeowners list with search
     */
    public function index(Request $request): Response
    {
        $query = Homeowner::query();

        // Handle search
        if ($searchTerm = $request->get('search')) {
            $query->search($searchTerm);
        }

        $homeowners = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('index', [
            'homeowners' => $homeowners,
            'searchTerm' => $searchTerm,
        ]);
    }

    /**
     * Store uploaded CSV file and import homeowners
     */
    public function store(Request $request): Response
    {
        $request->validate([
            'file' => 'required|file|mimes:csv',
        ]);

        // Service handles parsing, creating records, and transactions
        $result = $this->homeownerInterface->importFromCsv($request->file('file'));

        if ($result['success']) {
            // Get all homeowners after import (fresh data)
            $homeowners = Homeowner::latest()->paginate(15);

            return Inertia::render('index', [
                'homeowners' => $homeowners,
                'totalCreated' => $result['count'],
                'success' => "Successfully imported {$result['count']} homeowners.",
                'searchTerm' => null, // Clear any previous search
            ]);
        }

        // Handle error case - get current homeowners to maintain the list
        $homeowners = Homeowner::latest()->paginate(15);

        return Inertia::render('index', [
            'homeowners' => $homeowners,
            'error' => $result['error'] ?? 'An error occurred during import.',
            'searchTerm' => null,
        ]);
    }
}
