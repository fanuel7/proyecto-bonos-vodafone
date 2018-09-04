<?php if (!isset($this)) exit() ?>
<script type="text/javascript" src="<?php echo Helper::getURL('js/fieldValidation.js') ?>"></script>
<script type="text/javascript">
<?php require dirname(__FILE__).'/../../../inc/jsFieldValidationTranslations.php' ?>
var countries = new Array();
var companyNameHeight = '80px';
var loadProductInfoOnlyURL = '<?php echo Helper::getURL('ajax/productGetInfo.php') ?>';

$(function() {
	$("#BirthDate").datepicker();
    Validation.validation['MSISDN'] = [['validateMandatory'], ['validateAllDigitsNumeric'], ['validateLength', '9', '9']];
    Validation.validation['Name'] = [['validateMandatory']];
    Validation.validation['FirstLastName'] = [['validateMandatory']];
    Validation.validation['DocumentType'] = [['validateMandatory'], ['validateValuesList', '["1", "2", "3", "4"]']];
    Validation.validation['DocumentNumber'] = [['validateMandatory']];
    Validation.validation['ArticleId'] = [['validateMandatory']];
    Validation.validation['Nationality'] = [['validateMandatory']];
    Validation.validation['BirthDate'] = [['validateMandatory']];
    Validation.validation['Gender'] = [['validateMandatory']];

    $('#DocumentType').bind('change', function() {
        if ($(this).val() == '1' || $(this).val() == '3') {
        	$('#CompanyNameWrapper').animate({height:companyNameHeight}, 600);
        	$('#CompanyName').prop('disabled', false);
        } else {
        	$('#CompanyNameWrapper').animate({height:"0"}, 600);
        	$('#CompanyName').prop('disabled', true);
        }
    });
});

function validate() {
	if ($('#Nationality').val() == 'ES') Validation.validation['SecondLastName'] = [['validateMandatory', '"mandatoryFieldWhenNationalityES"']]; else Validation.validation['SecondLastName'] = [];
	Validation.validation['CompanyName'] = [];
	if ($('#DocumentType').val() == '1') Validation.validation['DocumentNumber'] = [['validateMandatory'], ['validateNIF']];
	else if ($('#DocumentType').val() == '2') Validation.validation['DocumentNumber'] = [['validateMandatory'], ['validateNIE']];
	else if ($('#DocumentType').val() == '3') {
		Validation.validation['DocumentNumber'] = [['validateMandatory'], ['validateCIF']];
	} else if ($('#DocumentType').val() == '4') Validation.validation['DocumentNumber'] = [['validateMandatory']];

	if ($('#Email').val().trim().length > 0) Validation.validation['Email'] = [['validateEmail']]; else Validation.validation['Email'] = [];

	if ($('#DocumentType').val() == '3' || $('#CompanyName').val().trim().length > 0) {
		Validation.validation['Nationality'] = [['validateMandatory'], ['validateValuesList', '["ES"]', '"nationalityMustBeESWhenEnterprise"']];
	} else Validation.validation['Nationality'] = [['validateMandatory']];

	var validationResult = Validation.validate();
	companyNameHeight = '38px';
	if (Validation.validateField(document.getElementById('CompanyName')) !== true && $('#DocumentType').val() == '3') {companyNameHeight = '62px'; $('#CompanyNameWrapper').css('height', '62px');}
	else if (Validation.validateField(document.getElementById('CompanyName')) === true && $('#DocumentType').val() == '3') {companyNameHeight = '38px'; $('#CompanyNameWrapper').css('height', '38px');}
	if (!validationResult) return false; else return true;
}
</script>

<div class = "c-content-panel">
<form id="formGoToScreen1" method="post" class = "col-xs-12 col-md-8">
<div class="c-content-title-1 c-title-md c-margin-b-20 clearfix">
		<img src="<?php echo Helper::getURL('imgprod/prod'.$this->productId.'.jpg') ?>" />
    <h3><?php echo $this->obj->languageShow("dataOfTheLine")?></h3>
</div>
<div id="MSISDNValidation" class="form_textError form_align" style="display:block"><?php echo $this->getCurrentError() ?></div>
<div class="col-xs-12 single_form" >
	<div class="form_align"><label for="MSISDN" class="form_label_align" style="width:200px"><strong><?php echo $this->obj->languageShow('phoneNumber') ?> :</strong></label>
		<input id="MSISDN" name="MSISDN" value="<?php echo $_POST['MSISDN'] ?>" class="form_input_align" title="<?php echo $this->obj->languageShow('phoneNumber') ?>" maxlength="9" autocomplete="off" style="width: 100%;"/><br>

