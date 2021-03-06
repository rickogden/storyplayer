<?php

/**
 * Copyright (c) 2011-present Mediasift Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Libraries
 * @package   Storyplayer/PlayerLib
 * @author    Stuart Herbert <stuart.herbert@datasift.com>
 * @copyright 2011-present Mediasift Ltd www.datasift.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://datasift.github.io/storyplayer
 */

namespace DataSift\Storyplayer\PlayerLib;

use Exception;

use DataSift\Storyplayer\Prose\E5xx_NoMatchingActions;
use DataSift\Storyplayer\Prose\PageContext;
use DataSift\Storyplayer\StoryLib\Story;
use DataSift\Storyplayer\DeviceLib;

use DataSift\Stone\HttpLib\HttpAddress;
use DataSift\Stone\Log\LogLib;
use DataSift\Stone\ObjectLib\BaseObject;
use DataSift\Stone\PathLib\PathTo;
use DataSift\Stone\ProcessLib\SubProcess;

/**
 * our main facilitation class
 *
 * all actions and tests inside a story are executed through an instance
 * of this class, making this class the StoryTeller :)
 *
 * @category  Libraries
 * @package   Storyplayer/PlayerLib
 * @author    Stuart Herbert <stuart.herbert@datasift.com>
 * @copyright 2011-present Mediasift Ltd www.datasift.com
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link      http://datasift.github.io/storyplayer
 */
class StoryTeller
{
	/**
	 * the story that is being played
	 * @var Story
	 */
	private $story = null;

	private $storyContext = null;
	private $pageContext = null;
	private $checkpoint = null;

	private $proseLoader = null;
	private $configLoader = null;

	// support for the current runtime config
	private $runtimeConfig = null;
	private $runtimeConfigManager = null;

	/**
	 * [$actionLogger description]
	 * @var Datasift\Storyplayer\PlayerLib\ActionLogItem
	 */
	private $actionLogger;

	/**
	 * which of the steps is currently being executed?
	 * @var [type]
	 */
	private $currentPhase = null;

	// test device support
	private $deviceAdapter = null;

	public function __construct()
	{
		// set a default page context
		$this->setPageContext(new PageContext);

		// create the actionlog
		$this->setActionLogger(new ActionLogger());

		// create an empty context
		$this->setCheckpoint(new StoryCheckpoint($this));

		// create our Prose Loader
		$this->setProseLoader();
	}

	// ==================================================================
	//
	// Getters and setters go here
	//
	// ------------------------------------------------------------------

	/**
	 * [description here]
	 *
	 * @return [type] [description]
	 */
	public function getActionLogger() {
	    return $this->actionLogger;
	}

	/**
	 * [Description]
	 *
	 * @param [type] $newactionLogger [description]
	 */
	public function setActionLogger(ActionLogger $actionLogger) {
	    $this->actionLogger = $actionLogger;

	    return $this;
	}

	/**
	 * [description here]
	 *
	 * @return [type] [description]
	 */
	public function getCheckpoint() {
	    return $this->checkpoint;
	}

	/**
	 * [Description]
	 *
	 * @param [type] $newcheckpoint [description]
	 */
	public function setCheckpoint(StoryCheckpoint $checkpoint) {
	    $this->checkpoint = $checkpoint;

	    return $this;
	}

	/**
	 * [description here]
	 *
	 * @return [type] [description]
	 */
	public function getPageContext() {
	    return $this->pageContext;
	}

	/**
	 * [Description]
	 *
	 * @param [type] $newPageContext [description]
	 */
	public function setPageContext(PageContext $pageContext) {
	    $this->pageContext = $pageContext;

	    return $this;
	}

	/**
	 * [description here]
	 *
	 * @return [type] [description]
	 */
	public function getStory() {
	    return $this->story;
	}

	/**
	 * [Description]
	 *
	 * @param [type] $newstory [description]
	 */
	public function setStory($story) {
	    $this->story = $story;

	    return $this;
	}

	/**
	 * [description here]
	 *
	 * @return [type] [description]
	 */
	public function getStoryContext() {
	    return $this->storyContext;
	}

	/**
	 * [Description]
	 *
	 * @param [type] $newstoryContext [description]
	 */
	public function setStoryContext(StoryContext $storyContext) {
	    $this->storyContext = $storyContext;

	    // we need to update our ProseLoader, as the list of namespaces
	    // to search for Prose classes may have changed
	    $this->proseLoader->setNamespaces($this);

	    // all done
	    return $this;
	}

	/**
	 * [description here]
	 *
	 * @return [type] [description]
	 */
	public function getRuntimeConfigManager() {
	    return $this->runtimeConfigManager;
	}

	/**
	 * [Description]
	 *
	 * @param [type] $runtimeConfigManager [description]
	 */
	public function setRuntimeConfigManager($runtimeConfigManager) {
	    $this->runtimeConfigManager = $runtimeConfigManager;

	    return $this;
	}

	public function getCurrentPhase()
	{
		return $this->currentPhase;
	}

	public function getCurrentPhaseName()
	{
		return Storyplayer::$phaseToText[$this->currentPhase];
	}

	public function setCurrentPhase($newPhase)
	{
		$this->currentPhase = $newPhase;
	}

	public function setProseLoader()
	{
		$this->proseLoader = new ProseLoader();
	}

