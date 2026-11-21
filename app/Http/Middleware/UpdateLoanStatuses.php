<?php

namespace App\Http\Middleware;

use App\Support\LoanStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLoanStatuses
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            LoanStatus::refresh();
        } catch (\Throwable) {
            // Database may not be migrated yet (first-run setup).
        }

        return $next($request);
    }
}
