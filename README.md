GridField Utilities
======

This module includes a subset of [GridField Utilities](https://github.com/milkyway-multimedia/ss-gridfield-utils) for SilverStripe 4 and 5.

Includes the following (note they all live in the namespace LeKoala\GridFieldUtils):
* [AddNewInlineExtended](docs/en/AddNewInlineExtended.md): A more complex version of GridFieldAddNewInlineButton, allowing you to set custom fields, rather than copying GridFieldEditableColumns (defaults to this behaviour)
* [EditableRow](docs/en/EditableRow.md): adds an expandable form to each row in the GridField, allowing you to edit records directly from the GridField.
* [HasOneSelector](docs/en/HasOneSelector.md): Allow you to select a has one relation from the current GridField
* [AddExistingPicker](docs/en/AddExistingPicker.md): Works exactly like the one in gridfieldextensions, except it allows you to add more before closing the window - allowing for a faster workflow (requires silverstripe-australia/gridfieldextensions)

### Caveats
* A deep nested EditableRow will be very slow, since it has many request handlers to access, but not much I can do about this behaviour...

## Examples

### HasOneSelector

```php
$grid = new GridField('Tags', 'Tags', $this->Tags(), new GridFieldConfig);
$grid->getConfig()->addComponent($component = new HasOneSelector('MainTag', 'Select main tag'));
// Configure any public property...
$component->showResetButton = true;
```

### AddNewInlineExtended

```php
$grid->getConfig()->addComponent(GridFieldButtonRow::create('before'))
    ->addComponent(GridFieldToolbarHeader::create())
    ->addComponent(GridFieldTitleHeader::create())
    ->addComponent(GridFieldEditableColumns::create())
    ->addComponent(GridFieldDeleteAction::create())
    ->addComponent(AddNewInlineExtended::create())
```

### EditableRow

```php
$grid = new GridField('Tags', 'Tags', $this->Tags(), GridFieldConfig_RecordEditor::create());
$grid->getConfig()->addComponent($component = new EditableRow());
```

### AddExistingPicker

```php
$grid = new GridField('Tags', 'Tags', $this->Tags(), GridFieldConfig_RelationEditor::create());
$grid->getConfig()->addComponent($component = new AddExistingPicker());
// instead of...
// $grid->getConfig()->addComponent($component = new GridFieldAddExistingSearchButton());
$fields->addFieldToTab('Root.Main', $grid);
```

## Requirements
* [silverstripe/framework](https://github.com/silverstripe/framework)
* [symbiote/silverstripe-gridfieldextensions](https://github.com/symbiote/silverstripe-gridfieldextensions)

## Credits
- [milkyway-multimedia](https://github.com/milkyway-multimedia): Original code
- [ajshort](https://github.com/ajshort "ajshort on Github"): He did most of the coding of GridFieldExtensions, which I borrowed for the more complex versions in this module
- [silverstripe-australia](https://github.com/silverstripe-australia "silverstripe-australia on Github"): They now look after the GridFieldExtensions module, and have done some updates which I have probably borrowed

## License
* MIT
