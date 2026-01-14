<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * トップページを表示
     */
    public function index(): View
    {
        return view('home');
    }
}
