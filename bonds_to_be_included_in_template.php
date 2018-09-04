<?php
/*
 * Description: Bonos, copiado de Recargas internacionales
 */

//require("config.inc.php");
if (!function_exists('__autoload')) {
	function __autoload($class_name) {
		require_once dirname(__FILE__).'/../class/'.$class_name.'.php';
	}
}

function showFinalScreen() {
	$obj = Util::getUser();
	Template::make()->setTemplate(dirname(__FILE__).'/../class/view/inner/templateStepsView/defaultFinal.html.php')
	->setVariable('resultOk', true)
	->setVariable('message', $_SESSION['screen']['bonds']['message'])
	->setVariable('productId', $_SESSION['screen']['bonds']['product-id'])
	->setVariable('ticket', Ticket::translateFromWebplusPrinterToHTML($_SESSION['screen']['bonds']['ticket']))
	->setVariable('ticketEncryptedData', $obj->webPlus_ticketPrn($_SESSION['screen']['bonds']['ticket']))
	->setVariable('textRows', array(
			'product' => Product::get($_SESSION['screen']['bonds']['product-id'], $obj)->getDescription(),
			'phoneNumber' => $_SESSION['screen']['bonds']['phone-number'],
			'amount' => $_SESSION['screen']['bonds']['amount-to-be-paid-by-customer'],
			'ticketNumber' => $_SESSION['screen']['bonds']['ticket-number'],
			'authorizationCode' => $_SESSION['screen']['bonds']['authorization-code']
		)
	)
	->render();
}

session_start();

if (	isset($_POST['verificar']) &&
		array_key_exists('screen', $_SESSION) &&
		array_key_exists('bonds', $_SESSION['screen']) &&
		array_key_exists('result-is-ok', $_SESSION['screen']['bonds']) &&
		$_SESSION['screen']['bonds']['result-is-ok'] === true)
{
	showFinalScreen();
	return;
}		


$obj=new robert();
# Validamos el usuario
if($obj->users_validateCookie()!=1 || $obj->rowUser["DoTransact"]=="N" || $obj->rowUser["DoTransact"]=="0" || $obj->rowUser["ShowMnuInternational"]!="S")
{
	header('location:'.Helper::getUrlFromBaseUrl('/'));
	return;
}

# Por defecto mostramos el formulario
$showForm=1;

# Como el campo del pais contiene id#pais, hay que separarlo...
$pais=explode("#",$_POST["pais"]);

