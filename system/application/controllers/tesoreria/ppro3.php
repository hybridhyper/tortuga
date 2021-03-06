<?php
class Ppro extends Controller {

	var $titp='Desembolsos';
	var $tits='Desembolso';
	var $url='tesoreria/ppro/';

	function ppro(){
		parent::Controller();
		$this->load->library("rapyd");
		$this->formatopres =$this->datasis->traevalor('FORMATOPRES');
		$this->flongpres   =strlen(trim($this->formatopres));
	}

	function index(){
		redirect($this->url."filteredgrid");
	}

	function filteredgrid(){
		//$this->datasis->modulo_id(101,1);
		$this->rapyd->load("datafilter","datagrid");

		$mSPRV=array(
				'tabla'   =>'sprv',
				'columnas'=>array(
				'proveed' =>'C&oacute;odigo',
				'nombre'=>'Nombre',
				'contacto'=>'Contacto'),
				'filtro'  =>array('proveed'=>'C&oacute;digo','nomb_prov'=>''),
				'retornar'=>array('proveed'=>'cod_prov' ),
				'titulo'  =>'Buscar Beneficiario');

		$bSPRV=$this->datasis->p_modbus($mSPRV,"proveed");

		$filter = new DataFilter("Filtro de $this->titp");

		$filter->db->select("a.id id,a.fecha fecha,a.tipo tipo,a.status status,a.cod_prov cod_prov,a.benefi benefi,a.monto monto,b.nombre proveed");
		$filter->db->from("mbanc a");
		$filter->db->join("sprv b"    ,"b.proveed=a.cod_prov", "LEFT");
		$filter->db->where("a.tipo ","E");
		//$filter->db->where("a.tipo !=", "A");
		//$filter->db->where("a.tipo !=", "B");
		//$filter->db->where("a.tipo !=", "C");
		//$filter->db->where("a.tipo !=", "D");

		$filter->id = new inputField("N&uacute;mero", "id");
		$filter->id->db_name="a.id";
		$filter->id->size  =10;

		$filter->fecha = new dateonlyField("Fecha", "fecha");
		$filter->fecha->size=12;

		$filter->cod_prov = new inputField("Beneficiario", 'cod_prov');
		$filter->cod_prov->size = 6;
		$filter->cod_prov->append($bSPRV);

		$filter->benefi = new inputField("Beneficiario", "benefi");
		$filter->benefi->db_name="a.id";
		$filter->benefi->size = 20;

		$filter->status = new dropdownField("Estado","status");
		$filter->status->option("","");
		$filter->status->option("E1","Sin Ejecutar");
		$filter->status->option("E2","Ejecutado");
		$filter->status->style="width:150px";

		$filter->buttons("reset","search");

		$filter->build();
		$uri = anchor($this->url.'dataedit/show/<#id#>','<str_pad><#id#>|8|0|STR_PAD_LEFT</str_pad>');

		function sta($status){
			switch($status){
				case "E1":return "Sin Ejecutar";break;
				case "E2":return "Ejecutado";break;
				//case "A":return "Anulado";break;
			}
		}

		$grid = new DataGrid("Lista de ".$this->titp);
		$grid->order_by("id","desc");
		$grid->per_page = 20;
		$grid->use_function('substr','str_pad');
		$grid->use_function('sta');

		$grid->column("N&uacute;mero"    ,$uri);
		$grid->column("Fecha"            ,"<dbdate_to_human><#fecha#></dbdate_to_human>","align='center'");
		$grid->column("Beneficiario"        ,"proveed");
		$grid->column("Beneficiario"     ,"benefi");
		$grid->column("Pago"             ,"<number_format><#monto#>|2|,|.</number_format>","align='right'");
		$grid->column("Estado"           ,"<sta><#status#></sta>"                       ,"align='center'");

		//echo $grid->db->last_query();
		$grid->add($this->url."dataedit/create");
		$grid->build();

		$data['content'] = $filter->output.$grid->output;
		$data['title']   = " $this->titp ";
		$data["head"]    = $this->rapyd->get_head();
		$this->load->view('view_ventanas', $data);
	}

