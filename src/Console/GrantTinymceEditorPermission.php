<?php
namespace RonyLang\FilamentTinymceEditor\Console;

use Illuminate\Console\Command;
use RonyLang\FilamentTinymceEditor\Models\TinymcePermission;
use App\Models\User;

class GrantTinymceEditorPermission extends Command
{
    protected $signature = 'tinymce:editor {--user=}';
    protected $description = 'Grant TinyMCE Editor permission to a user';

    public function handle()
    {
        $userId = $this->option('user');
        $user = User::find($userId);
        if (!$user) {
            $this->error('User not found.');
            return 1;
        }
        if (TinymcePermission::where('user_id', $user->id)->exists()) {
            $this->info('User already has TinyMCE Editor permission.');
            return 0;
        }
        TinymcePermission::create(['user_id' => $user->id]);
        $this->info('TinyMCE Editor permission granted to user ID: ' . $user->id);
        return 0;
    }
}
