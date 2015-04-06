<?php
/**
 * @package		Joomla.Site
 * @subpackage	plg_content_ogptag
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

jimport('joomla.utilities.date');

/**
 * An example custom ogptag plugin.
 *
 * @package		Joomla.Plugin
 * @subpackage	content.ogptag
 * @version		1.6
 */
class plgContentOgptag extends JPlugin
{
	/**
	 * Constructor
	 *
	 * @access      protected
	 * @param       object  $subject The object to observe
	 * @param       array   $config  An array that holds the plugin configuration
	 * @since       2.5
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	/**
	 * @param	string	$context	The context for the data
	 * @param	int		$data		The article id
	 * @param	object
	 *
	 * @return	boolean
	 * @since	2.5
	 */
	function onContentPrepareData($context, $data)
	{
		if (is_object($data))
		{
			$articleId = isset($data->id) ? $data->id : 0;
			if (!isset($data->ogptag) and $articleId > 0)
			{
				// Load the profile data from the database.
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select('article_id, ogptags');
				$query->from('#__content_ogptag');
				$query->where('article_id = ' . $db->Quote($articleId));
				$db->setQuery($query);
				$results = $db->loadObject();
				// Check for a database error.
				if ($db->getErrorNum())
				{
					$this->_subject->setError($db->getErrorMsg());
					return false;
				}

				// Merge the profile data.
				$data->ogptag = (array) json_decode($results->ogptags);
			}
		}

		return true;
	}

	/**
	 * @param	JForm	$form	The form to be altered.
	 * @param	array	$data	The associated data for the form.
	 *
	 * @return	boolean
	 * @since	2.5
	 */
	function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}

		// Add the extra fields to the form.
		// need a seperate directory for the installer not to consider the XML a package when "discovering"
		JForm::addFormPath(dirname(__FILE__) . '/ogptag');
		$form->loadFile('ogptag', false);

		return true;
	}

	/**
	 * Example after save content method
	 * Article is passed by reference, but after the save, so no changes will be saved.
	 * Method is called right after the content is saved
	 *
	 * @param	string		The context of the content passed to the plugin (added in 1.6)
	 * @param	object		A JTableContent object
	 * @param	bool		If the content is just about to be created
	 * @since	2.5
	 */
	public function onContentAfterSave($context, &$article, $isNew)
	{

		$articleId	= $article->id;
		if ($articleId && isset($article->ogptag) && (count($article->ogptag)))
		{
			try
			{
				$db = JFactory::getDbo();

				$query = $db->getQuery(true);
				$query->delete('#__content_ogptag');
				$query->where('article_id = ' . $db->Quote($articleId));
				$db->setQuery($query);
				if (!$db->query()) {
					throw new Exception($db->getErrorMsg());
				}

				$query->clear();

				$query->insert('#__content_ogptag');
				$order	= 1;
				$query->values("'',".$articleId.','.$db->quote(json_encode($article->ogptag)));
				$db->setQuery($query);
				if (!$db->query()) {
					throw new Exception($db->getErrorMsg());
				}
			}
			catch (JException $e)
			{
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}

		return true;
	}

	/**
	 * Finder after delete content method
	 * Article is passed by reference, but after the save, so no changes will be saved.
	 * Method is called right after the content is saved
	 *
	 * @param	string		The context of the content passed to the plugin (added in 1.6)
	 * @param	object		A JTableContent object
	 * @since   2.5
	 */
	public function onContentAfterDelete($context, $article)
	{
		
		$articleId	= $article->id;
		if ($articleId)
		{
			try
			{
				$db = JFactory::getDbo();

				$query = $db->getQuery(true);
				$query->delete();
				$query->from('#__content_ogptag');
				$query->where('article_id = ' . $db->Quote($articleId));
				$db->setQuery($query);

				if (!$db->query())
				{
					throw new Exception($db->getErrorMsg());
				}
			}
			catch (JException $e)
			{
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}

		return true;
	}
	
//Function for View on header section
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		if (!isset($article->ogptag) || !count($article->ogptag))
			return;

		// add extra css if needed
		$doc = JFactory::getDocument();
		$doc->addStyleSheet(JURI::base(true).'/plugins/content/ogptag/ogptag/ogptag.css');
		
		// construct a result for OGP Tag
			$ogpdata    = $article->ogptag;
			$opengraph  = '<meta property="og:locale" content="'.$ogpdata['ogplocale'].'"/>' ."\n";
			$opengraph .= '<meta property="og:site_name" content="'.$ogpdata['ogpsitename'].'"/>' ."\n";
			$opengraph .= '<meta property="og:title" content="'.$ogpdata['ogptitle'].'"/>' ."\n";
            $opengraph .= '<meta property="og:url" content="'.$ogpdata['ogpurl'].'"/>' ."\n";
            $opengraph .= '<meta property="og:type" content="'.$ogpdata['ogptype'].'"/>' ."\n";
            $opengraph .= '<meta property="og:description"  content="'.$ogpdata['ogpdescription'].'"/>' ."\n";
            $opengraph .= '<meta property="og:image"  content="'. $ogpdata['ogpimage'].'"/>' ."\n";
            
		//add the tags to the head of the page;
		$doc->addCustomTag($opengraph);

	}
	
}
