<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class StaffOnly {
    public function handle(Request $request, Closure $next) { abort_unless($request->user()?->isStaff(),403); return $next($request); }
}
