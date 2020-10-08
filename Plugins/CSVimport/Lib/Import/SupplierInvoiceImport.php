<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\Import;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Serie;

/**
 * Description of SupplierInvoiceImport
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class SupplierInvoiceImport extends CsvImporClass
{

    const MAX_INVOICES_IMPORT = 1000;

    /**
     * 
     * @param array $line
     *
     * @return Proveedor
     */
    protected static function getFactusolSupplier($line)
    {
        /// get code
        $parts = \explode('-', $line['Proveedor']);
        $code = (int) $parts[0];
        if (empty($code)) {
            $code = 99999;
        }

        $supplier = new Proveedor();
        if (!$supplier->loadFromCode($code)) {
            /// save new supplier
            $supplier->cifnif = '';
            $supplier->codproveedor = $code;
            $supplier->nombre = $parts[1];
            $supplier->save();
        }

        return $supplier;
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
        } elseif ($csv->titles[0] === 'S.' && $csv->titles[2] === 'Factura recibida') {
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
        return 'supplier-invoices';
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
            $invoice = new FacturaProveedor();
            $where = [
                new DataBaseWhere('codserie', $row['S.']),
                new DataBaseWhere('numero', $row['Núm.']),
            ];
            if ($invoice->loadFromCode('', $where) && $mode === static::INSERT_MODE) {
                continue;
            }

            /// save new invoice
            $invoice->setSubject(static::getFactusolSupplier($row));
            $invoice->codserie = $row['S.'];
            $invoice->setDate(static::getFixedDate($row['Fecha']), \date('H:i:s'));
            $invoice->numero = $row['Núm.'];
            $invoice->numproveedor = $row['Factura recibida'];
            if (!$invoice->save()) {
                break;
            }

            $num++;
            $newLine = $invoice->getNewLine();
            $newLine->descripcion = $row['Proveedor'];
            $newLine->pvpunitario = static::getFloat($row['Base']);
            $newLine->iva = static::getFactusolIVA($row['IVA'], $row['Base']);
            $newLine->recargo = static::getFactusolIVA($row['Rec'], $row['Base']);
            $newLine->save();

            $docTools = new BusinessDocumentTools();
            $docTools->recalculate($invoice);
            $invoice->save();

            /// paid invoice?
            if ($row['Estado'] === 'Pagado') {
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
