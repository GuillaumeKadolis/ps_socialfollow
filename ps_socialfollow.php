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

if (!defined('_CAN_LOAD_FILES_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Ps_Socialfollow extends Module implements WidgetInterface
{
    private $templateFile;

    public function __construct()
    {
        $this->name = 'ps_socialfollow';
        $this->author = 'PrestaShop';
        $this->version = '3.0.0';

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
    protected function installDB()
    {
        $res = (bool)Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'socialfollow_network` (
                    `id_socialfollow_network` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `class` varchar(255) NOT NULL,
                    `position` int(10) unsigned NOT NULL DEFAULT \'0\',
                    `active` tinyint(1) UNSIGNED NOT NULL DEFAULT \'0\',
                    PRIMARY KEY (`id_socialfollow_network`) ) 
                    ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
                ');

        $res &= Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'socialfollow_network_lang` (
                    `id_socialfollow_network` int(10) unsigned NOT NULL,
                    `id_lang` int(10) unsigned NOT NULL,
                    `label` varchar(255) NOT NULL,
                    `name` varchar(255) NOT NULL,
                    `description` varchar(255) NOT NULL,
                    PRIMARY KEY (`id_socialfollow_network`, `id_lang`) ) 
                    ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
                ');

        $res &= Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'socialfollow` (
                    `id_socialfollow` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `id_socialfollow_network` int(10) unsigned NOT NULL,
                    `id_shop` int(10) unsigned NOT NULL,
                    PRIMARY KEY (`id_socialfollow`),
                    UNIQUE (`id_socialfollow_network`, `id_shop`) ) 
                    ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
                ');

        $res &= Db::getInstance()->execute(
            ' CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'socialfollow_lang` (
                      `id_socialfollow` int(10) unsigned NOT NULL,
                      `id_lang` int(10) unsigned NOT NULL,
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
        return Db::getInstance()->execute('
            DROP TABLE IF EXISTS `'._DB_PREFIX_.'socialfollow_network`, `'._DB_PREFIX_.'socialfollow_network_lang`, 
                `'._DB_PREFIX_.'socialfollow`, `'._DB_PREFIX_.'socialfollow_lang`;
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

        $datas = [['class' => 'facebook','label'=>'Facebook URL','name'=>'Facebook','description'=>'Your Facebook fan page.'],
                    ['class' => 'twitter','label'=>'Twitter URL','name'=>'Twitter','description'=>'Your official Twitter account.'],
                    ['class' => 'rss','label'=>'RSS URL','name'=>'Rss','description'=>'The RSS feed of your choice (your blog, your store, etc.).'],
                    ['class' => 'youtube','label'=>'YouTube URL','name'=>'YouTube','description'=>'Your official YouTube account.'],
                    ['class' => 'googleplus','label'=>'Google+ URL','name'=>'Google +','description'=>'Your official Google+ page.'],
                    ['class' => 'pinterest','label'=>'Pinterest URL','name'=>'Pinterest','description'=>'Your official Pinterest account.'],
                    ['class' => 'vimeo','label'=>'Vimeo URL','name'=>'Vimeo','description'=>'Your official Vimeo account'],
                    ['class' => 'instagram','label'=>'Instagram URL','name'=>'Instagram','description'=>'Your official Instagram account'],
                ];

        foreach ($datas as $key => $data){
            $network = new SocialFollowNetwork();
            $network->class = $data['class'];
            $network->label = $data['label'];
            $network->name = $data['description'];
            $network->description = $data['description'];
            $network->active = 0;
            $network->position = $key;
            $network->save();
        }

        return $return;
    }

    /**
     * Register Administration tabs
     *
     * @param $tabName
     * @param $tabClass
     * @param $id_parent
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installTab()
    {
        $return = null;
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
        return null;
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitModule')) {
            Configuration::updateValue('BLOCKSOCIAL_FACEBOOK', Tools::getValue('blocksocial_facebook', ''));
            Configuration::updateValue('BLOCKSOCIAL_TWITTER', Tools::getValue('blocksocial_twitter', ''));
            Configuration::updateValue('BLOCKSOCIAL_RSS', Tools::getValue('blocksocial_rss', ''));
            Configuration::updateValue('BLOCKSOCIAL_YOUTUBE', Tools::getValue('blocksocial_youtube', ''));
            Configuration::updateValue('BLOCKSOCIAL_GOOGLE_PLUS', Tools::getValue('blocksocial_google_plus', ''));
            Configuration::updateValue('BLOCKSOCIAL_PINTEREST', Tools::getValue('blocksocial_pinterest', ''));
            Configuration::updateValue('BLOCKSOCIAL_VIMEO', Tools::getValue('blocksocial_vimeo', ''));
            Configuration::updateValue('BLOCKSOCIAL_INSTAGRAM', Tools::getValue('blocksocial_instagram', ''));

            $this->_clearCache('*');

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&tab_module='.$this->tab.'&conf=4&module_name='.$this->name);
        }

        return $this->renderForm();
    }

    public function _clearCache($template, $cache_id = null, $compile_id = null)
    {
        parent::_clearCache($this->templateFile);
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Settings', array(), 'Admin.Global'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Facebook URL', array(), 'Modules.Socialfollow.Admin'),
                        'name' => 'blocksocial_facebook',
                        'desc' => $this->trans('Your Facebook fan page.', array(), 'Modules.Socialfollow.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Twitter URL', array(), 'Modules.Socialfollow.Admin'),
                        'name' => 'blocksocial_twitter',
                        'desc' => $this->trans('Your official Twitter account.', array(), 'Modules.Socialfollow.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('RSS URL', array(), 'Modules.Socialfollow.Admin'),
                        'name' => 'blocksocial_rss',
                        'desc' => $this->trans('The RSS feed of your choice (your blog, your store, etc.).', array(), 'Modules.Socialfollow.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('YouTube URL', array(), 'Modules.Socialfollow.Admin'),
                        'name' => 'blocksocial_youtube',
                        'desc' => $this->trans('Your official YouTube account.', array(), 'Modules.Socialfollow.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Google+ URL:', array(), 'Modules.Socialfollow.Admin'),
                        'name' => 'blocksocial_google_plus',
                        'desc' => $this->trans('Your official Google+ page.', array(), 'Modules.Socialfollow.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Pinterest URL:', array(), 'Modules.Socialfollow.Admin'),
                        'name' => 'blocksocial_pinterest',
                        'desc' => $this->trans('Your official Pinterest account.', array(), 'Modules.Socialfollow.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Vimeo URL:', array(), 'Modules.Socialfollow.Admin'),
                        'name' => 'blocksocial_vimeo',
                        'desc' => $this->trans('Your official Vimeo account.', array(), 'Modules.Socialfollow.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Instagram URL:', array(), 'Modules.Socialfollow.Admin'),
                        'name' => 'blocksocial_instagram',
                        'desc' => $this->trans('Your official Instagram account.', array(), 'Modules.Socialfollow.Admin'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Global'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table =  $this->table;
        $helper->submit_action = 'submitModule';
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'blocksocial_facebook' => Tools::getValue('blocksocial_facebook', Configuration::get('BLOCKSOCIAL_FACEBOOK')),
            'blocksocial_twitter' => Tools::getValue('blocksocial_twitter', Configuration::get('BLOCKSOCIAL_TWITTER')),
            'blocksocial_rss' => Tools::getValue('blocksocial_rss', Configuration::get('BLOCKSOCIAL_RSS')),
            'blocksocial_youtube' => Tools::getValue('blocksocial_youtube', Configuration::get('BLOCKSOCIAL_YOUTUBE')),
            'blocksocial_google_plus' => Tools::getValue('blocksocial_google_plus', Configuration::get('BLOCKSOCIAL_GOOGLE_PLUS')),
            'blocksocial_pinterest' => Tools::getValue('blocksocial_pinterest', Configuration::get('BLOCKSOCIAL_PINTEREST')),
            'blocksocial_vimeo' => Tools::getValue('blocksocial_vimeo', Configuration::get('BLOCKSOCIAL_VIMEO')),
            'blocksocial_instagram' => Tools::getValue('blocksocial_instagram', Configuration::get('BLOCKSOCIAL_INSTAGRAM')),
        );
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId('ps_socialfollow'))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId('ps_socialfollow'));
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $social_links = array();

        if ($sf_facebook = Configuration::get('BLOCKSOCIAL_FACEBOOK')) {
            $social_links['facebook'] = array(
                'label' => $this->trans('Facebook', array(), 'Modules.Socialfollow.Shop'),
                'class' => 'facebook',
                'url' => $sf_facebook,
            );
        }

        if ($sf_twitter = Configuration::get('BLOCKSOCIAL_TWITTER')) {
            $social_links['twitter'] = array(
                'label' => $this->trans('Twitter', array(), 'Modules.Socialfollow.Shop'),
                'class' => 'twitter',
                'url' => $sf_twitter,
            );
        }

        if ($sf_rss = Configuration::get('BLOCKSOCIAL_RSS')) {
            $social_links['rss'] = array(
                'label' => $this->trans('Rss', array(), 'Modules.Socialfollow.Shop'),
                'class' => 'rss',
                'url' => $sf_rss,
            );
        }

        if ($sf_youtube = Configuration::get('BLOCKSOCIAL_YOUTUBE')) {
            $social_links['youtube'] = array(
                'label' => $this->trans('YouTube', array(), 'Modules.Socialfollow.Shop'),
                'class' => 'youtube',
                'url' => $sf_youtube,
            );
        }

        if ($sf_googleplus = Configuration::get('BLOCKSOCIAL_GOOGLE_PLUS')) {
            $social_links['googleplus'] = array(
                'label' => $this->trans('Google +', array(), 'Modules.Socialfollow.Shop'),
                'class' => 'googleplus',
                'url' => $sf_googleplus,
            );
        }

        if ($sf_pinterest = Configuration::get('BLOCKSOCIAL_PINTEREST')) {
            $social_links['pinterest'] = array(
                'label' => $this->trans('Pinterest', array(), 'Modules.Socialfollow.Shop'),
                'class' => 'pinterest',
                'url' => $sf_pinterest,
            );
        }

        if ($sf_vimeo = Configuration::get('BLOCKSOCIAL_VIMEO')) {
            $social_links['vimeo'] = array(
                'label' => $this->trans('Vimeo', array(), 'Modules.Socialfollow.Shop'),
                'class' => 'vimeo',
                'url' => $sf_vimeo,
            );
        }

        if ($sf_instagram = Configuration::get('BLOCKSOCIAL_INSTAGRAM')) {
            $social_links['instagram'] = array(
                'label' => $this->trans('Instagram', array(), 'Modules.Socialfollow.Shop'),
                'class' => 'instagram',
                'url' => $sf_instagram,
            );
        }

        return array(
            'social_links' => $social_links,
        );
    }
}
