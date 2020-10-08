<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\TarifasAvanzadas\Extension\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\Tarifa;
use FacturaScripts\Dinamic\Model\Variante;
use FacturaScripts\Plugins\TarifasAvanzadas\Model\TarifaProducto;

/**
 * Description of EditProducto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditProducto
{

    public function createViews()
    {
        return function() {
            $icon = $this->toolBox()->appSettings()->get('default', 'coddivisa') === 'EUR' ?
            'fas fa-euro-sign' : 'fas fa-dollar-sign';
            $this->addHtmlView('tarifas', 'Tab/TarifaProducto', 'TarifaProducto', 'prices', $icon);

            /// disable price columns
            if( isset($this->views['EditVariante']) ){
                $this->views['EditVariante']->disableColumn('cost-price');
                $this->views['EditVariante']->disableColumn('margin');
                $this->views['EditVariante']->disableColumn('price');
            }

            /// add javascript
            AssetManager::add('js', \FS_ROUTE . '/Dinamic/Assets/JS/EditProductoPricesTab.js');
        };
    }

    public function getPriceTabItems()
    {
        return function() {
            $rateModel = new Tarifa();
            $rates = $rateModel->all();

            $items = [];
            $product = $this->getModel();
            foreach ($product->getVariants() as $variant) {
                $items[] = [
                    'decimals' => FS_NF0,
                    'pricetax' => $this->roundPrice($variant->priceWithTax()),
                    'rates' => $this->getVariantRates($variant, $product, $rates),
                    'tax' => $product->getTax()->iva,
                    'variant' => $variant
                ];
            }

            return $items;
        };
    }

    protected function editPriceTabAction()
    {
        return function() {
            $variant = new Variante();
            if (false === $variant->loadFromCode($this->request->request->get('idvariante'))) {
                return true;
            }

            /// update variant
            $variant->coste = (float) $this->request->request->get('coste');
            $variant->margen = (float) $this->request->request->get('margen');
            $variant->precio = (float) $this->request->request->get('precio');

            $priceWithTax = $this->request->request->get('precioimp');
            if (!empty($priceWithTax)) {
                $variant->setPriceWithTax((float) $priceWithTax);
            }

            if (false === $variant->save()) {
                $this->toolBox()->i18nLog()->warning('record-save-error');
                return true;
            }

            /// save fixed prices
            for ($num = 1; $num < 50; $num++) {
                $this->updateTarifaProducto($variant, $num);
            }

            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        };
    }

    protected function execPreviousAction()
    {
        return function($action) {
            switch ($action) {
                case 'edit-price-tab':
                $this->editPriceTabAction();
                break;

                case 'reset-rates':
                $this->resetRatesAction();
                break;
            }

            return true;
        };
    }

    protected function getVariantRates()
    {
        return function($variant, $product, $rates) {
            $items = [];
            foreach ($rates as $rate) {
                $price = $rate->applyTo($variant, $product);
                $tax = $product->getTax()->iva;
                $items[] = [
                    'codtarifa' => $rate->codtarifa,
                    'explain' => $rate->explainTo($variant, $product),
                    'price' => $this->roundPrice($price),
                    'pricetax' => $this->roundPrice($price * (100 + $tax) / 100)
                ];
            }

            return $items;
        };
    }

    protected function resetRatesAction()
    {
        return function() {
            $variant = new Variante();
            if (false === $variant->loadFromCode($this->request->request->get('idvariante'))) {
                return true;
            }

            for ($num = 1; $num < 50; $num++) {
                $codtarifa = $this->request->request->get('codtarifa_' . $num);
                $tarifaProducto = new TarifaProducto();
                $where = [
                    new DataBaseWhere('codtarifa', $codtarifa),
                    new DataBaseWhere('referencia', $variant->referencia)
                ];
                if ($tarifaProducto->loadFromCode('', $where)) {
                    $tarifaProducto->delete();
                }
            }

            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        };
    }

    protected function roundPrice()
    {
        return function($number) {
            return \number_format($number, FS_NF0, '.', '');
        };
    }

    protected function updateTarifaProducto()
    {
        return function($variant, $num) {
            $codtarifa = $this->request->request->get('codtarifa_' . $num);
            $precio = $this->request->request->get('precio_' . $num);
            $precioimp = $this->request->request->get('precioimp_' . $num);
            if (empty($precio) && empty($precioimp)) {
                return;
            }

            $tarifaProducto = new TarifaProducto();
            $where = [
                new DataBaseWhere('codtarifa', $codtarifa),
                new DataBaseWhere('referencia', $variant->referencia)
            ];
            if (false === $tarifaProducto->loadFromCode('', $where)) {
                $tarifaProducto->codtarifa = $codtarifa;
                $tarifaProducto->referencia = $variant->referencia;
            }

            $tarifaProducto->pvp = $precio;
            if (!empty($precioimp)) {
                $tarifaProducto->setPriceWithTax((float) $precioimp);
            }

            $tarifaProducto->save();
        };
    }
}
