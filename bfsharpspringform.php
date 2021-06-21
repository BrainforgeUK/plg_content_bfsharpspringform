<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.bfsharpspringform
 *
 * @copyright   Copyright (C) 2020 Jonathan Brain, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

/**
 *
 */
class PlgContentBfsharpspringform extends CMSPlugin
{
	protected $isAdmin;
	protected $forms;

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 *
	 * @since   1.5
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		$this->isAdmin = Factory::getApplication()->isClient('administrator');
		$this->forms = array();
	}

	/**
	 * Loads the plugin language file
	 *
	 * @param   string  $extension  The extension for which a language file should be loaded
	 * @param   string  $basePath   The basepath to use
	 *
	 * @return  boolean  True, if the file has successfully loaded.
	 *
	 * @since   1.5
	 */
	public function loadLanguage($extension = '', $basePath = JPATH_ADMINISTRATOR)
	{
		return parent::loadLanguage('plg_content_bfsharpspringform', __DIR__);
	}

	/**
	 *
	 * @param   string   $context   The context of the content being passed to the plugin.
	 * @param   object   &$article  The article object.  Note $article->text is also available
	 * @param   mixed    &$params   The article params
	 * @param   integer  $page      The 'page' number
	 *
	 * @return  mixed   true if there is an error. Void otherwise.
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		if ($this->isAdmin)
		{
			return;
		}

		preg_match_all('@{bfsharpspringform}.+{/bfsharpspringform}@', $article->text, $forms);
		if (!empty($forms))
		{
			foreach($forms as $form)
			{
				if (!empty($form))
				{
					$this->forms += $form;
				}
			}
		}
	}

	/**
	 * Implement the form
	 *
	 * @throws Exception
	 */
	public function onAfterRender() {
		if ($this->isAdmin)
		{
			return;
		}

		if (empty($this->forms))
		{
			return;
		}

		$app = Factory::getApplication();
		$body = $app->getBody();

		foreach($this->forms as $i=>$form)
		{
			$targetID = 'bfsharpspringform-' . $i;
			$embedCode = $this->getEmbedCode($targetID, $form);

			$target = '<div id="' . $targetID . '">';
			if ($demo = $this->params->get('demo'))
			{
				$target .= $this->getDemo($form, $embedCode);
			}
			$target .= '</div>';

			$body = str_replace($form, $target, $body);

			if (!$demo)
			{
				$body = str_replace('</body>', $embedCode . '</body>', $body);
			}
		}

		$app->setBody($body);
	}

	protected function getEmbedCode($targetID, $form)
	{
		$form = str_replace('{bfsharpspringform}', '', $form);
		$form = str_replace('{/bfsharpspringform}', '', $form);

		list($formID, $account, $domain, $width, $height, $returnUrl) = explode('|', $form);

		$returnUrl = trim($returnUrl);
		if (strpos($returnUrl, 'http://') !== 0 &&
			strpos($returnUrl, 'https://') !== 0)
		{
			$returnUrl = JRoute::_(Uri::base() . $returnUrl);
		}

		$embedCode = "
<script type=\"text/javascript\">
var ss_form = {'account': '" . trim($account) . "', 'formID': '" . trim($formID) . "'};
ss_form.width = '" . trim($width) . "';
ss_form.height = '" . trim($height) . "';
ss_form.domain = 'app-" . trim($domain) . ".marketingautomation.services';
" . (empty($returnUrl) ? "" : "") . "ss_form.hidden = {'returnURL': '" . $returnUrl . "'};
ss_form.target_id = '" . $targetID . "';
</script>
<script type=\"text/javascript\" src=\"https://koi-" . trim($domain) . ".marketingautomation.services/client/form.js?ver=2.0.1\"></script>
";

		return $embedCode;
	}

	protected function getDemo($form, $embedCode)
	{
		return '
<h4 style="text-align:center">SharpSpring Form Embedded in Joomla! Article Demo</h4>
<p>Based upon  example embed code taken from SharpSpring help article :<br/>
<a href="https://help.sharpspring.com/hc/en-us/articles/115001033887-Using-Form-Embed-Codes">
https://help.sharpspring.com/hc/en-us/articles/115001033887-Using-Form-Embed-Codes
</a>.
</p>
<pre>' . htmlspecialchars($embedCode) . '</pre>
The content (Joomla article, custom module etc.) containing the embedded SharpSpring form will include the following
Joomla! embedded content escape sequence :
<pre>' . str_replace('{', '&lbrace;', $form) . '</pre>
This Joomla! embedded content escape sequence should be embedded in a &lt;div&gt; element like this :
<pre>&lt;div&gt;{bfsharpspringform}...etc ...{/bfsharpspringform}&lt;/div&gt;</pre>';
	}
}
