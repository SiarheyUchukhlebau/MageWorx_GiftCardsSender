<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types = 1);

namespace MageWorx\GiftCardsSender\Plugin;

use MageWorx\GiftCards\Api\GiftCardManagementInterface;
use Psr\Log\LoggerInterface;

class SendOnResourceSave
{
    private GiftCardManagementInterface $giftCardManagement;
    private LoggerInterface             $logger;

    public function __construct(
        GiftCardManagementInterface $giftCardManagement,
        LoggerInterface             $logger
    ) {
        $this->giftCardManagement = $giftCardManagement;
        $this->logger             = $logger;
    }

    /**
     * @param \MageWorx\GiftCards\Model\ResourceModel\GiftCards $subject
     * @param callable $proceed
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return \MageWorx\GiftCards\Model\ResourceModel\GiftCards
     */
    public function aroundSave(
        \MageWorx\GiftCards\Model\ResourceModel\GiftCards $subject,
        callable                                          $proceed,
        \Magento\Framework\Model\AbstractModel            $object
    ): \MageWorx\GiftCards\Model\ResourceModel\GiftCards {
        $shouldSend = $object->getId() === null && $object->isObjectNew();

        $result = $proceed($object);

        if ($shouldSend
            && (int)$object->getCardType() === \MageWorx\GiftCards\Model\GiftCards::TYPE_EMAIL
            && $object->getId()
        ) {
            try {
                $this->giftCardManagement->sendEmailWithGiftCard((int)$object->getId());
            } catch (\Exception $exception) {
                $this->logger->error('Unable to send gift card with id ' . $object->getId());
                $this->logger->error($exception->getMessage());
            }
        }

        return $result;
    }
}
