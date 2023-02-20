<?php
script('salattime', 'script');
script('salattime', 'settings');
style('salattime', 'style');
?>

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
