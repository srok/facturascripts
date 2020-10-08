<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\TarifasAvanzadas\Extension\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of EditGrupoClientes
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditGrupoClientes
{

    public function createViews()
    {
        return function() {
            $viewName = 'EditDescuentoCliente';
            $this->addEditListView($viewName, 'DescuentoCliente', 'discounts', 'fas fa-tags');

            /// disable columns
            $this->views[$viewName]->disableColumn('customer');
            $this->views[$viewName]->disableColumn('customer-group');
        };
    }

    public function loadData()
    {
        return function($viewName, $view) {
            if ($viewName === 'EditDescuentoCliente') {
                $codgrupo = $this->getViewModelValue($this->getMainViewName(), 'codgrupo');
                $where = [new DataBaseWhere('codgrupo', $codgrupo)];
                $view->loadData('', $where, ['prioridad' => 'DESC', 'id' => 'ASC']);
            }
        };
    }
}
