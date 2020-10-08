<?php

namespace FacturaScripts\Plugins\ProveedoresAvanzado\Extension\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of EditProveedor
 *
 * @author Srok
 */
class EditProveedor
{

    protected function createViews()
    {

        return function() {
            $this->createViewStock();
        };
    }

    protected function createViewStock()
    {
        return function($viewName = 'ListStock') {

         $this->addListView($viewName, 'Stock', 'stock', 'fas fa-cubes');
         $this->views[$viewName]->addOrderBy(['referencia'], 'reference');
         $this->views[$viewName]->addOrderBy(['cantidad'], 'quantity');
         $this->views[$viewName]->addOrderBy(['disponible'], 'available');
         $this->views[$viewName]->addOrderBy(['reservada'], 'reserved');
         $this->views[$viewName]->addOrderBy(['pterecibir'], 'pending-reception');
         $this->views[$viewName]->searchFields = ['referencia', 'refproveedor'];





        /// filters
         $selectValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
         $this->views[$viewName]->addFilterSelect('codalmacen', 'warehouse', 'codalmacen', $selectValues);
         $this->views[$viewName]->addFilterNumber('stockmin', 'stockmin', 'stockmin','<=');
         $this->views[$viewName]->addFilterNumber('reservada', 'reserved', 'reservada','>=');
        

         $this->views[$viewName]->addFilterSelectWhere('stock',
            [

                ['label' => 'Todos los estados', 'where' => []],
                ['label' => 'Stock Crítico', 'where' => [ new DataBaseWhere( 'disponible <= stockmin' ) ]],
                
            ]
        );

        /// disable buttons
         $this->setSettings($viewName, 'btnNew', false);
     };
 }

 protected function loadData()
 {
    return function($viewName, $view) {
        if ($viewName !== 'ListStock') {
            return;
        }

        $codproveedor = $this->getViewModelValue('EditProveedor', 'codproveedor');
            //$where = [new DataBaseWhere('codproveedor', $codproveedor)];
        $where = [];

        $view->loadData('', $where, [], 0 , \FS_ITEM_LIMIT, 'INNER JOIN productosprov on productosprov.codproveedor ='. $codproveedor . ' and stocks.idproducto = productosprov.idproducto','stocks.*');
    };
}
}
