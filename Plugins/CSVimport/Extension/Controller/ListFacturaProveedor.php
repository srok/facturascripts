<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Extension\Controller;

use FacturaScripts\Dinamic\Lib\Import\SupplierInvoiceImport;

/**
 * Description of ListFacturaProveedor
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListFacturaProveedor
{

    public function createViews()
    {
        return function() {
            if ($this->user->admin) {
                /// import button
                $newButton = [
                    'action' => 'import-invoices',
                    'color' => 'warning',
                    'icon' => 'fas fa-file-import',
                    'label' => 'import-invoices',
                    'type' => 'modal'
                ];
                $this->addButton('ListFacturaProveedor', $newButton);
            }
        };
    }

    public function execPreviousAction()
    {
        return function($action) {
            if ($action === 'import-invoices') {
                $this->importInvoicesAction();
            }
        };
    }

    public function importInvoicesAction()
    {
        return function() {
            $uploadFile = $this->request->files->get('invoicesfile');
            if (!SupplierInvoiceImport::isValidFile($uploadFile)) {
                $this->toolBox()->i18nLog()->error('file-not-supported');
                $this->toolBox()->i18nLog()->error($uploadFile->getMimeType());
                return true;
            }

            $mode = $this->request->request->get('mode', SupplierInvoiceImport::INSERT_MODE);
            if ($mode === SupplierInvoiceImport::ADVANCED_MODE) {
                $newCsvFile = SupplierInvoiceImport::advancedImport($uploadFile);
                $this->redirect($newCsvFile->url());
                return true;
            }

            $num = SupplierInvoiceImport::importCSV($uploadFile->getPathname(), $mode);
            $this->toolBox()->i18nLog()->notice('items-added-correctly', ['%num%' => $num]);
            return true;
        };
    }
}
