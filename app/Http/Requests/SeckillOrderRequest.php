<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Validation\Rule;

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
                if (!$sku = ProductSku::find($value)) {
                    return $fail('该商品不存在');
                }
                if($sku->product->type != Product::TYPE_SECKILL){
                    return $fail('该商品不支持秒杀');
                }
                if($sku->product->seckill->is_before_start){
                    return $fail('秒杀尚未开始');
                }
                if($sku->product->seckill->is_after_at){
                    return $fail('秒杀已经结束');
                }
                if (!$sku->product->on_sale) {
                    return $fail('该商品未上架');
                }
                if ($sku->stock < 1) {
                    return $fail('该商品已售完');
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
