<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Extension\Controller;

use FacturaScripts\Dinamic\Lib\Import\ManufacturerImport;

/**
 * Description of ListFabricante
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListFabricante
{

    public function createViews()
    {
        return function() {
            if ($this->user->admin) {
                /// import button
                $newButton = [
                    'action' => 'import-manufacturers',
                    'color' => 'warning',
                    'icon' => 'fas fa-file-import',
                    'label' => 'import-manufacturers',
                    'type' => 'modal'
                ];
                $this->addButton('ListFabricante', $newButton);
            }
        };
    }

    public function execPreviousAction()
    {
        return function($action) {
            if ($action === 'import-manufacturers') {
                $this->importManufacturersAction();
            }
        };
    }

    public function importManufacturersAction()
    {
        return function() {
            $uploadFile = $this->request->files->get('manufile');
            if (!ManufacturerImport::isValidFile($uploadFile)) {
                $this->toolBox()->i18nLog()->error('file-not-supported');
                $this->toolBox()->i18nLog()->error($uploadFile->getMimeType());
                return true;
            }

            $mode = $this->request->request->get('mode', ManufacturerImport::INSERT_MODE);
            if ($mode === ManufacturerImport::ADVANCED_MODE) {
                $newCsvFile = ManufacturerImport::advancedImport($uploadFile);
                $this->redirect($newCsvFile->url());
                return true;
            }

            $num = ManufacturerImport::importCSV($uploadFile->getPathname(), $mode);
            $this->toolBox()->i18nLog()->notice('items-added-correctly', ['%num%' => $num]);
            return true;
        };
    }
}