	function dataedit(){
		//$this->datasis->modulo_id(101,1);
		$this->rapyd->load('dataobject','datadetails');

		$mSPRV=array(
				'tabla'   =>'sprv',
				'columnas'=>array(
				'proveed' =>'C&oacute;odigo',
				'nombre'=>'Nombre',
				'contacto'=>'Contacto'),
				'filtro'  =>array('proveed'=>'C&oacute;digo','nombre'=>'Nombre'),
				'retornar'=>array('proveed'=>'cod_prov'     , 'nombre'=>'nomb_prov', 'nombre'=>'benefi'),
				'titulo'  =>'Buscar Beneficiario');

		$bSPRV=$this->datasis->p_modbus($mSPRV,"proveed");

		$modbus=array(
			'tabla'   =>'odirect',
			'columnas'=>array(
				'numero'  =>'N&uacute;mero',
				'fecha'   =>'fecha',
				'tipo'    =>'tipo'
				),
			'filtro'  =>array(
				'numero'  =>'N&uacute;mero',
				'fecha'   =>'fecha',
				'tipo'    =>'tipo'),
			'retornar'=>array(
				  'numero' =>'orden_<#i#>',
				  'total'  =>'monto_<#i#>'),
			'p_uri'=>array(
				  4=>'<#i#>',
				  5=>'<#cod_prov#>'),
			'where' =>'status = "F2" OR status = "B2" OR status = "R2" OR status = "G2" OR status = "I2" OR status = "S2" ',//cod_prov like "<#cod_prov#>%"
			'script'=>array('cal_total(<#i#>)'),
			'titulo'=>'Busqueda de Ordenes de Pago');

		$btn=$this->datasis->p_modbus($modbus,'<#i#>/<#cod_prov#>');

		$mBANC=array(
				'tabla'   =>'banc',
				'columnas'=>array(
					'codbanc' =>'C&oacute;odigo',
					'banco'=>'Banco',
					'saldo'=>'Saldo'),
				'filtro'  =>array(
					'codbanc' =>'C&oacute;odigo',
					'banco'=>'Banco',
					'saldo'=>'Saldo'),//39, 40
				'retornar'=>array(
					'codbanc'=>'codbanc','banco'=>'nombanc'
					 ),
				'where'=>'activo = "S"',
				'titulo'  =>'Buscar Bancos');

		$bBANC=$this->datasis->p_modbus($mBANC,"banc");

		$do = new DataObject("mbanc");

		$do->rel_one_to_many('itppro', 'itppro', array('id'=>'mbanc'));

		$edit = new DataDetails($this->tits, $do);
		$edit->back_url = site_url($this->url."filteredgrid");
		$edit->set_rel_title('itppro','Rubro <#o#>');

		$edit->pre_process('insert'  ,'_valida');
		$edit->pre_process('update'  ,'_valida');
		//$edit->post_process('insert'  ,'_paiva');
		//$edit->post_process('update'  ,'_paiva');

		$edit->id  = new inputField("N&uacute;mero", "id");
		$edit->id->mode="autohide";
		$edit->id->when=array('show');

		$edit->fecha = new  dateonlyField("Fecha",  "fecha");
		$edit->fecha->insertValue = date('Y-m-d');
		$edit->fecha->size        =12;
		$edit->fecha->rule        = 'required';

		$edit->tipo = new inputField("","tipo");
		$edit->tipo-> insertValue = "E";
		$edit->tipo->mode         = "autohide";
		$edit->tipo->when=array('');

		$edit->cod_prov = new inputField("Beneficiario", 'cod_prov');
		$edit->cod_prov->size     = 6;
		//$edit->cod_prov->rule     = "required";
		$edit->cod_prov->append($bSPRV);
		$edit->cod_prov->readonly=true;

		$edit->nomb_prov = new inputField("Nombre", 'nomb_prov');
		$edit->nomb_prov->db_name   = ' ';
		$edit->nomb_prov->size      = 50;
		$edit->nomb_prov->readonly  = true;
		$edit->nomb_prov->in        = "cod_prov";

		$edit->benefi = new inputField("Beneficiario", 'benefi');
		$edit->benefi->size       = 50;
		$edit->benefi->maxlength  = 50;
		$edit->benefi->rule       = 'required';

		$edit->observa = new textAreaField("Observaciones", 'observa');
		$edit->observa->cols = 106;
		$edit->observa->rows = 3;

	  $edit->codbanc =  new inputField("Banco", 'codbanc');
	  $edit->codbanc-> size     = 3;
	  $edit->codbanc-> rule     = "required";
	  $edit->codbanc-> append($bBANC);
    $edit->codbanc-> readonly=true;

    $edit->nombanc = new inputField("Nombre","nombanc");
    $edit->nombanc->size  =30;
    $edit->nombanc->readonly=true;
    $edit->nombanc->db_name =" ";

    $edit->cheque =  new inputField("Cheque", 'cheque');
	  $edit->cheque-> size  = 20;
	  $edit->cheque-> rule  = "required";

		$edit->monto = new inputField("Total", 'monto');
		$edit->monto->mode     = 'autohide';
		$edit->monto->when     = array('show');
		$edit->monto->size = 8;

		$edit->itorden = new inputField("(<#o#>) ", "orden_<#i#>");
		$edit->itorden->rule     ='callback_repetido|required|callback_itorden';
		$edit->itorden->size     =15;
		$edit->itorden->db_name  ='orden';
		$edit->itorden->rel_id   ='itppro';
		$edit->itorden->readonly =true;
		$edit->itorden->append('<img src="/tortuga/assets/default/images/system-search.png"  alt="Busqueda de Ordenes de Pago" title="Busqueda de Ordenes de Pago" border="0" onclick="modbusdepen(<#i#>)"/>');

		$edit->itmonto = new inputField("(<#o#>) Abonar", "monto_<#i#>");
		$edit->itmonto->db_name  = 'monto';
		$edit->itmonto->rel_id   = 'itppro';
		//$edit->itmonto->mode     = 'autohide';
		$edit->itmonto->when     = array('show','modify');
		$edit->itmonto->size     = 8;
	//	$edit->itmonto->readonly = true;

		$status=$edit->get_from_dataobjetct('status');
		if($status=='E1'){
			$action = "javascript:window.location='" .site_url($this->url.'/actualizar/'.$edit->rapyd->uri->get_edited_id()). "'";
			$edit->button_status("btn_status",'Actualizar',$action,"TR","show");
			$edit->buttons("modify","delete","save");
		}elseif($status=='E2'){
			$action = "javascript:window.location='" .site_url($this->url.'/reversar/'.$edit->rapyd->uri->get_edited_id()). "'";
			$edit->button_status("btn_rever",'Reversar',$action,"TR","show");
		}else{
			$edit->buttons("save");
		}

		$edit->buttons("undo","back","add_rel");
		$edit->build();

		$smenu['link']   = barra_menu('208');
		$data['smenu']   = $this->load->view('view_sub_menu', $smenu,true);
		$conten["form"]  =&  $edit;
		$data['content'] = $this->load->view('view_ppro', $conten,true);
		//$data['content'] = $edit->output;
		$data['title']   = " $this->tits ";
		$data["head"]    = $this->rapyd->get_head().script('jquery.js').script("plugins/jquery.numeric.pack.js").script("plugins/jquery.json.min.js");
		$this->load->view('view_ventanas', $data);
	}

