<?php

use App\Http\Controllers\Admin as A;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\Client\ClientJobController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FreelancerDirectoryController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\JobBoardController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// ---------- Public ----------
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/explore', [\App\Http\Controllers\FeedController::class, 'explore'])->name('explore');
Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/how-it-works', [PageController::class, 'how'])->name('how');
Route::get('/freelancers', [FreelancerDirectoryController::class, 'index'])->name('freelancers.index');
Route::get('/freelancers/{profile:slug}', [FreelancerDirectoryController::class, 'show'])->name('freelancers.show');
Route::get('/portfolio/{project}', [PortfolioController::class, 'show'])->name('portfolio.show');
Route::get('/jobs', [JobBoardController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [JobBoardController::class, 'show'])->name('jobs.show');

Route::post('/a/beacon', [\App\Http\Controllers\AnalyticsBeaconController::class, 'beacon'])->middleware('throttle:120,1')->name('analytics.beacon');
Route::post('/a/event', [\App\Http\Controllers\AnalyticsBeaconController::class, 'event'])->middleware('throttle:120,1')->name('analytics.event');
Route::post('/admin-mode', [\App\Http\Controllers\Admin\SystemController::class, 'switchMode'])->middleware(['auth', 'admin'])->name('admin.mode');
Route::post('/impersonation/stop', [\App\Http\Controllers\Admin\UserController::class, 'stopImpersonating'])->middleware('auth')->name('impersonation.stop');
Route::post('/webhooks/payments/{provider}', [PaymentWebhookController::class, 'handle'])->name('webhooks.payments');

// ---------- Guest ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:auth')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:auth')->name('password.update');
});

