<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class Message extends Model {

  protected $table = 'messages';
  protected $fillable = ['from_id', 'to_id', 'message'];

  public function from()
  {
    return $this->belongsTo('User', 'from_id');
  }

  public function to()
  {
    return $this->belongsTo('User', 'to_id');
  }
}
