<?

namespace FacturaScripts\Plugins\Afip\Lib;


use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Core\Base\ToolBox;

use Afip;

/**
 * 
 */
class DocumentoAfip 
{

	private $afip;

	private $system_document = '';
	
	private $system_client = '';

	private $tipo_doc_fiscal;

	private $concepto = 1; //producto

	private $punto_venta;

	private $tipo_de_documento = 99;//CONSUMIDOR FINAL

	private $nro_documento = 0;

	private $nro_factura;

	private $fecha;

	private $importe_gravado;

	private $importe_exento_iva;

	private $importe_iva;

	private $fecha_servicio_desde = null;

	private $fecha_servicio_hasta = null;

	private $fecha_vencimiento_pago = null;

	private $cantidad_facturas = 1;

	private $importe_neto_no_gravado = 0;

	private $importe_total_tributo = 0;

	private $moneda = 'PES';

	private $moneda_cotizacion = 1;

	private $iva;//array con alicuotas discrimindas.

	private $docFiscalCodes = [
			'NC'=>['A'=> 3,'B'=> 8,'C'=> 13],
			'ND'=>['A'=> 2,'B'=> 7,'C'=> 12],
			'FACTURAVENTA'=>['A'=> 1,'B'=> 6,'C'=> 11]
		];

	private $docCode = [
			'DNI' => 96,
			'CUIT' => 80,
			'CUIL' => 86,
			'CONSUMIDOR FINAL'=> 99
	];

	private $alicuotas = [
			'0' => 3,
			'10.5' => 4,
			'21' => 5,
			'27' => 6,
			'5' => 8,
			'2.5' => 9
	];


	private function setCodTipoDocFiscal($tipo,$letra){
			$this->tipo_doc_fiscal=$this->docFiscalCodes[$tipo][$letra];
	}

	private function setTipoDoc($tipo){
		$this->tipo_de_documento = $this->docCode[$tipo];
	}


	public function create(&$document, Cliente $cliente,$data){

			$this->system_document = &$document;
			$this->system_client = &$cliente;

			$this->afip = new Afip(array('CUIT'=>23312509679));

			$this->loadFromData($data);

			$afipData = $this->prepareData();
			/** 
			 * Creamos la Factura 
			 **/
			$res = $this->afip->ElectronicBilling->CreateVoucher($afipData);

			$this->system_document->cae=$res['CAE'];
			$this->system_document->caefechavto=$res['CAEFchVto'];
			$this->system_document->numero2=$this->nro_factura;

		
	}



	private function alicuotas($lineas){
		
		$ivas = [];

		foreach($lineas as $l){
			
			$key=strval($l['iva']);

			if(!isset($ivas[$key])){
			
				$ivas[$key]=[];
			
			}

			if(!isset($this->alicuotas[$key])){
				die($this->toolBox()->i18n()->trans('no-existe-valor-iva-afip'));
			}

			$ivas[$key]['Id'] = $this->alicuotas[$key];


			$ivas[$key]['BaseImp'] = $l['pvptotal'];

			$ivas[$key]['Importe'] = $l['pvptotal'] * ( 1 + ($l['iva']/100)) - $l['pvptotal'];
		}

		$this->iva=$ivas;
	}



	private function loadFromData($data){

			$this->setCodTipoDocFiscal($this->system_document->codsubtipodoc,$data['custom']['codserie']);

			$this->punto_venta = $data['form']['codpv'];

			$this->setTipoDoc($this->system_client->tipoidfiscal);

			$this->nro_documento = $this->system_client->cifnif;

			$last_voucher = $this->afip->ElectronicBilling->GetLastVoucher($this->punto_venta, $this->tipo_doc_fiscal);

			$this->nro_factura = $last_voucher+1;

			$this->fecha = date('Ymd',strtotime($data['form']['fecha']));

			$this->recalculate($this->system_document,$data['lines']);

			$this->importe_gravado = $this->system_document->neto;

			$this->importe_exento_iva = $this->system_document->total - $this->system_document->neto;

			$this->importe_iva = $this->system_document->totaliva;

			$this->iva=$this->alicuotas($data['lines']);
	}

	private function prepareData(){
		$data = array(
			'CantReg' 	=> $this->cantidad_facturas, // Cantidad de facturas a registrar
			'PtoVta' 	=> $this->punto_venta,
			'CbteTipo' 	=> $this->tipo_doc_fiscal, 
			'Concepto' 	=> $this->concepto,
			'DocTipo' 	=> $this->tipo_de_documento,
			'DocNro' 	=> $this->nro_documento,
			'CbteDesde' => $this->nro_factura,
			'CbteHasta' => $this->nro_factura,
			'CbteFch' 	=> $this->fecha,
			'FchServDesde'  => $this->fecha_servicio_desde,
			'FchServHasta'  => $this->fecha_servicio_hasta,
			'FchVtoPago'    => $this->fecha_vencimiento_pago,
			'ImpTotal' 	=> $this->importe_gravado + $this->importe_iva + $this->importe_exento_iva,
			'ImpTotConc'=> 0, // Importe neto no gravado
			'ImpNeto' 	=> $this->importe_gravado,
			'ImpOpEx' 	=> $this->importe_exento_iva,
			'ImpIVA' 	=> $this->importe_iva,
			'ImpTrib' 	=> 0, //Importe total de tributos
			'MonId' 	=> 'PES', //Tipo de moneda usada en la factura ('PES' = pesos argentinos) 
			'MonCotiz' 	=> 1, // Cotización de la moneda usada (1 para pesos argentinos)  
			'Iva' 		=> $this->iva,// Alícuotas asociadas al factura 
		);

		if($this->system_document->codsubtipodoc == 'NC'){
			$data['CbtesAsoc'] = array(
					array(
						'Tipo' 		=> '',
						'PtoVta' 	=> '',
						'Nro' 		=> ''
					)
			);
		}

		return $data;
	}	

