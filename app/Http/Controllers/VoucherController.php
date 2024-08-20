<?php

namespace App\Http\Controllers;

use App\Http\Requests\VoucherRequest;
use App\Http\Requests\VoucherUpdateRequest;
use App\Http\Resources\VoucherResource;
use App\Models\Cart;
use App\Models\Notification;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;

class VoucherController extends Controller
{
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

    public function index()
    {
        $data = Voucher::all();

        return VoucherResource::collection($data);
    }

    public function activeVouchers()
    {
        $userId = Auth::id();
        $currentDate = now();

        $cart = Cart::where('user_id', $userId)->first();
        $originalPrice = $cart ? $cart->cartItems->sum(function ($cart_item) {
            return $cart_item->quantity * $cart_item->price;
        }) : 0;

        $activeVouchers = Voucher::where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->whereDoesntHave('userVouchers', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('used', true);
            })
            ->get();

        $activeVouchers->each(function ($voucher) use ($originalPrice) {
            $isEligible = $originalPrice >= $voucher->min_transaction;
            $voucher->is_eligible = $isEligible;
            $voucher->save(); // Simpan perubahan ke database
        });

        return response()->json([
            'active_vouchers' => VoucherResource::collection($activeVouchers),
        ], 200);
    }

    public function store(VoucherRequest $request)
    {
        $request->validated();

        $imageName = time().'.'.$request->file('image')->extension();
        $request->image->storeAs('voucher', $imageName, 'public');

        $data = new Voucher([
            'title' => $request->title,
            'description' => $request->description,
            'image' => $imageName,
            'discount' => $request->discount,
            'min_transaction' => $request->min_transaction,
            'max_discount' => $request->max_discount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);
        $data->save();

        $notificationData = [
            'title' => 'Voucher Baru Tersedia',
            'body' => 'Voucher baru telah tersedia! Periksa voucher terbaru dan nikmati diskon menarik.',
            'route' => 'voucher',
        ];

        $notif = $this->notificationToAll($notificationData, $request);

        return response()->json([
            'message' => 'Voucher created successfully.',
            'voucher' => new VoucherResource($data),
            'notification' => $notif,
        ], 201);
    }

    public function show($id)
    {
        $data = Voucher::find($id);
        if (! $data) {
            return $this->resDataNotFound('Voucher');
        }

        return new VoucherResource($data);
    }

    public function update(VoucherUpdateRequest $request, $id)
    {
        $voucher = $request->validated();

        $data = Voucher::find($id);

        if (! $data) {
            return $this->resDataNotFound('Promo');
        }

        $data->fill($voucher);

        if ($request->hasFile('image')) {
            Storage::delete('public/voucher/'.$data->image);

            $imageName = time().'.'.$request->image->extension();
            $request->file('image')->storeAs('voucher', $imageName, 'public');

            $data->image = $imageName;
        }

        $data->save();

        return $this->resUpdatedData($data);
    }

    public function destroy($id)
    {
        $data = Voucher::find($id);

        if (! $data) {
            return $this->resDataNotFound('Voucher');
        }

        Storage::delete('public/voucher/'.$data->image);
        $data->delete();

        return $this->resDataDeleted();
    }
}
