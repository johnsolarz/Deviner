<?php
Yii::app()->clientScript->registerCoreScript('jquery');
Yii::app()->clientScript->registerScriptFile($this->scriptDirectory . 'jobOperations.js', CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScriptFile($this->scriptDirectory . 'jobEdit.js', CClientScript::POS_HEAD);
Yii::app()->clientScript->registerScript('add-job', "function addLine(sender, namePrefix){
	var count = $(sender).parent().children('.jobLines').children('.jobLine').children('.part').size();" .
	"$.ajax({
		url: '".CHtml::normalizeUrl(array('job/newLine'))."'," .
		"type: 'POST'," .
		"data: {
			namePrefix: namePrefix," .
			"count: count,
		}," .
		"success: function(data){
			$(sender).before(data);" .
			"var div_id = \$(data).attr('id');" .
			"\$('#' + div_id).children('.item-select').autocomplete({
				'select': function(event, ui){
					\$.getJSON(
					'".CHtml::normalizeUrl(array('product/allowedOptions'))."'," .
					"{
						itemID: ui.item.id," .
						"namePrefix: namePrefix," .
						"count: count,
					}," .
					"function(data){
						var colors = data.colors;" .
						"var sizes = data.sizes;" .
						"var cost = data.productCost;" .
						"var colorOptions = $('<select></select>')" .
							"\n.attr('name', 'color-select')" .
							".attr('class', 'color-select');" .
						"for(var color in colors){
							colorOptions.append($('<option></option>').val(colors[color].ID).html(colors[color].TEXT));
						}" .
						"colorOptions.attr('name', \$('#' + div_id).children('.color-select').attr('name'));" .
						"\$('#' + div_id).children('.color-select').replaceWith(colorOptions);\n" .
						"\$('#' + div_id).children('.jobLine').addClass('hidden-size').children('.score_part').attr('disabled', true).val(0);" .
						"\$('#' + div_id).children('.jobLine').children('.hidden_cost').val(cost);" .
						"onGarmentCostUpdate($('#' + div_id).find('.product-cost'), cost, $('#' + div_id).find('.editable-price'), $('#' + div_id).find('.hidden-price'), $('#' + div_id).find('.garment_part'));" .
						"for(var size in sizes){
							\$('#' + div_id).children('.' + div_id + sizes[size].ID)" .
							".removeClass('hidden-size')" .
							".children('.score_part').removeAttr('disabled');
						}" .
						"\$('#' + div_id).children('.hidden-style').val(ui.item.id);
					});
				}," .
				"'source': '".CHtml::normalizeUrl(array('product/findProduct', 'response'=>'juijson'))."'
			});
		},
	});
}", CClientScript::POS_BEGIN);

Yii::app()->clientScript->registerScript('calculate-total', "" .
		"function calculateTotal(garments, front, back, sleeve, dest){
			calculateTotalMain('".CHtml::normalizeUrl(array('job/garmentCost'))."', garments, front, back, sleeve, dest);
		}",
CClientScript::POS_BEGIN);

Yii::app()->clientScript->registerScript('calculate-setup-fee', "" .
		"function calculateSetupFee(garments, front, back, sleeve, dest){
			calculateSetupFeeMain('".CHtml::normalizeUrl(array('job/setupFee'))."', garments, front, back, sleeve, dest);
		}",
