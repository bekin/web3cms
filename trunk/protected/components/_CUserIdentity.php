<?php
/**
 * _CUserIdentity represents the data needed to create identity of a user.
 * It contains the authentication method that checks if the provided
 * data can create identity of the user.
 */
class _CUserIdentity extends CUserIdentity
{
    const ERROR_UNKNOWN_IDENTITY=10;
    const ERROR_ACCOUNT_IS_INACTIVE=11;
    const ERROR_IS_NOT_ADMINISTRATOR=12;

    private $_id;

    /**
     * @return integer the ID of the user record
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Authenticates a user.
     * @return boolean whether authentication succeeds.
     */
    public function authenticate()
    {
        if(UserLoginForm::getLoggingWithField()==='username')
            $user=User::model()->findByAttributes(array('username'=>$this->username));
        else if(UserLoginForm::getLoggingWithField()==='email')
            $user=User::model()->findByAttributes(array('email'=>$this->username));
        else if(UserLoginForm::getLoggingWithField()==='usernameOrEmail')
            $user=User::model()->find("`username`=? OR `email`=?",array($this->username,$this->username));
        if($user===null)
            $this->errorCode=self::ERROR_USERNAME_INVALID;
        else if(!$user->validatePassword($this->password))
            $this->errorCode=self::ERROR_PASSWORD_INVALID;
        else if($user->isActive===User::IS_NOT_ACTIVE)
            $this->errorCode=self::ERROR_ACCOUNT_IS_INACTIVE;
        else if(MArea::isBackend() && !User::isAdministrator($user->accessType))
        {
            $this->errorCode=self::ERROR_IS_NOT_ADMINISTRATOR;
            Yii::app()->controller->var->userAccessType=$user->getAttributeView('accessType');
        }
        else
        {
            $this->_id=$user->id;
            $this->errorCode=self::ERROR_NONE;
            // do not store password or other sensitive data in the persistent storage
            // when (config/main.php) allowAutoLogin is true, because
            // all these data will be stored in cookie = it is readable
            $this->setState('email',$user->email);
            $this->setState('interface',$user->interface);
            $this->setState('language',$user->language);
            $this->setState('screenName',$user->screenName);
            // init rbac
            $this->authorize($user);
        }
        return $this->errorCode==self::ERROR_NONE;
    }

    /**
     * Authenticates a user by cookie.
     * Is called by {@link _CWebUser::restoreFromCookie()}.
     * @return boolean whether authentication succeeds.
     */
    public function authenticateByCookie()
    {
        $user=User::model()->findByPk($this->username);
        if($user===null)
            $this->errorCode=self::ERROR_UNKNOWN_IDENTITY;
        else if($user->isActive===User::IS_NOT_ACTIVE)
            $this->errorCode=self::ERROR_ACCOUNT_IS_INACTIVE;
        else
        {
            $this->_id=$user->id;
            $this->errorCode=self::ERROR_NONE;
            // do not store password or other sensitive data in the persistent storage
            // when (config/main.php) allowAutoLogin is true, because
            // all these data will be stored in cookie = it is readable
            $this->setState('email',$user->email);
            $this->setState('interface',$user->interface);
            $this->setState('language',$user->language);
            $this->setState('screenName',$user->screenName);
            // init rbac
            $this->authorize($user);
        }
        return !$this->errorCode;
    }

