<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirebasePushController extends Controller
{
    protected $notification;

    public function __construct()
    {
        $this->notification = Firebase::messaging();
    }

    public function setToken(Request $request): JsonResponse
    {
        $token = $request->input('fcm_token');
        $request->user()->update([
            'fcm_token' => $token,
        ]);

        return response()->json([
            'message' => 'Successfully Updated FCM Token',
        ]);
    }

    public function notification(Request $request, $id)
    {
        $FcmToken = User::find($id)->fcm_token;
        $message = CloudMessage::fromArray([
            'token' => $FcmToken,
            'notification' => [
                'title' => $request->title,
                'body' => $request->body,
            ],
        ])->withData([
            'route' => $request->route,
        ]);

        $this->notification->send($message);

        return response()->json(['message' => 'Notification sent successfully', 'data' => $message]);
    }

    public function sendNotificationToAll(Request $request)
    {
        $tokens = User::where('role', 'user')->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();

        if (empty($tokens)) {
            return response()->json(['message' => 'No FCM tokens found'], 404);
        }

        $message = CloudMessage::new()
            ->withNotification([
                'title' => $request->title,
                'body' => $request->body,
            ])
            ->withData([
                'route' => $request->route,
            ]);

        $messaging = app(Messaging::class);
        $messaging->sendMulticast($message, $tokens);

        return response()->json(['message' => 'Notification sent to all users successfully'], 200);
    }
}
