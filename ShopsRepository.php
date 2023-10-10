<?php

namespace App\Repository;

use App\functions;
use App\Models\Ecommerce\Shops;
use App\Services\Utility;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\CalendarUtils;
use SoulDoit\DataTable\SSP;

class ShopsRepository
{
    public static function store( $request){

        try {
            $image_profile = Utility::uploadPhoto($request['image_profile'], 'uploads/ecommerce/shops');
            $image_banner = Utility::uploadPhoto($request['image_banner'], 'uploads/ecommerce/shops');
        }catch (\Exception $e){
            Log::alert('Error in ShopsRepository uploadPhoto(store) method : ',[$e->getMessage(),$e->getFile(),$e->getLine()]);
            return false;
        }
        return Shops::create([
            'user_id' => $request['user_id'],
            'shop_name'=>$request['shop_name'],
            'shop_id'=>$request['shop_id'],
            'address'=>$request['address'],
            'status'=>$request['status'],
            'bio'=>$request['bio'],
            'image_profile'=>$image_profile,
            'image_banner'=>$image_banner,
        ]);
    }

    public static function update( $request,$id){
        try {
            if (isset($request['image_profile']))
            $image_profile = Utility::uploadPhoto($request['image_profile'], 'uploads/ecommerce/shops');
            if (isset($request['image_banner']))
            $image_banner = Utility::uploadPhoto($request['image_banner'], 'uploads/ecommerce/shops');
        }catch (\Exception $e){
            Log::alert('Error in ShopsRepository uploadPhoto(update) method : ',[$e->getMessage(),$e->getFile(),$e->getLine()]);
            return false;
        }
        $data = array_filter([
            'user_id' => $request['user_id'],
            'shop_name'=>$request['shop_name'],
            'shop_id'=>$request['shop_id'],
            'address'=>$request['address'],
            'status'=>$request['status'],
            'bio'=>$request['bio'],
            'image_profile'=>$image_profile??null,
            'image_banner'=>$image_banner??null,
        ],'strlen');

        return Shops::where('id',$id)->update($data);
    }



    public static function getShopById($id){
        return Shops::find($id);
    }
    public static function listShops($request){

        if(strpos($request->search['value'],"#") !== false){
            $request->id = str_replace('#','',$request->search['value']);
            $request->merge(['search' => array('value'=>$request->id,'regex'=>$request->search['regex']) ]);
        }

        $functions = new functions;
        if (isset($request->date_start)) {
            $request->date_start = $functions->toLatin($request->date_start);
            $request->date_start = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->date_start);
        }

        if (isset($request->date_stop)) {
            $request->date_stop = $functions->toLatin($request->date_stop);
            $request->date_stop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->date_stop);
        }

        $dt = [
            ['label' => 'NameFamily', 'db' => 'users.family', 'dt' => 11],

            ['label' => 'Name', 'db' => 'users.name', 'dt' => 1],
            ['label' => 'ShopName', 'db' => 'shops.shop_name', 'dt' => 2],
            ['label' => 'ShopId', 'db' => 'shops.shop_id', 'dt' => 3],
            ['label' => 'Date', 'db' => 'shops.created_at', 'dt' => 4, 'formatter' => function ($value, $model) {
                return CalendarUtils::strftime('d F Y - H:i', strtotime($value));
            }],
            ['label' => 'Status', 'db' => 'shops.status', 'dt' => 5, 'formatter' => function ($value, $model) {
                 if ($value === 'confirm')
                    return '<span class="badge badge-pill badge-success font-weight-light">تایید شده</span>';
                elseif ($value === 'pending')
                    return '<span class="badge badge-pill badge-warning font-weight-light">منتظر برسی</span>';
                elseif ($value === 'reject')
                    return '<span class="badge badge-pill badge-danger font-weight-light">رد شده</span>';
            }],


            ['label' => 'Detail', 'db' => 'shops.id', 'dt' => 6, 'formatter' => function ($value, $model) {
                $link =  '<a href="' . route('admin.ecommerce.shops.edit',$value). '"
                            class="btn btn-icon rounded-circle btn-outline-primary waves-effect waves-light"><i class="feather icon-link"></i></a>';
                return $link;
            }],


        ];
        $my_ssp = (new SSP('shops', $dt))->where(function ($query){
            if (isset(request()->date_start)) {
                $query->where('orders.created_at', '>', request()->date_start);
            }

            if (isset(request()->date_stop)) {
                $query->where('orders.created_at', '<', request()->date_stop);
            }

            if (isset(request()->status)) {
                $query->where('status', request()->status);
            }
        });

        $my_ssp->leftJoin('users', 'shops.user_id', 'users.id');

        return $my_ssp->getDtArr();
    }

}
