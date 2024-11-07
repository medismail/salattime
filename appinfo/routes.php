<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\SalatTime\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
	   ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
           ['name' => 'page#settings', 'url' => '/settings', 'verb' => 'GET'],
           ['name' => 'page#adjustments', 'url' => '/adjustments', 'verb' => 'GET'],
           ['name' => 'page#prayertime', 'url' => '/prayertime', 'verb' => 'GET'],
           ['name' => 'page#savesetting', 'url' => 'savesetting', 'verb' => 'GET'],
           ['name' => 'page#saveadjustment', 'url' => 'saveadjustment', 'verb' => 'GET'],
           ['name' => 'widget#getWidgetContent', 'url' => '/widget-content', 'verb' => 'GET'],
           ['name' => 'notification#addjob',  'url' => '/notification/addjob',   'verb' => 'POST'],
           ['name' => 'notification#removejob', 'url' => '/notification/removejob', 'verb' => 'POST'],
           ['name' => 'calendar#addcalendar',  'url' => '/calendar/addcalendar',   'verb' => 'POST'],
           ['name' => 'calendar#removecalendar', 'url' => '/calendar/removecalendar', 'verb' => 'POST'],
    ]
];
