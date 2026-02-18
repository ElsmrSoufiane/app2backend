<?php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller {
  public function index(Request $request){
    return Product::where('user_id',$request->user()->id)->latest()->get();
  }

  public function store(Request $request){
    $data = $request->validate([
      'name'=>'required|string|max:200',
      'image_url'=>'nullable|string|max:2048',
      'default_cost'=>'nullable|numeric',
      'default_ship'=>'nullable|numeric',
      'default_sell'=>'nullable|numeric',
    ]);

    return Product::create($data + ['user_id'=>$request->user()->id]);
  }

  public function show(Request $request, Product $product){
    $this->authorizeOwner($request, $product);
    return $product;
  }

  public function update(Request $request, Product $product){
    $this->authorizeOwner($request, $product);
    $data = $request->validate([
      'name'=>'sometimes|required|string|max:200',
      'image_url'=>'nullable|string|max:2048',
      'default_cost'=>'nullable|numeric',
      'default_ship'=>'nullable|numeric',
      'default_sell'=>'nullable|numeric',
    ]);
    $product->update($data);
    return $product;
  }

  public function destroy(Request $request, Product $product){
    $this->authorizeOwner($request, $product);
    $product->delete();
    return response()->json(['success'=>true]);
  }

  private function authorizeOwner(Request $request, Product $p){
    abort_if($p->user_id !== $request->user()->id, 403);
  }
}