// ---------- Authenticated ----------
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');

    Route::middleware('verified')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/workspace', [SettingsController::class, 'switchWorkspace'])->name('workspace.switch');
        Route::post('/workspace/enable', [SettingsController::class, 'enableRole'])->name('workspace.enable');

        // Settings
        Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
        Route::put('/settings/account', [SettingsController::class, 'updateAccount'])->name('settings.account');
        Route::post('/settings/avatar', [SettingsController::class, 'updateAvatar'])->middleware('throttle:uploads')->name('settings.avatar');
        Route::post('/settings/cover', [SettingsController::class, 'updateCover'])->middleware(['role:freelancer', 'throttle:uploads'])->name('settings.cover');
        Route::put('/settings/freelancer', [SettingsController::class, 'updateFreelancer'])->middleware('role:freelancer')->name('settings.freelancer');
        Route::put('/settings/payment', [SettingsController::class, 'updatePayment'])->middleware('role:freelancer')->name('settings.payment');
        Route::put('/settings/client', [SettingsController::class, 'updateClient'])->middleware('role:client')->name('settings.client');
        Route::post('/settings/verification', [SettingsController::class, 'requestVerification'])->middleware(['role:client', 'throttle:uploads'])->name('settings.verification');
        Route::post('/settings/data-requests', [SettingsController::class, 'requestData'])->middleware('throttle:uploads')->name('settings.data-request');
        Route::get('/settings/data-export/{dataRequest}', [SettingsController::class, 'downloadExport'])->name('settings.data-export');

        // Freelancer workspace
        Route::middleware('role:freelancer')->group(function () {
            Route::get('/my/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
            Route::get('/my/portfolio/create', [PortfolioController::class, 'create'])->name('portfolio.create');
            Route::post('/my/portfolio/uploads', [\App\Http\Controllers\MediaUploadController::class, 'store'])->middleware('throttle:image-uploads')->name('media.uploads.store');
            Route::post('/my/portfolio', [PortfolioController::class, 'store'])->middleware('throttle:uploads')->name('portfolio.store');
            Route::get('/my/portfolio/{project}/edit', [PortfolioController::class, 'edit'])->name('portfolio.edit');
            Route::put('/my/portfolio/{project}', [PortfolioController::class, 'update'])->middleware('throttle:uploads')->name('portfolio.update');
            Route::post('/my/portfolio/{project}/publish', [PortfolioController::class, 'togglePublish'])->name('portfolio.publish');
            Route::delete('/my/portfolio/{project}', [PortfolioController::class, 'destroy'])->name('portfolio.destroy');

            Route::get('/my/applications', [ApplicationController::class, 'mine'])->name('applications.mine');
            Route::get('/jobs/{job}/apply', [ApplicationController::class, 'create'])->name('applications.create');
            Route::post('/jobs/{job}/apply', [ApplicationController::class, 'store'])->middleware('throttle:uploads')->name('applications.store');
            Route::post('/jobs/{job}/save', [JobBoardController::class, 'toggleSave'])->name('jobs.save');
            Route::get('/my/saved-jobs', [JobBoardController::class, 'saved'])->name('jobs.saved');

            Route::get('/my/invitations', [InvitationController::class, 'index'])->name('invitations.index');
            Route::post('/invitations/{invitation}/respond', [InvitationController::class, 'respond'])->name('invitations.respond');
        });

        // Client workspace
        Route::middleware('role:client')->prefix('client')->name('client.')->group(function () {
            Route::get('/jobs', [ClientJobController::class, 'index'])->name('jobs.index');
            Route::get('/jobs/create', [ClientJobController::class, 'create'])->name('jobs.create');
            Route::post('/jobs', [ClientJobController::class, 'store'])->name('jobs.store');
            Route::get('/jobs/{job}/edit', [ClientJobController::class, 'edit'])->name('jobs.edit');
            Route::put('/jobs/{job}', [ClientJobController::class, 'update'])->name('jobs.update');
            Route::get('/jobs/{job}/publish', [ClientJobController::class, 'publish'])->name('jobs.publish');
            Route::post('/jobs/{job}/checkout', [ClientJobController::class, 'checkout'])->name('jobs.checkout');
            Route::post('/jobs/{job}/status', [ClientJobController::class, 'status'])->name('jobs.status');
            Route::get('/jobs/{job}/applicants', [ClientJobController::class, 'applicants'])->name('jobs.applicants');
            Route::get('/saved', [FreelancerDirectoryController::class, 'saved'])->name('saved');
            Route::post('/freelancers/{profile:slug}/shortlist', [FreelancerDirectoryController::class, 'toggleShortlist'])->name('shortlist');
            Route::post('/freelancers/{profile:slug}/invite', [InvitationController::class, 'store'])->name('invite');
        });

        // Shared hiring
        Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::post('/applications/{application}/status', [ApplicationController::class, 'status'])->name('applications.status');
        Route::post('/applications/{application}/conversation', [MessageController::class, 'startFromApplication'])->name('applications.conversation');
        Route::post('/applications/{application}/agreements', [ApplicationController::class, 'proposeAgreement'])->name('agreements.store');
        Route::post('/agreements/{agreement}/confirm', [ApplicationController::class, 'confirmAgreement'])->name('agreements.confirm');

        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{conversation}', [MessageController::class, 'store'])->middleware('throttle:messages')->name('messages.store');

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::post('/projects/{project}/status', [ProjectController::class, 'status'])->name('projects.status');
        Route::post('/projects/{project}/milestones/{milestone}', [ProjectController::class, 'milestone'])->name('projects.milestone');
        Route::post('/projects/{project}/files', [ProjectController::class, 'upload'])->middleware('throttle:uploads')->name('projects.files.store');
        Route::get('/project-files/{file}/download', [ProjectController::class, 'download'])->name('projects.files.download');
        Route::post('/project-files/{file}/receipt', [ProjectController::class, 'receipt'])->name('projects.files.receipt');
        Route::post('/projects/{project}/review', [ProjectController::class, 'review'])->name('projects.review');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read', [NotificationController::class, 'readAll'])->name('notifications.read');

        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/purchase/{product:code}', [BillingController::class, 'purchase'])->name('billing.purchase');
        Route::get('/billing/orders/{order}', [BillingController::class, 'order'])->name('billing.order');
        Route::get('/sandbox/checkout/{order}', [BillingController::class, 'sandbox'])->name('sandbox.checkout');
        Route::post('/sandbox/checkout/{order}', [BillingController::class, 'sandboxComplete'])->name('sandbox.complete');

        Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
        Route::post('/portfolio/{project}/like', [\App\Http\Controllers\FeedController::class, 'toggleLike'])->middleware('throttle:messages')->name('portfolio.like');

        // Admin console
        Route::middleware(['admin', 'admin.stepup'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('/verify', [A\SystemController::class, 'verify'])->name('verify');
            Route::post('/verify', [A\SystemController::class, 'confirmVerify'])->middleware('throttle:auth')->name('verify.confirm');

            Route::get('/', A\DashboardController::class)->name('overview');
            Route::middleware('admin.can:search')->group(function () {
                Route::get('/search', [A\SearchController::class, 'index'])->name('search');
                Route::get('/search/suggest', [A\SearchController::class, 'suggest'])->name('search.suggest');
            });

            // People
            Route::middleware('admin.can:users')->group(function () {
                Route::get('/users', [A\UserController::class, 'index'])->name('users');
                Route::get('/users/{user}', [A\UserController::class, 'show'])->name('users.show');
                Route::post('/users/{user}/notes', [A\UserController::class, 'note'])->name('users.note');
                Route::post('/users/{user}/status', [A\UserController::class, 'status'])->name('users.status');
                Route::post('/users/{user}/verify', [A\UserController::class, 'verifyClient'])->name('users.verify');
                Route::post('/users/{user}/plan', [A\UserController::class, 'plan'])->name('users.plan');
                Route::post('/users/{user}/feature', [A\UserController::class, 'feature'])->name('users.feature');
                Route::post('/users/{user}/impersonate', [A\UserController::class, 'impersonate'])->middleware('admin.can:impersonate')->name('users.impersonate');
            });
            Route::middleware('admin.can:team')->group(function () {
                Route::get('/team', [A\TeamController::class, 'index'])->name('team');
                Route::post('/team', [A\TeamController::class, 'store'])->name('team.store');
                Route::post('/team/{user}', [A\TeamController::class, 'update'])->name('team.update');
            });

            // Inbox
            Route::middleware('admin.can:reports')->group(function () {
                Route::get('/reports', [A\ReportController::class, 'index'])->name('reports');
                Route::post('/reports/{report}/assign', [A\ReportController::class, 'assign'])->name('reports.assign');
                Route::post('/reports/{report}/resolve', [A\ReportController::class, 'resolve'])->name('reports.resolve');
            });
            Route::middleware('admin.can:moderation')->group(function () {
                Route::get('/jobs', [A\ModerationController::class, 'jobs'])->name('jobs');
                Route::post('/jobs/{job}/moderate', [A\ModerationController::class, 'moderateJob'])->name('jobs.moderate');
                Route::get('/portfolio', [A\ModerationController::class, 'portfolio'])->name('portfolio');
                Route::post('/portfolio/approve-all', [A\ModerationController::class, 'approveAll'])->name('portfolio.approve-all');
                Route::post('/portfolio/{project}/moderate', [A\ModerationController::class, 'moderatePortfolio'])->name('portfolio.moderate');
            });
            Route::middleware('admin.can:verification')->group(function () {
                Route::get('/verification', [A\VerificationController::class, 'index'])->name('verification');
                Route::post('/verification/{verification}', [A\VerificationController::class, 'decide'])->name('verification.decide');
            });
            Route::middleware('admin.can:data_requests')->group(function () {
                Route::get('/data-requests', [A\DataRequestController::class, 'index'])->name('data-requests');
                Route::post('/data-requests/{dataRequest}', [A\DataRequestController::class, 'process'])->name('data-requests.process');
            });

            // Marketplace
            Route::get('/applications', [A\MarketplaceController::class, 'applications'])->middleware('admin.can:marketplace')->name('applications');
            Route::middleware('admin.can:agreements')->group(function () {
                Route::get('/agreements', [A\MarketplaceController::class, 'agreements'])->name('agreements');
                Route::get('/agreements/{project}', [A\MarketplaceController::class, 'showAgreement'])->name('agreements.show');
            });
            Route::get('/payment-detail-log', [A\MarketplaceController::class, 'disclosures'])->middleware('admin.can:disclosures')->name('disclosures');
            Route::middleware('admin.can:reviews')->group(function () {
                Route::get('/reviews', [A\MarketplaceController::class, 'reviews'])->name('reviews');
                Route::post('/reviews/{review}/toggle', [A\MarketplaceController::class, 'toggleReview'])->name('reviews.toggle');
            });

            // Money
            Route::middleware('admin.can:finance')->group(function () {
                Route::get('/orders', [A\FinanceController::class, 'orders'])->name('orders');
                Route::get('/orders/issues', [A\FinanceController::class, 'issues'])->name('orders.issues');
                Route::get('/orders/export', [A\FinanceController::class, 'export'])->name('orders.export');
                Route::post('/orders/{order}/refund', [A\FinanceController::class, 'refund'])->name('orders.refund');
                Route::get('/subscriptions', [A\FinanceController::class, 'subscriptions'])->name('subscriptions');
                Route::get('/plans', [A\FinanceController::class, 'products'])->name('products');
                Route::put('/plans/{product}', [A\FinanceController::class, 'updateProduct'])->name('products.update');
            });

            // Insights
            Route::middleware('admin.can:insights')->prefix('insights')->name('insights.')->group(function () {
                Route::get('/traffic', [A\InsightsController::class, 'traffic'])->name('traffic');
                Route::get('/content', [A\InsightsController::class, 'content'])->name('content');
                Route::get('/funnels', [A\InsightsController::class, 'funnels'])->name('funnels');
                Route::get('/marketplace', [A\InsightsController::class, 'marketplace'])->name('marketplace');
            });
            Route::middleware('admin.can:exports')->group(function () {
                Route::get('/insights/export', [A\InsightsController::class, 'export'])->name('insights.export');
                Route::get('/insights/export/{dataset}', [A\InsightsController::class, 'download'])->name('insights.download');
            });

            // Content
            Route::middleware('admin.can:content')->group(function () {
                Route::get('/categories', [A\ContentController::class, 'categories'])->name('categories');
                Route::post('/categories', [A\ContentController::class, 'storeCategory'])->name('categories.store');
                Route::put('/categories/{category}', [A\ContentController::class, 'updateCategory'])->name('categories.update');
                Route::delete('/categories/{category}', [A\ContentController::class, 'destroyCategory'])->name('categories.destroy');
                Route::post('/skills', [A\ContentController::class, 'storeSkill'])->name('skills.store');
                Route::delete('/skills/{skill}', [A\ContentController::class, 'destroySkill'])->name('skills.destroy');
                Route::get('/featured', [A\ContentController::class, 'featured'])->name('featured');
                Route::get('/announcement', [A\ContentController::class, 'announcement'])->name('announcement');
                Route::put('/announcement', [A\ContentController::class, 'updateAnnouncement'])->name('announcement.update');
            });

            // System
            Route::middleware('admin.can:settings')->group(function () {
                Route::get('/settings', [A\SystemController::class, 'settings'])->name('settings');
                Route::put('/settings', [A\SystemController::class, 'updateSettings'])->name('settings.update');
            });
            Route::get('/health', [A\SystemController::class, 'health'])->middleware('admin.can:health')->name('health');
            Route::get('/audit', [A\SystemController::class, 'audit'])->middleware('admin.can:audit')->name('audit');
        });
    });
});
