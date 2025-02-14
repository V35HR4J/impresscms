<?php
/**
 * \ImpressCMS\Core\IPF\AbstractModel Table Listing
 *
 * Contains the classes responsible for displaying a highly configurable and features rich listing of IcmseristableObject objects
 *
 * @copyright    The ImpressCMS Project http://www.impresscms.org/
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since    1.1
 * @author    marcan <marcan@impresscms.org>
 */

namespace ImpressCMS\Core\View\Table;

use Exception;
use icms;
use Imponeer\Database\Criteria\CriteriaCompo;
use Imponeer\Database\Criteria\CriteriaElement;
use Imponeer\Database\Criteria\CriteriaItem;
use Imponeer\Database\Criteria\Enum\Order;
use ImpressCMS\Core\Models\AbstractExtendedHandler;
use ImpressCMS\Core\View\PageNav;
use ImpressCMS\Core\View\Template;
use SmartyException;
use UnexpectedValueException;

/**
 * ViewTable base class
 *
 * Base class representing a table for displaying \ImpressCMS\Core\IPF\AbstractModel objects
 *
 * @copyright    The ImpressCMS Project http://www.impresscms.org/
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package    ICMS\IPF\View
 * @since    1.1
 * @author    marcan <marcan@impresscms.org>
 * @todo    Properly declare all protected s with their visibility (private, protected, public) and follow naming convention
 */
class Table {

	protected $_id;
	protected $_objectHandler;
	protected $_columns;
	protected $_criteria;
	protected $_actions;
	protected $_objects = false;
	protected $_aObjects;
	protected $_custom_actions;
	protected $_sortsel;
	protected $_ordersel;
	protected $_limitsel;
	protected $_filtersel;
	protected $_filterseloptions;
	protected $_filtersel2;
	protected $_filtersel2options;
	protected $_filtersel2optionsDefault;

	protected $_tempObject;
	protected $_tpl;
	protected $_introButtons;
	protected $_quickSearch = false;
	protected $_actionButtons = false;
	protected $_head_css_class = 'bg3';
	protected $_hasActions = false;
	protected $_userSide = false;
	protected $_printerFriendlyPage = false;
	protected $_tableHeader = false;
	protected $_tableFooter = false;
	protected $_showActionsColumnTitle = true;
	protected $_isTree = false;
	protected $_showFilterAndLimit = true;
	protected $_enableColumnsSorting = true;
	protected $_customTemplate = false;
	protected $_withSelectedActions = [];

	/**
	 * Constructor
	 *
	 * @param AbstractExtendedHandler $objectHandler Handler
	 * @param false|CriteriaElement $criteria Criteria
	 * @param string[] $actions array representing the actions to offer
	 * @param bool $userSide For user side?
	 */
	public function __construct(&$objectHandler, $criteria = false, $actions = ['edit', 'delete'], $userSide = false) {
		$this->_id = $objectHandler->className;
		$this->_objectHandler = $objectHandler;

		if (!$criteria) {
			$criteria = new CriteriaCompo();
		}
		$this->_criteria = $criteria;
		$this->_actions = $actions;
		$this->_custom_actions = [];
		$this->_userSide = $userSide;
		if ($userSide) {
			$this->_head_css_class = 'head';
		}
	}

	/**
	 * Magic getter to make some variables for class read-only from outside
	 *
	 * @param string $name Variable name
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	public function __get($name) {
		if (isset($this->$name)) {
			trigger_error(sprintf('Accessing variable %s from outside IPF Table class was deprecated', $name), E_USER_DEPRECATED);
			return $this->$name;
		}

		throw new Exception(sprintf('%s variable for %s doesn\'t exists', $name, __CLASS__));
	}

	/**
	 *
	 * @param $op
	 * @param $caption
	 * @param $text
	 */
	public function addActionButton($op, $caption = false, $text = false) {
		$this->_actionButtons[] = compact('op', 'caption', 'text');
	}

	/**
	 *
	 * @param $columnObj
	 */
	public function addColumn($columnObj) {
		$this->_columns[] = $columnObj;
	}

