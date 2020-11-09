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

use FacturaScripts\Core\Lib\ExtendedController\SalesDocumentController as SalesDocumentControllerCore;
// use FacturaScripts\Core\Lib\ExtendedController\BusinessDocumentView;
// use FacturaScripts\Dinamic\Model\Cliente;
// 
/*use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Dinamic\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Lib\ExtendedController\SalesDocumentController;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\FacturaCliente;*/
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\BusinessDocumentCode;
use FacturaScripts\Dinamic\Model\EstadoDocumento;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\PuntosVenta;
use FacturaScripts\Plugins\Afip\Lib\DocumentoAfip;

/**
* Description of SalesDocumentController
*
* @author Carlos García Gómez <carlos@facturascripts.com>
*/
abstract class SalesDocumentController extends SalesDocumentControllerCore
{

  const PV_ELECTRONICO = 2;
  const PV_MANUAL = 1;
  const PV_WEB = 3;

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

    if( count( $data['lines'] ) < 1){
      $this->response->setContent($this->toolBox()->i18n()->trans('no-lines'));
      return false;
    }

//load extrainfo for code change if altpattern is enabled

//$data = $this->getBusinessFormData();

    $newEstado = $this->request->request->get('idestado'); 

    $estadoDocumento = new EstadoDocumento();

    $estadoDocumento->loadFromCode($newEstado);

    if($estadoDocumento->altpattern){

      $this->getInvoiceNumber($this->views[$this->active]->model,$data);

      BusinessDocumentCode::getNewCode($this->views[$this->active]->model,false,true);

    }


/// save
    $result = $this->saveDocumentResult($this->views[$this->active], $data);
    $this->response->setContent($result);

// Event finish
    $this->views[$this->active]->model->pipe('finish');
    return false;
  }

  protected function checkNumero2Valid($nro2, $pv, $serie){

    if(!$nro2){
      die("numero2-empty");
    }

    $where = [new DataBaseWhere('codpv', $pv),
    new DataBaseWhere('numero2', $nro2),
    new DataBaseWhere('codserie', $serie)
  ];

  $document = $this->views[$this->active]->model->all($where);

  if($document){
    die("numero2-duplicated");
  }

  return true;
}


protected function getInvoiceNumber(&$document,Array $data){
//ver tipo de punto de venta


  $puntoVenta = new PuntosVenta();
  $pv = $puntoVenta->get($data['form']['codpv']);
  $serie = $data['custom']['codserie'];
  $numero2 = $data['form']['numero2'];

  switch ($pv->tipo) {
    case self::PV_WEB:
    case self::PV_MANUAL:

    $this->checkNumero2Valid($numero2,$pv->codpv,$serie);



    break;


    case self::PV_ELECTRONICO:
//Cargo el cliente desde la factura

    $cliente = new Cliente();

    $cliente->loadFromCode($data['subject']['codcliente']);

    $facturaAfip = new DocumentoAfip();

    $facturaAfip->create($document, $cliente, $data);
    break;
    default:
    die("punto-venta-sin-tipo");

    break;
  }



}
}
