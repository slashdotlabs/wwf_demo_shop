<?php
// to prevent direct access to the plugin
// defined('ABSPATH') or die("No script kiddies please!");

// Plugin header- notifies wordpress of the existence of the plugin

/**
 * Plugin Name: WooCommerce iPay Payment Gateway
 * Plugin URI: ipayafrica.com
 * Description: iPay Payment Gateway for woocommerce.
 * Version: 2.0.0
 * Author: Kingmoses
 * Author URI: ipayafrica.com
 * License: GPL2
 */

// Plugin licence

/*  Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : moseskariuki2119@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//works by hooking into woocommerce

add_action( 'plugins_loaded', 'init_ipay_payment_gateway' );

function init_ipay_payment_gateway() {

    if( !class_exists( 'WC_Payment_Gateway' )) return;

    //defining class

    /**
     * iPay Payment Gateway
     *
     * @class          WC_Gateway_Ipay
     * @extends        WC_Payment_Gateway
     * @version        1.0.0
     */
    class WC_Gateway_Ipay extends WC_Payment_Gateway {

        /**
            *  Plugin constructor
            */
        public function __construct(){
            // setting basic settings eg name, callback url, title etc
            $this->id                 = 'ipay';
            $this->icon               = 'http://images.ipayafrica.com/channels/channels_500.png';//WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.jpg'; //apply_filters('ipay-logo', WC()->plugin_url() . '/images/logo-small.png');
            $this->has_fields         = false;
            $this->method_title       = __( 'iPay', 'woocommerce' );
            $this->method_description = __( 'Payments Made Easy' );
            $this->callback_url       = $this->ipay_callback();

            // load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title            = $this->get_option( 'title' );
            $this->description      = $this->get_option( 'description' );
            $this->instructions     = $this->get_option( 'instructions', $this->description );
            $this->mer              = $this->get_option( 'mer' );
            $this->vid              = $this->get_option( 'vid' );
            $this->hsh              = $this->get_option( 'hsh' );
            $this->live             = $this->get_option( 'live' );
            $this->mpesa            = $this->get_option( 'mpesa' );
            $this->airtel           = $this->get_option( 'airtel' );
            $this->equity           = $this->get_option( 'equity' );
            $this->creditcard       = $this->get_option( 'creditcard' );
            $this->debitcard       = $this->get_option( 'debitcard' );
            $this->pesalink       = $this->get_option( 'pesalink' );
            $this->autopay           = $this->get_option( 'autopay' );

            //=====================================================================================================================
            

            //Actions

            // actions handling the callback:

            add_action('init', array($this, 'callback_handler'));

            add_action ('woocommerce_api_'.strtolower( get_class( $this ) ), array( $this, 'callback_handler' ) );

            //Saving admin options
            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {

                add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( $this, 'process_admin_options' ) );

            } else {

                add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );

            }

            add_action( 'woocommerce_receipt_ipay', array( $this, 'receipt_page' ) );

        }

        /**
            *Initialize Gateway Form Fields - Backend Settings
            */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'woocommerce' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable iPay Payments Gateway', 'woocommerce' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                    'default'     => __( 'iPay', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __( 'Description', 'woocommerce' ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
                    'default'     => __( 'Place order and pay using (M-PESA, Airtel Money, Kenswitch, VISA, MasterCard) <br> Powered by www.ipayafrica.com', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
                    'title'       => __( 'Instructions', 'woocommerce' ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
                    'default'     => __( 'Place order and pay using (M-PESA, Airtel Money, Kenswitch, VISA, MasterCard) <br> Powered by www.ipayafrica.com', 'woocommerce' ),
                    // 'css'         => 'textarea { read-only};',
                    'desc_tip'    => true,
                ),
                'mer' => array(
                    'title'       => __( 'Merchant Name', 'woocommerce' ),
                    'description' => __( 'Company name', 'woocommerce' ),
                    'type'        => 'text',
                    'default'     => __( 'Company Name', 'woocommerce'),
                    'desc_tip'    => false,
                ),
                'vid' => array(
                'title'       => __( 'Vendor ID', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'Vendor ID as assigned by iPay. SET IN LOWER CASE.', 'woocommerce' ),
                'default'     => __( 'demo', 'woocommerce' ),
                'desc_tip'    => false,
                ),
                'hsh' => array(
                    'title'       => __( 'Security Key', 'woocommerce'),
                    'type'        => 'password',
                    'description' => __( 'Security key assigned by iPay', 'woocommerce' ),
                    'default'     => __( 'demo', 'woocommerce' ),
                    'desc_tip'    => false,
                ),
                'live' => array(
                    'title'     => __( 'Live/Demo', 'woocommerce' ),
                    'type'      => 'checkbox',
                    'label'     => __( 'Make iPay live', 'woocommerce' ),
                    'default'   => 'no',
                ),
                'mpesa' => array(
                    'title'     => __( 'MPESA', 'woocommerce' ),
                    'type'      => 'checkbox',
                    'label'     => __( 'Turn On Mobile MPESA', 'woocommerce' ),
                    'default'   => 'yes',
                ),
                'airtel' => array(
                    'title'     => __( 'Airtel', 'woocommerce' ),
                    'type'      => 'checkbox',
                    'label'     => __( 'Turn On Airtel', 'woocommerce' ),
                    'default'   => 'no',
                ),
                'equity' => array(
                    'title'     => __( 'Equity', 'woocommerce' ),
                    'type'      => 'checkbox',
                    'label'     => __( 'Turn On Equity(eazzypay)', 'woocommerce' ),
                    'default'   => 'no',
                ),
                'creditcard' => array(
                    'title'     => __( 'Credit Card Channel', 'woocommerce' ),
                    'type'      => 'checkbox',
                    'label'     => __( 'Turn On Credit Card Channel', 'woocommerce' ),
                    'default'   => 'no',
                ),
                'debitcard' => array(
                    'title'     => __( 'Debit Card Channel', 'woocommerce' ),
                    'type'      => 'checkbox',
                    'label'     => __( 'Turn On Debit Card Channel', 'woocommerce' ),
                    'default'   => 'no',
                ),
                'pesalink' => array(
                    'title'     => __( 'Pesalink', 'woocommerce' ),
                    'type'      => 'checkbox',
                    'label'     => __( 'Turn On Pesalink', 'woocommerce' ),
                    'default'   => 'no',
                ),
                'autopay' => array(
                    'title'     => __( 'autopay', 'woocommerce' ),
                    'type'      => 'checkbox',
                    'label'     => __( 'Turn On autopay', 'woocommerce' ),
                    'default'   => 'yes',
                ),
            );
        }

        /**
         * Generates the HTML for the admin settings page
         */
        public function admin_options(){
            /*
                *The heading and paragraph below are the ones that appear on the backend ipay settings page
                */
            echo '<h3>' . 'iPay Payments Gateway' . '</h3>';

            echo '<p>' . 'Payments Made Easy' . '</p>';

            echo '<table class="form-table">';

            $this->generate_settings_html( );

            echo '</table>';
        }

        /**
         * Receipt Page
         **/
        public function receipt_page( $order_id ) {

            echo $this->generate_ipay_iframe( $order_id );

        }

        /**
         * Function that posts the params to iPay and generates the iframe
         */
        public function generate_ipay_iframe( $order_id ) {

            global $woocommerce;

            $order = new WC_Order ( $order_id );

            /**
             *The checkboxes return the values 'yes' when checked and 'no' when unchecked.
            *YES = 0 in the ipay settings and NO = 1
            *Using if statements to set the values
            **/

            /**
             *For the live variable, unchecked = 0, checked = 1
            *For loop
            **/
            $mpesa      = ($this->mpesa == 'yes')? 1 : 0;
            $airtel     = ($this->airtel == 'yes')? 1 : 0;
            $equity     = ($this->equity == 'yes')? 1 : 0;
            $creditcard =($this->creditcard == 'yes')? 1 : 0;
            $debitcard =($this->debitcard == 'yes')? 1 : 0;
            $pesalink   = ($this->pesalink == 'yes')? 1 : 0;
            $autopay   = ($this->autopay == 'yes')? 1 : 0;


            if ( $this->live == 'no' ) {

                $live   = 0;

            }else{

                $live   = 1;
            }
            $mer        = $this->mer;
            $tel        = $order->get_billing_phone();

            //incase of any dashes in the telephone number the code below removes them
            $tel        = str_replace("-", "", $tel);
            $tel        = str_replace( array(' ', '<', '>', '&', '{', '}', '*', "+", '!', '@', '#', "$", '%', '^', '&'), "", $tel );
            $eml        = $order->get_billing_email();
            // make ssl if needed
            //added by star
            $live       = $live;
            $vid        = $this->vid;
            $oid        = $order->get_id();
            $inv        = $oid;
            $p1         = '';
            $p2         = '';
            $p3         = '';
            $p4         = '';
            $autopay 	= $autopay;
            $eml        = $order->get_billing_email();
            $curr       = get_woocommerce_currency();
            // $curr = 'KES';
            // $ttl        = $order->order_total;
            // Update: 10-03-20: Deprecated way of getting total. https://wordpress.org/support/topic/woocommerce-3-notices-in-log/
            $ttl        = $order->get_total();
            $tel        = $tel;
            $crl        = '0';
            $cst        = '1';
            $callbk     = $this->callback_url;
            $cbk        = $callbk;
            $hsh        = $this->hsh;

            $datastring = $live.$oid.$inv.$ttl.$tel.$eml.$vid.$curr.$p1.$p2.$p3.$p4.$cbk.$cst.$crl;
            $hash_string = hash_hmac('sha1', $datastring,$hsh);
            $hash = $hash_string;

            $url = "https://payments.ipayafrica.com/v3/ke?live=".$live."&oid=".$oid."&inv=".$inv."&ttl=".$ttl."&tel=".$tel."&eml=".$eml."&vid=".$vid."&curr=".$curr."&p1=".$p1."&p2=".$p2."&p3=".$p3."&p4=".$p4."&autopay=".$autopay."&cbk=".$cbk."&cst=".$cst."&crl=".$crl."&hsh=".$hash."&mpesa=".$mpesa."&airtel=".$airtel."&creditcard=".$creditcard."&equity=".$equity."&pesalink=".$pesalink."&debitcard=".$debitcard;////======================================POS ENDPOINT================================================


            $items = $order->get_items();
            //print_r($items);
            $text = "Phone Number : ".$tel." <br><br>";

            foreach ( $items as $item ){
                //$product_id = $item['product_id'];
                //$text .="Product ID: ".$product_id."<br>";

                $product_name = $item['name'];
                $text .="Name: ".$product_name."<br>";

                // $product_variation_id = $item['variation_id'];
                $qty =  $item['qty'];
                $text .="Quantity: ".$qty."<br><br>";

            }

            echo '<iframe src="'.$url.'" width="940" height="1000" style="border:0" scrolling="yes"></iframe>';
            //header("location: $url");
            //$this->insert_order_details($order,$tel);
        }

        /**
         * Returns link to the callback class
         * Refer to WC-API for more information on using classes as callbacks
         */
        public function ipay_callback(){

            return WC()->api_request_url('WC_Gateway_Ipay');
        }

        /**
         * This function gets the callback values posted by iPay to the callback url
         * It updates order status and order notes
         */
        public function callback_handler() {

            global $woocommerce;

            $val = $this->vid;
            /*
            these values below are picked from the incoming URL and assigned to variables that we
            will use in our security check URL
            */

            $val1 = $_GET['id'];
            $val2 = $_GET['ivm'];
            $val3 = $_GET['qwh'];
            $val4 = $_GET['afd'];
            $val5 = $_GET['poi'];
            $val6 = $_GET['uyt'];
            $val7 = $_GET['ifd'];
            $ipnurl = "https://www.ipayafrica.com/ipn/?vendor=".$val."&id=".$val1."&ivm=".
            $val2."&qwh=".$val3."&afd=".$val4."&poi=".$val5."&uyt=".$val6."&ifd=".$val7;
            if ( $this->live !== 'no' ) {
                $fp = fopen($ipnurl, "rb");
                $status = stream_get_contents($fp, -1, -1);
                fclose($fp);
            }else{
                $status = $_GET['status'];
            }
            //the value of the parameter “vendor”, in the url being opened above, is your iPayassigned
            //Vendor ID.
            //this is the correct iPay status code corresponding to this transaction.
            //Use it to validate your incoming transaction; not the one supplied in the incoming url
            //echo $status;
            //continue your shopping cart update routine code here below....
            //then redirect to to the customer notification page here...
            $this->notifications($status,$val1);
        }

        public function notifications($status,$val1){

            wp_enqueue_style('handle', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css');
            wp_enqueue_style('handle', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css');

            $order_id = $val1;
            $order = new WC_Order ( $order_id );
            //print_r($order);
            if($status == "fe2707etr5s4wq" ){       //failed
                ?><div class="alert alert-danger" style="border-left:solid #e52727 5px; border-top:solid #d4d4ce 1px; border-bottom:solid #d4d4ce 1px; border-right:solid #d4d4ce 1px;padding:15px 5px; background-color: #f2f2f2; border-top-right-radius: 5px; border-bottom-right-radius: 5px;" role="alert">
                    <strong>Transaction Failed : </strong> Please try again.
                </div>
                <?php
                $order->update_status('failed', 'The attempted payment FAILED - iPay.<br>', 'woocommerce' );
            }

            else if($status == "aei7p7yrx4ae34" ){ //successful
                ?><!-- <div class="alert alert-success" style="border-left:solid #43d44d 5px; border-top:solid #d4d4ce 1px; border-bottom:solid #d4d4ce 1px; border-right:solid #d4d4ce 1px;padding:15px 5px; background-color: #f2f2f2; border-top-right-radius: 5px; border-bottom-right-radius: 5px;" role="alert">
                    <strong>Transaction Successful : </strong>Have a blessed day.
                </div> -->
                <?php
                $order->update_status( 'completed', 'The order was SUCCESSFULLY processed by iPay.<br>', 'woocommerce' );
                // Reduce stock levels
                $order->reduce_order_stock();
                $success_page = $this->get_return_url( $order );
                ?>
                    <script type="text/javascript">
                        window.top.location.href = "<?php echo $success_page; ?>"; 
                    </script>
                <?php
            }

            else if($status == "bdi6p2yy76etrs"){ //pending
                ?><div class="alert alert-warning" style="border-left:solid #f68820 5px; border-top:solid #d4d4ce 1px; border-bottom:solid #d4d4ce 1px; border-right:solid #d4d4ce 1px;padding:15px 5px; background-color: #f2f2f2; border-top-right-radius: 5px; border-bottom-right-radius: 5px;" role="alert">
                        <strong>Transaction Pending : </strong>Please try again in 5 minutes or contact the Merchant for assistance.
                    </div>
                <?php
                $order->update_status( 'pending', 'The transaction is PENDING. Tell customer to try again -iPAY', 'woocommerce' );

            }

            else if($status == "cr5i3pgy9867e1" ){   //used code
                ?><div class="alert alert-warning" style="border-left:solid #f68820 5px; border-top:solid #d4d4ce 1px; border-bottom:solid #d4d4ce 1px; border-right:solid #d4d4ce 1px;padding:15px 5px; background-color: #f2f2f2; border-top-right-radius: 5px; border-bottom-right-radius: 5px;" role="alert">
                        <strong>Transaction Failed : </strong>That code had been used already, contact the Merchant for assistance.
                    </div>
                <?php
                $order->update_status( 'failed', __( 'The input payment code has already been USED. Please contact customer - iPay.<br>', 'woocommerce') );

            }

            else if($status == "dtfi4p7yty45wq"){        // less
                ?><div class="alert alert-warning" style="border-left:solid #f68820 5px; border-top:solid #d4d4ce 1px; border-bottom:solid #d4d4ce 1px; border-right:solid #d4d4ce 1px;padding:15px 5px; background-color: #f2f2f2; border-top-right-radius: 5px; border-bottom-right-radius: 5px;" role="alert">
                        <strong>Transaction Failed : </strong>The money received is less than the transaction cost, contact the Merchant for assistance.
                    </div>
                <?php
                $order->update_status( 'on-hold', __( 'Amount paid was LESS than the required - iPay.<br>', 'woocommerce') );
                // Reduce stock levels
                $order->reduce_order_stock();
            }

            else if($status == "eq3i7p5yt7645e"){ // more
                ?><div class="alert alert-info" style="border-left:solid #43d44d 5px; border-top:solid #d4d4ce 1px; border-bottom:solid #d4d4ce 1px; border-right:solid #d4d4ce 1px;padding:15px 5px; background-color: #f2f2f2; border-top-right-radius: 5px; border-bottom-right-radius: 5px;" role="alert">
                        <strong>Transaction Successful : </strong>The money sent exceeds the transaction cost, please contact the Merchant for assistance.
                    </div>
                <?php
                $order->update_status('completed', __( 'The amount paid was MORE than the required. Please refund customer - iPay.<br>', 'woocommerce' ));
                // Reduce stock levels
                $order->reduce_order_stock();
                // Getting url of order-received page - one of the 2 checkout endpoints
                $return_url = $order->get_checkout_order_received_url(true);

                echo '<script language="javascript">';
                echo 'confirm("Thank you for transacting with us. Your transaction was successful though you payed more than required. Please contact us for a refund.")';
                echo 'top.location.href = "'.$return_url.'";';
                echo '</script>';

                exit();

            }
            die;
        }

        /**
            * Process the payment field and redirect to checkout/pay page.
            *
            * @param $order_id
            * @return array
            */
        public function process_payment( $order_id ) {

            $order = new WC_Order( $order_id );

            // Redirect to checkout/pay page
            return array(
                'result' => 'success',
                'redirect' => add_query_arg('order-pay', $order->id,
                    add_query_arg('key', $order->order_key, $order->get_checkout_payment_url(true)))
            );

        }

    }

    /**
     * Telling woocommerce that ipay payments gateway class exists
     * Filtering woocommerce_payment_gateways
     * Add the Gateway to WooCommerce
     **/
    function add_ipay_gateway_class( $methods ) {

        $methods[] = 'WC_Gateway_Ipay';

        return $methods;

    }

    if(!add_filter( 'woocommerce_payment_gateways', 'add_ipay_gateway_class' )) die;
}