<br>
		<div class="col-xs-12 " >
		<div class="form_align"><label><strong> Id Activacion</strong></label> <br>

		<input type="text" name="Idactivacion" value="" style="width: 30%;"/>  
	</div>
</div>

</div>

<div id="NameValidation" class="form_textError form_align" style="display:block"><?php echo $this->getCurrentError(ErrorIndex::INCREASE_BEFORE) ?></div>
<div class="col-xs-12 col-md-6 single_form" >
	<div class="form_align">
		<label for="Name" class="form_label_align" style="width:200px">
			<strong><?php echo $this->obj->languageShow('name') ?> :</strong>
		</label>
		<input class="form_input_align" type="text" id="Name" name="Name" value="<?php echo $_POST['Name'] ?>" autocomplete="off" maxlength="30" autocomplete="off" />
	</div>
</div>
<div id="FirstLastNameValidation" class="form_textError form_align" style="display:block"><?php echo $this->getCurrentError(ErrorIndex::INCREASE_BEFORE) ?></div>
<div class="col-xs-12 col-md-6 single_form" >
	<div class="form_align">
		<label for="FirstLastName" class="form_label_align" style="width:200px">
			<strong><?php echo $this->obj->languageShow('firstLastName') ?> :</strong>
		</label>
		<input class="form_input_align" type="text" id="FirstLastName" name="FirstLastName" value="<?php echo $_POST['FirstLastName'] ?>" autocomplete="off" maxlength="30" />
	</div>
</div>
<div class="col-xs-12 col-md-6 single_form" >
<div id="SecondLastNameValidation" class="form_textError form_align" style="display:block"><?php echo $this->getCurrentError(ErrorIndex::INCREASE_BEFORE) ?></div>
	<div class="form_align">
		<label for="SecondLastName" class="form_label_align" style="width:200px">
			<?php echo $this->obj->languageShow('secondLastName') ?> :
		</label>
		<input class="form_input_align" type="text" id="SecondLastName" name="SecondLastName" value="<?php echo $_POST['SecondLastName'] ?>" autocomplete="off" maxlength="30" />
	</div>
</div>
<div class="col-xs-12 col-md-6 single_form" >
	<div id="DocumentTypeValidation" class="form_textError form_align" style="display:block"><?php echo $this->getCurrentError(ErrorIndex::INCREASE_BEFORE) ?></div>
	<div class="form_align">
		<label for="DocumentType" class="form_label_align" style="width:200px">
			<strong><?php echo $this->obj->languageShow('documentType') ?> :</strong>
		</label>
		<select class="form_input_align" id="DocumentType" name="DocumentType">
		<option value=""><?php echo select ?></option>
		<?php foreach ($this->documentTypes as $documentTypeId => $documentTypeName) : ?>
		<option<?php if ($_POST['DocumentType'] == $documentTypeId) echo ' selected="selected"' ?> value="<?php echo $documentTypeId ?>"><?php echo utf8_encode($this->obj->languageShow($documentTypeName)) ?></option>
		<?php endforeach ?>
		</select>
	</div>
</div>
<div class="col-xs-12 col-md-6 single_form" >
	<div id="DocumentNumberValidation" class="form_textError form_align" style="display:block"><?php echo $this->getCurrentError(ErrorIndex::INCREASE_BEFORE) ?></div>
	<div class="form_align">
		<label for="DocumentNumber" class="form_label_align" style="width:200px">
			<strong><?php echo $this->obj->languageShow('documentNumber') ?> :</strong>
		</label>
		<input class="form_input_align" type="text" id="DocumentNumber" name="DocumentNumber" value="<?php echo $_POST['DocumentNumber'] ?>" autocomplete="off" maxlength="30" />
	</div>
</div>
<div class="col-xs-12 col-md-6 single_form" >
	<div id="CompanyNameWrapper" style="margin-bottom:30px;overflow:hidden<?php if ($_POST['DocumentType'] != '1' && $_POST['DocumentType'] != '3') echo ';height:0' ?>">
		<div id="CompanyNameValidation" class="form_textError form_align" style="display:block"><?php echo $this->getCurrentError(ErrorIndex::INCREASE_BEFORE) ?></div>
		<div class="form_align">
			<label for="CompanyName" class="form_label_align" style="width:200px">
				<?php echo $this->obj->languageShow('companyNameWhenEnterprise') ?> :
			</label>
			<input class="form_input_align" type="text" id="CompanyName" name="CompanyName" value="<?php echo $_POST['CompanyName'] ?>" autocomplete="off" maxlength="30" />
		</div>
	</div>
	<div class="form_align">
		<label for="Email" class="form_label_align" style="width:200px">
			<?php echo $this->obj->languageShow('email') ?> :
		</label>
		<input class="form_input_align" type="text" id="Email" name="Email" value="<?php echo $_POST['Email'] ?>" autocomplete="off" maxlength="30" />
		<div id="EmailValidation" class="form_textError form_align" style="display:block"><?php echo $this->getCurrentError(ErrorIndex::INCREASE_BEFORE) ?></div>

	</div>
