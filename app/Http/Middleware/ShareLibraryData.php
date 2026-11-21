<?php

namespace App\Http\Middleware;

use App\Support\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareLibraryData
{
    public function handle(Request $request, Closure $next): Response
    {
        View::share('library', Settings::all());

        return $next($request);
    }
}
