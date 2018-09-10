<?php

namespace Masuga\Hvrcraft\controllers;

use Craft;
use craft\web\Controller;
use Masuga\Hvrcraft\Hvrcraft;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CommunicationController extends Controller
{

	/**
	 * Do not require an authenticated user for this controller.
	 * @var boolean
	 */
	protected $allowAnonymous = true;

	/**
	 * The instance of the Hvrcraft plugin.
	 * @var Hvrcraft
	 */
	private $plugin = null;

	public function init()
	{
		$this->plugin = Hvrcraft::getInstance();
	}

	/**
	 * This controller action method responds to requests from hvrcraft.com with
	 * plugin and update data formated as JSON.
	 * @return Response
	 */
	public function actionSynchronize(): Response
	{
		$settings = $this->plugin->getSettings();
		$siteKey = Craft::$app->request->get('key');
		// On fail, throw a 404 so bots/hackers can't scan for a hvrcraft plugin installation.
		if ( ! $siteKey || $siteKey !== $settings->siteKey ) {
			throw new NotFoundHttpException;
		}
		// Fetch the installed plugin and update data.
		$responseContent = $this->plugin->hvrcraft->getPluginsAndUpdates();
		return $this->asJson($responseContent);
	}

}
