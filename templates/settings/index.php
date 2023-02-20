<div id="app-settings">
	<div id="app-settings-header">
		<button class="settings-button"
				data-apps-slide-toggle="#app-settings-content"
		></button>
	</div>
	<div id="app-settings-content">
		<!-- Your settings in here -->
                <input type="checkbox" id="salatnotification" class="checkbox" name="salatnotification" <?php if ($_['notification'] == 'true') echo "checked"; ?> >
                <label for="salatnotification"> <?php echo $l->t('Enable notification'); ?> </label>
	</div>
</div>
