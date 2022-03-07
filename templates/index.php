<?php
script('salattime', 'script');
style('salattime', 'style');
?>

<style>
#salat {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 90%;
}

#salat td, #salat th {
  border: 1px solid #ddd;
  padding: 6px;
}

#salat tr:nth-child(even){background-color: #f2f2f2;}

#salat tr:hover {background-color: #ddd;}

#salat th {
  padding-top: 6px;
  padding-bottom: 6px;
  text-align: left;
  background-color: #04AA6D;
  color: white;
}
</style>

<!--div id="app"-->
	<div id="app-navigation">
		<?php print_unescaped($this->inc('navigation/index')); ?>
		<?php print_unescaped($this->inc('settings/index')); ?>
	</div>

	<div id="app-content">
		<div id="app-content-wrapper" class="viewcontainer" style="margin-left:44px; margin-top:20px">
			    <?php print_unescaped($this->inc('content/index')); ?>
		</div>
	</div>
<!--/div-->

