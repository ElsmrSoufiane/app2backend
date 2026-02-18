<?php
namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller {
  public function index(Request $request){
    return Expense::where('user_id',$request->user()->id)->latest()->get();
  }

  public function store(Request $request){
    $data = $request->validate([
      'date'=>'required|date',
      'type'=>'required|string|max:40',
      'amount'=>'required|numeric',
      'note'=>'nullable|string|max:255',
    ]);
    return Expense::create($data + ['user_id'=>$request->user()->id]);
  }

  public function update(Request $request, Expense $expense){
    abort_if($expense->user_id !== $request->user()->id, 403);
    $data = $request->validate([
      'date'=>'sometimes|required|date',
      'type'=>'sometimes|required|string|max:40',
      'amount'=>'sometimes|required|numeric',
      'note'=>'nullable|string|max:255',
    ]);
    $expense->update($data);
    return $expense;
  }

  public function destroy(Request $request, Expense $expense){
    abort_if($expense->user_id !== $request->user()->id, 403);
    $expense->delete();
    return response()->json(['success'=>true]);
  }
}
