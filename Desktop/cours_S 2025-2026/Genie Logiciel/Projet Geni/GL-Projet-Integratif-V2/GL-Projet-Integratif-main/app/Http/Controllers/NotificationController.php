<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationController extends Controller
{
    function notificationNonLu(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $notifications = Notification::where('user_id', $user->id)
            ->where('lu', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'les nouvelles notifications',
            'data' => [
                'nombreDeNotificationNonLu' => $notifications->count(),
                'notifications' => $notifications
            ]
        ]);
    }


    function notificationLu(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        $notifications = Notification::where('user_id', $user->id)
            ->where('lu', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'les anciennes notifications',
            'data' => [
                'nombreDeNotificationLu' => $notifications->count(),
                'notifications' => $notifications
            ]
        ]);
    }

    function tousLu(Request $request)
    {
        $user = JWTAuth::user();

        $notificationsIds = $request->input('notifsIds');

        foreach ($notificationsIds as $notificationId) {
            $notification = Notification::where('id', $notificationId)
                ->where('user_id', $user->id)
                ->first();
            if ($notification) {
                $notification->lu = true;
                $notification->save();
            }
        }
        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications non lues ont été marquées comme lues.',
            'data' => [],
        ]);
    }
}
