<?php

namespace App\Providers;

use App\Enums\AuditAction;
use App\Enums\Role;
use App\Models\ClassBatch;
use App\Models\DisciplerRelationship;
use App\Models\InvolvementRequest;
use App\Models\LeadershipCandidate;
use App\Models\LifeGroup;
use App\Models\LifeGroupJoinRequest;
use App\Models\MemberProgramProgress;
use App\Models\Ministry;
use App\Models\Order;
use App\Models\Product;
use App\Models\Profile;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Services\AccessScope;
use App\Services\AuditLogger;
use App\Services\Settings;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->scoped(AccessScope::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Relation::morphMap([
            'user' => User::class,
            'profile' => Profile::class,
            'involvement_request' => InvolvementRequest::class,
            'life_group' => LifeGroup::class,
            'life_group_join_request' => LifeGroupJoinRequest::class,
            'volunteer_application' => VolunteerApplication::class,
            'member_program_progress' => MemberProgramProgress::class,
            'class_batch' => ClassBatch::class,
            'event' => \App\Models\Event::class,
            'ministry' => Ministry::class,
            'discipler_relationship' => DisciplerRelationship::class,
            'leadership_candidate' => LeadershipCandidate::class,
            'product' => Product::class,
            'order' => Order::class,
        ]);

        Blade::directive('rupiah', fn (string $expression) => "<?php echo e(\\App\\Services\\Rupiah::format({$expression})); ?>");

        // Super Admin passes every ability check (policies still run for other roles).
        Gate::before(fn (User $user) => $user->hasRole(Role::SuperAdmin->value) ? true : null);

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(8)->letters()->numbers()->uncompromised()
            : Password::min(8));

        Paginator::defaultView('components.pagination');

        RateLimiter::for('public-forms', fn (Request $request) => Limit::perMinute(8)->by($request->ip()));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));

        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
                app(AuditLogger::class)->log(AuditAction::Login, $event->user, "{$event->user->name} signed in", userId: $event->user->id);
            }
        });

        View::composer(['layouts.*', 'components.layouts.*', 'site.*'], function ($view) {
            $view->with('settings', $this->app->make(Settings::class));
        });
    }
}
