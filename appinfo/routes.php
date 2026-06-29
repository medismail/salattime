<?php
declare(strict_types=1);

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#settings', 'url' => '/settings', 'verb' => 'GET'],
		['name' => 'page#adjustments', 'url' => '/adjustments', 'verb' => 'GET'],
		['name' => 'page#prayertime', 'url' => '/prayertime', 'verb' => 'GET'],
		['name' => 'page#savesetting', 'url' => '/savesetting', 'verb' => 'GET'],
		['name' => 'page#saveadjustment', 'url' => '/saveadjustment', 'verb' => 'GET'],
		['name' => 'widget#getWidgetData', 'url' => '/api/v1/widget', 'verb' => 'GET'],
		['name' => 'notification#addjob', 'url' => '/notification/addjob', 'verb' => 'POST'],
		['name' => 'notification#removejob', 'url' => '/notification/removejob', 'verb' => 'POST'],
		['name' => 'calendar#addcalendar', 'url' => '/calendar/addcalendar', 'verb' => 'POST'],
		['name' => 'calendar#removecalendar', 'url' => '/calendar/removecalendar', 'verb' => 'POST'],
	],
];
