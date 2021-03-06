<?php
/**
 * Class GeneralHandler
 *
 * @author      MundiPagg Embeddables Team <embeddables@mundipagg.com>
 * @copyright   2017 MundiPagg (http://www.mundipagg.com)
 * @license     http://www.mundipagg.com Copyright
 *
 * @link        http://www.mundipagg.com
 */

namespace MundiPagg\MundiPagg\Gateway\Transaction\CreditCard\ResourceGateway\Create\Response;


use Magento\Payment\Gateway\Response\HandlerInterface;
use MundiPagg\MundiPagg\Gateway\Transaction\Base\ResourceGateway\Response\AbstractHandler;
use MundiPagg\MundiPagg\Model\ChargesFactory;
use MundiPagg\MundiPagg\Gateway\Transaction\CreditCard\Config\Config as ConfigCreditCard;
use MundiPagg\MundiPagg\Helper\Logger;

class GeneralHandler extends AbstractHandler implements HandlerInterface
{
	/**
     * \MundiPagg\MundiPagg\Model\ChargesFactory
     */
	protected $modelCharges;

    /**
     * \MundiPagg\MundiPagg\Gateway\Transaction\CreditCard\Config\Config
     */
    protected $configCreditCard;

    /**
     * @var \MundiPagg\MundiPagg\Helper\Logger
     */
    protected $logger;

    public function __construct(
        ConfigCreditCard $configCreditCard,
    	ChargesFactory $modelCharges,
        Logger $logger
    ) {
        $this->modelCharges = $modelCharges;
        $this->configCreditCard = $configCreditCard;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function _handle($payment, $response)
    {
        $this->logger->logger(json_encode($response));
        $payment->setTransactionId($response->id);

        if($this->configCreditCard->getPaymentAction() == 'authorize_capture')
        {
            $payment->setIsTransactionClosed(true);
            $payment->accept()
                ->setParentTransactionId($response->id);
        }else{
            $payment->setIsTransactionClosed(false);
        }

        foreach($response->charges as $charge)
        {
        	try {
        		$model = $this->modelCharges->create();
	            $model->setChargeId($charge->id);
	            $model->setCode($charge->code);
	            $model->setOrderId($payment->getOrder()->getIncrementId());
	            $model->setType($charge->paymentMethod);
	            $model->setStatus($charge->status);
	            $model->setAmount($charge->amount);
                
	            $model->setPaidAmount(0);
	            $model->setRefundedAmount(0);
	            $model->setCreatedAt(date("Y-m-d H:i:s"));
	            $model->setUpdatedAt(date("Y-m-d H:i:s"));
	            $model->save();
        	} catch (\Exception $e) {
        		return $e->getMessage();
        	}
        }

        return $this;
    }
}
