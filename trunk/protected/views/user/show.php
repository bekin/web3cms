<?php MParams::setPageLabel($me ? Yii::t('page','View my profile') : Yii::t('page','View "{screenName}" member',array('{screenName}'=>$model->screenName))); ?>
<?php $this->var->links=array(); ?>
<?php if($me): ?>
<?php $this->var->links=array(
    array(
        'text'=>Yii::t('link','Edit my profile'),
        'url'=>array('update'),
        'icon'=>'pencil'
    ),
    array(
        'text'=>Yii::t('link','Change interface'),
        'url'=>array('updateInterface'),
    )
); ?>
<?php elseif(User::isAdministrator()): ?>
<?php $this->var->links=array(
    array(
        'text'=>Yii::t('link','Edit member\'s profile'),
        'url'=>array('update','id'=>$model->id),
        'icon'=>'pencil'
    ),
    array(
        'text'=>Yii::t('link','Change interface'),
        'url'=>array('updateInterface','id'=>$model->id),
    )
); ?>
<?php endif; ?>
<?php if(User::isAdministrator()): ?>
<?php /*$this->var->links=array_merge($this->var->links,array(array(
    'text'=>Yii::t('link','List of members'),
    'url'=>array('list'),
    'icon'=>'grip-solid-horizontal'
)));*/ ?>
<?php $this->var->links=array_merge($this->var->links,array(array(
    'text'=>Yii::t('link','Grid of members'),
    'url'=>array('grid'),
    'icon'=>'calculator'
))); ?>
<?php $this->var->links=array_merge($this->var->links,array(array(
    'text'=>Yii::t('link','Create a new member'),
    'url'=>array('create'),
    'icon'=>'plus'
))); ?>
<?php endif; ?>
<?php if(count($this->var->links)): ?>
<?php MListOfLinks::set('sidebar',array('links'=>$this->var->links)); ?>
<?php else: ?>
<?php MUserFlash::setSidebarInfo(Yii::t('hint','Some useful links will be added here soon.')); ?>
<?php endif; ?>
<?php $this->widget('application.components.WContentHeader',array(
    'breadcrumbs'=>array(
        array(
            'text'=>Yii::t('link','Members'),
            'url'=>array($this->id.'/'),
            'active'=>false
        ),
        array(
            'url'=>$me ?
                array($this->action->id) :
                array($this->action->id,'id'=>$model->id)
            ,
            'active'=>true
        )
    ),
)); ?>
<div class="w3-show-item-grid ui-widget-content ui-corner-all">

<?php if($me || User::isAdministrator()): ?>
<?php if(!$me): ?>
<div class="w3-grid-row<?php echo $this->var->isNotW3First ? '' : ' w3-first'; ?>">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->getAttributeLabel('isActive')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->getAttributeView('isActive')); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php $this->var->isNotW3First=true; ?>
<?php endif; ?>
<?php if($model->hasVirtualAttribute('username')): ?>
<div class="w3-grid-row<?php echo $this->var->isNotW3First ? '' : ' w3-first'; ?>">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->getAttributeLabel('username')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->username); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php $this->var->isNotW3First=true; ?>
<?php endif; ?>
<div class="w3-grid-row<?php echo $this->var->isNotW3First ? '' : ' w3-first'; ?>">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->getAttributeLabel('email')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->email); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php $this->var->isNotW3First=true; ?>
<?php elseif($model->details->isEmailVisible()): ?>
<div class="w3-grid-row<?php echo $this->var->isNotW3First ? '' : ' w3-first'; ?>">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->getAttributeLabel('email')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->email); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php $this->var->isNotW3First=true; ?>
<?php endif; ?>
<div class="w3-grid-row<?php echo $this->var->isNotW3First ? '' : ' w3-first'; ?>">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->getAttributeLabel('screenName')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->screenName); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<div class="w3-grid-row">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->details->getAttributeLabel('initials')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->details->initials); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php if($me): ?>
<div class="w3-grid-row">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->getAttributeLabel('language')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->getAttributeView('language')); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<div class="w3-grid-row">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->getAttributeLabel('User interface')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->getAttributeView('interface')); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php endif; ?>
<?php if($me || User::isAdministrator()): ?>
<div class="w3-grid-row">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->getAttributeLabel('accessType')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->getAttributeView('accessType')); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php if($model->hasVirtualAttribute('accessLevel')): ?>
<div class="w3-grid-row">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->getAttributeLabel('accessLevel')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->accessLevel); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php endif; ?>
<div class="w3-grid-row">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->details->getAttributeLabel('occupation')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->details->occupation); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php if($model->hasVirtualAttribute('isEmailConfirmed')): ?>
<div class="w3-grid-row">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->details->getAttributeLabel('isEmailConfirmed')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->details->getAttributeView('isEmailConfirmed')); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php endif; ?>
<?php if($model->hasVirtualAttribute('isEmailVisible')): ?>
<div class="w3-grid-row">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->details->getAttributeLabel('isEmailVisible')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->details->getAttributeView('isEmailVisible')); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php endif; ?>
<?php if(!empty($model->details->deactivationTime)): ?>
<div class="w3-grid-row">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->details->getAttributeLabel('deactivationTime')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode(MDate::format($model->details->deactivationTime,'full')); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php endif; ?>
<?php endif; /*end if me || admin*/ ?>
<div class="w3-grid-row">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->getAttributeLabel('createTime')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode(MDate::format($model->createTime,'full')); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php if(($me || User::isAdministrator())/* && $model->details->updateTime*/): ?>
<div class="w3-grid-row">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->details->getAttributeLabel('updateTime')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode(MDate::format($model->details->updateTime,'full')); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php endif; ?>
<?php if($model->hasVirtualAttribute('id') && ($me || User::isAdministrator())): ?>
<div class="w3-grid-row">
  <div class="w3-grid-row-label"><?php echo CHtml::encode($model->getAttributeLabel('id')); ?></div>
  <div class="w3-grid-row-value"><?php echo CHtml::encode($model->id); ?></div>
  <div class="clear">&nbsp;</div>
</div>
<?php endif; ?>

</div>