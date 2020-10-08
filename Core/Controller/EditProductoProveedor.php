<?php
/**
 * This file is part of FacturaScripts
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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

use FacturaScripts\Dinamic\Model\ProductoProveedor;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Controller to edit a single item from the AgenciaTransporte model
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Artex Trading sa         <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class EditProductoProveedor extends EditController
{

    /**
     * Returns the model name.
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'ProductoProveedor';
    }

    /**
     * Run the controller after actions.
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
          

            case 'edit':
            $ProductoProveedor = new ProductoProveedor(  );
            $ProductoProveedor->loadFromCode( $this->request->get( 'code' ) );
            if( $this->request->get( 'aplicar_costo' ) ){
                  $Variante = new Variante(  );
                  $where = [new DataBaseWhere( 'idproducto', $ProductoProveedor->idproducto )];
                  $Variante->loadFromCode( '',$where );
                  $Variante->coste = $ProductoProveedor->neto;
                  $Variante->save();
            }
          
                break;
        }
        parent::execAfterAction( $action );
    }


}
