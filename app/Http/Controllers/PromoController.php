<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromoRequest;
use App\Http\Resources\PromoResource;
use App\Models\Notification;
use App\Models\Promo;
use App\Models\User;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;

class PromoController extends Controller
{
    public function index()
    {
        $data = Promo::all();

        return PromoResource::collection($data);
    }

    public function store(PromoRequest $request)
    {
        $request->validated();

        $promo = new Promo([
            'title' => $request->title,
            'description' => $request->description,
            'discount' => $request->discount,
            'discount_type' => $request->discount_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);
        $promo->save();

        $promo->products()->attach($request->product_id);

        foreach ($promo->products as $product) {
            $product->original_regular_price = $product->regular_price;
            $product->original_large_price = $product->large_price;

            $priceField = $request->price_type === 'regular' ? 'regular_price' : 'large_price';

            $discountedPrice = $product->$priceField - $promo->discount;

            if ($discountedPrice < 0) {
                $discountedPrice = 0;
            }

            $product->$priceField = $discountedPrice;
            $product->save();
        }

        $notificationData = [
            'title' => 'Promo Baru Tersedia',
            'body' => 'Promo baru telah tersedia! Nikmati diskon menarik pada menu tertentu.',
            'route' => 'promo',
        ];

        $notif = $this->notificationToAll($notificationData, $request);

        return response()->json([
            'message' => 'Promo created successfully',
            'promo' => new PromoResource($promo),
            'notification' => $notif,
        ], 201);
    }

    public function notificationToAll(array $notificationData, Request $request)
    {
        $tokens = User::where('role', 'user')->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();

        if (empty($tokens)) {
            return response()->json(['message' => 'No FCM tokens found'], 404);
        }

        $message = CloudMessage::new()
            ->withNotification([
                'title' => $notificationData['title'],
                'body' => $notificationData['body'],
            ])
            ->withData([
                'route' => $notificationData['route'] ?? '',
            ]);

        $messaging = app(Messaging::class);
        $messaging->sendMulticast($message, $tokens);

        $data = new Notification([
            'title' => $notificationData['title'],
            'body' => $notificationData['body'],
            'route' => $notificationData['route'],
            'type' => 'voucher',
        ]);
        $data->save();

        return response()->json(['message' => 'Notification sent to all users successfully'], 200);
    }
}
