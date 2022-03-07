<?php
script('salattime', 'script');
style('salattime', 'style');
?>
<script type="text/javascript">
function switchHidden() {
        document.getElementById("div10").hidden = !document.getElementById("div10").hidden;
        //document.getElementById("longitude").hidden = !document.getElementById("longitude").hidden;
        //document.getElementById("elevation").hidden = !document.getElementById("elevation").hidden;
}
function switchHidden1() {
  var x = document.getElementById("div10");
  if (x.style.display === "none") {
    x.style.display = "block";
  } else {
    x.style.display = "none";
  }
}
</script>

<!--div id="app"-->
        <div id="app-navigation">
                <?php print_unescaped($this->inc('navigation/index')); ?>
                <?php print_unescaped($this->inc('settings/index')); ?>
        </div>

        <div id="app-content">
                <div id="app-content-wrapper" class="viewcontainer" style="float:left; margin-left:44px; margin-top:20px">
                            <?php print_unescaped($this->inc('content/settings')); ?>
                </div>
        </div>
<!--/div-->
