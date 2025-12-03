<?php
namespace RonyLang\FilamentTinymceEditor\Models;

use Illuminate\Database\Eloquent\Model;

class TinymcePermission extends Model
{
    protected $table = 'tinymce_permissions';

    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
