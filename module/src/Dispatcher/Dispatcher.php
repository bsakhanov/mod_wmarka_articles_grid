<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_wmarka_articles_grid
 *
 * @copyright   (C) 2026 Beibit Sakhanov <https://webmarka.kz>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Webmarka\Module\ArticlesGrid\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Helper\ModuleHelper;
use Webmarka\Module\ArticlesGrid\Site\Helper\ArticlesGridHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher for mod_wmarka_articles_grid.
 *
 * Joomla 4+ renders a module with services/provider.php through this class and
 * never executes a legacy mod_wmarka_articles_grid.php entry file, so everything the layouts need
 * is prepared here.
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * @return  array|false  Layout data, or false when there is nothing to show.
     */
    protected function getLayoutData()
    {
        $data   = parent::getLayoutData();
        $params = $data['params'];
        $input  = $data['input'];
        $idBase = null;

        if ($params->get('mode', 'normal') === 'dynamic') {
            if ($input->get('option') === 'com_content') {
                switch ($input->get('view')) {
                    case 'category':
                    case 'categories':
                        $idBase = $input->getInt('id');
                        break;

                    case 'article':
                        if ($params->get('show_on_article_page', 1)) {
                            $idBase = [$input->getInt('id'), $input->getInt('catid')];
                        }
                        break;
                }
            }
        } else {
            $idBase = $params->get('catid');
        }

        $cacheParams               = new \stdClass();
        $cacheParams->cachemode    = 'id';
        $cacheParams->class        = $this->getHelperFactory()->getHelper('ArticlesGridHelper');
        $cacheParams->method       = 'getArticles';
        $cacheParams->methodparams = [$params, $data['app']];
        $cacheParams->modeparams   = md5(serialize([$idBase, $this->module->module, $this->module->id]));

        $list = ModuleHelper::moduleCache($this->module, $params, $cacheParams);

        if (empty($list)) {
            return false;
        }

        ArticlesGridHelper::loadUikit($params);
        ArticlesGridHelper::seo($list, $params, $this->module, 'article');

        $data['list']        = $list;
        $data['grouped']     = $params->get('article_grouping', 'none') !== 'none';
        $data['moduleClass'] = trim((string) $params->get('moduleclass_sfx', ''));
        $data['juImg']       = ArticlesGridHelper::getJuImage();
        $data['blendMode']   = (string) $params->get('blend_mode', '');
        $data['overlayRgba'] = $data['blendMode'] !== ''
            ? ArticlesGridHelper::rgba((string) $params->get('overlay_color', '#000000'), $params->get('overlay_opacity', '0.5'))
            : '';

        return $data;
    }
}
