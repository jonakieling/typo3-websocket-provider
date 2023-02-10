<?php

namespace Werkraum\WebsocketProvider\Context;

class UserAspect extends \TYPO3\CMS\Core\Context\UserAspect
{
    /**
     * Generic helper to fetch
     *
     * @param string $propertyName
     * @return array|bool|int|int[]|string
     */
    public function getUserProperty(string $propertyName)
    {
        if (!isset($this->user->user, $this->user->user[$propertyName])) {
            return null;
        }
        return ($this->user->user[$propertyName]);
    }

}