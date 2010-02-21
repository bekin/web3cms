<?php MParams::setPageLabel($me ? Yii::t('page','Edit my profile') : Yii::t('page','Edit member\'s profile')); ?>
<?php MUserFlash::setTopError(_CHtml::errorSummary($model)); ?>
<?php MUserFlash::setTopError(_CHtml::errorSummary($model->details)); ?>
<?php if(User::isAdministrator()): ?>
<?php MUserFlash::setSidebarInfo(Yii::t('hint','Required: {authRoles}.',
    array(1,'{authRoles}'=>implode(', ',array(Yii::t('t',User::ADMINISTRATOR_T))))
)); ?>
<?php endif; ?>
<?php MListOfLinks::set('sidebar',array(
    'links'=>array(
        array(
            'text'=>$me ? Yii::t('link','Show my profile') : Yii::t('link','Show member'),
            'url'=>($me && !$idIsSpecified) ? array('show') : array('show','id'=>$model->id),
            'icon'=>'person',
        ),
        array(
            'text'=>Yii::t('link','List of members'),
            'url'=>array('list'),
            'icon'=>'grip-solid-horizontal',
            'visible'=>false,
        ),
        array(
            'text'=>Yii::t('link','Grid of members'),
            'url'=>array('grid'),
            'icon'=>'calculator',
            'visible'=>User::isAdministrator(),
        ),
        array(
            'text'=>Yii::t('link','Create a new member'),
            'url'=>array('create'),
            'icon'=>'plus',
            'visible'=>User::isAdministrator(),
        ),
    ),
)); ?>
<?php $this->widget('application.components.WContentHeader',array(
    'breadcrumbs'=>array(
        array(
            'text'=>Yii::t('link','Members'),
            'url'=>array($this->id.'/'),
            'active'=>false,
        ),
        array(
            'text'=>Yii::t('link','My profile'),
            'url'=>$idIsSpecified ? array('show','id'=>$model->id) : array('show'),
            'visible'=>$me,
        ),
        array(
            'text'=>Yii::t('link','"{screenName}" member',array('{screenName}'=>$model->screenName)),
            'url'=>array('show','id'=>$model->id),
            'visible'=>!$me,
        ),
        array(
            'url'=>($me&&!$idIsSpecified) ? array($this->action->id) : array($this->action->id,'id'=>$model->id),
            'active'=>true,
        ),
    ),
)); ?>
<div class="w3-main-form-wrapper ui-widget-content ui-corner-all">

<?php echo _CHtml::beginForm('','post',array('class'=>'w3-main-form'))."\n"; ?>

<?php if(User::isAdministrator()): ?>
<div class="w3-form-row w3-first">
  <div class="w3-form-row-label"><?php echo _CHtml::activeLabelEx($model,'isActive'); ?></div>
  <div class="w3-form-row-input">
    <?php echo _CHtml::activeDropDownList($model,'isActive',$model->getAttributeData('isActive'),array('class'=>'w3-input-text ui-widget-content ui-corner-all'))."\n"; ?>
    <br/><?php echo Yii::t('hint','Required: {authRoles}.',array(1,'{authRoles}'=>implode(', ',array(Yii::t('t',User::ADMINISTRATOR_T)))))."\n"; ?>
  </div>
  <div class="clear">&nbsp;</div>
</div>
<?php $this->var->isNotW3First=true; ?>
<?php endif; ?>
<div class="w3-form-row<?php echo $this->var->isNotW3First ? '' : ' w3-first'; ?>">
  <div class="w3-form-row-label"><?php echo _CHtml::activeLabelEx($model,'email'); ?></div>
  <div class="w3-form-row-input">
    <?php echo _CHtml::activeTextField($model,'email',array('class'=>'w3-input-text ui-widget-content ui-corner-all','maxlength'=>255))."\n"; ?>
  </div>
  <div class="clear">&nbsp;</div>
