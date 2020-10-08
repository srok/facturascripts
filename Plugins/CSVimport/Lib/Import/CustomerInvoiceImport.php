<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\Import;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\Serie;

/**
 * Description of CustomerInvoiceImport
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CustomerInvoiceImport extends CsvImporClass
{

    const MAX_INVOICES_IMPORT = 1000;

    /**
     * 
     * @param array $line
     *
     * @return Cliente
     */
    protected static function getFactusolCustomer($line)
    {
        /// get code
        $parts = \explode('-', $line['Cliente']);
        $code = (int) $parts[0];
        if (empty($code)) {
            $code = 99999;
        }

        $customer = new Cliente();
        if (!$customer->loadFromCode($code)) {
            /// save new customer
            $customer->cifnif = '';
            $customer->codcliente = $code;
            $customer->nombre = $parts[1];
            $customer->save();
        }

        return $customer;
    }

    /**
     * 
     * @param string $filePath
     *
     * @return int
     */
    protected static function getFileType(string $filePath): int
    {
        $csv = static::getCsv($filePath);

        if (\count($csv->titles) < 2) {
            return static::TYPE_NONE;
        } elseif ($csv->titles[0] === 'S.' && $csv->titles[3] === 'Cliente') {
            return static::TYPE_FACTUSOL;
        }

        return static::TYPE_NONE;
    }

    /**
     * 
     * @return string
     */
    protected static function getProfile()
    {
        return 'customer-invoices';
    }

    /**
     * 
     * @param string $filePath
     * @param string $mode
     *
     * @return int
     */
    protected static function importCSVfactusol(string $filePath, string $mode): int
    {
        $csv = static::getCsv($filePath);

        $num = 0;
        foreach ($csv->data as $row) {
            if (empty($row['S.']) || $num > static::MAX_INVOICES_IMPORT) {
                continue;
            }

            /// find serie
            $serie = new Serie();
            if (!$serie->loadFromCode($row['S.'])) {
                /// create new serie
                $serie->codserie = $row['S.'];
                $serie->descripcion = 'FactuSol #' . $row['S.'];
                $serie->save();
            }

            /// find invoice
            $invoice = new FacturaCliente();
            $where = [
                new DataBaseWhere('codserie', $row['S.']),
                new DataBaseWhere('numero', $row['Num.']),
            ];
            if ($invoice->loadFromCode('', $where) && $mode === static::INSERT_MODE) {
                continue;
            }

            /// save new invoice
            $invoice->setSubject(static::getFactusolCustomer($row));
            $invoice->codserie = $row['S.'];
            $invoice->setDate(static::getFixedDate($row['Fecha']), \date('H:i:s'));
            $invoice->numero = $row['Num.'];
            if (!$invoice->save()) {
                break;
            }

            $num++;
            $newLine = $invoice->getNewLine();
            $newLine->descripcion = $row['Cliente'];
            $newLine->pvpunitario = static::getFloat($row['Base']);
            $newLine->iva = static::getFactusolIVA($row['IVA'], $row['Base']);
            $newLine->recargo = static::getFactusolIVA($row['Rec.'], $row['Base']);
            $newLine->save();

            $docTools = new BusinessDocumentTools();
            $docTools->recalculate($invoice);
            $invoice->save();

            /// paid invoice?
            if ($row['Est.'] === 'Cobra') {
                foreach ($invoice->getReceipts() as $receipt) {
                    $receipt->fechapago = $invoice->fecha;
                    $receipt->pagado = true;
                    $receipt->save();
                }
            }

            static::toolBox()->log()->clear();
        }

        return $num;
    }

    /**
     * 
     * @param int    $type
     * @param string $filePath
     * @param string $mode
     *
     * @return int
     */
    protected static function importType($type, $filePath, $mode): int
    {
        switch ($type) {
            case static::TYPE_FACTUSOL:
                return static::importCSVfactusol($filePath, $mode);

            default:
                static::toolBox()->i18nLog()->error('file-not-supported-advanced');
                return 0;
        }
    }
}
