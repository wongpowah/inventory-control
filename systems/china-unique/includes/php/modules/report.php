<?php
  /* URL configurations. */
  define("REPORT_URL", INVENTORY_URL . "report/");
  define("REPORT_HISTORY_LOG_URL", REPORT_URL . "history-log/");
  define("REPORT_SALES_PERFORMANCE_URL", REPORT_URL . "sales-performance/");
  define("REPORT_SALES_PERFORMANCE_CLIENT_URL", REPORT_SALES_PERFORMANCE_URL . "client/");
  define("REPORT_SALES_PERFORMANCE_MODEL_URL", REPORT_SALES_PERFORMANCE_URL . "model/");

  define("REPORT_SALES_PERFORMANCE_URL", REPORT_URL . "sales-performance/");

  define("REPORT_STOCK_TAKE_URL", REPORT_URL . "stock-take/");
  define("REPORT_STOCK_TAKE_WAREHOUSE_URL", REPORT_STOCK_TAKE_URL . "warehouse/");
  define("REPORT_STOCK_TAKE_WAREHOUSE_DETAIL_URL", REPORT_STOCK_TAKE_WAREHOUSE_URL . "detail/");
  define("REPORT_STOCK_TAKE_BRAND_URL", REPORT_STOCK_TAKE_URL . "brand/");
  define("REPORT_STOCK_TAKE_BRAND_DETAIL_URL", REPORT_STOCK_TAKE_BRAND_URL . "detail/");
  define("REPORT_MULTIPLE_WAREHOUSE_URL", REPORT_STOCK_TAKE_URL . "multiple-warehouse/");


  /* Title configurations. */
  define("REPORT_TITLE", "(G) Management Report");

  define("REPORT_HISTORY_LOG_TITLE", "(G1) History Log");

  define("REPORT_MONLTHLY_SALES_TITLE", "(G2) Monthly Sales Report");

  define("REPORT_MEMORANDUM_TITLE", "(G3) Memorandum");

  define("REPORT_SALES_PERFORMANCE_TITLE", "(G4) Sales Performance");
  define("REPORT_SALES_PERFORMANCE_CLIENT_TITLE", "(G4a) Sales Performance By Client");
  define("REPORT_SALES_PERFORMANCE_MODEL_TITLE", "(G4b) Sales Performance By Model");

  define("REPORT_STOCK_TAKE_TITLE", "(G5) Stock Take");
  define("REPORT_STOCK_TAKE_WAREHOUSE_TITLE", "(G5a) Stock Take Summary By Location By Brand");
  define("REPORT_STOCK_TAKE_WAREHOUSE_DETAIL_TITLE", "(G5b) Stock Take Detail By Location By Brand");
  define("REPORT_STOCK_TAKE_BRAND_TITLE", "(G5c) Brand Exposure Summary");
  define("REPORT_STOCK_TAKE_BRAND_DETAIL_TITLE", "(G5d) Brand Exposure Details");
  define("REPORT_MULTIPLE_WAREHOUSE_TITLE", "(G5e) Multiple Warehouse Items");

  $REPORT_MODULE = array(
    REPORT_HISTORY_LOG_TITLE => REPORT_HISTORY_LOG_URL,
    REPORT_MONLTHLY_SALES_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/enquiry/monthly_sales.php",
    REPORT_MEMORANDUM_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/enquiry/memorandum.php",
    REPORT_SALES_PERFORMANCE_TITLE => array(
      REPORT_SALES_PERFORMANCE_CLIENT_TITLE => REPORT_SALES_PERFORMANCE_CLIENT_URL,
      REPORT_SALES_PERFORMANCE_MODEL_TITLE => REPORT_SALES_PERFORMANCE_MODEL_URL
    ),
    REPORT_STOCK_TAKE_TITLE => array(
      REPORT_STOCK_TAKE_WAREHOUSE_TITLE => REPORT_STOCK_TAKE_WAREHOUSE_URL,
      REPORT_STOCK_TAKE_WAREHOUSE_DETAIL_TITLE => REPORT_STOCK_TAKE_WAREHOUSE_DETAIL_URL,
      REPORT_STOCK_TAKE_BRAND_TITLE => REPORT_STOCK_TAKE_BRAND_URL,
      REPORT_STOCK_TAKE_BRAND_DETAIL_TITLE => REPORT_STOCK_TAKE_BRAND_DETAIL_URL,
      REPORT_MULTIPLE_WAREHOUSE_TITLE => REPORT_MULTIPLE_WAREHOUSE_URL
    )
  );
?>
