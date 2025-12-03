<?php
namespace RonyLang\FilamentTinymceEditor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RonyLang\FilamentTinymceEditor\Models\TinymcePermission;

class EnsureTinymcePermission
{
    public function handle(Request $request, Closure $next)
    {
        // $user = Auth::user();
        // if (!$user || !TinymcePermission::where('user_id', $user->id)->exists()) {
        //     abort(403, 'You do not have permission to access this section.');
        // }
        return $next($request);
    }
}
