<?php
	Yii::import('zii.widgets.grid.*');

	class CTableList extends CGridView {
		public $selectableRows	=	2;
		public $template		=	'{summary}{pager}{items}{pager}';
		public $changeUrl		=	'';

		public function init() {
			//CVarDumper::dump($this->dataProvider->model->attributes, 1000, true);

			if(!is_array($this->columns) || empty($this->columns)) {
				$this->columns = array(
									array('class'	=>	'CButtonColumn'),
								);

				$this->columns = array_merge(
									array(
										array(
											'class'	=>	'CCheckBoxColumn',	
										),
									),
									array_keys($this->dataProvider->model->attributes), $this->columns);
			}

			parent::init();
		}

		public function renderSummary() {
			if(($count = $this->dataProvider->getItemCount()) <= 0) return;

			echo '<div class="'.$this->summaryCssClass.'">';
			/*
			if($this->enablePagination) {
				$pagination	=	$this->dataProvider->getPagination();
				$total 		=	$this->dataProvider->getTotalItemCount();
				$start		=	$pagination->currentPage*$pagination->pageSize+1;
				$end		=	$start+$count-1;

				if($end > $total) {
					$end	=	$total;
					$start	=	$end-$count+1;
				}

				if(($summaryText = $this->summaryText) === null) {
					$summaryText=Yii::t('zii','Displaying {start}-{end} of 1 result.|Displaying {start}-{end} of {count} results.',$total);
				}

				echo strtr($summaryText,array(
						'{start}'	=>	$start,
						'{end}'		=>	$end,
						'{count}'	=>	$total,
						'{page}'	=>	$pagination->currentPage+1,
						'{pages}'	=>	$pagination->pageCount,
				));
			}
			else {
				if(($summaryText = $this->summaryText) === null) {
					$summaryText = Yii::t('zii', 'Total 1 result.|Total {count} results.', $count);
				}

				echo strtr($summaryText,array(
						'{count}'=>$count,
						'{start}'=>1,
						'{end}'=>$count,
						'{page}'=>1,
						'{pages}'=>1,
				));
			}
			*/

			echo CHtml::dropDownList(
							'aaa',
							'',
							array('1' => 1, 10 => '10', 20 => '20'),
							array('id' => 'perPageChanger', 'class' => '_ctl_per_pager')
						);

			echo '</div>';
		}

		public function run() {
			parent::run();

			Yii::app()->clientScript->registerScript('_js_ctablelist_per_pager', "
				$('#perPageChanger').on('change', function() {
					$.ajax(
						//url:	'',
						//data:	
					);
					
					alert('CHANGE OK');
				});
			",
			CClientScript::POS_READY);

			Yii::app()->clientScript->registerCss('_css_ctablelist_per_pager', "
				table.items {
					margin-top: 10px;
				}
			");
		}
	}
