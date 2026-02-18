<?php
namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller {
  public function index(Request $request){
    return Order::where('user_id',$request->user()->id)->latest()->get();
  }

  public function store(Request $request){
    $data = $request->validate([
      'product_id'=>'nullable|exists:products,id',
      'date'=>'required|date',
      'city'=>'nullable|string|max:120',
      'status'=>'required|in:delivered,pending,returned',
      'sell'=>'required|numeric',
      'cost'=>'required|numeric',
      'ship'=>'nullable|numeric',
    ]);

    return Order::create($data + ['user_id'=>$request->user()->id, 'ship'=>$data['ship'] ?? 0]);
  }

  public function update(Request $request, Order $order){
    abort_if($order->user_id !== $request->user()->id, 403);

    $data = $request->validate([
      'product_id'=>'nullable|exists:products,id',
      'date'=>'sometimes|required|date',
      'city'=>'nullable|string|max:120',
      'status'=>'sometimes|required|in:delivered,pending,returned',
      'sell'=>'sometimes|required|numeric',
      'cost'=>'sometimes|required|numeric',
      'ship'=>'nullable|numeric',
    ]);

    $order->update($data);
    return $order;
  }

  public function destroy(Request $request, Order $order){
    abort_if($order->user_id !== $request->user()->id, 403);
    $order->delete();
    return response()->json(['success'=>true]);
  }
}
