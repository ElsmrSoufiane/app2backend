<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model {
  protected $fillable = ['user_id','date','type','amount','note'];

  public function user(){ return $this->belongsTo(User::class); }
}
