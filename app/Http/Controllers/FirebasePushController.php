<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $data = new Notification([
            'title' => $request->title,
            'body' => $request->body,
            'route' => $request->route,
            'type' => 'common',
        ]);
        $data->save();

        return response()->json(['message' => 'Notification sent to all users successfully'], 200);
    }

    public function getNotifications(Request $request)
    {
        $user = Auth::user();
        $type = $request->input('type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Notification::orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            if ($startDate === $endDate) {
                $query->whereDate('created_at', $startDate);
            } else {
                $query->whereBetween('created_at', [$start, $end]);
            }
        }

        $notifications = $query->get();

        return response()->json([
            'data' => NotificationResource::collection($notifications),
        ]);
    }
}
