<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AdminPageController extends Controller
{
    public function login(): View
    {
        return view('admin.login');
    }

    public function dashboard(): View
    {
        return view('admin.dashboard');
    }
}