	/**
	 *
	 * @param $name
	 * @param $location
	 * @param $value
	 */
	public function addIntroButton($name, $location, $value) {
		$this->_introButtons[] = compact('name', 'location', 'value');
	}

	/**
	 *
	 */
	public function addPrinterFriendlyLink() {
		$current_url = icms::$urls['full'];
		$this->_printerFriendlyPage = $current_url . '&print';
	}

	/**
	 *
	 * @param $fields
	 * @param $caption
	 */
	public function addQuickSearch($fields, $caption = _CO_ICMS_QUICK_SEARCH) {
		$this->_quickSearch = compact('fields', 'caption');
	}

	/**
	 *
	 * @param unknown_type $content
	 */
	public function addHeader($content) {
		$this->_tableHeader = $content;
	}

	/**
	 *
	 * @param $content
	 */
	public function addFooter($content) {
		$this->_tableFooter = $content;
	}

	/**
	 *
	 * @param $caption
	 */
	public function addDefaultIntroButton($caption) {
		$this->addIntroButton($this->_objectHandler->itemName, $this->_objectHandler->_page . "?op=mod", $caption);
	}

	/**
	 *
	 * @param $method
	 */
	public function addCustomAction($method) {
		$this->_custom_actions[] = $method;
	}

	/**
	 *
	 * @param $default_sort
	 */
	public function setDefaultSort($default_sort) {
		$this->_sortsel = $default_sort;
	}

	/**
	 * Gets default sort
	 */
	public function getDefaultSort(): string {
		return $this->getCookie(
			$this->_id . '_sortsel',
			$this->_sortsel ?: $this->_objectHandler->identifierName
		);
	}

	/**
	 *
	 * @param unknown_type $default_order
	 */
	public function setDefaultOrder($default_order) {
		$this->_ordersel = $default_order;
	}

	/**
	 * Gets default order
	 */
	public function getDefaultOrder(): string {
		return $this->getCookie($this->_id . '_ordersel', $this->_ordersel ?: 'ASC');
	}

	/**
	 *
	 * @param $actions
	 */
	public function addWithSelectedActions($actions = []) {
		$this->addColumn(new Column('checked', 'center', 20, false, false, '&nbsp;'));
		$this->_withSelectedActions = $actions;
	}

	/**
	 * Adding a filter in the table
	 *
	 * @param string $key key to the field that will be used for sorting
	 * @param string $method method of the handler that will be called to populate the options when this filter is selected
	 */
	public function addFilter($key, $method, $default = false) {
		$this->_filterseloptions[$key] = $method;
		$this->_filtersel2optionsDefault = $default;
	}

	/**
	 *
	 * @param $default_filter
	 */
	public function setDefaultFilter($default_filter) {
		$this->_filtersel = $default_filter;
	}

	/**
	 *
	 */
	public function isForUserSide() {
		$this->_userSide = true;
	}

	/**
	 *
	 * @param $template
	 */
	public function setCustomTemplate($template) {
		$this->_customTemplate = $template;
	}

	/**
	 *
	 */
	public function setSortOrder() {
		$this->_sortsel = $_GET[$this->_objectHandler->itemName . '_' . 'sortsel'] ?? $this->getDefaultSort();
		//$this->_sortsel = isset($_POST['sortsel']) ? $_POST['sortsel'] : $this->_sortsel;

		$this->setCookie($this->_id . '_sortsel', $this->_sortsel);
		$fieldsForSorting = $this->_tempObject->getFieldsForSorting($this->_sortsel);

		if ($this->_tempObject->getVarInfo($this->_sortsel, 'itemName')) {
			$this->_criteria->setSort($this->_tempObject->getVarInfo($this->_sortsel, 'itemName') . '.' . $this->_sortsel);
		} else {
			$this->_criteria->setSort($this->_objectHandler->itemName . "." . $this->_sortsel);
		}

		$this->_ordersel = $_GET[$this->_objectHandler->itemName . '_' . 'ordersel'] ?? $this->getDefaultOrder();
		//$this->_ordersel = isset($_POST['ordersel']) ? $_POST['ordersel'] :$this->_ordersel;
		$this->setCookie($this->_id . '_ordersel', $this->_ordersel);
		$this->getOrdersArray();

		try {
			$this->_criteria->setOrder($this->_ordersel);
		} catch (UnexpectedValueException $exception) {
			$this->_criteria->setOrder(
				Order::ASC()
			);
		}
	}

