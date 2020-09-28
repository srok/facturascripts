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
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Lib\ExtendedController\BusinessDocumentView;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Description of PurchaseDocumentController
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class PurchaseDocumentController extends BusinessDocumentController
{

    /**
     * 
     * @return array
     */
    public function getCustomFields()
    {
        return [
            [
                'icon' => 'fas fa-hashtag',
                'label' => 'numsupplier',
                'name' => 'numproveedor',
                'maxlength' => '8'
                
            ]
        ];
    }

    /**
     * 
     * @return string
     */
    public function getNewSubjectUrl()
    {
        $proveedor = new Proveedor();
        return $proveedor->url('new') . '?return=' . $this->url();
    }

    /**
     * 
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * 
     * @return string
     */
    protected function getLineXMLView()
    {
        return 'PurchaseDocumentLine';
    }

    /**
     * 
     * @param BusinessDocumentView $view
     * @param array                $formData
     * 
     * @return string
     */
    protected function setSubject(&$view, $formData)
    {
        if (empty($formData['codproveedor'])) {
            return 'ERROR: ' . $this->toolBox()->i18n()->trans('supplier-not-found');
        }

        if ($view->model->codproveedor === $formData['codproveedor']) {
            return 'OK';
        }

        $proveedor = new Proveedor();
        if ($proveedor->loadFromCode($formData['codproveedor'])) {
            $view->model->setSubject($proveedor);
            return 'OK';
        }

        return 'ERROR: ' . $this->toolBox()->i18n()->trans('supplier-not-found');
    }

    /**
     * Run the autocomplete action.
     * Returns a JSON string for the searched values.
     *
     * @return array
     */
    protected function autocompleteAction(): array
    {
        $data = $this->requestGet(['field', 'fieldcode', 'fieldfilter', 'fieldtitle', 'formname', 'source', 'strict', 'term','codproveedor']);
        if ($data['source'] == '') {
            return $this->getAutocompleteValues($data['formname'], $data['field']);
        }

        $filter_json=json_decode($data['fieldfilter'],true);
        
        $where = [];
        $join='';

        switch ($data['fieldfilter']) {
            case 'productos_proveedor':
               $codproveedor =  $data['codproveedor'];
               if( $codproveedor ){
                $join = "INNER JOIN productosprov ON productosprov.idproducto = v.idproducto AND productosprov.codproveedor = $codproveedor";
                
               }
                break;
            
            default:

                foreach (DataBaseWhere::applyOperation($data['fieldfilter'] ?? '') as $field => $operation) {
                    $value = $this->request->get($field);
                    $where[] = new DataBaseWhere($field, $value, '=', $operation);
                } 
                break;
        }
            

    

        $results = [];
        $utils = $this->toolBox()->utils();
        foreach ($this->codeModel->search($data['source'], $data['fieldcode'], $data['fieldtitle'], $data['term'], $where, $join) as $value) {
            $results[] = ['key' => $utils->fixHtml($value->code), 'value' => $utils->fixHtml($value->description)];
        }

        if (empty($results) && '0' == $data['strict']) {
            $results[] = ['key' => $data['term'], 'value' => $data['term']];
        } elseif (empty($results)) {
            $results[] = ['key' => null, 'value' => $this->toolBox()->i18n()->trans('no-data')];
        }

        return $results;
    }

     /**
     * Load views and document.
     */
    protected function createViews()
    {
        /// tabs on top
        $this->setTabsPosition('top');

        /// document tab
        $fullModelName = self::MODEL_NAMESPACE . $this->getModelClassName();
        $view = new BusinessDocumentView($this->getLineXMLView(), 'new', $fullModelName);
        $view->template =  'Master/PurchaseDocumentView.html.twig';

        $this->addCustomView($view->getViewName(), $view);
        $this->setSettings($view->getViewName(), 'btnPrint', true);

        /// edit tab
        $viewName = 'Edit' . $this->getModelClassName();
        $this->addEditView($viewName, $this->getModelClassName(), 'detail', 'fas fa-edit');

        /// disable delete button
        $this->setSettings($viewName, 'btnDelete', false);
    }
}
