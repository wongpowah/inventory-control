<?php include_once "config.php"; ?>

<meta charset="utf-8">
<meta http-equiv="cache-control" content="no-cache" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/css/main.css">
<script>
  window.onload = function() {
    document.querySelector("body").addEventListener("scroll", function(event) {
      if (event.target.matches("input[type=\"number\"]")) {
        event.preventDefault();
      }
    }, true);
  }
</script>
