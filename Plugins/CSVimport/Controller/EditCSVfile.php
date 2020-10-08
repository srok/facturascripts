<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Description of EditCSVfile
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditCSVfile extends EditController
{

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'CSVfile';
    }

    /**
     * 
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['title'] = 'csv-file';
        $pageData['icon'] = 'fas fa-file-csv';
        return $pageData;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');
        $this->createViewsFields();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsFields(string $viewName = 'CSVfields')
    {
        $this->addHtmlView($viewName, 'CSVfields', 'CSVfile', 'fields', 'fas fa-cogs');
    }

    /**
     * 
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        parent::execAfterAction($action);
        if ($action === 'import') {
            $this->importCsvAction();
        }
    }

    protected function importCsvAction()
    {
        $options = [];
        foreacH ($this->request->request->all() as $key => $value) {
            if ($key != 'action' && !empty($value)) {
                $options[$key] = $value;
            }
        }

        $this->getModel()->setOptions($options);
        $num = $this->getModel()->getProfile()->import();
        $this->toolBox()->i18nLog()->notice('items-added-correctly', ['%num%' => $num]);
    }
}
