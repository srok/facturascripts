<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\TarifasAvanzadas\Extension\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of EditTarifa
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditTarifa
{

    public function createViews()
    {
        return function() {
            $this->addEditListView('EditTarifaFamilia', 'TarifaFamilia', 'families', 'fas fa-sitemap');
        };
    }

    public function loadData()
    {
        return function($viewName, $view) {
            if ($viewName === 'EditTarifaFamilia') {
                $codtarifa = $this->getViewModelValue($this->getMainViewName(), 'codtarifa');
                $where = [new DataBaseWhere('codtarifa', $codtarifa)];
                $view->loadData('', $where, ['id' => 'DESC']);
            }
        };
    }
}
