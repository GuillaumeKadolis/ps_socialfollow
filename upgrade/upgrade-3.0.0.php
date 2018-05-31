<?php
/*
* 2007-2018 PrestaShop
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
*  @copyright  2007-2018 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * upgrade function
 * @param $module
 * @return bool
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function upgrade_module_3_0_0($module)
{
    return $module->installDB() &&
        $module->installTab() &&
        migratedata() &&
        removeConfig();
}

/**
 * move config data to DB
 * @return bool
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function migratedata(){

    $return = true;

    $datas = [
        ['class' => 'facebook', 'title' => 'Facebook', 'url' => Configuration::get('BLOCKSOCIAL_FACEBOOK')],
        ['class' => 'twitter', 'title' => 'Twitter', 'url' => Configuration::get('BLOCKSOCIAL_TWITTER')],
        ['class' => 'rss', 'title' => 'Rss', 'url' => Configuration::get('BLOCKSOCIAL_RSS')],
        ['class' => 'youtube', 'title' => 'YouTube', 'url' => Configuration::get('BLOCKSOCIAL_YOUTUBE')],
        ['class' => 'googleplus', 'title' => 'Google +', 'url' => Configuration::get('BLOCKSOCIAL_GOOGLE_PLUS')],
        ['class' => 'pinterest', 'title' => 'Pinterest', 'url' => Configuration::get('BLOCKSOCIAL_PINTEREST')],
        ['class' => 'vimeo', 'title' => 'Vimeo', 'url' => Configuration::get('BLOCKSOCIAL_VIMEO')],
        ['class' => 'instagram', 'title' => 'Instagram', 'url' => Configuration::get('BLOCKSOCIAL_INSTAGRAM')],
    ];

    foreach ($datas as $key => $data){
        $social = new SocialFollow();
        $social->class = $data['class'];
        $social->active = $data['url'] ? 1 : 0;
        $social->position = $key;

        foreach (Language::getIDs() as $langID) {
            $social->title[$langID] = $data['title'];
            $social->url[$langID] = $data['url'];
        }

        $return &= $social->save();
    }

    return $return;
}

/**
 * Remove config vars
 * @return bool
 */
function removeConfig(){
    return Configuration::deleteByName('BLOCKSOCIAL_FACEBOOK') &&
        Configuration::deleteByName('BLOCKSOCIAL_TWITTER') &&
        Configuration::deleteByName('BLOCKSOCIAL_RSS') &&
        Configuration::deleteByName('BLOCKSOCIAL_YOUTUBE') &&
        Configuration::deleteByName('BLOCKSOCIAL_GOOGLE_PLUS') &&
        Configuration::deleteByName('BLOCKSOCIAL_PINTEREST') &&
        Configuration::deleteByName('BLOCKSOCIAL_VIMEO') &&
        Configuration::deleteByName('BLOCKSOCIAL_INSTAGRAM');
}
