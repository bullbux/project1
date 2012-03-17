<?php

/**
 * Copyright (C) 2007 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

 /* This is the response handler code that will be invoked every time
  * a notification or request is sent by the Google Server
  *
  * To allow this code to receive responses, the url for this file
  * must be set on the seller page under Settings->Integration as the
  * "API Callback URL'
  * Order processing commands can be sent automatically by placing these
  * commands appropriately
  *
  * To use this code for merchant-calculated feedback, this url must be
  * set also as the merchant-calculations-url when the cart is posted
  * Depending on your calculations for shipping, taxes, coupons and gift
  * certificates update parts of the code as required
  *
  */

  chdir("..");
  require_once('library/googleresponse.php');
  require_once('library/googlemerchantcalculations.php');
  require_once('library/googleresult.php');
  require_once('library/googlerequest.php');

  define('RESPONSE_HANDLER_ERROR_LOG_FILE', 'googleerror.log');
  define('RESPONSE_HANDLER_LOG_FILE', 'googlemessage.log');

  $merchant_id = "";  // Your Merchant ID
  $merchant_key = "";  // Your Merchant Key
  $server_type = "sandbox";  // change this to go live
  $currency = 'USD';  // set to GBP if in the UK

  $Grequest = new GoogleRequest($merchant_id, $merchant_key, $server_type, $currency);

  $item1 = new GoogleShipItem('SKU_1');
  $item1->AddTrackingData('USPS','123123adjsh123');
  $item2 = new GoogleShipItem('SKU_2');
  $item2->AddTrackingData('USPS','123123adjsh123');
  $item2->AddTrackingData('fedex','adajs549p80789163');

  $items = array($item1,
                 $item2,
                 new GoogleShipItem('SKU_3', array(array('carrier' => 'DHL', 
                                                         'tracking-number' => 'akjshdj12323'),
                                                   array('carrier' => 'USPS', 
                                                         'tracking-number' => 'aasd4a4sd465a3'))),
                 new GoogleShipItem('SKU_4', array(array('carrier' => 'UPS', 
                                                         'tracking-number' => '09876543231'))),
                 );

  $Grequest->SendShipItems('123456789', $items);
  $Grequest->SendCancelItems('123456789', array($item1, $item2), "reason", "comment");
  $Grequest->SendReturnItems('123456789', $items);
  
?>