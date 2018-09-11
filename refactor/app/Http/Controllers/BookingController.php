<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        $response = null;
        $user_id  = isset($request->get('user_id')) ?: null;

        if ($user_id) {
            $response = $this->repository->getUsersJobs($user_id);
        } elseif (
            $request->__authenticatedUser->user_type === config('ADMIN_ROLE_ID') || 
            $request->__authenticatedUser->user_type === config('SUPERADMIN_ROLE_ID')
        ) {
            $response = $this->repository->getAll($request);
        }

        return response($response);
    }

    /**
     * @param  int      $id
     * @return Response
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')
                                ->find($id);

        return response($job);
    }

    /**
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->store($request->__authenticatedUser, $data);

        return response($response);

    }

    /**
     * @param  int      $id
     * @param  Request  $request
     * @return Response
     */
    public function update($id, Request $request)
    {
        $data  = $request->all();
        $cuser = $request->__authenticatedUser;

        $response = $this->repository->updateJob(
            $id, 
            array_except($data, ['_token', 'submit']), 
            $cuser
        );

        return response($response);
    }

    /**
     * Send/Save email and more details
     * @param  Request  $request
     * @return Response
     */
    public function immediateJobEmail(Request $request)
    {
        $data             = $request->all();
        $adminSenderEmail = config('app.adminemail');
        
        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    /**
     * Get history and more details
     * @param  Request $request
     * @return Response
     */
    public function getHistory(Request $request)
    {
        $response = null;
        $user_id  = isset($request->get('user_id')) ?: null;

        if ($user_id) {
            $response = $this->repository->getUsersJobsHistory($user_id, $request);
        }

        return $response;
    }

    /**
     * Accept jobs and more details
     * @param  Request  $request
     * @return Response
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    /**
     * Accept specified job and more details
     * @param  Request  $request
     * @return Response
     */
    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * Cancel the job and more details
     * @param  Request $request
     * @return Response
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * Ends the job and more details
     * @param  Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->endJob($data);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    /**
     * Get Potential Jobs and more details
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    /**
     * Update the distance record
     * @param  Request  $request
     * @return Response
     */
    public function updateDistanceFeed(Request $request)
    {
        $data = $request->all();

        $distance         = !empty($data['distance']) ?: '';
        $time             = !empty($data['time']) ?: '';
        $jobid            = !empty($data['jobid']) ?: null;
        $session          = !empty($data['session_time']) ?: '';
        $flagged          = 'no';
        $manually_handled = 'no';
        $by_admin         = 'no';
        $admincomment     = !empty($data['admincomment']) ?: '';

        if ($data['flagged'] === 'true') {
            if (empty($data['admincomment'])) {
                return 'Please, add comment';
            }

            $flagged = 'yes';
        }
        
        if ($data['manually_handled'] === 'true') {
            $manually_handled = 'yes';
        }
        
        if ($data['by_admin'] === 'true') {
            $by_admin = 'yes';
        }

        if ($time || $distance) {
            $affectedRows = Distance::where('job_id', '=', $jobid)
                                    ->update(
                                        [
                                            'distance' => $distance, 
                                            'time'     => $time
                                        ]
                                    );
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {
            $affectedRows1 = Job::where('id', '=', $jobid)
                                ->update(
                                    [
                                        'admin_comments'   => $admincomment, 
                                        'flagged'          => $flagged, 
                                        'session_time'     => $session, 
                                        'manually_handled' => $manually_handled, 
                                        'by_admin'         => $by_admin
                                    ]
                                );
        }

        return response('Record updated!');
    }

    /**
     * Reopen repository
     * @param  Request  $request
     * @return Response
     */
    public function reOpen(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->reopen($data);

        return response($response);
    }

    /**
     * Resend Notifications
     * @param  Request $request
     * @return Response
     */
    public function reSendNotifications(Request $request)
    {
        $data = $request->all();

        $job      = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();

        $job      = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        $response = null;

        try {
            $this->repository->sendSMSNotificationToTranslator($job);

            $response = ['success' => 'SMS sent'];
        } catch (\Exception $e) {
            $response = ['success' => $e->getMessage()];
        }

        return response($response);
    }

}
