<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PublicPageController extends Controller
{
    public function home(): View
    {
        return view('public.home');
    }

    public function privacyPolicy(): View
    {
        return view('public.privacy-policy');
    }

    public function terms(): View
    {
        return view('public.terms');
    }

    public function accountDeletion(): View
    {
        return view('public.account-deletion');
    }
}
