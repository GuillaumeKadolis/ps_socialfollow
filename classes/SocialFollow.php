<?php

class SocialFollow extends ObjectModel
{
    /** @var string Target URL */
    public $url;

    /** @var string Name */
    public $title;

    /** @var string Class */
    public $class;

    /** @var bool Status for display */
    public $active;

    /** @var  int position */
    public $position;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'socialfollow',
        'primary' => 'id_socialfollow',
        'multilang' => true,
        'fields' => array(
            'class' => array('type' => self::TYPE_STRING,  'validate' => 'isCleanHtml', 'required' => true, 'size' => 60),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'position' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            // Lang fields
            'url' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isUrl', 'size' => 255),
            'title' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 100),
        )
    );

    /**
     * Get all socials
     * @param bool $active
     * @param null $idLang
     * @param null $orderBy
     * @param null $limit
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public static function getAllSocials($active = true, $idLang = null, $orderBy = null, $limit = null){
        $context = Context::getContext();
        if(!$idLang){
            $idLang = $context->language->id;
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT s.`id_socialfollow`, s.`class`, s.`position`, s.`active`, 
            sl.`title`, sl.`url`
            FROM ' . _DB_PREFIX_ . 'socialfollow s
            LEFT JOIN ' . _DB_PREFIX_ . 'socialfollow_lang sl ON (sl.`id_socialfollow` = s.`id_socialfollow`)
            WHERE sl.`id_lang` = ' . (int)$idLang .
            ($active ? ' AND s.`active` = 1' : ' ') . '
            ORDER BY '.($orderBy ?: ' s.position') .
            ($limit ? ' LIMIT ' . $limit : '')
        );
    }

    /**
     * Adds current SocialFollow as a new Object to the database
     *
     * @param bool $autoDate   Automatically set `date_upd` and `date_add` columns
     * @param bool $nullValues Whether we want to use NULL values instead of empty quotes values
     *
     * @return bool Indicates whether the SocialFollow has been successfully added
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        $this->position = (int)self::getNextPosition();
        return parent::add($autoDate, $nullValues);
    }

    /**
     * Deletes current Socialfollow from the database
     *
     * @return bool `true` if successfully deleted
     * @throws PrestaShopException
     */
    public function delete()
    {
        $this->clearCache();
        return (bool)parent::delete() &&
                self::cleanPositions();
    }

    /**
     * get the next position
     *
     * @return int $position Position
     */
    public static function getNextPosition()
    {
        if ((int)Db::getInstance()->getValue('
				SELECT COUNT(`id_socialfollow`)
				FROM `' . _DB_PREFIX_ . 'socialfollow`') === 0) {
            return 0;
        } else {
            return (1 + (int)Db::getInstance()->getValue('
				SELECT MAX(`position`)
				FROM `' . _DB_PREFIX_ . 'socialfollow`'));
        }
    }

    /**
     * Update the position of the current SocialFollow
     *
     * @param bool $way Indicates whether the SocialFollow should move up (`false`) or down (`true`)
     * @param int $position Current Position
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function updatePosition($way, $position)
    {
        if (!$res = Db::getInstance()->executeS('
            SELECT `id_socialfollow`, `position`
            FROM `' . _DB_PREFIX_ . 'socialfollow` 
            ORDER BY `position` ASC')
        ) {
            return false;
        }

        $moved = false;
        foreach ($res as $social) {
            if ((int)$social['id_socialfollow'] == (int)$this->id) {
                $moved = $social;
            }
        }

        if ($moved === false) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $result = (bool)(Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'socialfollow`
            SET `position`= `position` ' . ($way ? '- 1' : '+ 1') . '
            WHERE `position`
            ' . ($way
                    ? '> ' . (int)$moved['position'] . ' AND `position` <= ' . (int)$position
                    : '< ' . (int)$moved['position'] . ' AND `position` >= ' . (int)$position) )
            && Db::getInstance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'socialfollow`
            SET `position` = ' . (int)$position . '
            WHERE `id_socialfollow`=' . (int)$moved['id_socialfollow']));

        return $result;
    }

    /**
     * cleanPositions keep order
     *
     * @return bool true if succeed
     * @throws PrestaShopDatabaseException
     */
    public static function cleanPositions()
    {
        $return = true;
        $result = Db::getInstance()->executeS('
        SELECT `id_socialfollow`
        FROM `'._DB_PREFIX_.'socialfollow`
        ORDER BY `position`');
        $count = count($result);
        for ($i = 0; $i < $count; $i++) {
            $return &= Db::getInstance()->execute('
            UPDATE `'._DB_PREFIX_.'socialfollow`
            SET `position` = '.(int) ($i).'
            WHERE `id_socialfollow` = '.(int) $result[$i]['id_socialfollow']);
        }

        return $return;
    }

}
