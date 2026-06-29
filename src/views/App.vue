<template>
    <NcContent app-name="salattime">
	    <NcAppNavigation>
            <template #header>
			    <div class="salattime-nav-header"><div class="salattime-nav-icon">☾</div><div><h2>{{ t('Salat Time') }}</h2><p>{{ t('Muslim prayer times') }}</p></div></div>
            </template>
            <template #list>
                <NcAppNavigationItem
                    v-for="item in navigation"
                    :key="item.view"
                    :name="item.label"
                    :href="item.href"
                    :active="view === item.view" />
            </template>
			<NcAppNavigationSettings :name="t('Settings')">
				<NcCheckboxRadioSwitch v-model="status.notification" type="switch" @update:model-value="toggleStatus('notification')">
					{{ t('Enable notification') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch v-model="status.calendar" type="switch" @update:model-value="toggleStatus('calendar')">
					{{ t('Enable Calendar') }}
				</NcCheckboxRadioSwitch>
			</NcAppNavigationSettings>
        </NcAppNavigation>
		<div id="app-content"><div id="app-content-wrapper" class="salattime-content">
			<section v-if="view === 'overview'" class="salattime-page">
				<div class="salattime-hero"><div><p class="salattime-kicker">{{ t('Today is') }}</p><h1>{{ state.Hijri || t('Hijri Date') }}</h1><p>{{ t('City') }}: {{ state.City || t('Unknown city') }}</p></div><div class="salattime-next-card"><span>{{ t('Next') }}</span><strong>{{ t(state.Salat || 'Salat') }}</strong><small>{{ t('after') }} </small><strong>{{ state.Remain || '--:--' }}</strong></div></div>
				<div v-if="state.SpecialDay" class="salattime-special-day">✦ {{ state.SpecialDay }}</div>
				<div class="salattime-grid">
					<section class="salattime-card"><h2>{{ t('Prayer Times') }}</h2><div class="salattime-prayer-list"><div v-for="p in todayPrayers" :key="p.key" class="salattime-prayer-row" :class="{ active: state.Salat === p.key }"><span>{{ p.label }}</span><strong>{{ p.time || '--:--' }}</strong></div><div v-if="state.Jumaa" class="salattime-prayer-row"><span>{{ t("Juma'a") }}</span><strong>{{ state.Dhuhr }}</strong></div></div></section>
					<section class="salattime-card"><h2>{{ t('Day Information') }}</h2><dl class="salattime-facts"><template v-if="state.Imsak"><dt>{{ t('Imsak') }}</dt><dd>{{ state.Imsak }}</dd></template><dt>{{ t('Sunrise') }}</dt><dd>{{ state.Sunrise }}</dd><dt>{{ t('Sunset') }}</dt><dd>{{ state.Sunset }}</dd><dt>{{ t('Day length') }}</dt><dd>{{ state.DayLength }}</dd><template v-if="state.Moonrise"><dt>{{ t('Moonrise') }}</dt><dd>{{ state.Moonrise }}</dd></template><template v-if="state.Moonset"><dt>{{ t('Moonset') }}</dt><dd>{{ state.Moonset }}</dd></template></dl></section>
					<section class="salattime-card salattime-compass-card"><h2>{{ t('Qibla direction') }}</h2><div class="salattime-compass" :style="{ backgroundImage: 'url(' + imageUrl('pusula.png') + ')' }"><img :src="imageUrl('kiblaibra.png')" :style="rotate(state.QiblaDirection)" alt=""><img v-if="Number(state.SunAltitude) > 0" :src="imageUrl('sunibra.png')" :style="rotate(state.SunAzimuth)" alt=""><img v-if="Number(state.MoonAltitude) > 0 && Number(state.IlluminatedFraction) > 1" :src="imageUrl('moonibra.png')" :style="rotate(state.MoonAzimuth)" alt=""></div><p>{{ t('Azimuth') }}: <strong>{{ state.QiblaDirection }}°</strong></p></section>
					<section class="salattime-card"><h2>{{ t('Moon and Sun Information') }}</h2><dl class="salattime-facts"><dt>{{ t('Moon Phase') }}</dt><dd>{{ state.MoonPhase }}</dd><dt>{{ t("Moon's illuminated fraction") }}</dt><dd>{{ state.IlluminatedFraction }}%</dd><dt>{{ t('New moon') }}</dt><dd>{{ state.NewMoon }}</dd><dt>{{ t('Next new moon') }}</dt><dd>{{ state.NextNewMoon }}</dd><dt>{{ t('Sun Position') }}</dt><dd>{{ state.SunAzimuth }}° / {{ state.SunAltitude }}°</dd><dt>{{ t('Moon Position') }}</dt><dd>{{ state.MoonAzimuth }}° / {{ state.MoonAltitude }}°</dd></dl></section>
				</div>
			</section>
			<section v-else-if="view === 'prayers'" class="salattime-page"><header class="salattime-page-title"><p class="salattime-kicker">{{ t('Prayer Times') }}</p><h1>{{ t('Prayer Times') }}</h1></header><section class="salattime-card salattime-table-card"><table class="salattime-table"><thead><tr><th>{{ t('Day') }}</th><th v-if="hasImsak">{{ t('Imsak') }}</th><th v-for="p in tablePrayers" :key="p">{{ t(p) }}</th></tr></thead><tbody><tr v-for="row in prayerRows" :key="row.date" :class="{ today: row.isToday }"><td><strong>{{ row.dayName }}</strong><span>{{ row.hijriYear }}-{{ row.hijriMonth }}-{{ row.hijriDay }}</span><small v-if="row.specialDay">{{ row.specialDay }}</small></td><td v-if="hasImsak">{{ row.times.Imsak }}</td><td v-for="p in tablePrayers" :key="p">{{ row.times[p] }}</td></tr></tbody></table></section></section>
			<section v-else-if="view === 'settings'" class="salattime-page"><header class="salattime-page-title"><p class="salattime-kicker">{{ t('Settings') }}</p><h1>{{ t('Location') }}</h1><p v-if="state.city">{{ t('Current location') }}: {{ state.city }}</p></header><form class="salattime-card salattime-form" @submit.prevent="saveSettings"><div class="salattime-form-grid"><label><span>{{ t('Location address:') }}</span><input v-model="settingsForm.address" type="text" :placeholder="state.city || 'Makkah'"></label><label v-for="f in settingsFields" :key="f.key"><span>{{ f.label }}</span><input v-model="settingsForm[f.key]" :list="f.key === 'timezone' ? 'salattime-timezones' : null" type="text"></label><label><span>{{ t('Calculation method:') }}</span><select v-model="settingsForm.method"><option v-for="m in methods" :key="m.value" :value="m.value">{{ m.label }}</option></select></label></div><datalist id="salattime-timezones"><option v-for="z in timezones" :key="z" :value="z"></option></datalist><NcCheckboxRadioSwitch v-model="use24Hours" type="switch">{{ t('Use 24 hours format') }}</NcCheckboxRadioSwitch><div class="salattime-form-actions"><button type="button" class="button secondary" @click="getCurrentLocation">{{ t('Get current location') }}</button><button type="submit" class="button primary">{{ t('Save') }}</button></div></form></section>
			<section v-else-if="view === 'adjustments'" class="salattime-page"><header class="salattime-page-title"><p class="salattime-kicker">{{ t('Adjustments') }}</p><h1>{{ t('Hijri Date') }}</h1></header><form class="salattime-card salattime-form" @submit.prevent="saveAdjustments"><NcCheckboxRadioSwitch v-model="autoHijri" type="switch" @update:model-value="adjustmentForm.NMA = autoHijri ? '15' : '0'">{{ t('Hijri Date Auto Adjust') }}</NcCheckboxRadioSwitch><div class="salattime-form-grid"><label><span>{{ t('Day') }}</span><input v-model="adjustmentForm.Day" type="number" :disabled="autoHijri"></label><label v-for="f in adjustmentFields" :key="f"><span>{{ t(f) }}</span><input v-model="adjustmentForm[f]" type="number"></label></div><div class="salattime-form-actions"><button type="submit" class="button primary">{{ t('Save') }}</button></div></form></section>
		</div></div>
    </NcContent>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate } from '@nextcloud/l10n'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationSettings from '@nextcloud/vue/components/NcAppNavigationSettings'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcContent from '@nextcloud/vue/components/NcContent'

export default {
	name: 'SalatTimeApp',
	components: {
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationSettings,
		NcCheckboxRadioSwitch,
		NcContent,
	},
	props: { initialView: { type: String, default: 'overview' }, initialState: { type: Object, default: () => ({}) } },
	data() {
		const s = this.initialState || {}
		return {
			view: this.initialView,
			state: s,
			status: { notification: s.notification === true || s.notification === 'true', calendar: s.calendar === true || s.calendar === 'true' },
			settingsForm: { address: '', latitude: s.latitude || '21.3890824', longitude: s.longitude || '39.8579118', timezone: s.timezone || '+0300', elevation: s.elevation || '0', method: s.method || 'MWL', format_12_24: s.format_12_24 || '12h' },
			adjustmentForm: { Day: s.Day || '0', Fajr: s.Fajr || '0', Dhuhr: s.Dhuhr || '0', Asr: s.Asr || '0', Maghrib: s.Maghrib || '0', Isha: s.Isha || '0', NMA: s.NMA || '0' },
			autoHijri: String(s.NMA || '0') !== '0',
			timezones: ['UTC', '+0100', '+0200', '+0300', '+0400', '+0430', '+0500', '+0530', '+0545', '+0600', '+0630', '+0700', '+0800', '+0845', '+0900', '+0930', '+1000', '+1030', '+1100', '+1200', '-0100', '-0200', '-0230', '-0300', '-0400', '-0500', '-0600', '-0700', '-0800', '-0930', '-1000', '-1100', '-1200'],
			tablePrayers: ['Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'],
			adjustmentFields: ['Fajr', 'Dhuhr', 'Asr', 'Maghrib', 'Isha'],
		}
	},
	computed: {
		navigation() { return [{ view: 'overview', label: this.t('Today'), href: generateUrl('/apps/salattime/') }, { view: 'prayers', label: this.t('Prayer Times'), href: generateUrl('/apps/salattime/prayertime') }, { view: 'adjustments', label: this.t('Adjustments'), href: generateUrl('/apps/salattime/adjustments') }, { view: 'settings', label: this.t('Settings'), href: generateUrl('/apps/salattime/settings') }] },
		methods() { return ['MWL', 'MAKKAH', 'KARACHI', 'ISNA', 'JAFARI', 'GULF', 'MOONSIGHTING', 'TURKEY', 'TEHRAN', 'EGYPT', 'QATAR', 'KUWAIT', 'TUNISIA', 'INDONESIA', 'MOROCCO', 'JAKIM', 'JORDAN', 'ALGERIA', 'RUSSIA', 'FRANCE', 'PORTUGAL', 'SINGAPORE'].map((value) => ({ value, label: this.t(value) })) },
		settingsFields() { return [{ key: 'latitude', label: this.t('Latitude:') }, { key: 'longitude', label: this.t('Longitude:') }, { key: 'timezone', label: this.t('Timezone:') }, { key: 'elevation', label: this.t('Altitude:') }] },
		todayPrayers() { const rows = this.tablePrayers.filter((p) => p !== 'Sunrise').map((key) => ({ key, label: this.t(key), time: this.state[key] })); if (this.state.Imsak) rows.unshift({ key: 'Imsak', label: this.t('Imsak'), time: this.state.Imsak }); return rows },
		prayerRows() { return Array.isArray(this.state.prayers) ? this.state.prayers : [] },
		hasImsak() { return this.prayerRows.some((row) => row.times && row.times.Imsak) },
		use24Hours: { get() { return this.settingsForm.format_12_24 === '24h' }, set(v) { this.settingsForm.format_12_24 = v ? '24h' : '12h' } },
	},
	methods: {
		t(text) { return translate('salattime', text) },
		imageUrl(file) { return String(this.state.rurl || '') + file },
		rotate(deg) { return { transform: 'rotate(' + Number(deg || 0) + 'deg)' } },
		saveSettings() { window.location.href = generateUrl('/apps/salattime/savesetting') + '?' + new URLSearchParams(this.settingsForm).toString() },
		saveAdjustments() { const p = Object.assign({}, this.adjustmentForm, { NMA: this.autoHijri ? this.adjustmentForm.NMA || '15' : '0' }); window.location.href = generateUrl('/apps/salattime/saveadjustment') + '?' + new URLSearchParams(p).toString() },
		getCurrentLocation() { if (!navigator.geolocation) return; navigator.geolocation.getCurrentPosition((pos) => { this.settingsForm.latitude = String(pos.coords.latitude); this.settingsForm.longitude = String(pos.coords.longitude); this.settingsForm.address = '' }, (err) => console.debug('Could not get current location', err), { enableHighAccuracy: false, timeout: 10000, maximumAge: 0 }) },
		async toggleStatus(type) { const enabled = this.status[type]; const url = type === 'notification' ? (enabled ? '/apps/salattime/notification/addjob' : '/apps/salattime/notification/removejob') : (enabled ? '/apps/salattime/calendar/addcalendar' : '/apps/salattime/calendar/removecalendar'); try { await axios.post(generateUrl(url)) } catch (error) { this.status[type] = !enabled; console.error('Could not update Salat Time status', error) } },
	},
}
</script>