</div>
<div class="w3-form-row">
  <div class="w3-form-row-label"><?php echo _CHtml::activeLabelEx($model,'screenName'); ?></div>
  <div class="w3-form-row-input">
    <?php echo _CHtml::activeTextField($model,'screenName',array('class'=>'w3-input-text ui-widget-content ui-corner-all','maxlength'=>32))."\n"; ?>
  </div>
  <div class="clear">&nbsp;</div>
</div>
<div class="w3-form-row">
  <div class="w3-form-row-label"><?php echo _CHtml::activeLabelEx($model->details,'initials'); ?></div>
  <div class="w3-form-row-input">
    <?php echo _CHtml::activeTextField($model->details,'initials',array('class'=>'w3-input-text w3-input-w50percents ui-widget-content ui-corner-all','maxlength'=>16))."\n"; ?>
  </div>
  <div class="clear">&nbsp;</div>
</div>
<?php if(User::isAdministrator()): ?>
<div class="w3-form-row">
  <div class="w3-form-row-label"><?php echo _CHtml::activeLabelEx($model,'accessType'); ?></div>
  <div class="w3-form-row-input">
    <?php echo _CHtml::activeDropDownList($model,'accessType',$model->getAttributeData('accessType'),array('class'=>'w3-input-text ui-widget-content ui-corner-all'))."\n"; ?>
    <br/><?php echo Yii::t('hint','Required: {authRoles}.',array(1,'{authRoles}'=>implode(', ',array(Yii::t('t',User::ADMINISTRATOR_T)))))."\n"; ?>
  </div>
  <div class="clear">&nbsp;</div>
</div>
<?php endif; ?>
<div class="w3-form-row">
  <div class="w3-form-row-label"><?php echo _CHtml::activeLabelEx($model,'language'); ?></div>
  <div class="w3-form-row-input">
    <?php echo _CHtml::activeDropDownList($model,'language',$model->getAttributeData('language'),array('class'=>'w3-input-text ui-widget-content ui-corner-all'))."\n"; ?>
  </div>
  <div class="clear">&nbsp;</div>
</div>
<div class="w3-form-row">
  <div class="w3-form-row-label"><?php echo _CHtml::activeLabelEx($model->details,'occupation'); ?></div>
  <div class="w3-form-row-input">
    <?php echo _CHtml::activeTextField($model->details,'occupation',array('class'=>'w3-input-text ui-widget-content ui-corner-all','maxlength'=>128))."\n"; ?>
  </div>
  <div class="clear">&nbsp;</div>
</div>
<?php if($model->hasVirtualAttribute('isEmailVisible')): ?>
<div class="w3-form-row">
  <div class="w3-form-row-label"><?php echo _CHtml::activeLabelEx($model->details,'isEmailVisible'); ?></div>
  <div class="w3-form-row-input">
    <?php echo _CHtml::activeDropDownList($model->details,'isEmailVisible',$model->details->getAttributeData('isEmailVisible'),array('class'=>'w3-input-text ui-widget-content ui-corner-all'))."\n"; ?>
  </div>
  <div class="clear">&nbsp;</div>
</div>
<?php endif; ?>
<div class="w3-form-row">
  <div class="w3-form-row-label">&nbsp;</div>
  <div class="w3-form-row-input">
    <div class="w3-form-row-text">
      <?php echo Yii::t('hint','{saveButton} or {cancelLink}',array(
          '{saveButton}'=>_CHtml::submitButton(Yii::t('link','Save'),array('class'=>'w3-input-button ui-state-default ui-corner-all')),
          '{cancelLink}'=>CHtml::link(Yii::t('link','Cancel[form]'),($me && !$idIsSpecified) ? array('show') : array('show','id'=>$model->id)),
      ))."\n"; ?>
    </div>
  </div>
  <div class="clear">&nbsp;</div>
</div>

<?php echo _CHtml::endForm(); ?>

</div><!-- w3-main-form-wrapper -->

<?php MClientScript::registerScript('focusOnFormFirstItem'); ?>
<?php MClientScript::registerScript('formButton'); ?>