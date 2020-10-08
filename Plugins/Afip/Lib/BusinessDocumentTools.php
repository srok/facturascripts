<?

namespace FacturaScripts\Plugins\Afip\Lib;

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\ImpuestoZona;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Lib\RegimenIVA as DinRegimenIVA;
use FacturaScripts\Dinamic\Model\Serie;

use FacturaScripts\Core\Lib\BusinessDocumentTools as BusinessDocumentToolsCore;

class BusinessDocumentTools extends BusinessDocumentToolsCore{

    protected $ivadiscrimina = false;


     /**
     *
     * @param BusinessDocument $doc
     */
    protected function clearTotals(BusinessDocument &$doc)
    {
    	parent::clearTotals($doc);


    	$serie = new Serie();
        if ($serie->loadFromCode($doc->codserie)) {
            $this->ivadiscrimina = $serie->ivadiscrimina;
        }

    }



    /**
     *
     * @param BusinessDocumentLine $line
     */
    protected function recalculateLine(&$line)
    {
        $save = false;
        $newCodimpuesto = $this->recalculateLineTax($line);

        if ($this->siniva || $newCodimpuesto === null || $line->suplido) {
            $line->codimpuesto = null;
            $line->irpf = $line->iva = $line->recargo = 0.0;
            $save = true;
        } elseif ($newCodimpuesto !== $line->codimpuesto) {
            /// set new tax
            $line->codimpuesto = $newCodimpuesto;
            $line->iva = $line->getTax()->iva;
            $line->recargo = $line->getTax()->recargo;
            $save = true;
        }

        if ($line->recargo && $this->recargo === false) {
            $line->recargo = 0.0;
            $save = true;
        }

        if (!$this->ivadiscrimina){
        	 $line->codimpuesto = $newCodimpuesto;
        	 $line->iva=$line->getTax()->iva;
        	 $iva=$line->getTax()->iva;
    	  // $line->pvptotal = $line->pvptotal * (1 + ($iva/100));
    	  // $line->pvpunitario = $line->pvpunitario * (1 + ($iva/100));
    	  // $line->pvpsindto = $line->pvpsindto * (1 + ($iva/100));
        }

        if ($save) {
            $line->save();
        }
    }



}