# Si hemos pulsado el boton "recargar"...
if($_POST["verificar"]=="1" && empty($_POST["delete"]))
{
	# Creamos la recarga
	if(($_POST["operator"]>=300 && $_POST["operator"]<=305) || ($_POST["operator"]>=400 && $_POST["operator"]<450) || ($_POST["operator"]>=500 && $_POST["operator"]<550))
		$result=$obj->robert_tn_recarga(true,true);
	else
		$result=$obj->robert_tn_recarga(true);

	# Analizamos la cadena recibida...
	if (substr($result,0,5)=="ERROR")
	{
		#Mostramos el error...
		$error=$result;
		$showForm=0;
	}else if(substr($result,0,5)=="CSQ01"){
		if ($obj->robert_returnValue("23")=="OK")
		{
			# Ponemos los valores en variables de session
			$_SESSION['robert']=$result;
			$_SESSION['fecha']=date("d/m/Y");
			$_SESSION['pais']=$_POST["pais"];
			# Cogemos el nombre del operador
			#20110603 $row=$obj->get_row("SELECT DescProd FROM tbProd WHERE IDProd=".$_POST["operator"]);
			$row = DbCache::getSpWeb_ProdDesc($_POST["operator"], $obj->rowUser["LocalCurrency"], $obj->rowUser["Country"], $obj->rowUser["CompanyGroup"], $obj->rowUser["IDMach"]);
			if ($row === false) {
				$row=$obj->get_row("exec spWeb_ProdDesc ".$_POST["operator"].", ".$obj->rowUser["LocalCurrency"].", @CtryFrom=".$obj->rowUser["Country"].", @CompanyGroup=".$obj->rowUser["CompanyGroup"].", @TypeMach=".$obj->rowUser["IDMach"]);
				DbCache::setSpWeb_ProdDesc($_POST["operator"], $obj->rowUser["LocalCurrency"], $obj->rowUser["Country"], $obj->rowUser["CompanyGroup"], $obj->rowUser["IDMach"], $row);
			}
			$_SESSION['idoperator']=$_POST["operator"];
			$_SESSION['operator']=$obj->convertCharsetToHTML($row["DescProd"]);
			//Si el usuario ya ha colocado el prefijo...

			if($_POST["valuesframetype"]==9)
			{
				# telefono
				if(substr($_POST["telefono"],0,strlen($_SESSION['prefix']))==$_SESSION['prefix'])
					$_SESSION['telefono']=$_POST["telefono"];
				else
					$_SESSION['telefono']=$_SESSION['prefix'].$_POST["telefono"];
			}elseif($_POST["valuesframetype"]==5 || $_POST["valuesframetype"]==10){
				$_SESSION['tarjeta']=$_POST["telefono"];
				$_SESSION['telefono']=$_POST["telefono"];
			}
			$_SESSION['charlen']=$_POST["charlen"];
			$_SESSION['importe']=$_POST["importe"];
			$_SESSION['valuesframetype']=$_POST["valuesframetype"];
			$_SESSION['bonds-screen-ok'] = true;
			$_SESSION['bonds-ticket-number'] = $obj->ticketNumber;
			$_SESSION['bonds-authorization-code'] = $obj->robert_returnValue("24",$_SESSION["robert"]);
			$_SESSION['bonds-phone-number'] = $_POST['telefono'];
			$_SESSION['bonds-ticket'] = $obj->robert_returnValue("48", $_SESSION["robert"]);

			//header('location:'.Helper::getUrlFromBaseUrl('bonds/ok/'));
			// Mostrar pantalla final:
			$_SESSION['screen']['bonds']['result-is-ok'] = true;
			$_SESSION['screen']['bonds']['phone-number'] = $_POST['telefono'];
			$_SESSION['screen']['bonds']['product-id'] = $_POST['operator'];
			$_SESSION['screen']['bonds']['ticket-number'] = $obj->robert_returnValue("6", $_SESSION["robert"]);
			$_SESSION['screen']['bonds']['authorization-code'] = $obj->robert_returnValue("24", $_SESSION["robert"]);
			$_SESSION['screen']['bonds']['amount-to-be-paid-by-customer'] = Helper::formatAmountWithCurrency($_POST['importe'], Util::getUser());
			$_SESSION['screen']['bonds']['ticket'] = $obj->robert_returnValue("48", $_SESSION["robert"]);
			$_SESSION['screen']['bonds']['message'] = html_entity_decode(Lang::_firstUpperRestLower('v4_operationOK'));
			//var_dump($_POST);
			showFinalScreen();
				//echo 'PANTALLA FINAL';
				//
			return;
		}else{
			$showForm=0;
			$error=$obj->robert_returnValue("50")." (".$obj->languageShow("ticket").": ".$obj->ticketNumber.")";
		}
	}else{
		$showForm=0;
		$error=$obj->languageShow("error1");
	}
}else if($_POST["opc"]=="1"){
	$_POST["importe"]=str_replace(',','.',$_POST["importe"]);

	# Validamos los valores
	if($_POST["operator"]!=0 &&
		(
			(strlen($_POST["telefono"])>7 && $_POST["valuesframetype"]==9) ||
			(strlen($_POST["tarjeta"])>7 && ($_POST["valuesframetype"]==5 ||  $_POST["valuesframetype"]==10)) ||
			($_POST["valuesframetype"]==11 || $_POST["valuesframetype"]==2 || $_POST["valuesframetype"]==6)
		)
	)
	{
		if($obj->validateCurrencyRecharge($_POST["operator"]))
		{
			# correcto
			$showForm=0;
		}else{
			$eimporte=$obj->languageShow("amountError2");
			$infoamount=$obj->robert_ShowInfoAmount($_POST["operator"]);
		}
	}else{
		if($_POST["operator"]==0)
			$obj->formShow_setError("operator",$obj->languageShow("productError"));
		if($_POST["telefono"]<=8)
			$obj->formShow_setError("telefono",$obj->languageShow("phoneNumber"));
		if($_POST["tarjeta"]<=8)
			$obj->formShow_setError("tarjeta",$obj->languageShow("numberCardError"));
		if(!is_numeric($_POST["importe"]) || $_POST["importe"]<=0)
			$obj->formShow_setError("importe",$obj->languageShow("amountError1"));
	}
}

