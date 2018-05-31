<?php
/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
require_once(dirname(__FILE__) . '/classes/SocialFollow.php');

class Ps_Socialfollow extends Module implements WidgetInterface
{
    private $templateFile;

    public function __construct()
    {
        $this->name = 'ps_socialfollow';
        $this->author = 'PrestaShop';
        $this->version = '3.0.0';
        $this->controllers = array('AdminSocialfollow');

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Social media follow links', array(), 'Modules.Socialfollow.Admin');
        $this->description = $this->trans('Allows you to add information about your brand\'s social networking accounts.', array(), 'Modules.Socialfollow.Admin');

        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:ps_socialfollow/ps_socialfollow.tpl';
    }

    /**
     * Install()
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        return (parent::install() &&
            $this->installDB() &&
            $this->installDefaultData() &&
            $this->installTab() &&
            $this->registerHook('displayFooter'));
    }

    /**
     * Uninstall()
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        return (
            $this->uninstallDB() &&
            $this->uninstallTab() &&
            parent::uninstall());
    }

    /**
     * Install DataBase
     * @return bool
     */
    public function installDB()
    {
        $res = (bool)Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'socialfollow` (
                    `id_socialfollow` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `class` varchar(60) NOT NULL,
                    `position` int(10) unsigned NOT NULL DEFAULT \'0\',
                    `active` tinyint(1) UNSIGNED NOT NULL DEFAULT \'0\',
                    PRIMARY KEY (`id_socialfollow`) ) 
                    ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
                ');

        $res &= Db::getInstance()->execute(
            ' CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'socialfollow_lang` (
                      `id_socialfollow` int(10) unsigned NOT NULL,
                      `id_lang` int(10) unsigned NOT NULL,
                      `title` varchar(100) NOT NULL,
                      `url` varchar(255) NOT NULL,
                      PRIMARY KEY (`id_socialfollow`,`id_lang`) ) 
                    ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
                ');

        return $res;
    }

    /**
     * Drop tables
     * @return bool
     */
    protected function uninstallDB(){
        return (bool)Db::getInstance()->execute('
            DROP TABLE IF EXISTS `'._DB_PREFIX_.'socialfollow`, `'._DB_PREFIX_.'socialfollow_lang`;
        ');
    }

    /**
     * Add default data into DB
     * @return bool
     * @throws PrestaShopException
     */
    protected function installDefaultData()
    {
        $return = true;

        $datas = [
            ['class' => 'facebook', 'title' => 'Facebook'],
            ['class' => 'twitter', 'title' => 'Twitter'],
            ['class' => 'rss', 'title' => 'Rss'],
            ['class' => 'youtube', 'title' => 'YouTube'],
            ['class' => 'googleplus', 'title' => 'Google +'],
            ['class' => 'pinterest', 'title' => 'Pinterest'],
            ['class' => 'vimeo', 'title' => 'Vimeo'],
            ['class' => 'instagram', 'title' => 'Instagram'],
        ];

        foreach ($datas as $key => $data){
            $social = new SocialFollow();
            $social->class = $data['class'];
            $social->active = 0;
            $social->position = $key;

            foreach (Language::getIDs() as $langID) {
                $social->title[$langID] = $data['title'];
            }

            $return &= $social->save();
        }

        return $return;
    }

    /**
     * Register Administration tabs
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installTab()
    {
        $return = true;
        foreach ($this->controllers as $controller_name) {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = $controller_name;
            $tab->name = array();
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $this->name;
            }
            $tab->id_parent = -1;
            $tab->module = $this->name;
            if ($tab->add() == true) {
                $return &= true;
            } else {
                $return &= false;
            }
        }
        return $return;
    }

    /**
     * Remove Administration tabs
     * @return bool|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function uninstallTab()
    {
        foreach ($this->controllers as $controller_name) {
            $tabRepository = $this->get('prestashop.core.admin.tab.repository');
            $id_tab = (int)$tabRepository->findOneIdByClassName($controller_name);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                if (Validate::isLoadedObject($tab)) {
                    return ($tab->delete());
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }
        return true;
    }

    /**
     * Load the configuration.
     * @return string
     */
    public function getContent()
    {
        Tools::redirectAdmin('index.php?controller=AdminSocialfollow&token='. Tools::getAdminTokenLite('AdminSocialfollow'));
    }

    /**
     * Clear template cache
     * @param string $template
     * @param null $cache_id
     * @param null $compile_id
     * @return int|void
     */
    public function _clearCache($template, $cache_id = null, $compile_id = null)
    {
        parent::_clearCache($this->templateFile);
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId('ps_socialfollow'))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId('ps_socialfollow'));
    }

    /**
     * @param null $hookName
     * @param array $configuration
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        return [
            'social_links' => SocialFollow::getAllSocials(),
        ];
    }
}
