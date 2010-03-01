<?php MParams::setPageLabel(Yii::t('page','Grid of members')); ?>
<?php MUserFlash::setSidebarInfo(Yii::t('hint','Required: {authRoles}.',
    array(2,'{authRoles}'=>implode(', ',array(Yii::t('t',User::MANAGER_T),Yii::t('t',User::ADMINISTRATOR_T))))
)); ?>
<?php MLinkList::set('sidebar',array(
    'links'=>array(
        array(
            'text'=>Yii::t('link','View as list'),
            'url'=>array('list'),
            'icon'=>'grip-solid-horizontal',
            'visible'=>false,
        ),
        array(
            'text'=>Yii::t('link','Add a member'),
            'url'=>array('create'),
            'icon'=>'plus',
            'visible'=>User::isAdministrator(),
        ),
    ),
)); ?>
<?php $this->widget('application.components.WContentHeader',array(
    'afterLabel'=>false,
    'breadcrumbs'=>array(
        array(
            'url'=>array($this->action->id),
            'active'=>true,
        ),
    ),
)); ?>
<?php $this->widget('application.components.WPreItemActionBar',array(
    'links'=>array(
        array(
            'dropDown'=>array('links'=>$allAccessType),
            'text'=>CHtml::encode($accessTypeLinkText),
            'options'=>array('title'=>Yii::t('t','Access type'))
        ),
        array(
            'dropDown'=>array('links'=>$allState),
            'text'=>CHtml::encode($stateLinkText),
            'options'=>array('title'=>Yii::t('t','State[member]'))
        ),
    ),
)); ?>
<?php $this->widget('application.components.WGrid',array(
    'columns'=>array(
        array('title'=>CHtml::encode(User::model()->getAttributeLabel('screenName'))),
        array('title'=>CHtml::encode(UserDetails::model()->getAttributeLabel('occupation'))),
        array('title'=>CHtml::encode(User::model()->getAttributeLabel('email'))),
        array('title'=>CHtml::encode(User::model()->getAttributeLabel('Registered'))),
        array('title'=>CHtml::encode(UserDetails::model()->getAttributeLabel('Deact'))),
        array('title'=>CHtml::encode(User::model()->getAttributeLabel('accessType'))),
        array('title'=>CHtml::encode(Yii::t('t','Actions'))),
    ),
    'columnsModel'=>array(
        array('name'=>'screenName','width'=>150),
        array('name'=>'occupation','width'=>120),
        array('name'=>'email','width'=>150),
        array('name'=>'createTime','width'=>70,'align'=>'right'),
        array('name'=>'deactivationTime','width'=>70,'align'=>'right'),
        array('name'=>'accessType','width'=>79),
        array('name'=>'actions','width'=>59,'sortable'=>false),
    ),
    'pages'=>$pages,
    'rowNum'=>UserController::GRID_PAGE_SIZE,
    'rows'=>$gridRows,
    'sColumns'=>array(
        array('title'=>$sort->link(User::model()->tableName().'.screenName')),
        array('title'=>$sort->link('User_UserDetails.occupation',$sort->resolveLabel('details.occupation'))),
        array('title'=>$sort->link(User::model()->tableName().'.email')),
        array('title'=>$sort->link(User::model()->tableName().'.createTime',$sort->resolveLabel('Registered'))),
        array('title'=>$sort->link('User_UserDetails.deactivationTime',$sort->resolveLabel('details.Deact'))),
        array('title'=>$sort->link(User::model()->tableName().'.accessLevel',$sort->resolveLabel('accessType'))),
        array('title'=>Yii::t('t','Actions')),
    ),
    'sortname'=>'screenName',
    'sortorder'=>'asc',
    'url'=>Yii::app()->createUrl($this->id.'/gridData',$_GET),
)); ?>