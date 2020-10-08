<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Extension\Controller;

use FacturaScripts\Dinamic\Lib\Import\ProductImport;

/**
 * Description of ListProducto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListProducto
{

    public function createViews()
    {
        return function() {
            if ($this->user->admin) {
                /// import button
                $newButton = [
                    'action' => 'import-products',
                    'color' => 'warning',
                    'icon' => 'fas fa-file-import',
                    'label' => 'import-products',
                    'type' => 'modal'
                ];
                $this->addButton('ListProducto', $newButton);
            }
        };
    }

    public function execPreviousAction()
    {
        return function($action) {
            if ($action === 'import-products') {
                $this->importProductsAction();
            }
        };
    }

    public function importProductsAction()
    {
        return function() {
            $uploadFile = $this->request->files->get('productsfile');
            if (!ProductImport::isValidFile($uploadFile)) {
                $this->toolBox()->i18nLog()->error('file-not-supported');
                $this->toolBox()->i18nLog()->error($uploadFile->getMimeType());
                return true;
            }

            $mode = $this->request->request->get('mode', ProductImport::INSERT_MODE);
            if ($mode === ProductImport::ADVANCED_MODE) {
                $newCsvFile = ProductImport::advancedImport($uploadFile);
                $this->redirect($newCsvFile->url());
                return true;
            }

            $num = ProductImport::importCSV($uploadFile->getPathname(), $mode);
            $this->toolBox()->i18nLog()->notice('items-added-correctly', ['%num%' => $num]);
            return true;
        };
    }
}
