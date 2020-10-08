<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Extension\Controller;

use FacturaScripts\Dinamic\Lib\Import\SupplierImport;

/**
 * Description of ListProveedor
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListProveedor
{

    public function createViews()
    {
        return function() {
            if ($this->user->admin) {
                /// import button
                $newButton = [
                    'action' => 'import-suppliers',
                    'color' => 'warning',
                    'icon' => 'fas fa-file-import',
                    'label' => 'import-suppliers',
                    'type' => 'modal'
                ];
                $this->addButton('ListProveedor', $newButton);
            }
        };
    }

    public function execPreviousAction()
    {
        return function($action) {
            if ($action === 'import-suppliers') {
                $this->importSuppliersAction();
            }
        };
    }

    public function importSuppliersAction()
    {
        return function() {
            $uploadFile = $this->request->files->get('suppliersfile');
            if (!SupplierImport::isValidFile($uploadFile)) {
                $this->toolBox()->i18nLog()->error('file-not-supported');
                $this->toolBox()->i18nLog()->error($uploadFile->getMimeType());
                return true;
            }

            $mode = $this->request->request->get('mode', SupplierImport::INSERT_MODE);
            if ($mode === SupplierImport::ADVANCED_MODE) {
                $newCsvFile = SupplierImport::advancedImport($uploadFile);
                $this->redirect($newCsvFile->url());
                return true;
            }

            $num = SupplierImport::importCSV($uploadFile->getPathname(), $mode);
            $this->toolBox()->i18nLog()->notice('items-added-correctly', ['%num%' => $num]);
            return true;
        };
    }
}
