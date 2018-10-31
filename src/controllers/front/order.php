<?php
/**
 * Contains code for the order rest controller.
 */

use Boxtal\BoxtalConnectPrestashop\Util\OrderStorageUtil;
use Boxtal\BoxtalConnectPrestashop\Util\ShippingMethodUtil;
use Boxtal\BoxtalPhp\RestClient;
use Boxtal\BoxtalConnectPrestashop\Util\ApiUtil;
use Boxtal\BoxtalConnectPrestashop\Util\AuthUtil;
use Boxtal\BoxtalConnectPrestashop\Util\CarrierUtil;
use Boxtal\BoxtalConnectPrestashop\Util\MiscUtil;
use Boxtal\BoxtalConnectPrestashop\Util\OrderUtil;
use Boxtal\BoxtalConnectPrestashop\Util\ProductUtil;

/**
 * Order reset controller.
 *
 * Opens API endpoint to sync orders.
 */
class boxtalconnectOrderModuleFrontController extends ModuleFrontController
{

    /**
     * Processes request.
     *
     * @void
     */
    public function postProcess()
    {

        $entityBody = file_get_contents('php://input');

        AuthUtil::authenticate($entityBody);

        $route = Tools::getValue('route'); // Get route

        if ('order' === $route) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case RestClient::$POST:
                        $this->retrieveOrdersHandler();
                        break;

                    default:
                        break;
                }
            }
        } elseif ('shipped' === $route || 'delivered' === $route) {
            $orderId = Tools::getValue('orderId');
            $body = AuthUtil::decryptBody($entityBody);
            if (isset($_SERVER['REQUEST_METHOD'])) {
                switch ($_SERVER['REQUEST_METHOD']) {
                    case RestClient::$POST:
                        $this->trackingEventHandler($orderId, $route, $body);
                        break;

                    default:
                        break;
                }
            }
        }

        ApiUtil::sendApiResponse(400);
    }

    /**
     * Endpoint callback.
     *
     * @void
     */
    public function retrieveOrdersHandler()
    {
        $boxtalconnect = boxtalconnect::getInstance();
        $response = $this->getOrders($boxtalconnect->shopGroupId, $boxtalconnect->shopId);
        ApiUtil::sendApiResponse(200, $response);
    }

    /**
     * Get Prestashop orders.
     *
     * @param int $shopGroupId shop group id.
     * @param int $shopId      shop id.
     *
     * @return array $result
     */
    public function getOrders($shopGroupId, $shopId)
    {
        $orders = OrderUtil::getOrders($shopGroupId, $shopId);
        $result = array();

        foreach ($orders as $order) {
            if (null !== MiscUtil::notEmptyOrNull($order, 'id_order')) {
                $orderId = (int) MiscUtil::notEmptyOrNull($order, 'id_order');
            } else {
                continue;
            }

            $recipient = array(
                'firstname'    => MiscUtil::notEmptyOrNull($order, 'firstname'),
                'lastname'     => MiscUtil::notEmptyOrNull($order, 'lastname'),
                'company'      => MiscUtil::notEmptyOrNull($order, 'company'),
                'addressLine1' => MiscUtil::notEmptyOrNull($order, 'address1'),
                'addressLine2' => MiscUtil::notEmptyOrNull($order, 'address2'),
                'city'         => MiscUtil::notEmptyOrNull($order, 'city'),
                'state'        => MiscUtil::notEmptyOrNull($order, 'state_iso'),
                'postcode'     => MiscUtil::notEmptyOrNull($order, 'postcode'),
                'country'      => MiscUtil::notEmptyOrNull($order, 'country_iso'),
                'phone'        => MiscUtil::notEmptyOrNull($order, 'phone'),
                'email'        => MiscUtil::notEmptyOrNull($order, 'email'),
            );
            $items = OrderUtil::getItemsFromOrder($orderId);
            $products = array();
            foreach ($items as $item) {
                $product                = array();
                $product['weight']      = 0 !== (float) $item['product_weight'] ? (float) $item['product_weight'] : null;
                $product['quantity']    = (int) $item['product_quantity'];
                $product['price']       = (float) $item['product_price'];
                $description = ProductUtil::getProductDescriptionMultilingual((int) $item['product_id']);
                $product['description'] = $description;
                $products[]             = $product;
            }

            $parcelPoint          = null;
            $parcelPointCode     = OrderStorageUtil::get($orderId, 'bxParcelPointCode', $shopGroupId, $shopId);
            $parcelPointNetwork = OrderStorageUtil::get($orderId, 'bxParcelPointNetwork', $shopGroupId, $shopId);
            if ($parcelPointCode && $parcelPointNetwork) {
                $parcelPoint = array(
                    'code'     => $parcelPointCode,
                    'network' => $parcelPointNetwork,
                );
            }

            $multilingualStatus = OrderUtil::getStatusMultilingual($orderId);
            $multilingualShippingMethod = array();
            $shippingMethodName = MiscUtil::notEmptyOrNull($order, 'shippingMethod');
            foreach (\Language::getLanguages(true) as $lang) {
                $multilingualShippingMethod[str_replace('-', '_', $lang['locale'])] = $shippingMethodName;
            }

            $result[] = array(
                'internalReference'      => $orderId,
                'reference'      => MiscUtil::notEmptyOrNull($order, 'reference'),
                'status'         => array(
                    'key' => OrderUtil::getStatusId($orderId),
                    'translations' => $multilingualStatus,
                ),
                'shippingMethod' => array(
                    'key' => ShippingMethodUtil::getReferenceFromId(OrderUtil::getCarrierId($orderId)),
                    'translations' => $multilingualShippingMethod,
                ),
                'shippingAmount' => MiscUtil::toFloatOrNull(MiscUtil::notEmptyOrNull($order, 'shippingAmount')),
                'creationDate'   => MiscUtil::dateW3Cformat(MiscUtil::notEmptyOrNull($order, 'creationDate')),
                'orderAmount'    => MiscUtil::toFloatOrNull(MiscUtil::notEmptyOrNull($order, 'orderAmount')),
                'recipient'      => $recipient,
                'products'       => $products,
                'parcelPoint'    => $parcelPoint,
            );
        }

        return array('orders' => $result);
    }

    /**
     * Endpoint callback.
     *
     * @param int                   $orderId order id.
     * @param 'shipped'|'delivered' $route   tracking event.
     * @param object                $body    request body.
     *
     * @void
     */
    public function trackingEventHandler($orderId, $route, $body)
    {
        $boxtalconnect = boxtalconnect::getInstance();
        if (! is_object($body) || ! property_exists($body, 'accessKey') || $body->accessKey !== AuthUtil::getAccessKey($boxtalconnect->shopGroupId, $boxtalconnect->shopId)) {
            ApiUtil::sendApiResponse(403);
        }

        if (! is_int($orderId)) {
            ApiUtil::sendApiResponse(400);
        }



        ApiUtil::sendApiResponse(200);
    }
}
