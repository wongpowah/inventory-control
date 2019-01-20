<?php
  /* URL configurations. */
  define("PURCHASE_URL", MENU_URL . "purchase/");

  define("PURCHASE_ORDER_URL", PURCHASE_URL . "purchase-order/");
  define("PURCHASE_ORDER_SAVED_URL", PURCHASE_ORDER_URL . "saved/");
  define("PURCHASE_ORDER_POSTED_URL", PURCHASE_ORDER_URL . "posted/");
  define("PURCHASE_ORDER_PRINTOUT_URL", PURCHASE_ORDER_URL . "printout/");
  define("PURCHASE_ORDER_INTERNAL_PRINTOUT_URL", PURCHASE_ORDER_URL . "internal-printout/");

  define("INCOMING_ADVICE_URL", PURCHASE_URL . "incoming-advice/");
  define("INCOMING_ADVICE_SAVED_URL", INCOMING_ADVICE_URL . "saved/");
  define("INCOMING_ADVICE_CONFIRMED_URL", INCOMING_ADVICE_URL . "confirmed/");
  define("INCOMING_ADVICE_POSTED_URL", INCOMING_ADVICE_URL . "posted/");
  define("INCOMING_ADVICE_PRINTOUT_URL", INCOMING_ADVICE_URL . "printout/");

  define("PURCHASE_DELIVERY_ORDER_URL", PURCHASE_URL . "delivery-order/");
  define("PURCHASE_DELIVERY_ORDER_POSTED_URL", PURCHASE_DELIVERY_ORDER_URL . "posted/");


  /* Title configurations. */
  define("PURCHASE_TITLE", "(E) Purchase");

  define("PURCHASE_ORDER_TITLE", "(E1) Purchase Order");
  define("PURCHASE_ORDER_CREATE_TITLE", "(E1a) Create Purchase Order");
  define("PURCHASE_ORDER_SAVED_TITLE", "(E3b) Saved Purchase Orders");
  define("PURCHASE_ORDER_POSTED_TITLE", "(E3c) Posted Purchase Orders");
  define("PURCHASE_ORDER_PRINTOUT_TITLE", "Purchase Order");
  define("PURCHASE_ORDER_INTERNAL_PRINTOUT_TITLE", "Purchase Order (Internal)");

  define("INCOMING_ADVICE_TITLE", "(E3) Incoming Advice");
  define("INCOMING_ADVICE_CREATE_TITLE", "(E3a) Create Incoming Advice");
  define("INCOMING_ADVICE_SAVED_TITLE", "(E3b) Saved Incoming Advices");
  define("INCOMING_ADVICE_CONFIRMED_TITLE", "(E3c) Confirmed Incoming Advices");
  define("INCOMING_ADVICE_POSTED_TITLE", "(E3d) Posted Incoming Advices");
  define("INCOMING_ADVICE_PRINTOUT_TITLE", "Incoming Advice");

  define("PURCHASE_DELIVERY_ORDER_TITLE", "(E4) Delivery Order");
  define("PURCHASE_DELIVERY_ORDER_POSTED_TITLE", "(E4a) Posted Delivery Orders");


  $PURCHASE_MODULE = array(
    PURCHASE_ORDER_TITLE => array(
      PURCHASE_ORDER_CREATE_TITLE => PURCHASE_ORDER_URL,
      PURCHASE_ORDER_SAVED_TITLE => PURCHASE_ORDER_SAVED_URL,
      PURCHASE_ORDER_POSTED_TITLE => PURCHASE_ORDER_POSTED_URL
    ),
    INCOMING_ADVICE_TITLE => array(
      INCOMING_ADVICE_CREATE_TITLE => INCOMING_ADVICE_URL,
      INCOMING_ADVICE_SAVED_TITLE => INCOMING_ADVICE_SAVED_URL,
      INCOMING_ADVICE_CONFIRMED_TITLE => INCOMING_ADVICE_CONFIRMED_URL,
      INCOMING_ADVICE_POSTED_TITLE => INCOMING_ADVICE_POSTED_URL
    )
  );
?>