	/**
	 *
	 * @param $id
	 */
	public function setTableId($id) {
		$this->_id = $id;
	}

	/**
	 *
	 * @param $objects
	 */
	public function setObjects($objects) {
		$this->_objects = $objects;
	}

	/**
	 *
	 */
	public function createTableRows() {
		$this->_aObjects = [];

		$doWeHaveActions = false;

		$objectclass = 'odd';

		if (count($this->_objects) > 0) {
			foreach ($this->_objects as $object) {

				$aObject = [];

				$i = 0;

				$aColumns = [];

				foreach ($this->_columns as $column) {

					$aColumn = [];

					if ($i === 0) {
						$class = 'head';
					} elseif ($i % 2 == 0) {
						$class = 'even';
					} else {
						$class = 'odd';
					}
					if (method_exists($object, 'initiateCustomFields')) {
						//$object->initiateCustomFields();
					}
					if ($column->_keyname === 'checked') {
						$value = '<input type ="checkbox" name="selected_icms_persistableobjects[]" value="' . $object->id() . '" />';
					} elseif ($column->_customMethodForValue && method_exists($object, $column->_customMethodForValue)) {
						$method = $column->_customMethodForValue;
						if ($column->_param) {
							$value = $object->$method($column->_param);
						} else {
							$value = $object->$method();
						}
					} else {
						/**
						 * If the column is the identifier, then put a link on it
						 */
						if ($column->getKeyName() === $this->_objectHandler->identifierName) {
							$value = $object->getViewItemLink(false, false, $this->_userSide);
						} else {
							$value = $object->getVar($column->getKeyName());
						}
					}

					$aColumn['keyname'] = $column->getKeyName();
					$aColumn['value'] = $value;
					$aColumn['class'] = $class;
					$aColumn['width'] = $column->getWidth();
					$aColumn['align'] = $column->getAlign();

					$aColumns[] = $aColumn;
					$i++;
				}

				$aObject['columns'] = $aColumns;
				$aObject['id'] = $object->id();

				$objectclass = ($objectclass === 'even') ? 'odd' : 'even';

				$aObject['class'] = $objectclass;

				$actions = [];

				// Adding the custom actions if any
				foreach ($this->_custom_actions as $action) {
					if (method_exists($object, $action)) {
						$actions[] = $object->$action();
					}
				}

				if ((!is_array($this->_actions)) || in_array('edit', $this->_actions, true)) {
					$actions[] = $object->getEditItemLink(false, true, $this->_userSide);
				}
				if ((!is_array($this->_actions)) || in_array('delete', $this->_actions, true)) {
					$actions[] = $object->getDeleteItemLink(false, true, $this->_userSide);
				}
				$aObject['actions'] = $actions;

				$this->_tpl->assign('icms_actions_column_width', count($actions) * 30);

				$doWeHaveActions = $doWeHaveActions ? true : count($actions) > 0;

				$this->_aObjects[] = $aObject;
			}
			$this->_tpl->assign('icms_persistable_objects', $this->_aObjects);
		} else {
			$colspan = count($this->_columns) + 1;
			$this->_tpl->assign('icms_colspan', $colspan);
		}
		$this->_hasActions = $doWeHaveActions;
	}

	/**
	 *
	 * @param unknown_type $debug
	 * @return array
	 */
	public function fetchObjects($debug = false) {
		return $this->_objectHandler->getObjects($this->_criteria, true, true, false, $debug);
	}

	/**
	 * Gets default filter
	 */
	public function getDefaultFilter() {
		return $this->getCookie($this->_id . '_filtersel', $this->_filtersel ?: 'default');
	}

