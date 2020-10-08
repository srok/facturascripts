<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\ImportProfile;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\FacturaCliente;

/**
 * Description of CustomerInvoicesProfile
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CustomerInvoicesProfile extends ProfileClass
{

    /**
     * 
     * @return array
     */
    public function getDataFields(): array
    {
        return [
            'facturascli.numero' => ['title' => 'number'],
            'facturascli.codigo' => ['title' => 'code'],
            'facturascli.fecha' => ['title' => 'date'],
            'facturascli.hora' => ['title' => 'hour'],
            'facturascli.codserie' => ['title' => 'serie'],
            'facturascli.codcliente' => ['title' => 'customer-code'],
            'facturascli.nombrecliente' => ['title' => 'name'],
            'facturascli.neto' => ['title' => 'net'],
            'facturascli.totaliva' => ['title' => 'taxes'],
            'facturascli.total' => ['title' => 'total']
        ];
    }

    /**
     * 
     * @param array $item
     *
     * @return bool
     */
    protected function importItem(array $item): bool
    {
        $where = [];
        if (isset($item['facturascli.codigo']) && !empty($item['facturascli.codigo'])) {
            $where[] = new DataBaseWhere('codigo', $item['facturascli.codigo']);
        } elseif (isset($item['facturascli.numero']) && !empty($item['facturascli.numero'])) {
            $where[] = new DataBaseWhere('numero', $item['facturascli.numero']);
            if (isset($item['facturascli.codserie']) && !empty($item['facturascli.codserie'])) {
                $where[] = new DataBaseWhere('codserie', $item['facturascli.codserie']);
            }
        }

        if (empty($where)) {
            return false;
        }

        if (isset($item['facturascli.fecha']) && !empty($item['facturascli.fecha'])) {
            $where[] = new DataBaseWhere('fecha', $this->getDate($item['facturascli.fecha']));
        }

        $invoice = new FacturaCliente();
        if ($invoice->loadFromCode('', $where) && $this->mode === static::INSERT_MODE) {
            return false;
        }

        $this->setModelValues($invoice, $item, 'facturascli.');

        /// empty cifnif?
        if (empty($invoice->cifnif)) {
            $invoice->cifnif = '';
        }

        if ($invoice->save()) {
            return true;
        }

        return false;
    }
}
