<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Ministry dashboard
|--------------------------------------------------------------------------
| Every route requires an active account with `admin.access`. Fine-grained
| permissions live on the route groups below; record-level scoping (own
| LifeGroup, own campus, own ministry) is enforced by policies + AccessScope.
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::get('/search', Admin\SearchController::class)->name('search');
    Route::get('/notifications', [Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [Admin\NotificationController::class, 'markAllRead'])->name('notifications.read');

    // ── PEOPLE ─────────────────────────────────────────────────────
    Route::middleware('permission:members.view')->group(function () {
        Route::resource('members', Admin\MemberController::class)->parameters(['members' => 'profile']);
        Route::post('members/{profile}/notes', [Admin\MemberController::class, 'note'])->name('members.notes.store');
        Route::post('members/{profile}/activate', [Admin\MemberController::class, 'activate'])->name('members.activate');
        Route::post('members/{profile}/lifegroup', [Admin\MemberController::class, 'assignLifeGroup'])->name('members.lifegroup');
        Route::post('members/{profile}/account', [Admin\MemberController::class, 'createAccount'])->name('members.account');
        Route::post('members/{profile}/certificates', [Admin\CertificateController::class, 'store'])->name('members.certificates.store');
        Route::delete('certificates/{certificate}', [Admin\CertificateController::class, 'destroy'])->name('certificates.destroy');
        Route::post('members/{profile}/prophetic-words', [Admin\PropheticWordController::class, 'store'])->name('members.prophetic-words.store');
        Route::delete('prophetic-words/{word}', [Admin\PropheticWordController::class, 'destroy'])->name('prophetic-words.destroy');
    });
    Route::get('newcomers', [Admin\NewcomerController::class, 'index'])->middleware('permission:newcomers.view')->name('newcomers.index');
    Route::patch('newcomers/{newcomer}', [Admin\NewcomerController::class, 'update'])->middleware('permission:newcomers.manage')->name('newcomers.update');

    Route::middleware('permission:involvement.view')->prefix('involvement')->name('involvement.')->controller(Admin\InvolvementController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export', 'export')->middleware('permission:reports.export')->name('export');
        Route::get('/{involvementRequest}', 'show')->name('show');
        Route::post('/{involvementRequest}/assign', 'assign')->name('assign');
        Route::patch('/{involvementRequest}/status', 'status')->name('status');
        Route::post('/{involvementRequest}/notes', 'note')->name('notes.store');
        Route::post('/{involvementRequest}/lifegroup', 'lifeGroup')->name('lifegroup');
        Route::post('/{involvementRequest}/ministry', 'ministry')->name('ministry');
        Route::post('/{involvementRequest}/one2one', 'one2one')->name('one2one');
        Route::post('/{involvementRequest}/activate', 'activate')->name('activate');
        Route::post('/{involvementRequest}/archive', 'archive')->name('archive');
        Route::post('/{involvementRequest}/restore', 'restore')->name('restore');
    });

    Route::middleware('permission:followups.view')->group(function () {
        Route::get('follow-ups', [Admin\FollowUpController::class, 'index'])->name('follow-ups.index');
        Route::post('follow-ups', [Admin\FollowUpController::class, 'store'])->middleware('permission:followups.manage')->name('follow-ups.store');
        Route::patch('follow-ups/{task}', [Admin\FollowUpController::class, 'update'])->middleware('permission:followups.manage')->name('follow-ups.update');
    });

    // ── DISCIPLESHIP ───────────────────────────────────────────────
    Route::middleware('permission:discipleship.view')->group(function () {
        Route::get('journey', [Admin\JourneyController::class, 'index'])->name('journey.index');
        Route::get('one2one', [Admin\One2OneController::class, 'index'])->name('one2one.index');
        Route::post('one2one', [Admin\One2OneController::class, 'store'])->name('one2one.store');
        Route::post('members/{profile}/programs', [Admin\ProgressController::class, 'store'])->name('progress.store');
        Route::patch('members/{profile}/baptism', [Admin\ProgressController::class, 'baptism'])->name('members.baptism');
        Route::get('progress/{progress}', [Admin\ProgressController::class, 'show'])->name('progress.show');
        Route::patch('progress/{progress}', [Admin\ProgressController::class, 'update'])->name('progress.update');
        Route::put('progress/{progress}/chapters/{chapter}', [Admin\ProgressController::class, 'chapter'])->name('progress.chapter');

        Route::get('disciplers', [Admin\DisciplerController::class, 'index'])->name('disciplers.index');
        Route::get('disciplers/tree', [Admin\DisciplerController::class, 'tree'])->name('disciplers.tree');
        Route::post('relationships', [Admin\DisciplerController::class, 'store'])->name('relationships.store');
        Route::post('relationships/{relationship}/end', [Admin\DisciplerController::class, 'end'])->name('relationships.end');
        Route::post('relationships/{relationship}/meetings', [Admin\DisciplerController::class, 'meeting'])->name('relationships.meetings.store');
    });

    Route::middleware('permission:curriculum.manage')->group(function () {
        Route::get('curriculum', [Admin\CurriculumController::class, 'index'])->name('curriculum.index');
        Route::post('curriculum/stages', [Admin\CurriculumController::class, 'storeStage'])->name('curriculum.stages.store');
        Route::put('curriculum/stages/{stage}', [Admin\CurriculumController::class, 'updateStage'])->name('curriculum.stages.update');
        Route::delete('curriculum/stages/{stage}', [Admin\CurriculumController::class, 'destroyStage'])->name('curriculum.stages.destroy');
        Route::get('curriculum/programs/create', [Admin\ProgramController::class, 'create'])->name('curriculum.programs.create');
        Route::post('curriculum/programs', [Admin\ProgramController::class, 'store'])->name('curriculum.programs.store');
        Route::get('curriculum/programs/{program}/edit', [Admin\ProgramController::class, 'edit'])->name('curriculum.programs.edit');
        Route::put('curriculum/programs/{program}', [Admin\ProgramController::class, 'update'])->name('curriculum.programs.update');
        Route::delete('curriculum/programs/{program}', [Admin\ProgramController::class, 'destroy'])->name('curriculum.programs.destroy');
        Route::post('curriculum/programs/{program}/chapters', [Admin\ProgramController::class, 'storeChapter'])->name('curriculum.chapters.store');
        Route::put('curriculum/chapters/{chapter}', [Admin\ProgramController::class, 'updateChapter'])->name('curriculum.chapters.update');
        Route::delete('curriculum/chapters/{chapter}', [Admin\ProgramController::class, 'destroyChapter'])->name('curriculum.chapters.destroy');
        Route::get('books', [Admin\ProgramController::class, 'books'])->name('books.index');
    });

    Route::middleware('permission:classes.view')->group(function () {
        Route::resource('classes', Admin\ClassBatchController::class)->parameters(['classes' => 'batch']);
        Route::get('victory-weekend', [Admin\ClassBatchController::class, 'victoryWeekend'])->name('victory-weekend.index');
        Route::post('classes/{batch}/participants', [Admin\ClassBatchController::class, 'enroll'])->name('classes.participants.store');
        Route::patch('class-participants/{participant}', [Admin\ClassBatchController::class, 'updateParticipant'])->name('classes.participants.update');
        Route::post('classes/{batch}/sessions', [Admin\ClassBatchController::class, 'storeSession'])->name('classes.sessions.store');
        Route::post('classes/{batch}/sessions/generate', [Admin\ClassBatchController::class, 'generateSessions'])->name('classes.sessions.generate');
        Route::get('class-sessions/{session}', [Admin\ClassBatchController::class, 'session'])->name('classes.sessions.show');
        Route::put('class-sessions/{session}', [Admin\ClassBatchController::class, 'updateSession'])->name('classes.sessions.update');
        Route::delete('class-sessions/{session}', [Admin\ClassBatchController::class, 'destroySession'])->name('classes.sessions.destroy');
        Route::put('class-sessions/{session}/attendance', [Admin\ClassBatchController::class, 'attendance'])->name('classes.sessions.attendance');
    });

    Route::middleware('permission:leadership.view')->group(function () {
        Route::get('leadership', [Admin\LeadershipController::class, 'index'])->name('leadership.index');
        Route::post('leadership', [Admin\LeadershipController::class, 'store'])->middleware('permission:leadership.manage')->name('leadership.store');
        Route::patch('leadership/{candidate}', [Admin\LeadershipController::class, 'update'])->middleware('permission:leadership.manage')->name('leadership.update');
    });

    // ── COMMUNITY ──────────────────────────────────────────────────
    Route::middleware('permission:lifegroups.view')->group(function () {
        Route::resource('lifegroups', Admin\LifeGroupController::class)->parameters(['lifegroups' => 'lifeGroup']);
        Route::post('lifegroups/{lifeGroup}/members', [Admin\LifeGroupMemberController::class, 'store'])->name('lifegroups.members.store');
        Route::patch('lifegroup-members/{membership}', [Admin\LifeGroupMemberController::class, 'update'])->name('lifegroups.members.update');
        Route::delete('lifegroup-members/{membership}', [Admin\LifeGroupMemberController::class, 'destroy'])->name('lifegroups.members.destroy');
        Route::get('meetings', [Admin\MeetingController::class, 'index'])->name('meetings.index');
        Route::get('lifegroups/{lifeGroup}/meetings/create', [Admin\MeetingController::class, 'create'])->name('meetings.create');
        Route::post('lifegroups/{lifeGroup}/meetings', [Admin\MeetingController::class, 'store'])->name('meetings.store');
        Route::get('meetings/{meeting}/edit', [Admin\MeetingController::class, 'edit'])->name('meetings.edit');
        Route::put('meetings/{meeting}', [Admin\MeetingController::class, 'update'])->name('meetings.update');
        Route::delete('meetings/{meeting}', [Admin\MeetingController::class, 'destroy'])->name('meetings.destroy');
    });
    Route::middleware('permission:lifegroups.requests')->group(function () {
        Route::get('join-requests', [Admin\JoinRequestController::class, 'index'])->name('join-requests.index');
        Route::patch('join-requests/{joinRequest}', [Admin\JoinRequestController::class, 'update'])->name('join-requests.update');
        Route::post('join-requests/{joinRequest}/notes', [Admin\JoinRequestController::class, 'note'])->name('join-requests.notes.store');
        Route::get('join-requests/{joinRequest}/invite', [Admin\JoinRequestController::class, 'invite'])->name('join-requests.invite');
    });

    // ── MINISTRY ───────────────────────────────────────────────────
    Route::middleware('permission:ministries.view')->group(function () {
        Route::resource('ministries', Admin\MinistryController::class);
        Route::post('ministries/{ministry}/roles', [Admin\MinistryController::class, 'storeRole'])->name('ministries.roles.store');
        Route::delete('ministry-roles/{role}', [Admin\MinistryController::class, 'destroyRole'])->name('ministries.roles.destroy');
        Route::post('ministries/{ministry}/members', [Admin\VolunteerController::class, 'store'])->name('ministries.members.store');
        Route::get('volunteers', [Admin\VolunteerController::class, 'index'])->name('volunteers.index');
        Route::patch('volunteers/{member}', [Admin\VolunteerController::class, 'update'])->name('volunteers.update');
        Route::delete('volunteers/{member}', [Admin\VolunteerController::class, 'destroy'])->name('volunteers.destroy');
        Route::get('serving-schedule', [Admin\ServingScheduleController::class, 'index'])->name('serving.index');
        Route::post('serving-schedule', [Admin\ServingScheduleController::class, 'store'])->name('serving.store');
        Route::patch('serving-schedule/{schedule}', [Admin\ServingScheduleController::class, 'update'])->name('serving.update');
        Route::delete('serving-schedule/{schedule}', [Admin\ServingScheduleController::class, 'destroy'])->name('serving.destroy');
    });
    Route::middleware('permission:volunteers.manage')->group(function () {
        Route::get('volunteer-applications', [Admin\VolunteerApplicationController::class, 'index'])->name('volunteer-applications.index');
        Route::get('volunteer-applications/{application}', [Admin\VolunteerApplicationController::class, 'show'])->name('volunteer-applications.show');
        Route::patch('volunteer-applications/{application}', [Admin\VolunteerApplicationController::class, 'update'])->name('volunteer-applications.update');
        Route::post('volunteer-applications/{application}/notes', [Admin\VolunteerApplicationController::class, 'note'])->name('volunteer-applications.notes.store');
    });

    // ── CAMPUS ─────────────────────────────────────────────────────
    Route::middleware('permission:campus.view')->group(function () {
        Route::resource('campuses', Admin\CampusController::class);
    });

    // ── CONTENT ────────────────────────────────────────────────────
    Route::middleware('permission:events.manage')->group(function () {
        Route::resource('events', Admin\EventController::class);
        Route::get('events/{event}/check-in', [Admin\EventController::class, 'checkIn'])->name('events.check-in');
        Route::post('events/{event}/check-in', [Admin\EventController::class, 'processCheckIn'])->name('events.check-in.store');
        Route::get('events/{event}/export', [Admin\EventController::class, 'export'])->name('events.export');
        Route::patch('event-registrations/{registration}', [Admin\EventController::class, 'updateRegistration'])->name('events.registrations.update');
    });
    Route::middleware('permission:content.manage')->group(function () {
        Route::get('homepage', [Admin\HomepageController::class, 'edit'])->name('homepage.edit');
        Route::put('homepage', [Admin\HomepageController::class, 'update'])->name('homepage.update');
        Route::resource('devotionals', Admin\DevotionalController::class)->except('show');
        Route::resource('sermons', Admin\SermonController::class)->except('show');
        Route::post('sermon-series', [Admin\SermonController::class, 'storeSeries'])->name('sermon-series.store');
        Route::resource('galleries', Admin\GalleryController::class)->except('show');
        Route::post('galleries/{gallery}/images', [Admin\GalleryController::class, 'upload'])->name('galleries.images.store');
        Route::delete('gallery-images/{image}', [Admin\GalleryController::class, 'destroyImage'])->name('galleries.images.destroy');
        Route::resource('pages', Admin\PageController::class)->except('show');
    });

    // ── STORE ──────────────────────────────────────────────────────
    Route::middleware('permission:store.manage')->prefix('store')->name('store.')->group(function () {
        Route::resource('products', Admin\ProductController::class)->except('show');
        Route::delete('product-images/{image}', [Admin\ProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::get('categories', [Admin\ProductCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [Admin\ProductCategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [Admin\ProductCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [Admin\ProductCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('settings', [Admin\StoreSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [Admin\StoreSettingsController::class, 'update'])->name('settings.update');
    });
    Route::middleware('permission:orders.manage')->prefix('orders')->name('orders.')->controller(Admin\OrderController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/export', 'export')->name('export');
        Route::get('/{order}', 'show')->name('show');
        Route::post('/{order}/confirm-payment', 'confirmPayment')->name('confirm');
        Route::post('/{order}/reject-proof', 'rejectProof')->name('reject-proof');
        Route::patch('/{order}/status', 'status')->name('status');
        Route::post('/{order}/cancel', 'cancel')->name('cancel');
        Route::patch('/{order}/notes', 'notes')->name('notes');
        Route::get('/{order}/proof', 'proof')->name('proof');
    });

    // ── CARE ───────────────────────────────────────────────────────
    Route::middleware('permission:prayer.view')->group(function () {
        Route::get('prayer-requests', [Admin\PrayerRequestController::class, 'index'])->name('prayer-requests.index');
        Route::get('prayer-requests/{prayerRequest}', [Admin\PrayerRequestController::class, 'show'])->name('prayer-requests.show');
        Route::patch('prayer-requests/{prayerRequest}', [Admin\PrayerRequestController::class, 'update'])->middleware('permission:prayer.manage')->name('prayer-requests.update');
    });
    Route::middleware('permission:pastoral.view')->group(function () {
        Route::resource('pastoral-care', Admin\PastoralCareController::class)->parameters(['pastoral-care' => 'care'])->except('destroy');
        Route::post('pastoral-care/{care}/notes', [Admin\PastoralCareController::class, 'note'])->name('pastoral-care.notes.store');
    });

    // ── COMMUNICATION ──────────────────────────────────────────────
    Route::resource('announcements', Admin\AnnouncementController::class)->except('show')->middleware('permission:announcements.manage');
    Route::get('birthdays', Admin\BirthdayController::class)->middleware('permission:birthdays.view')->name('birthdays.index');

    // ── REPORTS ────────────────────────────────────────────────────
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [Admin\ReportController::class, 'show'])->name('reports.show');
        Route::get('reports/{report}/export', [Admin\ReportController::class, 'export'])->middleware('permission:reports.export')->name('reports.export');
    });

    // ── SYSTEM ─────────────────────────────────────────────────────
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}/edit', [Admin\UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    });
    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('roles', [Admin\RoleController::class, 'index'])->name('roles.index');
        Route::put('roles/{role}', [Admin\RoleController::class, 'update'])->name('roles.update');
        Route::put('users/{user}/roles', [Admin\RoleController::class, 'assign'])->name('users.roles.update');
    });
    Route::middleware('permission:media.manage')->group(function () {
        Route::get('media', [Admin\MediaController::class, 'index'])->name('media.index');
        Route::post('media', [Admin\MediaController::class, 'store'])->name('media.store');
        Route::delete('media/{media}', [Admin\MediaController::class, 'destroy'])->name('media.destroy');
    });
    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::get('interests', [Admin\InterestController::class, 'index'])->name('interests.index');
        Route::post('interests', [Admin\InterestController::class, 'store'])->name('interests.store');
        Route::put('interests/{interest}', [Admin\InterestController::class, 'update'])->name('interests.update');
        Route::delete('interests/{interest}', [Admin\InterestController::class, 'destroy'])->name('interests.destroy');
    });
    Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->middleware('permission:audit.view')->name('audit-logs.index');
});
