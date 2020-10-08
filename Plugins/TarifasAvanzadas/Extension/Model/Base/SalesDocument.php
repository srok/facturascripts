<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\TarifasAvanzadas\Extension\Model\Base;

use FacturaScripts\Plugins\TarifasAvanzadas\Model\DescuentoCliente;

/**
 * Description of SalesDocument
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class SalesDocument
{

    public function getNewProductLine()
    {
        return function($newLine, $variant, $product) {
            $subject = $this->getSubject();
            $discountModel = new DescuentoCliente();
            $order = ['prioridad' => 'DESC'];
            foreach ($discountModel->all([], $order, 0, 0) as $discount) {
                if (false === $discount->enabled() ||
                    false === $discount->appliesToCustomer($subject) ||
                    false === $discount->appliesToProduct($product, $variant)) {
                    continue;
                }

                $newLine->dtopor = $discount->applyDiscount($newLine->dtopor);
                if (false === $discount->acumular) {
                    break;
                }
            }
        };
    }
}
