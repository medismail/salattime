<?php
namespace OCA\SalatTime\Controller;


use OCA\SalatTime\Notificaion\BackgroundJob;
use OCP\AppFramework\Controller;
use OCP\BackgroundJob\IJobList;
use OCP\IRequest;

class NotificaionController extends Controller {

    private IJobList $jobList;

    private $UserId;

    public function __construct(string $appName, IRequest $request, IJobList $jobList, $UserId) {
        parent::__construct($appName, $request);

        $this->jobList = $jobList;
	$this->userId = $UserId;
    }

    public function addJob() {
        $this->jobList->add(BackgroundJob::class, ['uid' => $this->userId]);
    }

    public function removeJob() {
        $this->jobList->remove(BackgroundJob::class, ['uid' => $this->userId]);
    }
}