# Si estamos en la confirmacion y pulsamos Atras...
if($_POST["delete"])
{
	$showForm=1;
	$infoamount=$obj->robert_ShowInfoAmount($_POST["operator"]);
}

//include("inc/header.inc.php");

# intentamos mostrar el valor aproximado, al cargar la pagina, por si va atras.
?>
<script type="text/javascript">
<!--
onload=function()
{
	if (typeof(initializeProductsCombo) == 'function') initializeProductsCombo();
	idImageSelected='-------<?php echo $_POST["operator"] ?>';
	amtCurrencyDst($("#idimporte").val());
	$('#idSend').focus();
}

literal.set('aprox','<?php echo addslashes($obj->languageShow("aprox."))?>');
literal.set('onArrival','<?php echo addslashes($obj->languageShow("onArrival"))?>');
-->
</script>

			<?php include dirname(__FILE__).'/../inc/recargas.inc.php';?>
			<div class="row">
				<!--<ul class="vertical-menu">
					<li>
						<a class="button_icon" href="<?php Helper::getBaseUrl()?>/tpv.php">Anticipo</a>
					</li>
					<li>
						<a class="button_icon" onclick="javascript:terminos.focus();terminos.print();" style="cursor: pointer;">TPV</a>
					</li>
				</ul>-->
			</div>
