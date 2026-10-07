<?php

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Models\Announcement;
use App\Models\Devotional;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Product;
use App\Models\Sermon;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

/**
 * Flips scheduled content to Published once its publish date has passed.
 * (Public queries already show due scheduled content; this keeps statuses accurate.)
 */
class PublishScheduledContent extends Command
{
    protected $signature = 'church:publish-scheduled';

    protected $description = 'Publish scheduled devotionals, sermons, events, galleries, pages, announcements and store products';

    public function handle(AuditLogger $audit): int
    {
        $published = 0;

        foreach ([Devotional::class, Sermon::class, Event::class, Gallery::class, Page::class, Announcement::class, Product::class] as $model) {
            $model::where('status', ContentStatus::Scheduled->value)
                ->where('published_at', '<=', now())
                ->each(function ($item) use ($audit, &$published) {
                    $item->update(['status' => ContentStatus::Published]);
                    $audit->log(AuditAction::Publish, $item, 'Scheduled content published: '.($item->title ?? $item->name ?? $item->getKey()));
                    $published++;
                });
        }

        $this->info("{$published} item(s) published.");

        return self::SUCCESS;
    }
}