CClientScript::POS_BEGIN);
?>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'job-form',
	'enableAjaxValidation'=>false,
	'htmlOptions'=>array(
		'enctype'=>'multipart/form-data',
	),
)); ?>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<div class="grid_3 alpha">
			<?php echo $form->labelEx($model, 'NAME');?>
			<?php echo $form->textField($model, 'NAME');?>
			<?php echo $form->error($model, 'NAME');?>
		</div>
		<div class="grid_3 omega">
			<?php echo $form->labelEx($model, 'formattedDueDate');?>
			<?php $this->widget('zii.widgets.jui.CJuiDatePicker', array(
				'name'=>'Job[formattedDueDate]',
				'model'=>$model,
				'attribute'=>'formattedDueDate',
				'value' => $model->formattedDueDate,
				'options'=>array(
					'showAnim'=>'fold',
					'dateFormat'=>'DD, MM d, yy',
					'defaultDate'=> $model->formattedDueDate
				),
				'htmlOptions'=>array(
						'class'=>'input_full'
				),
			));?>
			<?php echo $form->error($model, 'formattedDueDate'); ?>
		</div>
	<!-- <div class="grid_3 omega"> -->
			<?php /*echo $form->labelEx($model, 'formattedPrintDate'); */?>
			<?php /*$this->widget('zii.widgets.jui.CJuiDatePicker', array(
				'name'=>'Job[formattedPrintDate]',
				'model'=>$model,
				'attribute'=>'formattedPrintDate',
				'htmlOptions'=>array(
					'class'=>'input_full'
				),
				'options'=>array(
					'showAnim'=>'fold',
					'dateFormat'=>'DD, MM d, yy',
				),
			));*/?>
			<?php /* echo $form->error($model, 'formattedPrintDate'); */ ?>
		<!-- </div> -->

		<div class="clear"></div>

		<?php $printerList = CHtml::listData($printers, 'ID', 'FIRST');?>
		<?php $leaderList = CHtml::listData($leaders, 'ID', 'FIRST');?>

		<div class="row">
			<div class="grid_2 alpha">
				<?php echo $form->labelEx($model, 'LEADER_ID');?>
				<?php echo $form->dropDownList($model, 'LEADER_ID', $leaderList, array('class'=>'input_full', 'prompt'=>'-- Select --')); ?>
				<?php echo $form->error($model, 'LEADER_ID');?>
			</div>
			<div class="clear"></div>
		</div>
	</div>

	<div class="separator"></div>

	<?php
		$this->renderPartial('//customer/_jobForm', array(
			'customerList'=>$customerList,
			'newCustomer'=>$newCustomer,
		));
	?>

	<div class="separator"></div>
	<?php $this->renderPartial('//print/_jobForm', array(
		'model'=> $print,
		'job'=>$model,
		'fileTypes'=>$fileTypes,
		'passes'=>$passes,
	));?>
	<div class="separator"></div>

	<div id="lines" class="row">
		<?php
		$index = 0;
		foreach($lineData as $lines){
			$this->renderPartial('//jobLine/_multiForm', array(
				'namePrefix'=>CHtml::activeName($model, 'jobLines'),
				'startIndex'=>$index,
				'products'=>$lines,
				'estimate'=>CostCalculator::calculateTotal($lines['model']->garmentCount, $print->FRONT_PASS, $print->BACK_PASS, $print->SLEEVE_PASS, 0),
				'formatter'=>new Formatter,
			));
			$index += count($lines);
		}?>
		<?php echo CHtml::button('Add Garment', array(
			'onclick'=>"addLine(this, '".CHtml::activeName($model, 'jobLines')."');",
		));?>
		<?php echo CHtml::link('Create new product', "",  // the link for open the dialog
    			array(
        			'style'=>'cursor: pointer; text-decoration: underline;',
        			'onclick'=>"{addProduct(); $('#dialogProduct').dialog('open');}"
    	));?>
 		<?php
			$this->beginWidget('zii.widgets.jui.CJuiDialog', array( // the dialog
    			'id'=>'dialogProduct',
   	 			'options'=>array(
        			'title'=>'Create new product',
        			'autoOpen'=>false,
        			'modal'=>true,
        			'width'=>750,
        			'height'=>470,
    			),
			));?>
		<div class="divForForm"></div>
 		<?php $this->endWidget();?>

		<script type="text/javascript">
			function addProduct()
			{
    			<?php echo CHtml::ajax(array(
            		'url'=>array('product/create'),
            		'data'=> "js:$(this).serialize()",
            		'type'=>'post',
            		'dataType'=>'json',
            		'success'=>"function(data)
            		{
               			 if (data.status == 'failure')
                		{
                    		$('#dialogProduct div.divForForm').html(data.div);
                          	// Here is the trick: on submit-> once again this function!
                    		$('#dialogProduct div.divForForm form').submit(addProduct);
                		}
                		else
                		{
    						alert(data.attributes);
                    		$('#dialogProduct div.divForForm').html(data.div);
                    		setTimeout(\"$('#dialogProduct').dialog('close') \",3000);
                		}

            		} ",
            ))?>;
    		return false;
			}
		</script>


	</div><!-- end add garment -->

	<div class="row">
		<?php $garmentCount = $model->garmentCount;?>
		<?php echo CHtml::label('Garment Count', 'garment_qty');?>
		<?php echo CHtml::textField('garment_qty', $garmentCount, array(
			'id'=>'garment_qty',
			'readonly'=>'readonly',
			'onchange'=>"js:\$('#".CHtml::getActiveId($model, 'QUOTE')."').val($(this).val() * $('#item_total').val());",
			'onkeyup'=>"js:\$('#".CHtml::getActiveId($model, 'QUOTE')."').val($(this).val() * $('#item_total').val());"
		));?>
	</div>

	<div class="separator"></div>


	<div class="row auto_quote">
		<!-- Rush Charge Group-->
		<div class="row">
			<?php echo $form->labelEx($model,'RUSH'); ?>
			<?php echo $form->textField($model,'RUSH', array('class'=>'part')); ?>
			<?php echo $form->error($model,'RUSH'); ?>
		</div>

		<!-- Art Charge Group-->
		<div class="row">
			<?php echo CHtml::activeLabelEx($print,'COST'); ?>
			<?php echo CHtml::activeTextField($print,'COST',array('size'=>6,'maxlength'=>6, 'class'=>'part')); ?>
			<?php echo CHtml::error($print,'COST'); ?>
		</div>

		<!-- Setup Fee Group-->
		<div class="row">
			<?php echo CHtml::activeLabelEx($model,'SET_UP_FEE'); ?>
		    <?php echo CHtml::activeCheckBox($model,'SET_UP_FEE', array(
		    		'value'=>GlobalConstants::SETUP_FEE_AMOUNT_DEFAULT,
		    		'uncheckValue'=> GlobalConstants::SETUP_FEE_AMOUNT_WAIVED,
		    		'class'=>'part editable-fee',
		    		'onchange'=>"$('#setup-fee-hint').text($(this).is(':checked') ? $(this).val() : 0)"
	    		)); ?>
	    	<span id='setup-fee-hint' class='intToUsd'><?php echo GlobalConstants::SETUP_FEE_AMOUNT_WAIVED ?></span>
		    <?php echo CHtml::error($model,'SET_UP_FEE'); ?>
		</div>

		<!-- Additional Fees Group-->
		<div class='row'>
		<?php foreach($model->additionalFees as $key=>$fee){?>
			<?php echo $form->labelEx($model, 'additionalFees['.$key.']', array(
				'label'=>$fee['TEXT'],
			));?>
			<?php echo $form->textField($model, 'additionalFees['.$key.']', array(
				'value'=>$fee['VALUE'],
				'size'=>6,
				'maxlength'=>6,
				'class'=>($fee['CONSTRAINTS']['part'] !== false) ? 'part' : '',
			));?>
		<?php }?>
		</div>

	<?php /* js: hide auto quote
		<!-- Auto Quote Group-->
		<div class="grid_6 alpha">
			<h4>Auto Quote</h4>
			<div class="grid_3 alpha">
				<?php echo CHtml::label('Sub Total', 'auto_total');?>
				<?php echo CHtml::textField('auto_total', $model->total, array('readonly'=>'readonly', 'id'=>'auto_total'));?>

				<?php $taxRate = $model->additionalFees[Job::FEE_TAX_RATE]['VALUE'] / 100;
				$taxRateField = CHtml::getIdByName('Job[additionalFees]['.Job::FEE_TAX_RATE.']');?>
				<?php echo CHtml::label('Total Tax', 'auto_tax');?>
				<?php echo CHtml::textField('auto_tax', $model->total * $taxRate, array('readonly'=>'readonly', 'id'=>'auto_tax'));?>

				<?php echo CHtml::label('Grand Total', 'auto_grand');?>
				<?php echo CHtml::textField('auto_grand', $model->total * (1 + $taxRate), array('readonly'=>'readonly', 'id'=>'auto_grand'));?>
			</div>
			<div class="grid_3 omega">
				<?php echo CHtml::label('Sub Total Per Garment', 'auto_total_each');?>
				<?php echo CHtml::textField('auto_total_each', $model->garmentPrice, array('readonly'=>'readonly', 'id'=>'auto_total_each'));?>
				<?php echo CHtml::label('Total Tax Per Garment', 'auto_tax_each');?>

				<?php echo CHtml::textField('auto_tax_each', $model->garmentPrice * $taxRate, array('readonly'=>'readonly', 'id'=>'auto_tax_each'));?>
				<?php echo CHtml::label('Grand Total Per Garment', 'auto_grand_each');?>
				<?php echo CHtml::textField('auto_grand_each', $model->garmentPrice * (1 + $taxRate), array('readonly'=>'readonly', 'id'=>'auto_grand_each'));?>
			</div>
		</div>

		<!-- Quoted Group-->
		<div class="grid_4 omega">
			<h4>Quoted</h4>

			<?php echo CHtml::label('Total Per Garment', 'item_total');?>
			<?php echo CHtml::textField('item_total', ($garmentCount == 0) ? 0 : $model->QUOTE / $garmentCount, array(
				'id'=>'item_total',
				'onchange'=>"\$('#".CHtml::getActiveId($model, 'QUOTE')."').val($(this).val() * $('#garment_qty').val());",
				'onkeyup'=>"\$('#".CHtml::getActiveId($model, 'QUOTE')."').val($(this).val() * $('#garment_qty').val());"
			));?>
			<?php echo $form->labelEx($model,'QUOTE'); ?>
			<?php echo $form->textField($model,'QUOTE',array(
				'size'=>7,
				'maxlength'=>7,
				'onchange'=>"\$('#item_total').val($(this).val() / $('#garment_qty').val());",
				'onkeyup'=>"\$('#item_total').val($(this).val() / $('#garment_qty').val());"
			)); ?>
			<?php echo $form->error($model,'QUOTE'); ?>
		</div>
		<p id="qty_warning" class="note" style="display: none;">The quote estimator only supports price quotation for up to two hundred (200) garments.</p>
		*/ ?>
		<div class="clear"></div>
		<div class="separator"></div>



		<?php Yii::app()->clientScript->registerScript('auto-garment-totaler', "" .
				"$('.item_qty, .sleeve_pass, .front_pass, .back_pass').live('change keyup', function(){
					var qty = 0;" .
					"$('.item_qty').each(function(index){
						qty += (1 * $(this).val());
					});" .
					"if(qty > 200){
						$('#auto_total, #auto_total_each, #auto_tax, #auto_tax_each, #auto_grand, #auto_grand_each').val(0).attr('disabled', 'disabled');" .
						"$('#qty_warning').show();
					} else {
						$('#auto_total, #auto_total_each, #auto_tax, #auto_tax_each, #auto_grand, #auto_grand_each').removeAttr('disabled');" .
						"$('#qty_warning').hide();
					}" .
					"$('#garment_qty').val(qty).change();" .
					"updateSetupCost('".CHtml::normalizeUrl(array('job/setupFee'))."', $('.editable-fee'), $('#setup-fee-hint'), qty);
				})",
		CClientScript::POS_END);

		?>
	</div> <!-- <div class="row auto_quote">-->

	<div class="row">
		<?php echo CHtml::hiddenField('score_base', 30, array('class'=>'score_base'));?>
		<?php /*echo $form->labelEx($model, 'SCORE');?>
		<?php echo CHtml::textField('score', $model->score, array(
			'id'=>'score',
			'readonly'=>'readonly',
		));*/?>
		<?php Yii::app()->clientScript->registerScript('auto-score', "" .
				"$('.score_part, .score_pass').live('change keyup', function(){
					var base = 1 * $('.score_base').val();" .
					"var passes = 1 * $('.score_pass').val();" .
					"var qty = 0;" .
					"$('.score_part').each(function(index){
						qty += 1 * $(this).val();
					});" .
					"$('#score').val(base + (passes * qty));
				});",
		CClientScript::POS_END);?>
	</div>



	<div class="row">
		<?php echo $form->labelEx($model,'NOTES'); ?>
		<?php echo $form->textArea($model,'NOTES',array('rows'=>6, 'cols'=>50)); ?>
		<?php echo $form->error($model,'NOTES'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save', array(
			'onclick'=>"preprocessForm($(this).parent().parent()); return false;"
		)); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->
