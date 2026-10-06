<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_wmarka_articles_grid
 *
 * @copyright   (C) 2026 Beibit Sakhanov <https://webmarka.kz>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Webmarka\Module\ArticlesGrid\Site\Helper\ArticlesGridHelper;

/**
 * Prepared by the Dispatcher:
 *
 * @var \stdClass                 $module
 * @var \Joomla\Registry\Registry $params
 * @var array                     $list         articles, or [group title => articles] when $grouped
 * @var bool                      $grouped
 * @var string                    $moduleClass
 * @var object|null               $juImg
 * @var string                    $blendMode
 * @var string                    $overlayRgba
 */

$esc       = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$headings  = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div'];
$sublayout = static function (string $suffix) use ($module, $params): string {
    $path = ModuleHelper::getLayoutPath($module->module, $params->get('layout', 'default') . '_' . $suffix);

    // An alternative layout without its own sublayout falls back to the default one, never to default.php itself.
    return basename($path) === 'default.php' ? ModuleHelper::getLayoutPath($module->module, 'default_' . $suffix) : $path;
};

$customHeader = trim((string) $params->get('custom_header', ''));
$headerLink   = trim((string) $params->get('header_link', ''));
$headerTag    = \in_array($params->get('header_tag', 'h2'), $headings, true) ? $params->get('header_tag', 'h2') : 'h2';
$headerClass  = trim($params->get('header_class', 'uk-heading-line') . ' ' . $params->get('header_size', '') . ($params->get('header_link_class', 1) ? ' uk-link-heading' : ''));

$gridClass = trim(implode(' ', array_filter([
    $params->get('grid_gutter', 'uk-grid-medium'),
    $params->get('grid_match', 1) ? 'uk-grid-match' : '',
    'uk-child-width-1-1',
    $params->get('cols_s', 'uk-child-width-1-2@s'),
    $params->get('cols_m', 'uk-child-width-1-3@m'),
    $params->get('cols_l', 'uk-child-width-1-3@l'),
])));
$gridAttr  = $params->get('grid_masonry', 0) ? 'uk-grid="masonry: true"' : 'uk-grid';
$itemsPath = $sublayout($params->get('articles_layout', 'items') === 'titles' ? 'titles' : 'items');

// UIkit front-end filter: works on a flat list only, a grouped list is already split into sections.
$filterBy      = (string) $params->get('frontend_filter_by', 'category');
$filterBy      = \in_array($filterBy, ['category', 'tag', 'author', 'year', 'month'], true) ? $filterBy : 'category';
$filterOptions = $params->get('frontend_filter', 0) && !$grouped ? ArticlesGridHelper::filterOptions($list, $filterBy) : [];
$filterStyle   = (string) $params->get('frontend_filter_style', 'uk-subnav uk-subnav-pill');
$filterAlign   = (string) $params->get('frontend_filter_align', 'uk-flex-center');
$filterAll     = Text::_(trim((string) $params->get('frontend_filter_all', '')) ?: 'MOD_WMARKA_ARTICLES_GRID_FILTER_ALL_DEFAULT');
$filterAnim    = (string) $params->get('frontend_filter_anim', 'slide');

$groups = $grouped ? $list : [$list];
?>
<div class="wmarka-articles-grid<?php echo $moduleClass !== '' ? ' ' . $esc($moduleClass) : ''; ?>">

    <?php if ($customHeader !== '') : ?>
        <div class="uk-margin-medium-bottom uk-text-center">
            <<?php echo $headerTag; ?> class="<?php echo $esc($headerClass); ?>">
                <?php if ($headerLink !== '') : ?>
                    <a href="<?php echo $esc($headerLink); ?>" class="uk-link-reset"><?php echo $esc($customHeader); ?></a>
                <?php else : ?>
                    <span><?php echo $esc($customHeader); ?></span>
                <?php endif; ?>
            </<?php echo $headerTag; ?>>
        </div>
    <?php endif; ?>

    <?php if ($filterOptions) : ?>
    <div uk-filter="target: .js-filter; animation: <?php echo $esc($filterAnim); ?>">

        <?php if ($filterStyle === 'uk-button-group') : ?>
            <div class="uk-margin-medium-bottom uk-flex <?php echo $esc($filterAlign); ?>">
                <div class="uk-button-group">
                    <button type="button" class="uk-button uk-button-default uk-active" uk-filter-control><?php echo $esc($filterAll); ?></button>
                    <?php foreach ($filterOptions as $key => $label) : ?>
                        <button type="button" class="uk-button uk-button-default" uk-filter-control=".f-<?php echo $filterBy; ?>-<?php echo $esc($key); ?>"><?php echo $esc($label); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else : ?>
            <ul class="<?php echo $esc($filterStyle . ' ' . $filterAlign); ?> uk-margin-medium-bottom">
                <li class="uk-active" uk-filter-control><a href="#"><?php echo $esc($filterAll); ?></a></li>
                <?php foreach ($filterOptions as $key => $label) : ?>
                    <li uk-filter-control=".f-<?php echo $filterBy; ?>-<?php echo $esc($key); ?>"><a href="#"><?php echo $esc($label); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>

    <?php foreach ($groups as $groupTitle => $items) : ?>
        <?php if ($grouped) : ?>
            <h3 class="uk-h4 uk-margin-medium-top uk-margin-bottom wmarka-articles-group"><?php echo $esc($groupTitle === 'MOD_WMARKA_ARTICLES_GRID_UNTAGGED' ? Text::_($groupTitle) : $groupTitle); ?></h3>
        <?php endif; ?>
        <div class="<?php echo $filterOptions ? 'js-filter ' : ''; ?><?php echo $esc($gridClass); ?>" <?php echo $gridAttr; ?>>
            <?php require $itemsPath; ?>
        </div>
    <?php endforeach; ?>

    <?php if ($filterOptions) : ?>
    </div>
    <?php endif; ?>

</div>