	function _valida($do){
		$this->rapyd->load('dataobject');

		$odirect = new DataObject("odirect");

		$error='';
		$tot=0;
		for($i=0;$i < $do->count_rel('itppro');$i++){
			$orden      = $do->get_rel('itppro','orden'   ,$i);

			$odirect->load($orden);

			$ivaa      =  $odirect->get('ivaa');
			$ivag      =  $odirect->get('ivag');
			$ivar      =  $odirect->get('ivar');
			$iva       =  $odirect->get('iva');
			$subtotal  =  $odirect->get('subtotal');
			$reteiva   =  $odirect->get('reteiva');
			$reten     =  $odirect->get('reten');
			$ivan      =  $ivag+$ivar+$ivaa+$iva;
			$total     =  $odirect->get('total');
			//$total     =  ($subtotal-$reten)+($ivan-($reteiva));

			$do->set_rel('itppro' ,'monto' ,$total ,$i);

			$tot  +=$total;
		}
		$codbanc =  $do->get('codbanc');

		$banc   = new DataObject("banc");
		$banc   ->load($codbanc);
		$saldo  = $banc->get('saldo');
		$activo = $banc->get('activo');
		$banco  = $banc->get('banco' );

		if($activo != 'S' )$error.="<div class='alert'><p>El banco ($banco) esta inactivo</p></div>";

		if($tot > $saldo )$error.="<div class='alert'><p>El Monto ($tot) del cheque es mayor al disponible ($saldo) en el banco ($banco)</p></div>";

		$do->set('monto'  , $tot );
		$do->set('tipo'   , 'E'  );
		$do->set('status' , 'E1' );

		if(!empty($error)){
			$do->error_message_ar['pre_ins']=$error;
			$do->error_message_ar['pre_upd']=$error;
			return false;
		}
	}

	function positivo($valor){
		if ($valor <= 0){
			$this->validation->set_message('ittotal',"El campo Monto debe ser positivo");
			return FALSE;
		}
  	return TRUE;
	}

	function repetido($orden){
		if(isset($this->__rorden)){
			if(in_array($orden, $this->__rorden)){
				$this->validation->set_message('repetido',"El rublo %s ($orden) esta repetido");
				return false;
			}
		}
		$this->__rorden[]=$orden;
		return true;
	}

	function itorden($orden){
		$cod_prov    = $this->db->escape($this->input->post('cod_prov'));
		$orden      = $this->db->escape($orden);
		if($cod_prov != "''"){
			$cana=$this->datasis->dameval("SELECT COUNT(*) FROM odirect WHERE cod_prov=$cod_prov AND numero=$orden");// AND status='O'
		}else{
			$cana=$this->datasis->dameval("SELECT COUNT(*) FROM odirect WHERE numero=$orden");// AND status='O'
		}

		if($cana>0){
			return true;
		}else{
			$this->validation->set_message('itorden',"La orden ($orden) No pertenece al proveedor ($cod_prov)");
			return false;
		}
	}