<?php
if($showForm==0)
{
	# Verificamos los datos
// 	echo "<h1>".$obj->languageShow("tid_recarga_title")."<br />".$obj->languageShow("international_text_ft".$_POST["valuesframetype"])."</h1>";
?>
<script type="text/javascript">
var screen = 2;
</script>
	<?php
	$obj->formShow_up(0, "disableButtonSend()", Helper::getURL('bonds/'));
	$obj->formShow_setValueHidden("AmtCurrencyDst",$_POST["AmtCurrencyDst"]);
	$obj->formShow_setValueHidden("AmtListSrc",$_POST["AmtListSrc"]);
	$obj->formShow_setValueHidden("AmtListDst",$_POST["AmtListDst"]);
	$obj->formShow_setValueHidden("AmtShowExchange",$_POST["AmtShowExchange"]);

	$obj->formShow_setValueHidden("verificar","1");
	$obj->formShow_setValueHidden("pais",$_POST["pais"]);
	$obj->formShow_setValueHidden("operallow",$_POST["operallow"]);
	$obj->formShow_setValueHidden("charlen",$_POST["charlen"]);
	$obj->formShow_setValueHidden("prefix",$_POST["prefix"]);
	$obj->formShow_setValueHidden("operator",$_POST["operator"]);
	if ($this->enterpriseCountry == 'ES') $obj->formShow_setValueHidden("changeAd", 1);
	else $obj->formShow_setValueHidden("changeAd", 0);
	if($_POST["valuesframetype"]==9 || $_POST["valuesframetype"]==11)
		$obj->formShow_setValueHidden("telefono",$_POST["telefono"]);
	elseif($_POST["valuesframetype"]==5 || $_POST["valuesframetype"]==10)
	{
		$obj->formShow_setValueHidden("telefono",$_POST["tarjeta"]);
		$obj->formShow_setValueHidden("tarjeta",$_POST["tarjeta"]);
	}
	$obj->formShow_setValueHidden("importe",$_POST["importe"]);
	$obj->formShow_setValueHidden("valuesframetype",$_POST["valuesframetype"]);

	#20110603 $row=$obj->get_row("SELECT DescProd,CurrencyProd FROM tbProd WHERE IDProd=".$_POST["operator"]);
	$row = DbCache::getSpWeb_ProdDesc($_POST["operator"], $obj->rowUser["LocalCurrency"], $obj->rowUser["Country"], $obj->rowUser["CompanyGroup"], $obj->rowUser["IDMach"]);
	if ($row === false) {
		$row=$obj->get_row("exec spWeb_ProdDesc ".$_POST["operator"].", ".$obj->rowUser["LocalCurrency"].", @CtryFrom=".$obj->rowUser["Country"].", @CompanyGroup=".$obj->rowUser["CompanyGroup"].", @TypeMach=".$obj->rowUser["IDMach"]);
		DbCache::setSpWeb_ProdDesc($_POST["operator"], $obj->rowUser["LocalCurrency"], $obj->rowUser["Country"], $obj->rowUser["CompanyGroup"], $obj->rowUser["IDMach"], $row);
	}
	$obj->formShow_setValueHidden("CurrencyProd",$row["CurrencyProd"]);
	?>
	<?php

}elseif ($showForm==1){
	unset($_SESSION['screen']['bonds']);
	
	# Mostramos el formulario
// 	echo "<h1>".$obj->languageShow("tid_recarga_title")."<br />".$obj->languageShow("international_text")."</h1>";

	$obj->formShow_up(0, "validateBonds()", Helper::getURL('bonds/'));
	if($error)
		echo "<div class='textError'>$error</div>";

	# Tipo de moneda del cliente para la posible conversion
	$obj->formShow_setValueHidden("AmtCurrencyDst",($_POST["AmtCurrencyDst"]?$_POST["AmtCurrencyDst"]:"0"));
	# Listado de importes para la posible conversion
	$obj->formShow_setValueHidden("AmtListSrc",($_POST["AmtListSrc"]?$_POST["AmtListSrc"]:""));
	# Listado de importes para la posible conversion
	$obj->formShow_setValueHidden("AmtListDst",($_POST["AmtListDst"]?$_POST["AmtListDst"]:""));
	# Listado de importes para la posible conversion
	$obj->formShow_setValueHidden("AmtShowExchange",($_POST["AmtShowExchange"]?$_POST["AmtShowExchange"]:""));

	# Generamos un hidden con el valor del OperAllow. Este valor se actualizara
	# al seleccionar el producto
	$obj->formShow_setValueHidden("operallow",($_POST["operallow"]?$_POST["operallow"]:"0"));

	# Generamos un hidden con el valor de la longitud del telefono. Este valor se
	# actualizara al seleccionar el producto
	$obj->formShow_setValueHidden("charlen",($_POST["charlen"]?$_POST["charlen"]:"0"));

	# Generamos un hidden con el valor del prefijo. Este valor se
	# actualizara al seleccionar el pais
	$obj->formShow_setValueHidden("prefix",($_POST["prefix"]?$_POST["prefix"]:"0"));

	if ($this->enterpriseCountry == 'ES') $obj->formShow_setValueHidden("changeAd", 1);
	else $obj->formShow_setValueHidden("changeAd", 0);
} // endif showform==1
?>
<div class="container">

               <?php 			      
			      $tituloLocal = "Bonos";//Aquí ponemos el título del servicio 
            	  $tipoTitulo = 1; //1 Titulo principal y 2 se refiere a sub-titulo
					TitleTemplate::make($obj)
						->setTitulo($tituloLocal)
						->setTipo($tipoTitulo)
						->render();
				   ?>


	<div class = "col-xs-12 col-md-7">
	
