<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpsertPortalUser extends Command
{
    protected $signature = 'portal:user:upsert
        {username : Portal username used at login}
        {password : Plain password to set for this portal user}
        {--role=ADMIN : Portal role: ADMIN_SUPER, ADMIN, or VENDOR}
        {--name= : Display name (defaults to username)}
        {--email= : Email (defaults to username@portal.local)}
        {--vendor-id= : Vendor identifier for VENDOR users}
        {--disable : Mark user as disabled for portal login}';

    protected $description = 'Create or update a database-backed portal user account';

    public function handle(): int
    {
        $username = trim((string) $this->argument('username'));
        $password = (string) $this->argument('password');
        $role = strtoupper(trim((string) $this->option('role')));

        if (!in_array($role, ['ADMIN_SUPER', 'ADMIN', 'VENDOR'], true)) {
            $this->error('Invalid role. Use ADMIN_SUPER, ADMIN, or VENDOR.');
            return self::FAILURE;
        }

        if ($username === '') {
            $this->error('Username cannot be empty.');
            return self::FAILURE;
        }

        if ($password === '') {
            $this->error('Password cannot be empty.');
            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $username));
        $defaultEmail = sprintf('%s@portal.local', preg_replace('/\s+/', '.', strtolower($username)) ?: 'portal-user');
        $email = trim((string) ($this->option('email') ?: $defaultEmail));
        $vendorId = trim((string) $this->option('vendor-id'));
        $enabled = !$this->option('disable');

        if ($role === 'VENDOR' && $vendorId === '') {
            $this->warn('No --vendor-id provided for a VENDOR user; access will still work but vendor scoping may be limited.');
        }

        $user = User::query()->where('username', $username)->first();

        if (!$user) {
            $existingEmail = User::query()->where('email', $email)->first();
            if ($existingEmail) {
                $this->error('Email is already used by another user. Provide a unique --email.');
                return self::FAILURE;
            }

            $user = new User();
            $user->username = $username;
        } elseif ($user->email !== $email) {
            $existingEmail = User::query()->where('email', $email)->where('id', '!=', $user->id)->first();
            if ($existingEmail) {
                $this->error('Email is already used by another user. Provide a unique --email.');
                return self::FAILURE;
            }
        }

        $user->name = $name;
        $user->email = $email;
        $user->password = $password;
        $user->portal_role = $role;
        $user->portal_enabled = $enabled;
        $user->portal_vendor_id = $role === 'VENDOR' ? ($vendorId !== '' ? $vendorId : null) : null;
        $user->save();

        $this->info('Portal user saved successfully.');
        $this->line('ID: ' . $user->id);
        $this->line('Username: ' . $user->username);
        $this->line('Role: ' . $user->portal_role);
        $this->line('Enabled: ' . ($user->portal_enabled ? 'yes' : 'no'));

        return self::SUCCESS;
    }
}
