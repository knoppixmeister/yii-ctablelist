<?php
	Yii::import('zii.widgets.grid.*');

	class CTableList_old extends CGridView {
		public function renderPer_pager() {
			echo $this->dataProvider->getItemCount()." of ".$this->dataProvider->totalItemCount."&nbsp;";

			echo CHtml::dropDownList(
							'aaa',
							'',
							array('1' => 1, 10 => '10', 20 => '20'),
							array('id' => 'perPageChanger')
						);

			Yii::app()->clientScript->registerScript('_js_ctablelist_per_pager', "
				$('#perPageChanger').on('change', function() {
					alert('aaa');
				});
			",
			CClientScript::POS_READY);
		}
	}
