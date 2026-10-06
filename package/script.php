<?php

/**
 * Installer script of the package pkg_wmarka_articles_grid: module + JUImage library.
 *
 * @package     pkg_wmarka_articles_grid
 *
 * @copyright   (C) 2026 Beibit Sakhanov <https://webmarka.kz>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Filesystem\Folder;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            InstallerScriptInterface::class,
            new class () implements InstallerScriptInterface {
                private const PACKAGE    = 'pkg_wmarka_articles_grid';
                private const PREFIX     = 'PKG_WMARKA_ARTICLES_GRID';
                private const MIN_PHP    = '8.1.0';
                private const MIN_JOOMLA = '5.0.0';

                /**
                 * JUImage travels inside the package but is not one of its children: a package
                 * uninstalls every child listed in its manifest, and JUImage is a shared library
                 * that other extensions (a template, other modules) may rely on.
                 */
                private const JUIMAGE_ARCHIVE = 'packages/lib_juimage.zip';

                public function install(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function update(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function uninstall(InstallerAdapter $adapter): bool
                {
                    $this->loadLanguage($adapter);

                    if ($this->juImagePresent()) {
                        Factory::getApplication()->enqueueMessage(Text::_(self::PREFIX . '_JUIMAGE_KEPT'), 'info');
                    }

                    return true;
                }

                public function preflight(string $type, InstallerAdapter $adapter): bool
                {
                    if ($type === 'uninstall') {
                        return true;
                    }

                    $this->loadLanguage($adapter);

                    if (version_compare(PHP_VERSION, self::MIN_PHP, '<')) {
                        Factory::getApplication()->enqueueMessage(Text::sprintf(self::PREFIX . '_ERROR_PHP', self::MIN_PHP, PHP_VERSION), 'error');

                        return false;
                    }

                    if (version_compare(JVERSION, self::MIN_JOOMLA, '<')) {
                        Factory::getApplication()->enqueueMessage(Text::sprintf(self::PREFIX . '_ERROR_JOOMLA', self::MIN_JOOMLA, JVERSION), 'error');

                        return false;
                    }

                    return true;
                }

                public function postflight(string $type, InstallerAdapter $adapter): bool
                {
                    if ($type === 'uninstall') {
                        return true;
                    }

                    $this->loadLanguage($adapter);
                    $this->ensureJuImage($adapter);

                    Factory::getApplication()->enqueueMessage(Text::_(self::PREFIX . '_UIKIT_NOTE'), 'info');

                    return true;
                }

                /**
                 * Installs the bundled JUImage only when the site has none.
                 * The module needs libraries/juimage/vendor/autoload.php; when it is there,
                 * the library on the site is left exactly as it is.
                 */
                private function ensureJuImage(InstallerAdapter $adapter): void
                {
                    $app = Factory::getApplication();

                    if ($this->juImagePresent()) {
                        $app->enqueueMessage(Text::sprintf(self::PREFIX . '_JUIMAGE_FOUND', $this->juImageVersion()), 'info');

                        return;
                    }

                    $archive = $adapter->getParent()->getPath('source') . '/' . self::JUIMAGE_ARCHIVE;

                    if (!is_file($archive)) {
                        $app->enqueueMessage(Text::_(self::PREFIX . '_JUIMAGE_MISSING_ARCHIVE'), 'warning');

                        return;
                    }

                    $package = InstallerHelper::unpack($archive, true);

                    if (empty($package['dir'])) {
                        $app->enqueueMessage(Text::_(self::PREFIX . '_JUIMAGE_FAILED'), 'warning');

                        return;
                    }

                    $installer = new Installer();
                    $installer->setDatabase(Factory::getContainer()->get(DatabaseInterface::class));
                    $installed = $installer->install($package['dir']);

                    if (!empty($package['extractdir']) && is_dir($package['extractdir'])) {
                        Folder::delete($package['extractdir']);
                    }

                    // Without JUImage the module still works and shows the original images.
                    $app->enqueueMessage(
                        $installed ? Text::sprintf(self::PREFIX . '_JUIMAGE_INSTALLED', $this->juImageVersion()) : Text::_(self::PREFIX . '_JUIMAGE_FAILED'),
                        $installed ? 'message' : 'warning'
                    );
                }

                private function juImagePresent(): bool
                {
                    return is_file(JPATH_LIBRARIES . '/juimage/vendor/autoload.php');
                }

                private function juImageVersion(): string
                {
                    $manifest = JPATH_MANIFESTS . '/libraries/juimage.xml';
                    $xml      = is_file($manifest) ? simplexml_load_file($manifest) : false;

                    return $xml && isset($xml->version) ? (string) $xml->version : '—';
                }

                private function loadLanguage(InstallerAdapter $adapter): void
                {
                    $language = Factory::getApplication()->getLanguage();
                    $source   = (string) $adapter->getParent()->getPath('source');

                    $language->load(self::PACKAGE . '.sys', JPATH_SITE) || ($source !== '' && $language->load(self::PACKAGE . '.sys', $source));
                }
            }
        );
    }
};
