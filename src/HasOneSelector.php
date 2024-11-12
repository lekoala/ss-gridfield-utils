<?php

namespace LeKoala\GridFieldUtils;

use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_SaveHandler;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Forms\FormField;

class HasOneSelector implements GridField_ColumnProvider, GridField_SaveHandler, GridField_HTMLProvider
{
    /**
     * @var string
     */
    public $resetButtonTitle;

    /**
     * This is mostly not needed since you can click to deselect
     * @var bool
     */
    public $showResetButton = false;

    /**
     * @var string
     */
    public $columnTitle = 'Select';

    /**
     * @var string
     */
    public $targetFragment;

    /**
     * @var string
     */
    protected $relation;

    /**
     * @param mixed $relation Relation name
     * @param string $columnTitle
     * @param string $targetFragment
     */
    public function __construct($relation, $columnTitle = '', $targetFragment = 'before')
    {
        $this->relation = $relation;
        $this->columnTitle = $columnTitle ?: FormField::name_to_label($relation);
        $this->targetFragment = $targetFragment;
        $this->resetButtonTitle = _t(
            'GridField_HasOneSelector.RESET',
            'Reset {columnTitle}',
            ['columnTitle' => $this->columnTitle]
        );
    }

    /**
     * Modify the list of columns displayed in the table.
     *
     * @see {@link GridFieldDataColumns->getDisplayFields()}
     * @see {@link GridFieldDataColumns}.
     *
     * @param GridField $gridField
     * @param array - List reference of all column names.
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array($this->relation, $columns)) {
            $columns[] = $this->relation;
        }
    }

    /**
     * Names of all columns which are affected by this component.
     *
     * @param GridField $gridField
     *
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return [$this->relation];
    }

    /**
     * HTML for the column, content of the <td> element.
     *
     * @param  GridField $gridField
     * @param  DataObject $record - Record displayed in this row
     * @param  string $columnName
     *
     * @return string - HTML for the column. Return NULL to skip.
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        $value = $gridField && $gridField->Form && $gridField->Form->Record ? $gridField->Form->Record->{$this->relation . 'ID'} : '';

        return $record->ID ? _t(
            'GridField_HasOneSelector.SELECTOR',
            '<input type="radio" name="{name}" value="{value}"{selected}/>',
            [
                'name'     => $this->getHasOneName($gridField),
                'value'    => $record->ID,
                'selected' => $value == $record->ID ? ' checked="checked"' : '',
            ]
        ) : '';
    }

    /**
     * @param GridField $gridField
     * @return string
     */
    public function getHasOneName($gridField)
    {
        return sprintf('%s_%s', $gridField->getName(), str_replace('\\', '_', __CLASS__));
    }

    /**
     * Attributes for the element containing the content returned by {@link getColumnContent()}.
     *
     * @param  GridField $gridField
     * @param  DataObject $record displayed in this row
     * @param  string $columnName
     *
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return [
            'class'         => 'ss-gridfield-hasOneSelector-holder ss-gridfield-hasOneSelector-col_' . $columnName,
            'data-relation' => $this->relation,
        ];
    }

    /**
     * Additional metadata about the column which can be used by other components,
     * e.g. to set a title for a search column header.
     *
     * @param GridField $gridField
     * @param string $columnName
     *
     * @return array - Map of arbitrary metadata identifiers to their values.
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        return ['title' => $this->columnTitle];
    }

    public function getHTMLFragments($grid)
    {
        Utilities::include_requirements();

        if (!$this->showResetButton) {
            return '';
        }

        return [
            $this->targetFragment => ArrayData::create([
                'Title'    => $this->resetButtonTitle,
                'Relation' => $this->relation,
            ])->renderWith('GridField_HasOneSelector'),
        ];
    }

    public function handleSave(GridField $grid, DataObjectInterface $record)
    {
        $name = $this->getHasOneName($grid);

        $value = intval($_POST[$name] ?? 0);

        $saveMethod = 'save' . $this->relation . 'FromGridField';
        if ($record->hasMethod($saveMethod)) {
            $record->$saveMethod($value);
        } else {
            $relationField = $this->relation . 'ID';
            $record->$relationField = $value;
        }
    }
}
