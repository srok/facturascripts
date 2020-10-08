<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Plugins\Afip\Lib\ExtendedController;

use FacturaScripts\Core\Lib\ExtendedController\BusinessDocumentView;
use FacturaScripts\Core\Lib\ExtendedController\PurchaseDocumentController as PurchaseDocumentControllerCore;
use FacturaScripts\Dinamic\Lib\BusinessDocumentCode;

use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\EstadoDocumento;

/**
 * Description of PurchaseDocumentController
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class PurchaseDocumentController extends PurchaseDocumentControllerCore
{

     /**
     * Saves the document.
     *
     * @return bool
     */
    protected function saveDocumentAction()
    {
        $this->setTemplate(false);
        if (!$this->permissions->allowUpdate) {
            $this->response->setContent($this->toolBox()->i18n()->trans('not-allowed-modify'));
            return false;
        }

        // duplicated request?
        if ($this->multiRequestProtection->tokenExist($this->request->request->get('multireqtoken', ''))) {
            $this->response->setContent($this->toolBox()->i18n()->trans('duplicated-request'));
            return false;
        }

        /// loads model
        $data = $this->getBusinessFormData();
        $this->views[$this->active]->model->setAuthor($this->user);
        $this->views[$this->active]->loadFromData($data['form']);
        $this->views[$this->active]->lines = $this->views[$this->active]->model->getLines();


        //load extrainfo for code change if altpattern is enabled

        $data = $this->getBusinessFormData();

        $newEstado = $this->request->request->get('idestado'); 

        $estadoDocumento = new EstadoDocumento();

        $estadoDocumento->loadFromCode($newEstado);

        if($estadoDocumento->altpattern){

          BusinessDocumentCode::getNewCode($this->views[$this->active]->model,false,true);

        }

        /// save
        $result = $this->saveDocumentResult($this->views[$this->active], $data);
        $this->response->setContent($result);

        // Event finish
        $this->views[$this->active]->model->pipe('finish');
        return false;
    }
}