	// ====================================================================
	//
	// Helpers to get parts of the story's context go here
	//
	// --------------------------------------------------------------------

	public function getAdminUser()
	{
		return $this->storyContext->env->adminUser;
	}

	public function getEnvironment()
	{
		return $this->storyContext->env;
	}

	public function getEnvironmentName()
	{
		return $this->storyContext->envName;
	}

	public function getRuntimeConfig()
	{
		return $this->runtimeConfig;
	}

	public function setRuntimeConfig($runtimeConfig)
	{
		$this->runtimeConfig = $runtimeConfig;
	}

	public function saveRuntimeConfig()
	{
		if (!isset($this->runtimeConfigManager)) {
			throw new E5xx_ActionFailed(__METHOD__, "no runtimeConfigManager available");
		}

		$this->runtimeConfigManager->saveRuntimeConfig($this->runtimeConfig);
	}

	public function getUser()
	{
		return $this->storyContext->user;
	}

	public function getUrl()
	{
		return $this->storyContext->env->url;
	}

	public function setUrl($url)
	{
		$this->storyContext->env->url = $url;

		return $this;
	}

	public function getDefines()
	{
		return $this->storyContext->defines;
	}

	public function getParams()
	{
		// get the current parameters from the story
		//
		// these may have been previously augmented by a template
		// calling $st->addDefaultParams()
		$return = $this->getStory()->getParams();

		// merge in any defines from the command-line
		$defines = $this->getDefines();
		foreach ($defines as $key => $value) {
			$return[$key] = $value;
		}

		// all done
		//
		// NOTE that we deliberately don't cache $return in here, as
		// the parameters storied in the story can (in theory) change
		// at any moment
		return $return;
	}

	// ==================================================================
	//
	// Accessors of other containers go here
	//
	// ------------------------------------------------------------------

	public function __call($methodName, $methodArgs)
	{
		// what class do we want?
		$className = $this->proseLoader->determineProseClassFor($methodName);

		// use the Prose Loader to create the object to call
		$obj = $this->proseLoader->loadProse($this, $className, $methodArgs);

		// did we find something?
		if (!is_object($obj)) {
			// alas, no
			throw new E5xx_NoMatchingActions($methodName);
		}

		// all done
		return $obj;
	}

	// ==================================================================
	//
	// Logging support
	//
	// ------------------------------------------------------------------

	public function startAction($text)
	{
		return $this->actionLogger->startAction($this->getUser(), $text);
	}

	public function closeAllOpenActions()
	{
		return $this->actionLogger->closeAllOpenActions();
	}

	// ==================================================================
	//
	// Device support
	//
	// ------------------------------------------------------------------

	public function getDeviceDetails()
	{
		return $this->storyContext->device;
	}

	public function getDeviceAdapter()
	{
		if (!isset($this->deviceAdapter)) {
			return null;
		}

		return $this->deviceAdapter;
	}

	public function setDeviceAdapter($adapter)
	{
	    $this->deviceAdapter = $adapter;

	    return $this;
	}

	public function getDeviceName()
	{
		return $this->storyContext->deviceName;
	}

	public function getRunningDevice()
	{
		if (!is_object($this->deviceAdapter))
		{
			$this->startDevice();
		}

		if (!is_object($this->deviceAdapter))
		{
			throw new E5xx_CannotStartDevice();
		}

		return $this->deviceAdapter->getDevice();
	}

	public function startDevice()
	{
		// what are we doing?
		$log = $this->startAction('start the test device');

		// what sort of browser are we starting?
		$deviceDetails = $this->getDeviceDetails();

		// get the adapter
		$adapter = DeviceLib::getDeviceAdapter($deviceDetails);

		// initialise the adapter
		$adapter->init($deviceDetails);

		// start the browser
		$adapter->start($this);

		// remember the adapter
		$this->setDeviceAdapter($adapter);

		// do we have a deviceSetup() phase?
		if ($this->story->hasDeviceSetup()) {
			// get the callbacks to call
			$callbacks = $this->story->getDeviceSetup();

			// make the call
			//
			// we do not need to wrap these in a TRY/CATCH block,
			// as we are already running inside one of the story's
			// phases
			foreach ($callbacks as $callback){
				call_user_func($callback, $this);
			}
		}

		// all done
		$log->endAction();
	}

	public function stopDevice()
	{
		// get the browser adapter
		$adapter = $this->getDeviceAdapter();

		// stop the web browser
		if (!$adapter) {
			// nothing to do
			return;
		}

		// what are we doing?
		$log = $this->startAction('stop the test device');

		// do we have a deviceTeardown() phase?
		//
		// we need to run this BEFORE we stop the device, otherwise
		// the deviceTeardown() phase has no device to work with
		if ($this->story->hasDeviceTeardown()) {
			// get the callbacks to call
			$callbacks = $this->story->getDeviceTeardown();

			// make the call
			//
			// we do not need to wrap these in a TRY/CATCH block,
			// as we are already running inside one of the story's
			// phases
			foreach ($callbacks as $callback){
				call_user_func($callback, $this);
			}
		}

		// stop the browser
		$adapter->stop();

		// destroy the adapter
		$this->setDeviceAdapter(null);

		// all done
		$log->endAction();
	}
}