<?php
     

// 	echo "<div id='epais' class='form_textError form_align'></div>";

	$obj->formShow_selectShowText(1);
	# Si el lenguaje es chino, arabe o urdo, no se ejecuta el html_entities
	if($_COOKIE["language"]>4)
		$obj->formShow_selectHumanToHtml(0);
	//$sql1="exec spWeb_CtryList @UserID=".$obj->rowUser["CodUser"].", @ArtCatg=0, @Lang=".$_COOKIE["language"].", @Menu=12, @LocalCurrency=".$obj->rowUser["LocalCurrency"].", @CtryFrom=".$obj->rowUser["Country"].", @OrderByCateg=1";
	$sql1="exec spWeb_CtryList @UserID=".$obj->rowUser["CodUser"].", @ArtCatg=0, @Lang=".$obj->rowUser["LangUser"].", @Menu=12, @LocalCurrency=".$obj->rowUser["LocalCurrency"].", @CtryFrom=".$obj->rowUser["Country"].", @OrderByCateg=1";
	//mostramos los paises
	//echo '<div style="width:640px; height:217px"></div>';
	require dirname(__FILE__).'/../class/view/Operators_ProductsTemplateView.php';
	$operators_productsTemplate = new Operators_ProductsTemplateView($obj);
	$operators_productsTemplate->setVariable('selectedProduct', $_POST['operator']);
	if ($showForm == 0) $operators_productsTemplate->makeUnselectable();
	$operators_productsTemplate->render();
	?>
	</div>
	<?php
	//$obj->formShow_country($obj->languageShow("country"),"pais",$sql1,0,$obj->languageShow("select"), "", "", "returnHTML", Helper::getUrlFromBaseUrl('ajax/paisProductoInternacionalGroup.php'),"blockoperator",true,"internacionales");
	$obj->formShow_selectHumanToHtml(1);

