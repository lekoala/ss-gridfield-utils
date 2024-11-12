<?php

namespace LeKoala\GridFieldUtils;

use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\ManyManyThroughList;

class Utilities
{
    public static function include_requirements()
    {
        Requirements::css('lekoala/ss-gridfield-utils:/client/css/gridfield.utils.css');
        Requirements::javascript('lekoala/ss-gridfield-utils:/client/js/gridfield.utils.js');
    }

    public static function getSession(): Session
    {
        return Controller::curr()->getRequest()->getSession();
    }

    public static function getExtraFields(array $data, SS_List $list)
    {
        $extra = [];
        if ($list instanceof ManyManyList || $list instanceof ManyManyThroughList) {
            $extra = array_intersect_key(
                $data,
                (array)$list->getExtraFields()
            );
        }
        return $extra;
    }

    public static function createObject(string $name, ...$args)
    {
        $injector = Injector::inst();
        if (empty($args)) {
            return $injector->create($name);
        }
        return $injector->createWithArgs($name, $args);
    }
}
