<?php
/**
 * This file is part of PreciosBulk plugin for FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\PreciosBulk\Controller;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\PanelController;

class EditPreciosBulk extends PanelController
{

    /**
     * Indicates if the main view has data or is empty.
     *
     * @var bool
     */
    public $hasData = TRUE;


    public function getModelClassName()
    {
      return 'PreciosBulk';
    }
    public function getPageData()
    {
      $pagedata = parent::getPageData();
      $pagedata['title'] = 'precios-bulk-prov';
      $pagedata['menu'] = 'purchases';
      $pagedata['icon'] = 'fas fa-file-invoice-dollar';

      return $pagedata;
    }

    protected function createViews()
    {
      $this->setTabsPosition('bottom');

      $this->editPreciosView(  );
      $this->listPreciosView(  );

    }


    protected function editPreciosView( $viewName = 'EditPreciosBulk' ){
      $this->addEditView($viewName, 'PreciosBulk', 'Actualizar precios');

      $this->setSettings($viewName, 'btnSave', false);
      $this->setSettings($viewName, 'btnUndo', false);
      $this->setSettings($viewName, 'btnDelete', false);
    }

    protected function listPreciosView( $viewName = 'ListPreciosBulk' ){
      $this->addListView($viewName, 'PreciosBulk', 'Precios Bulk');


      $this->setSettings($viewName, 'btnNew', false);
      $this->setSettings($viewName, 'btnDelete', false);
      $this->setSettings($viewName, 'clickable', false);

      $this->views[$viewName]->addOrderBy( ['fecha'], 'update-time', 2);
    


    }

    protected function execPreviousAction( $action ){
      switch ($action) {
        case 'bulk-update-prices':
        return $this->updatePreciosBulkAction();
        break;


      }

      return parent::execPreviousAction( $action );
    }

    protected function loadData($viewName, $view)
    {


      switch ($viewName) {
        case 'EditPreciosBulk':

        $this->views['EditPreciosBulk']->model->loadFromData( $this->request->request->all() );


        break;

        case 'ListPreciosBulk':
        $view->loadData();
        $view->totalAmounts = [];




        break;

      }
    }

    protected function updatePreciosBulkAction(){

      $idprov = (int) $this->request->request->get('codproveedor');
      $codfamilia = (int) $this->request->request->get('codfamilia');

      if( !$idprov ){

        $this->toolBox()->i18nLog()->error('supplier-required');
        return false;
      }

      $update_producto = ''; 
      $and_familia = ''; 

      if( $codfamilia ){
        $and_familia = "AND productos.codfamilia = $codfamilia";
      }

      $neto = (float) $this->request->request->get('neto') ?? 0;
      $dtopor = (float) $this->request->request->get('dtopor') ?? 0;
      $dtopor2 = (float) $this->request->request->get('dtopor2') ?? 0;
      $dtopor3 = (float) $this->request->request->get('dtopor3') ?? 0;
      $dtopor4 = (float) $this->request->request->get('dtopor4') ?? 0;
      $dtopor5 = (float) $this->request->request->get('dtopor5') ?? 0;
      $fletepor = (float) $this->request->request->get('flete') ?? 0;
      $utilidad = (float) $this->request->request->get('utilidad') ?? 0;
      $netoporc = (float) 1 + ($neto / 100);
      $dre = (float) 1 - ( ( 1 - $dtopor / 100 ) * ( 1 - $dtopor2 / 100 ) * ( 1 - $dtopor3 / 100 ) * ( 1 - $dtopor4 / 100 ) * ( 1 - $dtopor5 / 100 ) );
      $margenporc = (float) 1 + ($utilidad / 100);


      if( $this->request->request->get('aplicar_costo') ){
       $update_producto = ",variantes.coste = ROUND((variantes.coste * $netoporc) - (variantes.coste * $netoporc * $dre), 2),
       variantes.margen = $utilidad,
       variantes.precio = ROUND(((productosprov.neto * $netoporc) - (productosprov.neto * $netoporc * $dre)) * $margenporc, 2),
       productos.precio = ROUND(((productosprov.neto * $netoporc) - (productosprov.neto * $netoporc * $dre)) * $margenporc, 2)";
     }

     $sql = "UPDATE productosprov 
     INNER JOIN productos ON productosprov.idproducto = productos.idproducto $and_familia
     INNER JOIN variantes ON variantes.idproducto = productos.idproducto
     SET 
     productosprov.actualizado = NOW(),
     productosprov.dtopor = $dtopor,
     productosprov.dtopor2 = $dtopor2,
     productosprov.dtopor3 = $dtopor3,
     productosprov.dtopor4 = $dtopor4,
     productosprov.dtopor5 = $dtopor5,
     productosprov.fletepor = $fletepor,
     productosprov.neto = ROUND(productosprov.neto * $netoporc, 2),
     productosprov.precio = ROUND((productosprov.neto * $netoporc) - (productosprov.neto * $netoporc * $dre), 2)
     $update_producto
     WHERE productosprov.codproveedor = $idprov
     ";

     $database = new DataBase(  );
     if($database->exec( $sql )){
       $this->toolBox()->i18nLog()->notice('price-update-complete');              
     }
     $bulklog = $this->request->request->all();
     $bulklog['fecha'] = date( 'Y-m-d H:i:s' );
     $this->views['EditPreciosBulk']->model->loadFromData(  $bulklog );
     $this->views['EditPreciosBulk']->model->save(  );
       //mprd( $sql );
   }
 }
