<?php
namespace FacturaScripts\Plugins\Afip\Controller;

use FacturaScripts\Core\Lib\ExtendedController\ListController;

class ListPuntosVenta extends ListController
{
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'AFIP';
        $pageData['title'] = 'Puntos de venta';
        $pageData['icon'] = 'fas fa-file';

        return $pageData;
    }

    protected function createViews()
    {
        $this->addView('ListPuntosVenta', 'PuntosVenta');
        $this->addSearchFields('ListPuntosVenta', ['description']);
        $this->addOrderBy('ListPuntosVenta', ['codpv'], 'codpv');
    }
}
