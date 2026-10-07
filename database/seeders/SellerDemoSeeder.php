<?php
namespace Database\Seeders;
use App\Models\Category;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class SellerDemoSeeder extends Seeder
{
    public function run():void
    {
        if(!app()->environment('local','testing'))throw new \RuntimeException('Seller demo data can only be created locally or in tests.');
        $tag=strtolower(Str::random(7));$password='Demo!'.Str::random(14).'9aA';
        $seller=DB::transaction(function()use($tag,$password){
            $seller=User::create(['name'=>'Demo Seller '.$tag,'email'=>'seller.'.$tag.'@example.test','password'=>Hash::make($password),'role'=>'seller','status'=>'approved','phone_number'=>'09170000001']);
            $seller->sellerDetail()->create(['last_name'=>'Seller','first_name'=>'Demo','sex'=>'male','birthday'=>'1998-01-01','province'=>'Laguna','municipality'=>'Santa Cruz','barangay'=>'Demo Barangay','street'=>'12 Demo Street','zip_code'=>'4009','business_name'=>'Demo Store '.$tag,'business_permit_path'=>'']);
            $buyer=User::create(['name'=>'Demo Buyer '.$tag,'email'=>'buyer.'.$tag.'@example.test','password'=>Hash::make(Str::random(40)),'role'=>'buyer','status'=>'approved','phone_number'=>'09170000002']);
            $rider=User::create(['name'=>'Demo Rider '.$tag,'email'=>'rider.'.$tag.'@example.test','password'=>Hash::make(Str::random(40)),'role'=>'courier','status'=>'approved']);
            $category=Category::whereNull('parent_id')->first()??Category::create(['name'=>'Demo Products '.$tag]);$seller->categories()->sync([$category->id]);
            $product=Product::create(['product_code'=>'DEMO-'.$tag,'seller_id'=>$seller->id,'category_id'=>$category->id,'name'=>'Demo Wireless Earbuds '.$tag,'description'=>'Local UI test item.','price'=>499,'stock'=>100,'status'=>'approved']);
            $statuses=array_merge(['placed','placed','confirmed'],array_keys(Order::STATUSES));
            foreach($statuses as $i=>$status){
                $courier=in_array($status,['ready_for_pickup','picked_up','at_sorting_center','sorted','assigned_to_rider','out_for_delivery','delivered','completed','delivery_failed','returned'])?$rider->id:null;
                $order=Order::create(['buyer_id'=>$buyer->id,'seller_id'=>$seller->id,'courier_id'=>$courier,'total_amount'=>998,'status'=>$status,'payment_mode'=>'cod','shipping_address'=>'24 Sample Street, Demo Barangay, Santa Cruz, Laguna 4009']);
                $order->forceFill(['created_at'=>now()->subDays($i<2?0:$i),'updated_at'=>now()->subDays($i<2?0:$i)])->save();
                $order->items()->create(['product_id'=>$product->id,'quantity'=>2,'price'=>499,'color'=>'Black']);
                if(!in_array($status,['cancelled','returned']))$product->decrement('stock',2);
                $order->statusEvents()->create(['user_id'=>$seller->id,'to_status'=>$status,'note'=>'Demo order for local UI testing.']);
            }
            return $seller;
        });
        $this->command?->info('Created sample seller, buyer, rider, product, and orders in the local database.');
        echo json_encode(['seller_id'=>$seller->id,'email'=>$seller->email,'password'=>$password,'login'=>url('/seller/login')],JSON_PRETTY_PRINT).PHP_EOL;
    }
}
