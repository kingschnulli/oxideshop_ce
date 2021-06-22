<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;

/**
 * Class manages vendor assignment to articles
 */
class VendorMainAjax extends \OxidEsales\Eshop\Application\Controller\Admin\ListComponentAjax
{
    /**
     * If true extended column selection will be build
     *
     * @var bool
     */
    protected $_blAllowExtColumns = true;

    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = ['container1' => [ // field , table,       visible, multilanguage, ident
        ['oxartnum', 'oxarticles', 1, 0, 0],
        ['oxtitle', 'oxarticles', 1, 1, 0],
        ['oxean', 'oxarticles', 1, 0, 0],
        ['oxmpn', 'oxarticles', 0, 0, 0],
        ['oxprice', 'oxarticles', 0, 0, 0],
        ['oxstock', 'oxarticles', 0, 0, 0],
        ['oxid', 'oxarticles', 0, 0, 1]
    ],
                                 'container2' => [
                                     ['oxartnum', 'oxarticles', 1, 0, 0],
                                     ['oxtitle', 'oxarticles', 1, 1, 0],
                                     ['oxean', 'oxarticles', 1, 0, 0],
                                     ['oxmpn', 'oxarticles', 0, 0, 0],
                                     ['oxprice', 'oxarticles', 0, 0, 0],
                                     ['oxstock', 'oxarticles', 0, 0, 0],
                                     ['oxid', 'oxarticles', 0, 0, 1]
                                 ]
    ];

    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "getQuery" in next major
     */
    protected function _getQuery() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        // looking for table/view
        $sArtTable = $this->_getViewName('oxarticles');
        $sO2CView = $this->_getViewName('oxobject2category');
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $sVendorId = Registry::getRequest()->getRequestEscapedParameter('oxid');
        $sSynchVendorId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        // vendor selected or not ?
        if (!$sVendorId) {
            $sQAdd = ' from ' . $sArtTable . ' where ' . $sArtTable . '.oxshopid="' . $oConfig->getShopId() . '" and 1 ';
            $sQAdd .= $oConfig->getConfigParam('blVariantsSelection') ? '' : " and $sArtTable.oxparentid = '' and $sArtTable.oxvendorid != " . $oDb->quote($sSynchVendorId);
        } else {
            // selected category ?
            if ($sSynchVendorId && $sSynchVendorId != $sVendorId) {
                $sQAdd = " from $sO2CView left join $sArtTable on ";
                $sQAdd .= $oConfig->getConfigParam('blVariantsSelection') ? " ( $sArtTable.oxid = $sO2CView.oxobjectid or $sArtTable.oxparentid = oxobject2category.oxobjectid )" : " $sArtTable.oxid = $sO2CView.oxobjectid ";
                $sQAdd .= 'where ' . $sArtTable . '.oxshopid="' . $oConfig->getShopId() . '" and ' . $sO2CView . '.oxcatnid = ' . $oDb->quote($sVendorId) . ' and ' . $sArtTable . '.oxvendorid != ' . $oDb->quote($sSynchVendorId);
            } else {
                $sQAdd = " from $sArtTable where $sArtTable.oxvendorid = " . $oDb->quote($sVendorId);
            }

            $sQAdd .= $oConfig->getConfigParam('blVariantsSelection') ? '' : " and $sArtTable.oxparentid = '' ";
        }

        return $sQAdd;
    }

    /**
     * Adds filter SQL to current query
     *
     * @param string $sQ query to add filter condition
     *
     * @return string
     * @deprecated underscore prefix violates PSR12, will be renamed to "addFilter" in next major
     */
    protected function _addFilter($sQ) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $sArtTable = $this->_getViewName('oxarticles');
        $sQ = parent::_addFilter($sQ);

        // display variants or not ?
        $sQ .= \OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('blVariantsSelection') ? ' group by ' . $sArtTable . '.oxid ' : '';

        return $sQ;
    }

    /**
     * Removes article from Vendor
     */
    public function removeVendor()
    {
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        $aRemoveArt = $this->_getActionIds('oxarticles.oxid');

        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sArtTable = $this->_getViewName('oxarticles');
            $aRemoveArt = $this->_getAll($this->_addFilter("select $sArtTable.oxid " . $this->_getQuery()));
        }

        if (is_array($aRemoveArt)) {
            $sSelect = "update oxarticles set oxvendorid = null where "
                . $this->onVendorActionArticleUpdateConditions($aRemoveArt);
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sSelect);

            $this->resetCounter("vendorArticle", Registry::getRequest()->getRequestEscapedParameter('oxid'));

            $this->onVendorAction(Registry::getRequest()->getRequestEscapedParameter('oxid'));
        }
    }

    /**
     * Adds article to Vendor config
     */
    public function addVendor()
    {
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();

        $aAddArticle = $this->_getActionIds('oxarticles.oxid');
        $soxId = Registry::getRequest()->getRequestEscapedParameter('synchoxid');

        if (Registry::getRequest()->getRequestEscapedParameter('all')) {
            $sArtTable = $this->_getViewName('oxarticles');
            $aAddArticle = $this->_getAll($this->_addFilter("select $sArtTable.oxid " . $this->_getQuery()));
        }

        if ($soxId && $soxId != "-1" && is_array($aAddArticle)) {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $sSelect = "update oxarticles set oxvendorid = " . $oDb->quote($soxId) . " where "
                . $this->onVendorActionArticleUpdateConditions($aAddArticle);

            $oDb->Execute($sSelect);
            $this->resetCounter("vendorArticle", $soxId);

            $this->onVendorAction($soxId);
        }
    }

    /**
     * Condition for updating oxarticles on add / remove vendor actions.
     *
     * @param array $articleIds
     *
     * @return string
     */
    protected function onVendorActionArticleUpdateConditions($articleIds)
    {
        return 'oxid in (' . implode(", ", \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quoteArray($articleIds)) . ')';
    }

    /**
     * Additional actions on vendor add/remove.
     *
     * @param string $vendorOxid
     */
    protected function onVendorAction($vendorOxid)
    {
    }
}
