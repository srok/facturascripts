<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Extension\Controller;

use FacturaScripts\Dinamic\Lib\Import\FamilyImport;

/**
 * Description of ListFamilia
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ListFamilia
{

    public function createViews()
    {
        return function() {
            if ($this->user->admin) {
                /// import button
                $newButton = [
                    'action' => 'import-families',
                    'color' => 'warning',
                    'icon' => 'fas fa-file-import',
                    'label' => 'import-families',
                    'type' => 'modal'
                ];
                $this->addButton('ListFamilia', $newButton);
            }
        };
    }

    public function execPreviousAction()
    {
        return function($action) {
            if ($action === 'import-families') {
                $this->importFamiliesAction();
            }
        };
    }

    public function importFamiliesAction()
    {
        return function() {
            $uploadFile = $this->request->files->get('familiesfile');
            if (!FamilyImport::isValidFile($uploadFile)) {
                $this->toolBox()->i18nLog()->error('file-not-supported');
                $this->toolBox()->i18nLog()->error($uploadFile->getMimeType());
                return true;
            }

            $mode = $this->request->request->get('mode', FamilyImport::INSERT_MODE);
            if ($mode === FamilyImport::ADVANCED_MODE) {
                $newCsvFile = FamilyImport::advancedImport($uploadFile);
                $this->redirect($newCsvFile->url());
                return true;
            }

            $num = FamilyImport::importCSV($uploadFile->getPathname(), $mode);
            $this->toolBox()->i18nLog()->notice('items-added-correctly', ['%num%' => $num]);
            return true;
        };
    }
}
