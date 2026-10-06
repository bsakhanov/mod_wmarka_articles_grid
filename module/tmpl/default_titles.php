<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_wmarka_articles_grid
 *
 * @copyright   (C) 2026 Beibit Sakhanov <https://webmarka.kz>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Webmarka\Module\ArticlesGrid\Site\Helper\ArticlesGridHelper as Helper;

/**
 * @var \Joomla\Registry\Registry $params
 * @var array                     $items  articles of the current group
 */

$esc        = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$headings   = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div'];
$itemTag    = \in_array($params->get('item_heading_tag', 'h3'), $headings, true) ? $params->get('item_heading_tag', 'h3') : 'h3';
$titleClass = Helper::titleClass($params);
$introAttr  = Helper::introAttributes($params, 'uk-margin-small-top');
$showIntro  = (bool) $params->get('show_introtext', 1);
$microdata  = $params->get('structured_data', 'jsonld') === 'microdata';

foreach ($items as $item) : ?>
    <div class="<?php echo Helper::filterClasses($item); ?>">
        <article class="uk-card uk-card-default uk-card-body uk-card-hover uk-link-toggle"<?php echo $microdata ? ' itemscope itemtype="https://schema.org/Article"' : ''; ?>>
            <<?php echo $itemTag; ?> class="<?php echo $esc($titleClass); ?> uk-margin-remove"<?php echo $microdata ? ' itemprop="headline"' : ''; ?>>
                <a href="<?php echo $item->link; ?>" class="uk-link-reset"<?php echo $microdata ? ' itemprop="url"' : ''; ?>><?php echo $esc($item->title); ?></a>
            </<?php echo $itemTag; ?>>

            <?php if ($showIntro && !empty($item->displayIntrotext)) : ?>
                <div<?php echo $introAttr; ?><?php echo $microdata ? ' itemprop="abstract"' : ''; ?>><?php echo $item->displayIntrotext; ?></div>
            <?php endif; ?>
        </article>
    </div>
<?php endforeach; ?>
