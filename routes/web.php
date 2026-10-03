<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\Member;
use App\Http\Controllers\PropheticWordController;
use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/
Route::get('/', Site\HomeController::class)->name('home');
Route::get('/about', [Site\PageController::class, 'about'])->name('about');
Route::get('/discipleship', [Site\PageController::class, 'discipleship'])->name('discipleship');
Route::get('/campus-ministry', [Site\PageController::class, 'campus'])->name('campus');
Route::get('/pages/{page:slug}', [Site\PageController::class, 'show'])->name('pages.show');

Route::get('/lifegroups', [Site\LifeGroupController::class, 'index'])->name('lifegroups.index');
Route::get('/lifegroups/{lifeGroup:slug}', [Site\LifeGroupController::class, 'show'])->name('lifegroups.show');
Route::post('/lifegroups/{lifeGroup:slug}/join', [Site\LifeGroupController::class, 'join'])->middleware('throttle:public-forms')->name('lifegroups.join');

Route::get('/events', [Site\EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [Site\EventController::class, 'show'])->name('events.show');
Route::post('/events/{event:slug}/register', [Site\EventController::class, 'register'])->middleware('throttle:public-forms')->name('events.register');
Route::get('/events/{event:slug}/ticket/{code}', [Site\EventController::class, 'ticket'])->name('events.ticket');

Route::get('/devotionals', [Site\DevotionalController::class, 'index'])->name('devotionals.index');
Route::get('/devotionals/{devotional:slug}', [Site\DevotionalController::class, 'show'])->name('devotionals.show');
Route::get('/sermons', [Site\SermonController::class, 'index'])->name('sermons.index');
Route::get('/sermons/{sermon:slug}', [Site\SermonController::class, 'show'])->name('sermons.show');
Route::get('/gallery', [Site\GalleryController::class, 'index'])->name('gallery.index');
Route::get('/gallery/{gallery:slug}', [Site\GalleryController::class, 'show'])->name('gallery.show');

Route::get('/get-involved', [Site\GetInvolvedController::class, 'create'])->name('get-involved');
Route::post('/get-involved', [Site\GetInvolvedController::class, 'store'])->middleware('throttle:public-forms')->name('get-involved.store');
Route::get('/get-involved/thank-you', [Site\GetInvolvedController::class, 'thanks'])->name('get-involved.thanks');
Route::get('/get-involved/serve', [Site\ServeController::class, 'create'])->name('get-involved.serve');
Route::post('/get-involved/serve', [Site\ServeController::class, 'store'])->middleware('throttle:public-forms')->name('get-involved.serve.store');
Route::get('/connect', [Site\ConnectController::class, 'create'])->name('connect');
Route::post('/connect', [Site\ConnectController::class, 'store'])->middleware('throttle:public-forms')->name('connect.store');
Route::get('/prayer', [Site\PrayerController::class, 'create'])->name('prayer.create');
Route::post('/prayer', [Site\PrayerController::class, 'store'])->middleware('throttle:public-forms')->name('prayer.store');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:public-forms')->name('register.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:public-forms')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Personal files — opened by the person or by the team caring for them (policies decide)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');
    Route::get('/certificates/{certificate}/file', [CertificateController::class, 'file'])->name('certificates.file');
    Route::get('/journey-record/{profile}/{program}', [CertificateController::class, 'journeyRecord'])->name('certificates.journey-record');
    Route::get('/prophetic-words/{word}/audio', [PropheticWordController::class, 'audio'])->name('prophetic-words.audio');
    Route::get('/prophetic-words/{word}/download', [PropheticWordController::class, 'download'])->name('prophetic-words.download');
});

/*
|--------------------------------------------------------------------------
| Member area — every signed-in person (role USER and above)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('my')->name('member.')->group(function () {
    Route::get('/', Member\DashboardController::class)->name('dashboard');
    Route::get('/profile', [Member\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [Member\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [Member\ProfileController::class, 'password'])->name('password.update');
    Route::get('/journey', Member\JourneyController::class)->name('journey');
    Route::get('/lifegroup', Member\LifeGroupController::class)->name('lifegroup');
    Route::get('/classes', [Member\ClassController::class, 'index'])->name('classes');
    Route::post('/classes/{batch}', [Member\ClassController::class, 'register'])->name('classes.register');
    Route::get('/events', [Member\EventController::class, 'index'])->name('events');
    Route::delete('/events/{registration}', [Member\EventController::class, 'cancel'])->name('events.cancel');
    Route::get('/serving', Member\ServingController::class)->name('serving');
    Route::get('/certificates', Member\CertificateController::class)->name('certificates');
    Route::get('/prophetic-words', Member\PropheticWordController::class)->name('prophetic-words');
    Route::get('/disciples', [Member\DiscipleController::class, 'index'])->name('disciples');
    Route::get('/disciples/{profile}', [Member\DiscipleController::class, 'show'])->name('disciples.show');
    Route::post('/disciples/{profile}/meetings', [Member\DiscipleController::class, 'meeting'])->name('disciples.meetings.store');
    Route::post('/disciples/{profile}/notes', [Member\DiscipleController::class, 'note'])->name('disciples.notes.store');
    Route::post('/disciples/{profile}/programs', [Member\DiscipleController::class, 'startProgram'])->name('disciples.programs.store');
    Route::patch('/disciples/{profile}/baptism', [Member\DiscipleController::class, 'baptism'])->name('disciples.baptism');
    Route::put('/disciples/{profile}/progress/{progress}/chapters/{chapter}', [Member\DiscipleController::class, 'chapter'])->name('disciples.chapters.update');
});
