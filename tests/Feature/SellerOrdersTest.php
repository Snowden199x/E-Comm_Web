<?php
namespace Tests\Feature;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
class SellerOrdersTest extends TestCase
{
    private User $seller;private User $buyer;private Product $product;
    protected function setUp():void
    {
        parent::setUp();
        foreach(['0001_01_01_000000_create_users_table.php','2026_08_16_092724_add_role_and_status_to_users_table.php','2026_08_20_084154_add_phone_number_to_users_table.php','2026_08_20_092844_create_categories_table.php','2026_08_30_093251_create_products_table.php','2026_08_30_093307_create_product_images_table.php','2026_08_18_042255_create_orders_table.php','2026_09_03_172548_create_order_items_table.php','2026_09_17_163115_create_cart_items_table.php','2026_09_17_164153_add_shipping_address_to_orders_table.php','2026_09_17_164157_add_variation_to_order_items_table.php','2026_08_18_044239_create_notifications_table.php','2026_09_05_163359_add_user_id_to_notifications_table.php','2026_08_19_080438_create_announcements_table.php','2026_09_05_152251_add_fields_to_announcements_table.php','2026_08_20_092937_create_seller_details_table.php','2026_09_24_090000_expand_order_workflow_and_create_status_events.php'] as $file)(require database_path('migrations/'.$file))->up();
        $this->withoutVite();
        $this->seller=User::create(['name'=>'Seller','email'=>'seller@test.example','password'=>'password','role'=>'seller','status'=>'approved']);
        $this->buyer=User::create(['name'=>'Buyer','email'=>'buyer@test.example','password'=>'password','role'=>'buyer','status'=>'approved']);
        $this->product=Product::create(['product_code'=>'TEST-P1','seller_id'=>$this->seller->id,'name'=>'Test product','price'=>100,'stock'=>8,'status'=>'approved']);
    }
    protected function tearDown():void{Schema::dropAllTables();parent::tearDown();}
    private function order(string $status='placed',?User $seller=null):Order{$o=Order::create(['seller_id'=>($seller??$this->seller)->id,'buyer_id'=>$this->buyer->id,'status'=>$status,'total_amount'=>200,'payment_mode'=>'cod','shipping_address'=>'12 Sample Street']);$o->items()->create(['product_id'=>$this->product->id,'quantity'=>2,'price'=>100]);return $o;}
    private function act(Order $order,string $action,string $status,array $extra=[]){return $this->actingAs($this->seller)->patchJson('/seller/orders/'.$order->id,['action'=>$action,'expected_status'=>$status]+$extra);}
    public function test_full_seller_preparation_flow_and_rider_gate():void{$o=$this->order();$this->act($o,'accept','placed')->assertOk();$this->act($o,'ready','confirmed')->assertConflict();$this->act($o,'prepare','confirmed')->assertOk();$this->act($o,'ready','preparing')->assertOk();$this->act($o,'pickup','ready_for_pickup')->assertUnprocessable();$r=User::create(['name'=>'Rider','email'=>'rider@test.example','password'=>'password','role'=>'courier','status'=>'approved']);$o->update(['courier_id'=>$r->id]);$this->act($o,'pickup','ready_for_pickup')->assertOk();$this->assertSame('picked_up',$o->fresh()->status);$this->assertSame(8,$this->product->fresh()->stock);$this->assertSame(4,$o->statusEvents()->count());}
    public function test_decline_restores_reserved_stock_once_and_notifies_buyer():void{$o=$this->order();$this->act($o,'decline','placed')->assertUnprocessable();$this->act($o,'decline','placed',['reason'=>'Out of stock'])->assertOk();$this->act($o,'decline','placed',['reason'=>'Again'])->assertConflict();$this->assertSame(10,$this->product->fresh()->stock);$this->assertDatabaseHas('notifications',['user_id'=>$this->buyer->id,'type'=>'order_update']);}
    public function test_orders_are_private_to_their_seller_and_accounts_must_be_approved():void{$other=User::create(['name'=>'Other','email'=>'other@test.example','password'=>'password','role'=>'seller','status'=>'approved']);$o=$this->order('placed',$other);$this->actingAs($this->seller)->getJson('/seller/orders/'.$o->id)->assertNotFound();$this->actingAs($this->seller)->get('/seller/orders/'.$o->id.'/waybill')->assertNotFound();$this->act($o,'accept','placed')->assertNotFound();$this->seller->update(['status'=>'pending']);$this->actingAs($this->seller)->getJson('/seller/orders')->assertForbidden();}
    public function test_dashboard_uses_real_seller_sales_and_order_filtering():void{$this->order('delivered');$this->order('completed');$placed=$this->order();$this->actingAs($this->seller)->get('/seller/dashboard')->assertOk()->assertViewHas('stats',fn($s)=>$s['total_orders']===3&&(float)$s['total_sales']===400.0&&$s['pending_orders']===1);$this->getJson('/seller/orders?status=new&search='.$placed->number)->assertOk()->assertJsonPath('pagination.total',1)->assertJsonPath('counts.completed',2);$this->getJson('/seller/orders?date_from=2026-09-24&date_to=2026-09-01')->assertUnprocessable();$this->get('/seller/orders')->assertOk();$this->getJson('/seller/orders/'.$placed->id)->assertOk()->assertJsonStructure(['html']);}
    public function test_legacy_order_statuses_are_mapped_without_losing_orders():void{Schema::drop('order_status_events');$a=$this->order('pending');$b=$this->order('to_ship');$c=$this->order('in_transit');(require database_path('migrations/2026_09_24_090000_expand_order_workflow_and_create_status_events.php'))->up();$this->assertSame('placed',$a->fresh()->status);$this->assertSame('confirmed',$b->fresh()->status);$this->assertSame('picked_up',$c->fresh()->status);$this->assertDatabaseCount('orders',3);}
}
