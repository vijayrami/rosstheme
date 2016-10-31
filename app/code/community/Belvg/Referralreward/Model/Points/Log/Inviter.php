<?php
/**
 * BelVG LLC.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
 *
/******************************************
 *      MAGENTO EDITION USAGE NOTICE      *
 ******************************************/
 /* This package designed for Magento COMMUNITY edition
 * BelVG does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * BelVG does not provide extension support in case of
 * incorrect edition usage.
/******************************************
 *      DISCLAIMER                        *
 ******************************************/
/* Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future.
 ******************************************
 * @category   Belvg
 * @package    Belvg_Referralreward
 * @copyright  Copyright (c) 2010 - 2011 BelVG LLC. (http://www.belvg.com)
 * @license    http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
 */

class Belvg_Referralreward_Model_Points_Log_Inviter extends Belvg_Referralreward_Model_Points_Log_Invitee
{
    /**
     * Supplement Points
     *
     * @param Mage_Sales_Model_Order
     * @param array
     * @return Belvg_Referralreward_Model_Points_Log | boolean
     */
    public function supplementPoints($order, $params = array())
    {
        $helper = Mage::helper('referralreward');
        $points = (int) $helper->storeConfig('points/inviter');
        if ($points && isset($params['customer_id'])) {
            $params['type']        = Belvg_Referralreward_Model_Points_Log::TYPE_CUSTOMER_INVITER;
            $params['object_id']   = $order->getId();
            $params['points']      = $points;
            $params['points_orig'] = $points;

            return Mage::getModel('referralreward/points_log')
                ->supplementPoints($params['customer_id'], $params);
        }

        return FALSE;
    }

    /**
     * Withdraw Points
     *
     * @param Mage_Sales_Model_Order
     */
    public function withdrawPoints($order)
    {
        $orderLog   = Mage::getModel('referralreward/points_log')->getCollection()
            ->addFieldToFilter('object_id', $order->getId())
            ->addFieldToFilter('status', Belvg_Referralreward_Model_Points_Log::TYPE_CUSTOMER_INVITER)
            ->getFirstItem();
        $customerId = $orderLog->getCustomerId();
        if ($orderLog->getPoints() == $orderLog->getPointsOrig()) {
            $orderLog->delete();
            Mage::getModel('referralreward/points')->calculate($customerId);
        } else {
            $withdrawPoints = $orderLog->getPointsOrig() - $orderLog->getPoints();
            $orderLog
                ->setPoints(0)
                ->setPointsOrig($withdrawPoints * -1)
                ->save();
            Mage::getModel('referralreward/points_log')->withdrawPoints($customerId, $withdrawPoints);
        }

        $friend = Mage::getModel('referralreward/friends')->load($customerId);
        if ($friend->getId()) {
            $friend->setStatus(Belvg_Referralreward_Model_Friends::FRIEND_BRING)->save();
        }
    }

    /**
     * Get Log Item Title
     *
     * @return string
     */
    public function getLogTitle()
    {
        return Mage::helper('referralreward')->__('Inviter');
    }
}
