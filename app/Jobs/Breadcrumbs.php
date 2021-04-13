<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Models\CategoryData;

class Breadcrumbs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $result= Product::all()->pluck('id_product');
        if(count($result)>0){
            foreach($result as $product_id){
                $id_products=$product_id['id_product'];
                $newresult=CategoryData::withAndWhereHas('get_product_category',function($q) use($id_products){  
                    $q->where('id_product',$id_products);
                })->select('name','url_key')->where('include_in_breadcrumb','yes')->get()->toArray();
                $data = json_encode($newresult); 

                Product::whereNull('breadcrumb')->where('id_product',$product_id['id_product'])->update(["breadcrumb"=>$data]);
                
            
            }
        }
        

    }
}
