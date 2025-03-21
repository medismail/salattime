<div>
    <form name="auto" action="savesetting" method="get">
        <?php echo $l->t('Location address:'); ?> <input type="text" name="address"><br>
        <input type="text" name="latitude" style="display: none;" value<?php echo "\"" . $_['latitude'] . "\""; ?>>
        <input type="text" name="longitude" style="display: none;" value<?php echo "\"" . $_['longitude'] . "\""; ?>>
        <input type="text" name="timezone" style="display: none;" value<?php echo "\"" . $_['timezone'] . "\""; ?>>
        <input type="text" name="elevation" style="display: none;" value<?php echo "\"" . $_['elevation'] . "\""; ?>>
        <input type="text" name="method" style="display: none;" value<?php echo "\"" . $_['method'] . "\""; ?>>
        <input type="text" name="format_12_24" style="display: none;" value=<?php echo "\"" . $_['format_12_24'] . "\""; ?>>
        <input type="submit">
    </form>
    <br>
    <input id="checkbox1224" type="checkbox" name="checkbox1224" class="checkbox" <?php if ($_['format_12_24'] == "24h") {
    	echo "checked";
    } ?> />
    <label for="checkbox1224"> <?php echo $l->t('Use 24 hours format'); ?></label><br>
    <br>
    <button id="btnmtoggle"><?php echo $l->t('Click for manual settings'); ?></button>
    <div id="divmanuel" style="display: none;">
        <?php echo $l->t('Manual settings:'); ?>
        <br>
        <form name="man" action="savesetting" method="get">
            <input type="text" name="address" style="display: none;" value"">
            <?php echo $l->t('Latitude:'); ?> <input type="text" name="latitude" value=<?php echo "\"" . $_['latitude'] . "\""; ?>><br>
            <?php echo $l->t('Longitude:'); ?> <input type="text" name="longitude" value=<?php echo "\"" . $_['longitude'] . "\""; ?>><br>
            <?php echo $l->t('Timezone:'); ?> <input list="timezones" name="timezone" id="timezone" value=<?php echo "\"" . $_['timezone'] . "\""; ?>><br>
            <datalist id="timezones">
                <option value="UTC">
                <option value="+0100">
                <option value="+0200">
                <option value="+0300">
                <option value="+0400">
                <option value="+0430">
                <option value="+0500">
                <option value="+0530">
                <option value="+0545">
                <option value="+0600">
                <option value="+0630">
                <option value="+0700">
                <option value="+0800">
                <option value="+0845">
                <option value="+0900">
                <option value="+0930">
                <option value="+1000">
                <option value="+1030">
                <option value="+1100">
                <option value="+1200">
                <option value="-0100">
                <option value="-0200">
                <option value="-0230">
                <option value="-0300">
                <option value="-0400">
                <option value="-0500">
                <option value="-0600">
                <option value="-0700">
                <option value="-0800">
                <option value="-0930">
                <option value="-1000">
                <option value="-1100">
                <option value="-1200">
            </datalist>
            <?php echo $l->t('Altitude:'); ?> <input type="text" name="elevation" value=<?php echo "\"" . $_['elevation'] . "\""; ?>><br>
            <?php echo $l->t('Calculation method:'); ?> <select name="method" id="method">
                <option value="MWL" <?php if ($_['method'] == 'MWL') {
                	echo("selected");
                }?>><?php echo $l->t('Muslim World League'); ?></option>
                <option value="MAKKAH" <?php if ($_['method'] == 'MAKKAH') {
                	echo("selected");
                }?>><?php echo $l->t('Umm Al-Qura University, Makkah'); ?></option>
                <option value="KARACHI" <?php if ($_['method'] == 'KARACHI') {
                	echo("selected");
                }?>><?php echo $l->t('University of Islamic Sciences, Karachi'); ?></option>
                <option value="ISNA" <?php if ($_['method'] == 'ISNA') {
                	echo("selected");
                }?>><?php echo $l->t('Islamic Society of North America (ISNA)'); ?></option>
                <option value="JAFARI" <?php if ($_['method'] == 'JAFARI') {
                	echo("selected");
                }?>><?php echo $l->t('Shia Ithna-Ashari, Leva Institute, Qum'); ?></option>
                <option value="GULF" <?php if ($_['method'] == 'GULF') {
                	echo("selected");
                }?>><?php echo $l->t('Gulf Region'); ?></option>
                <option value="MOONSIGHTING" <?php if ($_['method'] == 'MOONSIGHTING') {
                	echo("selected");
                }?>><?php echo $l->t('Moonsighting Committee Worldwide (Moonsighting.com)'); ?></option>
                <option value="TURKEY" <?php if ($_['method'] == 'TURKEY') {
                	echo("selected");
                }?>><?php echo $l->t('Diyanet İşleri Başkanlığı, Turkey'); ?></option>
                <option value="TEHRAN" <?php if ($_['method'] == 'TEHRAN') {
                	echo("selected");
                }?>><?php echo $l->t('Institute of Geophysics, University of Tehran'); ?></option>
                <option value="EGYPT" <?php if ($_['method'] == 'EGYPT') {
                	echo("selected");
                }?>><?php echo $l->t('Egyptian General Authority of Survey'); ?></option>
                <option value="QATAR" <?php if ($_['method'] == 'QATAR') {
                	echo("selected");
                }?>><?php echo $l->t('Qatar'); ?></option>
                <option value="KUWAIT" <?php if ($_['method'] == 'KUWAIT') {
                	echo("selected");
                }?>><?php echo $l->t('Kuwait'); ?></option>
                <option value="TUNISIA" <?php if ($_['method'] == 'TUNISIA') {
                	echo("selected");
                }?>><?php echo $l->t('Tunisia'); ?></option>
                <option value="INDONESIA" <?php if ($_['method'] == 'INDONESIA') {
                	echo("selected");
                }?>><?php echo $l->t('Kementerian Agama Republik Indonesia'); ?></option>
                <option value="MOROCCO" <?php if ($_['method'] == 'MOROCCO') {
                	echo("selected");
                }?>><?php echo $l->t('Morocco'); ?></option>
                <option value="JAKIM" <?php if ($_['method'] == 'JAKIM') {
                	echo("selected");
                }?>><?php echo $l->t('Jabatan Kemajuan Islam Malaysia (JAKIM)'); ?></option>
                <option value="JORDAN" <?php if ($_['method'] == 'JORDAN') {
                	echo("selected");
                }?>><?php echo $l->t('Ministry of Awqaf, Islamic Affairs and Holy Places, Jordan'); ?></option>
                <option value="ALGERIA" <?php if ($_['method'] == 'ALGERIA') {
                	echo("selected");
                }?>><?php echo $l->t('Algeria'); ?></option>
                <option value="RUSSIA" <?php if ($_['method'] == 'RUSSIA') {
                	echo("selected");
                }?>><?php echo $l->t('Spiritual Administration of Muslims of Russia'); ?></option>
                <option value="FRANCE" <?php if ($_['method'] == 'FRANCE') {
                	echo("selected");
                }?>><?php echo $l->t('Union of Islamic Organizations of France (UOIF)'); ?></option>
                <option value="PORTUGAL" <?php if ($_['method'] == 'PORTUGAL') {
                	echo("selected");
                }?>><?php echo $l->t('Comunidade Islamica de Lisboa'); ?></option>
                <option value="SINGAPORE" <?php if ($_['method'] == 'SINGAPORE') {
                	echo("selected");
                }?>><?php echo $l->t('Majlis Ugama Islam Singapura, Singapore'); ?></option>
            </select>
            <br>
            <input type="text" name="format_12_24" style="display: none;" value=<?php echo "\"" . $_['format_12_24'] . "\""; ?>>
            <input type="submit">
        </form>
    </div>
</div>
