<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Redis;
use Illuminate\Auth\AuthenticationException;
use App\Exceptions\InvalidRequestException;

class SeckillOrderRequest extends Request
{
    public function rules()
    {
    return [
        'address.province'      => 'required',
        'address.city'          => 'required',
        'address.district'      => 'required',
        'address.address'       => 'required',
        'address.zip'           => 'required',
        'address.contact_name'  => 'required',
        'address.contact_phone' => 'required',
        'sku_id' => [
            'required',
            function ($attribute, $value, $fail) {
                // 从 Redis 中读取数据
                $stock = Redis::get('seckill_sku_'.$value);
                if (is_null($stock)) {
                    return $fail('该商品不存在');
                }

                if ($stock < 1) {
                    return $fail('该商品已售完');
                }

                // 大多数用户在上面的逻辑里就被拒绝了
                // 因此下方的 SQL 查询不会对整体性能有太大影响
                $sku = ProductSku::find($value);
                if ($sku->product->seckill->is_before_start) {
                    return $fail('秒杀尚未开始');
                }
                if ($sku->product->seckill->is_after_end) {
                    return $fail('秒杀已经结束');
                }


                if (!$user = \Auth::user()) {
                    throw new AuthenticationException('请先登录');
                }
                if (!$user->email_verified_at) {
                    throw new InvalidRequestException('请先验证邮箱');
                }

                //筛选出包含当前sku的订单
                //已支付的订单或未关闭的订单
                $order = Order::query()
                    ->where('user_id',$this->user()->id)
                    ->whereHas('items',function ($query) use($value){
                        $query->where('product_sku_id',$value);
                    })
                    ->where(function($query){
                        $query->whereNotNull('paid_at')->orWhere('closed',false);
                    })
                    ->first();

                if($order){
                    if($order->paid_at){
                        return $fail('你已经抢购了该商品');
                    }
                    return $fail('你已经下单了该商品，请到订单页面支付');
                }

            },
        ],
    ];
    }
}