	/**
	 *
	 */
	public function getFiltersArray() {
		$ret = [];
		$field = [];
		$field['caption'] = _CO_ICMS_NONE;
		$field['selected'] = '';
		$ret['default'] = $field;
		unset($field);

		if ($this->_filterseloptions) {
			foreach ($this->_filterseloptions as $key => $value) {
				$field = [];
				if (is_array($value)) {
					$field['caption'] = $key;
					$field['selected'] = $this->_filtersel == $key ? "selected='selected'" : '';
				} else {
					$field['caption'] = $this->_tempObject->getVarInfo($key, 'form_caption');
					$field['selected'] = $this->_filtersel == $key ? "selected='selected'" : '';
				}
				$ret[$key] = $field;
				unset($field);
			}
		} else {
			$ret = false;
		}
		return $ret;
	}

	/**
	 *
	 * @param unknown_type $default_filter2
	 */
	public function setDefaultFilter2($default_filter2) {
		$this->_filtersel2 = $default_filter2;
	}

	/**
	 * Gets default filter2
	 */
	public function getDefaultFilter2() {
		return $this->getCookie('filtersel2', $this->_filtersel2 ?: 'default');
	}

	/**
	 *
	 */
	public function getFilters2Array() {
		$ret = [];

		foreach ($this->_filtersel2options as $key => $value) {
			$field = [];
			$field['caption'] = $value;
			$field['selected'] = $this->_filtersel2 === $key ? "selected='selected'" : '';
			$ret[$key] = $field;
			unset($field);
		}
		return $ret;
	}

	/**
	 *
	 * @param $limitsArray
	 * @param $params_of_the_options_sel
	 */
	public function renderOptionSelection($limitsArray, $params_of_the_options_sel) {
		// Rendering the form to select options on the table
		$current_url = icms::$urls['full'];

		/**
		 * What was $params_of_the_options_sel doing again ?
		 */
		//$this->_tpl->assign('icms_optionssel_action', $_SERVER['SCRIPT_NAME'] . "?" . implode('&', $params_of_the_options_sel));
		$this->_tpl->assign('icms_optionssel_action', $current_url);
		$this->_tpl->assign('icms_optionssel_limitsArray', $limitsArray);
	}

	/**
	 *
	 */
	public function getLimitsArray() {
		$ret = [];
		$ret['all']['caption'] = _CO_ICMS_LIMIT_ALL;
		$ret['all']['selected'] = ('all' == $this->_limitsel) ? "selected='selected'" : '';

		$ret['5']['caption'] = icms_conv_nr2local('5');
		$ret['5']['selected'] = ('5' === (string)$this->_limitsel) ? "selected='selected'" : '';

		$ret['10']['caption'] = icms_conv_nr2local('10');
		$ret['10']['selected'] = ('10' === (string)$this->_limitsel) ? "selected='selected'" : '';

		$ret['15']['caption'] = icms_conv_nr2local('15');
		$ret['15']['selected'] = ('15' === (string)$this->_limitsel) ? "selected='selected'" : '';

		$ret['20']['caption'] = icms_conv_nr2local('20');
		$ret['20']['selected'] = ('20' === (string)$this->_limitsel) ? "selected='selected'" : '';

		$ret['25']['caption'] = icms_conv_nr2local('25');
		$ret['25']['selected'] = ('25' === (string)$this->_limitsel) ? "selected='selected'" : '';

		$ret['30']['caption'] = icms_conv_nr2local('30');
		$ret['30']['selected'] = ('30' === (string)$this->_limitsel) ? "selected='selected'" : '';

		$ret['35']['caption'] = icms_conv_nr2local('35');
		$ret['35']['selected'] = ('35' === (string)$this->_limitsel) ? "selected='selected'" : '';

		$ret['40']['caption'] = icms_conv_nr2local('40');
		$ret['40']['selected'] = ('40' === (string)$this->_limitsel) ? "selected='selected'" : '';
		return $ret;
	}

	/**
	 *
	 */
	public function getObjects() {
		return $this->_objects;
	}

	/**
	 *
	 */
	public function hideActionColumnTitle() {
		$this->_showActionsColumnTitle = false;
	}

	/**
	 *
	 */
	public function hideFilterAndLimit() {
		$this->_showFilterAndLimit = false;
	}

