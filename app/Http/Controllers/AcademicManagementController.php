<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesAcademicManagement;
use Illuminate\View\View;

class AcademicManagementController extends Controller
{
    use AuthorizesAcademicManagement;

    public function index(): View
    {
        $this->ensureAcademicManager();

        return view('academics.index');
    }
}
