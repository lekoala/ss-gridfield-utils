<?php

namespace LeKoala\GridFieldUtils;

use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;
use SilverStripe\Control\Controller;
use Symbiote\GridFieldExtensions\GridFieldExtensions;
use SilverStripe\View\ArrayData;

class AddExistingPicker extends GridFieldAddExistingSearchButton
{
    /**
     * @var callable
     */
    protected $searchHandlerFactory;
    /**
     * @var callable
     */
    protected $addHandler;
    /**
     * @var callable
     */
    protected $undoHandler;
    /**
     * @var string
     */
    protected $urlSegment;
    /**
     * @var bool
     */
    public $async = true;

    /**
     * Set a search handler factory, which can create a custom RequestHandler
     * to be used for searching
     *
     * @param callable $searchHandlerFactory
     * @return self $this
     */
    public function setSearchHandlerFactory($searchHandlerFactory)
    {
        $this->searchHandlerFactory = $searchHandlerFactory;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getSearchHandlerFactory()
    {
        return $this->searchHandlerFactory;
    }

    /**
     * Sets a custom handler for when the add action is called
     *
     * @param callable $addHandler
     * @return self $this
     */
    public function setAddHandler($addHandler)
    {
        $this->addHandler = $addHandler;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getAddHandler()
    {
        return $this->addHandler;
    }

    /**
     * Sets a custom handler for undoing the add action
     *
     * @param callable $undoHandler
     * @return self $this
     */
    public function setUndoHandler($undoHandler)
    {
        $this->undoHandler = $undoHandler;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getUndoHandler()
    {
        return $this->undoHandler;
    }

    /**
     * @return string
     */
    public function getUrlSegment()
    {
        return $this->urlSegment;
    }

    /**
     * @param string $urlSegment
     */
    public function setUrlSegment($urlSegment = '')
    {
        $this->urlSegment = $urlSegment;
        return $this;
    }

    /**
     * Enable the async picker, when an item is clicked in the list
     * it is automatically added to the list, with an undo option.
     *
     * @param bool $async
     * @return self $this
     */
    public function async($async = true)
    {
        $this->async = $async;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAsync()
    {
        return $this->async;
    }

    public function handleSearch($grid, $request)
    {
        if ($this->searchHandlerFactory) {
            return call_user_func($this->searchHandlerFactory, $grid, $this, $request);
        } else {
            return AddExistingPickerController::create(
                $grid,
                $this,
                $request
            );
        }
    }

    public function getHTMLFragments($grid)
    {
        GridFieldExtensions::include_requirements();
        Utilities::include_requirements();

        $data = ArrayData::create([
            'Title' => $this->getTitle(),
            'Classes' => 'action btn btn-primary font-icon-search add-existing-search',
            'Link'  => $grid->Link($this->getCurrentUrlSegment()),
        ]);

        if ($this->async) {
            $grid->addExtraClass('ss-gridfield-add-existing-picker_async');
        }

        return [
            $this->fragment => $data->renderWith([
                'GridField_AddExistingPicker',
                get_parent_class($this),
            ]),
        ];
    }

    public function getCurrentUrlSegment()
    {
        return $this->urlSegment ?: 'add-existing-search';
    }

    public function getURLHandlers($grid)
    {
        return [
            $this->getCurrentUrlSegment() => 'handleSearch',
        ];
    }
}
