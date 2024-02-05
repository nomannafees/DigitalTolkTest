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
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $response = null;
        if($user_id = $request->get('user_id')) {

            $response = $this->repository->getUsersJobs($user_id);

        }
        elseif($request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID'))
        {
            $response = $this->repository->getAll($request);
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $data = $request->validate([
             'name' => 'required|string|max:255',
        ]);
     
       $response = $this->repository->store($request->__authenticatedUser, $data);

        return response($response);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $data = $request->only(['field1', 'field2', 'field3']);
        $cuser = $request->__authenticatedUser;
        $response = $this->repository->updateJob($id, $data, $cuser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
        

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if($user_id = $request->get('user_id')) {

            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response($response);
        }

        return response()->json(['error' => 'User ID not provided'], 400);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|numeric',
        ]);
        $user = $request->__authenticatedUser;
        try {
        $response = $this->repository->acceptJob($data, $user);
            return response($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|numeric',
        ]);
    
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($data['job_id'], $user);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|numeric',
            // Add more validation rules as needed
        ]);
    
        $user = $request->__authenticatedUser;
        try {
        $response = $this->repository->cancelJobAjax($data, $user);
        return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|numeric',
        ]);
    
        try {
        $response = $this->repository->endJob($data);
         return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|numeric',
        ]);
        try {
        $response = $this->repository->customerNotCall($data);
        return response()->json($response);
        } catch (\Exception $e) {
            // Handle the exception and return an appropriate error response
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $request->validate([
            // Add validation rules for any expected parameters
        ]);
    
        $user = $request->__authenticatedUser;
        try {
        $response = $this->repository->getPotentialJobs($user);
        return response($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        $jobid = $data['jobid'] ?? null;

    $distanceParameters = ['distance', 'time'];
        foreach ($distanceParameters as $parameter) {
            ${$parameter} = isset($data[$parameter]) ? $data[$parameter] : null;
        }
    
        $jobParameters = ['admin_comments', 'flagged', 'session_time', 'manually_handled', 'by_admin'];
        foreach ($jobParameters as $parameter) {
            ${$parameter} = isset($data[$parameter]) ? $data[$parameter] : null;
        }
    
        if ($data['flagged'] == 'true' && $data['admincomment'] == '') {
            return response('Please, add comment', 400);
        }
    
        if ($time || $distance) {
            $affectedRows = Distance::where('job_id', '=', $jobid)->update(compact($distanceParameters));
        }
    
        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {
            $affectedRows1 = Job::where('id', '=', $jobid)->update(compact($jobParameters));
        }
    
        return response('Record updated!');
    }

    public function reopen(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'required|numeric',
        ]);
        try {
        $response = $this->repository->reopen($data);
        return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->validate([
            'jobid' => 'required|numeric',
        ]);
        try {
        $job = $this->repository->find($data['jobid']);
        if (!$job) {
            return response(['error' => 'Job not found'], 404);
        }
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->validate([
            'jobid' => 'required|numeric',
        ]);
        try {
            $job = $this->repository->find($data['jobid']);
             if (!$job) {
                return response(['error' => 'Job not found'], 404);
            }
            $job_data = $this->repository->jobToData($job);
            $this->repository->sendSMSNotificationToTranslator($job);
    
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

}
