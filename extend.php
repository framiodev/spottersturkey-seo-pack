<?php

/**
 * Ulasim Info SEO Pack - Ana Yapılandırma Dosyası
 * Schedule hatası giderilmiş, stabil versiyon.
 */

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

use Flarum\Extend;

// Kontrolcüler
use SpottersTurkey\SeoPack\InjectSeoTags;
use SpottersTurkey\SeoPack\SitemapController;
use SpottersTurkey\SeoPack\BatchIndexController;
use SpottersTurkey\SeoPack\InspectController;
use SpottersTurkey\SeoPack\ContentListController;
use SpottersTurkey\SeoPack\DashboardStatsController;
use SpottersTurkey\SeoPack\WebAutoIndexController;
use SpottersTurkey\SeoPack\ImageIndexController;

// Konsol Komutları
use SpottersTurkey\SeoPack\Console\AutoIndexCommand;
use SpottersTurkey\SeoPack\Console\FixOldAltTagsCommand;


// Dinleyiciler
use SpottersTurkey\SeoPack\Listener\SendToGoogleConsole;
use SpottersTurkey\SeoPack\Listener\AutoAltTags;

// Flarum Olayları
use Flarum\Discussion\Event\Started;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Revised;
use Flarum\Post\Event\Saving;

return [
    (new Extend\Locales(__DIR__.'/resources/locale')),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js'),

    (new Extend\Routes('forum'))
        ->get('/sitemap.xml', 'ulasiminfo.seo.sitemap', SitemapController::class)
        ->get('/sitemap_index.xml', 'ulasiminfo.seo.sitemap.alt', SitemapController::class),

    (new Extend\Routes('api'))
        ->get('/seo/batch', 'seo.batch.index', BatchIndexController::class)
        ->get('/seo/inspect', 'seo.inspect', InspectController::class)
        ->get('/seo/content', 'seo.content', ContentListController::class)
        ->get('/seo/stats', 'seo.dashboard.stats', DashboardStatsController::class)
        ->get('/seo/images', 'seo.images.index', ImageIndexController::class)
        ->get('/seo/trigger-index', 'seo.trigger.index', WebAutoIndexController::class),

    (new Extend\Frontend('forum'))
        ->content(InjectSeoTags::class),

    (new Extend\Event())
        ->listen(Started::class, SendToGoogleConsole::class.'@whenDiscussionStarted')
        ->listen(Posted::class, SendToGoogleConsole::class.'@whenPostCreated')
        ->listen(Revised::class, SendToGoogleConsole::class.'@whenPostRevised'),

    // Konsol komutu aktif kalsın (Manuel çalıştırma için: php flarum seo:auto-index)
    (new Extend\Console())
        ->command(AutoIndexCommand::class)
        ->command(FixOldAltTagsCommand::class),
    // NOT: Otomatik zamanlama cron-job.org üzerinden /api/seo/trigger-index endpoint'i ile sağlanır.
];