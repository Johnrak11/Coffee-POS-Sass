<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->shop) {
            // If they are not attached to a shop, maybe they are super admin or system user
            // Let them pass, or block depending on strictness. 
            // For now, let's assume if no shop, this middleware shouldn't block (e.g. super admin)
            return $next($request);
        }

        $shop = $user->shop;

        // Super admins transcend time and space (and billing)
        if ($user->is_super_admin) {
            return $next($request);
        }

        if ($shop->subscription_expires_at && now()->greaterThan($shop->subscription_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription expired. Please contact support or renew.'
            ], 402); // Payment Required
        }

        return $next($request);
    }
}