	/**
	 *
	 */
	public function getOrdersArray() {
		$ret = [];
		$ret['ASC']['caption'] = _CO_ICMS_SORT_ASC;
		$ret['ASC']['selected'] = ('ASC' === $this->_ordersel) ? "selected='selected'" : '';

		$ret['DESC']['caption'] = _CO_ICMS_SORT_DESC;
		$ret['DESC']['selected'] = ('DESC' === $this->_ordersel) ? "selected='selected'" : '';

		return $ret;
	}

	/**
	 *
	 */
	public function renderD() {
		return $this->render(false, true);
	}

	/**
	 *
	 */
	public function renderForPrint() {

	}

	/**
	 * Gets from cookie
	 *
	 * @param string $fieldName Field name read from cookie
	 * @param string|null $defaultValue Default value
	 *
	 * @return string
	 */
	protected function getCookie(string $fieldName, ?string $defaultValue = null) {
		$name = 'tbl_' . str_replace('.', '_', $fieldName);

		return $_COOKIE[$name] ?? $defaultValue;
	}

	/**
	 * Sets cookie
	 *
	 * @param string $fieldName
	 * @param string $value
	 */
	protected function setCookie(string $fieldName, string $value) {
		setcookie(
			'tbl_' . $fieldName,
			$value,
			time() + 3600 * 24 * 365,
			parse_url(ICMS_URL, PHP_URL_PATH),
			parse_url(ICMS_URL, PHP_URL_HOST),
			false,
			true
		);
	}

