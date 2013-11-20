<?php
/**
 * Curry CMS
 *
 * LICENSE
 *
 * This source file is subject to the GPL license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://currycms.com/license
 *
 * @category   Curry CMS
 * @package    Curry
 * @copyright  2011-2012 Bombayworks AB (http://bombayworks.se)
 * @license    http://currycms.com/license GPL
 * @link       http://currycms.com
 */

/**
 * The base class for all page-modules.
 * 
 * @package Curry\Module
 *
 */
abstract class Curry_Module
{
	/**
	 * The database object in which the module data is stored.
	 *
	 * @var int
	 */
	protected $moduleDataId;
	
	/**
	 * The PageGenerator which is being used to generate this module.
	 *
	 * @var Curry_PageGenerator
	 */
	protected $pageGenerator;
	
	/**
	 * An array of available modules.
	 *
	 * @var array
	 */
	private static $modules;
	
	/**
	 * Only serialize public/protected variables.
	 *
	 * @return array
	 */
	public function __sleep()
	{
		$fields = get_object_vars($this);
		unset($fields['moduleDataId']);
		unset($fields['pageGenerator']);
		return array_keys($fields);
	}
	
	/**
	 * Get a list of all available modules.
	 *
	 * @return array
	 */
	public static function getModuleList()
	{
		if(self::$modules)
			return self::$modules;
		
		// find all backend directories
		$dirs = glob(Curry_Util::path(Curry_Core::$config->curry->projectPath,'include','*','Module'), GLOB_ONLYDIR);
		if(!$dirs)
			$dirs = array();
		$dirs[] = Curry_Util::path(Curry_Core::$config->curry->basePath, 'include','Curry','Module');
		
		// find all php files in the directories
		self::$modules = array();
		foreach($dirs as $dir) {
			$it = new Curry_FileFilterIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)), '/\.php$/');
			foreach($it as $file) {
				$path = realpath($file->getPathname());
				$pos = strrpos($path, DIRECTORY_SEPARATOR."include".DIRECTORY_SEPARATOR);
				if($pos !== FALSE) {
					$className = str_replace(DIRECTORY_SEPARATOR, '_', substr($path, $pos + 9, -4));
					self::$modules[$className] = $className;
				}
			}
		}
		
		ksort(self::$modules);
		
		return self::$modules;
	}
	
	/**
	 * This function will be called when rendering a module. By default a
	 * template is required, and it will be rendered using the results from
	 * the toTwig() function.
	 *
	 * @param Curry_Twig_Template|null $template
	 * @return string	The content generated by the module.
	 */
	public function showFront(Curry_Twig_Template $template = null)
	{
		if(!$template)
			throw new Exception('A template is required for this module ('.get_class($this).').');
		return $template->render($this->toTwig());
	}
	
	/**
	 * Creates an associative array of values to be rendered by Twig.
	 * 
	 * @return array	The array to be rendered by Twig.
	 */
	public function toTwig()
	{
		return array();
	}
	
	/**
	 * Returns a template to use if no template is set in the Curry CMS backend.
	 * You only need to implement this if your module should have a default template.
	 * 
	 * @return string|null	Template string.
	 */
	public static function getDefaultTemplate()
	{
		return null;
	}
	
	/**
	 * Controls the back-end form. This function is supposed to return a
	 * Curry_Form_SubForm. If you don't want a backend for your module you
	 * dont need to implement this.
	 * 
	 * @return Curry_Form_SubForm|null
	 */
	public function showBack()
	{
		return null;
	}
	
	/**
	 * This function is automatically called when the form is saved.
	 *
	 * @param Zend_Form_SubForm $form
	 */
	public function saveBack(Zend_Form_SubForm $form)
	{
	}
	
	/**
	 * This function determine if the user should be allowed to select a template for
	 * this module. The default is true, override if you don't want it.
	 * 
	 * @return bool
	 */
	public static function hasTemplate()
	{
		return true;
	}
	
	/**
	 * Get a list of predefined templates. The keys will be used as names and the
	 * value is the actual template.
	 *
	 * @return string[]
	 */
	public static function getPredefinedTemplates()
	{
		return array();
	}
	
	/**
	 * Return an object describing how caching of this module is handled. Return
	 * null to disable caching.
	 *
	 * @return Curry_CacheProperties|null
	 */
	public function getCacheProperties()
	{
		return null;
	}
	
	/**
	 * Returns an array of commands to show for this module when viewed in
	 * inline-admin mode.
	 *
	 * @param array $commands
	 * @return array
	 */
	public function getInlineCommands($commands)
	{
		return $commands;
	}
	
	/**
	 * Get the ModuleDataId associated to this module instance.
	 *
	 * @return int
	 */
	public function getModuleDataId()
	{
		return $this->moduleDataId;
	}
	
	/**
	 * Get the ModuleData object associated to this module instance.
	 *
	 * @return ModuleData
	 */
	public function getModuleData()
	{
		return ModuleDataQuery::create()->findPk($this->moduleDataId);
	}
	
	/**
	 * Get the PageModule object associated to this module instance.
	 *
	 * @return PageModule
	 */
	public function getPageModule()
	{
		return $this->getModuleData()->getPageModule();
	}
	
	/**
	 * Get the PageModuleId associated to this module instance.
	 *
	 * @return int
	 */
	public function getPageModuleId()
	{
		return $this->getModuleData()->getPageModuleId();
	}
	
	/**
	 * Get the PageRevision object associated to this module instance.
	 *
	 * @return PageRevision
	 */
	public function getPageRevision()
	{
		return $this->getModuleData()->getPageRevision();
	}
	
	/**
	 * Set the module data id. This is the primary key of the ModuleData
	 * object used when writing the module to the database.
	 *
	 * @param int $id
	 */
	public function setModuleDataId($id)
	{
		$this->moduleDataId = $id;
	}
	
	/**
	 * Serializes this object and stores it in the database in the related ModuleData object.
	 * 
	 * @return bool Return true if the module was changed, otherwise false.
	 */
	public function saveModule()
	{
		if($this->moduleDataId === false)
			return false;
			
		if(!$this->moduleDataId)
			throw new Exception('Not allowed to save ModuleData.');
			
		$moduleData = $this->getModuleData();
		if(!$moduleData)
			throw new Exception('ModuleData not found.');
		
		$moduleData->setData(serialize($this));
		return $moduleData->save() ? true : false;
	}
	
	/**
	 * Associate a PageGenerator object with the module.
	 *
	 * @param Curry_PageGenerator $pageGenerator
	 */
	public function setPageGenerator($pageGenerator)
	{
		$this->pageGenerator = $pageGenerator;
	}

	/**
	 * Get the page generator class used to generate the current page.
	 *
	 * @return Curry_PageGenerator
	 */
	public function getPageGenerator()
	{
		return $this->pageGenerator;
	}
	
	/**
	 * Get the page generator class used to generate the current page.
	 *
	 * @return Curry_Request
	 */
	public function getRequest()
	{
		return $this->pageGenerator->getRequest();
	}
	
	/**
	 * Get the currently active backend module.
	 *
	 * @return Curry_Backend
	 */
	public function getBackend()
	{
		return Curry_Admin::getInstance()->getBackend();
	}
	
	/**
	 * Return json-data to browser and exit. Will set content-type header and encode the data.
	 * 
	 * @deprecated Please use Curry_Application::returnJson() instead.
	 *
	 * @param mixed $content	Data to encode with json_encode. Note that this must be utf-8 encoded. Strings will not be encoded.
	 */
	public function returnJson($content)
	{
		trace_warning('DEPRECATED: '.__CLASS__.'::'.__METHOD__.'(), please use Curry_Application::'.__METHOD__.'() instead.');
		Curry_Application::returnJson($content);
	}
	
	/**
	 * Return partial html-content to browser and exit. Will set content-type header and return the content.
	 *
	 * @deprecated Please use Curry_Application::returnPartial() instead.
	 * 
	 * @param string $content
	 * @param string $contentType
	 * @param string|null $charset
	 */
	public function returnPartial($content, $contentType = 'text/html', $charset = null)
	{
		trace_warning('DEPRECATED: '.__CLASS__.'::'.__METHOD__.'(), please use Curry_Application::'.__METHOD__.'() instead.');
		Curry_Application::returnPartial($content, $contentType, $charset);
	}

	/**
	 * Return partial a file to browser and exit. Will set appropriate headers and return the content.
	 * 
	 * @deprecated Please use Curry_Application::returnPartial() instead.
	 *
	 * @param string $file
	 * @param string $contentType
	 * @param string $filename
	 */
	public function returnFile($file, $contentType, $filename)
	{
		trace_warning('DEPRECATED: '.__CLASS__.'::'.__METHOD__.'(), please use Curry_Application::'.__METHOD__.'() instead.');
		Curry_Application::returnFile($file, $contentType, $filename);
	}
}