</div>
<div class="col-xs-12 col-md-6 single_form" >
	<div id="ArticleIdValidation" class="form_textError form_align" style="display:block"><?php echo $this->getCurrentError(ErrorIndex::INCREASE_BEFORE) ?></div>
	<div class="form_align">
		<label for="ArticleId" class="form_label_align" style="width:200px">
			<strong><?php echo $this->obj->languageShow('articulo') ?> :</strong>
		</label>
		<select class="form_input_align" id="ArticleId" name="ArticleId">
		<option value=""><?php echo select ?></option>
		<?php foreach ($this->articles as $articleId => $articleName) : ?>
		<option<?php if ($_POST['ArticleId'] == $articleId) echo ' selected="selected"' ?> value="<?php echo $articleId ?>"><?php echo utf8_encode($articleName) ?></option>
		<?php endforeach ?>
		</select>
	</div>
</div>
<div class="col-xs-12 col-md-6 single_form" >
	<div id="NationalityValidation" class="form_textError form_align" style="display:block"><?php echo $this->getCurrentError(ErrorIndex::INCREASE_BEFORE) ?></div>
	<div class="form_align">
		<label for="Nationality" class="form_label_align" style="width:200px">
			<strong><?php echo $this->obj->languageShow('nacionalidad') ?> :</strong>
		</label>
		<select class="form_input_align" id="Nationality" name="Nationality">
		<option value=""><?php echo select ?></option>
		<?php foreach (VodafoneDataField::getCountriesVodafoneWithAccents() as $nationalityId => $nationalityLabel) : ?>
		<option<?php if ($_POST['Nationality'] == $nationalityId) echo ' selected="selected"' ?> value="<?php echo $nationalityId ?>"><?php echo utf8_encode($nationalityLabel) ?></option>
		<?php endforeach ?>
		</select>
	</div>
</div>
<div class="col-xs-12 col-md-6 single_form" >
	<div id="BirthDateValidation" class="form_textError form_align" style="display:block"><?php echo $this->getCurrentError(ErrorIndex::INCREASE_BEFORE) ?></div>
	<div class="form_align">
		<label for="BirthDate" class="form_label_align" style="width:200px">
			<strong><?php echo $this->obj->languageShow('birthDate') ?> :</strong>
		</label>
		<input class="form_input_align" type="text" id="BirthDate" name="BirthDate" value="<?php echo $_POST['BirthDate'] ?>" autocomplete="off" maxlength="30" />
	</div>
</div>
<div class="col-xs-12 col-md-6 single_form" >
	<div id="GenderValidation" class="form_textError form_align" style="display:block"><?php echo $this->getCurrentError(ErrorIndex::INCREASE_BEFORE) ?></div>
	<div class="form_align">
		<label for="Gender" class="form_label_align" style="width:200px">
			<strong><?php echo $this->obj->languageShow('sex') ?> :</strong>
		</label>
		<select class="form_input_align" id="Gender" name="Gender">
		<option value=""><?php echo select ?></option>
		<?php foreach ($this->genders as $genderId => $genderLabel) : ?>
		<option<?php if ($_POST['Gender'] == $genderId) echo ' selected="selected"' ?> value="<?php echo $genderId ?>"><?php echo utf8_encode($this->obj->languageShow($genderLabel)) ?></option>
		<?php endforeach ?>
		</select>
	</div>
</div>
	<div class="col-xs-12 col-md-6 col-md single_form" >
		<div id="mainSubmitWrapper" class="form_center"><input style="margin-top:20px;margin-bottom:30px;" class="btn-primary md center-block btn btn-xlg c-btn-circle"   type="submit" id="mainSubmit" name="mainSubmit" value="<?php echo $this->obj->languageShow('send') ?>" onclick="return validate()" /></div>
</div>

<div class="col-xs-12 col-md-6 col-md single_form" >
<input  style="margin-top:20px;margin-bottom:30px;" class="btn-primary md center-block btn btn-xlg c-btn-circle"type="submit" value="Activar Bonos" onclick = "'/preb2b/index.php'"/>
	

	</div>
 </form>
</div>