// 		echo "<div id='eoperator' class='form_textError form_align'></div>";

	# Contiene el tipo de frameType, el qual determinara el tipo de cosulta
	# a realizar al robert.
	$obj->formShow_setValueHidden("valuesframetype",($_POST["valuesframetype"]?$_POST["valuesframetype"]:"0"));
	# Generamos el array de javascript con los volores del idProducto,frameType
	?>
		
		<?php // RECARGAS >> BONOS >> PRIMERA PARTE DISPLAY
		//cambiamos el nombre del id del div right-windows anterior blockoperator
		/*
		$nID = "NUEVO ID PRODUCT";
		$nTitle ="NUEVO TITLE";
		
		SaleTemplate::make($obj)
		->setProductID($nID)
		->setTitleProduct($nTitle)
		->render();
		*/
		?>
		
	<div id="right-window" class="col-md-5 col-sm-12 col-xs-12 template_box" style="padding:0">
		<?php if($showForm==1){ ?>
		<div class="shadow">
	      	<div style="width:100%; overflow:hidden; background-color:#E6E6E6; position:relative" class="grey_back">     
				<img id="product-image" border="0" style="width:50px; vertical-align:middle" /><h3 id="product-description" style="display:inline-block; vertical-align:middle; margin-left:10px"></h3>
				<?php ButtonShowProductDetailsTemplate::make()->render() ?>
			</div>
		   <div class="form_align">
		    <div style = "visibility:hidden;">
			   <label for="idoperator" class="form_label_align"><?php echo product ?> :</label>
			   <div id="idnameoperator">
				<span style="float:left">
					<select id="idoperator" name="operator" class="form_input_align" disabled="disabled"></select>
				</span>
			   </div>
			</div>
			<span id="clockoperator" style="float:left; visibility:hidden; display:none">
					<img src="<?php echo Helper::getURL('img/clock.gif') ?>" border="none" />
			</span>
		 </div>
		<div class="form_box_template">
		<?php } ?>
		<?php

	if($showForm==0){ // CONFIRMAR OPERACION ?>
	<div class="shadow">
	<div style="width:100%; overflow:hidden; background-color:#E6E6E6; position:relative" class="grey_back">     
		<img id="product-image" border="0" style="width:50px; vertical-align:middle" src="<?php echo Helper::getURL('imgprod/prod'.$_POST['operator'].'.jpg') ?>" /><h3 id="product-description" style="display:inline-block; vertical-align:middle; margin-left:10px"><?php echo Product::get($_POST['operator'], $obj)->getFieldValue('DescProd') ?><img src="/imgprod/operator/operator2.jpg" /> </h3>
		<?php ButtonShowProductDetailsTemplate::make()->setProductId($_POST['operator'])->render() ?>
	</div>
	<div style="padding:10px">
	<?php
		if($error){
		  echo "<div class='alert alert-danger text-center md' role='alert'>$error</div>";
	      }
    ?>
    <div>
    <?php
    echo "<div class='col-md-6 validation_box  validation_first' >
		 
           <img style='width:89px; height:89px' class='shadowImg' src='".Helper::getUrlFromBaseUrl('imgprod/prod'.$_POST["operator"].'.jpg')."' />
           
	     </div>";
	?>
	</div>
	<div >
	<div class='validation_box col-md-6'>
		<div style='margin-top: 25px; color: rgb(27,117,188);' class='val_title md'><?php echo $obj->languageShow("phoneNumber") ?></div>
		<div class='val_content2 md telefono c-font-28' style = "color:blue; bold:true" ><?php echo $obj->formatTelephone($_POST["telefono"],$_POST["charlen"]) ?></div>
	</div>
	<div class='col-md-6 validation_box block'>
 		<div class='val_content3 circle amount '><p><?php echo $_POST["importe"]." ".$currencySimbol[$obj->rowUser["LocalCurrency"]] ?></p></div>
	</div>
	</div>
	<?php
	echo "<div  style = 'margin-bottom: 2em; margin-top: 17em; clear:both; text-align:center'>".$obj->languageShow("v4_textCheckData")."</div>";


	# Si no dispone de la letra A (Anulacion) quiere decir que no puede anular
	//if(strpos($_POST["operallow"],"A")===false)
	if (strpos(Product::get($_POST['operator'], $obj)->getFieldValue('OperAllow'), "A") === false)
	{
		echo "<div class ='alert alert-warning md text-center' role = 'alert' style='color:black; text-transform:uppercase'>".$obj->languageShow("tid_recarga_text3")."</div>";
	} ?>
	<div style="display:inline-block; width:100%">
	<?php
	if($error)
	{
		echo "<div class = 'col-md-6 col-lg-6 col-sm-6 col-xs-6 ' style = 'text-align:center;'>
			      <input type='submit' name='delete' value='".$obj->languageShow("button_back")."' class='btn-secundary md btn btn-xlg c-btn-circle' title='".$obj->languageShow('button_delete_title')."' />
		      </div>
		      <div class = 'col-md-6 col-lg-6 col-sm-6 col-xs-6' style = ' text-align:center;'>
		          <input id='idSend' type='submit' name='send' value='".$obj->languageShow("button_retry")."' class='btn-primary md btn btn-xlg c-btn-circle' title='".$obj->languageShow('button_title')."' />
		      </div>";
  	}else{
  		echo "<div class = 'col-md-6 col-lg-6 col-sm-6 col-xs-6' style = 'text-align:center'>
			     <input type='submit' name='delete' value='".$obj->languageShow("button_back")."' class='btn-secundary md btn btn-xlg c-btn-circle' title='".$obj->languageShow('button_delete_title')."' />
			 </div>
			 <div class = 'col-md-6 col-lg-6 col-sm-6 col-xs-6' style = ' text-align:center;'>
			     <input id='idSend' type='submit' name='send' value='".$obj->languageShow("button_reload")."' class='btn-primary md btn btn-xlg c-btn-circle' title='".$obj->languageShow('button_title')."' />
			 </div>";
  	} ?>
  	</div>
  	<?php
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';

	}else{ // SELECTOR BONO
		echo "<div>";


		echo "<div style='text-align:center; clear:both;'><div id='amountButtons' class='key2_container' style='display:none;'></div></div>";

		echo '<div style="padding:10px 0; display:block">';	// Inicio de envoltorio para formulario y teclado
		echo '<div class="form-holder">';	// Inicio de envoltorio para formulario
		
		# Mostramos el importe
		//echo "\n<div id='ft' ".($_POST["valuesframetype"]!=0?"":"style='display:none;'").">";
		echo "\n<div id='ft'>";
			$obj->formShow_text($text=($_POST["operator"]?$obj->languageShow("amount")." (".$currencySimbol[$_SESSION['currency']].")":$obj->languageShow("amount")), $name="importe", $size="20", $maxSize="10", $obligatory=0, $errorMessage="", $description="", $enable=1, $functionAjax="", $funcionPHP="", $functionOnKeyUp="amtCurrencyDst(this.value)", $autocomplete=false, $follow="&nbsp;", $style="val_amount");
			echo "\n<div id='eimporte' class='form_textError form_align' style='".($eimporte?"display:block;":"")."'>".$eimporte."</div>";

		echo "\n</div>";

		// Fee:
		echo '<div id="blockfee" style="display:none">';
		$obj->formShow_text($text=$obj->languageShow("fee"),$name="fee",$size="20",$maxSize="10",$obligatory=0,$errorMessage="",$description="",$enable=0,$functionAjax="",$funcionPHP="",$functionOnKeyUp="", $autocomplete=false, $follow="", $style="val_amount");
		echo '</div>';

		# Campo para la recarga de telefono
		//echo "<div id='ft9' ".($_POST["valuesframetype"]==9?"":"style='display:none;'").">";
		echo "<div id='ft9'>";
			$obj->formShow_text($text=($_SESSION['prefix']?$obj->languageShow("phoneNumber"):$obj->languageShow("phoneNumber")),$name="telefono",$size="20",$maxSize=($_POST["charlen"]?($_POST["charlen"]+strlen($_POST["prefix"])):"20"),$obligatory=0,$errorMessage="",$description="",$enable=1,$functionAjax="",$funcionPHP="",$functionOnKeyUp="", $autocomplete=false, $follow="", $style="val_number");
			echo "<div id='etelefono' class='form_textError form_align'></div>";
		echo "</div>";

		# Campo para la recarga de tarjeta
		echo "<div id='ft5' ".($_POST["valuesframetype"]==5 || $_POST["valuesframetype"]==10?"":"style='display:none;'").">";

			$obj->formShow_text($text=$obj->languageShow("numberCard"),$name="tarjeta",$size="20",$maxSize="21",$obligatory=0, $errorMessage="", $description="", $enable=1, $functionAjax="", $funcionPHP="", $functionOnKeyUp="", $autocomplete=true, $follow="", $style="val_amount");
			echo "<div id='etarjeta' class='form_textError form_align'></div>";
		echo "</div>";

 		echo "<div id='valueAmt' class='infoAmount spacing-1' style='color:rgb(27,117,188)'></div>";
		echo "<div style='margin: 15px 0;'>";
		//$obj->formShow_buttonSubmit(0,0,$obj->languageShow("button_next"),$obj->languageShow("button_reloadCancelar"));
		echo '</div>';
	echo '</div>';	// Fin de envoltorio para formulario
	echo '<div class="keypad-holder" style="padding-right:0">';	// Inicio de lugar donde va el teclado
	NumericKeypadTemplate::make()->render();
	echo '</div>';	// Fin de lugar donde va el teclado
	echo '</div>';	// Fin de envoltorio para formulario y teclado
	?>
	<div style="width:100%; text-align:center">
		<input id="idSend" type="submit" name="send" value="<?php echo Lang::_firstUpper('next') ?>" title="<?php echo Lang::_firstUpper('button_title') ?>" class="btn-primary md btn btn-xlg c-btn-circle" />
	</div>
	<?php
	echo "</div>";
	echo "</div>";
	echo "</div>";
	} ?>
