<?php

class AdminSocialfollowController extends ModuleAdminController
{
    protected $position_identifier = 'id_socialfollow_to_move';

    public function __construct()
    {
        parent::__construct();

        $this->className = 'SocialFollow';
        $this->table = 'socialfollow';
        $this->bootstrap = true;
        $this->name = 'ps_socialfollow';
        $this->identifier = 'id_socialfollow';
        $this->lang = true;

        // Order By
        $this->explicitSelect = true;
        $this->_defaultOrderBy = 'position';

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->trans('Delete selected', array(), 'Admin.Actions'),
                'icon' => 'icon-trash',
                'confirm' => $this->trans('Delete selected items?', array(), 'Admin.Notifications.Warning')
            )
        );

        $this->fields_list = array(
            'id_socialfollow' => array(
                'title' => $this->trans('ID'),
                'filter_key' => 'a!id_socialfollow',
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'title' => array(
                'title' => $this->trans('Title'),
            ),
            'position' => array(
                'title' => $this->trans('Position'),
                'align' => 'left',
                'filter_key' => 'a!position',
                'position' => 'position',
            ),
            'active' => array(
                'title' => $this->trans('Enabled'),
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'ajax' => true,
                'orderby' => false
            ),
        );
    }

    /**
     * Render the view to display
     * @return false|string
     * @throws PrestaShopException
     */
    public function renderView()
    {
        $this->initToolbar();
        return $this->renderList();
    }

    /*
    *  Render the list to display
    * @return false|string
    * @throws PrestaShopException
    */
    public function renderList()
    {
        $this->addRowAction('add');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return parent::renderList();
    }

    /**
     * Render form
     * @return null|string
     * @throws SmartyException
     */
    public function renderForm()
    {
        $this->initToolbar();

        /** @var SocialFollow $obj */
        if (!($obj = $this->loadObject(true))) {
            return null;
        }

        $this->fields_form = array(
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->trans('Class', array(), 'Admin.Global'),
                    'name' => 'class',
                    'required' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('Title', array(), 'Admin.Global'),
                    'name' => 'title',
                    'required' => true,
                    'lang' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->trans('URL', array(), 'Admin.Global'),
                    'name' => 'url',
                    'required' => true,
                    'lang' => true,
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->trans('Enabled', array(), 'Admin.Global'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->trans('Yes', array(), 'Admin.Global')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->trans('No', array(), 'Admin.Global')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->trans('Save', array(), 'Admin.Actions'),
                'name' => 'submitData',
            )
        );

        return parent::renderForm();
    }

    /**
     * Ajax update status
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function ajaxProcessStatusSocialfollow()
    {
        if (!$id_socialfollow = (int)Tools::getValue('id_socialfollow')) {
            die(json_encode(array(
                'success' => false,
                'error' => true,
                'text' => $this->trans('Failed to update the status', array(), 'Admin.Notifications.Error')
            )));
        } else {
            $social = new SocialFollow((int)Tools::getValue('id_socialfollow'));
            if (Validate::isLoadedObject($social)) {
                $social->active = $social->active == 1 ? 0 : 1;
                $social->save() ?
                    die(json_encode(array(
                        'success' => true,
                        'text' => $this->trans('The status has been updated successfully', array(),
                            'Admin.Notifications.Success')
                    ))) :
                    die(json_encode(array(
                        'success' => false,
                        'error' => true,
                        'text' => $this->trans('Failed to update the status', array(), 'Admin.Notifications.Success')
                    )));
            }
        }
    }

    /**
     * Ajax update positions
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdatePositions()
    {
        $id_socialfollow_to_move = (int)Tools::getValue('id');
        $way = (int)Tools::getValue('way');
        $positions = Tools::getValue('socialfollow');

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);

            if (isset($pos[2]) && (int)$pos[2] === $id_socialfollow_to_move) {
                if ($social = new SocialFollow((int)$pos[2])) {
                    if (isset($position) && $social->updatePosition($way, $position)) {
                        die(true);
                    } else {
                        die('{"hasError" : true, errors : "Cannot update position"}');
                    }
                } else {
                    die('{"hasError" : true, "errors" : "This object cannot be loaded"}');
                }

                break;
            }
        }
    }

}
