<?php

class UserController extends _CController
{
    /**
     * @var string specifies the default action to be 'grid'.
     */
    public $defaultAction='grid';

    /**
     * @var CActiveRecord the currently loaded data model instance.
     */
    private $_model;

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
            //array('deny', // deny authenticated user to perform 'login' actions
                //'actions'=>array('login'),
                //'users'=>array('@'),
            //),
            array('allow', // allow all users to perform 'captcha', 'confirmEmail', 'grid', 'gridData', 'list', 'login', 'logout', 'register' and 'show' actions
                'actions'=>array('captcha','confirmEmail','grid','gridData','list','login','logout','register','show'),
                'users'=>array('*'),
            ),
            array('allow', // allow authenticated user to perform 'create', 'update' and 'updateInterface' actions
                'actions'=>array('create','update','updateInterface'),
                'users'=>array('@'),
            ),
            array('deny',  // deny all users
                'users'=>array('*'),
            ),
        );
    }

    /**
     * Declares class-based actions.
     */
    public function actions()
    {
        return array(
            // captcha action renders the CAPTCHA image
            // this is used by the register page
            'captcha'=>array(
                'class'=>'CCaptchaAction',
                'backColor'=>0xFFFFFF,
            ),
        );
    }

    /**
     * Confirm email address.
     */
    public function actionConfirmEmail()
    {
        $done=false;
        // use power of models
        $model=new User;
        // collect user input data
        if(isset($_POST['User']))
            // collect user input data
            $model->attributes=$_POST['User'];
        else
        {
            // parse url parameters (from the link in the 'welcome' email)
            isset($_GET['email']) && ($model->email=$_GET['email']);
            isset($_GET['key']) && ($model->emailConfirmationKey=$_GET['key']);
        }
        // attempt to confirm email
        if((isset($_POST['User']) && $model->validate('confirmEmail')) || (!isset($_POST['User']) && isset($_GET['email'],$_GET['key']) && $model->validate('confirmEmailUrl')))
        {
            // find user by email
            if(($user=User::model()->with('details')->findByAttributes(array('email'=>$model->email)))!==null)
            {
                if(is_object($user->details))
                {
                    if($user->details->isEmailConfirmed==='1')
                        // was confirmed earlier
                        MUserFlash::setTopInfo(Yii::t('hint',
                            'Email address {email} was confirmed earlier.',
                            array('{email}'=>'<strong>'.$user->email.'</strong>')
                        ));
                    else
                    {
                        if($user->details->emailConfirmationKey!==$model->emailConfirmationKey)
                            // wrong key
                            MUserFlash::setTopError(Yii::t('hint',
                                'We are sorry, but email address {email} has a different confirmation key. You provided: {emailConfirmationKey}.',
                                array(
                                    '{email}'=>'<strong>'.$user->email.'</strong>',
                                    '{emailConfirmationKey}'=>'<strong>'.$model->emailConfirmationKey.'</strong>',
                                )
                            ));
                        else
                        {
                            // confirm email
                            if($user->details->saveAttributes(array('isEmailConfirmed'=>'1')))
                            {
                                // set success message
                                MUserFlash::setTopSuccess(Yii::t('hint',
                                    'Email address {email} has been successfully confirmed.',
                                    array('{email}'=>'<strong>'.$user->email.'</strong>')
                                ));
                                // renew key in db
                                $user->details->saveAttributes(array('emailConfirmationKey'=>md5(uniqid(rand(),true))));
                                // clear form values
                                $model=new User;
                                // variable for view
                                $done=true;
                            }
                            else
                            {
                                // set error message
                                MUserFlash::setTopError(Yii::t('hint',
                                    'Error! Email address {email} could not be confirmed.',
                                    array('{email}'=>'<strong>'.$user->email.'</strong>')
                                ));
                                Yii::log(W3::t('system',
                                    'Could not save attributes of the {model} model. Model ID: {modelId}. Method called: {method}.',
                                    array('{model}'=>get_class($user->details),'{modelId}'=>$user->details->userId,'{method}'=>__METHOD__.'()')
                                ),'error','w3');
                            }
                        }
                    }
                }
                else
                {
                    // hmmm, user details does not exists
                    MUserFlash::setTopError(Yii::t('hint','System failure! Please accept our apologies...'));
                    Yii::log(W3::t('system',
                        'Member with ID {userId} has no UserDetails record associated. Method called: {method}.',
                        array(
                            '{userId}'=>$user->id,
                            '{method}'=>__METHOD__.'()'
                        )
                    ),'error','w3');
                }
            }
            else
            {
                // email is not registered?
                MUserFlash::setTopInfo(Yii::t('hint',
                    'A member account with email address {email} could not be found.',
                    array('{email}'=>'<strong>'.$model->email.'</strong>')
                ));
                // pay visitor attention to the 'email' field
                $model->addError('email','');
            }
        }
        // display the confirm email form
        $this->render('confirmEmail',array('model'=>$model,'done'=>$done));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'show' page.
     */
    public function actionCreate()
    {
        if(!User::isAdministrator())
        {
            // not enough rights
            MUserFlash::setTopError(Yii::t('hint','We are sorry, but you don\'t have enough rights to create a new member.'));
            $this->redirect($this->getGotoUrl());
        }
        $model=new User;
        if(isset($_POST['User']))
        {
            // collect user input data
            $model->attributes=$_POST['User'];
            // email and username are not in safeAttributes
            if(isset($_POST['User']['email']))
                $model->email=$_POST['User']['email'];
            if(isset($_POST['User']['username']))
                $model->username=$_POST['User']['username'];
            // instantiate a new user details object
            $model->details=new UserDetails(array(
                'emailConfirmationKey'=>md5(uniqid(rand(),true)),
            ));
            if(isset($_POST['UserDetails']))
                $model->details->attributes=$_POST['UserDetails'];
            // validate with $on = 'create' and save without validation
            if(($validated=$model->validate($this->action->id))!==false && ($saved=$model->save(false))!==false)
            {
                // save user details record
                $model->details->userId=$model->id;
                if($model->details->save()===false)
                    // hmmm, what could be the problem?
                    Yii::log(W3::t('system',
                        'Failed creating UserDetails record. Member ID: {userId}. Method called: {method}.',
                        array(
                            '{userId}'=>$model->id,
                            '{method}'=>__METHOD__.'()'
                        )
                    ),'error','w3');
                // set success message
                MUserFlash::setTopSuccess(Yii::t('hint',
                    'The new "{screenName}" member record has been successfully created.',
                    array('{screenName}'=>'<strong>'.$model->screenName.'</strong>')
                ));
                // go to the 'show' page
                $this->redirect(array('show','id'=>$model->id));
            }
        }
        else
        {
            // pre-assigned attributes (default values for a new record)
            $model->interface=MParams::getInterface();
            $model->language=MParams::getLanguage();
            $model->screenNameSame=true;
        }
        if(!isset($model->details))
            // new associated user details
            $model->details=new UserDetails;
        // display the create form
        $this->render('create',array('model'=>$model));
    }

    /**
     * Displays the login page
     */
    public function actionLogin()
    {
        $form=new UserLoginForm;
        // collect user input data
        if(isset($_POST['UserLoginForm']))
        {
            $form->attributes=$_POST['UserLoginForm'];
            if(isset($_POST['UserLoginForm']['loginWithField']))
                // if user is logging with email, but param changed to username,
                // we should try to log him in with email.
                // if login attempt is unsuccessful, he will have to try again with username
                UserLoginForm::$loginWithField=$_POST['UserLoginForm']['loginWithField'];
            // validate user input and redirect to return page if valid
            if($form->validate())
            {
                // set the welcome message
                MUserFlash::setTopSuccess(Yii::t('hint',
                    '{screenName}, you have been successfully logged in.',
                    array('{screenName}'=>'<strong>'.Yii::app()->user->screenName.'</strong>')
                ));
                // user was just authenticated, but let's check anyway
                if(!Yii::app()->user->isGuest)
                {
                    // update user stats
                    if(($userDetails=UserDetails::model()->findByPk(Yii::app()->user->id))!==null)
                        $userDetails->saveAttributes(array(
                            'lastLoginTime'=>time(),
                            'lastVisitTime'=>time(),
                            'totalTimeLoggedIn'=>$userDetails->totalTimeLoggedIn+60
                        ));
                    else
                        // hmmm, user details does not exists
                        Yii::log(W3::t('system',
                            'Member with ID {userId} has no UserDetails record associated. Method called: {method}.',
                            array(
                                '{userId}'=>Yii::app()->user->id,
                                '{method}'=>__METHOD__.'()'
                            )
                        ),'error','w3');
                }
                $this->redirect($this->getGotoUrl());
            }
        }
        if(!Yii::app()->user->isGuest)
            // warn user if already logged in
            MUserFlash::setTopInfo(Yii::t('hint',
                '{screenName}, this action will log you out from your current account.',
                array('{screenName}'=>'<strong>'.Yii::app()->user->screenName.'</strong>')
            ));
        // display the login form
        $this->render('login',array('form'=>$form));
    }

    /**
     * Logout the current user and redirect to homepage.
     */
    public function actionLogout()
    {
        $isLoggedIn=!Yii::app()->user->isGuest;
        $screenName=$isLoggedIn ? Yii::app()->user->screenName : '';
        // log user out and destroy all session data
        // if you want to keep the session alive, then use Yii::app()->user->logout(false) instead
        Yii::app()->user->logout();
        // if user was logged in, we should notify about logout
        if($isLoggedIn)
        {
            if(!Yii::app()->getSession()->getIsStarted())
                // if session is destroyed, we need to re-open it. this is necessary for user flash
                Yii::app()->getSession()->open();
            // set the goodbye message
            MUserFlash::setTopInfo(Yii::t('hint',
                '{screenName}, you have been successfully logged out.',
                array('{screenName}'=>'<strong>'.$screenName.'</strong>')
            ));
        }
        $this->redirect($this->getGotoUrl());
    }

    /**
     * Register a new member account.
     * If creation is successful, the browser will be redirected to the 'login' page.
     */
    public function actionRegister()
    {
        $model=new User;
        // collect user input data
        if(isset($_POST['User']))
        {
            // collect user input data
            $model->attributes=$_POST['User'];
            // email and username are not in safeAttributes
            if(isset($_POST['User']['email']))
                $model->email=$_POST['User']['email'];
            if(isset($_POST['User']['username']))
                $model->username=$_POST['User']['username'];
            // instantiate a new user details object
            $model->details=new UserDetails(array(
                'emailConfirmationKey'=>md5(uniqid(rand(),true)),
            ));
            if(isset($_POST['UserDetails']))
                $model->details->attributes=$_POST['UserDetails'];
            // validate with $on = 'register'
            if($model->validate('register'))
            {
                // if user is logged in
                if(!Yii::app()->user->isGuest)
                {
                    // if you place this code before validate() then verifyCode will be invalid
                    // log user out from the current account
                    Yii::app()->user->logout();
                    if(!Yii::app()->getSession()->getIsStarted())
                        // restore http session. this is necessary for user flash messages
                        Yii::app()->getSession()->open();
                }
                // create user record (without validation)
                if($model->save(false))
                {
                    // save user details record
                    $model->details->userId=$model->id;
                    if($model->details->save()===false)
                        // hmmm, what could be the problem?
                        Yii::log(W3::t('system',
                            'Failed creating UserDetails record. Member ID: {userId}. Method called: {method}.',
                            array(
                                '{userId}'=>$model->id,
                                '{method}'=>__METHOD__.'()'
                            )
                        ),'error','w3');
                    // set success message
                    MUserFlash::setTopSuccess(Yii::t('hint',
                        '{screenName}, your member account has been successfully created.',
                        array('{screenName}'=>'<strong>'.$model->screenName.'</strong>')
                    ));
                    // send welcome email
                    $headers="From: ".MParams::getAdminEmailAddress()."\r\nReply-To: ".MParams::getAdminEmailAddress();
                    $content=Yii::t('email',
                        'Content(New member account)',
                        array(
                            '{siteTitle}'=>MParams::getSiteTitle(),
                            '{screenName}'=>$model->screenName,
                            '{emailConfirmationKey}'=>$model->details->emailConfirmationKey,
                            '{emailConfirmationLink}'=>Yii::app()->createAbsoluteUrl($this->id.'/confirmEmail',array('email'=>$model->email,'key'=>$model->details->emailConfirmationKey)),
                        )
                    );
                    @mail($model->email,Yii::t('email','New member account'),$content,$headers);
                    // go to login page
                    $this->redirect($this->getGotoUrl());
                }
            }
        }
        else
        {
            // pre-assigned attributes (default values for a new record)
            $model->screenNameSame=true;
            $model->language=MParams::getLanguage();
            $model->interface=MParams::getInterface();
        }
        if(!Yii::app()->user->isGuest)
            // warn user if already logged in
            MUserFlash::setTopInfo(Yii::t('hint',
                '{screenName}, this action will log you out from your current account.',
                array('{screenName}'=>'<strong>'.Yii::app()->user->screenName.'</strong>')
            ));
        if(!isset($model->details))
            // new associated user details
            $model->details=new UserDetails;
        // render the view file
        $this->render('register',array('model'=>$model));
    }

    /**
     * Shows a particular model.
     */
    public function actionShow()
    {
        $me=(isset($_GET['id']) && (Yii::app()->user->isGuest || $_GET['id']!==Yii::app()->user->id)) ? false : true;
        $id=$me ? Yii::app()->user->id : $_GET['id'];
        $model=$this->loadUser(array('id'=>$id,'with'=>array('details')));
        // loaded user is me?
        $myModel=!Yii::app()->user->isGuest && Yii::app()->user->id===$model->id;
        if(!$myModel && !User::isManager() && !User::isAdministrator())
        {
            // not enough rights
            MUserFlash::setTopError(Yii::t('hint','We are sorry, but you don\'t have enough rights to browse members.'));
            $this->redirect($this->getGotoUrl());
        }
        // render the view file
        $this->render('show',array('model'=>$model,'me'=>$me));
    }

    /**
     * Updates a particular model.
     * Accessible only to authenticated users and admin.
     * If update is successful, the browser will be redirected to the 'show' page.
     */
    public function actionUpdate()
    {
        /*// if not admin
        if(isset($_GET['id']) && !User::isAdministrator())
            // redirect from user/update/id/2 to user/update
            $this->redirect(array($this->action->id));*/
        $idIsSpecified=isset($_GET['id']);
        // whether it's me. alternative: admin update member's account.
        $me=($idIsSpecified && $_GET['id']!==Yii::app()->user->id) ? false : true;
        $id=$me ? Yii::app()->user->id : $_GET['id'];
        // load model. if model doesn't exist, throw an http exception
        $model=$this->loadUser(array('id'=>$id,'with'=>array('details')));
        // loaded user is me?
        $myModel=!Yii::app()->user->isGuest && Yii::app()->user->id===$model->id;
        if(!$myModel && !User::isAdministrator())
        {
            // not enough rights
            MUserFlash::setTopError(Yii::t('hint','We are sorry, but you don\'t have enough rights to edit a member account.'));
            $this->redirect($this->getGotoUrl());
        }
        // whether data is passed
        if(isset($_POST['User']))
        {
            // collect user input data
            $model->attributes=$_POST['User'];
            // email is assigned in {@link User::beforeValidate}
            // validate with $on = 'update' and save without validation
            if(($validated=$model->validate($this->action->id))!==false && ($saved=$model->save(false))!==false)
            {
                // update variables first defined in {@link _CUserIdentity} class
                if($me)
                {
                    // update user states in the session for {@link _CController::init}
                    Yii::app()->user->setState('language',$model->language);
                    // update user screenName, so we continue calling visitor right, 
                    Yii::app()->user->setState('screenName',$model->screenName);
                    // set user preferred language
                    if(!empty($model->language))
                        W3::setLanguage($model->language);
                    // we do not need to update user cookie any more because
                    // we overrode auto-login with {@link _CWebUser::restoreFromCookie}
                }
                // user details
                $details=array();
                if($model->isActive===User::IS_ACTIVE && $model->details->deactivationTime!==null)
                    $details['deactivationTime']=null;
                else if(($model->isActive===User::IS_NOT_ACTIVE || $model->isActive===null) && empty($model->details->deactivationTime))
                    $details['deactivationTime']=time();
                if(isset($_POST['UserDetails']) || count($details)>=1)
                {
                    if(isset($_POST['UserDetails']))
                        // collect user input data
                        $model->details->attributes=$_POST['UserDetails'];
                    foreach($details as $attribute=>$value)
                        // set attributes outside of the form
                        $model->details->$attribute=$value;
                    // validate with $on = 'update'
                    if(($validated=$model->details->validate($this->action->id))!==false)
                    {
                        if(($saved=$model->details->save())!==false)
                        {
                            // set success message
                            MUserFlash::setTopSuccess(Yii::t('hint',
                                $me ?
                                    '{screenName}, your profile has been updated.' :
                                    'The member account "{screenName}" has been updated.'
                                ,
                                array('{screenName}'=>'<strong>'.$model->screenName.'</strong>')
                            ));
                            // go to 'show' page
                            $this->redirect($me ? array('show') : array('show','id'=>$model->id));
                        }
                        else
                        {
                            // set error message
                            MUserFlash::setTopError(Yii::t('hint',
                                $me ?
                                    'Error! {screenName}, your profile could not be updated.' :
                                    'Error! The member account "{screenName}" could not be updated.'
                                ,
                                array('{screenName}'=>'<strong>'.$model->screenName.'</strong>')
                            ));
                            Yii::log(W3::t('system',
                                'Could not save attributes of the {model} model. Model ID: {modelId}. Method called: {method}.',
                                array('{model}'=>get_class($model->details),'{modelId}'=>$model->details->userId,'{method}'=>__METHOD__.'()')
                            ),'error','w3');
                        }
                    }
                }
            }
            else if($validated && !$saved)
            {
                // set error message
                MUserFlash::setTopError(Yii::t('hint',
                    $me ?
                        'Error! {screenName}, your profile could not be updated.' :
                        'Error! The member account "{screenName}" could not be updated.'
                    ,
                    array('{screenName}'=>'<strong>'.$model->screenName.'</strong>')
                ));
                Yii::log(W3::t('system',
                    'Could not save attributes of the {model} model. Model ID: {modelId}. Method called: {method}.',
                    array('{model}'=>get_class($model),'{modelId}'=>$model->id,'{method}'=>__METHOD__.'()')
                ),'error','w3');
            }
        }
        // display the update form
        $this->render('update',array('model'=>$model,'me'=>$me,'idIsSpecified'=>$idIsSpecified));
    }

    /**
     * Update user interface.
     * Accessible only to authenticated users and admin.
     * If update is successful, the browser will be redirected to the 'show' page.
     */
    public function actionUpdateInterface()
    {
        /*// if not admin
        if(isset($_GET['id']) && !User::isAdministrator())
            // redirect from user/updateInterface/id/2 to user/updateInterface
            $this->redirect(array($this->action->id));*/
        $idIsSpecified=isset($_GET['id']);
        // whether it's me. alternative: admin update member's account.
        $me=($idIsSpecified && $_GET['id']!==Yii::app()->user->id) ? false : true;
        $id=$me ? Yii::app()->user->id : $_GET['id'];
        // load model. if model doesn't exist, throw an http exception
        $model=$this->loadUser(array('id'=>$id,'with'=>array('details')));
        // loaded user is me?
        $myModel=!Yii::app()->user->isGuest && Yii::app()->user->id===$model->id;
        if(!$myModel && !User::isAdministrator())
        {
            // not enough rights
            MUserFlash::setTopError(Yii::t('hint','We are sorry, but you don\'t have enough rights to change the user interface for a member account.'));
            $this->redirect($this->getGotoUrl());
        }
        // whether data is passed
        if(isset($_POST['User']))
        {
            // collect user input data
            $model->attributes=$_POST['User'];
            // validate with $on = 'updateInterface' and save without validation
            if(($validated=$model->validate($this->action->id))!==false && ($saved=$model->save(false))!==false)
            {
                // take care of updateTime (this is not critical)
                $model->details->saveAttributes(array('updateTime'=>time()));
                // update variables first defined in {@link _CUserIdentity} class
                if($me)
                {
                    // update user states in the session for {@link _CController::init}
                    Yii::app()->user->setState('interface',$model->interface);
                    // set user preferred interface
                    if(!empty($model->interface))
                        W3::setInterface($model->interface);
                    // we do not need to update user cookie any more because
                    // we overrode auto-login with {@link _CWebUser::restoreFromCookie}
                }
                // set success message
                MUserFlash::setTopSuccess(Yii::t('hint',
                    $me ?
                        '{screenName}, new user interface has been applied.' :
                        'The user interface for member account "{screenName}" has been updated.'
                    ,
                    array('{screenName}'=>'<strong>'.$model->screenName.'</strong>')
                ));
                // go to 'show' page
                $this->redirect($me ? array('show') : array('show','id'=>$model->id));
            }
            else if($validated && !$saved)
            {
                // set error message
                MUserFlash::setTopError(Yii::t('hint',
                    $me ?
                        'Error! {screenName}, new user interface could not be applied.' :
                        'Error! The user interface for member account "{screenName}" could not be updated.'
                    ,
                    array('{screenName}'=>'<strong>'.$model->screenName.'</strong>')
                ));
                Yii::log(W3::t('system',
                    'Could not save attributes of the {model} model. Model ID: {modelId}. Method called: {method}.',
                    array('{model}'=>get_class($model),'{modelId}'=>$model->id,'{method}'=>__METHOD__.'()')
                ),'error','w3');
            }
        }
        // display the update form
        $this->render('updateInterface',array('model'=>$model,'me'=>$me,'idIsSpecified'=>$idIsSpecified));
    }

    /**
     * Lists all models.
     */
    public function actionList()
    {
        $criteria=new CDbCriteria;
        $criteria->order="`".User::model()->tableName()."`.`screenName` ASC";

        $pages=new CPagination(User::model()->count($criteria));
        $pages->pageSize=self::LIST_PAGE_SIZE;
        $pages->applyLimit($criteria);

        $models=User::model()->with('details')->findAll($criteria);

        $this->render('list',array(
            'models'=>$models,
            'pages'=>$pages,
        ));
    }

    /**
     * Grid of all models.
     */
    public function actionGrid()
    {
        if(!User::isManager() && !User::isAdministrator())
        {
            // not enough rights
            MUserFlash::setTopError(Yii::t('hint','We are sorry, but you don\'t have enough rights to browse members.'));
            $this->redirect($this->getGotoUrl());
        }

        // specify filter parameters
        $accessType=isset($_GET['accessType']) ? $_GET['accessType'] : null;
        if($accessType!=='all' && $accessType!==(string)User::MEMBER && $accessType!==(string)User::CLIENT && $accessType!==(string)User::CONSULTANT && $accessType!==(string)User::MANAGER && $accessType!==(string)User::ADMINISTRATOR)
            $accessType='all';
        $state=isset($_GET['state']) ? $_GET['state'] : null;
        if($state!=='all' && $state!=='active' && $state!=='inactive')
            $state='all';

        // criteria
        $criteria=new CDbCriteria;
        if($accessType===(string)User::MEMBER)
        {
            $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."`".User::model()->tableName()."`.`accessType`=:member";
            $criteria->params=array_merge($criteria->params,array(':member'=>User::MEMBER));
        }
        else if($accessType===(string)User::CLIENT)
        {
            $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."`".User::model()->tableName()."`.`accessType`=:client";
            $criteria->params=array_merge($criteria->params,array(':client'=>User::CLIENT));
        }
        else if($accessType===(string)User::CONSULTANT)
        {
            $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."`".User::model()->tableName()."`.`accessType`=:consultant";
            $criteria->params=array_merge($criteria->params,array(':consultant'=>User::CONSULTANT));
        }
        else if($accessType===(string)User::MANAGER)
        {
            $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."`".User::model()->tableName()."`.`accessType`=:manager";
            $criteria->params=array_merge($criteria->params,array(':manager'=>User::MANAGER));
        }
        else if($accessType===(string)User::ADMINISTRATOR)
        {
            $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."`".User::model()->tableName()."`.`accessType`=:administrator";
            $criteria->params=array_merge($criteria->params,array(':administrator'=>User::ADMINISTRATOR));
        }
        if($state==='active')
        {
            $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."(`".User::model()->tableName()."`.`isActive` IS NULL OR `".User::model()->tableName()."`.`isActive`!=:isNotActive)";
            $criteria->params=array_merge($criteria->params,array(':isNotActive'=>User::IS_NOT_ACTIVE));
        }
        else if($state==='inactive')
        {
            $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."`".User::model()->tableName()."`.`isActive`=:isNotActive";
            $criteria->params=array_merge($criteria->params,array(':isNotActive'=>User::IS_NOT_ACTIVE));
        }

        // pagination
        $with=array();
        if(strpos($criteria->condition,'UserUserDetails')!==false)
            $with[]='details';
        if(count($with)>=1)
            $pages=new CPagination(User::model()->with($with)->count($criteria));
        else
            $pages=new CPagination(User::model()->count($criteria));
        $pages->pageSize=self::GRID_PAGE_SIZE;
        $pages->applyLimit($criteria);

        // sort
        $sort=new CSort('User');
        $sort->attributes=array(
            User::model()->tableName().'.accessLevel'=>'accessLevel',
            User::model()->tableName().'.email'=>'email',
            User::model()->tableName().'.screenName'=>'screenName',
            'UserUserDetails.deactivationTime'=>'deactivationTime',
            'UserUserDetails.occupation'=>'occupation',
        );
        $sort->defaultOrder="`".User::model()->tableName()."`.`screenName` ASC";
        $sort->applyOrder($criteria);

        // find all
        $models=User::model()->with('details')->findAll($criteria);

        // filters data
        $filters=array('accessType'=>$accessType,'state'=>$state);
        $allAccessType=array(
            array(
                'text'=>Yii::t('t','All'),
                'url'=>Yii::app()->createUrl($this->id.'/'.$this->action->id,array_merge($filters,array('accessType'=>'all'))),
                'active'=>$accessType==='all'
            ),
            array(
                'text'=>Yii::t('t',User::MEMBER_T),
                'url'=>Yii::app()->createUrl($this->id.'/'.$this->action->id,array_merge($filters,array('accessType'=>User::MEMBER))),
                'active'=>$accessType===(string)User::MEMBER
            ),
            array(
                'text'=>Yii::t('t',User::CLIENT_T),
                'url'=>Yii::app()->createUrl($this->id.'/'.$this->action->id,array_merge($filters,array('accessType'=>User::CLIENT))),
                'active'=>$accessType===(string)User::CLIENT
            ),
            array(
                'text'=>Yii::t('t',User::CONSULTANT_T),
                'url'=>Yii::app()->createUrl($this->id.'/'.$this->action->id,array_merge($filters,array('accessType'=>User::CONSULTANT))),
                'active'=>$accessType===(string)User::CONSULTANT
            ),
            array(
                'text'=>Yii::t('t',User::MANAGER_T),
                'url'=>Yii::app()->createUrl($this->id.'/'.$this->action->id,array_merge($filters,array('accessType'=>User::MANAGER))),
                'active'=>$accessType===(string)User::MANAGER
            ),
            array(
                'text'=>Yii::t('t',User::ADMINISTRATOR_T),
                'url'=>Yii::app()->createUrl($this->id.'/'.$this->action->id,array_merge($filters,array('accessType'=>User::ADMINISTRATOR))),
                'active'=>$accessType===(string)User::ADMINISTRATOR
            ),
        );
        switch($accessType)
        {
            case 'all':
                $accessTypeLinkText=Yii::t('t','All');
                break;
            case (string)User::MEMBER:
                $accessTypeLinkText=Yii::t('t',User::MEMBER_T);
                break;
            case (string)User::CLIENT:
                $accessTypeLinkText=Yii::t('t',User::CLIENT_T);
                break;
            case (string)User::CONSULTANT:
                $accessTypeLinkText=Yii::t('t',User::CONSULTANT_T);
                break;
            case (string)User::MANAGER:
                $accessTypeLinkText=Yii::t('t',User::MANAGER_T);
                break;
            case (string)User::ADMINISTRATOR:
                $accessTypeLinkText=Yii::t('t',User::ADMINISTRATOR_T);
                break;
            default:
                $accessTypeLinkText='&nbsp;';
        }
        $allState=array(
            array(
                'text'=>Yii::t('t','All'),
                'url'=>Yii::app()->createUrl($this->id.'/'.$this->action->id,array_merge($filters,array('state'=>'all'))),
                'active'=>$state==='all'
            ),
            array(
                'text'=>Yii::t('t','Active[members]'),
                'url'=>Yii::app()->createUrl($this->id.'/'.$this->action->id,array_merge($filters,array('state'=>'active'))),
                'active'=>$state==='active'
            ),
            array(
                'text'=>Yii::t('t','Inactive[members]'),
                'url'=>Yii::app()->createUrl($this->id.'/'.$this->action->id,array_merge($filters,array('state'=>'inactive'))),
                'active'=>$state==='inactive'
            ),
        );
        switch($state)
        {
            case 'all':
                $stateLinkText=Yii::t('t','All');
                break;
            case 'active':
                $stateLinkText=Yii::t('t','Active[members]');
                break;
            case 'inactive':
                $stateLinkText=Yii::t('t','Inactive[members]');
                break;
            default:
                $stateLinkText='&nbsp;';
        }

        // rows for the static grid
        $gridRows=array();
        foreach($models as $model)
        {
            $gridRows[]=array(
                array(
                    'content'=>CHtml::encode($model->screenName),
                ),
                array(
                    'content'=>CHtml::encode($model->details->occupation),
                ),
                array(
                    'content'=>CHtml::encode($model->email),
                ),
                array(
                    'align'=>'right',
                    'content'=>CHtml::encode(MDate::format($model->createTime,'medium',null)),
                    'title'=>CHtml::encode(MDate::format($model->createTime,'full')),
                ),
                array(
                    'align'=>'right',
                    'content'=>CHtml::encode(MDate::format($model->details->deactivationTime,'medium',null)),
                    'title'=>CHtml::encode(MDate::format($model->details->deactivationTime,'full')),
                ),
                array(
                    'content'=>CHtml::encode($model->getAttributeView('accessType')),
                ),
                array(
                    'content'=>
                        CHtml::link('<span class="ui-icon ui-icon-zoomin"></span>',array('show','id'=>$model->id),array(
                            'class'=>'w3-ig w3-link-icon w3-border-1px-transparent w3-first ui-corner-all',
                            'title'=>Yii::t('link','Show')
                        )).
                        CHtml::link('<span class="ui-icon ui-icon-pencil"></span>',array('update','id'=>$model->id),array(
                            'class'=>'w3-ig w3-link-icon w3-border-1px-transparent w3-last ui-corner-all',
                            'title'=>Yii::t('link','Edit')
                        )),
                ),
            );
        }

        // render the view file
        $this->render('grid',array(
            'models'=>$models,
            'pages'=>$pages,
            'sort'=>$sort,
            'accessType'=>$accessType,
            'state'=>$state,
            'filters'=>$filters,
            'allAccessType'=>$allAccessType,
            'accessTypeLinkText'=>$accessTypeLinkText,
            'allState'=>$allState,
            'stateLinkText'=>$stateLinkText,
            'gridRows'=>$gridRows,
        ));
    }

    /**
     * Print out array of models for the jqGrid rows.
     */
    public function actionGridData()
    {
        if(!User::isManager() && !User::isAdministrator())
            return null;

        if(Yii::app()->request->isPostRequest)
        {
            // specify request details
            $jqGrid=$this->processJqGridRequest();

            // specify filter parameters
            $accessType=isset($_GET['accessType']) ? $_GET['accessType'] : null;
            if($accessType!=='all' && $accessType!==(string)User::MEMBER && $accessType!==(string)User::CLIENT && $accessType!==(string)User::CONSULTANT && $accessType!==(string)User::MANAGER && $accessType!==(string)User::ADMINISTRATOR)
                $accessType='all';
            $state=isset($_GET['state']) ? $_GET['state'] : null;
            if($state!=='all' && $state!=='active' && $state!=='inactive')
                $state='all';

            // criteria
            $criteria=new CDbCriteria;
            if($jqGrid['searchField']!==null && $jqGrid['searchString']!==null && $jqGrid['searchOper']!==null)
            {
                $field=array(
                    'accessType'=>"`".User::model()->tableName()."`.`accessType`",
                    'createTime'=>"`".User::model()->tableName()."`.`createTime`",
                    'email'=>"`".User::model()->tableName()."`.`email`",
                    'screenName'=>"`".User::model()->tableName()."`.`screenName`",
                    'deactivationTime'=>"UserUserDetails.`deactivationTime`",
                    'occupation'=>"UserUserDetails.`occupation`",
                );
                $operation=$this->getJqGridOperationArray();
                $keywordFormula=$this->getJqGridKeywordFormulaArray();
                if(isset($field[$jqGrid['searchField']]) && isset($operation[$jqGrid['searchOper']]))
                {
                    $criteria->condition='('.$field[$jqGrid['searchField']].' '.$operation[$jqGrid['searchOper']].' :keyword)';
                    $criteria->params=array(':keyword'=>str_replace('keyword',$jqGrid['searchString'],$keywordFormula[$jqGrid['searchOper']]));
                    // search by special field types
                    if($jqGrid['searchField']==='createTime' && ($keyword=strtotime($jqGrid['searchString']))!==false)
                    {
                        $criteria->params=array(':keyword'=>str_replace('keyword',$keyword,$keywordFormula[$jqGrid['searchOper']]));
                        if(date('H:i:s',$keyword)==='00:00:00')
                            // visitor is looking for a precision by day, not by second
                            $criteria->condition='(TO_DAYS(FROM_UNIXTIME('.$field[$jqGrid['searchField']].',"%Y-%m-%d")) '.$operation[$jqGrid['searchOper']].' TO_DAYS(FROM_UNIXTIME(:keyword,"%Y-%m-%d")))';
                    }
                }
            }
            if($accessType===(string)User::MEMBER)
            {
                $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."`".User::model()->tableName()."`.`accessType`=:member";
                $criteria->params=array_merge($criteria->params,array(':member'=>User::MEMBER));
            }
            else if($accessType===(string)User::CLIENT)
            {
                $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."`".User::model()->tableName()."`.`accessType`=:client";
                $criteria->params=array_merge($criteria->params,array(':client'=>User::CLIENT));
            }
            else if($accessType===(string)User::CONSULTANT)
            {
                $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."`".User::model()->tableName()."`.`accessType`=:consultant";
                $criteria->params=array_merge($criteria->params,array(':consultant'=>User::CONSULTANT));
            }
            else if($accessType===(string)User::MANAGER)
            {
                $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."`".User::model()->tableName()."`.`accessType`=:manager";
                $criteria->params=array_merge($criteria->params,array(':manager'=>User::MANAGER));
            }
            else if($accessType===(string)User::ADMINISTRATOR)
            {
                $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."`".User::model()->tableName()."`.`accessType`=:administrator";
                $criteria->params=array_merge($criteria->params,array(':administrator'=>User::ADMINISTRATOR));
            }
            if($state==='active')
            {
                $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."(`".User::model()->tableName()."`.`isActive` IS NULL OR `".User::model()->tableName()."`.`isActive`!=:isNotActive)";
                $criteria->params=array_merge($criteria->params,array(':isNotActive'=>User::IS_NOT_ACTIVE));
            }
            else if($state==='inactive')
            {
                $criteria->condition.=($criteria->condition==='' ? '' : ' AND ')."`".User::model()->tableName()."`.`isActive`=:isNotActive";
                $criteria->params=array_merge($criteria->params,array(':isNotActive'=>User::IS_NOT_ACTIVE));
            }

            // pagination
            $with=array();
            if(strpos($criteria->condition,'UserUserDetails')!==false)
                $with[]='details';
            if(count($with)>=1)
                $pages=new CPagination(User::model()->with($with)->count($criteria));
            else
                $pages=new CPagination(User::model()->count($criteria));
            $pages->pageSize=$jqGrid['pageSize']!==null ? $jqGrid['pageSize'] : self::GRID_PAGE_SIZE;
            $pages->applyLimit($criteria);

            // sort
            $sort=new CSort('User');
            $sort->attributes=array(
                User::model()->tableName().'.accessLevel'=>'accessType',
                User::model()->tableName().'.createTime'=>'createTime',
                User::model()->tableName().'.email'=>'email',
                User::model()->tableName().'.screenName'=>'screenName',
                'UserUserDetails.deactivationTime'=>'deactivationTime',
                'UserUserDetails.occupation'=>'occupation',
            );
            $sort->defaultOrder="`".User::model()->tableName()."`.`screenName` ASC";
            $sort->applyOrder($criteria);

            // find all
            $models=User::model()->with('details')->findAll($criteria);

            // create resulting data array
            $data=array(
                'page'=>$pages->getCurrentPage()+1,
                'total'=>$pages->getPageCount(),
                'records'=>$pages->getItemCount(),
                'rows'=>array()
            );
            foreach($models as $model)
            {
                $data['rows'][]=array('id'=>$model->id,'cell'=>array(
                    CHtml::encode($model->screenName),
                    CHtml::encode($model->details->occupation),
                    CHtml::encode($model->email),
                    CHtml::encode(MDate::format($model->createTime,'medium',null)),
                    CHtml::encode(MDate::format($model->details->deactivationTime,'medium',null)),
                    CHtml::encode($model->getAttributeView('accessType')),
                    CHtml::link('<span class="ui-icon ui-icon-zoomin"></span>',array('show','id'=>$model->id),array(
                        'class'=>'w3-ig w3-link-icon w3-border-1px-transparent w3-first ui-corner-all',
                        'title'=>Yii::t('link','Show')
                    )).
                    CHtml::link('<span class="ui-icon ui-icon-pencil"></span>',array('update','id'=>$model->id),array(
                        'class'=>'w3-ig w3-link-icon w3-border-1px-transparent w3-last ui-corner-all',
                        'title'=>Yii::t('link','Edit')
                    )),
                ));
            }
            $this->printJson($data);
        }
        else
            throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param array of parameters
     */
    public function loadUser($parameters=array())
    {
        if($this->_model===null)
        {
            // processing parameters
            if(ctype_digit($parameters))
                $id=$parameters;
            else if(isset($parameters['id']))
                $id=$parameters['id'];
            else if(isset($_GET['id']))
                $id=$_GET['id'];
            else
                $id=null;
            $with=isset($parameters['with']) ? $parameters['with'] : null;
            // load the model
            if($id!==null)
            {
                if($with===null)
                    $this->_model=User::model()->findByPk($id);
                else
                    $this->_model=User::model()->with($with)->findByPk($id);
            }
            // check whether is success
            if($this->_model===null)
                throw new CHttpException(404,'The requested page does not exist.');
        }
        return $this->_model;
    }
}