	function actualizar($id){

		$this->rapyd->load('dataobject');

		$error='';
		
		$ord     = new DataObject("ordinal");

		$mbanc     =  new DataObject("mbanc");
		$mbanc     -> rel_one_to_many('itppro', 'itppro', array('id'=>'mbanc'));
		$mbanc     -> load($id);
		$m_codbanc =  $mbanc->get('codbanc');
		$m_monto   =  $mbanc->get('monto');

		$banc   = new DataObject("banc");
		$banc   ->load($m_codbanc);
		$saldo  = $banc->get('saldo');
		$activo = $banc->get('activo');

		$odirect = new DataObject("odirect");
		$odirect -> rel_one_to_many('pacom', 'pacom', array('numero'=>'pago'));
		$odirect -> rel_one_to_many('itodirect', 'itodirect', array('numero'=>'numero'));
		//$odirect -> rel_one_to_many('islr', 'islr', array('numero'=>'odirect'));
		$odirect->pointer('sprv' ,'sprv.proveed = odirect.cod_prov','sprv.nomfis as nom_prov, sprv.rif as rif_prov','LEFT');

		$ocompra = new DataObject("ocompra");
		$ocompra->rel_one_to_many('itocompra', 'itocompra', array('numero'=>'numero'));
		$ocompra->pointer('sprv' ,'sprv.proveed=ocompra.cod_prov','sprv.nombre as nom_prov, sprv.rif as rif_prov');

		$riva    =  new DataObject("riva");

		$presup = new DataObject("presupuesto");
		
		//$islr   = new DataObject("islr");
		
		$partidaiva=$this->datasis->traevalor("PARTIDAIVA");

		if($activo != 'S' )$error.="<div class='alert'><p>El banco ($banco) esta inactivo</p></div>";

		if($m_monto > $saldo )$error.="<div class='alert'><p>El Monto ($tot) del cheque es mayor al disponible ($saldo) en el banco ($banco)</p></div>";

		$sta=$mbanc->get('status');
		if(($sta=="E1")){
			$mbanc->set('status','E2');
			$m_benefi = $mbanc->get('benefi');
			$t=0;

			if(empty($error)){
			$tislr =0;

				for($j=0;$j < $mbanc->count_rel('itppro');$j++){

					$orden    = $mbanc->get_rel('itppro','orden',$j);
					$it_monto = $mbanc->get_rel('itppro','monto',$j);

					$odirect->load($orden);
					
					//print_r($odirect->get_all());

					$status       =  $odirect->get('status'     );
					$od_numero    =  $odirect->get('numero'     );
					$od_estadmin  =  $odirect->get('estadmin'   );
					$od_fondo     =  $odirect->get('fondo'      );
					$od_subtotal  =  $odirect->get('subtotal'   );
					$od_ivag      =  $odirect->get('ivag'       );
					$od_ivaa      =  $odirect->get('ivaa'       );
					$od_ivar      =  $odirect->get('ivar'       );
					$od_reten     =  $odirect->get('reten'      );
					$od_reteiva   =  $odirect->get('reteiva'    );
					$od_fechafac  =  $odirect->get('fechafac'   );
					$od_cod_prov  =  $odirect->get('cod_prov'    );
					$od_creten    =  $odirect->get('creten'      );
					$od_pr        =  $od_reten*100/$od_subtotal;
					
					if($status == "F2" ){

						for($g=0;$g < $odirect->count_rel('pacom');$g++){
							$p_t       = $odirect->get_rel('pacom','total' ,$g);
							$p_compra  = $odirect->get_rel('pacom','compra',$g);

							$ocompra->load($p_compra);

							$oc_codigoadm  = $ocompra->get('estadmin'  );
							$oc_fondo      = $ocompra->get('fondo'     );
							$oc_status     = $ocompra->get('status'    );
							$oc_cod_prov   = $ocompra->get('cod_prov'  );
							$oc_creten     = $ocompra->get('creten'    );
							$oc_fechafac   = $ocompra->get('fechafac'  );

							$pagado=$this->datasis->dameval("SELECT SUM(a.monto) FROM itppro a JOIN mbanc d ON d.id=a.mbanc JOIN odirect b ON a.orden=b.numero JOIN pacom c ON b.numero=c.pago WHERE c.compra=$p_compra AND d.status='E2'");
							$pagado+=$it_monto;

							$ivaa         =  $ocompra->get('ivaa');
							$ivag         =  $ocompra->get('ivag');
							$ivar         =  $ocompra->get('ivar');
							$subtotal     =  $ocompra->get('subtotal');
							$reteiva      =  $ocompra->get('reteiva');
							$impmunicipal =  $ocompra->get('imptimbre');
							$imptimbre    =  $ocompra->get('impmunicipal');
							$tislr=$reten =  $ocompra->get('reten');
							$ivan         =  $ivag+$ivar+$ivaa;
							//$total        =  ($subtotal - $reten)+($ivan-($reteiva))-$impmunicipal -$imptimbre;
							$total        =  $ocompra->get('total');
							
							//$pr = $reten*100/$subtotal;
							
							//echo $total;

							if($total==$pagado){
							
						//	echo "entro";
								
								$pk=array('codigoadm'=>$oc_codigoadm,'tipo'=>$oc_fondo);
								
								$partidaiva=$this->datasis->traevalor("PARTIDAIVA");
								$pk['codigopres'] = $partidaiva;
								$presup->load($pk);
					
								$pasignacion   =$presup->get("asignacion");
						
								for($h=0;$h < $odirect->count_rel('pacom');$h++){
									for($k=0;$k < $ocompra->count_rel('itocompra');$k++){
										$islrid = '';
										$codigopres  = $ocompra->get_rel('itocompra','partida',$k);
										$importe     = $ocompra->get_rel('itocompra','importe',$k);
										$iva         = $ocompra->get_rel('itocompra','iva'    ,$k);
										$ordinal     = $ocompra->get_rel('itocompra','ordinal',$k);
										//$islr        = $ocompra->get_rel('itocompra','islr'   ,$k);
										//$imptimbre   = $ocompra->get_rel('itocompra','imptimbre',$k);
										//$impmunicipal= $ocompra->get_rel('itocompra','impmunicipal',$k);										
										
										if($pasignacion>0)
											$mont        = $importe;//-$islr-$imptimbre-$impmunicipal;
										else
											$mont        = $importe+($importe*$iva/100);//-$islr-$imptimbre-$impmunicipal;
										
										$pk['codigopres'] = $codigopres;

										$presup->load($pk);
										$pagado=$presup->get("pagado");
										$pagado=$pagado+$mont;

										$presup->set("pagado",$pagado);
										$presup->save();
										
										if(!empty($ordinal)){
			
											$ord->load(array("codigoadm"=>$oc_codigoadm,"fondo"=>$oc_fondo,"codigopres"=>$codigopres,"ordinal"=>$ordinal));
											
											$pagado =$ord->get("pagado" );    
											$pag += $mont;
											$ord->set("pagado",$pag);
											$ord->save();								
										}	
									}
									$ii = $ivan-($reteiva);
			
									if($pasignacion>0){
										$pk['codigopres'] = $partidaiva;
										$presup->load($pk);
				
				
										$pagado =$presup->get("pagado");
										$pagado+=$ii;
										$presup->set("pagado",$pagado);
										$presup->save();
									}
									
									$this->sp_presucalc($oc_codigoadm);
									
									$riva->load_where('ocompra',$p_compra);

									$riva->set('ocompra'       , $p_compra                        );
									$riva->set('emision'       , date('Ymd')                      );
									$riva->set('periodo'       , date('Ym')                       );
									$riva->set('tipo_doc'      , ''                               );
									$riva->set('fecha'         , date('Ymd')                      );
									$riva->set('numero'        , $ocompra->get('factura'   )      );
									$riva->set('ffactura'      , $ocompra->get('fechafac'  )      );
									$riva->set('nfiscal'       , $ocompra->get('controlfac')      );
									$riva->set('clipro'        , $ocompra->get('cod_prov'  )      );
									$riva->set('nombre'        , $ocompra->get_pointer('nom_prov'));
									$riva->set('rif'           , $ocompra->get_pointer('rif_prov'));
									$riva->set('exento'        , $ocompra->get('exento'          ));
									$riva->set('tasa'          , $ocompra->get('tivag'           ));
									$riva->set('general'       , $ocompra->get('mivag'           ));
									$riva->set('geneimpu'      , $ocompra->get('ivag'            ));
									$riva->set('tasaadic'      , $ocompra->get('tivaa'           ));
									$riva->set('adicional'     , $ocompra->get('mivag'           ));
									$riva->set('adicimpu'      , $ocompra->get('ivaa'            ));
									$riva->set('tasaredu'      , $ocompra->get('tivar'           ));
									$riva->set('reducida'      , $ocompra->get('mivar'           ));
									$riva->set('reduimpu'      , $ocompra->get('ivar'            ));
									$riva->set('stotal'        , $ocompra->get('subtotal'        ));
									$riva->set('impuesto'      , $ocompra->get('ivag')+$ocompra->get('ivar')+$ocompra->get('ivaa')                          );
									$riva->set('gtotal'        , $ocompra->get('ivag')+$ocompra->get('ivar')+$ocompra->get('ivaa')+$ocompra->get('subtotal'));
									$riva->set('reiva'         , $ocompra->get('reteiva'         ));
									$riva->set('status'        , 'B'                              );
									$riva->set('mbanc'         , $id                              );
									$riva->save();
								}
								$ocompra->set('status','E');
								$ocompra->save();
							}
							//exit('aaaa');

							$odirect->set('status','F3');
							$odirect->save();
						}
						$odirect->save();

					}elseif($status == "B2" || $status == "I2" ){
					
						$ivaa         =  $od_ivaa;
						$ivag         =  $od_ivag;
						$ivar         =  $od_ivar;
						$subtotal     =  $od_subtotal;
						$reteiva      =  $od_reteiva;
						$tislr=$reten =  $od_reten;
						$ivan         =  $ivag+$ivar+$ivaa;
						$total        =  ($subtotal - $reten)+($ivan-($reteiva));
						
						$pr = $reten*100/$subtotal;
						$pk=array('codigoadm'=>$od_estadmin,'tipo'=>$od_fondo);
						
						$partidaiva=$this->datasis->traevalor("PARTIDAIVA");
						$pk['codigopres'] = $partidaiva;
						$presup->load($pk);
						
						$pasignacion   =$presup->get("asignacion");

						for($g=0;$g   <  $odirect->count_rel('itodirect');$g++){
							$islrid = '';
							$codigopres  = $odirect->get_rel('itodirect','partida',$g);
							$importe     = $odirect->get_rel('itodirect','importe',$g);
							$piva        = $odirect->get_rel('itodirect','iva'    ,$g);
							$islrid      = $odirect->get_rel('itodirect','islrid' ,$g);
							echo $ordinal     = $odirect->get_rel('itodirect','ordinal',$g);
							
							$i_islr      = $importe*$od_pr/100;
							
							
							if($pasignacion>0)
								$mont        = $importe;
							else
								$mont        = $importe+($importe*$piva/100);

							$pk['codigopres'] = $codigopres;

							$presup->load($pk);
							$pagado=$presup->get("pagado");
							$pagado=$pagado+($mont-$i_islr);

							$presup->set("pagado",$pagado);
							
							$presup->save();
							
							if(!empty($ordinal)){
						
								$ord->load(array("codigoadm"=>$od_estadmin,"fondo"=>$od_fondo,"codigopres"=>$codigopres,"ordinal"=>$ordinal));

								print_r($ordinal);
								
								$opa   =$ord->get("pagado" );
								$opa  +=$mont-$i_islr;
								$ord->set("pagado"  ,$opa  );
								$ord->save();			
							}	
							//exit('cafe');
							//if($status == "B2"){
							//
							//	$islr   = new DataObject("islr");
							//	if(!empty($islrid))
							//		$islr->load($islrid);
							//				
							//	$islr->set('estadmin'      , $od_estadmin        );
							//	$islr->set('fondo'         , $od_fondo           );
							//	$islr->set('partida'       , $codigopres         );
							//	$islr->set('codprov'       , $od_cod_prov        );
							//	$islr->set('fechafac'      , $od_fechafac        );
							//	$islr->set('benefi'        , $m_benefi           );
							//	$islr->set('porcen'        , $pr                 );
							//	//$islr->set('sustraendo'    ,                     );
							//	$islr->set('islr'          , $i_islr             );
							//	$islr->set('fecha'         , date('Ymd')         );
							//	$islr->set('creten'        , $od_creten          );
							//	$islr->set('odirect'       , $islrid             );
							//	$islr->set('status'        , 'B'                 );
							//	$islr->save();
							//	$islrid = $islr->get('id');	
	            //
							//	$odirect->set_rel('itodirect','islrid',$islrid ,$g);
						  //
							//}
						}
						
						if($status=='I2')
							$odirect->set('status','I3');
					
						if($status == "B2"){

							$riva->load_where('odirect',$od_numero);

							$riva->set('odirect'       , $od_numero                        );
							$riva->set('emision'       , date('Ymd')                      );
							$riva->set('periodo'       , date('Ym')                       );
							$riva->set('tipo_doc'      , ''                               );
							$riva->set('fecha'         , date('Ymd')                      );
							$riva->set('numero'        , $odirect->get('factura'   )      );
							$riva->set('ffactura'      , $odirect->get('fechafac'  )      );
							$riva->set('nfiscal'       , $odirect->get('controlfac')      );
							$riva->set('clipro'        , $odirect->get('cod_prov'  )      );
							$riva->set('nombre'        , $odirect->get_pointer('nom_prov'));
							$riva->set('rif'           , $odirect->get_pointer('rif_prov'));
							$riva->set('exento'        , $odirect->get('exento'          ));
							$riva->set('tasa'          , $odirect->get('tivag'           ));
							$riva->set('general'       , $odirect->get('mivag'           ));
							$riva->set('geneimpu'      , $odirect->get('ivag'            ));
							$riva->set('tasaadic'      , $odirect->get('tivaa'           ));
							$riva->set('adicional'     , $odirect->get('mivag'           ));
							$riva->set('adicimpu'      , $odirect->get('ivaa'            ));
							$riva->set('tasaredu'      , $odirect->get('tivar'           ));
							$riva->set('reducida'      , $odirect->get('mivar'           ));
							$riva->set('reduimpu'      , $odirect->get('ivar'            ));
							$riva->set('stotal'        , $odirect->get('subtotal'        ));
							$riva->set('impuesto'      , $odirect->get('ivag')+$ocompra->get('ivar')+$ocompra->get('ivaa')                          );
							$riva->set('gtotal'        , $ocompra->get('ivag')+$ocompra->get('ivar')+$ocompra->get('ivaa')+$odirect->get('subtotal'));
							$riva->set('reiva'         , $odirect->get('reteiva'         ));
							$riva->set('status'        , 'B'                              );
							$riva->set('mbanc'         , $id                              );
							$riva->save();
							
							$ii = ($od_ivaa+$od_ivag+$od_ivar)-($od_reteiva);
							
							if($pasignacion>0){
								$pk['codigopres'] = $partidaiva;
								$presup->load($pk);
		
								$pagado =$presup->get("pagado");
								$pagado+=$ii;
								$presup->set("pagado",$pagado);
								$presup->save();
							}
							
							$odirect->set('status','B3');
							
							$this->sp_presucalc($od_estadmin);
						}
						$odirect->save();
						
					}elseif($status=='S2'){
						
						$pk=array('codigoadm'=>$od_estadmin,'tipo'=>$od_fondo);

						for($g=0;$g   <  $odirect->count_rel('islr');$g++){
							$islrid = '';
							$codigopres  = $odirect->get_rel('islr','partida' ,$g);
							$i_islr      = $odirect->get_rel('islr','islr'    ,$g);
							
							$pk['codigopres'] = $codigopres;

							$presup->load($pk);
							$pagado=$presup->get("pagado");
							$pagado+=$i_islr;

							$presup->set("pagado",$pagado);
							$presup->save();
							
							$this->sp_presucalc($od_estadmin);
							
							$codigopres  = $odirect->set_rel('islr','status','C' ,$g);
						}
						$odirect->set('status','S3');
						$odirect->save();
						
					}
					elseif($status=='R2'){
						
						$odirect->set('status','R3');
						$odirect->save();
					}
					elseif($status=='G2'){
						
						$odirect->set('status','G3');
						$odirect->save();
					}
					else{
						$error.="<div class='alert'><p>N se puede realizar la operacion para la orden de pago ($od_numero)</p></div>";						
					}
				}
				
				$islrid = $mbanc->get('islrid');
				
				$islr = new DataObject("islr");
				if (!(empty($islrid)))				
					$islr->load($islrid);
				
				if(date('d')>15)$q='02'; else $q='01';
				$islr->set('emision'       , date('Ymd')         );
				$islr->set('periodo'       , $q.date('m')        );
				$islr->set('clipro'        , $od_cod_prov        );
				$islr->set('total'         , $tislr              );
				$islr->set('status'        , 'B'                 );
				$islr->save();
				$islrid = $islr->get('nrocomp');

				$mbanc->set('islrid',$islrid);
				
			}else
				$error.="<div class='alert'><p>Error aun no determinado</p></div>";
		}else{
			$error.="<div class='alert'><p>No se Puede Completar la operacion s</p></div>";
		}
		
		

		if(empty($error)){
			$mbanc->set('status','E2');
			$mbanc->save();

			$saldo-=$m_monto;
			$banc->set('saldo',$saldo);
			$banc->save();
			
			redirect($this->url."dataedit/show/$id");
		}else{
		
			$data['content'] = $error.anchor($this->url."/dataedit/show/$id",'Regresar');
			$data['title']   = " ".$this->tits." ";
			$data["head"]    = $this->rapyd->get_head().script('jquery.js').script("plugins/jquery.numeric.pack.js");
			$this->load->view('view_ventanas', $data);
		}
	}

