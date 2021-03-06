<?php

class reportController extends Controller
{


	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',
				'actions'=>array('create', 'update', 'deleteLine', 'approveLine', 'newLine', 'view', 'list', 'index', 'garmentCost'),
				'users'=>array('@'),
				'expression'=>"Yii::app()->user->getState('isDefaultRole');",
			),
			array('allow',
				'actions'=>array('create', 'update', 'deleteLine', 'approveLine', 'newLine', 'view', 'list', 'index', 'garmentCost'),
				'users'=>array('@'),
				'expression'=>"Yii::app()->user->getState('isLead');",
			),
			array('allow',
				'actions'=>array(),
				'users'=>array('@'),
				'expression'=>"Yii::app()->user->getState('isCustomer');",
			),
			array('allow',
				'users'=>array('@'),
				'expression'=>"Yii::app()->user->getState('isAdmin');",
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}
	
	public function actionNewLine(){
		$namePrefix = $_POST['namePrefix'];
		$count = $_POST['count'];
		
		$sizes = Lookup::listItems('Size');
		$products = array();	
		foreach($sizes as $size){
			$product = new Product;
			$product->SIZE = $size->ID;
			$products[] = array(
				'product'=>$product,
				'line'=>new reportLine,
			);	
		}
		
		$products['lines'] = $products;
		$products['style'] = '';
		$products['availableColors'] = array();
		$products['currentColor'] = null;
		$products['approved'] = false;
		$products['saved'] = false;
		
		$this->renderPartial('//reportLine/_multiForm', array(
			'namePrefix'=>$namePrefix,
			'startIndex'=>$count,
			'products'=>$products,
		));
	}
	
	public function actionApproveLine(){
		$namePrefix = $_POST['namePrefix'];
		$startIndex = $_POST['startIndex'];
		$idList = $_POST['idList'];
		$models = reportLine::model()->findAllByPk($idList);
		$sizes = Lookup::model()->findAllByAttributes(array('TYPE'=>'Size'));
		$products = array();
		$groupedLines = array();
		foreach($models as $model){			
			if($model){
				$model->approve();
				$groupedLines[(string) $model->product->STYLE][(string) $model->product->COLOR][(string) $model->product->SIZE] = $model;
			}
		}
		
		foreach($groupedLines as $style=>$styleGroup){
			foreach($styleGroup as $color=>$colorGroup){
				$approved = false;
				foreach($sizes as $size){ //iterating through sizes because we want ALL of them
					if(isset($colorGroup[(string) $size->ID])){
						$line = $colorGroup[(string) $size->ID];						
						$products[] = array(
							'product'=>$line->product,
							'line'=>$line,
						);
						if($line->isApproved){
							$approved = true;
						}
						$latestProduct = $line->product;
					} else {
						$product = new Product;
						$product->SIZE = $size->ID;
						$product->STYLE = $style;
						$product->COLOR = $color;
						$products[] = array(
							'product'=>$product,
							'line'=>new reportLine,
						);
					}
				}
				if(count($products) > 0){
					$products['lines'] = $products;
					$products['style'] = $latestProduct->vendorStyle; //we'll always have a latestProduct, otherwise we wouldn't enter this loop
					$products['availableColors'] = CHtml::listData(Product::getAllowedColors($latestProduct->VENDOR_ITEM_ID), 'ID', 'TEXT');
					$products['currentColor'] = $color;
					$products['approved'] = $approved;
					$products['saved'] = true; //we're guaranteed that some of the lines in this group are persistent
				}
			}
		}
		if($approved && !Yii::app()->user->getState('isAdmin')){
			$view = '//reportLine/_multiView';
		} else {
			$view = '//reportLine/_multiForm';
		}
		
		$this->renderPartial($view, array(
			'namePrefix'=>$namePrefix,
			'startIndex'=>$startIndex,
			'products'=>$products,
		));
	}
	
	public function actionUnapproveLine(){
		$namePrefix = $_POST['namePrefix'];
		$startIndex = $_POST['startIndex'];
		$idList = $_POST['idList'];
		$models = reportLine::model()->findAllByPk($idList);
		$sizes = Lookup::model()->findAllByAttributes(array('TYPE'=>'Size'));
		$products = array();
		$groupedLines = array();
		foreach($models as $model){			
			if($model){
				$model->unapprove();
				$groupedLines[(string) $model->product->STYLE][(string) $model->product->COLOR][(string) $model->product->SIZE] = $model;
			}
		}
		
		foreach($groupedLines as $style=>$styleGroup){
			foreach($styleGroup as $color=>$colorGroup){
				$approved = false;
				foreach($sizes as $size){ //iterating through sizes because we want ALL of them
					if(isset($colorGroup[(string) $size->ID])){
						$line = $colorGroup[(string) $size->ID];						
						$products[] = array(
							'product'=>$line->product,
							'line'=>$line,
						);
						if($line->isApproved){
							$approved = true;
						}
						$latestProduct = $line->product;
					} else {
						$product = new Product;
						$product->SIZE = $size->ID;
						$product->STYLE = $style;
						$product->COLOR = $color;
						$products[] = array(
							'product'=>$product,
							'line'=>new reportLine,
						);
					}
				}
				if(count($products) > 0){
					$products['lines'] = $products;
					$products['style'] = $latestProduct->vendorStyle; //we'll always have a latestProduct, otherwise we wouldn't enter this loop
					$products['availableColors'] = CHtml::listData(Product::getAllowedColors($latestProduct->VENDOR_ITEM_ID), 'ID', 'TEXT');
					$products['currentColor'] = $color;
					$products['approved'] = $approved;
					$products['saved'] = true; //we're guaranteed that some of the lines in this group are persistent
				}
			}
		}
		$view = '//reportLine/_multiForm';
		
		$this->renderPartial($view, array(
			'namePrefix'=>$namePrefix,
			'startIndex'=>$startIndex,
			'products'=>$products,
		));
	}
	
	public function actionDeleteLine(){
		$model = reportLine::model()->findByPk((int) $_POST['id']);
		if($model){
			if(!$model->delete()){
				throw new CException('Could not delete the report line.');
			}
		}
	}
	
	public function actionGarmentCost($garments, $front, $back, $sleeve){
		$result = array('result'=>CostCalculator::calculateTotal($garments, $front, $back, $sleeve, 0));
		echo CJSON::encode($result);
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new report;
		$customer = new Customer;
		$existingCustomers = Customer::model()->findAll();
		$leaders = User::listUsersWithRole(User::LEAD_ROLE);
		$printers = User::listUsersWithRole(User::DEFAULT_ROLE);
		$styles = Lookup::model()->findAllByAttributes(array('TYPE'=>'Style'));
		$sizes = Lookup::model()->findAllByAttributes(array('TYPE'=>'Size'));
		$colors = Lookup::model()->findAllByAttributes(array('TYPE'=>'Color'));
		$passes = array(0, 1, 2, 3, 4, 5, 6); //as instructed by Ben, number of passes
		//should be limited to a few numbers.
		$print = new Printreport;
		
		$lineData = array();
		$products = array();	
		foreach($sizes as $size){
			$product = new Product;
			$product->SIZE = $size->ID;
			$products[] = array(
				'product'=>$product,
				'line'=>new reportLine,
			);	
		}
				
		$products['lines'] = $products;
		$products['style'] = '';
		$products['availableColors'] = array();
		$products['currentColor'] = null;
		$products['approved'] = false;
		$products['saved'] = false;
		$lineData[] = $products;
		
		/*
		 * Now that I've totally forgotten the format, I think it's time to 
		 * document what the format of the "lineData" array is. The parent array,
		 * "lineData" is a list of lists. For each combination of style and color,
		 * there is a list in "lineData". Each child list is composed of children 
		 * with two elements: a "product" element of type Product which
		 * has its "SIZE" property set to the corresponding size from the DB, and
		 * a "line" element of type reportLine which represents the report line itself.
		 * 
		 * Every list in "lineData" should be grouped by color.
		 * 
		 * New change: each item of lineData is now a triplet of "lines", "style", "currentColor", and 
		 * "availableColors". "lines" contains what was originally the item of lineData,
		 * "style" contains text describing the selected vendor style, "availableColors"
		 * contains the colors available for the selected vendor style (if any), already processed
		 * with CHtml::listData, and "currentColor" contains the ID of the color for the group.
		 * "approved" is true if the set of lines has been approved, otherwise false.*/

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['report']))
		{
			$model->loadFromArray($_POST['report']);
			$customer->attributes = $_POST['Customer'];
			$print->attributes = $_POST['Printreport'];
			
			$saved = true;
			if($saved){
				$printFile = $_FILES['Printreport_Art'];
				$print->createArtFile($printFile);
				$saved = $saved && $print->save();
			} 
			if($saved) {
				$saved = $saved && $customer->save();
			}
			if($saved){
				$model->CUSTOMER_ID = $customer->ID;
				$model->PRINT_ID = $print->ID;
				$model->printDate = $model->dueDate;
				$saved = $saved && $model->save();
			}
			if($saved){
				//if saved, redirect
				Yii::app()->user->setFlash('success', 'The report was created successfully!');
				$this->redirect(array('update', 'id'=>$model->ID));
			} else {
				//otherwise, delete everything
				if(!$model->isNewRecord) {$model->delete();}
				if(!$customer->isNewRecord) {$customer->delete();}
				if(!$print->isNewRecord) {$print->delete();}				
			}
		}	
		

		$this->render('create',array(
			'model'=>$model,
			'customerList'=>$existingCustomers,
			'newCustomer'=>$customer,
			'print'=>$print,
			'leaders'=>$leaders,
			'printers'=>$printers,
			'styles'=>$styles,
			'colors'=>$colors,
			'sizes'=>$sizes,
			'passes'=>$passes,
			'lineData'=>$lineData,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);
		$customer = $model->CUSTOMER;
		$print = $model->printreport;
		$existingCustomers = Customer::model()->findAll();
		$leaders = User::listUsersWithRole(User::LEAD_ROLE);
		$printers = User::listUsersWithRole(User::DEFAULT_ROLE);
		$styles = Lookup::model()->findAllByAttributes(array('TYPE'=>'Style'));
		$sizes = Lookup::model()->findAllByAttributes(array('TYPE'=>'Size'));
		$colors = Lookup::model()->findAllByAttributes(array('TYPE'=>'Color'));
		$passes = array(0, 1, 2, 3, 4, 5, 6); //as instructed by Ben, number of passes
		//should be limited to a few numbers.
		
		$lineData = array();
		$products = array();
		$groupedLines = array();
		foreach($model->reportLines as $line){
			$groupedLines[(string) $line->product->STYLE][(string) $line->product->COLOR][(string) $line->product->SIZE] = $line;
		}
		
		foreach($groupedLines as $style=>$styleGroup){
			foreach($styleGroup as $color=>$colorGroup){
				$approved = false;
				foreach($sizes as $size){ //iterating through sizes because we want ALL of them
					if(isset($colorGroup[(string) $size->ID])){
						$line = $colorGroup[(string) $size->ID];						
						$products[] = array(
							'product'=>$line->product,
							'line'=>$line,
						);
						if($line->isApproved){
							$approved = true;
						}
						$latestProduct = $line->product;
					} else {
						$product = new Product;
						$product->SIZE = $size->ID;
						$product->STYLE = $style;
						$product->COLOR = $color;
						$products[] = array(
							'product'=>$product,
							'line'=>new reportLine,
						);
					}
				}
				if(count($products) > 0){
					$products['lines'] = $products;
					$products['style'] = $latestProduct->vendorStyle; //we'll always have a latestProduct, otherwise we wouldn't enter this loop
					$products['availableColors'] = CHtml::listData(Product::getAllowedColors($latestProduct->VENDOR_ITEM_ID), 'ID', 'TEXT');
					$products['currentColor'] = $color;
					$products['approved'] = $approved;
					$products['saved'] = true; //we're guaranteed that some of the lines in this group are persistent
					$lineData[] = $products;
					$products = array();
				}
			}
		}

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['report']))
		{
			$model->loadFromArray($_POST['report']);
			$customer->attributes = $_POST['Customer'];
			$print->attributes = $_POST['Printreport'];
			
			$saved = true;
			if($saved){
				$printFile = $_FILES['Printreport_Art'];
				$print->createArtFile($printFile);
				$saved = $saved && $print->save();
			} 
			if($saved) {
				$saved = $saved && $customer->save();
			}
			if($saved){
				$model->CUSTOMER_ID = $customer->ID;
				$model->PRINT_ID = $print->ID;
				$saved = $saved && $model->save();
			}
			if($saved){
				//if saved, redirect
				Yii::app()->user->setFlash('success', 'The report was saved successfully!');
				$this->redirect(array('update', 'id'=>$model->ID));
			}
		}
		
		if($print->ART != null){
			$artLink = CHtml::normalizeUrl(array('report/art', 'id'=>$model->ID));
		} else {
			$artLink = null;
		}

		$this->render('update',array(
			'model'=>$model,
			'customerList'=>$existingCustomers,
			'newCustomer'=>$customer,
			'print'=>$print,
			'leaders'=>$leaders,
			'printers'=>$printers,
			'styles'=>$styles,
			'colors'=>$colors,
			'sizes'=>$sizes,
			'artLink'=>$artLink,
			'passes'=>$passes,
			'lineData'=>$lineData,
		));
		
	}
	
	/**
	 * Let's the user download the art associated with a report.
	 */
	public function actionArt($id){
		$model = $this->loadModel($id);
		if($model){
			$file = $model->printreport->ART;
			if($file){
				$name = basename($file);
				//code below obtained from http://iamcam.wordpress.com/2007/03/20/clean-file-names-using-php-preg_replace/
				$replace="_";
				$pattern="/([[:alnum:]_\.-]*)/";
				$name=str_replace(str_split(preg_replace($pattern,$replace,$name)),$replace,$name);
				//end snippet
				
				Yii::app()->request->sendFile($name, file_get_contents($file));
			}
		}
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		if(Yii::app()->request->isPostRequest)
		{
			// we only allow deletion via POST request
			$this->loadModel($id)->delete();

			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax']))
				$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$lastSunday = strtotime('last sunday', time());
		$secondsPerWeek = 24*60*60*7;
		$nextSaturday = $lastSunday + $secondsPerWeek - 1;
		$reportsThisWeek = EventLog::model()->findAllByAttributes(array(
			'USER_ASSIGNED'=>Yii::app()->user->id,
			'OBJECT_TYPE'=>'report',				
			//'EVENT_ID'=>EventLog::report_PRINT,	
		), '`DATE` BETWEEN FROM_UNIXTIME(' . $lastSunday . ') AND FROM_UNIXTIME(' . $nextSaturday . ')');
		$reports = array();
		foreach($reportsThisWeek as $event){
			$reports[] = $event->assocObject;
		}
		$dataProvider = new CArrayDataProvider($reports, array(
			'keyField'=>'ID',
		));
		
		$lastSunday = $lastSunday + $secondsPerWeek;
		$nextSaturday = $nextSaturday + $secondsPerWeek;
		$reportsNextWeek = EventLog::model()->findAllByAttributes(array(
			'USER_ASSIGNED'=>Yii::app()->user->id,
			'OBJECT_TYPE'=>'report',		
			'EVENT_ID'=>EventLog::report_PRINT,
		), '`DATE` BETWEEN FROM_UNIXTIME(' . $lastSunday . ') AND FROM_UNIXTIME(' . $nextSaturday . ')');
		
		$currentWeek = $this->resultToCalendarData($reportsThisWeek);
		$nextWeek = $this->resultToCalendarData($reportsNextWeek);
		$this->render('dashboard',array(
			'dataProvider'=>$dataProvider,
			'currentData'=>$currentWeek,
			'nextData'=>$nextWeek,
		));
	}
	
	public function actionList(){
		$dataProvider = new CActiveDataProvider('report', array(
			'pagination'=>false,
		));
		$this->render('list', array(
			'dataProvider'=>$dataProvider,
		));
	}
	
	private function resultToCalendarData($result){
		$calendarData = array();
		foreach($result as $event){
			$eventDate = strtotime($event->DATE);
			$dayName = date('l', $eventDate);
			$calendarData[$dayName]['date'] = $eventDate;
			$calendarData[$dayName]['items'][] = $event;
		}
		return $calendarData;
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new report('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['report']))
			$model->attributes=$_GET['report'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model=report::model()->findByPk((int)$id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}
	
	public function loadList($type){
		return CHtml::listData(Lookup::listItems($type), 'ID', 'TEXT');
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='report-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
