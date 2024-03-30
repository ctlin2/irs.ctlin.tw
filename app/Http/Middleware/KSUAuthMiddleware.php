<?php


namespace App\Http\Middleware;
use Log;
use Closure;

class KSUAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $isLogin = false;
//        Log::info('enter KSUAuth middleware');
        if (!session('teacher_login')) {
            return redirect('teacher/login');
        }

        return $next($request);
    }
}
