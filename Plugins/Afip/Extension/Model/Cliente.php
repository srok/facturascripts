<?php
namespace FacturaScripts\Plugins\Afip\Extension\Model;
use FacturaScripts\Plugins\Afip\Lib\RegimenIVA;
use FacturaScripts\Core\Model\CodeModel;
class Cliente{



    public function saveBefore()
    {  


        return function() {
            /**
             * Toma el régimen de la empresa por defecto. 
             */
            
            $idempresa = $this->toolBox()->appSettings()->get('default','idempresa');

            $empresa = CodeModel::get('empresas','idempresa',$idempresa ,'regimeniva');

            $regimenDefault = $empresa->description;

            $this->codserie = RegimenIVA::defaultSerie($regimenDefault,$this->regimeniva);



        };
    }
}
