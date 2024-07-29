<?php

namespace App\Http\Controllers;

use App\Http\Requests\VoucherRequest;
use App\Http\Resources\VoucherResource;
use App\Models\Voucher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VoucherController extends Controller
{
    public function index()
    {
        $data = Voucher::all();

        return VoucherResource::collection($data);
    }

    public function activeVouchers()
    {
        $userId = Auth::id();
        $currentDate = now();

        $activeVouchers = Voucher::where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->whereDoesntHave('userVouchers', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('used', true);
            })
            ->get();

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
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);
        $data->save();

        return new VoucherResource($data);
    }

    public function show($id)
    {
        $data = Voucher::find($id);
        if (! $data) {
            return $this->resDataNotFound('Voucher');
        }

        return new VoucherResource($data);
    }

    public function update(VoucherRequest $request, $id)
    {
        $request->validated();

        $data = Voucher::find($id);

        if (! $data) {
            return $this->resDataNotFound('Promo');
        }

        if ($request->hasFile('image')) {
            Storage::delete('public/voucher/'.$data->image);

            $imageName = time().'.'.$request->image->extension();
            $request->file('image')->storeAs('voucher', $imageName, 'public');

            $data->update([
                'title' => $request->title,
                'description' => $request->description,
                'image' => $imageName,
                'discount' => $request->discount,
                'min_transaction' => $request->min_transaction,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
        } else {
            $data->update([
                'title' => $request->title,
                'description' => $request->description,
                'discount' => $request->discount,
                'min_transaction' => $request->min_transaction,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);
        }

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
