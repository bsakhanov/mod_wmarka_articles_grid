<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_wmarka_articles_grid
 *
 * @copyright   (C) 2026 Beibit Sakhanov <https://webmarka.kz>
 *              Article query logic is derived from the core module mod_articles,
 *              (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Webmarka\Module\ArticlesGrid\Site\Helper;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Event\Content;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_wmarka_articles_grid
 */
class ArticlesGridHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Retrieve a list of articles
     *
     * @param   Registry         $params  The module parameters.
     * @param   SiteApplication  $app     The current application.
     *
     * @return  object[]
     */
    public function getArticles(Registry $params, SiteApplication $app)
    {
        $factory = $app->bootComponent('com_content')->getMVCFactory();

        // Get an instance of the generic articles model
        $articles = $factory->createModel('Articles', 'Site', ['ignore_request' => true]);

        // Set application parameters in model
        $input     = $app->getInput();
        $appParams = $app->getParams();
        $articles->setState('params', $appParams);

        $articles->setState('list.start', 0);
        $articles->setState('filter.published', ContentComponent::CONDITION_PUBLISHED);

        // Set the filters based on the module params
        $articles->setState('list.limit', (int) $params->get('count', 0));
        $articles->setState('load_tags', $params->get('show_tags', 0) || $params->get('article_grouping', 'none') === 'tags' || !empty($params->get('filter_tag', [])) || ($params->get('frontend_filter', 0) == 1 && $params->get('frontend_filter_by', 'category') === 'tag'));

        // Get the user object
        $user = $app->getIdentity();

        // Access filter
        $access     = !ComponentHelper::getParams('com_content')->get('show_noauth');
        $authorised = Access::getAuthorisedViewLevels($user->id);
        $articles->setState('filter.access', $access);

        // Prep for Normal or Dynamic Modes
        $mode = $params->get('mode', 'normal');

        switch ($mode) {
            case 'dynamic':
                $option = $input->get('option');
                $view   = $input->get('view');

                if ($option === 'com_content') {
                    switch ($view) {
                        case 'category':
                        case 'categories':
                            $catids = [$input->getInt('id')];
                            break;
                        case 'article':
                            if ($params->get('show_on_article_page', 1)) {
                                $article_id = $input->getInt('id');
                                $catid      = $input->getInt('catid');

                                if (!$catid) {
                                    // Get an instance of the generic article model
                                    $article = $factory->createModel('Article', 'Site', ['ignore_request' => true]);

                                    $article->setState('params', $appParams);
                                    $article->setState('filter.published', 1);
                                    $article->setState('article.id', (int) $article_id);
                                    $item   = $article->getItem();
                                    $catids = [$item->catid];
                                } else {
                                    $catids = [$catid];
                                }
                            } else {
                                // Return right away if show_on_article_page option is off
                                return [];
                            }
                            break;

                        default:
                            // Return right away if not on the category or article views
                            return [];
                    }
                } else {
                    // Return right away if not on a com_content page
                    return [];
                }

                break;

            default:
                $catids = $params->get('catid');
                $articles->setState('filter.category_id.include', (bool) $params->get('category_filtering_type', 1));
                break;
        }

        // Category filter
        if ($catids) {
            if ($params->get('show_child_category_articles', 0) && (int) $params->get('levels', 0) > 0) {
                // Get an instance of the generic categories model
                $categories = $factory->createModel('Categories', 'Site', ['ignore_request' => true]);
                $categories->setState('params', $appParams);
                $levels = $params->get('levels', 1) ?: 9999;
                $categories->setState('filter.get_children', $levels);
                $categories->setState('filter.published', 1);
                $categories->setState('filter.access', $access);
                $additional_catids = [];

                foreach ($catids as $catid) {
                    $categories->setState('filter.parentId', $catid);
                    $recursive = true;
                    $items     = $categories->getItems($recursive);

                    if ($items) {
                        foreach ($items as $category) {
                            $condition = (($category->level - $categories->getParent()->level) <= $levels);

                            if ($condition) {
                                $additional_catids[] = $category->id;
                            }
                        }
                    }
                }

                $catids = array_unique(array_merge($catids, $additional_catids));
            }

            $articles->setState('filter.category_id', $catids);
        }

        // Ordering
        $ordering = $params->get('article_ordering', 'a.ordering');

        switch ($ordering) {
            case 'random':
                $articles->setState('list.ordering', $this->getDatabase()->createQuery()->rand());
                break;

            case 'rating_count':
            case 'rating':
                $articles->setState('list.ordering', $ordering);
                $articles->setState('list.direction', $params->get('article_ordering_direction', 'ASC'));

                if (!PluginHelper::isEnabled('content', 'vote')) {
                    $articles->setState('list.ordering', 'a.ordering');
                }

                break;

            default:
                $articles->setState('list.ordering', $ordering);
                $articles->setState('list.direction', $params->get('article_ordering_direction', 'ASC'));
                break;
        }

        // Filter by multiple tags
        $articles->setState('filter.tag', $params->get('filter_tag', []));

        // Filter by featured
        $articles->setState('filter.featured', $params->get('show_featured', 'show'));

        // Filter by author
        if ((int) $params->get('author_filtering_type', 1) === 2) {
            $articles->setState('filter.author_id', [$user->id]);
        } else {
            $articles->setState('filter.author_id', $params->get('created_by', []));
            $articles->setState('filter.author_id.include', $params->get('author_filtering_type', 1));
        }

        $articles->setState('filter.author_alias', $params->get('created_by_alias', []));
        $articles->setState('filter.author_alias.include', $params->get('author_alias_filtering_type', 1));

        // Filter archived articles
        if ($params->get('show_archived', 'hide') === 'show') {
            $articles->setState('filter.published', ContentComponent::CONDITION_ARCHIVED);
        }

        // Check if we include or exclude articles and process data
        $ex_or_include_articles = $params->get('ex_or_include_articles', 0);
        $filterInclude          = true;
        $articlesList           = [];
        $currentArticleId       = $input->get('id', 0, 'UINT');

        $isArticleAndShouldExcluded = $params->get('exclude_current', 1) === 1
            && $input->get('option') === 'com_content'
            && $input->get('view') === 'article';

        $articlesListToProcess = $params->get('included_articles', '');

        if ($ex_or_include_articles === 0) {
            $filterInclude = false;

            if ($isArticleAndShouldExcluded) {
                $articlesList[] = $currentArticleId;
            }

            $articlesListToProcess = $params->get('excluded_articles', '');
        }

        foreach (ArrayHelper::fromObject($articlesListToProcess) as $article) {
            if (
                $ex_or_include_articles === 1
                && $isArticleAndShouldExcluded
                && (int) $article['id'] === $currentArticleId
            ) {
                continue;
            }

            $articlesList[] = (int) $article['id'];
        }

        if (
            $ex_or_include_articles === 1
            && $isArticleAndShouldExcluded
            && empty($articlesList)
        ) {
            $filterInclude  = false;
            $articlesList[] = $currentArticleId;
        }

        if (!empty($articlesList)) {
            $articles->setState('filter.article_id', $articlesList);
            $articles->setState('filter.article_id.include', $filterInclude);
        }

        $date_filtering = $params->get('date_filtering', 'off');

        if ($date_filtering !== 'off') {
            $articles->setState('filter.date_filtering', $date_filtering);
            $articles->setState('filter.date_field', $params->get('date_field', 'a.created'));
            $articles->setState('filter.start_date_range', $params->get('start_date_range', '1000-01-01 00:00:00'));
            $articles->setState('filter.end_date_range', $params->get('end_date_range', '9999-12-31 23:59:59'));
            $articles->setState('filter.relative_date', $params->get('relative_date', 30));
        }

        // Filter by language
        $articles->setState('filter.language', $app->getLanguageFilter());

        $items = $articles->getItems();

        // Display options
        $show_date          = $params->get('show_date', 0);
        $show_date_field    = $params->get('show_date_field', 'created');
        $show_date_format   = $params->get('show_date_format', 'Y-m-d H:i:s');
        $show_category      = $params->get('show_category', 0);
        $show_category_link = $params->get('show_category_link', 0);
        $show_hits          = $params->get('show_hits', 0);
        $show_author        = $params->get('show_author', 0);
        $show_introtext     = $params->get('show_introtext', 1);
        $introtext_limit    = (int) $params->get('introtext_limit', 150);

        // Find current Article ID if on an article page
        $option = $input->get('option');
        $view   = $input->get('view');

        if ($option === 'com_content' && $view === 'article') {
            $active_article_id = $input->getInt('id');
        } else {
            $active_article_id = 0;
        }

        // Prepare data for display using display options
        foreach ($items as &$item) {
            $item->slug = $item->id . ':' . $item->alias;

            $articleLink = Route::_(RouteHelper::getArticleRoute($item->slug, $item->catid, $item->language));

            if ($access || \in_array($item->access, $authorised)) {
                // We know that user has the privilege to view the article
                $item->link = $articleLink;
            } else {
                $menu      = $app->getMenu();
                $menuitems = $menu->getItems('link', 'index.php?option=com_users&view=login');

                if (isset($menuitems[0])) {
                    $Itemid = $menuitems[0]->id;
                } elseif ($input->getInt('Itemid') > 0) {
                    $Itemid = $input->getInt('Itemid');
                }

                $return = base64_encode($articleLink);
                $item->link = Route::_('index.php?option=com_users&view=login&Itemid=' . ($Itemid ?? 0) . '&return=' . $return);
            }

            $item->event   = new \stdClass();

            // Check if we should trigger additional plugin events
            if ($params->get('trigger_events', 0)) {
                $dispatcher = Factory::getApplication()->getDispatcher();

                // Process the content plugins.
                PluginHelper::importPlugin('content', null, true, $dispatcher);

                $contentEventArguments = [
                    'context' => 'com_content.article',
                    'subject' => $item,
                    'params'  => $item->params,
                ];

                // Extra content from events
                $contentEvents = [
                    'afterDisplayTitle'    => new Content\AfterTitleEvent('onContentAfterTitle', $contentEventArguments),
                    'beforeDisplayContent' => new Content\BeforeDisplayEvent('onContentBeforeDisplay', $contentEventArguments),
                    'afterDisplayContent'  => new Content\AfterDisplayEvent('onContentAfterDisplay', $contentEventArguments),
                ];

                foreach ($contentEvents as $resultKey => $event) {
                    $results = $dispatcher->dispatch($event->getName(), $event)->getArgument('result', []);
                    $item->event->{$resultKey} = $results ? trim(implode("\n", $results)) : '';
                }
            } else {
                $item->event->afterDisplayTitle    = '';
                $item->event->beforeDisplayContent = '';
                $item->event->afterDisplayContent  = '';
            }

            // Used for styling the active article
            $item->active      = $item->id == $active_article_id ? 'active' : '';

            if ($show_date) {
                $item->displayDate = HTMLHelper::_('date', $item->$show_date_field, $show_date_format);
            }

            if ($show_category) {
                $item->displayCategoryTitle = $item->category_title;
            }

            if ($show_category_link) {
                $item->displayCategoryLink = Route::_(RouteHelper::getCategoryRoute($item->catid, $item->category_language));
            }

            $item->displayAuthorName    = $show_author ? $item->author : '';
            $item->displayCategoryTitle = $show_category ? $item->category_title : '';
            $item->displayCategoryLink  = $show_category_link ? $item->displayCategoryLink : '';
            $item->displayDate          = $show_date ? $item->displayDate : '';
            $item->displayHits          = $show_hits ? $item->hits : '';

            if ($show_introtext) {
                $item->displayIntrotext = HTMLHelper::_('content.prepare', $item->introtext, '', 'mod_articles.content');

                $item->displayIntrotext = self::formatIntro($item->displayIntrotext, $params);
            }

            // Show the Intro/Full image field of the article
            if ($params->get('img_intro_full', 'none') !== 'none') {
                $images             = (new Registry($item->images))->toObject();
                $item->imageSrc     = '';

                if ($params->get('img_intro_full') === 'intro' && !empty($images->image_intro)) {
                    $item->imageSrc       = htmlspecialchars($images->image_intro, ENT_COMPAT, 'UTF-8');
                    $images->float_intro .= ' mod-articles-image';
                } elseif ($params->get('img_intro_full') === 'full' && !empty($images->image_fulltext)) {
                    $item->imageSrc          = htmlspecialchars($images->image_fulltext, ENT_COMPAT, 'UTF-8');
                    $images->float_fulltext .= ' mod-articles-image';
                }

                $item->images = json_encode($images);
            }

            $item->displayReadmore  = $item->alternative_readmore;
        }

        // Check if items need be grouped
        $article_grouping           = $params->get('article_grouping', 'none');
        $article_grouping_direction = $params->get('article_grouping_direction', 'ksort');
        $grouped                    = $article_grouping !== 'none';

        if ($items && $grouped) {
            switch ($article_grouping) {
                case 'year':
                case 'month_year':
                    $items = ArticlesGridHelper::groupByDate(
                        $items,
                        $article_grouping_direction,
                        $article_grouping,
                        $params->get('month_year_format', 'F Y'),
                        $params->get('date_grouping_field', 'created')
                    );
                    break;
                case 'author':
                case 'category_title':
                    $items = ArticlesGridHelper::groupBy($items, $article_grouping, $article_grouping_direction);
                    break;
                case 'tags':
                    $items = ArticlesGridHelper::groupByTags($items, $article_grouping_direction);
                    break;
            }
        }

        return $items;
    }

    /**
     * Groups items by field
     *
     * @param   array   $list             list of items
     * @param   string  $fieldName        name of field that is used for grouping
     * @param   string  $direction        ordering direction
     * @param   null    $fieldNameToKeep  field name to keep
     *
     * @return  array
     */
    public static function groupBy($list, $fieldName, $direction, $fieldNameToKeep = null)
    {
        $grouped = [];

        if (!\is_array($list)) {
            if ($list === '') {
                return $grouped;
            }

            $list = [$list];
        }

        foreach ($list as $key => $item) {
            if (!isset($grouped[$item->$fieldName])) {
                $grouped[$item->$fieldName] = [];
            }

            if ($fieldNameToKeep === null) {
                $grouped[$item->$fieldName][$key] = $item;
            } else {
                $grouped[$item->$fieldName][$key] = $item->$fieldNameToKeep;
            }

            unset($list[$key]);
        }

        $direction($grouped);

        return $grouped;
    }

    /**
     * Groups items by date
     *
     * @param   array   $list             list of items
     * @param   string  $direction        ordering direction
     * @param   string  $type             type of grouping
     * @param   string  $monthYearFormat  date format to use
     * @param   string  $field            date field to group by
     *
     * @return  array
     */
    public static function groupByDate($list, $direction = 'ksort', $type = 'year', $monthYearFormat = 'F Y', $field = 'created')
    {
        $grouped = [];

        if (!\is_array($list)) {
            if ($list === '') {
                return $grouped;
            }

            $list = [$list];
        }

        foreach ($list as $key => $item) {
            switch ($type) {
                case 'month_year':
                    $month_year = StringHelper::substr($item->$field, 0, 7);

                    if (!isset($grouped[$month_year])) {
                        $grouped[$month_year] = [];
                    }

                    $grouped[$month_year][$key] = $item;
                    break;

                default:
                    $year = StringHelper::substr($item->$field, 0, 4);

                    if (!isset($grouped[$year])) {
                        $grouped[$year] = [];
                    }

                    $grouped[$year][$key] = $item;
                    break;
            }

            unset($list[$key]);
        }

        $direction($grouped);

        if ($type === 'month_year') {
            foreach ($grouped as $group => $items) {
                $date                      = new Date($group);
                $formatted_group           = $date->format($monthYearFormat);
                $grouped[$formatted_group] = $items;

                unset($grouped[$group]);
            }
        }

        return $grouped;
    }

    /**
     * Groups items by tags
     *
     * @param   array   $list       list of items
     * @param   string  $direction  ordering direction
     *
     * @return  array
     */
    public static function groupByTags($list, $direction = 'ksort')
    {
        $grouped  = [];
        $untagged = [];

        if (!$list) {
            return $grouped;
        }

        foreach ($list as $item) {
            if ($item->tags->itemTags) {
                foreach ($item->tags->itemTags as $tag) {
                    $grouped[$tag->title][] = $item;
                }
            } else {
                $untagged[] = $item;
            }
        }

        $direction($grouped);

        if ($untagged) {
            $grouped['MOD_WMARKA_ARTICLES_GRID_UNTAGGED'] = $untagged;
        }

        return $grouped;
    }

    /**
     * JUImage instance, or null when the library is not installed.
     * The library ships its own Composer autoloader in libraries/juimage/vendor.
     *
     * @return  object|null
     */
    public static function getJuImage(): ?object
    {
        static $instance = false;

        if ($instance === false) {
            $instance = null;
            $autoload = JPATH_LIBRARIES . '/juimage/vendor/autoload.php';

            if (is_file($autoload)) {
                require_once $autoload;
            }

            if (class_exists('JUImage\\Image')) {
                $instance = new \JUImage\Image();
            }
        }

        return $instance;
    }

    /**
     * First available article image, cleaned of the #joomlaImage:// suffix.
     *
     * @param   object  $item   Article
     * @param   array   $order  Image fields in order of preference: intro, fulltext
     *
     * @return  array  ['src' => string, 'alt' => string]
     */
    public static function articleImage(object $item, array $order = ['intro', 'fulltext']): array
    {
        $images = json_decode((string) ($item->images ?? ''), true) ?: [];

        foreach ($order as $type) {
            $key = 'image_' . $type;

            if (!empty($images[$key])) {
                $clean = HTMLHelper::_('cleanImageURL', $images[$key]);

                return [
                    'src'    => (string) $clean->url,
                    'alt'    => (string) ($images[$key . '_alt'] ?? ''),
                    'width'  => (int) ($clean->attributes['width'] ?? 0) ?: null,
                    'height' => (int) ($clean->attributes['height'] ?? 0) ?: null,
                ];
            }
        }

        return ['src' => '', 'alt' => '', 'width' => null, 'height' => null];
    }

    /**
     * Thumbnail path made by JUImage, or an empty string when JUImage is unavailable.
     * Options follow JUImage 5.x (phpThumb): w, h, q, f, zc (zoom-crop), cache.
     *
     * @param   object|null  $juImg    JUImage instance
     * @param   string       $src      Image path relative to the site root
     * @param   array        $options  JUImage options
     *
     * @return  string
     */
    public static function thumb(?object $juImg, string $src, array $options): string
    {
        if ($juImg === null || $src === '') {
            return '';
        }

        try {
            $thumb = $juImg->render($src, $options + ['cache' => 'img']);
        } catch (\Throwable $e) {
            return '';
        }

        if (\is_object($thumb)) {
            $thumb = $thumb->img ?? '';
        }

        return ltrim((string) $thumb, '/');
    }

    /**
     * Root-relative URL for a path stored in Joomla; absolute URLs pass through.
     *
     * @param   string  $path  Path or URL
     *
     * @return  string
     */
    public static function url(string $path): string
    {
        if ($path === '' || preg_match('#^(?:[a-z][a-z0-9+.\-]*:)?//#i', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        return Uri::root(true) . '/' . ltrim($path, '/');
    }

    /**
     * CSS classes used by the UIkit front-end filter.
     *
     * @param   object  $item  Article
     *
     * @return  string
     */
    public static function filterClasses(object $item): string
    {
        $created = (string) ($item->created ?? '');
        $classes = [
            'f-category-' . (int) $item->catid,
            'f-author-' . (int) $item->created_by,
            'f-year-' . StringHelper::substr($created, 0, 4),
            'f-month-' . StringHelper::substr($created, 0, 7),
        ];

        if (!empty($item->tags->itemTags)) {
            foreach ($item->tags->itemTags as $tag) {
                $classes[] = 'f-tag-' . (int) $tag->id;
            }
        }

        return implode(' ', $classes);
    }

    /**
     * Buttons of the UIkit front-end filter: [class suffix => label].
     *
     * @param   array   $items  Articles
     * @param   string  $by     category | author | year | month | tag
     *
     * @return  array
     */
    public static function filterOptions(array $items, string $by): array
    {
        $options = [];

        foreach ($items as $item) {
            $created = (string) ($item->created ?? '');

            switch ($by) {
                case 'category':
                    $options[(int) $item->catid] ??= (string) $item->category_title;
                    break;

                case 'author':
                    $options[(int) $item->created_by] ??= (string) $item->author;
                    break;

                case 'year':
                    $year = StringHelper::substr($created, 0, 4);
                    $options[$year] ??= $year;
                    break;

                case 'month':
                    $month = StringHelper::substr($created, 0, 7);
                    $options[$month] ??= (new Date($created))->format('F Y');
                    break;

                case 'tag':
                    foreach ($item->tags->itemTags ?? [] as $tag) {
                        $options[(int) $tag->id] ??= (string) $tag->title;
                    }
                    break;
            }
        }

        if ($by === 'year' || $by === 'month') {
            krsort($options);
        } else {
            asort($options);
        }

        return $options;
    }

    /**
     * rgba() colour for the overlay, or an empty string for an invalid colour.
     *
     * @param   string  $hex      #RGB or #RRGGBB
     * @param   mixed   $opacity  0..1
     *
     * @return  string
     */
    public static function rgba(string $hex, $opacity): string
    {
        $hex = ltrim(trim($hex), '#');

        if (\strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return '';
        }

        $alpha = max(0, min(1, (float) str_replace(',', '.', (string) $opacity)));
        $alpha = rtrim(rtrim(number_format($alpha, 2, '.', ''), '0'), '.');

        return sprintf('rgba(%d, %d, %d, %s)', hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)), $alpha);
    }

    /** UIkit version loaded from the CDN when the page has none. */
    public const UIKIT_VERSION = '3.25.26';

    /** jsDelivr copy of the official UIkit npm package. */
    public const UIKIT_CDN = 'https://cdn.jsdelivr.net/npm/uikit@3.25.26/dist';

    /** Subresource Integrity: sha384 of the files of the UIkit 3.25.26 npm package. */
    private const UIKIT_SRI = [
        'css/uikit.min.css'     => 'sha384-eFPwc2rkuXQtvGq9zLh3WjwPrzoQsn1/47gOoXbAOt6NJeYXJmvq49HQ8/LQ4JJ4',
        'js/uikit.min.js'       => 'sha384-eQMI/oQqbrMXR6Xo22oh6Jaj+bC7D9P/iZP/yOo0udvit+Xge7/6Y6fdsSrqKaRK',
        'js/uikit-icons.min.js' => 'sha384-3Xg8K3xVxV/rrbK3hJP/81rHeyZXEGmj72OmFFebDHtp94obtG6sm+0Mhs2Nniox',
    ];

    /**
     * Makes sure UIkit is on the page.
     *
     * auto: the template's UIkit is used; when the page has none, UIkit comes from the CDN.
     *       The server looks for UIkit among the page assets; if it finds nothing, a small
     *       script checks window.UIkit in the browser after the template's scripts have run.
     * cdn:  UIkit is always loaded from the CDN in the page head.
     * none: the template is responsible for UIkit.
     *
     * @param   Registry  $params  Module parameters
     *
     * @return  void
     */
    public static function loadUikit(Registry $params): void
    {
        $mode     = (string) $params->get('uikit_load', 'auto');
        $document = Factory::getApplication()->getDocument();

        if ($mode === 'none' || !$document instanceof HtmlDocument) {
            return;
        }

        if ($mode === 'auto' && self::uikitOnPage($document)) {
            return;
        }

        $base  = rtrim(trim((string) $params->get('uikit_cdn', '')), '/') ?: self::UIKIT_CDN;
        $files = [];

        foreach (self::UIKIT_SRI as $file => $hash) {
            // Integrity hashes are valid only for the pinned default CDN files.
            $files[$file] = [$base . '/' . $file, $base === self::UIKIT_CDN ? ['integrity' => $hash, 'crossorigin' => 'anonymous'] : []];
        }

        $wa = $document->getWebAssetManager();

        if ($mode === 'cdn') {
            [$css, $cssAttr]     = $files['css/uikit.min.css'];
            [$js, $jsAttr]       = $files['js/uikit.min.js'];
            [$icons, $iconsAttr] = $files['js/uikit-icons.min.js'];

            // 'version' => '' keeps Joomla's media version query off the CDN addresses.
            $wa->registerAndUseStyle('wmarka.uikit', $css, ['version' => ''], $cssAttr)
                ->registerAndUseScript('wmarka.uikit', $js, ['version' => ''], ['defer' => true] + $jsAttr)
                ->registerAndUseScript('wmarka.uikit-icons', $icons, ['version' => ''], ['defer' => true] + $iconsAttr, ['wmarka.uikit']);

            return;
        }

        $config = [
            'css'   => ['href' => $files['css/uikit.min.css'][0]] + $files['css/uikit.min.css'][1],
            'js'    => ['src' => $files['js/uikit.min.js'][0]] + $files['js/uikit.min.js'][1],
            'icons' => ['src' => $files['js/uikit-icons.min.js'][0]] + $files['js/uikit-icons.min.js'][1],
        ];

        $wa->addInlineScript(
            '(function(d,w){if(w.WmarkaUikitLoader){return;}w.WmarkaUikitLoader=1;'
            . 'var f=' . json_encode($config, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . ';'
            . 'function put(t,a){var e=d.createElement(t);for(var k in a){e.setAttribute(k,a[k]);}if(t==="script"){e.async=false;}else{e.rel="stylesheet";}d.head.appendChild(e);}'
            . 'function run(){if(w.UIkit){return;}put("link",f.css);put("script",f.js);put("script",f.icons);}'
            . 'if(d.readyState==="loading"){d.addEventListener("DOMContentLoaded",run);}else{run();}'
            . '})(document,window);',
            ['name' => 'wmarka.uikit.loader']
        );
    }

    /**
     * Whether the page already has UIkit: a WebAssetManager asset or a document script/style
     * with "uikit" in its name or address.
     *
     * @param   HtmlDocument  $document  Current document
     *
     * @return  boolean
     */
    private static function uikitOnPage(HtmlDocument $document): bool
    {
        $wa = $document->getWebAssetManager();

        foreach (['script', 'style'] as $type) {
            foreach ($wa->getAssets($type) as $asset) {
                if (stripos($asset->getName() . ' ' . $asset->getUri(false), 'uikit') !== false) {
                    return true;
                }
            }
        }

        foreach (array_merge(array_keys($document->_scripts), array_keys($document->_styleSheets)) as $url) {
            if (stripos((string) $url, 'uikit') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Preview image of an article, as the module options ask for it.
     *
     * Source: the intro or the full image of the article (image_source), with or without a fallback
     * to the other one (image_fallback). Processing (image_processing): JUImage thumbnails in WebP,
     * with srcset widths and a phone source, or the original file as it is — then no thumbnail is made.
     *
     * @param   object       $item     Article
     * @param   Registry     $params   Module options
     * @param   object|null  $juImg    JUImage instance
     * @param   string       $sizes    sizes attribute for the srcset
     * @param   string       $default   Default source: intro | full
     * @param   array        $defaults  Defaults of img_w, img_h, mob_w, mob_h as in the module manifest
     *
     * @return  array  [] when there is no image; else src, width, height, alt, srcset, sizes, mobile, mobileSizes, thumb
     */
    public static function picture(object $item, Registry $params, ?object $juImg, string $sizes = '100vw', string $default = 'intro', array $defaults = []): array
    {
        $defaults += ['img_w' => 800, 'img_h' => 0, 'mob_w' => 0, 'mob_h' => 0];

        $order = $params->get('image_source', $default) === 'full' ? ['fulltext', 'intro'] : ['intro', 'fulltext'];
        $image = self::articleImage($item, $params->get('image_fallback', 1) ? $order : [$order[0]]);

        if ($image['src'] === '') {
            return [];
        }

        $isRemote = (bool) preg_match('#^(?:[a-z][a-z0-9+.\-]*:)?//#i', $image['src']);
        [$ow, $oh] = $isRemote ? [0, 0] : self::imageSize($image['src'], $image['width'], $image['height']);

        $result = [
            'src'         => self::url($image['src']),
            'width'       => $ow ?: null,
            'height'      => $oh ?: null,
            'alt'         => $image['alt'] !== '' ? $image['alt'] : (string) $item->title,
            'srcset'      => '',
            'sizes'       => '',
            'mobile'      => '',
            'mobileSizes' => '',
            'thumb'       => false,
        ];

        if ($juImg === null || $isRemote || $params->get('image_processing', 'juimage') !== 'juimage') {
            return $result;
        }

        $w = max(1, (int) $params->get('img_w', $defaults['img_w']));
        $h = max(0, (int) $params->get('img_h', $defaults['img_h']));
        $q = (int) $params->get('img_q', 70);

        // Height follows the crop ratio, or the photo's own proportions when the height is empty.
        $ratio = $h > 0 ? $h / $w : ($ow > 0 ? $oh / $ow : 0);
        $make  = static function (int $width, int $height) use ($juImg, $image, $q): string {
            $options = $height > 0
                ? ['w' => $width, 'h' => $height, 'q' => $q, 'f' => 'webp', 'zc' => 1]
                : ['w' => $width, 'q' => $q, 'f' => 'webp'];

            return self::thumb($juImg, $image['src'], $options);
        };

        $main = $make($w, $h);

        if ($main === '') {
            return $result;
        }

        $result['src']    = self::url($main);
        $result['thumb']  = true;
        $result['width']  = $w;
        $result['height'] = $ratio > 0 ? (int) round($w * $ratio) : null;

        $useSrcset = (bool) $params->get('srcset', 1);

        if ($useSrcset) {
            $set = [];

            foreach (self::widths((string) $params->get('srcset_widths', '400, 800, 1200'), $w, $ow) as $width) {
                $file = $width === $w ? $main : $make($width, $h > 0 ? (int) round($width * $h / $w) : 0);

                if ($file !== '') {
                    $set[$width] = self::url($file) . ' ' . $width . 'w';
                }
            }

            if (\count($set) > 1) {
                ksort($set);
                $result['srcset'] = implode(', ', $set);
                $result['sizes']  = $sizes;
            }
        }

        // Phone source: its own size (art direction), one and two device pixels per CSS pixel.
        $mw = (int) $params->get('mob_w', $defaults['mob_w']);
        $mh = (int) $params->get('mob_h', $defaults['mob_h']);

        if ($mw > 0 && ($h === 0 || $mh > 0)) {
            $set = [];

            foreach ($useSrcset ? [$mw, $mw * 2] : [$mw] as $width) {
                if ($width !== $mw && $ow > 0 && $width > $ow) {
                    continue;
                }

                $file = $make($width, $mh > 0 ? (int) round($width * $mh / $mw) : 0);

                if ($file !== '') {
                    $set[$width] = self::url($file) . ' ' . $width . 'w';
                }
            }

            if (\count($set) > 1) {
                $result['mobile']      = implode(', ', $set);
                $result['mobileSizes'] = '100vw';
            } elseif ($set) {
                $result['mobile'] = explode(' ', reset($set))[0];
            }
        }

        return $result;
    }

    /**
     * <picture> (or a single <img>) for a picture() result.
     *
     * @param   array  $picture     picture() result
     * @param   array  $attributes  Extra <img> attributes; true renders a bare attribute
     *
     * @return  string
     */
    public static function pictureTag(array $picture, array $attributes = []): string
    {
        $esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $img = ['src' => $picture['src']];

        if ($picture['srcset'] !== '') {
            $img['srcset'] = $picture['srcset'];
            $img['sizes']  = $picture['sizes'];
        }

        $img += array_filter(['width' => $picture['width'], 'height' => $picture['height']]);
        $img += ['alt' => $picture['alt'], 'loading' => 'lazy', 'decoding' => 'async'];
        $img  = array_merge($img, $attributes);
        $tag  = '<img';

        foreach ($img as $name => $value) {
            if ($value === true) {
                $tag .= ' ' . $name;
            } elseif ($value !== false && $value !== null) {
                $tag .= ' ' . $name . '="' . $esc($value) . '"';
            }
        }

        $tag .= '>';

        if ($picture['mobile'] === '') {
            return $tag;
        }

        return '<picture><source media="(max-width: 639px)" srcset="' . $esc($picture['mobile']) . '"'
            . ($picture['mobileSizes'] !== '' ? ' sizes="' . $esc($picture['mobileSizes']) . '"' : '') . '>' . $tag . '</picture>';
    }

    /**
     * Full image of an article (full, then intro), the original file: for the lightbox, modals, SEO.
     *
     * @param   object  $item  Article
     *
     * @return  array  src (root-relative), width, height, alt; src is '' when there is no image
     */
    public static function fullImage(object $item): array
    {
        $image = self::articleImage($item, ['fulltext', 'intro']);

        if ($image['src'] === '') {
            return ['src' => '', 'width' => null, 'height' => null, 'alt' => ''];
        }

        $isRemote = (bool) preg_match('#^(?:[a-z][a-z0-9+.\-]*:)?//#i', $image['src']);
        [$w, $h]  = $isRemote ? [0, 0] : self::imageSize($image['src'], $image['width'], $image['height']);

        return ['src' => self::url($image['src']), 'width' => $w ?: null, 'height' => $h ?: null, 'alt' => $image['alt'] !== '' ? $image['alt'] : (string) $item->title];
    }

    /**
     * Pixel size of an image stored on the site: from Joomla's #joomlaImage data, else from the file.
     *
     * @return  array  [width, height], [0, 0] when unknown
     */
    public static function imageSize(string $src, ?int $width = null, ?int $height = null): array
    {
        if ($width && $height) {
            return [$width, $height];
        }

        $file = JPATH_ROOT . '/' . ltrim(rawurldecode(parse_url($src, PHP_URL_PATH) ?: ''), '/');
        $size = is_file($file) ? @getimagesize($file) : false;

        return $size ? [(int) $size[0], (int) $size[1]] : [0, 0];
    }

    /**
     * srcset widths: the list from the options plus the main width, never wider than the original
     * (JUImage would enlarge it), at most six.
     *
     * @return  int[]
     */
    public static function widths(string $list, int $main, int $original = 0): array
    {
        $widths = [$main];

        foreach (preg_split('/[^0-9]+/', $list, -1, PREG_SPLIT_NO_EMPTY) as $width) {
            $width = (int) $width;

            if ($width >= 100 && $width <= 4000 && ($original === 0 || $width <= $original)) {
                $widths[] = $width;
            }
        }

        $widths = array_values(array_unique($widths));
        sort($widths);

        return \array_slice($widths, 0, 6);
    }

    /**
     * sizes attribute from UIkit child-width classes (uk-child-width-1-3@m and so on).
     *
     * @param   array  $classes   Column classes
     * @param   float  $fraction  Share of a column the image takes from the @s breakpoint on
     *
     * @return  string
     */
    public static function sizes(array $classes, float $fraction = 1.0): string
    {
        $breakpoints = ['xl' => 1600, 'l' => 1200, 'm' => 960, 's' => 640];
        $columns     = ['' => 1];

        foreach ($classes as $class) {
            foreach (preg_split('/\s+/', (string) $class) as $one) {
                if (preg_match('/^uk-child-width-1-(\d)(?:@(s|m|l|xl))?$/', $one, $m)) {
                    $columns[$m[2] ?? ''] = (int) $m[1];
                }
            }
        }

        $parts = [];

        foreach ($breakpoints as $key => $px) {
            if (isset($columns[$key])) {
                $parts[] = '(min-width: ' . $px . 'px) ' . round(100 / $columns[$key] * $fraction, 2) . 'vw';
            }
        }

        $parts[] = round(100 / $columns[''], 2) . 'vw';

        return implode(', ', $parts);
    }

    /**
     * Intro text for a card: plain text or HTML, cut by characters or by words.
     * Pictures inside the intro text are always dropped: the module shows its own image.
     *
     * @param   string    $html    Intro text after the content plugins
     * @param   Registry  $params  Module options
     *
     * @return  string  Safe HTML
     */
    public static function formatIntro(string $html, Registry $params): string
    {
        $limit = max(0, (int) $params->get('introtext_limit', 150));
        $words = $params->get('introtext_truncate', 'chars') === 'words';
        $html  = preg_replace(['/<img[^>]*>/i', '/<a[^>]*>\s*<\/a>/i'], '', $html);

        if ($params->get('introtext_format', 'plain') === 'plain') {
            $text = self::plainText($html);

            if ($limit > 0) {
                $text = $words ? self::cutWords($text, $limit) : self::cutChars($text, $limit);
            }

            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }

        if ($limit === 0) {
            return $html;
        }

        if ($words) {
            $all = preg_split('/\s+/u', self::plainText($html), -1, PREG_SPLIT_NO_EMPTY);

            if (\count($all) <= $limit) {
                return $html;
            }

            // truncateComplex() counts characters: cut at the length of the first N words.
            $limit = StringHelper::strlen(implode(' ', \array_slice($all, 0, $limit))) + 1;
        }

        return HTMLHelper::_('string.truncateComplex', $html, $limit);
    }

    /**
     * Text without tags and entities, on one line; neighbouring paragraphs keep a space between them.
     */
    public static function plainText(string $html): string
    {
        $text = strip_tags(preg_replace('/<[^>]+>/', ' $0', $html));

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /**
     * First characters of a text, cut at a word boundary, with an ellipsis.
     */
    public static function cutChars(string $text, int $limit): string
    {
        if (StringHelper::strlen($text) <= $limit) {
            return $text;
        }

        $cut   = StringHelper::substr($text, 0, $limit + 1);
        $space = StringHelper::strrpos($cut, ' ');
        $cut   = $space !== false && $space > $limit * 0.6 ? StringHelper::substr($cut, 0, $space) : StringHelper::substr($cut, 0, $limit);

        return preg_replace('/[\s,.;:—–-]+$/u', '', $cut) . '…';
    }

    /**
     * First words of a text, with an ellipsis.
     */
    public static function cutWords(string $text, int $limit): string
    {
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (\count($words) <= $limit) {
            return $text;
        }

        return preg_replace('/[,.;:—–-]+$/u', '', implode(' ', \array_slice($words, 0, $limit))) . '…';
    }

    /**
     * class and style attributes of the intro text: UIkit size, colour, style, alignment, extra
     * classes, and an optional clamp to N lines (UIkit has no class for it, the CSS line-clamp does it).
     *
     * @return  string  Ready attributes, starting with a space
     */
    public static function introAttributes(Registry $params, string $extra = '', int $defaultClamp = 0): string
    {
        $classes = trim(implode(' ', array_filter([
            (string) $params->get('intro_size', 'uk-text-small'),
            (string) $params->get('intro_color', 'uk-text-muted'),
            (string) $params->get('intro_style', ''),
            (string) $params->get('intro_align', ''),
            trim((string) $params->get('intro_classes', '')),
            $extra,
        ])));
        $lines = max(0, (int) $params->get('intro_clamp', 0)) ?: $defaultClamp;
        $attr  = $classes !== '' ? ' class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '"' : '';

        if ($lines > 0) {
            $attr .= ' style="display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:' . $lines . ';line-clamp:' . $lines . ';overflow:hidden"';
        }

        return $attr;
    }

    /**
     * Classes of a card title: extra classes plus a UIkit size (uk-h1…uk-h6, uk-heading-*, uk-text-lead…).
     */
    public static function titleClass(Registry $params, string $defaultExtra = 'uk-card-title uk-link-heading', string $defaultSize = ''): string
    {
        $size    = trim((string) $params->get('item_heading_size', $defaultSize));
        $classes = preg_split('/\s+/', trim((string) $params->get('title_classes', $defaultExtra)), -1, PREG_SPLIT_NO_EMPTY);

        // uk-card-title comes later in the UIkit CSS and would override any size class.
        if ($size !== '') {
            $classes = array_diff($classes, ['uk-card-title']);
        }

        return trim(implode(' ', $classes) . ' ' . $size);
    }

    /**
     * Search engine and social network data for the module: JSON-LD ItemList and og:image.
     *
     * @param   array     $list    Articles, or [group => articles]
     * @param   Registry  $params  Module options
     * @param   object    $module  Module record
     * @param   string    $kind    article: list of articles; image: list of photos (ImageObject)
     *
     * @return  void
     */
    public static function seo(array $list, Registry $params, object $module, string $kind = 'article'): void
    {
        $app      = Factory::getApplication();
        $document = $app->getDocument();

        if (!$document instanceof HtmlDocument) {
            return;
        }

        $items = [];

        foreach ($list as $entry) {
            foreach (\is_array($entry) ? $entry : [$entry] as $item) {
                $items[(int) $item->id] = $item;
            }
        }

        $origin   = Uri::getInstance()->toString(['scheme', 'user', 'pass', 'host', 'port']);
        $absolute = static function (string $url) use ($origin): string {
            $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');

            if ($url === '' || preg_match('#^[a-z][a-z0-9+.\-]*://#i', $url)) {
                return $url;
            }

            return str_starts_with($url, '//') ? Uri::getInstance()->getScheme() . ':' . $url : $origin . '/' . ltrim($url, '/');
        };

        if ($params->get('structured_data', 'jsonld') === 'jsonld' && $items) {
            $elements = [];

            foreach ($items as $item) {
                $image = self::fullImage($item);

                if ($kind === 'image') {
                    if ($image['src'] === '') {
                        continue;
                    }

                    $element = [
                        '@type'    => 'ListItem',
                        'position' => \count($elements) + 1,
                        'url'      => $absolute((string) $item->link),
                        'item'     => array_filter([
                            '@type'      => 'ImageObject',
                            'contentUrl' => $absolute($image['src']),
                            'name'       => (string) $item->title,
                            'caption'    => self::cutChars(self::plainText((string) ($item->introtext ?? '')), 200),
                            'width'      => $image['width'],
                            'height'     => $image['height'],
                        ]),
                    ];
                } else {
                    $element = array_filter([
                        '@type'    => 'ListItem',
                        'position' => \count($elements) + 1,
                        'url'      => $absolute((string) $item->link),
                        'name'     => (string) $item->title,
                        'image'    => $image['src'] !== '' ? $absolute($image['src']) : null,
                    ]);
                }

                $elements[] = $element;
            }

            if ($elements) {
                $data = [
                    '@context'        => 'https://schema.org',
                    '@type'           => 'ItemList',
                    'name'            => trim((string) $params->get('custom_header', '')) ?: (string) $module->title,
                    'numberOfItems'   => \count($elements),
                    'itemListElement' => $elements,
                ];

                $document->getWebAssetManager()->addInlineScript(
                    json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG),
                    ['name' => 'wmarka.jsonld.' . (int) $module->id],
                    ['type' => 'application/ld+json']
                );
            }
        }

        $og = (string) $params->get('og_images', 'auto');

        if ($og === 'off' || !$items) {
            return;
        }

        if ($og === 'auto') {
            $input = $app->getInput();

            // An article page has its own picture; a page that already has og:image keeps it.
            if ($input->get('option') === 'com_content' && $input->get('view') === 'article') {
                return;
            }

            if ($document->getMetaData('og:image', 'property') !== '') {
                return;
            }

            foreach ($document->_custom as $tag) {
                if (stripos((string) $tag, 'og:image') !== false) {
                    return;
                }
            }
        }

        $left = max(1, min(10, (int) $params->get('og_count', 1)));

        foreach ($items as $item) {
            $image = self::fullImage($item);

            if ($image['src'] === '') {
                continue;
            }

            $tags = ['og:image' => $absolute($image['src']), 'og:image:width' => $image['width'], 'og:image:height' => $image['height'], 'og:image:alt' => $image['alt']];

            foreach ($tags as $property => $value) {
                if ($value !== null && $value !== '') {
                    $document->addCustomTag('<meta property="' . $property . '" content="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">');
                }
            }

            if (--$left === 0) {
                break;
            }
        }
    }
}