<?php /**
<div id="info_container2" style="width:100%; text-align:center; margin-top:10px" class="shadow">
</div> */ ?>

<?php echo "</div>";
	$obj->formShow_down();
	# Fin contenido de la web
?>
				<p>&nbsp;</p>
			<div id="cont_box2"></div>

</div>
<?php //include('inc/footer.inc.php'); ?>

<?php
if($showForm==1)
{	
	?>
	<script type="text/javascript">
	$(function() {
		$(document).on("click", ".operator", function() {
			$(".operator").css("box-shadow", "none");
			$(this).css("box-shadow", "rgb(42, 163, 216) 0px 0px 6px 4px");
		});
		$(document).on("click", ".product", function() {
			$(".product").css("box-shadow", "none");
			$(this).css("box-shadow", "rgb(42, 163, 216) 0px 0px 6px 4px");
		});
	});
	<?php //if (isset($_POST['operator']) && isset($_POST['importe'])) : ?>
	<?php if (false) : ?>
	$(function() {
		timer = setInterval(function(){initialize1('<?php echo $_POST['operator'] ?>', <?php echo $_POST['importe'] ?>, true)},100);
	});
	<?php endif ?>

	function chooseInternationalProduct(idProd){
		updateImagesCurrencyTrama('valueAmt');
		idImageSelected='imgprod'+idProd;
		<?php /** loadProductInfoOnly(idProd);	// Ésta es la función que pone el título y el icono en el right-window */ ?>
		if (idProd && Product.products[idProd]) {
			$("#product-image").attr("src", "<?php echo Helper::getURL('imgprod/prod') ?>"+idProd+".jpg");
			$("#product-description").html(Product.products[idProd].descProd);
		} else if (idProd != 0) {
			$("#product-image").attr("src", "");
			$("#product-description").html("");
		}
		returnHTML('valueAmt','clockoperator','<?php echo Helper::getUrlFromBaseUrl('ajax/operatorAmt3.php') ?>',idProd,'updateImagesCurrencyTrama');
	}
	</script>
<?php
}

