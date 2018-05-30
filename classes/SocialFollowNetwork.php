<?php

class SocialFollowNetwork extends ObjectModel
{
    /** @var string Description */
    public $description;

    /** @var string Name */
    public $name;

    /** @var string Label to display into admin */
    public $label;

    /** @var string Class */
    public $class;

    /** @var bool Status for display */
    public $active;

    /** @var  int content position */
    public $position;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'socialfollow_network',
        'primary' => 'id_socialfollow_network',
        'multilang' => true,
        'fields' => array(
            'class' => array('type' => self::TYPE_HTML,  'validate' => 'isCleanHtml', 'required' => true),
            'active' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
            'position' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            // Lang fields
            'name' => array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true),
            'label' => array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true),
            'description' => array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
        )
    );

}