	/**
	 *
	 * @param $fetchOnly
	 * @param $debug
	 * @return
	 * @throws SmartyException
	 */
	public function render($fetchOnly = false, $debug = false) {
		$this->_tpl = new Template();

		/**
		 * We need access to the protected s of the \ImpressCMS\Core\IPF\AbstractModel for a few things in the table creation.
		 * Since we may not have an \ImpressCMS\Core\IPF\AbstractModel to look into now, let's create one for this purpose
		 * and we will free it after
		 */
		$this->_tempObject = &$this->_objectHandler->create();

		$this->_criteria->setStart(isset($_GET['start' . $this->_objectHandler->keyName]) ? (int)($_GET['start' . $this->_objectHandler->keyName]) : 0);

		$this->setSortOrder();

		if (!$this->_isTree) {
			$this->_limitsel = $_GET['limitsel'] ?? $this->getCookie('limitsel', '15');
		} else {
			$this->_limitsel = 'all';
		}

		$this->_limitsel = $_POST['limitsel'] ?? $this->_limitsel;
		$this->setCookie('limitsel', $this->_limitsel);
		$limitsArray = $this->getLimitsArray();
		$this->_criteria->setLimit($this->_limitsel);

		$this->_filtersel = $_GET['filtersel'] ?? $this->getDefaultFilter();
		$this->_filtersel = $_POST['filtersel'] ?? $this->_filtersel;
		$this->setCookie($this->_id . '_filtersel', $this->_filtersel);
		$filtersArray = $this->getFiltersArray();

		if ($filtersArray) {
			$this->_tpl->assign('icms_optionssel_filtersArray', $filtersArray);
		}

		// Check if the selected filter is defined and if so, create the selfilter2
		if (isset($this->_filterseloptions[$this->_filtersel])) {
			// check if method associate with this filter exists in the handler
			if (is_array($this->_filterseloptions[$this->_filtersel])) {
				$filter = $this->_filterseloptions[$this->_filtersel];
				$this->_criteria->add($filter['criteria']);
			} else if (method_exists($this->_objectHandler, $this->_filterseloptions[$this->_filtersel])) {
				// then we will create the selfilter2 options by calling this method
				$method = $this->_filterseloptions[$this->_filtersel];
				$this->_filtersel2options = $this->_objectHandler->$method();

				$this->_filtersel2 = $_GET['filtersel2'] ?? $this->getDefaultFilter2();
				$this->_filtersel2 = $_POST['filtersel2'] ?? $this->_filtersel2;

				$filters2Array = $this->getFilters2Array();
				$this->_tpl->assign('icms_optionssel_filters2Array', $filters2Array);

				$this->setCookie('filtersel2', $this->_filtersel2);
				if ($this->_filtersel2 !== 'default') {
					$this->_criteria->add(new CriteriaItem($this->_filtersel, $this->_filtersel2));
				}
			}
		}
		// Check if we have a quicksearch

		if (isset($_POST['quicksearch_' . $this->_id]) && $_POST['quicksearch_' . $this->_id]) {
			$quicksearch_criteria = new CriteriaCompo();
			if (is_array($this->_quickSearch['fields'])) {
				foreach ($this->_quickSearch['fields'] as $v) {
					$quicksearch_criteria->add(new CriteriaItem($v, '%' . $_POST['quicksearch_' . $this->_id] . '%', 'LIKE'), 'OR');
				}
			} else {
				$quicksearch_criteria->add(new CriteriaItem($this->_quickSearch['fields'], '%' . $_POST['quicksearch_' . $this->_id] . '%', 'LIKE'));
			}
			$this->_criteria->add($quicksearch_criteria);
		}

		$this->_objects = $this->fetchObjects($debug);

		/**
		 * $params_of_the_options_sel is an array with all the parameters of the page
		 * but without the pagenave parameters. This array will be used in the
		 * OptionsSelection
		 */
		$params_of_the_options_sel = [];
		if ($this->_criteria->getLimit() > 0) {

			/**
			 * Geeting rid of the old params
			 * $new_get_array is an array containing the new GET parameters
			 */
			$new_get_array = [];

			$not_needed_params = ['sortsel', 'limitsel', 'ordersel', 'start' . $this->_objectHandler->keyName];
			foreach ($_GET as $k => $v) {
				if (!in_array($k, $not_needed_params, true)) {
					$new_get_array[] = "$k=$v";
					$params_of_the_options_sel[] = "$k=$v";
				}
			}

			/**
			 * Adding the new params of the pagenav
			 */
			$new_get_array[] = 'sortsel=' . $this->_sortsel;
			$new_get_array[] = 'ordersel=' . $this->_ordersel;
			$new_get_array[] = 'limitsel=' . $this->_limitsel;
			$otherParams = implode('&', $new_get_array);

			$pagenav = new PageNav($this->_objectHandler->getCount($this->_criteria), $this->_criteria->getLimit(), $this->_criteria->getStart(), 'start' . $this->_objectHandler->keyName, $otherParams);
			$this->_tpl->assign('icms_pagenav', $pagenav->renderNav());
		}
		$this->renderOptionSelection($limitsArray, $params_of_the_options_sel);

		// retreive the current url and the query string
		$current_url = icms::$urls['full_phpself'];
		$query_string = icms::$urls['querystring'];
		if ($query_string) {
			$query_string = str_replace('?', '', $query_string);
		}
		$query_stringArray = explode('&', $query_string);
		$new_query_stringArray = [];
		foreach ($query_stringArray as $query_string) {
			if (strpos($query_string, 'sortsel') === false && strpos($query_string, 'ordersel') === false) {
				$new_query_stringArray[] = $query_string;
			}
		}
		$new_query_string = implode('&', $new_query_stringArray);

		$orderArray = [];
		$orderArray['ASC']['image'] = 'desc.png';
		$orderArray['ASC']['neworder'] = 'DESC';
		$orderArray['DESC']['image'] = 'asc.png';
		$orderArray['DESC']['neworder'] = 'ASC';

		$aColumns = [];

		foreach ($this->_columns as $column) {
			$aColumn = [];
			$aColumn['width'] = $column->getWidth();
			$aColumn['align'] = $column->getAlign();
			$aColumn['key'] = $column->getKeyName();

			if ($column->_keyname === 'checked') {
				$aColumn['caption'] = '<input type ="checkbox" id="checkall_icmspersistableobjects" name="checkall_icmspersistableobjects"' .
					' value="checkall_icmspersistableobjects" onclick="icms_checkall(window.document.form_' . $this->_id . ', \'selected_icmspersistableobjects\');" />';
			} elseif ($column->getCustomCaption()) {
				$aColumn['caption'] = $column->getCustomCaption();
			} else {
				$aColumn['caption'] = $this->_tempObject->getVarInfo($column->getKeyName(), 'form_caption') ?: $column->getKeyName();
			}
			// Are we doing a GET sort on this column ?
			$getSort = (isset($_GET[$this->_objectHandler->itemName . '_' . 'sortsel']) && $_GET[$this->_objectHandler->itemName . '_' . 'sortsel'] == $column->getKeyName()) || ($this->_sortsel == $column->getKeyName());
			$order = $_GET[$this->_objectHandler->itemName . '_' . 'ordersel'] ?? 'DESC';

			if (isset($_REQUEST['quicksearch_' . $this->_id]) && $_REQUEST['quicksearch_' . $this->_id]) {
				$filter = isset($_POST['quicksearch_' . $this->_id]) ? INPUT_POST : INPUT_GET;
				$qs_param = '&amp;quicksearch_' . $this->_id . '=' . filter_input($filter, 'quicksearch_' . $this->_id, FILTER_SANITIZE_SPECIAL_CHARS);
			} else {
				$qs_param = '';
			}
			if (!$this->_enableColumnsSorting || $column->_keyname === 'checked' || !$column->_sortable) {
				// $aColumn['caption'] = $aColumn['caption'];
			} elseif ($getSort) {
				$aColumn['caption'] = '<a href="' . $current_url . '?' . $this->_objectHandler->itemName . '_' . 'sortsel=' . $column->getKeyName() . '&amp;' . $this->_objectHandler->itemName . '_' . 'ordersel=' . $orderArray[$order]['neworder'] . $qs_param . '&amp;' . $new_query_string . '">' . $aColumn['caption'] . ' <img src="' . ICMS_IMAGES_SET_URL . '/actions/' . $orderArray[$order]['image'] . '" alt="ASC" /></a>';
			} else {
				$aColumn['caption'] = '<a href="' . $current_url . '?' . $this->_objectHandler->itemName . '_' . 'sortsel=' . $column->getKeyName() . '&amp;' . $this->_objectHandler->itemName . '_' . 'ordersel=ASC' . $qs_param . '&amp;' . $new_query_string . '">' . $aColumn['caption'] . '</a>';
			}
			$aColumns[] = $aColumn;
		}
		$this->_tpl->assign('icms_columns', $aColumns);

		if ($this->_quickSearch) {
			$this->_tpl->assign('icms_quicksearch', $this->_quickSearch['caption']);
		}

		$this->createTableRows();

		$this->_tpl->assign('icms_showFilterAndLimit', $this->_showFilterAndLimit);
		$this->_tpl->assign('icms_isTree', $this->_isTree);
		$this->_tpl->assign('icms_show_action_column_title', $this->_showActionsColumnTitle);
		$this->_tpl->assign('icms_table_header', $this->_tableHeader);
		$this->_tpl->assign('icms_table_footer', $this->_tableFooter);
		$this->_tpl->assign('icms_printer_friendly_page', $this->_printerFriendlyPage);
		$this->_tpl->assign('icms_user_side', $this->_userSide);
		$this->_tpl->assign('icms_has_actions', $this->_hasActions);
		$this->_tpl->assign('icms_head_css_class', $this->_head_css_class);
		$this->_tpl->assign('icms_actionButtons', $this->_actionButtons);
		$this->_tpl->assign('icms_introButtons', $this->_introButtons);
		$this->_tpl->assign('icms_id', $this->_id);
		if (!empty($this->_withSelectedActions)) {
			$this->_tpl->assign('icms_withSelectedActions', $this->_withSelectedActions);
		}

		$icms_table_template = $this->_customTemplate ?: 'system_persistabletable_display.html';
		if ($fetchOnly) {
			return $this->_tpl->fetch('db:' . $icms_table_template);
		}

		$this->_tpl->display('db:' . $icms_table_template);
	}

	/**
	 *
	 */
	public function disableColumnsSorting() {
		$this->_enableColumnsSorting = false;
	}

	/**
	 *
	 * @param $debug
	 * @return string
	 *
	 * @throws SmartyException
	 */
	public function fetch($debug = false) {
		return $this->render(true, $debug);
	}
}

