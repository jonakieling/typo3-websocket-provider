<?php

namespace Werkraum\WebsocketProvider\Context;

use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;

class UserAspect extends \TYPO3\CMS\Core\Context\UserAspect
{
    /**
     * Additionaly can retrieve the users team_id
     *
     * @param string $name
     * @return array|bool|int|int[]|string
     * @throws AspectPropertyNotFoundException
     */
    public function get(string $name)
    {
        if ($name === 'teamId') {
            return (int)($this->user->user['tx_stzteamverwaltung_team_id'] ?? 0);
        }

        return parent::get($name);
    }

}