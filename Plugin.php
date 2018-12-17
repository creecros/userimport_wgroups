<?php

namespace Kanboard\Plugin\ImportWithGroup;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;
use Kanboard\Plugin\ImportWithGroup\Import\NewUserImport;
use Kanboard\Core\Security\Role;

class Plugin extends Base
{
    public function initialize()
    {   
        //Models
        $this->container['userImport'] = $this->container->factory(function ($c) {
            return new NewUserImport($c);
        });

    }

    public function onStartup()
    {
        // Translation
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
    }

    public function getClasses()
    {
        return [
            'Plugin\ImportWithGroup\Import' => [
                'NewUserImport', 
                ],
        ];
    }

    public function getPluginName()
    {
        return 'ImportWithGroup';
    }

    public function getPluginDescription()
    {
        return t('Import Users with Group');
    }

    public function getPluginAuthor()
    {
        return 'Craig Crosby';
    }

    public function getPluginVersion()
    {
        return '1.0.0';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/creecros/userimport_wgroups';
    }
}
