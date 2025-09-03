<?php

namespace App\Http\Controllers;

use App\Interfaces\HomeownerParserInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UploadController extends Controller
{
    public function __construct(protected HomeownerParserInterface $homeownerInterface) {}

    public function index(): Response
    {
        return Inertia::render('index');
    }

    public function store(Request $request): Response
    {
        $request->validate([
            'file' => 'required|file|mimes:csv',
        ]);

        $parseResults = $this->homeownerInterface->parseCsvFile($request->file('file'));

        return Inertia::render('index', [
            'output' => $parseResults,
        ]);
    }
}