	function reversar($id){
		$this->rapyd->load('dataobject');

		$error='';
		
		$ord      =  new DataObject("ordinal"  );

		$mbanc     =  new DataObject("mbanc");
		$mbanc     -> rel_one_to_many('itppro', 'itppro', array('id'=>'mbanc'));
		$mbanc     -> load($id);
		$m_codbanc =  $mbanc->get('codbanc');
		$m_monto   =  $mbanc->get('monto');

		$banc   = new DataObject("banc");
		$banc   ->load($m_codbanc);
		$saldo  = $banc->get('saldo');
		$activo = $banc->get('activo');

		$odirect = new DataObject("odirect");
		$odirect -> rel_one_to_many('pacom', 'pacom', array('numero'=>'pago'));
		$odirect -> rel_one_to_many('itodirect', 'itodirect', array('numero'=>'numero'));
		$odirect -> rel_one_to_many('islr', 'islr', array('numero'=>'odirect'));

		$ocompra = new DataObject("ocompra");
		$ocompra->rel_one_to_many('itocompra', 'itocompra', array('numero'=>'numero'));
		//$ocompra->pointer('sprv' ,'sprv.proveed=ocompra.cod_prov','sprv.nombre as nom_prov, sprv.rif as rif_prov');

		$riva    =  new DataObject("riva");

		$presup = new DataObject("presupuesto");
		
		$partidaiva=$this->datasis->traevalor("PARTIDAIVA");

		if($activo != 'S' )$error.="<div class='alert'><p>El banco ($banco) esta inactivo</p></div>";

		//if($m_monto > $saldo )$error.="<div class='alert'><p>El Monto ($tot) del cheque es mayor al disponible ($saldo) en el banco ($banco)</p></div>";

		$sta=$mbanc->get('status');
		if(($sta=="E2")){
			
			$t=0;

			if(empty($error)){

				for($j=0;$j < $mbanc->count_rel('itppro');$j++){

					$orden    = $mbanc->get_rel('itppro','orden',$j);
					$it_monto = $mbanc->get_rel('itppro','monto',$j);

					$odirect->load($orden);
					$status       =  $odirect->get('status'     );
					$od_numero    =  $odirect->get('numero'     );
					$od_estadmin  =  $odirect->get('estadmin'   );
					$od_fondo     =  $odirect->get('fondo'      );
					$od_ivag      =  $odirect->get('ivag'       );
					$od_ivaa      =  $odirect->get('ivaa'       );
					$od_ivar      =  $odirect->get('ivar'       );
					$od_reten     =  $odirect->get('reten'      );
					$od_reteiva   =  $odirect->get('reteiva'    );
					$od_subtotal  =  $odirect->get('subtotal'   );
					$od_pr        =  $od_reten*100/$od_subtotal;
					
					if($status == "F3" ){

						for($g=0;$g < $odirect->count_rel('pacom');$g++){
							$p_t       = $odirect->get_rel('pacom','total' ,$g);
							$p_compra  = $odirect->get_rel('pacom','compra',$g);

							$ocompra->load($p_compra);

							$oc_codigoadm  = $ocompra->get('estadmin'  );
							$oc_fondo      = $ocompra->get('fondo'     );
							$oc_status     = $ocompra->get('status'    );

							//$pagado=$this->datasis->dameval("SELECT SUM(a.monto) FROM itppro a JOIN mbanc d ON d.id=a.mbanc JOIN odirect b ON a.orden=b.numero JOIN pacom c ON b.numero=c.pago WHERE c.compra=$p_compra AND d.status='E2'");
							//$pagado+=$it_monto;
	            //
							$ivaa      =  $ocompra->get('ivaa');
							$ivag      =  $ocompra->get('ivag');
							$ivar      =  $ocompra->get('ivar');
							//$subtotal  =  $ocompra->get('subtotal');
							$reteiva   =  $ocompra->get('reteiva');
							$reten     =  $ocompra->get('reten');
							$ivan      =  $ivag+$ivar+$ivaa;
							//$total     =  $subtotal+($ivan-($reteiva+$reten));
	
							//if($total==$pagado){

								$pk=array('codigoadm'=>$oc_codigoadm,'tipo'=>$oc_fondo);
								
								$partidaiva=$this->datasis->traevalor("PARTIDAIVA");
								$pk['codigopres'] = $partidaiva;
								$presup->load($pk);
					
								$pasignacion   =$presup->get("asignacion");
						
								$odirect->count_rel('pacom');
								for($h=0;$h < $odirect->count_rel('pacom');$h++){
									for($k=0;$k < $ocompra->count_rel('itocompra');$k++){
										$codigopres  = $ocompra->get_rel('itocompra','partida',$k);
										$importe     = $ocompra->get_rel('itocompra','importe',$k);
										$islrid      = $ocompra->get_rel('itocompra','islrid' ,$k);
										//$mont        = $importe*(($iva+100)/100);
										//$i_islr      = $importe*$od_pr/100;
										//$mont        = $importe-$i_islr;
										$ordinal     = $ocompra->get_rel('itocompra','ordinal',$k);		
										$iva         = $ocompra->get_rel('itocompra','iva'    ,$k);
										
										
										if($pasignacion>0)
											$mont        = $importe;
										else
											$mont        = $importe+($importe*$iva/100);
										
										

										$pk['codigopres'] = $codigopres;

										$presup->load($pk);
										$pagado=$presup->get("pagado");
										$pagado=$pagado-$mont;

										$presup->set("pagado",$pagado);
										$presup->save();
										
										if(!empty($ordinal)){
			
											$ord->load(array("codigoadm"=>$oc_codigoadm,"fondo"=>$oc_fondo,"codigopres"=>$codigopres,"ordinal"=>$ordinal));
											
											$opa =$ord->get("pagado" );    
											$opa -= $mont;
											$ord->set("pagado",$opa);
											$ord->save();
										}	
										
										//$islr   = new DataObject("islr");
										//if(!empty($islrid))
										//	$islr->load($islrid);
		                //
										//$islr->set('status' , 'A' );
										//$islr->save();
									}
									
									if($pasignacion>0){
										$pk['codigopres'] = $partidaiva;
										$presup->load($pk);
	
										$pagado =$presup->get("pagado");
										$pagado-=$ivan-($reteiva);
										$presup->set("pagado",$pagado);
										$presup->save();
									}
									$this->sp_presucalc($oc_codigoadm);

									$riva->load_where('ocompra',$p_compra);
									$riva->set('status'  , 'A' );
									//print_r($riva->get_all());
									$riva->save();
								}
								$ocompra->set('status','O');
								$ocompra->save();
							//}

							$odirect->set('status','F2');
							$odirect->save();
						}
//exit('assaas');
					}elseif($status == "B3" || $status == "I3" ){

						$pk=array('codigoadm'=>$od_estadmin,'tipo'=>$od_fondo);
						
						$partidaiva=$this->datasis->traevalor("PARTIDAIVA");
						$pk['codigopres'] = $partidaiva;
						$presup->load($pk);
						
						$pasignacion   =$presup->get("asignacion");

						for($g=0;$g   <  $odirect->count_rel('itodirect');$g++){
							$codigopres  = $odirect->get_rel('itodirect','partida',$g);
							$importe     = $odirect->get_rel('itodirect','importe',$g);
							$piva        = $odirect->get_rel('itodirect','iva'    ,$g);
							$islrid      = $odirect->get_rel('itodirect','islrid' ,$g);
							echo $ordinal     = $odirect->get_rel('itodirect','ordinal',$g);
							$i_islr      = $importe*$od_pr/100;
										//exit('12');		
							
							if($pasignacion>0)
								$mont        = $importe;
							else
								$mont        = $importe+($importe*$piva/100);

							$pk['codigopres'] = $codigopres;

							$presup->load($pk);
							$pagado=$presup->get("pagado");
							$pagado=$pagado-($mont-$i_islr);

							$presup->set("pagado",$pagado);
							$presup->save();
							
							if(!empty($ordinal)){
						
								$ord->load(array("codigoadm"=>$od_estadmin,"fondo"=>$od_fondo,"codigopres"=>$codigopres,"ordinal"=>$ordinal));
print_r($ord->get_all());
								
								
								$opa   =$ord->get("pagado" );
								$opa  -=$mont-$i_islr;
								$ord->set("pagado"  ,$opa  );
								$ord->save();			
							}
							//exit('ww');	
						}

						if($status == "B3"){

							$riva->load_where('odirect',$od_numero);
							$riva->set('status' , 'A'  );
							$riva->save();
							
							$ii = ($od_ivaa+$od_ivag+$od_ivar)-($od_reteiva);
							if($pasignacion>0){
								$pk['codigopres'] = $partidaiva;
								$presup->load($pk);
		
								$pagado =$presup->get("pagado");
								$pagado-=$ii;
								$presup->set("pagado",$pagado);
								$presup->save();
							}
							$odirect->set('status','B2');
							$odirect->save();
							$this->sp_presucalc($od_estadmin);
						}

					}elseif($status=='S3'){
						
						$pk=array('codigoadm'=>$od_estadmin,'tipo'=>$od_fondo);

						for($g=0;$g   <  $odirect->count_rel('islr');$g++){
							$islrid = '';
							$codigopres  = $odirect->get_rel('islr','partida' ,$g);
							$i_islr      = $odirect->get_rel('islr','islr'    ,$g);
							
							$pk['codigopres'] = $codigopres;

							$presup->load($pk);
							$pagado=$presup->get("pagado");
							$pagado-=$i_islr;

							$presup->set("pagado",$pagado);
							$presup->save();
							
							$codigopres  = $odirect->set_rel('islr','status','B' ,$g);
						}
						
						
						$odirect->set('status','S2');
						$odirect->save();
						$this->sp_presucalc($codigoadm);
						
					}elseif($status=='R3')
						$odirect->set('status','R2');
					elseif($status=='G3')
						$odirect->set('status','G2');
					else
						$error.="<div class='alert'><p>No se puede realizar la operacion para la orden de pago ($od_numero)</p></div>";
				}
				$odirect->save();
				//exit('hgghhgg');
				if($status == "B2"){
				$islr   = new DataObject("islr");
				$islrid = $do->get->islr('islrid');
				if(!empty($islrid)){
					$islr->load($islrid);
        
					$islr->set('status' , 'A' );
					$islr->save();
					
				}
				}
			}else
				$error.="<div class='alert'><p>Error aun no determinado</p></div>";
		}else{
			$error.="<div class='alert'><p>No se Puede Completar la operacion s</p></div>";
		}
		
		

		if(empty($error)){
			$mbanc->set('status','E1');
			$mbanc->save();

			$saldo+=$m_monto;
			$banc->set('saldo',$saldo);
			$banc->save();
			redirect($this->url."dataedit/show/$id");
		}else{
			$data['content'] = $error.anchor($this->url."/dataedit/show/$id",'Regresar');
			$data['title']   = " ".$this->tits." ";
			$data["head"]    = $this->rapyd->get_head().script('jquery.js').script("plugins/jquery.numeric.pack.js");
			$this->load->view('view_ventanas', $data);
		}
	}
	
	function siva($id){
	
	$this->rapyd->load('dataobject');
	
		$riva   =  new DataObject("riva");
		$riva->load_where('mbanc',$id);
		$rivaid  = $riva->get('nrocomp');

		
		redirect('formatos/ver/RIVA/'.$rivaid);
	}
	
	function sislr($id){
	
		$this->rapyd->load('dataobject');
		
		$riva   =  new DataObject("mbanc");
		$riva->load($id);
		$rivaid  = $riva->get('islrid');
	
		
		redirect('formatos/ver/ISLR/'.$rivaid);
	}

	function sp_presucalc($codigoadm){
		//$this->db->simple_query("CALL sp_presucalc($codigoadm)");
		return true;
	}

}
?>



