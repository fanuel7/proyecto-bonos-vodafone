<?php
class SIMActivationVodafoneScreen {
	protected $obj = null;
	protected $view = null;
	protected $productId = null;
	protected $resultIsOk = null;
	protected $resultMessage = null;
	protected $ticket = null;
	protected $documentTypes = array(
		'1' => 'nif',
		'2' => 'nie',
		'3' => 'cif',
		'4' => 'passport'
	);
	protected $articles = array(
		'200081779' => 'Internacional Smartphone',
		'2000826' => 'PACK FACIL',
		'200081786' => 'SIM INTERNACIONAL 15&euro;',
		'20008502' => 'SIM INTERNACIONAL 5&euro;',
		'20008796' => 'SIM YU 15&euro;',
		'200081740' => 'Yu!',
		'200081831' => 'Internacional',
		'200081858' => 'Internacional Smartphone',
		'200082942' => 'Welcome SIM Yu MEGAYUSER 20&euro;',
		'200084257' => 'Vodafone Smart Turbo7',
		'200084258' => 'Bono Europa 300 min 5&euro;',
		'200084259' => 'Bono Latinoamerica 100 Min 5&euro;',
		'200084260' => 'Bono Turistas  20&euro;'

	);
	protected $genders = array(
		'H' => 'male',
		'M' => 'female'
	);
	
	public function __construct($obj, $productId) {
		$this->obj = $obj;
		$this->productId = $productId;
		$tabs = array(
			'v4_sims' => Helper::getURL('sim_activation_presentation.php'),
			//'portability' => Helper::getURL('portability/'),
			'SIMActivationHistory' => Helper::getURL('simActivation/history/')
		);
		$this->view = new TemplateStepsView($obj);
		$this->view ->setTabs($tabs)
			 		->setSelectedTab('v4_sims')
			 		->setModule('sim-activation/vodafone')
			 		->setShowDetails(true)
			 		->setVariable('productId', $this->productId)
					->setAdTemplatePath('sim-activation/vodafone/view/templateAd.html.php');
			 		
			 		//->setAdTemplatePath('pinless/pins/view/adTemplate.html.php');
	}
	
	public function render1($errors = null) {
		//$productsArray = $this->products->get();		
		$this->view
			 //->setInnerTemplate('defaultInput')
			 ->setScreen(1)
			 //->setVariable('showAmountsText', false)
			 ->setVariable('loadProductInfoJS', false)
			 ->setVariable('documentTypes', $this->documentTypes)
			 ->setVariable('articles', $this->articles)
			 ->setVariable('genders', $this->genders);
			 //->setVariable('operator', $_POST['operator'])
			 //->setVariable('productsQuery', $this->products)
			 //->setVariable('amount', $this->amount)
			 //->setVariable('SMSFixedValue', $SMSFixedValue)
			 //->setVariable('SMSFixedValueLength', $SMSFixedValueLength)
			 //->setVariable('SMSField', $this->SMSField);
		if (isset($errors)) $this->view->setErrors($errors);
		$this->view->render();
	}
	
	public function render2() {
		//$product = Product::get($this->productId, $this->obj);
		//var_dump($product);
		//$amountText = trim($product->getFieldValue('AmtListSrc'), '·');
		//$amountText.= ' '.Helper::getLocalCurrency($this->obj);
		$data = $this->renderSIMData();
		$this->view	->setInnerTemplate('defaultValidation')
					->setScreen(2)
					->setVariable('productId', $this->productId)
					->setVariable('hidden', array('MSISDN' => $this->MSISDN, 'Name' => $this->Name, 'FirstLastName' => $this->FirstLastName,
					'SecondLastName' => $this->SecondLastName, 'DocumentType' => $this->DocumentType, 'DocumentNumber' => $this->DocumentNumber,
					'CompanyName' => $this->CompanyName, 'Email' => $this->Email, 'Town' => $this->Town, 'PostalCode' => $this->PostalCode,
					'ArticleId' => $this->ArticleId, 'WayType' => $this->WayType, 'WayName' => $this->WayName, 'Number' => $this->Number,
					'Nationality' => $this->Nationality, 'BirthDate' => $this->BirthDate, 'Gender' => $this->Gender))
					->setVariable('platformError', $resultMessage)
					->setVariable('labelValidationTextTitle1', 'sim_title1')
					->setVariable('labelValidationText1', $data)
					->setVariable('hideConfirmationButton', false)
					->setVariable('platformError', $this->resultMessage);
		
		if (SMS::checkIfMustBeSent($this->obj, $this->SMSField, $this->productId)) {
			$this->view	->setVariable('labelValidationTextTitle2', 'customerMobileNumberToBeSentSMSTo')
						->setVariable('labelValidationText2', Helper::formatPhoneWithPrefix($this->SMSField));
		}
		
		$this->view->render();
	}
	
	protected function renderSIMData() {
		$SIMDataView = new View($this->obj);
		$SIMDataView->setTemplate('../../sim-activation/vodafone/view/validation')
					->setVariable('screen', $this);
		ob_start();
		$SIMDataView->render();
		$text = ob_get_contents();
		ob_end_clean();
		/*
		$text = MSISDN.': '.$this->MSISDN.'<br />';
		$text.= name.': '.$this->Name.'<br />';
		$text.= firstLastName.': '.$this->Name.'<br />';
		$text.= secondLastName.': '.$this->FirstLastName.'<br />';
		return $text;
		*/
		return $text;
	}
	
	public function render3() {
		$finalScreenData = array();
		$this->view	->setInnerTemplate('defaultFinal')
					->setScreen(3)
					->setVariable('productId', $this->productId)
					->setVariable('resultOk', $this->resultIsOk)
					->setVariable('message', $this->resultMessage)
					//->setVariable('titlesColumnStyle', 'width:300px')
					->setVariable('textRows', $finalScreenData)
					->setVariable('ticketEncryptedData', $this->obj->webPlus_ticketPrn($this->ticket));
		$this->view->render();
	}
	
	public function __set($name, $value) {
		$this->$name = $value;
	}
	
	public function __get($name) {
		return $this->$name;
	}
	
	public function setProductId($value) {
		$this->productId = $value;
		return $this;
	}
	
	public function setAmount($value) {
		$this->amount = $value;
		return $this;
	}
	
	public function setResultIsOk($value) {
		$this->resultIsOk = $value;
		return $this;
	}
	
	public function setResultMessage($value) {
		$this->resultMessage = $value;
		return $this;
	}
	
	public function setSMSField($value) {
		$this->SMSField = $value;
		return $this;
	}
	
	public function setTicket($value) {
		$this->ticket = $value;
		return $this;
	}
}