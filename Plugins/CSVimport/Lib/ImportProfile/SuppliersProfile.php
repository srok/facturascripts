<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\ImportProfile;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\CuentaBancoProveedor;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Description of SuppliersProfile
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class SuppliersProfile extends ProfileClass
{

    /**
     * 
     * @return array
     */
    public function getDataFields(): array
    {
        return [
            'proveedores.codproveedor' => ['title' => 'supplier-code'],
            'proveedores.nombre' => ['title' => 'name'],
            'proveedores.razonsocial' => ['title' => 'business-name'],
            'proveedores.cifnif' => ['title' => 'cifnif'],
            'proveedores.telefono1' => ['title' => 'phone'],
            'proveedores.telefono2' => ['title' => 'phone2'],
            'proveedores.fax' => ['title' => 'fax'],
            'proveedores.email' => ['title' => 'email'],
            'proveedores.web' => ['title' => 'web'],
            'proveedores.codsubcuenta' => ['title' => 'subaccount'],
            'contactos.direccion' => ['title' => 'address'],
            'contactos.apartado' => ['title' => 'post-office-box'],
            'contactos.codpostal' => ['title' => 'zip-code'],
            'contactos.ciudad' => ['title' => 'city'],
            'contactos.provincia' => ['title' => 'province'],
            'cuentasbcopro.descripcion' => ['title' => 'bank'],
            'cuentasbcopro.iban' => ['title' => 'iban'],
            'cuentasbcopro.swift' => ['title' => 'swift']
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
        if (isset($item['proveedores.codproveedor']) && !empty($item['proveedores.codproveedor'])) {
            $where[] = new DataBaseWhere('codproveedor', $item['proveedores.codproveedor']);
        } elseif (isset($item['proveedores.nombre']) && !empty($item['proveedores.nombre'])) {
            $where[] = new DataBaseWhere('nombre', $item['proveedores.nombre']);
        } elseif (isset($item['proveedores.cifnif']) && !empty($item['proveedores.cifnif'])) {
            $where[] = new DataBaseWhere('cifnif', $item['proveedores.cifnif']);
        }

        if (empty($where)) {
            return false;
        }

        $supplier = new Proveedor();
        if ($supplier->loadFromCode('', $where) && $this->mode === static::INSERT_MODE) {
            return false;
        }

        $this->setModelValues($supplier, $item, 'proveedores.');
        if ($supplier->save()) {
            $this->importAddress($supplier, $item);
            $this->importBankAccount($supplier, $item);
            return true;
        }

        return false;
    }

    /**
     * 
     * @param Proveedor $supplier
     * @param array     $item
     *
     * @return bool
     */
    private function importAddress($supplier, $item): bool
    {
        foreach ($supplier->getAdresses() as $address) {
            $this->setModelValues($address, $item, 'contactos.');
            return $address->save();
        }

        return false;
    }

    /**
     * 
     * @param Proveedor $supplier
     * @param array     $item
     *
     * @return bool
     */
    private function importBankAccount($supplier, $item): bool
    {
        $description = $item['cuentasbcopro.descripcion'] ?? '';
        $iban = $item['cuentasbcopro.iban'] ?? '';
        $swift = $item['cuentasbcopro.swift'] ?? '';
        if (empty($iban) && empty($swift)) {
            return true;
        }

        $bankAccountModel = new CuentaBancoProveedor();
        $where = [new DataBaseWhere('codproveedor', $supplier->codproveedor)];
        foreach ($bankAccountModel->all($where) as $bank) {
            $bank->descripcion = $description;
            $bank->iban = $iban;
            $bank->swift = $swift;
            return $bank->save();
        }

        /// new bank account
        $newBankAccount = new CuentaBancoProveedor();
        $newBankAccount->codproveedor = $supplier->codproveedor;
        $newBankAccount->descripcion = $description;
        $newBankAccount->iban = $iban;
        $newBankAccount->swift = $swift;
        return $newBankAccount->save();
    }
}
