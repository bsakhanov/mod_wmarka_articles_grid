<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_wmarka_articles_grid
 *
 * @copyright   (C) 2026 Beibit Sakhanov <https://webmarka.kz>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Webmarka\Module\ArticlesGrid\Site\Helper\ArticlesGridHelper as Helper;

/**
 * @var \Joomla\Registry\Registry $params
 * @var array                     $items        articles of the current group
 * @var object|null               $juImg
 * @var string                    $blendMode
 * @var string                    $overlayRgba
 */

$esc      = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$headings = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div'];

$mediaPos      = (string) $params->get('card_media_pos', 'top');
$cardStructure = (string) $params->get('card_structure', 'body-only');
$hasHeader     = \in_array($cardStructure, ['header-body', 'header-body-footer'], true);
$hasFooter     = \in_array($cardStructure, ['body-footer', 'header-body-footer'], true);
$itemTag       = \in_array($params->get('item_heading_tag', 'h3'), $headings, true) ? $params->get('item_heading_tag', 'h3') : 'h3';
$titleClass    = Helper::titleClass($params);
$introAttr     = Helper::introAttributes($params, 'uk-margin-small-top');
$showIntro     = (bool) $params->get('show_introtext', 1);
$microdata     = $params->get('structured_data', 'jsonld') === 'microdata';

// The cover keeps the img_w × img_h ratio on every screen, with JUImage or with the original file.
$ratioW  = max(1, (int) $params->get('img_w', 800));
$ratioH  = max(1, (int) $params->get('img_h', 500));
$columns = [$params->get('cols_s', 'uk-child-width-1-2@s'), $params->get('cols_m', 'uk-child-width-1-3@m'), $params->get('cols_l', 'uk-child-width-1-3@l')];
$sizesV  = Helper::sizes($columns);
$sizesH  = Helper::sizes($columns, 1 / 3);

$n = 0;

foreach ($items as $item) :
    $position = $mediaPos;

    if ($mediaPos === 'zebra-v') {
        $position = $n % 2 ? 'bottom' : 'top';
    } elseif ($mediaPos === 'zebra-h') {
        $position = $n % 2 ? 'right' : 'left';
    }

    $n++;

    $sideways   = $position === 'left' || $position === 'right';
    $picture    = Helper::picture($item, $params, $juImg, $sideways ? $sizesH : $sizesV, 'full', ['img_h' => 500, 'mob_w' => 400, 'mob_h' => 300]);
    $horizontal = $picture && $sideways;
    $title      = $esc($item->title);
    $readmore   = trim((string) ($item->alternative_readmore ?? '')) ?: Text::_('MOD_WMARKA_ARTICLES_GRID_READMORE');
    $media      = '';

    if ($picture) {
        $media = '<div class="uk-card-media-' . $position . ' uk-cover-container' . ($horizontal ? ' uk-width-1-1 uk-width-1-3@s' : '') . '"'
            . ($microdata ? ' itemprop="image" itemscope itemtype="https://schema.org/ImageObject"' : '') . '>'
            . Helper::pictureTag($picture, ['uk-cover' => true, 'itemprop' => $microdata ? 'contentUrl' : null])
            . '<canvas width="' . $ratioW . '" height="' . $ratioH . '"></canvas>'
            . ($blendMode !== '' && $overlayRgba !== '' ? '<div class="uk-position-cover ' . $esc($blendMode) . '" style="background-color: ' . $overlayRgba . ';"></div>' : '')
            . '</div>';
    }
    ?>
    <div class="<?php echo Helper::filterClasses($item); ?>">
        <article class="uk-card uk-card-default uk-link-toggle uk-overflow-hidden<?php echo $horizontal ? ' uk-grid-collapse' : ' uk-flex uk-flex-column'; ?>"<?php echo $horizontal ? ' uk-grid' : ''; ?><?php echo $microdata ? ' itemscope itemtype="https://schema.org/Article"' : ''; ?>>

            <?php if ($position === 'top' || $position === 'left') : ?>
                <?php echo $media; ?>
            <?php endif; ?>

            <div class="uk-flex uk-flex-column<?php echo $horizontal ? ' uk-width-1-1 uk-width-2-3@s' : ' uk-flex-1'; ?>">
                <?php if ($hasHeader) : ?>
                    <div class="uk-card-header">
                        <<?php echo $itemTag; ?> class="<?php echo $esc($titleClass); ?> uk-margin-remove"<?php echo $microdata ? ' itemprop="headline"' : ''; ?>>
                            <a href="<?php echo $item->link; ?>" class="uk-link-reset"<?php echo $microdata ? ' itemprop="url"' : ''; ?>><?php echo $title; ?></a>
                        </<?php echo $itemTag; ?>>
                    </div>
                <?php endif; ?>

                <div class="uk-card-body uk-flex-1">
                    <?php if (!$hasHeader) : ?>
                        <<?php echo $itemTag; ?> class="<?php echo $esc($titleClass); ?> uk-margin-remove-top"<?php echo $microdata ? ' itemprop="headline"' : ''; ?>>
                            <a href="<?php echo $item->link; ?>" class="uk-link-reset"<?php echo $microdata ? ' itemprop="url"' : ''; ?>><?php echo $title; ?></a>
                        </<?php echo $itemTag; ?>>
                    <?php endif; ?>

                    <?php if ($showIntro && !empty($item->displayIntrotext)) : ?>
                        <div<?php echo $introAttr; ?><?php echo $microdata ? ' itemprop="abstract"' : ''; ?>><?php echo $item->displayIntrotext; ?></div>
                    <?php endif; ?>
                </div>

                <?php if ($hasFooter) : ?>
                    <div class="uk-card-footer">
                        <a href="<?php echo $item->link; ?>" class="uk-button uk-button-text"><?php echo $esc($readmore); ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($position === 'bottom' || $position === 'right') : ?>
                <?php echo $media; ?>
            <?php endif; ?>

        </article>
    </div>
<?php endforeach; ?>
