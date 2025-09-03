<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class UploadController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Upload/index');
    }
}
