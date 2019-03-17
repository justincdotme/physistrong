<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppController extends Controller
{

    /**
     * Display the app homepage.
     * This includes a browser based API consumer.
     */
    public function index()
    {
        return view('templates.app', [
            'baseUrl' => config('app.api_url'),
            'baseDomain' => config('app.api_base_url'),
            'baseProtocol' => config('app.protocol'),
            'basePort' => isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 443,
        ]);
    }
}
