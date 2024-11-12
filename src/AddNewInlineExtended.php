<?php

namespace LeKoala\GridFieldUtils;

use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_SaveHandler;
use SilverStripe\Forms\GridField\GridField_URLHandler;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Core\Flushable;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\HiddenField;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\Requirements;
use SilverStripe\ORM\DB;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Validator;
use SilverStripe\ORM\HasManyList;
use Psr\SimpleCache\CacheInterface;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class AddNewInlineExtended extends RequestHandler implements GridField_HTMLProvider, GridField_SaveHandler, GridField_URLHandler, Flushable
{
    /**
     * @var string
     */
    public $urlSegment = 'extendedInline';

    /**
     * @var bool
     */
    public $loadViaAjax = true;

    /**
     * @var bool
     */
    public $cacheAjaxLoading = true;

    /**
     * @var bool
     */
    public $hideUnlessOpenedWithEditableColumns = true;

    /**
     * @var bool
     */
    public $ignoreEditableColumns = false;

    /**
     * @var bool
     */
    public $openToggleByDefault = false;

    /**
     * @var bool
     */
    public $prepend = false;

    public $rowTemplate;

    /**
     * @var string
     */
    public $setWorkingParentOnRecordTo = 'Parent';

    protected $itemEditFormCallback;

    protected $fragment;

    protected $title;

    protected $fields;

    protected $template;

    protected $validator;

    /**
     * @var string
     */
    protected $buttonTemplate = 'GridField_AddNewInlineExtended_Button';

    /**
     * @var GridField
     */
    private $workingGrid;

    /**
     * @var CacheInterface
     */
    private $cache;

    private static $allowed_actions = [
        'loadItem',
        'handleForm',
    ];

    public static function flush()
    {
        singleton(__CLASS__)->cleanCache();
    }

    public function cleanCache()
    {
        if ($this->cache) {
            $this->cache->clear();
        }
    }

    /**
     * @param string $fragment the fragment to render the button in
     * @param string $title the text to display on the button
     * @param \FieldList|Callable|array $fields the fields to display in inline form
     */
    public function __construct($fragment = 'buttons-before-left', $title = '', $fields = null)
    {
        parent::__construct();
        $this->fragment = $fragment;
        $this->title = $title ?: _t('GridFieldExtensions.ADD', 'Add');
        $this->fields = $fields;
    }

    public function getCache(): ?CacheInterface
    {
        if (!$this->cache) {
            // $this->cache = Injector::inst()->get(CacheInterface::class . '.gridUtils');
        }
        return $this->cache;
    }

    public function getURLHandlers($gridField)
    {
        return [
            $this->urlSegment . '/load'   => 'loadItem',
            $this->urlSegment . '/$Form!' => 'handleForm',
        ];
    }

    /**
     * Gets the fragment name this button is rendered into.
     *
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Sets the fragment name this button is rendered into.
     *
     * @param string $fragment
     * @return static $this
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;

        return $this;
    }

    /**
     * Gets the button title text.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the button title text.
     *
     * @param string $title
     * @return static $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the fields for this class
     *
     * @return \FieldList|Callable|array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Sets the fields that will be displayed in this component
     *
     * @param \FieldList|Callable|array $fields
     * @return static $this
     */
    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Gets the validator
     *
     * @return \Validator|Callable|array
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Sets the validator that will be displayed in this component
     *
     * @param \Validator|Callable|array $validator
     * @return static $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Get the callback for changes on the edit form after constructing it
     *
     * @return callable
     */
    public function getItemEditFormCallback()
    {
        return $this->itemEditFormCallback;
    }

    /**
     * Make changes on the edit form after constructing it.
     *
     * @param callable $itemEditFormCallback
     * @return static $this
     */
    public function setItemEditFormCallback($itemEditFormCallback)
    {
        $this->itemEditFormCallback = $itemEditFormCallback;

        return $this;
    }

    public function getHTMLFragments($grid)
    {
        if (!$this->canCreate($grid)) {
            return [];
        }

        $this->workingGrid = $grid;

        $grid->addExtraClass('ss-gridfield-add-inline-extended--table');
        $grid->setAttribute('data-prepend', $this->prepend);

        $fragments = [
            $this->getFragment() => $this->getButtonFragment($grid),
        ];

        if (!$this->loadViaAjax) {
            Requirements::javascript('symbiote/silverstripe-gridfieldextensions:javascript/tmpl.js');
            $fragments['after'] = isset($fragments['after']) ? $fragments['after'] . $this->getRowTemplate($grid) : $this->getRowTemplate($grid);
        }

        Utilities::include_requirements();

        return $fragments;
    }

    protected function getButtonFragment($grid)
    {
        return ArrayData::create([
            'Title' => $this->getTitle(),
            'Ajax'  => $this->loadViaAjax,
            'Link'  => $this->loadViaAjax ? $this->Link('load') : '',
        ])->renderWith($this->buttonTemplate);
    }

    protected function getRowTemplate($grid)
    {
        return ArrayData::create($this->getRowTemplateVariables($grid))
            ->renderWith(array_merge((array)$this->template, ['GridField_AddNewInlineExtended']));
    }

    protected function getRowTemplateVariables($grid, $placeholder = '{%=o.num%}', $modelClass = '')
    {
        $form = $this->getForm(
            $grid,
            '-' . $placeholder,
            true,
            $modelClass
        )->setHTMLID('Form-' . $this->getComponentName() . '-' . $placeholder);

        if ($modelClass && !$form->Fields()->dataFieldByName('_modelClass')) {
            $form->Fields()->push(HiddenField::create('_modelClass', '', $modelClass));
        }

        $fields = $form->Fields()->dataFields();
        $editableColumnsTemplate = null;
        $countUntilThisColumn = 0;

        foreach ($fields as $field) {
            $field->setName(str_replace(
                ['$gridfield', '$class', '$placeholder', '$field'],
                [$grid->getName(), $this->getComponentName(), $placeholder, $field->getName()],
                '$gridfield[$class][$placeholder][$field]'
            ));
        }

        $editableColumns = $grid->Config->getComponentByType(GridFieldEditableColumns::class);
        if ($this->canEditWithEditableColumns($grid) && $editableColumns) {
            $currentModelClass = $grid->getModelClass();
            $grid->setModelClass($modelClass);
            $ecFragments = (new GridFieldAddNewInlineButton())->getHTMLFragments($grid);
            $grid->setModelClass($currentModelClass);
            $toggleClasses = $this->openToggleByDefault ? ' ss-gridfield-add-inline-extended--toggle_open' : '';

            $editableColumnsTemplate = str_replace(
                [
                    'GridFieldAddNewInlineButton',
                    'GridFieldEditableColumns',
                    '{%=o.num%}',
                    'ss-gridfield-editable-row--icon-holder">',
                    'ss-gridfield-inline-new"',
                    'ss-gridfield-delete-inline',
                ],
                [
                    $this->getComponentName(),
                    $this->getComponentName(),
                    $placeholder,
                    sprintf(
                        'ss-gridfield-editable-row--icon-holder"><i class="ss-gridfield-add-inline-extended--toggle%s"></i>',
                        $toggleClasses
                    ),
                    'ss-gridfield-inline-new-extended" data-inline-new-extended-row="' . $placeholder . '"',
                    'ss-gridfield-inline-new-extended--row-delete',
                ],
                str_replace([
                    '<script type="text/x-tmpl" class="ss-gridfield-add-inline-template">',
                    '</script>',
                ], '', $ecFragments['after'])
            );
        }

        return [
            'EditableColumns'           => $editableColumnsTemplate,
            'OpenByDefault'             => $this->openToggleByDefault,
            'Form'                      => $form,
            'AllColumnsCount'           => count($grid->getColumns()),
            'ColumnCount'               => count($grid->getColumns()) - $countUntilThisColumn,
            'ColumnCountWithoutActions' => count($grid->getColumns()) - $countUntilThisColumn - 1,
            'PrevColumnsCount'          => $countUntilThisColumn,
            'Model'                     => (($record = $this->getRecordFromGrid(
                $grid,
                $modelClass
            )) && $record->hasMethod('i18n_singular_name')) ? $record->i18n_singular_name() : _t(
                'GridFieldUtils.ITEM',
                'Item'
            ),
        ];
    }

    public function handleSave(GridField $grid, DataObjectInterface $record)
    {
        $list = $grid->getList();
        $value = $grid->Value();
        $componentName = $this->getComponentName();

        if (!isset($value[$componentName]) || !is_array($value[$componentName])) {
            return;
        }

        $class = $grid->getModelClass();

        if (!singleton($class)->canCreate()) {
            return;
        }

        $form = $this->getForm($grid, '', false);
        $id = $grid->ID();

        $orderable = $grid->Config->getComponentByType(GridFieldOrderableRows::class);
        $sortField = $orderable ? $orderable->getSortField() : '';
        $max = $sortField && !$this->prepend ? $orderable->getManipulatedData(
            $grid,
            $list
        )->max($sortField) + 1 : false;
        $count = 1;
        $itemIds = [];

        foreach ($value[$componentName] as $fields) {
            $item = Injector::inst()->create($fields['_modelClass'] ?? $class);

            $form->loadDataFrom($fields);
            $form->saveInto($item);

            $extra = Utilities::getExtraFields($form->getData(), $list);

            if ($sortField && $max !== false) {
                $item->$sortField = $max;
                $extra[$sortField] = $max;
                $max++;
            } else {
                if ($sortField) {
                    $item->$sortField = $count;
                    $extra[$sortField] = $count;
                    $count++;
                }
            }

            $item->write();
            $list->add($item, $extra);
            $itemIds[] = $item->ID;

            Utilities::getSession()->set('EditableRowToggles.' . $id . '.' . get_class($item) . '_' . $item->ID, true);
        }

        // Fix other sorts for prepends in one query
        if ($sortField && $max === false) {
            DB::query(sprintf(
                'UPDATE "%s" SET "%s" = %s + %d WHERE %s',
                $orderable->getSortTable($list),
                $sortField,
                $sortField,
                $count,
                '"ID" NOT IN (' . implode(',', $itemIds) . ')'
            ));
        }
    }

    /**
     * @param GridField $grid
     * @param string $append
     * @param bool $removeEditableColumnFields
     * @param string $modelClass
     * @return Form
     */
    protected function getForm($grid, $append = '', $removeEditableColumnFields = true, $modelClass = '')
    {
        $this->workingGrid = $grid;
        $form = Form::create(
            $this,
            'Form-' . $grid->getModelClass() . $append,
            $this->getFieldList($grid, $removeEditableColumnFields, $modelClass),
            FieldList::create(),
            $this->getValidatorForForm($grid, $modelClass)
        )->loadDataFrom($this->getRecordFromGrid($grid, $modelClass));

        if ($form->Fields()->hasTabSet() && ($root = $form->Fields()->findOrMakeTab('Root')) && $root->Template == 'CMSTabSet') {
            $root->setTemplate('');
            $form->removeExtraClass('cms-tabset');
        }

        $callback = $this->getItemEditFormCallback();

        if ($callback) {
            call_user_func($callback, $form, $this, $grid, $modelClass, $removeEditableColumnFields);
        }

        return $form;
    }

    /**
     * @param GridField $grid
     * @param bool $removeEditableColumnFields
     * @param string $modelClass
     * @return FieldList
     */
    protected function getFieldList($grid = null, $removeEditableColumnFields = true, $modelClass = '')
    {
        $fields = null;

        if ($this->fields) {
            if ($this->fields instanceof FieldList) {
                $fields = $this->fields;
            } elseif (is_callable($this->fields)) {
                $fields = call_user_func_array(
                    $this->fields,
                    [$this->getRecordFromGrid($grid, $modelClass), $grid, $this]
                );
            } else {
                $fields = FieldList::create($this->fields);
            }
        }

        $editableRows = $grid->getConfig()->getComponentByType(EditableRow::class);
        $editableCols = $grid->getConfig()->getComponentByType(GridFieldEditableColumns::class);

        if (!$fields && $grid) {
            if ($editableRows && $editableRows instanceof EditableRow) {
                $fields = $editableRows->getForm(
                    $grid,
                    $this->getRecordFromGrid($grid, $modelClass),
                    $removeEditableColumnFields
                )->Fields();
            } elseif ($editableCols && $editableCols instanceof GridFieldEditableColumns && !$this->ignoreEditableColumns) {
                $fields = $editableCols->getFields($grid, $this->getRecordFromGrid($grid, $modelClass));
            }
        }

        if (!$fields && $record = $this->getRecordFromGrid($grid, $modelClass)) {
            $fields = $record->hasMethod('getEditableRowFields') ? $record->getEditableRowFields($grid) : $record->getCMSFields();
        }

        if (!$fields) {
            throw new \Exception(sprintf('Please setFields on your %s component', __CLASS__));
        }

        // Remove fields provided by EditableColumns
        if ($removeEditableColumnFields && $grid && $this->canEditWithEditableColumns($grid) && $editableCols && $editableCols instanceof GridFieldEditableColumns) {
            if (!isset($record)) {
                $record = $this->getRecordFromGrid($grid, $modelClass);
            }

            $editableColumns = $editableCols->getFields($grid, $record);

            foreach ($editableColumns as $column) {
                $fields->removeByName($column->Name);
            }
        }

        return $fields;
    }

    protected function getValidatorForForm($grid = null, $modelClass = '')
    {
        if ($this->validator) {
            if ($this->validator instanceof Validator) {
                return $this->validator;
            } elseif (is_callable($this->validator)) {
                return call_user_func_array(
                    $this->validator,
                    [$this->getRecordFromGrid($grid, $modelClass), $grid, $this]
                );
            } else {
                return Validator::create($this->validator);
            }
        }

        return null;
    }

    protected function getRecordFromGrid($grid, $class = '')
    {
        if ($grid->getList()) {
            if (!$class) {
                $class = $grid->getModelClass();
            }

            $record = Injector::inst()->create($class);

            if ($grid->List && ($grid->List instanceof HasManyList) && $grid->Form && $grid->Form->Record) {
                $gridName = $grid->Name;
                $gridField = $gridName . "ID";
                $record->$gridName = $grid->Form->Record;
                $record->$gridField = $grid->Form->Record->ID;
            } else {
                $workingParent = $this->setWorkingParentOnRecordTo;
                if (!$workingParent && $grid->Config && $editableRow = $grid->Config->getComponentByType(EditableRow::class)) {
                    $workingParent = $editableRow->setWorkingParentOnRecordTo;
                }

                if ($workingParent && $grid->List && $grid->Form && $grid->Form->Record) {
                    $record->$workingParent = $grid->Form->Record;
                }
            }

            return $record;
        }

        return null;
    }

    public function handleForm($grid, $request)
    {
        $remaining = $request->remaining();
        $modelClass = $request->getVar($grid->ID() . '_modelClass');
        $form = $this->getForm($grid, '', true, $modelClass);

        $expr = sprintf('/\/%s\[%s\]\[([0-9]+)\]/', preg_quote($grid->Name), $this->getComponentName());
        $result = preg_match(
            $expr,
            $remaining,
            $matches
        );
        if ($result && isset($matches[1])) {
            $this->renameFieldsInCompositeField($form->Fields(), $grid, $matches[1]);
        }

        return $form;
    }

    public function loadItem($grid, $request)
    {
        $modelClass = $request->getVar($grid->ID() . '_modelClass');
        $cacheKey = $this->getCacheKey([
            'class' => get_class($this->getRecordFromGrid($grid, $modelClass)),
            'id'    => spl_object_hash($this),
            'open'  => $this->openToggleByDefault,
        ]);

        $cache = $this->getCache();
        $template = null;
        if ($cache && $this->cacheAjaxLoading) {
            $template = $cache->get($cacheKey);
        }

        if (!$template) {
            $ssTemplates = array_merge((array)$this->template, ['Includes/GridField_AddNewInlineExtended_Row']);
            $templateData = array_merge(
                $this->getRowTemplateVariables($grid, '{{ placeholder }}', $modelClass),
                [
                    'Ajax'        => true,
                    'placeholder' => '{{ placeholder }}',
                ]
            );
            $template = (string)ArrayData::create($templateData)->renderWith($ssTemplates);

            if ($cache && $this->cacheAjaxLoading) {
                $cache->set($cacheKey, $template);
            }
        }

        $template = str_replace('{{ placeholder }}', $request->getVar('_datanum'), $template);

        return $template;
    }

    public function Link($action = '')
    {
        return $this->workingGrid ? Controller::join_links(
            $this->workingGrid->Link($this->urlSegment),
            $action
        ) : null;
    }

    /**
     * @param Gridfield $gridField
     * @return bool
     */
    public function canEditWithEditableColumns($gridField)
    {
        if ($this->ignoreEditableColumns) {
            return false;
        }
        return $this->hideUnlessOpenedWithEditableColumns && $gridField->Config->getComponentByType(GridFieldEditableColumns::class);
    }

    protected function getCacheKey(array $vars = [])
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $this->getComponentName() . '_' . urldecode(http_build_query($vars, '', '_')));
    }

    protected function renameFieldsInCompositeField($fields, $grid, $rowNumber = 1)
    {
        $class = $this->getComponentName();

        foreach ($fields as $field) {
            $field->setName(sprintf(
                '%s[%s][%s][%s]',
                $grid->getName(),
                $class,
                $rowNumber,
                $field->getName()
            ));

            if ($field->isComposite()) {
                $this->renameFieldsInCompositeField($field->FieldList(), $grid, $rowNumber);
            }
        }
    }

    protected function canCreate($grid)
    {
        return $grid->getList() && singleton($grid->getModelClass())->canCreate();
    }

    protected function getComponentName()
    {
        return str_replace(['\\', '-'], '_', __CLASS__ . '_' . $this->urlSegment);
    }
}
