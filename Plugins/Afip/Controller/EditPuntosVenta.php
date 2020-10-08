<?php
namespace FacturaScripts\Plugins\Afip\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

class EditPuntosVenta extends EditController
{
    public function getModelClassName()
    {
        return 'PuntosVenta';
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['menu'] = 'AFIP';
        $pagedata['title'] = 'project';
        $pagedata['icon'] = 'fas fa-tasks';

        return $pagedata;
    }
}
