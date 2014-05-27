<?php

defined('C5_EXECUTE') or die(_("Access Denied."));

class SocialApiPackage extends Package {

    protected $pkgHandle = 'social_api';
    protected $appVersionRequired = '5.6.0';
    protected $pkgVersion = '1.0.3';

    public function getPackageName() {
        return t("Social API Package");
    }

    public function getPackageDescription() {
        return t("Social API Package");
    }

    public function install() {
        $pkg = parent::install();

        $this->installOrUpgrade($pkg);
    }

    // Update any existing installation
    public function upgrade() {
        parent::upgrade();

        $pkg = $this;

        $this->installOrUpgrade($pkg);
    }

    // Called whenever install or upgrade of package is attempted
    private function installOrUpgrade($pkg) {
        $this->installUserAttribute($pkg, 'has_unknown_password',   'boolean',  'Has Unknown Password');
        $this->installUserAttribute($pkg, 'has_unknown_email',      'boolean',  'Has Unknown Email');
        $this->installUserAttribute($pkg, 'created_by_network',     'text',     'Created By Network');
        $this->installUserAttribute($pkg, 'social_networks',        'text',     'Social Networks');

        $this->installUserAttribute($pkg, 'facebook_user_id',       'text', 'Facebook User ID');
        $this->installUserAttribute($pkg, 'facebook_user_name',     'text', 'Facebook User Name');
        $this->installUserAttribute($pkg, 'facebook_email',         'text', 'Facebook Email');
        $this->installUserAttribute($pkg, 'facebook_name',          'text', 'Facebook Name');

        $this->installUserAttribute($pkg, 'twitter_user_id',        'text', 'Twitter User ID');
        $this->installUserAttribute($pkg, 'twitter_screen_name',    'text', 'Twitter Screen Name');
        $this->installUserAttribute($pkg, 'twitter_name',           'text', 'Twitter Name');
        $this->installUserAttribute($pkg, 'twitter_otoken',         'text', 'Twitter OAuth Token');
        $this->installUserAttribute($pkg, 'twitter_otoken_secret',  'text', 'Twitter OAuth Token Secret');

        $this->installSinglePage($pkg, '/social_api', 'Social API', 'Social API');

        /* ---------------- INSTALL BLOCK ---------------- */
        $this->installBlockType($pkg, 'twitter_feed');
    }

    // Called every page load
    public function on_start() {
        $html = Loader::helper('html');
        $v = View::getInstance();

        // $v->addHeaderItem($html->css('calendar.css', 'bootstrap_calendar'));

        // $v->addHeaderItem($html->javascript('calendar.js', 'bootstrap_calendar'));
    }

    private function installTheme($pkg, $handle) {
        $pageTheme = PageTheme::getByHandle($handle);

        if (!$pageTheme) {
            $pageTheme = PageTheme::add($handle, $pkg);
        }

        return $pageTheme;
    }

    private function installBlockType($pkg, $handle) {
        //try {
            // TODO: Need to detect a previous block type installation rather than try/catch
            return BlockType::installBlockTypeFromPackage($handle, $pkg);
        // }
        // catch (Exception $ex) {

        // }
    }

    private function installAttribute($pkg, $handle, $type, $name, $extraParams = NULL) {
        $attributeType = AttributeType::getByHandle($type);
        $collectionAttributeKey = CollectionAttributeKey::getByHandle($handle);

        if (!is_object($collectionAttributeKey)) {
            $params = array('akHandle' => $handle, 'akName' => $name, 'akIsSearchable' => false);

            if ($extraParams) $params = array_unique(array_merge($extraParams, $params));

            CollectionAttributeKey::add($attributeType, $params, $pkg);
        }

        return CollectionAttributeKey::getByHandle($handle);
    }

    private function installUserAttribute($pkg, $handle, $type, $name, $extraParams = NULL) {
        $attributeType = AttributeType::getByHandle($type);
        $userAttributeKey = UserAttributeKey::getByHandle($handle);

        if (!is_object($userAttributeKey)) {
            $params = array('akHandle' => $handle, 'akName' => $name, 'akIsSearchable' => false);

            if ($extraParams) $params = array_unique(array_merge($extraParams, $params));

            UserAttributeKey::add($attributeType, $params, $pkg);
        }

        return UserAttributeKey::getByHandle($handle);
    }

    private function installPageType($pkg, $handle, $name) {
        Loader::model('collection_types');

        $collectionType = CollectionType::getByHandle($handle);

        if (!$collectionType || !intval($collectionType->getCollectionTypeID())) {
            $collectionType = CollectionType::add(array('ctHandle' => $handle, 'ctName' => t($name)), $pkg);
        }

        return $collectionType;
    }

    private function assignAttributeToPageType($pkg, $attributeHandle, $pageTypeHandle) {
        try {
            $collectionAttributeKey = CollectionAttributeKey::getByHandle($attributeHandle);
            $collectionType = CollectionType::getByHandle($pageTypeHandle);

            if ($collectionAttributeKey && $collectionType) {
                $collectionType->assignCollectionAttribute($collectionAttributeKey);
            }
        }
        catch (Exception $ex) {
            // Probably already assigned. TODO: Need more elegant way of detecting already assigned
        }
    }

    private function installSinglePage($pkg, $url, $name, $description) {
        Loader::model('single_page');

        try {
            $sp = SinglePage::add($url, $pkg);
            if ($sp) $sp->update(array('cName' => t($name), 'cDescription' => t($description)));
        }
        catch (Exception $ex) {

        }
    }

}
