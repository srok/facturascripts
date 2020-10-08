<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\Import;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\CuentaBancoCliente;

/**
 * Description of CustomerImport
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CustomerImport extends CsvImporClass
{

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
        } elseif ($csv->titles[0] === 'cifnif' && $csv->titles[1] === 'codagente') {
            return static::TYPE_FACTURASCRIPTS;
        } elseif ($csv->titles[0] === 'codcliente' && $csv->titles[1] === 'nombre') {
            return static::TYPE_FACTURASCRIPTS_2017;
        } elseif ($csv->titles[0] === 'Código' && \in_array('N.I.F.', $csv->titles)) {
            return static::TYPE_FACTUSOL;
        } elseif ($csv->titles[0] === 'Cód' && $csv->titles[1] === 'Nombre') {
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
        return 'customers';
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
            /// find customer
            $customer = new Cliente();
            $code = $row['Código'] ?? $row['Cód'];
            if (empty($code) || ($customer->loadFromCode($code) && $mode === static::INSERT_MODE)) {
                continue;
            }

            /// save new customer
            $customer->codcliente = $code;
            $customer->cifnif = $row['N.I.F.'];
            $customer->nombre = substr($row['Nombre comercial'] ?? $row['Nombre'], 0, 100);
            $customer->telefono1 = $row['Teléfono'];

            /// optional fields
            if (isset($row['E-mail'])) {
                $customer->email = $row['E-mail'];
            }

            if (isset($row['Fax'])) {
                $customer->fax = $row['Fax'];
            }

            if (isset($row['Móvil'])) {
                $customer->telefono2 = $row['Móvil'];
            }

            if (isset($row['Nombre fiscal'])) {
                $customer->razonsocial = $row['Nombre fiscal'];
            }

            if (!$customer->save()) {
                continue;
            }

            $num++;
            foreach ($customer->getAdresses() as $address) {
                $address->direccion = $row['Domicilio'] ?? $row['Dirección'];
                $address->codpostal = $row['Cód. Postal'] ?? $row['C.P.'];
                $address->ciudad = $row['Población'];
                $address->provincia = $row['Provincia'];
                $address->save();
                break;
            }

            if (isset($row['IBAN del banco']) && isset($row['SWIFT del banco'])) {
                static::saveBankAccount($customer, $row['Banco'], $row['IBAN del banco'], $row['SWIFT del banco']);
            }
        }

        return $num;
    }

    /**
     * 
     * @param string $filePath
     * @param string $mode
     *
     * @return int
     */
    protected static function importCSVfs(string $filePath, string $mode): int
    {
        $csv = static::getCsv($filePath);

        $num = 0;
        foreach ($csv->data as $row) {
            /// find customer
            $customer = new Cliente();
            if (empty($row['codcliente']) || ($customer->loadFromCode($row['codcliente']) && $mode === static::INSERT_MODE)) {
                continue;
            }

            /// exclude some important fields unless update
            $exclude = $mode === static::INSERT_MODE ? [
                'codagente', 'coddivisa', 'codgrupo', 'codpago', 'codproveedor',
                'codretencion', 'codserie', 'codtarifa', 'idcontactofact', 'idcontactoenv'
                ] : [];

            /// save new customer
            $customer->loadFromData($row, $exclude);
            if ($customer->save()) {
                $num++;
            }
        }

        return $num;
    }

    /**
     * 
     * @param string $filePath
     * @param string $mode
     *
     * @return int
     */
    protected static function importCSVfs2017(string $filePath, string $mode): int
    {
        $csv = static::getCsv($filePath);

        $num = 0;
        foreach ($csv->data as $row) {
            /// find customer
            $customer = new Cliente();
            if (empty($row['codcliente']) || ($customer->loadFromCode($row['codcliente']) && $mode === static::INSERT_MODE)) {
                continue;
            }

            /// exclude some important fields unless update
            $exclude = $mode === static::INSERT_MODE ? [
                'codagente', 'coddivisa', 'codgrupo', 'codpago', 'codproveedor',
                'codretencion', 'codserie', 'codtarifa', 'idcontactofact', 'idcontactoenv'
                ] : [];

            /// save new customer
            $customer->loadFromData($row, $exclude);
            if (!$customer->save()) {
                continue;
            }

            $num++;
            foreach ($customer->getAdresses() as $address) {
                $address->direccion = $row['direccion'];
                $address->codpostal = $row['codpostal'];
                $address->ciudad = $row['ciudad'];
                $address->provincia = $row['provincia'];
                $address->codpais = $row['pais'];
                $address->save();
                break;
            }

            if (!empty($row['iban']) || !empty($row['swift'])) {
                static::saveBankAccount($customer, 'Bank', $row['iban'], $row['swift']);
            }
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

            case static::TYPE_FACTURASCRIPTS:
                return static::importCSVfs($filePath, $mode);

            case static::TYPE_FACTURASCRIPTS_2017:
                return static::importCSVfs2017($filePath, $mode);

            default:
                static::toolBox()->i18nLog()->error('file-not-supported-advanced');
                return 0;
        }
    }

    /**
     * 
     * @param Cliente $customer
     * @param string  $bankName
     * @param string  $iban
     * @param string  $swift
     */
    protected static function saveBankAccount($customer, $bankName, $iban, $swift)
    {
        /// Find customer bank accounts
        $bankAcountModel = new CuentaBancoCliente();
        $where = [new DataBaseWhere('codcliente', $customer->codcliente)];
        foreach ($bankAcountModel->all($where) as $bank) {
            $bank->descripcion = $bankName;
            $bank->iban = $iban;
            $bank->swift = $swift;
            $bank->save();
            return;
        }

        /// No previous bank accounts?Then create a new one
        $newBank = new CuentaBancoCliente();
        $newBank->codcliente = $customer->codcliente;
        $newBank->descripcion = $bankName;
        $newBank->iban = $iban;
        $newBank->swift = $swift;
        $newBank->save();
    }
}
