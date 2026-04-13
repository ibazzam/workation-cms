<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use App\Services\TransportProviderAdapterInterface;
use App\Services\HttpTransportProviderAdapter;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // bind the transport provider adapter interface to the HTTP stub
        $this->app->bind(TransportProviderAdapterInterface::class, HttpTransportProviderAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
            $s3Disk = (array) config('filesystems.disks.s3', []);
            if (($s3Disk['driver'] ?? '') === 's3') {
                unset($s3Disk['visibility'], $s3Disk['directory_visibility']);

                $s3Options = (array) ($s3Disk['options'] ?? []);
                unset($s3Options['ACL'], $s3Options['acl']);
                if ($s3Options !== []) {
                    $s3Disk['options'] = $s3Options;
                } else {
                    unset($s3Disk['options']);
                }

                config(['filesystems.disks.s3' => $s3Disk]);
            }

            ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
                $portal = 'admin';
                if ($notifiable instanceof Customer) {
                    $portal = 'customer';
                } elseif ($notifiable instanceof User) {
                    $role = Str::upper((string) $notifiable->portal_role);
                    $normalizedRole = $role === 'ADMIN_FINACE' ? 'ADMIN_FINANCE' : $role;
                    if ($normalizedRole === 'VENDOR') {
                        $portal = 'vendor';
                    }
                }

                $basePath = '/portal/' . $portal . '/reset-password/' . $token;
                return URL::to($basePath) . '?email=' . urlencode($notifiable->getEmailForPasswordReset());
            });

            Gate::define('manage-portal-users', function () {
                $request = request();
                return $request->session()->get('portal_admin_authenticated', false)
                    && $request->session()->get('portal_admin_role') === 'ADMIN_SUPER';
            });
    }
}
