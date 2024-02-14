<?php
/**
 * CNTechnoLabs
 * Copyright (C) 2023 CNTechnoLabs 
 *
 * @category  CNTechnoLabs
 * @package   CNTechnoLabs_Reindex
 * @copyright Copyright (c) 2023 CNTechnoLabs
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author    CNTechnoLabs
 */

namespace CNTechnoLabs\Reindex\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Indexer\Model\Indexer;
use Throwable;
use Exception;

class Reindex extends Action
{
    /**
     * ACL resource
     */
    const ACTION_RESOURCE = 'CNTechnoLabs_Reindex::reindex';

    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * MassReindex constructor.
     * @param IndexerRegistry $indexerRegistry
     * @param Context $context
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        Context $context
    ) {
        $this->indexerRegistry = $indexerRegistry;
        return parent::__construct($context);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ACTION_RESOURCE);
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        $ids = $this->getRequest()->getParam('indexer_ids');
        $this->reindexAll($ids);

        $this->_redirect('indexer/indexer/list');
    }

    /**
     * @param integer[] $ids
     *
     * @return bool
     */
    protected function reindexAll(array $ids)
    {
        if (!is_array($ids)) {
            $this->messageManager->addErrorMessage(__('Please select one or two indxers.'));
            return false;
        }

        foreach ($ids as $id) {
            $this->reindex($id);
        }

        return true;
    }

    /**
     * @param string $id
     * @throws Throwable
     */
    protected function reindex(string $id)
    {
        $startTime = microtime(true);

        try {
            /** @var Indexer $indexer */
            $indexer = $this->indexerRegistry->get($id);
            $indexer->reindexAll();
            $totalTime = microtime(true) - $startTime;
            $totalTime = round($totalTime, 2);

            $msg = sprintf(__('%s was reindexed in %s seconds'), $indexer->getTitle(), $totalTime);
            $this->messageManager->addSuccessMessage($msg);

        } catch (LocalizedException $e) {
            $msg = sprintf(__('%s indexer process exception'), $indexer->getTitle());
            $this->messageManager->addErrorMessage($msg, $e);

        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }
    }
}