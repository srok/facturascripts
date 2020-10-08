<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\TarifasAvanzadas\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Description of EditDescuentoCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditDescuentoCliente extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'DescuentoCliente';
    }

    /**
     * 
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'discount';
        $data['icon'] = 'fas fa-tag';
        return $data;
    }
}
