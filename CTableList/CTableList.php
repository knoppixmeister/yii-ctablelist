<?php
	defined('YII_APP') || die;

	Yii::import('zii.widgets.grid.CGridView');

	class CTableList extends CGridView {
		public $selectableRows = 0;

		public $modelName;
		public $viewName;
		//public $ajaxUp;
		public $moduleName;
		public $enableGvSettings = true;

		public $defaultGvSettings;

		public $specialColumns;
		public $urlControls;

		public $enableControls = false;
		public $enableTags = false;

		public $summaryText;

		private $allFields = array();
		private $allFieldNames = array();
		private $specialColumnNames = array();
		private $gvSettings = null;
		private $columnSelectorId;
		private $columnSelectorHtml;

	    protected $ajax = false;

		public function init() {
			$this->ajax = isset($_GET['ajax']) && $_GET['ajax'] === $this->id;
			//$this->ajax = $this->ajaxUp;
			if($this->ajax) ob_clean();

			// $this->selectionChanged = 'js:function() { console.debug($.fn.yiiGridView.getSelection("'.$this->id.'")); }';

			// if(empty($this->modelName))
				// $this->modelName = $this->getId();
			if(empty($this->viewName)) $this->viewName = $this->modelName;
			if(empty($this->moduleName)) $this->moduleName = '';
			//if($this->modelName == 'Quotes') $this->modelName='Quote';

			$this->columnSelectorId = $this->getId() . '-column-selector';

			if(	isset($_GET['gvSettings']) && 
				isset($_GET['viewName']) && 
				$_GET['viewName'] == $this->viewName)
			{
				$this->gvSettings = json_decode($_GET['gvSettings'],true);
				// unset($_GET['gvSettings']);
				// die(var_dump($this->gvSettings));
				
				ProfileChild::setGridviewSettings($this->gvSettings,$this->viewName);
			}
			else {
				$this->gvSettings = ProfileChild::getGridviewSettings($this->viewName);
			}

			if($this->gvSettings == null) {
				$this->gvSettings = $this->defaultGvSettings;
			}

			// die(var_dump($this->gvSettings));
			// die(var_dump(ProfileChild::getGridviewSettings($this->viewName)));

			// load names from $specialColumns into $specialColumnNames
			foreach($this->specialColumns as $columnName => &$columnData) {
				if(isset($columnData['header'])) {
					$this->specialColumnNames[$columnName] = $columnData['header'];
				}
				else {
					$this->specialColumnNames[$columnName] = CActiveRecord::model($this->modelName)->getAttributeLabel($columnName);
				}
			}

			// start allFieldNames with the special fields
			if(!empty($this->specialColumnNames)) {
				$this->allFieldNames = $this->specialColumnNames;
			}

			// add controls column if specified
			if ($this->enableControls) {
				$this->allFieldNames['gvControls'] = Yii::t('app','Tools');
			}
			
			$this->allFieldNames['gvCheckbox'] = Yii::t('app', 'Checkbox');
	
			// load fields from DB
			// $fields=Fields::model()->findAllByAttributes(array('modelName'=>ucwords($this->modelName)));
			$fields = CActiveRecord::model($this->modelName)->getFields();
			foreach($fields as $field){
				$this->allFields[$field->fieldName] = $field;
			}
			
			// add tags column if specified
			if($this->enableTags) {
				$this->allFieldNames['tags'] = Yii::t('app','Tags');
			}
			
	
			foreach($this->allFields as $fieldName=>&$field) {
				//echo 'fieldName='.$field->fieldName.'<br>';
				$this->allFieldNames[$fieldName] = CActiveRecord::model($this->modelName)->getAttributeLabel($field->fieldName);
			}
		    //die();
			// update columns if user has submitted data
			if(isset($_GET['columns']) && isset($_GET['viewName']) && $_GET['viewName'] == $this->viewName) {	// has the user changed column visibility?
	
				foreach(array_keys($this->gvSettings) as $key) {
					$index = array_search($key,$_GET['columns']);	// search $_GET['columns'] for the column
					if($index === false)							// if it's not in there,
						unset($this->gvSettings[$key]);					// delete that junk
					else											// othwerise, remove it from $_GET['columns']
						unset($_GET['columns'][$index]);			// so the next part doesn't add it a second time
				}
				foreach(array_keys($this->allFieldNames) as $key) {							// now go through $allFieldNames and add any fields that
					if(!isset($this->gvSettings[$key]) && in_array($key,$_GET['columns']))	// are present in $_GET['columns'] but not already in the list
						$this->gvSettings[$key] = 80;										// default width of 80
				}
			}
			unset($_GET['columns']);	// prevents columns data from ending up in sort/pagination links
			unset($_GET['viewName']);
			unset($_GET['gvSettings']);
	
			// adding/removing columns changes the total width,
			// so let's scale the columns to match the correct total (590px)
			$totalWidth = array_sum(array_values($this->gvSettings));
			
			if($totalWidth > 0) {
				$widthFactor = (585 ) / $totalWidth; //- count($this->gvSettings)
				$sum = 0;
				$scaledSum = 0;
				foreach($this->gvSettings as $columnName => &$columnWidth) {
					$sum += $columnWidth;
					$columnWidth = round(($sum) * $widthFactor)-$scaledSum;		// map each point onto the nearest integer in the scaled space
					$scaledSum += $columnWidth;
				}
			}
			// die(var_dump($this->gvSettings).' '.$this->viewName);
			ProfileChild::setGridviewSettings($this->gvSettings,$this->viewName);	// save the new Gridview Settings
	
			// die(var_dump($this->gvSettings));
	
			$columns = array();
	
			$datePickerJs = '';
	
			foreach($this->gvSettings as $columnName => $width) {
				
				// $width = (!empty($width) && is_numeric($width))? 'width:'.$width.'px;' : null;	// make sure width is reasonable, then convert it to CSS
				$width = (!empty($width) && is_numeric($width))? $width : null;	// make sure width is reasonable
				
				// $isDate = in_array($columnName,array('createDate','completeDate','lastUpdated','dueDate', 'expectedCloseDate', 'expirationDate', 'timestamp','lastactivity'));
				
				$isCurrency = in_array($columnName,array('annualRevenue','quoteAmount'));
				
				$lang = (Yii::app()->language == 'en')? '':Yii::app()->getLanguage();
				
				//if($isDate)
					//$datePickerJs .= ' $("#'.$columnName.'DatePicker").datepicker('
						//.'$.extend({showMonthAfterYear:false}, {"dateFormat":"'.$this->controller->formatDatePicker().'"})); ';
						// .'{"showAnim":"fold","dateFormat":"yy-mm-dd","changeMonth":"true","showButtonPanel":"true","changeYear":"true","constrainInput":"false"}));';
				
				
				$newColumn = array();
	
				if(array_key_exists($columnName,$this->specialColumnNames)) {
					
					$newColumn = $this->specialColumns[$columnName];
					// $newColumn['name'] = 'lastName';
					$newColumn['id'] = 'C_'.$columnName;
					// $newColumn['header'] = Yii::t('contacts','Name');
					$newColumn['headerHtmlOptions'] = array('colWidth'=>$width);
					// $newColumn['value'] = 'CHtml::link($data->firstName." ".$data->lastName,array("view","id"=>$data->id))';
					// $newColumn['type'] = 'raw';
					// die(print_r($newColumn));
					$columns[] = $newColumn;
	
				} else if((array_key_exists($columnName, $this->allFields))) { // && $this->allFields[$columnName]->visible == 1)) {
	
					$newColumn['name'] = $columnName;
					$newColumn['id'] = 'C_'.$columnName;
					$newColumn['header'] = CActiveRecord::model($this->modelName)->getAttributeLabel($columnName);
					$newColumn['headerHtmlOptions'] = array('colWidth'=>$width);
					
					if($isCurrency) {
						$newColumn['value'] = 'Yii::app()->locale->numberFormatter->formatCurrency($data->'.$columnName.',Yii::app()->params->currency)';
						$newColumn['type'] = 'raw';
					} else if($columnName == 'assignedTo' || (($columnName == 'createdBy' || $columnName == 'updatedBy') && $this->allFields[$columnName]->type=='assignment')) {
						if ($columnName == 'assignedTo') {
						  $newColumn['value'] = 'empty($data->assignedTo)?Yii::t("app","Anyone"):User::getUserLinks($data->assignedTo)';
						}
						if ($columnName == 'createdBy') {
						  $newColumn['value'] = 'empty($data->createdBy)?"":User::getUserLinks($data->createdBy)';
						}
						if ($columnName == 'updatedBy') {
						  $newColumn['value'] = 'empty($data->updatedBy)?"":User::getUserLinks($data->updatedBy)';
						}
						$newColumn['type'] = 'raw';
					} elseif($this->allFields[$columnName]->type=='date') {
						$newColumn['value'] = 'empty($data["'.$columnName.'"])? "" : Yii::app()->dateFormatter->format(Yii::app()->locale->getDateFormat("medium"), $data["'.$columnName.'"])';
						//echo $newColumn['value'];
						//die();
					} elseif($this->allFields[$columnName]->type=='link') {
						$newColumn['value'] = 'LSModel::getModelLink($data->'.$columnName.',"'.ucfirst($this->allFields[$columnName]->linkType).'")';
						$newColumn['type'] = 'raw';
					} elseif($this->allFields[$columnName]->type=='boolean') {
						$field=$this->allFields[$columnName];
						$type=ucfirst($field->linkType);
						$newColumn['value']='$data->'.$columnName.'==1?Yii::t("actions","Yes"):Yii::t("actions","No")';
						$newColumn['type'] = 'raw';
					}elseif($this->allFields[$columnName]->type=='phone'){
	                    $newColumn['type'] = 'raw';
	                    $newColumn['value'] = 'LSModel::getPhoneNumber("'.$columnName.'","'.$this->modelName.'",$data->id)';
	                }
				
	
					if(Yii::app()->language == 'en') {
						$format =  "M d, yy";
					} else {
			    		$format = Yii::app()->locale->getDateFormat('medium'); // translate Yii date format to jquery
			    		$format = str_replace('yy', 'y', $format);
			    		$format = str_replace('MM', 'mm', $format);
			    		$format = str_replace('M','m', $format);
			    	}
			    	
					/* $newColumn['filter'] = $isDate? $this->widget("zii.widgets.jui.CJuiDatePicker",array(
						'model'=>$this->filter, //Model object
						// 'id'=>$columnName.'DatePicker',
						'attribute'=>$columnName, //attribute name
						// 'mode'=>'datetime', //use 'time','date' or 'datetime' (default)
						// 'htmlOptions'=>array('style'=>'width:80%;'),
						'options'=>array(
							'dateFormat'=>$format,
						), // jquery plugin options
						'language'=>$lang,
					),true) : null; */
					
					$columns[] = $newColumn;
						
				} else if($columnName == 'gvControls') {
					$strControls = '';
					if (Yii::app()->user->getName() != 'admin' || isset($this->urlControls['viewButtonOptions'])) {
						$modcon = '';
						if (strlen($this->moduleName) > 0) {
							$modcon .= ucfirst($this->moduleName).'.';
						}
						$modcon .= ucfirst($this->controller->id).'.';
						$nameViewM = ucfirst($this->modelName);
						if ($nameViewM == 'Contacts') {
						  $nameViewM = 'Contact';
						}
						if (Yii::app()->user->checkAccess($modcon.'View', array('userid'=>Yii::app()->user->id)) ||
						    Yii::app()->user->checkAccess($modcon.'View'.$nameViewM, array('userid'=>Yii::app()->user->id))
						    ) {
						   $fNada = false;
					       if ($this->urlControls != null) {
					       	  if (isset($this->urlControls['viewButtonUrl']) && !empty($this->urlControls['viewButtonUrl'])) {
			                  //foreach($this->urlControls as $urlName => &$urlData) {
			                  //} //foreach($this->urlControls as $urlName => &$urlData)
	   		                    $fNada = true;
			                    //if (isset(Yii::app()->controller)) {
			                    //if (isset($this->controller) && isset($this->filter)) {	
			                    //   if (!$this->controller->checkPermissions($this->filter, 'view')) {
	                            //      //throw new CHttpException(403,Yii::t('app', 'Permissions Denied'));
	                            //      $fNada = false;
	                            //   }
			                    //} // if (isset($this->controller))
					       	  }
					       }
					       if ($fNada) {
						     $strControls = '{view}';
					       }
						}
						if (Yii::app()->user->checkAccess($modcon.'Update', array('userid'=>Yii::app()->user->id)) || 
						    Yii::app()->user->checkAccess($modcon.'Update'.$nameViewM, array('userid'=>Yii::app()->user->id)) 
						   ) {
						   $fNada = false;
					       if ($this->urlControls != null) {
					       	  if (isset($this->urlControls['updateButtonUrl']) && !empty($this->urlControls['updateButtonUrl'])) {
			                    $fNada = true;
			                    //if (isset($this->controller) && isset($this->filter)) {	
			                    //   if (!$this->controller->checkPermissions($this->filter, 'edit')) {
	                            //      //throw new CHttpException(403,Yii::t('app', 'Permissions Denied'));
	                            //      $fNada = false;
	                            //   }
			                    //} // if (isset($this->controller))
					       	  }
					       }
					       if ($fNada) {
						     $strControls .= '{update}';
					       }
						}
						if (Yii::app()->user->checkAccess($modcon.'Delete', array('userid'=>Yii::app()->user->id)) ||
						    Yii::app()->user->checkAccess($modcon.'Delete'.$nameViewM, array('userid'=>Yii::app()->user->id)) 
						   ) {
						   $fNada = false;
					       if ($this->urlControls != null) {
					       	  if (isset($this->urlControls['deleteButtonUrl']) && !empty($this->urlControls['deleteButtonUrl'])) {
			                    $fNada = true;
			                    //if (isset($this->controller) && isset($this->filter)) {	
			                    //   if (!$this->controller->checkPermissions($this->filter, 'delete')) {
	                            //      //throw new CHttpException(403,Yii::t('app', 'Permissions Denied'));
	                            //      $fNada = false;
	                            //   }
			                    //} // if (isset($this->controller))
					       	  }
					       }
					       if ($fNada) {
						     $strControls .= '{delete}';
					       }
						}
	
					} else {
						$strControls = '{view}{update}{delete}';
					}
					if (strlen($strControls) > 0) {
					  $newColumn['id'] = 'C_gvControls';
					  $newColumn['class'] = 'CButtonColumn';
					  $newColumn['header'] = Yii::t('app','Tools');
					  $newColumn['headerHtmlOptions'] = array('colWidth'=>$width);
					  if ($this->urlControls != null) {
			            foreach($this->urlControls as $urlName => &$urlData) {
					       $newColumn[$urlName] = $urlData;
			            } //foreach($this->urlControls as $urlName => &$urlData)
					  }
					  //if (Yii::app()->user->getName() != 'admin') {
						//echo ucfirst($this->moduleName).'.'.ucfirst($this->controller->id).'.*';
						//die();
						//$newColumn['template'] = '{view}{update}{delete}';
					  //}
					  $newColumn['template'] = $strControls;
					  $columns[] = $newColumn;
					}
					
				} else if($columnName == 'tags') {
					$newColumn['id'] = 'C_'.'tags';
					// $newColumn['class'] = 'CDataColumn';
					$newColumn['header'] = Yii::t('app','Tags');
					$newColumn['headerHtmlOptions'] = array('colWidth'=>$width);
					$newColumn['value'] = 'Tags::getTagLinks("'.$this->modelName.'",$data->id,2)';
					$newColumn['type'] = 'raw';
					$newColumn['filter'] = CHtml::textField('tagField',isset($_GET['tagField'])? $_GET['tagField'] : '');
					
					
					$columns[] = $newColumn;
				} else if ($columnName == 'gvCheckbox') {
					$newColumn['id'] = 'C_gvCheckbox';
					$newColumn['class'] = 'CCheckBoxColumn';
					$newColumn['selectableRows'] = 2;
					$newColumn['headerHtmlOptions'] = array('colWidth'=>$width);
						
					$columns[] = $newColumn;
				}
			}
			///////////////////////////////////////////////////////
			// dobavil!!!!!!!!!!
			// $this->afterAjaxUpdate = 'function(id, data) { '.$datePickerJs.' }';
			if(!empty($this->afterAjaxUpdate)) {
			  $this->afterAjaxUpdate = "var callback = ".$this->afterAjaxUpdate."; if(typeof callback == 'function') callback();";
			}
			if ($this->enableGvSettings) {		
			  $this->afterAjaxUpdate = " function(id,data) { ".$this->afterAjaxUpdate." ";
			  $this->afterAjaxUpdate.="
				$('#".$this->getId()." table').gvSettings({
					viewName:'".$this->viewName."',
					columnSelectorId:'".$this->columnSelectorId."',
					//ajaxUpdate:true
				    columnSelectorHtml:'".addcslashes($this->columnSelectorHtml,"'")."',
					ajaxUpdate:".($this->ajax?'true':'false').",
				});";
			  $this->afterAjaxUpdate .= " refreshQtip(); } ";
			}
		    ///////////////////////////////////////////////////////
		    
			$this->columns = $columns;
			//$this->columns = array();
			
			natcasesort($this->allFieldNames); // sort column names
			
			// generate column selector HTML
			$this->columnSelectorHtml = CHtml::beginForm(array('site/saveGvSettings'),'get')
				.'<ul class="column-selector" id="'.$this->columnSelectorId.'">';
			foreach($this->allFieldNames as $fieldName=>&$attributeLabel) {
				$selected = array_key_exists($fieldName,$this->gvSettings);
				$this->columnSelectorHtml .= "<li>"
				.CHtml::checkbox('columns[]',$selected,array('id'=>$fieldName.'_checkbox','value'=>$fieldName))
				.CHtml::label($attributeLabel,$fieldName.'_checkbox')
				."</li>";
			}
			$this->columnSelectorHtml .= '</ul>'.CHtml::endForm();
			// Yii::app()->clientScript->renderBodyBegin($columnHtml);
			// Yii::app()->clientScript->registerScript(__CLASS__.'#'.$this->getId().'_columnSelector',
			// "$('#".$this->getId()." table').after('".addcslashes($columnHtml,"'")."');
			
			// ",CClientScript::POS_READY);
			$themeURL = Yii::app()->theme->getBaseUrl();
	                /*
			Yii::app()->clientScript->registerScript('logos',"
			$(window).load(function(){
				if((!$('#main-menu-icon').length) || (!$('#x2touch-logo').length) || (!$('#x2crm-logo').length)){
					$('a').removeAttr('href');
					alert('Please put the logo back');
					window.location='http://www.x2engine.com';
				}
				var touchlogosrc = $('#x2touch-logo').attr('src');
				var logosrc=$('#x2crm-logo').attr('src');
				if(logosrc!='$themeURL/images/x2footer.png'|| touchlogosrc!='$themeURL/images/x2touch.png'){
					$('a').removeAttr('href');
					alert('Please put the logo back');
					window.location='http://www.x2engine.com';
				}
			});    
			");
			*/
	        if (isset($this->filter) && isset($this->filter->adminpag) && ($this->filter->adminpag > 0)) {
	        	
	        } else {
			  // add a dropdown to the summary text that let's user set how many rows to show on each page
			  $this->summaryText = Yii::t('app','<b>{start}&ndash;{end}</b> of <b>{count}</b>')
				. '<div class="form no-border" style="display:inline;"> '
				. CHtml::dropDownList('resultsPerPage', (isset($this->filter) && isset($this->filter->adminpag) && ($this->filter->adminpag>0)?$this->filter->adminpag:Profile::getResultsPerPage()), Profile::getPossibleResultsPerPage(), array(
						'ajax' => array(
							'url' => $this->controller->createUrl('/profile/setResultsPerPage'),
							'complete' => "function(response) { 
								\$.fn.yiiGridView.update('{$this->id}', {" . 
									(isset($this->modelName)? "data: {'{$this->modelName}_page': 1}," : "") . "
									complete: function(jqXHR, status) {
										refreshQtip();
									}
								});
							}",
							'data' => "js: {results: $(this).val()}",
						),
						'style' => 'margin:0 0 2px 0;',
					))
				. ' </div>'
				. Yii::t('app', ' results per page');
	        }	
	        //if (isset($this->filter->adminpag)) {
	        //  $this->filter->adminpag = 0;
	        //}
			
			// ?
			//if (isset(Yii::app()->controller->module) && Yii::app()->controller->module->id=='contacts'){
			//	// after user moves to a different page, make sure the tool tips get added to the newly showing rows
			//	$this->afterAjaxUpdate = 'js: function(id, data) { refreshQtip(); $(".qtip-hint").qtip({content:false}); }';
	        //}
	        
	        // after user moves to a different page, make sure the tool tips get added to the newly showing rows
	        //if (empty($this->afterAjaxUpdate)) {
			//  $this->afterAjaxUpdate = "js: function(id, data) { refreshQtip(); }";
	        //}
			
			parent::init();
	        //$this->ajaxUpdate = true;
	        //$this->ajaxVar = 'ajax';
			
		}

	 	public function renderItems() {
			if($this->dataProvider->getItemCount() > 0 || $this->showTableOnEmpty) {
				echo "<table class=\"{$this->itemsCssClass}\">\n";
				$this->renderTableHeader();
				ob_start();
				//echo 'nui?'.$this->dataProvider->getItemCount();
				$this->renderTableBody();
				$body = ob_get_clean();
				$this->renderTableFooter();
				echo $body; // TFOOT must appear before TBODY according to the standard.
				echo "</table>";
			}
			else $this->renderEmptyText();
		}

		public function run() { 
			$this->registerClientScript();
	
			// give this a special class so the javascript can tell it apart from the regular, lame gridviews
			if (!isset($this->htmlOptions['class'])) $this->htmlOptions['class'] = 'grid-view';
			//$this->htmlOptions['class'] .= ' x2-gridview';
			
			echo CHtml::openTag($this->tagName,$this->htmlOptions)."\n";
	
			$this->renderContent();
			$this->renderKeys();
			
			if ($this->ajax) {
		
				Yii::app()->clientScript->scriptMap['*.js'] = false;
				Yii::app()->clientScript->scriptMap['*.css'] = false;
				
				// remove JS for gridview checkboxes and delete buttons (these events use jQuery.on() and shouldn't be reapplied)
				Yii::app()->clientScript->registerScript('CButtonColumn#C_gvControls',null);
				Yii::app()->clientScript->registerScript('CCheckBoxColumn#C_gvCheckbox',null);
	
				$output = '';
				Yii::app()->getClientScript()->renderBodyEnd($output);
				echo $output;

				echo CHtml::closeTag($this->tagName);
				ob_flush();

				Yii::app()->end();
			}

			echo CHtml::closeTag($this->tagName);
		}

		public function registerClientScript() {
			parent::registerClientScript();

			if($this->enableGvSettings) {
				if(!$this->ajax) {
					Yii::app()->clientScript->registerScriptFile(Yii::app()->getBaseUrl().'/js/colResizable-1.2.x2.js');
					Yii::app()->clientScript->registerScriptFile(Yii::app()->getBaseUrl().'/js/jquery.dragtable.x2.js');
					Yii::app()->clientScript->registerScriptFile(Yii::app()->getBaseUrl().'/js/x2gridview.js');

				  Yii::app()->clientScript->registerScript(__CLASS__.'#'.$this->getId().'_gvSettings',
				    "$('#".$this->getId()." table').gvSettings({
					  viewName:'".$this->viewName."',
					  columnSelectorId:'".$this->columnSelectorId."',
					  columnSelectorHtml:'".addcslashes($this->columnSelectorHtml,"'")."',
					  ajaxUpdate:".($this->ajax?'true':'false').",
				    });",CClientScript::POS_READY);
				}
			}
		}

		public function renderTableHeader() {
			if(!$this->hideHeader) {
				echo "<colgroup>";
				foreach($this->columns as $column) {
					echo '<col width="'.$column->headerHtmlOptions['colWidth'].'">';
					// $column->id = null;
					$column->headerHtmlOptions['colWidth'] = null;
				}
				echo "</colgroup>\n";
	
				echo "<thead>\n";
	
				if($this->filterPosition===self::FILTER_POS_HEADER)
						$this->renderFilter();
	
				echo "<tr>\n";
				foreach($this->columns as $column)
						$column->renderHeaderCell();
				echo "</tr>\n";
	
				if($this->filterPosition===self::FILTER_POS_BODY)
						$this->renderFilter();
	
				echo "</thead>\n";
			}
			else if($this->filter!==null && ($this->filterPosition===self::FILTER_POS_HEADER || $this->filterPosition===self::FILTER_POS_BODY)) {
				echo "<colgroup>";
				foreach($this->columns as $column) {
					echo '<col width="'.$column->headerHtmlOptions['colWidth'].'">';
					// $column->id = null;
					$column->headerHtmlOptions['colWidth'] = null;
				}
				echo "</colgroup>\n";
			
			
				echo "<thead>\n";
				$this->renderFilter();
				echo "</thead>\n";
			}
		}
		public static function getFilterHint() {
		
			$text = Yii::t('app','<b>Tip:</b> You can use the following comparison operators with filter values to fine-tune your search.');
			$text .= '<ul class="filter-hint">';
			$text .= '<li><b>&lt;</b> '		.Yii::t('app','less than')				.'</li>';
			$text .= '<li><b>&lt;=</b> '	.Yii::t('app','less than or equal to')		.'</li>';
			$text .= '<li><b>&gt;</b> '		.Yii::t('app','greater than')			.'</li>';
			$text .= '<li><b>&gt;=</b> '	.Yii::t('app','greater than or equal to')	.'</li>';
			$text .= '<li><b>=</b> '		.Yii::t('app','equal to')					.'</li>';
			$text .= '<li><b>&lt;&gt</b> '	.Yii::t('app','not equal to')				.'</li>';
			$text .= '</ul>';
	
			return LSInfo::hint($text,false);
		}
		
		
	} 