if($_SESSION["o"] && $_SESSION["c"])
{
	?>
	<script type="text/javascript">
	var timeout;
	$(document).ready(function(){
		timeout = setTimeout(checkVariable,1000);
		$("#idpais").combobox('autocomplete', '<?php echo $_SESSION["c"]?>');
		selectPais('<?php echo $_SESSION["c"]?>',<?php echo $_SESSION["o"]?>);
	});
	function checkVariable() {
		if (typeof idImg === 'undefined') {
			timeout = setTimeout(checkVariable,1000);
		} else {
			chooseInternationalProduct(<?php echo $_SESSION['o'] ?>);
			$('#idoperator').val('<?php echo $_SESSION['o'] ?>');
			$('#infoProdContainer').show()
			clearTimeout(timeout);
		}
	}
	</script>
	<?php
	$_SESSION["o"]=$_SESSION["c"]="";
}
?>
<?php if($showForm==0) { ?>
<script type="text/javascript">
window.onload = function(e){
	$(".product > img").removeAttr("onclick");
}
</script>
<button id="showResultModal" style="display: none">  </button>

<div id="windowModal" class="modal">
  <div id="mContent" class="modal-content" style="border-width:2px; border-type:solid; position: relative">
    <?php 
         
     	include "../template/waitProcessTemplate.php";
     ?>
   </div>
</div>
<script type="text/javascript" src="<?php echo Helper::getUrlFromBaseUrl('js/new/modal.js') ?>"></script>
<?php } ?>