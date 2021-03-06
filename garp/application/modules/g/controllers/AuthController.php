<?php
/**
 * G_AuthController
 * This controller handles logging users in and out.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_AuthController extends Garp_Controller_Action {
	const EXCEPTION_INVALID_LOGIN_HELPER =
		'A Login Helper is registered, but not of type Garp_Controller_Helper_Login.';
	const EXCEPTION_INVALID_REGISTER_HELPER =
 	   	'A Register Helper is registered, but not of type Garp_Controller_Helper_Register.';

	public function init() {
		$action = $this->getRequest()->getActionName();
		$this->_setViewSettings($action);
	}

	/**
	 * Index page, just redirects to $this->loginAction().
	 * It's only here because it might be handy to have a landing page someday.
	 * @return Void
	 */
	public function indexAction() {
		$this->_redirect('/g/auth/login');
	}

	/**
	 * Register a new account
	 * @return Void
	 */
	public function registerAction() {
		$this->view->title = __('register page title');
		$authVars = Garp_Auth::getInstance()->getConfigValues();

		if (!$this->getRequest()->isPost()) {
			return;
		}
		$errors = array();
		$postData = $this->getRequest()->getPost();
		$this->view->postData = $postData;

		// Apply some mild validation
		$password = $this->getRequest()->getPost('password');
		if (!$password) {
			$errors[] = sprintf(__('%s is a required field'), __('Password'));
		}

		$checkRepeatPassword = !empty($authVars['register']['repeatPassword']) && $authVars['register']['repeatPassword'];
		if ($checkRepeatPassword) {
			$repeatPasswordField = $this->getRequest()->getPost($authVars['register']['repeatPasswordField']);
			unset($postData[$authVars['register']['repeatPasswordField']]);
			if ($password != $repeatPasswordField) {
				$errors[] = __('the passwords do not match');
			}
		}

		if (count($errors)) {
			$this->view->errors = $errors;
			return;
		}

		// Save the new user
		$userModel = new Model_User();
		try {
			// Before register hook
			$this->_beforeRegister($postData);

			// Extract columns that are not part of the user model
			$userData = $userModel->filterColumns($postData);
			$insertId = $userModel->insert($userData);
			$this->_helper->flashMessenger(__($authVars['register']['successMessage']));

			// Store new user directly thru Garp_Auth so that they're logged in immediately
			$newUser = $userModel->find($insertId)->current();

			$auth = Garp_Auth::getInstance();
			$auth->store($newUser->toArray(), 'db');

			// After register hook
			$this->_afterRegister();

			// Determine targetUrl. This is the URL the user was trying to access before registering, or a default URL.
			$router = Zend_Controller_Front::getInstance()->getRouter();
			if (!empty($authVars['register']['successRoute'])) {
				$targetUrl = $router->assemble(array(), $authVars['register']['successRoute']);
			} elseif (!empty($authVars['register']['successUrl'])) {
				$targetUrl = $authVars['register']['successUrl'];
			} else {
				$targetUrl = '/';
			}
			$store = Garp_Auth::getInstance()->getStore();
			if ($store->targetUrl) {
				$targetUrl = $store->targetUrl;
				unset($store->targetUrl);
			}

			$this->_redirect($targetUrl);
		// Check for duplication errors in order to show
		// a helpful error to the user.
		} catch (Zend_Db_Statement_Exception $e) {
			if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'email_unique') !== false) {
				$errors[] = __('this email address already exists');
			} else {
				throw $e;
			}
		// Validation errors should be safe to show to the user (note: translation
		// must be done in the validator itself)
		} catch (Garp_Model_Validator_Exception $e) {
			$errors[] = $e->getMessage();

		// Unknown error? Yikes... Show to developers, but show a
		// generic error to the general public.
		} catch (Exception $e) {
			$error = APPLICATION_ENV === 'development' ? $e->getMessage() : __('register error');
			$errors[] = $error;
		}
		$this->view->errors = $errors;
	}

	/**
	 * Show a login page.
	 * Note that $this->processAction does the actual logging in.
	 * This separation is useful because some 3rd parties send back
	 * GET variables instead of POST. This way we don't need to
	 * worry about that here.
	 * @return Void
	 */
	public function loginAction() {
		// Do not cache login page
		$this->_helper->cache->setNoCacheHeaders($this->getResponse());

		$this->view->title = __('login page title');
		$this->view->description = __('login page description');

		// allow callers to set a targetUrl via the request
		if ($this->getRequest()->getParam('targetUrl')) {
			$targetUrl = $this->getRequest()->getParam('targetUrl');
			Garp_Auth::getInstance()->getStore()->targetUrl = $targetUrl;
		}

		$authVars = Garp_Auth::getInstance()->getConfigValues();
		// self::processAction might have populated 'errors' and/or 'postData'
		if ($this->getRequest()->getParam('errors')) {
			$this->view->errors = $this->getRequest()->getParam('errors');
		}
		if ($this->getRequest()->getParam('postData')) {
			$this->view->postData = $this->getRequest()->getParam('postData');
		}
	}

	/**
	 * Process the login request. @see G_AuthController::loginAction as to
	 * why this is separate.
	 * @return Void
	 */
	public function processAction() {
		// never cache the process request
		$this->_helper->cache->setNoCacheHeaders($this->getResponse());
		// This action does not render a view, it only redirects elsewhere.
		$this->_helper->viewRenderer->setNoRender(true);
		$method = $this->getRequest()->getParam('method') ?: 'db';
		$adapter = Garp_Auth_Factory::getAdapter($method);
		$authVars = Garp_Auth::getInstance()->getConfigValues();

		// Before login hook.
		$this->_beforeLogin($authVars, $adapter);

		/**
		 * Params can come from GET or POST.
		 * The implementing adapter should decide which to use,
		 * using the current request to fetch params.
		 */
		if (!$userData = $adapter->authenticate($this->getRequest())) {
			$this->_respondToFaultyProcess($adapter);
			return;
		}

		if ($userData instanceof Garp_Db_Table_Row) {
			$userData = $userData->toArray();
		}

		// Save user data in a store.
		Garp_Auth::getInstance()->store($userData, $method);

		// Store User role in a cookie, so that we can use it with Javascript.
		if (!Garp_Auth::getInstance()->getStore() instanceof Garp_Store_Cookie) {
			$this->_storeRoleInCookie();
		}

		// Determine targetUrl. This is the URL the user was trying to access before logging in, or a default URL.
		$router = Zend_Controller_Front::getInstance()->getRouter();
		if (!empty($authVars['login']['successRoute'])) {
			$targetUrl = $router->assemble(array(), $authVars['login']['successRoute']);
		} elseif (!empty($authVars['login']['successUrl'])) {
			$targetUrl = $authVars['login']['successUrl'];
		} else {
			$targetUrl = '/';
		}
		$store = Garp_Auth::getInstance()->getStore();
		if ($store->targetUrl) {
			$targetUrl = $store->targetUrl;
			unset($store->targetUrl);
		}

		// After login hook.
		$this->_afterLogin($userData, $targetUrl);

		// Set a Flash message welcoming the user.
		$flashMessenger = $this->_helper->getHelper('FlashMessenger');
		$fullName = new Garp_Util_FullName($userData);
		$successMsg = __($authVars['login']['successMessage']);
		if (strpos($successMsg, '%s') !== false) {
			$successMsg = sprintf($successMsg, $fullName);
		} elseif (strpos('%USERNAME%', $successMsg) !== false) {
			$successMsg = Garp_Util_String::interpolate($successMsg, array(
				'USERNAME' => $fullName
			));
		}
		$flashMessenger->addMessage($successMsg);
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_redirect($targetUrl);
	}

	/**
	 * Log a user out.
	 * @return Void
	 */
	public function logoutAction() {
		// never cache the logout request
		$this->_helper->cache->setNoCacheHeaders($this->getResponse());

		$auth = Garp_Auth::getInstance();
		$userData = $auth->getUserData();
		$this->_beforeLogout($userData);

		$auth->destroy();
		$authVars = $auth->getConfigValues();
		$target = '/';
		if ($authVars && !empty($authVars['logout']['successUrl'])) {
			$target = $authVars['logout']['successUrl'];
		}

		// Remove the role cookie
		if (!$auth->getStore() instanceof Garp_Store_Cookie) {
			$this->_removeRoleCookie();
		}

		$this->_afterLogout($userData);

		$flashMessenger = $this->_helper->getHelper('FlashMessenger');
		$flashMessenger->addMessage(__($authVars['logout']['successMessage']));

		$cacheBuster = 'action=logout';
		$target .= (strpos($target, '?') === false ? '?' : '&') . $cacheBuster;
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_redirect($target);
	}

	public function tokenrequestedAction() {
		$this->view->title = __('login token requested page title');
	}

	/**
	 * Forgot password
	 * @return Void
	 */
	public function forgotpasswordAction() {
		$this->view->title = __('forgot password page title');
		$auth = Garp_Auth::getInstance();
		$authVars = $auth->getConfigValues();
		$request = $this->getRequest();

		if ($request->getParam('success') == '1') {
			$this->view->successMessage = __($authVars['forgotpassword']['success_message']);
		}

		if (!$request->isPost()) {
			return;
		}

		// Honeypot validation
		$hp = $request->getPost('hp');
		if (!empty($hp)) {
			throw new Garp_Auth_Exception(__('honeypot error'));
		}

		// Find user by email address
		$this->view->email = $email = $request->getPost('email');
		$userModel = new Model_User();
		$user = $userModel->fetchRow(
			$userModel->select()->where('email = ?', $email)
		);
		if (!$user) {
			$this->view->formError = __('email addr not found');
		} else {
			// Update user
			$activationToken = uniqid();
			$activationCode  = '';
			$activationCode .= $activationToken;
			$activationCode .= md5($email);
			$activationCode .= md5($authVars['salt']);
			$activationCode .= md5($user->id);
			$activationCode = md5($activationCode);
			$activationUrl = '/g/auth/resetpassword/c/'.$activationCode.'/e/'.md5($email).'/';

			$activationCodeExpiresColumn = $authVars['forgotpassword']['activation_code_expiration_date_column'];
			$activationTokenColumn = $authVars['forgotpassword']['activation_token_column'];
			$activationCodeExpiry = date('Y-m-d', strtotime($authVars['forgotpassword']['activation_code_expires_in']));

			$user->{$activationCodeExpiresColumn} = $activationCodeExpiry;
			$user->{$activationTokenColumn} = $activationToken;

			if ($user->save()) {
				// Render the email message
				$this->_helper->layout->disableLayout();
				// Email can be put in a partial...
				if (!empty($authVars['forgotpassword']['email_partial'])) {
					$this->view->user = $user;
					$this->view->activationUrl = $activationUrl;
					// Add "default" module as a script path so the partial can
					// be found.
					$this->view->addScriptPath(APPLICATION_PATH.'/modules/default/views/scripts/');
					$emailMessage = $this->view->render($authVars['forgotpassword']['email_partial']);
				} else {
					// ...or the email can be added as a snippet
					$snippet_column = !empty($authVars['forgotpassword']['email_snippet_column']) ?
						$authVars['forgotpassword']['email_snippet_column'] : 'text';
					$snippet_identifier = !empty($authVars['forgotpassword']['email_snippet_identifier']) ?
						$authVars['forgotpassword']['email_snippet_identifier'] : 'forgot password email';
					$snippetModel = $this->_getSnippetModel();
					$emailSnippet = $snippetModel->fetchByIdentifier($snippet_identifier);
					$emailMessage = $emailSnippet->{$snippet_column};
					$emailMessage = Garp_Util_String::interpolate($emailMessage, array(
						'USERNAME'       => (string)new Garp_Util_FullName($user),
						'ACTIVATION_URL' => (string)new Garp_Util_FullUrl($activationUrl)
					));
				}

				// Send mail to the user
				// @todo Make this more transparent. Use a Strategy design pattern for instance.
				$emailMethod = 'ses';
				$email_content_type = 'Text';
				if (!empty($authVars['forgotpassword']['email_content_type'])) {
					$email_content_type = $authVars['forgotpassword']['email_content_type'];
				}
				if (!empty($authVars['forgotpassword']['email_method'])) {
					$emailMethod = $authVars['forgotpassword']['email_method'];
				}
				if ($emailMethod === 'ses') {
					$ses = new Garp_Service_Amazon_Ses();
					$response = $ses->sendEmail(array(
						'Destination' => $email,
						'Message'     => array(
							$email_content_type => $emailMessage,
						),
						'Subject'     => __($authVars['forgotpassword']['email_subject']),
						'Source'      => $authVars['forgotpassword']['email_from_address']
					));
				} elseif ($emailMethod === 'zend') {
					$mail = new Zend_Mail();
					$mail->setBodyText($emailMessage);
					$mail->setFrom($authVars['forgotpassword']['email_from_address']);
					$mail->addTo($email);
					$mail->setSubject(__($authVars['forgotpassword']['email_subject']));
					$response = $mail->send();
				} elseif (Garp_Loader::getInstance()->isLoadable($emailMethod)) {
					$mailer = new $emailMethod;
					$params = isset($authVars['forgotpassword']['default_mail_params']) ?
						(array)$authVars['forgotpassword']['default_mail_params'] : array();
					$params = array_merge($params, array(
						'to' => $email,
						'subject' => __($authVars['forgotpassword']['email_subject']),
						'message' => $emailMessage,
						'from' => $authVars['forgotpassword']['email_from_address']
					));
					$response = $mailer->send($params);
				} else {
					throw new Garp_Auth_Exception('Unknown email_method chosen. '.
						'Please reconfigure auth.forgotpassword.email_method');
				}
				if ($response) {
					if (isset($authVars['forgotpassword']['route'])) {
						$this->_helper->redirector->gotoRoute(array('success' => 1), $authVars['forgotpassword']['route']);
					} elseif (isset($authVars['forgotpassword']['url'])) {
						$targetUrl = $authVars['forgotpassword']['url'];
						$this->_helper->redirector->gotoUrl($targetUrl . '?success=1');
					}
				} else {
					$this->view->formError = __($authVars['forgotpassword']['failure_message']);
				}
			}
		}
	}

	/**
	 * Allow a user to reset his password after he had forgotten it.
	 */
	public function resetpasswordAction() {
		$this->view->title = __('reset password page title');
		$authVars = Garp_Auth::getInstance()->getConfigValues();
		$activationCode = $this->getRequest()->getParam('c');
		$activationEmail = $this->getRequest()->getParam('e');
		$expirationColumn = $authVars['forgotpassword']['activation_code_expiration_date_column'];

		$userModel = new Model_User();
		$activationCodeClause =
			'MD5(CONCAT('.
				$userModel->getAdapter()->quoteIdentifier($authVars['forgotpassword']['activation_token_column']).','.
				'MD5(email),'.
				'MD5('.$userModel->getAdapter()->quote($authVars['salt']).'),'.
				'MD5(id)'.
			')) = ?'
		;
		$select = $userModel->select()
			// check if a user matches up to the given code
			->where($activationCodeClause, $activationCode)
			// check if the given email address is part of the same user record
			->where('MD5(email) = ?', $activationEmail)
		;

		$user = $userModel->fetchRow($select);
		if (!$user) {
			$this->view->error = __('reset password user not found');
			return;
		}
		if (strtotime($user->{$expirationColumn}) < time()) {
			$this->view->error = __('reset password link expired');
			return;
		}
		if (!$this->getRequest()->isPost()) {
			return;
		}
		$password = $this->getRequest()->getPost('password');
		if (!$password) {
			$this->view->formError = sprintf(__('%s is a required field'), ucfirst(__('password')));
			return;
		}

		if (!empty($authVars['forgotpassword']['repeatPassword']) &&
			!empty($authVars['forgotpassword']['repeatPasswordField'])) {
			$repeatPasswordField =
				$this->getRequest()->getPost($authVars['forgotpassword']['repeatPasswordField']);
			if ($password != $repeatPasswordField) {
				$this->view->formError = __('the passwords do not match');
				return;
			}
		}

		// Update the user's password and send him along to the login page
		$updateClause = $userModel->getAdapter()->quoteInto('id = ?', $user->id);
		$userModel->update(array(
			'password' => $password,
			$authVars['forgotpassword']['activation_token_column'] => null,
			$authVars['forgotpassword']['activation_code_expiration_date_column'] => null
		), $updateClause);
		$this->_helper->flashMessenger(__($authVars['resetpassword']['success_message']));
		$this->_redirect('/g/auth/login');
	}

	/**
	 * Validate email address. In scenarios where users receive an email validation email,
	 * this action is used to validate the address.
	 */
	public function validateemailAction() {
		$this->view->title = __('activate email page title');
		$auth = Garp_Auth::getInstance();
		$authVars = $auth->getConfigValues();
		$request = $this->getRequest();
		$activationCode = $request->getParam('c');
		$activationEmail = $request->getParam('e');
		$emailValidColumn = $authVars['validateEmail']['email_valid_column'];

		if (!$activationEmail || !$activationCode) {
			throw new Zend_Controller_Action_Exception('Invalid request.', 404);
		}

		$userModel = new Model_User();
		// always collect fresh data for this one
		$userModel->setCacheQueries(false);
		$activationCodeClause =
			'MD5(CONCAT('.
				$userModel->getAdapter()->quoteIdentifier($authVars['validateEmail']['token_column']).','.
				'MD5(email),'.
				'MD5('.$userModel->getAdapter()->quote($authVars['salt']).'),'.
				'MD5(id)'.
			')) = ?'
		;
		$select = $userModel->select()
			// check if a user matches up to the given code
			->where($activationCodeClause, $activationCode)
			// check if the given email address is part of the same user record
			->where('MD5(email) = ?', $activationEmail)
		;

		$user = $userModel->fetchRow($select);
		if (!$user) {
			$this->view->error = __('invalid email activation code');
		} else {
			$user->{$emailValidColumn} = 1;
			if (!$user->save()) {
				$this->view->error = __('activate email error');
			} elseif ($auth->isLoggedIn()) {
				// If the user is currently logged in, update the cookie
				$method = $auth->getStore()->method;
				$userData = $auth->getUserData();
				// Sanity check: is the user that has just validated his email address the currently logged in user?
				if ($userData['id'] == $user->id) {
					$userData[$emailValidColumn] = 1;
					$auth->store($userData, $method);
				}
			}
		}
	}

	/**
	 * Render a configured view
	 * @param Array $authVars Configuration for a specific auth section.
	 * @return Void
	 */
	protected function _setViewSettings($action) {
		$authVars = Garp_Auth::getInstance()->getConfigValues();
		if (!isset($authVars[$action])) {
			return;
		}
		$authVars = $authVars[$action];
		$module = isset($authVars['module']) ? $authVars['module'] : 'default';
		$moduleDirectory = $this->getFrontController()
			->getModuleDirectory($module);
		$viewPath = $moduleDirectory.'/views/scripts/';

		$this->view->addScriptPath($viewPath);
		$view = isset($authVars['view']) ? $authVars['view'] : $action;
		$this->_helper->viewRenderer($view);
		$layout = isset($authVars['layout']) ? $authVars['layout'] : 'layout';
		if ($this->_helper->layout->isEnabled()) {
			$this->_helper->layout->setLayoutPath($moduleDirectory.'/views/layouts');
			$this->_helper->layout->setLayout($layout);
		}
	}

	/**
	 * Store user role in cookie, so it can be used with Javascript
	 * @return Void
	 */
	protected function _storeRoleInCookie() {
		$userRecord = Garp_Auth::getInstance()->getUserData();
		if (!empty($userRecord['role'])) {
			$cookie = new Garp_Store_Cookie('Garp_Auth');
			$cookie->userData = array('role' => $userRecord['role']);
		}
	}

	/**
	 * Remove role cookie
	 * @return Void
	 */
	protected function _removeRoleCookie() {
		// Use the cookie store to destroy the cookie.
		$store = new Garp_Store_Cookie('Garp_Auth');
		$store->destroy();
	}

	/**
	 * Before register hook
	 * @param Array $postData
	 * @return Void
	 */
	protected function _beforeRegister(array &$postData) {
		if ($registerHelper = $this->_getRegisterHelper()) {
			$registerHelper->beforeRegister($postData);
		}
	}

	/**
	 * After register hook
	 * @return Void
	 */
	protected function _afterRegister() {
		if ($registerHelper = $this->_getRegisterHelper()) {
			$registerHelper->afterRegister();
		}
	}

	/**
	 * Before login hook
	 * @param Array $authVars Containing auth-related configuration.
	 * @param Garp_Auth_Adapter_Abstract $adapter The chosen adapter.
	 * @return Void
	 */
	protected function _beforeLogin(array $authVars, Garp_Auth_Adapter_Abstract $adapter) {
		if ($loginHelper = $this->_getLoginHelper()) {
			$loginHelper->beforeLogin($authVars, $adapter);
		}
	}

	/**
	 * After login hook
	 * @param Array $userData The data of the logged in user
	 * @param String $targetUrl The URL the user is being redirected to
	 * @return Void
	 */
	protected function _afterLogin(array $userData, $targetUrl) {
		if ($loginHelper = $this->_getLoginHelper()) {
			$loginHelper->afterLogin($userData, $targetUrl);
		}
	}

	/**
	 * Before logout hook
	 * @param Array $userData The current user's data
	 * @return Void
	 */
	protected function _beforeLogout($userData) {
		if ($loginHelper = $this->_getLoginHelper()) {
			$loginHelper->beforeLogout($userData);
		}
	}

	/**
	 * Before login hook
	 * @param Array $userData The current user's data
	 * @return Void
	 */
	protected function _afterLogout($userData) {
		if ($loginHelper = $this->_getLoginHelper()) {
			$loginHelper->afterLogout($userData);
		}
	}

	/**
	 * Get the Login helper, if registered.
	 * @return Zend_Controller_Action_Helper_Abstract
	 */
	protected function _getLoginHelper() {
		$loginHelper = $this->_helper->getHelper('Login');
		if (!$loginHelper) {
			return null;
		}
		if (!$loginHelper instanceof Garp_Controller_Helper_Login) {
			throw new Garp_Auth_Exception(self::EXCEPTION_INVALID_LOGIN_HELPER);
		}
		return $loginHelper;
	}

	/**
	 * Get the Register helper, if registered.
	 * @return Zend_Controller_Action_Helper_Abstract
	 */
	protected function _getRegisterHelper() {
		$registerHelper = $this->_helper->getHelper('Register');
		if (!$registerHelper) {
			return null;
		}
		if (!$registerHelper instanceof Garp_Controller_Helper_Register) {
			throw new Garp_Auth_Exception(self::EXCEPTION_INVALID_REGISTER_HELPER);
		}
		return $registerHelper;
	}

	/**
 	 * Auth adapters may return false if no user is logged in yet.
 	 * We then have a couple of options on how to respond. Showing the login page again
 	 * with errors would be the default, but some adapters require a redirect to an external site.
 	 */
	protected function _respondToFaultyProcess(Garp_Auth_Adapter_Abstract $authAdapter) {
		if ($redirectUrl = $authAdapter->getRedirect()) {
			$this->_helper->redirector->gotoUrl($redirectUrl);
			return;
		}
		// Show the login page again.
		$request = clone $this->getRequest();
		$request->setActionName('login')
			->setParam('errors', $authAdapter->getErrors())
			->setParam('postData', $this->getRequest()->getPost());
		$this->_helper->actionStack($request);
		$this->_setViewSettings('login');
	}

	/**
 	 * Retrieve snippet model for system messages.
 	 */
	protected function _getSnippetModel() {
		$snippetModel = new Model_Snippet();
		if ($snippetModel->getObserver('Translatable')) {
			$i18nModelFactory = new Garp_I18n_ModelFactory();
			$snippetModel = $i18nModelFactory->getModel($snippetModel);
		}
		return $snippetModel;
	}
}
