<div>
<input id="checkboxnma" type="checkbox" name="checkboxnma" class="checkbox" <?php if ($_['nma'] !== "0") { echo "checked"; } ?> />
<label for="checkboxnma"> <?php echo $l->t('Hijri Date Auto Adjust'); ?></label><br>
<br>
<?php echo $l->t('Day'); ?>: <input type="text" name="vday" value=<?php echo "\"" . $_['day'] . "\""; ?> <?php if ($_['nma'] !== "0") { echo "disabled"; } ?>><br><br>
<form action="saveadjustment" method="get">
    <?php echo $l->t('Fajr'); ?>: <input type="text" name="Fajr" value=<?php echo "\"" . $_['Fajr'] . "\""; ?>><br>
    <?php echo $l->t('Dhuhr'); ?>: <input type="text" name="Dhuhr" value=<?php echo "\"" . $_['Dhuhr'] . "\""; ?>><br>
    <?php echo $l->t('Asr'); ?>: <input type="text" name="Asr" value=<?php echo "\"" . $_['Asr'] . "\""; ?>><br>
    <?php echo $l->t('Maghrib'); ?>: <input type="text" name="Maghrib" value=<?php echo "\"" . $_['Maghrib'] . "\""; ?>><br>
    <?php echo $l->t('Isha'); ?>: <input type="text" name="Isha" value=<?php echo "\"" . $_['Isha'] . "\""; ?>><br>
    <input type="text" name="day" style="display: none;" value=<?php echo "\"" . $_['day'] . "\""; ?>>
    <input type="text" name="nma" style="display: none;" value=<?php echo "\"" . $_['nma'] . "\""; ?>>
    <input type="submit">
</form>
</div>