    /**
     * Initialize Role-Based Access Control (RBAC).
     * Role -> task -> operation are essentially the same thing,
     * as you can see in the code they are of class {@link CAuthItem}.
     * With operations:
     *   $bizRule='return Yii::app()->user->id==$params["model"]->id;';
     *   $task=$auth->createTask('user/updateOwn','update own model',$bizRule);
     *   $task->addChild('user/update');
     * we can check using just:
     *   Yii::app()->user->checkAccess('user/update',array('model'=>$this->loadModel()));
     * For more info see: http://www.yiiframework.com/doc/guide/topics.auth
     * @param User model
     */
    private function authorize($user)
    {
        $auth=Yii::app()->authManager;
        // step one. destroy rbac object from previous save
        $auth->clearAll();
        // describe existing operations
        $auth->createOperation('company/grid','browse company grid');
        $auth->createOperation('company/list','browse company list');
        $auth->createOperation('company/update','update a company record');
        $auth->createOperation('companyPayment/grid','browse company payment grid');
        $auth->createOperation('companyPayment/list','browse company payment list');
        $auth->createOperation('companyPayment/update','update a company payment record');
        $auth->createOperation('expense/delete','delete an expense record');
        $bizRule='return is_object($params["model"]) && $params["model"]->invoiceId==0;';
        $task=$auth->createTask('expense/deleteWhenInvoiceIsNotSet','delete an expense record not associated with any invoice yet',$bizRule);
        $task->addChild('expense/delete');
        $auth->createOperation('expense/grid','browse expense grid');
        $auth->createOperation('expense/list','browse expense list');
        $auth->createOperation('expense/update','update an expense record');
        $bizRule='return is_object($params["model"]) && $params["model"]->invoiceId==0;';
        $task=$auth->createTask('expense/updateWhenInvoiceIsNotSet','update an expense record not associated with any invoice yet',$bizRule);
        $task->addChild('expense/update');
        $auth->createOperation('invoice/grid','browse invoice grid');
        $auth->createOperation('invoice/list','browse invoice list');
        $auth->createOperation('invoice/update','update an invoice record');
        $auth->createOperation('project/grid','browse project grid');
        $auth->createOperation('project/list','browse project list');
        $auth->createOperation('project/update','update a project record');
        $auth->createOperation('task/grid','browse task grid');
        $auth->createOperation('task/list','browse task list');
        $auth->createOperation('task/update','update a task record');
        $auth->createOperation('time/delete','delete a time record');
        $bizRule='return is_object($params["model"]) && $params["model"]->invoiceId==0;';
        $task=$auth->createTask('time/deleteWhenInvoiceIsNotSet','delete a time record not associated with any invoice yet',$bizRule);
        $task->addChild('time/delete');
        $auth->createOperation('time/grid','browse time grid');
        $auth->createOperation('time/list','browse time list');
        $auth->createOperation('time/update','update a time record');
        $bizRule='return is_object($params["model"]) && $params["model"]->invoiceId==0;';
        $task=$auth->createTask('time/updateWhenInvoiceIsNotSet','update a time record not associated with any invoice yet',$bizRule);
        $task->addChild('time/update');
        $auth->createOperation('user/grid','browse user grid');
        $auth->createOperation('user/list','browse user list');
        $auth->createOperation('user/update','update an user record');
        $bizRule='return Yii::app()->user->id==$params["model"]->id;';
        $task=$auth->createTask('user/updateOwn','update user own model',$bizRule);
        $task->addChild('user/update');
        // set relations between roles, tasks, operations
        $role=$auth->createRole(User::MEMBER);
        $role->addChild('user/updateOwn');
        $role=$auth->createRole(User::CLIENT);
        $role->addChild('company/grid');
        $role->addChild('company/list');
        $role->addChild('companyPayment/grid');
        $role->addChild('companyPayment/list');
        $role->addChild('expense/grid');
        $role->addChild('expense/list');
        $role->addChild('invoice/grid');
        $role->addChild('invoice/list');
        $role->addChild('project/grid');
        $role->addChild('project/list');
        $role->addChild('task/grid');
        $role->addChild('task/list');
        $role->addChild('time/grid');
        $role->addChild('time/list');
        $role->addChild('user/updateOwn');
        $role=$auth->createRole(User::CONSULTANT);
        $role->addChild('project/grid');
        $role->addChild('project/list');
        $role->addChild('task/grid');
        $role->addChild('task/list');
        $role->addChild('time/grid');
        $role->addChild('time/list');
        $role->addChild('user/updateOwn');
        $role=$auth->createRole(User::MANAGER);
        $role->addChild(User::CONSULTANT);
        $role->addChild('company/grid');
        $role->addChild('company/list');
        $role->addChild('companyPayment/grid');
        $role->addChild('companyPayment/list');
        $role->addChild('expense/deleteWhenInvoiceIsNotSet');
        $role->addChild('expense/updateWhenInvoiceIsNotSet');
        $role->addChild('expense/grid');
        $role->addChild('expense/list');
        $role->addChild('invoice/grid');
        $role->addChild('invoice/list');
        $role->addChild('task/update');
        $role->addChild('time/deleteWhenInvoiceIsNotSet');
        $role->addChild('time/updateWhenInvoiceIsNotSet');
        $role->addChild('user/grid');
        $role->addChild('user/list');
        $role=$auth->createRole(User::ADMINISTRATOR);
        $role->addChild(User::MANAGER);
        $role->addChild('company/update');
        $role->addChild('companyPayment/update');
        $role->addChild('expense/delete');
        $role->addChild('expense/update');
        $role->addChild('invoice/update');
        $role->addChild('project/update');
        $role->addChild('time/delete');
        $role->addChild('time/update');
        $role->addChild('user/update');
        // assign user his access type as role
        $auth->assign($user->accessType,$user->id);
        // last step. save
        $auth->save();
    }
}