	/**
     *
     * @param BusinessDocument $doc
     */
    protected function clearTotals(&$doc)
    {
      
        $doc->neto = 0.0;
        $doc->netosindto = 0.0;
        $doc->total = 0.0;
        $doc->totaleuros = 0.0;
        $doc->totalirpf = 0.0;
        $doc->totaliva = 0.0;
        $doc->totalrecargo = 0.0;
        $doc->totalsuplidos = 0.0;
       
    }


	/**
     * Recalculates document totals.
     *
     * @param BusinessDocument $doc
     */
    public function recalculate(&$doc,$lines)
    {
        $this->clearTotals($doc);


        foreach ($this->getSubtotals($lines, [$doc->dtopor1, $doc->dtopor2]) as $subt) {
            $doc->neto += $subt['neto'];
            $doc->netosindto += $subt['netosindto'];
            $doc->totalirpf += $subt['totalirpf'];
            $doc->totaliva += $subt['totaliva'];
            $doc->totalrecargo += $subt['totalrecargo'];
            $doc->totalsuplidos += $subt['totalsuplidos'];
        }

        /// rounding totals again
        $doc->neto = \round($doc->neto, (int) \FS_NF0);
        $doc->netosindto = \round($doc->netosindto, (int) \FS_NF0);
        $doc->totalirpf = \round($doc->totalirpf, (int) \FS_NF0);
        $doc->totaliva = \round($doc->totaliva, (int) \FS_NF0);
        $doc->totalrecargo = \round($doc->totalrecargo, (int) \FS_NF0);
        $doc->totalsuplidos = \round($doc->totalsuplidos, (int) \FS_NF0);
        $doc->total = \round($doc->neto + $doc->totalsuplidos + $doc->totaliva + $doc->totalrecargo - $doc->totalirpf, (int) \FS_NF0);

    }

    /**
     * Returns subtotals by tax.
     *
     * @param BusinessDocumentLine[] $lines
     * @param array                  $discounts
     *
     * @return array
     */
    public function getSubtotals(array $lines, array $discounts): array
    {
        /// calculates the equivalent unified discount
        $eud = 1.0;
        foreach ($discounts as $dto) {
            $eud *= 1 - $dto / 100;
        }

        $irpf = 0.0;
        $subtotals = [];
        $totalIrpf = 0.0;
        $totalSuplidos = 0.0;
        foreach ($lines as $line) {
        	$line = json_decode(json_encode($line), FALSE);;
            $pvpTotal = $line->pvptotal * $eud;
            if (empty($pvpTotal)) {
                continue;
            } elseif ($line->suplido) {
                $totalSuplidos += $pvpTotal;
                continue;
            }

            $codimpuesto = empty($line->codimpuesto) ? $line->iva . '-' . $line->recargo : $line->codimpuesto;
            if (false === \array_key_exists($codimpuesto, $subtotals)) {
                $subtotals[$codimpuesto] = [
                    'irpf' => 0.0,
                    'iva' => $line->iva,
                    'neto' => 0.0,
                    'netosindto' => 0.0,
                    'recargo' => $line->recargo,
                    'totalirpf' => 0.0,
                    'totaliva' => 0.0,
                    'totalrecargo' => 0.0,
                    'totalsuplidos' => 0.0
                ];
            }

            $subtotals[$codimpuesto]['neto'] += $pvpTotal;
            $subtotals[$codimpuesto]['netosindto'] += $line->pvptotal;

            $irpf = \max([$irpf, $line->irpf]);
            $totalIrpf += $pvpTotal * $line->irpf / 100;

          
            $subtotals[$codimpuesto]['totaliva'] += $pvpTotal * $line->iva / 100;
            $subtotals[$codimpuesto]['totalrecargo'] += $pvpTotal * $line->recargo / 100;
        }

        /// Aditional taxes to the first subtotal
        foreach ($subtotals as $key => $value) {
            $subtotals[$key]['irpf'] = $irpf;
            $subtotals[$key]['totalirpf'] = $totalIrpf;
            $subtotals[$key]['totalsuplidos'] = $totalSuplidos;
            break;
        }

        /// rounding totals
        foreach ($subtotals as $key => $value) {
            $subtotals[$key]['neto'] = \round($value['neto'], (int) \FS_NF0);
            $subtotals[$key]['netosindto'] = \round($value['netosindto'], (int) \FS_NF0);
            $subtotals[$key]['totalirpf'] = \round($value['totalirpf'], (int) \FS_NF0);
            $subtotals[$key]['totaliva'] = \round($value['totaliva'], (int) \FS_NF0);
            $subtotals[$key]['totalrecargo'] = \round($value['totalrecargo'], (int) \FS_NF0);
            $subtotals[$key]['totalsuplidos'] = \round($value['totalsuplidos'], (int) \FS_NF0);
        }

        return $subtotals;
    }

	 /**
     *
     * @return ToolBox
     */
	    private function toolBox()
	    {
	        return new ToolBox();
	    }
}