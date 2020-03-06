<?php
namespace WapplerSystems\Samlauth;


use Pitbulk\SAML2\Model\AuthFactory;

class Data
{


    /**
     * Get saml auth
     *
     * @return Auth
     */
    public function getAuth()
    {
        $settingsInfo = $this->getSettings();
        $auth = $this->authFactory->create(["settings" => $settingsInfo]);
        return $auth;
    }


}
