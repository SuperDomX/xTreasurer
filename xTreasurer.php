<?php
/**
 * @name Treasurer
 * @desc Control how $$ is collected
 * @version v1.0.0
 * @author heylisten@xtiv.net
 * @icon cash_register.png
 * @mini credit-card
 * @see market
 * @link treasurer
 * @todo
 * @formHandler
 */
	class xTreasurer extends Xengine{
		/**
		 * @remotable
		 */
		function loadPaypal(){
			$CFG = $this->readConfigs();

			return array(
				'success' => true,
				'data' => array(
					'PAYPAL_EMAIL' => $CFG['PAYPAL_EMAIL']
				)
			);
		}

		public function stripe($swipe)
		{
			# code...
			$this->lib('stripe/Stripe.php');
			// Set your secret key: remember to change this to your live secret key in production
			// See your keys here https://dashboard.stripe.com/account

			$CFG = $this->readConfigs();

			$key = $CFG['stripe_key'];
			
			Stripe::setApiKey($key);

			// Get the credit card details submitted by the form
			$token   = $swipe['id'];
			$amount  = $swipe['amount'];

			$cus_id = $q->Select('stripe_id','Users',array(
				'id' => $swipe['user_id']
			));

			if( !empty($cus_id) && $cus_id[0]['stripe_id'] != '' ){
				$cus_id = $cus_id[0]['stripe_id'];  
			}else{
				// Create a Customer
				$customer = Stripe_Customer::create(array(
				  "card" 		=> $token,
				  "description" => $swipe['email']
				));

				$cus_id = $customer->id;

				$q->Update('Users',array(
					'stripe_id' => $cus_id
				),array(
					'id' => $swipe['user_id']
				));
			}

			$checkout['success'] = false; 

			try {
				$charge = Stripe_Charge::create(array(
					"amount"   => 100 * $amount, # amount in cents, again
					"currency" => "usd",
					"customer" => $cus_id
				));
				$checkout['success'] = true; 
			} catch(Stripe_CardError $e) {
			  // Since it's a decline, Stripe_CardError will be caught
			  $body = $e->getJsonBody();
			  $err  = $body['error'];

			  print('Status is:' . $e->getHttpStatus() . "\n");
			  print('Type is:' . $err['type'] . "\n");
			  print('Code is:' . $err['code'] . "\n");
			  // param is '' in this case
			  print('Param is:' . $err['param'] . "\n");
			  print('Message is:' . $err['message'] . "\n");
			  $checkout['error'] =  $err['message'];
			} catch (Stripe_InvalidRequestError $err ) {
			  // Invalid parameters were supplied to Stripe's API
				$checkout['error'] = $err->getMessage();
			} catch (Stripe_AuthenticationError $err) {
			  // Authentication with Stripe's API failed
			  // (maybe you changed API keys recently)
				$checkout['error'] = $err->getMessage();
			} catch (Stripe_ApiConnectionError $err) {
			  // Network communication with Stripe failed
				$checkout['error'] = $err->getMessage();
			} catch (Stripe_Error $err) { 
			  // Display a very generic error to the user, and maybe send
			  // yourself an email
				$checkout['error'] = $err->getMessage();
			} catch (Exception $err) {
			  // Something else happened, completely unrelated to Stripe
				$checkout['error'] = $err->getMessage();
			}

			return $checkout;
		}

		/**
		 * @remotable
		 * @formHandler
		 */
		function submit($f){
			$this->setConfig('PAYPAL_EMAIL',$f['PAYPAL_EMAIL']);
			return array(
				'success' => true,
				'data'	=> $f,
				'errors' => $errors
			);
		}

		function paypal(){
			if($this->IS_ADMIN){

			}
		}
	}
?>